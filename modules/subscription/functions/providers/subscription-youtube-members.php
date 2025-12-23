<?php
// File: modules/subscription/functions/providers/subscription-youtube-members.php

if (!defined('ABSPATH')) exit;

/**
 * YouTube paid memberships (JOIN) sync helpers.
 *
 * Endpoints used:
 * - channels.list?mine=true  (sanity check token belongs to channel owner)
 * - membershipsLevels.list?mine=true (best-effort preflight; can be 403 on non-allowlisted projects)
 * - members.list (the real paid members list)
 *
 * Tokens expected in provider settings:
 * - creator_access_token
 * - creator_refresh_token
 * - creator_token_expires_at (unix timestamp)
 */

function admin_lab_youtube_members_log($provider_slug, $msg, $debug_log = false) {
    if ($debug_log && function_exists('admin_lab_log_custom')) {
        $line = "[YOUTUBE MEMBERS] {$provider_slug} {$msg}";
        admin_lab_log_custom($line, 'subscription-sync.log');
    }
}

/**
 * tokeninfo debug: shows scope + audience + exp.
 * Use only in debug mode.
 */
function admin_lab_youtube_tokeninfo($token) {
    $url = 'https://oauth2.googleapis.com/tokeninfo?access_token=' . rawurlencode($token);
    $res = wp_remote_get($url, ['timeout' => 20]);

    if (is_wp_error($res)) {
        return new WP_Error('youtube_tokeninfo_http', $res->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $raw  = (string) wp_remote_retrieve_body($res);
    $data = json_decode($raw, true);

    if ($code < 200 || $code >= 300 || !is_array($data)) {
        return new WP_Error('youtube_tokeninfo_api', "tokeninfo error ({$code}): {$raw}");
    }

    return [
        'aud'   => (string)($data['aud'] ?? ''),
        'scope' => (string)($data['scope'] ?? ''),
        'exp'   => (string)($data['exp'] ?? ''),
        'email' => (string)($data['email'] ?? ''),
    ];
}

/**
 * Ensure valid access token (refresh if needed).
 *
 * @return string|WP_Error
 */
function admin_lab_youtube_get_valid_creator_access_token($provider_slug) {
    $access_token  = (string) admin_lab_get_provider_setting($provider_slug, 'creator_access_token', '');
    $refresh_token = (string) admin_lab_get_provider_setting($provider_slug, 'creator_refresh_token', '');
    $expires_at    = (int) admin_lab_get_provider_setting($provider_slug, 'creator_token_expires_at', 0);

    // If not expiring soon, keep it
    if ($access_token && $expires_at > 0 && time() < ($expires_at - 60)) {
        return $access_token;
    }

    // If no refresh token, cannot refresh
    if (!$refresh_token) {
        if ($access_token) return $access_token;
        return new WP_Error('youtube_no_token', 'YouTube: missing access/refresh token. Re-authenticate OAuth.');
    }

    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider || empty($provider['client_id']) || empty($provider['client_secret'])) {
        return new WP_Error('youtube_missing_client', "YouTube: Client ID/Secret missing for provider {$provider_slug}.");
    }

    $client_id = (string) $provider['client_id'];
    $client_secret = (string) $provider['client_secret'];

    // Decrypt client_secret if encrypted
    if (function_exists('admin_lab_decrypt_data')) {
        $dec = admin_lab_decrypt_data($client_secret);
        if ($dec && $dec !== $client_secret) {
            $client_secret = $dec;
        }
    }

    $resp = wp_remote_post('https://oauth2.googleapis.com/token', [
        'timeout' => 20,
        'body' => [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
        ],
    ]);

    if (is_wp_error($resp)) {
        return new WP_Error('youtube_refresh_error', 'YouTube refresh token error: ' . $resp->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($resp);
    $body = (string) wp_remote_retrieve_body($resp);
    $data = json_decode($body, true);

    if ($code < 200 || $code >= 300 || empty($data['access_token'])) {
        return new WP_Error('youtube_refresh_bad_response', "YouTube refresh failed ({$code}): {$body}");
    }

    $new_access = (string) $data['access_token'];
    $expires_in = (int) ($data['expires_in'] ?? 0);

    admin_lab_set_provider_setting($provider_slug, 'creator_access_token', $new_access);
    if ($expires_in > 0) {
        admin_lab_set_provider_setting($provider_slug, 'creator_token_expires_at', time() + max(0, $expires_in - 60));
    }

    return $new_access;
}

/**
 * channels.list?mine=true
 * @return array|WP_Error ['id'=>..., 'title'=>...]
 */
function admin_lab_youtube_get_authed_channel_sync($token) {
    $url = 'https://www.googleapis.com/youtube/v3/channels?' . http_build_query([
        'part' => 'snippet',
        'mine' => 'true',
        'maxResults' => 1,
    ], '', '&', PHP_QUERY_RFC3986);

    $res = wp_remote_get($url, [
        'timeout' => 25,
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($res)) {
        return new WP_Error('youtube_channels_http', $res->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $raw  = (string) wp_remote_retrieve_body($res);
    $json = json_decode($raw, true);

    if ($code < 200 || $code >= 300) {
        return new WP_Error('youtube_channels_api', "channels.list error ({$code}): {$raw}");
    }

    $item = $json['items'][0] ?? null;
    if (!is_array($item) || empty($item['id'])) {
        return new WP_Error('youtube_channels_empty', 'channels.list returned no channel id (token not linked to a channel owner?)');
    }

    return [
        'id'    => (string) $item['id'],
        'title' => (string) ($item['snippet']['title'] ?? ''),
    ];
}

/**
 * membershipsLevels.list?mine=true (best-effort preflight)
 * @return array|WP_Error ['levels_count'=>int,'http'=>int]
 */
function admin_lab_youtube_check_membership_levels($token) {
    $url = 'https://www.googleapis.com/youtube/v3/membershipsLevels?' . http_build_query([
        'part' => 'id,snippet',
        'mine' => 'true',
        'maxResults' => 50,
    ], '', '&', PHP_QUERY_RFC3986);

    $res = wp_remote_get($url, [
        'timeout' => 25,
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($res)) {
        return new WP_Error('youtube_levels_http', $res->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $raw  = (string) wp_remote_retrieve_body($res);
    $json = json_decode($raw, true);

    if ($code < 200 || $code >= 300) {
        return new WP_Error('youtube_levels_api', "YouTube membershipsLevels API error ({$code}): {$raw}");
    }

    $items = (!empty($json['items']) && is_array($json['items'])) ? $json['items'] : [];

    return [
        'levels_count' => count($items),
        'http' => $code,
    ];
}

/**
 * Extract a nice {reason,message} from YouTube error payload.
 */
function admin_lab_youtube_parse_error($raw) {
    $json = json_decode((string)$raw, true);
    $reason = '';
    $message = '';

    if (is_array($json) && !empty($json['error'])) {
        $message = (string)($json['error']['message'] ?? '');
        if (!empty($json['error']['errors'][0]['reason'])) {
            $reason = (string)$json['error']['errors'][0]['reason'];
        }
    }

    return [
        'reason' => $reason,
        'message' => $message,
    ];
}

/**
 * Fetch paid members (JOIN).
 *
 * @return array|WP_Error
 */
function admin_lab_youtube_fetch_paid_members($provider_slug, $debug_log = false, $expected_creator_channel_id = '') {
    $token = admin_lab_youtube_get_valid_creator_access_token($provider_slug);
    if (is_wp_error($token)) return $token;

    // Debug tokeninfo
    if ($debug_log) {
        $ti = admin_lab_youtube_tokeninfo($token);
        if (is_wp_error($ti)) {
            admin_lab_youtube_members_log($provider_slug, 'tokeninfo_error=' . $ti->get_error_message(), true);
        } else {
            admin_lab_youtube_members_log(
                $provider_slug,
                'tokeninfo aud=' . ($ti['aud'] ?: 'n/a') . ' exp=' . ($ti['exp'] ?: 'n/a') . ' scope=' . ($ti['scope'] ?: 'n/a'),
                true
            );
        }
    }

    // Must be channel owner
    $authed = admin_lab_youtube_get_authed_channel_sync($token);
    if (is_wp_error($authed)) {
        admin_lab_youtube_members_log($provider_slug, 'channels.list(mine=true) ERROR=' . $authed->get_error_message(), $debug_log);
        return new WP_Error(
            'youtube_channels_mine_failed',
            'YouTube: channels.list (mine=true) failed. Reconnect OAuth with the channel owner account and ensure scopes are granted.'
        );
    }

    $authed_channel_id = (string) $authed['id'];
    $authed_title = (string) $authed['title'];

    admin_lab_youtube_members_log(
        $provider_slug,
        "authed_channel_id={$authed_channel_id} title=" . ($authed_title ?: 'n/a') . " expected=" . ($expected_creator_channel_id ?: 'n/a'),
        $debug_log
    );

    if ($expected_creator_channel_id && $expected_creator_channel_id !== $authed_channel_id) {
        return new WP_Error(
            'youtube_channel_mismatch',
            "YouTube OAuth token is connected to channel {$authed_channel_id}, but this provider expects {$expected_creator_channel_id}. Reconnect OAuth with the correct YouTube channel."
        );
    }

    // Best-effort preflight: membershipsLevels
    $levels = admin_lab_youtube_check_membership_levels($token);
    if (is_wp_error($levels)) {
        // DO NOT fail hard here, because many projects get 403 on this endpoint too.
        admin_lab_youtube_members_log($provider_slug, 'membershipsLevels WARNING (ignored): ' . $levels->get_error_message(), $debug_log);
    } else {
        admin_lab_youtube_members_log($provider_slug, 'membership_levels_count=' . (int)$levels['levels_count'], $debug_log);
        // If you want: treat 0 levels as "no memberships enabled"
        if ((int)$levels['levels_count'] === 0) {
            return new WP_Error(
                'youtube_memberships_not_enabled',
                'YouTube: channel memberships appear disabled/unavailable (0 membership levels).'
            );
        }
    }

    // members.list
    $all = [];
    $pageToken = '';
    $loop_guard = 0;

    do {
        $loop_guard++;
        if ($loop_guard > 50) {
            return new WP_Error('youtube_members_pagination', 'YouTube members pagination loop guard triggered.');
        }

        $params = [
            'part' => 'snippet',
            'mode' => 'all_current',
            'maxResults' => 100,
        ];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $url = 'https://www.googleapis.com/youtube/v3/members?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $res = wp_remote_get($url, [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($res)) {
            return new WP_Error('youtube_members_http', 'YouTube members HTTP error: ' . $res->get_error_message());
        }

        $http = (int) wp_remote_retrieve_response_code($res);
        $raw  = (string) wp_remote_retrieve_body($res);
        $json = json_decode($raw, true);

        $count_items = (!empty($json['items']) && is_array($json['items'])) ? count($json['items']) : 0;
        $next = (string) ($json['nextPageToken'] ?? '');

        admin_lab_youtube_members_log($provider_slug, "members.list HTTP={$http} items={$count_items} next=" . ($next ? 'yes' : 'no'), $debug_log);

        if ($http < 200 || $http >= 300) {
            $e = admin_lab_youtube_parse_error($raw);
            $reason = $e['reason'] ?: 'unknown';
            $message = $e['message'] ?: 'unknown';

            // This is the key case you have
            if ($http === 403) {
                return new WP_Error(
                    'youtube_members_forbidden',
                    "YouTube members API error (403) reason={$reason} message={$message}: {$raw}\n" .
                    '(403: forbidden; this endpoint may require extra access/allowlist for your Google project. The official doc mentions requesting access.)'
                );
            }

            if ($http === 401) {
                return new WP_Error('youtube_members_unauthorized', "YouTube members API error (401): {$raw} (token invalid/expired; reconnect OAuth)");
            }

            return new WP_Error('youtube_members_api', "YouTube members API error ({$http}): {$raw}");
        }

        $items = !empty($json['items']) && is_array($json['items']) ? $json['items'] : [];

        foreach ($items as $it) {
            $sn = $it['snippet'] ?? [];
            $md = $sn['memberDetails'] ?? [];
            $ms = $sn['membershipsDetails'] ?? [];

            $highest_level_id   = (string) ($ms['highestAccessibleLevel'] ?? '');
            $highest_level_name = (string) ($ms['highestAccessibleLevelDisplayName'] ?? '');

            $duration = $ms['membershipsDuration'] ?? [];
            $member_since = (string) ($duration['memberSince'] ?? '');
            $total_months = (int) ($duration['memberTotalDurationMonths'] ?? 0);

            $at_levels = [];
            if (!empty($ms['membershipsDurationAtLevels']) && is_array($ms['membershipsDurationAtLevels'])) {
                $at_levels = $ms['membershipsDurationAtLevels'];
            } elseif (!empty($ms['membershipsDurationAtLevel']) && is_array($ms['membershipsDurationAtLevel'])) {
                $at_levels = $ms['membershipsDurationAtLevel'];
            }

            if ((!$member_since || !$total_months) && !empty($at_levels[0]) && is_array($at_levels[0])) {
                if (!$member_since && !empty($at_levels[0]['memberSince'])) {
                    $member_since = (string) $at_levels[0]['memberSince'];
                }
                if (!$total_months && isset($at_levels[0]['memberTotalDurationMonths'])) {
                    $total_months = (int) $at_levels[0]['memberTotalDurationMonths'];
                }
            }

            $external_user_id  = (string) ($md['channelId'] ?? '');
            $external_username = (string) ($md['displayName'] ?? '');

            // Fallback deterministic ID if channelId missing
            if (!$external_user_id) {
                $seed = ($external_username ?: 'unknown') . '|' . ($highest_level_id ?: 'member') . '|' . ($member_since ?: 'na');
                $external_user_id = 'yt_anon_' . substr(sha1($seed), 0, 16);
            }

            $level_slug = $highest_level_id ?: 'member';

            $all[] = [
                'external_user_id'  => $external_user_id,
                'external_username' => $external_username,
                'level_slug'        => $level_slug,
                'level_name'        => $highest_level_name,
                'member_since'      => $member_since,
                'total_months'      => $total_months,
            ];
        }

        $pageToken = $next;

    } while ($pageToken);

    return $all; // 0 members is OK
}

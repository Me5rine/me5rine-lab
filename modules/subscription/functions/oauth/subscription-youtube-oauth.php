<?php
// File: modules/subscription/functions/oauth/subscription-youtube-oauth.php

if (!defined('ABSPATH')) exit;

/**
 * YouTube OAuth (Creator) for memberships / members.list
 *
 * Goals:
 * - Single redirect_uri for ALL YouTube providers (no per-channel redirect URI to register)
 * - Stateless signed state (HMAC) carries provider slug
 * - START remains per-provider (to pick correct client_id)
 * - CALLBACK is unique: action=admin_lab_youtube_oauth_callback
 * - Priority 1 to beat generic oauth handler
 */

function admin_lab_youtube_oauth_log($msg, $debug_log = false) {
    if ($debug_log && function_exists('admin_lab_log_custom')) {
        $line = '[YOUTUBE OAUTH] ' . $msg;
        admin_lab_log_custom($line, 'subscription-sync.log');
    }
}

/* -------------------------------------------------------------------------- */
/*  Public base URL                                                           */
/* -------------------------------------------------------------------------- */

/**
 * Returns a "public" base URL for OAuth flows.
 * Priority:
 * 1) provider settings: public_base_url
 * 2) site_url() fallback
 */
function admin_lab_youtube_get_public_base_url($provider_slug) {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if ($provider) {
        $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
        $public_base = isset($settings['public_base_url']) ? trim((string) $settings['public_base_url']) : '';
        if ($public_base) {
            return rtrim($public_base, '/');
        }
    }
    return rtrim(site_url(), '/');
}

/**
 * Redirect URI (MUST match exactly Google Cloud Console).
 * Single callback for all YouTube providers.
 *
 * Example:
 * https://YOUR_PUBLIC_HOST/wp-admin/admin-post.php?action=admin_lab_youtube_oauth_callback
 */
function admin_lab_youtube_redirect_uri($provider_slug) {
    $base = admin_lab_youtube_get_public_base_url($provider_slug);
    return $base . '/wp-admin/admin-post.php?action=admin_lab_youtube_oauth_callback';
}

/* -------------------------------------------------------------------------- */
/*  Stateless signed state                                                    */
/* -------------------------------------------------------------------------- */

function admin_lab_youtube_state_secret() {
    return wp_salt('admin_lab_youtube_oauth_state');
}

function admin_lab_youtube_base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function admin_lab_youtube_base64url_decode($data) {
    $b64 = strtr($data, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad) $b64 .= str_repeat('=', 4 - $pad);
    return base64_decode($b64);
}

/**
 * Build signed state.
 * payload: { p: provider_slug, t: timestamp, r: random }
 * state: base64url(json) . '_' . hmac
 */
function admin_lab_youtube_build_state($provider_slug) {
    $payload = [
        'p' => (string) $provider_slug,
        't' => time(),
        'r' => wp_generate_password(12, false, false),
    ];

    $json = wp_json_encode($payload);
    $b64  = admin_lab_youtube_base64url_encode($json);
    $sig  = hash_hmac('sha256', $b64, admin_lab_youtube_state_secret());

    return $b64 . '_' . $sig;
}

/**
 * Verify signed state and return payload or WP_Error.
 * If $expected_provider_slug is empty, provider mismatch check is skipped.
 */
function admin_lab_youtube_verify_state($state, $expected_provider_slug = '', $ttl_seconds = 600) {
    $state = (string) $state;

    // Accept '_' only (current). If you want compat, you can also accept '.'
    $separator = (strpos($state, '_') !== false) ? '_' : (strpos($state, '.') !== false ? '.' : null);
    if (!$state || $separator === null) {
        return new WP_Error('youtube_state_format', 'OAuth state invalid (bad format).');
    }

    list($b64, $sig) = explode($separator, $state, 2);
    if (!$b64 || !$sig) {
        return new WP_Error('youtube_state_format', 'OAuth state invalid (bad format).');
    }

    $calc = hash_hmac('sha256', $b64, admin_lab_youtube_state_secret());
    if (!hash_equals($calc, $sig)) {
        return new WP_Error('youtube_state_sig', 'OAuth state invalid (signature mismatch).');
    }

    $json = admin_lab_youtube_base64url_decode($b64);
    $payload = json_decode((string) $json, true);
    if (!is_array($payload)) {
        return new WP_Error('youtube_state_payload', 'OAuth state invalid (payload decode failed).');
    }

    $p = isset($payload['p']) ? (string) $payload['p'] : '';
    $t = isset($payload['t']) ? (int) $payload['t'] : 0;

    if (!$p || !$t) {
        return new WP_Error('youtube_state_payload', 'OAuth state invalid (payload missing fields).');
    }

    if ($expected_provider_slug !== '' && $p !== (string) $expected_provider_slug) {
        return new WP_Error('youtube_state_provider', 'OAuth state invalid (provider mismatch).');
    }

    $age = time() - $t;
    if ($age < 0 || $age > (int) $ttl_seconds) {
        return new WP_Error('youtube_state_expired', 'OAuth state expired (restart OAuth).');
    }

    return $payload;
}

/* -------------------------------------------------------------------------- */
/*  Base START handler                                                        */
/* -------------------------------------------------------------------------- */

/**
 * START URL:
 * /wp-admin/admin-post.php?action=admin_lab_youtube_oauth_start&provider=youtube_xxx
 */
add_action('admin_post_admin_lab_youtube_oauth_start', function () {

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $provider_slug = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
    if (!$provider_slug) {
        wp_die('YouTube OAuth error: provider missing in start URL.');
    }

    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider || empty($provider['client_id'])) {
        wp_die('YouTube Client ID missing for provider: ' . esc_html($provider_slug));
    }

    $client_id = (string) $provider['client_id'];

    $redirect_uri = admin_lab_youtube_redirect_uri($provider_slug);
    $state = admin_lab_youtube_build_state($provider_slug);

    // Check if debug logging is enabled for this provider
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $debug_log = !empty($settings['debug_log']);
    admin_lab_youtube_oauth_log("START: provider={$provider_slug} redirect_uri={$redirect_uri} host=" . ($_SERVER['HTTP_HOST'] ?? ''), $debug_log);

    $params = [
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'scope'         => implode(' ', [
            'https://www.googleapis.com/auth/youtube.channel-memberships.creator',
            'https://www.googleapis.com/auth/youtube.readonly',
        ]),
        'access_type'   => 'offline',
        'prompt'        => 'consent',
        'state'         => $state,
    ];

    $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    wp_redirect($url);
    exit;

}, 1); // priority 1 so generic oauth handler can't hijack

/* -------------------------------------------------------------------------- */
/*  Base CALLBACK handler (UNIQUE)                                             */
/* -------------------------------------------------------------------------- */

/**
 * CALLBACK URL (registered in Google):
 * /wp-admin/admin-post.php?action=admin_lab_youtube_oauth_callback
 *
 * Provider is derived from signed state payload.
 */
add_action('admin_post_admin_lab_youtube_oauth_callback', 'admin_lab_youtube_oauth_callback_handler', 1);
add_action('admin_post_nopriv_admin_lab_youtube_oauth_callback', 'admin_lab_youtube_oauth_callback_handler', 1);

function admin_lab_youtube_oauth_callback_handler() {

    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    $code   = isset($_GET['code'])  ? sanitize_text_field($_GET['code'])  : '';
    $state  = isset($_GET['state']) ? (string) wp_unslash($_GET['state']) : '';

    // Get provider for debug_log check (will be available after state verification)
    $debug_log = false;

    if (!$state) {
        wp_die('YouTube OAuth error: missing state.');
    }

    // Verify state (signature + ttl). Provider is inside payload.
    $ver = admin_lab_youtube_verify_state($state, '', 10 * MINUTE_IN_SECONDS);
    if (is_wp_error($ver)) {
        wp_die($ver->get_error_message());
    }

    $provider_slug = (string) ($ver['p'] ?? '');
    if (!$provider_slug) {
        wp_die('YouTube OAuth error: provider missing in state.');
    }
    
    // Check if debug logging is enabled for this provider
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if ($provider) {
        $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
        $debug_log = !empty($settings['debug_log']);
    }

    if (!$code) {
        wp_die('YouTube OAuth error: missing code.');
    }

    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider || empty($provider['client_id']) || empty($provider['client_secret'])) {
        wp_die('YouTube provider misconfigured: ' . esc_html($provider_slug));
    }

    $client_id = (string) $provider['client_id'];
    $client_secret = (string) $provider['client_secret'];

    if (function_exists('admin_lab_decrypt_data')) {
        $dec = admin_lab_decrypt_data($client_secret);
        if ($dec && $dec !== $client_secret) {
            $client_secret = $dec;
        }
    }

    $redirect_uri = admin_lab_youtube_redirect_uri($provider_slug);

    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'timeout' => 20,
        'body' => [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirect_uri,
        ],
    ]);

    if (is_wp_error($response)) {
        wp_die('YouTube token error: ' . esc_html($response->get_error_message()));
    }

    $raw = (string) wp_remote_retrieve_body($response);
    $data = json_decode($raw, true);

    if (empty($data['access_token'])) {
        wp_die('Invalid token response.');
    }

    $access_token  = (string) $data['access_token'];
    $refresh_token = isset($data['refresh_token']) ? (string) $data['refresh_token'] : '';
    $expires_in    = (int) ($data['expires_in'] ?? 0);

    admin_lab_set_provider_setting($provider_slug, 'creator_access_token', $access_token);

    // refresh_token can be empty on subsequent consents (normal)
    if (!empty($refresh_token)) {
        admin_lab_set_provider_setting($provider_slug, 'creator_refresh_token', $refresh_token);
    }

    if ($expires_in > 0) {
        admin_lab_set_provider_setting($provider_slug, 'creator_token_expires_at', time() + max(0, $expires_in - 60));
    }

    // Optional: store channel identity
    $channel = admin_lab_youtube_get_authed_channel($access_token);
    if (is_array($channel) && !empty($channel['id'])) {
        admin_lab_set_provider_setting($provider_slug, 'creator_channel_id', (string) $channel['id']);
        admin_lab_set_provider_setting($provider_slug, 'creator_channel_title', (string) ($channel['snippet']['title'] ?? ''));
    }

    admin_lab_youtube_oauth_log(
        "TOKENS SAVED: provider={$provider_slug} access=yes refresh=" . (!empty($refresh_token) ? 'yes' : 'no') .
        " redirect_uri={$redirect_uri}",
        $debug_log
    );

    wp_redirect(admin_url('admin.php?page=admin-lab-subscription&tab=providers&oauth=' . urlencode($provider_slug) . '_ok'));
    exit;
}

/**
 * Fetch authenticated channel info (mine=true)
 *
 * @param string $access_token
 * @param bool   $as_error If true, returns WP_Error on failure. Otherwise returns null.
 * @return array|null|WP_Error channel item (YouTube API) or null
 */
function admin_lab_youtube_get_authed_channel($access_token) {
    $res = wp_remote_get('https://www.googleapis.com/youtube/v3/channels?part=id,snippet&mine=true&maxResults=1', [
        'timeout' => 20,
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Accept'        => 'application/json',
        ],
    ]);

    if (is_wp_error($res)) {
        return new WP_Error('youtube_channels_http', $res->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $raw  = (string) wp_remote_retrieve_body($res);

    if ($code < 200 || $code >= 300) {
        return new WP_Error('youtube_channels_api', "channels.list error ({$code}): {$raw}");
    }

    $body = json_decode($raw, true);
    $item = $body['items'][0] ?? null;

    if (!$item || empty($item['id'])) {
        return new WP_Error('youtube_channels_empty', 'channels.list returned no channel (mine=true).');
    }

    return $item;
}

/* -------------------------------------------------------------------------- */
/*  Proxy handlers for youtube_* providers                                    */
/* -------------------------------------------------------------------------- */

add_action('init', function () {

    $fallback_slug = 'youtube_me5rine_gaming';

    // Fallback START proxy (per provider)
    add_action('admin_post_admin_lab_' . $fallback_slug . '_oauth_start', function () use ($fallback_slug) {
        $_GET['provider'] = $fallback_slug;
        admin_lab_youtube_oauth_log("PROXY START fallback hit: {$fallback_slug}");
        do_action('admin_post_admin_lab_youtube_oauth_start');
    });

    // Optional: keep old per-provider callback endpoints for backward compatibility
    add_action('admin_post_nopriv_admin_lab_' . $fallback_slug . '_oauth_callback', function () use ($fallback_slug) {
        admin_lab_youtube_oauth_log("PROXY CALLBACK (legacy) NOPRIV hit: {$fallback_slug}");
        do_action('admin_post_nopriv_admin_lab_youtube_oauth_callback');
    });
    add_action('admin_post_admin_lab_' . $fallback_slug . '_oauth_callback', function () use ($fallback_slug) {
        admin_lab_youtube_oauth_log("PROXY CALLBACK (legacy) hit: {$fallback_slug}");
        do_action('admin_post_admin_lab_youtube_oauth_callback');
    });

    if (!function_exists('admin_lab_get_subscription_providers')) {
        admin_lab_youtube_oauth_log('admin_lab_get_subscription_providers() missing at init');
        return;
    }

    $providers = admin_lab_get_subscription_providers();
    if (empty($providers) || !is_array($providers)) {
        admin_lab_youtube_oauth_log('No providers found at init');
        return;
    }

    foreach ($providers as $p) {
        $slug = isset($p['provider_slug']) ? (string) $p['provider_slug'] : '';
        if (!$slug || strpos($slug, 'youtube') !== 0 || $slug === 'youtube') {
            continue;
        }

        $action_start    = 'admin_lab_' . $slug . '_oauth_start';
        $action_callback = 'admin_lab_' . $slug . '_oauth_callback'; // legacy

        add_action('admin_post_' . $action_start, function () use ($slug) {
            $_GET['provider'] = $slug;
            admin_lab_youtube_oauth_log("PROXY START hit: {$slug}");
            do_action('admin_post_admin_lab_youtube_oauth_start');
        });

        // Legacy per-provider callback endpoints (optional)
        add_action('admin_post_' . $action_callback, function () use ($slug) {
            admin_lab_youtube_oauth_log("PROXY CALLBACK (legacy) hit: {$slug}");
            do_action('admin_post_admin_lab_youtube_oauth_callback');
        });

        add_action('admin_post_nopriv_' . $action_callback, function () use ($slug) {
            admin_lab_youtube_oauth_log("PROXY CALLBACK (legacy) NOPRIV hit: {$slug}");
            do_action('admin_post_nopriv_admin_lab_youtube_oauth_callback');
        });
    }

}, 1);

<?php
// File: modules/subscription/functions/sync/subscription-sync-youtube.php

if (!defined('ABSPATH')) exit;

/**
 * Fetch YouTube subscriptions (channel members / JOIN - paid memberships)
 * Uses members.list API endpoint (not subscriptions.list which is for free subscribers)
 *
 * NOTE: We rely on admin_lab_youtube_fetch_paid_members() for token validity + refresh.
 * (Keeps a single source of truth for OAuth token handling.)
 */
function admin_lab_fetch_youtube_subscriptions($channel, $provider_slug = 'youtube') {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return ['_error' => "Provider '{$provider_slug}' not configured"];
    }

    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $debug = !empty($settings['debug_log']);

    // Expected channel id (stored at OAuth time) fallback to channel row
    $expected_channel_id = admin_lab_get_provider_setting($provider_slug, 'creator_channel_id', '');
    if (!$expected_channel_id) {
        $expected_channel_id = $channel['channel_identifier'] ?? '';
    }

    if ($debug && function_exists('admin_lab_log_custom')) {
        $stored_channel_id = $settings['creator_channel_id'] ?? '';
        admin_lab_log_custom(
            '[YOUTUBE SYNC] expected_channel_id=' . ($expected_channel_id ?: 'NONE') .
            ' stored_creator_channel_id=' . ($stored_channel_id ?: 'NONE'),
            'subscription-sync.log'
        );
    }

    // Use the members API function
    $members = admin_lab_youtube_fetch_paid_members($provider_slug, $debug, $expected_channel_id);

    // Handle errors
    if (is_wp_error($members)) {
        $error_msg = $members->get_error_message();

        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[YOUTUBE SYNC] ERROR: ' . $error_msg, 'subscription-sync.log');
        }

        // Provide helpful error messages (optional refinement)
        $error_code = $members->get_error_code();
        if ($error_code === 'youtube_members_api') {
            $error_data = $members->get_error_data();
            if (is_string($error_data) && strpos($error_data, '403') !== false) {
                $error_msg .= ' - Channel memberships may not be enabled for this channel. Enable "Channel Memberships" in YouTube Studio.';
            }
        }

        return ['_error' => $error_msg];
    }

    // Map members to subscription format
    $creator_channel_id = $channel['channel_identifier'];
    $creator_channel_name = $channel['channel_name'];
    $subscriptions = [];

    foreach ($members as $member) {
        $external_user_id = $member['external_user_id'] ?? '';
        $external_username = $member['external_username'] ?? '';
        $level_slug_raw = $member['level_slug'] ?? 'member';
        $level_name = $member['level_name'] ?? '';
        $member_since = $member['member_since'] ?? '';
        $total_months = $member['total_months'] ?? 0;

        // Skip if no user ID (should not happen; member fetch creates anon ids)
        if (empty($external_user_id)) {
            continue;
        }

        // Build level_slug: yt_{levelId} or yt_member as fallback
        $level_slug = ($level_slug_raw && $level_slug_raw !== 'member') ? ('yt_' . $level_slug_raw) : 'yt_member';

        // Parse member_since date if available
        $started_at = null;
        if (!empty($member_since)) {
            $parsed = strtotime($member_since);
            if ($parsed !== false) {
                $started_at = date('Y-m-d H:i:s', $parsed);
            }
        }

        $subscriptions[] = [
            'provider_slug' => $provider_slug, // may be youtube_me5rine_gaming
            'external_user_id' => $external_user_id,
            'external_username' => $external_username,
            'external_subscription_id' => $external_user_id . '_' . $creator_channel_id . '_' . $level_slug,
            'level_slug' => $level_slug,
            'status' => 'active',
            'started_at' => $started_at,
            'expires_at' => null, // YouTube memberships don't expire automatically
            'metadata' => json_encode([
                'channel_id' => $creator_channel_id,
                'channel_name' => $creator_channel_name,
                'member_channel_id' => $external_user_id,
                'member_display_name' => $external_username,
                'membership_level_id' => $level_slug_raw,
                'membership_level_name' => $level_name,
                'member_since' => $member_since,
                'total_months' => (int)$total_months,
                'subscription_type' => 'payant',
            ]),
        ];
    }

    // IMPORTANT: For YouTube "memberships", having 0 members is NOT an error.
    // Only return error if admin_lab_youtube_fetch_paid_members() returned a WP_Error
    $total_count = count($subscriptions);

    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[YOUTUBE SYNC] Total members mapped: ' . $total_count, 'subscription-sync.log');
    }

    if (empty($subscriptions)) {
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[YOUTUBE SYNC] 0 paid members returned (normal if you currently have no JOIN members).', 'subscription-sync.log');
        }
        return []; // do NOT return _error
    }

    // In debug mode only: log anonymized sample
    if ($debug && $total_count > 0 && function_exists('admin_lab_log_custom')) {
        $sample_count = min(3, $total_count);
        $sample = [];
        for ($i = 0; $i < $sample_count; $i++) {
            $sub = $subscriptions[$i];
            $user_id_hash = !empty($sub['external_user_id']) ? substr(hash('sha256', $sub['external_user_id']), 0, 8) : 'N/A';
            $sample[] = [
                'user_id_hash' => $user_id_hash,
                'level_slug' => $sub['level_slug'] ?? 'N/A',
            ];
        }
        $sample_json = json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        admin_lab_log_custom("[YOUTUBE SYNC] Sample (anonymized, {$sample_count}/{$total_count}): " . $sample_json, 'subscription-sync.log');
    }

    return $subscriptions;
}

/**
 * Deactivate YouTube subscriptions that are no longer active
 * Called after syncing YouTube subscriptions to mark inactive those not in the current list
 * 
 * @param array $channel Channel data
 * @param array $active_subscription_ids Array of active subscription IDs
 * @param string $provider_slug Provider slug (e.g., 'youtube', 'youtube_me5rine_gaming')
 * @return int Number of deactivated subscriptions
 */
function admin_lab_deactivate_inactive_youtube_subscriptions($channel, $active_subscription_ids, $provider_slug = 'youtube') {
    global $wpdb;
    $table = admin_lab_getTable('user_subscriptions');
    $channel_id = $channel['channel_identifier'];
    
    // Get provider settings to check debug mode
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    $debug = false;
    if ($provider && !empty($provider['settings'])) {
        $settings = maybe_unserialize($provider['settings']);
        $debug = !empty($settings['debug_log']);
    }
    
    // Build WHERE clause to find YouTube subscriptions for this channel
    // Check both provider_slug (normalized to 'youtube') and provider_target_slug (specific like 'youtube_me5rine_gaming')
    // Also check channel_id in metadata
    // Handle both cases:
    // - provider_target_slug matches the specific provider (e.g., 'youtube_me5rine_gaming')
    // - OR provider_target_slug is NULL and provider_slug is 'youtube' (for old data or base provider)
    if ($provider_slug === 'youtube') {
        // Base provider: match provider_slug = 'youtube' (with or without provider_target_slug)
        $where = $wpdb->prepare(
            "provider_slug = 'youtube' AND (provider_target_slug IS NULL OR provider_target_slug = 'youtube') AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.channel_id')) = %s AND status = 'active'",
            $channel_id
        );
    } else {
        // Specific provider (e.g., 'youtube_me5rine_gaming'): match provider_target_slug OR fallback to base
        $where = $wpdb->prepare(
            "(provider_target_slug = %s OR (provider_target_slug IS NULL AND provider_slug = 'youtube')) AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.channel_id')) = %s AND status = 'active'",
            $provider_slug,
            $channel_id
        );
    }
    
    // If we have active subscription IDs, exclude them from deactivation
    if (!empty($active_subscription_ids)) {
        $placeholders = implode(',', array_fill(0, count($active_subscription_ids), '%s'));
        $where .= $wpdb->prepare(" AND external_subscription_id NOT IN ({$placeholders})", ...$active_subscription_ids);
    }
    
    // Deactivate subscriptions not in the active list
    $deactivated = $wpdb->query(
        "UPDATE {$table} SET status = 'inactive', updated_at = NOW() WHERE {$where}"
    );
    
    if ($deactivated > 0) {
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[YOUTUBE SYNC] Deactivated {$deactivated} inactive subscription(s) for channel {$channel_id} (provider: {$provider_slug})", 'subscription-sync.log');
        }
    } elseif ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[YOUTUBE SYNC] No inactive subscriptions to deactivate for channel {$channel_id} (provider: {$provider_slug})", 'subscription-sync.log');
    }
    
    return $deactivated;
}
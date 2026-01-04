<?php
// File: modules/subscription/functions/sync/subscription-sync-youtube-fallback.php

if (!defined('ABSPATH')) exit;

/**
 * Fetch YouTube subscriptions (members) for a Discord server
 * Uses Discord bot API to get members with specific Discord roles
 * These roles are synchronized directly from YouTube (by an external bot/service)
 * Calls: /role-members?guild_id=...&role_id=...
 * 
 * This is a fallback provider when YouTube API scopes are not available.
 * 
 * @param array $channel Channel data
 * @param string $provider_slug Provider slug (default: 'youtube_no_api')
 * @return array Array of subscription data or error array with '_error' key
 */
function admin_lab_fetch_youtube_no_api_subscriptions($channel, $provider_slug = 'youtube_no_api') {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return ['_error' => "Provider '{$provider_slug}' not configured"];
    }

    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $debug    = !empty($settings['debug_log']);

    $bot_api_url = rtrim($settings['bot_api_url'] ?? '', '/');
    $bot_api_key = $settings['bot_api_key'] ?? '';
    
    // Decrypt bot_api_key if encrypted
    if (!empty($bot_api_key) && function_exists('admin_lab_decrypt_data')) {
        $decrypted = admin_lab_decrypt_data($bot_api_key);
        // If decryption succeeded (returned value is different), use decrypted value
        if ($decrypted !== $bot_api_key) {
            $bot_api_key = $decrypted;
        }
    }

    // Safe logging: only in debug mode, and only hash/length, not the key itself
    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[YouTube No API SYNC] Using provider slug: {$provider_slug}", 'subscription-sync.log');
        admin_lab_log_custom("[YouTube No API SYNC] Bot API configured: url=" . ($bot_api_url ? 'yes' : 'no') . ", key=" . ($bot_api_key ? 'yes' : 'no'), 'subscription-sync.log');
        if ($bot_api_key) {
            // Safe: only log hash and length, never the actual key
            admin_lab_log_custom("[YouTube No API SYNC] bot_api_key_len=" . strlen($bot_api_key) . ", bot_api_key_sha=" . substr(hash('sha256', $bot_api_key), 0, 12), 'subscription-sync.log');
        }
    }

    if (!$bot_api_url || !$bot_api_key) {
        return ['_error' => 'YouTube No API bot API not configured'];
    }

    $guild_id   = $channel['channel_identifier'];
    $guild_name = $channel['channel_name'];

    // Get subscription levels for this specific provider that have discord_role_id configured
    // Each provider (youtube_no_api_me5rine_gaming, youtube_no_api_autre, etc.) has its own subscription types
    $levels = admin_lab_get_subscription_levels_by_provider($provider_slug);
    
    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[YouTube No API SYNC] Found " . count($levels) . " level(s) for provider {$provider_slug}", 'subscription-sync.log');
    }
    
    $role_mappings = [];
    
    foreach ($levels as $level) {
        if (!empty($level['discord_role_id']) && !empty($level['level_slug'])) {
            $role_mappings[$level['discord_role_id']] = $level['level_slug'];
            if ($debug && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[YouTube No API SYNC] Mapped role {$level['discord_role_id']} -> level {$level['level_slug']}", 'subscription-sync.log');
            }
        }
    }
    
    // Fallback: also check provider settings for role mappings (backward compatibility)
    if (empty($role_mappings)) {
        $settings_role_mappings = $settings['role_mappings'] ?? [];
        if (is_array($settings_role_mappings) && !empty($settings_role_mappings)) {
            $role_mappings = $settings_role_mappings;
        }
    }
    
    if (empty($role_mappings)) {
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[YouTube No API SYNC] No Discord role mappings configured for {$provider_slug}. Please configure discord_role_id in subscription types.", 'subscription-sync.log');
        }
        return ['_error' => 'No Discord role mappings configured for YouTube No API. Please configure discord_role_id in subscription types or role mappings in provider settings.'];
    }

    $all_subscriptions = [];

    // Fetch members for each role
    foreach ($role_mappings as $role_id => $level_slug) {
        if (empty($role_id) || empty($level_slug)) {
            continue;
        }

        $url = add_query_arg([
            'guild_id' => $guild_id,
            'role_id' => $role_id,
        ], $bot_api_url . '/role-members');

        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[YouTube No API SYNC] Calling bot API for role {$role_id} -> level {$level_slug}: {$url}", 'subscription-sync.log');
        }

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'x-admin-lab-key' => $bot_api_key,
                'accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            $msg = "Bot API error for role {$role_id}: " . $response->get_error_message();
            if ($debug && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[YouTube No API SYNC] ERROR: {$msg}", 'subscription-sync.log');
            }
            // Continue with other roles instead of failing completely
            continue;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[YouTube No API SYNC] HTTP {$code} for role {$role_id}", 'subscription-sync.log');
            admin_lab_log_custom("[YouTube No API SYNC] Body (first 500): " . substr($body, 0, 500), 'subscription-sync.log');
            // Log first member structure to see what fields are available
            $data_preview = json_decode($body, true);
            if (!empty($data_preview['members'][0])) {
                admin_lab_log_custom("[YouTube No API SYNC] First member structure: " . json_encode($data_preview['members'][0]), 'subscription-sync.log');
            }
        }

        if ($code !== 200) {
            if ($debug && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[YouTube No API SYNC] Bot API HTTP {$code} for role {$role_id}: {$body}", 'subscription-sync.log');
            }
            // Continue with other roles
            continue;
        }

        $data = json_decode($body, true);
        if (empty($data['members']) || !is_array($data['members'])) {
            if ($debug && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[YouTube No API SYNC] No members in response for role {$role_id}", 'subscription-sync.log');
            }
            continue;
        }

        // Map members to subscription format
        foreach ($data['members'] as $member) {
            $discord_user_id = $member['discord_user_id'] ?? $member['id'] ?? $member['user']['id'] ?? '';
            if (!$discord_user_id) continue;

            // Try multiple possible fields for username (API might return user object or flat structure)
            $external_username = $member['username'] 
                ?? $member['display_name'] 
                ?? $member['user']['username'] 
                ?? $member['user']['global_name'] 
                ?? $member['user']['display_name'] 
                ?? $member['nick'] 
                ?? '';

            $started_at = null;
            if (!empty($member['joined_at'])) {
                $started_at = date('Y-m-d H:i:s', strtotime($member['joined_at']));
            } elseif (!empty($member['premium_since'])) {
                $started_at = date('Y-m-d H:i:s', strtotime($member['premium_since']));
            }

            $subscription_data = [
                'provider_slug' => $provider_slug, // Original provider slug (e.g., youtube_no_api_me5rine) - will be normalized in admin_lab_save_subscription
                'external_user_id' => $discord_user_id,
                'external_username' => $external_username,
                'external_subscription_id' => $discord_user_id . '_' . $guild_id . '_' . $level_slug,
                'level_slug' => $level_slug,
                'status' => 'active',
                'started_at' => $started_at,
                'expires_at' => null,
                'metadata' => json_encode([
                    'guild_id' => $guild_id,
                    'channel_name' => $guild_name, // Use channel_name from subscription_channels table
                    'guild_name' => $data['guild_name'] ?? $guild_name, // Keep guild_name for backward compatibility
                    'role_id' => $role_id,
                    'discord_user_id' => $discord_user_id,
                    'external_username' => $external_username,
                    'subscription_type' => 'payant', // YouTube members are always paid
                    'source' => 'youtube_no_api', // Indicate this is from YouTube No API (Discord roles)
                ]),
            ];
            
            if ($debug && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[YouTube No API SYNC] Created subscription data: provider_slug={$provider_slug}, level_slug={$level_slug}, user_id={$discord_user_id}", 'subscription-sync.log');
            }
            
            $all_subscriptions[] = $subscription_data;
        }

        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[YouTube No API SYNC] Mapped " . count($data['members']) . " member(s) for role {$role_id} -> level {$level_slug}", 'subscription-sync.log');
        }
    }

    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[YouTube No API SYNC] Total YouTube No API members mapped: " . count($all_subscriptions) . " for provider {$provider_slug}", 'subscription-sync.log');
    }

    return $all_subscriptions;
}

/**
 * Deactivate YouTube No API members that no longer have the required Discord roles
 * Called after syncing YouTube No API subscriptions to mark inactive those not in the current list
 * 
 * @param array $channel Channel data
 * @param array $active_subscription_ids Array of active subscription IDs
 * @return int Number of deactivated members
 */
function admin_lab_deactivate_inactive_youtube_no_api_members($channel, $active_subscription_ids, $provider_slug = 'youtube_no_api') {
    global $wpdb;
    $table = admin_lab_getTable('user_subscriptions');
    $guild_id = $channel['channel_identifier'];
    
    // Build WHERE clause to find YouTube No API members for this guild
    // Use the specific provider_slug passed as parameter
    $where = $wpdb->prepare(
        "provider_slug = %s AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.guild_id')) = %s AND status = 'active'",
        $provider_slug,
        $guild_id
    );
    
    // If we have active subscription IDs, exclude them from deactivation
    if (!empty($active_subscription_ids)) {
        $placeholders = implode(',', array_fill(0, count($active_subscription_ids), '%s'));
        $where .= $wpdb->prepare(" AND external_subscription_id NOT IN ({$placeholders})", ...$active_subscription_ids);
    }
    
    // Deactivate members not in the active list
    $deactivated = $wpdb->query(
        "UPDATE {$table} SET status = 'inactive', updated_at = NOW() WHERE {$where}"
    );
    
    $debug = false; // Could be passed as parameter if needed
    if ($deactivated > 0 && $debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[YouTube No API SYNC] Deactivated {$deactivated} inactive member(s) for guild {$guild_id}", 'subscription-sync.log');
    }
    
    return $deactivated;
}


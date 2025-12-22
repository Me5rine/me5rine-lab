<?php
// File: modules/subscription/functions/sync/subscription-sync-discord.php

if (!defined('ABSPATH')) exit;

/**
 * Fetch Discord subscriptions (boosters) for a server
 * Calls the Discord bot API to get list of boosters
 * 
 * @param array $channel Channel data
 * @param string $provider_slug Provider slug (default: 'discord')
 * @return array Array of subscription data or error array with '_error' key
 */
function admin_lab_fetch_discord_subscriptions($channel, $provider_slug = 'discord') {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return ['_error' => "Provider '{$provider_slug}' not configured"];
    }

    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $debug    = !empty($settings['debug_log']) || (defined('WP_DEBUG') && WP_DEBUG);

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
        admin_lab_log_custom("[DISCORD SYNC] Using provider slug: {$provider_slug}", 'subscription-sync.log');
        admin_lab_log_custom("[DISCORD SYNC] Bot API configured: url=" . ($bot_api_url ? 'yes' : 'no') . ", key=" . ($bot_api_key ? 'yes' : 'no'), 'subscription-sync.log');
        if ($bot_api_key) {
            // Safe: only log hash and length, never the actual key
            admin_lab_log_custom("[DISCORD SYNC] bot_api_key_len=" . strlen($bot_api_key) . ", bot_api_key_sha=" . substr(hash('sha256', $bot_api_key), 0, 12), 'subscription-sync.log');
        }
    }

    if (!$bot_api_url || !$bot_api_key) {
        return ['_error' => 'Discord bot API not configured'];
    }

    $guild_id   = $channel['channel_identifier'];
    $guild_name = $channel['channel_name'];

    $url = add_query_arg(['guild_id' => $guild_id], $bot_api_url . '/boosters');

    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[DISCORD SYNC] Calling bot API: {$url}", 'subscription-sync.log');
    }

    $response = wp_remote_get($url, [
        'timeout' => 30,
        'headers' => [
            'x-admin-lab-key' => $bot_api_key,
            'accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        $msg = 'Bot API error: ' . $response->get_error_message();
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[DISCORD SYNC] ERROR: {$msg}", 'subscription-sync.log');
        }
        return ['_error' => $msg];
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[DISCORD SYNC] HTTP {$code}", 'subscription-sync.log');
        admin_lab_log_custom("[DISCORD SYNC] Body (first 500): " . substr($body, 0, 500), 'subscription-sync.log');
    }

    if ($code !== 200) {
        return ['_error' => "Bot API HTTP {$code}: {$body}"];
    }

    $data = json_decode($body, true);
    if (empty($data['boosters']) || !is_array($data['boosters'])) {
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[DISCORD SYNC] No boosters in response", 'subscription-sync.log');
        }
        return [];
    }

    $subscriptions = [];

    foreach ($data['boosters'] as $b) {
        $discord_user_id = $b['discord_user_id'] ?? '';
        if (!$discord_user_id) continue;

        $started_at = null;
        if (!empty($b['premium_since'])) {
            $started_at = date('Y-m-d H:i:s', strtotime($b['premium_since']));
        }

        $subscriptions[] = [
            'provider_slug' => $provider_slug, // Original provider slug (e.g., discord_me5rine) - will be normalized in admin_lab_save_subscription
            'external_user_id' => $discord_user_id,
            'external_username' => $b['username'] ?? '',
            'external_subscription_id' => $discord_user_id . '_' . $guild_id . '_booster',
            'level_slug' => 'booster',
            'status' => 'active',
            'started_at' => $started_at,
            'expires_at' => null,
            'metadata' => json_encode([
                'guild_id' => $guild_id,
                'guild_name' => $data['guild_name'] ?? $guild_name,
                'premium_since' => $b['premium_since'] ?? null,
                'external_username' => $b['username'] ?? '', // Store username in metadata for display
                'subscription_type' => 'payant', // Discord boosters are always paid
            ]),
        ];
    }

    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[DISCORD SYNC] Total boosters mapped: " . count($subscriptions), 'subscription-sync.log');
    }

    return $subscriptions;
}

/**
 * Deactivate Discord boosters that are no longer boosting
 * Called after syncing Discord subscriptions to mark inactive those not in the current list
 * 
 * @param array $channel Channel data
 * @param array $active_subscription_ids Array of active subscription IDs
 * @return int Number of deactivated boosters
 */
function admin_lab_deactivate_inactive_discord_boosters($channel, $active_subscription_ids) {
    global $wpdb;
    $table = admin_lab_getTable('user_subscriptions');
    $guild_id = $channel['channel_identifier'];
    
    // Build WHERE clause to find boosters for this guild
    $where = $wpdb->prepare(
        "provider_slug = 'discord' AND level_slug = 'booster' AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.guild_id')) = %s AND status = 'active'",
        $guild_id
    );
    
    // If we have active subscription IDs, exclude them from deactivation
    if (!empty($active_subscription_ids)) {
        $placeholders = implode(',', array_fill(0, count($active_subscription_ids), '%s'));
        $where .= $wpdb->prepare(" AND external_subscription_id NOT IN ({$placeholders})", ...$active_subscription_ids);
    }
    
    // Deactivate boosters not in the active list
    $deactivated = $wpdb->query(
        "UPDATE {$table} SET status = 'inactive', updated_at = NOW() WHERE {$where}"
    );
    
    if ($deactivated > 0) {
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[DISCORD SYNC] Deactivated {$deactivated} inactive booster(s) for guild {$guild_id}", 'subscription-sync.log');
        }
        error_log("[DISCORD SYNC] Deactivated {$deactivated} inactive booster(s) for guild {$guild_id}");
    }
    
    return $deactivated;
}


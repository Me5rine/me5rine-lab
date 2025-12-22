<?php
// File: modules/subscription/functions/sync/subscription-sync.php

if (!defined('ABSPATH')) exit;

/**
 * Sync subscriptions from all active providers and channels
 * 
 * This is the orchestrator function that coordinates synchronization
 * across all providers. Provider-specific logic is handled in separate files.
 */
function admin_lab_sync_subscriptions_from_providers() {
    // Log using both methods to ensure we capture everything
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[SUBSCRIPTION SYNC] Starting synchronization from providers', 'subscription-sync.log');
    }
    error_log('[SUBSCRIPTION SYNC] Starting synchronization from providers');
    
    $providers = admin_lab_get_subscription_providers(true); // Only active
    $provider_count = count($providers);
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[SUBSCRIPTION SYNC] Found ' . $provider_count . ' active provider(s)', 'subscription-sync.log');
    }
    error_log('[SUBSCRIPTION SYNC] Found ' . $provider_count . ' active provider(s)');
    
    $results = [
        'success' => [],
        'errors' => [],
        'total_synced' => 0,
    ];
    
    foreach ($providers as $provider) {
        $provider_slug = $provider['provider_slug'];
        $is_discord = !empty($provider_slug) && strpos($provider_slug, 'discord') === 0;
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION SYNC] Processing provider: {$provider_slug}", 'subscription-sync.log');
        }
        error_log("[SUBSCRIPTION SYNC] Processing provider: {$provider_slug}");
        
        // Get channels for this provider
        $channels = admin_lab_get_subscription_channels_by_provider($provider_slug, true);
        $channel_count = count($channels);
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION SYNC] Found {$channel_count} active channel(s) for {$provider_slug}", 'subscription-sync.log');
        }
        error_log("[SUBSCRIPTION SYNC] Found {$channel_count} active channel(s) for {$provider_slug}");
        
        if (empty($channels)) {
            $results['errors'][$provider_slug] = 'No active channels configured';
            if (function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[SUBSCRIPTION SYNC] ERROR: No active channels for {$provider_slug}", 'subscription-sync.log');
            }
            error_log("[SUBSCRIPTION SYNC] ERROR: No active channels for {$provider_slug}");
            continue;
        }
        
        try {
            $synced = 0;
            $channel_errors = [];
            $channel_success = [];
            
            foreach ($channels as $channel) {
                $channel_info = "{$channel['channel_name']} (ID: {$channel['channel_identifier']})";
                if (function_exists('admin_lab_log_custom')) {
                    admin_lab_log_custom("[SUBSCRIPTION SYNC] Fetching subscriptions for channel: {$channel_info}", 'subscription-sync.log');
                }
                error_log("[SUBSCRIPTION SYNC] Fetching subscriptions for channel: {$channel_info}");
                $subscriptions = admin_lab_fetch_subscriptions_from_provider($provider_slug, $channel);
                
                // Check for error in response
                if (is_array($subscriptions) && isset($subscriptions['_error'])) {
                    $error_msg = $channel['channel_name'] . ' (' . $channel['channel_identifier'] . '): ' . $subscriptions['_error'];
                    $channel_errors[] = $error_msg;
                    if (function_exists('admin_lab_log_custom')) {
                        admin_lab_log_custom("[SUBSCRIPTION SYNC] ERROR for channel {$channel['channel_name']}: {$subscriptions['_error']}", 'subscription-sync.log');
                    }
                    error_log("[SUBSCRIPTION SYNC] ERROR for channel {$channel['channel_name']}: {$subscriptions['_error']}");
                    continue;
                }
                
                $sub_count = count($subscriptions);
                if (function_exists('admin_lab_log_custom')) {
                    admin_lab_log_custom("[SUBSCRIPTION SYNC] Retrieved {$sub_count} subscription(s) for channel {$channel['channel_name']}", 'subscription-sync.log');
                }
                error_log("[SUBSCRIPTION SYNC] Retrieved {$sub_count} subscription(s) for channel {$channel['channel_name']}");
                
                if (is_array($subscriptions) && !empty($subscriptions)) {
                    $channel_synced = 0;
                    $active_subscription_ids = []; // Track active subscription IDs for this channel
                    
                    foreach ($subscriptions as $subscription_data) {
                        // Skip error entries
                        if (isset($subscription_data['_error'])) {
                            continue;
                        }
                        
                        $saved = admin_lab_save_subscription($subscription_data);
                        if ($saved) {
                            $synced++;
                            $channel_synced++;
                            $active_subscription_ids[] = $subscription_data['external_subscription_id'];
                            // Safe logging: anonymize user_id
                            $user_id_hash = !empty($subscription_data['external_user_id']) ? substr(hash('sha256', $subscription_data['external_user_id']), 0, 8) : 'N/A';
                            admin_lab_log_custom("[SUBSCRIPTION SYNC] Saved subscription: {$subscription_data['external_subscription_id']} (user_id_hash: {$user_id_hash})", 'subscription-sync.log');
                        } else {
                            admin_lab_log_custom("[SUBSCRIPTION SYNC] Failed to save subscription: {$subscription_data['external_subscription_id']}", 'subscription-sync.log');
                        }
                    }
                    
                    // For Discord: deactivate boosters that are no longer boosting
                    if ($is_discord && function_exists('admin_lab_deactivate_inactive_discord_boosters')) {
                        admin_lab_deactivate_inactive_discord_boosters($channel, $active_subscription_ids);
                    }
                    
                    // For Tipeee: deactivate members that no longer have the required roles
                    if (strpos($provider_slug, 'tipeee') === 0 && function_exists('admin_lab_deactivate_inactive_tipeee_members')) {
                        admin_lab_deactivate_inactive_tipeee_members($channel, $active_subscription_ids);
                    }
                    
                    if ($channel_synced > 0) {
                        $channel_success[] = $channel['channel_name'] . ': ' . $channel_synced . ' subscriptions';
                    }
                } elseif (is_array($subscriptions) && empty($subscriptions)) {
                    // For Discord: if no boosters found, deactivate all boosters for this guild
                    if ($is_discord && function_exists('admin_lab_deactivate_inactive_discord_boosters')) {
                        admin_lab_deactivate_inactive_discord_boosters($channel, []);
                    }
                    
                    // For Tipeee: if no members found, deactivate all members for this guild
                    if (strpos($provider_slug, 'tipeee') === 0 && function_exists('admin_lab_deactivate_inactive_tipeee_members')) {
                        admin_lab_deactivate_inactive_tipeee_members($channel, []);
                    }
                    
                    // For YouTube: 0 members is normal (not an error)
                    if (strpos($provider_slug, 'youtube') === 0) {
                        if (function_exists('admin_lab_log_custom')) {
                            admin_lab_log_custom("[SUBSCRIPTION SYNC] Channel {$channel['channel_name']}: 0 paid members (this is normal if you have no JOIN members)", 'subscription-sync.log');
                        }
                        // Don't add to errors for YouTube - 0 members is valid
                    } elseif (strpos($provider_slug, 'tipeee') === 0) {
                        if (function_exists('admin_lab_log_custom')) {
                            admin_lab_log_custom("[SUBSCRIPTION SYNC] Channel {$channel['channel_name']}: 0 Tipeee members (this is normal if no Discord roles are assigned)", 'subscription-sync.log');
                        }
                        // Don't add to errors for Tipeee - 0 members is valid (no Discord roles assigned)
                    } else {
                        $channel_errors[] = $channel['channel_name'] . ': No subscriptions found (channel may have no subscribers)';
                    }
                }
            }
            
            if ($synced > 0) {
                $success_msg = $synced . ' subscriptions synced';
                if (!empty($channel_success)) {
                    $success_msg .= ' (' . implode(', ', $channel_success) . ')';
                }
                $results['success'][$provider_slug] = $success_msg;
                $results['total_synced'] += $synced;
            }
            
            if (!empty($channel_errors)) {
                $results['errors'][$provider_slug] = implode(' | ', $channel_errors);
            }
        } catch (Exception $e) {
            $results['errors'][$provider_slug] = $e->getMessage();
        }
    }
    
    return $results;
}

/**
 * Fetch subscriptions from a specific provider and channel
 * 
 * This function delegates to provider-specific implementations.
 * 
 * @param string $provider_slug Provider slug (e.g., 'twitch', 'discord', 'youtube', 'patreon', 'tipeee')
 * @param array $channel Channel data
 * @return array Array of subscription data or error array with '_error' key
 */
function admin_lab_fetch_subscriptions_from_provider($provider_slug, $channel) {
    if (strpos($provider_slug, 'twitch') === 0) {
        return admin_lab_fetch_twitch_subscriptions($channel, $provider_slug);
    } elseif (strpos($provider_slug, 'discord') === 0) {
        return admin_lab_fetch_discord_subscriptions($channel, $provider_slug);
    } elseif (strpos($provider_slug, 'youtube') === 0) {
        // Support both 'youtube' and custom slugs like 'youtube_me5rine_gaming'
        return admin_lab_fetch_youtube_subscriptions($channel, $provider_slug);
    } elseif (strpos($provider_slug, 'patreon') === 0) {
        return admin_lab_fetch_patreon_subscriptions($channel, $provider_slug);
    } elseif (strpos($provider_slug, 'tipeee') === 0) {
        // Support both 'tipeee' and custom slugs like 'tipeee_me5rine'
        return admin_lab_fetch_tipeee_subscriptions($channel, $provider_slug);
    }
    return [];
}

/**
 * Save a subscription to the database
 * If user_id is 0, the subscription is not linked to a WordPress account
 * 
 * @param array $data Subscription data
 * @return int|false Subscription ID on success, false on failure
 */
function admin_lab_save_subscription($data) {
    global $wpdb;
    $table = admin_lab_getTable('user_subscriptions');
    
    // Find or create account
    $account_id = 0;
    $user_id = 0;
    
    // Extract provider base (global) and provider target (specific)
    $provider_target_slug = sanitize_text_field($data['provider_slug'] ?? '');
    $provider_slug = $provider_target_slug; // Default: same as provider_target_slug
    
    // Normalize provider_slug to base provider (twitch, youtube, discord, tipeee, patreon)
    if (strpos($provider_target_slug, 'twitch') === 0) {
        $provider_slug = 'twitch';
    } elseif (strpos($provider_target_slug, 'youtube') === 0) {
        $provider_slug = 'youtube';
    } elseif (strpos($provider_target_slug, 'discord') === 0) {
        $provider_slug = 'discord';
    } elseif (strpos($provider_target_slug, 'tipeee') === 0) {
        $provider_slug = 'tipeee';
    } elseif (strpos($provider_target_slug, 'patreon') === 0) {
        $provider_slug = 'patreon';
    }
    
    // Debug: log normalization (only for Tipeee to avoid too many logs)
    if (strpos($provider_target_slug, 'tipeee') === 0) {
        error_log("[SUBSCRIPTION SAVE] Tipeee - Original provider_slug from data: " . ($data['provider_slug'] ?? 'N/A'));
        error_log("[SUBSCRIPTION SAVE] Tipeee - Normalized provider_slug (base): {$provider_slug}");
        error_log("[SUBSCRIPTION SAVE] Tipeee - provider_target_slug (specific): {$provider_target_slug}");
    }
    
    // Find or create account (use provider_target_slug for account lookup)
    if (!empty($data['external_user_id'])) {
        $account = admin_lab_get_subscription_account_by_external($provider_target_slug, $data['external_user_id']);
        if ($account) {
            $account_id = $account['id'];
            $user_id = $account['user_id'];
        }
    }
    
    // Validate that level_slug exists in subscription_levels table (use base provider_slug for lookup)
    $level_slug = $data['level_slug'] ?? '';
    if ($level_slug && $provider_slug) {
        $table_levels = admin_lab_getTable('subscription_levels');
        $level_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_levels} WHERE provider_slug = %s AND level_slug = %s",
            $provider_slug,
            $level_slug
        ));
        if (!$level_exists) {
            error_log("[SUBSCRIPTION SYNC] WARNING: Level '{$level_slug}' for provider '{$provider_slug}' does not exist in subscription_levels table. Subscription may not be properly linked.");
        }
    }
    
    // Check if subscription already exists by external_subscription_id
    $external_subscription_id = $data['external_subscription_id'] ?? '';
    if (empty($external_subscription_id) && !empty($data['external_user_id'])) {
        // Generate external_subscription_id if not provided
        $channel_id = '';
        if (isset($data['metadata'])) {
            $metadata = is_string($data['metadata']) ? json_decode($data['metadata'], true) : $data['metadata'];
            $channel_id = $metadata['channel_id'] ?? '';
        }
        $external_subscription_id = $data['external_user_id'] . '_' . $channel_id . '_' . $data['level_slug'];
    }
    
    $existing = null;
    if (!empty($external_subscription_id)) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE external_subscription_id = %s LIMIT 1",
            $external_subscription_id
        ), ARRAY_A);
    }
    
    // Check if provider_target_slug column exists, create it if not
    $columns = $wpdb->get_col("DESCRIBE {$table}");
    $has_provider_target_slug = in_array('provider_target_slug', $columns);
    
    if (!$has_provider_target_slug) {
        // Add column if it doesn't exist
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN provider_target_slug VARCHAR(100) DEFAULT NULL AFTER provider_slug");
        $has_provider_target_slug = true;
        error_log("[SUBSCRIPTION SAVE] Added provider_target_slug column to {$table}");
    }
    
    $save_data = [
        'user_id' => $user_id,
        'account_id' => $account_id,
        'provider_slug' => $provider_slug, // Base provider (twitch, youtube, etc.)
        'level_slug' => sanitize_text_field($data['level_slug']),
        'external_subscription_id' => sanitize_text_field($external_subscription_id),
        'status' => sanitize_text_field($data['status'] ?? 'active'),
        'started_at' => isset($data['started_at']) ? $data['started_at'] : null,
        'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : null,
        'last_verified_at' => current_time('mysql'),
        'metadata' => isset($data['metadata']) ? (is_string($data['metadata']) ? $data['metadata'] : json_encode($data['metadata'])) : null,
    ];
    
    // Add provider_target_slug only if column exists
    if ($has_provider_target_slug) {
        $save_data['provider_target_slug'] = $provider_target_slug; // Specific provider (youtube_me5rine, discord_me5rine, etc.)
    }
    
    // Debug log (only for Discord and Tipeee to avoid too many logs)
    if (strpos($provider_target_slug, 'discord') === 0 || strpos($provider_target_slug, 'tipeee') === 0) {
        error_log("[SUBSCRIPTION SAVE] provider_slug={$provider_slug}, provider_target_slug={$provider_target_slug}, has_column=" . ($has_provider_target_slug ? 'yes' : 'no'));
    }
    
    if ($existing) {
        // Force update of provider_target_slug even if it seems the same
        // This ensures the column is updated correctly
        $result = $wpdb->update($table, $save_data, ['id' => $existing['id']]);
        if ($result === false) {
            error_log("[SUBSCRIPTION SAVE] Update failed: " . $wpdb->last_error);
            error_log("[SUBSCRIPTION SAVE] Last query: " . $wpdb->last_query);
        } else {
            // Verify the update worked
            $updated = $wpdb->get_row($wpdb->prepare("SELECT provider_slug, provider_target_slug FROM {$table} WHERE id = %d", $existing['id']), ARRAY_A);
            if ($updated) {
                error_log("[SUBSCRIPTION SAVE] After update - provider_slug: {$updated['provider_slug']}, provider_target_slug: " . ($updated['provider_target_slug'] ?? 'NULL'));
            }
        }
        return $existing['id'];
    } else {
        $result = $wpdb->insert($table, $save_data);
        if ($result === false) {
            error_log("[SUBSCRIPTION SAVE] Insert failed: " . $wpdb->last_error);
            error_log("[SUBSCRIPTION SAVE] Last query: " . $wpdb->last_query);
        } else {
            // Verify the insert worked
            $inserted = $wpdb->get_row($wpdb->prepare("SELECT provider_slug, provider_target_slug FROM {$table} WHERE id = %d", $wpdb->insert_id), ARRAY_A);
            if ($inserted) {
                error_log("[SUBSCRIPTION SAVE] After insert - provider_slug: {$inserted['provider_slug']}, provider_target_slug: " . ($inserted['provider_target_slug'] ?? 'NULL'));
            }
        }
        return $wpdb->insert_id;
    }
}

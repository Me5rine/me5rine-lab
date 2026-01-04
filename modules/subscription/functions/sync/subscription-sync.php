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
    $providers = admin_lab_get_subscription_providers(true); // Only active
    $provider_count = count($providers);
    
    $results = [
        'success' => [],
        'errors' => [],
        'total_synced' => 0,
    ];
    
    foreach ($providers as $provider) {
        $provider_slug = $provider['provider_slug'];
        $is_discord = !empty($provider_slug) && strpos($provider_slug, 'discord') === 0;
        $debug_log = admin_lab_subscription_is_debug_log_enabled($provider_slug);
        
        if ($debug_log && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION SYNC] Processing provider: {$provider_slug}", 'subscription-sync.log');
        }
        
        // Get channels for this provider
        $channels = admin_lab_get_subscription_channels_by_provider($provider_slug, true);
        $channel_count = count($channels);
        
        if ($debug_log && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION SYNC] Found {$channel_count} active channel(s) for {$provider_slug}", 'subscription-sync.log');
        }
        
        if (empty($channels)) {
            $results['errors'][$provider_slug] = 'No active channels configured';
            if ($debug_log && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[SUBSCRIPTION SYNC] ERROR: No active channels for {$provider_slug}", 'subscription-sync.log');
            }
            continue;
        }
        
        try {
            $synced = 0;
            $channel_errors = [];
            $channel_success = [];
            
            foreach ($channels as $channel) {
                $channel_info = "{$channel['channel_name']} (ID: {$channel['channel_identifier']})";
                if ($debug_log && function_exists('admin_lab_log_custom')) {
                    admin_lab_log_custom("[SUBSCRIPTION SYNC] Fetching subscriptions for channel: {$channel_info}", 'subscription-sync.log');
                }
                $subscriptions = admin_lab_fetch_subscriptions_from_provider($provider_slug, $channel);
                
                // Check for error in response
                if (is_array($subscriptions) && isset($subscriptions['_error'])) {
                    $error_msg = $channel['channel_name'] . ' (' . $channel['channel_identifier'] . '): ' . $subscriptions['_error'];
                    $channel_errors[] = $error_msg;
                    if ($debug_log && function_exists('admin_lab_log_custom')) {
                        admin_lab_log_custom("[SUBSCRIPTION SYNC] ERROR for channel {$channel['channel_name']}: {$subscriptions['_error']}", 'subscription-sync.log');
                    }
                    continue;
                }
                
                $sub_count = count($subscriptions);
                if ($debug_log && function_exists('admin_lab_log_custom')) {
                    admin_lab_log_custom("[SUBSCRIPTION SYNC] Retrieved {$sub_count} subscription(s) for channel {$channel['channel_name']}", 'subscription-sync.log');
                }
                
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
                            if ($debug_log && function_exists('admin_lab_log_custom')) {
                                // Safe logging: anonymize user_id
                                $user_id_hash = !empty($subscription_data['external_user_id']) ? substr(hash('sha256', $subscription_data['external_user_id']), 0, 8) : 'N/A';
                                admin_lab_log_custom("[SUBSCRIPTION SYNC] Saved subscription: {$subscription_data['external_subscription_id']} (user_id_hash: {$user_id_hash})", 'subscription-sync.log');
                            }
                        } else {
                            if ($debug_log && function_exists('admin_lab_log_custom')) {
                                admin_lab_log_custom("[SUBSCRIPTION SYNC] Failed to save subscription: {$subscription_data['external_subscription_id']}", 'subscription-sync.log');
                            }
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
                    
                    // For YouTube No API: deactivate members that no longer have the required roles
                    if (strpos($provider_slug, 'youtube_no_api') === 0 && function_exists('admin_lab_deactivate_inactive_youtube_no_api_members')) {
                        admin_lab_deactivate_inactive_youtube_no_api_members($channel, $active_subscription_ids);
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
                    
                    // For YouTube No API: if no members found, deactivate all members for this guild
                    if (strpos($provider_slug, 'youtube_no_api') === 0 && function_exists('admin_lab_deactivate_inactive_youtube_no_api_members')) {
                        admin_lab_deactivate_inactive_youtube_no_api_members($channel, []);
                    }
                    
                    // For YouTube: 0 members is normal (not an error)
                    if (strpos($provider_slug, 'youtube') === 0) {
                        if ($debug_log && function_exists('admin_lab_log_custom')) {
                            admin_lab_log_custom("[SUBSCRIPTION SYNC] Channel {$channel['channel_name']}: 0 paid members (this is normal if you have no JOIN members)", 'subscription-sync.log');
                        }
                        // Don't add to errors for YouTube - 0 members is valid
                    } elseif (strpos($provider_slug, 'tipeee') === 0) {
                        if ($debug_log && function_exists('admin_lab_log_custom')) {
                            admin_lab_log_custom("[SUBSCRIPTION SYNC] Channel {$channel['channel_name']}: 0 Tipeee members (this is normal if no Discord roles are assigned)", 'subscription-sync.log');
                        }
                        // Don't add to errors for Tipeee - 0 members is valid (no Discord roles assigned)
                    } elseif (strpos($provider_slug, 'youtube_no_api') === 0) {
                        if ($debug_log && function_exists('admin_lab_log_custom')) {
                            admin_lab_log_custom("[SUBSCRIPTION SYNC] Channel {$channel['channel_name']}: 0 YouTube No API members (this is normal if no Discord roles are assigned)", 'subscription-sync.log');
                        }
                        // Don't add to errors for YouTube No API - 0 members is valid (no Discord roles assigned)
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
            
            // After syncing all subscriptions for this provider, assign account types to users
            // This ensures account types are assigned even if subscriptions were already in the database
            // Use base provider_slug for account type assignment (mappings are stored with base provider)
            $base_provider_for_mapping = $provider_slug;
            if (strpos($provider_slug, 'twitch') === 0) {
                $base_provider_for_mapping = 'twitch';
            } elseif (strpos($provider_slug, 'youtube_no_api') === 0) {
                $base_provider_for_mapping = 'youtube_no_api';
            } elseif (strpos($provider_slug, 'youtube') === 0) {
                $base_provider_for_mapping = 'youtube';
            } elseif (strpos($provider_slug, 'discord') === 0) {
                $base_provider_for_mapping = 'discord';
            } elseif (strpos($provider_slug, 'tipeee') === 0) {
                $base_provider_for_mapping = 'tipeee';
            } elseif (strpos($provider_slug, 'patreon') === 0) {
                $base_provider_for_mapping = 'patreon';
            }
            
            if (function_exists('admin_lab_assign_account_types_from_subscriptions')) {
                admin_lab_assign_account_types_from_subscriptions($base_provider_for_mapping);
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
    } elseif (strpos($provider_slug, 'youtube_no_api') === 0) {
        // YouTube No API: uses Discord bot API (no YouTube API needed)
        return admin_lab_fetch_youtube_no_api_subscriptions($channel, $provider_slug);
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
    
    // Normalize provider_slug to base provider (twitch, youtube, youtube_no_api, discord, tipeee, patreon)
    if (strpos($provider_target_slug, 'twitch') === 0) {
        $provider_slug = 'twitch';
    } elseif (strpos($provider_target_slug, 'youtube_no_api') === 0) {
        $provider_slug = 'youtube_no_api';
    } elseif (strpos($provider_target_slug, 'youtube') === 0) {
        $provider_slug = 'youtube';
    } elseif (strpos($provider_target_slug, 'discord') === 0) {
        $provider_slug = 'discord';
    } elseif (strpos($provider_target_slug, 'tipeee') === 0) {
        $provider_slug = 'tipeee';
    } elseif (strpos($provider_target_slug, 'patreon') === 0) {
        $provider_slug = 'patreon';
    }
    
    // Check if debug logging is enabled for this provider
    $debug_log = admin_lab_subscription_is_debug_log_enabled($provider_target_slug);
    
    // Find or create account
    // Try with provider_target_slug first (for OAuth-linked accounts), then with base provider_slug (for Keycloak-linked accounts)
    if (!empty($data['external_user_id'])) {
        $account = admin_lab_get_subscription_account_by_external($provider_target_slug, $data['external_user_id']);
        
        // If not found, try with base provider_slug (for Keycloak accounts which use base provider)
        if (!$account && $provider_target_slug !== $provider_slug) {
            $account = admin_lab_get_subscription_account_by_external($provider_slug, $data['external_user_id']);
        }
        
        if ($account) {
            $account_id = $account['id'];
            $user_id = $account['user_id'];
            
            // Update account provider_slug if it's using base provider but we have a specific provider
            // This allows migration from base to specific provider
            if ($account['provider_slug'] === $provider_slug && $provider_target_slug !== $provider_slug) {
                global $wpdb;
                $table_accounts = admin_lab_getTable('subscription_accounts');
                $wpdb->update(
                    $table_accounts,
                    ['provider_slug' => $provider_target_slug],
                    ['id' => $account_id]
                );
            }
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
        if (!$level_exists && $debug_log) {
            if (function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[SUBSCRIPTION SYNC] WARNING: Level '{$level_slug}' for provider '{$provider_slug}' does not exist in subscription_levels table. Subscription may not be properly linked.", 'subscription-sync.log');
            }
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
    
    if ($existing) {
        // Force update of provider_target_slug even if it seems the same
        // This ensures the column is updated correctly
        $result = $wpdb->update($table, $save_data, ['id' => $existing['id']]);
        if ($result === false && $debug_log && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION SAVE] Update failed: " . $wpdb->last_error, 'subscription-sync.log');
        }
        $subscription_id = $existing['id'];
    } else {
        $result = $wpdb->insert($table, $save_data);
        if ($result === false && $debug_log && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION SAVE] Insert failed: " . $wpdb->last_error, 'subscription-sync.log');
        }
        $subscription_id = $wpdb->insert_id;
    }
    
    // Assign or remove account type to user based on provider mapping
    if ($user_id > 0) {
        // Get account type for this provider from mapping
        if (function_exists('admin_lab_get_provider_account_type')) {
            // Cherche d'abord mapping spécifique (youtube_me5rine, discord_xxx),
            // puis fallback sur base (youtube, discord, etc.)
            $account_type = admin_lab_get_provider_account_type($provider_target_slug);
            if (!$account_type && $provider_target_slug !== $provider_slug) {
                $account_type = admin_lab_get_provider_account_type($provider_slug);
            }
            
            // Debug logging
            if ($debug_log && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[SUBSCRIPTION SAVE] Checking account type for user {$user_id}, provider_target '{$provider_target_slug}', provider_base '{$provider_slug}': " . ($account_type ? $account_type : 'NOT FOUND'), 'subscription-sync.log');
            }
            
            if ($account_type && function_exists('admin_lab_set_account_type')) {
                // Verify that the account type is registered
                if (function_exists('admin_lab_get_registered_account_types')) {
                    $registered_types = admin_lab_get_registered_account_types();
                    if (!isset($registered_types[$account_type])) {
                        if ($debug_log && function_exists('admin_lab_log_custom')) {
                            admin_lab_log_custom("[SUBSCRIPTION SAVE] WARNING: Account type '{$account_type}' is not registered. Registered types: " . implode(', ', array_keys($registered_types)), 'subscription-sync.log');
                        }
                        return $subscription_id; // Exit early if type is not registered
                    }
                }
                
                if ($save_data['status'] === 'active') {
                    // Add account type to user (if not already present)
                    admin_lab_set_account_type($user_id, $account_type, 'add');
                    if ($debug_log && function_exists('admin_lab_log_custom')) {
                        admin_lab_log_custom("[SUBSCRIPTION SAVE] Assigned account type '{$account_type}' to user {$user_id} based on provider '{$provider_slug}'", 'subscription-sync.log');
                    }
                } else {
                    // Check if user has any other active subscriptions for this provider
                    $table_subscriptions = admin_lab_getTable('user_subscriptions');
                    $active_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_subscriptions} 
                         WHERE user_id = %d AND provider_slug = %s AND status = 'active'",
                        $user_id,
                        $provider_slug
                    ));
                    
                    // If no active subscriptions for this provider, remove the account type
                    if ($active_count == 0) {
                        admin_lab_set_account_type($user_id, $account_type, 'remove');
                        if ($debug_log && function_exists('admin_lab_log_custom')) {
                            admin_lab_log_custom("[SUBSCRIPTION SAVE] Removed account type '{$account_type}' from user {$user_id} (no active subscriptions for provider '{$provider_slug}')", 'subscription-sync.log');
                        }
                    }
                }
            } else {
                if (!$account_type && $debug_log && function_exists('admin_lab_log_custom')) {
                    admin_lab_log_custom("[SUBSCRIPTION SAVE] No account type mapping found for provider '{$provider_slug}'", 'subscription-sync.log');
                }
            }
        }
    }
    
    return $subscription_id;
}

/**
 * Assign account types to users based on their active subscriptions
 * This function processes all active subscriptions and assigns account types according to provider mappings
 * Called after syncing subscriptions to ensure account types are assigned even for existing subscriptions
 * 
 * @param string|null $provider_slug If provided, only process subscriptions for this provider
 */
function admin_lab_assign_account_types_from_subscriptions($provider_slug = null) {
    global $wpdb;
    
    if (!function_exists('admin_lab_get_provider_account_type') || !function_exists('admin_lab_set_account_type')) {
        return;
    }
    
    // Check if debug logging is enabled (check first provider if multiple)
    $debug_log = false;
    if ($provider_slug) {
        $debug_log = admin_lab_subscription_is_debug_log_enabled($provider_slug);
    } else {
        // If no specific provider, check if any provider has debug_log enabled
        $providers = admin_lab_get_subscription_providers(true);
        foreach ($providers as $provider) {
            if (admin_lab_subscription_is_debug_log_enabled($provider['provider_slug'])) {
                $debug_log = true;
                break;
            }
        }
    }
    
    $table = admin_lab_getTable('user_subscriptions');
    
    // Check if provider_target_slug column exists
    $columns = $wpdb->get_col("DESCRIBE {$table}");
    $has_provider_target_slug = in_array('provider_target_slug', $columns);
    $provider_col = $has_provider_target_slug ? 'provider_target_slug' : 'provider_slug';
    
    // Build query to get all active subscriptions with user_id > 0
    $where = "status = 'active' AND user_id > 0";
    if ($provider_slug) {
        // If using provider_target_slug, we need to check both columns
        // This handles cases where:
        // - provider_target_slug is specific (youtube_me5rine) and we search for base (youtube)
        // - provider_target_slug is NULL and provider_slug is base (youtube)
        // - provider_target_slug matches exactly
        if ($has_provider_target_slug) {
            // Check provider_target_slug that starts with provider_slug (for specific providers)
            // OR provider_target_slug is NULL and provider_slug matches
            // OR provider_target_slug matches exactly
            $where .= $wpdb->prepare(" AND (provider_target_slug LIKE %s OR provider_target_slug = %s OR (provider_target_slug IS NULL AND provider_slug = %s))", 
                $provider_slug . '%', 
                $provider_slug, 
                $provider_slug
            );
        } else {
            $where .= $wpdb->prepare(" AND provider_slug = %s", $provider_slug);
        }
    }
    
    // Get all active subscriptions grouped by user_id and provider
    // Use provider_target_slug if available, otherwise fallback to provider_slug
    $subscriptions = $wpdb->get_results(
        "SELECT DISTINCT user_id, {$provider_col} AS provider_slug 
         FROM {$table} 
         WHERE {$where}",
        ARRAY_A
    );
    
    if (empty($subscriptions)) {
        return;
    }
    
    if ($debug_log && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[ACCOUNT TYPE ASSIGN] Processing " . count($subscriptions) . " user-provider combinations", 'subscription-sync.log');
    }
    
    // Process each user-provider combination
    foreach ($subscriptions as $sub) {
        $user_id = intval($sub['user_id']);
        $provider = $sub['provider_slug'];
        
        if ($user_id <= 0) {
            continue;
        }
        
        // Get account type for this provider from mapping
        // admin_lab_get_provider_account_type() already handles fallback from specific to base
        $account_type = admin_lab_get_provider_account_type($provider);
        
        if ($account_type) {
            // Verify that the account type is registered
            if (function_exists('admin_lab_get_registered_account_types')) {
                $registered_types = admin_lab_get_registered_account_types();
                if (!isset($registered_types[$account_type])) {
                    if ($debug_log && function_exists('admin_lab_log_custom')) {
                        admin_lab_log_custom("[ACCOUNT TYPE ASSIGN] WARNING: Account type '{$account_type}' is not registered for provider '{$provider}'", 'subscription-sync.log');
                    }
                    continue;
                }
            }
            
            // Add account type to user
            admin_lab_set_account_type($user_id, $account_type, 'add');
            if ($debug_log && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[ACCOUNT TYPE ASSIGN] Assigned account type '{$account_type}' to user {$user_id} based on provider '{$provider}'", 'subscription-sync.log');
            }
        } else {
            if ($debug_log && function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[ACCOUNT TYPE ASSIGN] No account type mapping found for provider '{$provider}'", 'subscription-sync.log');
            }
        }
    }
}

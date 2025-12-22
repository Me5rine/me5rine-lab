<?php
// File: modules/subscription/functions/subscription-sync-levels.php

if (!defined('ABSPATH')) exit;

/**
 * Sync subscription types from providers
 * This function retrieves available subscription levels from each provider's API
 * and updates the subscription_levels table
 * 
 * For Twitch/Discord: types are global (tier1, tier2, tier3 for Twitch, booster for Discord)
 * For others (YouTube, Patreon, Tipeee): types are per provider (youtube_1, youtube_2, etc.)
 */
function admin_lab_sync_subscription_types_from_providers() {
    $providers = admin_lab_get_subscription_providers(true); // Only active providers
    
    $results = [
        'success' => [],
        'errors' => [],
    ];
    
    // Group providers by type
    $twitch_providers = [];
    $discord_providers = [];
    $other_providers = [];
    
    foreach ($providers as $provider) {
        $provider_slug = $provider['provider_slug'];
        if (strpos($provider_slug, 'twitch') === 0) {
            $twitch_providers[] = $provider;
        } elseif (strpos($provider_slug, 'discord') === 0) {
            $discord_providers[] = $provider;
        } else {
            $other_providers[] = $provider;
        }
    }
    
    // Handle Twitch: sync once globally (use 'twitch' as base provider_slug)
    if (!empty($twitch_providers)) {
        try {
            $levels = admin_lab_get_twitch_subscription_levels();
            
            if (is_array($levels) && !empty($levels)) {
                foreach ($levels as $level_data) {
                    // For Twitch, use 'twitch' as provider_slug (global)
                    $existing = admin_lab_get_subscription_level_by_slug('twitch', $level_data['level_slug']);
                    
                    if ($existing) {
                        admin_lab_save_subscription_level([
                            'id' => $existing['id'],
                            'provider_slug' => 'twitch', // Global for all Twitch providers
                            'level_slug' => $level_data['level_slug'],
                            'level_name' => $level_data['level_name'],
                            'level_tier' => $level_data['level_tier'] ?? null,
                            'subscription_type' => null,
                            'is_active' => 1,
                        ]);
                    } else {
                        admin_lab_save_subscription_level([
                            'provider_slug' => 'twitch', // Global for all Twitch providers
                            'level_slug' => $level_data['level_slug'],
                            'level_name' => $level_data['level_name'],
                            'level_tier' => $level_data['level_tier'] ?? null,
                            'subscription_type' => null,
                            'is_active' => 1,
                        ]);
                    }
                }
                
                $results['success']['twitch'] = count($levels) . ' types synced (global for all Twitch providers)';
            }
        } catch (Exception $e) {
            $results['errors']['twitch'] = $e->getMessage();
        }
    }
    
    // Handle Discord: sync once globally (use 'discord' as base provider_slug)
    if (!empty($discord_providers)) {
        try {
            $levels = admin_lab_get_discord_subscription_levels();
            
            if (is_array($levels) && !empty($levels)) {
                foreach ($levels as $level_data) {
                    // For Discord, use 'discord' as provider_slug (global)
                    $existing = admin_lab_get_subscription_level_by_slug('discord', $level_data['level_slug']);
                    
                    if ($existing) {
                        admin_lab_save_subscription_level([
                            'id' => $existing['id'],
                            'provider_slug' => 'discord', // Global for all Discord providers
                            'level_slug' => $level_data['level_slug'],
                            'level_name' => $level_data['level_name'],
                            'level_tier' => $level_data['level_tier'] ?? null,
                            'subscription_type' => null,
                            'is_active' => 1,
                        ]);
                    } else {
                        admin_lab_save_subscription_level([
                            'provider_slug' => 'discord', // Global for all Discord providers
                            'level_slug' => $level_data['level_slug'],
                            'level_name' => $level_data['level_name'],
                            'level_tier' => $level_data['level_tier'] ?? null,
                            'subscription_type' => null,
                            'is_active' => 1,
                        ]);
                    }
                }
                
                $results['success']['discord'] = count($levels) . ' types synced (global for all Discord providers)';
            }
        } catch (Exception $e) {
            $results['errors']['discord'] = $e->getMessage();
        }
    }
    
    // Handle other providers: sync per provider (youtube_1, youtube_2, etc.)
    foreach ($other_providers as $provider) {
        $provider_slug = $provider['provider_slug'];
        
        try {
            $levels = admin_lab_get_provider_subscription_levels($provider_slug);
            
            if (is_array($levels) && !empty($levels)) {
                foreach ($levels as $level_data) {
                    // For other providers, use the specific provider_slug
                    $existing = admin_lab_get_subscription_level_by_slug($provider_slug, $level_data['level_slug']);
                    
                    if ($existing) {
                        admin_lab_save_subscription_level([
                            'id' => $existing['id'],
                            'provider_slug' => $provider_slug, // Specific to this provider
                            'level_slug' => $level_data['level_slug'],
                            'level_name' => $level_data['level_name'],
                            'level_tier' => $level_data['level_tier'] ?? null,
                            'subscription_type' => null,
                            'is_active' => 1,
                        ]);
                    } else {
                        admin_lab_save_subscription_level([
                            'provider_slug' => $provider_slug, // Specific to this provider
                            'level_slug' => $level_data['level_slug'],
                            'level_name' => $level_data['level_name'],
                            'level_tier' => $level_data['level_tier'] ?? null,
                            'subscription_type' => null,
                            'is_active' => 1,
                        ]);
                    }
                }
                
                $results['success'][$provider_slug] = count($levels) . ' types synced';
            } else {
                $results['errors'][$provider_slug] = 'No levels returned from provider';
            }
        } catch (Exception $e) {
            $results['errors'][$provider_slug] = $e->getMessage();
        }
    }
    
    return $results;
}

/**
 * Get subscription levels from a specific provider
 * This function should be implemented per provider
 * Note: Patreon, Tipeee, YouTube must be synced from their APIs (no defaults)
 */
function admin_lab_get_provider_subscription_levels($provider_slug) {
    // Handle providers that start with 'twitch' (e.g., 'twitch', 'twitch_me5rine')
    if (strpos($provider_slug, 'twitch') === 0) {
        return admin_lab_get_twitch_subscription_levels();
    }
    
    switch ($provider_slug) {
        case 'discord':
            return admin_lab_get_discord_subscription_levels();
        case 'youtube':
            // YouTube levels must be synced from API, no defaults
            return admin_lab_get_youtube_subscription_levels($provider_slug);
        case 'patreon':
            // Patreon levels must be synced from API, no defaults
            return admin_lab_get_patreon_subscription_levels();
        case 'tipeee':
            // Tipeee levels must be synced from API, no defaults
            return admin_lab_get_tipeee_subscription_levels();
        default:
            return [];
    }
}

/**
 * Get Twitch subscription levels
 * Simplified: Only tier1, tier2, tier3
 * subscription_type (gratuit/payant) is stored in user_subscriptions metadata, NOT in subscription_levels
 */
function admin_lab_get_twitch_subscription_levels() {
    // Only return tier1, tier2, tier3 - subscription_type is handled at user subscription level
    return [
        [
            'level_slug' => 'tier1',
            'level_name' => 'Tier 1',
            'level_tier' => 1,
            'subscription_type' => null, // subscription_type is NOT stored here
        ],
        [
            'level_slug' => 'tier2',
            'level_name' => 'Tier 2',
            'level_tier' => 2,
            'subscription_type' => null, // subscription_type is NOT stored here
        ],
        [
            'level_slug' => 'tier3',
            'level_name' => 'Tier 3',
            'level_tier' => 3,
            'subscription_type' => null, // subscription_type is NOT stored here
        ],
    ];
}

/**
 * Get Discord subscription levels
 * Only booster - subscription_type is handled at user subscription level
 */
function admin_lab_get_discord_subscription_levels() {
    return [
        [
            'level_slug' => 'booster',
            'level_name' => 'Booster',
            'level_tier' => null,
            'subscription_type' => null, // subscription_type is NOT stored here
        ],
    ];
}

/**
 * Get YouTube subscription levels
 * Must be synced from YouTube API - no default levels
 */
function admin_lab_get_youtube_subscription_levels($provider_slug = 'youtube') {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return [];
    }

    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $debug = !empty($settings['debug_log']) || (defined('WP_DEBUG') && WP_DEBUG);

    // Refresh access token
    $token = admin_lab_youtube_refresh_access_token($provider_slug, $provider, $settings, $debug);
    if (isset($token['_error'])) {
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[YOUTUBE LEVELS] ERROR: ' . $token['_error'], 'subscription-sync.log');
        }
        return [];
    }

    $access_token = $token['access_token'];

    // Call membershipsLevels.list API
    $url = 'https://www.googleapis.com/youtube/v3/membershipsLevels?part=id,snippet';

    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[YOUTUBE LEVELS] Calling API: {$url}", 'subscription-sync.log');
    }

    $resp = wp_remote_get($url, [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($resp)) {
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[YOUTUBE LEVELS] API error: ' . $resp->get_error_message(), 'subscription-sync.log');
        }
        return [];
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);
    $data = json_decode($body, true);

    if ($code !== 200) {
        $api_msg = $data['error']['message'] ?? ($data['message'] ?? $body);
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[YOUTUBE LEVELS] API Error (HTTP {$code}): {$api_msg}", 'subscription-sync.log');
        }
        return [];
    }

    $items = $data['items'] ?? [];
    if (!is_array($items) || empty($items)) {
        return [];
    }

    $levels = [];
    foreach ($items as $level) {
        $level_id = $level['id'] ?? '';
        $level_name = $level['snippet']['displayName'] ?? '';

        if (!$level_id) {
            continue;
        }

        $levels[] = [
            'level_slug' => 'yt_' . $level_id,
            'level_name' => $level_name ?: 'Level ' . $level_id,
            'level_tier' => null, // YouTube doesn't use tiers like Twitch
            'subscription_type' => null, // subscription_type is NOT stored here
        ];
    }

    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[YOUTUBE LEVELS] Found ' . count($levels) . ' level(s)', 'subscription-sync.log');
    }

    return $levels;
}

/**
 * Get Patreon subscription levels
 * Must be synced from Patreon API - no default levels
 */
function admin_lab_get_patreon_subscription_levels() {
    // TODO: Implement Patreon subscription levels retrieval from API
    // No default levels - must be synced from provider
    return [];
}

/**
 * Get Tipeee subscription levels
 * For Tipeee, levels are created manually (not synced from API)
 * Returns levels from database for the provider
 */
function admin_lab_get_tipeee_subscription_levels($provider_slug = 'tipeee') {
    // Get levels from database for this provider
    return admin_lab_get_subscription_levels_by_provider($provider_slug);
}


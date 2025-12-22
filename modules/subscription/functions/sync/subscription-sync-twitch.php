<?php
// File: modules/subscription/functions/sync/subscription-sync-twitch.php

if (!defined('ABSPATH')) exit;

/**
 * Get Twitch app access token using client credentials
 * This token is used for server-to-server API calls
 * 
 * @param string $client_id Twitch Client ID
 * @param string $client_secret Twitch Client Secret
 * @return string|null Access token or null on failure
 */
function admin_lab_get_twitch_app_access_token($client_id, $client_secret) {
    // Check if we have a cached token (tokens are valid for a while)
    $cache_key = 'twitch_app_access_token_' . md5($client_id);
    $cached_token = get_transient($cache_key);
    
    if ($cached_token) {
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[TWITCH TOKEN] Using cached app access token', 'subscription-sync.log');
        }
        error_log('[TWITCH TOKEN] Using cached app access token');
        return $cached_token;
    }
    
    // Request new token from Twitch
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[TWITCH TOKEN] Requesting new app access token from Twitch', 'subscription-sync.log');
    }
    error_log('[TWITCH TOKEN] Requesting new app access token from Twitch');
    
    $response = wp_remote_post('https://id.twitch.tv/oauth2/token', [
        'body' => [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'client_credentials',
        ],
        'timeout' => 30,
    ]);
    
    if (is_wp_error($response)) {
        $error_msg = 'Failed to get Twitch app access token: ' . $response->get_error_message();
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH TOKEN] ERROR: {$error_msg}", 'subscription-sync.log');
        }
        error_log("[TWITCH TOKEN] ERROR: {$error_msg}");
        return null;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        $error_msg = 'Twitch token request failed (HTTP ' . $response_code . '): ' . ($error_data['message'] ?? 'Unknown error');
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH TOKEN] ERROR: {$error_msg}", 'subscription-sync.log');
        }
        error_log("[TWITCH TOKEN] ERROR: {$error_msg}");
        return null;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['access_token'])) {
        $error_msg = 'Twitch token response missing access_token';
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH TOKEN] ERROR: {$error_msg}", 'subscription-sync.log');
        }
        error_log("[TWITCH TOKEN] ERROR: {$error_msg}");
        return null;
    }
    
    $access_token = $data['access_token'];
    $expires_in = isset($data['expires_in']) ? intval($data['expires_in']) : 3600; // Default to 1 hour if not specified
    
    // Cache the token (store for slightly less time than expiry to be safe)
    set_transient($cache_key, $access_token, $expires_in - 60); // Cache for 1 minute less than expiry
    
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[TWITCH TOKEN] Successfully obtained app access token (expires in {$expires_in}s)", 'subscription-sync.log');
    }
    error_log("[TWITCH TOKEN] Successfully obtained app access token (expires in {$expires_in}s)");
    
    return $access_token;
}

/**
 * Fetch Twitch subscriptions for a channel
 * 
 * @param array $channel Channel data
 * @param string $provider_slug Provider slug (e.g., 'twitch', 'twitch_me5rine')
 * @return array Array of subscription data or error array with '_error' key
 */
function admin_lab_fetch_twitch_subscriptions($channel, $provider_slug = 'twitch') {
    global $wpdb;
    
    $channel_info = $channel['channel_name'] . ' (ID: ' . $channel['channel_identifier'] . ')';
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[TWITCH SYNC] Starting fetch for channel: ' . $channel_info, 'subscription-sync.log');
    }
    error_log('[TWITCH SYNC] Starting fetch for channel: ' . $channel_info);
    
    // Get provider using the provided slug (supports 'twitch', 'twitch_me5rine', etc.)
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH SYNC] ERROR: Provider '{$provider_slug}' not found in database", 'subscription-sync.log');
        }
        error_log("[TWITCH SYNC] ERROR: Provider '{$provider_slug}' not found in database");
        return [
            '_error' => "Provider '{$provider_slug}' not configured",
        ];
    }
    
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[TWITCH SYNC] Using provider: {$provider_slug} (ID: {$provider['id']}, Name: {$provider['provider_name']})", 'subscription-sync.log');
    }
    error_log("[TWITCH SYNC] Using provider: {$provider_slug} (ID: {$provider['id']}, Name: {$provider['provider_name']})");
    
    if (empty($provider['client_id'])) {
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[TWITCH SYNC] ERROR: Twitch Client ID is empty', 'subscription-sync.log');
        }
        error_log('[TWITCH SYNC] ERROR: Twitch Client ID is empty');
        return [
            '_error' => 'Twitch Client ID not configured',
        ];
    }
    
    $client_id_preview = substr($provider['client_id'], 0, 10) . '...';
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[TWITCH SYNC] Provider found with Client ID: ' . $client_id_preview, 'subscription-sync.log');
    }
    error_log('[TWITCH SYNC] Provider found with Client ID: ' . $client_id_preview);
    
    // Get settings and determine debug mode
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $debug = !empty($settings['debug_log']) || (defined('WP_DEBUG') && WP_DEBUG);
    
    $has_token = !empty($settings['broadcaster_access_token']);
    $has_refresh = !empty($settings['broadcaster_refresh_token']);
    $expires_at = isset($settings['broadcaster_token_expires_at']) ? intval($settings['broadcaster_token_expires_at']) : 0;
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[TWITCH SYNC] Token status: has_token=" . ($has_token ? 'yes' : 'no') . ", has_refresh=" . ($has_refresh ? 'yes' : 'no') . ", expires_at=" . ($expires_at ? date('Y-m-d H:i:s', $expires_at) : 'never') . ", debug=" . ($debug ? 'enabled' : 'disabled'), 'subscription-sync.log');
    }
    error_log("[TWITCH SYNC] Token status: has_token=" . ($has_token ? 'yes' : 'no') . ", has_refresh=" . ($has_refresh ? 'yes' : 'no') . ", expires_at=" . ($expires_at ? date('Y-m-d H:i:s', $expires_at) : 'never') . ", debug=" . ($debug ? 'enabled' : 'disabled'));
    
    $channel_identifier = $channel['channel_identifier']; // broadcaster_id
    $channel_name = $channel['channel_name'];
    
    // For Twitch, we need a user access token (not app access token) to read subscriptions
    // The token must belong to the broadcaster and have the scope: channel:read:subscriptions
    // Ensure token is valid and refresh if needed
    $access_token = null;
    
    // Try to get token from provider settings
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $broadcaster_token = $settings['broadcaster_access_token'] ?? null;
    $refresh_token = $settings['broadcaster_refresh_token'] ?? null;
    $expires_at = isset($settings['broadcaster_token_expires_at']) ? intval($settings['broadcaster_token_expires_at']) : 0;
    
    if ($broadcaster_token) {
        // Check if token is still valid
        if ($expires_at && time() < $expires_at) {
            // Token is still valid
            if (function_exists('admin_lab_decrypt_data')) {
                $decrypted = admin_lab_decrypt_data($broadcaster_token);
                if ($decrypted !== $broadcaster_token) {
                    $access_token = $decrypted;
                } else {
                    $access_token = $broadcaster_token;
                }
            } else {
                $access_token = $broadcaster_token;
            }
        } elseif ($refresh_token && !empty($provider['client_id']) && !empty($provider['client_secret'])) {
            // Token expired, try to refresh
            $client_id = $provider['client_id'];
            $client_secret = $provider['client_secret'];
            
            // Decrypt client_secret if encrypted
            if (function_exists('admin_lab_decrypt_data')) {
                $decrypted = admin_lab_decrypt_data($client_secret);
                if ($decrypted !== $client_secret) {
                    $client_secret = $decrypted;
                }
            }
            
            // Decrypt refresh_token if encrypted
            if (function_exists('admin_lab_decrypt_data')) {
                $decrypted = admin_lab_decrypt_data($refresh_token);
                if ($decrypted !== $refresh_token) {
                    $refresh_token = $decrypted;
                }
            }
            
            // Refresh token
            $response = wp_remote_post('https://id.twitch.tv/oauth2/token', [
                'timeout' => 20,
                'body' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh_token,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                ],
            ]);
            
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($data['access_token'])) {
                    $access_token = $data['access_token'];
                    // Update settings
                    admin_lab_set_provider_setting($provider_slug, 'broadcaster_access_token', $data['access_token']);
                    if (!empty($data['refresh_token'])) {
                        admin_lab_set_provider_setting($provider_slug, 'broadcaster_refresh_token', $data['refresh_token']);
                    }
                    $expires_in = (int)($data['expires_in'] ?? 0);
                    admin_lab_set_provider_setting($provider_slug, 'broadcaster_token_expires_at', time() + max(0, $expires_in - 60));
                }
            }
        }
    }
    
    if (empty($access_token)) {
        $error_msg = 'Twitch broadcaster access token not configured or expired. Please connect the broadcaster via OAuth (see "Providers" tab).';
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH SYNC] ERROR: {$error_msg}", 'subscription-sync.log');
        }
        error_log("[TWITCH SYNC] ERROR: {$error_msg}");
        return [
            '_error' => $error_msg,
            '_channel_id' => $channel_identifier,
        ];
    }
    
    // Safe logging: only log token length, not the token itself
    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[TWITCH SYNC] Using broadcaster access token (length: " . strlen($access_token) . ")", 'subscription-sync.log');
    }
    
    $subscriptions = [];
    $limit = 100;
    $after = null;
    $all_raw = [];
    
    while (true) {
        $url = add_query_arg([
            'broadcaster_id' => $channel_identifier,
            'first' => $limit,
        ], 'https://api.twitch.tv/helix/subscriptions');
        
        if (!empty($after)) {
            $url = add_query_arg('after', $after, $url);
        }
        
        if ($debug && function_exists('admin_lab_log_custom')) {
            // Safe: log URL without sensitive data
            $safe_url = preg_replace('/after=[^&]+/', 'after=***', $url);
            admin_lab_log_custom("[TWITCH SYNC] Calling API: {$safe_url}", 'subscription-sync.log');
        }
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Client-ID' => $provider['client_id'],
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            $error_msg = 'Twitch API Error: ' . $response->get_error_message();
            if (function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[TWITCH SYNC] ERROR: {$error_msg}", 'subscription-sync.log');
            }
            error_log("[TWITCH SYNC] ERROR: {$error_msg}");
            break;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        $json = json_decode($raw_body, true);
        
        // Safe logging: only log response code and counts, not sensitive data
        if ($debug && function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH SYNC] API Response Code: {$response_code}", 'subscription-sync.log');
        }
        
        if ($response_code !== 200) {
            $error_message = $json['message'] ?? 'Unknown error';
            
            // Safe error logging: only log error message, not full response
            if (function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[TWITCH SYNC] API Error (HTTP {$response_code}): {$error_message}", 'subscription-sync.log');
            }
            
            // Provide helpful error messages based on common Twitch API errors
            $helpful_message = $error_message;
            if ($response_code === 401) {
                $helpful_message .= ' - Token may be expired or invalid. Please reconnect the broadcaster via OAuth.';
            } elseif ($response_code === 403) {
                $helpful_message .= ' - Token may not have the required "channel:read:subscriptions" scope, or token does not belong to the broadcaster.';
            } elseif ($response_code === 400) {
                $helpful_message .= ' - Invalid broadcaster ID. Make sure the Channel Identifier is the broadcaster\'s User ID (not username).';
            }
            
            return [
                '_error' => 'Twitch API Error (HTTP ' . $response_code . '): ' . $helpful_message,
                '_channel_id' => $channel_identifier,
            ];
        }
        
        // Extract data array
        $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];
        $all_raw = array_merge($all_raw, $data);
        
        // Extract cursor
        $cursor = '';
        if (!empty($json['pagination']) && is_array($json['pagination']) && !empty($json['pagination']['cursor'])) {
            $cursor = (string) $json['pagination']['cursor'];
        }
        
        // Safe logging: only log counts and pagination status
        $count_items = count($data);
        $has_cursor = !empty($cursor);
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH SYNC] Page: items={$count_items}, cursor_present=" . ($has_cursor ? 'yes' : 'no'), 'subscription-sync.log');
        }
        
        // Stop conditions (safe pagination)
        if (empty($data)) {
            // Plus rien à lire
            break;
        }
        
        // Ne pagine QUE si on a rempli la page
        if ($count_items < $limit) {
            break;
        }
        
        // Et seulement s'il y a un cursor
        if (empty($cursor)) {
            break;
        }
        
        $after = $cursor;
            
    }
    
    // Process all subscriptions (including broadcaster if they subscribed to themselves)
    foreach ($all_raw as $sub_data) {
        // Twitch API returns tier as "1000", "2000", "3000" (in cents)
        // Convert to tier number: 1000 = tier 1, 2000 = tier 2, 3000 = tier 3
        $tier_raw = intval($sub_data['tier'] ?? 1000);
        $tier = (int)($tier_raw / 1000); // Convert 1000->1, 2000->2, 3000->3
        if ($tier < 1 || $tier > 3) {
            $tier = 1; // Fallback to tier 1 if unexpected value
        }
        
        $is_gift = !empty($sub_data['is_gift']);
        
        // Simplified: level_slug is just tier1, tier2, tier3
        // subscription_type (gratuit/payant) is stored separately in metadata
        $level_slug = "tier{$tier}";
        
        // Determine subscription_type: gratuit (gift) or payant (paid)
        $subscription_type = $is_gift ? 'gratuit' : 'payant';
        
        // Safe logging: only in debug mode, and anonymize user_id
        if ($debug && function_exists('admin_lab_log_custom')) {
            $user_id_hash = !empty($sub_data['user_id']) ? substr(hash('sha256', $sub_data['user_id']), 0, 8) : 'N/A';
            admin_lab_log_custom("[TWITCH SYNC] Processed: user_id_hash={$user_id_hash}, tier={$tier}, level_slug={$level_slug}, subscription_type={$subscription_type}", 'subscription-sync.log');
        }
        
        // Parse dates
        $started_at = null;
        if (!empty($sub_data['created_at'])) {
            $started_at = date('Y-m-d H:i:s', strtotime($sub_data['created_at']));
        }
        
        // Twitch API doesn't provide expires_at directly, but we can calculate it
        // Subscriptions are typically monthly, so expires_at = started_at + 1 month
        $expires_at = null;
        if ($started_at) {
            // Add 1 month to started_at
            $expires_at = date('Y-m-d H:i:s', strtotime($started_at . ' +1 month'));
        }
        
        $subscriptions[] = [
            'provider_slug' => $provider_slug, // Original provider slug (e.g., twitch_me5rine) - will be normalized in admin_lab_save_subscription
            'external_user_id' => $sub_data['user_id'] ?? '', // Used to find linked account
            'external_username' => $sub_data['user_name'] ?? '', // Used to find linked account
            'external_subscription_id' => $sub_data['user_id'] . '_' . $channel_identifier . '_' . $level_slug . '_' . $subscription_type, // Unique subscription ID
            'level_slug' => $level_slug, // Links to subscription_levels table
            'status' => 'active',
            'started_at' => $started_at,
            'expires_at' => $expires_at,
            'metadata' => json_encode([
                'channel_id' => $channel_identifier,
                'channel_name' => $channel_name,
                'external_username' => $sub_data['user_name'] ?? '',
                'tier_raw' => $tier_raw,
                'tier' => $tier,
                'subscription_type' => $subscription_type, // gratuit or payant
                'is_gift' => $is_gift,
                'raw_api_data' => $sub_data, // Store complete API response for reference
            ]),
        ];
    }
    
    // Safe logging: only log counts, not user data
    $total_count = count($subscriptions);
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[TWITCH SYNC] Total subscriptions retrieved: {$total_count}", 'subscription-sync.log');
    }
    
    // In debug mode only: log anonymized summary
    if ($debug && $total_count > 0 && function_exists('admin_lab_log_custom')) {
        $summary = [];
        $sample_count = min(3, $total_count);
        for ($i = 0; $i < $sample_count; $i++) {
            $sub = $subscriptions[$i];
            $user_id_hash = !empty($sub['external_user_id']) ? substr(hash('sha256', $sub['external_user_id']), 0, 8) : 'N/A';
            $summary[] = [
                'user_id_hash' => $user_id_hash,
                'level_slug' => $sub['level_slug'] ?? 'N/A',
            ];
        }
        $summary_json = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        admin_lab_log_custom("[TWITCH SYNC] Sample (anonymized, {$sample_count}/{$total_count}): " . $summary_json, 'subscription-sync.log');
    }
    
    if ($total_count === 0) {
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[TWITCH SYNC] WARNING: No subscriptions found. This may be normal if the channel has no subscribers.", 'subscription-sync.log');
        }
    }
    
    return $subscriptions;
}


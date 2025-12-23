<?php
// File: modules/subscription/functions/subscription-sync-keycloak.php

if (!defined('ABSPATH')) exit;

/**
 * Extract and sync Keycloak identities to subscription_accounts
 * This function extracts provider identities from Keycloak claims and stores them
 */
function admin_lab_extract_keycloak_identities($user_id, $user_claim = null) {
    if (!$user_claim) {
        // Try to get claims from OpenID Connect Generic plugin
        if (function_exists('openid_connect_generic_get_user_claim')) {
            $user_claim = openid_connect_generic_get_user_claim($user_id);
        }
    }
    
    if (empty($user_claim)) {
        // Safe logging: only log user_id, not sensitive claims
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[KEYCLOAK SYNC] No user claims found for user {$user_id}", 'subscription-sync.log');
        }
        return;
    }
    
    // Safe logging: only log user_id, not full claims (may contain sensitive data)
    $debug = defined('WP_DEBUG') && WP_DEBUG;
    if ($debug && function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom("[KEYCLOAK SYNC] Extracting identities for user {$user_id}", 'subscription-sync.log');
        // Only log claim keys, not values (for debugging structure)
        $claim_keys = array_keys($user_claim);
        admin_lab_log_custom("[KEYCLOAK SYNC] Available claim keys: " . implode(', ', $claim_keys), 'subscription-sync.log');
    }
    
    // Map of Keycloak claim keys to provider slugs
    $provider_mapping = [
        'twitch_id' => 'twitch',
        'twitch_username' => 'twitch',
        'discord_id' => 'discord',
        'discord_username' => 'discord',
        'youtube_id' => 'youtube',
        'youtube_username' => 'youtube',
        'google_id' => 'google',
        'facebook_id' => 'facebook',
    ];
    
    $identities = [];
    
    // Extract Twitch identity
    // Try multiple possible claim names for Twitch ID
    $twitch_id = null;
    $twitch_username = '';
    
    // Common Keycloak claim names for Twitch
    $possible_twitch_id_keys = ['twitch_id', 'twitch_user_id', 'twitch_sub', 'identity_provider_twitch_id'];
    $possible_twitch_username_keys = ['twitch_username', 'twitch_login', 'twitch_name', 'preferred_username'];
    
    foreach ($possible_twitch_id_keys as $key) {
        if (!empty($user_claim[$key])) {
            $twitch_id = (string) $user_claim[$key];
            break;
        }
    }
    
    foreach ($possible_twitch_username_keys as $key) {
        if (!empty($user_claim[$key])) {
            $twitch_username = $user_claim[$key];
            break;
        }
    }
    
    // Safe logging: only in debug mode, and anonymize sensitive data
    $debug = defined('WP_DEBUG') && WP_DEBUG;
    if ($debug && function_exists('admin_lab_log_custom')) {
        $claim_keys = array_keys($user_claim);
        admin_lab_log_custom("[KEYCLOAK SYNC] Available claim keys: " . implode(', ', $claim_keys), 'subscription-sync.log');
        
        // Log Twitch-related keys only (not values)
        $twitch_related_keys = array_filter($claim_keys, function($key) {
            return stripos($key, 'twitch') !== false;
        });
        if (!empty($twitch_related_keys)) {
            admin_lab_log_custom("[KEYCLOAK SYNC] Twitch-related claim keys: " . implode(', ', $twitch_related_keys), 'subscription-sync.log');
        }
        
        if ($twitch_id) {
            // Anonymize: only log hash of ID, not the ID itself
            $twitch_id_hash = substr(hash('sha256', $twitch_id), 0, 8);
            admin_lab_log_custom("[KEYCLOAK SYNC] Found Twitch ID (hash: {$twitch_id_hash})", 'subscription-sync.log');
        } else {
            admin_lab_log_custom("[KEYCLOAK SYNC] No Twitch ID found in claims", 'subscription-sync.log');
        }
    }
    
    if ($twitch_id) {
        $identities['twitch'] = [
            'external_user_id' => $twitch_id,
            'external_username' => $twitch_username,
            'keycloak_identity_id' => $user_claim['sub'] ?? null,
        ];
    }
    
    // Extract Discord identity
    if (!empty($user_claim['discord_id'])) {
        $identities['discord'] = [
            'external_user_id' => (string) $user_claim['discord_id'],
            'external_username' => $user_claim['discord_username'] ?? $user_claim['discord_name'] ?? '',
            'keycloak_identity_id' => $user_claim['sub'] ?? null,
        ];
        // Safe logging: anonymize Discord ID
        $debug = defined('WP_DEBUG') && WP_DEBUG;
        if ($debug && function_exists('admin_lab_log_custom')) {
            $discord_id_hash = substr(hash('sha256', $user_claim['discord_id']), 0, 8);
            admin_lab_log_custom("[KEYCLOAK SYNC] Found Discord ID (hash: {$discord_id_hash})", 'subscription-sync.log');
        }
    }
    
    // Extract YouTube identity (Google ID is used for YouTube)
    // Try both youtube_id and google_id
    $youtube_id = $user_claim['youtube_id'] ?? $user_claim['google_id'] ?? null;
    if ($youtube_id) {
        $youtube_username = $user_claim['youtube_username'] ?? $user_claim['youtube_name'] ?? $user_claim['google_email'] ?? '';
        $identities['youtube'] = [
            'external_user_id' => (string) $youtube_id,
            'external_username' => $youtube_username,
            'keycloak_identity_id' => $user_claim['sub'] ?? null,
        ];
        // Safe logging: anonymize YouTube/Google ID
        $debug = defined('WP_DEBUG') && WP_DEBUG;
        if ($debug && function_exists('admin_lab_log_custom')) {
            $youtube_id_hash = substr(hash('sha256', $youtube_id), 0, 8);
            admin_lab_log_custom("[KEYCLOAK SYNC] Found YouTube/Google ID (hash: {$youtube_id_hash})", 'subscription-sync.log');
        }
    }
    
    // Try to get access token from Keycloak session or user claims
    // Note: Keycloak typically doesn't provide provider tokens in claims
    // Tokens must be obtained via OAuth flow with each provider
    
    // Save each identity
    foreach ($identities as $provider_slug => $identity_data) {
        $account_data = array_merge($identity_data, [
            'is_active' => 1,
            'last_sync_at' => current_time('mysql'),
        ]);
        
        $account_id = admin_lab_link_subscription_account(
            $user_id,
            $provider_slug,
            $identity_data['external_user_id'],
            $account_data
        );
        
        // Safe logging: anonymize external_user_id
        if ($account_id) {
            $debug = defined('WP_DEBUG') && WP_DEBUG;
            if ($debug && function_exists('admin_lab_log_custom')) {
                $external_id_hash = !empty($identity_data['external_user_id']) ? substr(hash('sha256', $identity_data['external_user_id']), 0, 8) : 'N/A';
                admin_lab_log_custom("[KEYCLOAK SYNC] Linked {$provider_slug} account for user {$user_id} (external_id_hash: {$external_id_hash})", 'subscription-sync.log');
            }
        } else {
            if (function_exists('admin_lab_log_custom')) {
                admin_lab_log_custom("[KEYCLOAK SYNC] Failed to link {$provider_slug} account for user {$user_id}", 'subscription-sync.log');
            }
        }
    }
}

/**
 * Get access token from Keycloak session
 * This attempts to retrieve the access token from the OpenID Connect session
 */
function admin_lab_get_keycloak_access_token($user_id = null) {
    // Try to get token from OpenID Connect Generic plugin
    if (function_exists('openid_connect_generic_get_access_token')) {
        return openid_connect_generic_get_access_token($user_id);
    }
    
    // Try to get from session/transient
    if ($user_id) {
        $token = get_transient('keycloak_access_token_' . $user_id);
        if ($token) {
            return $token;
        }
    }
    
    // Try to get from current session
    if (isset($_SESSION['openid-connect-generic-access-token'])) {
        return $_SESSION['openid-connect-generic-access-token'];
    }
    
    // Try to get from user meta (if stored)
    if ($user_id) {
        $token = get_user_meta($user_id, 'keycloak_access_token', true);
        if ($token) {
            return $token;
        }
    }
    
    return null;
}

/**
 * Sync Keycloak identities on user login
 */
add_action('openid-connect-generic-update-user-using-current-claim', function($user, $user_claim) {
    if (!$user || !$user->ID) {
        return;
    }
    
    // Extract and sync identities
    admin_lab_extract_keycloak_identities($user->ID, $user_claim);
}, 20, 2);

/**
 * Sync Keycloak identities on WordPress login (fallback)
 */
add_action('wp_login', function($user_login, $user) {
    if (!$user || !$user->ID) {
        return;
    }
    
    // Try to get claims
    $user_claim = null;
    if (function_exists('openid_connect_generic_get_user_claim')) {
        $user_claim = openid_connect_generic_get_user_claim($user->ID);
    }
    
    if ($user_claim) {
        admin_lab_extract_keycloak_identities($user->ID, $user_claim);
    }
}, 20, 2);


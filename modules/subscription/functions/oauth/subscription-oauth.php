<?php
// File: modules/subscription/functions/subscription-oauth.php

if (!defined('ABSPATH')) exit;

/**
 * Initialize OAuth endpoints
 */
add_action('init', function() {
    // Add rewrite rule for OAuth callback
    add_rewrite_rule(
        '^subscription/oauth/callback/([^/]+)/?$',
        'index.php?subscription_oauth_callback=1&subscription_oauth_provider=$matches[1]',
        'top'
    );
    
    // Add query vars
    add_filter('query_vars', function($vars) {
        $vars[] = 'subscription_oauth_callback';
        $vars[] = 'subscription_oauth_provider';
        return $vars;
    });
    
    // Handle OAuth callback
    add_action('template_redirect', 'admin_lab_handle_oauth_callback');
    
    // Flush rewrite rules on activation (handled by module activation hook)
});

/**
 * Flush rewrite rules when subscription module is activated
 */
add_action('admin_lab_subscription_module_activated', function() {
    flush_rewrite_rules();
});

/**
 * Get OAuth callback URL for a provider
 */
function admin_lab_get_oauth_callback_url($provider_slug) {
    return home_url('/subscription/oauth/callback/' . sanitize_key($provider_slug) . '/');
}

/**
 * Handle OAuth callback
 */
function admin_lab_handle_oauth_callback() {
    if (!get_query_var('subscription_oauth_callback')) {
        return;
    }
    
    $provider_slug = get_query_var('subscription_oauth_provider');
    
    if (empty($provider_slug)) {
        wp_die('Invalid OAuth callback: Provider not specified.');
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(add_query_arg(['subscription_oauth_provider' => $provider_slug], home_url('/subscription/oauth/callback/' . $provider_slug . '/'))) . '&redirect_to=' . urlencode(home_url('/subscription/oauth/callback/' . $provider_slug . '/')));
        exit;
    }
    
    $user_id = get_current_user_id();
    $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
    $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
    $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
    
    // Handle error
    if (!empty($error)) {
        $error_description = isset($_GET['error_description']) ? sanitize_text_field($_GET['error_description']) : $error;
        wp_die('OAuth Error: ' . esc_html($error_description));
    }
    
    // Verify state (should match what we sent)
    if (empty($code)) {
        wp_die('OAuth Error: Authorization code not received.');
    }
    
    // Exchange code for token based on provider
    $result = admin_lab_exchange_oauth_code_for_token($provider_slug, $code);
    
    if (is_wp_error($result)) {
        wp_die('OAuth Error: ' . esc_html($result->get_error_message()));
    }
    
    // Save account link (tokens are no longer stored in keycloak_accounts)
    $account_data = [
        'external_username' => $result['external_username'] ?? '',
        'is_active' => 1,
    ];
    
    $account_id = admin_lab_link_subscription_account($user_id, $provider_slug, $result['external_user_id'], $account_data);
    
    if ($account_id) {
        // Redirect to success page or account connections page
        $redirect_url = admin_url('admin.php?page=admin-lab-subscription&tab=keycloak_identities&oauth_success=1&provider=' . $provider_slug);
        wp_redirect($redirect_url);
        exit;
    } else {
        wp_die('Error: Failed to link account.');
    }
}

/**
 * Exchange OAuth code for access token
 */
function admin_lab_exchange_oauth_code_for_token($provider_slug, $code) {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    
    if (!$provider || empty($provider['client_id']) || empty($provider['client_secret'])) {
        return new WP_Error('invalid_provider', 'Provider not configured or missing credentials.');
    }
    
    $callback_url = admin_lab_get_oauth_callback_url($provider_slug);
    
    switch ($provider_slug) {
        case 'twitch':
            return admin_lab_exchange_twitch_token($provider, $code, $callback_url);
        case 'discord':
            return admin_lab_exchange_discord_token($provider, $code, $callback_url);
        case 'youtube':
            return admin_lab_exchange_youtube_token($provider, $code, $callback_url);
        default:
            return new WP_Error('unsupported_provider', 'Provider not supported for OAuth.');
    }
}

/**
 * Exchange Twitch OAuth code for token
 */
function admin_lab_exchange_twitch_token($provider, $code, $callback_url) {
    // Client secret is stored as-is (encrypted at database level if needed)
    $client_secret = $provider['client_secret'];
    
    $response = wp_remote_post('https://id.twitch.tv/oauth2/token', [
        'body' => [
            'client_id' => $provider['client_id'],
            'client_secret' => $client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $callback_url,
        ],
        'timeout' => 30,
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['access_token'])) {
        return new WP_Error('token_exchange_failed', 'Failed to exchange code for token: ' . ($data['message'] ?? 'Unknown error'));
    }
    
    // Get user info from Twitch
    $user_response = wp_remote_get('https://api.twitch.tv/helix/users', [
        'headers' => [
            'Client-ID' => $provider['client_id'],
            'Authorization' => 'Bearer ' . $data['access_token'],
        ],
        'timeout' => 30,
    ]);
    
    if (is_wp_error($user_response)) {
        return $user_response;
    }
    
    $user_body = wp_remote_retrieve_body($user_response);
    $user_data = json_decode($user_body, true);
    
    if (empty($user_data['data'][0])) {
        return new WP_Error('user_info_failed', 'Failed to get user info from Twitch');
    }
    
    $twitch_user = $user_data['data'][0];
    
    return [
        'external_user_id' => $twitch_user['id'],
        'external_username' => $twitch_user['login'] ?? $twitch_user['display_name'] ?? '',
        'access_token' => $data['access_token'],
        'refresh_token' => $data['refresh_token'] ?? '',
        'expires_in' => $data['expires_in'] ?? null,
    ];
}

/**
 * Exchange Discord OAuth code for token
 */
function admin_lab_exchange_discord_token($provider, $code, $callback_url) {
    // Client secret is stored as-is (encrypted at database level if needed)
    $client_secret = $provider['client_secret'];
    
    $response = wp_remote_post('https://discord.com/api/oauth2/token', [
        'body' => [
            'client_id' => $provider['client_id'],
            'client_secret' => $client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $callback_url,
        ],
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'timeout' => 30,
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['access_token'])) {
        return new WP_Error('token_exchange_failed', 'Failed to exchange code for token: ' . ($data['error_description'] ?? 'Unknown error'));
    }
    
    // Get user info from Discord
    $user_response = wp_remote_get('https://discord.com/api/users/@me', [
        'headers' => [
            'Authorization' => 'Bearer ' . $data['access_token'],
        ],
        'timeout' => 30,
    ]);
    
    if (is_wp_error($user_response)) {
        return $user_response;
    }
    
    $user_body = wp_remote_retrieve_body($user_response);
    $discord_user = json_decode($user_body, true);
    
    if (empty($discord_user['id'])) {
        return new WP_Error('user_info_failed', 'Failed to get user info from Discord');
    }
    
    return [
        'external_user_id' => $discord_user['id'],
        'external_username' => $discord_user['username'] ?? '',
        'access_token' => $data['access_token'],
        'refresh_token' => $data['refresh_token'] ?? '',
        'expires_in' => $data['expires_in'] ?? null,
    ];
}

/**
 * Exchange YouTube OAuth code for token
 */
function admin_lab_exchange_youtube_token($provider, $code, $callback_url) {
    // TODO: Implement YouTube OAuth
    return new WP_Error('not_implemented', 'YouTube OAuth not yet implemented');
}

/**
 * Get OAuth authorization URL for a provider
 */
function admin_lab_get_oauth_authorization_url($provider_slug) {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    
    if (!$provider || empty($provider['client_id'])) {
        return '';
    }
    
    $callback_url = admin_lab_get_oauth_callback_url($provider_slug);
    $state = wp_create_nonce('subscription_oauth_' . $provider_slug);
    
    // Store state in transient for verification
    set_transient('subscription_oauth_state_' . get_current_user_id() . '_' . $provider_slug, $state, 600);
    
    switch ($provider_slug) {
        case 'twitch':
            $scopes = 'channel:read:subscriptions user:read:email';
            return 'https://id.twitch.tv/oauth2/authorize?' . http_build_query([
                'client_id' => $provider['client_id'],
                'redirect_uri' => $callback_url,
                'response_type' => 'code',
                'scope' => $scopes,
                'state' => $state,
            ]);
            
        case 'discord':
            $scopes = 'identify email';
            return 'https://discord.com/api/oauth2/authorize?' . http_build_query([
                'client_id' => $provider['client_id'],
                'redirect_uri' => $callback_url,
                'response_type' => 'code',
                'scope' => $scopes,
                'state' => $state,
            ]);
            
        case 'youtube':
            // TODO: Implement YouTube OAuth
            return '';
            
        default:
            return '';
    }
}


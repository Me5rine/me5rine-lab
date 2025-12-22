<?php
// File: modules/subscription/functions/oauth/subscription-twitch-oauth.php

if (!defined('ABSPATH')) exit;

/**
 * Get provider setting
 */
function admin_lab_get_provider_setting($provider_slug, $key, $default = null) {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return $default;
    }
    
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $value = $settings[$key] ?? $default;
    
    // Decrypt sensitive data if encrypted
    if ($value && function_exists('admin_lab_decrypt_data')) {
        if (in_array($key, ['broadcaster_access_token', 'broadcaster_refresh_token', 'creator_access_token', 'creator_refresh_token', 'client_secret'])) {
            $decrypted = admin_lab_decrypt_data($value);
            if ($decrypted !== $value) {
                $value = $decrypted;
            }
        }
    }
    
    return $value;
}

/**
 * Set provider setting
 */
function admin_lab_set_provider_setting($provider_slug, $key, $value) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_providers');
    
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return false;
    }
    
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $settings[$key] = $value;
    
    // Encrypt sensitive data if function exists
    if (function_exists('admin_lab_encrypt_data')) {
        if (in_array($key, ['broadcaster_access_token', 'broadcaster_refresh_token', 'creator_access_token', 'creator_refresh_token', 'client_secret'])) {
            $settings[$key] = admin_lab_encrypt_data($value);
        }
    }
    
    return $wpdb->update(
        $table,
        ['settings' => maybe_serialize($settings)],
        ['id' => $provider['id']]
    );
}

/**
 * URL de callback déclarée dans Twitch Dev Console
 */
function admin_lab_twitch_redirect_uri($provider_slug = null) {
    // IMPORTANT: doit matcher EXACTEMENT l'URL enregistrée dans Twitch
    $action = 'admin_lab_twitch_oauth_callback';
    
    // Try to find provider if slug provided, or find any Twitch provider
    if (!$provider_slug) {
        $twitch_providers = admin_lab_get_subscription_providers();
        foreach ($twitch_providers as $p) {
            if (strpos($p['provider_slug'], 'twitch') === 0) {
                $provider_slug = $p['provider_slug'];
                break;
            }
        }
    }
    
    // Check if provider has a public_base_url setting
    if ($provider_slug) {
        $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
        if ($provider) {
            $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
            $public_base = $settings['public_base_url'] ?? '';
            if ($public_base) {
                // Use public base URL if configured
                return rtrim($public_base, '/') . '/wp-admin/admin-post.php?action=' . urlencode($action);
            }
        }
    }
    
    // Default: use site_url()
    return admin_url('admin-post.php?action=' . $action);
}

/**
 * Lance l'OAuth Twitch (Authorization Code flow)
 * URL: /wp-admin/admin-post.php?action=admin_lab_twitch_oauth_start
 */
add_action('admin_post_admin_lab_twitch_oauth_start', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    // Récupère Client ID depuis provider settings
    // Try to find any Twitch provider (supports 'twitch', 'twitch_me5rine', etc.)
    $twitch_providers = admin_lab_get_subscription_providers();
    $provider = null;
    foreach ($twitch_providers as $p) {
        if (strpos($p['provider_slug'], 'twitch') === 0) {
            $provider = $p;
            break;
        }
    }
    
    if (!$provider || empty($provider['client_id'])) {
        wp_die('Twitch Client ID manquant. Veuillez configurer le provider Twitch d\'abord.');
    }
    
    $provider_slug = $provider['provider_slug'];

    $client_id = $provider['client_id'];
    
    $state = wp_generate_password(32, false, false);
    set_transient('admin_lab_twitch_oauth_state', $state, 10 * MINUTE_IN_SECONDS);
    set_transient('admin_lab_twitch_oauth_provider_slug', $provider_slug, 10 * MINUTE_IN_SECONDS);

    $params = [
        'client_id'     => $client_id,
        'redirect_uri'  => admin_lab_twitch_redirect_uri($provider_slug),
        'response_type' => 'code',
        'scope'         => 'channel:read:subscriptions',
        'state'         => $state,
        'force_verify'  => 'true', // optionnel: force le consent
    ];

    $url = 'https://id.twitch.tv/oauth2/authorize?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    wp_redirect($url);
    exit;
});

/**
 * Callback OAuth Twitch
 * URL: /wp-admin/admin-post.php?action=admin_lab_twitch_oauth_callback&code=...&state=...
 */
add_action('admin_post_admin_lab_twitch_oauth_callback', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $code  = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
    $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';

    $expected_state = get_transient('admin_lab_twitch_oauth_state');
    $provider_slug = get_transient('admin_lab_twitch_oauth_provider_slug');
    delete_transient('admin_lab_twitch_oauth_state');
    delete_transient('admin_lab_twitch_oauth_provider_slug');

    if (!$code || !$state || !$expected_state || !hash_equals($expected_state, $state)) {
        wp_die('OAuth state invalide (CSRF).');
    }
    
    // Fallback: if provider_slug not in transient, try to find any Twitch provider
    if (!$provider_slug) {
        $twitch_providers = admin_lab_get_subscription_providers();
        foreach ($twitch_providers as $p) {
            if (strpos($p['provider_slug'], 'twitch') === 0) {
                $provider_slug = $p['provider_slug'];
                break;
            }
        }
    }
    
    if (!$provider_slug) {
        wp_die('Provider Twitch non trouvé.');
    }

    // Get provider using the slug from transient or find any Twitch provider
    if ($provider_slug) {
        $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    }
    
    if (!$provider) {
        // Fallback: try to find any Twitch provider
        $twitch_providers = admin_lab_get_subscription_providers();
        foreach ($twitch_providers as $p) {
            if (strpos($p['provider_slug'], 'twitch') === 0) {
                $provider = $p;
                $provider_slug = $p['provider_slug'];
                break;
            }
        }
    }
    
    if (!$provider || empty($provider['client_id']) || empty($provider['client_secret'])) {
        wp_die('Twitch Client ID/Secret manquant. Veuillez configurer le provider Twitch d\'abord.');
    }
    
    if (!$provider_slug) {
        $provider_slug = $provider['provider_slug'];
    }

    $client_id = $provider['client_id'];
    $client_secret = $provider['client_secret'];
    
    // Decrypt client_secret if encrypted
    if (function_exists('admin_lab_decrypt_data')) {
        $decrypted = admin_lab_decrypt_data($client_secret);
        if ($decrypted !== $client_secret) {
            $client_secret = $decrypted;
        }
    }

    $response = wp_remote_post('https://id.twitch.tv/oauth2/token', [
        'timeout' => 20,
        'body' => [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => admin_lab_twitch_redirect_uri($provider_slug),
        ],
    ]);

    if (is_wp_error($response)) {
        wp_die('Erreur token Twitch: ' . esc_html($response->get_error_message()));
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data['access_token'])) {
        wp_die('Réponse token invalide: ' . esc_html(wp_remote_retrieve_body($response)));
    }

    $access_token  = $data['access_token'];
    $refresh_token = $data['refresh_token'] ?? '';
    $expires_in    = (int)($data['expires_in'] ?? 0);

    // Stockage provider settings
    admin_lab_set_provider_setting($provider_slug, 'broadcaster_access_token', $access_token);
    if (!empty($refresh_token)) {
        admin_lab_set_provider_setting($provider_slug, 'broadcaster_refresh_token', $refresh_token);
    }
    admin_lab_set_provider_setting($provider_slug, 'broadcaster_token_expires_at', time() + max(0, $expires_in - 60)); // marge 60s

    // Optionnel mais utile : vérifier quel broadcaster a autorisé (GET /users)
    // et stocker broadcaster_id pour éviter les confusions.
    $user = admin_lab_twitch_get_authed_user($access_token, $client_id);
    if (!empty($user['id'])) {
        admin_lab_set_provider_setting($provider_slug, 'broadcaster_user_id', (string)$user['id']);
        admin_lab_set_provider_setting($provider_slug, 'broadcaster_login', (string)($user['login'] ?? ''));
    }

    wp_redirect(admin_url('admin.php?page=admin-lab-subscription&tab=providers&oauth=twitch_ok'));
    exit;
});

/**
 * Get authenticated user info from Twitch
 */
function admin_lab_twitch_get_authed_user($access_token, $client_id) {
    $res = wp_remote_get('https://api.twitch.tv/helix/users', [
        'timeout' => 20,
        'headers' => [
            'Client-ID' => $client_id,
            'Authorization' => 'Bearer ' . $access_token,
        ],
    ]);
    if (is_wp_error($res)) {
        return null;
    }
    $body = json_decode(wp_remote_retrieve_body($res), true);
    return $body['data'][0] ?? null;
}

/**
 * Ensure broadcaster token is valid (refresh if needed)
 * @param string $provider_slug Provider slug (default: 'twitch')
 */
function admin_lab_twitch_ensure_broadcaster_token($provider_slug = 'twitch') {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return new WP_Error('twitch_no_provider', "Provider '{$provider_slug}' not configured");
    }

    $client_id = $provider['client_id'];
    $client_secret = $provider['client_secret'];
    
    // Decrypt client_secret if encrypted
    if (function_exists('admin_lab_decrypt_data')) {
        $decrypted = admin_lab_decrypt_data($client_secret);
        if ($decrypted !== $client_secret) {
            $client_secret = $decrypted;
        }
    }

    $access  = admin_lab_get_provider_setting($provider_slug, 'broadcaster_access_token');
    $refresh = admin_lab_get_provider_setting($provider_slug, 'broadcaster_refresh_token');
    $exp_at  = (int) admin_lab_get_provider_setting($provider_slug, 'broadcaster_token_expires_at');

    if ($access && $exp_at && time() < $exp_at) {
        // Decrypt access token if encrypted
        if (function_exists('admin_lab_decrypt_data')) {
            $decrypted = admin_lab_decrypt_data($access);
            if ($decrypted !== $access) {
                return $decrypted;
            }
        }
        return $access; // encore valide
    }

    if (empty($refresh)) {
        return new WP_Error('twitch_no_refresh', 'Token Twitch expiré et aucun refresh_token. Reconnecte le broadcaster.');
    }

    // Decrypt refresh token if encrypted
    if (function_exists('admin_lab_decrypt_data')) {
        $decrypted = admin_lab_decrypt_data($refresh);
        if ($decrypted !== $refresh) {
            $refresh = $decrypted;
        }
    }

    $response = wp_remote_post('https://id.twitch.tv/oauth2/token', [
        'timeout' => 20,
        'body' => [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ],
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data['access_token'])) {
        return new WP_Error('twitch_refresh_failed', 'Refresh token Twitch échoué: ' . wp_remote_retrieve_body($response));
    }

    admin_lab_set_provider_setting($provider_slug, 'broadcaster_access_token', $data['access_token']);
    if (!empty($data['refresh_token'])) {
        admin_lab_set_provider_setting($provider_slug, 'broadcaster_refresh_token', $data['refresh_token']);
    }
    $expires_in = (int)($data['expires_in'] ?? 0);
    admin_lab_set_provider_setting($provider_slug, 'broadcaster_token_expires_at', time() + max(0, $expires_in - 60));

    return $data['access_token'];
}


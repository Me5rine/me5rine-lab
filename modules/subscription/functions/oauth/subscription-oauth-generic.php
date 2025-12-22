<?php
// File: modules/subscription/functions/subscription-oauth-generic.php

if (!defined('ABSPATH')) exit;

/**
 * Resolve a concrete provider slug for an OAuth flow.
 * Useful when "youtube" is a base type but you store multiple instances like "youtube_me5rine_gaming".
 *
 * - If a ?provider=... query arg is present and matches the same prefix, use it.
 * - Otherwise, keep the given $provider_slug.
 */
function admin_lab_provider_oauth_resolve_slug($provider_slug) {
    $provider_slug = (string) $provider_slug;

    if (!empty($_GET['provider'])) {
        $requested = sanitize_text_field(wp_unslash($_GET['provider']));

        // Only allow "same family" override to avoid abusing other providers.
        // Example: base "youtube" can become "youtube_me5rine_gaming".
        if ($requested && strpos($requested, $provider_slug) === 0) {
            return $requested;
        }
    }

    return $provider_slug;
}

/**
 * Generic OAuth redirect URI for a provider
 */
function admin_lab_provider_oauth_redirect_uri($provider_slug) {
    $provider_slug = admin_lab_provider_oauth_resolve_slug($provider_slug);

    $action = 'admin_lab_' . $provider_slug . '_oauth_callback';

    // Check if provider has a public_base_url setting
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if ($provider) {
        $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
        $public_base = $settings['public_base_url'] ?? '';
        if ($public_base) {
            // Use public base URL if configured
            return rtrim($public_base, '/') . '/wp-admin/admin-post.php?action=' . urlencode($action);
        }
    }

    // Default: use admin_url()
    return admin_url('admin-post.php?action=' . $action);
}

/**
 * Normalize scopes for providers that expect a different delimiter.
 * - Google expects space-separated scopes in the `scope` query param.
 * - Many UIs store them comma-separated; convert commas to spaces.
 */
function admin_lab_provider_oauth_normalize_scopes($provider_slug, $scopes_raw) {
    $scopes_raw = (string) $scopes_raw;

    // Trim and normalize whitespace
    $scopes_raw = trim($scopes_raw);

    // If comma-separated, turn into space-separated
    if (strpos($scopes_raw, ',') !== false) {
        $parts = array_filter(array_map('trim', explode(',', $scopes_raw)));
        $scopes_raw = implode(' ', $parts);
    }

    // For safety, collapse multiple spaces
    $scopes_raw = preg_replace('/\s+/', ' ', $scopes_raw);

    return $scopes_raw;
}

/**
 * Generic OAuth start handler
 * URL: /wp-admin/admin-post.php?action=admin_lab_{provider}_oauth_start
 */
function admin_lab_provider_oauth_start_handler($provider_slug) {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    // Resolve to concrete provider instance if ?provider= is present
    $provider_slug = admin_lab_provider_oauth_resolve_slug($provider_slug);

    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider || empty($provider['client_id'])) {
        wp_die("Provider '{$provider_slug}' Client ID missing. Please configure the provider first.");
    }

    $client_id = $provider['client_id'];

    // Generate state for CSRF protection
    $state = wp_generate_password(32, false, false);

    // Namespace transients by resolved slug to avoid collisions (youtube vs youtube_xxx)
    set_transient('admin_lab_' . $provider_slug . '_oauth_state', $state, 10 * MINUTE_IN_SECONDS);
    set_transient('admin_lab_' . $provider_slug . '_oauth_provider_slug', $provider_slug, 10 * MINUTE_IN_SECONDS);

    // Get OAuth configuration from provider settings or use defaults
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $auth_url = $settings['oauth_authorize_url'] ?? '';
    $scopes   = $settings['oauth_scopes'] ?? '';
    $redirect_uri = admin_lab_provider_oauth_redirect_uri($provider_slug);

    if (empty($auth_url)) {
        wp_die("OAuth authorization URL not configured for provider '{$provider_slug}'.");
    }

    $params = [
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'state'         => $state,
    ];

    $scopes = admin_lab_provider_oauth_normalize_scopes($provider_slug, $scopes);
    if (!empty($scopes)) {
        $params['scope'] = $scopes;
    }

    // Google-specific extras (harmless for others, but we only apply when provider starts with "youtube")
    if (strpos($provider_slug, 'youtube') === 0) {
        $params['access_type'] = 'offline';
        $params['prompt']      = 'consent';
        // If you want incremental auth: $params['include_granted_scopes'] = 'true';
    }

    $url = $auth_url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    wp_redirect($url);
    exit;
}

/**
 * Generic OAuth callback handler
 * URL: /wp-admin/admin-post.php?action=admin_lab_{provider}_oauth_callback
 */
function admin_lab_provider_oauth_callback_handler($provider_slug) {

    // Resolve to concrete provider instance if ?provider= is present
    $provider_slug = admin_lab_provider_oauth_resolve_slug($provider_slug);

    $code  = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
    $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';

    $expected_state = get_transient('admin_lab_' . $provider_slug . '_oauth_state');
    $stored_provider_slug = get_transient('admin_lab_' . $provider_slug . '_oauth_provider_slug');

    delete_transient('admin_lab_' . $provider_slug . '_oauth_state');
    delete_transient('admin_lab_' . $provider_slug . '_oauth_provider_slug');

    if (!$code || !$state || !$expected_state || !hash_equals($expected_state, $state)) {
        wp_die('OAuth state invalid (CSRF).');
    }

    // (Optional) sanity check
    if (!empty($stored_provider_slug) && $stored_provider_slug !== $provider_slug) {
        wp_die('OAuth provider mismatch.');
    }

    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider || empty($provider['client_id']) || empty($provider['client_secret'])) {
        wp_die("Provider '{$provider_slug}' Client ID/Secret missing.");
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

    // Get OAuth token URL from provider settings
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    $token_url = $settings['oauth_token_url'] ?? '';
    $redirect_uri = admin_lab_provider_oauth_redirect_uri($provider_slug);

    if (empty($token_url)) {
        wp_die("OAuth token URL not configured for provider '{$provider_slug}'.");
    }

    $response = wp_remote_post($token_url, [
        'timeout' => 20,
        'body' => [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirect_uri,
        ],
    ]);

    if (is_wp_error($response)) {
        wp_die('OAuth token error: ' . esc_html($response->get_error_message()));
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['access_token'])) {
        wp_die('Invalid token response: ' . esc_html($body));
    }

    $access_token  = $data['access_token'];
    $refresh_token = $data['refresh_token'] ?? '';
    $expires_in    = (int)($data['expires_in'] ?? 0);

    // Store provider settings (encrypted by your setter)
    admin_lab_set_provider_setting($provider_slug, 'access_token', $access_token);
    if (!empty($refresh_token)) {
        admin_lab_set_provider_setting($provider_slug, 'refresh_token', $refresh_token);
    }
    if ($expires_in > 0) {
        admin_lab_set_provider_setting($provider_slug, 'token_expires_at', time() + max(0, $expires_in - 60)); // 60s margin
    }

    // Trigger hook for provider-specific post-OAuth actions (e.g., store creator info)
    do_action('admin_lab_provider_oauth_success', $provider_slug);

    wp_redirect(admin_url('admin.php?page=admin-lab-subscription&tab=providers&oauth=' . urlencode($provider_slug) . '_ok'));
    exit;
}

/**
 * Register OAuth handlers for each provider base type.
 * Note: YouTube multi-instance is handled via ?provider=... with the patches above.
 */
$providers_with_oauth = ['discord', 'youtube', 'patreon', 'tipeee'];
foreach ($providers_with_oauth as $provider_slug) {
    add_action('admin_post_admin_lab_' . $provider_slug . '_oauth_start', function() use ($provider_slug) {
        admin_lab_provider_oauth_start_handler($provider_slug);
    });

    add_action('admin_post_admin_lab_' . $provider_slug . '_oauth_callback', function() use ($provider_slug) {
        admin_lab_provider_oauth_callback_handler($provider_slug);
    });
}

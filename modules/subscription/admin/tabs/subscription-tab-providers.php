<?php
// File: modules/subscription/admin/tabs/subscription-tab-providers.php

if (!defined('ABSPATH')) exit;

/**
 * Tab: Providers
 * Subscription providers management (Twitch, YouTube, Discord, etc.)
 */
function admin_lab_subscription_tab_providers() {
    global $wpdb;

    $table = admin_lab_getTable('subscription_providers');

    /* ------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------ */

    /**
     * Safe get settings array from provider row.
     */
    $get_settings_array = function($provider_row) {
        $raw = !empty($provider_row['settings']) ? $provider_row['settings'] : [];

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $maybe = maybe_unserialize($raw);

            // If still a string, it might be JSON
            if (is_string($maybe)) {
                $decoded = json_decode($maybe, true);
                return is_array($decoded) ? $decoded : [];
            }

            return is_array($maybe) ? $maybe : [];
        }

        return [];
    };

    /**
     * Sanitize settings array recursively (simple approach).
     */
    $sanitize_settings = function($settings) {
        if (!is_array($settings)) {
            return [];
        }

        $out = [];
        foreach ($settings as $k => $v) {
            $key = sanitize_key($k);

            if (is_array($v)) {
                $out[$key] = $v; // keep arrays as-is (if you need deeper sanitization later)
                continue;
            }

            // Specific known keys
            if ($key === 'bot_api_url') {
                $url = trim((string) $v);
            
                // Si vide: OK (ça permet d'effacer)
                if ($url === '') {
                    $out[$key] = '';
                    continue;
                }
            
                // Forcer https si l'utilisateur tape juste "bots.me5rine-lab.com"
                if (!preg_match('#^https?://#i', $url)) {
                    $url = 'https://' . ltrim($url, '/');
                }
            
                // Nettoyage + validation WordPress
                $url = esc_url_raw($url);
            
                // Sécurité: on n'accepte QUE https
                if ($url && stripos($url, 'https://') !== 0) {
                    $url = '';
                }
            
                // Optionnel: enlever le slash final pour éviter doubles // dans add_query_arg
                $url = rtrim($url, '/');
            
                $out[$key] = $url;
                continue;
            }            

            if ($key === 'bot_api_key') {
                // Encrypt bot_api_key before storing (same as client_secret)
                $key_value = trim((string) $v);
                if (!empty($key_value)) {
                    if (function_exists('admin_lab_encrypt_data')) {
                        $encrypted = admin_lab_encrypt_data($key_value);
                        if ($encrypted && $encrypted !== $key_value) {
                            $out[$key] = $encrypted;
                        } else {
                            // Encryption failed or returned same value
                            $out[$key] = $key_value; // Fallback to plain text (should not happen)
                        }
                    } else {
                        $out[$key] = $key_value; // Fallback to plain text
                    }
                } else {
                    // Empty key, don't set it (will keep existing encrypted value)
                    // Don't set $out[$key] so it won't override existing value
                }
                // Do NOT log the actual key value
                continue;
            }

            if ($key === 'debug_log') {
                $out[$key] = !empty($v) ? 1 : 0;
                continue;
            }

            if ($key === 'role_mappings') {
                // Handle role mappings: convert from [role_id => level_slug] format
                // Input format: role_mappings[role_id] = role_id, role_mappings_level[role_id] = level_slug
                // We need to process this separately after the loop
                continue;
            }

            // Default sanitize
            $out[$key] = sanitize_text_field((string) $v);
        }

        // Process role_mappings if present
        // Format from form: role_mappings[role_id_0] = role_id, role_mappings_level[role_id_0] = level_slug
        // OR: role_mappings[actual_role_id] = actual_role_id, role_mappings_level[actual_role_id] = level_slug
        if (isset($settings['role_mappings']) && is_array($settings['role_mappings'])) {
            $role_mappings = [];
            $role_mappings_level = isset($settings['role_mappings_level']) && is_array($settings['role_mappings_level']) 
                ? $settings['role_mappings_level'] 
                : [];
            
            foreach ($settings['role_mappings'] as $key => $role_id_value) {
                // $key can be "role_id_0" or an actual role_id
                // $role_id_value is the actual role_id
                $role_id = sanitize_text_field((string) $role_id_value);
                
                // Try to find the corresponding level_slug
                // First try with the same key
                $level_slug = isset($role_mappings_level[$key]) 
                    ? sanitize_text_field((string) $role_mappings_level[$key]) 
                    : '';
                
                // If not found, try with the role_id as key (for existing mappings)
                if (empty($level_slug) && isset($role_mappings_level[$role_id])) {
                    $level_slug = sanitize_text_field((string) $role_mappings_level[$role_id]);
                }
                
                if (!empty($role_id) && !empty($level_slug)) {
                    $role_mappings[$role_id] = $level_slug;
                }
            }
            
            if (!empty($role_mappings)) {
                $out['role_mappings'] = $role_mappings;
            }
        }

        return $out;
    };

    /* ------------------------------------------------------------
     * Actions
     * ------------------------------------------------------------ */

    // Save provider
    if (
        isset($_POST['action']) &&
        $_POST['action'] === 'save_provider' &&
        check_admin_referer('subscription_provider_action')
    ) {
        $provider_id   = isset($_POST['provider_id']) ? intval($_POST['provider_id']) : 0;
        $provider_slug = sanitize_text_field($_POST['provider_slug'] ?? '');

        // Determine is_active value
        $is_active_value = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;
        
        $data = [
            'id'            => $provider_id,
            'provider_slug' => $provider_slug,
            'provider_name' => sanitize_text_field($_POST['provider_name'] ?? ''),
            'api_endpoint'  => esc_url_raw($_POST['api_endpoint'] ?? ''),
            'auth_type'     => sanitize_text_field($_POST['auth_type'] ?? ''),
            'client_id'     => sanitize_text_field($_POST['client_id'] ?? ''),
            'client_secret' => sanitize_text_field($_POST['client_secret'] ?? ''),
            'is_active'     => $is_active_value,
        ];

        // Load existing settings (to preserve tokens / oauth state)
        $existing_settings = [];
        if ($provider_id > 0) {
            $existing_provider = admin_lab_get_subscription_provider($provider_id);
            if (!empty($existing_provider)) {
                $existing_settings = $get_settings_array($existing_provider);
            }
        }

        // New settings from POST
        $new_settings = (isset($_POST['settings']) && is_array($_POST['settings'])) ? $_POST['settings'] : [];

        // Sanitize new settings
        $new_settings = $sanitize_settings($new_settings);
        
        // For Discord and Tipeee bot_api_key: if empty or not set in POST, keep existing encrypted value
        if (strpos($provider_slug, 'discord') === 0 || strpos($provider_slug, 'tipeee') === 0) {
            // Check if bot_api_key_exists flag is set (indicates key exists but user left field empty)
            $bot_key_exists_flag = isset($new_settings['bot_api_key_exists']) && $new_settings['bot_api_key_exists'] == '1';
            unset($new_settings['bot_api_key_exists']); // Remove flag, it's not a real setting
            
            // If bot_api_key is not in new_settings (empty field) or is empty, keep existing encrypted value
            if (!isset($new_settings['bot_api_key']) || empty(trim($new_settings['bot_api_key'] ?? ''))) {
                if (!empty($existing_settings['bot_api_key']) || $bot_key_exists_flag) {
                    // Keep existing encrypted key
                    // Don't unset, just don't include it in new_settings so merge keeps existing
                    unset($new_settings['bot_api_key']);
                }
            }
        }

        // Merge: new settings override existing ones
        // Important: array_merge($existing, $new) means $new values take precedence
        $merged_settings = array_merge($existing_settings, $new_settings);

        // Checkbox behavior: present = 1, absent = 0
        $merged_settings['debug_log'] = isset($_POST['settings']['debug_log']) ? 1 : 0;

        /**
         * Discord: uses Bot API, not OAuth.
         * We enforce a clean config so the UI/DB is not confusing.
         */
        if (strpos($provider_slug, 'discord') === 0) {
            $data['api_endpoint']  = '';
            $data['auth_type']     = 'bot'; // or 'none'
            $data['client_id']     = '';
            $data['client_secret'] = '';

            // (optional) If you never want OAuth remnants on Discord:
            unset($merged_settings['access_token'], $merged_settings['refresh_token'], $merged_settings['expires_at']);

            // Enforce required bot api config for Discord
            // Check if bot_api_key exists in merged_settings OR in existing_settings (encrypted key)
            $has_bot_key = !empty($merged_settings['bot_api_key']) || !empty($existing_settings['bot_api_key']);
            if (empty($merged_settings['bot_api_url']) || !$has_bot_key) {
                echo '<div class="notice notice-error"><p><strong>Discord:</strong> Bot API URL and Bot API Key are required.</p></div>';
                // Stop here to avoid saving broken config
                return;
            }
        }

        /**
         * Tipeee: uses Bot API (Discord bot), not OAuth.
         * Similar to Discord but with role mappings.
         */
        if (strpos($provider_slug, 'tipeee') === 0) {
            $data['api_endpoint']  = '';
            $data['auth_type']     = 'bot';
            $data['client_id']     = '';
            $data['client_secret'] = '';

            // Remove OAuth remnants
            unset($merged_settings['access_token'], $merged_settings['refresh_token'], $merged_settings['expires_at']);

            // Enforce required bot api config for Tipeee
            // Check if bot_api_key exists in merged_settings OR in existing_settings (encrypted key)
            $has_bot_key = !empty($merged_settings['bot_api_key']) || !empty($existing_settings['bot_api_key']);
            if (empty($merged_settings['bot_api_url']) || !$has_bot_key) {
                echo '<div class="notice notice-error"><p><strong>Tipeee:</strong> Bot API URL and Bot API Key are required.</p></div>';
                return;
            }

            // Role mappings are optional - user can configure them later
            // Only show a warning if they're missing, but don't block saving
            if (empty($merged_settings['role_mappings']) || !is_array($merged_settings['role_mappings']) || count($merged_settings['role_mappings']) === 0) {
                echo '<div class="notice notice-warning"><p><strong>Tipeee:</strong> No Discord role mappings configured yet. You can configure them later, but synchronization will not work until at least one role mapping is set up.</p></div>';
                // Don't return - allow saving without role mappings
            }
        }


        $data['settings'] = $merged_settings;

        admin_lab_save_subscription_provider($data);

        // Redirect to remove edit parameter
        $redirect_url = add_query_arg(['page' => 'admin-lab-subscription', 'tab' => 'providers', 'saved' => '1'], remove_query_arg(['edit', 'delete']));
        wp_redirect($redirect_url);
        exit;
    }
    
    // Show success message after redirect
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Provider saved successfully.</p></div>';
    }

    // Delete provider
    if (isset($_GET['delete']) && check_admin_referer('delete_provider_' . $_GET['delete'])) {
        admin_lab_delete_subscription_provider(intval($_GET['delete']));
        echo '<div class="notice notice-success"><p>Provider deleted.</p></div>';
    }

    /* ------------------------------------------------------------
     * Data
     * ------------------------------------------------------------ */

    $providers = admin_lab_get_subscription_providers();

    // Provider to edit
    $edit_provider = null;
    if (isset($_GET['edit']) && $_GET['edit'] !== 'new') {
        $edit_provider = admin_lab_get_subscription_provider(intval($_GET['edit']));
    } elseif (isset($_GET['edit']) && $_GET['edit'] === 'new') {
        $edit_provider = [
            'id'            => 0,
            'provider_slug' => '',
            'provider_name' => '',
            'api_endpoint'  => '',
            'auth_type'     => '',
            'client_id'     => '',
            'is_active'     => 0,
            'settings'      => [],
        ];
    }

    // OAuth success message
    if (isset($_GET['oauth'])) {
        $oauth_provider = str_replace('_ok', '', (string) $_GET['oauth']);
        if ($oauth_provider) {
            $provider_name = ucfirst($oauth_provider);
            echo '<div class="notice notice-success"><p>' . esc_html($provider_name) . ' account connected successfully!</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h2>Subscription Providers</h2>

        <?php if ($edit_provider) : ?>
            <h3><?php echo $edit_provider['id'] > 0 ? 'Edit Provider' : 'Add Provider'; ?></h3>
            <?php include __DIR__ . '/../forms/subscription-provider-form.php'; ?>
        <?php else : ?>
            <p><a href="<?php echo esc_url(add_query_arg(['tab' => 'providers', 'edit' => 'new'], remove_query_arg(['saved', 'delete']))); ?>" class="button button-primary">Add Provider</a></p>

            <?php if (!empty($providers)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>API URL</th>
                            <th>Client ID</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($providers as $provider) :
                            $provider_slug = $provider['provider_slug'] ?? '';
                            $settings = $get_settings_array($provider);
                            $has_discord_bot = (strpos($provider_slug, 'discord') === 0 && !empty($settings['bot_api_url']) && !empty($settings['bot_api_key']));

                            // Mask display for client_id
                            $has_client_id = !empty($provider['client_id']);
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($provider['provider_name']); ?></strong></td>
                                <td><code><?php echo esc_html($provider_slug); ?></code></td>
                                <td><?php echo !empty($provider['api_endpoint']) ? esc_html($provider['api_endpoint']) : '-'; ?></td>
                                <td><?php echo $has_client_id ? esc_html('***') : '-'; ?></td>
                                <td>
                                    <?php echo !empty($provider['is_active']) ? '<span class="status-active">✓ Active</span>' : '<span class="status-inactive">✗ Inactive</span>'; ?>

                                    <?php if (strpos($provider_slug, 'discord') === 0) : ?>
                                        <br><small class="subscription-bot-api-label">Discord: Bot API</small>
                                        <?php if ($has_discord_bot) : ?>
                                            <br><small class="subscription-bot-api-configured">✓ Bot API configured</small>
                                        <?php else : ?>
                                            <br><small class="subscription-bot-api-missing">⚠ Bot API missing (URL/Key)</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'providers', 'edit' => $provider['id']], remove_query_arg(['saved', 'delete']))); ?>" class="button button-small">Edit</a>

                                    <?php
                                    /**
                                     * OAuth Connect button:
                                     * - Twitch: yes
                                     * - Other OAuth providers: yes
                                     * - Discord: NO (Bot API)
                                     */
                                    if (strpos($provider_slug, 'discord') !== 0 && !empty($provider['client_id'])) :
                                        if (strpos($provider_slug, 'twitch') === 0) {
                                    
                                            $has_token = !empty($settings['broadcaster_access_token']);
                                            $oauth_start_url = admin_url('admin-post.php?action=admin_lab_twitch_oauth_start');
                                            $button_text = $has_token ? 'Reconnect Twitch' : 'Connect Twitch';
                                    
                                        } elseif (strpos($provider_slug, 'youtube') === 0) {
                                    
                                            // YouTube uses creator tokens + single START action with provider param
                                            $has_token = !empty($settings['creator_access_token']);
                                            $oauth_start_url = add_query_arg([
                                                'action'   => 'admin_lab_youtube_oauth_start',
                                                'provider' => $provider_slug,
                                            ], admin_url('admin-post.php'));
                                            $button_text = $has_token ? 'Reconnect YouTube' : 'Connect YouTube';
                                    
                                        } else {
                                    
                                            $has_token = !empty($settings['access_token']);
                                            $oauth_start_url = admin_url('admin-post.php?action=admin_lab_' . $provider_slug . '_oauth_start');
                                            $provider_display_name = ucfirst($provider_slug);
                                            $button_text = $has_token ? 'Reconnect' : 'Connect ' . $provider_display_name;
                                    
                                        }
                                    ?>                                    
                                        <a href="<?php echo esc_url($oauth_start_url); ?>"
                                           class="button button-small <?php echo $has_token ? '' : 'button-primary'; ?>">
                                            <?php echo esc_html($button_text); ?>
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('delete', $provider['id']), 'delete_provider_' . $provider['id'])); ?>"
                                       class="button button-small"
                                       onclick="return confirm('Are you sure you want to delete this provider?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No providers configured.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

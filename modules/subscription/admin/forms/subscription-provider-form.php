<?php
// File: modules/subscription/admin/forms/subscription-provider-form.php

if (!defined('ABSPATH')) exit;

// Get variables from parent scope
$edit_provider = isset($edit_provider) ? $edit_provider : null;
$provider_id = $edit_provider ? (int) ($edit_provider['id'] ?? 0) : 0;
$current_provider_slug = $edit_provider['provider_slug'] ?? '';
$provider_settings = !empty($edit_provider['settings']) ? maybe_unserialize($edit_provider['settings']) : [];

if (!is_array($provider_settings)) {
    $decoded = json_decode((string) $provider_settings, true);
    $provider_settings = is_array($decoded) ? $decoded : [];
}

// Helpers
$is_twitch  = (!empty($current_provider_slug) && strpos($current_provider_slug, 'twitch') === 0);
$is_discord = (!empty($current_provider_slug) && strpos($current_provider_slug, 'discord') === 0);
$is_youtube = (!empty($current_provider_slug) && strpos($current_provider_slug, 'youtube') === 0);
$is_tipeee  = (!empty($current_provider_slug) && strpos($current_provider_slug, 'tipeee') === 0);

// Get OAuth redirect URI + provider display data
if ($is_twitch) {
    if (function_exists('admin_lab_twitch_redirect_uri')) {
        $redirect_uri = admin_lab_twitch_redirect_uri($current_provider_slug);
    } else {
        // Fallback: check for public_base_url
        $public_base = $provider_settings['public_base_url'] ?? '';
        if ($public_base) {
            $redirect_uri = rtrim($public_base, '/') . '/wp-admin/admin-post.php?action=admin_lab_twitch_oauth_callback';
        } else {
            $redirect_uri = admin_url('admin-post.php?action=admin_lab_twitch_oauth_callback');
        }
    }
    $has_token = !empty($provider_settings['broadcaster_access_token']);
    $connected_user = $provider_settings['broadcaster_login'] ?? null;
    $connected_user_id = $provider_settings['broadcaster_user_id'] ?? null;
    $provider_name = 'Twitch';
} elseif ($is_youtube) {

    // YouTube uses a SINGLE redirect URI for all youtube_* providers.
    // It depends only on the provider's public_base_url (or site_url fallback).
    if (function_exists('admin_lab_youtube_get_public_base_url')) {
        $public_base = admin_lab_youtube_get_public_base_url($current_provider_slug);
    } else {
        $public_base = $provider_settings['public_base_url'] ?? '';
        $public_base = $public_base ? rtrim($public_base, '/') : rtrim(site_url(), '/');
    }

    // Always show the unique callback action
    $redirect_uri = rtrim($public_base, '/') . '/wp-admin/admin-post.php?action=admin_lab_youtube_oauth_callback';

    $has_token = !empty($provider_settings['creator_access_token']);
    $connected_user = $provider_settings['creator_channel_title'] ?? null;
    $connected_user_id = $provider_settings['creator_channel_id'] ?? null;
    $provider_name = 'YouTube';

} else {
    if (function_exists('admin_lab_provider_oauth_redirect_uri')) {
        $redirect_uri = admin_lab_provider_oauth_redirect_uri($current_provider_slug);
    } else {
        // Fallback: check for public_base_url
        $public_base = $provider_settings['public_base_url'] ?? '';
        $action = 'admin_lab_' . $current_provider_slug . '_oauth_callback';
        if ($public_base) {
            $redirect_uri = rtrim($public_base, '/') . '/wp-admin/admin-post.php?action=' . urlencode($action);
        } else {
            $redirect_uri = admin_url('admin-post.php?action=' . $action);
        }
    }
    $has_token = !empty($provider_settings['access_token']);
    $connected_user = $provider_settings['connected_user'] ?? null;
    $connected_user_id = $provider_settings['connected_user_id'] ?? null;
    $provider_name = !empty($edit_provider['provider_name'])
    ? $edit_provider['provider_name']
    : (!empty($current_provider_slug) ? ucwords(str_replace(['_', '-'], ' ', $current_provider_slug)) : 'Provider');
}

$debug_log = !empty($provider_settings['debug_log']);

            // Discord bot fields (stored in settings)
            $discord_bot_api_url = $provider_settings['bot_api_url'] ?? '';
            // For bot_api_key: if encrypted, don't display it (leave empty for password field)
            $discord_bot_api_key = '';
            $has_existing_bot_key = false;
            if (!empty($provider_settings['bot_api_key'])) {
                // Try to decrypt to check if it's encrypted
                if (function_exists('admin_lab_decrypt_data')) {
                    $decrypted = admin_lab_decrypt_data($provider_settings['bot_api_key']);
                    // If decryption succeeded (returned value is different), it was encrypted
                    // Don't display it in the form (security: password field should be empty)
                    if ($decrypted !== $provider_settings['bot_api_key']) {
                        // Key is encrypted, leave empty (user must re-enter to change)
                        $discord_bot_api_key = '';
                        $has_existing_bot_key = true; // Mark that key exists
                    } else {
                        // Key is not encrypted (old format), display it (but user should re-enter to encrypt)
                        $discord_bot_api_key = $provider_settings['bot_api_key'];
                        $has_existing_bot_key = true;
                    }
                } else {
                    // Encryption function not available, display as-is
                    $discord_bot_api_key = $provider_settings['bot_api_key'];
                    $has_existing_bot_key = true;
                }
            }
?>

<form method="post" action="">
    <?php wp_nonce_field('subscription_provider_action'); ?>
    <input type="hidden" name="action" value="save_provider">
    <input type="hidden" name="provider_id" value="<?php echo esc_attr($provider_id); ?>">

    <table class="form-table">
        <?php if ($provider_id === 0) : ?>
        <tr>
            <th><label for="provider_type">Provider Type</label></th>
            <td>
                <select name="provider_type" id="provider_type" onchange="updateProviderFields()">
                    <option value="">-- Select a provider type --</option>
                    <option value="twitch" <?php selected($is_twitch, true); ?>>Twitch</option>
                    <option value="discord" <?php selected($is_discord, true); ?>>Discord</option>
                    <option value="youtube" <?php selected(strpos($current_provider_slug, 'youtube') === 0, true); ?>>YouTube</option>
                    <option value="patreon" <?php selected(strpos($current_provider_slug, 'patreon') === 0, true); ?>>Patreon</option>
                    <option value="tipeee"  <?php selected(strpos($current_provider_slug, 'tipeee') === 0, true);  ?>>Tipeee</option>
                </select>
                <p class="description">Select a provider type to auto-fill default values. You can still customize them.</p>
            </td>
        </tr>
        <?php endif; ?>

        <tr>
            <th><label for="provider_slug">Provider Slug</label></th>
            <td>
                <input type="text" name="provider_slug" id="provider_slug"
                       value="<?php echo esc_attr($edit_provider['provider_slug'] ?? ''); ?>"
                       required <?php echo $provider_id > 0 ? 'readonly' : ''; ?>>
                <p class="description">e.g., "twitch", "discord", "youtube". Cannot be changed after creation.</p>
            </td>
        </tr>

        <tr>
            <th><label for="provider_name">Provider Name</label></th>
            <td>
                <input type="text" name="provider_name" id="provider_name"
                       value="<?php echo esc_attr($edit_provider['provider_name'] ?? ''); ?>"
                       required>
            </td>
        </tr>

        <tr>
            <th><label for="api_endpoint">API Endpoint</label></th>
            <td>
                <input type="url" name="api_endpoint" id="api_endpoint"
                       value="<?php echo esc_attr($edit_provider['api_endpoint'] ?? ''); ?>"
                       placeholder="https://api.twitch.tv/helix">
                <p class="description">Base URL for the provider's API (optional, defaults are used if empty)</p>
            </td>
        </tr>

        <tr>
            <th><label for="auth_type">Auth Type</label></th>
            <td>
                <input type="text" name="auth_type" id="auth_type"
                       value="<?php echo esc_attr($edit_provider['auth_type'] ?? 'oauth2'); ?>"
                       placeholder="oauth2">
                <p class="description">
                    Authentication type (usually "oauth2" for Twitch/YouTube/Patreon).
                    For Discord (Bot API) you can use "bot" or leave it as-is.
                </p>
            </td>
        </tr>

        <!-- OAuth credentials rows (hidden for Discord, optional there) -->
        <tr data-provider-row="oauth-credentials" style="<?php echo $is_discord ? 'display:none;' : ''; ?>">
            <th><label for="client_id">Client ID</label></th>
            <td>
                <input type="text" name="client_id" id="client_id"
                       value="<?php echo esc_attr($edit_provider['client_id'] ?? ''); ?>"
                       <?php echo $is_discord ? '' : 'required'; ?>>

                <div id="provider_help_text">
                    <?php if ($is_twitch || (empty($edit_provider['id']) && empty($edit_provider['provider_slug']))) : ?>
                        <p class="description">
                            <strong>Twitch Configuration:</strong><br>
                            1. Go to <a href="https://dev.twitch.tv/console/apps" target="_blank" rel="noreferrer noopener">Twitch Developer Console</a><br>
                            2. Create a new application or use an existing one<br>
                            3. Copy the "Client ID" here<br>
                            4. The "Client Secret" is optional (only needed for OAuth flows)
                        </p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>

        <tr data-provider-row="oauth-credentials" style="<?php echo $is_discord ? 'display:none;' : ''; ?>">
            <th><label for="client_secret">Client Secret</label></th>
            <td>
                <input type="password" name="client_secret" id="client_secret" value="" placeholder="Leave empty to keep unchanged">
                <p class="description">
                    Client secret is encrypted when stored.<br>
                    <strong>Note:</strong> Channels/servers are managed in the "Channels/Servers" tab.
                </p>

                <!-- OAuth configuration fields for non-Twitch providers (except Discord which uses Bot API) -->
                <div data-provider="oauth" class="subscription-oauth-config-section" <?php echo (!empty($current_provider_slug) && !$is_twitch && !$is_discord) ? '' : 'style="display: none;"'; ?>>
                    <strong>OAuth Configuration:</strong><br>

                    <label style="display: block; margin-top: 10px;">
                        <strong>OAuth Authorization URL:</strong><br>
                        <input type="text" name="settings[oauth_authorize_url]"
                               value="<?php echo esc_attr($provider_settings['oauth_authorize_url'] ?? ''); ?>"
                               placeholder="https://example.com/oauth/authorize" style="width: 100%; margin-top: 5px;">
                        <small>e.g., https://discord.com/api/oauth2/authorize</small>
                    </label>

                    <label style="display: block; margin-top: 10px;">
                        <strong>OAuth Token URL:</strong><br>
                        <input type="text" name="settings[oauth_token_url]"
                               value="<?php echo esc_attr($provider_settings['oauth_token_url'] ?? ''); ?>"
                               placeholder="https://example.com/oauth/token" style="width: 100%; margin-top: 5px;">
                        <small>e.g., https://discord.com/api/oauth2/token</small>
                    </label>

                    <label style="display: block; margin-top: 10px;">
                        <strong>OAuth Scopes (comma-separated):</strong><br>
                        <input type="text" name="settings[oauth_scopes]"
                               value="<?php echo esc_attr($provider_settings['oauth_scopes'] ?? ''); ?>"
                               placeholder="scope1,scope2" style="width: 100%; margin-top: 5px;">
                        <small>e.g., bot, guilds.members.read</small>
                    </label>
                </div>
            </td>
        </tr>

        <!-- Discord Bot API configuration (shown for Discord and Tipeee) -->
        <tr data-provider-row="discord-bot" style="<?php echo ($is_discord || $is_tipeee) ? '' : 'display:none;'; ?>">
            <th><label><?php echo $is_tipeee ? 'Tipeee Bot API' : 'Discord Bot API'; ?></label></th>
            <td>
                <div data-provider="discord-bot" class="subscription-bot-config-section">
                    <strong><?php echo $is_tipeee ? 'Tipeee Bot API Configuration:' : 'Discord Bot API Configuration:'; ?></strong><br>

                    <label style="display: block; margin-top: 10px;">
                        <strong>Bot API URL:</strong><br>
                        <input type="text" name="settings[bot_api_url]"
                               value="<?php echo esc_attr($discord_bot_api_url); ?>"
                               placeholder="https://bots.me5rine-lab.com"
                               style="width: 100%; margin-top: 5px;"
                               <?php echo ($is_discord || $is_tipeee) ? 'required' : ''; ?>>
                        <small>Base URL of your Discord bot API (e.g., https://bots.me5rine-lab.com)</small>
                    </label>

                    <label style="display: block; margin-top: 10px;">
                        <strong>Bot API Key:</strong><br>
                        <input type="password" name="settings[bot_api_key]"
                               value="<?php echo esc_attr($discord_bot_api_key); ?>"
                               placeholder="<?php echo $has_existing_bot_key ? '•••••••••••••••• (key already configured, leave empty to keep)' : 'Your bot API key'; ?>"
                               style="width: 100%; margin-top: 5px;"
                               <?php echo (($is_discord || $is_tipeee) && !$has_existing_bot_key) ? 'required' : ''; ?>
                               data-has-existing-key="<?php echo $has_existing_bot_key ? '1' : '0'; ?>">
                        <small>
                            <?php if ($has_existing_bot_key): ?>
                                API key is already configured. Leave empty to keep the existing key, or enter a new key to replace it.
                            <?php else: ?>
                                API key for authenticating with the bot (same as in your .env file)
                            <?php endif; ?>
                        </small>
                        <?php if ($has_existing_bot_key): ?>
                            <input type="hidden" name="settings[bot_api_key_exists]" value="1">
                        <?php endif; ?>
                    </label>

                    <?php if ($is_tipeee): ?>
                        <?php
                        // Get role mappings from settings
                        $role_mappings = $provider_settings['role_mappings'] ?? [];
                        if (!is_array($role_mappings)) {
                            $role_mappings = [];
                        }
                        // Get available subscription levels for Tipeee
                        $tipeee_levels = admin_lab_get_subscription_levels('tipeee');
                        ?>
                        <label style="display: block; margin-top: 15px;">
                            <strong>Discord Role Mappings:</strong><br>
                            <small>Associate Discord role IDs with subscription types</small>
                            <div id="tipeee-role-mappings" style="margin-top: 10px;">
                                <?php if (empty($role_mappings)): ?>
                                    <div class="role-mapping-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                                        <input type="text" name="settings[role_mappings][role_id_0]" 
                                               placeholder="Discord Role ID" 
                                               style="flex: 1;"
                                               pattern="[0-9]+">
                                        <select name="settings[role_mappings_level][role_id_0]" style="flex: 1;">
                                            <option value="">Select subscription type</option>
                                            <?php foreach ($tipeee_levels as $level): ?>
                                                <option value="<?php echo esc_attr($level['level_slug']); ?>">
                                                    <?php echo esc_html($level['level_name'] ?: $level['level_slug']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="button remove-role-mapping" style="display: none;">Remove</button>
                                    </div>
                                <?php else: ?>
                                    <?php $index = 0; foreach ($role_mappings as $role_id => $level_slug): ?>
                                        <div class="role-mapping-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                                            <input type="text" name="settings[role_mappings][<?php echo esc_attr($role_id); ?>]" 
                                                   value="<?php echo esc_attr($role_id); ?>"
                                                   placeholder="Discord Role ID" 
                                                   style="flex: 1;"
                                                   pattern="[0-9]+">
                                            <select name="settings[role_mappings_level][<?php echo esc_attr($role_id); ?>]" style="flex: 1;">
                                                <option value="">Select subscription type</option>
                                                <?php foreach ($tipeee_levels as $level): ?>
                                                    <option value="<?php echo esc_attr($level['level_slug']); ?>" 
                                                            <?php selected($level_slug, $level['level_slug']); ?>>
                                                        <?php echo esc_html($level['level_name'] ?: $level['level_slug']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="button remove-role-mapping">Remove</button>
                                        </div>
                                    <?php $index++; endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="add-role-mapping" class="button" style="margin-top: 10px;">Add Role Mapping</button>
                        </label>
                    <?php endif; ?>

                    <p class="description" style="margin-top:10px;">
                        <strong>Note:</strong> <?php echo $is_tipeee ? 'Tipeee' : 'Discord'; ?> in this plugin uses your local Bot API (no OAuth needed).
                    </p>
                </div>
            </td>
        </tr>

        <tr data-provider-row="oauth-credentials" style="<?php echo $is_discord ? 'display:none;' : ''; ?>">
            <th><label for="public_base_url">Public Base URL</label></th>
            <td>
                <input type="url" name="settings[public_base_url]" id="public_base_url"
                       value="<?php echo esc_attr($provider_settings['public_base_url'] ?? ''); ?>"
                       placeholder="https://example.com">
                <p class="description">
                    <strong>Optional:</strong> If your site is behind a local domain (e.g., me5rine-lab.local) but OAuth callbacks must use a public domain, enter the public base URL here.
                    <br>Example: <code>https://me5rine-lab.com</code>
                    <br>If empty, uses <code><?php echo esc_html(site_url()); ?></code>
                </p>
            </td>
        </tr>

        <?php
        // OAuth info block: show only if NOT Discord and we have a client_id
        if (!$is_discord && !empty($edit_provider['client_id'])) :
        ?>
        <tr>
            <th><?php echo esc_html($provider_name); ?> Connection</th>
            <td>
                <div class="subscription-oauth-info-section">
                    <strong><?php echo esc_html($provider_name); ?> OAuth Configuration:</strong><br>
                    <strong>OAuth Redirect URL (to configure in <?php echo esc_html($provider_name); ?> Developer Console):</strong><br>
                    <code class="subscription-oauth-redirect-code"><?php echo esc_html($redirect_uri); ?></code>
                    <br>
                    <?php if ($has_token) : ?>
                        <span class="subscription-account-connected">✓ Account connected</span>
                        <?php if ($connected_user) : ?>
                            <br><small>Connected as: <strong><?php echo esc_html($connected_user); ?></strong>
                            <?php if ($connected_user_id) : ?>
                                (ID: <?php echo esc_html($connected_user_id); ?>)
                            <?php endif; ?>
                            </small>
                        <?php endif; ?>
                    <?php else : ?>
                        <span class="subscription-account-not-connected">⚠ Account not connected</span>
                        <br><small>Click "Connect <?php echo esc_html($provider_name); ?>" below to authorize the account.</small>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>

        <tr>
            <th><label for="is_active">Active</label></th>
            <td>
                <?php 
                // Ensure is_active is an integer (MySQL TINYINT can be returned as string)
                $is_active_value = isset($edit_provider['is_active']) ? intval($edit_provider['is_active']) : 0;
                ?>
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       <?php checked($is_active_value, 1); ?>>
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    <!-- Debug: is_active = <?php echo esc_attr($is_active_value); ?> (raw: <?php echo esc_attr($edit_provider['is_active'] ?? 'NOT SET'); ?>) -->
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th><label for="debug_log">Debug Logging</label></th>
            <td>
                <input type="checkbox" name="settings[debug_log]" id="debug_log" value="1"
                       <?php checked($debug_log, 1); ?>>
                <label for="debug_log">Enable detailed logging (logs API responses, subscription data, etc.)</label>
                <p class="description">
                    ⚠️ <strong>Warning:</strong> Debug logging will log user data (user_id, user_name) from subscriptions.
                    Only enable in development/staging environments.
                    In production, use with caution and ensure log files are properly secured.
                    <br>
                    Debug mode is also automatically enabled if <code>WP_DEBUG</code> is true.
                </p>
            </td>
        </tr>
    </table>

    <p class="submit">
        <input type="submit" class="button button-primary" value="Save">
        <a href="<?php echo esc_url(remove_query_arg('edit')); ?>" class="button">Cancel</a>

        <?php
        // OAuth Connect button: for Twitch + other OAuth providers, but NOT Discord
        if (!$is_discord && !empty($edit_provider['client_id'])) :
            if ($is_twitch) {
                $has_token = !empty($provider_settings['broadcaster_access_token']);
                $oauth_start_url = admin_url('admin-post.php?action=admin_lab_twitch_oauth_start');
                $button_text = $has_token ? 'Reconnect Twitch' : 'Connect Twitch';
            } elseif ($is_youtube) {
                $has_token = !empty($provider_settings['creator_access_token']);
            
                // Single START action, provider passed in query
                $oauth_start_url = add_query_arg([
                    'action'   => 'admin_lab_youtube_oauth_start',
                    'provider' => $current_provider_slug,
                ], admin_url('admin-post.php'));
            
                $button_text = $has_token ? 'Reconnect YouTube' : 'Connect YouTube';
            } else {
                $has_token = !empty($provider_settings['access_token']);
                $oauth_start_url = admin_url('admin-post.php?action=admin_lab_' . $current_provider_slug . '_oauth_start');
                $provider_display_name = ucfirst($current_provider_slug);
                $button_text = $has_token ? 'Reconnect ' . $provider_display_name : 'Connect ' . $provider_display_name;
            }
        ?>
            <a href="<?php echo esc_url($oauth_start_url); ?>"
               class="button <?php echo $has_token ? 'button-secondary' : 'button-primary'; ?>"
               style="margin-left: 10px;">
                <?php echo esc_html($button_text); ?>
            </a>
        <?php endif; ?>
    </p>
</form>

<script>
// Provider type presets
const providerPresets = {
    twitch: {
        slug: 'twitch',
        name: 'Twitch',
        api_endpoint: 'https://api.twitch.tv/helix',
        auth_type: 'oauth2'
    },
    discord: {
        slug: 'discord',
        name: 'Discord',
        api_endpoint: '',
        auth_type: 'bot'
    },
    youtube: {
        slug: 'youtube',
        name: 'YouTube',
        api_endpoint: 'https://www.googleapis.com/youtube/v3',
        auth_type: 'oauth2'
    },
    patreon: {
        slug: 'patreon',
        name: 'Patreon',
        api_endpoint: 'https://www.patreon.com/api/oauth2/v2',
        auth_type: 'oauth2'
    },
    tipeee: {
        slug: 'tipeee',
        name: 'Tipeee',
        api_endpoint: '',
        auth_type: 'bot'
    }
};

function updateProviderFields() {
    const providerType = document.getElementById('provider_type');
    if (!providerType || !providerType.value) return;

    const preset = providerPresets[providerType.value];
    if (!preset) return;

    // Only update if fields are empty (don't overwrite existing values)
    const slugField = document.getElementById('provider_slug');
    const nameField = document.getElementById('provider_name');
    const endpointField = document.getElementById('api_endpoint');
    const authTypeField = document.getElementById('auth_type');

    if (slugField && !slugField.value) slugField.value = preset.slug;
    if (nameField && !nameField.value) nameField.value = preset.name;
    if (endpointField && !endpointField.value) endpointField.value = preset.api_endpoint;
    if (authTypeField && !authTypeField.value) authTypeField.value = preset.auth_type;

    updateFormVisibility(providerType.value);
    updateHelpText(providerType.value);
}

function updateFormVisibility(providerType) {
    const isDiscord = (providerType === 'discord');
    const isTipeee = (providerType === 'tipeee');
    const isTwitch = (providerType === 'twitch');
    const isBotBased = (isDiscord || isTipeee);
    
    // Store isTipeee in a way accessible to nested functions
    window.currentProviderIsTipeee = isTipeee;

    // Hide/show OAuth credentials rows for Discord and Tipeee
    document.querySelectorAll('[data-provider-row="oauth-credentials"]').forEach(row => {
        row.style.display = isBotBased ? 'none' : '';
    });

    // Show/hide Discord bot row (also used for Tipeee)
    document.querySelectorAll('[data-provider-row="discord-bot"]').forEach(row => {
        row.style.display = isBotBased ? '' : 'none';
    });

    // Show/hide generic OAuth extra fields for non-discord, non-tipeee and non-twitch
    document.querySelectorAll('[data-provider="oauth"]').forEach(block => {
        block.style.display = (!isBotBased && !isTwitch) ? 'block' : 'none';
    });

    // Toggle required attributes
    const clientId = document.getElementById('client_id');
    if (clientId) {
        if (isBotBased) clientId.removeAttribute('required');
        else clientId.setAttribute('required', 'required');
    }

    const botUrl = document.querySelector('input[name="settings[bot_api_url]"]');
    const botKey = document.querySelector('input[name="settings[bot_api_key]"]');
    if (botUrl && botKey) {
        if (isBotBased) {
            botUrl.setAttribute('required', 'required');
            // Only set required on bot_key if no existing key is configured
            const hasExistingKey = botKey.getAttribute('data-has-existing-key') === '1';
            if (!hasExistingKey) {
                botKey.setAttribute('required', 'required');
            } else {
                botKey.removeAttribute('required');
            }
        } else {
            botUrl.removeAttribute('required');
            botKey.removeAttribute('required');
        }
    }

    // Role mappings are optional - no required attribute needed
    // They will be validated during synchronization, not during provider save
}

function updateHelpText(providerType) {
    const helpContainer = document.getElementById('provider_help_text');
    if (!helpContainer) return;

    const helpTexts = {
        twitch: '<p class="description"><strong>Twitch Configuration:</strong><br>1. Go to <a href="https://dev.twitch.tv/console/apps" target="_blank" rel="noreferrer noopener">Twitch Developer Console</a><br>2. Create a new application or use an existing one<br>3. Copy the "Client ID" here<br>4. The "Client Secret" is optional (only needed for OAuth flows)</p>',
        discord: '<p class="description"><strong>Discord Configuration:</strong><br>Discord uses your local Bot API for fetching boosters. Fill the Bot API URL and Key below. No OAuth needed.</p>',
        youtube: '<p class="description"><strong>YouTube Configuration:</strong><br>1. Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noreferrer noopener">Google Cloud Console</a><br>2. Create OAuth 2.0 credentials<br>3. Copy the "Client ID" and "Client Secret" here</p>',
        patreon: '<p class="description"><strong>Patreon Configuration:</strong><br>1. Go to <a href="https://www.patreon.com/portal/registration/register-clients" target="_blank" rel="noreferrer noopener">Patreon Client Portal</a><br>2. Create a new client<br>3. Copy the "Client ID" and "Client Secret" here</p>',
        tipeee: '<p class="description"><strong>Tipeee Configuration:</strong><br>Tipeee uses your Discord bot API to fetch members with specific Discord roles. Fill the Bot API URL and Key below, then configure role mappings to associate Discord role IDs with subscription types. No OAuth needed.</p>'
    };

    helpContainer.innerHTML = helpTexts[providerType] || '';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  const providerType = document.getElementById('provider_type');
  const slugField = document.getElementById('provider_slug');
  const currentSlug = slugField ? slugField.value : '';

  const inferTypeFromSlug = (slug) => {
    if (!slug) return '';
    if (slug.startsWith('discord')) return 'discord';
    if (slug.startsWith('twitch')) return 'twitch';
    if (slug.startsWith('youtube')) return 'youtube';
    if (slug.startsWith('patreon')) return 'patreon';
    if (slug.startsWith('tipeee')) return 'tipeee';
    if (slug.startsWith('discord')) return 'discord';
    // fallback: keep slug (in case you add providers later)
    return slug;
  };

  const type = (providerType && providerType.value) ? providerType.value : inferTypeFromSlug(currentSlug);

  if (type) {
    updateFormVisibility(type);
    updateHelpText(type);
  }

  // Tipeee role mappings management
  const roleMappingsContainer = document.getElementById('tipeee-role-mappings');
  const addRoleMappingBtn = document.getElementById('add-role-mapping');
  
  if (roleMappingsContainer && addRoleMappingBtn) {
    let roleMappingIndex = roleMappingsContainer.querySelectorAll('.role-mapping-row').length;
    
    // Add role mapping
    addRoleMappingBtn.addEventListener('click', function() {
      const row = document.createElement('div');
      row.className = 'role-mapping-row';
      row.style.cssText = 'margin-bottom: 10px; display: flex; gap: 10px; align-items: center;';
      
      const roleIdInput = document.createElement('input');
      roleIdInput.type = 'text';
      roleIdInput.name = `settings[role_mappings][role_id_${roleMappingIndex}]`;
      roleIdInput.placeholder = 'Discord Role ID';
      roleIdInput.style.cssText = 'flex: 1;';
      roleIdInput.pattern = '[0-9]+';
      // Role mappings are optional - no required attribute
      
      const levelSelect = document.createElement('select');
      levelSelect.name = `settings[role_mappings_level][role_id_${roleMappingIndex}]`;
      levelSelect.style.cssText = 'flex: 1;';
      // Role mappings are optional - no required attribute
      
      // Get subscription levels from existing select if available
      const existingSelect = roleMappingsContainer.querySelector('select');
      if (existingSelect) {
        levelSelect.innerHTML = existingSelect.innerHTML;
      } else {
        levelSelect.innerHTML = '<option value="">Select subscription type</option>';
      }
      
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'button remove-role-mapping';
      removeBtn.textContent = 'Remove';
      removeBtn.addEventListener('click', function() {
        row.remove();
      });
      
      row.appendChild(roleIdInput);
      row.appendChild(levelSelect);
      row.appendChild(removeBtn);
      roleMappingsContainer.appendChild(row);
      roleMappingIndex++;
    });
    
    // Remove role mapping
    roleMappingsContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('remove-role-mapping')) {
        e.target.closest('.role-mapping-row').remove();
      }
    });
  }
});
</script>


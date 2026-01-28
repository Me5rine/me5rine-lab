<?php
// File: /settings/tabs/settings-tab-api.php

if (!defined('ABSPATH')) exit;

$notice = null;

if (is_admin() && current_user_can('manage_options')) {
    if (isset($_POST['delete_youtube_key'])) {
        admin_lab_delete_global_option('admin_lab_youtube_api_key');
        $notice = [
            'message' => __('YouTube API Key deleted successfully.', 'me5rine-lab'),
            'type' => 'warning'
        ];
    } elseif (isset($_POST['save_youtube_key'])) {
        $new_key = sanitize_text_field($_POST['admin_lab_youtube_api_key']);
        admin_lab_save_global_option('admin_lab_youtube_api_key', $new_key);
        $notice = [
            'message' => __('YouTube API Key saved successfully.', 'me5rine-lab'),
            'type' => 'success'
        ];
    } elseif (isset($_POST['save_clicksngames_api'])) {
        $api_base  = untrailingslashit(esc_url_raw($_POST['admin_lab_clicksngames_api_base'] ?? ''));
        $api_token = sanitize_text_field($_POST['admin_lab_clicksngames_api_token'] ?? '');
        
        // Sauvegarder comme options globales (comme YouTube)
        admin_lab_save_global_option('admin_lab_clicksngames_api_base', $api_base);
        admin_lab_save_global_option('admin_lab_clicksngames_api_token', $api_token);
        
        $notice = [
            'message' => __('ClicksNGames API settings saved successfully.', 'me5rine-lab'),
            'type' => 'success'
        ];
    } elseif (isset($_POST['delete_clicksngames_api'])) {
        admin_lab_delete_global_option('admin_lab_clicksngames_api_base');
        admin_lab_delete_global_option('admin_lab_clicksngames_api_token');
        $notice = [
            'message' => __('ClicksNGames API settings deleted successfully.', 'me5rine-lab'),
            'type' => 'warning'
        ];
    }
}

$youtube_api_key = admin_lab_get_global_option('admin_lab_youtube_api_key');
$clicksngames = [
    'api_base'  => admin_lab_get_global_option('admin_lab_clicksngames_api_base') ?: '',
    'api_token' => admin_lab_get_global_option('admin_lab_clicksngames_api_token') ?: '',
];

if (!empty($notice) && is_array($notice)): ?>
    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
        <p><?php echo esc_html($notice['message']); ?></p>
    </div>
<?php endif; ?>

<p class="description" style="margin-bottom: 1.5em;">
    <?php _e('Configure here all API keys used by the modules (YouTube, ClicksNGames, etc.). This is the only place to set them.', 'me5rine-lab'); ?>
</p>

<form method="post">
    <h2><?php _e('YouTube API Key', 'me5rine-lab'); ?></h2>
    <p><?php _e('This key allows the plugin to retrieve YouTube channel names from user profiles.', 'me5rine-lab'); ?></p>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <label for="admin_lab_youtube_api_key"><?php _e('API Key', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="password"
                        name="admin_lab_youtube_api_key"
                        id="admin_lab_youtube_api_key"
                        value="<?php echo esc_attr($youtube_api_key); ?>"
                        class="regular-text"
                        autocomplete="off" />

                    <?php if ($youtube_api_key): ?>
                        <span title="API Key present" style="color: var(--admin-lab-color-green); font-size: 18px;">✅</span>
                    <?php else: ?>
                        <span title="API Key missing" style="color: var(--admin-lab-color-red); font-size: 18px;">❌</span>
                    <?php endif; ?>

                    <button type="button" class="button" onclick="toggleYoutubeKeyVisibility()">👁️</button>
                </div>
                <p class="description"><?php _e('Enter your YouTube Data API v3 key here.', 'me5rine-lab'); ?></p>

                <p style="margin-top: 8px;">
                    <button type="submit" name="save_youtube_key" class="button button-primary">
                        <?php esc_html_e('Save API Key', 'me5rine-lab'); ?>
                    </button>

                    <?php if ($youtube_api_key): ?>
                        <button type="submit" name="delete_youtube_key" class="button button-secondary admin-lab-button-delete"
                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete the API key?', 'me5rine-lab'); ?>');">
                            <?php esc_html_e('Delete API Key', 'me5rine-lab'); ?>
                        </button>
                    <?php endif; ?>
                </p>
            </td>
        </tr>
    </table>
</form>

<hr style="margin: 30px 0;" />

<form method="post">
    <h2><?php _e('ClicksNGames API', 'me5rine-lab'); ?></h2>
    <p><?php _e('Used by Comparator and Game Servers (and other modules) to fetch game data (name, logo, etc.).', 'me5rine-lab'); ?></p>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <label for="admin_lab_clicksngames_api_base"><?php _e('API base URL', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="url"
                    name="admin_lab_clicksngames_api_base"
                    id="admin_lab_clicksngames_api_base"
                    value="<?php echo esc_attr($clicksngames['api_base']); ?>"
                    class="regular-text"
                    placeholder="https://api.clicksngames.com/api" />
                <p class="description"><?php _e('Example: https://api.clicksngames.com/api', 'me5rine-lab'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="admin_lab_clicksngames_api_token"><?php _e('API token', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="password"
                        name="admin_lab_clicksngames_api_token"
                        id="admin_lab_clicksngames_api_token"
                        value="<?php echo esc_attr($clicksngames['api_token']); ?>"
                        class="regular-text"
                        autocomplete="off" />
                    <?php if (!empty($clicksngames['api_token'])): ?>
                        <span title="<?php esc_attr_e('API token present', 'me5rine-lab'); ?>" style="color: var(--admin-lab-color-green); font-size: 18px;">✅</span>
                    <?php else: ?>
                        <span title="<?php esc_attr_e('API token missing', 'me5rine-lab'); ?>" style="color: var(--admin-lab-color-red); font-size: 18px;">❌</span>
                    <?php endif; ?>
                    <button type="button" class="button" onclick="toggleClicksngamesTokenVisibility()">👁️</button>
                </div>
                <p class="description"><?php _e('Bearer token used for API calls.', 'me5rine-lab'); ?></p>
                <p style="margin-top: 8px;">
                    <button type="submit" name="save_clicksngames_api" class="button button-primary">
                        <?php esc_html_e('Save ClicksNGames API', 'me5rine-lab'); ?>
                    </button>
                    
                    <?php if (!empty($clicksngames['api_base']) || !empty($clicksngames['api_token'])): ?>
                        <button type="submit" name="delete_clicksngames_api" class="button button-secondary admin-lab-button-delete"
                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete the ClicksNGames API settings?', 'me5rine-lab'); ?>');">
                            <?php esc_html_e('Delete ClicksNGames API', 'me5rine-lab'); ?>
                        </button>
                    <?php endif; ?>
                </p>
            </td>
        </tr>
    </table>
</form>

<script>
function toggleYoutubeKeyVisibility() {
    const input = document.getElementById('admin_lab_youtube_api_key');
    input.type = input.type === 'password' ? 'text' : 'password';
}
function toggleClicksngamesTokenVisibility() {
    const input = document.getElementById('admin_lab_clicksngames_api_token');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

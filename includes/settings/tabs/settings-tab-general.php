<?php
// File: /settings/tabs/settings-tab-general.php

if (!defined('ABSPATH')) exit;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

$is_rafflepress_active = is_plugin_active('rafflepress-pro/rafflepress-pro.php');
$is_um_active = is_plugin_active('ultimate-member/ultimate-member.php');
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules)) $active_modules = [];

$is_user_management_active = in_array('user_management', $active_modules, true);

$available_modules = array(
    'marketing_campaigns' => __('Marketing Campaigns', 'me5rine-lab'),
    'giveaways'           => __('Giveaways Management', 'me5rine-lab'),
    'partnership'         => __('Partnership', 'me5rine-lab'),
    'shortcodes'          => __('Custom Shortcodes', 'me5rine-lab'),
    'socialls'            => __('Socialls', 'me5rine-lab'),
    'events'              => __('Events', 'me5rine-lab'),
    'subscription'        => __('Subscription System (Roles & Access)', 'me5rine-lab'),
    'remote_news'         => __('Remote News', 'me5rine-lab'),
    'user_management'     => __('User Management (Slug & Display Name)', 'me5rine-lab'),
    'comparator'          => __('Comparator', 'me5rine-lab'),
    'keycloak_account_pages' => __('Keycloak Account Pages', 'me5rine-lab'),
    'game_servers'        => __('Game Servers', 'me5rine-lab'),
);

$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules)) $active_modules = [];

$can_manage_cleanup = defined('ME5RINE_LAB_CUSTOM_PREFIX') && ME5RINE_LAB_CUSTOM_PREFIX === $GLOBALS['table_prefix'];
?>

<form method="post" action="options.php">
    <?php
    settings_fields('admin_lab_settings');
    do_settings_sections('me5rine-lab');
    ?>

    <h2><?php _e('Active Modules', 'me5rine-lab'); ?></h2>
    <p><?php _e('Select the modules you want to activate.', 'me5rine-lab'); ?></p>
    <p class="description" style="margin-bottom: 1em;">
        <?php _e('Modules that use external APIs (Comparator, Game Servers for ClicksNGames; YouTube for profiles; etc.) require API keys to be configured in Me5rine LAB → Settings → API Keys.', 'me5rine-lab'); ?>
    </p>

    <table class="form-table available-modules-table">
        <tr valign="top">
            <th scope="row"><?php _e('Available Modules', 'me5rine-lab'); ?>:</th>
            <td>
                <?php
                foreach ($available_modules as $module_key => $module_label) {
                    $disabled = '';
                    $message = '';
                
                    if ($module_key === 'user_management' && !$is_um_active) {
                        $disabled = 'disabled';
                        $message = ' <em>(' . __('Requires Ultimate Member', 'me5rine-lab') . ')</em>';
                    }
                
                    if ($module_key === 'giveaways') {
                        if (!$is_rafflepress_active && !$is_um_active) {
                            $disabled = 'disabled';
                            $message = ' <em>(' . __('Requires RafflePress and Ultimate Member', 'me5rine-lab') . ')</em>';
                        } elseif (!$is_rafflepress_active) {
                            $disabled = 'disabled';
                            $message = ' <em>(' . __('Requires RafflePress', 'me5rine-lab') . ')</em>';
                        } elseif (!$is_um_active) {
                            $disabled = 'disabled';
                            $message = ' <em>(' . __('Requires Ultimate Member', 'me5rine-lab') . ')</em>';
                        }
                    }
                
                    if ($module_key === 'partnership' || $module_key === 'subscription'|| $module_key === 'socialls') {
                        $missing = [];

                        if (!$is_um_active) {
                            $missing[] = __('Ultimate Member', 'me5rine-lab');
                        }
                        if (!$is_user_management_active) {
                            $missing[] = __('User Management', 'me5rine-lab');
                        }

                        if (!empty($missing)) {
                            $disabled = 'disabled';
                            $message = ' <em>(' . sprintf(__('Requires %s', 'me5rine-lab'), implode(' and ', $missing)) . ')</em>';
                        }
                    }

                    // Indication des clés API à configurer (une seule source : Settings → API Keys)
                    if ($module_key === 'comparator' || $module_key === 'game_servers') {
                        $message .= ' <em>(' . __('ClicksNGames: configure in Settings → API Keys', 'me5rine-lab') . ')</em>';
                    }

                    $checked = in_array($module_key, $active_modules) ? 'checked="checked"' : '';
                    echo '<label><input type="checkbox" name="admin_lab_active_modules[]" value="' . esc_attr($module_key) . '" ' . $checked . ' ' . $disabled . ' /><span> ' . esc_html($module_label) . $message . '</span></label><br>';
                }                
                ?>
            </td>
        </tr>
    </table>

    <h2><?php _e('Plugin Cleanup', 'me5rine-lab'); ?></h2>
    <p><?php _e('Choose whether to delete all plugin data when uninstalling.', 'me5rine-lab'); ?></p>

    <h2><?php _e('Profile URLs', 'me5rine-lab'); ?></h2>
    <p><?php _e('Configure the base URL for user profile pages. Leave empty to use the default (/profil/).', 'me5rine-lab'); ?></p>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <label for="admin_lab_profile_base_url"><?php _e('Profile Base URL', 'me5rine-lab'); ?></label>
            </th>
            <td>
                <input type="url" 
                       id="admin_lab_profile_base_url" 
                       name="admin_lab_profile_base_url" 
                       value="<?php echo esc_attr(get_option('admin_lab_profile_base_url', '')); ?>" 
                       class="regular-text" 
                       placeholder="<?php echo esc_attr(home_url('/profil/')); ?>" />
                <p class="description">
                    <?php _e('Base URL for user profile pages. Example:', 'me5rine-lab'); ?> 
                    <code><?php echo esc_html(home_url('/profil/')); ?></code>
                    <br>
                    <?php _e('If empty, the default will be used:', 'me5rine-lab'); ?> 
                    <code><?php echo esc_html(home_url('/profil/')); ?></code>
                </p>
            </td>
        </tr>
    </table>

    <?php if ($can_manage_cleanup): ?>
        <h2><?php _e('Plugin Cleanup', 'me5rine-lab'); ?></h2>
        <p><?php _e('Choose whether to delete all plugin data when uninstalling.', 'me5rine-lab'); ?></p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Delete data on uninstall', 'me5rine-lab'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="admin_lab_delete_data_on_uninstall" value="1" <?php checked(get_option('admin_lab_delete_data_on_uninstall'), 1); ?> />
                        <?php _e('Delete all plugin data when the plugin is deleted from WordPress.', 'me5rine-lab'); ?>
                    </label>
                </td>
            </tr>
        </table>
    <?php else: ?>
        <h2><?php _e('Plugin Cleanup', 'me5rine-lab'); ?></h2>
        <div class="notice notice-info inline">
            <p><?php _e('Data deletion on uninstall is only available on the main site.', 'me5rine-lab'); ?></p>
        </div>
    <?php endif; ?>

    <?php submit_button(); ?>
</form>

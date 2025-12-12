<?php
// File: modules/partnership/admin/partnership-admin-ui.php

if (!defined('ABSPATH')) exit;

function admin_lab_partnership_admin_ui() {
    if (isset($_POST['admin_lab_account_id'])) {
        check_admin_referer('admin_lab_partnership_settings');
        admin_lab_set_global_admin_lab_account_id((int) sanitize_text_field($_POST['admin_lab_account_id']));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'me5rine-lab') . '</p></div>';
    }
    
    if (isset($_POST['admin_lab_create_partnership_pages'])) {
        check_admin_referer('admin_lab_partnership_settings');
        admin_lab_partnership_create_pages();
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Partnership pages created successfully.', 'me5rine-lab') . '</p></div>';
    }    
    ?>
    <div class="wrap">
        <h1><?php _e('Me5rine LAB Account', 'me5rine-lab'); ?></h1>
        <p><?php _e('Select the user whose social media links will be used to inject Me5rine LAB actions into partner giveaways.', 'me5rine-lab'); ?></p>

        <form method="post" action="">
            <?php
            wp_nonce_field('admin_lab_partnership_settings');

            $admin_lab_account_id = admin_lab_get_global_admin_lab_account_id() ?: '';

            wp_dropdown_users([
                'name' => 'admin_lab_account_id',
                'selected' => $admin_lab_account_id,
                'show_option_none' => __('— None —', 'me5rine-lab'),
                'option_none_value' => '',
                'role__in' => ['administrator'],
            ]);
            ?>
            <p class="description"><?php _e('This account will be used to retrieve Me5rine LAB\'s social networks (Facebook, Discord, etc.).', 'me5rine-lab'); ?></p>

            <?php submit_button(__('Save Settings', 'me5rine-lab')); ?>

            <hr>

            <h2><?php _e('Partnership Pages', 'me5rine-lab'); ?></h2>
            <p><?php _e('You can (re)create the necessary pages manually if needed.', 'me5rine-lab'); ?></p>

            <input type="submit" name="admin_lab_create_partnership_pages" class="button button-primary" value="<?php esc_attr_e('Create Partnership Pages', 'me5rine-lab'); ?>">
        </form>
    </div>
    <?php
}

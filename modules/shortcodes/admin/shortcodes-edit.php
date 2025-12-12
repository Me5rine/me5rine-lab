<?php
// File: modules/shortcodes/admin/shortcodes-edit.php

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    $parent = 'admin-lab-shortcodes'; // la page liste Shortcodes

    add_submenu_page(
        $parent,
        __('Edit Shortcode', 'me5rine-lab'),
        __('Edit Shortcode', 'me5rine-lab'),
        'manage_options',
        'admin-lab-shortcodes-edit',
        'admin_lab_shortcodes_edit_page'
    );

    // on cache cette entrée
    remove_submenu_page($parent, 'admin-lab-shortcodes-edit');
}, 20);

function admin_lab_shortcodes_edit_page() {
    global $wpdb;
    $table_name = admin_lab_getTable('shortcodes');

    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
    $shortcode = ($edit_id > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id)) : null;

    $is_edit = !is_null($shortcode);
    $page_title = $is_edit ? __('Edit Shortcode', 'me5rine-lab') : __('Add New Shortcode', 'me5rine-lab');
    $action_url = admin_url('admin-post.php');
    ?>

    <div class="wrap">
        <h1><?php echo esc_html($page_title); ?></h1>

        <form method="post" action="<?php echo esc_url($action_url); ?>">
            <?php wp_nonce_field($is_edit ? 'admin_lab_edit_shortcode_nonce' : 'admin_lab_add_shortcode_nonce'); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($is_edit ? 'admin_lab_edit_shortcode' : 'admin_lab_add_shortcode'); ?>">

            <?php if ($is_edit): ?>
                <input type="hidden" name="edit_shortcode" value="<?php echo esc_attr($shortcode->id); ?>">
            <?php endif; ?>

            <table class="form-table edit-shortcode-admin-table">
                <tr>
                    <th><label for="shortcode_name"><?php esc_html_e('Shortcode Name', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" name="shortcode_name" id="shortcode_name" value="<?php echo esc_attr($shortcode->name ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="shortcode_description"><?php esc_html_e('Shortcode Description', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" name="shortcode_description" id="shortcode_description" value="<?php echo esc_attr(stripslashes($shortcode->description ?? '')); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="shortcode_function"><?php esc_html_e('PHP Code', 'me5rine-lab'); ?></label></th>
                    <td>
                        <textarea name="shortcode_function" id="shortcode_function" rows="10" required><?php echo esc_textarea($shortcode->content ?? ''); ?></textarea>
                        <p class="description"><?php esc_html_e("Écrivez votre fonction PHP ici. N'incluez pas <?php ni ?>.", 'me5rine-lab'); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html($is_edit ? __('Save Changes', 'me5rine-lab') : __('Add Shortcode', 'me5rine-lab')); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-shortcodes')); ?>" class="button"><?php esc_html_e('Back to List', 'me5rine-lab'); ?></a>
            </p>
        </form>
    </div>
    <?php
}
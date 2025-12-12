<?php
// File: modules/marketing/marketing-edit.php

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    $parent = 'admin-lab-marketing';

    add_submenu_page(
        $parent,
        __('Edit Campaign', 'me5rine-lab'),
        __('Edit Campaign', 'me5rine-lab'),
        'manage_options',
        'admin-lab-marketing-edit',
        'admin_lab_marketing_edit_page'
    );

    remove_submenu_page($parent, 'admin-lab-marketing-edit');
}, 20);

function admin_lab_marketing_edit_page() {
    global $wpdb;
    $table_name = admin_lab_getTable('marketing_links');

    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
    $link = ($edit_id > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id)) : null;

    $is_edit = !is_null($link);
    $page_title = $is_edit ? __('Edit Campaign', 'me5rine-lab') : __('Add New Campaign', 'me5rine-lab');
    $action_url = admin_url('admin-post.php');
    ?>

    <div class="wrap">
        <h1><?php echo esc_html($page_title); ?></h1>

        <form method="post" action="<?php echo esc_url($action_url); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field($is_edit ? 'admin_lab_edit_link_nonce' : 'admin_lab_add_link_nonce'); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($is_edit ? 'admin_lab_edit_link' : 'admin_lab_add_link'); ?>" />

            <?php if ($is_edit) : ?>
                <input type="hidden" name="edit_marketing_link" value="<?php echo esc_attr($link->id); ?>" />
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th><label for="partner_name"><?php esc_html_e('Partner Name', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" name="partner_name" id="partner_name" value="<?php echo esc_attr($link->partner_name ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="campaign_slug"><?php esc_html_e('Campaign Slug', 'me5rine-lab'); ?></label></th>
                    <td><input type="text" name="campaign_slug" id="campaign_slug" value="<?php echo esc_attr($link->campaign_slug ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="campaign_url"><?php esc_html_e('Campaign URL', 'me5rine-lab'); ?></label></th>
                    <td><input type="url" name="campaign_url" id="campaign_url" value="<?php echo esc_url($link->campaign_url ?? ''); ?>" required></td>
                </tr>
                <?php
                global $admin_lab_marketing_zones;

                $zones = is_array($admin_lab_marketing_zones) ? $admin_lab_marketing_zones : [];
                $selected_zones = [];

                if ($is_edit && !empty($link->id)) {
                    foreach ($zones as $zone_key => $_) {
                        $current_zone_id = get_option("admin_lab_marketing_zone_$zone_key");
                        if ((int)$current_zone_id === (int)$link->id) {
                            $selected_zones[] = $zone_key;
                        }
                    }
                }
                ?>

                <tr>
                    <th><label for="campaign_display_zones"><?php _e('Display Zones', 'me5rine-lab'); ?></label></th>
                    <td>
                        <select name="campaign_display_zones[]" id="campaign_display_zones" class="campaign-display-zones" multiple>
                            <?php foreach ($zones as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected(in_array($key, $selected_zones), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('You can assign this campaign to one or more display zones. Only one campaign can be active per zone.', 'me5rine-lab'); ?></p>
                        <p class="description"><?php _e('Hold down the Ctrl (Windows) or Command (Mac) key to select or deselect multiple zones.', 'me5rine-lab'); ?></p>

                    </td>
                </tr>
                <?php
                $formats = [
                    'sidebar'    => ['Sidebar 1', 'Sidebar 2'],
                    'banner'     => ['Banner 1', 'Banner 2'],
                    'background' => ['Background'] // une seule image
                ];

                foreach ($formats as $key => $labels) {
                    foreach ($labels as $index => $label) {
                        // suffixe (ex: "_1", "_2") sauf pour background
                        $suffix = ($key === 'background') ? '' : '_' . ($index + 1);

                        // colonne en base de données
                        $column_name = "image_url_" . $key . $suffix;
                        $img_url = $link->$column_name ?? '';
                        ?>
                        <tr>
                            <th><label><?php echo esc_html($label . ' Image'); ?></label></th>
                            <td>
                                <input type="hidden" 
                                    name="campaign_image_<?php echo esc_attr($key . $suffix); ?>" 
                                    id="campaign_image_<?php echo esc_attr($key . $suffix); ?>" 
                                    value="<?php echo esc_url($img_url); ?>">
                                <img id="campaign_image_preview_<?php echo esc_attr($key . $suffix); ?>" 
                                    src="<?php echo esc_url($img_url); ?>" 
                                    class="campaign-image-preview<?php echo empty($img_url) ? ' hidden' : ''; ?>" />
                                <br>
                                <button type="button" 
                                        class="button upload_campaign_image" 
                                        data-type="<?php echo esc_attr($key . $suffix); ?>">
                                    <?php _e('Choose Image', 'me5rine-lab'); ?>
                                </button>
                                <button type="button" 
                                        class="button remove_campaign_image" 
                                        data-type="<?php echo esc_attr($key . $suffix); ?>">
                                    <?php _e('Remove', 'me5rine-lab'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php
                    }

                    // si background → champ couleur
                    if ($key === 'background') { ?>
                        <tr>
                            <th><label for="campaign_bg_color"><?php _e('Background Color', 'me5rine-lab'); ?></label></th>
                            <td>
                                <input type="text" name="campaign_bg_color" id="campaign_bg_color" class="color-field"
                                    value="<?php echo esc_attr($link->background_color ?? '#000000'); ?>" />
                                <p class="description"><?php _e('Color to apply behind the background image.', 'me5rine-lab'); ?></p>
                            </td>
                        </tr>
                    <?php }
                }
                ?>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html($is_edit ? __('Save Changes', 'me5rine-lab') : __('Add Campaign', 'me5rine-lab')); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-marketing')); ?>" class="button"><?php esc_html_e('Back to List', 'me5rine-lab'); ?></a>
            </p>
        </form>
    </div>

    <script>
    jQuery(function($) {
        $('.color-field').wpColorPicker();
    });
    </script>

<script type="text/html" id="tmpl-marketing-url-template">
    <div style="padding:20px;">
        <label style="font-weight:bold; display:block; margin-bottom:5px;">
            <?php echo esc_html__('Image URL:', 'me5rine-lab'); ?>
        </label>
        <input type="url" id="marketing_url_input" class="widefat"
            placeholder="https://example.com/image.jpg" />

        <!-- Prévisualisation -->
        <div style="margin:15px 0; text-align:center;">
            <img id="marketing_url_preview" src="" style="display:none;max-height:200px;width:auto;margin:10px 0; border:1px solid #ddd; padding:5px;" />
        </div>
    </div>
</script>

    <?php
}

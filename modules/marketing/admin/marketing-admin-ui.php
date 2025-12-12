<?php
// File: modules/marketing/marketing-admin-ui.php

if (!defined('ABSPATH')) exit;

/**
 * Screen options pour la page Campaigns.
 */
function admin_lab_campaigns_screen_options() {
    $screen = get_current_screen();

    // ID de l'écran pour la page "Campaigns"
    if ( ! is_object( $screen ) || $screen->id !== 'me5rine-lab_page_admin-lab-marketing' ) {
        return;
    }

    add_screen_option(
        'per_page',
        [
            'label'   => __( 'Campaigns per page', 'me5rine-lab' ),
            'default' => 20,
            'option'  => 'admin_lab_marketing_campaigns_per_page',
        ]
    );
}

/**
 * Sauvegarde de l’option "Campaigns per page" pour le campaigns.
 */
function admin_lab_set_campaigns_screen_option( $status, $option, $value ) {
    if ( $option === 'admin_lab_marketing_campaigns_per_page' ) {
        return (int) $value;
    }
    return $status;
}
add_filter( 'set-screen-option', 'admin_lab_set_campaigns_screen_option', 10, 3 );

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

$marketing_class_file = plugin_dir_path(__FILE__) . 'marketing-list-table.php';
if (!class_exists('Admin_LAB_Marketing_List_Table') && file_exists($marketing_class_file)) {
    require_once $marketing_class_file;
}

function admin_lab_marketing_page() {
    global $admin_lab_marketing_zones;

    $campaigns = admin_lab_get_marketing_campaigns_for_select(); // [id => slug]
    $zones = $admin_lab_marketing_zones;
    $search = esc_attr($_GET['s'] ?? '');

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Marketing Campaigns', 'me5rine-lab'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=admin-lab-marketing-edit')); ?>" class="page-title-action">
            <?php esc_html_e('Add New', 'me5rine-lab'); ?>
        </a>
        <hr class="wp-header-end">

        <table class="form-table campaign-zones-table">
            <tbody>
                <tr>
                    <?php foreach ($zones as $zone_key => $zone_label): 
                        $current_id = get_option("admin_lab_marketing_zone_$zone_key"); ?>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="campaign-zone-form">
                                <?php wp_nonce_field("update_zone_$zone_key", "_wpnonce_zone_$zone_key"); ?>
                                <input type="hidden" name="action" value="admin_lab_update_zone_<?php echo esc_attr($zone_key); ?>">
                                <label for="campaign_<?php echo esc_attr($zone_key); ?>"><strong><?php echo esc_html($zone_label); ?></strong></label>
                                <div class="campaign-zone-selector-inline">
                                    <div class="campaign-zone-selector">
                                        <select name="campaign_id" id="campaign_<?php echo esc_attr($zone_key); ?>" class="admin-lab-select2 campaign-zone-select" data-placeholder="<?php esc_attr_e('Choose a campaign…', 'me5rine-lab'); ?>">
                                            <option value=""></option>
                                            <?php foreach ($campaigns as $id => $slug): ?>
                                                <option value="<?php echo esc_attr($id); ?>" <?php selected($id, $current_id); ?>>
                                                    <?php echo esc_html($slug); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="button"><?php _e('Update', 'me5rine-lab'); ?></button>
                                </div>
                            </form>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>

        <form method="get" class="marketing-campaign-search-block">
            <input type="hidden" name="page" value="admin-lab-marketing">
            <p class="search-box">
                <input type="search" name="s" value="<?php echo $search; ?>" placeholder="<?php esc_attr_e('Search a campaign', 'me5rine-lab'); ?>">
                <button type="submit" class="button"><?php esc_html_e('Search', 'me5rine-lab'); ?></button>
            </p>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('.admin-lab-select2').select2({
                width: 'resolve',
                allowClear: true,
                placeholder: $(this).data('placeholder') || 'Choose a campaign…'
            });
        });
        </script>

        <?php
        global $wpdb;
        $table = admin_lab_getTable('marketing_links');

        $total_all   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_trashed = 0");
        $total_trash = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_trashed = 1");

        $view = $_GET['view'] ?? 'active';

        $views = [
            'active' => sprintf(__('All (%d)', 'me5rine-lab'), $total_all),
            'trash'  => sprintf(__('Trash (%d)', 'me5rine-lab'), $total_trash),
        ];
        ?>
        <ul class="subsubsub">
            <?php
            $i = 0;
            foreach ($views as $key => $label): 
                $class = ($view === $key) ? 'class="current"' : '';
                $sep = ($i++ > 0) ? ' | ' : '';
                echo "<li>{$sep}<a href='" . esc_url(add_query_arg(['page' => 'admin-lab-marketing', 'view' => $key])) . "' $class>$label</a></li>";
            endforeach;
            ?>
        </ul>
        <br class="clear" />

        <?php
        if (class_exists('Admin_LAB_Marketing_List_Table')) {
            $campaigns_table = new Admin_LAB_Marketing_List_Table();
            $campaigns_table->prepare_items();
            ?>
            <form method="post">
                <?php $campaigns_table->display(); ?>
            </form>
            <?php
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html(__('Error: The class Admin_LAB_Marketing_List_Table is missing.', 'me5rine-lab')) . '</p></div>';
        }
        ?>

        <?php if (($_GET['view'] ?? 'active') === 'trash') : ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const actionsTop = document.querySelector('#bulk-action-selector-top')?.parentNode;
            const actionsBottom = document.querySelector('#bulk-action-selector-bottom')?.parentNode;

            if (actionsTop) {
                const button = document.createElement('button');
                button.type = 'submit';
                button.name = 'empty_trash';
                button.value = '1';
                button.className = 'button action';
                button.textContent = '🧹 <?php echo esc_js(__('Empty Trash', 'me5rine-lab')); ?>';
                button.onclick = function () {
                    return confirm('<?php echo esc_js(__('Are you sure you want to permanently delete all trashed campaigns?', 'me5rine-lab')); ?>');
                };
                actionsTop.appendChild(button.cloneNode(true));
            }

            if (actionsBottom) {
                const button = document.createElement('button');
                button.type = 'submit';
                button.name = 'empty_trash';
                button.value = '1';
                button.className = 'button action';
                button.textContent = '🧹 <?php echo esc_js(__('Empty Trash', 'me5rine-lab')); ?>';
                button.onclick = function () {
                    return confirm('<?php echo esc_js(__('Are you sure you want to permanently delete all trashed campaigns?', 'me5rine-lab')); ?>');
                };
                actionsBottom.appendChild(button);
            }
        });
        </script>
        <?php endif; ?>
    </div>
    <?php
}

global $admin_lab_marketing_zones;
foreach (array_keys($admin_lab_marketing_zones) as $zone_key) {
    add_action("admin_post_admin_lab_update_zone_$zone_key", function () use ($zone_key) {
        if (!current_user_can('manage_options') || !check_admin_referer("update_zone_$zone_key", "_wpnonce_zone_$zone_key")) {
            wp_die(__('Unauthorized request', 'me5rine-lab'));
        }

        $campaign_id = isset($_POST['campaign_id']) ? absint($_POST['campaign_id']) : 0;
        $option_key = "admin_lab_marketing_zone_$zone_key";

        if ($campaign_id > 0) {
            update_option($option_key, $campaign_id);
        } else {
            delete_option($option_key);
        }

        wp_redirect(admin_url('admin.php?page=admin-lab-marketing&message=zone-updated'));
        exit;
    });
}


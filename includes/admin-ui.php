<?php
// File: admin-ui.php

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . '/functions/admin-lab-helpers.php';

function admin_lab_settings_page() {
    $active_modules = get_option('admin_lab_active_modules', []);
    if (!is_array($active_modules)) {
        $active_modules = [];
    }

    global $wpdb;

    $unused_campaigns = [];
    $sources_count = $queries_count = $maps_count = 0;

    if ( admin_lab_is_module_active('giveaways') ) {

        $rafflepress_table = $wpdb->prefix . 'rafflepress_giveaways';
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $rafflepress_table)
        );

        if ( $table_exists === $rafflepress_table ) {
            $used_ids = $wpdb->get_col("
                SELECT meta_value 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_rafflepress_campaign'
            ");

            if ( empty($used_ids) ) {
                $unused_campaigns = $wpdb->get_results("
                    SELECT ID, name, settings 
                    FROM {$rafflepress_table}
                ");
            } else {
                $unused_campaigns = $wpdb->get_results("
                    SELECT ID, name, settings 
                    FROM {$rafflepress_table}
                    WHERE ID NOT IN (" . implode(',', array_map('intval', $used_ids)) . ")
                ");
            }
        } else {
            $unused_campaigns = [];
        }
    }

    if ( admin_lab_is_module_active('remote_news') ) {

        $base_table = $wpdb->prefix . 'remote_news_sources';
        $remote_news_table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $base_table)
        );

        if ( $remote_news_table_exists === $base_table ) {
            $sources_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}remote_news_sources");
            $queries_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}remote_news_queries");
            $maps_count    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}remote_news_category_map");
        }
    }

    $marketing_campaigns_count = $wpdb->get_var("SELECT COUNT(*) FROM " . ME5RINE_LAB_GLOBAL_PREFIX . "marketing_links");
    $users_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    $shortcodes_count = $wpdb->get_var("SELECT COUNT(*) FROM " . ME5RINE_LAB_GLOBAL_PREFIX . "shortcodes");
    $giveaways_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'giveaway'");
    $partner_count = count(get_users(['role' => 'um_partner']));
    $partner_plus_count = count(get_users(['role' => 'um_partner_plus']));
    $sub_count = count(get_users(['role__in' => ['um_sub', 'um_premium']]));
    $events_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_event_enabled' AND meta_value = '1'");
    $networks = maybe_unserialize( admin_lab_get_global_option('admin_lab_socials_list') );
    $socials_count_enabled  = count(array_filter($networks, fn($n) => !empty($n['enabled'])));
    $socials_count_disabled = count(array_filter($networks, fn($n) => empty($n['enabled'])));

    ?>

    <div class="wrap">
        <h1><?php _e('Me5rine LAB - Dashboard', 'me5rine-lab'); ?></h1>
        <p><?php _e("Here is a summary of the plugin's current status.", 'me5rine-lab'); ?></p>

        <h2><?php _e('Active modules', 'me5rine-lab'); ?></h2>
        <?php if (!empty($active_modules)) : ?>
            <div class="admin-lab-modules-container">
                <?php foreach ($active_modules as $module) :
                    $module_labels = [
                        'marketing_campaigns' => __('Marketing Campaigns', 'me5rine-lab'),
                        'user_management'     => __('User Management', 'me5rine-lab'),
                        'shortcodes'          => __('Shortcodes', 'me5rine-lab'),
                        'giveaways'           => __('Giveaways', 'me5rine-lab'),
                        'partnership'         => __('Partnership', 'me5rine-lab'),
                        'subscription'        => __('Subscription', 'me5rine-lab'),
                        'remote_news'         => __('Remote News', 'me5rine-lab'),
                        'socialls'            => __('Socialls', 'me5rine-lab'),
                        'events'              => __('Events', 'me5rine-lab'),
                    ];
                    $label = $module_labels[$module] ?? ucfirst(str_replace('_', ' ', $module));
                    $link = '';
                    $additional_info = '';
                    switch ($module) {
                        case 'marketing_campaigns':
                            $link = 'admin.php?page=admin-lab-marketing';
                            $additional_info = sprintf( ' (%d %s)', $marketing_campaigns_count, __( 'campaigns', 'me5rine-lab' ) );
                            break;
                        case 'user_management':
                            $link = 'admin.php?page=admin-lab-user-management';
                            $additional_info = sprintf( ' (%d %s)', $users_count, __( 'users', 'me5rine-lab' ) );
                            break;
                        case 'shortcodes':
                            $link = 'admin.php?page=admin-lab-shortcodes';
                            $additional_info = sprintf(' (%d %s)', $shortcodes_count, _n('shortcode', 'shortcodes', $shortcodes_count, 'me5rine-lab'));
                            break;
                        case 'giveaways':
                            $link = 'edit.php?post_type=giveaway';
                            $additional_info = sprintf(' (%d %s)', $giveaways_count, _n('giveaway', 'giveaways', $giveaways_count, 'me5rine-lab'));
                            break;
                        case 'partnership':
                            $link = 'admin.php?page=admin-lab-partnership';
                            $additional_info = sprintf(' (%d %s / %d %s+)', $partner_count, _n('partner', 'partners', $partner_count, 'me5rine-lab'), $partner_plus_count, _n('partner', 'partners', $partner_plus_count, 'me5rine-lab'));
                            break;
                        case 'subscription':
                            $link = 'admin.php?page=admin-lab-subscription';
                            $additional_info = sprintf(' (%d %s)', $sub_count, _n('sub', 'subs', $sub_count, 'me5rine-lab'));
                            break;  
                        case 'remote_news':
                            $link = 'admin.php?page=admin-lab-remote-news';
                            $additional_info = sprintf(' (%d %s / %d %s / %d %s)', $sources_count, _n('source', 'sources', $sources_count, 'me5rine-lab'), $queries_count, _n('query', 'queries', $queries_count, 'me5rine-lab'), $maps_count, _n('mapping', 'mappings', $maps_count, 'me5rine-lab'));
                            break;   
                        case 'socialls':
                            $link = 'admin.php?page=admin-lab-socialls';
                            $additional_info = sprintf(' (%d %s / %d %s)', $socials_count_enabled, _n('activate', 'activates', $socials_count_enabled, 'me5rine-lab'), $socials_count_disabled, _n('desactivate', 'desactivates', $socials_count_disabled, 'me5rine-lab'));
                            break;
                        case 'events':
                            $link = 'edit.php';
                            $additional_info = sprintf(' (%d %s)', $events_count, _n('event', 'events', $events_count, 'me5rine-lab'));
                            break;                                                                                  
                    }
                ?>
                    <div class="admin-lab-module-card">
                        <strong><?php echo esc_html($label); ?></strong>
                        <?php if ($link) : ?>
                            <a class="button button-primary" href="<?php echo esc_url(admin_url($link)); ?>">
                                <?php _e('Access module', 'me5rine-lab'); ?>
                            </a>
                            <small class="admin-lab-module-card-additional-info"><?php echo esc_html($additional_info); ?></small>
                        <?php endif; ?>

                        <?php if ($module === 'giveaways') : ?>
                            <div class="admin-lab-module-actions">
                                <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=giveaway_category')); ?>">
                                    <?php _e('Manage categories', 'me5rine-lab'); ?>
                                </a>
                                <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=giveaway_rewards')); ?>">
                                    <?php _e('Manage rewards', 'me5rine-lab'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($module === 'events') : ?>
                            <div class="admin-lab-module-actions">
                                <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=event_type')); ?>">
                                    <?php _e('Manage events types', 'me5rine-lab'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p><?php _e('No active modules.', 'me5rine-lab'); ?></p>
        <?php endif; ?>

        <h2><?php _e('Database information', 'me5rine-lab'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Site prefix', 'me5rine-lab'); ?></th>
                    <td><?php echo esc_html(ME5RINE_LAB_SITE_PREFIX); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Global prefix', 'me5rine-lab'); ?></th>
                    <td><?php echo esc_html(ME5RINE_LAB_GLOBAL_PREFIX); ?></td>
                </tr>
            </tbody>
        </table>
        
        <?php if (admin_lab_is_module_active('giveaways')) : ?>
            <h2><?php _e('Unlinked RafflePress campaigns', 'me5rine-lab'); ?></h2>
            <?php if (!empty($unused_campaigns)) {
                if (!class_exists('WP_List_Table')) {
                    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
                }

                require_once plugin_dir_path(__FILE__) . 'classes/unlinked-campaigns-list-table.php';

                $table = new Admin_LAB_Unlinked_RafflePress_Campaigns_List_Table();
                $table->prepare_items();
                $table->display();
            } else {
                echo '<p>' . esc_html__('No unlinked campaigns found.', 'me5rine-lab') . '</p>';
            }
        endif; ?>
    </div>
    <?php
}

add_action('admin_init', function () {
    if (!current_user_can('edit_posts')) return;

    if (isset($_GET['create_giveaway_from']) && is_numeric($_GET['create_giveaway_from'])) {
        $rafflepress_id = intval($_GET['create_giveaway_from']);
        $campaign_title = get_rafflepress_campaign_name($rafflepress_id);

        $new_post_id = wp_insert_post([
            'post_type'   => 'giveaway',
            'post_title'  => $campaign_title,
            'post_status' => 'draft',
        ]);

        if ($new_post_id && !is_wp_error($new_post_id)) {
            update_post_meta($new_post_id, '_rafflepress_campaign', $rafflepress_id);
            wp_redirect(admin_url('post.php?post=' . $new_post_id . '&action=edit'));
            exit;
        }
    }
});

function get_rafflepress_campaign_name($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'rafflepress_giveaways';
    return $wpdb->get_var($wpdb->prepare("SELECT name FROM {$table} WHERE ID = %d", $id)) ?: 'Giveaway';
}

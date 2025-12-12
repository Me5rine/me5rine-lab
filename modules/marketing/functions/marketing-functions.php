<?php
// File: modules/marketing/marketing-edit.php

if (!defined('ABSPATH')) exit;

// Afficher les notifications
function admin_lab_marketing_notices() {
    if (!isset($_GET['message']) || !isset($_GET['page'])) return;

    if ($_GET['page'] !== 'admin-lab-marketing') return;

    $messages = [
        'added'           => __('The marketing campaign has been successfully added!', 'me5rine-lab'),
        'updated'         => __('The marketing campaign has been updated successfully!', 'me5rine-lab'),
        'trashed_single'  => __('The campaign has been moved to the trash.', 'me5rine-lab'),
        'restored_single' => __('The campaign has been restored from the trash.', 'me5rine-lab'),
        'deleted_single'  => __('The campaign has been permanently deleted.', 'me5rine-lab'),
        'trashed_bulk'    => __('The selected campaigns have been moved to trash.', 'me5rine-lab'),
        'restored_bulk'   => __('The selected campaigns have been restored.', 'me5rine-lab'),
        'deleted_bulk'    => __('The selected campaigns have been permanently deleted.', 'me5rine-lab'),
        'trash_emptied'     => __('The trash has been emptied!', 'me5rine-lab'),
        'zone-updated'    => __('The display zone has been updated!', 'me5rine-lab'),
        'error'           => __('No campaign selected for deletion.', 'me5rine-lab')
    ];

    if (isset($messages[$_GET['message']])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$_GET['message']]) . '</p></div>';
    }
}
add_action('admin_notices', 'admin_lab_marketing_notices');

// Ajout de campagne
add_action('admin_post_admin_lab_add_link', function () {
    global $wpdb;
    check_admin_referer('admin_lab_add_link_nonce');

    $images = [
        'sidebar_1' => esc_url_raw($_POST['campaign_image_sidebar_1'] ?? ''),
        'sidebar_2' => esc_url_raw($_POST['campaign_image_sidebar_2'] ?? ''),
        'banner_1'  => esc_url_raw($_POST['campaign_image_banner_1'] ?? ''),
        'banner_2'  => esc_url_raw($_POST['campaign_image_banner_2'] ?? ''),
        'background'=> esc_url_raw($_POST['campaign_image_background'] ?? ''),
    ];

    $wpdb->insert(admin_lab_getTable('marketing_links'), [
        'partner_name'          => sanitize_text_field($_POST['partner_name']),
        'campaign_slug'         => sanitize_text_field($_POST['campaign_slug']),
        'campaign_url'          => esc_url_raw($_POST['campaign_url']),
        'image_url_sidebar_1' => $images['sidebar_1'],
        'image_url_sidebar_2' => $images['sidebar_2'],
        'image_url_banner_1'  => $images['banner_1'],
        'image_url_banner_2'  => $images['banner_2'],
        'image_url_background'=> $images['background'],
        'background_color'      => sanitize_hex_color($_POST['campaign_bg_color'] ?? '#000000'),
        'created_at'            => current_time('mysql', 1),
    ]);

    $campaign_id = $wpdb->insert_id;

    // Gérer les zones (sidebar, banner, background)
    global $admin_lab_marketing_zones;

    $zones = array_keys($admin_lab_marketing_zones);
    $selected_zones = $_POST['campaign_display_zones'] ?? [];

    foreach ($zones as $zone_key) {
        $option_key = "admin_lab_marketing_zone_$zone_key";
        if (in_array($zone_key, $selected_zones, true)) {
            update_option($option_key, $campaign_id);
        } else {
            if (get_option($option_key) == $campaign_id) {
                delete_option($option_key);
            }
        }
    }

    wp_redirect(admin_url('admin.php?page=admin-lab-marketing&message=added'));
    exit;
});

// Édition de campagne
add_action('admin_post_admin_lab_edit_link', function () {
    global $wpdb;
    check_admin_referer('admin_lab_edit_link_nonce');

    $campaign_id = absint($_POST['edit_marketing_link']);

    $images = [
        'sidebar_1' => esc_url_raw($_POST['campaign_image_sidebar_1'] ?? ''),
        'sidebar_2' => esc_url_raw($_POST['campaign_image_sidebar_2'] ?? ''),
        'banner_1'  => esc_url_raw($_POST['campaign_image_banner_1'] ?? ''),
        'banner_2'  => esc_url_raw($_POST['campaign_image_banner_2'] ?? ''),
        'background'=> esc_url_raw($_POST['campaign_image_background'] ?? ''),
    ];

    $wpdb->update(admin_lab_getTable('marketing_links'), [
        'partner_name'          => sanitize_text_field($_POST['partner_name']),
        'campaign_slug'         => sanitize_text_field($_POST['campaign_slug']),
        'campaign_url'          => esc_url_raw($_POST['campaign_url']),
        'image_url_sidebar_1'   => $images['sidebar_1'],
        'image_url_sidebar_2'   => $images['sidebar_2'],
        'image_url_banner_1'    => $images['banner_1'],
        'image_url_banner_2'    => $images['banner_2'],
        'image_url_background'  => $images['background'],
        'background_color'      => sanitize_hex_color($_POST['campaign_bg_color'] ?? '#000000'),
    ], ['id' => $campaign_id]);

    // Gérer les zones
    $zones = ['sidebar', 'banner', 'background'];
    $selected_zones = $_POST['campaign_display_zones'] ?? [];

    foreach ($zones as $zone_key) {
        $option_key = "admin_lab_marketing_zone_$zone_key";
        if (in_array($zone_key, $selected_zones, true)) {
            update_option($option_key, $campaign_id);
        } else {
            if (get_option($option_key) == $campaign_id) {
                delete_option($option_key);
            }
        }
    }

    wp_redirect(admin_url('admin.php?page=admin-lab-marketing&message=updated'));
    exit;
});

// Dupliquer une campagne
add_action('admin_post_admin_lab_duplicate_link', function () {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'me5rine-lab'));
    }

    if (!isset($_GET['duplicate_id']) || !($id = absint($_GET['duplicate_id']))) {
        wp_die(__('Missing campaign ID', 'me5rine-lab'));
    }

    global $wpdb;
    $table = admin_lab_getTable('marketing_links');
    $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);

    if (!$original) {
        wp_die(__('Campaign not found.', 'me5rine-lab'));
    }

    unset($original['id']);
    $original['campaign_slug'] .= '-copy';
    $original['created_at'] = current_time('mysql');

    $base_slug = $original['campaign_slug'];
    $suffix = 1;
    while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE campaign_slug = %s", $original['campaign_slug']))) {
        $original['campaign_slug'] = $base_slug . '-' . $suffix++;
    }

    $wpdb->insert($table, $original);
    wp_redirect(admin_url('admin.php?page=admin-lab-marketing&message=added'));
    exit;
});

// Mettre à la corbeille
add_action('admin_post_admin_lab_trash_link', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('admin_lab_trash_link_nonce')) {
        wp_die(__('Unauthorized request', 'me5rine-lab'));
    }

    $id = absint($_GET['trash_marketing_link'] ?? 0);
    global $wpdb;
    $table = admin_lab_getTable('marketing_links');

    if ($id > 0) {
        $wpdb->update($table, ['is_trashed' => 1], ['id' => $id]);
    }

    wp_redirect(admin_url('admin.php?page=admin-lab-marketing&message=trashed_single'));
    exit;
});

// Restaurer
add_action('admin_post_admin_lab_restore_link', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('admin_lab_restore_link_nonce')) {
        wp_die(__('Unauthorized request', 'me5rine-lab'));
    }

    $id = absint($_GET['restore_id'] ?? 0);
    global $wpdb;
    $table = admin_lab_getTable('marketing_links');

    if ($id > 0) {
        $wpdb->update($table, ['is_trashed' => 0], ['id' => $id]);
    }

    wp_redirect(admin_url('admin.php?page=admin-lab-marketing&view=trash&message=restored_single'));
    exit;
});

//Supprimer définitivement
add_action('admin_post_admin_lab_delete_link_permanent', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('admin_lab_delete_link_permanent_nonce')) {
        wp_die(__('Unauthorized request', 'me5rine-lab'));
    }

    $id = absint($_GET['delete_id'] ?? 0);
    global $wpdb;
    $table = admin_lab_getTable('marketing_links');

    if ($id > 0) {
        $wpdb->delete($table, ['id' => $id]);
    }

    wp_redirect(admin_url('admin.php?page=admin-lab-marketing&view=trash&message=deleted_single'));
    exit;
});

// Vider la corbeille
add_action('admin_post_admin_lab_empty_marketing_trash', function () {
    if (!current_user_can('manage_options') || !check_admin_referer('admin_lab_empty_trash_nonce')) {
        wp_die(__('Unauthorized request', 'me5rine-lab'));
    }

    global $wpdb;
    $table = admin_lab_getTable('marketing_links');

    $wpdb->query("DELETE FROM $table WHERE is_trashed = 1");

    wp_redirect(admin_url('admin.php?page=admin-lab-marketing&view=trash&message=deleted_bulk'));
    exit;
});
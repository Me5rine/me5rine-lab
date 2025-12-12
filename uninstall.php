<?php
// File: uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Suppression des pages partenaires
$page_slugs = ['partenariat', 'admin-giveaways', 'add-giveaway', 'edit-giveaway'];

foreach ($page_slugs as $slug) {
    $option_key = 'partnership_page_' . $slug;
    $page_id = get_option($option_key);

    if ($page_id && get_post($page_id)) {
        wp_delete_post($page_id, true);
    }

    delete_option($option_key);
}

// Vérifications avant suppression complète
$delete_all = get_option('admin_lab_delete_data_on_uninstall');
$is_master_site = defined('ME5RINE_LAB_CUSTOM_PREFIX') && ME5RINE_LAB_CUSTOM_PREFIX === $GLOBALS['table_prefix'];

if (!$is_master_site || !$delete_all) {
    return;
}

// Suppression des options du plugin
delete_option('admin_lab_active_modules');
delete_option('admin_lab_delete_data_on_uninstall');
delete_transient('admin_lab_admin_notice');

// Suppression des métas utilisateurs
global $wpdb;
$meta_keys = [
    'admin_lab_marketing_campaigns_per_page',
    'admin_lab_shortcodes_per_page',
    'admin_lab_per_page__admin_giveaways',
    'custom_user_nicename'
];

foreach ($meta_keys as $key) {
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
        $key
    ));
}

// Suppression des tables personnalisées
$prefix = $wpdb->prefix;
$tables = [
    "{$prefix}rafflepress_index",
    "{$prefix}marketing_links",
    "{$prefix}user_slugs",
    "{$prefix}shortcodes",
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}
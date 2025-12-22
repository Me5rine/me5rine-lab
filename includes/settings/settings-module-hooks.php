<?php
// File: /includes/settings/settings-module-hooks.php

if (!defined('ABSPATH')) exit;

add_filter('pre_update_option_admin_lab_active_modules', function ($new_value, $old_value) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    $is_rafflepress_active = is_plugin_active('rafflepress-pro/rafflepress-pro.php');
    $is_um_active = is_plugin_active('ultimate-member/ultimate-member.php');

    if (!is_array($new_value)) $new_value = [];
    if (!is_array($old_value)) $old_value = [];

    // Modules bloqués s'il manque Ultimate Member ou RafflePress
    if (in_array('user_management', $new_value) && !$is_um_active) {
        $new_value = array_diff($new_value, ['user_management']);
        set_transient('admin_lab_admin_notice', [
            'message' => __('User Management module could not be activated because Ultimate Member is not installed or active.', 'me5rine-lab'),
            'type' => 'error'
        ], 30);
    }

    if (in_array('giveaways', $new_value) && (!$is_um_active || !$is_rafflepress_active)) {
        $new_value = array_diff($new_value, ['giveaways']);
        set_transient('admin_lab_admin_notice', [
            'message' => __('Giveaways module could not be activated because RafflePress and/or Ultimate Member is not installed or active.', 'me5rine-lab'),
            'type' => 'error'
        ], 30);
    }

    if (in_array('socialls', $new_value)) {
        if (!$is_um_active || !in_array('user_management', $new_value)) {
            $new_value = array_diff($new_value, ['socialls']);
            set_transient('admin_lab_admin_notice', [
                'message' => __('Socialls module could not be activated because Ultimate Member or User Management is not installed or active.', 'me5rine-lab'),
                'type' => 'error'
            ], 30);
        }
    }

    if (in_array('partnership', $new_value)) {
        if (!$is_um_active || !in_array('user_management', $new_value)) {
            $new_value = array_diff($new_value, ['partnership']);
            set_transient('admin_lab_admin_notice', [
                'message' => __('Partnership module could not be activated because Ultimate Member or User Management is not installed or active.', 'me5rine-lab'),
                'type' => 'error'
            ], 30);
        }
    }
    
    if (in_array('subscription', $new_value)) {
        if (!$is_um_active || !in_array('user_management', $new_value)) {
            $new_value = array_diff($new_value, ['subscription']);
            set_transient('admin_lab_admin_notice', [
                'message' => __('Subscription module could not be activated because Ultimate Member or User Management is not installed or active.', 'me5rine-lab'),
                'type' => 'error'
            ], 30);
        }
    }    

    return $new_value;
}, 10, 2);

add_filter('default_option_admin_lab_active_modules', function ($value = false) {
    return is_array($value) ? $value : [];
}, 10, 3);

add_action('update_option_admin_lab_active_modules', function ($old_value, $new_value) {
    if (!is_array($new_value)) return;

    $activated = array_diff($new_value, $old_value);
    foreach ($activated as $module) {
        do_action("admin_lab_{$module}_module_activated");
    }

    $deactivated = array_diff($old_value, $new_value);
    foreach ($deactivated as $module) {
        do_action("admin_lab_{$module}_module_desactivated");
    }

    if (in_array('giveaways', $activated)) {
        set_transient('admin_lab_pending_activations', ['giveaways'], 60);
    }

    update_option('admin_lab_last_active_modules', $new_value);
}, 10, 2);

add_action('admin_notices', function () {
    if ($notice = get_transient('admin_lab_admin_notice')) {
        echo '<div class="notice notice-' . esc_attr($notice['type']) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
        delete_transient('admin_lab_admin_notice');
    }
});

add_action('admin_init', function () {
    if (!class_exists('Admin_Lab_DB')) return;

    $active_modules = get_option('admin_lab_active_modules', []);
    if (!is_array($active_modules)) return;

    $modules_needing_tables = [
        'giveaways'           => admin_lab_getTable('rafflepress_index', false),
        'marketing_campaigns' => admin_lab_getTable('marketing_links'),
        'shortcodes'          => admin_lab_getTable('shortcodes'),
        'user_management'     => admin_lab_getTable('user_slugs'),
        'remote_news'         => [admin_lab_getTable('remote_news_sources', false), admin_lab_getTable('remote_news_queries', false), admin_lab_getTable('remote_news_category_map', false),],
        'comparator'          => admin_lab_getTable('comparator_clicks', false),
        'subscription'        => [
            admin_lab_getTable('subscription_providers'),
            admin_lab_getTable('subscription_accounts'),
            admin_lab_getTable('subscription_levels'),
            admin_lab_getTable('subscription_tiers'),
            admin_lab_getTable('subscription_tier_mappings'),
            admin_lab_getTable('subscription_channels'),
            admin_lab_getTable('subscription_provider_account_types'),
            admin_lab_getTable('user_subscriptions'),
        ],
    ];

    global $wpdb;
    $modules_to_create = [];
    foreach ($modules_needing_tables as $module => $tables) {
        $tables = (array) $tables;
        $needs_create = false;
        foreach ($tables as $table) {
            $exists = ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table);
            if (!$exists) { $needs_create = true; break; }
        }
        if ($needs_create && in_array($module, $active_modules, true)) {
            $modules_to_create[] = $module;
        }
    }

    if ($modules_to_create) {
        Admin_Lab_DB::getInstance()->createTables($modules_to_create);
    }
});

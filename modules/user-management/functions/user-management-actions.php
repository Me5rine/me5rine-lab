<?php
// File: modules/user-management/functions/user-management-actions.php

if (!defined('ABSPATH')) exit;

// Enregistrer le type de display_name et lancer la mise à jour en batch
add_action('admin_post_admin_lab_update_display_name_type', function () {
    check_admin_referer('admin_lab_update_display_name_type');

    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied.', 'me5rine-lab'));
    }

    if (empty($_POST['name-type'])) {
        wp_die(__('No value received.', 'me5rine-lab'));
    }

    $name_type = sanitize_text_field($_POST['name-type']);
    update_option('admin_lab_display_name_type', $name_type);

    global $wpdb;
    $total_users = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}");
    update_option('admin_lab_progress_total', $total_users);
    update_option('admin_lab_progress_current', 0);
    delete_option('admin_lab_update_display_offset');

    wp_safe_redirect(admin_url('admin.php?page=admin-lab-user-management&tab=display&batch=1&updated=true'));
    exit;
});

// Lancer manuellement la mise à jour des noms d’affichage (batch uniquement)
add_action('admin_post_admin_lab_trigger_display_update', function () {
    check_admin_referer('admin_lab_trigger_display_update');

    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied.', 'me5rine-lab'));
    }

    global $wpdb;
    $total_users = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}");
    update_option('admin_lab_progress_total', $total_users);
    update_option('admin_lab_progress_current', 0);
    delete_option('admin_lab_update_display_offset');

    wp_safe_redirect(admin_url('admin.php?page=admin-lab-user-management&tab=display&batch=1'));
    exit;
});

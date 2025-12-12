<?php
// File: modules/user-management/functions/account-types-actions.php

if (!defined('ABSPATH')) exit;

add_action('admin_post_admin_lab_add_account_type', function () {
    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied.', 'me5rine-lab'));
    }

    check_admin_referer('admin_lab_add_account_type');

    if (empty($_POST['type_slug']) || empty($_POST['type_role'])) {
        wp_die(__('Missing required fields.', 'me5rine-lab'));
    }

    $slug = sanitize_title($_POST['type_slug']);
    $role = sanitize_text_field($_POST['type_role']);
    $label = sanitize_text_field($_POST['type_label'] ?? '');

    $scope = 'global';
    if (!empty($_POST['type_scope_mode']) && $_POST['type_scope_mode'] === 'custom' && !empty($_POST['type_scope_custom'])) {
        $scope = array_filter(array_map('sanitize_text_field', (array) $_POST['type_scope_custom']));
    }

    $modules = [];
    if (!empty($_POST['type_modules']) && is_array($_POST['type_modules'])) {
        $modules = array_filter(array_map('sanitize_text_field', $_POST['type_modules']));
    }

    admin_lab_register_account_type($slug, [
        'label' => $label ?: ucfirst($slug),
        'role'  => $role,
        'scope' => $scope,
        'modules' => $modules
    ]);
    
    wp_safe_redirect(admin_url('admin.php?page=admin-lab-user-management&tab=types&added=1'));
    exit;
});

add_action('admin_post_admin_lab_unregister_account_type', function () {
    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied.', 'me5rine-lab'));
    }

    $slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
    if (empty($slug)) {
        wp_die(__('Missing account type slug.', 'me5rine-lab'));
    }

    check_admin_referer('admin_lab_unregister_account_type_' . $slug);

    $types = admin_lab_get_registered_account_types();

    if (!isset($types[$slug])) {
        wp_die(__('Account type not found.', 'me5rine-lab'));
    }

    admin_lab_unregister_account_type($slug);


    wp_safe_redirect(admin_url('admin.php?page=admin-lab-user-management&tab=types&deleted=1'));
    exit;
});

add_action('admin_post_admin_lab_update_account_type', function () {
    check_admin_referer('admin_lab_add_account_type');

    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied.', 'me5rine-lab'));
    }

    $slug = sanitize_key($_POST['type_slug'] ?? '');
    $role = sanitize_text_field($_POST['type_role'] ?? '');
    $scope_mode = $_POST['type_scope_mode'] ?? 'global';
    $custom_scope = $_POST['type_scope_custom'] ?? [];
    $label = sanitize_text_field($_POST['type_label'] ?? '');

    if (!$slug || !$role) {
        wp_die(__('Invalid data.', 'me5rine-lab'));
    }

    $scope = $scope_mode === 'custom'
        ? array_map('sanitize_text_field', (array) $custom_scope)
        : 'global';

    $modules = [];
    if (!empty($_POST['type_modules']) && is_array($_POST['type_modules'])) {
        $modules = array_filter(array_map('sanitize_text_field', $_POST['type_modules']));
    }

    admin_lab_update_account_type($slug, [
        'label' => $label ?: ucfirst($slug),
        'role'  => $role,
        'scope' => $scope,
        'modules' => $modules
    ]);

    wp_safe_redirect(admin_url('admin.php?page=admin-lab-user-management&tab=types&updated=true'));
    exit;
});

add_filter('manage_users_columns', function ($columns) {
    $columns['account_type'] = __('Account Type', 'me5rine-lab');
    return $columns;
});

add_action('manage_users_custom_column', function ($value, $column_name, $user_id) {
    if ($column_name === 'account_type') {
        $types = get_user_meta($user_id, 'admin_lab_account_types', true);
        if (!is_array($types)) return '';
        $registered = admin_lab_get_registered_account_types();

        return esc_html(implode(', ', array_map(function ($slug) use ($registered) {
            return $registered[$slug]['label'] ?? $slug;
        }, $types)));
    }
    return $value;
}, 10, 3);

// 1. Ajouter un sélecteur + bouton proprement
add_action('manage_users_extra_tablenav', function($which) {
    if ($which !== 'top') return; // Seulement en haut du tableau

    $account_types = admin_lab_get_registered_account_types();
    $selected = $_GET['filter_account_type'] ?? '';

    echo '<select name="filter_account_type" id="filter_account_type" class="account-type-filter" style="margin-left: 6px;">';
    echo '<option value="">' . esc_html__('All Account Types', 'me5rine-lab') . '</option>';
    foreach ($account_types as $slug => $data) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($slug),
            selected($selected, $slug, false),
            esc_html($data['label'])
        );
    }
    echo '</select>';
});

// 2. Déclarer notre paramètre custom pour la query
add_filter('user_query_vars', function($vars) {
    $vars[] = 'filter_account_type';
    return $vars;
});

add_action( 'pre_user_query', function( $query ) {
    if ( ! is_admin() ) {
        return;
    }

    if ( ! empty( $_GET['filter_account_type'] ) ) {
        global $wpdb;

        $account_type = sanitize_text_field( $_GET['filter_account_type'] );

        $query->query_from .= " INNER JOIN $wpdb->usermeta account_type_meta ON account_type_meta.user_id = $wpdb->users.ID ";
        $query->query_where .= $wpdb->prepare(
            " AND account_type_meta.meta_key = 'admin_lab_account_types' AND account_type_meta.meta_value LIKE %s ",
            '%"' . $wpdb->esc_like( $account_type ) . '"%'
        );
    }
} );

add_action('admin_footer-users.php', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const accountTypeSelect = document.getElementById('filter_account_type');
        const umStatusSelect = document.getElementById('um_user_status');
        if (accountTypeSelect && umStatusSelect) {
            // On insère notre select juste après le select des statuts UM
            umStatusSelect.insertAdjacentElement('afterend', accountTypeSelect);
        }
    });
    </script>
    <?php
});


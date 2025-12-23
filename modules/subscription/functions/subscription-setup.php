<?php
// File: modules/subscription/functions/subscription-setup.php

if (!defined('ABSPATH')) exit;

/**
 * Setup functions for subscription module
 * Includes: roles, account types, pages protection
 */

/**
 * Creates Ultimate Member 'sub' and 'premium' roles if they don't exist
 */
function admin_lab_create_um_subscription_roles_if_missing() {
    if (!function_exists('um_fetch_user')) return;

    $roles_to_create = [
        'sub'     => ['label' => 'Abonné', 'priority' => 70],
        'premium' => ['label' => 'Premium', 'priority' => 70]
    ];

    $um_roles = get_option('um_roles', []);

    foreach ($roles_to_create as $slug => $props) {
        if (!in_array($slug, $um_roles, true)) {
            $um_roles[] = $slug;
        }

        $option_key = 'um_role_' . $slug . '_meta';

        if (!get_option($option_key)) {
            $meta = [
                '_um_is_custom' => '1',
                'name' => $props['label'],
                '_um_priority' => $props['priority'],
                '_um_can_access_wpadmin' => false,
                '_um_can_not_see_adminbar' => true,
                '_um_can_edit_everyone' => false,
                '_um_can_edit_roles' => '',
                '_um_can_delete_everyone' => false,
                '_um_can_delete_roles' => '',
                '_um_can_edit_profile' => true,
                '_um_can_delete_profile' => true,
                '_um_can_view_all' => true,
                '_um_can_view_roles' => '',
                '_um_can_make_private_profile' => false,
                '_um_can_access_private_profile' => false,
                '_um_profile_noindex' => '',
                '_um_default_homepage' => true,
                '_um_redirect_homepage' => '',
                '_um_status' => 'approved',
                '_um_auto_approve_act' => 'redirect_profile',
                '_um_auto_approve_url' => '',
                '_um_checkmail_action' => 'show_message',
                '_um_checkmail_message' => 'Merci pour votre inscription. Veuillez activer votre compte via le lien reçu par email.',
                '_um_checkmail_url' => '',
                '_um_login_email_activate' => false,
                '_um_url_email_activate' => '',
                '_um_pending_action' => 'show_message',
                '_um_pending_message' => 'Votre inscription est en attente de validation.',
                '_um_pending_url' => '',
                '_um_after_login' => 'refresh',
                '_um_login_redirect_url' => '',
                '_um_after_logout' => 'refresh',
                '_um_logout_redirect_url' => '',
                '_um_after_delete' => 'redirect_home',
                '_um_delete_redirect_url' => '',
                'wp_capabilities' => [
                    'read' => true
                ]
            ];

            update_option($option_key, $meta);
        }
    }

    update_option('um_roles', $um_roles);
}

/**
 * Delete Ultimate Member subscription roles
 */
function admin_lab_delete_um_subscription_roles() {
    $roles_to_remove = ['sub', 'premium'];

    foreach ($roles_to_remove as $slug) {
        delete_option('um_role_' . $slug . '_meta');
    }

    $um_roles = get_option('um_roles', []);
    $um_roles = array_filter($um_roles, fn($role) => !in_array($role, $roles_to_remove));
    update_option('um_roles', $um_roles);

    remove_role('um_sub');
    remove_role('um_premium');
}

/**
 * Register subscription account types ("sub" and "premium") on module activation
 */
function admin_lab_register_subscription_account_types() {
    if (!admin_lab_is_main_site()) return;

    $existing = admin_lab_get_registered_account_types();

    if (!isset($existing['sub'])) {
        admin_lab_register_account_type('sub', [
            'label' => 'Abonné',
            'role'  => 'um_sub',
            'scope' => 'global',
        ]);
    }

    if (!isset($existing['premium'])) {
        admin_lab_register_account_type('premium', [
            'label' => 'Premium',
            'role'  => 'um_premium',
            'scope' => 'global',
        ]);
    }
}

/**
 * Unregister subscription account types on module deactivation
 */
function admin_lab_unregister_subscription_account_types() {
    if (!admin_lab_is_main_site()) return;

    admin_lab_unregister_account_type('sub');
    admin_lab_unregister_account_type('premium');
}

/**
 * Protect subscription pages (redirect to login if not logged in)
 */
function admin_lab_protect_subscription_pages() {
    if (is_page('subscription-page') && !is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }
}





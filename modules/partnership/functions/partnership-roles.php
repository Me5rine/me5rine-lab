<?php
// File: modules/partnership/functions/partnership-roles.php

if (!defined('ABSPATH')) exit;

function admin_lab_create_um_partner_roles_if_missing() {
    if (!function_exists('um_fetch_user')) return;

    $roles_to_create = [
        'partenaire'      => ['label' => 'Partenaire', 'priority' => 60],
        'partenaire_plus' => ['label' => 'Partenaire+', 'priority' => 60],
    ];

    $um_roles = get_option('um_roles', []);

    foreach ($roles_to_create as $slug => $config) {
        if (!in_array($slug, $um_roles, true)) {
            $um_roles[] = $slug;
        }

        $option_key = 'um_role_' . $slug . '_meta';

        if (!get_option($option_key)) {
            $meta = [
                '_um_is_custom' => '1',
                'name' => $config['label'],
                '_um_priority' => $config['priority'],
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
                '_um_checkmail_message' => 'Merci pour votre inscription. Avant de pouvoir vous connecter vous devez activer votre compte en cliquant sur le lien d\u2019activation que nous venons de vous envoyer par e-mail.',
                '_um_checkmail_url' => '',
                '_um_login_email_activate' => false,
                '_um_url_email_activate' => '',
                '_um_pending_action' => 'show_message',
                '_um_pending_message' => 'Merci de votre demande d\u2019inscription \u00e0 notre site. Nous examinerons vos informations et nous vous enverrons un e-mail vous indiquant si votre demande a \u00e9t\u00e9 accept\u00e9e ou non.',
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

function admin_lab_delete_um_partner_roles() {
    $roles_to_remove = ['partenaire', 'partenaire_plus'];

    foreach ($roles_to_remove as $slug) {
        delete_option('um_role_' . $slug . '_meta');
    }

    $um_roles = get_option('um_roles', []);
    $um_roles = array_filter($um_roles, fn($role) => !in_array($role, $roles_to_remove));
    update_option('um_roles', $um_roles);

    remove_role('um_partenaire');
    remove_role('um_partenaire_plus');
}

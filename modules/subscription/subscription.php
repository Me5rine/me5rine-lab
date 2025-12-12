<?php
// File: modules/subscription/subscription.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('subscription', $active_modules, true)) return;

// Chargement de l’interface d’administration
if (is_admin()) {
    include_once __DIR__ . '/admin/subscription-admin-ui.php';
}

require_once __DIR__ . '/functions/subscription-roles.php';
require_once __DIR__ . '/functions/subscription-pages.php';
require_once __DIR__ . '/functions/subscription-global-functions.php';
require_once __DIR__ . '/functions/subscription-types.php';

// Activation : création des rôles et types
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (in_array('subscription', $active, true)) {
        if (!get_option('um_role_sub_meta')) {
            admin_lab_create_um_subscription_roles_if_missing();
        }

        admin_lab_register_subscription_account_types(); // Ajouter les types de comptes "sub" et "premium"
        do_action('admin_lab_subscription_module_activated');
    }
});

// Désactivation : suppression des rôles et types
add_action('admin_lab_subscription_module_desactivated', 'admin_lab_delete_um_subscription_roles');
add_action('admin_lab_subscription_module_desactivated', 'admin_lab_unregister_subscription_account_types');

// Protection front-end des pages souscription
add_action('template_redirect', 'admin_lab_protect_subscription_pages');

// Enqueue Choices.js
function admin_lab_enqueue_choices_js_subscription() {
    wp_enqueue_style('choices-css', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
    wp_enqueue_script('choices-js', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'admin_lab_enqueue_choices_js_subscription');

<?php
// File: modules/partnership/partnership.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('partnership', $active_modules, true)) return;

// Chargement des fonctions du module
require_once __DIR__ . '/functions/partnership-roles.php';
require_once __DIR__ . '/functions/partnership-pages.php';
require_once __DIR__ . '/functions/partnership-types.php';
require_once __DIR__ . '/functions/partnership-menu.php';
require_once __DIR__ . '/shortcodes/partnership-shortcodes.php';

// Chargement de l’interface d’administration
if (is_admin()) {
    include_once __DIR__ . '/admin/partnership-admin-ui.php';
}

// Activation auto à l'init
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (in_array('partnership', $active, true)) {
        if (!get_option('um_role_partenaire_meta')) {
            admin_lab_create_um_partner_roles_if_missing();
        }

        admin_lab_register_partnership_account_types();

        do_action('admin_lab_partnership_module_activated');
    }
});

// Désactivation : suppression des rôles et des pages
add_action('admin_lab_partnership_module_desactivated', 'admin_lab_delete_um_partner_roles');
add_action('admin_lab_partnership_module_desactivated', 'admin_lab_delete_partnership_pages');

// Désactivation : suppression des types de comptes partenaires
add_action('admin_lab_partnership_module_desactivated', 'admin_lab_unregister_partnership_account_types');

// Protection front-end des pages partenariat
add_action('template_redirect', 'admin_lab_protect_partnership_pages');

// Enqueue Choices.js
function admin_lab_enqueue_choices_js_partnership() {
    wp_enqueue_style('choices-css', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
    wp_enqueue_script('choices-js', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'admin_lab_enqueue_choices_js_partnership');

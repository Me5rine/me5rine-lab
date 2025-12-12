<?php
// File: modules/socialls/socialls.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('socialls', $active_modules, true)) return;

// Inclure les autres fichiers du module
require_once __DIR__ . '/functions/socialls-functions.php';
require_once __DIR__ . '/functions/socialls-shortcodes.php';
require_once __DIR__ . '/functions/socialls-pages.php';

// Inclure immédiatement le fichier avec la fonction du menu admin
if (is_admin()) {
    require_once __DIR__ . '/admin/socialls-admin-ui.php';
}

// Ensuite vérifier si le module est activé avant d'inclure les autres
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('socialls', $active_modules, true)) return;

// Activation auto à l'init
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (in_array('socialls', $active, true)) {
        admin_lab_socialls_create_pages();
        do_action('admin_lab_socialls_module_activated');
    }
});

// Désactivation : suppression des pages
add_action('admin_lab_socialls_module_desactivated', 'admin_lab_delete_socialls_pages');

// Protection front-end des pages socialls
add_action('template_redirect', 'admin_lab_protect_socialls_pages');

// Inclure la Media Library WordPress pour la gestion des icônes des réseaux sociaux
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'admin-lab-socialls') !== false) {
        wp_enqueue_media();
    }
});

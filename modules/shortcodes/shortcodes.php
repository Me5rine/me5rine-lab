<?php
// File: modules/shortcodes/shortcodes.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('shortcodes', $active_modules, true)) return;

// Chargement de l’interface d’administration
if (is_admin()) {
    require_once __DIR__ . '/admin/shortcodes-admin-ui.php';
}

// Chargement conditionnel de WP_List_Table si besoin
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Chargement de la classe de liste des shortcodes
$shortcode_class_file = __DIR__ . '/admin/shortcodes-list-table.php';
if (!class_exists('Admin_LAB_Shortcodes_List_Table') && file_exists($shortcode_class_file)) {
    require_once $shortcode_class_file;
}

// Chargement des composants
require_once __DIR__ . '/functions/shortcodes-functions.php';
require_once __DIR__ . '/admin/shortcodes-edit.php';

// Chargement JS/CSS pour l'interface admin des shortcodes uniquement
if (is_admin()) {
    add_action('admin_enqueue_scripts', function ($hook) {
        // Assure-toi de bien cibler la page des shortcodes
        if (!isset($_GET['page']) || $_GET['page'] !== 'admin-lab-shortcodes') {
            return;
        }

        // JS
        wp_enqueue_script(
            'admin-lab-shortcodes-js',
            ME5RINE_LAB_URL . 'assets/js/shortcodes-admin-table.js',
            [],
            ME5RINE_LAB_VERSION,
            true
        );

        // CSS (optionnel)
        wp_enqueue_style(
            'admin-lab-shortcodes-css',
            ME5RINE_LAB_URL . 'assets/css/shortcodes-admin-table.css',
            [],
            ME5RINE_LAB_VERSION
        );
    });
}

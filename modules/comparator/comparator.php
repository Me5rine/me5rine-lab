<?php
// File: modules/comparator/comparator.php

if (!defined('ABSPATH')) {
    exit;
}

// 1) Settings en premier (contient get_settings / update_settings / UI)
require_once __DIR__ . '/admin/comparator-settings.php';

// 2) API (utilise admin_lab_comparator_get_settings())
require_once __DIR__ . '/api/comparator-api.php';

// 3) Rendu et shortcodes/blocs
require_once __DIR__ . '/functions/comparator-helpers.php';
require_once __DIR__ . '/functions/comparator-render.php';
require_once __DIR__ . '/functions/comparator-shortcodes.php';
require_once __DIR__ . '/functions/comparator-render.php';
require_once __DIR__ . '/functions/comparator-shortcodes.php';
require_once __DIR__ . '/functions/comparator-widgets.php';
require_once __DIR__ . '/functions/comparator-tracking.php';

/**
 * Bootstrap du module Comparator pour Me5rine LAB
 */

// Front (shortcodes + blocs)
add_action('init', 'admin_lab_comparator_init');
function admin_lab_comparator_init() {
    admin_lab_comparator_register_shortcodes();
    admin_lab_comparator_register_blocks();
}

// Admin (options / settings)
add_action('admin_init', 'admin_lab_comparator_register_settings');

// Shortcodes + blocs dynamiques
add_action('init', 'admin_lab_comparator_register_shortcodes');
add_action('init', 'admin_lab_comparator_register_blocks');

// Widgets
add_action('widgets_init', 'admin_lab_comparator_register_widgets');

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'admin-lab-comparator-widgets',
        ME5RINE_LAB_URL . 'assets/css/comparator-widgets.css',
        [],
        ME5RINE_LAB_VERSION
    );
});



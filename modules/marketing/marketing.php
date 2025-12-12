<?php
// File: modules/marketing/marketing.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('marketing_campaigns', $active_modules, true)) return;

// Définition globale des zones marketing disponibles
global $admin_lab_marketing_zones;
$admin_lab_marketing_zones = [
    'sidebar_1'  => 'Sidebar 1',
    'sidebar_2'  => 'Sidebar 2',
    'sidebar_3'  => 'Sidebar 3',
    'banner_1'   => 'Banner 1',
    'banner_2'   => 'Banner 2',
    'banner_3'   => 'Banner 3',
    'background' => 'Background'
];

// Inclure immédiatement le fichier avec la fonction du menu admin
if (is_admin()) {
    require_once __DIR__ . '/admin/marketing-admin-ui.php';
}

// Ensuite tu peux vérifier que le module est activé avant d'inclure les autres
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('marketing_campaigns', $active_modules, true)) return;

// Inclure les autres fichiers du module
require_once __DIR__ . '/templates/marketing-edit.php';
require_once __DIR__ . '/functions/marketing-functions.php';
require_once __DIR__ . '/functions/marketing-shortcodes.php';
require_once __DIR__ . '/functions/frontend-display.php';
require_once __DIR__ . '/includes/marketing-list-table.php';

// Inclure la Media Library WordPress
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'admin-lab-marketing') !== false) {
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('admin-lab-media-url', ME5RINE_LAB_URL . 'assets/js/admin-lab-media-url.js', ['jquery', 'media-views'], ME5RINE_LAB_VERSION, true);
    }

        wp_localize_script('admin-lab-media-url', 'marketingMedia', [
        'selectTitle' => __('Select or Upload Campaign Image', 'me5rine-lab'),
        'buttonText'  => __('Use this image', 'me5rine-lab'),
        'tabUrl'      => __('Insert from URL', 'me5rine-lab'),
        'inputLabel'  => __('Image URL:', 'me5rine-lab'),
        'inputDesc'   => __('Enter a direct image URL to use instead of the media library.', 'me5rine-lab'),
    ]);
});

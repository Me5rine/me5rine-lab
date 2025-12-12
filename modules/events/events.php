<?php
// File: modules/events/events.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('events', $active_modules, true)) return;

// Includes
require_once __DIR__ . '/includes/events-taxonomy.php';
require_once __DIR__ . '/functions/events-helper.php';
require_once __DIR__ . '/admin/events-meta-box.php';
require_once __DIR__ . '/admin/events-admin-columns.php';

// Chargement des assets CSS/JS du module Events
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    wp_enqueue_style('admin-lab-events-admin', ME5RINE_LAB_URL . 'assets/css/events-admin.css', [], ME5RINE_LAB_VERSION);

    wp_enqueue_script('admin-lab-events-admin', ME5RINE_LAB_URL . 'assets/js/events-admin.js', ['jquery'], ME5RINE_LAB_VERSION, true);
});

// Hooks spécifiques (activation / désactivation du module)
add_action('admin_lab_events_module_activated', function () {
    $default_label = __('Default', 'me5rine-lab');
    $default_slug  = sanitize_title($default_label);

    if (!term_exists($default_label, 'event_type')) {
        wp_insert_term($default_label, 'event_type', ['slug' => $default_slug]);
    }
});

add_action('admin_lab_events_module_desactivated', function () {

});

/**
 * Enqueue Media Library + JS uniquement sur la taxonomy "event_type"
 */
add_action('admin_enqueue_scripts', function ($hook) {
    // On cible les écrans d’édition de taxonomies : edit-tags.php (liste + ajout) et term.php (édition)
    if (!in_array($hook, ['edit-tags.php', 'term.php'], true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->taxonomy !== 'event_type') {
        return;
    }

    // Médiathèque WP
    wp_enqueue_media();

    // Color picker WordPress (pour la couleur)
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // JS de gestion de l'image par défaut de la taxonomy
    wp_enqueue_script('admin-lab-media-url', ME5RINE_LAB_URL . 'assets/js/admin-lab-media-url.js', ['jquery', 'media-views'], ME5RINE_LAB_VERSION, true);

    wp_localize_script(
        'admin-lab-media-url', 'eventsTaxMedia',
        [
            'selectTitle' => __('Select or Upload Default Event Image', 'me5rine-lab'),
            'buttonText'  => __('Use this image', 'me5rine-lab'),
            'tabUrl'      => __('Insert from URL', 'me5rine-lab'),
            'inputLabel'  => __('Image URL:', 'me5rine-lab'),
            'inputDesc'   => __('Enter a direct image URL to use instead of the media library.', 'me5rine-lab'),
            'useUrl'      => __('Use this URL', 'me5rine-lab'),
            'emptyUrl'    => __('Please enter a valid URL.', 'me5rine-lab'),
        ]
    );

    // Init JS du color picker sur notre champ de taxonomy
    wp_add_inline_script(
        'admin-lab-media-url',
        'jQuery(function($){ $(".event-type-color-field").wpColorPicker(); });'
    );
});

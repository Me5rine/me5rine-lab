<?php
// File: modules/giveaways/register/giveaways-register-types.php

if (!defined('ABSPATH')) exit;

$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('giveaways', $active_modules, true)) return;

/**
 * Enregistre le Custom Post Type "giveaway".
 */
function giveaways_register_custom_post_type() {
    $labels = [
        'name' => __('Giveaways', 'me5rine-lab'),
        'singular_name' => __('Giveaway', 'me5rine-lab'),
        'menu_name' => __('Giveaways', 'me5rine-lab'),
        'name_admin_bar' => __('Giveaway', 'me5rine-lab'),
        'add_new' => __('Add New Giveaway', 'me5rine-lab'),
        'add_new_item' => __('Add New Giveaway', 'me5rine-lab'),
        'new_item' => __('New Giveaway', 'me5rine-lab'),
        'edit_item' => __('Edit Giveaway', 'me5rine-lab'),
        'view_item' => __('View Giveaway', 'me5rine-lab'),
        'all_items' => __('All Giveaways', 'me5rine-lab'),
        'search_items' => __('Search Giveaways', 'me5rine-lab'),
        'not_found' => __('No giveaways found.', 'me5rine-lab'),
        'not_found_in_trash' => __('No giveaways found in Trash.', 'me5rine-lab'),
    ];

    register_post_type('giveaway', [
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'query_var' => true,
        'rewrite' => ['slug' => 'giveaway'],
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields']
    ]);
}
add_action('init', 'giveaways_register_custom_post_type');

/**
 * Enregistre les taxonomies associées au CPT "giveaway".
 */
function giveaways_register_taxonomies() {
    register_taxonomy('giveaway_rewards', 'giveaway', [
        'label' => __('Rewards', 'me5rine-lab'),
        'rewrite' => ['slug' => 'giveaway-rewards'],
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_ui' => true,
    ]);

    register_taxonomy('giveaway_category', 'giveaway', [
        'label' => __('Giveaway Categories', 'me5rine-lab'),
        'rewrite' => ['slug' => 'giveaway-category'],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_ui' => true,
    ]);
}
add_action('init', 'giveaways_register_taxonomies');

/**
 * Crée les catégories par défaut pour les concours.
 */
function giveaways_create_default_categories() {
    // S'assurer que la taxonomie existe
    if (!taxonomy_exists('giveaway_category')) {
        return;
    }

    // Créer les catégories par défaut si elles n'existent pas
    if (!term_exists('Me5rine LAB', 'giveaway_category')) {
        wp_insert_term('Me5rine LAB', 'giveaway_category');
    }

    if (!term_exists('Partenaires', 'giveaway_category')) {
        wp_insert_term('Partenaires', 'giveaway_category');
    }
}
add_action('init', 'giveaways_create_default_categories', 20); // Priorité plus élevée que les enregistrements

<?php
// File: modules/giveaways/functions/giveaways-pages.php

if (!defined('ABSPATH')) exit;

/**
 * Crée les pages liées au module Giveaways.
 */
function admin_lab_giveaways_create_pages() {
    $default_pages = [
        'admin-giveaways' => [
            'title'   => 'Mes concours',
            'content' => '[admin_giveaways]'
        ],
        'add-giveaway' => [
            'title'   => 'Ajouter un concours',
            'content' => '[add_giveaway]'
        ],
        'edit-giveaway' => [
            'title'   => 'Modifier un concours',
            'content' => '[edit_giveaway]'
        ]
    ];

    foreach ($default_pages as $slug => $data) {
        $option_key = 'giveaways_page_' . $slug;
        $page_id = get_option($option_key);

        if ($page_id && get_post_status($page_id)) continue;

        $existing = get_posts([
            'post_type'      => 'page',
            'post_status'    => ['publish', 'draft', 'private', 'pending', 'trash'],
            'name'           => $slug,
            'posts_per_page' => 1,
            'fields'         => 'ids'
        ]);

        if (!empty($existing)) {
            update_option($option_key, $existing[0]);
            continue;
        }

        $new_page_id = wp_insert_post([
            'post_title'     => $data['title'],
            'post_name'      => $slug,
            'post_content'   => $data['content'],
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => get_current_user_id(),
            'comment_status' => 'closed'
        ]);

        if (!is_wp_error($new_page_id)) {
            update_option($option_key, $new_page_id);
        }
    }
}

/**
 * Supprime les pages à la désactivation.
 */
function admin_lab_delete_giveaways_pages() {
    $page_slugs = ['admin-giveaways', 'add-giveaway', 'edit-giveaway']; // ← corrigé ici

    foreach ($page_slugs as $slug) {
        $option_key = 'giveaways_page_' . $slug;
        $page_id = get_option($option_key);

        if ($page_id && get_post($page_id)) {
            wp_delete_post($page_id, true);
        }

        delete_option($option_key);
    }
}
add_action('admin_lab_giveaways_module_desactivated', 'admin_lab_delete_giveaways_pages');

/**
 * Protège les pages Giveaways.
 */
function admin_lab_protect_giveaways_pages() {
    $protected_pages = array_filter([
        get_option('giveaways_page_admin-giveaways'),
        get_option('giveaways_page_add-giveaway'),
        get_option('giveaways_page_edit-giveaway')
    ], function($id) {
        return $id && is_numeric($id);
    });

    if (empty($protected_pages)) return;

    if (is_page($protected_pages)) {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (admin_lab_user_has_allowed_role('giveaways', $user->ID)) return;
            wp_redirect(home_url('/'));
            exit;
        } else {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }
}
add_action('template_redirect', 'admin_lab_protect_giveaways_pages');
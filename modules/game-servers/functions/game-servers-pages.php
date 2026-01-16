<?php
// File: modules/game-servers/functions/game-servers-pages.php

if (!defined('ABSPATH')) exit;

/**
 * Creates pages related to the Game Servers module.
 */
function admin_lab_game_servers_create_pages() {
    $default_pages = [
        'game-servers' => [
            'title'   => __('Game Servers', 'me5rine-lab'),
            'content' => '[game_servers_list]'
        ]
    ];

    foreach ($default_pages as $slug => $data) {
        $option_key = 'game_servers_page_' . $slug;
        $page_id = get_option($option_key);
        
        if ($page_id && get_post_status($page_id)) {
            continue;
        }
        
        $existing_page = get_page_by_path($slug);
        if ($existing_page) {
            update_option($option_key, $existing_page->ID);
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
 * Deletes pages on module deactivation.
 */
function admin_lab_game_servers_delete_pages() {
    $page_slugs = ['game-servers'];

    foreach ($page_slugs as $slug) {
        $option_key = 'game_servers_page_' . $slug;
        $page_id = get_option($option_key);

        if ($page_id && get_post($page_id)) {
            wp_delete_post($page_id, true);
        }

        delete_option($option_key);
    }
}


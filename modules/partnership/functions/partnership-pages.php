<?php
// File: modules/partnership/functions/partnership-pages.php

if (!defined('ABSPATH')) exit;

function admin_lab_partnership_create_pages() {
    $default_pages = [
        'partenariat' => [
            'title'   => 'Tableau de bord',
            'content' => '[partner_dashboard]'
        ]
    ];

    foreach ($default_pages as $slug => $data) {
        $option_key = 'partnership_page_' . $slug;
        $page_id = get_option($option_key);
        if ($page_id && get_post_status($page_id)) continue;
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

function admin_lab_delete_partnership_pages() {
    $page_slugs = ['partenariat'];

    foreach ($page_slugs as $slug) {
        $option_key = 'partnership_page_' . $slug;
        $page_id = get_option($option_key);

        if ($page_id && get_post($page_id)) {
            wp_delete_post($page_id, true);
        }

        delete_option($option_key);
    }
}

function admin_lab_protect_partnership_pages() {
    $page_id = get_option('partnership_page_partenariat');
    if (!$page_id || !is_numeric($page_id)) return;

    if (is_page($page_id)) {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            if (admin_lab_user_is_partner($user_id)) return;
            wp_redirect(home_url('/'));
            exit;
        } else {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }
}
add_action('template_redirect', 'admin_lab_protect_partnership_pages');

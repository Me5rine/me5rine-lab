<?php
// File: modules/remote-news/register/remote-news-register-types.php

if (!defined('ABSPATH')) exit;

$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('remote_news', $active_modules, true)) return;

// Enregistre le custom_post_type et remplace le permalien par l’URL distante
if (!defined('ABSPATH')) exit;

function remote_news_register_custom_post_type() {
    register_post_type('remote_news', [
        'labels' => [
            'name'          => __('Remote News', 'me5rine-lab'),
            'singular_name' => __('Remote News', 'me5rine-lab'),
            'add_new_item'  => __('Add Remote News', 'me5rine-lab'),
            'edit_item'     => __('Edit Remote News', 'me5rine-lab'),
            'all_items'     => __('All Remote News', 'me5rine-lab'),
        ],
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => false,
        'query_var'    => true,
        'rewrite'      => ['slug' => 'remote-news', 'with_front' => false],
        'has_archive'  => false,
        'supports'     => ['title','editor','excerpt','thumbnail','author','custom-fields'],
        'menu_position'=> 5,
    ]);
}
add_action('init', 'remote_news_register_custom_post_type');

function remote_news_external_permalink($permalink, $post, $leavename) {
    if ($post->post_type !== 'remote_news') return $permalink;
    $url = get_post_meta($post->ID, '_remote_source_url', true);
    return $url ?: $permalink;
}
add_filter('post_type_link', 'remote_news_external_permalink', 10, 3);

<?php
// File: modules/giveaways/functions/giveaways-admin-actions.php

if (!defined('ABSPATH')) exit;

add_action('admin_post_publish_giveaway', function () {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You are not allowed to do this action.', 'me5rine-lab'));
    }

    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    $nonce   = $_GET['_wpnonce'] ?? '';

    if (!$post_id || !wp_verify_nonce($nonce, 'publish_giveaway_' . $post_id)) {
        wp_die(__('Invalid request.', 'me5rine-lab'));
    }

    wp_update_post([
        'ID'          => $post_id,
        'post_status' => 'publish',
    ]);

    wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php?post_type=giveaway'));
    exit;
});

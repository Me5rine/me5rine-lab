<?php
// File: modules/user-management/functions/user-management-um-filters.php

if (!defined('ABSPATH')) exit;

add_filter('um_user_profile_photo', function ($url, $user_id) {
    $shared_url = get_user_meta($user_id, 'shared_profile_photo_url', true);
    return $shared_url ?: $url;
}, 10, 2);

add_action('um_after_upload_profile_photo', function ($user_id, $upload, $args) {
    if (!empty($upload['url'])) {
        update_user_meta($user_id, 'shared_profile_photo_url', esc_url_raw($upload['url']));
    }
}, 10, 3);

<?php
// File: modules/subscription/functions/subscription-pages

if (!defined('ABSPATH')) exit;

function admin_lab_protect_subscription_pages() {
    if (is_page('subscription-page') && !is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }
}

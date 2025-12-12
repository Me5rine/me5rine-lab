<?php
// File: modules/user-management/functions/openid-logout-hooks.php

if (!defined('ABSPATH')) exit;

add_action('check_admin_referer', 'admin_lab_logout_without_confirm', 10, 2);
function admin_lab_logout_without_confirm($action, $result)
{
    if (in_array($action, ['log-out', 'openid-connect-logout']) && !isset($_GET['_wpnonce'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : home_url('/');
        
        wp_logout();
        wp_redirect($redirect_to);
        exit;
    }
}

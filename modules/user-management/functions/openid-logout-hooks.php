<?php
// File: modules/user-management/functions/openid-logout-hooks.php

if (!defined('ABSPATH')) exit;

add_action('check_admin_referer', 'admin_lab_logout_without_confirm', 10, 2);
function admin_lab_logout_without_confirm($action, $result)
{
    // Vérifier que l'action est bien une action de déconnexion
    if (!in_array($action, ['log-out', 'openid-connect-logout'])) {
        return;
    }
    
    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return;
    }
    
    // Vérifier qu'aucun nonce n'est présent (ni dans GET, ni dans POST, ni dans REQUEST)
    // Cela permet la déconnexion sans nonce uniquement pour OpenID Connect
    $nonce_in_get = isset($_GET['_wpnonce']) && !empty($_GET['_wpnonce']);
    $nonce_in_post = isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce']);
    $nonce_in_request = isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce']);
    
    // Si un nonce est présent, laisser WordPress gérer la déconnexion normalement
    if ($nonce_in_get || $nonce_in_post || $nonce_in_request) {
        return;
    }
    
    // Vérifier que c'est vraiment une tentative de déconnexion (paramètre action présent)
    // pour éviter les déclenchements accidentels
    $action_param = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';
    if (!in_array($action_param, ['logout', 'openid-connect-logout'], true)) {
        return;
    }
    
    // Si toutes les conditions sont remplies, procéder à la déconnexion sans nonce
    // (pour OpenID Connect uniquement)
    $redirect_to = isset($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : home_url('/');
    
    wp_logout();
    wp_safe_redirect($redirect_to);
    exit;
}

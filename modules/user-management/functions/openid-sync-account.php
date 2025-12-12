<?php
// File: modules/user-management/functions/openid-sync-account.php

if (!defined('ABSPATH')) exit;

// Synchronisation des données utilisateurs depuis OpenID Connect (ex: Keycloak)
add_action('openid-connect-generic-update-user-using-current-claim', function($user, $user_claim) {
    $update_data = ['ID' => $user->ID];

    if (!empty($user_claim['email']) && $user_claim['email'] !== $user->user_email) {
        $update_data['user_email'] = sanitize_email($user_claim['email']);
    }

    if (!empty($user_claim['given_name']) && $user_claim['given_name'] !== $user->first_name) {
        $update_data['first_name'] = sanitize_text_field($user_claim['given_name']);
    }

    if (!empty($user_claim['family_name']) && $user_claim['family_name'] !== $user->last_name) {
        $update_data['last_name'] = sanitize_text_field($user_claim['family_name']);
    }

    if (count($update_data) > 1) {
        wp_update_user($update_data);
    }
}, 10, 2);

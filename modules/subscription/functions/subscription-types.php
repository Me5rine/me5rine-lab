<?php
// File: modules/subscription/functions/subscription-types.php

if (!defined('ABSPATH')) exit;

/**
 * Crée les types de comptes "sub" et "premium" lors de l'activation du module.
 */
function admin_lab_register_subscription_account_types() {
    if (!admin_lab_is_main_site()) return;

    $existing = admin_lab_get_registered_account_types();

    if (!isset($existing['sub'])) {
        admin_lab_register_account_type('sub', [
            'label' => 'Abonné',
            'role'  => 'um_sub',
            'scope' => 'global',
        ]);
    }

    if (!isset($existing['premium'])) {
        admin_lab_register_account_type('premium', [
            'label' => 'Premium',
            'role'  => 'um_premium',
            'scope' => 'global',
        ]);
    }
}

/**
 * Supprime les types de comptes "sub" et "premium" lors de la désactivation du module.
 */
function admin_lab_unregister_subscription_account_types() {
    if (!admin_lab_is_main_site()) return;

    admin_lab_unregister_account_type('sub');
    admin_lab_unregister_account_type('premium');
}

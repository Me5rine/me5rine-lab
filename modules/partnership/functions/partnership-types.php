<?php
// File: modules/partnership/functions/partnership-types.php

if (!defined('ABSPATH')) exit;

/**
 * Crée les types de comptes partenaires lors de l'activation du module.
 */
function admin_lab_register_partnership_account_types() {
    if (!admin_lab_is_main_site()) return;

    $existing = admin_lab_get_registered_account_types();

    if (!isset($existing['partenaire'])) {
        admin_lab_register_account_type('partenaire', [
            'label' => 'Partenaire',
            'role'  => 'um_partenaire',
            'scope' => 'global',
        ]);
    }

    if (!isset($existing['partenaire_plus'])) {
        admin_lab_register_account_type('partenaire_plus', [
            'label' => 'Partenaire+',
            'role'  => 'um_partenaire_plus',
            'scope' => 'global',
        ]);
    }
}

/**
 * Supprime les types de comptes partenaires lors de la désactivation du module.
 */
function admin_lab_unregister_partnership_account_types() {
    if (!admin_lab_is_main_site()) return;

    admin_lab_unregister_account_type('partenaire');
    admin_lab_unregister_account_type('partenaire_plus');
}

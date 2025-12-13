<?php
// File: modules/user-management/functions/sync-account-type-roles.php

if (!defined('ABSPATH')) exit;

/**
 * Synchronise automatiquement les rôles WordPress d'un utilisateur
 * en fonction des types de comptes définis dans la meta 'admin_lab_account_types'.
 * 
 * Cette fonction synchronise également les rôles sur tous les sites du réseau
 * en respectant la portée définie pour chaque type de compte.
 */
function admin_lab_sync_roles_from_account_types($meta_id, $user_id, $meta_key, $_meta_value) {
    if ($meta_key !== 'admin_lab_account_types') return;

    $user = new WP_User($user_id);
    $types = get_user_meta($user_id, 'admin_lab_account_types', true);
    if (!is_array($types)) $types = [];

    $registered = admin_lab_get_registered_account_types();
    $expected_roles = [];

    foreach ($types as $type_slug) {
        if (!empty($registered[$type_slug]['role'])) {
            $expected_roles[] = $registered[$type_slug]['role'];
        }
    }

    $all_defined_roles = array_filter(array_map(fn($t) => $t['role'] ?? null, $registered));

    // Supprimer les rôles qui ne sont plus liés à un type de compte
    foreach ($all_defined_roles as $role) {
        if (!in_array($role, $expected_roles) && in_array($role, $user->roles)) {
            $user->remove_role($role);
            // Synchroniser la suppression sur tous les sites
            admin_lab_update_user_role_across_sites($user_id, $role, false);
        }
    }

    // Ajouter les rôles manquants
    foreach ($expected_roles as $role) {
        if (!in_array($role, $user->roles)) {
            $user->add_role($role);
            // Synchroniser l'ajout sur tous les sites
            admin_lab_update_user_role_across_sites($user_id, $role, true);
        }
    }
}

add_action('update_user_meta', 'admin_lab_sync_roles_from_account_types', 10, 4);
add_action('delete_user_meta', 'admin_lab_sync_roles_from_account_types', 10, 4);

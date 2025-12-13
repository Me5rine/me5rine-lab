<?php
// File: modules/user-management/functions/sync-account-type-roles.php

if (!defined('ABSPATH')) exit;

/**
 * Synchronise les rôles WordPress d'un utilisateur en fonction de ses types de comptes.
 * 
 * Cette fonction peut être appelée directement ou via le hook update_user_meta.
 * Elle synchronise également les rôles sur tous les sites du réseau
 * en respectant la portée définie pour chaque type de compte.
 * 
 * @param int $user_id ID de l'utilisateur à synchroniser
 */
function admin_lab_sync_user_roles_from_account_types($user_id) {
    $user = new WP_User($user_id);
    if (!$user || !$user->ID) {
        return;
    }

    $types = get_user_meta($user_id, 'admin_lab_account_types', true);
    if (!is_array($types)) {
        $types = [];
    }

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
            if (function_exists('admin_lab_update_user_role_across_sites')) {
                admin_lab_update_user_role_across_sites($user_id, $role, false);
            }
        }
    }

    // Ajouter les rôles manquants
    foreach ($expected_roles as $role) {
        if (!in_array($role, $user->roles)) {
            $user->add_role($role);
            // Synchroniser l'ajout sur tous les sites
            if (function_exists('admin_lab_update_user_role_across_sites')) {
                admin_lab_update_user_role_across_sites($user_id, $role, true);
            }
        }
    }
}

/**
 * Hook pour synchroniser automatiquement les rôles lors de la mise à jour de la meta.
 * 
 * Cette fonction est appelée par le hook update_user_meta.
 */
function admin_lab_sync_roles_from_account_types($meta_id, $user_id, $meta_key, $_meta_value) {
    if ($meta_key !== 'admin_lab_account_types') {
        return;
    }
    
    admin_lab_sync_user_roles_from_account_types($user_id);
}

add_action('update_user_meta', 'admin_lab_sync_roles_from_account_types', 10, 4);
add_action('delete_user_meta', 'admin_lab_sync_roles_from_account_types', 10, 4);

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
            // Synchroniser la suppression sur tous les sites SAUF le site actuel
            // (le site actuel vient d'être mis à jour avec remove_role)
            if (function_exists('admin_lab_update_user_role_across_sites')) {
                admin_lab_update_user_role_across_sites($user_id, $role, false, true);
            }
        }
    }

    // Ajouter les rôles manquants
    global $wpdb;
    $current_prefix = $wpdb->prefix;
    $cap_key = $current_prefix . 'capabilities';
    $needs_reload = false;
    
    foreach ($expected_roles as $role) {
        if (!in_array($role, $user->roles)) {
            // Méthode 1 : Utiliser add_role() de WordPress
            $user->add_role($role);
            $needs_reload = true;
            
            // Méthode 2 : Ajouter directement via les capabilities pour garantir la persistance
            // (utile si add_role() ne fonctionne pas sur certains sites)
            $caps = get_user_meta($user_id, $cap_key, true);
            if (!is_array($caps)) {
                $caps = [];
            }
            if (empty($caps[$role])) {
                $caps[$role] = true;
                update_user_meta($user_id, $cap_key, $caps);
            }
        }
    }
    
    // Recharger l'utilisateur une seule fois après toutes les modifications
    if ($needs_reload) {
        clean_user_cache($user_id);
        $user = new WP_User($user_id);
        
        // Vérifier et synchroniser tous les rôles ajoutés
        foreach ($expected_roles as $role) {
            if (in_array($role, $user->roles)) {
                // Synchroniser l'ajout sur tous les sites SAUF le site actuel
                if (function_exists('admin_lab_update_user_role_across_sites')) {
                    admin_lab_update_user_role_across_sites($user_id, $role, true, true);
                }
            } else {
                // Si le rôle n'est toujours pas là, forcer l'ajout une dernière fois
                $caps = get_user_meta($user_id, $cap_key, true);
                if (!is_array($caps)) {
                    $caps = [];
                }
                $caps[$role] = true;
                update_user_meta($user_id, $cap_key, $caps);
                clean_user_cache($user_id);
                
                // Synchroniser après le forçage
                if (function_exists('admin_lab_update_user_role_across_sites')) {
                    admin_lab_update_user_role_across_sites($user_id, $role, true, true);
                }
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

/**
 * Protège les rôles liés aux types de compte après une mise à jour de profil.
 * S'exécute avec une priorité très élevée (999) pour s'assurer que cela se fait
 * APRÈS Ultimate Member et autres plugins qui pourraient modifier les rôles.
 */
function admin_lab_protect_account_type_roles_after_um($user_id, $old_user_data = null) {
    // Synchroniser les rôles après toute mise à jour de profil
    // Cela restaure les rôles si Ultimate Member ou un autre plugin les a supprimés
    admin_lab_sync_user_roles_from_account_types($user_id);
}

/**
 * Hook avec priorité très élevée pour s'exécuter APRÈS Ultimate Member
 * et restaurer les rôles liés aux types de compte si Ultimate Member les a supprimés.
 */
add_action('profile_update', 'admin_lab_protect_account_type_roles_after_um', 999, 2);

/**
 * Hook spécifique Ultimate Member pour restaurer les rôles après mise à jour UM.
 */
add_action('um_after_user_updated', 'admin_lab_sync_user_roles_from_account_types', 999);
add_action('um_user_after_updating_profile', 'admin_lab_sync_user_roles_from_account_types', 999);

/**
 * Hook différé pour vérifier et corriger les rôles après l'ajout d'un type de compte.
 * 
 * Ce hook s'exécute après que tous les autres hooks aient terminé, pour s'assurer
 * que le rôle persiste même si un autre plugin/hook l'a supprimé.
 * 
 * Optimisé : utilise une variable statique pour éviter les requêtes SQL répétées.
 */
add_action('shutdown', function() {
    static $processed_users = [];
    static $modified_users_cache = null;
    
    // Charger la liste des utilisateurs modifiés une seule fois
    if ($modified_users_cache === null) {
        global $wpdb;
        $modified_users_cache = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key = %s AND meta_value = %s",
                '_admin_lab_account_types_modified',
                '1'
            )
        );
    }
    
    foreach ($modified_users_cache as $user_id) {
        if (in_array($user_id, $processed_users, true)) {
            continue;
        }
        
        $processed_users[] = $user_id;
        
        // Vérifier et corriger les rôles
        admin_lab_sync_user_roles_from_account_types($user_id);
        
        // Supprimer la meta temporaire
        delete_user_meta($user_id, '_admin_lab_account_types_modified');
    }
}, 999);

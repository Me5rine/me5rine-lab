<?php
// File: modules/user-management/functions/account-types-functions.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère le type de compte de l'utilisateur.
 */
function admin_lab_get_account_type($user_id = null) {
    static $cache = [];

    $user_id = $user_id ?: get_current_user_id();

    if (!isset($cache[$user_id])) {
        $types = get_user_meta($user_id, 'admin_lab_account_types', true);
        $cache[$user_id] = is_array($types) ? $types : [];
    }

    return $cache[$user_id];
}

/**
 * Vérifie si l'utilisateur a un type de compte donné.
 */
function admin_lab_is_account_type($type, $user_id = null) {
    return in_array($type, admin_lab_get_account_type($user_id), true);
}

/**
 * Met à jour la portée du type de compte "partenaire" ou "partenaire_plus".
 *
 * Cette fonction modifie les metas `partner_sites` et `partner_all_sites` selon le scope fourni.
 * Si aucun des types de compte n’est encore défini comme partenaire, elle nettoie les metas.
 *
 * @param int          $user_id               Utilisateur concerné.
 * @param string       $type                  'partenaire' ou 'partenaire_plus'.
 * @param string|array $scope                 'all' pour tous les sites, un tableau de domaines, ou null pour désactiver.
 * @param array        $all_current_types     Liste complète des types définis (pour nettoyer si besoin).
 */
function admin_lab_set_account_scope(int $user_id, string $type, $scope, array $current_account_types = []): void {
    $is_current_type_partner = in_array($type, ['partenaire', 'partenaire_plus'], true);
    $has_other_partner_type  = count(array_intersect(['partenaire', 'partenaire_plus'], $current_account_types)) > 1;

    if (!$is_current_type_partner) {
        if (!$has_other_partner_type) {
            delete_user_meta($user_id, 'partner_sites');
            delete_user_meta($user_id, 'partner_all_sites');
        }
        return;
    }

    delete_user_meta($user_id, 'partner_sites');
    delete_user_meta($user_id, 'partner_all_sites');

    if ($scope === 'all') {
        update_user_meta($user_id, 'partner_all_sites', 1);
    } elseif (is_array($scope) && !empty($scope)) {
        update_user_meta($user_id, 'partner_sites', $scope);
    }
}

/**
 * Récupère la portée d’un type de compte (global ou liste de domaines).
 *
 * Pour les comptes "partenaire" et "partenaire_plus", on regarde :
 * - `partner_all_sites` => retourne 'all'
 * - `partner_sites` => retourne un tableau de domaines
 * - sinon => retourne 'global'
 */
function admin_lab_get_account_scope($type, $user_id = null) {
    $user_id = $user_id ?: get_current_user_id();

    if (in_array($type, ['partenaire', 'partenaire_plus'], true)) {
        if (get_user_meta($user_id, 'partner_all_sites', true)) {
            return 'all';
        }

        $sites = get_user_meta($user_id, 'partner_sites', true);
        if (is_array($sites)) {
            return $sites;
        }

        return 'global';
    }

    $registered = admin_lab_get_registered_account_types();
    return $registered[$type]['scope'] ?? 'global';
}

/**
 * Vérifie si un type de compte est actif sur le site actuel.
 */
function admin_lab_is_account_type_active_here($type, $user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    if (!admin_lab_is_account_type($type, $user_id)) return false;

    $scope = admin_lab_get_account_scope($type, $user_id);
    if ($scope === 'global') return true;

    $host = $_SERVER['HTTP_HOST'];
    return in_array($host, $scope);
}

/**
 * Met à jour la liste des types de compte d'un utilisateur.
 *
 * Cette fonction ne gère **que** la méta `admin_lab_account_types`.
 * Elle ne modifie pas les rôles, ne déclenche aucune propagation réseau,
 * et ne tient pas compte des portées partenaires.
 *
 * Elle peut être utilisée pour :
 * - Ajouter un type de compte (`add`)
 * - Supprimer un type de compte (`remove`)
 * - Remplacer tous les types existants par un seul (`replace`)
 *
 * @param int    $user_id Utilisateur concerné.
 * @param string $type    Slug du type de compte à modifier.
 * @param string $action  Action à effectuer : 'add', 'remove' ou 'replace'. (Par défaut : 'add')
 */
function admin_lab_set_account_type($user_id, string $type, string $action = 'add'): void {
    $registered = admin_lab_get_registered_account_types();
    if (!isset($registered[$type])) {
        return;
    }

    $types = get_user_meta($user_id, 'admin_lab_account_types', true);
    if (!is_array($types)) {
        $types = [];
    }

    switch ($action) {
        case 'add':
            if (!in_array($type, $types, true)) {
                $types[] = $type;
                update_user_meta($user_id, 'admin_lab_account_types', $types);
            }
            break;

        case 'remove':
            $new_types = array_filter($types, fn($t) => $t !== $type);
            if ($new_types !== $types) {
                update_user_meta($user_id, 'admin_lab_account_types', $new_types);
            }
            break;

        case 'replace':
            update_user_meta($user_id, 'admin_lab_account_types', [$type]);
            break;
    }
}

/**
 * Met à jour la liste des types de comptes d'un utilisateur
 * en supprimant ceux qui ne sont plus listés et en ajoutant les nouveaux.
 */
function admin_lab_set_account_types_batch(int $user_id, array $new_types): void {
    $existing = get_user_meta($user_id, 'admin_lab_account_types', true);
    if (!is_array($existing)) $existing = [];

    $to_remove = array_diff($existing, $new_types);
    $to_add    = array_diff($new_types, $existing);

    foreach ($to_remove as $type) {
        admin_lab_set_account_type($user_id, $type, 'remove');
    }
    foreach ($to_add as $type) {
        admin_lab_set_account_type($user_id, $type, 'add');
    }
}

/**
 * Assigne ou retire un rôle WordPress à un utilisateur sur tous les sites,
 * en respectant la portée définie par le type de compte.
 *
 * Cette fonction repose sur `admin_lab_get_account_scope()` pour déterminer
 * sur quels domaines le rôle doit être actif.
 *
 * @param int    $user_id ID de l'utilisateur.
 * @param string $role    Slug du rôle WordPress.
 * @param bool   $assign  true = ajouter, false = retirer.
 */
function admin_lab_update_user_role_across_sites($user_id, $role, $assign) {
    $prefixes = admin_lab_get_all_sites_prefixes();

    $account_type_slug = admin_lab_role_to_account_type($role);
    if (!$account_type_slug) return;

    $scope = admin_lab_get_account_scope($account_type_slug, $user_id);

    foreach ($prefixes as $prefix) {
        $domain = admin_lab_get_site_domain_from_prefix($prefix);

        // Vérifie si ce domaine fait partie de la portée
        $should_have = (
            $scope === 'all' ||
            $scope === 'global' ||
            (is_array($scope) && in_array($domain, $scope, true))
        );

        $cap_key = $prefix . 'capabilities';
        $caps = get_user_meta($user_id, $cap_key, true);
        if (!is_array($caps)) $caps = [];

        if ($assign && $should_have && empty($caps[$role])) {
            $caps[$role] = true;
            update_user_meta($user_id, $cap_key, $caps);
        }

        if ((!$assign || !$should_have) && isset($caps[$role])) {
            unset($caps[$role]);
            update_user_meta($user_id, $cap_key, $caps);
        }
    }

    do_action('admin_lab_user_role_' . ($assign ? 'assigned' : 'removed') . '_across_sites', $user_id, $role);
}

/**
 * Récupère la liste des types de comptes définis dans l'admin.
 */
function admin_lab_get_registered_account_types() {
    static $types = null;

    if ($types !== null) {
        return $types;
    }

    global $wpdb;
    $table = admin_lab_getTable('account_types');

    $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);

    $types = [];
    foreach ($rows as $row) {
        $types[$row['slug']] = [
            'label'        => $row['label'],
            'role'         => $row['role'],
            'role_name'    => $row['role_name'],
            'capabilities' => maybe_unserialize($row['capabilities']),
            'scope'        => maybe_unserialize($row['scope'] ?? null),
            'modules'      => maybe_unserialize($row['modules'] ?? null),
        ];
    }

    return $types;
}

/**
 * Met à jour un type de compte existant dans la table dédiée.
 */
function admin_lab_update_account_type($slug, $args = []) {
    global $wpdb;
    $table = admin_lab_getTable('account_types');

    if (!is_string($slug) || $slug === '') {
        return;
    }

    $defaults = [
        'label'        => ucfirst($slug),
        'role'         => null,
        'role_name'    => null,
        'capabilities' => ['read' => true],
        'scope'        => 'global',
        'modules'      => null,
    ];

    $args = array_merge($defaults, $args);

    $data = [
        'label'        => (string) $args['label'],
        'role'         => $args['role'],
        'role_name'    => $args['role_name'],
        'capabilities' => maybe_serialize($args['capabilities']),
        'scope'        => maybe_serialize($args['scope']),
        'modules'      => isset($args['modules']) ? maybe_serialize($args['modules']) : null,
    ];

    $wpdb->update($table, $data, ['slug' => $slug]);
}

/**
 * Enregistre un nouveau type de compte dans la table dédiée.
 */
function admin_lab_register_account_type($slug, $args = []) {
    global $wpdb;
    $table = admin_lab_getTable('account_types');

    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table` WHERE slug = %s", $slug));
    if ($exists) return;

    $data = [
        'slug'         => $slug,
        'label'        => $args['label'] ?? ucfirst($slug),
        'role'         => $args['role'] ?? null,
        'role_name'    => $args['role_name'] ?? null,
        'capabilities' => maybe_serialize($args['capabilities'] ?? ['read' => true]),
        'scope'        => maybe_serialize($args['scope'] ?? 'global'),
        'modules'      => isset($args['modules']) ? maybe_serialize($args['modules']) : null, // <-- AJOUT
    ];

    $wpdb->insert($table, $data);

    // Créer le rôle si défini et pas encore présent sur le site
    if (!empty($args['role']) && !get_role($args['role'])) {
        add_role($args['role'], $args['role_name'] ?? ucfirst($args['role']), $args['capabilities'] ?? ['read' => true]);
    }
}

/**
 * Supprime un type de compte et son rôle associé s’il n’est plus utilisé.
 *
 * @param string $slug
 */
function admin_lab_unregister_account_type($slug) {
    global $wpdb;
    $table = admin_lab_getTable('account_types');

    $type = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE slug = %s", $slug), ARRAY_A);
    if (!$type) return;

    $wpdb->delete($table, ['slug' => $slug]);

    $role = $type['role'] ?? null;
    if ($role) {
        $others = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table` WHERE role = %s", $role));
        if ((int) $others === 0 && get_role($role)) {
            remove_role($role);
        }
    }
}

/**
 * Récupère la liste des rôles associés aux types de comptes définis dans l'admin.
 */
function admin_lab_get_account_type_roles() {
    $types = admin_lab_get_registered_account_types();
    $roles = [];

    foreach ($types as $type) {
        if (!empty($type['role'])) {
            $roles[] = $type['role'];
        }
    }

    return array_unique($roles);
}

/**
 * Retourne le rôle WordPress associé à un type de compte donné.
 *
 * Cette fonction utilise un cache statique en mémoire pour ne charger
 * les types de comptes enregistrés qu'une seule fois pendant l'exécution.
 *
 * @param string $account_type Slug du type de compte (ex: 'partenaire', 'premium').
 * @return string|null Le rôle WordPress associé, ou null si aucun rôle n’est défini.
 */
function admin_lab_account_type_to_role($account_type) {
    $account_types = admin_lab_get_registered_account_types();

    return $account_types[$account_type]['role'] ?? null;
}

/**
 * Trouve le type de compte correspondant à un rôle WordPress donné.
 *
 * Cette fonction parcourt les types de comptes enregistrés pour trouver
 * celui dont le rôle associé correspond exactement au rôle fourni.
 *
 * @param string $role Nom du rôle WordPress (ex: 'um_partenaire', 'subscriber').
 * @return string|null Le slug du type de compte correspondant, ou null si aucun ne correspond.
 */
function admin_lab_role_to_account_type($role) {
    $registered_types = admin_lab_get_registered_account_types();
    foreach ($registered_types as $slug => $data) {
        if (!empty($data['role']) && $data['role'] === $role) {
            return $slug;
        }
    }
    return null;
}

/**
 * Synchronise les modules autorisés d'un utilisateur en fonction de ses types de comptes actifs.
 *
 * @param int $user_id ID utilisateur.
 */
function admin_lab_sync_user_enabled_modules($user_id) {
    if (!$user_id) {
        return;
    }

    $modules = [];
    $user_account_types = get_user_meta($user_id, 'admin_lab_account_types', true);

    if (is_array($user_account_types)) {
        $registered_types = admin_lab_get_registered_account_types();

        foreach ($user_account_types as $type_slug) {
            if (isset($registered_types[$type_slug])) {
                $type_data = $registered_types[$type_slug];
                if (!empty($type_data['modules'])) {
                    $type_modules = maybe_unserialize($type_data['modules']);
                    if (is_array($type_modules)) {
                        $modules = array_merge($modules, $type_modules);
                    }
                }
            }
        }
    }

    $modules = array_unique($modules);
    update_user_meta($user_id, 'lab_enabled_modules', $modules);
}

/**
 * Récupère la liste des modules autorisés pour un type de compte donné.
 *
 * @param string $slug Slug du type de compte.
 * @return array Liste des modules autorisés (vide si aucun).
 */
function admin_lab_get_account_type_modules($slug) {
    global $wpdb;
    $table = admin_lab_getTable('account_types');

    $modules = $wpdb->get_var($wpdb->prepare(
        "SELECT modules FROM `$table` WHERE slug = %s",
        $slug
    ));

    if (!$modules) {
        return [];
    }

    $decoded = maybe_unserialize($modules);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Récupère la liste des modules accessibles pour un utilisateur donné.
 *
 * @param int $user_id ID de l'utilisateur.
 * @return array Liste des modules activés.
 */
function admin_lab_get_user_enabled_modules($user_id) {
    $types = get_user_meta($user_id, 'admin_lab_account_types', true);
    if (!is_array($types)) {
        $types = [];
    }

    $modules = [];
    foreach ($types as $type) {
        $type_modules = admin_lab_get_account_type_modules($type);
        if (!empty($type_modules)) {
            $modules = array_merge($modules, $type_modules);
        }
    }

    return array_unique($modules);
}

/**
 * Retourne la liste des domaines sur lesquels l'utilisateur est partenaire actif.
 */
function admin_lab_get_partner_sites($user_id): array {
    $all_sites = array_map('admin_lab_get_site_domain_from_prefix', admin_lab_get_all_sites_prefixes());

    $partner_all = get_user_meta($user_id, 'partner_all_sites', true);
    $partner_sites = get_user_meta($user_id, 'partner_sites', true);

    if ($partner_all) {
        return $all_sites;
    }

    if (is_array($partner_sites)) {
        return array_values($partner_sites);
    }

    return [];
}


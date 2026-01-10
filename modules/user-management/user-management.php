<?php
// File: modules/user-management/user-management.php


if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('user_management', $active_modules, true)) return;

// Chargement de l’interface d’administration
if (is_admin()) {
    require_once __DIR__ . '/admin/user-management-admin-ui.php';
}

// Chargement conditionnel de WP_List_Table si besoin
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Chargement de la classe de liste des types de comptes
$account_types_class_file = __DIR__ . '/admin/classes/class-account-types-list-table.php';
if (!class_exists('Admin_LAB_Account_Types_List_Table') && file_exists($account_types_class_file)) {
    require_once $account_types_class_file;
}

// Chargement des fichiers admin
require_once __DIR__ . '/admin/user-management-partner.php';
require_once __DIR__ . '/admin/user-management-roles-meta-box.php';

// Chargement des fonctions générales
require_once __DIR__ . '/functions/user-management-actions.php';
require_once __DIR__ . '/functions/user-management-batch.php';
require_once __DIR__ . '/functions/user-management-functions.php';
require_once __DIR__ . '/functions/user-management-um-custom-fields.php';
// Charger sync-account-type-roles.php avant account-types-functions.php pour que la fonction de synchronisation soit disponible
require_once __DIR__ . '/functions/sync-account-type-roles.php';
require_once __DIR__ . '/functions/account-types-functions.php';
require_once __DIR__ . '/functions/account-types-actions.php';
require_once __DIR__ . '/functions/openid-logout-hooks.php';
require_once __DIR__ . '/functions/openid-sync-account.php';
require_once __DIR__ . '/functions/user-management-um-filters.php';
<?php
// File: modules/game-servers/game-servers.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('game_servers', $active_modules, true)) return;

// Chargement des fonctions du module
require_once __DIR__ . '/functions/game-servers-helpers.php';
require_once __DIR__ . '/functions/game-servers-crud.php';
require_once __DIR__ . '/functions/game-servers-omgserv.php';
require_once __DIR__ . '/functions/game-servers-pages.php';
require_once __DIR__ . '/functions/game-servers-minecraft-auth.php';
require_once __DIR__ . '/functions/game-servers-minecraft-crud.php';

// Chargement de l'API REST
require_once __DIR__ . '/api/game-servers-rest-api.php';

// Chargement de l'interface d'administration
if (is_admin()) {
    include_once __DIR__ . '/admin/game-servers-admin-ui.php';
}

// Chargement des shortcodes
require_once __DIR__ . '/shortcodes/game-servers-shortcodes.php';

// Activation : création des tables (gérée par le système centralisé) et des pages
add_action('init', function () {
    $active = get_option('admin_lab_active_modules', []);
    if (in_array('game_servers', $active, true)) {
        admin_lab_game_servers_create_pages();
        
        // S'assurer que Microsoft est dans la configuration des providers Keycloak
        admin_lab_game_servers_ensure_microsoft_provider();
        
        do_action('admin_lab_game_servers_module_activated');
    }
});

/**
 * S'assure que Microsoft est présent dans la configuration des providers Keycloak
 */
function admin_lab_game_servers_ensure_microsoft_provider() {
    // Vérifier que le module keycloak_account_pages est actif
    if (!class_exists('Keycloak_Account_Pages_Keycloak')) {
        return;
    }
    
    $providers_json = get_option('admin_lab_kap_providers_json', '{}');
    
    // Décoder le JSON
    if (is_serialized($providers_json)) {
        $providers = @unserialize($providers_json);
    } else {
        $providers = json_decode($providers_json, true);
    }
    
    if (!is_array($providers)) {
        $providers = [];
    }
    
    // Vérifier si Microsoft est déjà présent
    if (!isset($providers['microsoft'])) {
        // Ajouter Microsoft à la configuration
        $providers['microsoft'] = [
            'label' => 'Microsoft',
            'kc_alias' => 'microsoft'
        ];
        
        // Sauvegarder la configuration mise à jour
        update_option('admin_lab_kap_providers_json', wp_json_encode($providers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

// Désactivation : suppression des pages
add_action('admin_lab_game_servers_module_desactivated', 'admin_lab_game_servers_delete_pages');

// Enregistrement des shortcodes
add_action('init', function() {
    if (function_exists('admin_lab_game_servers_register_shortcodes')) {
        admin_lab_game_servers_register_shortcodes();
    }
});

// Enqueue des styles et scripts
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'admin-lab-game-servers',
        ME5RINE_LAB_URL . 'assets/css/game-servers.css',
        [],
        ME5RINE_LAB_VERSION
    );
});


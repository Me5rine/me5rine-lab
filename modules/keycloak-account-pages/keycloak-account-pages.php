<?php
// File: modules/keycloak-account-pages/keycloak-account-pages.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère les modules actifs de manière robuste (gère les options sérialisées/JSON)
 */
function admin_lab_kap_get_active_modules(): array {
  $active = get_option('admin_lab_active_modules', []);

  // Option parfois sérialisée
  if (is_string($active)) {
    $maybe = @unserialize($active);
    if ($maybe !== false || $active === 'b:0;') {
      $active = $maybe;
    } else {
      // Option parfois en JSON
      $json = json_decode($active, true);
      if (is_array($json)) {
        $active = $json;
      }
    }
  }

  return is_array($active) ? $active : [];
}

/**
 * Vérifie si le module keycloak_account_pages est actif
 */
function admin_lab_kap_is_active(): bool {
  return in_array('keycloak_account_pages', admin_lab_kap_get_active_modules(), true);
}

// Vérifie que le module est activé
if (!admin_lab_kap_is_active()) return;

// Définitions de constantes
define('ADMIN_LAB_KAP_VERSION', '1.0.0');
define('ADMIN_LAB_KAP_DIR', __DIR__);
// Note: Les assets sont dans les assets globaux (ME5RINE_LAB_URL), pas besoin de ADMIN_LAB_KAP_URL

// Charger les classes
require_once ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-keycloak.php';
require_once ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-rest.php';
require_once ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-shortcodes.php';
require_once ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-admin.php';
require_once ADMIN_LAB_KAP_DIR . '/functions/keycloak-account-pages-ultimate-member.php';

// Activation du module : création des tables (gérée par le système de DB du plugin)
add_action('init', function () {
    if (!admin_lab_kap_is_active()) return;

    // Options par défaut si elles n'existent pas
    $defaults = [
        'admin_lab_kap_kc_base_url'        => 'https://KEYCLOAK_DOMAIN',
        'admin_lab_kap_kc_realm'           => 'YOUR_REALM',
        'admin_lab_kap_kc_client_id'       => 'wordpress',
        'admin_lab_kap_kc_client_secret'   => '',
        'admin_lab_kap_kc_admin_client_id' => 'admin-cli',
        'admin_lab_kap_kc_admin_secret'    => '',
        'admin_lab_kap_kc_redirect_uri'    => home_url('/wp-json/admin-lab-kap/v1/keycloak/callback'),
        'admin_lab_kap_providers_json'     => wp_json_encode([
            'google'   => ['label' => 'Google',   'kc_alias' => 'google'],
            'discord'  => ['label' => 'Discord',  'kc_alias' => 'discord'],
            'facebook' => ['label' => 'Facebook', 'kc_alias' => 'facebook'],
            'twitch'   => ['label' => 'Twitch',   'kc_alias' => 'twitch'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'admin_lab_kap_prevent_last_disconnect' => 1,
    ];
    foreach ($defaults as $k => $v) {
        if (get_option($k, null) === null) {
            add_option($k, $v);
        }
    }

    do_action('admin_lab_keycloak_account_pages_module_activated');
});

// Enregistrement des assets (dans les assets globaux du plugin)
add_action('init', function () {
    wp_register_script('admin-lab-kap-js', ME5RINE_LAB_URL . 'assets/js/keycloak-account-pages.js', ['wp-api-fetch'], ADMIN_LAB_KAP_VERSION, true);
    wp_register_style('admin-lab-kap-css', ME5RINE_LAB_URL . 'assets/css/keycloak-account-pages.css', [], ADMIN_LAB_KAP_VERSION);
});

// Initialisation des classes REST immédiatement (pour éviter les problèmes de timing avec rest_api_init)
if (admin_lab_kap_is_active()) {
    Keycloak_Account_Pages_Rest::init();
}

// Autres initialisations sur plugins_loaded
add_action('plugins_loaded', function () {
    if (!admin_lab_kap_is_active()) return;
    
    Keycloak_Account_Pages_Admin::init();
    
    // Intégration Ultimate Member
    if (class_exists('UM')) {
        Keycloak_Account_Pages_Ultimate_Member::init();
    }
});

// Enregistrement des shortcodes sur init (comme les autres modules)
add_action('init', function () {
    if (!admin_lab_kap_is_active()) return;
    Keycloak_Account_Pages_Shortcodes::init();
}, 10);

// Désactivation du module
add_action('admin_lab_keycloak_account_pages_module_desactivated', function () {
    // Actions de nettoyage si nécessaire
});

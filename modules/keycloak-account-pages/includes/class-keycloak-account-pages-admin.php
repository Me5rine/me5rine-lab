<?php
// File: modules/keycloak-account-pages/includes/class-keycloak-account-pages-admin.php

if (!defined('ABSPATH')) exit;

class Keycloak_Account_Pages_Admin {

  public static function init(): void {
    add_action('admin_init', [__CLASS__, 'settings']);
    add_action('admin_menu', [__CLASS__, 'add_settings_tab']);
  }

  public static function settings(): void {
    register_setting('admin_lab_settings', 'admin_lab_kap_kc_base_url');
    register_setting('admin_lab_settings', 'admin_lab_kap_kc_realm');
    register_setting('admin_lab_settings', 'admin_lab_kap_kc_client_id');
    register_setting('admin_lab_settings', 'admin_lab_kap_kc_client_secret');
    register_setting('admin_lab_settings', 'admin_lab_kap_kc_admin_client_id');
    register_setting('admin_lab_settings', 'admin_lab_kap_kc_admin_secret');
    register_setting('admin_lab_settings', 'admin_lab_kap_kc_redirect_uri');
    register_setting('admin_lab_settings', 'admin_lab_kap_providers_json');
    register_setting('admin_lab_settings', 'admin_lab_kap_prevent_last_disconnect', [
      'type' => 'integer',
      'default' => 1,
    ]);
  }

  public static function add_settings_tab(): void {
    // L'onglet sera ajouté via les hooks du système de settings
  }
}


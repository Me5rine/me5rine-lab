<?php
// File: modules/keycloak-account-pages/functions/keycloak-account-pages-ultimate-member.php

if (!defined('ABSPATH')) exit;

class Keycloak_Account_Pages_Ultimate_Member {

  public static function init(): void {
    // Ajouter des onglets dans le profil Ultimate Member
    add_filter('um_profile_tabs', [__CLASS__, 'add_profile_tabs'], 1000);
    add_action('um_profile_content_connexions_default', [__CLASS__, 'render_connections_tab']);
    add_action('um_profile_content_compte_default', [__CLASS__, 'render_account_settings_tab']);
    
    // Fallback : si Ultimate Member traite le contenu comme du texte, traiter les shortcodes
    add_filter('um_profile_tab_content_connexions', [__CLASS__, 'process_shortcode_in_content'], 10, 2);
    add_filter('um_profile_tab_content_compte', [__CLASS__, 'process_shortcode_in_content'], 10, 2);
    
    // Synchroniser les mises à jour de profil vers Keycloak
    add_action('profile_update', [__CLASS__, 'sync_profile_to_keycloak'], 10, 2);
    add_action('um_after_user_account_updated', [__CLASS__, 'sync_um_profile_to_keycloak'], 10, 2);
  }
  
  /**
   * Traite les shortcodes dans le contenu des onglets (si Ultimate Member les passe comme texte)
   */
  public static function process_shortcode_in_content($content, $tab) {
    // Si le contenu contient notre shortcode en texte brut, le traiter
    if (is_string($content)) {
      if (strpos($content, '[admin_lab_kap_connections]') !== false) {
        $content = str_replace('[admin_lab_kap_connections]', admin_lab_kap_render_connections(), $content);
      }
      if (strpos($content, '[admin_lab_kap_account_settings]') !== false) {
        $content = str_replace('[admin_lab_kap_account_settings]', admin_lab_kap_render_account_settings(), $content);
      }
      // Traiter aussi via do_shortcode au cas où
      $content = do_shortcode($content);
    }
    return $content;
  }

  /**
   * Ajoute les onglets dans le profil Ultimate Member
   */
  public static function add_profile_tabs($tabs) {
    if (!is_array($tabs)) {
      $tabs = [];
    }

    $tabs['connexions'] = [
      'name' => 'Connexions',
      'icon' => 'um-faicon-link',
    ];

    $tabs['compte'] = [
      'name' => 'Mon compte',
      'icon' => 'um-faicon-cog',
    ];

    return $tabs;
  }

  /**
   * Affiche le contenu de l'onglet Connexions
   * Appelle directement la fonction de rendu (plus fiable que do_shortcode dans Ultimate Member)
   */
  public static function render_connections_tab($args) {
    // Rediriger vers l'onglet par défaut si l'utilisateur n'est pas connecté
    if (function_exists('admin_lab_redirect_to_default_profile_tab')) {
      admin_lab_redirect_to_default_profile_tab();
    }
    
    // Protection supplémentaire si accès forcé
    if (!is_user_logged_in()) {
      return;
    }
    
    // Debug : vérifier que la fonction est appelée
    // error_log('KAP: render_connections_tab called');
    
    // S'assurer que le fichier shortcodes est chargé
    if (!function_exists('admin_lab_kap_render_connections')) {
      // Le fichier devrait être chargé, mais au cas où...
      if (defined('ADMIN_LAB_KAP_DIR') && file_exists(ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-shortcodes.php')) {
        require_once ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-shortcodes.php';
      }
    }
    
    // Appelle directement la fonction de rendu (plus fiable)
    if (function_exists('admin_lab_kap_render_connections')) {
      echo admin_lab_kap_render_connections();
      return;
    }
    
    if (class_exists('Keycloak_Account_Pages_Shortcodes')) {
      // Fallback vers la méthode de classe
      echo Keycloak_Account_Pages_Shortcodes::connections();
      return;
    }
    
    // Si rien ne fonctionne, afficher un message d'erreur
    echo '<p>Erreur : Le module Keycloak Account Pages n\'est pas correctement chargé. Fonction admin_lab_kap_render_connections non trouvée.</p>';
  }

  /**
   * Affiche le contenu de l'onglet Mon compte
   * Appelle directement la fonction de rendu (plus fiable que do_shortcode dans Ultimate Member)
   */
  public static function render_account_settings_tab($args) {
    // Vérifier si on a un message de changement d'email (même si déconnecté)
    $kap_status = isset($_GET['kap']) ? sanitize_text_field($_GET['kap']) : '';
    
    // S'assurer que le fichier shortcodes est chargé
    if (!function_exists('admin_lab_kap_render_account_settings')) {
      // Le fichier devrait être chargé, mais au cas où...
      if (defined('ADMIN_LAB_KAP_DIR') && file_exists(ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-shortcodes.php')) {
        require_once ADMIN_LAB_KAP_DIR . '/includes/class-keycloak-account-pages-shortcodes.php';
      }
    }
    
    // Si on a un message de changement d'email, permettre l'affichage même si déconnecté
    if ($kap_status === 'email_changed') {
      if (function_exists('admin_lab_kap_render_account_settings')) {
        echo admin_lab_kap_render_account_settings();
        return;
      }
    }
    
    // Pour les autres cas, vérifier la connexion
    if (!is_user_logged_in()) {
      // Rediriger vers l'onglet par défaut si l'utilisateur n'est pas connecté
      if (function_exists('admin_lab_redirect_to_default_profile_tab')) {
        admin_lab_redirect_to_default_profile_tab();
      }
      return;
    }
    
    // Debug : vérifier que la fonction est appelée
    // error_log('KAP: render_account_settings_tab called');
    
    // Appelle directement la fonction de rendu (plus fiable)
    if (function_exists('admin_lab_kap_render_account_settings')) {
      echo admin_lab_kap_render_account_settings();
      return;
    }
    
    if (class_exists('Keycloak_Account_Pages_Shortcodes')) {
      // Fallback vers la méthode de classe
      echo Keycloak_Account_Pages_Shortcodes::account_settings();
      return;
    }
    
    // Si rien ne fonctionne, afficher un message d'erreur
    echo '<p>Erreur : Le module Keycloak Account Pages n\'est pas correctement chargé. Fonction admin_lab_kap_render_account_settings non trouvée.</p>';
  }

  /**
   * Synchronise les mises à jour de profil WordPress vers Keycloak
   * Hook: profile_update (WordPress standard)
   */
  public static function sync_profile_to_keycloak($user_id, $old_user_data = null): void {
    if (!admin_lab_kap_is_active()) {
      return;
    }

    $user = get_userdata($user_id);
    if (!$user) {
      return;
    }

    // Récupérer le kc_user_id
    $kc_user_id = Keycloak_Account_Pages_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if (!$kc_user_id) {
      // Pas d'utilisateur Keycloak lié, on ne fait rien
      return;
    }

    // Préparer les données à mettre à jour
    $kc_payload = [];
    $has_changes = false;

    // Nom de famille (last_name)
    $last_name = get_user_meta($user_id, 'last_name', true);
    if ($last_name !== false && $last_name !== '') {
      $kc_payload['lastName'] = sanitize_text_field($last_name);
      $has_changes = true;
    }

    // Prénom (first_name)
    $first_name = get_user_meta($user_id, 'first_name', true);
    if ($first_name !== false && $first_name !== '') {
      $kc_payload['firstName'] = sanitize_text_field($first_name);
      $has_changes = true;
    }

    // Nom d'affichage (display_name)
    if (!empty($user->display_name)) {
      $kc_payload['displayName'] = sanitize_text_field($user->display_name);
      $has_changes = true;
    }

    // Username (user_login) - on synchronise si l'utilisateur Keycloak n'a pas de provider externe
    // Pour les comptes avec provider externe (Google, Discord, etc.), le username est géré par le provider
    try {
      $kc_user = Keycloak_Account_Pages_Keycloak::get_user($kc_user_id);
      if (is_array($kc_user)) {
        // Vérifier si l'utilisateur a des identités fédérées (providers externes)
        $has_federated = !empty($kc_user['federatedIdentities']) && is_array($kc_user['federatedIdentities']) && count($kc_user['federatedIdentities']) > 0;
        
        // Si pas de provider externe, on peut synchroniser le username
        // Sinon, on ne touche pas au username car il est géré par le provider
        if (!$has_federated && !empty($user->user_login)) {
          // Vérifier si le username a changé
          if (empty($kc_user['username']) || $kc_user['username'] !== $user->user_login) {
            $kc_payload['username'] = sanitize_user($user->user_login, true);
            $has_changes = true;
          }
        }
      }
    } catch (Exception $e) {
      // En cas d'erreur, on continue sans mettre à jour le username
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log(sprintf('[KAP] Erreur lors de la vérification du username Keycloak: %s', $e->getMessage()));
      }
    }

    // Mettre à jour Keycloak si on a des changements
    if ($has_changes) {
      try {
        Keycloak_Account_Pages_Keycloak::update_user_safe($kc_user_id, $kc_payload);
        
        // Logger pour debug si activé
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log(sprintf('[KAP] Profil WordPress synchronisé vers Keycloak pour user_id=%d, kc_user_id=%s', $user_id, $kc_user_id));
        }
      } catch (Exception $e) {
        // Logger l'erreur mais ne pas bloquer la mise à jour WordPress
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log(sprintf('[KAP] Erreur lors de la synchronisation vers Keycloak: %s', $e->getMessage()));
        }
      }
    }
  }

  /**
   * Synchronise les mises à jour de profil Ultimate Member vers Keycloak
   * Hook: um_after_user_account_updated
   */
  public static function sync_um_profile_to_keycloak($user_id, $args): void {
    if (!admin_lab_kap_is_active()) {
      return;
    }

    // Ultimate Member appelle aussi profile_update, donc on se contente d'appeler la même fonction
    // Mais on peut aussi traiter les champs spécifiques UM ici si nécessaire
    self::sync_profile_to_keycloak($user_id);
  }
}


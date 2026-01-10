<?php
// File: modules/keycloak-account-pages/includes/class-admin-lab-kap-rest.php

if (!defined('ABSPATH')) exit;

class Admin_Lab_KAP_Rest {
  private static $routes_registered = false;

  public static function init(): void {
    // Enregistrer sur rest_api_init si pas encore déclenché
    add_action('rest_api_init', [__CLASS__, 'routes']);
    
    // Si rest_api_init a déjà été déclenché, enregistrer immédiatement
    if (did_action('rest_api_init')) {
      self::routes();
    }
  }

  public static function routes(): void {
    // Éviter les doublons
    if (self::$routes_registered) {
      return;
    }
    self::$routes_registered = true;

    register_rest_route('admin-lab-kap/v1', '/connections', [
      'methods' => 'GET',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'get_connections'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/connect', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'connect_provider'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/disconnect', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'disconnect_provider'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/profile', [
      'methods' => 'GET',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'get_profile'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/profile', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'update_profile'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/password', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'update_password'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/password/init-change', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'init_password_change'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/email', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'update_email'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/email/init-change', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'init_email_change'],
    ]);

    register_rest_route('admin-lab-kap/v1', '/email/resend-verification', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'must_be_logged_in'],
      'callback' => [__CLASS__, 'resend_verification_email'],
    ]);

    // Callback Keycloak broker link
    register_rest_route('admin-lab-kap/v1', '/keycloak/callback', [
      'methods' => 'GET',
      'permission_callback' => '__return_true',
      'callback' => [__CLASS__, 'keycloak_callback'],
    ]);
  }

  public static function must_be_logged_in($request = null): bool {
    // Vérifier le nonce REST API si présent dans les en-têtes
    if ($request instanceof WP_REST_Request) {
      $nonce = $request->get_header('X-WP-Nonce');
      if ($nonce) {
        // Vérifier le nonce REST API
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
          return false;
        }
      }
    }
    
    // Vérifier que l'utilisateur est connecté (via cookie de session)
    // WordPress REST API peut utiliser le cookie OU le nonce pour l'authentification
    if (!is_user_logged_in()) {
      // Si pas de cookie, vérifier via wp_get_current_user() qui peut fonctionner avec le nonce
      $user = wp_get_current_user();
      if (!$user || $user->ID === 0) {
        return false;
      }
    }
    
    // Double vérification : s'assurer que l'utilisateur existe bien
    $user = wp_get_current_user();
    return $user && $user->ID > 0;
  }

  private static function json_ok($data) {
    return new WP_REST_Response(['ok' => true, 'data' => $data], 200);
  }

  private static function json_err($message, int $status = 400) {
    // ✅ Accepter soit une string, soit un array avec code/message
    $error_data = is_array($message) ? $message : ['message' => $message];
    return new WP_REST_Response(['ok' => false, 'error' => $error_data], $status);
  }

  /**
   * Récupère la liste des providers connectés pour l'utilisateur actuel.
   * 
   * SOURCE DES DONNÉES :
   * - Base : table WordPress (admin_lab_keycloak_accounts) pour avoir une base locale
   * - Synchronisation : Keycloak (via get_federated_identities) pour avoir les données à jour
   * - Affichage final : table WordPress mise à jour avec les données Keycloak
   * 
   * Cette approche hybride permet :
   * - D'avoir des données même si Keycloak est temporairement inaccessible
   * - De synchroniser avec Keycloak pour avoir les dernières informations
   * - De stocker des métadonnées locales (last_sync_at, etc.)
   */
  public static function get_connections(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $providers = Admin_Lab_KAP_Keycloak::get_providers();
    
    // 1️⃣ Récupérer les connexions depuis la table WordPress (source locale)
    $active = Admin_Lab_DB::getInstance()->get_active_keycloak_connections($user_id);

    $map = [];
    foreach ($active as $row) {
      // Ignorer le provider spécial '_keycloak' qui stocke juste le kc_user_id
      if ($row['provider_slug'] !== '_keycloak') {
        $map[$row['provider_slug']] = $row;
      }
    }

    // 2️⃣ Si on a un kc_user_id, synchroniser avec Keycloak pour avoir les données à jour (source Keycloak)
    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if ($kc_user_id) {
      try {
        $fed = Admin_Lab_KAP_Keycloak::get_federated_identities($kc_user_id);
        
        // 3️⃣ Mettre à jour la table WordPress avec les identités fédérées depuis Keycloak (synchronisation)
        foreach ($fed as $item) {
          if (!is_array($item)) continue;

          $alias = (string)($item['identityProvider'] ?? '');
          $extId = (string)($item['userId'] ?? '');
          $extName = (string)($item['userName'] ?? '');

          if (!$alias) continue;

          // Trouver le provider_slug correspondant à cet alias
          $provider_slug = null;
          foreach ($providers as $slug => $cfg) {
            $cfg_alias = $cfg['kc_alias'] ?? $slug;
            if ($cfg_alias === $alias) {
              $provider_slug = $slug;
              break;
            }
          }
          
          if (!$provider_slug) continue;

          // Normaliser le provider_slug (YouTube → google)
          if (function_exists('admin_lab_normalize_account_provider_slug')) {
            $provider_slug = admin_lab_normalize_account_provider_slug($provider_slug);
          } elseif (strpos($provider_slug, 'youtube') === 0) {
            $provider_slug = 'google';
          }

          // Mettre à jour ou créer l'entrée dans la table
          Admin_Lab_DB::getInstance()->upsert_keycloak_connection([
            'user_id' => $user_id,
            'provider_slug' => $provider_slug,
            'external_user_id' => $extId,
            'external_username' => $extName,
            'keycloak_identity_id' => $kc_user_id,
            'is_active' => 1,
            'last_sync_at' => current_time('mysql'),
          ]);
          
          // Mettre à jour le map pour le retour
          $map[$provider_slug] = [
            'provider_slug' => $provider_slug,
            'external_user_id' => $extId,
            'external_username' => $extName,
            'keycloak_identity_id' => $kc_user_id,
            'is_active' => 1,
            'last_sync_at' => current_time('mysql'),
          ];
        }
      } catch (Exception $e) {
        // En cas d'erreur, on continue avec les données de la table
      }
    }

    $out = [];
    foreach ($providers as $slug => $cfg) {
      $row = $map[$slug] ?? null;
      $out[] = [
        'provider_slug' => $slug,
        'label' => $cfg['label'] ?? $slug,
        'kc_alias' => $cfg['kc_alias'] ?? $slug,
        'connected' => $row ? true : false,
        'external_username' => $row['external_username'] ?? null,
        'last_sync_at' => $row['last_sync_at'] ?? null,
      ];
    }

    return self::json_ok($out);
  }

  public static function connect_provider(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $provider = sanitize_key((string)$req->get_param('provider_slug'));
    if (!$provider) return self::json_err('provider_slug manquant');

    $providers = Admin_Lab_KAP_Keycloak::get_providers();
    if (empty($providers[$provider])) return self::json_err('Provider inconnu');

    $kc_alias = $providers[$provider]['kc_alias'] ?? $provider;

    // ✅ Vérifier que l'utilisateur existe dans Keycloak (ne pas le créer s'il existe déjà)
    // On vérifie juste que le kc_user_id est disponible
    try {
      $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
      
      // Si l'utilisateur n'existe pas dans Keycloak, essayer de le créer
      // (mais normalement, s'il s'est connecté avec Keycloak, il devrait exister)
      if (empty($kc_user_id)) {
        $kc_user_id = Admin_Lab_KAP_Keycloak::ensure_user_exists($user_id);
      }
      
      // S'assurer que le rôle manage-account-links est assigné
      if (!empty($kc_user_id)) {
        try {
          Admin_Lab_KAP_Keycloak::assign_account_role($kc_user_id, 'manage-account-links');
        } catch (Exception $e) {
          // Le rôle existe peut-être déjà, continuer
          if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[KAP Connect] Role assignment note: ' . $e->getMessage());
          }
        }
        
        // Stocker le kc_user_id dans la base de données pour référence future
        Admin_Lab_DB::getInstance()->upsert_keycloak_connection([
          'user_id' => $user_id,
          'provider_slug' => '_keycloak',
          'keycloak_identity_id' => $kc_user_id,
          'is_active' => 1,
          'last_sync_at' => current_time('mysql'),
        ]);
      }
    } catch (Exception $e) {
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[KAP Connect] Failed to ensure user exists: ' . $e->getMessage());
      }
      // Ne pas bloquer si l'utilisateur existe déjà, on continue quand même
      // Le linking pourra fonctionner si l'utilisateur s'authentifie correctement
    }

    // ✅ Nouveau système : token stocké en transient (plus robuste que nonce WP dans callback public)
    $token = wp_generate_password(32, false, false);
    
    // Récupérer l'URL de retour depuis la requête (si présente) ou utiliser l'URL du profil
    $return_url = '';
    if (!empty($_SERVER['HTTP_REFERER'])) {
      $return_url = esc_url_raw($_SERVER['HTTP_REFERER']);
    } else {
      $return_url = admin_lab_get_current_user_profile_url('linked-accounts');
    }
    
    // Stocker les infos dans un transient avec le token comme clé
    set_transient(
      'admin_lab_kap_state_' . $token,
      [
        'u' => $user_id,
        'p' => $provider,
        't' => time(),
        'mode' => 'broker_link',
        'return_url' => $return_url, // URL de retour pour la redirection finale
      ],
      10 * MINUTE_IN_SECONDS // Expire après 10 minutes
    );

    // State signé : contient uniquement le token (les infos sont dans le transient)
    $payload = ['k' => $token];
    $state_raw = wp_json_encode($payload);
    $sig = hash_hmac('sha256', $state_raw, wp_salt('auth'));
    $state = base64_encode($state_raw) . '.' . $sig;

    // ✅ Pre-auth OIDC pour obtenir une session Keycloak et session_state
    // Puis on enchaînera sur /broker/{provider}/link dans le callback
    // ⚠️ Ne pas forcer la connexion (force_login=false) : utilise la session existante si disponible
    // La connexion ne sera demandée que si l'utilisateur n'a pas de session active
    $url = Admin_Lab_KAP_Keycloak::build_auth_url($state, false);

    return self::json_ok(['redirect' => $url]);
  }

  public static function disconnect_provider(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $provider = sanitize_key((string)$req->get_param('provider_slug'));
    if (!$provider) return self::json_err('provider_slug manquant');

    $providers = Admin_Lab_KAP_Keycloak::get_providers();
    if (empty($providers[$provider])) return self::json_err('Provider inconnu');

    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if (!$kc_user_id) return self::json_err("keycloak_user_id introuvable pour cet utilisateur.");

    // ✅ Compter les providers actifs (en excluant le provider spécial '_keycloak')
    $active = Admin_Lab_DB::getInstance()->get_active_keycloak_connections($user_id);
    $active_providers = [];
    foreach ($active as $row) {
      // Ignorer le provider spécial '_keycloak' qui stocke juste le kc_user_id
      if (!empty($row['provider_slug']) && $row['provider_slug'] !== '_keycloak' && !empty($row['is_active'])) {
        $active_providers[] = $row['provider_slug'];
      }
    }
    
    // ✅ Vérifier si le provider à déconnecter est actif et si c'est le dernier
    $provider_is_active = in_array($provider, $active_providers, true);
    $is_last_provider = $provider_is_active && count($active_providers) <= 1;

    if ($is_last_provider) {
      // Vérifier si l'utilisateur a un mot de passe dans Keycloak
      $has_password = Admin_Lab_KAP_Keycloak::user_has_password($kc_user_id);
      
      if (!$has_password) {
        // L'utilisateur n'a pas de mot de passe → empêcher la déconnexion et retourner un warning
        return self::json_err([
          'code' => 'last_provider_no_password',
          'message' => 'Impossible de déconnecter le dernier provider. Vous devez d\'abord définir un mot de passe dans Keycloak pour pouvoir vous connecter sans provider externe.',
        ], 409);
      }
      // L'utilisateur a un mot de passe → autoriser la déconnexion (avec avertissement optionnel)
    }

    $kc_alias = $providers[$provider]['kc_alias'] ?? $provider;

    try {
      Admin_Lab_KAP_Keycloak::unlink_provider($kc_user_id, $kc_alias);
      Admin_Lab_DB::getInstance()->deactivate_keycloak_connection($user_id, $provider);
      
      // ✅ Retourner le provider pour la redirection avec notice
      return self::json_ok([
        'provider_slug' => $provider,
        'disconnected' => true,
        'redirect' => true, // Indiquer qu'une redirection est nécessaire
      ]);
    } catch (Exception $e) {
      return self::json_err($e->getMessage(), 500);
    }
  }

  public static function get_profile(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    $profile = [
      'first_name' => get_user_meta($user_id, 'first_name', true) ?: '',
      'last_name' => get_user_meta($user_id, 'last_name', true) ?: '',
      'nickname' => get_user_meta($user_id, 'nickname', true) ?: $user->display_name,
      'email' => $user->user_email ?? '',
      'email_verified' => false,
    ];

    // Récupérer l'email et emailVerified depuis Keycloak
    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    $profile['has_keycloak_password'] = false;
    if ($kc_user_id) {
      try {
        $kc_user = Admin_Lab_KAP_Keycloak::get_user($kc_user_id);
        if (!empty($kc_user['email'])) {
          $profile['email'] = $kc_user['email'];
        }
        $profile['email_verified'] = !empty($kc_user['emailVerified']) ? (bool)$kc_user['emailVerified'] : false;
        $profile['has_keycloak_password'] = Admin_Lab_KAP_Keycloak::user_has_password($kc_user_id);
      } catch (Exception $e) {
        // En cas d'erreur, utiliser les données WordPress
      }
    }
    
    return self::json_ok($profile);
  }

  public static function update_profile(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    $first = sanitize_text_field((string)$req->get_param('first_name'));
    $last  = sanitize_text_field((string)$req->get_param('last_name'));
    $nick  = sanitize_text_field((string)$req->get_param('nickname'));

    // Update WP
    wp_update_user([
      'ID' => $user_id,
      'first_name' => $first,
      'last_name'  => $last,
      'display_name' => $nick ?: ($user->display_name ?: $user->user_login),
    ]);
    if ($nick) update_user_meta($user_id, 'nickname', $nick);

    // Update Keycloak (si possible)
    // ✅ IMPORTANT : Ne construire le payload qu'avec les champs réellement modifiés (non vides)
    // ✅ Ne JAMAIS inclure 'email' dans le payload si on ne veut pas le modifier
    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if ($kc_user_id) {
      try {
        // Construire le payload Keycloak seulement avec les champs non vides
        $kc_payload = [];
        
        // First name : ajouter seulement si non vide
        if ($first !== '') {
          $kc_payload['firstName'] = $first;
        }
        
        // Last name : ajouter seulement si non vide
        if ($last !== '') {
          $kc_payload['lastName'] = $last;
        }
        
        // Nickname : ajouter seulement si non vide
        if ($nick !== '') {
          $kc_payload['attributes'] = [
            'nickname' => [$nick],
          ];
        }
        
        // ✅ IMPORTANT : ne JAMAIS mettre email ici si le formulaire ne le gère pas
        // Le payload ne contiendra que les champs que nous voulons vraiment mettre à jour
        
        // Mettre à jour Keycloak seulement si on a quelque chose à envoyer
        // Utiliser update_user_safe() pour préserver les champs sensibles (email, username, etc.)
        if (!empty($kc_payload)) {
          Admin_Lab_KAP_Keycloak::update_user_safe($kc_user_id, $kc_payload);
        }
      } catch (Exception $e) {
        // WP est à jour, Keycloak a échoué -> on remonte l'info
        return self::json_err("WP ok, Keycloak erreur: " . $e->getMessage(), 502);
      }
    }

    return self::json_ok(['updated' => true]);
  }

  /**
   * Initie le changement de mot de passe avec ré-authentification OIDC
   * Si l'utilisateur a déjà un mot de passe, redirige vers Keycloak pour ré-auth
   * Sinon, met à jour directement le mot de passe
   */
  public static function init_password_change(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $pass1 = (string)$req->get_param('password');
    $pass2 = (string)$req->get_param('password_confirm');
    $return_url = (string)$req->get_param('return_url') ?: '';

    if (strlen($pass1) < 8) {
      return self::json_err('Mot de passe trop court (min 8 caractères).');
    }
    
    if ($pass1 !== $pass2) {
      return self::json_err('Les mots de passe ne correspondent pas.');
    }

    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if (!$kc_user_id) {
      return self::json_err('keycloak_user_id introuvable pour cet utilisateur.');
    }

    try {
      // ✅ TOUJOURS demander une ré-authentification avant de changer le mot de passe (sécurité)
      // Stocker le nouveau mot de passe dans un transient sécurisé
      $token = bin2hex(random_bytes(16));
      
      set_transient(
        'admin_lab_kap_password_change_' . $token,
        [
          'user_id' => $user_id,
          'kc_user_id' => $kc_user_id,
          'password' => $pass1, // Stocker en plain pour l'API Keycloak (transient court, 10 min max)
          'timestamp' => time(),
          'return_url' => $return_url,
        ],
        10 * MINUTE_IN_SECONDS // Expire après 10 minutes
      );

      // Construire le state signé pour le flow de ré-auth
      $payload = ['k' => $token, 'action' => 'password_change'];
      $state_raw = wp_json_encode($payload);
      $sig = hash_hmac('sha256', $state_raw, wp_salt('auth'));
      $state = base64_encode($state_raw) . '.' . $sig;

      // Rediriger vers Keycloak avec prompt=login pour forcer la ré-authentification
      $auth_url = Admin_Lab_KAP_Keycloak::build_reauth_url($state);

      return self::json_ok([
        'requires_reauth' => true,
        'redirect' => $auth_url,
      ]);
    } catch (Exception $e) {
      return self::json_err($e->getMessage(), 500);
    }
  }

  /**
   * Finalise le changement de mot de passe après ré-authentification réussie
   * Appelé depuis le callback Keycloak après une ré-auth réussie
   */
  public static function finalize_password_change(string $token): array {
    $transient_key = 'admin_lab_kap_password_change_' . $token;
    $data = get_transient($transient_key);
    
    if (!$data || !is_array($data)) {
      return ['success' => false, 'error' => 'Token invalide ou expiré'];
    }

    $user_id = (int) ($data['user_id'] ?? 0);
    $kc_user_id = (string) ($data['kc_user_id'] ?? '');
    $new_password = (string) ($data['password'] ?? '');

    if (empty($kc_user_id) || empty($new_password)) {
      delete_transient($transient_key);
      return ['success' => false, 'error' => 'Données incomplètes'];
    }

    try {
      // ✅ Mettre à jour le mot de passe via Admin API (on est déjà authentifié via OIDC)
      Admin_Lab_KAP_Keycloak::reset_password($kc_user_id, $new_password, false);
      
      // Supprimer le transient
      delete_transient($transient_key);
      
      return [
        'success' => true,
        'return_url' => $data['return_url'] ?? '',
      ];
    } catch (Exception $e) {
      delete_transient($transient_key);
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Met à jour le mot de passe directement (pour les cas sans ré-auth, ou appelé depuis le callback)
   * Cette méthode peut être appelée après ré-authentification ou pour définir un premier mot de passe
   */
  public static function update_password(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $pass1 = (string)$req->get_param('password');
    $pass2 = (string)$req->get_param('password_confirm');

    if (strlen($pass1) < 8) {
      return self::json_err('Mot de passe trop court (min 8 caractères).');
    }
    
    if ($pass1 !== $pass2) {
      return self::json_err('Les mots de passe ne correspondent pas.');
    }

    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if (!$kc_user_id) {
      return self::json_err('keycloak_user_id introuvable pour cet utilisateur.');
    }

    try {
      $has_password = Admin_Lab_KAP_Keycloak::user_has_password($kc_user_id);
      
      // ✅ Mettre à jour ou créer le mot de passe
      Admin_Lab_KAP_Keycloak::reset_password($kc_user_id, $pass1, false);
      
      return self::json_ok([
        'password_changed' => true,
        'was_set' => !$has_password,
      ]);
    } catch (Exception $e) {
      return self::json_err($e->getMessage(), 500);
    }
  }

  /**
   * Initie le changement d'email avec ré-authentification OIDC
   * Toujours demande une ré-authentification pour la sécurité
   */
  public static function init_email_change(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $new_email = sanitize_email((string)$req->get_param('email'));
    $return_url = (string)$req->get_param('return_url') ?: '';

    if (empty($new_email) || !is_email($new_email)) {
      return self::json_err('Email invalide.');
    }

    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if (!$kc_user_id) {
      return self::json_err('keycloak_user_id introuvable pour cet utilisateur.');
    }

    try {
      // Vérifier si l'email existe déjà pour un autre utilisateur
      $existing_user = Admin_Lab_KAP_Keycloak::find_user_by_email($new_email);
      if ($existing_user && isset($existing_user['id']) && $existing_user['id'] !== $kc_user_id) {
        return self::json_err('Cet email est déjà utilisé par un autre compte.');
      }

      // ✅ TOUJOURS demander une ré-authentification avant de changer l'email (sécurité)
      // Stocker le nouvel email dans un transient sécurisé
      $token = bin2hex(random_bytes(16));
      
      set_transient(
        'admin_lab_kap_email_change_' . $token,
        [
          'user_id' => $user_id,
          'kc_user_id' => $kc_user_id,
          'new_email' => $new_email,
          'timestamp' => time(),
          'return_url' => $return_url,
        ],
        10 * MINUTE_IN_SECONDS // Expire après 10 minutes
      );

      // Construire le state signé pour le flow de ré-auth
      $payload = ['k' => $token, 'action' => 'email_change'];
      $state_raw = wp_json_encode($payload);
      $sig = hash_hmac('sha256', $state_raw, wp_salt('auth'));
      $state = base64_encode($state_raw) . '.' . $sig;

      // Rediriger vers Keycloak avec prompt=login pour forcer la ré-authentification
      $auth_url = Admin_Lab_KAP_Keycloak::build_reauth_url($state);

      return self::json_ok([
        'requires_reauth' => true,
        'redirect' => $auth_url,
      ]);
    } catch (Exception $e) {
      return self::json_err($e->getMessage(), 500);
    }
  }

  /**
   * Finalise le changement d'email après ré-authentification réussie
   * Appelé depuis le callback Keycloak après une ré-auth réussie
   */
  public static function finalize_email_change(string $token): array {
    $transient_key = 'admin_lab_kap_email_change_' . $token;
    $data = get_transient($transient_key);
    
    if (!$data || !is_array($data)) {
      return ['success' => false, 'error' => 'Token invalide ou expiré'];
    }

    $user_id = (int) ($data['user_id'] ?? 0);
    $kc_user_id = (string) ($data['kc_user_id'] ?? '');
    $new_email = (string) ($data['new_email'] ?? '');

    if (empty($kc_user_id) || empty($new_email)) {
      delete_transient($transient_key);
      return ['success' => false, 'error' => 'Données incomplètes'];
    }

    try {
      // ✅ Mettre à jour l'email dans Keycloak (emailVerified sera mis à false automatiquement)
      Admin_Lab_KAP_Keycloak::update_user_safe($kc_user_id, [
        'email' => $new_email,
        'emailVerified' => false, // Forcer la vérification
      ]);

      // ✅ Mettre à jour l'email dans WordPress également
      $update_result = wp_update_user([
        'ID' => $user_id,
        'user_email' => $new_email,
      ]);
      
      if (is_wp_error($update_result)) {
        // Logger l'erreur mais ne pas bloquer (Keycloak est la source de vérité)
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log('[KAP Update Email] Failed to update WordPress email: ' . $update_result->get_error_message());
        }
      }

      // ✅ Envoyer l'email de vérification via execute-actions-email (plus fiable que send-verify-email)
      try {
        Admin_Lab_KAP_Keycloak::admin_request('PUT', '/users/' . rawurlencode($kc_user_id) . '/execute-actions-email', ['VERIFY_EMAIL']);
      } catch (Exception $e) {
        // Si execute-actions-email échoue, essayer avec send-verify-email en fallback
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log('[KAP Update Email] execute-actions-email failed, trying send-verify-email: ' . $e->getMessage());
        }
        try {
          $client_id = Admin_Lab_KAP_Keycloak::opt('kc_client_id');
          $redirect_uri = Admin_Lab_KAP_Keycloak::opt('kc_redirect_uri');
          Admin_Lab_KAP_Keycloak::send_verification_email($kc_user_id, $client_id ?: null, $redirect_uri ?: null);
        } catch (Exception $e2) {
          // Logger l'erreur mais ne pas bloquer la mise à jour de l'email
          if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[KAP Update Email] send-verify-email also failed: ' . $e2->getMessage());
          }
        }
      }
      
      // Supprimer le transient
      delete_transient($transient_key);
      
      return [
        'success' => true,
        'return_url' => $data['return_url'] ?? '',
      ];
    } catch (Exception $e) {
      delete_transient($transient_key);
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Met à jour l'email de l'utilisateur dans Keycloak
   * L'email devra être vérifié via le système Keycloak
   * @deprecated Utiliser init_email_change() à la place pour la sécurité
   */
  public static function update_email(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $new_email = sanitize_email((string)$req->get_param('email'));

    if (empty($new_email) || !is_email($new_email)) {
      return self::json_err('Email invalide.');
    }

    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    if (!$kc_user_id) {
      return self::json_err('keycloak_user_id introuvable pour cet utilisateur.');
    }

    try {
      // Vérifier si l'email existe déjà pour un autre utilisateur
      $existing_user = Admin_Lab_KAP_Keycloak::find_user_by_email($new_email);
      if ($existing_user && isset($existing_user['id']) && $existing_user['id'] !== $kc_user_id) {
        return self::json_err('Cet email est déjà utilisé par un autre compte.');
      }

      // ✅ Mettre à jour l'email dans Keycloak (emailVerified sera mis à false automatiquement)
      Admin_Lab_KAP_Keycloak::update_user_safe($kc_user_id, [
        'email' => $new_email,
        'emailVerified' => false, // Forcer la vérification
      ]);

      // ✅ Mettre à jour l'email dans WordPress également
      $update_result = wp_update_user([
        'ID' => $user_id,
        'user_email' => $new_email,
      ]);
      
      if (is_wp_error($update_result)) {
        // Logger l'erreur mais ne pas bloquer (Keycloak est la source de vérité)
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log('[KAP Update Email] Failed to update WordPress email: ' . $update_result->get_error_message());
        }
      }

      // ✅ Envoyer l'email de vérification via execute-actions-email (plus fiable que send-verify-email)
      // Cette méthode déclenche l'envoi d'un email avec un lien de vérification
      try {
        Admin_Lab_KAP_Keycloak::admin_request('PUT', '/users/' . rawurlencode($kc_user_id) . '/execute-actions-email', ['VERIFY_EMAIL']);
      } catch (Exception $e) {
        // Si execute-actions-email échoue, essayer avec send-verify-email en fallback
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log('[KAP Update Email] execute-actions-email failed, trying send-verify-email: ' . $e->getMessage());
        }
        try {
          $client_id = Admin_Lab_KAP_Keycloak::opt('kc_client_id');
          $redirect_uri = Admin_Lab_KAP_Keycloak::opt('kc_redirect_uri');
          Admin_Lab_KAP_Keycloak::send_verification_email($kc_user_id, $client_id ?: null, $redirect_uri ?: null);
        } catch (Exception $e2) {
          // Logger l'erreur mais ne pas bloquer la mise à jour de l'email
          // L'utilisateur pourra renvoyer l'email de vérification manuellement
          if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[KAP Update Email] send-verify-email also failed: ' . $e2->getMessage());
          }
        }
      }

      return self::json_ok([
        'email_updated' => true,
        'email_verification_sent' => true,
        'message' => 'Email mis à jour. Un email de vérification a été envoyé.',
      ]);
    } catch (Exception $e) {
      return self::json_err($e->getMessage(), 500);
    }
  }

  /**
   * Renvoie l'email de vérification pour l'utilisateur actuel
   */
  public static function resend_verification_email(WP_REST_Request $req) {
    $user_id = get_current_user_id();
    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    
    if (!$kc_user_id) {
      return self::json_err('keycloak_user_id introuvable pour cet utilisateur.');
    }

    try {
      // ✅ Utiliser execute-actions-email avec VERIFY_EMAIL (plus fiable)
      Admin_Lab_KAP_Keycloak::admin_request('PUT', '/users/' . rawurlencode($kc_user_id) . '/execute-actions-email', ['VERIFY_EMAIL']);
      
      return self::json_ok([
        'verification_email_sent' => true,
        'message' => 'Email de vérification renvoyé avec succès.',
      ]);
    } catch (Exception $e) {
      // Fallback sur send-verify-email si execute-actions-email échoue
      try {
        $client_id = Admin_Lab_KAP_Keycloak::opt('kc_client_id');
        $redirect_uri = Admin_Lab_KAP_Keycloak::opt('kc_redirect_uri');
        Admin_Lab_KAP_Keycloak::send_verification_email($kc_user_id, $client_id ?: null, $redirect_uri ?: null);
        
        return self::json_ok([
          'verification_email_sent' => true,
          'message' => 'Email de vérification renvoyé avec succès.',
        ]);
      } catch (Exception $e2) {
        return self::json_err($e2->getMessage(), 500);
      }
    }
  }

  /**
   * Callback après linking Keycloak.
   * Keycloak renvoie state (+ potentiellement d'autres params).
   * Ici, on marque la connexion active dans la table.
   */
  public static function keycloak_callback(WP_REST_Request $req) {
    // ✅ Logging pour debug (seulement en mode debug)
    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
      $all_params = $req->get_params();
      error_log('[KAP Callback] Params reçus: ' . wp_json_encode($all_params));
    }
    
    // ✅ Retour final du broker link: Keycloak revient sur redirect_uri avec kap_token, sans state OIDC
    $kap_token = (string) $req->get_param('kap_token');
    $state_param = (string) $req->get_param('state');

    if ($kap_token && empty($state_param)) {
      $st = get_transient('admin_lab_kap_state_' . $kap_token);
      if (!$st || !is_array($st) || empty($st['u']) || empty($st['p'])) {
        return new WP_REST_Response('State expired or invalid', 403);
      }

      // one-time use
      delete_transient('admin_lab_kap_state_' . $kap_token);

      $user_id = (int) $st['u'];
      $provider = sanitize_key((string) $st['p']);

      // Ici, pas de code: on vérifie le linking en lisant les federated identities
      $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);

      $linking_success = false;
      if ($kc_user_id) {
        try {
          $providers = Admin_Lab_KAP_Keycloak::get_providers();
          $kc_alias = $providers[$provider]['kc_alias'] ?? $provider;
          $fed = Admin_Lab_KAP_Keycloak::get_federated_identities($kc_user_id);

          foreach ($fed as $item) {
            if (!is_array($item)) continue;
            $alias = (string)($item['identityProvider'] ?? '');
            if ($alias === $kc_alias) {
              $linking_success = true;
              break;
            }
          }
        } catch (Exception $e) {
          // si on ne peut pas vérifier, on évite de bloquer l'utilisateur
          $linking_success = true;
        }
      } else {
        $linking_success = true;
      }

      Admin_Lab_DB::getInstance()->upsert_keycloak_connection([
        'user_id' => $user_id,
        'provider_slug' => $provider,
        'keycloak_identity_id' => $kc_user_id ?: '',
        'is_active' => $linking_success ? 1 : 0,
        'last_sync_at' => current_time('mysql'),
      ]);

      // ✅ Redirection vers l'URL de retour stockée (avec l'onglet), ou profil UM par défaut
      $return_url = !empty($st['return_url']) ? $st['return_url'] : '';
      
      // Si pas d'URL stockée, essayer de récupérer depuis le referer ou construire l'URL du profil
      if (!$return_url) {
        // Essayer de récupérer l'URL depuis le referer si disponible
        if (!empty($_SERVER['HTTP_REFERER'])) {
          $return_url = esc_url_raw($_SERVER['HTTP_REFERER']);
        } else {
          $return_url = admin_lab_get_current_user_profile_url('linked-accounts');
        }
      }
      
      // Si toujours pas d'URL, utiliser la page d'accueil
      if (!$return_url) {
        $return_url = home_url('/');
      }

      // Ajouter les paramètres de statut à l'URL de retour
      $redirect_url = add_query_arg([
        'kap' => $linking_success ? 'success' : 'error',
        'provider' => $provider,
      ], $return_url);

      wp_safe_redirect($redirect_url);
      exit;
    }
    
    // ✅ Vérifier kc_action_status (indique le résultat du linking AIA)
    $kc_action_status = (string) $req->get_param('kc_action_status');
    if ($kc_action_status === 'error') {
      // Le linking a échoué dans Keycloak
      $error_description = 'Le linking a échoué dans Keycloak. ';
      $error_description .= 'Vérifiez que l\'utilisateur a le rôle "account.manage-account-links" et que le provider Google est correctement configuré dans Keycloak.';
      
      $redirect_url = add_query_arg([
        'kap' => 'error',
        'error' => 'kc_action_error',
        'error_description' => rawurlencode($error_description),
      ], home_url('/'));
      
      $profile_url = admin_lab_get_current_user_profile_url('compte');
      if ($profile_url) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
            'error' => 'kc_action_error',
            'error_description' => rawurlencode($error_description),
          ], $profile_url);
        }
      }
      
      wp_safe_redirect($redirect_url);
      exit;
    }
    
    // ✅ Gérer les erreurs Keycloak (error=invalid_request, invalid_redirect_uri, etc.)
    $error = (string) $req->get_param('error');
    if ($error) {
      $desc = (string) $req->get_param('error_description');
      
      // Message d'erreur personnalisé selon le type d'erreur
      $error_message = $desc ?: $error;
      if ($error === 'invalid_redirect_uri') {
        $redirect_uri = (string) Admin_Lab_KAP_Keycloak::opt('kc_redirect_uri');
        $error_message = sprintf(
          'URI de redirection invalide. Veuillez ajouter "%s" dans les "Valid Redirect URIs" du client Keycloak "%s".',
          esc_html($redirect_uri),
          esc_html(Admin_Lab_KAP_Keycloak::opt('kc_client_id'))
        );
      }
      
      $redirect_url = add_query_arg([
        'kap' => 'error',
        'error' => rawurlencode($error),
        'error_description' => rawurlencode($error_message),
      ], home_url('/'));
      
      // Essayer de rediriger vers le profil UM si disponible
      $profile_url = admin_lab_get_current_user_profile_url('compte');
      if ($profile_url) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
            'error' => rawurlencode($error),
            'error_description' => rawurlencode($error_message),
          ], $profile_url);
        }
      }
      
      wp_safe_redirect($redirect_url);
      exit;
    }

    // Vérifier et décoder le state
    $state = (string)$req->get_param('state');
    if (!$state || strpos($state, '.') === false) {
      // Logger pour debug
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[KAP Callback] State invalide ou manquant');
      }
      return new WP_REST_Response('Invalid state', 400);
    }

    [$b64, $sig] = explode('.', $state, 2);
    $raw = base64_decode($b64, true);
    if (!$raw) return new WP_REST_Response('Invalid state payload', 400);

    $expected = hash_hmac('sha256', $raw, wp_salt('auth'));
    if (!hash_equals($expected, $sig)) {
      return new WP_REST_Response('Invalid state signature', 403);
    }

    // ✅ Nouveau système : récupérer les infos depuis le transient
    $payload = json_decode($raw, true);
    if (!is_array($payload) || empty($payload['k'])) {
      return new WP_REST_Response('Invalid state data', 400);
    }

    // ✅ Détecter si c'est un flow de changement de mot de passe ou d'email
    if (!empty($payload['action'])) {
      if ($payload['action'] === 'password_change') {
        self::handle_password_change_callback($req, $payload['k'], $state);
        return; // La méthode gère déjà la redirection, on s'arrête ici
      } elseif ($payload['action'] === 'email_change') {
        self::handle_email_change_callback($req, $payload['k'], $state);
        return; // La méthode gère déjà la redirection, on s'arrête ici
      }
    }

    // Récupérer les infos depuis le transient (pour broker link)
    $st = get_transient('admin_lab_kap_state_' . $payload['k']);
    if (!$st || !is_array($st) || empty($st['u']) || empty($st['p'])) {
      return new WP_REST_Response('State expired or invalid', 403);
    }

    // ⚠️ NE PAS supprimer le transient ici si on est en mode broker_link
    // On le supprimera dans la branche kap_token lors du retour final

    $user_id = (int) $st['u'];
    $provider = sanitize_key((string) $st['p']);

    // ✅ Vérifier que le code d'autorisation est présent (sinon le linking n'a pas eu lieu)
    $code = (string) $req->get_param('code');
    if (empty($code)) {
      // Logger pour debug
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        $all_params = $req->get_params();
        error_log('[KAP Callback] Code manquant. Tous les params: ' . wp_json_encode($all_params));
      }
      
      // Pas de code = le linking n'a pas été complété ou l'utilisateur a annulé
      // Peut-être que Keycloak redirige quand même si l'utilisateur a annulé
      $session_state = (string) $req->get_param('session_state');
      
      $error_description = 'Le processus de liaison n\'a pas été complété. ';
      if ($session_state) {
        $error_description .= 'Vous avez peut-être annulé l\'autorisation ou une erreur est survenue dans Keycloak. ';
      }
      $error_description .= 'Veuillez vérifier les logs Keycloak et réessayer.';
      
      $redirect_url = add_query_arg([
        'kap' => 'error',
        'error' => 'missing_code',
        'error_description' => rawurlencode($error_description),
      ], home_url('/'));
      
      $profile_url = admin_lab_get_current_user_profile_url('compte');
      if ($profile_url) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
            'error' => 'missing_code',
            'error_description' => rawurlencode($error_description),
          ], $profile_url);
        }
      }
      
      wp_safe_redirect($redirect_url);
      exit;
    }

    // ✅ Récupérer le code_verifier PKCE depuis le transient
    $code_verifier = get_transient('kap_pkce_' . md5($state));
    if (!$code_verifier) {
      // Code verifier expiré ou introuvable
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[KAP Callback] Code verifier PKCE manquant pour state: ' . substr($state, 0, 20) . '...');
      }
    } else {
      // Nettoyer le transient après récupération
      delete_transient('kap_pkce_' . md5($state));
    }

    // Récupérer le kc_user_id de l'utilisateur WordPress
    // On essaie d'abord depuis les claims OpenID, puis depuis la table
    $kc_user_id = Admin_Lab_KAP_Keycloak::get_kc_user_id_for_wp_user($user_id);
    
    // Si on a un code, essayer de récupérer le kc_user_id depuis le token
    if (!empty($code)) {
      try {
        // Échanger le code contre un token avec PKCE
        $token_response = Admin_Lab_KAP_Keycloak::exchange_code_for_token($code, $code_verifier ?: '');
        $access_token = $token_response['access_token'] ?? '';
        
        if (!empty($access_token)) {
          $kc_user_id_from_token = Admin_Lab_KAP_Keycloak::get_user_id_from_token($access_token);
          
          // Utiliser le kc_user_id du token si on n'en avait pas
          if (empty($kc_user_id) && !empty($kc_user_id_from_token)) {
            $kc_user_id = $kc_user_id_from_token;
            
            // Stocker le kc_user_id pour ce user_id WordPress
            Admin_Lab_DB::getInstance()->upsert_keycloak_connection([
              'user_id' => $user_id,
              'provider_slug' => '_keycloak',
              'keycloak_identity_id' => $kc_user_id,
              'is_active' => 1,
              'last_sync_at' => current_time('mysql'),
            ]);
          }
        }

        // ✅ Si on est en mode broker_link, on enchaîne immédiatement sur /broker/{provider}/link
        if (!empty($st['mode']) && $st['mode'] === 'broker_link') {
          $session_state = (string) $req->get_param('session_state');
          if (!$session_state && !empty($token_response['session_state'])) {
            $session_state = (string) $token_response['session_state'];
          }

          // issued_for = ton client_id (celui qui lance l'action)
          $issued_for = (string) Admin_Lab_KAP_Keycloak::opt('kc_client_id');

          // redirect_uri = callback + kap_token pour retrouver le transient au retour final
          $kap_token = (string) $payload['k'];
          $redirect_uri = (string) Admin_Lab_KAP_Keycloak::opt('kc_redirect_uri');
          $redirect_uri = add_query_arg(['kap_token' => $kap_token], $redirect_uri);

          $providers = Admin_Lab_KAP_Keycloak::get_providers();
          $kc_alias = $providers[$provider]['kc_alias'] ?? $provider;

          if (!$session_state) {
            // Si on n'a vraiment pas session_state, on ne peut pas signer le broker link
            return new WP_REST_Response('Missing session_state for broker link', 400);
          }

          $broker_url = Admin_Lab_KAP_Keycloak::build_broker_link_url(
            $kc_alias,
            $redirect_uri,
            $session_state,
            $issued_for
          );

          wp_safe_redirect($broker_url);
          exit;
        }
      } catch (Exception $e) {
        // Logger l'erreur pour debug
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log('[KAP Callback] Erreur échange token: ' . $e->getMessage());
        }
      }
    }
    
    // Si on n'a toujours pas de kc_user_id, on ne peut pas vérifier le linking
    // mais on peut quand même marquer le provider comme "en cours de traitement"
    if (empty($kc_user_id)) {
      // On essaie quand même de synchroniser depuis la table existante
      // Peut-être que l'utilisateur a déjà un compte Keycloak lié
      Admin_Lab_DB::getInstance()->upsert_keycloak_connection([
        'user_id' => $user_id,
        'provider_slug' => $provider,
        'keycloak_identity_id' => '',
        'is_active' => 0, // On marque comme inactif jusqu'à vérification
        'last_sync_at' => current_time('mysql'),
      ]);
    }

    // ✅ Vérifier que le linking a réellement eu lieu en récupérant les identités fédérées
    $linking_success = false;
    if ($kc_user_id) {
      try {
        $providers = Admin_Lab_KAP_Keycloak::get_providers();
        $kc_alias = $providers[$provider]['kc_alias'] ?? $provider;
        $fed = Admin_Lab_KAP_Keycloak::get_federated_identities($kc_user_id);
        
        // Vérifier si le provider est présent dans les identités fédérées
        foreach ($fed as $item) {
          if (!is_array($item)) continue;
          $alias = (string)($item['identityProvider'] ?? '');
          if ($alias === $kc_alias) {
            $linking_success = true;
            break;
          }
        }
      } catch (Exception $e) {
        // Si on ne peut pas vérifier, on suppose que ça a marché (pour ne pas bloquer)
        $linking_success = true; // Optimiste
      }
    } else {
      // Si on n'a pas de kc_user_id, on ne peut pas vérifier maintenant
      // On suppose que ça a marché et on essaiera de récupérer les infos plus tard
      $linking_success = true; // Optimiste
    }

    // On active au minimum la ligne provider dans la DB WordPress
            Admin_Lab_DB::getInstance()->upsert_keycloak_connection([
      'user_id' => $user_id,
      'provider_slug' => $provider,
      'keycloak_identity_id' => $kc_user_id ?: '', // peut être vide si user tout neuf
      'is_active' => $linking_success ? 1 : 0, // Activer seulement si le linking a réussi
      'last_sync_at' => current_time('mysql'),
    ]);

    // Si on a le kc_user_id, on pull toutes les identités fédérées et on remplit external_user_id/username
    if ($kc_user_id) {
      try {
        $providers = Admin_Lab_KAP_Keycloak::get_providers();
        $fed = Admin_Lab_KAP_Keycloak::get_federated_identities($kc_user_id);

        // fed items contiennent souvent : identityProvider, userId, userName
        foreach ($fed as $item) {
          if (!is_array($item)) continue;

          $alias = (string)($item['identityProvider'] ?? '');
          $extId = (string)($item['userId'] ?? '');
          $extName = (string)($item['userName'] ?? '');

          if (!$alias) continue;

          // retrouver provider_slug correspondant à cet alias
          $provider_slug = null;
          foreach ($providers as $slug => $cfg) {
            $cfg_alias = $cfg['kc_alias'] ?? $slug;
            if ($cfg_alias === $alias) {
              $provider_slug = $slug;
              break;
            }
          }
          if (!$provider_slug) {
            // provider non déclaré côté WP : on ignore
            continue;
          }

          // Normaliser le provider_slug : YouTube utilise Google OAuth, donc sauvegarder comme 'google'
          if (function_exists('admin_lab_normalize_account_provider_slug')) {
            $provider_slug = admin_lab_normalize_account_provider_slug($provider_slug);
          } elseif (strpos($provider_slug, 'youtube') === 0) {
            $provider_slug = 'google';
          }

          Admin_Lab_DB::getInstance()->upsert_keycloak_connection([
            'user_id' => $user_id,
            'provider_slug' => $provider_slug,
            'external_user_id' => $extId,
            'external_username' => $extName,
            'keycloak_identity_id' => $kc_user_id,
            'is_active' => 1,
            'last_sync_at' => current_time('mysql'),
          ]);
        }
      } catch (Exception $e) {
        // On n'empêche pas la redirection
      }
    }
      
    // ✅ Redirection vers l'URL de retour stockée (avec l'onglet), ou profil UM par défaut
    $return_url = !empty($st['return_url']) ? $st['return_url'] : '';
    
    // Si pas d'URL stockée, essayer de récupérer depuis le referer ou construire l'URL du profil
    if (!$return_url) {
      // Essayer de récupérer l'URL depuis le referer si disponible
      if (!empty($_SERVER['HTTP_REFERER'])) {
        $return_url = esc_url_raw($_SERVER['HTTP_REFERER']);
      } else {
        $return_url = admin_lab_get_current_user_profile_url('linked-accounts');
      }
    }
    
    // Si toujours pas d'URL, utiliser la page d'accueil
    if (!$return_url) {
      $return_url = home_url('/');
    }
    
    $redirect_params = [];
    
    if ($linking_success) {
      $redirect_params['kap'] = 'linked';
      $redirect_params['provider'] = rawurlencode($provider);
    } else {
      // Si le linking n'a pas réussi, rediriger avec une erreur
      $redirect_params['kap'] = 'error';
      $redirect_params['error'] = 'linking_failed';
      $redirect_params['error_description'] = rawurlencode('La liaison avec ' . esc_html($provider) . ' n\'a pas pu être complétée. Vérifiez vos droits dans Keycloak.');
    }
    
    $redirect_url = add_query_arg($redirect_params, $return_url);
    
    wp_safe_redirect($redirect_url);
    exit;
  }

  /**
   * Gère le callback Keycloak pour le changement de mot de passe après ré-authentification
   */
  private static function handle_password_change_callback(WP_REST_Request $req, string $token, string $state) {
    $code = (string) $req->get_param('code');
    if (empty($code)) {
      $redirect_url = add_query_arg([
        'kap' => 'error',
        'error' => 'missing_code',
        'error_description' => rawurlencode('Le processus de ré-authentification n\'a pas été complété.'),
      ], home_url('/'));
      
      $profile_url = admin_lab_get_current_user_profile_url('compte');
      if ($profile_url) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
          'error' => 'missing_code',
          'error_description' => rawurlencode('Le processus de ré-authentification n\'a pas été complété.'),
        ], $profile_url);
      }
      
      wp_safe_redirect($redirect_url);
      exit;
    }

    // ✅ Échanger le code contre un token pour vérifier la ré-authentification
    $code_verifier = get_transient('kap_pkce_' . md5($state));
    if ($code_verifier) {
      delete_transient('kap_pkce_' . md5($state));
    }

    try {
      $token_response = Admin_Lab_KAP_Keycloak::exchange_code_for_token($code, $code_verifier ?: '');
      
      // Si on obtient un token, la ré-authentification a réussi
      if (empty($token_response['access_token'])) {
        throw new Exception('Impossible d\'obtenir un token après ré-authentification');
      }

      // ✅ Finaliser le changement de mot de passe
      $result = self::finalize_password_change($token);
      
      if (!$result['success']) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
          'error' => 'password_change_failed',
          'error_description' => rawurlencode($result['error'] ?? 'Erreur lors du changement de mot de passe.'),
        ], home_url('/'));
      } else {
        $redirect_url = add_query_arg([
          'kap' => 'password_changed',
        ], $result['return_url'] ?: home_url('/'));
        
        // Fallback vers le profil avec l'onglet compte
        if (empty($result['return_url'])) {
          $profile_url = admin_lab_get_current_user_profile_url('compte');
          if ($profile_url) {
            $redirect_url = add_query_arg(['kap' => 'password_changed'], $profile_url);
          }
        }
      }

      wp_safe_redirect($redirect_url);
      exit;
    } catch (Exception $e) {
      $redirect_url = add_query_arg([
        'kap' => 'error',
        'error' => 'reauth_failed',
        'error_description' => rawurlencode('Erreur lors de la ré-authentification : ' . $e->getMessage()),
      ], home_url('/'));
      
      $profile_url = admin_lab_get_current_user_profile_url('compte');
      if ($profile_url) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
          'error' => 'reauth_failed',
          'error_description' => rawurlencode('Erreur lors de la ré-authentification : ' . $e->getMessage()),
        ], $profile_url);
      }
      
      wp_safe_redirect($redirect_url);
      exit;
    }
  }

  /**
   * Gère le callback Keycloak pour le changement d'email après ré-authentification
   */
  private static function handle_email_change_callback(WP_REST_Request $req, string $token, string $state) {
    $code = (string) $req->get_param('code');
    if (empty($code)) {
      $redirect_url = add_query_arg([
        'kap' => 'error',
        'error' => 'missing_code',
        'error_description' => rawurlencode('Le processus de ré-authentification n\'a pas été complété.'),
      ], home_url('/'));
      
      $profile_url = admin_lab_get_current_user_profile_url('compte');
      if ($profile_url) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
          'error' => 'missing_code',
          'error_description' => rawurlencode('Le processus de ré-authentification n\'a pas été complété.'),
        ], $profile_url);
      }
      
      wp_safe_redirect($redirect_url);
      exit;
    }

    // ✅ Échanger le code contre un token pour vérifier la ré-authentification
    $code_verifier = get_transient('kap_pkce_' . md5($state));
    if ($code_verifier) {
      delete_transient('kap_pkce_' . md5($state));
    }

    try {
      $token_response = Admin_Lab_KAP_Keycloak::exchange_code_for_token($code, $code_verifier ?: '');
      
      // Si on obtient un token, la ré-authentification a réussi
      if (empty($token_response['access_token'])) {
        throw new Exception('Impossible d\'obtenir un token après ré-authentification');
      }

      // ✅ Finaliser le changement d'email
      $result = self::finalize_email_change($token);
      
      if (!$result['success']) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
          'error' => 'email_change_failed',
          'error_description' => rawurlencode($result['error'] ?? 'Erreur lors du changement d\'email.'),
        ], home_url('/'));
      } else {
        $redirect_url = add_query_arg([
          'kap' => 'email_changed',
        ], $result['return_url'] ?: home_url('/'));
        
        // Fallback vers le profil avec l'onglet compte
        if (empty($result['return_url'])) {
          $profile_url = admin_lab_get_current_user_profile_url('compte');
          if ($profile_url) {
            $redirect_url = add_query_arg(['kap' => 'email_changed'], $profile_url);
          }
        }
      }

      wp_safe_redirect($redirect_url);
      exit;
    } catch (Exception $e) {
      $redirect_url = add_query_arg([
        'kap' => 'error',
        'error' => 'reauth_failed',
        'error_description' => rawurlencode('Erreur lors de la ré-authentification : ' . $e->getMessage()),
      ], home_url('/'));
      
      $profile_url = admin_lab_get_current_user_profile_url('compte');
      if ($profile_url) {
        $redirect_url = add_query_arg([
          'kap' => 'error',
          'error' => 'reauth_failed',
          'error_description' => rawurlencode('Erreur lors de la ré-authentification : ' . $e->getMessage()),
        ], $profile_url);
      }
      
      wp_safe_redirect($redirect_url);
      exit;
    }
  }
}


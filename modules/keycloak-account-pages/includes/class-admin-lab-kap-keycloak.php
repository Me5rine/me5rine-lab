<?php
// File: modules/keycloak-account-pages/includes/class-admin-lab-kap-keycloak.php

if (!defined('ABSPATH')) exit;

class Admin_Lab_KAP_Keycloak {

  public static function opt(string $key, $default = '') {
    $v = get_option('admin_lab_kap_' . $key, null);
    return $v === null ? $default : $v;
  }

  public static function get_providers(): array {
    $raw = self::opt('providers_json', '{}');
    
    // Si c'est déjà un tableau (sérialisé par WordPress), le retourner directement
    if (is_array($raw)) {
      return apply_filters('admin_lab_kap_providers', $raw);
    }
    
    // Sinon, essayer de le décoder comme JSON
    $json = (string) $raw;
    
    // Si c'est une chaîne sérialisée, la désérialiser d'abord
    if (is_serialized($json)) {
      $maybe = @unserialize($json);
      if (is_array($maybe)) {
        return apply_filters('admin_lab_kap_providers', $maybe);
      }
    }
    
    // Décoder le JSON
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
      $arr = [];
    }
    
    return apply_filters('admin_lab_kap_providers', $arr);
  }

  /**
   * Récupère le Keycloak user ID pour un utilisateur WordPress
   * Essaie plusieurs sources dans l'ordre :
   * 1. Les claims OpenID Connect (si disponible via OpenID Connect Generic plugin)
   * 2. La table keycloak_accounts (connexion active)
   * 3. La table keycloak_accounts (toute connexion)
   */
  public static function get_kc_user_id_for_wp_user(int $user_id): string {
    // 1. Essayer depuis les claims OpenID Connect (le plus fiable si l'utilisateur est connecté via Keycloak)
    if (function_exists('openid_connect_generic_get_user_claim')) {
      $user_claim = openid_connect_generic_get_user_claim($user_id);
      if (!empty($user_claim['sub'])) {
        return (string) $user_claim['sub'];
      }
    }
    
    // 2. Essayer depuis la table (connexion active)
    $kc = Admin_Lab_DB::getInstance()->get_kc_identity_id_for_user($user_id, true);
    if ($kc) {
      return (string) $kc;
    }
    
    // 3. Essayer depuis la table (toute connexion)
    $kc = Admin_Lab_DB::getInstance()->get_kc_identity_id_for_user($user_id, false);
    if ($kc) {
      return (string) $kc;
    }
    
    return '';
  }

  public static function base_realm_url(): string {
    $base = rtrim((string) self::opt('kc_base_url'), '/');
    $realm = rawurlencode((string) self::opt('kc_realm'));
    return "{$base}/realms/{$realm}";
  }

  public static function admin_url(): string {
    $base = rtrim((string) self::opt('kc_base_url'), '/');
    $realm = rawurlencode((string) self::opt('kc_realm'));
    return "{$base}/admin/realms/{$realm}";
  }

  public static function get_admin_token(): string {
    $cached = get_transient('admin_lab_kap_kc_admin_token');
    if ($cached) return (string)$cached;

    $token_url = self::base_realm_url() . '/protocol/openid-connect/token';

    $client_id = (string) self::opt('kc_admin_client_id');
    $client_secret = (string) self::opt('kc_admin_secret');

    $resp = wp_remote_post($token_url, [
      'timeout' => 15,
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'body'    => http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
      ], '', '&'),
    ]);

    if (is_wp_error($resp)) {
      throw new Exception('Keycloak token error: ' . $resp->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);

    if ($code < 200 || $code >= 300 || !is_array($body) || empty($body['access_token'])) {
      throw new Exception('Keycloak token error: HTTP ' . $code);
    }

    $ttl = !empty($body['expires_in']) ? max(30, (int)$body['expires_in'] - 30) : 240;
    set_transient('admin_lab_kap_kc_admin_token', $body['access_token'], $ttl);

    return (string)$body['access_token'];
  }

  public static function admin_request(string $method, string $path, array $jsonBody = null): array {
    $token = self::get_admin_token();
    $url = self::admin_url() . $path;

    $args = [
      'timeout' => 15,
      'method'  => $method,
      'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type'  => 'application/json',
      ],
    ];

    if ($jsonBody !== null) {
      $args['body'] = wp_json_encode($jsonBody);
    }

    $resp = wp_remote_request($url, $args);
    if (is_wp_error($resp)) {
      throw new Exception('Keycloak admin request error: ' . $resp->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($resp);
    $raw  = wp_remote_retrieve_body($resp);

    $data = null;
    if ($raw !== '') {
      $parsed = json_decode($raw, true);
      $data = is_array($parsed) ? $parsed : $raw;
    }

    return ['code' => $code, 'data' => $data];
  }

  /**
   * Encode en base64url (sans padding, avec -_ au lieu de +/)
   */
  private static function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Génère le code_challenge PKCE à partir du verifier
   */
  private static function pkce_challenge(string $verifier): string {
    return self::base64url_encode(hash('sha256', $verifier, true));
  }

  /**
   * Construit l'URL de linking AIA (Account Initiated Action) pour Keycloak
   * Utilise l'endpoint OIDC standard avec kc_action=idp_link:<alias>
   * Compatible avec Keycloak 26.2.4
   * 
   * Cette méthode utilise l'approche AIA recommandée par Keycloak pour lier un provider
   * à un compte Keycloak existant.
   * 
   * Prérequis Keycloak :
   * - L'IdP doit être configuré et activé dans Keycloak
   * - Le client doit avoir les scopes "openid profile email" au minimum
   * - PKCE doit être activé (code_challenge_method requis)
   * - L'utilisateur doit avoir le rôle account.manage-account-links
   * - L'utilisateur DOIT avoir une session Keycloak active pour que kc_action fonctionne
   * 
   * Note: Pour que kc_action=idp_link fonctionne, l'utilisateur doit être authentifié
   * dans Keycloak. Si l'utilisateur n'a pas de session active, Keycloak peut échouer.
   * On utilise prompt=login pour forcer l'authentification si nécessaire.
   */
  public static function build_link_url(string $provider_alias, string $state, ?int $user_id = null): string {
    $base = self::base_realm_url();
    $client_id = rawurlencode((string) self::opt('kc_client_id'));
    $redirect  = rawurlencode((string) self::opt('kc_redirect_uri'));
    $alias     = rawurlencode($provider_alias);

    // Nonce OIDC
    $oidc_nonce = wp_generate_password(16, false, false);

    // Action Keycloak : idp_link:<identity-provider-alias>
    $kc_action = rawurlencode('idp_link:' . $provider_alias);

    // Scope incluant openid pour OIDC
    $scope = rawurlencode('openid profile email');

    // ✅ PKCE (REQUIS pour Keycloak 26.2.4 avec kc_action)
    $code_verifier  = self::base64url_encode(random_bytes(32));
    $code_challenge = self::pkce_challenge($code_verifier);

    // ✅ Stocker le verifier pour échanger le code dans le callback
    set_transient('kap_pkce_' . md5($state), $code_verifier, 10 * MINUTE_IN_SECONDS);

    // Utiliser l'endpoint OIDC standard avec kc_action pour le linking
    // ✅ Utiliser prompt=login pour forcer l'affichage de la page de login
    // Cela permet à l'utilisateur de choisir comment s'authentifier (mot de passe ou provider)
    // ⚠️ NE PAS utiliser login_hint ici car cela cause des problèmes si l'utilisateur n'a pas de mot de passe
    // Keycloak essaiera d'authentifier automatiquement et échouera avec invalid_user_credentials
    $url = $base . "/protocol/openid-connect/auth"
      . "?client_id={$client_id}"
      . "&redirect_uri={$redirect}"
      . "&response_type=code"
      . "&scope={$scope}"
      . "&kc_action={$kc_action}"
      . "&state=" . rawurlencode($state)
      . "&nonce=" . rawurlencode($oidc_nonce)
      . "&code_challenge=" . rawurlencode($code_challenge)
      . "&code_challenge_method=S256"
      . "&prompt=login"; // Forcer l'affichage de la page de login pour permettre le choix du provider

    // ⚠️ NE PAS utiliser login_hint ici
    // login_hint + pas de prompt=login = Keycloak essaie d'authentifier automatiquement
    // Cela échoue si l'utilisateur n'a pas de mot de passe (connexion via provider uniquement)

    return $url;
  }

  /**
   * Construit une URL OIDC "standard" (sans kc_action) pour faire un pre-auth
   * Objectif: obtenir une session Keycloak + session_state, puis enchaîner sur /broker/{idp}/link
   */
  public static function build_auth_url(string $state): string {
    $base = self::base_realm_url();
    $client_id = rawurlencode((string) self::opt('kc_client_id'));
    $redirect  = rawurlencode((string) self::opt('kc_redirect_uri'));

    $oidc_nonce = wp_generate_password(16, false, false);
    $scope = rawurlencode('openid profile email');

    // PKCE
    $code_verifier  = self::base64url_encode(random_bytes(32));
    $code_challenge = self::pkce_challenge($code_verifier);

    set_transient('kap_pkce_' . md5($state), $code_verifier, 10 * MINUTE_IN_SECONDS);

    return $base . "/protocol/openid-connect/auth"
      . "?client_id={$client_id}"
      . "&redirect_uri={$redirect}"
      . "&response_type=code"
      . "&scope={$scope}"
      . "&state=" . rawurlencode($state)
      . "&nonce=" . rawurlencode($oidc_nonce)
      . "&code_challenge=" . rawurlencode($code_challenge)
      . "&code_challenge_method=S256"
      . "&prompt=login";
  }

  /**
   * Construit une URL OIDC pour la ré-authentification (changement de mot de passe)
   * Force l'utilisateur à se ré-authentifier avec prompt=login
   */
  public static function build_reauth_url(string $state): string {
    $base = self::base_realm_url();
    $client_id = rawurlencode((string) self::opt('kc_client_id'));
    $redirect  = rawurlencode((string) self::opt('kc_redirect_uri'));

    $oidc_nonce = wp_generate_password(16, false, false);
    $scope = rawurlencode('openid profile email');

    // PKCE
    $code_verifier  = self::base64url_encode(random_bytes(32));
    $code_challenge = self::pkce_challenge($code_verifier);

    set_transient('kap_pkce_' . md5($state), $code_verifier, 10 * MINUTE_IN_SECONDS);

    // prompt=login force une nouvelle authentification
    // acr_values peut être utilisé pour forcer une authentification par mot de passe si nécessaire
    return $base . "/protocol/openid-connect/auth"
      . "?client_id={$client_id}"
      . "&redirect_uri={$redirect}"
      . "&response_type=code"
      . "&scope={$scope}"
      . "&state=" . rawurlencode($state)
      . "&nonce=" . rawurlencode($oidc_nonce)
      . "&code_challenge=" . rawurlencode($code_challenge)
      . "&code_challenge_method=S256"
      . "&prompt=login";
  }

  /**
   * Legacy broker link:
   * /realms/{realm}/broker/{provider}/link?client_id=...&redirect_uri=...&nonce=...&hash=...
   * hash = Base64Url( SHA-256( nonce + session_state + issuedFor + provider ) )
   */
  public static function build_broker_link_url(
    string $provider_alias,
    string $redirect_uri,
    string $session_state,
    string $issued_for
  ): string {
    $base = self::base_realm_url();
    $provider_enc = rawurlencode($provider_alias);

    $nonce = wp_generate_password(24, false, false);

    $input  = $nonce . $session_state . $issued_for . $provider_alias;
    $digest = hash('sha256', $input, true);
    $hash   = self::base64url_encode($digest);

    return "{$base}/broker/{$provider_enc}/link"
      . "?client_id=" . rawurlencode($issued_for)
      . "&redirect_uri=" . rawurlencode($redirect_uri)
      . "&nonce=" . rawurlencode($nonce)
      . "&hash=" . rawurlencode($hash);
  }

  public static function unlink_provider(string $kc_user_id, string $provider_alias): void {
    $kc_user_id = rawurlencode($kc_user_id);
    $provider_alias = rawurlencode($provider_alias);

    $res = self::admin_request('DELETE', "/users/{$kc_user_id}/federated-identity/{$provider_alias}");
    if ($res['code'] !== 204 && ($res['code'] < 200 || $res['code'] >= 300)) {
      throw new Exception('Keycloak unlink failed (HTTP ' . $res['code'] . ')');
    }
  }

  /**
   * Récupère un utilisateur Keycloak par son ID
   * GET /users/{id}
   */
  /**
   * Vérifie si un utilisateur Keycloak a un mot de passe défini
   * 
   * Méthodes de vérification (par ordre de fiabilité) :
   * 1. Vérifier si l'utilisateur a UPDATE_PASSWORD dans requiredActions (indique pas de mot de passe)
   * 2. Récupérer les credentials via l'endpoint dédié /users/{id}/credentials
   * 3. Vérifier si l'utilisateur peut s'authentifier avec un mot de passe (test avec un mot de passe invalide)
   * 
   * @return bool true si l'utilisateur a un mot de passe défini, false sinon
   */
  public static function user_has_password(string $kc_user_id): bool {
    try {
      $user = self::get_user($kc_user_id);
      
      // ✅ Méthode 1 : Vérifier les requiredActions
      // Si UPDATE_PASSWORD est dans requiredActions, l'utilisateur n'a PAS de mot de passe
      if (!empty($user['requiredActions']) && is_array($user['requiredActions'])) {
        if (in_array('UPDATE_PASSWORD', $user['requiredActions'], true)) {
          return false; // L'utilisateur doit définir un mot de passe
        }
      }
      
      // ✅ Méthode 2 : Vérifier les credentials via l'endpoint dédié
      try {
        $kc_user_id_enc = rawurlencode($kc_user_id);
        $cred_res = self::admin_request('GET', "/users/{$kc_user_id_enc}/credentials");
        
        if ($cred_res['code'] >= 200 && $cred_res['code'] < 300 && is_array($cred_res['data'])) {
          foreach ($cred_res['data'] as $cred) {
            if (is_array($cred) && isset($cred['type']) && $cred['type'] === 'password') {
              return true; // L'utilisateur a un credential de type password
            }
          }
        }
      } catch (Exception $e) {
        // Si l'endpoint credentials échoue, continuer avec la méthode 3
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log('[KAP] Failed to fetch credentials: ' . $e->getMessage());
        }
      }
      
      // ✅ Méthode 3 : Vérifier via credentials dans l'objet user (peut être vide pour sécurité)
      if (!empty($user['credentials']) && is_array($user['credentials'])) {
        foreach ($user['credentials'] as $cred) {
          if (is_array($cred) && isset($cred['type']) && $cred['type'] === 'password') {
            return true;
          }
        }
      }
      
      // ✅ Méthode 4 : Si l'utilisateur n'a pas de providers liés (federatedIdentities vide ou null)
      // et qu'il n'a pas UPDATE_PASSWORD, on peut supposer qu'il a un mot de passe
      // (car sinon il ne pourrait pas se connecter)
      $has_federated = !empty($user['federatedIdentities']) && is_array($user['federatedIdentities']) && count($user['federatedIdentities']) > 0;
      if (!$has_federated) {
        // Si pas de providers liés et pas d'action UPDATE_PASSWORD, l'utilisateur doit avoir un mot de passe
        // pour pouvoir se connecter
        return true;
      }
      
      // Par défaut, on suppose qu'il n'a pas de mot de passe (sécurité)
      return false;
    } catch (Exception $e) {
      // En cas d'erreur, supposer qu'il n'y a pas de mot de passe pour être sûr
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[KAP] user_has_password error: ' . $e->getMessage());
      }
      return false;
    }
  }

  public static function get_user(string $kc_user_id): array {
    $kc_user_id = rawurlencode($kc_user_id);
    $res = self::admin_request('GET', "/users/{$kc_user_id}");
    if ($res['code'] < 200 || $res['code'] >= 300 || !is_array($res['data'])) {
      throw new Exception('Keycloak get user failed (HTTP ' . $res['code'] . ')');
    }
    return $res['data'];
  }

  /**
   * Recherche un utilisateur Keycloak par email
   * GET /users?email={email}
   * Retourne le premier utilisateur trouvé ou null
   */
  public static function find_user_by_email(string $email): ?array {
    $email_encoded = rawurlencode($email);
    $res = self::admin_request('GET', "/users?email={$email_encoded}&exact=true");
    if ($res['code'] < 200 || $res['code'] >= 300 || !is_array($res['data'])) {
      return null;
    }
    // Keycloak retourne un tableau d'utilisateurs
    if (empty($res['data']) || !is_array($res['data'])) {
      return null;
    }
    // Retourner le premier utilisateur (il ne devrait y en avoir qu'un avec exact=true)
    return $res['data'][0] ?? null;
  }

  /**
   * Crée un utilisateur dans Keycloak
   * POST /users
   * Retourne l'ID de l'utilisateur créé
   */
  public static function create_user(array $user_data): string {
    // Champs minimum requis
    if (empty($user_data['email'])) {
      throw new Exception('Email is required to create a Keycloak user');
    }

    $payload = [
      'email' => $user_data['email'],
      'enabled' => $user_data['enabled'] ?? true,
      'emailVerified' => $user_data['emailVerified'] ?? false,
    ];

    // Username optionnel (si non fourni, Keycloak utilise l'email)
    if (!empty($user_data['username'])) {
      $payload['username'] = $user_data['username'];
    }

    // Prénom et nom
    if (!empty($user_data['firstName'])) {
      $payload['firstName'] = $user_data['firstName'];
    }
    if (!empty($user_data['lastName'])) {
      $payload['lastName'] = $user_data['lastName'];
    }

    // Attributs personnalisés
    if (!empty($user_data['attributes']) && is_array($user_data['attributes'])) {
      $payload['attributes'] = $user_data['attributes'];
    }

    $res = self::admin_request('POST', '/users', $payload);
    
    // Keycloak retourne 201 Created avec l'Location header contenant l'ID
    if ($res['code'] !== 201) {
      $error_msg = 'Keycloak create user failed (HTTP ' . $res['code'] . ')';
      if (is_array($res['data']) && !empty($res['data']['errorMessage'])) {
        $error_msg .= ': ' . $res['data']['errorMessage'];
      }
      throw new Exception($error_msg);
    }

    // Extraire l'ID depuis le Location header (si disponible) ou rechercher par email
    // Keycloak ne retourne pas l'ID dans le body, il faut le récupérer via Location header
    // ou chercher l'utilisateur par email
    $created_user = self::find_user_by_email($user_data['email']);
    if (!$created_user || empty($created_user['id'])) {
      throw new Exception('User created but ID not found');
    }

    // Ajouter le rôle account.manage-account-links si nécessaire
    try {
      self::assign_account_role($created_user['id'], 'manage-account-links');
    } catch (Exception $e) {
      // Ne pas bloquer si l'assignation du rôle échoue
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[KAP] Failed to assign manage-account-links role: ' . $e->getMessage());
      }
    }

    return (string) $created_user['id'];
  }

  /**
   * Assigne un rôle client à un utilisateur Keycloak
   * POST /users/{id}/role-mappings/clients/{client_id}
   */
  public static function assign_account_role(string $kc_user_id, string $role_name): void {
    $kc_user_id = rawurlencode($kc_user_id);
    
    // Récupérer le client "account" (client interne Keycloak pour Account Console)
    // Le client UUID pour "account" est généralement fixe, mais on peut le récupérer
    // via GET /clients?clientId=account
    $clients_res = self::admin_request('GET', '/clients?clientId=account');
    if ($clients_res['code'] < 200 || $clients_res['code'] >= 300 || !is_array($clients_res['data']) || empty($clients_res['data'][0]['id'])) {
      throw new Exception('Failed to find account client');
    }
    $account_client_id = $clients_res['data'][0]['id'];

    // Récupérer le rôle
    $roles_res = self::admin_request('GET', "/clients/{$account_client_id}/roles/{$role_name}");
    if ($roles_res['code'] < 200 || $roles_res['code'] >= 300 || !is_array($roles_res['data'])) {
      throw new Exception("Failed to find role: {$role_name}");
    }
    $role = $roles_res['data'];

    // Assigner le rôle
    $res = self::admin_request('POST', "/users/{$kc_user_id}/role-mappings/clients/{$account_client_id}", [$role]);
    if ($res['code'] < 200 || $res['code'] >= 300) {
      throw new Exception("Failed to assign role {$role_name} (HTTP {$res['code']})");
    }
  }

  /**
   * Vérifie si un utilisateur existe dans Keycloak et le crée si nécessaire
   * Retourne l'ID Keycloak de l'utilisateur
   * 
   * IMPORTANT : Pour utiliser kc_action=idp_link, l'utilisateur doit pouvoir s'authentifier
   * dans Keycloak. Si l'utilisateur est créé sans mot de passe, on ajoute une action
   * UPDATE_PASSWORD pour forcer la création d'un mot de passe lors de la première connexion.
   */
  public static function ensure_user_exists(int $wp_user_id): string {
    $user = get_userdata($wp_user_id);
    if (!$user || empty($user->user_email)) {
      throw new Exception('WordPress user not found or missing email');
    }

    // Chercher l'utilisateur par email
    $kc_user = self::find_user_by_email($user->user_email);
    
    if ($kc_user && !empty($kc_user['id'])) {
      // Utilisateur existe déjà, vérifier le rôle
      try {
        self::assign_account_role($kc_user['id'], 'manage-account-links');
      } catch (Exception $e) {
        // Le rôle existe peut-être déjà, continuer
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
          error_log('[KAP] Role assignment note: ' . $e->getMessage());
        }
      }
      
      // Si l'utilisateur n'a pas de credentials (pas de mot de passe), ajouter l'action UPDATE_PASSWORD
      // Cela permet à l'utilisateur de définir un mot de passe lors de la première connexion
      if (empty($kc_user['credentials']) || !is_array($kc_user['credentials']) || count($kc_user['credentials']) === 0) {
        try {
          // Ajouter l'action UPDATE_PASSWORD pour forcer la création d'un mot de passe
          self::admin_request('PUT', '/users/' . rawurlencode($kc_user['id']) . '/execute-actions-email', ['UPDATE_PASSWORD']);
        } catch (Exception $e) {
          // Si ça échoue, on peut essayer d'ajouter l'action directement
          try {
            $updated_user = self::get_user($kc_user['id']);
            $required_actions = $updated_user['requiredActions'] ?? [];
            if (!in_array('UPDATE_PASSWORD', $required_actions, true)) {
              $required_actions[] = 'UPDATE_PASSWORD';
              self::update_user($kc_user['id'], ['requiredActions' => $required_actions]);
            }
          } catch (Exception $e2) {
            // Ne pas bloquer si l'ajout de l'action échoue
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
              error_log('[KAP] Failed to add UPDATE_PASSWORD action: ' . $e2->getMessage());
            }
          }
        }
      }
      
      return (string) $kc_user['id'];
    }

    // Créer l'utilisateur
    $user_data = [
      'email' => $user->user_email,
      'username' => $user->user_login,
      'firstName' => $user->first_name ?: '',
      'lastName' => $user->last_name ?: '',
      'enabled' => true,
      'emailVerified' => false, // L'utilisateur devra vérifier son email s'il le souhaite
    ];

    $kc_user_id = self::create_user($user_data);
    
    // ✅ Générer un mot de passe temporaire aléatoire pour permettre l'authentification
    // L'utilisateur pourra le changer plus tard ou utiliser directement les providers
    try {
      $temp_password = wp_generate_password(24, true, true);
      self::reset_password($kc_user_id, $temp_password, true); // temporary = true
      
      // Marquer comme action requise pour forcer le changement lors de la première connexion
      $required_actions = ['UPDATE_PASSWORD'];
      self::update_user($kc_user_id, ['requiredActions' => $required_actions]);
      
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[KAP] Created Keycloak user with temporary password');
      }
    } catch (Exception $e) {
      // Si la création du mot de passe échoue, on continue quand même
      // L'utilisateur pourra peut-être utiliser les providers directement
      if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[KAP] Failed to set temporary password: ' . $e->getMessage());
      }
    }
    
    return $kc_user_id;
  }

  public static function update_user(string $kc_user_id, array $payload): void {
    $kc_user_id = rawurlencode($kc_user_id);
    $res = self::admin_request('PUT', "/users/{$kc_user_id}", $payload);
    if ($res['code'] < 200 || $res['code'] >= 300) {
      throw new Exception('Keycloak update user failed (HTTP ' . $res['code'] . ')');
    }
  }

  /**
   * Update safe: récupère l'utilisateur Keycloak puis merge le payload
   * pour éviter d'effacer des champs (email, username, enabled, etc.)
   * 
   * Sur Keycloak, un PUT peut être traité comme une représentation complète
   * et certains champs non envoyés peuvent être réinitialisés.
   */
  public static function update_user_safe(string $kc_user_id, array $partial): void {
    // Récupérer l'utilisateur existant
    $existing = self::get_user($kc_user_id);

    // Base = existant
    $merged = is_array($existing) ? $existing : [];

    // Merge attributes proprement (Keycloak attend souvent array de valeurs)
    if (isset($partial['attributes']) && is_array($partial['attributes'])) {
      $merged['attributes'] = isset($merged['attributes']) && is_array($merged['attributes']) ? $merged['attributes'] : [];
      foreach ($partial['attributes'] as $k => $v) {
        $merged['attributes'][$k] = $v;
      }
      unset($partial['attributes']);
    }

    // Merge le reste
    foreach ($partial as $k => $v) {
      $merged[$k] = $v;
    }

    // Garde-fous: jamais envoyer email vide
    if (array_key_exists('email', $merged) && (string)$merged['email'] === '') {
      unset($merged['email']);
    }

    // Certaines clés renvoyées par GET ne doivent pas être renvoyées en PUT
    // (Keycloak ignore souvent, mais on nettoie pour être propre)
    $readonly_fields = [
      'access',
      'createdTimestamp',
      'totp',
      'disableableCredentialTypes',
      'requiredActions',
      'federatedIdentities',
      'clientRoles',
      'realmRoles',
      'groups',
      'notBefore'
    ];
    foreach ($readonly_fields as $field) {
      unset($merged[$field]);
    }

    // Utiliser update_user() avec le payload complet et sécurisé
    self::update_user($kc_user_id, $merged);
  }

  /**
   * Vérifie si un mot de passe est correct pour un utilisateur Keycloak
   * Utilise le grant_type=password (Resource Owner Password Credentials Grant) pour valider les credentials
   * 
   * ⚠️ IMPORTANT : Le client Keycloak configuré (kc_client_id) doit supporter le grant_type=password.
   * Pour activer ce grant type dans Keycloak :
   * 1. Aller dans Clients → [votre_client] → Settings
   * 2. Activer "Direct Access Grants Enabled"
   * 3. Le client doit avoir le scope "openid" au minimum
   * 
   * @param string $username Email ou username de l'utilisateur Keycloak
   * @param string $password Mot de passe à vérifier
   * @return bool true si le mot de passe est correct, false sinon
   */
  public static function verify_password(string $username, string $password): bool {
    $token_url = self::base_realm_url() . '/protocol/openid-connect/token';
    $client_id = (string) self::opt('kc_client_id');
    $client_secret = (string) self::opt('kc_client_secret');
    
    $debug = defined('WP_DEBUG') && WP_DEBUG;

    // Essayer plusieurs variantes du username si c'est un email
    $usernames_to_try = [$username];
    if (is_email($username)) {
      // Si c'est un email, essayer aussi le username seul (sans @domain)
      $email_parts = explode('@', $username);
      if (!empty($email_parts[0])) {
        $usernames_to_try[] = $email_parts[0];
      }
    }

    foreach ($usernames_to_try as $try_username) {
      $body_params = [
        'grant_type' => 'password',
        'client_id' => $client_id,
        'username' => $try_username,
        'password' => $password,
        'scope' => 'openid profile email', // Ajouter le scope explicitement
      ];
      
      // Ajouter client_secret si disponible (clients confidentiels)
      if (!empty($client_secret)) {
        $body_params['client_secret'] = $client_secret;
      }

      $resp = wp_remote_post($token_url, [
        'timeout' => 10,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => http_build_query($body_params, '', '&'),
      ]);

      if (is_wp_error($resp)) {
        if ($debug && function_exists('error_log')) {
          error_log('[KAP verify_password] WP_Error: ' . $resp->get_error_message());
        }
        continue; // Essayer le prochain username
      }

      $code_status = wp_remote_retrieve_response_code($resp);
      $body = json_decode(wp_remote_retrieve_body($resp), true);

      if ($code_status === 200 && is_array($body) && !empty($body['access_token'])) {
        // Succès !
        return true;
      }
      
      // Si ce n'est pas 200, logger l'erreur en mode debug
      if ($debug && function_exists('error_log')) {
        $error_msg = is_array($body) && !empty($body['error_description']) 
          ? $body['error_description'] 
          : (is_array($body) && !empty($body['error']) ? $body['error'] : 'Unknown error');
        error_log(sprintf(
          '[KAP verify_password] Failed for username "%s": HTTP %d, Error: %s',
          $try_username,
          $code_status,
          $error_msg
        ));
      }
    }

    // Tous les usernames ont échoué
    return false;
  }

  /**
   * Renvoie l'email de vérification pour un utilisateur Keycloak
   * PUT /users/{id}/send-verify-email
   */
  public static function send_verification_email(string $kc_user_id, ?string $client_id = null, ?string $redirect_uri = null): void {
    $kc_user_id = rawurlencode($kc_user_id);
    
    $params = [];
    if ($client_id) {
      $params['client_id'] = $client_id;
    }
    if ($redirect_uri) {
      $params['redirect_uri'] = $redirect_uri;
    }
    
    $query_string = !empty($params) ? '?' . http_build_query($params) : '';
    
    $res = self::admin_request('PUT', "/users/{$kc_user_id}/send-verify-email{$query_string}");
    if ($res['code'] < 200 || $res['code'] >= 300) {
      throw new Exception('Keycloak send verification email failed (HTTP ' . $res['code'] . ')');
    }
  }

  public static function reset_password(string $kc_user_id, string $new_password, bool $temporary = false): void {
    $kc_user_id = rawurlencode($kc_user_id);
    $res = self::admin_request('PUT', "/users/{$kc_user_id}/reset-password", [
      'type' => 'password',
      'temporary' => $temporary,
      'value' => $new_password,
    ]);
    if ($res['code'] < 200 || $res['code'] >= 300) {
      throw new Exception('Keycloak reset password failed (HTTP ' . $res['code'] . ')');
    }
  }

  /**
   * Échange le code d'autorisation contre un token d'accès
   * Utilisé dans le callback pour récupérer le token et extraire le user ID
   */
  public static function exchange_code_for_token(string $code, string $code_verifier): array {
    $token_url = self::base_realm_url() . '/protocol/openid-connect/token';
    $client_id = (string) self::opt('kc_client_id');
    $redirect_uri = (string) self::opt('kc_redirect_uri');

    $resp = wp_remote_post($token_url, [
      'timeout' => 15,
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'body' => http_build_query([
        'grant_type' => 'authorization_code',
        'client_id' => $client_id,
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'code_verifier' => $code_verifier,
      ], '', '&'),
    ]);

    if (is_wp_error($resp)) {
      throw new Exception('Keycloak token exchange error: ' . $resp->get_error_message());
    }

    $code_status = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);

    if ($code_status < 200 || $code_status >= 300 || !is_array($body) || empty($body['access_token'])) {
      $error = $body['error'] ?? 'Unknown error';
      $error_desc = $body['error_description'] ?? '';
      throw new Exception('Keycloak token exchange failed: ' . $error . ($error_desc ? ' - ' . $error_desc : ''));
    }

    return $body;
  }

  /**
   * Récupère les informations utilisateur depuis le token (via userinfo endpoint)
   * Retourne le 'sub' (subject = ID utilisateur Keycloak)
   */
  public static function get_user_id_from_token(string $access_token): string {
    $userinfo_url = self::base_realm_url() . '/protocol/openid-connect/userinfo';
    
    $resp = wp_remote_get($userinfo_url, [
      'timeout' => 15,
      'headers' => [
        'Authorization' => 'Bearer ' . $access_token,
      ],
    ]);

    if (is_wp_error($resp)) {
      throw new Exception('Keycloak userinfo error: ' . $resp->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);

    if ($code < 200 || $code >= 300 || !is_array($body) || empty($body['sub'])) {
      throw new Exception('Keycloak userinfo failed: HTTP ' . $code);
    }

    return (string) $body['sub'];
  }

  /**
   * Récupère les identités fédérées (remplit external_user_id / external_username)
   * GET /users/{id}/federated-identity
   * Compatible avec Keycloak 26.2.4
   */
  public static function get_federated_identities(string $kc_user_id): array {
    $kc_user_id = rawurlencode($kc_user_id);
    $res = self::admin_request('GET', "/users/{$kc_user_id}/federated-identity");
    if ($res['code'] < 200 || $res['code'] >= 300 || !is_array($res['data'])) {
      throw new Exception('Keycloak federated identities failed (HTTP ' . $res['code'] . ')');
    }
    return $res['data'];
  }
}


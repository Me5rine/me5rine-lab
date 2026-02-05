<?php
// File: modules/game-servers/api/game-servers-rest-api.php

if (!defined('ABSPATH')) exit;

/**
 * API REST pour le module Game Servers (mod Minecraft : whitelist, update-stats, OAuth)
 */
class Game_Servers_Rest_API {
    
    private static $routes_registered = false;
    
    /**
     * Initialise l'API REST
     */
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        
        // Si rest_api_init a déjà été déclenché, enregistrer immédiatement
        if (did_action('rest_api_init')) {
            self::register_routes();
        }
        
        // Enregistrer le handler admin-post pour le callback complet (évite les problèmes d'authentification REST)
        add_action('admin_post_minecraft_oauth_complete', [__CLASS__, 'minecraft_oauth_callback_complete']);
        add_action('admin_post_nopriv_minecraft_oauth_complete', [__CLASS__, 'minecraft_oauth_callback_complete']);
    }
    
    /**
     * Enregistre les routes REST
     */
    public static function register_routes() {
        if (self::$routes_registered) {
            return;
        }
        self::$routes_registered = true;
        
        // Endpoints pour l'authentification Minecraft
        register_rest_route('admin-lab-game-servers/v1', '/minecraft/init-link', [
            'methods' => 'POST',
            'permission_callback' => '__return_true', // Vérifié dans le callback
            'callback' => [__CLASS__, 'init_minecraft_link'],
        ]);
        
        register_rest_route('admin-lab-game-servers/v1', '/minecraft/callback', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [__CLASS__, 'minecraft_oauth_callback'],
        ]);
        
        register_rest_route('admin-lab-game-servers/v1', '/minecraft/account', [
            'methods' => 'GET',
            'permission_callback' => [__CLASS__, 'check_user_logged_in'],
            'callback' => [__CLASS__, 'get_minecraft_account'],
        ]);
        
        register_rest_route('admin-lab-game-servers/v1', '/minecraft/unlink', [
            'methods' => 'POST',
            'permission_callback' => [__CLASS__, 'check_user_logged_in'],
            'callback' => [__CLASS__, 'unlink_minecraft_account'],
        ]);
        
        // Endpoint pour vérifier si un UUID Minecraft est autorisé (whitelist)
        register_rest_route('me5rine-lab/v1', '/minecraft-auth', [
            'methods' => 'GET',
            'permission_callback' => '__return_true', // Vérifié dans le callback
            'callback' => [__CLASS__, 'check_minecraft_auth'],
            'args' => [
                'uuid' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        // Valider le format UUID (avec ou sans tirets)
                        $uuid_clean = str_replace('-', '', $param);
                        return strlen($uuid_clean) === 32 && ctype_xdigit($uuid_clean);
                    },
                ],
            ],
        ]);
        
        // Endpoint pour le mod Minecraft : mettre à jour les stats du serveur (DÉPRÉCIÉ)
        // Le mod n'envoie plus les stats vers WordPress. WordPress récupère maintenant les stats depuis le serveur HTTP du mod (port 25566).
        // Cet endpoint est conservé pour compatibilité mais ne devrait plus être utilisé.
        // Enregistré sous les deux namespaces pour compatibilité (me5rine-lab/v1 et admin-lab-game-servers/v1)
        $update_stats_args = [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [__CLASS__, 'update_minecraft_server_stats_deprecated'],
            'args' => [
                'ip_address' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Adresse IP du serveur',
                ],
                'port' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Port du serveur (0 ou 25565 pour port par défaut)',
                ],
                'current_players' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Nombre de joueurs actuellement connectés',
                ],
                'max_players' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Nombre maximum de joueurs',
                ],
                'version' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Version du serveur',
                ],
                'online' => [
                    'required' => false,
                    'type' => 'boolean',
                    'description' => 'Statut en ligne du serveur',
                ],
            ],
        ];
        register_rest_route('me5rine-lab/v1', '/minecraft/update-stats', $update_stats_args);
        register_rest_route('admin-lab-game-servers/v1', '/minecraft/update-stats', $update_stats_args);
        
        // GET : récupérer les stats (front-end, rafraîchissement)
        register_rest_route('me5rine-lab/v1', '/game-servers/stats', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [__CLASS__, 'get_servers_stats'],
            'args' => [
                'ids' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'IDs des serveurs séparés par des virgules (ex: 1,2,3)',
                ],
                'status' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Filtrer par statut (active, inactive)',
                ],
            ],
        ]);
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public static function check_user_logged_in($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_authenticated',
                __('You must be logged in to perform this action.', 'me5rine-lab'),
                ['status' => 401]
            );
        }
        return true;
    }
    
    /**
     * Initialise le processus de liaison du compte Minecraft
     * Génère l'URL d'autorisation Microsoft OAuth
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function init_minecraft_link($request) {
        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_authenticated',
                __('You must be logged in to link your Minecraft account.', 'me5rine-lab'),
                ['status' => 401]
            );
        }
        
        $user_id = get_current_user_id();
        
        // Récupérer le client ID Microsoft depuis les options (à configurer dans les paramètres)
        $client_id = get_option('admin_lab_microsoft_client_id', '');
        if (empty($client_id)) {
            return new WP_Error(
                'missing_config',
                __('Microsoft OAuth client ID is not configured.', 'me5rine-lab'),
                ['status' => 500]
            );
        }
        
        // Générer un state pour la sécurité (CSRF protection)
        // Utiliser un token unique qui sera stocké avec les données OAuth
        $state = wp_generate_password(32, false);
        $state_token = wp_generate_password(32, false);
        
        // URL de retour après liaison (page d'origine)
        $return_url_raw = $request->get_param('return_url');
        $return_url = !empty($return_url_raw) ? wp_validate_redirect($return_url_raw, home_url()) : home_url();
        
        // Stocker le state avec le user_id et return_url dans un transient
        set_transient('minecraft_oauth_state_' . $state_token, [
            'state' => $state,
            'user_id' => $user_id,
            'return_url' => $return_url,
            'timestamp' => time()
        ], 600); // 10 minutes
        
        // URL de redirection après authentification
        $redirect_uri = rest_url('admin-lab-game-servers/v1/minecraft/callback');
        
        // Paramètres OAuth2 Microsoft
        // Inclure le state_token dans le state pour pouvoir le récupérer après
        $params = [
            'client_id' => $client_id,
            'response_type' => 'code',
            'redirect_uri' => $redirect_uri,
            'scope' => 'XboxLive.signin',
            'response_mode' => 'query',
            'state' => $state_token . '|' . $state, // Combiner state_token et state
        ];
        
        // Utiliser le tenant "consumers" pour les comptes Microsoft personnels
        $auth_url = 'https://login.microsoftonline.com/consumers/oauth2/v2.0/authorize?' . http_build_query($params);
        
        return new WP_REST_Response([
            'success' => true,
            'auth_url' => $auth_url,
        ], 200);
    }
    
    /**
     * Callback OAuth Microsoft pour lier le compte Minecraft
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function minecraft_oauth_callback($request) {
        $code = $request->get_param('code');
        $state_param = $request->get_param('state');
        $error = $request->get_param('error');
        
        // Extraire le state_token et le state depuis le paramètre state (pour pouvoir récupérer return_url)
        $state_parts = explode('|', $state_param, 2);
        if (count($state_parts) !== 2) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Paramètre state invalide.', 'me5rine-lab'))], home_url('/'));
            wp_redirect($error_url);
            exit;
        }
        
        $state_token = $state_parts[0];
        $state = $state_parts[1];
        
        // Récupérer les données du state
        $state_data = get_transient('minecraft_oauth_state_' . $state_token);
        if (empty($state_data) || !is_array($state_data)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Session expirée. Veuillez réessayer.', 'me5rine-lab'))], home_url('/'));
            wp_redirect($error_url);
            exit;
        }
        
        $return_url = isset($state_data['return_url']) ? $state_data['return_url'] : home_url();
        
        // Vérifier les erreurs OAuth (après récupération du return_url)
        if (!empty($error)) {
            $error_description = $request->get_param('error_description');
            $error_msg = $error . ($error_description ? ' - ' . $error_description : '');
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode($error_msg)], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Vérifier que le state correspond
        if ($state_data['state'] !== $state) {
            delete_transient('minecraft_oauth_state_' . $state_token);
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Paramètre state invalide. Veuillez réessayer.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        $expected_user_id = $state_data['user_id'];
        
        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            // Stocker les paramètres OAuth dans un transient pour les récupérer après connexion
            $oauth_data = [
                'code' => $code,
                'state_token' => $state_token,
                'expected_user_id' => $expected_user_id,
                'return_url' => $return_url,
                'timestamp' => time()
            ];
            
            // Générer un token unique pour récupérer les données après connexion
            $oauth_token = wp_generate_password(32, false);
            set_transient('minecraft_oauth_pending_' . $oauth_token, $oauth_data, 600); // 10 minutes
            
            // Rediriger vers la page de connexion avec le token
            // Utiliser admin-post.php au lieu de REST API pour éviter les problèmes d'authentification
            $callback_url = admin_url('admin-post.php?action=minecraft_oauth_complete&token=' . urlencode($oauth_token));
            $login_url = add_query_arg([
                'redirect_to' => urlencode($callback_url),
                'minecraft_oauth' => '1'
            ], wp_login_url());
            
            wp_redirect($login_url);
            exit;
        }
        
        $user_id = get_current_user_id();
        
        // Vérifier que l'utilisateur connecté correspond à celui qui a initié la requête
        if ($user_id != $expected_user_id) {
            delete_transient('minecraft_oauth_state_' . $state_token);
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('L\'utilisateur connecté ne correspond pas à celui qui a initié la liaison.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Supprimer le transient du state
        delete_transient('minecraft_oauth_state_' . $state_token);
        
        // Vérifier le state (CSRF protection)
        $stored_state = get_transient('minecraft_oauth_state_' . $user_id);
        if (empty($state) || $state !== $stored_state) {
            delete_transient('minecraft_oauth_state_' . $user_id);
            return new WP_Error(
                'invalid_state',
                __('Invalid state parameter. Please try again.', 'me5rine-lab'),
                ['status' => 400]
            );
        }
        
        delete_transient('minecraft_oauth_state_' . $user_id);
        
        if (empty($code)) {
            return new WP_Error(
                'missing_code',
                __('Authorization code is missing.', 'me5rine-lab'),
                ['status' => 400]
            );
        }
        
        // Échanger le code contre un access token
        $client_id = get_option('admin_lab_microsoft_client_id', '');
        $client_secret = get_option('admin_lab_microsoft_client_secret', '');
        $redirect_uri = rest_url('admin-lab-game-servers/v1/minecraft/callback');
        
        if (empty($client_id)) {
            return new WP_Error(
                'missing_config',
                __('Microsoft OAuth client ID is not configured.', 'me5rine-lab'),
                ['status' => 500]
            );
        }
        
        // Échanger le code contre un access token
        $token_url = 'https://login.microsoftonline.com/consumers/oauth2/v2.0/token';
        
        $token_response = wp_remote_post($token_url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            ]),
        ]);
        
        if (is_wp_error($token_response)) {
            return new WP_Error(
                'token_exchange_error',
                __('Failed to exchange authorization code for access token.', 'me5rine-lab'),
                ['status' => 500]
            );
        }
        
        $token_code = wp_remote_retrieve_response_code($token_response);
        $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
        
        if ($token_code < 200 || $token_code >= 300 || empty($token_body['access_token'])) {
            $error_msg = $token_body['error_description'] ?? 'Unknown error';
            return new WP_Error(
                'token_exchange_failed',
                __('Failed to get access token: ', 'me5rine-lab') . $error_msg,
                ['status' => 400]
            );
        }
        
        $access_token = $token_body['access_token'];
        
        // Charger la classe d'authentification Minecraft
        require_once __DIR__ . '/../functions/game-servers-minecraft-auth.php';
        require_once __DIR__ . '/../functions/game-servers-minecraft-crud.php';
        
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Callback OAuth - Demande compte Minecraft pour user_id=' . $user_id);
        }
        // Récupérer l'UUID Minecraft
        $profile = Game_Servers_Minecraft_Auth::get_minecraft_uuid_from_microsoft_token($access_token);
        
        if (is_wp_error($profile)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Callback OAuth - Échec: ' . $profile->get_error_code() . ' - ' . $profile->get_error_message());
            }
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode($profile->get_error_message())], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Callback OAuth - Profil obtenu UUID=' . ($profile['uuid'] ?? '') . ' username=' . ($profile['username'] ?? ''));
        }
        // Récupérer l'ID Microsoft depuis Keycloak (optionnel)
        $microsoft_id = null;
        if (class_exists('Game_Servers_Minecraft_Auth')) {
            $microsoft_id = Game_Servers_Minecraft_Auth::get_microsoft_id_from_keycloak($user_id);
        }
        
        // Lier le compte Minecraft
        $result = admin_lab_game_servers_link_minecraft_account(
            $user_id,
            $profile['uuid'],
            $profile['username'],
            $microsoft_id
        );
        
        if (is_wp_error($result)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode($result->get_error_message())], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Rediriger vers la page d'origine avec le système de notices
        $success_url = add_query_arg([
            'notice' => 'success',
            'notice_msg' => rawurlencode(__('Votre compte Minecraft a été lié avec succès !', 'me5rine-lab')),
        ], $return_url);
        wp_redirect($success_url);
        exit;
    }
    
    /**
     * Callback complet après connexion de l'utilisateur
     * Récupère les données OAuth stockées et continue le processus
     * Utilise admin-post.php pour éviter les problèmes d'authentification REST API
     *
     * @return void
     */
    public static function minecraft_oauth_callback_complete() {
        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
            if (empty($token)) {
                $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Token OAuth manquant.', 'me5rine-lab'))], home_url('/'));
                wp_redirect($error_url);
                exit;
            }
            
            $callback_url = admin_url('admin-post.php?action=minecraft_oauth_complete&token=' . urlencode($token));
            $login_url = add_query_arg([
                'redirect_to' => urlencode($callback_url),
                'minecraft_oauth' => '1'
            ], wp_login_url());
            
            wp_redirect($login_url);
            exit;
        }
        
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        if (empty($token)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Token OAuth manquant.', 'me5rine-lab'))], home_url('/'));
            wp_redirect($error_url);
            exit;
        }
        
        // Récupérer les données OAuth stockées
        $oauth_data = get_transient('minecraft_oauth_pending_' . $token);
        
        if (empty($oauth_data) || !is_array($oauth_data)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Les données OAuth ont expiré. Veuillez réessayer.', 'me5rine-lab'))], home_url('/'));
            wp_redirect($error_url);
            exit;
        }
        
        $return_url = isset($oauth_data['return_url']) ? $oauth_data['return_url'] : home_url();
        
        // Vérifier que les données ne sont pas trop anciennes (plus de 10 minutes)
        if (isset($oauth_data['timestamp']) && (time() - $oauth_data['timestamp']) > 600) {
            delete_transient('minecraft_oauth_pending_' . $token);
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Les données OAuth ont expiré. Veuillez réessayer.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Supprimer le transient
        delete_transient('minecraft_oauth_pending_' . $token);
        
        $user_id = get_current_user_id();
        
        // Récupérer les données du state depuis le state_token
        $state_data = get_transient('minecraft_oauth_state_' . $oauth_data['state_token']);
        if (empty($state_data) || !is_array($state_data)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Session expirée. Veuillez réessayer.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Vérifier que l'utilisateur connecté correspond à celui qui a initié la requête
        if ($user_id != $oauth_data['expected_user_id'] || $user_id != $state_data['user_id']) {
            delete_transient('minecraft_oauth_state_' . $oauth_data['state_token']);
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('L\'utilisateur connecté ne correspond pas à celui qui a initié la liaison.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Supprimer le transient du state
        delete_transient('minecraft_oauth_state_' . $oauth_data['state_token']);
        
        if (empty($oauth_data['code'])) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Code d\'autorisation manquant.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Continuer avec le processus OAuth normal
        $code = $oauth_data['code'];
        
        // Échanger le code contre un access token
        $client_id = get_option('admin_lab_microsoft_client_id', '');
        $client_secret = get_option('admin_lab_microsoft_client_secret', '');
        $redirect_uri = rest_url('admin-lab-game-servers/v1/minecraft/callback');
        
        if (empty($client_id)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Microsoft OAuth client ID non configuré.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Échanger le code contre un access token
        $token_url = 'https://login.microsoftonline.com/consumers/oauth2/v2.0/token';
        
        $token_response = wp_remote_post($token_url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            ]),
        ]);
        
        if (is_wp_error($token_response)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode(__('Erreur lors de l\'échange du code d\'autorisation.', 'me5rine-lab'))], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        $token_code = wp_remote_retrieve_response_code($token_response);
        $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
        
        if ($token_code < 200 || $token_code >= 300 || empty($token_body['access_token'])) {
            $error_msg = $token_body['error_description'] ?? __('Erreur inconnue lors de l\'obtention du token d\'accès.', 'me5rine-lab');
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode($error_msg)], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        $access_token = $token_body['access_token'];
        
        // Charger la classe d'authentification Minecraft
        require_once __DIR__ . '/../functions/game-servers-minecraft-auth.php';
        require_once __DIR__ . '/../functions/game-servers-minecraft-crud.php';
        
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Callback complete - Demande compte Minecraft pour user_id=' . $user_id);
        }
        // Récupérer l'UUID Minecraft
        $profile = Game_Servers_Minecraft_Auth::get_minecraft_uuid_from_microsoft_token($access_token);
        
        if (is_wp_error($profile)) {
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log('[Minecraft Auth] Callback complete - Échec: ' . $profile->get_error_code() . ' - ' . $profile->get_error_message());
            }
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode($profile->get_error_message())], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[Minecraft Auth] Callback complete - Profil obtenu UUID=' . ($profile['uuid'] ?? '') . ' username=' . ($profile['username'] ?? ''));
        }
        // Récupérer l'ID Microsoft depuis Keycloak (optionnel)
        $microsoft_id = null;
        if (class_exists('Game_Servers_Minecraft_Auth')) {
            $microsoft_id = Game_Servers_Minecraft_Auth::get_microsoft_id_from_keycloak($user_id);
        }
        
        // Lier le compte Minecraft
        $result = admin_lab_game_servers_link_minecraft_account(
            $user_id,
            $profile['uuid'],
            $profile['username'],
            $microsoft_id
        );
        
        if (is_wp_error($result)) {
            $error_url = add_query_arg(['notice' => 'error', 'notice_msg' => rawurlencode($result->get_error_message())], $return_url);
            wp_redirect($error_url);
            exit;
        }
        
        // Rediriger vers la page d'origine avec le système de notices
        $success_url = add_query_arg([
            'notice' => 'success',
            'notice_msg' => rawurlencode(__('Votre compte Minecraft a été lié avec succès !', 'me5rine-lab')),
        ], $return_url);
        wp_redirect($success_url);
        exit;
    }
    
    /**
     * Récupère le compte Minecraft de l'utilisateur connecté
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_minecraft_account($request) {
        $user_id = get_current_user_id();
        
        require_once __DIR__ . '/../functions/game-servers-minecraft-crud.php';
        
        $account = admin_lab_game_servers_get_minecraft_account($user_id);
        
        if (!$account) {
            return new WP_REST_Response([
                'success' => false,
                'account' => null,
            ], 200);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'account' => [
                'uuid' => $account['minecraft_uuid'],
                'username' => $account['minecraft_username'],
                'linked_at' => $account['linked_at'],
            ],
        ], 200);
    }
    
    /**
     * Supprime le lien du compte Minecraft
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function unlink_minecraft_account($request) {
        $user_id = get_current_user_id();
        
        require_once __DIR__ . '/../functions/game-servers-minecraft-crud.php';
        
        $result = admin_lab_game_servers_unlink_minecraft_account($user_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Minecraft account unlinked successfully.', 'me5rine-lab'),
        ], 200);
    }
    
    /**
     * Vérifie si un UUID Minecraft est autorisé à se connecter (whitelist)
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function check_minecraft_auth($request) {
        // Logging pour débogage - TOUJOURS logger même sans WP_DEBUG pour voir les appels
        $log_msg = '[Game Servers] check_minecraft_auth called - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        error_log($log_msg);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_msg . ' - Method: ' . $request->get_method() . ', URI: ' . $request->get_route());
        }
        
        // Vérifier l'authentification optionnelle (X-Api-Key ou Authorization: Bearer)
        $api_key_option = get_option('admin_lab_minecraft_api_key', '');
        if (!empty($api_key_option)) {
            $header_auth = $request->get_header('Authorization');
            $header_api_key = $request->get_header('X-Api-Key');
            $key = '';
            
            if ($header_auth && preg_match('/^Bearer\s+(.+)$/i', $header_auth, $m)) {
                $key = trim($m[1]);
            } elseif ($header_api_key) {
                $key = trim($header_api_key);
            }
            
            if (empty($key) || $key !== $api_key_option) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[Game Servers] check_minecraft_auth - API key mismatch or missing');
                }
                return new WP_REST_Response([
                    'error' => 'unauthorized',
                    'message' => __('Invalid or missing API key.', 'me5rine-lab'),
                ], 401);
            }
        }
        
        // Récupérer l'UUID
        $uuid = $request->get_param('uuid');
        if (empty($uuid)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Game Servers] check_minecraft_auth - UUID missing');
            }
            return new WP_REST_Response([
                'error' => 'missing_uuid',
                'message' => __('UUID parameter is required.', 'me5rine-lab'),
            ], 400);
        }
        
        // Formater l'UUID avec tirets si nécessaire
        $uuid_clean = str_replace('-', '', $uuid);
        if (strlen($uuid_clean) !== 32 || !ctype_xdigit($uuid_clean)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Game Servers] check_minecraft_auth - Invalid UUID format: ' . $uuid);
            }
            return new WP_REST_Response([
                'error' => 'invalid_uuid',
                'message' => __('Invalid UUID format.', 'me5rine-lab'),
            ], 400);
        }
        
        // Formater avec tirets (format standard: 8-4-4-4-12)
        if (strlen($uuid) === 32) {
            $uuid = substr($uuid_clean, 0, 8) . '-' . 
                    substr($uuid_clean, 8, 4) . '-' . 
                    substr($uuid_clean, 12, 4) . '-' . 
                    substr($uuid_clean, 16, 4) . '-' . 
                    substr($uuid_clean, 20, 12);
        } else {
            // S'assurer que l'UUID est bien formaté même s'il avait déjà des tirets
            $uuid = substr($uuid_clean, 0, 8) . '-' . 
                    substr($uuid_clean, 8, 4) . '-' . 
                    substr($uuid_clean, 12, 4) . '-' . 
                    substr($uuid_clean, 16, 4) . '-' . 
                    substr($uuid_clean, 20, 12);
        }
        
        // Charger les fonctions nécessaires
        require_once __DIR__ . '/../functions/game-servers-minecraft-crud.php';
        
        // Trouver l'utilisateur associé à cet UUID
        $account = admin_lab_game_servers_get_minecraft_account_by_uuid($uuid);
        
        if (!$account || empty($account['user_id'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Game Servers] check_minecraft_auth - UUID not linked: ' . $uuid);
            }
            return new WP_REST_Response([
                'allowed' => false,
            ], 200);
        }
        
        $user_id = (int) $account['user_id'];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] check_minecraft_auth - UUID linked to user_id: ' . $user_id);
        }
        
        // Vérifier si l'utilisateur a un account type avec le module "game_servers" dans ses modules actifs
        // Accepte les deux formats : game_servers et game-servers
        $check_game_servers = function($modules) {
            if (!is_array($modules)) {
                return false;
            }
            return in_array('game_servers', $modules, true) || in_array('game-servers', $modules, true);
        };
        
        $allowed = false;
        
        // 1) Via admin_lab_get_user_enabled_modules (liste dérivée des account types)
        if (function_exists('admin_lab_get_user_enabled_modules')) {
            $enabled_modules = admin_lab_get_user_enabled_modules($user_id);
            if ($check_game_servers($enabled_modules)) {
                $allowed = true;
            }
        }
        
        // 2) Fallback : meta lab_enabled_modules (synchro effectuée par admin_lab_sync_user_enabled_modules)
        if (!$allowed) {
            $lab_modules = get_user_meta($user_id, 'lab_enabled_modules', true);
            if ($check_game_servers($lab_modules)) {
                $allowed = true;
            }
        }
        
        // 3) Fallback : vérifier manuellement les account types (si user_management pas chargé ou cache vide)
        if (!$allowed) {
            $account_types = get_user_meta($user_id, 'admin_lab_account_types', true);
            if (is_array($account_types) && !empty($account_types) && function_exists('admin_lab_get_registered_account_types')) {
                $registered_types = admin_lab_get_registered_account_types();
                foreach ($account_types as $type_slug) {
                    if (isset($registered_types[$type_slug])) {
                        $type_data = $registered_types[$type_slug];
                        if (!empty($type_data['modules'])) {
                            $type_modules = $type_data['modules'];
                            if (is_string($type_modules)) {
                                $type_modules = maybe_unserialize($type_modules);
                            }
                            if ($check_game_servers($type_modules)) {
                                $allowed = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] check_minecraft_auth - Result for UUID ' . $uuid . ' (user_id: ' . $user_id . '): allowed=' . ($allowed ? 'true' : 'false'));
        }
        
        return new WP_REST_Response([
            'allowed' => (bool) $allowed,
        ], 200);
    }
    
    /**
     * Met à jour les statistiques d'un serveur Minecraft depuis le mod (DÉPRÉCIÉ)
     * 
     * @deprecated Le mod n'envoie plus les stats vers WordPress. WordPress récupère maintenant les stats depuis le serveur HTTP du mod (port 25566).
     * Utilisez plutôt le cron WordPress qui appelle automatiquement http://IP:25566/stats
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function update_minecraft_server_stats_deprecated($request) {
        // Logger que cet endpoint déprécié a été appelé
        error_log('[Game Servers] WARNING: Deprecated endpoint update_minecraft_server_stats called. The mod should not send stats to WordPress anymore. WordPress now fetches stats from the mod HTTP server.');
        
        // Appeler l'ancienne méthode pour compatibilité
        return self::update_minecraft_server_stats($request);
    }
    
    /**
     * Met à jour les statistiques d'un serveur Minecraft depuis le mod
     * Utilise l'API key pour l'authentification et IP/port pour identifier le serveur
     * 
     * @deprecated Utilisé uniquement par update_minecraft_server_stats_deprecated pour compatibilité
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function update_minecraft_server_stats($request) {
        // Logging pour débogage - TOUJOURS logger même sans WP_DEBUG pour voir les appels
        $remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $log_msg = '[Game Servers] update_minecraft_server_stats called - IP: ' . $remote_ip;
        error_log($log_msg);
        
        $body_params = $request->get_body_params();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_msg . ' - Method: ' . $request->get_method() . ', URI: ' . $request->get_route());
            error_log('[Game Servers] update_minecraft_server_stats - Body: ' . json_encode($body_params));
        } else {
            // Toujours logger les données importantes même sans WP_DEBUG
            error_log('[Game Servers] update_minecraft_server_stats - IP: ' . ($body_params['ip_address'] ?? 'N/A') . ', Port: ' . ($body_params['port'] ?? 'N/A') . ', Players: ' . ($body_params['current_players'] ?? 'N/A') . '/' . ($body_params['max_players'] ?? 'N/A'));
        }
        
        // Vérifier l'authentification via API key
        $api_key_option = get_option('admin_lab_minecraft_api_key', '');
        if (!empty($api_key_option)) {
            $header_auth = $request->get_header('Authorization');
            $header_api_key = $request->get_header('X-Api-Key');
            $key = '';
            
            if ($header_auth && preg_match('/^Bearer\s+(.+)$/i', $header_auth, $m)) {
                $key = trim($m[1]);
            } elseif ($header_api_key) {
                $key = trim($header_api_key);
            }
            
            if (empty($key) || $key !== $api_key_option) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[Game Servers] update_minecraft_server_stats - API key mismatch or missing');
                }
                return new WP_REST_Response([
                    'error' => 'unauthorized',
                    'message' => __('Invalid or missing API key.', 'me5rine-lab'),
                ], 401);
            }
        }
        
        // Récupérer IP et port
        $ip_address = sanitize_text_field($request->get_param('ip_address'));
        $port = (int) $request->get_param('port');
        
        if (empty($ip_address)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Game Servers] update_minecraft_server_stats - IP address missing');
            }
            return new WP_REST_Response([
                'error' => 'missing_ip',
                'message' => __('IP address parameter is required.', 'me5rine-lab'),
            ], 400);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] update_minecraft_server_stats - Looking for server: IP=' . $ip_address . ', port=' . $port);
        }
        
        // Trouver le serveur par IP/port
        $server = admin_lab_game_servers_get_by_ip_port($ip_address, $port);
        
        if (!$server) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Game Servers] update_minecraft_server_stats - Server not found: IP=' . $ip_address . ', port=' . $port);
            }
            return new WP_REST_Response([
                'error' => 'server_not_found',
                'message' => __('Server not found with this IP address and port.', 'me5rine-lab'),
            ], 404);
        }
        
        $server_id = (int) $server['id'];
        
        // Préparer les stats à mettre à jour
        $stats = [];
        
        if ($request->has_param('current_players')) {
            $stats['current_players'] = (int) $request->get_param('current_players');
        }
        
        if ($request->has_param('max_players')) {
            $stats['max_players'] = (int) $request->get_param('max_players');
        }
        
        if ($request->has_param('version')) {
            $stats['version'] = sanitize_text_field($request->get_param('version'));
        }
        
        // Mettre à jour le statut si le serveur est offline
        if ($request->has_param('online') && !$request->get_param('online')) {
            $stats['status'] = 'inactive';
        } elseif ($request->has_param('online') && $request->get_param('online')) {
            $stats['status'] = 'active';
        }
        
        if (empty($stats)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Game Servers] update_minecraft_server_stats - No data to update');
            }
            return new WP_REST_Response([
                'error' => 'no_data',
                'message' => __('No data to update.', 'me5rine-lab'),
            ], 400);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] update_minecraft_server_stats - Updating server_id=' . $server_id . ' with stats: ' . json_encode($stats));
        }
        
        // Mettre à jour les stats
        $result = admin_lab_game_servers_update_stats($server_id, $stats);
        
        if (is_wp_error($result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Game Servers] update_minecraft_server_stats - Error: ' . $result->get_error_message());
            }
            return $result;
        }
        
        // Toujours logger le succès (même sans WP_DEBUG)
        error_log('[Game Servers] update_minecraft_server_stats - SUCCESS: server_id=' . $server_id . ', server_name=' . ($server['name'] ?? 'N/A') . ', players=' . ($stats['current_players'] ?? 'N/A') . '/' . ($stats['max_players'] ?? 'N/A') . ', online=' . (isset($stats['status']) && $stats['status'] === 'active' ? 'yes' : 'no'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] update_minecraft_server_stats - Full response data: ' . json_encode([
                'server_id' => $server_id,
                'server_name' => $server['name'],
                'stats' => $stats,
            ]));
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Statistics updated successfully.', 'me5rine-lab'),
            'server_id' => $server_id,
            'server_name' => $server['name'],
            'updated_stats' => $stats,
            'timestamp' => current_time('mysql'),
        ], 200);
    }
    
    /**
     * GET sur push-stats : message d’info (évite rest_no_route en visitant l’URL en navigateur).
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function push_stats_get_info($request) {
        return new WP_REST_Response([
            'message' => __('Use POST to push server stats. Body: JSON with online, max, version. Optional header: Authorization: Bearer <statsSecret>.', 'me5rine-lab'),
            'method' => 'POST',
            'body_example' => ['online' => 0, 'max' => 100, 'version' => '1.21'],
        ], 200);
    }

    /**
     * Reçoit le push automatique des stats envoyé par le mod (statsPushEnabled).
     * Le mod envoie le même JSON que GET /stats : online (nombre), max, version.
     * Header optionnel : Authorization: Bearer <statsSecret> (stats_secret du serveur).
     * Identification du serveur : ip_address + port dans le body, ou REMOTE_ADDR + port 25565.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function receive_push_stats($request) {
        // IP réelle du client : derrière Cloudflare/proxy, REMOTE_ADDR est l'IP du proxy
        $remote_ip = '';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $remote_ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            $remote_ip = sanitize_text_field(trim($forwarded[0]));
        }
        if (empty($remote_ip)) {
            $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        $body = $request->get_body();
        $data = json_decode($body, true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] receive_push_stats - client IP: ' . $remote_ip . ', body: ' . $body);
        }
        
        if (!is_array($data)) {
            return new WP_REST_Response([
                'error' => 'invalid_json',
                'message' => __('Invalid JSON body.', 'me5rine-lab'),
            ], 400);
        }
        
        // Identification : body peut contenir ip_address et port, sinon IP client (Cloudflare / X-Forwarded-For / REMOTE_ADDR)
        $ip_address = isset($data['ip_address']) ? sanitize_text_field($data['ip_address']) : $remote_ip;
        $port = isset($data['port']) ? (int) $data['port'] : 25565;
        
        if (empty($ip_address)) {
            return new WP_REST_Response([
                'error' => 'missing_ip',
                'message' => __('Could not identify server (missing IP).', 'me5rine-lab'),
            ], 400);
        }
        
        // Trouver le serveur : d'abord par IP + port, puis par IP seule (fallback si port en base = 0 ou différent)
        $server = admin_lab_game_servers_get_by_ip_port($ip_address, $port, true);
        if (!$server && function_exists('admin_lab_game_servers_get_by_ip_only')) {
            $server = admin_lab_game_servers_get_by_ip_only($ip_address, true);
        }
        
        if (!$server) {
            // Toujours logger en prod pour diagnostiquer les 404 (IP/port vus par WordPress)
            error_log('[Me5rineLAB-stats-push] Server not found: ip_used=' . $ip_address . ', port_used=' . $port);
            return new WP_REST_Response([
                'error' => 'server_not_found',
                'message' => __('No server found for this IP and port.', 'me5rine-lab'),
                'data' => ['ip_used' => $ip_address, 'port_used' => $port],
            ], 404);
        }
        
        // Vérifier le secret si le serveur en a un
        $stats_secret = !empty($server['stats_secret']) ? $server['stats_secret'] : '';
        if (!empty($stats_secret)) {
            $auth_header = $request->get_header('Authorization');
            $token = '';
            if ($auth_header && preg_match('/^Bearer\s+(.+)$/i', trim($auth_header), $m)) {
                $token = trim($m[1]);
            }
            if ($token !== $stats_secret) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[Game Servers] receive_push_stats - Unauthorized: invalid or missing Bearer token');
                }
                return new WP_REST_Response([
                    'error' => 'unauthorized',
                    'message' => __('Invalid or missing Authorization Bearer token.', 'me5rine-lab'),
                ], 401);
            }
        }
        
        // Mapper le JSON du mod (online, max, version) vers notre format
        $stats = [];
        if (isset($data['online'])) {
            $stats['current_players'] = (int) $data['online'];
        }
        if (isset($data['max'])) {
            $stats['max_players'] = (int) $data['max'];
        }
        if (isset($data['version'])) {
            $stats['version'] = sanitize_text_field($data['version']);
        }
        // Le mod envoie quand il est en ligne, donc on marque actif
        $stats['status'] = 'active';
        
        if (empty($stats)) {
            return new WP_REST_Response([
                'error' => 'no_data',
                'message' => __('No stats in body (expected: online, max, version).', 'me5rine-lab'),
            ], 400);
        }
        
        $server_id = (int) $server['id'];
        $result = admin_lab_game_servers_update_stats($server_id, $stats);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] receive_push_stats - OK server_id=' . $server_id . ', players=' . ($stats['current_players'] ?? '') . '/' . ($stats['max_players'] ?? ''));
        }
        
        return new WP_REST_Response([
            'success' => true,
            'server_id' => $server_id,
            'message' => __('Stats updated.', 'me5rine-lab'),
        ], 200);
    }
    
    /**
     * Récupère les stats des serveurs pour le rafraîchissement front-end
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_servers_stats($request) {
        
        $ids_param = $request->get_param('ids');
        $status = $request->get_param('status');
        
        $args = [];
        // Filtrer par statut seulement si spécifié explicitement
        // Par défaut, retourner tous les serveurs (actifs et inactifs)
        if (!empty($status)) {
            $args['status'] = $status;
        }
        
        // Récupérer tous les serveurs ou seulement ceux spécifiés
        if (!empty($ids_param)) {
            $ids = array_map('intval', explode(',', $ids_param));
            $ids = array_filter($ids);
            
            if (!empty($ids)) {
                // Récupérer tous les serveurs puis filtrer par IDs
                $all_servers = admin_lab_game_servers_get_all($args);
                $servers = array_filter($all_servers, function($server) use ($ids) {
                    return in_array((int) $server['id'], $ids, true);
                });
                $servers = array_values($servers); // Réindexer
            } else {
                $servers = [];
            }
        } else {
            $servers = admin_lab_game_servers_get_all($args);
        }
        
        // Formater les données pour le front-end (seulement les champs nécessaires)
        $stats = [];
        foreach ($servers as $server) {
            $display_addr = function_exists('admin_lab_game_servers_get_display_address') ? admin_lab_game_servers_get_display_address($server) : ($server['ip_address'] ?? '');
            $address = function_exists('admin_lab_game_servers_format_address') ? admin_lab_game_servers_format_address($display_addr, (int) ($server['port'] ?? 0)) : $display_addr;
            $stats[] = [
                'id' => (int) $server['id'],
                'name' => $server['name'],
                'status' => $server['status'],
                'current_players' => (int) $server['current_players'],
                'max_players' => (int) $server['max_players'],
                'version' => $server['version'] ?? '',
                'online' => $server['status'] === 'active',
                'address' => $address,
            ];
        }
        
        return new WP_REST_Response([
            'success' => true,
            'servers' => $stats,
            'timestamp' => current_time('mysql'),
        ], 200);
    }
    
}

// Initialiser l'API REST (les routes sont enregistrées UNIQUEMENT sur le hook rest_api_init)
Game_Servers_Rest_API::init();
add_action('rest_api_init', [Game_Servers_Rest_API::class, 'register_routes'], 5);


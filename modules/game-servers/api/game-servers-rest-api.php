<?php
// File: modules/game-servers/api/game-servers-rest-api.php

if (!defined('ABSPATH')) exit;

/**
 * API REST pour recevoir les statistiques des serveurs depuis les plugins serveur
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
    }
    
    /**
     * Enregistre les routes REST
     */
    public static function register_routes() {
        if (self::$routes_registered) {
            return;
        }
        self::$routes_registered = true;
        
        // Endpoint pour recevoir les stats d'un serveur
        register_rest_route('admin-lab-game-servers/v1', '/update-stats', [
            'methods' => 'POST',
            'permission_callback' => [__CLASS__, 'verify_token'],
            'callback' => [__CLASS__, 'update_server_stats'],
            'args' => [
                'server_token' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Token d\'authentification du serveur',
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
        ]);
        
        // Endpoint pour vérifier la connexion
        register_rest_route('admin-lab-game-servers/v1', '/ping', [
            'methods' => 'GET',
            'permission_callback' => [__CLASS__, 'verify_token'],
            'callback' => [__CLASS__, 'ping'],
        ]);
        
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
    }
    
    /**
     * Vérifie le token d'authentification
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public static function verify_token($request) {
        $token = $request->get_header('X-Server-Token');
        
        // Si pas dans le header, chercher dans les paramètres
        if (empty($token)) {
            $token = $request->get_param('server_token');
        }
        
        if (empty($token)) {
            return new WP_Error(
                'missing_token',
                __('Authentication token missing.', 'me5rine-lab'),
                ['status' => 401]
            );
        }
        
        // Chercher le serveur avec ce token
        global $wpdb;
        // Global table: shared across all sites
        $table_name = admin_lab_getTable('game_servers', true);
        
        $server = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, provider FROM {$table_name} WHERE provider_server_id = %s AND status = 'active'",
                $token
            ),
            ARRAY_A
        );
        
        if (!$server) {
            return new WP_Error(
                'invalid_token',
                __('Invalid authentication token or inactive server.', 'me5rine-lab'),
                ['status' => 403]
            );
        }
        
        // Stocker l'ID du serveur dans la requête pour l'utiliser dans le callback
        $request->set_param('_server_id', $server['id']);
        $request->set_param('_server_name', $server['name']);
        
        return true;
    }
    
    /**
     * Met à jour les statistiques d'un serveur
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function update_server_stats($request) {
        $server_id = $request->get_param('_server_id');
        
        if (!$server_id) {
            return new WP_Error(
                'server_not_found',
                __('Server not found.', 'me5rine-lab'),
                ['status' => 404]
            );
        }
        
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
            return new WP_Error(
                'no_data',
                __('No data to update.', 'me5rine-lab'),
                ['status' => 400]
            );
        }
        
        $result = admin_lab_game_servers_update_stats($server_id, $stats);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Statistics updated successfully.', 'me5rine-lab'),
            'server_id' => $server_id,
        ], 200);
    }
    
    /**
     * Endpoint ping pour vérifier la connexion
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function ping($request) {
        $server_name = $request->get_param('_server_name');
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Connection established successfully.', 'me5rine-lab'),
            'server' => $server_name,
            'timestamp' => current_time('mysql'),
        ], 200);
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
        $state = wp_generate_password(32, false);
        set_transient('minecraft_oauth_state_' . $user_id, $state, 600); // 10 minutes
        
        // URL de redirection après authentification
        $redirect_uri = rest_url('admin-lab-game-servers/v1/minecraft/callback');
        
        // Paramètres OAuth2 Microsoft
        $params = [
            'client_id' => $client_id,
            'response_type' => 'code',
            'redirect_uri' => $redirect_uri,
            'scope' => 'XboxLive.signin',
            'response_mode' => 'query',
            'state' => $state,
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
        $state = $request->get_param('state');
        $error = $request->get_param('error');
        
        // Vérifier les erreurs OAuth
        if (!empty($error)) {
            $error_description = $request->get_param('error_description');
            return new WP_Error(
                'oauth_error',
                __('OAuth error: ', 'me5rine-lab') . $error . ($error_description ? ' - ' . $error_description : ''),
                ['status' => 400]
            );
        }
        
        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            // Rediriger vers la page de connexion
            wp_redirect(wp_login_url(home_url('/wp-json/admin-lab-game-servers/v1/minecraft/callback?' . $_SERVER['QUERY_STRING'])));
            exit;
        }
        
        $user_id = get_current_user_id();
        
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
        
        // Récupérer l'UUID Minecraft
        $profile = Game_Servers_Minecraft_Auth::get_minecraft_uuid_from_microsoft_token($access_token);
        
        if (is_wp_error($profile)) {
            // Rediriger vers une page d'erreur
            $error_url = home_url('/?minecraft_link_error=' . urlencode($profile->get_error_message()));
            wp_redirect($error_url);
            exit;
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
            $error_url = home_url('/?minecraft_link_error=' . urlencode($result->get_error_message()));
            wp_redirect($error_url);
            exit;
        }
        
        // Rediriger vers une page de succès
        $success_url = home_url('/?minecraft_link_success=1');
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
    
}

// Initialiser l'API REST
Game_Servers_Rest_API::init();


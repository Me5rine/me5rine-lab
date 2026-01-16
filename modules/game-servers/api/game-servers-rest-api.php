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
    
}

// Initialiser l'API REST
Game_Servers_Rest_API::init();


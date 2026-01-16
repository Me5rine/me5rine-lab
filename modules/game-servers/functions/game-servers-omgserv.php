<?php
// File: modules/game-servers/functions/game-servers-omgserv.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère le token d'authentification pour un serveur
 * Le token est stocké dans provider_server_id et sert à authentifier les requêtes depuis le plugin serveur
 *
 * @param int $server_id ID du serveur dans WordPress
 * @return string|WP_Error Token d'authentification
 */
function admin_lab_game_servers_get_server_token($server_id) {
    $server = admin_lab_game_servers_get_by_id($server_id);
    
    if (!$server) {
        return new WP_Error('server_not_found', __('Server not found.', 'me5rine-lab'));
    }
    
    // Si le token n'existe pas encore, le générer
    if (empty($server['provider_server_id'])) {
        // Générer un token unique basé sur l'ID, le nom et une clé secrète
        $secret = get_option('admin_lab_game_servers_secret_key', wp_generate_password(32, false));
        
        // Si la clé secrète n'existe pas, la créer
        if (get_option('admin_lab_game_servers_secret_key') === false) {
            update_option('admin_lab_game_servers_secret_key', $secret);
        }
        
        // Le token est l'ID du serveur + hash pour sécurité
        $token_data = $server_id . '|' . $server['name'] . '|' . $secret;
        $token = hash('sha256', $token_data);
        
        // Sauvegarder le token dans provider_server_id
        admin_lab_game_servers_update($server_id, [
            'provider_server_id' => $token
        ]);
        
        return $token;
    }
    
    return $server['provider_server_id'];
}

/**
 * Récupère l'URL de l'endpoint REST pour un serveur
 *
 * @param int $server_id
 * @return string URL de l'endpoint
 */
function admin_lab_game_servers_get_endpoint_url($server_id) {
    return rest_url('admin-lab-game-servers/v1/update-stats');
}

/**
 * Met à jour les statistiques d'un serveur depuis OMGserv
 *
 * @param int $server_id ID du serveur dans la base de données
 * @return bool|WP_Error
 */
function admin_lab_game_servers_omgserv_sync_stats($server_id) {
    $server = admin_lab_game_servers_get_by_id($server_id);
    
    if (!$server) {
        return new WP_Error('server_not_found', __('Server not found.', 'me5rine-lab'));
    }
    
    // Cette fonction n'est plus utilisée car les stats sont maintenant envoyées
    // directement depuis le plugin serveur via l'API REST
    // Conservée pour compatibilité mais ne fait rien
    return new WP_Error('deprecated', __('This function is deprecated. Statistics are now sent directly from the server plugin.', 'me5rine-lab'));
}

/**
 * Synchronise tous les serveurs OMGserv
 *
 * @return array Résultats de la synchronisation
 */
function admin_lab_game_servers_omgserv_sync_all() {
    // Récupérer tous les serveurs actifs avec provider custom
    $all_servers = admin_lab_game_servers_get_all(['status' => 'active']);
    $servers = array_filter($all_servers, function($server) {
        return $server['provider'] === 'custom';
    });
    
    $results = [
        'success' => 0,
        'errors' => 0,
        'messages' => [],
    ];
    
    foreach ($servers as $server) {
        $result = admin_lab_game_servers_omgserv_sync_stats($server['id']);
        
        if (is_wp_error($result)) {
            $results['errors']++;
            $results['messages'][] = sprintf(
                __('Server %s : %s', 'me5rine-lab'),
                $server['name'],
                $result->get_error_message()
            );
        } else {
            $results['success']++;
        }
    }
    
    return $results;
}


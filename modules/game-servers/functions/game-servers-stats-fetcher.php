<?php
// File: modules/game-servers/functions/game-servers-stats-fetcher.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère les stats d'un serveur depuis le mod Minecraft (HTTP server sur port 25566)
 *
 * @param int $server_id ID du serveur
 * @return array|WP_Error Stats récupérées ou erreur
 */
function admin_lab_game_servers_fetch_stats_from_mod($server_id) {
    $server = admin_lab_game_servers_get_by_id($server_id);
    
    if (!$server) {
        return new WP_Error('server_not_found', __('Server not found.', 'me5rine-lab'));
    }
    
    $ip = $server['ip_address'];
    $stats_port = !empty($server['stats_port']) ? (int) $server['stats_port'] : 25566;
    $stats_secret = !empty($server['stats_secret']) ? $server['stats_secret'] : '';
    
    // Construire l'URL : http://IP:PORT/stats
    $url = 'http://' . $ip . ':' . $stats_port . '/stats';
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Game Servers] fetch_stats_from_mod - Fetching from: ' . $url . ' (server_id: ' . $server_id . ')');
    }
    
    $args = [
        'timeout' => 10,
        'sslverify' => false,
    ];
    
    // Ajouter le secret si défini (header Authorization: Bearer)
    if (!empty($stats_secret)) {
        $args['headers'] = [
            'Authorization' => 'Bearer ' . $stats_secret,
        ];
    }
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] fetch_stats_from_mod - Error: ' . $error_msg);
        }
        return new WP_Error('fetch_error', sprintf(__('Error fetching stats: %s', 'me5rine-lab'), $error_msg));
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        $error_msg = sprintf(__('HTTP %d: %s', 'me5rine-lab'), $status_code, wp_remote_retrieve_response_message($response));
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] fetch_stats_from_mod - HTTP Error: ' . $error_msg);
        }
        return new WP_Error('http_error', $error_msg);
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data || !is_array($data)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] fetch_stats_from_mod - Invalid JSON: ' . $body);
        }
        return new WP_Error('invalid_json', __('Invalid JSON response from mod.', 'me5rine-lab'));
    }
    
    // Mapper les champs du mod vers notre format
    $stats = [];
    
    // online = nombre de joueurs connectés
    if (isset($data['online'])) {
        $stats['current_players'] = (int) $data['online'];
    }
    
    // max = places max
    if (isset($data['max'])) {
        $stats['max_players'] = (int) $data['max'];
    }
    
    // version = version du serveur
    if (isset($data['version'])) {
        $stats['version'] = sanitize_text_field($data['version']);
    }
    
    // Déterminer le statut (online/offline) - si online > 0 ou si le serveur répond, considérer comme actif
    // On peut aussi vérifier si le serveur répond (si on arrive ici, il répond)
    $stats['status'] = 'active';
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Game Servers] fetch_stats_from_mod - Success: ' . json_encode($stats));
    }
    
    return $stats;
}

/**
 * Met à jour les stats d'un serveur depuis le mod
 *
 * @param int $server_id ID du serveur
 * @return bool|WP_Error
 */
function admin_lab_game_servers_update_stats_from_mod($server_id) {
    $stats = admin_lab_game_servers_fetch_stats_from_mod($server_id);
    
    if (is_wp_error($stats)) {
        // Si le serveur ne répond pas, mettre le statut à 'inactive'
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] update_stats_from_mod - Server not responding, setting status to inactive (server_id: ' . $server_id . ')');
        }
        $offline_stats = [
            'status' => 'inactive',
            'current_players' => 0,
        ];
        admin_lab_game_servers_update_stats($server_id, $offline_stats);
        return $stats; // Retourner l'erreur originale
    }
    
    if (empty($stats)) {
        return new WP_Error('no_stats', __('No stats to update.', 'me5rine-lab'));
    }
    
    return admin_lab_game_servers_update_stats($server_id, $stats);
}

/**
 * Met à jour les stats de tous les serveurs depuis le mod
 * Vérifie tous les serveurs (actifs et inactifs) pour détecter les changements de statut
 *
 * @return array Résultats par serveur
 */
function admin_lab_game_servers_update_all_stats_from_mod() {
    // Vérifier tous les serveurs, pas seulement ceux avec status='active'
    // Cela permet de détecter si un serveur inactif redémarre et de le remettre à 'active'
    $servers = admin_lab_game_servers_get_all();
    $results = [];
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Game Servers] update_all_stats_from_mod - Updating ' . count($servers) . ' servers');
    }
    
    foreach ($servers as $server) {
        $server_id = (int) $server['id'];
        $result = admin_lab_game_servers_update_stats_from_mod($server_id);
        
        $results[$server_id] = [
            'server_id' => $server_id,
            'server_name' => $server['name'],
            'success' => !is_wp_error($result),
            'error' => is_wp_error($result) ? $result->get_error_message() : null,
        ];
    }
    
    return $results;
}

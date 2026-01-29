<?php
// File: modules/game-servers/functions/game-servers-helpers.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère le jeu associé depuis l'API ClicksNGames
 *
 * @param int $game_id
 * @return array|WP_Error
 */
function admin_lab_game_servers_get_game($game_id) {
    if (empty($game_id)) {
        return new WP_Error('invalid_game_id', __('Invalid game ID.', 'me5rine-lab'));
    }

    if (!function_exists('admin_lab_clicksngames_get_game_by_id')) {
        return new WP_Error('api_not_available', __('Configure ClicksNGames API in Me5rine LAB → Settings → API Keys.', 'me5rine-lab'));
    }
    return admin_lab_clicksngames_get_game_by_id($game_id);
}

/**
 * Formate l'adresse IP avec le port
 *
 * @param string $ip
 * @param int    $port
 * @return string
 */
function admin_lab_game_servers_format_address($ip, $port = 0) {
    if (empty($port) || $port == 25565) { // Port par défaut Minecraft
        return $ip;
    }
    return $ip . ':' . $port;
}

/**
 * Récupère le statut d'un serveur avec badge HTML (doc FRONT_CSS.md : admin_lab_render_status).
 *
 * @param string $status 'active' ou autre
 * @return string
 */
function admin_lab_game_servers_get_status_badge($status) {
    if ($status === 'active') {
        return admin_lab_render_status(__('Online', 'me5rine-lab'), 'success');
    }
    return admin_lab_render_status(__('Offline', 'me5rine-lab'), 'error');
}

/**
 * Récupère le pourcentage de remplissage d'un serveur
 *
 * @param int $current
 * @param int $max
 * @return float
 */
function admin_lab_game_servers_get_fill_percentage($current, $max) {
    if ($max <= 0) {
        return 0;
    }
    return min(100, ($current / $max) * 100);
}

/**
 * Retourne l'URL de la page du serveur
 * Priorité : URL personnalisée du serveur > Pages créées automatiquement > Fallback
 *
 * @param int $server_id
 * @return string
 */
function admin_lab_game_servers_get_server_page_url($server_id) {
    // Priorité 1 : URL personnalisée du serveur (si définie)
    $server = admin_lab_game_servers_get_by_id($server_id);
    if ($server && !empty($server['page_url'])) {
        return esc_url($server['page_url']);
    }
    
    // Priorité 2 : Pages créées automatiquement
    $page_id = get_option('game_servers_page_game-servers');
    if (!$page_id || !get_post_status($page_id)) {
        $page_id = get_option('game_servers_page_minecraft-servers');
    }
    if ($page_id && get_post_status($page_id)) {
        return get_permalink($page_id) . '#server-' . (int) $server_id;
    }
    
    // Fallback : ancre sur la page d'accueil
    return home_url('/#server-' . (int) $server_id);
}

/**
 * Parse les tags d'un serveur
 *
 * @param string $tags Tags séparés par des virgules
 * @return array
 */
function admin_lab_game_servers_parse_tags($tags) {
    if (empty($tags)) {
        return [];
    }
    
    $tags_array = explode(',', $tags);
    return array_map('trim', $tags_array);
}


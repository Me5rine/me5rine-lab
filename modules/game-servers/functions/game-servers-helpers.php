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
    
    // Utiliser la fonction du module comparator si disponible
    if (function_exists('admin_lab_comparator_get_game_by_id')) {
        $game = admin_lab_comparator_get_game_by_id($game_id);
        
        if (is_wp_error($game)) {
            return $game;
        }
        
        // Normaliser la réponse Strapi
        if (isset($game['data'])) {
            $game = $game['data'];
        }
        
        if (isset($game['attributes'])) {
            $attrs = $game['attributes'];
            return [
                'id' => $game['id'] ?? $game_id,
                'name' => $attrs['name'] ?? '',
                'slug' => $attrs['slug'] ?? '',
                'logo' => isset($attrs['logo']) && isset($attrs['logo']['data']) 
                    ? ($attrs['logo']['data']['attributes']['url'] ?? '') 
                    : '',
            ];
        }
        
        return $game;
    }
    
    return new WP_Error('api_not_available', __('ClicksNGames API is not available.', 'me5rine-lab'));
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
 * Récupère le statut d'un serveur avec badge HTML
 *
 * @param string $status
 * @return string
 */
function admin_lab_game_servers_get_status_badge($status) {
    $statuses = [
        'active' => ['label' => __('Active', 'me5rine-lab'), 'class' => 'status-active'],
        'inactive' => ['label' => __('Inactive', 'me5rine-lab'), 'class' => 'status-inactive'],
    ];
    
    $info = $statuses[$status] ?? $statuses['inactive'];
    
    return sprintf(
        '<span class="game-server-status %s">%s</span>',
        esc_attr($info['class']),
        esc_html($info['label'])
    );
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


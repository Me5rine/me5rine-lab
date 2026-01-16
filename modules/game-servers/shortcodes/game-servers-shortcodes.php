<?php
// File: modules/game-servers/shortcodes/game-servers-shortcodes.php

if (!defined('ABSPATH')) exit;

/**
 * Enregistre les shortcodes pour les serveurs de jeux
 */
function admin_lab_game_servers_register_shortcodes() {
    add_shortcode('game_servers_list', 'admin_lab_game_servers_shortcode_list');
    add_shortcode('game_server', 'admin_lab_game_servers_shortcode_single');
}

/**
 * Shortcode pour afficher la liste des serveurs
 *
 * @param array $atts {
 *   @type string $status Filtrer par statut (active, inactive)
 *   @type int    $game_id Filtrer par jeu
 *   @type string $orderby Champ de tri
 *   @type string $order Direction (ASC, DESC)
 *   @type int    $limit Nombre de serveurs à afficher
 *   @type string $template Template à utiliser (default, compact)
 * }
 */
function admin_lab_game_servers_shortcode_list($atts) {
    $atts = shortcode_atts([
        'status' => 'active',
        'game_id' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
        'limit' => 0,
        'template' => 'default',
    ], $atts, 'game_servers_list');
    
    $args = [
        'status' => $atts['status'],
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
    ];
    
    if (!empty($atts['game_id'])) {
        $args['game_id'] = (int) $atts['game_id'];
    }
    
    if (!empty($atts['limit'])) {
        $args['limit'] = (int) $atts['limit'];
    }
    
    $servers = admin_lab_game_servers_get_all($args);
    
    if (empty($servers)) {
        return '<p>' . __('No servers found.', 'me5rine-lab') . '</p>';
    }
    
    ob_start();
    
    $template = sanitize_file_name($atts['template']);
    $template_file = __DIR__ . '/../templates/list-' . $template . '.php';
    
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        // Template par défaut
        include __DIR__ . '/../templates/list-default.php';
    }
    
    return ob_get_clean();
}

/**
 * Shortcode pour afficher un serveur unique
 *
 * @param array $atts {
 *   @type int $id ID du serveur
 *   @type string $template Template à utiliser
 * }
 */
function admin_lab_game_servers_shortcode_single($atts) {
    $atts = shortcode_atts([
        'id' => 0,
        'template' => 'default',
    ], $atts, 'game_server');
    
    $server_id = (int) $atts['id'];
    if ($server_id <= 0) {
        return '<p>' . __('Invalid server ID.', 'me5rine-lab') . '</p>';
    }
    
    $server = admin_lab_game_servers_get_by_id($server_id);
    
    if (!$server) {
        return '<p>' . __('Server not found.', 'me5rine-lab') . '</p>';
    }
    
    ob_start();
    
    $template = sanitize_file_name($atts['template']);
    $template_file = __DIR__ . '/../templates/single-' . $template . '.php';
    
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        // Template par défaut
        include __DIR__ . '/../templates/single-default.php';
    }
    
    return ob_get_clean();
}


<?php
// File: modules/game-servers/functions/game-servers-crud.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère tous les serveurs
 *
 * @param array $args {
 *   @type string $status Status du serveur (active, inactive)
 *   @type int    $game_id ID du jeu associé
 *   @type string $orderby Champ de tri
 *   @type string $order Direction du tri (ASC, DESC)
 *   @type int    $limit Nombre de résultats
 *   @type int    $offset Offset pour la pagination
 * }
 * @return array Liste des serveurs
 */
function admin_lab_game_servers_get_all($args = []) {
    global $wpdb;
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Game Servers] get_all called with args: ' . json_encode($args));
    }
    
    $defaults = [
        'status' => '',
        'game_id' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
        'limit' => 0,
        'offset' => 0,
    ];
    
    $args = wp_parse_args($args, $defaults);
    // Global table: shared across all sites
    $table_name = admin_lab_getTable('game_servers', true);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Game Servers] get_all - table_name: ' . $table_name);
    }
    
    // Vérifier que la table existe
    $table_exists = ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name);
    if (!$table_exists) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Servers] get_all - ERROR: Table does not exist: ' . $table_name);
        }
        return [];
    }
    
    $where = ['1=1'];
    $values = [];
    
    if (!empty($args['status'])) {
        $where[] = 'status = %s';
        $values[] = $args['status'];
    }
    
    if (!empty($args['game_id'])) {
        $where[] = 'game_id = %d';
        $values[] = (int) $args['game_id'];
    }
    
    if (!empty($args['provider'])) {
        $where[] = 'provider = %s';
        $values[] = $args['provider'];
    }
    
    $where_clause = implode(' AND ', $where);
    
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    if (!$orderby) {
        $orderby = 'name ASC';
    }
    
    $sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$orderby}";
    
    if ($args['limit'] > 0) {
        $sql .= $wpdb->prepare(' LIMIT %d', $args['limit']);
        if ($args['offset'] > 0) {
            $sql .= $wpdb->prepare(' OFFSET %d', $args['offset']);
        }
    }
    
    if (!empty($values)) {
        $sql = $wpdb->prepare($sql, $values);
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Game Servers] get_all - SQL: ' . $sql);
    }
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if ($wpdb->last_error) {
            error_log('[Game Servers] get_all - SQL ERROR: ' . $wpdb->last_error);
        }
        error_log('[Game Servers] get_all - Results count: ' . (is_array($results) ? count($results) : 'non-array'));
    }
    
    return $results;
}

/**
 * Récupère un serveur par ID
 *
 * @param int $server_id
 * @return array|null
 */
function admin_lab_game_servers_get_by_id($server_id) {
    global $wpdb;
    // Global table: shared across all sites
    $table_name = admin_lab_getTable('game_servers', true);
    
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $server_id),
        ARRAY_A
    );
}

/**
 * Récupère un serveur par IP et port
 *
 * @param string $ip_address
 * @param int    $port
 * @return array|null
 */
/**
 * Récupère un serveur par IP et port.
 *
 * @param string $ip_address Adresse IP du serveur
 * @param int    $port       Port du serveur (0 = port par défaut Minecraft)
 * @param bool   $any_status Si true, ne filtre pas par status (pour recevoir les push d'un serveur inactif)
 * @return array|null
 */
function admin_lab_game_servers_get_by_ip_port($ip_address, $port = 0, $any_status = false) {
    global $wpdb;
    $table_name = admin_lab_getTable('game_servers', true);
    
    $where = 'ip_address = %s';
    $values = [$ip_address];
    
    if ($port > 0) {
        $where .= ' AND port = %d';
        $values[] = $port;
    } else {
        $where .= ' AND (port = 0 OR port = 25565)';
    }
    
    if (!$any_status) {
        $where .= ' AND status = %s';
        $values[] = 'active';
    }
    
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE {$where} LIMIT 1", $values),
        ARRAY_A
    );
}

/**
 * Crée un nouveau serveur
 *
 * @param array $data Données du serveur
 * @return int|WP_Error ID du serveur créé ou erreur
 */
function admin_lab_game_servers_create($data) {
    global $wpdb;
    // Global table: shared across all sites
    $table_name = admin_lab_getTable('game_servers', true);
    
    $defaults = [
        'name' => '',
        'description' => '',
        'game_id' => 0,
        'ip_address' => '',
        'port' => 0,
        'provider' => '',
        'provider_server_id' => '',
        'status' => 'active',
        'max_players' => 0,
        'current_players' => 0,
        'version' => '',
        'tags' => '',
        'banner_url' => '',
        'logo_url' => '',
        'enable_subscriber_whitelist' => 0,
        'stats_port' => 25566,
        'stats_secret' => '',
    ];
    
    $data = wp_parse_args($data, $defaults);
    
    // Validation
    if (empty($data['name'])) {
        return new WP_Error('invalid_data', __('Server name is required.', 'me5rine-lab'));
    }
    
    if (empty($data['ip_address'])) {
        return new WP_Error('invalid_data', __('IP address is required.', 'me5rine-lab'));
    }
    
    // Nettoyage des données
    $insert_data = [
        'name' => sanitize_text_field($data['name']),
        'description' => wp_kses_post($data['description']),
        'game_id' => (int) $data['game_id'],
        'ip_address' => sanitize_text_field($data['ip_address']),
        'port' => (int) $data['port'],
        'provider' => sanitize_text_field($data['provider']),
        'provider_server_id' => sanitize_text_field($data['provider_server_id']),
        'status' => in_array($data['status'], ['active', 'inactive'], true) ? $data['status'] : 'active',
        'max_players' => (int) $data['max_players'],
        'current_players' => (int) $data['current_players'],
        'version' => sanitize_text_field($data['version']),
        'tags' => sanitize_text_field($data['tags']),
        'banner_url' => esc_url_raw($data['banner_url']),
        'logo_url' => esc_url_raw($data['logo_url']),
        'page_url' => !empty($data['page_url']) ? esc_url_raw($data['page_url']) : '',
        'enable_subscriber_whitelist' => isset($data['enable_subscriber_whitelist']) ? (int) $data['enable_subscriber_whitelist'] : 0,
        'stats_port' => isset($data['stats_port']) ? (int) $data['stats_port'] : 25566,
        'stats_secret' => isset($data['stats_secret']) ? sanitize_text_field($data['stats_secret']) : '',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ];
    
    $result = $wpdb->insert($table_name, $insert_data);
    
    if ($result === false) {
        return new WP_Error('db_error', __('Error creating server.', 'me5rine-lab'));
    }
    
    return $wpdb->insert_id;
}

/**
 * Met à jour un serveur
 *
 * @param int   $server_id
 * @param array $data Données à mettre à jour
 * @return bool|WP_Error
 */
function admin_lab_game_servers_update($server_id, $data) {
    global $wpdb;
    // Global table: shared across all sites
    $table_name = admin_lab_getTable('game_servers', true);
    
    $server_id = (int) $server_id;
    if ($server_id <= 0) {
        return new WP_Error('invalid_id', __('Invalid server ID.', 'me5rine-lab'));
    }
    
    // Vérifier que le serveur existe
    $existing = admin_lab_game_servers_get_by_id($server_id);
    if (!$existing) {
        return new WP_Error('not_found', __('Server not found.', 'me5rine-lab'));
    }
    
    $update_data = [];
    
    $allowed_fields = [
        'name', 'description', 'game_id', 'ip_address', 'port',
        'provider', 'provider_server_id', 'status', 'max_players',
        'current_players', 'version', 'tags', 'banner_url', 'logo_url',
        'page_url', 'enable_subscriber_whitelist', 'stats_port', 'stats_secret'
    ];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            switch ($field) {
                case 'name':
                case 'ip_address':
                case 'provider':
                case 'provider_server_id':
                case 'version':
                case 'tags':
                    $update_data[$field] = sanitize_text_field($data[$field]);
                    break;
                case 'description':
                    $update_data[$field] = wp_kses_post($data[$field]);
                    break;
                case 'game_id':
                case 'port':
                case 'max_players':
                case 'current_players':
                    $update_data[$field] = (int) $data[$field];
                    break;
                case 'status':
                    $update_data[$field] = in_array($data[$field], ['active', 'inactive'], true) ? $data[$field] : 'active';
                    break;
                case 'banner_url':
                case 'logo_url':
                case 'page_url':
                    $update_data[$field] = esc_url_raw($data[$field]);
                    break;
                case 'enable_subscriber_whitelist':
                    $update_data[$field] = isset($data[$field]) ? (int) $data[$field] : 0;
                    break;
                case 'stats_port':
                    $update_data[$field] = isset($data[$field]) ? (int) $data[$field] : 25566;
                    break;
                case 'stats_secret':
                    $update_data[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
                    break;
            }
        }
    }
    
    if (empty($update_data)) {
        return new WP_Error('no_data', __('No data to update.', 'me5rine-lab'));
    }
    
    $update_data['updated_at'] = current_time('mysql');
    
    $result = $wpdb->update(
        $table_name,
        $update_data,
        ['id' => $server_id],
        null,
        ['%d']
    );
    
    if ($result === false) {
        return new WP_Error('db_error', __('Error updating server.', 'me5rine-lab'));
    }
    
    return true;
}

/**
 * Supprime un serveur
 *
 * @param int $server_id
 * @return bool|WP_Error
 */
function admin_lab_game_servers_delete($server_id) {
    global $wpdb;
    // Global table: shared across all sites
    $table_name = admin_lab_getTable('game_servers', true);
    
    $server_id = (int) $server_id;
    if ($server_id <= 0) {
        return new WP_Error('invalid_id', __('Invalid server ID.', 'me5rine-lab'));
    }
    
    $result = $wpdb->delete($table_name, ['id' => $server_id], ['%d']);
    
    if ($result === false) {
        return new WP_Error('db_error', __('Error deleting server.', 'me5rine-lab'));
    }
    
    return true;
}

/**
 * Met à jour les statistiques d'un serveur (joueurs, etc.)
 *
 * @param int   $server_id
 * @param array $stats {
 *   @type int $current_players
 *   @type int $max_players
 *   @type string $version
 * }
 * @return bool|WP_Error
 */
function admin_lab_game_servers_update_stats($server_id, $stats) {
    $update_data = [];
    
    if (isset($stats['current_players'])) {
        $update_data['current_players'] = (int) $stats['current_players'];
    }
    
    if (isset($stats['max_players'])) {
        $update_data['max_players'] = (int) $stats['max_players'];
    }
    
    if (isset($stats['version'])) {
        $update_data['version'] = sanitize_text_field($stats['version']);
    }
    
    // Permettre la mise à jour du statut (active/inactive)
    if (isset($stats['status'])) {
        $update_data['status'] = in_array($stats['status'], ['active', 'inactive'], true) ? $stats['status'] : 'active';
    }
    
    if (empty($update_data)) {
        return new WP_Error('no_data', __('No statistics to update.', 'me5rine-lab'));
    }
    
    return admin_lab_game_servers_update($server_id, $update_data);
}


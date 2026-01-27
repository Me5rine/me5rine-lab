<?php
// File: modules/game-servers/functions/game-servers-minecraft-crud.php

if (!defined('ABSPATH')) exit;

/**
 * Récupère le compte Minecraft d'un utilisateur
 *
 * @param int $user_id ID de l'utilisateur WordPress
 * @return array|null Données du compte Minecraft ou null
 */
function admin_lab_game_servers_get_minecraft_account($user_id) {
    global $wpdb;
    
    $table_name = admin_lab_getTable('minecraft_accounts', true);
    
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d", $user_id),
        ARRAY_A
    );
}

/**
 * Récupère un compte Minecraft par UUID
 *
 * @param string $uuid UUID Minecraft
 * @return array|null Données du compte Minecraft ou null
 */
function admin_lab_game_servers_get_minecraft_account_by_uuid($uuid) {
    global $wpdb;
    
    $table_name = admin_lab_getTable('minecraft_accounts', true);
    
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE minecraft_uuid = %s", $uuid),
        ARRAY_A
    );
}

/**
 * Lie un compte Minecraft à un utilisateur WordPress
 *
 * @param int    $user_id ID de l'utilisateur WordPress
 * @param string $uuid UUID Minecraft
 * @param string $username Username Minecraft
 * @param string|null $microsoft_id ID Microsoft (optionnel)
 * @return int|WP_Error ID de l'enregistrement ou erreur
 */
function admin_lab_game_servers_link_minecraft_account($user_id, $uuid, $username = null, $microsoft_id = null) {
    global $wpdb;
    
    $table_name = admin_lab_getTable('minecraft_accounts', true);
    
    // Validation
    if (empty($user_id) || $user_id <= 0) {
        return new WP_Error('invalid_user_id', __('Invalid user ID.', 'me5rine-lab'));
    }
    
    if (empty($uuid)) {
        return new WP_Error('invalid_uuid', __('Minecraft UUID is required.', 'me5rine-lab'));
    }
    
    // Vérifier le format UUID (format standard avec tirets)
    $uuid_clean = str_replace('-', '', $uuid);
    if (strlen($uuid_clean) !== 32 || !ctype_xdigit($uuid_clean)) {
        return new WP_Error('invalid_uuid_format', __('Invalid Minecraft UUID format.', 'me5rine-lab'));
    }
    
    // Formater l'UUID avec tirets si nécessaire
    if (strlen($uuid) === 32) {
        $uuid = substr($uuid, 0, 8) . '-' . 
                substr($uuid, 8, 4) . '-' . 
                substr($uuid, 12, 4) . '-' . 
                substr($uuid, 16, 4) . '-' . 
                substr($uuid, 20, 12);
    }
    
    // Vérifier si l'utilisateur a déjà un compte Minecraft lié
    $existing = admin_lab_game_servers_get_minecraft_account($user_id);
    
    // Vérifier si cet UUID est déjà lié à un autre utilisateur
    $existing_uuid = admin_lab_game_servers_get_minecraft_account_by_uuid($uuid);
    if ($existing_uuid && (int)$existing_uuid['user_id'] !== $user_id) {
        return new WP_Error('uuid_already_linked', __('Ce compte Minecraft est déjà lié à un autre utilisateur.', 'me5rine-lab'));
    }
    
    $data = [
        'user_id' => (int) $user_id,
        'minecraft_uuid' => sanitize_text_field($uuid),
        'minecraft_username' => !empty($username) ? sanitize_text_field($username) : null,
        'microsoft_id' => !empty($microsoft_id) ? sanitize_text_field($microsoft_id) : null,
        'updated_at' => current_time('mysql')
    ];
    
    if ($existing) {
        // Mise à jour
        $result = $wpdb->update(
            $table_name,
            $data,
            ['user_id' => $user_id],
            ['%d', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error updating Minecraft account.', 'me5rine-lab'));
        }
        
        return $existing['id'];
    } else {
        // Insertion
        $data['linked_at'] = current_time('mysql');
        
        $result = $wpdb->insert($table_name, $data, ['%d', '%s', '%s', '%s', '%s', '%s']);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error linking Minecraft account.', 'me5rine-lab'));
        }
        
        return $wpdb->insert_id;
    }
}

/**
 * Supprime le lien d'un compte Minecraft
 *
 * @param int $user_id ID de l'utilisateur WordPress
 * @return bool|WP_Error True en cas de succès, ou erreur
 */
function admin_lab_game_servers_unlink_minecraft_account($user_id) {
    global $wpdb;
    
    $table_name = admin_lab_getTable('minecraft_accounts', true);
    
    $result = $wpdb->delete($table_name, ['user_id' => $user_id], ['%d']);
    
    if ($result === false) {
        return new WP_Error('db_error', __('Error unlinking Minecraft account.', 'me5rine-lab'));
    }
    
    return true;
}

/**
 * Met à jour le username Minecraft
 *
 * @param int    $user_id ID de l'utilisateur WordPress
 * @param string $username Nouveau username Minecraft
 * @return bool|WP_Error True en cas de succès, ou erreur
 */
function admin_lab_game_servers_update_minecraft_username($user_id, $username) {
    global $wpdb;
    
    $table_name = admin_lab_getTable('minecraft_accounts', true);
    
    $result = $wpdb->update(
        $table_name,
        [
            'minecraft_username' => sanitize_text_field($username),
            'updated_at' => current_time('mysql')
        ],
        ['user_id' => $user_id],
        ['%s', '%s'],
        ['%d']
    );
    
    if ($result === false) {
        return new WP_Error('db_error', __('Error updating Minecraft username.', 'me5rine-lab'));
    }
    
    return true;
}

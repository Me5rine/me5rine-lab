<?php
// File: modules/subscription/functions/subscription-levels.php

if (!defined('ABSPATH')) exit;

/**
 * Get a level by ID
 */
function admin_lab_get_subscription_level($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
}

/**
 * Get a level by provider and slug
 */
function admin_lab_get_subscription_level_by_slug($provider_slug, $level_slug) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE provider_slug = %s AND level_slug = %s",
        $provider_slug, $level_slug
    ), ARRAY_A);
}

/**
 * Get all levels for a provider
 */
function admin_lab_get_subscription_levels_by_provider($provider_slug) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE provider_slug = %s ORDER BY level_name ASC",
        $provider_slug
    ), ARRAY_A);
}

/**
 * Get all levels
 */
function admin_lab_get_subscription_levels($provider_slug = null) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    $where = $provider_slug ? $wpdb->prepare("WHERE provider_slug = %s", $provider_slug) : "";
    return $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY provider_slug ASC, level_name ASC", ARRAY_A);
}

/**
 * Save a level (create or update)
 */
function admin_lab_save_subscription_level($data) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    
    // Debug: log input data
    error_log('admin_lab_save_subscription_level - Input data: ' . print_r($data, true));
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    unset($data['id']);
    
    $save_data = [
        'provider_slug' => sanitize_text_field($data['provider_slug'] ?? ''),
        'level_slug' => sanitize_text_field($data['level_slug'] ?? $data['slug'] ?? ''),
        'level_name' => sanitize_text_field($data['level_name'] ?? $data['name'] ?? ''),
        'level_tier' => isset($data['level_tier']) && $data['level_tier'] !== '' && $data['level_tier'] !== null 
            ? sanitize_text_field($data['level_tier']) 
            : null,
        'subscription_type' => sanitize_text_field($data['subscription_type'] ?? null),
        'priority' => isset($data['priority']) ? intval($data['priority']) : 0,
        'is_active' => isset($data['is_active']) ? 1 : 1,
    ];
    
    // Add discord_role_id if provided (for Tipeee)
    if (isset($data['discord_role_id']) && $data['discord_role_id'] !== '' && $data['discord_role_id'] !== null) {
        $save_data['discord_role_id'] = sanitize_text_field($data['discord_role_id']);
    } else {
        // Set to NULL if not provided (for updates)
        $save_data['discord_role_id'] = null;
    }
    
    // Debug: log save data
    error_log('admin_lab_save_subscription_level - Save data: ' . print_r($save_data, true));
    error_log('admin_lab_save_subscription_level - Table: ' . $table);
    error_log('admin_lab_save_subscription_level - ID: ' . $id);
    
    if ($id > 0) {
        $result = $wpdb->update($table, $save_data, ['id' => $id], null, ['%d']);
        if ($result === false) {
            $error = $wpdb->last_error ?: 'Unknown error';
            error_log('Subscription Level Update Error: ' . $error);
            error_log('Subscription Level Update - Last query: ' . $wpdb->last_query);
            return false;
        }
        error_log('Subscription Level Update - Success, rows affected: ' . $result);
        return $id;
    } else {
        $result = $wpdb->insert($table, $save_data);
        if ($result === false) {
            $error = $wpdb->last_error ?: 'Unknown error';
            error_log('Subscription Level Insert Error: ' . $error);
            error_log('Subscription Level Insert - Last query: ' . $wpdb->last_query);
            return false;
        }
        $insert_id = $wpdb->insert_id;
        error_log('Subscription Level Insert - Success, insert_id: ' . $insert_id);
        return $insert_id;
    }
}

/**
 * Delete a level
 */
function admin_lab_delete_subscription_level($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    $table_mappings = admin_lab_getTable('subscription_tier_mappings');
    $table_subscriptions = admin_lab_getTable('user_subscriptions');
    
    // Check if level is used
    $level = admin_lab_get_subscription_level($id);
    if (!$level) {
        return false;
    }
    
    // Check in mappings
    $used_in_mappings = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_mappings} WHERE provider_slug = %s AND level_slug = %s",
        $level['provider_slug'], $level['level_slug']
    ));
    
    // Check in active subscriptions
    $used_in_subscriptions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_subscriptions} WHERE level_slug = %s AND status = 'active'",
        $level['level_slug']
    ));
    
    if ($used_in_mappings > 0 || $used_in_subscriptions > 0) {
        return false; // Cannot be deleted
    }
    
    return $wpdb->delete($table, ['id' => $id]);
}

<?php
// File: modules/subscription/functions/subscription-providers.php

if (!defined('ABSPATH')) exit;

/**
 * Get a provider by ID
 */
function admin_lab_get_subscription_provider($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_providers');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
}

/**
 * Get a provider by slug
 */
function admin_lab_get_subscription_provider_by_slug($slug) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_providers');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE provider_slug = %s", $slug), ARRAY_A);
}

/**
 * Get all providers
 */
function admin_lab_get_subscription_providers($active_only = false) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_providers');
    $where = $active_only ? "WHERE is_active = 1" : "";
    return $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY provider_name ASC", ARRAY_A);
}

/**
 * Save a provider (create or update)
 */
function admin_lab_save_subscription_provider($data) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_providers');
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    unset($data['id']);
    
    // Prepare data
    // Note: settings should be an array at this point (already merged in subscription-tab-providers.php)
    // We'll serialize it later before saving
    $save_data = [
        'provider_slug' => sanitize_text_field($data['provider_slug'] ?? $data['slug'] ?? ''),
        'provider_name' => sanitize_text_field($data['provider_name'] ?? $data['name'] ?? ''),
        'api_endpoint' => isset($data['api_endpoint']) ? esc_url_raw($data['api_endpoint']) : (isset($data['api_base_url']) ? esc_url_raw($data['api_base_url']) : null),
        'auth_type' => sanitize_text_field($data['auth_type'] ?? null),
        'client_id' => sanitize_text_field($data['client_id'] ?? null),
        'client_secret' => sanitize_text_field($data['client_secret'] ?? null),
        'is_active' => isset($data['is_active']) ? intval($data['is_active']) : 0, // Use actual value, not just isset check
        'settings' => isset($data['settings']) ? $data['settings'] : null, // Keep as array for now
    ];
    
    if ($id > 0) {
        // Update: only update client_secret if provided
        if (empty($save_data['client_secret'])) {
            unset($save_data['client_secret']);
        }
        
        // Settings are already merged in subscription-tab-providers.php
        // Serialize them before saving
        if (is_array($save_data['settings'])) {
            // Serialize the array for storage
            $save_data['settings'] = maybe_serialize($save_data['settings']);
        } elseif ($id > 0 && (empty($save_data['settings']) || $save_data['settings'] === null)) {
            // If updating and no settings provided, keep existing ones
            $existing = $wpdb->get_row($wpdb->prepare("SELECT settings FROM {$table} WHERE id = %d", $id), ARRAY_A);
            if ($existing && !empty($existing['settings'])) {
                $save_data['settings'] = $existing['settings'];
            } else {
                unset($save_data['settings']); // Don't update if empty
            }
        } elseif (!empty($save_data['settings']) && !is_array($save_data['settings'])) {
            // If settings is already a string (serialized), keep it as is
            // This shouldn't happen if called from subscription-tab-providers.php, but handle it anyway
        }
        
        // Define format strings for wpdb->update
        // Format: %s = string, %d = integer, %f = float
        $formats = [];
        foreach ($save_data as $key => $value) {
            if ($key === 'id') continue;
            if (in_array($key, ['is_active'])) {
                $formats[] = '%d'; // Integer
            } elseif (in_array($key, ['api_endpoint', 'client_id', 'client_secret', 'provider_slug', 'provider_name', 'auth_type', 'settings'])) {
                $formats[] = '%s'; // String
            } else {
                $formats[] = '%s'; // Default to string
            }
        }
        
        $where_format = ['%d']; // id is integer
        
        $wpdb->update($table, $save_data, ['id' => $id], $formats, $where_format);
        
        return $id;
    } else {
        if (is_array($save_data['settings'])) {
            $save_data['settings'] = maybe_serialize($save_data['settings']);
        }
        $wpdb->insert($table, $save_data);
        return $wpdb->insert_id;
    }
}

/**
 * Delete a provider
 */
function admin_lab_delete_subscription_provider($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_providers');
    return $wpdb->delete($table, ['id' => intval($id)]);
}

/**
 * Check if debug logging is enabled for a provider
 * 
 * @param string $provider_slug Provider slug
 * @return bool True if debug_log is enabled for this provider
 */
function admin_lab_subscription_is_debug_log_enabled($provider_slug) {
    $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
    if (!$provider) {
        return false;
    }
    
    $settings = !empty($provider['settings']) ? maybe_unserialize($provider['settings']) : [];
    if (!is_array($settings)) {
        return false;
    }
    
    return !empty($settings['debug_log']);
}

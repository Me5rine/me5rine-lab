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
    
    // Debug: log is_active value from input data
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[PROVIDER SAVE FUNCTION] Input data[is_active]: ' . ($data['is_active'] ?? 'NOT SET'));
        error_log('[PROVIDER SAVE FUNCTION] Input data[is_active] type: ' . (isset($data['is_active']) ? gettype($data['is_active']) : 'NOT SET'));
        error_log('[PROVIDER SAVE FUNCTION] Calculated save_data[is_active]: ' . $save_data['is_active']);
    }
    
    if ($id > 0) {
        // Update: only update client_secret if provided
        if (empty($save_data['client_secret'])) {
            unset($save_data['client_secret']);
        }
        
        // Settings are already merged in subscription-tab-providers.php
        // Serialize them before saving
        if (is_array($save_data['settings'])) {
            // Debug: log what we're saving (remove in production)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $safe_copy = $save_data['settings'];
                if (isset($safe_copy['bot_api_key'])) {
                    $safe_copy['bot_api_key'] = '***';
                }
                error_log('[PROVIDER SAVE] Saving settings (safe): ' . print_r($safe_copy, true));
            }
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
        
        // Debug: log is_active value
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PROVIDER SAVE] is_active value: ' . ($save_data['is_active'] ?? 'NOT SET'));
            error_log('[PROVIDER SAVE] All save_data keys: ' . implode(', ', array_keys($save_data)));
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
        
        $result = $wpdb->update($table, $save_data, ['id' => $id], $formats, $where_format);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PROVIDER SAVE] Update result: ' . ($result !== false ? 'SUCCESS (rows affected: ' . $result . ')' : 'FAILED'));
            if ($result === false) {
                error_log('[PROVIDER SAVE] Last error: ' . $wpdb->last_error);
            }
        }
        
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

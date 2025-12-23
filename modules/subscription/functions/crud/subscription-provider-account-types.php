<?php
// File: modules/subscription/functions/subscription-provider-account-types.php

if (!defined('ABSPATH')) exit;

/**
 * Get account type for a provider
 * Tries with the provided provider_slug first, then with the base provider if not found
 * 
 * @param string $provider_slug Provider slug (can be specific like 'twitch_me5rine' or base like 'twitch')
 * @return string|null Account type slug or null if not found
 */
function admin_lab_get_provider_account_type($provider_slug) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_provider_account_types');
    
    // First, try with the exact provider_slug
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE provider_slug = %s AND is_active = 1",
        $provider_slug
    ), ARRAY_A);
    
    if ($result) {
        return $result['account_type_slug'];
    }
    
    // If not found, try with base provider (normalize)
    $base_provider = $provider_slug;
    if (strpos($provider_slug, 'twitch') === 0) {
        $base_provider = 'twitch';
    } elseif (strpos($provider_slug, 'youtube') === 0) {
        $base_provider = 'youtube';
    } elseif (strpos($provider_slug, 'discord') === 0) {
        $base_provider = 'discord';
    } elseif (strpos($provider_slug, 'tipeee') === 0) {
        $base_provider = 'tipeee';
    } elseif (strpos($provider_slug, 'patreon') === 0) {
        $base_provider = 'patreon';
    }
    
    // Only try base provider if it's different from the original
    if ($base_provider !== $provider_slug) {
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE provider_slug = %s AND is_active = 1",
            $base_provider
        ), ARRAY_A);
        
        if ($result) {
            return $result['account_type_slug'];
        }
    }
    
    return null;
}

/**
 * Get all provider → account type mappings
 */
function admin_lab_get_provider_account_types() {
    global $wpdb;
    $table = admin_lab_getTable('subscription_provider_account_types');
    return $wpdb->get_results("SELECT * FROM {$table} ORDER BY provider_slug ASC", ARRAY_A);
}

/**
 * Save a provider → account type mapping
 */
function admin_lab_save_provider_account_type($data) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_provider_account_types');
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    unset($data['id']);
    
    $save_data = [
        'provider_slug' => sanitize_text_field($data['provider_slug'] ?? ''),
        'account_type_slug' => sanitize_text_field($data['account_type_slug'] ?? ''),
        'is_active' => isset($data['is_active']) ? 1 : 1,
    ];
    
    if ($id > 0) {
        $wpdb->update($table, $save_data, ['id' => $id]);
        return $id;
    } else {
        // Check if mapping already exists for this provider
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE provider_slug = %s",
            $save_data['provider_slug']
        ), ARRAY_A);
        
        if ($existing) {
            $wpdb->update($table, $save_data, ['id' => $existing['id']]);
            return $existing['id'];
        } else {
            $wpdb->insert($table, $save_data);
            return $wpdb->insert_id;
        }
    }
}

/**
 * Delete a mapping
 */
function admin_lab_delete_provider_account_type($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_provider_account_types');
    return $wpdb->delete($table, ['id' => intval($id)]);
}

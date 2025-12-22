<?php
// File: modules/subscription/functions/subscription-tiers.php

if (!defined('ABSPATH')) exit;

/**
 * Get a tier by ID
 */
function admin_lab_get_subscription_tier($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_tiers');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
}

/**
 * Get a tier by slug
 */
function admin_lab_get_subscription_tier_by_slug($slug) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_tiers');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE tier_slug = %s", $slug), ARRAY_A);
}

/**
 * Get all tiers
 */
function admin_lab_get_subscription_tiers() {
    global $wpdb;
    $table = admin_lab_getTable('subscription_tiers');
    // Group by id to ensure uniqueness (in case of any data inconsistency)
    $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY tier_order ASC, tier_name ASC", ARRAY_A);
    
    // Remove duplicates by id (safety check)
    $unique_tiers = [];
    foreach ($results as $tier) {
        if (!isset($unique_tiers[$tier['id']])) {
            $unique_tiers[$tier['id']] = $tier;
        }
    }
    
    return array_values($unique_tiers);
}

/**
 * Save a tier
 */
function admin_lab_save_subscription_tier($data) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_tiers');
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    unset($data['id']);
    
    $tier_slug = sanitize_text_field($data['tier_slug'] ?? $data['slug'] ?? '');
    $save_data = [
        'tier_slug' => $tier_slug,
        'tier_name' => sanitize_text_field($data['tier_name'] ?? $data['name'] ?? ''),
        'tier_order' => isset($data['tier_order']) ? intval($data['tier_order']) : (isset($data['priority']) ? intval($data['priority']) : 0),
        'is_active' => isset($data['is_active']) ? 1 : 1,
    ];
    
    if ($id > 0) {
        // Update: check if tier_slug already exists for another tier
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE tier_slug = %s AND id != %d",
            $tier_slug,
            $id
        ), ARRAY_A);
        
        if ($existing) {
            error_log('[TIER SAVE] WARNING: Tier slug already exists for another tier (ID: ' . $existing['id'] . ')');
            return false;
        }
        
        $wpdb->update($table, $save_data, ['id' => $id]);
        return $id;
    } else {
        // Insert: check if tier_slug already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE tier_slug = %s",
            $tier_slug
        ), ARRAY_A);
        
        if ($existing) {
            error_log('[TIER SAVE] WARNING: Tier slug already exists (ID: ' . $existing['id'] . '). Updating existing tier instead.');
            // Update existing tier instead of creating duplicate
            $wpdb->update($table, $save_data, ['id' => $existing['id']]);
            return $existing['id'];
        }
        
        $wpdb->insert($table, $save_data);
        return $wpdb->insert_id;
    }
}

/**
 * Get tier mappings
 */
function admin_lab_get_subscription_tier_mappings($tier_slug = null) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_tier_mappings');
    $where = $tier_slug ? $wpdb->prepare("WHERE tier_slug = %s", $tier_slug) : "";
    $sql = "SELECT * FROM {$table} {$where} ORDER BY tier_slug ASC, provider_slug ASC, level_slug ASC";
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[TIER MAPPINGS] SQL: ' . $sql);
    }
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[TIER MAPPINGS] Found ' . count($results) . ' mappings from database');
        foreach ($results as $m) {
            error_log('[TIER MAPPINGS] ID: ' . $m['id'] . ', tier: ' . $m['tier_slug'] . ', provider: ' . $m['provider_slug'] . ', level: ' . $m['level_slug']);
        }
    }
    
    // Remove duplicates by id (safety check)
    $unique_mappings = [];
    foreach ($results as $mapping) {
        $mapping_id = intval($mapping['id']);
        if (!isset($unique_mappings[$mapping_id])) {
            $unique_mappings[$mapping_id] = $mapping;
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[TIER MAPPINGS] WARNING: Duplicate ID ' . $mapping_id . ' found, skipping');
            }
        }
    }
    
    $final_results = array_values($unique_mappings);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[TIER MAPPINGS] Returning ' . count($final_results) . ' unique mappings');
        foreach ($final_results as $m) {
            error_log('[TIER MAPPINGS] Final ID: ' . $m['id']);
        }
    }
    
    return $final_results;
}

/**
 * Save a tier mapping
 */
function admin_lab_save_subscription_tier_mapping($data) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_tier_mappings');
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    unset($data['id']);
    
    // Get tier_slug from tier_id if provided
    $tier_slug = '';
    if (isset($data['tier_id']) && $data['tier_id'] > 0) {
        $tier = admin_lab_get_subscription_tier($data['tier_id']);
        if ($tier) {
            $tier_slug = $tier['tier_slug'];
        }
    } elseif (isset($data['tier_slug'])) {
        $tier_slug = $data['tier_slug'];
    }
    
    $provider_slug = sanitize_text_field($data['provider_slug'] ?? '');
    $level_slug = sanitize_text_field($data['level_slug'] ?? '');
    
    $save_data = [
        'tier_slug' => sanitize_text_field($tier_slug),
        'provider_slug' => $provider_slug,
        'level_slug' => $level_slug,
        'is_active' => isset($data['is_active']) ? 1 : 1,
    ];
    
    if ($id > 0) {
        // Update: check if this exact mapping already exists for another id
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE tier_slug = %s AND provider_slug = %s AND level_slug = %s AND id != %d",
            $tier_slug,
            $provider_slug,
            $level_slug,
            $id
        ), ARRAY_A);
        
        if ($existing) {
            error_log('[TIER MAPPING SAVE] WARNING: Mapping already exists for another entry (ID: ' . $existing['id'] . ')');
            return false;
        }
        
        $wpdb->update($table, $save_data, ['id' => $id]);
        return $id;
    } else {
        // Insert: check if this exact mapping already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE tier_slug = %s AND provider_slug = %s AND level_slug = %s",
            $tier_slug,
            $provider_slug,
            $level_slug
        ), ARRAY_A);
        
        if ($existing) {
            error_log('[TIER MAPPING SAVE] WARNING: Mapping already exists (ID: ' . $existing['id'] . '). Updating existing mapping instead.');
            // Update existing mapping instead of creating duplicate
            $wpdb->update($table, $save_data, ['id' => $existing['id']]);
            return $existing['id'];
        }
        
        $wpdb->insert($table, $save_data);
        return $wpdb->insert_id;
    }
}

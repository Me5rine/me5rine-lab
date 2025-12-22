<?php
// File: modules/subscription/functions/subscription-channels.php

if (!defined('ABSPATH')) exit;

/**
 * Get a channel by ID
 */
function admin_lab_get_subscription_channel($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_channels');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
}

/**
 * Get all channels for a provider
 */
function admin_lab_get_subscription_channels_by_provider($provider_slug, $active_only = false) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_channels');
    $where = $wpdb->prepare("WHERE provider_slug = %s", $provider_slug);
    if ($active_only) {
        $where .= " AND is_active = 1";
    }
    return $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY channel_name ASC", ARRAY_A);
}

/**
 * Get all channels
 */
function admin_lab_get_subscription_channels($provider_slug = null) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_channels');
    $where = $provider_slug ? $wpdb->prepare("WHERE provider_slug = %s", $provider_slug) : "";
    return $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY provider_slug ASC, channel_name ASC", ARRAY_A);
}

/**
 * Save a channel
 */
function admin_lab_save_subscription_channel($data) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_channels');
    
    $id = isset($data['id']) ? intval($data['id']) : 0;
    unset($data['id']);
    
    $save_data = [
        'provider_slug' => sanitize_text_field($data['provider_slug'] ?? ''),
        'channel_identifier' => sanitize_text_field($data['channel_identifier'] ?? $data['channel_id'] ?? ''),
        'channel_name' => sanitize_text_field($data['channel_name'] ?? ''),
        'channel_type' => sanitize_text_field($data['channel_type'] ?? null),
        'is_active' => isset($data['is_active']) ? 1 : 0,
        'settings' => isset($data['settings']) ? maybe_serialize($data['settings']) : null,
    ];
    
    if ($id > 0) {
        $wpdb->update($table, $save_data, ['id' => $id]);
        return $id;
    } else {
        $wpdb->insert($table, $save_data);
        return $wpdb->insert_id;
    }
}

/**
 * Delete a channel
 */
function admin_lab_delete_subscription_channel($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_channels');
    return $wpdb->delete($table, ['id' => intval($id)]);
}

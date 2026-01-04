<?php
// File: modules/subscription/functions/subscription-accounts.php

if (!defined('ABSPATH')) exit;

/**
 * Link an external account to a WordPress user
 */
function admin_lab_link_subscription_account($user_id, $provider_slug, $external_user_id, $data = []) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_accounts');
    
    // Check if account already exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE provider_slug = %s AND external_user_id = %s",
        $provider_slug, $external_user_id
    ), ARRAY_A);
    
    // Prepare save data - always update these fields
    $save_data = [
        'user_id' => intval($user_id),
        'provider_slug' => sanitize_text_field($provider_slug),
        'external_user_id' => sanitize_text_field($external_user_id),
        'external_username' => sanitize_text_field($data['external_username'] ?? ''),
        'keycloak_identity_id' => sanitize_text_field($data['keycloak_identity_id'] ?? ''),
        'is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1,
        'last_sync_at' => isset($data['last_sync_at']) ? $data['last_sync_at'] : current_time('mysql'),
    ];
    
    if ($existing) {
        error_log("[SUBSCRIPTION ACCOUNTS] Updating account {$existing['id']} for user {$user_id}, provider {$provider_slug}, external_id {$external_user_id}");
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION ACCOUNTS] Updating account {$existing['id']} for user {$user_id}, provider {$provider_slug}, external_id {$external_user_id}", 'subscription-sync.log');
        }
        $wpdb->update($table, $save_data, ['id' => $existing['id']]);
        $account_id = $existing['id'];
    } else {
        error_log("[SUBSCRIPTION ACCOUNTS] Creating new account for user {$user_id}, provider {$provider_slug}, external_id {$external_user_id}");
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom("[SUBSCRIPTION ACCOUNTS] Creating new account for user {$user_id}, provider {$provider_slug}, external_id {$external_user_id}", 'subscription-sync.log');
        }
        $wpdb->insert($table, $save_data);
        $account_id = $wpdb->insert_id;
    }
    
    // Déclencher la synchronisation vers user_profiles si c'est un compte Discord
    // Le plugin Poké HUB écoute cette action pour synchroniser les IDs
    if ($provider_slug === 'discord' && $user_id > 0) {
        do_action('poke_hub_sync_user_profile_from_subscription', $user_id);
    }
    
    return $account_id;
}

/**
 * Get an account by ID
 */
function admin_lab_get_subscription_account($id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_accounts');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
}

/**
 * Get all accounts for a user
 */
function admin_lab_get_user_subscription_accounts($user_id, $provider_slug = null) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_accounts');
    $where = $wpdb->prepare("WHERE user_id = %d", $user_id);
    if ($provider_slug) {
        $where .= $wpdb->prepare(" AND provider_slug = %s", $provider_slug);
    }
    return $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY provider_slug ASC", ARRAY_A);
}

/**
 * Get an account by provider and external_user_id
 */
function admin_lab_get_subscription_account_by_external($provider_slug, $external_user_id) {
    global $wpdb;
    $table = admin_lab_getTable('subscription_accounts');
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE provider_slug = %s AND external_user_id = %s",
        $provider_slug, $external_user_id
    ), ARRAY_A);
}


<?php
// File: modules/subscription/functions/subscription-cleanup-types.php

if (!defined('ABSPATH')) exit;

/**
 * Cleanup old subscription types and keep only simplified structure
 * Removes tier1_payant, tier1_gift, tier1_prime, etc. and keeps only tier1, tier2, tier3
 * Also removes default types for Patreon, Tipeee, YouTube (they must be synced from APIs)
 */
function admin_lab_cleanup_subscription_types() {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    
    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $table
    ));
    
    if (!$table_exists) {
        // Table doesn't exist yet, skip cleanup
        return;
    }
    
    // List of level_slugs to DELETE (old structure)
    $slugs_to_delete = [
        // Old Twitch types
        'tier1_payant',
        'tier1_gift',
        'tier1_prime',
        'tier2_payant',
        'tier2_gift',
        'tier3_payant',
        'tier3_gift',
    ];
    
    // Delete old Twitch types
    foreach ($slugs_to_delete as $slug) {
        $wpdb->delete($table, [
            'provider_slug' => 'twitch',
            'level_slug' => $slug,
        ]);
    }
    
    // Delete default types for Patreon, Tipeee, YouTube (they must be synced from APIs)
    // BUT: Don't delete manually created types for Tipeee (they have discord_role_id)
    $providers_to_clean = ['patreon', 'tipeee', 'youtube'];
    foreach ($providers_to_clean as $provider_slug) {
        // Get all levels for this provider
        $levels = $wpdb->get_results($wpdb->prepare(
            "SELECT id, level_slug, discord_role_id FROM {$table} WHERE provider_slug = %s",
            $provider_slug
        ), ARRAY_A);
        
        // Check if provider exists and is configured
        $provider = admin_lab_get_subscription_provider_by_slug($provider_slug);
        
        // For Tipeee, check bot_api_key instead of client_id
        $is_configured = false;
        if ($provider) {
            if ($provider_slug === 'tipeee' || strpos($provider_slug, 'tipeee') === 0) {
                // Tipeee uses bot_api_key, not client_id
                $settings = maybe_unserialize($provider['settings'] ?? []);
                $bot_api_key = $settings['bot_api_key'] ?? '';
                $is_configured = !empty($bot_api_key);
            } else {
                // Other providers use client_id
                $is_configured = !empty($provider['client_id']);
            }
        }
        
        if (!$provider || !$is_configured) {
            // Provider not configured - delete default types, but keep manually created ones
            foreach ($levels as $level) {
                // For Tipeee: Don't delete if it has discord_role_id (manually created)
                if (($provider_slug === 'tipeee' || strpos($provider_slug, 'tipeee') === 0) && !empty($level['discord_role_id'])) {
                    continue; // Skip manually created Tipeee types
                }
                
                // Only delete if no active subscriptions use this level
                $table_subscriptions = admin_lab_getTable('user_subscriptions');
                $has_subscriptions = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_subscriptions} WHERE provider_slug = %s AND level_slug = %s AND status = 'active'",
                    $provider_slug,
                    $level['level_slug']
                ));
                
                if (!$has_subscriptions) {
                    $wpdb->delete($table, ['id' => $level['id']]);
                }
            }
        }
    }
    
    // Ensure we have the correct default types (tier1, tier2, tier3 for Twitch, booster for Discord)
    admin_lab_init_default_subscription_types();
}


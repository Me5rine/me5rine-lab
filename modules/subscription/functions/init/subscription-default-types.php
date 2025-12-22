<?php
// File: modules/subscription/functions/subscription-default-types.php

if (!defined('ABSPATH')) exit;

/**
 * Initialize default subscription types for providers
 * Only creates default types for Twitch (tier1, tier2, tier3) and Discord (booster)
 * Other providers (Patreon, Tipeee, YouTube) must be synced from their APIs
 */
function admin_lab_init_default_subscription_types() {
    global $wpdb;
    $table = admin_lab_getTable('subscription_levels');
    
    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $table
    ));
    
    if (!$table_exists) {
        // Table doesn't exist yet, skip initialization
        return;
    }
    
    // Twitch default types - Only tier1, tier2, tier3
    // subscription_type (gratuit/payant) is stored in user_subscriptions metadata, NOT in subscription_levels
    $twitch_types = [
        ['level_slug' => 'tier1', 'level_name' => 'Tier 1', 'level_tier' => 1],
        ['level_slug' => 'tier2', 'level_name' => 'Tier 2', 'level_tier' => 2],
        ['level_slug' => 'tier3', 'level_name' => 'Tier 3', 'level_tier' => 3],
    ];
    
    // Discord default types - Only booster
    $discord_types = [
        ['level_slug' => 'booster', 'level_name' => 'Server Booster', 'level_tier' => 1],
    ];
    
    // Insert Twitch types (only if they don't exist)
    foreach ($twitch_types as $type) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE provider_slug = 'twitch' AND level_slug = %s",
            $type['level_slug']
        ));
        if (!$exists) {
            $wpdb->insert($table, [
                'provider_slug' => 'twitch',
                'level_slug' => $type['level_slug'],
                'level_name' => $type['level_name'],
                'level_tier' => $type['level_tier'],
                'subscription_type' => null, // subscription_type is NOT stored here, it's in user_subscriptions metadata
                'is_active' => 1,
            ]);
        }
    }
    
    // Insert Discord types (only if they don't exist)
    foreach ($discord_types as $type) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE provider_slug = 'discord' AND level_slug = %s",
            $type['level_slug']
        ));
        if (!$exists) {
            $wpdb->insert($table, [
                'provider_slug' => 'discord',
                'level_slug' => $type['level_slug'],
                'level_name' => $type['level_name'],
                'level_tier' => $type['level_tier'],
                'subscription_type' => 'payant', // Discord boosters are always paid
                'is_active' => 1,
            ]);
        }
    }
}

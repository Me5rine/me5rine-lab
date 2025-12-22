<?php
// File: modules/subscription/functions/sync/subscription-sync-cron.php

if (!defined('ABSPATH')) exit;

/**
 * Schedule automatic subscription synchronization
 * Runs every hour by default
 */
function admin_lab_schedule_subscription_sync() {
    // Check if already scheduled
    if (!wp_next_scheduled('admin_lab_subscription_sync_cron')) {
        // Schedule to run every hour
        wp_schedule_event(time(), 'hourly', 'admin_lab_subscription_sync_cron');
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[SUBSCRIPTION SYNC] Scheduled automatic sync (hourly)', 'subscription-sync.log');
        }
        error_log('[SUBSCRIPTION SYNC] Scheduled automatic sync (hourly)');
    }
}

/**
 * Unschedule automatic subscription synchronization
 */
function admin_lab_unschedule_subscription_sync() {
    $timestamp = wp_next_scheduled('admin_lab_subscription_sync_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'admin_lab_subscription_sync_cron');
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[SUBSCRIPTION SYNC] Unscheduled automatic sync', 'subscription-sync.log');
        }
        error_log('[SUBSCRIPTION SYNC] Unscheduled automatic sync');
    }
}

/**
 * Execute automatic subscription synchronization
 * Called by WordPress cron
 */
function admin_lab_execute_subscription_sync_cron() {
    if (function_exists('admin_lab_log_custom')) {
        admin_lab_log_custom('[SUBSCRIPTION SYNC] Automatic sync triggered by cron', 'subscription-sync.log');
    }
    error_log('[SUBSCRIPTION SYNC] Automatic sync triggered by cron');
    
    // Execute the sync
    if (function_exists('admin_lab_sync_subscriptions_from_providers')) {
        $results = admin_lab_sync_subscriptions_from_providers();
        if (function_exists('admin_lab_log_custom')) {
            admin_lab_log_custom('[SUBSCRIPTION SYNC] Automatic sync completed. Total synced: ' . ($results['total_synced'] ?? 0), 'subscription-sync.log');
        }
        error_log('[SUBSCRIPTION SYNC] Automatic sync completed. Total synced: ' . ($results['total_synced'] ?? 0));
    }
}

/**
 * Get sync schedule status
 */
function admin_lab_get_subscription_sync_schedule_status() {
    $next_run = wp_next_scheduled('admin_lab_subscription_sync_cron');
    if ($next_run) {
        return [
            'scheduled' => true,
            'next_run' => $next_run,
            'next_run_formatted' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run),
            'interval' => 'hourly'
        ];
    }
    return [
        'scheduled' => false,
        'next_run' => null,
        'next_run_formatted' => null,
        'interval' => null
    ];
}

// Hook the cron event
add_action('admin_lab_subscription_sync_cron', 'admin_lab_execute_subscription_sync_cron');

// Schedule on module activation
add_action('admin_lab_subscription_module_activated', 'admin_lab_schedule_subscription_sync');

// Unschedule on module deactivation
add_action('admin_lab_subscription_module_desactivated', 'admin_lab_unschedule_subscription_sync');

// Ensure schedule is set on init (in case it was missed)
add_action('init', function() {
    $active_modules = get_option('admin_lab_active_modules', []);
    if (is_array($active_modules) && in_array('subscription', $active_modules, true)) {
        if (!wp_next_scheduled('admin_lab_subscription_sync_cron')) {
            admin_lab_schedule_subscription_sync();
        }
    }
});


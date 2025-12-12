<?php
// File: modules/giveaways/functions/giveaways-metadatas-cron.php

if (!defined('ABSPATH')) exit;

$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('giveaways', $active_modules)) {
    return;
}

add_action('wp', function () {
    if (!wp_next_scheduled('sync_giveaways_participants_entries')) {
        wp_schedule_event(time(), 'hourly', 'sync_giveaways_participants_entries');
        if (defined('WP_DEBUG') && WP_DEBUG) error_log('[Me5rine] Cron: sync_giveaways_participants_entries activé');
    }

    if (!wp_next_scheduled('rafflepress_campaign_sync_statuses')) {
        wp_schedule_event(time(), 'hourly', 'rafflepress_campaign_sync_statuses');
        if (defined('WP_DEBUG') && WP_DEBUG) error_log('[Me5rine] Cron: rafflepress_campaign_sync_statuses activé');
    }
});

register_deactivation_hook(__FILE__, function () {
    $crons = ['sync_giveaways_participants_entries', 'rafflepress_campaign_sync_statuses'];

    foreach ($crons as $hook) {
        $timestamp = wp_next_scheduled($hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook);
            if (defined('WP_DEBUG') && WP_DEBUG) error_log("[Me5rine] Cron désactivé : {$hook}");
        }
    }
});

add_action('sync_giveaways_participants_entries', function () {
    global $wpdb;

    $ongoing_ids = $wpdb->get_col("
        SELECT post_id 
        FROM {$wpdb->prefix}postmeta 
        WHERE meta_key = '_giveaway_status' AND meta_value = 'Ongoing'
    ");

    foreach ($ongoing_ids as $post_id) {
        $campaign_id = get_post_meta($post_id, '_rafflepress_campaign', true);
        if (!$campaign_id) continue;

        $participant_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}rafflepress_contestants WHERE giveaway_id = %d
        ", $campaign_id));

        $entry_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}rafflepress_entries WHERE giveaway_id = %d
        ", $campaign_id));

        update_post_meta($post_id, '_giveaway_participants_count', $participant_count);
        update_post_meta($post_id, '_giveaway_entries_count', $entry_count);
    }
});

add_action('rafflepress_campaign_sync_statuses', function () {
    $now = new DateTime('now', new DateTimeZone('UTC'));

    $args = [
        'post_type'      => 'giveaway',
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => '_giveaway_end_date',
                'value'   => $now->format('Y-m-d\TH:i'),
                'compare' => '>=',
                'type'    => 'DATETIME',
            ],
        ],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $giveaways = get_posts($args);

    foreach ($giveaways as $post_id) {
        $start = get_post_meta($post_id, '_giveaway_start_date', true);
        $end = get_post_meta($post_id, '_giveaway_end_date', true);
        if (!$start || !$end) continue;

        $start_date = new DateTime($start, new DateTimeZone('UTC'));
        $end_date = new DateTime($end, new DateTimeZone('UTC'));

        $status = ($now >= $start_date && $now <= $end_date) ? 'Ongoing' : 'Upcoming';
        update_post_meta($post_id, '_giveaway_status', $status);
    }
});

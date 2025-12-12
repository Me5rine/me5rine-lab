<?php
// File: modules/giveaways/admin-filters/giveaways-search.php

if (!defined('ABSPATH')) {
    exit;
}

// AJAX: Search for partners
add_action('wp_ajax_search_partners', 'admin_lab_ajax_search_partners');
function admin_lab_ajax_search_partners() {
    if (empty($_POST['search'])) {
        wp_send_json([]);
    }

    $term = sanitize_text_field($_POST['search']);
    $users = get_users([
        'role__in'        => ['um_partenaire', 'um_partenaire_plus'],
        'search'          => '*' . $term . '*',
        'search_columns'  => ['display_name', 'user_email']
    ]);    

    $results = array_map(function($user) {
        return [
            'id'   => $user->ID,
            'text' => esc_html($user->display_name),
        ];
    }, $users);

    wp_send_json($results);
}

// AJAX: Search for RafflePress campaigns
add_action('wp_ajax_search_rafflepress_campaigns', 'admin_lab_ajax_search_rafflepress_campaigns');
function admin_lab_ajax_search_rafflepress_campaigns() {
    if (empty($_POST['search'])) {
        wp_send_json([]);
    }

    global $wpdb;
    $term = sanitize_text_field($_POST['search']);
    $table = admin_lab_getTable('rafflepress_giveaways', false);

    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
        wp_send_json_error(__('RafflePress table not found.', 'me5rine-lab'));
    }

    $campaigns = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, starts, ends FROM $table WHERE name LIKE %s ORDER BY starts DESC LIMIT 20",
        '%' . $wpdb->esc_like($term) . '%'
    ));

    $timezone = get_option('timezone_string') ?: 'UTC';
    $tz = new DateTimeZone($timezone);

    $results = array_map(function($campaign) use ($tz) {
        $start = new DateTime($campaign->starts, new DateTimeZone('UTC'));
        $start->setTimezone($tz);
        $start_fmt = $start->format(get_option('date_format') . ' ' . get_option('time_format'));

        $end = new DateTime($campaign->ends, new DateTimeZone('UTC'));
        $end->setTimezone($tz);
        $end_fmt = $end->format(get_option('date_format') . ' ' . get_option('time_format'));

        return [
            'id'   => $campaign->id,
            'text' => esc_html(sprintf('%s (%s - %s)', $campaign->name, $start_fmt, $end_fmt))
        ];
    }, $campaigns);

    wp_send_json($results);
}

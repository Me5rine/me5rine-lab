<?php
// File: modules/giveaways/functions/rafflepress-campaign-save.php

if (!defined('ABSPATH')) exit;

function save_rafflepress_campaign($mode = 'create', $data = []) {
    global $wpdb;

    $title         = sanitize_text_field($data['title'] ?? '');
    $starts        = sanitize_text_field($data['start_date'] ?? '');
    $ends          = sanitize_text_field($data['end_date'] ?? '');
    $prizes        = $data['prizes'] ?? [];
    $entry_options = $data['entry_options'] ?? [];
    $campaign_id   = isset($data['campaign_id']) ? intval($data['campaign_id']) : null;

    if (!$title || !$starts || !$ends) {
        return false;
    }

    $timezone = new DateTimeZone('Europe/Paris');
    $start_datetime = new DateTime("$starts {$_POST['campaign_start_hour']}:{$_POST['campaign_start_minute']}", $timezone);
    $end_datetime   = new DateTime("$ends {$_POST['campaign_end_hour']}:{$_POST['campaign_end_minute']}", $timezone);

    $start_date = $start_datetime->format('Y-m-d');
    $start_time = $start_datetime->format('H:i');
    $end_date   = $end_datetime->format('Y-m-d');
    $end_time   = $end_datetime->format('H:i');

    $start_utc = $start_datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $end_utc   = $end_datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

    $settings = [
        'starts'               => $start_date,
        'starts_time'          => $start_time,
        'prizes'               => $prizes,
        'webhook_items'        => [[
            'txt'                    => 'Webhook',
            'webhooks_url'          => '',
            'webhooks_request_format' => 'json',
            'webhooks_secret'       => '',
            'header'                => [[
                'parameter_keys'   => '',
                'parameter_value'  => ''
            ]],
        ]],
        'layout'               => '1',
        'entry_options'        => $entry_options,
        'rules'                => $data['rules'] ?? '',
        'ends'                 => $end_date,
        'ends_time'            => $end_time,
        'timezone'             => wp_timezone_string(),
        'font'                 => '0',
        'is_new'               => false,
        'confirmation_email'   => __('Please click the link below to confirm your email address.', 'me5rine-lab') . "\r\n{confirmation-link}",
        'confirmation_subject' => __('[Action Required] Confirm your entry', 'me5rine-lab'),
        'from_name'            => get_bloginfo('name'),
        'from_email'           => get_option('admin_email'),
        'show_powered_by_link' => false,
        'sponsor_name'         => $data['sponsor_name'] ?? '',
        'sponsor_email'        => $data['sponsor_email'] ?? '',
        'sponsor_address'      => $data['sponsor_address'] ?? '',
        'sponsor_country'      => $data['sponsor_country'] ?? '',
        'state_province'       => isset($data['eligible_countries']) ? implode(', ', $data['eligible_countries']) : '',
        'eligible_min_age'     => (string) ($data['minimum_age'] ?? 18),
        'custom_css'           => '.rafflepress-giveaway body {margin-top: 0 !important;}',
    ];

    $table = $wpdb->prefix . 'rafflepress_giveaways';

    if ($mode === 'create') {
        $inserted = $wpdb->insert($table, [
            'name'                => $title,
            'slug'                => '',
            'parent_url'          => '',
            'uuid'                => wp_generate_uuid4(),
            'settings'            => wp_json_encode($settings),
            'meta'                => null,
            'starts'              => $start_utc,
            'ends'                => $end_utc,
            'active'              => 1,
            'show_leaderboard'    => 0,
            'giveawaytemplate_id' => 'basic_giveaway',
            'created_at'          => current_time('mysql'),
            'deleted_at'          => null
        ]);

        return $inserted ? $wpdb->insert_id : false;
    }

    if ($mode === 'update' && $campaign_id) {
        $updated = $wpdb->update($table, [
            'name'     => $title,
            'settings' => wp_json_encode($settings),
            'starts'   => $start_utc,
            'ends'     => $end_utc,
        ], ['id' => $campaign_id]);

        return $updated !== false ? $campaign_id : false;
    }

    return false;
}

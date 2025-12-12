<?php
// File: modules/giveaways/elementor/giveaways-elementor-queries.php

if (!defined('ABSPATH')) exit;

// Elementor: concours en cours
function admin_lab_elementor_query_active_giveaways($query) {
    $now = current_time('Y-m-d\TH:i', true);

    $query->set('post_type', 'giveaway');
    $query->set('meta_query', [
        'relation' => 'AND',
        [
            'key'     => '_giveaway_start_date',
            'value'   => $now,
            'compare' => '<=',
            'type'    => 'CHAR',
        ],
        [
            'key'     => '_giveaway_end_date',
            'value'   => $now,
            'compare' => '>=',
            'type'    => 'CHAR',
        ],
    ]);
    $query->set('meta_key', '_giveaway_end_date');
    $query->set('orderby', ['meta_value' => 'ASC']);
}
add_action('elementor/query/active_giveaways', 'admin_lab_elementor_query_active_giveaways');

// Elementor: concours terminés
function admin_lab_elementor_query_past_giveaways($query) {
    $now = current_time('Y-m-d\TH:i', true);

    $query->set('post_type', 'giveaway');
    $query->set('meta_query', [[
        'key'     => '_giveaway_end_date',
        'value'   => $now,
        'compare' => '<',
        'type'    => 'CHAR',
    ]]);
    $query->set('meta_key', '_giveaway_end_date');
    $query->set('orderby', ['meta_value' => 'DESC']);
}
add_action('elementor/query/past_giveaways', 'admin_lab_elementor_query_past_giveaways');

// Elementor: concours à venir
function admin_lab_elementor_query_future_giveaways($query) {
    $now = current_time('Y-m-d\TH:i', true);

    $query->set('post_type', 'giveaway');
    $query->set('meta_query', [[
        'key'     => '_giveaway_start_date',
        'value'   => $now,
        'compare' => '>',
        'type'    => 'CHAR',
    ]]);
    $query->set('meta_key', '_giveaway_start_date');
    $query->set('orderby', ['meta_value' => 'ASC']);
}
add_action('elementor/query/future_giveaways', 'admin_lab_elementor_query_future_giveaways');

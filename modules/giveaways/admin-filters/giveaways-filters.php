<?php
// File: modules/giveaways/admin-filters/giveaways-filters.php

if (!defined('ABSPATH')) exit;

add_action('restrict_manage_posts', 'giveaways_filter_by_partner');
function giveaways_filter_by_partner() {
    global $typenow;
    if ($typenow !== 'giveaway') return;

    $selected = isset($_GET['filter_partner']) ? $_GET['filter_partner'] : '';
    $users = get_users(array('role__in' => ['um_partenaire', 'um_partenaire_plus']));

    echo '<select name="filter_partner">';
    echo '<option value="">' . __('All Partners', 'me5rine-lab') . '</option>';
    foreach ($users as $user) {
        echo '<option value="' . esc_attr($user->ID) . '" ' . selected($selected, $user->ID, false) . '>' . esc_html($user->display_name) . '</option>';
    }
    echo '</select>';
}

add_action('pre_get_posts', 'giveaways_filter_by_partner_query');
function giveaways_filter_by_partner_query($query) {
    if (!is_admin() || !$query->is_main_query() || empty($_GET['filter_partner'])) {
        return;
    }
    $query->set('meta_query', array(
        array(
            'key' => '_giveaway_partner_id',
            'value' => sanitize_text_field($_GET['filter_partner']),
            'compare' => '='
        )
    ));
}

add_action('restrict_manage_posts', 'giveaways_filter_by_status');
function giveaways_filter_by_status() {
    global $typenow;
    if ($typenow !== 'giveaway') return;

    $selected = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
    $statuses = array(
        'Upcoming' => __('Upcoming', 'me5rine-lab'),
        'Ongoing'  => __('Ongoing', 'me5rine-lab'),
        'Finished' => __('Finished', 'me5rine-lab')
    );

    echo '<select name="filter_status">';
    echo '<option value="">' . __('All Statuses', 'me5rine-lab') . '</option>';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($selected, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

add_action('pre_get_posts', 'giveaways_filter_by_status_query');
function giveaways_filter_by_status_query($query) {
    if (!is_admin() || !$query->is_main_query() || empty($_GET['filter_status'])) {
        return;
    }
    $query->set('meta_query', array(
        array(
            'key' => '_giveaway_status',
            'value' => sanitize_text_field($_GET['filter_status']),
            'compare' => '='
        )
    ));
}

add_action('restrict_manage_posts', 'giveaways_filter_by_reward');
function giveaways_filter_by_reward() {
    global $typenow;
    if ($typenow !== 'giveaway') return;

    $selected = isset($_GET['filter_reward']) ? $_GET['filter_reward'] : '';

    echo '<select id="filter_reward" name="filter_reward" class="reward-select">';

    if (!empty($selected)) {
        $term = get_term_by('id', $selected, 'giveaway_rewards');
        if ($term) {
            echo '<option value="' . esc_attr($term->term_id) . '" selected="selected">' . esc_html($term->name) . '</option>';
        }
    }

    echo '</select>';
}

add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
function enqueue_admin_scripts($hook) {
    if ($hook !== 'edit.php' || get_current_screen()->post_type !== 'giveaway') return;

    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery'), null, true);
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css', array(), null);

    wp_add_inline_script('select2-js', "
        jQuery(document).ready(function($) {
            $('#filter_reward').select2({
                placeholder: '" . __('Search for a reward...', 'me5rine-lab') . "',
                allowClear: true,
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'search_rewards',
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        });
    ");
}

add_action('wp_ajax_search_rewards', 'search_rewards_callback');
function search_rewards_callback() {
    if (!isset($_POST['search'])) {
        wp_send_json([]);
        return;
    }

    global $wpdb;
    $search_term = sanitize_text_field($_POST['search']);

    $rewards = $wpdb->get_results($wpdb->prepare(
        "SELECT t.term_id, t.name 
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = 'giveaway_rewards'
        AND t.name LIKE %s
        LIMIT 10",
        '%' . $wpdb->esc_like($search_term) . '%'
    ));

    $results = [];
    foreach ($rewards as $reward) {
        $results[] = [
            'id' => $reward->term_id,
            'text' => $reward->name
        ];
    }

    wp_send_json($results);
}

add_action('pre_get_posts', 'giveaways_filter_by_reward_query');
function giveaways_filter_by_reward_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!empty($_GET['filter_reward'])) {
        $reward_id = sanitize_text_field($_GET['filter_reward']);

        if (!empty($reward_id)) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'giveaway_rewards',
                    'field'    => 'id',
                    'terms'    => $reward_id,
                )
            ));
        }
    }
}

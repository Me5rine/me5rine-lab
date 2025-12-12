<?php
// File: modules/giveaways/front/giveaways-user-shortcodes.php

if (!defined('ABSPATH')) exit;

add_shortcode('partner_active_giveaways', 'admin_lab_display_partner_active_giveaways');

function admin_lab_display_partner_active_giveaways() {
    if (!function_exists('um_profile_id')) return __('Profile not recognized.', 'me5rine-lab');

    wp_enqueue_style(
        'admin-lab-profil-giveaways',
        ME5RINE_LAB_URL . 'assets/css/giveaways-front-profil.css',
        [],
        ME5RINE_LAB_VERSION
    );

    $user_id = um_profile_id();
    if (!$user_id) return __('No user displayed.', 'me5rine-lab');

    $user = get_userdata($user_id);
    if (!$user || !admin_lab_user_is_partner($user_id)) return '';

    $now = current_time('mysql');

    $query = new WP_Query([
        'post_type'      => 'giveaway',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_giveaway_partner_id',
                'value'   => $user_id,
                'compare' => '='
            ],
            [
                'key'     => '_giveaway_start_date',
                'value'   => $now,
                'compare' => '<=',
                'type'    => 'DATETIME'
            ],
            [
                'key'     => '_giveaway_end_date',
                'value'   => $now,
                'compare' => '>=',
                'type'    => 'DATETIME'
            ]
        ]
    ]);

    ob_start();

    if ($query->have_posts()) {
        echo '<div class="partner-giveaway-wrapper">';
        echo '<h3 class="partner-giveaway-title">' . __('Giveaways from this partner', 'me5rine-lab') . '</h3>';

        while ($query->have_posts()) {
            $query->the_post();

            $post_id = get_the_ID();
            $title = get_the_title();
            $link = get_permalink();
            $thumb = get_the_post_thumbnail_url($post_id, 'medium');

            $end_date = get_post_meta($post_id, '_giveaway_end_date', true);
            $end_ts   = strtotime($end_date);
            $now_ts   = current_time('timestamp', true);

            $time_left = admin_lab_format_time_remaining($end_ts, $now_ts);
            $time_left_display = $time_left ? esc_html($time_left) : __('—', 'me5rine-lab');

            $participant_count = (int) get_post_meta($post_id, '_giveaway_participants_count', true);

            $terms = wp_get_post_terms($post_id, 'giveaway_rewards');
            $prizes = !empty($terms) ? implode(', ', wp_list_pluck($terms, 'name')) : __('—', 'me5rine-lab');

            echo '<div class="partner-giveaway-card">';

            if ($thumb) {
                echo '<img class="partner-giveaway-thumb" src="' . esc_url($thumb) . '" alt="' . esc_attr($title) . '">';
            }

            echo '<div class="partner-giveaway-content">';
            echo '<div class="partner-giveaway-header">';
            echo '<h4 class="partner-giveaway-name"><a href="' . esc_url($link) . '">' . esc_html($title) . '</a></h4>';
            echo '<div class="partner-giveaway-meta">';
            echo '<span class="giveaway-time-left">' . __('Time left:', 'me5rine-lab') . ' ' . $time_left_display . '</span>';
            echo '<span class="giveaway-participants">' . sprintf(
                _n('%s participant', '%s participants', $participant_count, 'me5rine-lab'),
                number_format_i18n($participant_count)
            ) . '</span>';
            echo '</div></div>';

            echo '<p class="partner-giveaway-gifts"><strong>' . __('Prizes:', 'me5rine-lab') . '</strong> ' . esc_html($prizes) . '</p>';
            echo '<a href="' . esc_url($link) . '" class="partner-giveaway-button">' . __('Join Now', 'me5rine-lab') . '</a>';
            echo '</div></div>';
        }

        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>' . __('This partner currently has no active giveaways.', 'me5rine-lab') . '</p>';
    }

    return ob_get_clean();
}

add_shortcode('admin_lab_participation_table', function () {
    $user_id = function_exists('um_get_requested_user') ? um_get_requested_user() : get_current_user_id();
    if (function_exists('admin_lab_render_participation_table')) {
        return admin_lab_render_participation_table($user_id, '', 0);
    }
    return '';
});

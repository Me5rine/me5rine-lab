<?php
// File: modules/giveaways/front/giveaways-ajax-participations.php

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_admin_lab_filter_giveaways', 'admin_lab_ajax_render_my_participations');

function admin_lab_ajax_render_my_participations() {
    try {
        $user_id = get_current_user_id();
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        $context = 'my_giveaway_participations';
        $meta_key = 'admin_lab_per_page__' . $context;

        $per_page = (int) get_user_meta($user_id, $meta_key, true);
        if (!$per_page) $per_page = 10;

        if (isset($_POST['per_page'])) {
            $new_per_page = max(1, intval($_POST['per_page']));
            if ($new_per_page !== $per_page) {
                update_user_meta($user_id, $meta_key, $new_per_page);
            }
            $per_page = $new_per_page;
        }

        $paged = isset($_POST['pg']) ? max(1, intval($_POST['pg'])) : 1;
        $_GET['pg'] = $paged;

        echo admin_lab_render_participation_table($user_id, $status_filter, $per_page);
    } catch (Throwable $e) {
        wp_send_json_error(['message' => 'Erreur interne AJAX : ' . $e->getMessage()]);
    }

    wp_die();
}

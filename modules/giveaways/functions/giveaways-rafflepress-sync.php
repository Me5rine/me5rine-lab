<?php
// File: modules/giveaways/functions/giveaways-rafflepress-sync.php

if (!defined('ABSPATH')) exit;

function sync_rafflepress_campaign($mode = 'update', $data = []) {
    global $wpdb;

    $rafflepress_id = isset($data['rafflepress_id']) ? intval($data['rafflepress_id']) : 0;
    $user_id        = isset($data['user_id']) ? intval($data['user_id']) : null;

    if (!$rafflepress_id) return false;

    $table = $wpdb->prefix . 'rafflepress_giveaways';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $rafflepress_id));
    if (!$row) return false;

    $settings = json_decode($row->settings, true);
    if (!$settings) return false;

    $start  = date('Y-m-d\TH:i', strtotime($row->starts));
    $end    = date('Y-m-d\TH:i', strtotime($row->ends));
    $now    = current_time('Y-m-d\TH:i', true);
    $status = ($now < $start) ? 'Upcoming' : (($now <= $end) ? 'Ongoing' : 'Finished');

    $post_id = admin_lab_get_post_id_from_rafflepress($rafflepress_id);

    $creating = false;

    if (!$post_id) {
        if ($mode === 'create' && $user_id) {
            $post_id = wp_insert_post([
                'post_title'   => sanitize_text_field($row->name),
                'post_content' => '[custom_rafflepress id="' . $rafflepress_id . '"]',
                'post_status'  => 'draft',
                'post_type'    => 'giveaway',
                'post_author'  => $user_id,
            ]);
            $creating = true;

            update_post_meta($post_id, '_rafflepress_campaign', $rafflepress_id);
            update_post_meta($post_id, '_giveaway_partner_id', $user_id);
        } else {
            return false;
        }
    }

    wp_update_post([
        'ID'         => $post_id,
        'post_title' => sanitize_text_field($row->name)
    ]);

    update_post_meta($post_id, '_giveaway_start_date', $start);
    update_post_meta($post_id, '_giveaway_end_date', $end);
    update_post_meta($post_id, '_giveaway_status', $status);

    if (isset($settings['prizes']) && is_array($settings['prizes'])) {
        $existing  = wp_get_post_terms($post_id, 'giveaway_rewards', ['fields' => 'ids']);
        $new_terms = [];

        $term_ids = [];

        foreach ($settings['prizes'] as $prize) {
            $term_id = admin_lab_register_reward_term($prize['name']);
            if ($term_id) $term_ids[] = $term_id;
        }

        wp_set_post_terms($post_id, $term_ids, 'giveaway_rewards');
    }

    if ($creating) {
        $cat = get_term_by('slug', 'partenaires', 'giveaway_category');
        if (!$cat) $cat = wp_insert_term('Partenaires', 'giveaway_category');
        if (!is_wp_error($cat)) {
            $term_id = is_object($cat) ? $cat->term_id : $cat['term_id'];
            wp_set_post_terms($post_id, [$term_id], 'giveaway_category', false);
        }
    }

    if (!empty($settings['prizes'][0]['image'])) {
        giveaways_set_featured_image_from_prize($post_id, $settings);
    }

    return $post_id;
}

function rafflepress_campaign_sync_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rafflepress_pro_save_giveaway')) {
        wp_send_json_error('Invalid nonce', 400);
    }

    if (empty($_POST['giveaway_id'])) {
        wp_send_json_error('Missing giveaway ID', 400);
    }

    $rafflepress_id = intval($_POST['giveaway_id']);

    $post_id = sync_rafflepress_campaign('update', [
        'rafflepress_id' => $rafflepress_id
    ]);

    if ($post_id === false) {
        wp_send_json_success("No associated WordPress giveaway found.");
    }

    wp_send_json_success("Giveaway #$post_id updated.");
}
add_action('wp_ajax_rafflepress_campaign_sync', 'rafflepress_campaign_sync_ajax');

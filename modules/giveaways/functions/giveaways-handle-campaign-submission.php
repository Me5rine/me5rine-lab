<?php
// File: modules/giveaways/functions/giveaways-handle-campaign-submission.php

if (!defined('ABSPATH')) exit;

function admin_lab_store_campaign_form_data($user_id, $post_data) {
    if (!$user_id || !is_array($post_data)) {
        return;
    }

    $form_data = wp_unslash($post_data);
    unset($form_data['campaign_nonce'], $form_data['submit_campaign']);

    set_transient('rafflepress_form_data_' . (int) $user_id, $form_data, 10 * MINUTE_IN_SECONDS);
    update_user_meta((int) $user_id, '_rafflepress_form_data_backup', $form_data);
}

function admin_lab_set_campaign_notice($user_id, $notice_key, $message, $ttl = 10) {
    set_transient($notice_key, $message, $ttl);
    if ($user_id && $notice_key) {
        update_user_meta((int) $user_id, '_'.$notice_key.'_backup', (string) $message);
    }
}

function admin_lab_clear_campaign_form_data($user_id) {
    if (!$user_id) {
        return;
    }

    delete_transient('rafflepress_form_data_' . (int) $user_id);
    delete_user_meta((int) $user_id, '_rafflepress_form_data_backup');
}

function admin_lab_redirect_back_to_form($redirect_back_url) {
    $fallback = home_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/');
    $target = $redirect_back_url ?: $fallback;

    if (!wp_safe_redirect($target)) {
        wp_safe_redirect($fallback);
    }
    exit;
}

function handle_campaign_submission() {
    if (!isset($_POST['submit_campaign']) || !is_user_logged_in()) return;

    if (!isset($_POST['campaign_nonce']) || !wp_verify_nonce($_POST['campaign_nonce'], 'create_rafflepress_campaign')) {
        wp_die(__('Invalid permission', 'me5rine-lab'));
    }

    $user_id = get_current_user_id();
    // Always persist current form state at submission start.
    // This guarantees restoration even if an unexpected failure occurs
    // before a specific error branch stores data.
    admin_lab_store_campaign_form_data($user_id, $_POST);
    $redirect_back_url = wp_get_referer();
    if (!$redirect_back_url) {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $redirect_back_url = home_url($request_uri);
    }

    $title       = sanitize_text_field($_POST['campaign_title']);
    $description = sanitize_textarea_field($_POST['campaign_description']);
    $start_date  = sanitize_text_field($_POST['campaign_start']);
    $end_date    = sanitize_text_field($_POST['campaign_end']);
    $start_time  = sprintf('%02d:%02d', $_POST['campaign_start_hour'] ?? 0, $_POST['campaign_start_minute'] ?? 0);
    $end_time    = sprintf('%02d:%02d', $_POST['campaign_end_hour'] ?? 23, $_POST['campaign_end_minute'] ?? 59);

    try {
        $tz = new DateTimeZone(get_option('timezone_string') ?: 'UTC');
        $start_dt = new DateTime("$start_date $start_time", $tz);
        $end_dt   = new DateTime("$end_date $end_time", $tz);
        $start_dt->setTimezone(new DateTimeZone('UTC'));
        $end_dt->setTimezone(new DateTimeZone('UTC'));
    } catch (Exception $e) {
        $message = __('Invalid start or end date.', 'me5rine-lab');
        admin_lab_store_campaign_form_data($user_id, $_POST);
        admin_lab_set_campaign_notice($user_id, 'rafflepress_sync_error', $message, 10);
        admin_lab_redirect_back_to_form($redirect_back_url);
    }

    $start_utc = $start_dt->format('Y-m-d H:i:s');
    $end_utc   = $end_dt->format('Y-m-d H:i:s');

    global $wpdb;

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}rafflepress_giveaways WHERE name = %s AND starts = %s AND ends = %s",
        $title, $start_utc, $end_utc
    ));
    if ($existing) {
        $message = __('A RafflePress campaign with the same information already exists.', 'me5rine-lab');
        admin_lab_store_campaign_form_data($user_id, $_POST);
        admin_lab_set_campaign_notice($user_id, 'rafflepress_duplicate_error', $message, 30);
        admin_lab_redirect_back_to_form($redirect_back_url);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $prizes = [];

    if (empty($_POST['prize_name']) || !is_array($_POST['prize_name'])) {
        $message = __('Please add at least one prize.', 'me5rine-lab');
        admin_lab_set_campaign_notice($user_id, 'rafflepress_file_error', $message, 10);
        admin_lab_redirect_back_to_form($redirect_back_url);
    }

    foreach ($_POST['prize_name'] as $i => $name) {
        $name = trim($name);
        if ($name === '') continue;
        $has_image = !empty($_FILES['prize_image_file']['name'][$i]);
        if ($i === 0 && !$has_image) {
            $message = __('The main prize must have an image.', 'me5rine-lab');
            admin_lab_store_campaign_form_data($user_id, $_POST);
            admin_lab_set_campaign_notice($user_id, 'rafflepress_file_error', $message, 10);
            admin_lab_redirect_back_to_form($redirect_back_url);
        }

        $image_url = '';
        if ($has_image) {
            if ($_FILES['prize_image_file']['size'][$i] > 2 * 1024 * 1024) {
                $message = __('The file is too large (2MB max).', 'me5rine-lab');
                admin_lab_store_campaign_form_data($user_id, $_POST);
                admin_lab_set_campaign_notice($user_id, 'rafflepress_file_error', $message, 10);
                admin_lab_redirect_back_to_form($redirect_back_url);
            }

            $checked_type = wp_check_filetype_and_ext(
                $_FILES['prize_image_file']['tmp_name'][$i],
                $_FILES['prize_image_file']['name'][$i]
            );
            $detected_type = $checked_type['type'] ?? '';

            if (!in_array($detected_type, $allowed_types, true)) {
                $message = __('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'me5rine-lab');
                admin_lab_store_campaign_form_data($user_id, $_POST);
                admin_lab_set_campaign_notice($user_id, 'rafflepress_file_error', $message, 10);
                admin_lab_redirect_back_to_form($redirect_back_url);
            }

            $file = [
                'name'     => $_FILES['prize_image_file']['name'][$i],
                'type'     => $_FILES['prize_image_file']['type'][$i],
                'tmp_name' => $_FILES['prize_image_file']['tmp_name'][$i],
                'error'    => $_FILES['prize_image_file']['error'][$i],
                'size'     => $_FILES['prize_image_file']['size'][$i],
            ];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $message = __('Error uploading file.', 'me5rine-lab');
                admin_lab_store_campaign_form_data($user_id, $_POST);
                admin_lab_set_campaign_notice($user_id, 'rafflepress_file_error', $message, 10);
                admin_lab_redirect_back_to_form($redirect_back_url);
            }

            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            $_FILES['prize_image'] = [
                'name'     => $_FILES['prize_image_file']['name'][$i],
                'type'     => $_FILES['prize_image_file']['type'][$i],
                'tmp_name' => $_FILES['prize_image_file']['tmp_name'][$i],
                'error'    => $_FILES['prize_image_file']['error'][$i],
                'size'     => $_FILES['prize_image_file']['size'][$i],
            ];

            $attachment_id = media_handle_upload('prize_image', 0);
            if (is_wp_error($attachment_id)) {
                $message = __('Error uploading file.', 'me5rine-lab');
                admin_lab_store_campaign_form_data($user_id, $_POST);
                admin_lab_set_campaign_notice($user_id, 'rafflepress_file_error', $message, 10);
                admin_lab_redirect_back_to_form($redirect_back_url);
            }

            $image_url = wp_get_attachment_url($attachment_id);
        }

        $prizes[] = [
            'name'        => stripslashes(sanitize_text_field($name)),
            'description' => stripslashes(sanitize_textarea_field($_POST['prize_description'][$i])),
            'image'       => $image_url,
            'video'       => ''
        ];
    }

    if (empty($prizes) || empty($prizes[0]['image'])) {
        $message = __('The main prize must have an image.', 'me5rine-lab');
        admin_lab_store_campaign_form_data($user_id, $_POST);
        admin_lab_set_campaign_notice($user_id, 'rafflepress_file_error', $message, 10);
        admin_lab_redirect_back_to_form($redirect_back_url);
    }

    $entry_options = generate_combined_entry_options($user_id, $_POST['actions'], $post_id ?? null);

    $minimum_age = isset($_POST['minimum_age']) ? (int) $_POST['minimum_age'] : 18;
    
    $eligible_countries = isset($_POST['eligible_countries']) && is_array($_POST['eligible_countries'])
        ? array_map('sanitize_text_field', $_POST['eligible_countries'])
        : [];
    
    $state_province = implode(', ', $eligible_countries);

    $user = get_userdata($user_id);
    $sponsor_name    = $user->display_name;
    $sponsor_email   = $user->user_email;
    $sponsor_country = get_user_meta($user_id, 'country', true) ?: 'France';
    $sponsor_address = '';

    $rules = admin_lab_generate_rafflepress_rules([
        'minimum_age'        => $minimum_age,
        'eligible_countries' => $eligible_countries,
        'start_date'         => $start_date,
        'end_date'           => $end_date,
        'sponsor_name'       => $sponsor_name,
        'sponsor_email'      => $sponsor_email,
        'sponsor_country'    => $sponsor_country,
    ]);

    $rafflepress_id = save_rafflepress_campaign('create', [
        'title'              => $title,
        'description'        => $description,
        'start_date'         => $start_date,
        'end_date'           => $end_date,
        'start_time'         => $start_time,
        'end_time'           => $end_time,
        'start_datetime_utc' => $start_utc,
        'end_datetime_utc'   => $end_utc,
        'prizes'             => $prizes,
        'entry_options'      => $entry_options,
        'minimum_age'        => $minimum_age,
        'eligible_countries' => $eligible_countries,
        'state_province'     => $state_province,
        'sponsor_name'       => $sponsor_name,
        'sponsor_email'      => $sponsor_email,
        'sponsor_country'    => $sponsor_country,
        'sponsor_address'    => $sponsor_address,
        'rules'              => $rules,
    ]);

    if (!$rafflepress_id) {
        $message = __('An error occurred while creating the RafflePress campaign.', 'me5rine-lab');
        admin_lab_store_campaign_form_data($user_id, $_POST);
        admin_lab_set_campaign_notice($user_id, 'rafflepress_sync_error', $message, 10);
        admin_lab_redirect_back_to_form($redirect_back_url);
    }

    $post_id = sync_rafflepress_campaign('create', [
        'rafflepress_id' => $rafflepress_id,
        'user_id'        => $user_id
    ]);
    
    if ($post_id) {
        admin_lab_register_rafflepress_index($rafflepress_id, $post_id);
        
        if (!empty($prizes[0]['image'])) {
            giveaways_set_featured_image_from_prize($post_id, ['prizes' => $prizes]);
        }

        update_post_meta($post_id, '_giveaway_start_date', $start_utc);
        update_post_meta($post_id, '_giveaway_end_date', $end_utc);

        $now = current_time('mysql', 1);
        $status = ($start_utc > $now) ? 'Upcoming' : (($end_utc < $now) ? 'Finished' : 'Ongoing');
        update_post_meta($post_id, '_giveaway_status', $status);

        admin_lab_set_campaign_notice($user_id, 'rafflepress_campaign_success', __('Giveaway created successfully!', 'me5rine-lab'), 10);
        admin_lab_clear_campaign_form_data($user_id);

        $redirect_url = isset($_POST['redirect_url']) && is_string($_POST['redirect_url']) && $_POST['redirect_url'] !== ''
            ? esc_url_raw(wp_unslash($_POST['redirect_url']))
            : (isset($_GET['redirect_url']) ? urldecode(wp_unslash($_GET['redirect_url'])) : home_url());
        wp_redirect(get_permalink() . '?redirect_url=' . urlencode($redirect_url));
        exit;
    }

    $message = __('The giveaway was created in RafflePress, but linking to a WordPress giveaway failed.', 'me5rine-lab');
    admin_lab_store_campaign_form_data($user_id, $_POST);
    admin_lab_set_campaign_notice($user_id, 'rafflepress_sync_error', $message, 10);
    admin_lab_redirect_back_to_form($redirect_back_url);
}

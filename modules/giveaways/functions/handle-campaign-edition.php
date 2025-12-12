<?php
// File: modules/giveaways/functions/handle-campaign-edition.php

if (!defined('ABSPATH')) exit;

function handle_campaign_edition() {

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['edit_campaign'])
        && isset($_POST['campaign_nonce'])
        && wp_verify_nonce($_POST['campaign_nonce'], 'edit_rafflepress_campaign')
    ) {
        global $wpdb;

        $campaign_id = intval($_POST['campaign_id']);
        $title       = sanitize_text_field($_POST['campaign_title']);
        $description = sanitize_textarea_field($_POST['campaign_description']);
        $start_date  = sanitize_text_field($_POST['campaign_start']);
        $end_date    = sanitize_text_field($_POST['campaign_end']);
        $start_hour  = sanitize_text_field($_POST['campaign_start_hour']);
        $start_min   = sanitize_text_field($_POST['campaign_start_minute']);
        $end_hour    = sanitize_text_field($_POST['campaign_end_hour']);
        $end_min     = sanitize_text_field($_POST['campaign_end_minute']);

        $_POST['campaign_start_hour'] = $start_hour;
        $_POST['campaign_start_minute'] = $start_min;
        $_POST['campaign_end_hour'] = $end_hour;
        $_POST['campaign_end_minute'] = $end_min;

        $tz = new DateTimeZone(get_option('timezone_string') ?: 'UTC');
        $start_dt = new DateTime("$start_date $start_hour:$start_min", $tz);
        $end_dt   = new DateTime("$end_date $end_hour:$end_min", $tz);

        $start_dt->setTimezone(new DateTimeZone('UTC'));
        $end_dt->setTimezone(new DateTimeZone('UTC'));

        $start_utc = $start_dt->format('Y-m-d H:i:s');
        $end_utc   = $end_dt->format('Y-m-d H:i:s');

        $existing_entry_options = [];
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT settings FROM {$wpdb->prefix}rafflepress_giveaways WHERE id = %d",
            $campaign_id
        ));

        if ($existing) {
            $settings = json_decode($existing->settings, true);
            if (!empty($settings['entry_options'])) {
                foreach ($settings['entry_options'] as $opt) {
                    if (isset($opt['type'])) {
                        $existing_entry_options[$opt['type']][] = $opt;
                    }
                }
            }
        }

$user_id = get_current_user_id();
$socials = get_socials_for_giveaway($user_id);

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $prizes = [];

        foreach ($_POST['prize_name'] as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;

            $image_url = $_POST['existing_prize_image'][$i] ?? '';

            if (empty($image_url) && empty($_FILES['prize_image_file']['name'][$i])) {
                set_transient('rafflepress_file_error', __('Each prize must have an image.', 'me5rine-lab'), 10);
                return;
            }

            if (!empty($_FILES['prize_image_file']['name'][$i])) {
                delete_transient('rafflepress_file_error');

                if ($_FILES['prize_image_file']['size'][$i] > 2 * 1024 * 1024) {
                    set_transient('rafflepress_file_error', __('The file is too large (2MB max).', 'me5rine-lab'), 10);
                    return;
                }

                if (!in_array($_FILES['prize_image_file']['type'][$i], $allowed_types)) {
                    set_transient('rafflepress_file_error', __('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'me5rine-lab'), 10);
                    return;
                }

                if (!function_exists('media_handle_upload')) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                }

                $_FILES['single_image'] = [
                    'name'     => $_FILES['prize_image_file']['name'][$i],
                    'type'     => $_FILES['prize_image_file']['type'][$i],
                    'tmp_name' => $_FILES['prize_image_file']['tmp_name'][$i],
                    'error'    => $_FILES['prize_image_file']['error'][$i],
                    'size'     => $_FILES['prize_image_file']['size'][$i]
                ];

                $upload = media_handle_upload('single_image', 0);

                if (!is_wp_error($upload)) {
                    $image_url = wp_get_attachment_url($upload);
                } else {
                    set_transient('rafflepress_file_error', __('Upload failed.', 'me5rine-lab'), 10);
                    return;
                }
            }

            $prizes[] = [
                'name' => stripslashes(sanitize_text_field($name)),
                'description' => stripslashes(sanitize_textarea_field($_POST['prize_description'][$i])),
                'image' => $image_url,
                'video' => ''
            ];
        }

        $actions = $_POST['actions'] ?? [];
        $entry_options = generate_combined_entry_options($user_id, $_POST['actions'], $post_id ?? null);

        $minimum_age = isset($_POST['minimum_age']) ? (int) $_POST['minimum_age'] : 18;
        $eligible_countries = isset($_POST['eligible_countries']) && is_array($_POST['eligible_countries'])
            ? array_map('sanitize_text_field', $_POST['eligible_countries'])
            : [];
        $state_province = implode(', ', $eligible_countries);

        $sponsor_name    = get_userdata($user_id)->display_name;
        $sponsor_email   = get_userdata($user_id)->user_email;
        $sponsor_country = get_user_meta($user_id, 'country', true) ?: 'France';

        $rules = admin_lab_generate_rafflepress_rules([
            'minimum_age'        => $minimum_age,
            'eligible_countries' => $eligible_countries,
            'start_date'         => $start_date,
            'end_date'           => $end_date,
            'sponsor_name'       => $sponsor_name,
            'sponsor_email'      => $sponsor_email,
            'sponsor_country'    => $sponsor_country,
        ]);

        $data = [
            'campaign_id'        => $campaign_id,
            'title'              => $title,
            'description'        => $description,
            'start_date'         => $start_date,
            'end_date'           => $end_date,
            'start_time'         => "$start_hour:$start_min",
            'end_time'           => "$end_hour:$end_min",
            'start_datetime_utc' => $start_utc,
            'end_datetime_utc'   => $end_utc,
            'prizes'             => $prizes,
            'entry_options'      => $entry_options,
            'minimum_age'        => $minimum_age,
            'eligible_countries' => $eligible_countries,
            'sponsor_name'       => $sponsor_name,
            'sponsor_email'      => $sponsor_email,
            'sponsor_country'    => $sponsor_country,
            'rules'              => $rules
        ];

        $result = save_rafflepress_campaign('update', $data);

        if ($result) {
            $post_id = sync_rafflepress_campaign('update', [
                'rafflepress_id' => $campaign_id
            ]);

            if ($post_id) {
                set_transient('rafflepress_campaign_success', __('Your giveaway has been successfully updated!', 'me5rine-lab'), 10);
                $redirect_url = isset($_GET['redirect_url']) ? urldecode($_GET['redirect_url']) : get_permalink($post_id);
                wp_redirect(add_query_arg('redirect_url', urlencode($redirect_url), get_permalink()));
                exit;
            } else {
                set_transient('rafflepress_sync_error', __('An error occurred while updating the giveaway post.', 'me5rine-lab'), 10);
                wp_redirect(get_permalink());
                exit;
            }
        }
    }
}

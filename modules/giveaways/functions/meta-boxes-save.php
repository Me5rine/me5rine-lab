<?php
// File: modules/giveaways/functions/meta-boxes-save.php

if (!defined('ABSPATH')) exit;

function giveaways_save_meta($post_id) {
    global $wpdb;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['giveaways_meta_box_nonce']) || !wp_verify_nonce($_POST['giveaways_meta_box_nonce'], 'giveaways_meta_box_nonce')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'giveaway') return;

    if (isset($_GET['action']) && $_GET['action'] === 'publish_giveaway') {
        return;
    }

    remove_action('save_post', 'giveaways_save_meta');

    $rafflepress_id = isset($_POST['rafflepress_campaign']) ? sanitize_text_field($_POST['rafflepress_campaign']) : admin_lab_get_rafflepress_id_from_post($post_id);
    
    if (!$rafflepress_id) {
        return;
    }

    $current_campaign_id = admin_lab_get_rafflepress_id_from_post($post_id);
    if ($rafflepress_id !== $current_campaign_id) {
        update_post_meta($post_id, '_rafflepress_campaign', $rafflepress_id);
        admin_lab_register_rafflepress_index($rafflepress_id, $post_id);
    }

    $existing_post_id = admin_lab_get_post_id_from_rafflepress($rafflepress_id, $post_id);

    if ($existing_post_id) {
        admin_lab_add_admin_notice(
            sprintf(
                __('The RafflePress #%1$d campaign is already linked to the contest : <a href="%2$s">%3$s</a>', 'me5rine-lab'),
                $rafflepress_id,
                get_edit_post_link($existing_post_id),
                get_the_title($existing_post_id)
            ),
            'error'
        );
        return;
    }

    $meta_updates = [];

    $table_name = admin_lab_getTable('rafflepress_giveaways', false);
    $rafflepress_data = $wpdb->get_row(
        $wpdb->prepare("SELECT name, starts, ends, settings FROM {$table_name} WHERE id = %d", $rafflepress_id)
    );
    
    if ($rafflepress_data) {
        wp_update_post([ 'ID' => $post_id, 'post_title' => $rafflepress_data->name ]);

        $existing_meta = get_post_meta($post_id);

        $start_date = sanitize_text_field($_POST['giveaway_start_date'] ?? '');
        $start_hour = sanitize_text_field($_POST['giveaway_start_hour'] ?? '');
        $start_minute = sanitize_text_field($_POST['giveaway_start_minute'] ?? '');
        $end_date = sanitize_text_field($_POST['giveaway_end_date'] ?? '');
        $end_hour = sanitize_text_field($_POST['giveaway_end_hour'] ?? '');
        $end_minute = sanitize_text_field($_POST['giveaway_end_minute'] ?? '');

        $tz = new DateTimeZone(get_option('timezone_string') ?: 'UTC');

        $start = ($start_date && $start_hour && $start_minute) ? new DateTime("$start_date $start_hour:$start_minute", $tz) : new DateTime('now', $tz);
        $end = ($end_date && $end_hour && $end_minute) ? new DateTime("$end_date $end_hour:$end_minute", $tz) : new DateTime('now', $tz);

        $start->setTimezone(new DateTimeZone('UTC'));
        $end->setTimezone(new DateTimeZone('UTC'));

        $rafflepress_start_date_utc = $start->format('Y-m-d H:i');
        $rafflepress_end_date_utc = $end->format('Y-m-d H:i');

        $wordpress_start_date_utc = $start->format('Y-m-d\TH:i');
        $wordpress_end_date_utc = $end->format('Y-m-d\TH:i');

        $start->setTimezone($tz);
        $end->setTimezone($tz);

        $rafflepress_start_time_local = $start->format('H:i');
        $rafflepress_end_time_local = $end->format('H:i');

        $existing_start = $existing_meta['_giveaway_start_date'][0] ?? '';
        $existing_end = $existing_meta['_giveaway_end_date'][0] ?? '';

        $do_not_update = $_POST['do_not_update_rafflepress_dates'] ?? null;

        if ($do_not_update) {
            $meta_updates['_giveaway_start_date'] = $rafflepress_data->starts;
            $meta_updates['_giveaway_end_date'] = $rafflepress_data->ends;

            $update_rafflepress_settings = false; 
        } else {
            $meta_updates['_giveaway_start_date'] = $rafflepress_start_date_utc;
            $meta_updates['_giveaway_end_date'] = $rafflepress_end_date_utc;

            $update_rafflepress_settings = true;

            $disabled_input = isset($_POST['disable_admin_lab_actions']) ? '1' : '';
            $existing_disabled = $existing_meta['_disable_admin_lab_actions'][0] ?? '';

            if ($disabled_input !== $existing_disabled) {
                if ($disabled_input) {
                    $meta_updates['_disable_admin_lab_actions'] = $disabled_input;
                } else {
                    delete_post_meta($post_id, '_disable_admin_lab_actions');
                }
            }

            // État final après mise à jour
            $will_be_disabled = ($disabled_input === '1');

            $settings_data = isset($rafflepress_data->settings) ? json_decode($rafflepress_data->settings, true) : [];

            if ($settings_data) {
                $settings_data['starts'] = $start->format('Y-m-d');
                $settings_data['starts_time'] = $rafflepress_start_time_local;
                $settings_data['ends'] = $end->format('Y-m-d');
                $settings_data['ends_time'] = $rafflepress_end_time_local;

                // Supprimer les actions automatiques déjà présentes
                if (isset($settings_data['entry_options']) && is_array($settings_data['entry_options'])) {
                    $settings_data['entry_options'] = array_values(array_filter($settings_data['entry_options'], function ($entry) {
                        return !(
                            ($entry['id'] ?? '') === 'separator' ||
                            ($entry['id'] ?? '') === 'discord_2' ||
                            !empty($entry['is_admin_lab'])
                        );
                    }));
                }

                // Si non désactivé → ajouter les entrées Me5rine
                if (!$will_be_disabled) {
                    $partner_id = isset($_POST['giveaway_partner_id']) ? $_POST['giveaway_partner_id'] : '';
                    if ($partner_id) {
                        $user = get_userdata($partner_id);
                        $roles = (array) $user->roles;

                        if (in_array('um_partenaire', $roles) && !in_array('um_partenaire_plus', $roles)) {
                            $actions = [];
                            $entries = generate_combined_entry_options($partner_id, $actions, $post_id);
                            $bonus_entries = array_filter($entries, function ($entry) {
                                return !empty($entry['is_admin_lab']);
                            });

                            $settings_data['entry_options'] = array_merge($settings_data['entry_options'], array_values($bonus_entries));
                        }
                    }
                }

                if ($update_rafflepress_settings && $settings_data) {
                    $updated_settings = wp_json_encode($settings_data);

                    $wpdb->update(
                        $table_name,
                        [
                            'starts'   => $rafflepress_start_date_utc,
                            'ends'     => $rafflepress_end_date_utc,
                            'settings' => $updated_settings,
                        ],
                        ['id' => $rafflepress_id],
                        ['%s', '%s', '%s'],
                        ['%d']
                    );
                }
            }
        }

        $settings = json_decode($rafflepress_data->settings, true);
        if (isset($settings['prizeTitle'])) {
            $reward_location = sanitize_text_field($settings['prizeTitle']);
            $existing_reward_location = $existing_meta['_giveaway_reward_location'][0] ?? '';

            if ($reward_location !== $existing_reward_location) {
                $meta_updates['_giveaway_reward_location'] = $reward_location;
            }
        }
        $start_gmt = $meta_updates['_giveaway_start_date'] ?? ($existing_meta['_giveaway_start_date'][0] ?? '');
        $end_gmt = $meta_updates['_giveaway_end_date'] ?? ($existing_meta['_giveaway_end_date'][0] ?? '');
        $now = new DateTime('now', new DateTimeZone('UTC'));

        if (!empty($start_gmt) && !empty($end_gmt)) {
            $start = new DateTime($start_gmt, new DateTimeZone('UTC'));
            $end = new DateTime($end_gmt, new DateTimeZone('UTC'));

            $status = ($now >= $start && $now <= $end) ? 'Ongoing' : (($now > $end) ? 'Finished' : 'Upcoming');
            $existing_status = $existing_meta['_giveaway_status'][0] ?? '';
            if ($status !== $existing_status) {
                $meta_updates['_giveaway_status'] = $status;
            }            
        }

        $shortcode = '[custom_rafflepress id="' . $rafflepress_id . '"]';
        $post = get_post($post_id);
        $content = $post->post_content;
        $updated_content = (strpos($content, '[custom_rafflepress id=') === false)
            ? $content . "\n\n" . $shortcode
            : preg_replace('/\[custom_rafflepress id="\d+"\]/', $shortcode, $content);

        wp_update_post([ 'ID' => $post_id, 'post_content' => $updated_content ]);

        $partner_id = sanitize_text_field($_POST['giveaway_partner_id'] ?? '');
        $existing_partner_id = $existing_meta['_giveaway_partner_id'][0] ?? '';
        if ($partner_id !== $existing_partner_id) {
            $meta_updates['_giveaway_partner_id'] = $partner_id;
        } elseif (empty($partner_id) && !empty($existing_partner_id)) {
            delete_post_meta($post_id, '_giveaway_partner_id');
        }

        if (isset($settings['prizes']) && is_array($settings['prizes'])) {
            $existing_rewards = wp_get_post_terms($post_id, 'giveaway_rewards', ['fields' => 'ids']);
            $new_rewards = [];
            $errors = false;

            foreach ($settings['prizes'] as $reward) {
                $reward_name = sanitize_text_field($reward['name']);
                $term_id = admin_lab_register_reward_term($reward_name);

                if ($term_id) {
                    $new_rewards[] = $term_id;
                } else {
                    $errors = true;
                }
            }

            $to_remove = array_diff($existing_rewards, $new_rewards);
            if (!empty($to_remove)) {
                wp_remove_object_terms($post_id, $to_remove, 'giveaway_rewards');
            }

            wp_set_post_terms($post_id, $new_rewards, 'giveaway_rewards');

            if (!$errors) {
                admin_lab_add_admin_notice(__('Rewards successfully updated for this contest.', 'me5rine-lab'), 'success');
            } else {
                admin_lab_add_admin_notice(__('An error occurred while updating rewards.', 'me5rine-lab'), 'error');
            }
        }

        if ($now >= $start) {
            $result = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM {$wpdb->prefix}rafflepress_contestants WHERE giveaway_id = %d) AS participants,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}rafflepress_entries WHERE giveaway_id = %d) AS entries
            ", $rafflepress_id, $rafflepress_id), ARRAY_A);
        
            $participant_count = $result['participants'] ?? 0;
            $entry_count = $result['entries'] ?? 0;
        
            $existing_participants = $existing_meta['_giveaway_participants_count'][0] ?? '';
            $existing_entries = $existing_meta['_giveaway_entries_count'][0] ?? '';
        
            if ($participant_count != $existing_participants) {
                $meta_updates['_giveaway_participants_count'] = $participant_count;
            }
            if ($entry_count != $existing_entries) {
                $meta_updates['_giveaway_entries_count'] = $entry_count;
            }
        }        
    }

    $partner_id = isset($_POST['giveaway_partner_id']) ? $_POST['giveaway_partner_id'] : '';
    $category = !empty($partner_id) ? 'Partenaires' : 'Me5rine LAB';

    $term = term_exists($category, 'giveaway_category');
    if (!$term || is_wp_error($term)) {
        $term = wp_insert_term($category, 'giveaway_category');
    }

    if (!is_wp_error($term) && isset($term['term_id'])) {
        wp_set_post_terms($post_id, [$term['term_id']], 'giveaway_category');
    }

    if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) return;

    $already_updating = $existing_meta['_is_being_updated'][0] ?? '';

    if ($already_updating) return;

    $meta_updates['_is_being_updated'] = true;   

    $post = get_post($post_id);
    $original_slug = $post->post_name;
    $new_slug = sanitize_title($post->post_title);

    if ($original_slug !== $new_slug) {
        $meta_updates['post_name'] = $new_slug;
    }    

    foreach ($meta_updates as $meta_key => $meta_value) {
        update_post_meta($post_id, $meta_key, $meta_value);
    }

    foreach ($meta_deletes ?? [] as $meta_key) {
        delete_post_meta($post_id, $meta_key);
    }    

    delete_post_meta($post_id, '_is_being_updated');
    add_action('save_post', 'giveaways_save_meta');
}

add_action('save_post', 'giveaways_save_meta');

<?php
// File: modules/giveaways/admin-filters/giveaways-table.php

if (!defined('ABSPATH')) {
    exit;
}

function set_custom_giveaway_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = __('Title', 'me5rine-lab');
    $new_columns['giveaway_start_date'] = __('Start Date', 'me5rine-lab');
    $new_columns['giveaway_end_date'] = __('End Date', 'me5rine-lab');
    $new_columns['giveaway_partner'] = __('Partner & Reward', 'me5rine-lab');
    $new_columns['giveaway_status'] = __('Status', 'me5rine-lab');
    $new_columns['giveaway_participants'] = __('Participants', 'me5rine-lab');
    $new_columns['giveaway_entries'] = __('Entries', 'me5rine-lab');
    $new_columns['giveaway_actions'] = __('Actions', 'me5rine-lab');
    return $new_columns;
}

function custom_giveaway_column($column, $post_id) {
    switch ($column) {
        case 'giveaway_start_date':
            $start_date = get_post_meta($post_id, '_giveaway_start_date', true);

            if ($start_date) {
                $tz = new DateTimeZone(get_option('timezone_string'));
                $start = new DateTime($start_date, new DateTimeZone('UTC'));
                $start->setTimezone($tz);
                echo $start->format(get_option('date_format') . ' ' . get_option('time_format'));
            } else {
                echo '-';
            }
            break;

        case 'giveaway_end_date':
            $end_date = get_post_meta($post_id, '_giveaway_end_date', true);

            if ($end_date) {
                $tz = new DateTimeZone(get_option('timezone_string'));
                $end = new DateTime($end_date, new DateTimeZone('UTC'));
                $end->setTimezone($tz);
                echo $end->format(get_option('date_format') . ' ' . get_option('time_format'));
            } else {
                echo '-';
            }
            break;

        case 'giveaway_partner':
            $partner_id = get_post_meta($post_id, '_giveaway_partner_id', true);
            if ($partner_id) {
                $user = get_user_by('ID', $partner_id);
                $profile_url = home_url('/profil/' . $user->user_nicename . '/');
                echo 'Partner : <a href="' . esc_url($profile_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($user->display_name) . '</a>';
            } else {
                echo __('No partner', 'me5rine-lab');
            }
            echo '<br>';

            $rewards = wp_get_post_terms($post_id, 'giveaway_rewards');
            if (!empty($rewards)) {
                $reward_names = wp_list_pluck($rewards, 'name');
                $reward_count = count($reward_names);
                $reward_text = implode(', ', $reward_names);
                echo ($reward_count > 1 ? $reward_count . ' Récompenses : ' . $reward_text : '1 Récompense : ' . $reward_text);
            } else {
                echo __('Pas de récompense', 'me5rine-lab');
            }
            break;

        case 'giveaway_status':
            $status = get_post_meta($post_id, '_giveaway_status', true);
            echo $status ? esc_html($status) : '-';
            break;

        case 'giveaway_actions':
            $output = '';

            // Bouton Edit in RafflePress
            $rafflepress_campaign_id = get_post_meta($post_id, '_rafflepress_campaign', true);
            if ($rafflepress_campaign_id) {
                $rafflepress_url = add_query_arg(['page' => 'rafflepress_pro_builder', 'id' => $rafflepress_campaign_id], admin_url('admin.php')) . '#/setup/' . $rafflepress_campaign_id;
                $output .= '<a href="' . esc_url($rafflepress_url) . '" class="button button-primary" target="_blank">' . __('Edit in RafflePress', 'me5rine-lab') . '</a>';
            }

            // Bouton Publish now si brouillon
            $post = get_post($post_id);
            if ($post && $post->post_status === 'draft') {
                $url = wp_nonce_url(
                    admin_url('admin-post.php?action=publish_giveaway&post_id=' . $post_id),
                    'publish_giveaway_' . $post_id
                );
                $output .= '<a href="' . esc_url($url) . '" class="button button-secondary">' . __('Publish now', 'me5rine-lab') . '</a>';
            }

            echo $output ?: '-';
            break;            

        case 'giveaway_winner':
            $rafflepress_campaign_id = get_post_meta($post_id, '_rafflepress_campaign', true);
            if ($rafflepress_campaign_id) {
                global $wpdb;
        
                $query = "
                    SELECT c.email
                    FROM {$wpdb->prefix}rafflepress_contestants c
                    WHERE c.giveaway_id = %d AND c.winner = 1
                ";
        
                $winners_data = $wpdb->get_results($wpdb->prepare($query, $rafflepress_campaign_id));
        
                if ($winners_data) {
                    $winner_links = [];
        
                    foreach ($winners_data as $winner_data) {
                        $winner_email = $winner_data->email;
                        $user = get_user_by('email', $winner_email);
        
                        if ($user) {
                            $profile_url = home_url('/profil/' . $user->user_nicename . '/');
                            $winner_links[] = '<a href="' . esc_url($profile_url) . '" target="_blank">' . esc_html($user->display_name) . '</a>';
                        } else {
                            $winner_links[] = __('Winner not found in WordPress', 'me5rine-lab');
                        }
                    }
        
                    echo implode(', ', $winner_links);
                } else {
                    $rafflepress_url = admin_url('admin.php?page=rafflepress_pro#/contestants/' . $rafflepress_campaign_id);
                    $table_name = $wpdb->prefix . 'rafflepress_giveaways';
                    $rafflepress_data = $wpdb->get_row($wpdb->prepare("SELECT ends FROM {$table_name} WHERE id = %d", $rafflepress_campaign_id));
        
                    if ($rafflepress_data) {
                        $end_date = strtotime($rafflepress_data->ends);
                        $current_date = current_time('timestamp');
        
                        if ($current_date >= $end_date) {
                            echo '<a href="' . esc_url($rafflepress_url) . '" target="_blank">' . __('Pick winner', 'me5rine-lab') . '</a>';
                        }
                    }
                }
        
                if (!$winners_data) {
                    $rafflepress_data = $wpdb->get_row($wpdb->prepare("SELECT starts, ends FROM {$table_name} WHERE id = %d", $rafflepress_campaign_id));
        
                    if ($rafflepress_data) {
                        $start_date = strtotime($rafflepress_data->starts);
                        $end_date = strtotime($rafflepress_data->ends);
                        $current_date = current_time('timestamp');
                        
                        $label = ($current_date < $start_date) ? __('Starts in ', 'me5rine-lab') : __('Ends in ', 'me5rine-lab');
                        $timestamp = ($current_date < $start_date) ? $start_date : $end_date;
                        $time_left = admin_lab_format_time_remaining($timestamp, $current_date);

                        if ($time_left) {
                            echo esc_html($label . $time_left);
                        }
                    }
                }
            } else {
                echo '-';
            }
            break; 
            
        case 'giveaway_participants':
            $participant_count = get_post_meta($post_id, '_giveaway_participants_count', true);
            if ($participant_count) {
                echo esc_html($participant_count);
            } else {
                echo '-';
            }
            break;
        
        case 'giveaway_entries':
            $action_count = get_post_meta($post_id, '_giveaway_entries_count', true);
            if ($action_count) {
                echo esc_html($action_count);
            } else {
                echo '-';
            }
            break;            
    }
}

function giveaways_sortable_columns($columns) {
    $columns['giveaway_start_date'] = 'giveaway_start_date';
    $columns['giveaway_end_date'] = 'giveaway_end_date';
    $columns['giveaway_status'] = 'giveaway_status';
    $columns['giveaway_participants'] = 'giveaway_participants';
    $columns['giveaway_entries'] = 'giveaway_entries';
    return $columns;
}

function giveaways_sort_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ($orderby === 'giveaway_participants') {
        $query->set('meta_key', '_giveaway_participants_count');
        $query->set('orderby', 'meta_value_num');
    } elseif ($orderby === 'giveaway_entries') {
        $query->set('meta_key', '_giveaway_entries_count');
        $query->set('orderby', 'meta_value_num');
    } elseif (in_array($orderby, ['giveaway_start_date', 'giveaway_end_date', 'giveaway_status'], true)) {
        $query->set('meta_key', "_{$orderby}");
        $query->set('orderby', 'meta_value');
    }
}

add_filter('manage_edit-giveaway_columns', 'set_custom_giveaway_columns');
add_action('manage_giveaway_posts_custom_column', 'custom_giveaway_column', 10, 2);

add_filter('manage_edit-giveaway_sortable_columns', 'giveaways_sortable_columns');
add_action('pre_get_posts', 'giveaways_sort_query');
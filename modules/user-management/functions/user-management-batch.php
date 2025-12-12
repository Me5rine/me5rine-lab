<?php
// File: modules/user-management/functions/user-management-batch.php

if (!defined('ABSPATH')) exit;

function admin_lab_update_user_display_batch() {
    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . "user_slugs";

    delete_option('admin_lab_update_display_offset');

    $batch_size = defined('ADMIN_LAB_BATCH_SIZE') ? ADMIN_LAB_BATCH_SIZE : 200;
    $offset = (int) get_option('admin_lab_update_display_offset', 0);

    $total_users = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}");
    update_option('admin_lab_progress_total', $total_users);
    update_option('admin_lab_progress_current', 0);

    $users = $wpdb->get_results($wpdb->prepare("
        SELECT ID FROM {$wpdb->users} 
        LIMIT %d OFFSET %d
    ", $batch_size, $offset));

    if (empty($users)) {
        delete_option('admin_lab_progress_total');
        delete_option('admin_lab_progress_current');
        delete_option('admin_lab_update_display_offset');
        return;
    }

    foreach ($users as $user) {
        $user_data = get_userdata($user->ID);
        if (!$user_data) continue;

        // Générer display_name
        $new_display_name = admin_lab_get_display_name($user_data);

        if (!empty($new_display_name) && $user_data->display_name !== $new_display_name) {
            wp_update_user([
                'ID' => $user->ID,
                'display_name' => $new_display_name
            ]);
        }

        // Générer slug
        $new_slug = admin_lab_clean_slug($new_display_name);
        $slug_data = $wpdb->get_row($wpdb->prepare("SELECT user_slug, user_slug_id FROM {$table} WHERE user_id = %d", $user->ID));

        if (!$slug_data) {
            $user_slug_id = admin_lab_generate_unique_slug_id($table);
            $wpdb->insert($table, [
                'user_id'      => $user->ID,
                'user_slug'    => $new_slug,
                'user_slug_id' => $user_slug_id
            ]);
        } else {
            $user_slug_id = $slug_data->user_slug_id;
            if ($slug_data->user_slug !== $new_slug) {
                $wpdb->update($table, ['user_slug' => $new_slug], ['user_id' => $user->ID]);
            }
        }

        // Générer user_nicename
        $new_nicename = $new_slug . '-' . $user_slug_id;
        if ($user_data->user_nicename !== $new_nicename) {
            wp_update_user([
                'ID' => $user->ID,
                'user_nicename' => $new_nicename
            ]);
        }

        admin_lab_sync_um_permalink_meta($user->ID, $new_nicename);

        // Mise à jour progression
        $progress_current = get_option('admin_lab_progress_current', 0) + 1;
        $total_users = (int) get_option('admin_lab_progress_total', 0);

        if ($progress_current > $total_users) {
            $progress_current = $total_users;
        }

        update_option('admin_lab_progress_current', $progress_current);

        if ($progress_current >= $total_users) {
            delete_option('admin_lab_progress_total');
            delete_option('admin_lab_progress_current');
            delete_option('admin_lab_update_display_offset');
        }
    }

    update_option('admin_lab_update_display_offset', $offset + $batch_size);
}

add_action('wp_ajax_admin_lab_batch_next', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Access denied']);
    }

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $batch_size = defined('ADMIN_LAB_BATCH_SIZE') ? ADMIN_LAB_BATCH_SIZE : 200;

    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . "user_slugs";

    $total_users = (int) get_option('admin_lab_progress_total', $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}"));
    $users = $wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->users} LIMIT %d OFFSET %d", $batch_size, $offset));

    if (empty($users)) {
        delete_option('admin_lab_progress_total');
        delete_option('admin_lab_progress_current');
        wp_send_json_success(['done' => true]);
    }

    foreach ($users as $user) {
        $user_data = get_userdata($user->ID);
        if (!$user_data) continue;

        $new_display_name = admin_lab_get_display_name($user_data);
        wp_update_user(['ID' => $user->ID, 'display_name' => $new_display_name]);

        $new_slug = admin_lab_clean_slug($new_display_name);
        $slug_data = $wpdb->get_row($wpdb->prepare("SELECT user_slug, user_slug_id FROM {$table} WHERE user_id = %d", $user->ID));

        if (!$slug_data) {
            $user_slug_id = admin_lab_generate_unique_slug_id($table);
            $wpdb->insert($table, ['user_id' => $user->ID, 'user_slug' => $new_slug, 'user_slug_id' => $user_slug_id]);
        } else {
            $user_slug_id = $slug_data->user_slug_id;
            if ($slug_data->user_slug !== $new_slug) {
                $wpdb->update($table, ['user_slug' => $new_slug], ['user_id' => $user->ID]);
            }
        }

        $new_nicename = $new_slug . '-' . $user_slug_id;
        wp_update_user(['ID' => $user->ID, 'user_nicename' => $new_nicename]);

        admin_lab_sync_um_permalink_meta($user->ID, $new_nicename);

        $progress_current = get_option('admin_lab_progress_current', 0) + 1;
        update_option('admin_lab_progress_current', $progress_current);
    }

    $has_more = ($offset + $batch_size) < $total_users;
    wp_send_json_success([
        'offset'   => $offset + $batch_size,
        'has_more' => $has_more,
        'current'  => get_option('admin_lab_progress_current', 0),
        'total'    => $total_users,
        'percent'  => round(get_option('admin_lab_progress_current', 0) / $total_users * 100, 2)
    ]);
});

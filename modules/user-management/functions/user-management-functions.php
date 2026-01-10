<?php
// File: modules/user-management/functions/user-management-functions.php

if (!defined('ABSPATH')) exit;

// Nettoyer un slug (accents, caractères spéciaux, espaces)
function admin_lab_clean_slug($string) {
    $string = remove_accents($string);
    $string = preg_replace('/[^a-zA-Z0-9-]/', '-', $string);
    return strtolower(trim(preg_replace('/-+/', '-', $string), '-'));
}

// Générer un ID unique
function admin_lab_generate_unique_slug_id() {
    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . "user_slugs";

    do {
        $slug_id = str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_slug_id = %s", $slug_id));
    } while ($exists);

    return $slug_id;
}

// Générer le user_nicename à partir du slug + id
function admin_lab_generate_user_nicename($user_id) {
    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . "user_slugs";

    $slug_data = $wpdb->get_row($wpdb->prepare("SELECT user_slug, user_slug_id FROM {$table} WHERE user_id = %d", $user_id));

    if (!$slug_data) {
        $user = get_userdata($user_id);
        if (!$user) return false;

        $user_slug = admin_lab_clean_slug($user->display_name);
        if (empty($user_slug)) return false;

        $user_slug_id = admin_lab_generate_unique_slug_id();
        $wpdb->insert($table, [
            'user_id'      => $user_id,
            'user_slug'    => $user_slug,
            'user_slug_id' => $user_slug_id
        ]);
    } else {
        $user_slug = $slug_data->user_slug;
        $user_slug_id = $slug_data->user_slug_id;
    }

    return $user_slug . '-' . $user_slug_id;
}

// Récupérer le display_name selon l’option définie
function admin_lab_get_display_name($user) {
    if (!$user) return '';

    $type = get_option('admin_lab_display_name_type', 'display_name');

    switch ($type) {
        case 'user_login': return sanitize_text_field($user->user_login);
        case 'first_name': return !empty($user->first_name) ? sanitize_text_field($user->first_name) : sanitize_text_field($user->user_login);
        case 'last_name': return !empty($user->last_name) ? sanitize_text_field($user->last_name) : sanitize_text_field($user->user_login);
        case 'nickname': return !empty($user->nickname) ? sanitize_text_field($user->nickname) : sanitize_text_field($user->user_login);
        case 'first_last': return trim(sanitize_text_field($user->first_name . ' ' . $user->last_name)) ?: sanitize_text_field($user->user_login);
        case 'last_first': return trim(sanitize_text_field($user->last_name . ' ' . $user->first_name)) ?: sanitize_text_field($user->user_login);
        default: return sanitize_text_field($user->display_name);
    }
}

// Synchroniser les données à l'inscription
function admin_lab_sync_user_data_on_register($user_id) {
    static $processed_users = [];

    if (in_array($user_id, $processed_users)) {
        return;
    }
    $processed_users[] = $user_id;

    $user = get_userdata($user_id);
    $display_name = admin_lab_get_display_name($user);
    $slug = admin_lab_clean_slug($display_name);

    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . "user_slugs";

    $existing = $wpdb->get_row($wpdb->prepare("SELECT user_slug_id FROM {$table} WHERE user_id = %d", $user_id));

    if ($existing) {
        $slug_id = $existing->user_slug_id;
        if ($wpdb->get_var($wpdb->prepare("SELECT user_slug FROM {$table} WHERE user_id = %d", $user_id)) !== $slug) {
            $wpdb->update($table, ['user_slug' => $slug], ['user_id' => $user_id]);
        }
    } else {
        $slug_id = admin_lab_generate_unique_slug_id();
        $wpdb->insert($table, [
            'user_id'      => $user_id,
            'user_slug'    => $slug,
            'user_slug_id' => $slug_id
        ]);
    }

    $nicename = $slug . '-' . $slug_id;

    wp_update_user([
        'ID'            => $user_id,
        'display_name'  => $display_name,
        'user_nicename' => $nicename
    ]);

    admin_lab_sync_um_permalink_meta($user_id, $nicename);

}


add_action('user_register', 'admin_lab_sync_user_data_on_register');
add_action('edit_user_created_user', 'admin_lab_sync_user_data_on_register');

// Synchroniser les données lors de la mise à jour d'un profil
function admin_lab_sync_user_data_on_profile_update($user_id, $old_user_data) {
    $user = get_userdata($user_id);
    $new_display_name = admin_lab_get_display_name($user);
    $new_slug = admin_lab_clean_slug($new_display_name);

    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . "user_slugs";

    $slug_data = $wpdb->get_row($wpdb->prepare("SELECT user_slug, user_slug_id FROM {$table} WHERE user_id = %d", $user_id));

    if (!$slug_data) {
        $user_slug_id = admin_lab_generate_unique_slug_id();
        $wpdb->insert($table, [
            'user_id'      => $user_id,
            'user_slug'    => $new_slug,
            'user_slug_id' => $user_slug_id
        ]);
    } else {
        $user_slug_id = $slug_data->user_slug_id;
        if ($slug_data->user_slug !== $new_slug) {
            $wpdb->update($table, ['user_slug' => $new_slug], ['user_id' => $user_id]);
        }
    }

    $new_nicename = $new_slug . '-' . $user_slug_id;

    if (
        $old_user_data->display_name !== $new_display_name ||
        $old_user_data->user_nicename !== $new_nicename
    ) {
        wp_update_user([
            'ID'            => $user_id,
            'display_name'  => $new_display_name,
            'user_nicename' => $new_nicename
        ]);
    }

    update_user_meta($user_id, 'custom_user_nicename', $new_nicename);
}
add_action('profile_update', 'admin_lab_sync_user_data_on_profile_update', 10, 2);


// Synchroniser lors d’un ajout manuel
function admin_lab_sync_user_data_on_manual_addition($user_id) {
    admin_lab_sync_user_data_on_register($user_id);
}
add_action('edit_user_created_user', 'admin_lab_sync_user_data_on_manual_addition');

// Nettoyer les slugs quand un utilisateur est supprimé
function admin_lab_delete_user_slug($user_id) {
    global $wpdb;
    $table = ME5RINE_LAB_GLOBAL_PREFIX . "user_slugs";
    $wpdb->delete($table, ['user_id' => $user_id]);
}
add_action('delete_user', 'admin_lab_delete_user_slug');

// Redéfinir l'URL de profil UM
function custom_um_profile_url($url, $user_id) {
    $user = get_user_by('id', $user_id);
    if ($user && !empty($user->user_nicename)) {
        // Utiliser la fonction helper pour construire l'URL avec l'option configurée
        return admin_lab_build_profile_url($user->user_nicename);
    }
    return $url;
}
add_filter('um_user_profile_url', 'custom_um_profile_url', 10, 2);
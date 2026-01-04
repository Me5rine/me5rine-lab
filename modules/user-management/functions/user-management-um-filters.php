<?php
// File: modules/user-management/functions/user-management-um-filters.php

if (!defined('ABSPATH')) exit;

add_filter('um_user_profile_photo', function ($url, $user_id) {
    $shared_url = get_user_meta($user_id, 'shared_profile_photo_url', true);
    return $shared_url ?: $url;
}, 10, 2);

add_action('um_after_upload_profile_photo', function ($user_id, $upload, $args) {
    if (!empty($upload['url'])) {
        update_user_meta($user_id, 'shared_profile_photo_url', esc_url_raw($upload['url']));
    }
}, 10, 3);

/**
 * Purge le cache Ultimate Member pour un utilisateur donné.
 * 
 * Cette fonction supprime le cache UM stocké dans wp_options (um_cache_userdata_{ID}),
 * nettoie le cache WordPress/Redis, et recharge le contexte UM en mémoire.
 * 
 * À appeler après toute modification de user meta utilisée par Ultimate Member
 * (ex: country, first_name, etc.) pour forcer UM à reconstruire son cache.
 * 
 * Exemple d'utilisation :
 * <code>
 * if ($wp_user_id !== null && $wp_user_id > 0) {
 *     if (!empty($country)) {
 *         update_user_meta($wp_user_id, 'country', $country);
 *         admin_lab_purge_um_user_cache($wp_user_id);
 *     }
 * }
 * </code>
 * 
 * @param int $user_id L'ID de l'utilisateur dont le cache doit être purgé.
 */
function admin_lab_purge_um_user_cache($user_id) {
    if (empty($user_id) || !is_numeric($user_id)) {
        return;
    }
    
    $user_id = (int) $user_id;
    
    // 1) Ultimate Member cache (wp_options)
    delete_option('um_cache_userdata_' . $user_id);
    
    // 2) WordPress/Redis object cache
    clean_user_cache($user_id);
    wp_cache_delete($user_id, 'user_meta');
    wp_cache_delete($user_id, 'users');
    
    // 3) Ultimate Member in-memory user context (same request)
    if (function_exists('um_fetch_user')) {
        um_fetch_user($user_id);
    }
}
<?php
// File: modules/shortcodes/functions/shortcodes-functions.php

if (!defined('ABSPATH')) exit;

add_action('admin_notices', 'admin_lab_shortcodes_notices');
function admin_lab_shortcodes_notices() {
    if (!isset($_GET['message']) || !isset($_GET['page'])) return;

    if ($_GET['page'] !== 'admin-lab-shortcodes') return;

    $messages = [
        'added'   => __('The shortcode has been successfully added!', 'me5rine-lab'),
        'updated' => __('The shortcode has been updated successfully!', 'me5rine-lab'),
        'deleted' => __('The shortcode has been deleted successfully!', 'me5rine-lab')
    ];

    if (isset($messages[$_GET['message']])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$_GET['message']]) . '</p></div>';
    }
}

add_action('admin_post_admin_lab_add_shortcode', 'admin_lab_add_shortcode');
function admin_lab_add_shortcode() {
    global $wpdb;
    check_admin_referer('admin_lab_add_shortcode_nonce');

    $wpdb->insert(admin_lab_getTable('shortcodes'), [
        'name'        => sanitize_text_field($_POST['shortcode_name']),
        'description' => stripslashes($_POST['shortcode_description'] ?? ''),
        'content'     => isset($_POST['shortcode_function']) ? stripslashes(trim($_POST['shortcode_function'])) : ''
    ]);

    wp_redirect(admin_url('admin.php?page=admin-lab-shortcodes&message=added'));
    exit;
}

add_action('admin_post_admin_lab_edit_shortcode', 'admin_lab_edit_shortcode');
function admin_lab_edit_shortcode() {
    global $wpdb;
    check_admin_referer('admin_lab_edit_shortcode_nonce');

    remove_all_filters('content_save_pre');
    $wpdb->update(admin_lab_getTable('shortcodes'), [
        'name'        => sanitize_text_field($_POST['shortcode_name']),
        'description' => stripslashes($_POST['shortcode_description'] ?? ''),
        'content'     => isset($_POST['shortcode_function']) ? stripslashes(trim($_POST['shortcode_function'])) : ''
    ], ['id' => absint($_POST['edit_shortcode'])]);

    wp_redirect(admin_url('admin.php?page=admin-lab-shortcodes&message=updated'));
    exit;
}

add_action('admin_post_admin_lab_delete_shortcode', 'admin_lab_delete_shortcode');
function admin_lab_delete_shortcode() {
    global $wpdb;

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'admin_lab_delete_shortcode_nonce')) {
        wp_die(__('Security check failed', 'me5rine-lab'));
    }

    if (empty($_GET['delete_shortcode'])) {
        wp_die(__('No shortcode selected for deletion.', 'me5rine-lab'));
    }

    $id = absint($_GET['delete_shortcode']);
    $wpdb->delete(admin_lab_getTable('shortcodes'), ['id' => $id]);

    wp_redirect(admin_url('admin.php?page=admin-lab-shortcodes&message=deleted'));
    exit;
}

add_action('admin_post_admin_lab_bulk_delete_shortcodes', 'admin_lab_bulk_delete_shortcodes');
function admin_lab_bulk_delete_shortcodes() {
    global $wpdb;
    check_admin_referer('bulk-shortcodes');

    if (empty($_POST['shortcode']) || !is_array($_POST['shortcode'])) {
        wp_redirect(admin_url('admin.php?page=admin-lab-shortcodes&message=error'));
        exit;
    }

    $ids = array_map('absint', $_POST['shortcode']);

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare("DELETE FROM " . admin_lab_getTable('shortcodes') . " WHERE id IN ($placeholders)", ...$ids));
    }

    wp_redirect(admin_url('admin.php?page=admin-lab-shortcodes&message=deleted'));
    exit;
}

add_shortcode('custom_shortcode', 'admin_lab_render_custom_shortcode');

function admin_lab_render_custom_shortcode($atts = [], $content = null) {
    global $wpdb;

    $table_name = admin_lab_getTable('shortcodes');

    $shortcode_name = sanitize_text_field($atts['name'] ?? '');
    if (empty($shortcode_name)) {
        return __('No shortcode name specified.', 'me5rine-lab');
    }

    $shortcode = $wpdb->get_row($wpdb->prepare("SELECT content FROM $table_name WHERE name = %s", $shortcode_name));

    if (!$shortcode || empty($shortcode->content)) {
        return __('Shortcode not found or empty.', 'me5rine-lab');
    }

    $code = stripslashes($shortcode->content);

    // Sécurise un peu : pas de balises PHP ouvertes dans le code stocké
    $code = trim(preg_replace('#^\s*<\?(php)?#i', '', $code));

    try {
        // Crée dynamiquement une fonction qui prend $atts et $content
        $dynamic_function = eval('return function($atts = [], $content = null) { ' . $code . ' };');

        if (is_callable($dynamic_function)) {
            return $dynamic_function($atts, $content);
        } else {
            return '<strong>Erreur :</strong> Impossible d\'interpréter le shortcode.';
        }
    } catch (Throwable $e) {
        return '<strong>Erreur d\'exécution :</strong> ' . esc_html($e->getMessage());
    }
}
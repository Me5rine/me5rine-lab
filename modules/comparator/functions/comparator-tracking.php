<?php
// File: modules/comparator/functions/comparator-tracking.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Construit une URL suivie (tracking) vers la page produit.
 *
 * @param int   $game_id
 * @param array $args {
 *   @type string target_url URL finale du vendeur (OBLIGATOIRE)
 *   @type string store      Nom de la boutique
 *   @type string platform   Plateforme (PC, Switch, etc.)
 *   @type string click_type Type de clic (offer, all_prices, banner, ...)
 *   @type string context    Contexte d'affichage (classic, banner, ...)
 *   @type int    post_id    ID du post WP où le widget est affiché
 * }
 *
 * @return string URL front qui logge puis redirige vers target_url
 */
function admin_lab_comparator_get_tracked_url($game_id, array $args = []) {
    $defaults = [
        'target_url' => '',
        'store'      => '',
        'platform'   => '',
        'click_type' => '',
        'context'    => '',
        'post_id'    => 0,
    ];

    $data = wp_parse_args($args, $defaults);

    // Si pas d'URL cible → on ne wrappe rien
    if (empty($data['target_url'])) {
        return '';
    }

    // On encode l'URL produit pour la passer en GET
    $encoded_target = rawurlencode($data['target_url']);

    $query = [
        'mlab_cmp_click' => 1,
        'gid'            => (int) $game_id,
        't'              => $encoded_target,
        'st'             => mb_substr( sanitize_text_field( $data['store'] ), 0, 190 ),
        'pf'             => mb_substr( sanitize_text_field( $data['platform'] ), 0, 190 ),
        'ct'             => substr( sanitize_key( $data['click_type'] ), 0, 20 ),
        'cx'             => substr( sanitize_key( $data['context'] ), 0, 20 ),
        'pid'            => (int) $data['post_id'],
    ];

    // URL FRONT, pas admin_url()
    return add_query_arg($query, home_url('/'));
}

/**
 * Handler front qui reçoit les clics, logge en base, puis redirige.
 */
function admin_lab_comparator_handle_click() {
    if (empty($_GET['mlab_cmp_click'])) {
        return;
    }

    // On récupère et sécurise l'URL cible
    $target_raw = isset($_GET['t']) ? wp_unslash($_GET['t']) : '';
    $target_url = esc_url_raw(rawurldecode($target_raw));

    // Si pas de target, on renvoie vers la home
    if (empty($target_url)) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    // On tente d'écrire en base (si la table existe)
    global $wpdb;
    $table = admin_lab_getTable('comparator_clicks', false);

    if (!empty($table)) {
        $game_id  = isset($_GET['gid']) ? (int) $_GET['gid'] : 0;
        $post_id  = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;
        $store    = isset($_GET['st']) ? sanitize_text_field($_GET['st']) : '';
        $platform = isset($_GET['pf']) ? sanitize_text_field($_GET['pf']) : '';
        $ctype    = isset($_GET['ct']) ? sanitize_key($_GET['ct']) : '';
        $context  = isset($_GET['cx']) ? sanitize_key($_GET['cx']) : '';

        $wpdb->insert(
            $table,
            [
                'game_id'    => $game_id,
                'post_id'    => $post_id,
                'store'      => $store,
                'platform'   => $platform,
                'click_type' => $ctype,
                'context'    => $context,
                'clicked_at' => current_time('mysql', true),
                'ip_hash'    => admin_lab_comparator_get_ip_hash(),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    // Redirection finale vers la page produit (EXTERNE AUTORISÉE)
    wp_redirect($target_url);
    exit;
}
add_action('template_redirect', 'admin_lab_comparator_handle_click');

/**
 * Hash IP (simple anonymisation)
 */
function admin_lab_comparator_get_ip_hash() {
    if (empty($_SERVER['REMOTE_ADDR'])) {
        return '';
    }

    return hash('sha256', $_SERVER['REMOTE_ADDR'] . wp_salt('auth'));
}

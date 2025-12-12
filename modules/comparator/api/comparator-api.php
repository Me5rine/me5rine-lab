<?php
// File: modules/comparator/functions/api/comparator-api.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Appel brut à l’API comparateur.
 *
 * Utilise les settings:
 *  - api_base
 *  - api_token
 *
 * @param string $path       Chemin relatif, ex: 'games/123'
 * @param array  $query_args Paramètres de query string
 * @return array|WP_Error
 */
function admin_lab_comparator_api_request($path, $query_args = []) {
    if (!function_exists('admin_lab_comparator_get_settings')) {
        return new WP_Error('missing_settings', 'Comparator settings not loaded.');
    }

    $settings = admin_lab_comparator_get_settings();

    $base = !empty($settings['api_base'])
        ? untrailingslashit($settings['api_base'])
        : 'https://api.clicksngames.com/api';

    $url = $base . '/' . ltrim($path, '/');

    if (!empty($query_args)) {
        $url = add_query_arg($query_args, $url);
    }

    $args = [
        'method'      => 'GET',
        'timeout'     => 10,
        'redirection' => 3,
        'blocking'    => true,
        'headers'     => [
            'Accept' => 'application/json',
        ],
    ];

    if (!empty($settings['api_token'])) {
        $args['headers']['Authorization'] = 'Bearer ' . $settings['api_token'];
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        return new WP_Error('api_error', $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {

        return new WP_Error(
            'api_error',
            'Invalid API status code: ' . $code . ' for URL: ' . $url
        );
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('api_error', 'Invalid JSON response from comparator API.');
    }

    return $data;
}

/**
 * Récupère un jeu par ID.
 * Endpoint: /games/{id}?populate=*
 *
 * @param int   $game_id
 * @param array $args {
 *   @type array $extra_query Paramètres additionnels de query
 * }
 *
 * @return array|WP_Error
 */
function admin_lab_comparator_get_game_by_id($game_id, $args = []) {
    $game_id = (int) $game_id;

    $query = [
        'populate' => '*',
    ];

    if (!empty($args['extra_query']) && is_array($args['extra_query'])) {
        $query = array_merge($query, $args['extra_query']);
    }

    $data = admin_lab_comparator_api_request('games/' . $game_id, $query);

    if (is_wp_error($data)) {
        return $data;
    }

    return $data;
}

/**
 * Récupère un jeu associé à une catégorie WordPress via wordpress_category_id (mode auto).
 *
 * Ici on reste simple: 1 catégorie → 1 jeu (pageSize=1).
 *
 * @param int $category_id
 * @return array|WP_Error Node Strapi "data[0]"
 */
function admin_lab_comparator_get_game_by_category_auto($category_id) {
    $category_id = (int) $category_id;
    if ($category_id <= 0) {
        return new WP_Error('invalid_category', 'Invalid category ID');
    }

    $query = [
        'filters[wordpress_category_id][$eq]' => $category_id,
        'pagination[pageSize]'               => 1,
        'populate'                           => '*',
        'sort[0]'                            => 'name',
    ];

    $data = admin_lab_comparator_api_request('games', $query);

    if (is_wp_error($data)) {
        return $data;
    }

    if (empty($data['data'][0])) {
        return new WP_Error('no_game', 'No game found for this category');
    }

    // On renvoie directement le node "data[0]" (Strapi style)
    return $data['data'][0];
}

/**
 * Récupère le jeu pour une catégorie selon le mode (auto / manual).
 *
 * - mode = manual → on regarde le mapping 'category_map' dans les settings
 * - mode = auto   → filtre par wordpress_category_id
 *
 * @param int $category_id
 * @return array|WP_Error
 */
function admin_lab_comparator_get_game_for_category($category_id) {
    if (!function_exists('admin_lab_comparator_get_settings')) {
        return new WP_Error('missing_settings', 'Comparator settings not loaded.');
    }

    $settings    = admin_lab_comparator_get_settings();
    $category_id = (int) $category_id;

    if ($category_id <= 0) {
        return new WP_Error('invalid_category', 'Invalid category ID');
    }

    if (!empty($settings['mode']) && $settings['mode'] === 'manual') {
        if (!empty($settings['category_map'][$category_id])) {
            $game_id = (int) $settings['category_map'][$category_id];
            return admin_lab_comparator_get_game_by_id($game_id);
        }

        return new WP_Error('no_mapping', 'No game mapping defined for this category.');
    }

    // Mode auto
    return admin_lab_comparator_get_game_by_category_auto($category_id);
}

/**
 * Helper haut niveau utilisé par les shortcodes/blocs/widgets.
 *
 * Résout un jeu à partir:
 * - d’un game_id explicite
 * - d’un category_id explicite
 * - du contexte courant (catégorie ou article)
 *
 * @param array $args {
 *   @type int $game_id
 *   @type int $category_id
 * }
 *
 * @return array|WP_Error
 */
function admin_lab_comparator_resolve_game_from_context($args = []) {
    $args = wp_parse_args($args, [
        'game_id'     => 0,
        'category_id' => 0,
    ]);

    // 1) Si game_id fourni → priorité absolue
    if (!empty($args['game_id'])) {
        return admin_lab_comparator_get_game_by_id((int) $args['game_id']);
    }

    // 2) Si category_id fourni → on passe par get_game_for_category()
    if (!empty($args['category_id'])) {
        return admin_lab_comparator_get_game_for_category((int) $args['category_id']);
    }

    // 3) Sinon, on déduit la catégorie du contexte WP
    $cat_id = 0;

    if (is_category()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $cat_id = (int) $term->term_id;
        }
    } elseif (is_singular()) {
        $cats = get_the_category();
        if (!empty($cats[0])) {
            // On prend la première catégorie (comme ton legacy)
            $cat_id = (int) $cats[0]->term_id;
        }
    }

    if (!$cat_id) {
        return new WP_Error('no_category', 'No category context found.');
    }

    return admin_lab_comparator_get_game_for_category($cat_id);
}

/**
 * Calcule le "better price" pour un jeu donné
 * en se basant sur la relation game_prices du jeu.
 *
 * On n'appelle plus d'endpoint /better-price dédié : tout est calculé côté WP.
 *
 * @param int $game_id
 * @return array|WP_Error {
 *   @type float      $price    Prix le plus bas
 *   @type int|null   $discount Pourcentage de réduction si dispo
 *   @type string     $pageUrl  URL du marchand
 * }
 */
function admin_lab_comparator_get_better_price( $game_id ) {
    $game_id = (int) $game_id;
    if ( $game_id <= 0 ) {
        return new WP_Error( 'invalid_game_id', 'Invalid game ID' );
    }

    if ( ! function_exists( 'admin_lab_comparator_get_game_by_id' ) ) {
        return new WP_Error( 'missing_api_helper', 'Comparator API helpers not loaded.' );
    }

    // On récupère le jeu complet depuis Strapi
    $data = admin_lab_comparator_get_game_by_id( $game_id );

    if ( is_wp_error( $data ) || empty( $data ) ) {
        return $data;
    }

    // Normalisation Strapi
    $game  = isset( $data['data'] ) ? $data['data'] : $data;
    $attrs = isset( $game['attributes'] ) ? $game['attributes'] : [];

    if ( ! function_exists( 'admin_lab_comparator_extract_offers' ) ) {
        return new WP_Error( 'missing_offers_helper', 'Offers helper not loaded.' );
    }

    // On récupère les offres triées par prix (helper que tu as déjà)
    $offers = admin_lab_comparator_extract_offers( $attrs, 1 );

    if ( empty( $offers ) || empty( $offers[0]['price'] ) ) {
        return new WP_Error( 'no_offer', 'No offer found for this game.' );
    }

    $best = $offers[0];

    return [
        'price'    => (float) $best['price'],
        'discount' => isset( $best['discount_percentage'] ) ? $best['discount_percentage'] : null,
        'pageUrl'  => $best['url'],
    ];
}


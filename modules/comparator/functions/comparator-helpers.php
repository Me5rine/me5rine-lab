<?php
// File: modules/comparator/functions/comparator-helpers.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper universel pour extraire une relation many-to-many Strapi.
 *
 * @param array $relation Exemple: $attrs['studios'], $attrs['platforms'], etc.
 * @return array [
 *     'names' => 'Nom 1, Nom 2',
 *     'count' => 2,
 *     'list'  => ['Nom 1', 'Nom 2']
 * ]
 */
function admin_lab_comparator_extract_relation(array $relation): array {
    $items = [];

    if (!empty($relation['data']) && is_array($relation['data'])) {
        foreach ($relation['data'] as $node) {
            if (!empty($node['attributes']['name'])) {
                $items[] = $node['attributes']['name'];
            }
        }
    }

    return [
        'names' => implode(', ', $items),
        'count' => count($items),
        'list'  => $items,
    ];
}

/**
 * Extrait les offres de prix depuis la relation game_prices du jeu.
 *
 * @param array $attrs Attributs du jeu (/games/{id}?populate=*)
 * @param int   $limit Nombre max d'offres à retourner (triées par prix croissant)
 * @return array[]
 */
function admin_lab_comparator_extract_offers(array $attrs, $limit = 3) {
    if (empty($attrs['game_prices']['data']) || !is_array($attrs['game_prices']['data'])) {
        return [];
    }

    $offers = [];

    foreach ($attrs['game_prices']['data'] as $node) {
        if (empty($node['attributes']) || !is_array($node['attributes'])) {
            continue;
        }

        $o = $node['attributes'];

        $price = isset($o['price']) ? (float) $o['price'] : null;
        $url   = !empty($o['url']) ? $o['url'] : null;

        // On ignore les offres sans prix ou sans URL
        if ($price === null || $price <= 0 || empty($url)) {
            continue;
        }

        // Filtres dispo / stock si présents
        if (isset($o['is_available']) && $o['is_available'] === false) {
            continue;
        }
        if (isset($o['is_in_stock']) && $o['is_in_stock'] === false) {
            continue;
        }

        $offers[] = [
            'price'               => $price,
            'store'               => isset($o['store']) ? $o['store'] : '',
            'platform'            => isset($o['platform']) ? $o['platform'] : '',
            'url'                 => $url,
            'currency'            => isset($o['currency']) ? $o['currency'] : 'EUR',
            'original_price'      => isset($o['original_price']) ? $o['original_price'] : null,
            'discount_percentage' => isset($o['discount_percentage']) ? $o['discount_percentage'] : null,
        ];
    }

    if (empty($offers)) {
        return [];
    }

    // Tri par prix croissant
    usort($offers, static function ($a, $b) {
        return $a['price'] <=> $b['price'];
    });

    return array_slice($offers, 0, max(1, (int) $limit));
}

/**
 * URL "See all prices" pour un jeu.
 *
 * - Si settings['frontend_base'] est défini :
 *     {frontend_base}/game/{slug}
 *   ex: https://hub-segment-comparator.vercel.app/game/pokemon-red
 *
 * - Sinon fallback:
 *     - $attrs['news_link'] (si utilisé)
 *     - ou URL de la meilleure offre
 *
 * @param array      $game       Node Strapi "data" complet pour le jeu
 * @param array      $attrs      $game['attributes']
 * @param array[]    $offers     Liste des offres extraites
 * @param array|null $best_offer Meilleure offre (optionnel)
 *
 * @return string
 */
function admin_lab_comparator_get_all_prices_url(array $game, array $attrs, array $offers, $best_offer) {
    $settings = admin_lab_comparator_get_settings();
    $url      = '';

    // 1) URL de base du comparateur depuis les settings
    if (!empty($settings['frontend_base']) && !empty($attrs['slug'])) {
        $base = untrailingslashit($settings['frontend_base']);

        // On part du slug Strapi (déjà normalisé côté API)
        $slug = sanitize_title($attrs['slug']);

        $url = $base . '/game/' . $slug;
    }
    // 2) Fallback : champ news_link éventuel
    elseif (!empty($attrs['news_link'])) {
        $url = $attrs['news_link'];
    }
    // 3) Fallback ultime : URL de la meilleure offre
    elseif (!empty($best_offer['url'])) {
        $url = $best_offer['url'];
    }

    /**
     * Permet d’overrider l’URL "See all prices" si besoin.
     */
    $url = apply_filters(
        'admin_lab_comparator_all_prices_url',
        $url,
        $game,
        $attrs,
        $offers,
        $best_offer
    );

    return $url;
}

/**
 * Retourne les infos "jeu" à partir d'un game_id,
 * où le "nom du jeu" = catégorie principale du post le plus cliqué.
 *
 * @param int $game_id
 * @return array {
 *   @type string label      Nom du jeu (nom de la catégorie, sinon "Game #ID")
 *   @type int    term_id    ID de la catégorie (0 si aucune)
 *   @type string term_link  Lien vers l'archive de la catégorie (vide si none)
 *   @type int    post_id    Post WP principal (optionnel, pour debug)
 *   @type string permalink  Lien vers le post principal (optionnel)
 * }
 */
function admin_lab_comparator_get_game_label_from_clicks( $game_id ) {
    static $cache = [];

    $game_id = (int) $game_id;

    if ( $game_id <= 0 ) {
        $label = sprintf( __( 'Game #%d', 'me5rine-lab' ), $game_id );
        return [
            'label'     => $label,
            'term_id'   => 0,
            'term_link' => '',
            'post_id'   => 0,
            'permalink' => '',
        ];
    }

    if ( isset( $cache[ $game_id ] ) ) {
        return $cache[ $game_id ];
    }

    global $wpdb;
    $table = admin_lab_getTable( 'comparator_clicks', false );

    // Post le plus cliqué pour ce game_id
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT post_id, COUNT(*) AS clicks
            FROM {$table}
            WHERE game_id = %d AND post_id > 0
            GROUP BY post_id
            ORDER BY clicks DESC
            LIMIT 1
            ",
            $game_id
        ),
        ARRAY_A
    );

    $post_id   = $row && ! empty( $row['post_id'] ) ? (int) $row['post_id'] : 0;
    $permalink = $post_id ? get_permalink( $post_id ) : '';
    $term_id   = 0;
    $term_link = '';
    $label     = '';

    if ( $post_id ) {
        $cats = get_the_category( $post_id );
        if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
            // On prend la première catégorie comme "jeu"
            $term      = $cats[0];
            $term_id   = (int) $term->term_id;
            $label     = $term->name; // <= NOM humain, pas slug
            $term_link = get_term_link( $term );
        }
    }

    if ( $label === '' ) {
        $label = sprintf( __( 'Game #%d', 'me5rine-lab' ), $game_id );
    }

    $data = [
        'label'     => $label,
        'term_id'   => $term_id,
        'term_link' => ! is_wp_error( $term_link ) ? $term_link : '',
        'post_id'   => $post_id,
        'permalink' => $permalink,
    ];

    $cache[ $game_id ] = $data;
    return $data;
}

/**
 * Transforme un slug (store / platform / click_type) en label lisible.
 *
 * @param string $slug
 * @param string $type 'store'|'platform'|'click_type'|'generic'
 * @return string
 */
function admin_lab_comparator_humanize_slug( $slug, $type = 'generic' ) {
    $slug = (string) $slug;
    if ( $slug === '' ) {
        return '';
    }

    // Mapping manuel pour les cas connus
    $maps = [
        'click_type' => [
            'offer'      => __( 'Offer button', 'me5rine-lab' ),
            'all_prices' => __( 'All prices button', 'me5rine-lab' ),
            'banner'     => __( 'Banner', 'me5rine-lab' ),
        ],
    ];

    if ( isset( $maps[ $type ][ $slug ] ) ) {
        $label = $maps[ $type ][ $slug ];
    } else {
        // Générique : on remplace -/_ par des espaces et on capitalise
        $label = ucwords( str_replace( [ '-', '_' ], ' ', $slug ) );
    }

    /**
     * Filtre pour ajouter/modifier des mappings si besoin.
     */
    return apply_filters( 'admin_lab_comparator_humanize_slug', $label, $slug, $type );
}

/**
 * Récupère le slug d'un jeu dans Strapi à partir de son ID.
 *
 * NOTE : helper "métier" qui s'appuie sur l'API comparateur.
 * Il reste ici (helpers) car il est utilisé par la couche front (URL).
 *
 * @param int $game_id
 * @return string Slug du jeu ou chaîne vide si introuvable.
 */
function admin_lab_comparator_get_game_slug( $game_id ) {
    $game_id = (int) $game_id;
    if ( $game_id <= 0 ) {
        return '';
    }

    if ( ! function_exists( 'admin_lab_comparator_get_game_by_id' ) ) {
        return '';
    }

    $data = admin_lab_comparator_get_game_by_id( $game_id );

    if ( is_wp_error( $data ) || empty( $data ) ) {
        return '';
    }

    // Normalisation Strapi : ['data' => ['id' => ..., 'attributes' => [...]]]
    $game  = isset( $data['data'] ) ? $data['data'] : $data;
    $attrs = isset( $game['attributes'] ) ? $game['attributes'] : [];

    if ( empty( $attrs['slug'] ) ) {
        return '';
    }

    return (string) $attrs['slug'];
}

/**
 * URL front "fiche comparateur" pour un jeu.
 *
 * Utilise le setting frontend_base du comparateur :
 *   {frontend_base}/game/{slug}
 *
 * @param int $game_id
 * @return string
 */
function admin_lab_comparator_get_game_frontend_url( $game_id ) {
    $game_id = (int) $game_id;
    if ( $game_id <= 0 ) {
        return '';
    }

    if ( ! function_exists( 'admin_lab_comparator_get_settings' ) ) {
        return '';
    }

    // Récupérer le slug depuis Strapi
    if ( ! function_exists( 'admin_lab_comparator_get_game_slug' ) ) {
        return '';
    }

    $slug = admin_lab_comparator_get_game_slug( $game_id );
    if ( $slug === '' ) {
        return '';
    }

    $settings = admin_lab_comparator_get_settings();
    if ( empty( $settings['frontend_base'] ) ) {
        return '';
    }

    $base = untrailingslashit( $settings['frontend_base'] );

    // On garde le slug Strapi tel quel et on l’encode juste pour l’URL
    return $base . '/game/' . rawurlencode( $slug );
}

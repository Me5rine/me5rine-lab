<?php
// File: modules/comparator/functions/comparator-shortcodes.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enregistre les shortcodes pour remplacer les widgets.
 */
function admin_lab_comparator_register_shortcodes() {
    add_shortcode('me5rine_comparator', 'admin_lab_comparator_shortcode');
    add_shortcode('me5rine_comparator_banner', 'admin_lab_comparator_banner_shortcode');
}

/**
 * Shortcode générique.
 * [me5rine_comparator layout="classic" game_id="123" category_id="45"]
 */
function admin_lab_comparator_shortcode($atts = [], $content = null) {
    $atts = shortcode_atts([
        'layout'      => 'classic', // classic|banner
        'game_id'     => 0,
        'category_id' => 0,
    ], $atts, 'me5rine_comparator');

    $game_data = admin_lab_comparator_resolve_game_from_context([
        'game_id'     => (int) $atts['game_id'],
        'category_id' => (int) $atts['category_id'],
    ]);

    if ($atts['layout'] === 'banner') {
        return admin_lab_comparator_render_banner($game_data);
    }

    return admin_lab_comparator_render_classic($game_data);
}

/**
 * Second shortcode dédié au layout banner
 * [me5rine_comparator_banner game_id="123" category_id="45"]
 */
function admin_lab_comparator_banner_shortcode($atts = [], $content = null) {
    $atts = shortcode_atts([
        'game_id'     => 0,
        'category_id' => 0,
    ], $atts, 'me5rine_comparator_banner');

    $game_data = admin_lab_comparator_resolve_game_from_context([
        'game_id'     => (int) $atts['game_id'],
        'category_id' => (int) $atts['category_id'],
    ]);

    return admin_lab_comparator_render_banner($game_data);
}

/**
 * Enregistre des blocs dynamiques (PHP) qui réutilisent le même moteur.
 */
function admin_lab_comparator_register_blocks() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    // Récupère le registre global des blocs
    $registry = WP_Block_Type_Registry::get_instance();

    // Bloc "classic"
    if ( ! $registry->is_registered( 'me5rine-lab/comparator-classic' ) ) {
        register_block_type(
            'me5rine-lab/comparator-classic',
            [
                'render_callback' => function ( $attributes, $content ) {
                    $game_data = admin_lab_comparator_resolve_game_from_context(
                        [
                            'game_id'     => isset( $attributes['gameId'] ) ? (int) $attributes['gameId'] : 0,
                            'category_id' => isset( $attributes['categoryId'] ) ? (int) $attributes['categoryId'] : 0,
                        ]
                    );

                    return admin_lab_comparator_render_classic( $game_data );
                },
                'attributes'      => [
                    'gameId'     => [
                        'type'    => 'number',
                        'default' => 0,
                    ],
                    'categoryId' => [
                        'type'    => 'number',
                        'default' => 0,
                    ],
                ],
            ]
        );
    }

    // Bloc "banner"
    if ( ! $registry->is_registered( 'me5rine-lab/comparator-banner' ) ) {
        register_block_type(
            'me5rine-lab/comparator-banner',
            [
                'render_callback' => function ( $attributes, $content ) {
                    $game_data = admin_lab_comparator_resolve_game_from_context(
                        [
                            'game_id'     => isset( $attributes['gameId'] ) ? (int) $attributes['gameId'] : 0,
                            'category_id' => isset( $attributes['categoryId'] ) ? (int) $attributes['categoryId'] : 0,
                        ]
                    );

                    return admin_lab_comparator_render_banner( $game_data );
                },
                'attributes'      => [
                    'gameId'     => [
                        'type'    => 'number',
                        'default' => 0,
                    ],
                    'categoryId' => [
                        'type'    => 'number',
                        'default' => 0,
                    ],
                ],
            ]
        );
    }
}

add_action( 'init', 'admin_lab_comparator_register_shortcodes' );
add_action( 'init', 'admin_lab_comparator_register_blocks' );
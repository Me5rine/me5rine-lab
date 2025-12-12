<?php
// File: modules/comparator/functions/comparator-widgets.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget bannière, basé sur la nouvelle logique (render_banner + resolve_game_from_context).
 */
class Admin_Lab_Comparator_Banner_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'admin_lab_comparator_widget_banner',
            __('Comparator – Bannière', 'me5rine-lab'),
            ['description' => __('Bannière de comparaison de prix (ClicksNGames)', 'me5rine-lab')]
        );
    }

    public function widget($args, $instance) {
        // On ne fait quelque chose que sur une page de contenu (article, catégorie, etc.)
        if (!is_singular() && !is_category()) {
            return;
        }

        echo isset($args['before_widget']) ? $args['before_widget'] : '';

        $game_data = admin_lab_comparator_resolve_game_from_context([
            'game_id'     => 0,
            'category_id' => 0,
        ]);

        echo admin_lab_comparator_render_banner($game_data);

        echo isset($args['after_widget']) ? $args['after_widget'] : '';
    }

    public function form($instance) {}
    public function update($new_instance, $old_instance) {
        return $old_instance;
    }
}

/**
 * Widget "fiche classique", basé sur admin_lab_comparator_render_classic().
 */
class Admin_Lab_Comparator_Classic_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'admin_lab_comparator_widget_classic',
            __('Comparator – Fiche jeu', 'me5rine-lab'),
            ['description' => __('Fiche jeu avec bouton meilleur prix', 'me5rine-lab')]
        );
    }

    public function widget($args, $instance) {
        if (!is_singular() && !is_category()) {
            return;
        }

        echo isset($args['before_widget']) ? $args['before_widget'] : '';

        $game_data = admin_lab_comparator_resolve_game_from_context([
            'game_id'     => 0,
            'category_id' => 0,
        ]);

        echo admin_lab_comparator_render_classic($game_data);

        echo isset($args['after_widget']) ? $args['after_widget'] : '';
    }

    public function form($instance) {}
    public function update($new_instance, $old_instance) {
        return $old_instance;
    }
}

/**
 * Hook widgets_init pour enregistrer les widgets comparator.
 */
function admin_lab_comparator_register_widgets() {
    register_widget('Admin_Lab_Comparator_Banner_Widget');
    register_widget('Admin_Lab_Comparator_Classic_Widget');
}

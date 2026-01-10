<?php
// File: modules/giveaways/giveaways.php

if (!defined('ABSPATH')) exit;

// Vérifie que le module est activé
$active_modules = get_option('admin_lab_active_modules', []);
if (!is_array($active_modules) || !in_array('giveaways', $active_modules, true)) return;

// Enregistrement du custom post type et des taxonomies
require_once __DIR__ . '/register/giveaways-register-types.php';
require_once __DIR__ . '/functions/giveaways-pages.php';

// Filtres admin : colonnes, recherches, filtres...
require_once __DIR__ . '/admin-filters/giveaways-table.php';
require_once __DIR__ . '/admin-filters/giveaways-meta-boxes.php';
require_once __DIR__ . '/admin-filters/giveaways-filters.php';
require_once __DIR__ . '/admin-filters/giveaways-search.php';
require_once __DIR__ . '/elementor/giveaways-elementor-queries.php';

// Fonctions front et logiques internes
require_once __DIR__ . '/functions/giveaways-functions.php';
require_once __DIR__ . '/functions/giveaways-rafflepress-sync.php';
require_once __DIR__ . '/functions/giveaways-rafflepress-campaign-save.php';
require_once __DIR__ . '/functions/giveaways-metadatas-cron.php';
require_once __DIR__ . '/front/giveaways-user-shortcodes.php';
require_once __DIR__ . '/functions/giveaways-admin-actions.php';
require_once __DIR__ . '/functions/giveaways-shortcode-custom-rafflepress.php';
require_once __DIR__ . '/functions/giveaways-custom-render-route.php';
require_once __DIR__ . '/front/giveaways-user-participation.php';
require_once __DIR__ . '/functions/giveaways-handle-campaign-submission.php';
require_once __DIR__ . '/functions/giveaways-handle-campaign-edition.php';
require_once __DIR__ . '/functions/giveaways-rafflepress-entry-options.php';
require_once __DIR__ . '/functions/giveaways-rafflepress-rules-generation.php';

// Shortcodes front (formulaires, tableau, etc.)
require_once __DIR__ . '/shortcodes/giveaways-shortcodes.php';
require_once __DIR__ . '/front/giveaways-giveaways-promo.php';
require_once __DIR__ . '/front/giveaways-ajax-participations.php';

// Hooks pour soumissions front
add_action('init', 'admin_lab_giveaways_init');

// Activation du module
function admin_lab_giveaways_init() {
    handle_campaign_submission();
    handle_campaign_edition();

    $active_modules = get_option('admin_lab_active_modules', []);
    if (in_array('giveaways', $active_modules, true)) {
        if (!get_option('giveaways_page_dashboard')) {
            admin_lab_giveaways_create_pages();
        }

        do_action('admin_lab_giveaways_module_activated');
    }
}

// Désactivation du module
add_action('admin_lab_giveaways_module_desactivated', 'admin_lab_delete_giveaways_pages');

// Scripts front : ajax participations
// NOTE: Les scripts giveaway-rafflepress-iframe-content.js et giveaway-rafflepress-iframe-resizer.js
// ont été supprimés car remplacés par la route personnalisée et un calcul de hauteur personnalisé
// via postMessage dans le template giveaways-custom-rafflepress-giveaway.php

// Scripts AJAX participations front (onglet "Mes concours")
function enqueue_giveaways_tab_filter_script() {
    wp_enqueue_script(
        'admin-lab-giveaway-tab-filter',
        plugins_url('../../assets/js/giveaway-tab-filter.js', __FILE__),
        ['jquery'], ME5RINE_LAB_VERSION, true
    );

    wp_localize_script('admin-lab-giveaway-tab-filter', 'admin_lab_ajax_obj', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('admin_lab_ajax_nonce'),
    ]);

    if ( is_user_logged_in() && isset($_GET['tab']) && $_GET['tab'] === 'giveaways' ) {
        wp_enqueue_script(
            'giveaways-user-participation',
            plugins_url('../../assets/js/giveaways-user-participation.js', __FILE__),
            ['jquery'],
            ME5RINE_LAB_VERSION,
            true
        );
	}
}
add_action('wp_enqueue_scripts', 'enqueue_giveaways_tab_filter_script');

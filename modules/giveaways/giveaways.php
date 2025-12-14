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
require_once __DIR__ . '/functions/rafflepress-campaign-save.php';
require_once __DIR__ . '/functions/giveaways-metadatas-cron.php';
require_once __DIR__ . '/front/giveaways-user-shortcodes.php';
require_once __DIR__ . '/functions/giveaways-admin-actions.php';
require_once __DIR__ . '/functions/shortcode-custom-rafflepress.php';
require_once __DIR__ . '/functions/giveaways-custom-render-route.php';
require_once __DIR__ . '/front/giveaways-user-participation.php';
require_once __DIR__ . '/functions/handle-campaign-submission.php';
require_once __DIR__ . '/functions/handle-campaign-edition.php';
require_once __DIR__ . '/functions/rafflepress-entry-options.php';
require_once __DIR__ . '/functions/rafflepress-rules-generation.php';

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

// Scripts front : iframe + ajax participations
function enqueue_rafflepress_login_script() {
    wp_enqueue_script(
        'giveaway-rafflepress-iframe-content',
        plugins_url('assets/js/giveaway-rafflepress-iframe-content.js', WP_PLUGIN_DIR . '/me5rine-lab/me5rine-lab.php'),
        [], ME5RINE_LAB_VERSION, false
    );

    wp_localize_script('giveaway-rafflepress-iframe-content', 'adminlabTranslations', [
        'prizeMessage' => [
            'single'   => __('You can participate in the giveaway for a chance to win a(n) %s.', 'me5rine-lab'),
            'multiple' => __('You can participate in the giveaway for a chance to win one of the following prizes: %s.', 'me5rine-lab'),
            'none'     => __('You can participate in the giveaway below.', 'me5rine-lab'),
            'login'    => __('Please log in to participate:', 'me5rine-lab'),
            'loginBtn' => __('Login', 'me5rine-lab'),
            'registerBtn' => __('Register', 'me5rine-lab')
        ],
        'greeting'        => __('Hello, %s! You are logged in.', 'me5rine-lab'),
        'separator'       => __('More chances to win with Me5rine LAB', 'me5rine-lab'),
        'discordJoinLabel'=> __('Join %s on Discord', 'me5rine-lab'),
        'discordJoinText' => __('To get credit for this entry, join us on Discord.', 'me5rine-lab'),
        'discordJoinBtn'  => __('Join Discord', 'me5rine-lab'),
        'blueskyJoinLabel'   => __('Join %s on Bluesky', 'me5rine-lab'),
        'blueskyJoinText'    => __('To get credit for this entry, join us on Bluesky.', 'me5rine-lab'),
        'blueskyJoinBtn'     => __('Join Bluesky', 'me5rine-lab'),
        'threadsJoinLabel'   => __('Join %s on Threads', 'me5rine-lab'),
        'threadsJoinText'    => __('To get credit for this entry, join us on Threads.', 'me5rine-lab'),
        'threadsJoinBtn'     => __('Join Threads', 'me5rine-lab'),
    ]);

    if (function_exists('admin_lab_get_elementor_kit_colors')) {
        wp_localize_script(
            'giveaway-rafflepress-iframe-content',
            'adminlabColors',
            admin_lab_get_elementor_kit_colors()
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_rafflepress_login_script', 99);

// Note: L'iframe-resizer a été retiré pour éviter les rebonds au scroll
// L'iframe utilise maintenant une hauteur fixe définie dans le shortcode (min-height)
// Si vous avez besoin d'un redimensionnement dynamique, vous pouvez réactiver cette fonction
/*
function enqueue_giveaway_scripts() {
    if (!is_singular('giveaway')) return;

    wp_enqueue_script('jquery');
    wp_enqueue_script('iframe-resizer', 'https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.3.9/iframeResizer.min.js', ['jquery'], null, true);
    wp_enqueue_script('giveaway-rafflepress-iframe-resizer', plugins_url('assets/js/giveaway-rafflepress-iframe-resizer.js', WP_PLUGIN_DIR . '/me5rine-lab/me5rine-lab.php'), ['jquery', 'iframe-resizer'], ME5RINE_LAB_VERSION, true);
}
add_action('wp_enqueue_scripts', 'enqueue_giveaway_scripts', 100);
*/

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

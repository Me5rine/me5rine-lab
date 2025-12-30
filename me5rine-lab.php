<?php
/*
Plugin Name: Me5rine LAB
Plugin URI: https://me5rine.com
Description: Plugin modulaire pour gérer giveaways, partenaires et plus.
Version: 1.10.2
Author: Me5rine
Author URI: https://me5rine.com
License: GPL2
*/

// Deploy test: me5rine-lab auto-deploy OK


// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_START_TIME')) {
    define('WP_START_TIME', microtime(true));
}

// Charger les traductions
function admin_lab_load_textdomain() {
    load_plugin_textdomain('me5rine-lab', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'admin_lab_load_textdomain');

$plugin_data = get_file_data(__FILE__, ['Version' => 'Version'], false);

// Définir les constantes du plugin
define('ME5RINE_LAB_PATH', plugin_dir_path(__FILE__));
define('ME5RINE_LAB_URL', plugin_dir_url(__FILE__));
define('ME5RINE_LAB_VERSION', $plugin_data['Version']);
define('ME5RINE_LAB_MODULES', ME5RINE_LAB_PATH . 'modules/');
define('ME5RINE_LAB_INCLUDES', ME5RINE_LAB_PATH . 'includes/');
define('ME5RINE_LAB_HELPERS', ME5RINE_LAB_INCLUDES . 'functions/admin-lab-helpers.php');
define('ME5RINE_LAB_SETTINGS', ME5RINE_LAB_INCLUDES . 'settings/settings.php');
define('ME5RINE_LAB_SETTINGS_MODULES', ME5RINE_LAB_INCLUDES . 'settings/settings-modules.php');
define('ME5RINE_LAB_SETTINGS_HOOKS', ME5RINE_LAB_INCLUDES . 'settings/settings-module-hooks.php');
define('ME5RINE_LAB_DB', ME5RINE_LAB_INCLUDES . 'me5rine-lab-db.php');
define('ME5RINE_LAB_ADMIN_UI', ME5RINE_LAB_INCLUDES . 'me5rine-lab-admin-ui.php');

// Définir les préfixes globaux
global $wpdb;
define('ME5RINE_LAB_SITE_PREFIX', isset($table_prefix) ? $table_prefix : $wpdb->prefix);
define('ME5RINE_LAB_GLOBAL_PREFIX', defined('ME5RINE_LAB_CUSTOM_PREFIX') ? ME5RINE_LAB_CUSTOM_PREFIX : 'me5rine_lab_global_');

// Charger les fichiers nécessaires
include_once ME5RINE_LAB_HELPERS;
include_once ME5RINE_LAB_SETTINGS;
include_once ME5RINE_LAB_SETTINGS_MODULES;
include_once ME5RINE_LAB_SETTINGS_HOOKS;
include_once ME5RINE_LAB_DB;
include_once ME5RINE_LAB_ADMIN_UI;

add_filter('wp_insert_post_data', function($data, $postarr) {
    if (!empty($postarr['post_type']) && $postarr['post_type'] === 'giveaway') {
        admin_lab_log_custom('[PROFILE] wp_insert_post_data started');
    }
    return $data;
}, 10, 2);

add_action('save_post', function($post_id, $post, $update) {
    if ($post instanceof WP_Post && $post->post_type === 'giveaway') {
        $elapsed = microtime(true) - WP_START_TIME;
        admin_lab_log_custom("[PROFILE] save_post completed in " . number_format($elapsed, 4) . " sec");
    }
}, 99, 3);

// Ajouter le menu admin
function admin_lab_admin_menu() {
    add_menu_page(
        __('Me5rine LAB', 'me5rine-lab'),
        __('Me5rine LAB', 'me5rine-lab'),
        'manage_options',
        'me5rine-lab',
        'admin_lab_settings_page',
        'dashicons-admin-generic',
        100
    );

    $active_modules = get_option('admin_lab_active_modules', []);
    if (!is_array($active_modules)) {
        $active_modules = [];
    }

    if (in_array('marketing_campaigns', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Marketing Campaigns', 'me5rine-lab'),
            __('Marketing Campaigns', 'me5rine-lab'),
            'manage_options',
            'admin-lab-marketing',
            'admin_lab_marketing_page'
        );
    }

    if (in_array('giveaways', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Giveaways', 'me5rine-lab'),
            __('Giveaways', 'me5rine-lab'),
            'manage_options',
            'edit.php?post_type=giveaway'
        );
    }

    if (in_array('shortcodes', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Custom shortcodes', 'me5rine-lab'),
            __('Shortcodes', 'me5rine-lab'),
            'manage_options',
            'admin-lab-shortcodes',
            'admin_lab_shortcodes_page'
        );
    }

    if (in_array('socialls', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Manage Social Labels', 'me5rine-lab'),
            __('Social Labels', 'me5rine-lab'),
            'manage_options',
            'admin-lab-socialls',
            'admin_lab_socialls_labels_page'
        );
    }

    if (in_array('partnership', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Partnership', 'me5rine-lab'),
            __('Partnership', 'me5rine-lab'),
            'manage_options',
            'admin-lab-partnership',
            'admin_lab_partnership_admin_ui'
        );
    }    

    if (in_array('subscription', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Subscription', 'me5rine-lab'),
            __('Subscription', 'me5rine-lab'),
            'manage_options',
            'admin-lab-subscription',
            'admin_lab_subscription_admin_ui'
        );
    }

    if (in_array('remote_news', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Manage Remote News', 'me5rine-lab'),
            __('Remote News', 'me5rine-lab'),
            'manage_options',
            'admin-lab-remote-news',
            'admin_lab_remote_news_admin_ui'
        );
    }

    if (in_array('user_management', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('User management', 'me5rine-lab'),
            __('User management', 'me5rine-lab'),
            'manage_options',
            'admin-lab-user-management',
            'admin_lab_user_management_admin_ui'
        );
    }

    if (in_array('comparator', $active_modules)) {
        add_submenu_page(
            'me5rine-lab',
            __('Comparator', 'me5rine-lab'),
            __('Comparator', 'me5rine-lab'),
            'manage_options',
            'admin-lab-comparator',
            'admin_lab_comparator_admin_ui'
        );
    }
    
    add_submenu_page(
        'me5rine-lab',
        __('Settings', 'me5rine-lab'),
        __('Settings', 'me5rine-lab'),
        'manage_options',
        'admin-lab-settings',
        'admin_lab_admin_ui'
    );
}
add_action('admin_menu', 'admin_lab_admin_menu');


// Ajouter toutes les pages qui doivent être rattachées au menu "Me5rine LAB"
function admin_lab_me5rine_pages() {
    return [
        'me5rine-lab',
        'admin-lab-marketing',
        'admin-lab-marketing-edit',
        'admin-lab-shortcodes',
        'admin-lab-shortcodes-edit',
        'admin-lab-remote-news',
        'admin-lab-remote-news-edit-source',
        'admin-lab-remote-news-edit-mapping',
        'admin-lab-remote-news-edit-query',
        'admin-lab-socialls',
        'admin-lab-partnership',
        'admin-lab-subscription',
        'admin-lab-user-management',
        'admin-lab-settings',
        'admin-lab-comparator',
    ];
}

// Surlignage du sous-menu
function admin_lab_me5rine_submenu_groups() {
    return [
        'admin-lab-remote-news' => [
            'admin-lab-remote-news',
            'admin-lab-remote-news-edit-source',
            'admin-lab-remote-news-edit-mapping',
            'admin-lab-remote-news-edit-query',
        ],
        'admin-lab-shortcodes' => [
            'admin-lab-shortcodes',
            'admin-lab-shortcodes-edit',
        ],
        'admin-lab-marketing' => [
            'admin-lab-marketing',
            'admin-lab-marketing-edit',
        ],
    ];
}

// Inclure les options d'écran
add_action( 'load-me5rine-lab_page_admin-lab-comparator', 'admin_lab_comparator_screen_options' );
add_action( 'load-me5rine-lab_page_admin-lab-shortcodes', 'admin_lab_shortcodes_screen_options' );
add_action( 'load-me5rine-lab_page_admin-lab-marketing', 'admin_lab_campaigns_screen_options' );

// Écrans liés aux giveaways
function admin_lab_is_giveaway_related() {
    global $pagenow;

    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'giveaway') {
        return true;
    }
    if ($pagenow === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === 'giveaway') {
        return true;
    }
    if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'giveaway') {
        return true;
    }
    if ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], ['giveaway_category', 'giveaway_rewards'], true)) {
        return true;
    }
    if ($pagenow === 'term.php' && isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], ['giveaway_category', 'giveaway_rewards'], true)) {
        return true;
    }

    return false;
}

add_filter('parent_file', function ($parent_file) {
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

    if (admin_lab_is_giveaway_related()) {
        return 'me5rine-lab';
    }

    if ($page && in_array($page, admin_lab_me5rine_pages(), true)) {
        return 'me5rine-lab';
    }

    return $parent_file;
});

add_filter('submenu_file', function ($submenu_file) {
    $page   = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $groups = admin_lab_me5rine_submenu_groups();

    if ($page) {
        foreach ($groups as $parent_slug => $children) {
            if (in_array($page, $children, true)) {
                $submenu_file = $parent_slug;
                break;
            }
        }
    }

    if (admin_lab_is_giveaway_related()) {
        $submenu_file = 'edit.php?post_type=giveaway';
    }

    return $submenu_file;
});

// Enregistrer les paramètres des modules
function admin_lab_register_settings() {
    register_setting('admin_lab_settings', 'admin_lab_active_modules', [
        'type' => 'array',
        'sanitize_callback' => function ($value) {
            return is_array($value) ? array_filter($value, 'is_string') : [];
        }
    ]);
    register_setting('admin_lab_settings', 'admin_lab_delete_data_on_uninstall', [
        'type'              => 'boolean',
        'sanitize_callback' => function ($value) {
            return (bool) $value;
        },
        'default'           => false
    ]);    
}
add_action('admin_init', 'admin_lab_register_settings');

// Charger les modules activés
function admin_lab_load_modules() {
    $active_modules = get_option('admin_lab_active_modules', []);
    $registry = function_exists('admin_lab_get_modules_registry') ? admin_lab_get_modules_registry() : [];

    foreach ($active_modules as $slug) {
        if (isset($registry[$slug])) {
            $path = ME5RINE_LAB_MODULES . $registry[$slug];
            if (file_exists($path)) {
                include_once $path;
            }
        }
    }
}
add_action('plugins_loaded', 'admin_lab_load_modules');

// Définition du chemin pour le dossier custom hooks
define('ME5RINE_HOOKS_DIR', WP_CONTENT_DIR . '/uploads/me5rine-lab');
define('ME5RINE_HOOKS_FILE', ME5RINE_HOOKS_DIR . '/custom-hooks.php');

/**
 * Charger les hooks personnalisés s'ils existent.
 */
if (file_exists(ME5RINE_HOOKS_FILE)) {
    include_once ME5RINE_HOOKS_FILE;
}

/**
 * Vérifie et crée le fichier custom-hooks.php si nécessaire (uniquement via FTP).
 */
function admin_lab_ensure_custom_hooks_file() {
    // Créer le dossier s'il n'existe pas
    if (!file_exists(ME5RINE_HOOKS_DIR)) {
        wp_mkdir_p(ME5RINE_HOOKS_DIR);
    }

    // Créer le fichier avec un contenu par défaut s'il n'existe pas
    if (!file_exists(ME5RINE_HOOKS_FILE)) {
        $default_content = "<?php\n// Ajoutez vos hooks personnalisés ici\n";
        file_put_contents(ME5RINE_HOOKS_FILE, $default_content);
    }
}

// Exécuter cette fonction lors de l'activation du plugin
register_activation_hook(__FILE__, 'admin_lab_ensure_custom_hooks_file');

/**
 * Affiche un message d'avertissement si le fichier custom-hooks.php est manquant.
 */
add_action('admin_notices', function() {
    if (!file_exists(ME5RINE_HOOKS_FILE)) {
        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>Me5rine LAB :</strong> Le fichier <code>custom-hooks.php</code> est manquant. Veuillez le créer dans <code>/wp-content/uploads/me5rine-lab/</code> via FTP.</p>
        </div>';
    }
});

// Vérifier si l'événement cron est déjà planifié, sinon l'ajouter
$timestamp = wp_next_scheduled('admin_lab_update_display_name_cron');
if ($timestamp === false || abs(time() - $timestamp) > 3600) {
    wp_clear_scheduled_hook('admin_lab_update_display_name_cron');
    wp_schedule_event(time(), 'hourly', 'admin_lab_update_display_name_cron');
}

// Ajouter l'action pour le cron
add_action('admin_lab_update_display_name_cron', 'admin_lab_update_user_display_batch');

// Charger Select2 dans l'administration WordPress
function load_select2_admin_scripts() {
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'load_select2_admin_scripts');

// Charger UI Touch Punch dans l'administration WordPress
function admin_lab_enqueue_touch_support() {
    wp_enqueue_script('jquery-ui-touch-punch', plugin_dir_url(__FILE__) . 'assets/js/jquery.ui.touch-punch.min.js', ['jquery', 'jquery-ui-sortable'], ME5RINE_LAB_VERSION, true);
}
add_action('admin_enqueue_scripts', 'admin_lab_enqueue_touch_support');
add_action('wp_enqueue_scripts', 'admin_lab_enqueue_touch_support');

// Chargement des styles et scripts (admin + front + spécifiques)
function admin_lab_enqueue_assets() {
    if (is_admin()) {
        wp_enqueue_style('admin-lab-admin-style', plugin_dir_url(__FILE__) . 'assets/css/global-admin.css', [], ME5RINE_LAB_VERSION);
        wp_enqueue_style('admin-lab-colors-style', plugin_dir_url(__FILE__) . 'assets/css/global-colors.css', ['elementor-frontend'], ME5RINE_LAB_VERSION);

        if (isset($_GET['page']) && $_GET['page'] === 'rafflepress_pro_builder') {
            wp_enqueue_script('admin-lab-rafflepress-save-listener', plugin_dir_url(__FILE__) . 'assets/js/giveaways-rafflepress-save-listener.js', ['jquery'], ME5RINE_LAB_VERSION, true);
            wp_localize_script('admin-lab-rafflepress-save-listener', 'admin_lab_ajax_obj', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('rafflepress_pro_save_giveaway')
            ]);
        }
    } else {
        // Chargement du script de synchronisation des couleurs Elementor côté front
        wp_enqueue_script('admin-lab-sync-elementor-colors', plugin_dir_url(__FILE__) . 'assets/js/sync-elementor-colors.js', [], ME5RINE_LAB_VERSION, true);
        wp_enqueue_style('admin-lab-colors-style', plugin_dir_url(__FILE__) . 'assets/css/global-colors.css', ['elementor-frontend'], ME5RINE_LAB_VERSION);
    }
}
add_action('admin_enqueue_scripts', 'admin_lab_enqueue_assets');
add_action('wp_enqueue_scripts', 'admin_lab_enqueue_assets');

function load_admin_lab_translations() {
    load_plugin_textdomain('me5rine-lab', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('init', 'load_admin_lab_translations');
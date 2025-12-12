<?php
// File: modules/comparator/admin/comparator-settings.php

if (!defined('ABSPATH')) {
    exit;
}

// Tabs UI
require_once __DIR__ . '/tabs/comparator-settings-tab-general.php';
require_once __DIR__ . '/tabs/comparator-settings-tab-categories.php';
require_once __DIR__ . '/tabs/comparator-settings-tab-stats.php';

/**
 * Screen options pour la page Comparator (onglet Stats).
 */
function admin_lab_comparator_screen_options() {
    $screen = get_current_screen();

    // ID de l'écran pour la page "Comparator"
    if ( ! is_object( $screen ) || $screen->id !== 'me5rine-lab_page_admin-lab-comparator' ) {
        return;
    }

    // On ne l’affiche que sur l’onglet "stats"
    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
    if ( $current_tab !== 'stats' ) {
        return;
    }

    add_screen_option(
        'per_page',
        [
            'label'   => __( 'Clicks per page', 'me5rine-lab' ),
            'default' => 20,
            'option'  => 'admin_lab_comparator_clicks_per_page',
        ]
    );
}

/**
 * Sauvegarde de l’option "Clicks per page" pour le comparator.
 */
function admin_lab_set_comparator_screen_option( $status, $option, $value ) {
    if ( $option === 'admin_lab_comparator_clicks_per_page' ) {
        return (int) $value;
    }
    return $status;
}
add_filter( 'set-screen-option', 'admin_lab_set_comparator_screen_option', 10, 3 );

/**
 * Déclare les colonnes disponibles pour l’écran "Comparator".
 *
 * Cela permet à WordPress d’afficher les cases à cocher
 * dans "Options de l’écran" → "Colonnes".
 */
function admin_lab_comparator_manage_columns( $columns ) {

    // On s’appuie sur les colonnes de la WP_List_Table
    if ( class_exists( 'Admin_Lab_Comparator_Clicks_List_Table' ) ) {
        $table   = new Admin_Lab_Comparator_Clicks_List_Table();
        $columns = $table->get_columns();
    }

    return $columns;
}
add_filter(
    'manage_me5rine-lab_page_admin-lab-comparator_columns',
    'admin_lab_comparator_manage_columns'
);

/**
 * Getter des settings du comparator.
 * Option: admin_lab_comparator_settings
 *
 * [
 *   'mode'         => 'auto'|'manual',
 *   'api_base'     => 'https://api.clicksngames.com/api',
 *   'api_token'    => '',
 *   'category_map' => [ cat_id => game_id, ... ],
 * ]
 */
function admin_lab_comparator_get_settings() {
    $defaults = [
        'mode'          => 'auto',
        'api_base'      => '',
        'api_token'     => '',
        'category_map'  => [],
        'frontend_base' => '',
    ];

    $options = get_option('admin_lab_comparator_settings', []);
    if (!is_array($options)) {
        $options = [];
    }

    return wp_parse_args($options, $defaults);
}

/**
 * Mise à jour + nettoyage des settings du comparator.
 *
 * $settings peut contenir par exemple :
 * - mode
 * - api_base
 * - api_token
 * - category_map[cat_id] = game_id
 * - new_category / new_game_id (ajout d’un mapping)
 */
function admin_lab_comparator_update_settings(array $settings) {
    $current = admin_lab_comparator_get_settings();
    $new     = array_merge($current, $settings);

    // Mode
    $new['mode'] = in_array($new['mode'] ?? '', ['auto', 'manual'], true)
        ? $new['mode']
        : 'auto';

    // URL API
    $new['api_base'] = untrailingslashit(esc_url_raw($new['api_base'] ?? ''));

    // Token
    $new['api_token'] = trim($new['api_token'] ?? '');

    // Mapping catégorie -> game_id (existant)
    $category_map = [];
    if (!empty($new['category_map']) && is_array($new['category_map'])) {
        foreach ($new['category_map'] as $cat_id => $game_id) {
            $cat_id  = (int) $cat_id;
            $game_id = (int) $game_id;
            if ($cat_id > 0 && $game_id > 0) {
                $category_map[$cat_id] = $game_id;
            }
        }
    }

    // URL de base du comparateur (frontend)
    $new['frontend_base'] = untrailingslashit(esc_url_raw($new['frontend_base'] ?? ''));

    // Nouveau mapping ajouté via la ligne "Add a new mapping"
    if (!empty($new['new_category']) && !empty($new['new_game_id'])) {
        $new_cat  = (int) $new['new_category'];
        $new_game = (int) $new['new_game_id'];

        if ($new_cat > 0 && $new_game > 0) {
            $category_map[$new_cat] = $new_game;
        }
    }

    $new['category_map'] = $category_map;

    // Ne pas persister ces champs temporaires
    unset($new['new_category'], $new['new_game_id']);

    return $new;
}

/**
 * Enregistrement du groupe d’options pour le comparator.
 */
function admin_lab_comparator_register_settings() {
    register_setting(
        'admin_lab_comparator_settings_group',
        'admin_lab_comparator_settings',
        [
            'sanitize_callback' => 'admin_lab_comparator_sanitize_settings',
        ]
    );
}

/**
 * Sanitize callback utilisé par register_setting.
 */
function admin_lab_comparator_sanitize_settings($input) {
    if (!is_array($input)) {
        $input = [];
    }

    // Retourner la version nettoyée, WordPress la sauvegarde.
    return admin_lab_comparator_update_settings($input);
}

/**
 * UI de la page admin du comparator.
 *
 * Callback du submenu:
 *  add_submenu_page(
 *      'me5rine-lab',
 *      'Comparator',
 *      'Comparator',
 *      'manage_options',
 *      'admin-lab-comparator',
 *      'admin_lab_comparator_admin_ui'
 *  );
 */
function admin_lab_comparator_admin_ui() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = admin_lab_comparator_get_settings();
    $mode     = $settings['mode'] ?? 'auto';

    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

    $tabs = [
        'general' => __('General', 'me5rine-lab'),
    ];

    if ($mode === 'manual') {
        $tabs['categories'] = __('Category Mapping', 'me5rine-lab');
    }

    $tabs['stats'] = __('Statistics', 'me5rine-lab');

    if (!isset($tabs[$current_tab])) {
        $current_tab = 'general';
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Comparator settings', 'me5rine-lab'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                <?php
                $tab_url = add_query_arg(
                    [
                        'page' => 'admin-lab-comparator',
                        'tab'  => $tab_key,
                    ],
                    admin_url('admin.php')
                );
                $active_class = ($current_tab === $tab_key) ? ' nav-tab-active' : '';
                ?>
                <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab<?php echo esc_attr($active_class); ?>">
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <?php
        switch ($current_tab) {
            case 'categories':
                if ($mode !== 'manual') {
                    echo '<div class="notice notice-info"><p>' .
                         esc_html__('Category mapping is only available when mode is set to "Manual".', 'me5rine-lab') .
                         '</p></div>';
                } else {
                    admin_lab_comparator_render_tab_categories($settings);
                }
                break;

            case 'stats':
                admin_lab_comparator_render_stats_tab();
                break;

            case 'general':
            default:
                admin_lab_comparator_render_tab_general($settings);
                break;
        }
        ?>
    </div>
    <?php
}

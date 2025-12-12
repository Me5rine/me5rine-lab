<?php
// File: modules/comparator/admin/tabs/comparator-settings-tab-stats.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/../comparator-class-clicks-list-table.php';

/**
 * Récupère / normalise les filtres de la page stats.
 */
function admin_lab_comparator_get_clicks_filters() {
    $now   = current_time( 'timestamp', true );
    $month = 30 * DAY_IN_SECONDS;

    $default_from = gmdate( 'Y-m-d', $now - $month );
    $default_to   = gmdate( 'Y-m-d', $now );

    $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : $default_from;
    $date_to   = isset( $_GET['date_to'] )   ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) )   : $default_to;

    // Normalisation Y-m-d (si jamais)
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
        $date_from = $default_from;
    }
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
        $date_to = $default_to;
    }

    return [
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'game_id'   => isset( $_GET['game_id'] )   ? (int) $_GET['game_id']   : 0,
        'store'     => isset( $_GET['store'] )     ? sanitize_text_field( wp_unslash( $_GET['store'] ) ) : '',
        'platform'  => isset( $_GET['platform'] )  ? sanitize_text_field( wp_unslash( $_GET['platform'] ) ) : '',
        'click_type'=> isset( $_GET['click_type'] )? sanitize_text_field( wp_unslash( $_GET['click_type'] ) ) : '',
        'context'   => isset( $_GET['context'] )   ? sanitize_text_field( wp_unslash( $_GET['context'] ) ) : '',
    ];
}

/**
 * Construit le WHERE SQL en fonction des filtres.
 *
 * @return array [ 'sql' => 'WHERE ...', 'params' => [ ... ] ]
 */
function admin_lab_comparator_get_clicks_where_sql( array $filters ) {
    $where   = [];
    $params  = [];

    // Période
    $where[]  = 'clicked_at >= %s';
    $params[] = $filters['date_from'] . ' 00:00:00';

    $where[]  = 'clicked_at <= %s';
    $params[] = $filters['date_to'] . ' 23:59:59';

    // Jeu
    if ( ! empty( $filters['game_id'] ) ) {
        $where[]  = 'game_id = %d';
        $params[] = (int) $filters['game_id'];
    }

    // Store
    if ( $filters['store'] !== '' ) {
        $where[]  = 'store = %s';
        $params[] = $filters['store'];
    }

    // Platform
    if ( $filters['platform'] !== '' ) {
        $where[]  = 'platform = %s';
        $params[] = $filters['platform'];
    }

    // Click type
    if ( $filters['click_type'] !== '' ) {
        $where[]  = 'click_type = %s';
        $params[] = $filters['click_type'];
    }

    // Context
    if ( $filters['context'] !== '' ) {
        $where[]  = 'context = %s';
        $params[] = $filters['context'];
    }

    $sql = '';
    if ( $where ) {
        $sql = 'WHERE ' . implode( ' AND ', $where );
    }

    return [
        'sql'    => $sql,
        'params' => $params,
    ];
}

/**
 * Récupère les valeurs possibles pour les filtres (jeux, stores, plateformes, etc.)
 * sur la période.
 */
function admin_lab_comparator_get_clicks_filter_values( array $filters ) {
    global $wpdb;
    $table = admin_lab_getTable( 'comparator_clicks', false );

    $where_data = admin_lab_comparator_get_clicks_where_sql( $filters );
    $where_sql  = $where_data['sql'];
    $params     = $where_data['params'];

    $game_ids   = [];
    $stores     = [];
    $platforms  = [];
    $ctypes     = [];
    $contexts   = [];

    // Jeux
    $sql = "SELECT DISTINCT game_id FROM {$table} {$where_sql} AND game_id > 0 ORDER BY game_id ASC";
    $game_ids = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

    // Stores
    $sql = "SELECT DISTINCT store FROM {$table} {$where_sql} AND store <> '' ORDER BY store ASC";
    $stores = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

    // Platforms
    $sql = "SELECT DISTINCT platform FROM {$table} {$where_sql} AND platform <> '' ORDER BY platform ASC";
    $platforms = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

    // Click types
    $sql = "SELECT DISTINCT click_type FROM {$table} {$where_sql} AND click_type <> '' ORDER BY click_type ASC";
    $ctypes = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

    // Contexts
    $sql = "SELECT DISTINCT context FROM {$table} {$where_sql} AND context <> '' ORDER BY context ASC";
    $contexts = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

    return [
        'game_ids'  => array_map( 'intval', $game_ids ),
        'stores'    => $stores,
        'platforms' => $platforms,
        'ctypes'    => $ctypes,
        'contexts'  => $contexts,
    ];
}

/**
 * Calcule les stats overview sur la période / les filtres.
 */
function admin_lab_comparator_get_overview_stats( array $filters ) {
    global $wpdb;
    $table      = admin_lab_getTable( 'comparator_clicks', false );
    $where_data = admin_lab_comparator_get_clicks_where_sql( $filters );
    $where_sql  = $where_data['sql'];
    $params     = $where_data['params'];

    // Total clicks
    $sql_total = "SELECT COUNT(*) FROM {$table} {$where_sql}";
    $total_clicks = (int) $wpdb->get_var( $wpdb->prepare( $sql_total, $params ) );

    // Clicks par type
    $sql_by_type = "
        SELECT click_type, COUNT(*) AS total
        FROM {$table}
        {$where_sql}
        GROUP BY click_type
        ORDER BY total DESC
    ";
    $by_type = $wpdb->get_results( $wpdb->prepare( $sql_by_type, $params ), ARRAY_A );

    // Jeux uniques + top 5 jeux
    $sql_unique_games = "
        SELECT COUNT(DISTINCT game_id)
        FROM {$table}
        {$where_sql}
        AND game_id > 0
    ";
    $unique_games = (int) $wpdb->get_var( $wpdb->prepare( $sql_unique_games, $params ) );

    $sql_top_games = "
        SELECT game_id, COUNT(*) AS clicks
        FROM {$table}
        {$where_sql}
        AND game_id > 0
        GROUP BY game_id
        ORDER BY clicks DESC
        LIMIT 5
    ";
    $top_games = $wpdb->get_results( $wpdb->prepare( $sql_top_games, $params ), ARRAY_A );

    // Boutiques uniques + top 5 boutiques
    $sql_unique_stores = "
        SELECT COUNT(DISTINCT store)
        FROM {$table}
        {$where_sql}
        AND store <> ''
    ";
    $unique_stores = (int) $wpdb->get_var( $wpdb->prepare( $sql_unique_stores, $params ) );

    $sql_top_stores = "
        SELECT store, COUNT(*) AS clicks
        FROM {$table}
        {$where_sql}
        AND store <> ''
        GROUP BY store
        ORDER BY clicks DESC
        LIMIT 5
    ";
    $top_stores = $wpdb->get_results( $wpdb->prepare( $sql_top_stores, $params ), ARRAY_A );

    // Plateformes uniques + top 5 plateformes
    $sql_unique_platforms = "
        SELECT COUNT(DISTINCT platform)
        FROM {$table}
        {$where_sql}
        AND platform <> ''
    ";
    $unique_platforms = (int) $wpdb->get_var( $wpdb->prepare( $sql_unique_platforms, $params ) );

    $sql_top_platforms = "
        SELECT platform, COUNT(*) AS clicks
        FROM {$table}
        {$where_sql}
        AND platform <> ''
        GROUP BY platform
        ORDER BY clicks DESC
        LIMIT 5
    ";
    $top_platforms = $wpdb->get_results( $wpdb->prepare( $sql_top_platforms, $params ), ARRAY_A );

    return [
        'total_clicks'     => $total_clicks,
        'by_type'          => $by_type,
        'unique_games'     => $unique_games,
        'top_games'        => $top_games,
        'unique_stores'    => $unique_stores,
        'top_stores'       => $top_stores,
        'unique_platforms' => $unique_platforms,
        'top_platforms'    => $top_platforms,
    ];
}

/**
 * Callback de l’onglet "Statistics".
 */
function admin_lab_comparator_render_stats_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $filters       = admin_lab_comparator_get_clicks_filters();
    $filter_values = admin_lab_comparator_get_clicks_filter_values( $filters );
    $overview      = admin_lab_comparator_get_overview_stats( $filters );

    $list_table = new Admin_Lab_Comparator_Clicks_List_Table( [
        'filters' => $filters,
    ] );

    $list_table->prepare_items();
    ?>

    <h2><?php echo esc_html( __( 'Statistics filters', 'me5rine-lab' ) ); ?></h2>

    <div class="wrap admin-lab-comparator-stats-wrap">
                <!-- FILTERS BAR -->
        <form method="get" action="">
            <input type="hidden" name="page" value="admin-lab-comparator" />
            <input type="hidden" name="tab" value="stats" />

            <div class="admin-lab-comparator-stats-filters" style="display:flex; flex-wrap:wrap; gap:15px; align-items:flex-end; margin-bottom:20px;">

                <!-- Date from -->
                <div>
                    <label for="admin-lab-date-from"><?php esc_html_e( 'From', 'me5rine-lab' ); ?></label><br>
                    <input type="date" id="admin-lab-date-from" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" />
                </div>

                <!-- Date to -->
                <div>
                    <label for="admin-lab-date-to"><?php esc_html_e( 'To', 'me5rine-lab' ); ?></label><br>
                    <input type="date" id="admin-lab-date-to" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" />
                </div>

                <!-- Game -->
                <div>
                    <label for="admin-lab-game-id"><?php esc_html_e( 'Game', 'me5rine-lab' ); ?></label><br>
                    <select id="admin-lab-game-id" name="game_id">
                        <option value="0"><?php esc_html_e( 'All games', 'me5rine-lab' ); ?></option>
                        <?php foreach ( $filter_values['game_ids'] as $gid ) :
                            $info = function_exists( 'admin_lab_comparator_get_game_label_from_clicks' )
                                ? admin_lab_comparator_get_game_label_from_clicks( $gid )
                                : [
                                    'label' => sprintf( __( 'Game #%d', 'me5rine-lab' ), $gid ),
                                ];
                            ?>
                            <option value="<?php echo esc_attr( $gid ); ?>" <?php selected( $filters['game_id'], $gid ); ?>>
                                <?php echo esc_html( $info['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Store -->
                <div>
                    <label for="admin-lab-store"><?php esc_html_e( 'Store', 'me5rine-lab' ); ?></label><br>
                    <select id="admin-lab-store" name="store">
                        <option value=""><?php esc_html_e( 'All stores', 'me5rine-lab' ); ?></option>
                        <?php foreach ( $filter_values['stores'] as $store ) : ?>
                            <option value="<?php echo esc_attr( $store ); ?>" <?php selected( $filters['store'], $store ); ?>>
                                <?php echo esc_html( $store ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Platform -->
                <div>
                    <label for="admin-lab-platform"><?php esc_html_e( 'Platform', 'me5rine-lab' ); ?></label><br>
                    <select id="admin-lab-platform" name="platform">
                        <option value=""><?php esc_html_e( 'All platforms', 'me5rine-lab' ); ?></option>
                        <?php foreach ( $filter_values['platforms'] as $pf ) : ?>
                            <option value="<?php echo esc_attr( $pf ); ?>" <?php selected( $filters['platform'], $pf ); ?>>
                                <?php echo esc_html( $pf ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Click type -->
                <div>
                    <label for="admin-lab-click-type"><?php esc_html_e( 'Click type', 'me5rine-lab' ); ?></label><br>
                    <select id="admin-lab-click-type" name="click_type">
                        <option value=""><?php esc_html_e( 'All types', 'me5rine-lab' ); ?></option>
                        <?php foreach ( $filter_values['ctypes'] as $ct ) : ?>
                            <option value="<?php echo esc_attr( $ct ); ?>" <?php selected( $filters['click_type'], $ct ); ?>>
                                <?php echo esc_html( admin_lab_comparator_humanize_slug( $ct, 'click_type' ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Context -->
                <div>
                    <label for="admin-lab-context"><?php esc_html_e( 'Context', 'me5rine-lab' ); ?></label><br>
                    <select id="admin-lab-context" name="context">
                        <option value=""><?php esc_html_e( 'All contexts', 'me5rine-lab' ); ?></option>
                        <?php foreach ( $filter_values['contexts'] as $cx ) : ?>
                            <option value="<?php echo esc_attr( $cx ); ?>" <?php selected( $filters['context'], $cx ); ?>>
                                <?php echo esc_html( $cx ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Submit -->
                <div>
                    <button class="button button-primary" type="submit" style="margin-top:4px;">
                        <?php esc_html_e( 'Filter', 'me5rine-lab' ); ?>
                    </button>
                </div>
            </div>
        </form>

        <h2><?php echo esc_html( __( 'Comparator statistics', 'me5rine-lab' ) ); ?></h2>

        <!-- OVERVIEW TILES -->
        <div class="admin-lab-comparator-stats-tiles" style="display:flex; gap:20px; margin:20px 0; flex-wrap:wrap;">

            <!-- Tile: Total clicks -->
            <div class="admin-lab-stat-tile" style="flex:1; min-width:220px; background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px;">
                <h3 style="margin-top:0;"><?php echo esc_html( __( 'Clicks', 'me5rine-lab' ) ); ?></h3>
                <p style="font-size:24px; font-weight:bold; margin:0 0 10px;">
                    <?php echo esc_html( number_format_i18n( $overview['total_clicks'] ) ); ?>
                </p>
                <?php if ( ! empty( $overview['by_type'] ) ) : ?>
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ( $overview['by_type'] as $row ) : ?>
                            <li>
                                <?php
                                $label_slug = $row['click_type'];
                                $label = $label_slug !== ''
                                    ? admin_lab_comparator_humanize_slug( $label_slug, 'click_type' )
                                    : __( 'Unknown', 'me5rine-lab' );
                                echo esc_html( sprintf( '%s: %d', $label, $row['total'] ) );
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p style="margin:0;"><?php echo esc_html( __( 'No clicks in this period.', 'me5rine-lab' ) ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Tile: Games -->
            <div class="admin-lab-stat-tile" style="flex:1; min-width:220px; background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px;">
                <h3 style="margin-top:0;"><?php echo esc_html( __( 'Games', 'me5rine-lab' ) ); ?></h3>
                <p style="font-size:24px; font-weight:bold; margin:0 0 10px;">
                    <?php echo esc_html( number_format_i18n( $overview['unique_games'] ) ); ?>
                </p>
                <?php if ( ! empty( $overview['top_games'] ) ) : ?>
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ( $overview['top_games'] as $game ) : ?>
                            <?php
                            $gid  = (int) $game['game_id'];
                            $info = function_exists( 'admin_lab_comparator_get_game_label_from_clicks' )
                                ? admin_lab_comparator_get_game_label_from_clicks( $gid )
                                : [
                                    'label'     => sprintf( __( 'Game #%d', 'me5rine-lab' ), $gid ),
                                    'term_link' => '',
                                ];

                            $cmp_url = function_exists( 'admin_lab_comparator_get_game_frontend_url' )
                                ? admin_lab_comparator_get_game_frontend_url( $gid )
                                : '';
                            ?>
                            <li>
                                <?php if ( ! empty( $info['term_link'] ) ) : ?>
                                    <a href="<?php echo esc_url( $info['term_link'] ); ?>" target="_blank">
                                        <?php echo esc_html( $info['label'] ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $info['label'] ); ?>
                                <?php endif; ?>

                                <?php if ( $cmp_url ) : ?>
                                    &nbsp;(
                                    <a href="<?php echo esc_url( $cmp_url ); ?>" target="_blank">
                                        <?php esc_html_e( 'Game sheet', 'me5rine-lab' ); ?>
                                    </a>
                                    )
                                <?php endif; ?>

                                &nbsp;– <?php echo esc_html( $game['clicks'] ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p style="margin:0;"><?php echo esc_html( __( 'No games in this period.', 'me5rine-lab' ) ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Tile: Stores -->
            <div class="admin-lab-stat-tile" style="flex:1; min-width:220px; background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px;">
                <h3 style="margin-top:0;"><?php echo esc_html( __( 'Stores', 'me5rine-lab' ) ); ?></h3>
                <p style="font-size:24px; font-weight:bold; margin:0 0 10px;">
                    <?php echo esc_html( number_format_i18n( $overview['unique_stores'] ) ); ?>
                </p>
                <?php if ( ! empty( $overview['top_stores'] ) ) : ?>
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ( $overview['top_stores'] as $store ) : ?>
                            <li>
                                <?php
                                $label = $store['store'] !== '' ? $store['store'] : __( 'Unknown', 'me5rine-lab' );
                                echo esc_html( sprintf( '%s: %d', $label, $store['clicks'] ) );
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p style="margin:0;"><?php echo esc_html( __( 'No stores in this period.', 'me5rine-lab' ) ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Tile: Platforms -->
            <div class="admin-lab-stat-tile" style="flex:1; min-width:220px; background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px;">
                <h3 style="margin-top:0;"><?php echo esc_html( __( 'Platforms', 'me5rine-lab' ) ); ?></h3>
                <p style="font-size:24px; font-weight:bold; margin:0 0 10px;">
                    <?php echo esc_html( number_format_i18n( $overview['unique_platforms'] ) ); ?>
                </p>
                <?php if ( ! empty( $overview['top_platforms'] ) ) : ?>
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ( $overview['top_platforms'] as $pf ) : ?>
                            <li>
                                <?php
                                $label = $pf['platform'] !== '' ? $pf['platform'] : __( 'Unknown', 'me5rine-lab' );
                                echo esc_html( sprintf( '%s: %d', $label, $pf['clicks'] ) );
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p style="margin:0;"><?php echo esc_html( __( 'No platforms in this period.', 'me5rine-lab' ) ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- LOGS TABLE -->
        <h3><?php esc_html_e( 'Clicks log', 'me5rine-lab' ); ?></h3>
        <form method="get">
            <input type="hidden" name="page" value="admin-lab-comparator" />
            <input type="hidden" name="tab" value="stats" />

            <?php
            // On repasse les filtres dans le formulaire de pagination
            foreach ( $filters as $key => $value ) :
                if ( in_array( $key, [ 'date_from', 'date_to', 'game_id', 'store', 'platform', 'click_type', 'context' ], true ) ) :
                    ?>
                    <input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
                    <?php
                endif;
            endforeach;

            $list_table->display();
            ?>
        </form>
    </div>

    <?php
}


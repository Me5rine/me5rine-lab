<?php
// File: modules/comparator/admin/comparator-class-clicks-list-table.php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Tableau des clics du comparator.
 */
class Admin_Lab_Comparator_Clicks_List_Table extends WP_List_Table {

    /**
     * Filtres (période, jeu, store, etc.)
     * @var array
     */
    protected $filters = [];

    /**
     * Constructeur.
     *
     * @param array $args
     */
    public function __construct($args = []) {
        parent::__construct([
            'singular' => 'comparator_click',
            'plural'   => 'comparator_clicks',
            'ajax'     => false,
        ]);

        $defaults = [
            'filters' => [],
        ];
        $args = wp_parse_args($args, $defaults);

        $this->filters = is_array($args['filters']) ? $args['filters'] : [];
    }

    /**
     * Colonnes du tableau.
     */
    public function get_columns() {
        return [
            'game_id'    => __( 'Game', 'me5rine-lab' ),
            'store'      => __( 'Store', 'me5rine-lab' ),
            'platform'   => __( 'Platform', 'me5rine-lab' ),
            'click_type' => __( 'Click type', 'me5rine-lab' ),
            'clicked_at' => __( 'Clicked at', 'me5rine-lab' ),
            'post_id'    => __( 'Post', 'me5rine-lab' ),
        ];
    }

    /**
     * Colonnes triables.
     */
    protected function get_sortable_columns() {
        return [
            'clicked_at' => ['clicked_at', true],
            'game_id'    => ['game_id', false],
            'store'      => ['store', false],
            'platform'   => ['platform', false],
            'click_type' => ['click_type', false],
            'post_id'    => ['post_id', false],
            'offer_price'=> ['offer_price', false],
        ];
    }

    /**
     * Colonnes masquées (si besoin).
     */
    public function get_hidden_columns() {
        return [];
    }

    /**
     * Contenu par défaut des colonnes.
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'store':
                if ($item['store'] === 'all-prices') {
                    return esc_html__( 'All prices button', 'me5rine-lab' );
                }
                $slug = isset( $item['store'] ) ? $item['store'] : '';
                if ( $slug === '' ) {
                    return '-';
                }
                return esc_html( admin_lab_comparator_humanize_slug( $slug, 'store' ) );

            case 'game_id':
                $game_id = (int) $item['game_id'];
                if ( $game_id <= 0 ) {
                    return '-';
                }

                $info = admin_lab_comparator_get_game_label_from_clicks( $game_id );
                $label = $info['label'];

                // 1️⃣ Lien prioritaire : fiche comparateur
                $cmp_url = function_exists( 'admin_lab_comparator_get_game_frontend_url' )
                    ? admin_lab_comparator_get_game_frontend_url( $game_id )
                    : '';

                if ( $cmp_url ) {
                    return sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        esc_url( $cmp_url ),
                        esc_html( $label )
                    );
                }

                // 2️⃣ Fallback : catégorie "jeu"
                if ( ! empty( $info['term_link'] ) ) {
                    return sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        esc_url( $info['term_link'] ),
                        esc_html( $label )
                    );
                }

                // 3️⃣ Fallback : permalink du post principal
                if ( ! empty( $info['permalink'] ) ) {
                    return sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        esc_url( $info['permalink'] ),
                        esc_html( $label )
                    );
                }

                return esc_html( $label );

            case 'post_id':
                if (empty($item['post_id'])) {
                    return '-';
                }
                $post_id = (int) $item['post_id'];
                $title   = get_the_title($post_id);
                // 🔁 On pointe vers le FRONT, plus vers l'édition admin
                $link    = get_permalink($post_id);

                if ($link) {
                    return sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        esc_url($link),
                        esc_html($title ?: ('#' . $post_id))
                    );
                }
                return esc_html($title ?: ('#' . $post_id));

            case 'click_type':
                $slug = isset( $item['click_type'] ) ? $item['click_type'] : '';
                if ( $slug === '' ) {
                    return '-';
                }
                return esc_html( admin_lab_comparator_humanize_slug( $slug, 'click_type' ) );

            case 'platform':
                $slug = isset( $item['platform'] ) ? $item['platform'] : '';
                if ( $slug === '' ) {
                    return '-';
                }
                return esc_html( admin_lab_comparator_humanize_slug( $slug, 'platform' ) );

            case 'clicked_at':
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
        }

        return '';
    }

    /**
     * Prépare les items (requête SQL + pagination).
     */
    public function prepare_items() {
        global $wpdb;

        $table = admin_lab_getTable('comparator_clicks', false);
        if (empty($table)) {
            $this->items = [];
            return;
        }

        // Colonnes et colonnes triables
        $columns  = $this->get_columns();
        $hidden   = get_hidden_columns( $this->screen );
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        // Pagination
        $per_page     = $this->get_items_per_page( 'admin_lab_comparator_clicks_per_page', 20 );
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        // Tri
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'clicked_at';
        $order   = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $sortable_fields = array_keys($sortable);
        if (!in_array($orderby, $sortable_fields, true)) {
            $orderby = 'clicked_at';
        }

        /*
         * WHERE en fonction des filtres
         */
        $where   = [];
        $params  = [];

        // Période (obligatoire)
        $from = $this->filters['from'] ?? '';
        $to   = $this->filters['to'] ?? '';

        if (!$from || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$to || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d');
        }

        $where[]  = 'clicked_at >= %s';
        $params[] = $from . ' 00:00:00';

        $where[]  = 'clicked_at <= %s';
        $params[] = $to . ' 23:59:59';

        // Game ID
        if (!empty($this->filters['game_id'])) {
            $where[]  = 'game_id = %d';
            $params[] = (int) $this->filters['game_id'];
        }

        // Store
        if (!empty($this->filters['store'])) {
            $where[]  = 'store = %s';
            $params[] = $this->filters['store'];
        }

        // Platform
        if (!empty($this->filters['platform'])) {
            $where[]  = 'platform = %s';
            $params[] = $this->filters['platform'];
        }

        // Click type
        if (!empty($this->filters['click_type'])) {
            $where[]  = 'click_type = %s';
            $params[] = $this->filters['click_type'];
        }

        $where_sql = '';
        if (!empty($where)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }

        // Nombre total pour pagination
        $sql_count = "SELECT COUNT(*) FROM {$table} {$where_sql}";
        $total_items = (int) $wpdb->get_var($wpdb->prepare($sql_count, $params));

        // Requête principale
        $sql_items = "
            SELECT 
                id,
                game_id,
                store,
                platform,
                clicked_at,
                post_id,
                click_type
            FROM {$table}
            {$where_sql}
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d
        ";

        $params_items = array_merge($params, [$per_page, $offset]);

        $this->items = $wpdb->get_results(
            $wpdb->prepare($sql_items, $params_items),
            ARRAY_A
        );

        // Pagination WP_List_Table
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => $per_page > 0 ? ceil($total_items / $per_page) : 1,
        ]);
    }
}

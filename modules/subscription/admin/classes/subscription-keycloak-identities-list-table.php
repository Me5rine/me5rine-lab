<?php
// File: modules/subscription/admin/classes/subscription-keycloak-identities-list-table.php

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for Keycloak Identities
 */
class Subscription_Keycloak_Identities_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'user',
            'plural'   => 'users',
            'ajax'     => false
        ]);
    }

    public function get_items_per_page_option() {
        return 'subscription_keycloak_identities_per_page';
    }

    public function get_columns() {
        $columns = [
            'user'            => __('User', 'me5rine-lab'),
            'identity_count'  => __('Identities Count', 'me5rine-lab'),
            'last_sync'       => __('Last Sync', 'me5rine-lab'),
            'actions'         => __('Actions', 'me5rine-lab'),
        ];
        return $columns;
    }

    public function get_sortable_columns() {
        return [
            'user'            => ['display_name', false],
            'identity_count'  => ['identity_count', false],
            'last_sync'       => ['last_sync_at', true],
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'user':
                $display = !empty($item['display_name']) ? $item['display_name'] : $item['user_login'];
                $output = '<strong>' . esc_html($display) . '</strong><br>';
                $output .= '<small>' . esc_html($item['user_email']) . '</small>';
                return $output;
            
            case 'identity_count':
                return '<strong>' . esc_html($item['identity_count']) . '</strong>';
            
            case 'last_sync':
                if (!empty($item['last_sync_at'])) {
                    return esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['last_sync_at'])));
                }
                return '<em class="subscription-empty-state">Never</em>';
            
            default:
                return '';
        }
    }

    public function column_actions($item) {
        $view_url = add_query_arg([
            'page' => 'admin-lab-subscription',
            'tab' => 'keycloak_identities',
            'user_id' => $item['user_id']
        ], admin_url('admin.php'));
        
        $sync_url = wp_nonce_url(
            add_query_arg([
                'page' => 'admin-lab-subscription',
                'tab' => 'keycloak_identities',
                'sync_user' => $item['user_id']
            ], admin_url('admin.php')),
            'sync_user_' . $item['user_id']
        );
        
        return sprintf(
            '<a href="%s" class="button button-small">%s</a> ' .
            '<a href="%s" class="button button-small">%s</a>',
            esc_url($view_url),
            __('View Details', 'me5rine-lab'),
            esc_url($sync_url),
            __('Sync', 'me5rine-lab')
        );
    }

    public function prepare_items() {
        global $wpdb;
        
        $table_accounts = admin_lab_getTable('subscription_accounts');
        
        // Pagination
        $per_page = $this->get_items_per_page($this->get_items_per_page_option(), 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Search
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Sorting
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'display_name';
        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        // Build WHERE conditions
        $where_conditions = [];
        $params = [];
        
        if ($search) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_conditions[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
        }
        
        $where = !empty($where_conditions) ? 'WHERE ' . implode(' OR ', $where_conditions) : '';
        
        // Count total - need to count users with identity_count > 0
        // First get all users with their identity counts, then filter
        $count_sql = "
            SELECT sa.user_id
            FROM {$table_accounts} sa
            LEFT JOIN {$wpdb->users} u ON sa.user_id = u.ID
            {$where}
            GROUP BY sa.user_id
            HAVING SUM(CASE WHEN sa.provider_slug IN ('discord', 'twitch', 'youtube') THEN 1 ELSE 0 END) > 0
        ";
        if (!empty($params)) {
            $count_results = $wpdb->get_results($wpdb->prepare($count_sql, $params), ARRAY_A);
            $total_items = count($count_results);
        } else {
            $count_results = $wpdb->get_results($count_sql, ARRAY_A);
            $total_items = count($count_results);
        }
        
        // Build ORDER BY
        $allowed_orderby = ['display_name', 'user_login', 'user_email', 'identity_count', 'last_sync_at'];
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'display_name';
        }
        $orderby_clause = "u.{$orderby}";
        if ($orderby === 'identity_count') {
            $orderby_clause = 'identity_count';
        } elseif ($orderby === 'last_sync_at') {
            $orderby_clause = 'last_sync_at';
        }
        
        // Get users with identity counts
        $sql = "
            SELECT 
                sa.user_id,
                u.user_login,
                u.user_email,
                u.display_name,
                SUM(CASE WHEN sa.provider_slug IN ('discord', 'twitch', 'youtube') THEN 1 ELSE 0 END) as identity_count,
                MAX(sa.last_sync_at) as last_sync_at
            FROM {$table_accounts} sa
            LEFT JOIN {$wpdb->users} u ON sa.user_id = u.ID
            {$where}
            GROUP BY sa.user_id
            HAVING identity_count > 0
            ORDER BY {$orderby_clause} {$order}
            LIMIT %d OFFSET %d
        ";
        
        $params_with_limit = array_merge($params, [$per_page, $offset]);
        $this->items = $wpdb->get_results($wpdb->prepare($sql, $params_with_limit), ARRAY_A);
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
        
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }

    public function no_items() {
        _e('No users with linked identities found.', 'me5rine-lab');
    }

    protected function get_views() {
        return [];
    }
}


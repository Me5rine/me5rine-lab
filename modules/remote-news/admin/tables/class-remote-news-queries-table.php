<?php
// File: modules/remote-news/admin/tables/class-remote-news-queries-table.php

if (!defined('ABSPATH')) exit;
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Admin_Lab_Remote_News_Queries_Table extends WP_List_Table {

    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'query_id'    => __('Query ID', 'me5rine-lab'),
            'label'       => __('Label', 'me5rine-lab'),
            'sources'     => __('Sources', 'me5rine-lab'),
            'include_cats'=> __('Include (slugs)', 'me5rine-lab'),
            'exclude_cats'=> __('Exclude (slugs)', 'me5rine-lab'),
            'limit_items' => __('Limit', 'me5rine-lab'),
            'orderby'     => __('Order by', 'me5rine-lab'),
            'sort_order'  => __('Order', 'me5rine-lab'),
        ];
    }

    public function get_primary_column_name() {
        return 'query_id';
    }

    public function get_sortable_columns() {
        return [
            'query_id'    => ['query_id', true],
            'label'       => ['label', false],
            'limit_items' => ['limit_items', false],
            'orderby'     => ['orderby', false],
            'sort_order'  => ['sort_order', false],
        ];
    }

    public function column_cb($item) {
        return '<input type="checkbox" name="bulk_ids[]" value="'.esc_attr($item['query_id']).'" />';
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'query_id':
            case 'label':
            case 'include_cats':
            case 'exclude_cats':
            case 'limit_items':
            case 'orderby':
            case 'sort_order':
                return esc_html((string)($item[$column_name] ?? ''));
            case 'sources':
                $srcs = (array)($item['sources'] ?? []);
                return esc_html( implode(', ', array_map('strval', $srcs)) );
            default:
                return '';
        }
    }

    public function column_query_id($item) {
        $value = esc_html((string)($item['query_id'] ?? ''));

        // URL de retour
        $return = add_query_arg(
            ['page' => ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG, 'tab' => 'queries'],
            admin_url('admin.php')
        );

        // URL Edit
        $edit_url = add_query_arg([
            'page'   => 'admin-lab-remote-news-edit-query',
            'mode'   => 'edit',
            'id'     => $item['query_id'],
            'return' => rawurlencode($return),
        ], admin_url('admin.php'));

        // URL Delete
        $del_url = wp_nonce_url(
            add_query_arg([
                'action' => 'remote_news_delete_query',
                'id'     => $item['query_id'],
                'tab'    => 'queries',
            ], admin_url('admin-post.php')),
            'remote_news_delete_query_' . $item['query_id']
        );

        $actions = [
            'edit'   => sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Edit', 'me5rine-lab')),
            'delete' => sprintf('<a class="submitdelete" href="%s">%s</a>', esc_url($del_url), esc_html__('Delete', 'me5rine-lab')),
        ];

        return $value . $this->row_actions($actions);
    }

    public function get_bulk_actions() {
        return [
            'bulk_delete' => __('Delete', 'me5rine-lab'),
        ];
    }

    public function process_bulk_action() {
        if ($this->current_action() === 'bulk_delete'
            && !empty($_POST['bulk_ids'])
            && current_user_can('manage_options')
        ) {
            // Nonce natif des bulk actions :
            // action = 'bulk-' . $this->_args['plural']
            check_admin_referer('bulk-' . $this->_args['plural']);

            global $wpdb;
            $t = admin_lab_getTable('remote_news_queries', false);

            foreach ((array) $_POST['bulk_ids'] as $key) {
                $wpdb->delete($t, ['query_id' => sanitize_key($key)]);
            }
        }
    }

    public function prepare_items() {
        $per_page = (int) get_user_meta(get_current_user_id(), 'remote_news_queries_per_page', true);
        if ($per_page <= 0) $per_page = 20;

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $paged  = isset($_REQUEST['paged']) ? max(1, (int)$_REQUEST['paged']) : 1;

        $this->process_bulk_action();
        [$items, $total] = remote_news_queries_paginated($paged, $per_page, $search);

        $this->items = $items;
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page'    => $per_page,
            'total_pages' => max(1, ceil($total / $per_page)),
        ]);
    }

    public function __construct() {
        parent::__construct([
            'singular' => 'remote_news_query',
            'plural'   => 'remote_news_queries',
            'ajax'     => false,
        ]);
    }
}

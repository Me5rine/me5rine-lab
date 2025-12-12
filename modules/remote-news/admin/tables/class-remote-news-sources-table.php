<?php
// File: modules/remote-news/admin/tables/class-remote-news-sources-table.php

if (!defined('ABSPATH')) exit;
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

class Admin_Lab_Remote_News_Sources_Table extends WP_List_Table {

    public function get_columns() {
        return [
            'cb'             => '<input type="checkbox" />',
            'source_key'     => __('Key','me5rine-lab'),
            'table_prefix'   => __('Table prefix','me5rine-lab'),
            'site_url'       => __('Site URL','me5rine-lab'),
            'include_cats'   => __('Include cats (remote slugs)','me5rine-lab'),
            'limit_items'    => __('Limit','me5rine-lab'),
            'max_age_days'   => __('Max age (days)','me5rine-lab'),
            'sideload_images'=> __('Sideload','me5rine-lab'),
        ];
    }

    public function get_primary_column_name() {
        return 'source_key';
    }

    public function get_sortable_columns() {
        return [
            'source_key' => ['source_key', true],
        ];
    }

    public function column_cb($item) {
        return '<input type="checkbox" name="bulk_ids[]" value="'.esc_attr($item['source_key']).'" />';
    }

    public function column_default($item, $col) {
        switch ($col) {
            case 'sideload_images':
                return !empty($item[$col]) ? '✓' : '—';
            case 'table_prefix':
            case 'site_url':
            case 'include_cats':
            case 'limit_items':
            case 'max_age_days':
            case 'source_key':
                return esc_html((string)($item[$col] ?? ''));
            default:
                return '';
        }
    }

    // Colonne principale
    public function column_source_key($item) {
        $value = esc_html((string)($item['source_key'] ?? ''));

        // URL de retour
        $return = add_query_arg(
            ['page' => ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG, 'tab' => 'sources'],
            admin_url('admin.php')
        );

        // Edit
        $edit_url = add_query_arg([
            'page' => 'admin-lab-remote-news-edit-source',
            'mode' => 'edit',
            'key'  => $item['source_key'],
            'return' => rawurlencode($return),
        ], admin_url('admin.php'));

        // Delete
        $del_url = wp_nonce_url(
            add_query_arg([
                'action' => 'remote_news_delete_source',
                'key'    => $item['source_key'],
                'tab'    => 'sources',
            ], admin_url('admin-post.php')),
            'remote_news_delete_source_'.$item['source_key']
        );

        $actions = [
            'edit'   => sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Edit', 'me5rine-lab')),
            'delete' => sprintf('<a class="submitdelete" href="%s">%s</a>', esc_url($del_url), esc_html__('Delete', 'me5rine-lab')),
        ];

        return $value . $this->row_actions($actions);
    }

    public function get_bulk_actions() {
        return ['bulk_delete' => __('Delete','me5rine-lab')];
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
            $t = admin_lab_getTable('remote_news_sources', false);

            foreach ((array) $_POST['bulk_ids'] as $key) {
                $wpdb->delete($t, ['source_key' => sanitize_key($key)]);
            }
        }
    }

    public function prepare_items() {
        $per_page = (int) get_user_meta(get_current_user_id(), 'remote_news_sources_per_page', true);
        if ($per_page <= 0) $per_page = 20;

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $paged  = isset($_REQUEST['paged']) ? max(1, (int)$_REQUEST['paged']) : 1;

        $this->process_bulk_action();
        [$items, $total] = remote_news_sources_paginated($paged, $per_page, $search);

        $this->items = $items;
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->set_pagination_args([
            'total_items' => $total,
            'per_page'    => $per_page,
            'total_pages' => max(1, ceil($total/$per_page)),
        ]);
    }

    public function __construct() {
        parent::__construct([
            'singular' => 'remote_news_source',
            'plural'   => 'remote_news_sources',
            'ajax'     => false,
        ]);
    }
}

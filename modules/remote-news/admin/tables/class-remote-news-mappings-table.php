<?php
// File: modules/remote-news/admin/tables/class-remote-news-mappings-table.php

if (!defined('ABSPATH')) exit;
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Admin_Lab_Remote_News_Mappings_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'remote_news_mapping',
            'plural'   => 'remote_news_mappings',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'source_key'  => __('Source', 'me5rine-lab'),
            'remote_slug' => __('Remote slug', 'me5rine-lab'),
            'local_slug'  => __('Local slug', 'me5rine-lab'),
        ];
    }

    public function get_primary_column_name() {
        // La colonne principale = "Source"
        return 'source_key';
    }

    public function get_sortable_columns() {
        return [
            'source_key'  => ['source_key', true],
            'remote_slug' => ['remote_slug', true],
            'local_slug'  => ['local_slug', true],
        ];
    }

    public function column_cb($item) {
        // On encode source + remote pour le bulk : source::remote
        $val = $item['source_key'] . '::' . $item['remote_slug'];
        return '<input type="checkbox" name="bulk_ids[]" value="' . esc_attr($val) . '" />';
    }

    /**
     * Colonne principale "Source" avec actions "Edit / Delete"
     */
    public function column_source_key($item) {
        $primary = esc_html((string)($item['source_key'] ?? ''));

        $src   = $item['source_key'] ?? '';
        $rem   = $item['remote_slug'] ?? '';

        $page_slug = defined('ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG')
            ? ADMIN_LAB_REMOTE_NEWS_PAGE_SLUG
            : 'admin-lab-remote-news';

        // Retour vers l’onglet "Mappings"
        $return = add_query_arg([
            'page' => $page_slug,
            'tab'  => 'mappings',
        ], admin_url('admin.php'));

        // URL d’édition (formulaire add/edit mapping)
        $edit_url = add_query_arg([
            'page'   => 'admin-lab-remote-news-edit-mapping',
            'mode'   => 'edit',
            'src'    => $src,
            'remote' => $rem,
            'return' => rawurlencode($return),
        ], admin_url('admin.php'));

        // URL de suppression (handler admin_post_remote_news_delete_mapping)
        $del_url = wp_nonce_url(
            add_query_arg([
                'action' => 'remote_news_delete_mapping',
                'src'    => $src,
                'remote' => $rem,
                'return' => rawurlencode($return),
            ], admin_url('admin-post.php')),
            'remote_news_delete_mapping_' . $src . '__' . $rem
        );

        $actions = [
            'edit'   => '<a href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'me5rine-lab') . '</a>',
            'delete' => '<a href="' . esc_url($del_url) . '" class="submitdelete delete-mapping">'
                        . esc_html__('Delete', 'me5rine-lab') . '</a>',
        ];

        return $primary . $this->row_actions($actions);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'remote_slug':
            case 'local_slug':
                return esc_html((string)($item[$column_name] ?? ''));
            // 'source_key' est géré par column_source_key()
            default:
                return '';
        }
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
            // Nonce natif WP_List_Table : 'bulk-' . $plural
            check_admin_referer('bulk-' . $this->_args['plural']);

            global $wpdb;
            $t = remote_news_table_mappings();

            foreach ((array) $_POST['bulk_ids'] as $pair) {
                $pair = (string)$pair;
                if (strpos($pair, '::') === false) {
                    continue;
                }

                list($src, $rem) = explode('::', $pair, 2);
                $src = sanitize_key($src);
                $rem = sanitize_title($rem);

                if (!$src || !$rem) {
                    continue;
                }

                $wpdb->delete($t, [
                    'source_key'  => $src,
                    'remote_slug' => $rem,
                ]);
            }
        }
    }

    public function prepare_items() {
        $per_page = (int) get_user_meta(get_current_user_id(), 'remote_news_mappings_per_page', true);
        if ($per_page <= 0) {
            $per_page = 20;
        }

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $paged  = isset($_REQUEST['paged']) ? max(1, (int)$_REQUEST['paged']) : 1;

        $this->process_bulk_action();

        $filter_source = isset($_REQUEST['filter_source']) ? sanitize_key($_REQUEST['filter_source']) : '';

        list($items, $total) = remote_news_mappings_paginated($paged, $per_page, $search, $filter_source);

        $this->items = $items;

        $this->_column_headers = [
            $this->get_columns(),
            [], // hidden
            $this->get_sortable_columns(),
        ];

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page'    => $per_page,
            'total_pages' => max(1, ceil($total / $per_page)),
        ]);
    }
}

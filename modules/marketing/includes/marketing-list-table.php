<?php
// File: modules/marketing/marketing-list-table.php

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Admin_LAB_Marketing_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'campaign',
            'plural'   => 'campaigns',
            'ajax'     => false
        ]);
    }

    public function get_items_per_page_option() {
        $user_id = get_current_user_id();
        $option = get_user_meta($user_id, 'admin_lab_marketing_campaigns_per_page', true);
        return ($option && is_numeric($option)) ? (int) $option : 10;
    }

    public function get_columns() {
        return [
            'cb'             => '<input type="checkbox" name="campaign[]" />',
            'partner_name'   => __('Partner Name', 'me5rine-lab'),
            'campaign_slug'  => __('Campaign Slug', 'me5rine-lab'),
            'campaign_url'   => __('Campaign URL', 'me5rine-lab'),
            'zones'          => __('Zones', 'me5rine-lab'),
            'actions'        => __('Actions', 'me5rine-lab')
        ];
    }

    public function get_sortable_columns() {
        return [
            'partner_name'  => ['partner_name', false],
            'campaign_slug' => ['campaign_slug', false],
            'created_at'    => ['created_at', false]
        ];
    }

    public function get_bulk_actions() {
        $view = $_GET['view'] ?? 'active';

        if ($view === 'trash') {
            return [
                'restore' => __('Restore', 'me5rine-lab'),
                'delete_permanently' => __('Delete permanently', 'me5rine-lab'),
            ];
        }

        return [
            'bulk_trash' => __('Move to Trash', 'me5rine-lab'),
        ];
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="campaign[]" value="%s" />', esc_attr($item->id));
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'partner_name':
            case 'campaign_slug':
                return esc_html($item->$column_name);
            case 'campaign_url':
                return sprintf(
                    '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
                    esc_url($item->$column_name),
                    esc_html($item->$column_name)
                );
            case 'zones':
                global $admin_lab_marketing_zones;
                $zones = is_array($admin_lab_marketing_zones) ? $admin_lab_marketing_zones : [];
                $active_zones = [];

                foreach ($zones as $zone_key => $zone_label) {
                    $campaign_id = get_option("admin_lab_marketing_zone_$zone_key");
                    if ((int)$campaign_id === (int)$item->id) {
                        $active_zones[] = $zone_label;
                    }
                }

                return !empty($active_zones) ? implode(', ', $active_zones) : '—';
            default:
                return print_r($item, true);
        }
    }

    public function column_actions($item) {
        if (!empty($item->is_trashed)) {
            // Actions pour élément en corbeille : restaurer ou supprimer définitivement
            $restore_url = wp_nonce_url(
                admin_url('admin-post.php?action=admin_lab_restore_link&restore_id=' . $item->id),
                'admin_lab_restore_link_nonce'
            );
            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=admin_lab_delete_link_permanent&delete_id=' . $item->id),
                'admin_lab_delete_link_permanent_nonce'
            );

            return sprintf(
                '<a href="%1$s" class="button">%2$s</a>
                <a href="%3$s" class="button button-danger admin-lab-button-delete">%5$s</a>',
                esc_url($restore_url),
                __('Restore', 'me5rine-lab'),
                esc_url($delete_url),
                esc_attr(__('Are you sure you want to permanently delete this item?', 'me5rine-lab')),
                __('Delete Permanently', 'me5rine-lab')
            );
        }

        // Actions normales
        $edit_url = admin_url('admin.php?page=admin-lab-marketing-edit&edit=' . $item->id);
        $trash_url = wp_nonce_url(
            admin_url('admin-post.php?action=admin_lab_trash_link&trash_marketing_link=' . $item->id),
            'admin_lab_trash_link_nonce'
        );
        $duplicate_url = admin_url('admin-post.php?action=admin_lab_duplicate_link&duplicate_id=' . $item->id);

        return sprintf(
            '<div class="action-buttons">
            <a href="%1$s" class="button button-primary">%2$s</a> 
            <a href="%3$s" class="button">%4$s</a>
            <a href="%5$s" class="button button-secondary admin-lab-button-delete">%7$s</a>
            </div>',
            esc_url($edit_url),
            __('Edit', 'me5rine-lab'),
            esc_url($duplicate_url),
            __('Duplicate', 'me5rine-lab'),
            esc_url($trash_url),
            esc_attr(__('Are you sure you want to move this item to the trash?', 'me5rine-lab')),
            __('Move to Trash', 'me5rine-lab')
        );
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = admin_lab_getTable('marketing_links');

        $this->process_bulk_action();

        // Pagination : récupère la valeur choisie dans "Options de l'écran"
        $per_page     = $this->get_items_per_page( 'admin_lab_marketing_campaigns_per_page', 20 );
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        $search = isset($_GET['s']) ? trim($_GET['s']) : '';
        $view = $_GET['view'] ?? 'active';
        $is_trashed = ($view === 'trash') ? 1 : 0;

        // Requête principale
        $query = "SELECT * FROM $table_name WHERE is_trashed = %d";
        $params = [$is_trashed];

        if (!empty($search)) {
            $query .= " AND (partner_name LIKE %s OR campaign_slug LIKE %s)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $query .= " ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $this->items = $wpdb->get_results($wpdb->prepare($query, ...$params));

        // Total pour pagination
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE is_trashed = %d";
        $count_params = [$is_trashed];

        if (!empty($search)) {
            $count_query .= " AND (partner_name LIKE %s OR campaign_slug LIKE %s)";
            $count_params[] = '%' . $search . '%';
            $count_params[] = '%' . $search . '%';
        }

        $total_items = (int) $wpdb->get_var($wpdb->prepare($count_query, ...$count_params));

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }

    public function process_bulk_action() {
        global $wpdb;
        $table_name = admin_lab_getTable('marketing_links');

        $action = $this->current_action();

        if (isset($_POST['empty_trash'])) {
            check_admin_referer('bulk-campaigns');
            $wpdb->query("DELETE FROM $table_name WHERE is_trashed = 1");

            wp_redirect(add_query_arg([
                'page' => 'admin-lab-marketing',
                'view' => 'trash',
                'message' => 'trash_emptied'
            ], admin_url('admin.php')));
            exit;
        }

        if (!empty($_POST['campaign']) && is_array($_POST['campaign'])) {
            check_admin_referer('bulk-campaigns');
            $ids = array_map('absint', $_POST['campaign']);

            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));

                switch ($action) {
                    case 'bulk_trash':
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $table_name SET is_trashed = 1 WHERE id IN ($placeholders)", ...$ids
                        ));
                        $message = 'trashed_bulk';
                        break;

                    case 'restore':
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $table_name SET is_trashed = 0 WHERE id IN ($placeholders)", ...$ids
                        ));
                        $message = 'restored_bulk';
                        break;

                    case 'delete_permanently':
                        $wpdb->query($wpdb->prepare(
                            "DELETE FROM $table_name WHERE id IN ($placeholders)", ...$ids
                        ));
                        $message = 'deleted_bulk';
                        break;

                    default:
                        return;
                }

                wp_redirect(add_query_arg([
                    'page' => 'admin-lab-marketing',
                    'view' => $_GET['view'] ?? 'active',
                    'message' => $message
                ], admin_url('admin.php')));
                exit;
            }
        }
    }

    public function single_row($item) {
        echo '<tr>';
        $this->single_row_columns($item);
        echo '</tr>';
    }
}

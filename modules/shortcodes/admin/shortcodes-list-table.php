<?php
// File: modules/shortcodes/admin/shortcodes-list-table.php

if (!defined('ABSPATH')) exit;

class Admin_LAB_Shortcodes_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'shortcode',
            'plural'   => 'shortcodes',
            'ajax'     => false,
        ]);
    }

    public function get_items_per_page_option() {
        $user_id = get_current_user_id();
        $option  = get_user_meta($user_id, 'admin_lab_shortcodes_shortcodes_per_page', true);
        return ($option && is_numeric($option)) ? (int) $option : 10;
    }

    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" name="shortcode[]" />',
            'name'        => __('Shortcode', 'me5rine-lab'),
            'description' => __('Description', 'me5rine-lab'),
            'content'     => __('Content', 'me5rine-lab'),
            'actions'     => __('Actions', 'me5rine-lab'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'name'        => ['name', false],
            'description' => ['description', false],
        ];
    }

    public function get_bulk_actions() {
        return [
            'delete' => __('Delete', 'me5rine-lab'),
        ];
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="shortcode[]" value="%d" />', esc_attr($item->id));
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
                $shortcode = '[custom_shortcode name="' . $item->name . '"]';
                return sprintf(
                    '<span class="admin-lab-shortcode-name" data-shortcode="%s" title="%s">
                        <span class="dashicons dashicons-clipboard"></span> %s
                    </span>',
                    esc_attr($shortcode),
                    esc_attr(__('Click to copy shortcode', 'me5rine-lab')),
                    esc_html($item->name)
                );
            case 'description':
                return esc_html(stripslashes($item->description));
            case 'content':
                return '<code>' . esc_html($item->content) . '</code>';
            default:
                return '';
        }
    }

    public function column_actions($item) {
        $edit_url = admin_url('admin.php?page=admin-lab-shortcodes-edit&edit=' . $item->id);
        $delete_url = wp_nonce_url(
            admin_url('admin-post.php?action=admin_lab_delete_shortcode&delete_shortcode=' . $item->id),
            'admin_lab_delete_shortcode_nonce'
        );

        return sprintf(
            '<a href="%s" class="button button-primary">%s</a> <a href="%s" class="button button-danger admin-lab-button-delete" onclick="return confirm(\'%s\');">%s</a>',
            esc_url($edit_url),
            __('Edit', 'me5rine-lab'),
            esc_url($delete_url),
            esc_attr(__('Are you sure you want to delete this item?', 'me5rine-lab')),
            __('Delete', 'me5rine-lab')
        );
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = admin_lab_getTable('shortcodes');

        $this->process_bulk_action();

        // Pagination : récupère la valeur choisie dans "Options de l'écran"
        $per_page     = $this->get_items_per_page( 'admin_lab_shortcodes_shortcodes_per_page', 20 );
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;
        $search       = isset($_GET['s']) ? trim($_GET['s']) : '';

        $query = "SELECT * FROM $table_name WHERE 1=1";

        if (!empty($search)) {
            $query .= $wpdb->prepare(" AND (name LIKE %s OR description LIKE %s)", "%$search%", "%$search%");
        }

        $query .= $wpdb->prepare(" ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset);
        $this->items = $wpdb->get_results($query);

        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }

    public function process_bulk_action() {
        global $wpdb;
        $table_name = admin_lab_getTable('shortcodes');

        if ($this->current_action() === 'delete' && !empty($_POST['shortcode'])) {
            check_admin_referer('bulk-shortcodes');

            $ids = array_map('absint', $_POST['shortcode']);
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", ...$ids));

                wp_redirect(admin_url('admin.php?page=admin-lab-shortcodes&message=deleted'));
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

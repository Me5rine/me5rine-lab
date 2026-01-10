<?php
// File: modules/user-management/admin/classes/class-account-types-list-table.php

if (!defined('ABSPATH')) exit;

class Admin_LAB_Account_Types_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'account_type',
            'plural'   => 'account_types',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'label'    => __('Name', 'me5rine-lab'),
            'slug'     => __('Slug', 'me5rine-lab'),
            'role'     => __('Associated Role', 'me5rine-lab'),
            'scope'    => __('Default Scope', 'me5rine-lab'),
            'modules'  => __('Active Modules', 'me5rine-lab'),
            'actions'  => __('Actions', 'me5rine-lab'),
        ];
    }

    public function prepare_items() {
        $account_types = admin_lab_get_registered_account_types();
        $this->items = [];

        foreach ($account_types as $slug => $data) {
            $this->items[] = array_merge($data, ['slug' => $slug]);
        }

        $this->_column_headers = [$this->get_columns(), [], []];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'label':
                return esc_html($item['label'] ?? '');
            case 'slug':
                return '<code>' . esc_html($item['slug']) . '</code>';
            case 'role':
                global $wp_roles;
                return esc_html($wp_roles->get_names()[$item['role']] ?? $item['role']);
            case 'scope':
                return is_array($item['scope']) ? esc_html(implode(', ', $item['scope'])) : esc_html($item['scope']);
            case 'modules':
                $modules = is_array($item['modules']) ? $item['modules'] : maybe_unserialize($item['modules']);
                return !empty($modules) ? esc_html(implode(', ', $modules)) : '<em>' . esc_html__('None', 'me5rine-lab') . '</em>';
            case 'actions':
                $slug = urlencode($item['slug']);
                $edit_url = admin_url("admin.php?page=admin-lab-user-management&tab=types&edit={$slug}");
                $delete_url = wp_nonce_url(admin_url("admin-post.php?action=admin_lab_unregister_account_type&slug={$slug}"), "admin_lab_unregister_account_type_{$slug}");

                return sprintf(
                    '<a href="%s" class="button button-primary">%s</a> <a href="%s" class="button button-secondary admin-lab-button-delete">%s</a>',
                    esc_url($edit_url),
                    __('Edit', 'me5rine-lab'),
                    esc_url($delete_url),
                    __('Delete', 'me5rine-lab')
                );
            default:
                return '';
        }
    }
}

<?php
// File: includes\/settings/classes\/unlinked-campaigns-list-table.php

if (!defined('ABSPATH')) exit;

class Admin_LAB_Unlinked_RafflePress_Campaigns_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'rafflepress_campaign',
            'plural'   => 'rafflepress_campaigns',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'name'    => __('Campaign name', 'me5rine-lab'),
            'start'   => __('Start date', 'me5rine-lab'),
            'end'     => __('End date', 'me5rine-lab'),
            'rewards' => __('Rewards', 'me5rine-lab'),
            'actions' => __('Actions', 'me5rine-lab'),
        ];
    }

    public function prepare_items() {
        global $wpdb;

        $rafflepress_table = $wpdb->prefix . 'rafflepress_giveaways';
        $used_ids = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_rafflepress_campaign'");
        $query = empty($used_ids)
            ? "SELECT ID, name, settings FROM {$rafflepress_table}"
            : "SELECT ID, name, settings FROM {$rafflepress_table} WHERE ID NOT IN (" . implode(',', array_map('intval', $used_ids)) . ")";
        
        $results = $wpdb->get_results($query);
        $this->items = [];

        foreach ($results as $campaign) {
            $settings = json_decode($campaign->settings, true);

            $start = !empty($settings['starts']) ? date('d-m-Y H:i', strtotime($settings['starts'] . ' ' . $settings['starts_time'])) : 'N/A';
            $end = !empty($settings['ends']) ? date('d-m-Y H:i', strtotime($settings['ends'] . ' ' . $settings['ends_time'])) : 'N/A';
            $rewards = !empty($settings['prizes']) ? implode(', ', array_map(fn($r) => $r['name'], $settings['prizes'])) : __('No prize', 'me5rine-lab');

            $this->items[] = [
                'ID'      => $campaign->ID,
                'name'    => $campaign->name,
                'start'   => $start,
                'end'     => $end,
                'rewards' => $rewards,
            ];
        }

        $this->_column_headers = [$this->get_columns(), [], []];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
            case 'start':
            case 'end':
            case 'rewards':
                return esc_html($item[$column_name]);
            case 'actions':
                $id = (int) $item['ID'];
                $create_url = admin_url("post-new.php?post_type=giveaway&create_giveaway_from={$id}");
                $view_url = admin_url("admin.php?page=rafflepress_pro_builder&id={$id}#/setup/{$id}");
                return sprintf(
                    '<a class="button button-primary" href="%s">%s</a> <a class="button" target="_blank" href="%s">%s</a>',
                    esc_url($create_url),
                    __('Create post', 'me5rine-lab'),
                    esc_url($view_url),
                    __('View campaign', 'me5rine-lab')
                );
            default:
                return '';
        }
    }
}

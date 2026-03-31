<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

use ContentEgg\application\models\AutoblogModel;;

/**
 * PrefillLogTable class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class PrefillLogTable extends MyListTable
{
    const per_page = 50;

    function default_orderby()
    {
        return 'updated_at';
    }

    function default_order()
    {
        return 'desc';
    }

    function get_columns()
    {
        $columns = array_merge(
            array(
                'post_id' => AutoblogModel::model()->getAttributeLabel('post_id'),
                'status' => AutoblogModel::model()->getAttributeLabel('status'),
                'log' => AutoblogModel::model()->getAttributeLabel('log'),
                'updated_at' => AutoblogModel::model()->getAttributeLabel('updated_at'),
            )
        );
        return $columns;
    }

    function get_bulk_actions()
    {
        return array();
    }

    function column_post_id($item)
    {
        $post_id = (int) $item['post_id'];
        $post = get_post($post_id);

        if (!$post)
        {
            return sprintf('<span class="text-muted">#%d</span>', $post_id);
        }

        $title = get_the_title($post_id);
        $edit_link = get_edit_post_link($post_id);
        $view_link = get_permalink($post_id);

        return sprintf(
            '<a href="%s" target="_blank">%s</a><br><small><a href="%s" target="_blank" class="text-muted">#%d</a></small>',
            esc_url($edit_link),
            esc_html($title ?: __('(no title)', 'content-egg')),
            esc_url($view_link),
            $post_id
        );
    }

    function column_status($item)
    {
        $status = strtolower($item['status']);
        $label = ucfirst($status);

        switch ($status)
        {
            case 'done':
                $class = 'bg-success';
                break;
            case 'failed':
                $class = 'bg-danger';
                break;
            case 'pending':
                $class = 'bg-secondary';
                break;
            default:
                $class = 'bg-secondary';
                break;
        }

        return sprintf(
            '<span class="badge %s">%s</span>',
            esc_attr('badge ' . $class),
            esc_html($label)
        );
    }

    function column_log(array $item): string
    {
        if (empty($item['log']) && empty($item['processing_time']) && empty($item['ai_cost']))
        {
            return '-';
        }

        $allowed_tags = [
            'br'     => [],
            'em'     => [],
            'strong' => [],
            'b'      => [],
            'code'   => [],
        ];

        $output = '';

        if (! empty($item['log']))
        {
            $clean_log = wp_kses($item['log'], $allowed_tags);
            $output   .= sprintf(
                '<div class="cegg-log">%s</div>',
                $clean_log
            );
        }

        $meta_pieces = [];

        if (! empty($item['processing_time']))
        {
            $time = round((float) $item['processing_time']);
            $display_time = $time > 0
                ? number_format_i18n($time) . 's'
                : '&lt; 1s';

            $meta_pieces[] = sprintf(
                /* translators: %s is the processing time, e.g. "2s" */
                esc_html__('Time: %s', 'content-egg'),
                wp_kses_post($display_time)
            );
        }

        if (! empty($item['ai_cost']))
        {
            $cost = (float) $item['ai_cost'];
            $display_cost = $cost > 0.0001
                ? '$' . number_format_i18n($cost, 4)
                : '$&lt; 0.0001';

            $meta_pieces[] = sprintf(
                /* translators: %s is the AI cost, e.g. "$0.0123" */
                esc_html__('AI cost: %s', 'content-egg'),
                wp_kses_post($display_cost)
            );
        }

        if ($meta_pieces)
        {
            $output .= sprintf(
                '<div class="cegg-log-meta small text-muted mt-1">%s</div>',
                implode(' | ', $meta_pieces)
            );
        }

        return $output;
    }

    function column_updated_at($item)
    {
        return $this->view_column_datetime($item, 'updated_at');
    }

    function column_processing_time($item)
    {
        if (empty($item['processing_time']))
        {
            return '<span class="text-muted">—</span>';
        }

        $time = (float) $item['processing_time'];

        if ($time < 0.001)
        {
            // Edge case: very tiny values
            return '<span class="text-muted">&lt;1ms</span>';
        }

        $formatted_time = sprintf('%.3f', $time);

        return sprintf(
            '<span class="badge bg-secondary">%s s</span>',
            esc_html($formatted_time)
        );
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'post_id' => array('post_id', true),
            'status' => array('status', true),
            'updated_at' => array('updated_at', true),
        );

        return $sortable_columns;
    }
}

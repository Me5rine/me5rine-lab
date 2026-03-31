<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\MyListTable;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\TextHelper;



defined('\ABSPATH') || exit;

/**
 * ImportQueueTable – WP-Admin list-table for the Product / Post import queue
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ImportQueueTable extends MyListTable
{
    public const per_page = 50;

    /* -------------------------------------------------------------------------
       Sorting defaults
    ------------------------------------------------------------------------- */
    public function default_orderby()
    {
        return 'updated_at';
    }

    public function default_order()
    {
        return 'desc';
    }

    /* -------------------------------------------------------------------------
       Columns
    ------------------------------------------------------------------------- */
    public function get_columns(): array
    {
        return [
            'post_id'          => __('Post',              'content-egg'),
            'product'          => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-image" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/></svg>',
            'module_id'        => __('Module',            'content-egg'),
            'preset_id'        => __('Preset',            'content-egg'),
            'status'           => __('Status',            'content-egg'),
            'log'              => __('Log',               'content-egg'),
            'updated_at'       => __('Updated',           'content-egg'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'preset_id'  => ['preset_id', true],
            'module_id'  => ['module_id', true],
            'post_id'    => ['post_id',   true],
            'status'     => ['status',    true],
            'updated_at' => ['updated_at', true],
        ];
    }

    public function get_bulk_actions(): array
    {
        return [];
    }

    /* -------------------------------------------------------------------------
       Column renderers
    ------------------------------------------------------------------------- */
    public function column_preset_id(array $item): string
    {
        $preset_id = (int) $item['preset_id'];
        $preset    = get_post($preset_id);

        if (!$preset)
        {
            return sprintf('<span class="text-muted">#%d</span>', $preset_id);
        }

        $link = add_query_arg(
            [
                'page'      => 'content-egg-product-import',
                'tab'       => 'presets',
                'action'      => 'edit',
                'preset_id' => $preset_id,
            ],
            admin_url('admin.php')
        );

        return sprintf(
            '<a href="%s">%s</a>',
            esc_url($link),
            esc_html(get_the_title($preset_id) ?: __('(no title)', 'content-egg')),
        );
    }

    public function column_module_id(array $item): string
    {
        $name = ModuleManager::getInstance()->getModuleNameById($item['module_id']);
        return esc_html($name);
    }

    public function column_post_id(array $item): string
    {
        $post_id        = isset($item['post_id']) ? (int) $item['post_id'] : 0;
        $source_post_id = isset($item['source_post_id']) ? (int) $item['source_post_id'] : 0;

        if (!$post_id)
        {
            return '—';
        }

        $badge = '';
        if ($source_post_id > 0)
        {
            $badge_title = sprintf(__('Bridge import from post #%d', 'content-egg'), $source_post_id);
            $badge = sprintf(
                ' <span class="badge bg-light text-muted border ms-1 align-middle" title="%s">%s</span>',
                esc_attr($badge_title),
                esc_html__('Bridge', 'content-egg')
            );
        }

        $post = get_post($post_id);
        if (!$post)
        {
            return sprintf('<span class="text-muted">#%d</span>%s', $post_id, $badge);
        }

        $edit  = get_edit_post_link($post_id);
        $perma = get_permalink($post_id);

        return sprintf(
            '<a href="%s">%s</a>%s<br><small><a href="%s" target="_blank" class="text-muted">#%d</a></small>',
            esc_url($edit),
            esc_html(get_the_title($post_id) ?: __('(no title)', 'content-egg')),
            $badge,
            esc_url($perma),
            $post_id
        );
    }

    public function column_product(array $item): string
    {
        if (empty($item['payload']))
        {
            return '<span class="text-muted">—</span>';
        }

        $product = json_decode($item['payload'], true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($product))
        {
            return '<span class="text-muted">—</span>';
        }

        if (empty($product['img']))
        {
            return '<span class="text-muted">—</span>';
        }

        $img_url = esc_url($product['img']);
        $title   = ! empty($product['title'])
            ? esc_attr($product['title'])
            : esc_attr__('Product Image', 'content-egg');

        $html = sprintf(
            '<img src="%1$s" alt="%2$s" title="%2$s" class="cegg-queue-product-img card-img-top object-fit-scale rounded" />',
            $img_url,
            $title
        );

        return $html;
    }

    public function column_keyword(array $item): string
    {
        return esc_html($item['keyword'] ?: '—');
    }

    public function column_status(array $item): string
    {
        $status = strtolower($item['status']);
        $map    = [
            'done'    => 'success',
            'failed'  => 'danger',
            'working' => 'warning',
            'pending' => 'secondary',
        ];
        $class = isset($map[$status]) ? $map[$status] : 'secondary';

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            esc_attr($class),
            esc_html(ucfirst($status))
        );
    }

    public function column_log(array $item): string
    {
        $lines = [];

        // Keyword or URL line
        if (!empty($item['keyword']))
        {
            $keyword = trim($item['keyword']);
            $trunc   = TextHelper::truncate($keyword, 100, '…');
            $label   = TextHelper::isUrl($keyword)
                ? esc_html__('Product URL:', 'content-egg')
                : esc_html__('Keyword:', 'content-egg');

            $lines[] = sprintf(
                '%s %s',
                $label,
                esc_html($trunc)
            );
        }
        // Product title
        elseif (!empty($item['payload']))
        {
            $payload = json_decode($item['payload'], true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($payload['title']))
            {
                $title  = TextHelper::truncate($payload['title'], 100, '…');
                $lines[] = sprintf(
                    '%s %s',
                    esc_html__('Product:', 'content-egg'),
                    esc_html($title)
                );
            }
        }

        // Log messages
        if (!empty($item['log']))
        {
            $allowed = [
                'br'     => [],
                'em'     => [],
                'strong' => [],
                'code'   => [],
            ];
            $safe_log = wp_kses($item['log'], $allowed);
            $parts = explode("\n", $safe_log);
            foreach ($parts as $part)
            {
                $part = trim($part);
                if ($part !== '')
                {
                    $lines[] = $part;
                }
            }
        }

        // 4) Processing time line
        if (isset($item['processing_time']) && $item['processing_time'] !== '')
        {
            $time  = number_format_i18n((float) $item['processing_time'], 3);
            $label = sprintf(
                esc_html__('Time: %s s', 'content-egg'),
                esc_html($time)
            );
            $lines[] = sprintf(
                '<span class="small text-muted">%s</span>',
                $label
            );
        }

        // 5) Nothing to display?
        if (empty($lines))
        {
            return '&mdash;';
        }

        // 6) Wrap each line for CSS spacing
        $html = '<div class="cegg-log">';
        foreach ($lines as $line)
        {
            $html .= sprintf(
                '<div class="cegg-log-line">%s</div>',
                $line
            );
        }
        $html .= '</div>';

        return $html;
    }

    public function column_processing_time(array $item): string
    {
        return empty($item['processing_time'])
            ? '<span class="text-muted">—</span>'
            : esc_html(number_format_i18n($item['processing_time'], 3));
    }

    public function column_prompt_tokens(array $item): string
    {
        return $item['prompt_tokens'] ? number_format_i18n((int) $item['prompt_tokens']) : '—';
    }

    public function column_completion_tokens(array $item): string
    {
        return $item['completion_tokens'] ? number_format_i18n((int) $item['completion_tokens']) : '—';
    }

    public function column_updated_at(array $item): string
    {
        return $this->view_column_datetime($item, 'updated_at');
    }
}

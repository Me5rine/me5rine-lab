<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\MyListTable;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\models\AutoImportRuleModel;



defined('\ABSPATH') || exit;

/**
 * AutoImportRulesTable – WP-Admin list-table for Auto-Import rules.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class AutoImportRulesTable extends MyListTable
{

    /** Items per page */
    public const per_page = 20;

    /* ------------------------------------------------------------------
	   Sorting defaults
	------------------------------------------------------------------ */
    public function default_orderby()
    {
        return 'updated_at';
    }

    public function default_order()
    {
        return 'desc';
    }

    /* ------------------------------------------------------------------
	   Columns
	------------------------------------------------------------------ */
    public function get_columns(): array
    {
        return [
            'name'         => __('Rule',      'content-egg'),
            'module_id'    => __('Module',    'content-egg'),
            'preset_id'    => __('Preset',    'content-egg'),
            'status'       => __('Status',    'content-egg'),
            //'keywords'     => __('Keywords',  'content-egg'),
            //'frequency'    => __('Every',     'content-egg'),
            'post_count'   => __('Imported',  'content-egg'),
            'next_run_at'  => __('Next Run',  'content-egg'),
            //'last_run_at'  => __('Last Run',  'content-egg'),
            //'consecutive_errors' => __('Conv. Errors',    'content-egg'),
            'log_history' => __('Recent Activity',      'content-egg'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'name'          => ['name',          true],
            'module_id'     => ['module_id',     true],
            'status'        => ['status',        true],
            'post_count'    => ['post_count',    true],
            'next_run_at'   => ['next_run_at',   true],
            'last_run_at'   => ['last_run_at',   true],
            'consecutive_errors' => ['consecutive_errors', true],
        ];
    }

    /* ------------------------------------------------------------------
	   Bulk-actions (none for now)
	------------------------------------------------------------------ */
    public function get_bulk_actions(): array
    {
        return [];
    }

    /* ------------------------------------------------------------------
	   Column renderers
	------------------------------------------------------------------ */

    public function column_name(array $item): string
    {
        $base_args = [
            'page' => 'content-egg-product-import',
            'tab'  => 'autoimport',
        ];

        // Edit link
        $edit_url = add_query_arg(
            $base_args + [
                'action'  => 'edit',
                'rule_id' => $item['id'],
            ],
            admin_url('admin.php')
        );

        // Delete link
        $delete_url = wp_nonce_url(
            add_query_arg(
                $base_args + [
                    'ai_action' => 'delete',
                    'rule_id'   => $item['id'],
                    'noheader' => 'true',
                ],
                admin_url('admin.php')
            ),
            'cegg_autoimport'
        );

        // Build actions array
        $actions = [
            'edit'   => sprintf(
                '<a href="%s">%s</a>',
                esc_url($edit_url),
                __('Edit', 'content-egg')
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($delete_url),
                esc_js(__('Delete this rule?', 'content-egg')),
                __('Delete', 'content-egg')
            ),
        ];

        // Run Now link for active rules
        if ($item['status'] === AutoImportRuleModel::STATUS_ACTIVE)
        {
            $run_now_url = wp_nonce_url(
                add_query_arg(
                    $base_args + [
                        'ai_action' => 'run',
                        'rule_id'   => $item['id'],
                        'noheader' => 'true',
                    ],
                    admin_url('admin.php')
                ),
                'cegg_autoimport'
            );
            $actions['run_now'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($run_now_url),
                __('Run Now', 'content-egg')
            );
        }

        // Only show Pause/Resume when rule is active or paused
        if (in_array($item['status'], [
            AutoImportRuleModel::STATUS_ACTIVE,
            AutoImportRuleModel::STATUS_PAUSED,
        ], true))
        {
            $is_active    = $item['status'] === AutoImportRuleModel::STATUS_ACTIVE;
            $toggle_label = $is_active
                ? __('Pause', 'content-egg')
                : __('Resume', 'content-egg');
            $toggle_url = wp_nonce_url(
                add_query_arg(
                    $base_args + [
                        'ai_action' => 'toggle',
                        'rule_id'   => $item['id'],
                        'noheader' => 'true',
                    ],
                    admin_url('admin.php')
                ),
                'cegg_autoimport'
            );
            $actions['toggle'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($toggle_url),
                esc_html($toggle_label)
            );
        }

        return sprintf(
            '<a href="%s"><strong>%s</strong></a>%s',
            esc_url($edit_url),
            esc_html($item['name'] ?: __('(no name)', 'content-egg')),
            $this->row_actions($actions)
        );
    }

    /** Module name */
    public function column_module_id(array $item): string
    {
        return esc_html(
            ModuleManager::getInstance()->getModuleNameById($item['module_id'])
        );
    }

    /** Preset title */
    public function column_preset_id(array $item): string
    {
        $preset_id = (int) $item['preset_id'];
        $title     = get_the_title($preset_id) ?: __('(no title)', 'content-egg');

        return sprintf(
            '<a href="%s">%s</a>',
            esc_url(add_query_arg(
                [
                    'page'      => 'content-egg-product-import',
                    'tab'       => 'presets',
                    'action'    => 'edit',
                    'preset_id' => $preset_id,
                ],
                admin_url('admin.php')
            )),
            esc_html($title)
        );
    }

    /** Status badge */
    public function column_status(array $item): string
    {
        $map = [
            AutoImportRuleModel::STATUS_ACTIVE   => 'success',
            AutoImportRuleModel::STATUS_PAUSED   => 'secondary',
            AutoImportRuleModel::STATUS_FINISHED => 'info',
            AutoImportRuleModel::STATUS_DISABLED => 'danger',
        ];
        $class = $map[$item['status']] ?? 'secondary';

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            esc_attr($class),
            esc_html(ucfirst($item['status']))
        );
    }

    /** Keywords (show up to 3, then +N) */
    public function column_keywords(array $item): string
    {
        $decoded = json_decode($item['keywords_json'], true);
        if (! is_array($decoded) || ! $decoded)
        {
            return '—';
        }

        $words = array_column($decoded, 'keyword');
        $count = count($words);

        if ($count > 3)
        {
            $display = array_slice($words, 0, 3);
            $display[] = sprintf(__('+%d more', 'content-egg'), $count - 3);
        }
        else
        {
            $display = $words;
        }

        $display = implode(', ', $display);
        $display = TextHelper::truncate($display, 100, '…');

        return '<div class="cegg-log">' . esc_html($display) . '</div>';
    }

    /** Frequency (human-readable) */
    public function column_frequency(array $item): string
    {
        $sec = (int) $item['interval_seconds'];

        if ($sec < HOUR_IN_SECONDS)
        {
            $mins = max(1, (int) floor($sec / MINUTE_IN_SECONDS));
            return sprintf(
                _n('%d minute', '%d minutes', $mins, 'content-egg'),
                $mins
            );
        }

        if ($sec < DAY_IN_SECONDS)
        {
            $hrs = max(1, (int) floor($sec / HOUR_IN_SECONDS));
            return sprintf(
                _n('%d hour', '%d hours', $hrs, 'content-egg'),
                $hrs
            );
        }

        if ($sec < 30 * DAY_IN_SECONDS)
        {
            $days = max(1, (int) floor($sec / DAY_IN_SECONDS));
            return sprintf(
                _n('%d day', '%d days', $days, 'content-egg'),
                $days
            );
        }

        if ($sec < YEAR_IN_SECONDS)
        {
            $months = max(1, (int) floor($sec / (30 * DAY_IN_SECONDS)));
            return sprintf(
                _n('%d month', '%d months', $months, 'content-egg'),
                $months
            );
        }

        $years = max(1, (int) floor($sec / YEAR_IN_SECONDS));
        return sprintf(
            _n('%d year', '%d years', $years, 'content-egg'),
            $years
        );
    }

    public function column_post_count(array $item): string
    {
        return esc_html(number_format_i18n($item['post_count']));
    }

    public function column_consecutive_errors(array $item): string
    {
        return $item['consecutive_errors']
            ? '<span class="cegg-text-danger">' . esc_html($item['consecutive_errors']) . '</span>'
            : '0';
    }

    public function column_next_run_at(array $item): string
    {
        // Only show for active rules
        if (($item['status'] ?? '') !== AutoImportRuleModel::STATUS_ACTIVE)
        {
            return '<span class="text-muted">&mdash;</span>';
        }

        $raw = $item['next_run_at'] ?? '';
        if (empty($raw) || $raw === '0000-00-00 00:00:00')
        {
            return '<span class="text-muted">&mdash;</span>';
        }

        $ts = strtotime($raw);
        if (! $ts)
        {
            return '<span class="text-muted">&mdash;</span>';
        }

        $now       = current_time('timestamp');
        $fmt       = get_option('date_format') . ' ' . get_option('time_format');
        $tooltip   = esc_attr(date_i18n($fmt, $ts));
        $threshold = 10 * MINUTE_IN_SECONDS;

        // Overdue
        if ($ts <= $now)
        {
            $delta = $now - $ts;

            // If overdue by 10 minutes or less, just say “Now”
            if ($delta <= $threshold)
            {
                return sprintf(
                    '<span title="%s">%s</span>',
                    $tooltip,
                    esc_html__('Now', 'your-text-domain')
                );
            }

            // Otherwise show the red warning with icon and “x ago”
            $diff = human_time_diff($now, $ts);
            return sprintf(
                '<span class="text-danger" title="%s">'
                    . '<span class="dashicons dashicons-warning" aria-hidden="true"></span> %s ago'
                    . '</span>',
                $tooltip,
                esc_html($diff)
            );
        }

        // Upcoming
        $diff = human_time_diff($now, $ts);
        return sprintf(
            '<span title="%s">in %s</span>',
            $tooltip,
            esc_html($diff)
        );
    }

    public function column_last_run_at(array $item): string
    {
        return $this->view_column_datetime($item, 'last_run_at');
    }

    public function column_log_history(array $item): string
    {
        $logs = [];
        if (!empty($item['log_history']))
        {
            $decoded = json_decode($item['log_history'], true);
            if (is_array($decoded))
            {
                $logs = $decoded;
            }
        }

        if (empty($logs))
        {
            return '&mdash;';
        }

        $allowed_tags = [
            'br'     => [],
            'em'     => [],
            'strong' => [],
            'code'   => [],
        ];

        $html = '<div class="cegg-log">';
        foreach ($logs as $line)
        {
            $line = trim($line);
            if ($line === '')
            {
                continue;
            }
            $safe = wp_kses($line, $allowed_tags);
            $html .= sprintf(
                '<div class="cegg-log-line">%s</div>',
                $safe
            );
        }
        $html .= '</div>';

        return $html;
    }

    /* ------------------------------------------------------------------
	   Data source
	------------------------------------------------------------------ */
    protected function get_rows(array $query_args): array
    {
        $model = AutoImportRuleModel::model();

        // Build WHERE / ORDER BY from $query_args (orderby, order, per_page, offset)
        $orderby = sanitize_key($query_args['orderby'] ?? $this->default_orderby());
        $order   = strtoupper($query_args['order'] ?? $this->default_order()) === 'ASC' ? 'ASC' : 'DESC';
        $per     = (int) ($query_args['per_page'] ?? self::per_page);
        $offset  = (int) ($query_args['offset'] ?? 0);

        $rows = $model->getDb()->get_results(
            $model->getDb()->prepare(
                "SELECT * FROM {$model->tableName()}
				 ORDER BY {$orderby} {$order}
				 LIMIT %d OFFSET %d",
                $per,
                $offset
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /** For total items pagination */
    protected function get_total_items(): int
    {
        return (int) AutoImportRuleModel::model()->getDb()->get_var(
            "SELECT COUNT(*) FROM " . AutoImportRuleModel::model()->tableName()
        );
    }
}

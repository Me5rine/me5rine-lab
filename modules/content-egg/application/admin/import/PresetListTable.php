<?php

namespace ContentEgg\application\admin\import;

defined('ABSPATH') || exit;

/**
 * PresetListTable class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright © 2025 keywordrush.com
 */

if (!class_exists('\WP_List_Table'))
{
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PresetListTable extends \WP_List_Table
{
    /**
     * Cached preset meta keyed by post ID.
     *
     * @var array<int,array>
     */
    protected array $meta_cache = [];

    public function __construct()
    {
        parent::__construct([
            'plural'   => 'import_presets',
            'singular' => 'import_preset',
            'ajax'     => false,
        ]);
    }

    /* ------------------------------------------------------------------
       Columns & sortable
    ------------------------------------------------------------------ */
    public function get_columns(): array
    {
        return [
            'title'     => __('Preset Name',  'content-egg'),
            'post_type' => __('Post Type',    'content-egg'),
            'status'    => __('Post Status',  'content-egg'),
            'ai'        => __('AI',           'content-egg'),
            'isdefault' => __('Default?',     'content-egg'),
            'date'      => __('Created date', 'content-egg'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'title' => ['title', false],
            'date'  => ['date',  true],
        ];
    }

    /* ------------------------------------------------------------------
       Data prep
    ------------------------------------------------------------------ */
    public function prepare_items(): void
    {
        // 2) Pagination vars
        $per_page     = $this->get_items_per_page('import_presets_per_page', 20);
        $current_page = $this->get_pagenum();

        // 3) Sortable columns whitelist
        $sortable_cols = array_keys($this->get_sortable_columns());
        $orderby       = isset($_GET['orderby']) && in_array($_GET['orderby'], $sortable_cols, true)
            ? sanitize_key($_GET['orderby'])
            : 'date';
        $order         = (isset($_GET['order']) && 'asc' === strtolower($_GET['order']))
            ? 'ASC'
            : 'DESC';

        // 4) Search query
        $search = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';

        // 5) Query arguments
        $query_args = [
            'post_type'      => PresetPostType::POST_TYPE,
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'orderby'        => $orderby,
            'order'          => $order,
            's'              => $search,
            'post_status'    => 'any',
        ];

        $query       = new \WP_Query($query_args);
        $this->items = $query->posts;

        // 5.1) Warm the meta cache to avoid repeated get_post_meta() calls per row
        foreach ($this->items as $post)
        {
            $this->meta_cache[$post->ID] = get_post_meta($post->ID, PresetRepository::META_KEY, true) ?: [];
        }

        // 6) Tell WP_List_Table about our columns
        $this->_column_headers = [
            $this->get_columns(),
            /* hidden */
            [],
            /* sortable */
            $this->get_sortable_columns(),
        ];

        // 7) Pagination args
        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages,
        ]);
    }

    /* ------------------------------------------------------------------
       Helpers
    ------------------------------------------------------------------ */
    /**
     * Get cached meta for a preset.
     *
     * @param int $post_id
     * @return array<string,mixed>
     */
    protected function get_preset_meta(int $post_id): array
    {
        return $this->meta_cache[$post_id] ?? [];
    }

    /* ------------------------------------------------------------------
       Column callbacks
    ------------------------------------------------------------------ */
    public function column_title($item): string
    {
        $base_url  = admin_url('admin.php');
        $base_args = [
            'page'     => 'content-egg-product-import',
            'tab'      => 'presets',
        ];

        // URLs
        $edit_url = add_query_arg(
            $base_args + [
                'action'    => 'edit',
                'preset_id' => $item->ID,
            ],
            $base_url
        );

        $dup_url = add_query_arg(
            $base_args + [
                'action'       => 'add',
                'duplicate_id' => $item->ID,
            ],
            $base_url
        );

        $del_url = wp_nonce_url(
            add_query_arg(
                $base_args + [
                    'action'    => 'trash',
                    'preset_id' => $item->ID,
                    'noheader' => 'true',
                ],
                $base_url
            ),
            'cegg_delete_preset_' . $item->ID
        );

        // Actions
        $confirm_text = esc_js(__('Are you sure you want to delete this preset?', 'content-egg'));

        $actions = [
            'edit'      => sprintf(
                '<a href="%s">%s</a>',
                esc_url($edit_url),
                esc_html__('Edit', 'content-egg')
            ),
            'duplicate' => sprintf(
                '<a href="%s">%s</a>',
                esc_url($dup_url),
                esc_html__('Duplicate', 'content-egg')
            ),
            'delete'    => sprintf(
                '<a href="%1$s" class="submitdelete" onclick="return confirm(\'%2$s\');">%3$s</a>',
                esc_url($del_url),
                $confirm_text,
                esc_html__('Delete', 'content-egg')
            ),
        ];

        // Row content
        return sprintf(
            '<strong><a href="%1$s">%2$s</a></strong> %3$s',
            esc_url($edit_url),
            esc_html(get_the_title($item)),
            $this->row_actions($actions)
        );
    }

    public function column_post_type($item): string
    {
        $meta = $this->get_preset_meta($item->ID);
        return esc_html($meta['post_type'] ?? '-');
    }

    public function column_status($item): string
    {
        $meta = $this->get_preset_meta($item->ID);
        return esc_html(ucfirst($meta['post_status'] ?? '-'));
    }

    public function column_date($item): string
    {
        return esc_html(get_the_time(get_option('date_format'), $item));
    }

    public function column_ai($item): string
    {
        $meta = $this->get_preset_meta($item->ID);

        $used = (!empty($meta['ai_product_content']) && is_array($meta['ai_product_content']))
            || !empty($meta['ai_title'])
            || !empty($meta['ai_content']);

        return $used
            ? '<span class="cegg-icon-yes" title="' . esc_attr__('AI features enabled', 'content-egg') . '">&#10003;</span>'
            : '<span class="cegg-icon-no" title="' . esc_attr__('No AI features', 'content-egg') . '">&#8212;</span>';
    }

    public function column_isdefault($item): string
    {
        $meta = $this->get_preset_meta($item->ID);
        return !empty($meta['use_default']) ? esc_html__('Yes', 'content-egg') : '';
    }

    public function column_default($item, $column_name)
    {
        return '';
    }
}

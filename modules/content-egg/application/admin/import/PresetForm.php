<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\AdminNotice;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\helpers\AdminHelper;
use ContentEgg\application\Plugin;;

defined('ABSPATH') || exit;

/**
 * PresetForm class
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link   https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class PresetForm
{
    /* ------------------------------------------------------------------
       Actions
    ------------------------------------------------------------------ */
    public static function register(): void
    {
        add_action('admin_post_cegg_save_preset', [self::class, 'handle_save']);
    }

    /* ------------------------------------------------------------------
       Shared defaults (used by both render() and handle_save())
    ------------------------------------------------------------------ */
    private static function get_defaults(): array
    {
        return [
            // Core options
            'post_type'           => 'post',
            'product_type'        => 'external',
            'default_woo_cat'     => get_option('default_product_cat', 1),
            'default_cat'         => get_option('default_category', 1),
            'dynamic_categories'  => 'none',
            'post_status'         => 'publish',
            'author_id'           => get_current_user_id(),
            'tags'                => '',
            'price_comparison'    => 'disabled',

            // Templates
            'title_tpl'           => '',
            'body_tpl'            => '',
            'woo_short_desc_tpl'  => '',

            // AI settings
            'ai_product_content'  => [],
            'ai_title'            => '',
            'ai_content'          => '',
            'ai_short_desc'       => '',
            'prompt1'             => '',
            'prompt2'             => '',
            'prompt3'             => '',

            // Custom fields – provide three empty slots by default
            'custom_fields'       => array_fill(0, 3, [
                'key'   => '',
                'value' => '',
            ]),

            // Flags
            'avoid_duplicates'      => true,
            'avoid_duplicates_gtin' => false,
            'use_default'           => false,
            'make_canonical'        => false,
        ];
    }

    /* ------------------------------------------------------------------
       Helper: Build & sanitise "clean" array from raw input
    ------------------------------------------------------------------ */
    public static function build_clean_array(array $data): array
    {
        // Merge incoming data with defaults first so we can rely on keys existing
        $data = wp_parse_args($data, self::get_defaults());

        return [
            'post_type'          => in_array($data['post_type'], ['post', 'product'], true)
                ? $data['post_type']
                : 'post',
            'product_type'       => in_array($data['product_type'], ['external', 'simple'], true)
                ? $data['product_type']
                : 'external',
            'default_woo_cat'    => absint($data['default_woo_cat']),
            'default_cat'        => absint($data['default_cat']),
            'dynamic_categories' => in_array($data['dynamic_categories'], ['none', 'create', 'create_nested'], true)
                ? $data['dynamic_categories']
                : 'none',
            'post_status'        => in_array($data['post_status'], ['publish', 'pending', 'draft'], true)
                ? $data['post_status']
                : 'publish',
            'author_id'          => absint($data['author_id']),
            'tags'               => sanitize_text_field($data['tags']),
            'price_comparison'   => in_array($data['price_comparison'], ['enabled', 'disabled'], true)
                ? $data['price_comparison']
                : 'disabled',
            'title_tpl'          => sanitize_text_field($data['title_tpl']),
            'body_tpl'           => wp_kses_post($data['body_tpl']),
            'woo_short_desc_tpl' => wp_kses_post($data['woo_short_desc_tpl']),
            'ai_product_content' => array_intersect(
                (array) $data['ai_product_content'],
                [
                    'generate_short_title',
                    'generate_short_description',
                    'generate_subtitle',
                    'generate_rating',
                    'generate_badge',
                    'generate_badge_icon',
                    'generate_attributes',
                    'generate_keyword',
                ]
            ),
            'ai_title'           => sanitize_key($data['ai_title']),
            'ai_content'         => sanitize_key($data['ai_content']),
            'ai_short_desc'      => sanitize_key($data['ai_short_desc']),
            'prompt1'            => sanitize_textarea_field($data['prompt1']),
            'prompt2'            => sanitize_textarea_field($data['prompt2']),
            'prompt3'            => sanitize_textarea_field($data['prompt3']),
            'custom_fields'      => array_map(static function ($field)
            {
                return [
                    'key'   => sanitize_text_field($field['key']   ?? ''),
                    'value' => sanitize_text_field($field['value'] ?? ''),
                ];
            }, array_values((array) $data['custom_fields'])),
            'avoid_duplicates'          => ! empty($data['avoid_duplicates']),
            'avoid_duplicates_gtin'     => ! empty($data['avoid_duplicates_gtin']),
            'use_default'               => ! empty($data['use_default']),
            'make_canonical'            => ! empty($data['make_canonical']),
        ];
    }

    /* ------------------------------------------------------------------
       Render form. $id = 0  => add-new mode
    ------------------------------------------------------------------ */
    public static function render(int $id = 0, array $prefill = []): void
    {
        $is_edit = $id > 0;
        $post    = $is_edit ? get_post($id) : null;

        // Existing data from DB (if in edit mode)
        $saved_data = $is_edit ? (array) get_post_meta($id, PresetRepository::META_KEY, true) : [];

        // Combine: DB ➜ prefill (e.g. duplicate) ➜ defaults
        $data = wp_parse_args($prefill, $saved_data);
        $data = wp_parse_args($data, self::get_defaults());

        PluginAdmin::render(
            'preset_form',
            [
                'is_edit' => $is_edit,
                'id'      => $id,
                'post'    => $post,
                'data'    => $data,
                'is_pro'  => Plugin::isPro(),
            ]
        );
    }

    /* ------------------------------------------------------------------
       Handles saving (and updating) import presets.
    ------------------------------------------------------------------ */
    public static function handle_save(): void
    {
        // 1) Nonce & capability
        if (
            empty($_POST['cegg_preset_nonce']) ||
            ! wp_verify_nonce($_POST['cegg_preset_nonce'], 'cegg_save_preset')
        )
        {
            wp_die(esc_html__('Invalid nonce.', 'content-egg'));
        }

        if (! current_user_can('manage_options'))
        {
            wp_die(esc_html__('You do not have permission to save presets.', 'content-egg'));
        }

        // 2) Gather raw input
        $id   = isset($_POST['preset_id']) ? (int) $_POST['preset_id'] : 0;
        $raw  = $_POST['cegg_preset'] ?? [];
        $name = sanitize_text_field($_POST['preset_name'] ?? '');

        // 3) Sanitize & validate via helper
        $clean = self::build_clean_array($raw);

        // Force non-admins to use themselves as author
        if (! current_user_can('manage_options'))
        {
            $clean['author_id'] = get_current_user_id();
        }

        // 4) Insert or update the CPT entry
        $postarr = [
            'post_title'  => $name,
            'post_type'   => PresetPostType::POST_TYPE,
            'post_status' => 'publish',
            'post_author' => $clean['author_id'],
        ];

        if ($id)
        {
            $postarr['ID'] = $id;
            wp_update_post($postarr);
        }
        else
        {
            $id = wp_insert_post($postarr);
        }

        // 5) Persist meta
        update_post_meta($id, PresetRepository::META_KEY, $clean);

        // 6) If set as default, clear the flag on all others
        if ($clean['use_default'])
        {
            PresetRepository::clearDefaultPreset($id);
        }

        // 7) Redirect back with notice
        $redirect_url = add_query_arg(
            [
                'page'    => 'content-egg-product-import',
                'tab'     => 'presets',
                'updated' => 'true',
            ],
            admin_url('admin.php')
        );

        $redirect_url = AdminNotice::add2Url($redirect_url, 'preset_saved', 'success');

        AdminHelper::redirect($redirect_url);
    }
}

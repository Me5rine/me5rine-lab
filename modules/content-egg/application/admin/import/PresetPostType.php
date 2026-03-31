<?php

namespace ContentEgg\application\admin\import;

defined('ABSPATH') || exit;

/**
 * PresetPostType class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class PresetPostType
{
    public const POST_TYPE = 'cegg_import_preset';

    public static function register(): void
    {
        self::register_post_type();
    }

    public static function register_post_type(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'          => __('Import Presets', 'content-egg'),
                'singular_name' => __('Import Preset',  'content-egg'),
                'add_new_item'  => __('Add New Preset', 'content-egg'),
                'edit_item'     => __('Edit Preset',    'content-egg'),
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'capability_type'   => 'post',
            'map_meta_cap'      => true,
            'supports'          => ['title'],
            'menu_icon'         => 'dashicons-filter',
        ]);
    }
}

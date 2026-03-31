<?php

namespace ContentEgg\application\blocks\productblock;

use ContentEgg\application\components\BlockTemplateManager;
use ContentEgg\application\components\ModuleManager;;

defined('\ABSPATH') || exit;

/**
 * ProductBlock class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ProductBlock
{
    public static function initAction()
    {
        self::registerBlock();
        //add_action('init', array(__CLASS__, 'registerBlock'));
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueueBlockAssets'));
    }

    public static function getAttributes()
    {
        return array(
            '_refresh' => array(
                'type' => 'integer',
                'default' => 0
            ),
            'template' => array(
                'type' => 'string',
                'default' => ''
            ),
            'color_mode' => array(
                'type' => 'string',
                'default' => ''
            ),
            'limit' => array(
                'type' => 'integer'
            ),
            'offset' => array(
                'type' => 'integer'
            ),
            'next' => array(
                'type' => 'integer'
            ),
            'products' => array(
                'type' => 'string',
                'default' => ''
            ),
            'border' => array(
                'type' => 'integer'
            ),
            'btn_variant' => array(
                'type' => 'string',
                'default' => ''
            ),
            'cols' => array(
                'type' => 'integer'
            ),
            'cols_xs' => array(
                'type' => 'integer'
            ),
            'modules' => array(
                'type' => 'array',
                'default' => array()
            ),
            'exclude_modules' => array(
                'type' => 'array',
                'default' => array()
            ),
            'groups' => array(
                'type' => 'array',
                'default' => array()
            ),
            'hide' => array(
                'type' => 'array',
                'default' => array()
            ),
            'visible' => array(
                'type' => 'array',
                'default' => array()
            ),
            'title_tag' => array(
                'type' => 'string',
                'default' => ''
            ),
            'currency' => array(
                'type' => 'string',
                'default' => ''
            ),
            'add_query_arg' => array(
                'type' => 'string',
                'default' => ''
            ),
            'btn_text' => array(
                'type' => 'string',
                'default' => ''
            ),
            'img_ratio' => array(
                'type' => 'string',
                'default' => ''
            ),
            'border_color' => array(
                'type' => 'string',
                'default' => ''
            ),
            'tabs_type' => array(
                'type' => 'string',
                'default' => ''
            ),
            'cols_order' => array(
                'type' => 'string',
                'default' => ''
            ),
            'start_number' => array(
                'type' => 'integer'
            ),
            'link_target' => [
                'type'    => 'string',
                'default' => 'auto',
                'enum'    => ['auto', 'affiliate', 'bridge'],
            ],
            'post_id' => array(
                'type' => 'integer',
                'default' => 0,
            ),
        );
    }

    public static function registerBlock()
    {
        register_block_type('content-egg/products', array(
            'editor_script' => 'content-egg-products-editor',
            'render_callback' => array(__CLASS__, 'renderShortcode'),
            'attributes' => self::getAttributes(),
        ));
    }

    public static function enqueueBlockAssets()
    {
        wp_register_script(
            'content-egg-products-editor',
            plugins_url('block.js', __FILE__),
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components')
        );

        $modules = ModuleManager::getInstance()->getAffiliateParsersList(true);

        $tpl_manager = BlockTemplateManager::getInstance();
        $templates = $tpl_manager->getTemplatesList(true, true);
        $formatted_templates = array();

        foreach ($templates as $key => $value)
        {
            if ($key == 'customizable')
                continue;

            if (BlockTemplateManager::isPreviewAvailable($key))
                $preview = $key . '.webp';
            else
                $preview = '';

            $formatted_templates[] = array(
                'value' => __($key, 'content-egg'),
                'label' => $value,
                'preview' => $preview,
                'is_custom' => BlockTemplateManager::isCustomTemplate($key),
            );
        }

        wp_localize_script(
            'content-egg-products-editor',
            'contentEggProductsBlockData',
            array(
                'imagesBaseUrl' => \ContentEgg\PLUGIN_DIR_URL . '/templates/preview/',
                'modules' => $modules,
                'templates' => $formatted_templates,
            )
        );

        BlockTemplateManager::getInstance()->enqueueCeggStyle();
    }

    public static function renderShortcode($attributes)
    {
        $is_editor = defined('REST_REQUEST') && REST_REQUEST;

        $template = isset($attributes['template']) ? $attributes['template'] : '';

        if ($is_editor && BlockTemplateManager::isCustomTemplate($template))
            return '<div><small>' . esc_html__('Preview is not available for custom/theme templates.', 'content-egg') . '</small></div>';

        foreach ($attributes as $key => $value)
        {
            if (is_array($value))
            {
                $attributes[$key] = array_map('sanitize_text_field', $value);
                $attributes[$key] = join(',', $attributes[$key]);
            }
            else
                $attributes[$key] = sanitize_text_field($value);
        }

        $shortcode = '[content-egg-block';

        foreach ($attributes as $key => $value)
        {
            if ($value || ($key == 'border' && $value == 0))
                $shortcode .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        $shortcode .= ']';

        return do_shortcode($shortcode);
    }
}

<?php

namespace ContentEgg\application\components;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\helpers\TemplateHelper;
use ContentEgg\application\helpers\TextHelper;

use function ContentEgg\prn;

defined('\ABSPATH') || exit;

/**
 * ShortcodeAtts class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ShortcodeAtts
{
    public static function getAllowedAtts()
    {
        $allowed_atts = array(
            'modules' => '',
            'exclude_modules' => '',
            'template' => '',
            'post_id' => 0,
            'limit' => 0,
            'offset' => 0,
            'next' => 0,
            'start_number' => 0,
            'title' => '',
            'sort' => '',
            'order' => '',
            'currency' => '',
            'groups' => '',
            'group' => '',
            'products' => '',
            'product' => '',
            'hide' => '',
            'visible' => '',
            'show' => '',
            'btn_text' => '',
            'btn_variant' => '',
            'btn_color' => '',
            'btn_class' => '',
            'locale' => '',
            'ean' => '',
            'add_query_arg' => '',
            'remove_duplicates_by' => '',
            'cols' => 0,
            'cols_xs' => 0,
            'cols_sm' => 0,
            'cols_md' => 0,
            'cols_lg' => 0,
            'cols_xl' => 0,
            'cols_xxl' => 0,
            'img_ratio' => '',
            'color_mode' => '',
            'title_tag' => '',
            'border' => '',
            'border_color' => '',
            'tabs_type' => '',
            'cols_order' => '',
            'group_pick' => '',
            'link_target' => '',
        );

        $allowed_atts = \apply_filters('cegg_block_shortcode_atts', $allowed_atts);
        return $allowed_atts;
    }

    public static function prepare($atts)
    {
        $allowed_atts = self::getAllowedAtts();
        $a = \shortcode_atts($allowed_atts, $atts);
        $a['next'] = (int) $a['next'];
        $a['limit'] = (int) $a['limit'];
        $a['offset'] = (int) $a['offset'];
        $a['cols'] = abs((int)$a['cols']);
        $a['cols_xs'] = abs((int)$a['cols_xs']);
        $a['cols_sm'] = abs((int)$a['cols_sm']);
        $a['cols_md'] = abs((int)$a['cols_md']);
        $a['cols_lg'] = abs((int)$a['cols_lg']);
        $a['cols_xl'] = abs((int)$a['cols_xl']);
        $a['cols_xxl'] = abs((int)$a['cols_xxl']);
        $a['start_number'] = abs((int)$a['start_number']);
        $a['title'] = \sanitize_text_field($a['title']);
        $a['currency'] = strtoupper(TextHelper::clear($a['currency']));
        $a['groups'] = \sanitize_text_field($a['groups']);
        $a['group'] = \sanitize_text_field($a['group']);
        $a['hide'] = TemplateHelper::prepareParamHideVisible($a['hide']);
        $a['visible'] = TemplateHelper::prepareParamHideVisible($a['visible']);
        $a['show'] = strtolower(sanitize_text_field($a['show']));
        $a['btn_text'] = \sanitize_text_field($a['btn_text']);
        $a['btn_variant'] = strtolower(\sanitize_text_field($a['btn_variant']));
        $a['btn_color'] = strtolower(\sanitize_text_field($a['btn_color']));
        $a['btn_class'] = \sanitize_text_field($a['btn_class']);
        $a['add_query_arg'] = \sanitize_text_field($a['add_query_arg']);
        $a['locale'] = TextHelper::clear($a['locale']);
        $a['ean'] = TemplateHelper::eanParamPrepare($a['ean']);
        $a['remove_duplicates_by'] = \sanitize_text_field($a['remove_duplicates_by']);
        $a['color_mode'] = strtolower(\sanitize_text_field($a['color_mode']));
        $a['title_tag'] = strtolower(\sanitize_text_field($a['title_tag']));
        $a['border_color'] = strtolower(\sanitize_text_field($a['border_color']));
        $a['tabs_type'] = strtolower(\sanitize_text_field($a['tabs_type']));
        $a['cols_order'] = \sanitize_text_field($a['cols_order']);
        $a['group_pick'] = strtolower(\sanitize_text_field($a['group_pick']));
        $a['link_target'] = strtolower(\sanitize_text_field($a['link_target']));

        if (is_numeric($a['border']))
            $a['border'] = abs($a['border']);
        else
            $a['border'] = '';

        if ($a['group'] && !$a['groups'])
            $a['groups'] = $a['group'];
        if ($a['groups'])
            $a['groups'] = TextHelper::getArrayFromCommaList($a['groups']);
        if ($a['product'] && !$a['products'])
            $a['products'] = $a['product'];
        if ($a['products'])
            $a['products'] = TextHelper::getArrayFromCommaList($a['products']);
        if ($a['add_query_arg'])
        {
            parse_str($a['add_query_arg'], $a['add_query_arg']);
        }

        if ($a['cols'] && !$a['cols_md'])
            $a['cols_md'] = $a['cols'];

        $allowed_link_target = array('affiliate', 'bridge', 'auto');
        $allowed_group_pick = array('cheapest', 'priciest', 'random');
        $allowed_sort = array('price', 'discount', 'reverse', 'total_price');
        $allowed_order = array('asc', 'desc');
        $allowed_img_ratio = array('1x1', '4x3', '16x9', '21x9');
        $allowed_btn_variants = GeneralConfig::getBtnVariants();
        $allowed_color_mode = array('light', 'dark');
        $allowed_title_tag = array('div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6');
        $allowed_border_color = array('primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark');
        $tabs_type = array('tabs', 'pills', 'underline');

        $a['sort'] = strtolower($a['sort']);
        $a['order'] = strtolower($a['order']);
        if (!in_array($a['sort'], $allowed_sort))
            $a['sort'] = '';
        if (!in_array($a['group_pick'], $allowed_group_pick))
            $a['group_pick'] = '';
        if (!in_array($a['link_target'], $allowed_link_target))
            $a['link_target'] = '';
        if ($a['link_target'] == 'auto')
            $a['link_target'] = '';
        if (!in_array($a['order'], $allowed_order))
            $a['order'] = '';
        if ($a['sort'] == 'discount' && !$a['order'])
            $a['order'] = 'desc';
        if (!in_array($a['img_ratio'], $allowed_img_ratio))
            $a['img_ratio'] = '';
        if (!in_array($a['btn_variant'], $allowed_btn_variants))
            $a['btn_variant'] = '';
        if (!in_array($a['btn_color'], $allowed_btn_variants))
            $a['btn_color'] = '';
        if (!in_array($a['color_mode'], $allowed_color_mode))
            $a['color_mode'] = '';
        if (!in_array($a['title_tag'], $allowed_title_tag))
            $a['title_tag'] = '';
        if (!in_array($a['border_color'], $allowed_border_color))
            $a['border_color'] = '';
        if (!in_array($a['tabs_type'], $tabs_type))
            $a['tabs_type'] = '';
        if ($a['border'] && $a['border'] > 5)
            $a['border'] = 5;

        if ($a['cols_order'])
        {
            $a['cols_order'] = TextHelper::getArrayFromCommaList($a['cols_order']);
            $a['cols_order'] = array_map('intval', $a['cols_order']);
        }
        else
            $a['cols_order'] = array();

        if (!$a['btn_variant'] && $a['btn_color'])
            $a['btn_variant'] = $a['btn_color'];

        if ($a['modules'])
        {
            $modules = TextHelper::getArrayFromCommaList($a['modules']);
            $module_ids = array();
            foreach ($modules as $module_id)
            {
                if (ModuleManager::getInstance()->isModuleActive($module_id))
                    $module_ids[] = $module_id;
            }
            $a['modules'] = $module_ids;
        }

        if ($a['exclude_modules'])
            $a['exclude_modules'] = TextHelper::getArrayFromCommaList($a['exclude_modules']);

        if ($a['template'])
            $a['template'] = BlockTemplateManager::getInstance()->prepareShortcodeTempate($a['template']);

        $a['post_id'] = (int) $a['post_id'];

        return $a;
    }
}

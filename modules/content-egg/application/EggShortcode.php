<?php

namespace ContentEgg\application;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ContentManager;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\components\ModuleTemplateManager;
use ContentEgg\application\components\ShortcodeAtts;
use ContentEgg\application\components\Shortcoded;
use ContentEgg\application\helpers\TextHelper;



/**
 * EggShortcode class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class EggShortcode
{
    const shortcode = 'content-egg';

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null)
            self::$instance = new self;

        return self::$instance;
    }

    private function __construct()
    {
        \add_shortcode(self::shortcode, array($this, 'viewData'));
        \add_filter('term_description', 'shortcode_unautop');
        \add_filter('term_description', 'do_shortcode');
    }

    private function prepareAttr($atts)
    {
        $allowed_atts = array(
            'module' => null,
            'disable_features' => 0,
            'keyword' => '',
            'template' => '',
            'groups' => '',
        );

        $allowed_atts = \apply_filters('cegg_module_shortcode_atts', $allowed_atts);
        $a = \shortcode_atts($allowed_atts, $atts);

        $a['disable_features'] = filter_var($a['disable_features'], FILTER_VALIDATE_BOOLEAN);
        $a['keyword'] = \sanitize_text_field(html_entity_decode($a['keyword']));
        $a['module'] = TextHelper::clear($a['module']);

        if ($a['template'] && $a['module'])
            $a['template'] = ModuleTemplateManager::getInstance($a['module'])->prepareShortcodeTempate($a['template']);
        else
            $a['template'] = '';

        if ($a['keyword'] && !$a['groups'])
        {
            if (strstr($a['keyword'], '->'))
            {

                list($keywords, $groups) = ContentManager::prepareMultipleKeywords($a['keyword']);
                $a['groups'] = $groups;
            }
            else
                $a['groups'] = array($a['keyword']);
        }

        $general = ShortcodeAtts::prepare($atts);
        $a = array_merge($general, $a);

        return $a;
    }

    public function viewData($atts, $content)
    {
        $a = $this->prepareAttr($atts);

        if (empty($a['module']))
            return;

        $post_id = null;
        if (empty($a['post_id']))
        {
            global $post;
            if (!empty($post))
                $post_id = $post->ID;
        }
        else
            $post_id = $a['post_id'];

        if (!$post_id)
            return array();

        $module_id = $a['module'];
        if (!ModuleManager::getInstance()->isModuleActive($module_id))
            return;

        Shortcoded::getInstance($post_id)->setShortcodedModule($module_id);
        return ModuleViewer::getInstance()->viewModuleData($module_id, $post_id, $a, $content);
    }

    public static function arraySortByColumn(&$arr, $col, $dir = SORT_ASC)
    {
        $sort_col = array();
        foreach ($arr as $key => $row)
        {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }
}

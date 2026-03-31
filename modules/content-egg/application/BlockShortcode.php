<?php

namespace ContentEgg\application;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\components\BlockTemplateManager;
use ContentEgg\application\components\ShortcodeAtts;

use function ContentEgg\prn;;

/**
 * BlockShortcode class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class BlockShortcode extends EggShortcode
{

    const shortcode = 'content-egg-block';

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null)
            self::$instance = new self;
        return self::$instance;
    }

    private function __construct()
    {
        \add_shortcode(self::shortcode, array($this, 'viewDataShortcode'));
    }

    public function viewDataShortcode($atts, $content = '')
    {
        return $this->viewData($atts, $content);
    }

    public function viewData($atts, $content = '', $only_return_data = false)
    {
        $a = ShortcodeAtts::prepare($atts);

        if (empty($a['post_id']))
        {
            global $post;
            if (empty($post))
                return '';

            $post_id = $post->ID;
        }
        else
            $post_id = $a['post_id'];

        if (empty($a['template']))
            return;

        if ($a['template'] != 'block_greenshift')
        {
            $tpl_manager = BlockTemplateManager::getInstance();

            if (!$tpl_manager->isTemplateExists($a['template']))
                return;

            $template_file = $tpl_manager->getViewPath($a['template']);
            if (!$template_file)
                return '';

            // Get supported modules for this tpl
            $headers = \get_file_data($template_file, array('module_ids' => 'Modules', 'module_types' => 'Module Types', 'shortcoded' => 'Shortcoded'));
            $supported_module_ids = array();
            if ($headers && !empty($headers['module_ids']))
            {
                $supported_module_ids = explode(',', $headers['module_ids']);
                $supported_module_ids = array_map('trim', $supported_module_ids);
            }
            elseif ($headers && !empty($headers['module_types']))
            {
                $module_types = explode(',', $headers['module_types']);
                $module_types = array_map('trim', $module_types);
                $supported_module_ids = ModuleManager::getInstance()->getParserModuleIdsByTypes($module_types, true);
            }
            elseif (!$headers || empty($headers['module_types']))
                $module_types = 'PRODUCT';

            if ($headers && !empty($headers['shortcoded']))
                $a['shortcoded'] = filter_var($headers['shortcoded'], FILTER_VALIDATE_BOOLEAN);
        }
        else
        {
            $a['shortcoded'] = true;
            $supported_module_ids = ModuleManager::getInstance()->getParserModuleIdsByTypes('PRODUCT', true);
        }

        if (!$supported_module_ids)
            return '';

        if ($a['modules'])
            $module_ids = $a['modules'];
        else
            $module_ids = ModuleManager::getInstance()->getParserModulesIdList(true);

        $module_ids = array_intersect($module_ids, $supported_module_ids);

        if ($a['exclude_modules'])
            $module_ids = array_diff($module_ids, $a['exclude_modules']);

        return ModuleViewer::getInstance()->viewBlockData($module_ids, $post_id, $a, $content, $only_return_data);
    }
}

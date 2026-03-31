<?php

namespace ContentEgg\application\components;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\EggShortcode;
use ContentEgg\application\components\ModuleManager;;

defined('\ABSPATH') || exit;

/**
 * ShortcodePreprocessor class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ShortcodePreprocessor
{

    public static function initAction()
    {
        \add_action('save_post', array(__CLASS__, 'maybeDoAction'), 13, 3);
    }

    public static function maybeDoAction($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (\get_post_status($post) == 'auto-draft' || \wp_is_post_revision($post))
            return;

        if (!in_array(\get_post_type($post), GeneralConfig::getInstance()->option('post_types')))
            return;

        if (!preg_match('/\[' . EggShortcode::shortcode . '\s.+?keyword.+?\]/', $post->post_content))
            return;

        self::doAction($post_id, $post, $update);
    }

    public static function doAction($post_id, $post, $update)
    {
        if (!preg_match_all('/' . \get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER))
            return;

        $data = array();
        foreach ($matches as $shortcode)
        {
            if ($shortcode[2] !== EggShortcode::shortcode)
                continue;

            $attrs = \shortcode_parse_atts($shortcode[3]);
            if (!is_array($attrs))
                $attrs = array();

            if (isset($attrs['keyword']) && isset($attrs['module']))
            {
                if (!isset($data[$attrs['module']]))
                    $data[$attrs['module']] = array();

                $data[$attrs['module']][] = \sanitize_text_field(html_entity_decode($attrs['keyword']));
            }
        }

        foreach ($data as $module_id => $keywords)
        {
            if (!ModuleManager::getInstance()->moduleExists($module_id) || !ModuleManager::getInstance()->isModuleActive($module_id))
                continue;

            $module = ModuleManager::getInstance()->factory($module_id);
            if (!$module->isParser())
                continue;

            $keywords = array_unique($keywords);

            foreach ($keywords as $i => $k)
            {
                if (!strstr($k, '->'))
                    $keywords[$i] = $k . '->' . $k; // create group automatically
            }

            $keywords = join(',', $keywords);

            self::proccessKeywords($keywords, $module_id, $post_id);
        }
    }

    public static function proccessKeywords($keyword, $module_id, $post_id)
    {
        list($k, $g) = ContentManager::prepareMultipleKeywords($keyword);

        $k = array_unique($k);

        $terms = array();
        foreach ($k as $i => $_k)
        {
            $t = '';
            if (!$g[$i])
                $g[$i] = $_k; // create group automatically

            $t .= $_k . '->' . $g[$i];
            $terms[] = $t;
        }

        $save_meta = join(',', $terms);
        $existed_meta = \get_post_meta($post_id, ContentManager::META_PREFIX_KEYWORD . $module_id, true);

        if ($existed_meta != $save_meta)
        {
            @set_time_limit(60);

            \update_post_meta($post_id, ContentManager::META_PREFIX_KEYWORD . $module_id, $save_meta);
            ContentManager::updateByKeyword($post_id, $module_id);
        }
    }
}

<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\components\ContentManager;
use ContentEgg\application\components\ContentProduct;
use ContentEgg\application\components\ContentCoupon;
use ContentEgg\application\components\ExtraData;
use ContentEgg\application\components\LManager;
use ContentEgg\application\helpers\ClickStatsHelper;
use ContentEgg\application\Plugin;

/**
 * EggMetabox class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class EggMetabox
{

    private $app_params = array();

    public function __construct()
    {
        if (Plugin::isActivated() && LManager::isNulled())
            return;

        \add_action('wp_ajax_cegg_update_products', array($this, 'ajaxUpdateProducts'));
        \add_action('add_meta_boxes', array($this, 'addMetabox'));
        \add_action('save_post', array($this, 'saveMeta'));
    }

    private function addAppParam($param, $value)
    {
        $this->app_params[$param] = \apply_filters('content_egg_add_app_param', $value, $param);
    }

    private function getAppParams()
    {
        return $this->app_params;
    }

    public function addMetabox($post_type)
    {
        if (!in_array($post_type, GeneralConfig::getInstance()->option('post_types')))
            return;

        if (!ModuleManager::getInstance()->getModules(true))
        {
            \add_meta_box('content_meta_box', 'Content Egg', array($this, 'renderBlankMetabox'), $post_type, 'normal', 'high');
            return;
        }
        $this->modulesOptionsInit();
        $this->metadataInit();
        $title = 'Content Egg';
        if (Plugin::isFree())
            $title .= '&nbsp;&nbsp;&nbsp;<a target="_blank" href="' . \ContentEgg\application\Plugin::pluginPricingUrl('ce_metabox', 'go_pro_link') . '">' . __('Go PRO', 'content-egg') . '</a>';
        else
            $title .= ' Pro';

        \add_meta_box('content_meta_box', $title, array($this, 'renderMetabox'), $post_type, 'normal', 'high');
        $this->angularInit();
    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function renderMetabox($post)
    {
        $modules = ModuleManager::getInstance()->getModules(true);
        $module_ids = \apply_filters('content_egg_metabox_modules', array_keys($modules));
        $modules = array_intersect_key($modules, array_flip($module_ids));

        $keywordsExist = false;
        $dataExist = false;
        foreach ($modules as $module)
        {
            if (!$keywordsExist && ContentManager::isAutoupdateKeywordExists($post->ID, $module->getId()))
                $keywordsExist = true;

            if (!$dataExist && ContentManager::isDataExists($post->ID, $module->getId()))
                $dataExist = true;

            if ($keywordsExist && $dataExist)
                break;
        }

        echo '<div ng-app="contentEgg" class="egg-container" id="content-egg" ng-cloak>';
        echo '<div ng-controller="ContentEggController" class="container-fluid" style="padding: 0px;">';

        PluginAdmin::render('metabox_general', array('post' => $post, 'keywordsExist' => $keywordsExist, 'dataExist' => $dataExist));
        foreach ($modules as $module)
        {
            $module->enqueueScripts();
            $module->renderMetaboxModule();
        }

        // scroll to #module-product_id
        echo '
<script>
"use strict";
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        if (window.location.hash) {
            var element = document.getElementById(window.location.hash.substring(1));
            console.log(element);
            if (element) {
                element.scrollIntoView();
            }
        }
    }, 500);
});
</script>
';

        echo '</div>';
        echo '</div>';
    }

    public function renderBlankMetabox($post)
    {
        esc_attr_e('Configure and activate modules for the Content Egg plugin.', 'content-egg');
    }

    private function metadataInit()
    {
        global $post;

        $post_id      = isset($post->ID) ? (int) $post->ID : 0;
        $modules      = ModuleManager::getInstance()->getModules(true);
        $statsEnabled = ClickStatsHelper::isEnabled();

        // Warm link-id cache once for this post to speed up lookups
        if ($statsEnabled && $post_id > 0)
        {
            ClickStatsHelper::warmCacheForPost($post_id);
        }

        // modules data
        $init_data = array();
        $init_productGroups = array();
        foreach ($modules as $module)
        {
            $module_id = $module->getId();
            $post_meta = ContentManager::getData($post_id, $module_id);

            if (!$post_meta || !is_array($post_meta))
                continue;

            $post_meta = ContentManager::applyBridgeMetaForModuleAdmin($post_meta, $module_id, $post_id, true);

            foreach ($post_meta as $key => $meta)
            {
                if (!empty($meta['description']) && !TextHelper::isHtmlTagDetected($meta['description']))
                    $post_meta[$key]['description'] = TextHelper::br2nl($meta['description']);

                if ($module_id === 'Coupon')
                {
                    if (!empty($post_meta[$key]['startDate']))
                        $post_meta[$key]['startDate'] *= 1000;
                    if (!empty($post_meta[$key]['endDate']))
                        $post_meta[$key]['endDate'] *= 1000;
                }

                if (!empty($meta['group']) && !in_array($meta['group'], $init_productGroups, true))
                    $init_productGroups[] = $meta['group'];

                // ---- Click stats enrichment ------------------------------------
                // Always set numeric fields; UI will hide badges if zero.
                $post_meta[$key]['_clicks_30d'] = 0;
                $post_meta[$key]['_clicks']     = 0;

                if ($statsEnabled && !empty($meta['unique_id']))
                {
                    $link_id = ClickStatsHelper::linkIdForTriplet(
                        $post_id,
                        (string) $module_id,
                        (string) $meta['unique_id']
                    );

                    if ($link_id > 0)
                    {
                        $agg = ClickStatsHelper::aggregatesForLink($link_id);
                        $post_meta[$key]['_clicks_30d'] = (int) ($agg['d30']  ?? 0);
                        $post_meta[$key]['_clicks']     = (int) ($agg['total'] ?? 0);
                    }
                }
                // -----------------------------------------------------------------
            }

            $init_data[$module_id] = array_values($post_meta);
        }

        $init_productGroups = \apply_filters('cegg_static_product_groups', $init_productGroups, $post_id);
        $init_productGroups = array_values(array_unique($init_productGroups));

        $this->addAppParam('initData', $init_data);
        $this->addAppParam('initProductGroups', $init_productGroups);

        // keywords
        $init_keywords = array();
        $init_updateParams = array();
        foreach ($modules as $module)
        {
            if (!$module->isParser())
                continue;
            $keywords_meta = \get_post_meta($post_id, ContentManager::META_PREFIX_KEYWORD . $module->getId(), true);
            if (!$keywords_meta)
                continue;
            $init_keywords[$module->getId()] = $keywords_meta;

            $update_params_meta = \get_post_meta($post_id, ContentManager::META_PREFIX_UPDATE_PARAMS . $module->getId(), true);
            if (!$update_params_meta)
                continue;
            $init_updateParams[$module->getId()] = $update_params_meta;
        }
        $this->addAppParam('initKeywords', $init_keywords);
        $this->addAppParam('initUpdateParams', $init_updateParams);

        // blank content model
        $content = new ContentProduct;
        $content->extra = new ExtraData;
        $this->addAppParam('contentProduct', $content);

        // blank Coupon
        $coupon = new ContentCoupon;
        $this->addAppParam('contentCoupon', $coupon);
    }

    private function modulesOptionsInit()
    {
        $init_options = array();
        foreach (ModuleManager::getInstance()->getModules(true) as $module)
        {
            $init_options[$module->getId()] = array();
            foreach ($module->getConfigInstance()->options() as $option_name => $option)
            {
                if (isset($option['metaboxInit']) && $option['metaboxInit'])
                {
                    $init_options[$module->getId()][$option_name] = $module->config($option_name);
                }
            }
        }
        $this->addAppParam('modulesOptions', $init_options);
    }

    private function angularInit()
    {
        // Justified gallery jquery plugin
        \wp_enqueue_script('justified-gallery', \ContentEgg\PLUGIN_RES . '/justified_gallery/jquery.justifiedGallery.min.js', array('jquery'), Plugin::version());
        \wp_enqueue_style('justified-gallery', \ContentEgg\PLUGIN_RES . '/justified_gallery/justifiedGallery.min.css');

        // Angular core
        \wp_enqueue_script('angularjs', \ContentEgg\PLUGIN_RES . '/app/vendor/angular.min.js', array('jquery'), null);
        \wp_enqueue_script('angularjs-sanitize', \ContentEgg\PLUGIN_RES . '/app/vendor/angular-sanitize.js', array('angularjs'), null, false);

        // ContentEgg angular application
        \wp_enqueue_style('contentegg-admin', \ContentEgg\PLUGIN_RES . '/css/admin.css', null, '' . Plugin::version());
        \wp_enqueue_script('angular-sortable', \ContentEgg\PLUGIN_RES . '/app/vendor/angular-sortable.js', array('angularjs', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable'), Plugin::version);
        \wp_enqueue_script('angular-ui-tinymce', \ContentEgg\PLUGIN_RES . '/app/vendor/angular-tinymce.js', array('angularjs', 'wp-tinymce'), Plugin::version);
        \wp_enqueue_script('tinymce-code', \ContentEgg\PLUGIN_RES . '/app/vendor/tinymce-code/plugin.min.js', array('wp-tinymce'), Plugin::version);
        \wp_register_script('contentegg-metabox-app', \ContentEgg\PLUGIN_RES . '/app/app.js', array('angularjs'), Plugin::version());
        \wp_enqueue_script('contentegg-metabox-service', \ContentEgg\PLUGIN_RES . '/app/ModuleService.js', array('contentegg-metabox-app'), Plugin::version());
        \wp_enqueue_script('cegg-import-service', PluginAdmin::res('app/import/service.js'), ['contentegg-metabox-app'], Plugin::version(), true);
        \wp_enqueue_script('cegg-toast-service', PluginAdmin::res('app/bs-toast.service.js'), ['contentegg-metabox-app'], Plugin::version(), true);

        // Bootstrap
        \wp_enqueue_style('cegg-bootstrap-admin', \ContentEgg\PLUGIN_RES . '/admin/bootstrap/css/bootstrap.css', array(), Plugin::version());
        \wp_enqueue_style('cegg-bootstrap-icons', \ContentEgg\PLUGIN_RES . '/admin/bootstrap/css/bootstrap-icons.min.css', array(), Plugin::version());
        \wp_enqueue_script('cegg-bootstrap-admin', \ContentEgg\PLUGIN_RES . '/admin/bootstrap/js/bootstrap.bundle.min.js', array(), Plugin::version());

        // ContentEgg application params
        $this->addAppParam('active_modules', ModuleManager::getInstance()->getModulesIdList(true));
        $this->addAppParam('nonce', \wp_create_nonce('contentegg-metabox'));
        $this->addAppParam('importNonce', \wp_create_nonce('cegg_import'));

        \wp_localize_script('contentegg-metabox-app', 'contentegg_params', $this->getAppParams());
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function saveMeta($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (!isset($_POST['contentegg_nonce']))
            return;

        \check_admin_referer('contentegg_metabox', 'contentegg_nonce');

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'page')
        {
            if (!current_user_can('edit_page', $post_id))
                return;
        }
        else
        {
            if (!current_user_can('edit_post', $post_id))
                return;
        }

        // need stripslashes? wp bug with revision post type?
        if (\wp_is_post_revision($post_id))
            $stripslashes = false;
        else
            $stripslashes = true;

        // keywords for automatic updates
        $keywords = array();
        if (isset($_POST['cegg_updateKeywords']))
        {
            $keywords = array_map(array('ContentEgg\application\components\ContentManager', 'sanitizeKeyword'), wp_unslash($_POST['cegg_updateKeywords']));
        }

        $update_params = array();
        if (isset($_POST['cegg_updateParams']))
        {
            $update_params = array_map('sanitize_text_field', wp_unslash($_POST['cegg_updateParams']));
        }

        foreach ($keywords as $module_id => $keyword)
        {
            if (!ModuleManager::getInstance()->moduleExists($module_id) || !ModuleManager::getInstance()->isModuleActive($module_id))
                continue;

            $module = ModuleManager::getInstance()->factory($module_id);
            if (!$module->isParser())
                continue;

            $keyword = ContentManager::sanitizeKeyword($keyword);
            if ($keyword)
            {
                \update_post_meta($post_id, ContentManager::META_PREFIX_KEYWORD . $module_id, $keyword);
                if (isset($update_params[$module_id]))
                {
                    \update_post_meta($post_id, ContentManager::META_PREFIX_UPDATE_PARAMS . $module_id, json_decode($update_params[$module_id], true));
                }
            }
            else
            {
                \delete_post_meta($post_id, ContentManager::META_PREFIX_KEYWORD . $module_id);
                \delete_post_meta($post_id, ContentManager::META_PREFIX_UPDATE_PARAMS . $module_id);
            }
        }

        if (isset($_POST['globalUpdateKeyword']))
        {
            if ($global_keyword = \sanitize_text_field(\wp_unslash($_POST['globalUpdateKeyword'])))
                \update_post_meta($post_id, '_cegg_global_autoupdate_keyword', $global_keyword);
            else
                \delete_post_meta($post_id, '_cegg_global_autoupdate_keyword');
        }

        // save content data
        if (isset($_POST['cegg_data']))
            $content = wp_unslash($_POST['cegg_data']);  // phpcs:ignore
        else
            $content = array();

        if (!is_array($content))
            return;

        $i = 0;
        foreach ($content as $module_id => $data)
        {
            $module_id = sanitize_text_field($module_id);
            $i++;
            if (!ModuleManager::getInstance()->moduleExists($module_id) || !ModuleManager::getInstance()->isModuleActive($module_id))
                continue;

            $data = json_decode($data, true);
            $data = $this->dataPrepare($data);
            if ($i == count($content))
                $last_iteration = true;
            else
                $last_iteration = false;
            ContentManager::saveData($data, $module_id, $post_id, $last_iteration);
        }
    }

    private function dataPrepare($data): array
    {
        if (!is_array($data) || empty($data))
        {
            return [];
        }

        foreach ($data as $i => &$row)
        {
            if (!is_array($row))
            {
                unset($data[$i]);
                continue;
            }

            // dinamic fields
            $row['aff_url'] = null;
            $row['bridge_url'] = null;
            $row['target_post_id'] = null;

            if (isset($row['description']) && is_string($row['description']))
            {
                $desc = trim($row['description']);
                if ($desc !== '' && !TextHelper::isHtmlTagDetected($desc))
                {
                    $desc = str_replace(["\r\n", "\r"], "\n", $desc);
                    $row['description'] = TextHelper::nl2br($desc);
                }
                else
                {
                    $row['description'] = $desc;
                }
            }

            if (array_key_exists('price', $row))
            {
                $row['price'] = (float)$row['price'];
            }
            if (array_key_exists('priceOld', $row))
            {
                $row['priceOld'] = (float)$row['priceOld'];
            }
        }
        unset($row);

        return $data;
    }

    public function ajaxUpdateProducts()
    {
        if (!isset($_POST['contentegg_nonce']) || !\wp_verify_nonce($_POST['contentegg_nonce'], 'contentegg_metabox'))
            \wp_die('Invalid nonce');

        if (!\current_user_can('edit_posts'))
            \wp_die('You don\'t have access to this page.');

        $post_id = isset($_POST['post_id']) ? intval(\wp_unslash($_POST['post_id'])) : null;

        if (!$post_id)
            \wp_die('Invalid post ID');

        @set_time_limit(60);

        $action = isset($_POST['btn']) ? TextHelper::clear(\sanitize_text_field(\wp_unslash($_POST['btn']))) : null;

        if ($action == 'cegg_update_lists')
            self::updateByKeyword($post_id);
        elseif ($action == 'cegg_update_prices')
            self::updateItems($post_id);
        else
            \wp_die('Invalid action');

        \wp_die();
    }

    private function updateByKeyword($post_id)
    {
        @set_time_limit(180);
        ContentManager::updateAllByKeyword($post_id);
    }

    private function updateItems($post_id)
    {
        ContentManager::updateAllItems($post_id);
    }
}

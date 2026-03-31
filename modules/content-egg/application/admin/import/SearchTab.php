<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\import\AbstractTab;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\WooHelper;
use ContentEgg\application\Plugin;;

defined('ABSPATH') || exit;

/**
 * SearchTab class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class SearchTab extends AbstractTab
{

    public function __construct()
    {
        parent::__construct('search', __('Search', 'content-egg'));
    }

    public function enqueueAssets(): void
    {
        // 1) Angular + sanitize
        wp_enqueue_script('cegg-angular',   PluginAdmin::res('app/vendor/angular.min.js'), [], null, true);
        wp_enqueue_script('cegg-sanitize',  PluginAdmin::res('app/vendor/angular-sanitize.js'), ['cegg-angular'], null, true);

        // 2) Our Angular pieces
        wp_enqueue_script('cegg-import-module',      PluginAdmin::res('app/import/module.js'),      ['cegg-sanitize'],   Plugin::version(), true);
        wp_enqueue_script('cegg-import-service',     PluginAdmin::res('app/import/service.js'),     ['cegg-import-module'], Plugin::version(), true);
        wp_enqueue_script('cegg-import-controllers', PluginAdmin::res('app/import/controllers.js'), ['cegg-import-service'], Plugin::version(), true);

        // 3) Bootstrap
        wp_enqueue_style('cegg-bootstrap5-full');
        wp_enqueue_style('cegg-bootstrap-icons', PluginAdmin::res('/admin/bootstrap/css/bootstrap-icons.min.css'), [], Plugin::version());

        $preset_options = PresetRepository::getList();
        $post_cat_options = WooHelper::getPostCategoryList();
        $woo_cat_options = WooHelper::getWooCategoryList();
        $default_preset_id = (int) PresetRepository::getDefaultId();
        $module_meta = ModuleManager::getInstance()->getAffiliateParsersMeta(true, true, true);

        // 4) Localize
        wp_localize_script(
            'cegg-import-module',
            'contentegg_params',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('contentegg-metabox'),
                'importNonce'     => \wp_create_nonce('cegg_import'),
                'presets'      => $preset_options,
                'postCats'     => $post_cat_options,
                'wooCats'      => $woo_cat_options,
                'defaultPresetId' => $default_preset_id,
                'moduleMeta' => $module_meta,
            ]
        );
    }

    public function render(): void
    {
        $modules = ModuleManager::getInstance()->getAffiliateParsers(true, true);
        if (empty($modules))
        {
            echo '<div class="cegg5-container"><div class="alert alert-warning mt-4">'
                . esc_html__('No modules found. Please activate at least one module.', 'content-egg')
                . '</div></div>';
            return;
        }

        PluginAdmin::getInstance()->render('search_import');
    }
}

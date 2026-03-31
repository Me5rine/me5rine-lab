<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

use ContentEgg\application\Plugin;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\admin\import\ImportQueueApi;
use ContentEgg\application\admin\import\PresetRepository;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\components\ModuleApi;
use ContentEgg\application\components\LManager;
use ContentEgg\application\components\ReviewNotice;
use ContentEgg\application\components\FeaturedImage;
use ContentEgg\application\Installer;
use ContentEgg\application\LinkIndexScheduler;
use ContentEgg\application\ModuleUpdateScheduler;
use ContentEgg\application\SystemScheduler;



/**
 * PluginAdmin class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class PluginAdmin
{

    protected static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null)
            self::$instance = new self;

        return self::$instance;
    }

    private function __construct()
    {
        if (!\is_admin())
            die('You are not authorized to perform the requested action.');

        \add_action('admin_menu', array($this, 'add_admin_menu'));
        \add_action('admin_enqueue_scripts', array($this, 'admin_load_scripts'));
        \add_filter('parent_file', array($this, 'highlight_admin_menu'));

        if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'plugins.php')
        {
            \add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
        }

        AdminNotice::getInstance()->adminInit();
        if (!Plugin::isFree())
        {
            LManager::getInstance()->adminInit();
        }

        if (Plugin::isFree())
            ReviewNotice::getInstance()->adminInit();
        else
            SystemScheduler::addScheduleEvent('weekly', time() + rand(259200, 604800));

        if (Plugin::isFree() || (Plugin::isPro() && Plugin::isActivated()) || Plugin::isEnvato())
        {
            PresetRepository::init();
            GeneralConfig::getInstance()->adminInit();
            ModuleManager::getInstance()->adminInit();
            new ModuleSettingsContoller;
            new ProductImportController;
            new ProductPrefillController;
            new ProductController;
            new ClicksStatsController;
            new EggMetabox;
            new ModuleApi;
            new FeaturedImage;
            new ToolsController;
            ImportQueueApi::init();
            AeIntegrationConfig::getInstance()->adminInit();
            new AutoblogController;
            ProUpsellLinks::init();
            ModuleUpdateScheduler::addScheduleEvent('ten_min');
        }

        if (Plugin::isEnvato() && !Plugin::isActivated() && !\get_option(Plugin::slug . '_env_install'))
            EnvatoConfig::getInstance()->adminInit();
        elseif (Plugin::isPro())
            LicConfig::getInstance()->adminInit();

        if (Plugin::isPro() && Plugin::isActivated())
        {
            new \ContentEgg\application\Autoupdate(Plugin::version(), plugin_basename(\ContentEgg\PLUGIN_FILE), Installer::getApiUrl(), Plugin::slug);
        }
    }

    function admin_load_scripts()
    {
        if ($GLOBALS['pagenow'] != 'admin.php' || empty($_GET['page']))
            return;

        $page_pats = explode('-', sanitize_key(wp_unslash($_GET['page'])));

        if (count($page_pats) < 2 || $page_pats[0] . '-' . $page_pats[1] != 'content-egg')
            return;

        \wp_enqueue_script('content_egg_common', \ContentEgg\PLUGIN_RES . '/js/common.js', array('jquery'));
        \wp_localize_script('content_egg_common', 'contenteggL10n', array(
            'are_you_shure' => __('Are you sure?', 'content-egg'),
            'sitelang' => GeneralConfig::getInstance()->option('lang'),
        ));

        \wp_enqueue_style('contentegg-admin', \ContentEgg\PLUGIN_RES . '/css/admin.css', null, '' . Plugin::version());
    }

    public function add_plugin_row_meta(array $links, $file)
    {
        if ($file == plugin_basename(\ContentEgg\PLUGIN_FILE) && (Plugin::isActivated() || Plugin::isFree()))
        {
            return array_merge(
                $links,
                array(
                    '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=content-egg">' . __('Settings', 'content-egg') . '</a>',
                )
            );
        }
        return $links;
    }

    public function add_admin_menu()
    {
        $icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iNjgwLjc0IiBoZWlnaHQ9IjgzMS4zNyIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgNjgwLjc0IDgzMS4zNyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIiB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+PG1ldGFkYXRhPjxyZGY6UkRGPjxjYzpXb3JrIHJkZjphYm91dD0iIj48ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD48ZGM6dHlwZSByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIi8+PGRjOnRpdGxlLz48L2NjOldvcms+PC9yZGY6UkRGPjwvbWV0YWRhdGE+PGRlZnM+PGNsaXBQYXRoIGlkPSJjbGlwUGF0aDI2Ij48cGF0aCBkPSJtMCA5MDBoNjAwdi05MDBoLTYwMHoiLz48L2NsaXBQYXRoPjwvZGVmcz48ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLjMzMzMgMCAwIC0xLjMzMzMgLTYyLjI5NSAxMDE0LjEpIj48ZyBjbGlwLXBhdGg9InVybCgjY2xpcFBhdGgyNikiPjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDU1Ny4yMSAzOTYpIj48cGF0aCBkPSJtMCAwaC0zNi4zNTZzM2UtMyAtMC41OTYgM2UtMyAtMC45MDJjMC0xNDIuOTgtODYuMDMyLTIyMi40NS0yMDYuNDEtMjIyLjQ1LTEyMC4zOCAwLTIyOS41MiA3OS40ODMtMjI5LjUyIDIyMi40NiAwIDE0Mi45NyAxMDIuOTIgMzI1LjIxIDIyMy4zIDMyNS4yMSA5NC4xNTYgMCAxNzEuMjItMTEwLjMxIDIwMC4xOS0yMjcuMzFoLTM1Mi40MXYtMzdoMzk1LjAyYy0xLjQxMiA5LTMuMjcgMjAtNS4zNjQgMjloMC4wOTJjLTAuNTAyIDItMS4wMjEgNC43NzEtMS41NTMgNi45OTYtMC4wNTIgMC4yMjktMC4wOTkgMS4wMDQtMC4xNTEgMS4wMDRoLTAuMDE0Yy0zMi42NDggMTM1LTEyMy40IDI2Ny42MS0yMzUuNDMgMjY3LjYxLTE0MC45OSAwLTI2MS44OC0yMDkuODctMjYxLjg4LTM3MS4zOSAwLTE2MS41MiAxMjYuMTgtMjUyLjE0IDI2Ny4xNy0yNTIuMTRzMjQzLjM5IDkwLjUwMiAyNDMuMzkgMjUyLjAyYzAgMi4zNDgtMC4wMjMgNC45MDItMC4wNjkgNi45MDIiIGZpbGw9IiMwMGMxYWQiLz48L2c+PC9nPjwvZz48L3N2Zz4K';
        $title = 'Content Egg';
        if (Plugin::isPro())
            $title .= ' Pro';
        \add_menu_page($title, $title, 'publish_posts', Plugin::slug, null, $icon_svg);
    }

    public static function render($view_name, $_data = null)
    {
        if (is_array($_data))
            extract($_data, EXTR_PREFIX_SAME, 'data');
        else
            $data = $_data;

        include \ContentEgg\PLUGIN_PATH . 'application/admin/views/' . TextHelper::clear($view_name) . '.php';
    }

    /**
     * Highlight menu for hidden submenu item
     */
    function highlight_admin_menu($file)
    {
        global $plugin_page;

        // options.php - hidden submenu items
        if ($file != 'options.php' || substr($plugin_page, 0, strlen(Plugin::slug())) !== Plugin::slug())
            return $file;

        $page_parts = explode('--', $plugin_page);
        if (count($page_parts) > 1)
        {
            $plugin_page = $page_parts[0];
        }
        else
            $plugin_page = Plugin::slug();

        return $file;
    }

    public static function res(string $relativePath): string
    {
        $base = rtrim(\ContentEgg\PLUGIN_RES, '/');
        $path = ltrim($relativePath, '/');

        return "{$base}/{$path}";
    }
}

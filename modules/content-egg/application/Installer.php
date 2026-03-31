<?php

namespace ContentEgg\application;

defined('\ABSPATH') || exit;

use ContentEgg\application\Plugin;
use ContentEgg\application\admin\import\AutoImportScheduler;
use ContentEgg\application\admin\import\PresetRepository;
use ContentEgg\application\admin\import\ProductImportScheduler;
use ContentEgg\application\admin\LicConfig;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\models\LinkIndexModel;

/**
 * Installer class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class Installer
{
    const API_URL = 'https://www.keywordrush.com/api/v1';
    const API_URL2 = '';
    const TIMEOUT   = 15;

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null)
            self::$instance = new self;

        return self::$instance;
    }

    private function __construct()
    {

        if (!empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'plugins.php')
        {
            \add_action('admin_init', array($this, 'requirements'), 0);
        }

        \add_action('admin_init', array($this, 'upgrade'));
        \add_action('admin_init', array($this, 'redirect_after_activation'));
    }

    static public function dbVesrion()
    {
        return Plugin::db_version;
    }

    static function getApiUrl()
    {
        return self::API_URL;
    }

    public static function activate()
    {
        if (!\current_user_can('activate_plugins'))
        {
            return;
        }

        self::requirements();

        \add_option(Plugin::slug . '_do_activation_redirect', true);
        \add_option(Plugin::slug . '_first_activation_date', time());
        self::upgradeTables();

        if (!Plugin::isFree())
        {
            SystemScheduler::addScheduleEvent('weekly', time() + rand(259200, 604800));
        }

        MaintenanceScheduler::activate();
        AutoblogScheduler::maybeAddScheduleEvent();
        ProductPrefillScheduler::maybeAddScheduleEvent();
        ProductImportScheduler::maybeAddScheduleEvent();
        AutoImportScheduler::maybeAddScheduleEvent();

        PresetRepository::maybeInstallBuiltInPresets();
    }

    public static function deactivate()
    {
        MaintenanceScheduler::deactivate();
        ModuleUpdateScheduler::clearScheduleEvent();
        AutoblogScheduler::clearScheduleEvent();
        ProductPrefillScheduler::clearScheduleEvent();
        ProductImportScheduler::clearScheduleEvent();
        AutoImportScheduler::clearScheduleEvent();
        if (!Plugin::isFree())
            SystemScheduler::clearScheduleEvent();
    }

    public static function requirements()
    {
        $php_min_version = '7.4.33';
        $extensions = array(
            'simplexml',
            'mbstring',
            'hash',
        );

        $errors = array();
        $name = get_file_data(\ContentEgg\PLUGIN_FILE, array('Plugin Name'), 'plugin');

        global $wp_version;
        if (version_compare(Plugin::wp_requires, $wp_version, '>'))
            $errors[] = sprintf('You are using WordPress %s. <em>%s</em> requires at least <strong>WordPress %s</strong>.', $wp_version, $name[0], Plugin::wp_requires);

        $php_current_version = phpversion();
        if (version_compare($php_min_version, $php_current_version, '>'))
            $errors[] = sprintf('PHP is installed on your server %s. <em>%s</em> requires at least <strong>PHP %s</strong>.', $php_current_version, $name[0], $php_min_version);

        foreach ($extensions as $extension)
        {
            if (!extension_loaded($extension))
                $errors[] = sprintf('Requires extension <strong>%s</strong>.', $extension);
        }
        if (!$errors)
            return;
        unset($_GET['activate']);
        \deactivate_plugins(\plugin_basename(\ContentEgg\PLUGIN_FILE));
        $e = sprintf('<div class="error"><p>%1$s</p><p><em>%2$s</em> ' . 'cannot be installed!' . '</p></div>', join('</p><p>', $errors), $name[0]);
        \wp_die(wp_kses_post($e));
    }

    public static function uninstall()
    {
        if (!\current_user_can('activate_plugins'))
            return;

        \delete_option(Plugin::slug . '_db_version');
        \delete_option(Plugin::slug . '_env_install');
        \delete_option(Plugin::getShortSlug() . '_sys_status');
        \delete_option(Plugin::getShortSlug() . '_sys_deadline');
        if (Plugin::isPro())
        {
            \delete_option(LicConfig::getInstance()->option_name());
        }
    }

    public static function upgrade()
    {
        $db_version = \get_option(Plugin::slug . '_db_version');

        if ((int) $db_version >= (int) self::dbVesrion())
            return;

        self::upgradeTables();

        if ($db_version < 50)
            self::upgrade_v50();

        if ($db_version < 53)
            self::upgrade_v53();

        if ($db_version < 56)
            self::upgrade_v56();

        if ($db_version < 57)
            self::upgrade_v57();

        if ($db_version < 80)
            self::upgrade_v80();

        if ($db_version < 83)
            self::upgrade_v83();

        if ($db_version < 86)
            self::upgrade_v86();

        if ($db_version < 88)
            self::upgrade_v88();

        \update_option(Plugin::slug . '_db_version', self::dbVesrion());
    }

    private static function upgradeTables()
    {
        $models = array('AutoblogModel', 'PriceHistoryModel', 'PriceAlertModel', 'ProductModel', 'PrefillQueueModel', 'ImportQueueModel', 'AutoImportRuleModel', 'ProductMapModel', 'LinkIndexModel', 'LinkClicksDailyModel');
        $sql = '';
        foreach ($models as $model)
        {
            $m = "\\ContentEgg\\application\\models\\" . $model;
            $sql .= $m::model()->getDump();
            $sql .= "\r\n";
        }
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);
    }

    private static function upgrade_v50()
    {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cegg_awin_product');
    }

    private static function upgrade_v53()
    {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cegg_daisycon_product');
    }

    private static function upgrade_v56()
    {
        ModuleUpdateScheduler::clearScheduleEvent();
        ModuleUpdateScheduler::addScheduleEvent('ten_min');
    }

    private static function upgrade_v57()
    {
        if (!Plugin::isFree())
            SystemScheduler::addScheduleEvent('weekly', time() + rand(259200, 604800));
    }

    private static function upgrade_v80()
    {
        PresetRepository::maybeInstallBuiltInPresets();
    }

    private static function upgrade_v83()
    {
        MaintenanceScheduler::activate();
    }

    private static function upgrade_v86()
    {
        LocalRedirector::flushRules();

        if (!wp_next_scheduled('cegg_link_index_backfill_once'))
        {
            wp_schedule_single_event(time() + 5, 'cegg_link_index_backfill_once', ['redirect', null]);
        }
    }

    private static function upgrade_v88()
    {
        $module_ids = array_keys(ModuleManager::getInstance()->getContentModules(false));
        foreach ($module_ids as $module_id)
        {
            LinkIndexModel::model()->deleteByModule($module_id);
        }
    }

    public function redirect_after_activation()
    {
        if (\get_option(Plugin::slug . '_do_activation_redirect', false))
        {
            \delete_option(Plugin::slug . '_do_activation_redirect');
            \wp_safe_redirect(\get_admin_url(\get_current_blog_id(), 'admin.php?page=' . Plugin::slug));
        }
    }

    public static function apiRequest(array $body = array())
    {
        $api_urls = array_filter([static::API_URL, static::API_URL2]);

        foreach ($api_urls as $url)
        {
            $response = function_exists('curl_version') && function_exists('curl_exec')
                ? static::requestWithCurl($url, $body)
                : static::requestWithWpRemote($url, $body);

            if (false === $response)
            {
                continue;
            }

            if ($response['code'] >= 200 && $response['code'] <= 404 && '' !== $response['body'])
            {
                return $response;
            }
        }

        return false;
    }

    protected static function requestWithCurl($url, array $body)
    {
        $ch = \curl_init($url);

        \curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => \http_build_query($body, '', '&'),
            CURLOPT_USERAGENT      => Plugin::getName() . '/' . Plugin::version() . '; ' . \get_bloginfo('url'),
            CURLOPT_TIMEOUT        => static::TIMEOUT,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw  = \curl_exec($ch);
        $code = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (\curl_errno($ch))
        {
            \curl_close($ch);
            return false;
        }

        \curl_close($ch);

        return [
            'body' => $raw,
            'code' => (int) $code,
        ];
    }

    protected static function requestWithWpRemote($url, array $body)
    {
        $response = wp_remote_post($url, [
            'body'       => $body,
            'timeout'    => static::TIMEOUT,
            'user-agent' => Plugin::getName() . '/' . Plugin::version() . '; ' . get_bloginfo('url'),
        ]);

        if (is_wp_error($response))
        {
            return false;
        }

        return [
            'body' => wp_remote_retrieve_body($response),
            'code' => (int) wp_remote_retrieve_response_code($response),
        ];
    }
}

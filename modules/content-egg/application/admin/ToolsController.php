<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

use ContentEgg\application\models\PriceAlertModel;
use ContentEgg\application\helpers\FileHelper;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\components\ContentManager;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\AdminHelper;
use ContentEgg\application\helpers\LogoHelper;;

/**
 * ToolsController class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link httsp://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ToolsController
{

    const slug = 'content-egg-tools';

    public function __construct()
    {
        \add_action('admin_menu', array($this, 'actionHandler'));
    }

    public function actionHandler(): void
    {

        if (empty($GLOBALS['pagenow']) || $GLOBALS['pagenow'] !== 'admin.php')
        {
            return;
        }

        $page = isset($_REQUEST['page']) ? sanitize_key(wp_unslash($_REQUEST['page'])) : '';
        if ($page !== 'content-egg-tools')
        {
            return;
        }

        if (! current_user_can('manage_options'))
        {
            wp_die(
                'You do not have sufficient permissions to perform this action.',
                'Access Denied',
                ['response' => 403]
            );
        }

        $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '';

        if (! $action)
        {
            return;
        }

        $routes = [
            'subscribers-export'      => 'actionSubscribersExport',
            'offer-urls-export'       => 'actionOfferUrlsExport',
            'feed-export'             => 'actionFeedDataExport',
            'feed-reset'             => 'actionFeedDataReset',
            'export-module-settings'  => 'actionExportModuleSettings',
            'import-module-settings'  => 'actionImportModuleSettings',
            'export-plugin-settings'  => 'actionExportPluginSettings',
            'import-plugin-settings'  => 'actionImportPluginSettings',
            'clear-logo-cache'  => 'actionClearLogoCache',
        ];

        if (empty($routes[$action]) || ! method_exists($this, $routes[$action]))
        {
            return;
        }

        check_admin_referer("cegg_{$action}");

        call_user_func([$this, $routes[$action]]);
    }

    private function actionSubscribersExport()
    {
        if (!\current_user_can('administrator'))
            die('You do not have permission to view this page.');

        $where = array();
        if (!empty($_GET['active_only']) && (bool) $_GET['active_only'])
            $where = array('where' => 'status = ' . PriceAlertModel::STATUS_ACTIVE);
        $subscribers = $total_price_alerts = PriceAlertModel::model()->findAll($where);

        $csv_arr = array();
        $ignore_fields = array('activkey', 'email', 'status');
        foreach ($subscribers as $subscriber)
        {
            $csv_line = array();
            $csv_line['email'] = $subscriber['email'];
            $csv_line['status'] = PriceAlertModel::getStatus($subscriber['status']);

            foreach ($subscriber as $key => $s)
            {
                if (in_array($key, $ignore_fields))
                    continue;
                $csv_line[$key] = $s;
            }

            $unsubscribe_all_url = \add_query_arg(array(
                'ceggaction' => 'unsubscribe',
                'email' => urlencode($subscriber['email']),
                'key' => urlencode($subscriber['activkey']),
            ), \get_site_url());
            $delete_url = \add_query_arg(array(
                'ceggaction' => 'delete',
                'email' => urlencode($subscriber['email']),
                'key' => urlencode($subscriber['activkey']),
            ), \get_site_url());

            $csv_line['unsubscribe_url'] = $unsubscribe_all_url;
            $csv_line['delete_url'] = $delete_url;

            $csv_arr[] = $csv_line;
        }
        $filename = 'subscribers-' . date('d-m-Y') . '.csv';
        FileHelper::sendDownloadHeaders($filename);
        echo FileHelper::array2Csv($csv_arr); // phpcs:ignore
        exit;
    }

    private function actionOfferUrlsExport()
    {
        if (!\current_user_can('administrator'))
            die('You do not have permission to view this page.');

        if (isset($_GET['module']))
            $module_id = TextHelper::clear(\sanitize_text_field(wp_unslash($_GET['module'])));
        else
            die('Module param can not be empty.');

        if (!ModuleManager::getInstance()->moduleExists($module_id))
            die('The module does not exist.');

        global $wpdb;

        $sql = $wpdb->prepare('SELECT * FROM ' . $wpdb->postmeta . ' WHERE meta_key LIKE "%s"', $wpdb->esc_like(ContentManager::META_PREFIX_DATA . $module_id));

        $results = $wpdb->get_results($sql, \ARRAY_A);

        $csv_arr = array();
        foreach ($results as $result)
        {
            if (!$data = unserialize($result['meta_value']))
                continue;

            $csv_line = array();
            $csv_line['post_id'] = $result['post_id'];
            foreach ($data as $d)
            {
                $csv_line['title'] = $d['title'];
                $csv_line['price'] = $d['price'];
                $csv_line['priceOld'] = $d['priceOld'];
                $csv_line['currencyCode'] = $d['currencyCode'];
                $csv_line['url'] = $d['url'];
                $csv_line['orig_url'] = $d['orig_url'];
                $csv_line['img'] = $d['img'];
                $csv_arr[] = $csv_line;
            }
        }
        $filename = $module_id . '-data-' . date('d-m-Y') . '.csv';
        FileHelper::sendDownloadHeaders($filename);
        echo FileHelper::array2Csv($csv_arr); // phpcs:ignore
        exit;
    }

    private function actionFeedDataExport()
    {
        if (!\current_user_can('administrator'))
            die('You do not have permission to view this page.');

        if (isset($_GET['module']))
            $module_id = TextHelper::clear(\sanitize_text_field(wp_unslash($_GET['module'])));
        else
            die('Module param can not be empty.');

        if (!ModuleManager::getInstance()->moduleExists($module_id))
            die('The module does not exist.');

        if (!empty($_GET['field']))
            $field = sanitize_key(wp_unslash($_GET['field']));
        else
            $field = 'url';

        $module = ModuleManager::getInstance()->factory($module_id);
        $model = $module->getProductModel();

        if ($field == 'ean')
            $results = $model->getEans();
        elseif ($field == 'ean_dublicate')
            $results = $model->getDublicateEans();
        else
            $results = $model->getAllUrls();

        $filename = $module->getName() . '-' . $field . '-' . date('d-m-Y') . '.txt';
        FileHelper::sendDownloadHeaders($filename);
        $results = array_map('sanitize_text_field', $results);
        echo join("\r\n", $results); // phpcs:ignore
        exit;
    }
    private function actionFeedDataReset()
    {
        if (!\current_user_can('administrator'))
            die('You do not have permission to view this page.');

        if (isset($_GET['module']))
            $module_id = TextHelper::clear(\sanitize_text_field(wp_unslash($_GET['module'])));
        else
            die('Module param can not be empty.');

        if (!ModuleManager::getInstance()->moduleExists($module_id))
            die('The module does not exist.');

        $module = ModuleManager::getInstance()->factory($module_id);

        if (!$module->isFeedModule())
            die('This module does not support data reset.');

        $config = $module->getConfigInstance();
        $is_active = $config->option('is_active');
        $module->refreshFeedData($is_active);

        $redirect_url = admin_url(sprintf('admin.php?page=content-egg-modules--%s', $module_id));
        $redirect_url = AdminNotice::add2Url($redirect_url, 'feed_reseted', 'success');

        AdminHelper::redirect($redirect_url);
    }

    private static function actionExportModuleSettings()
    {
        if (! current_user_can('manage_options'))
        {
            wp_die('You do not have sufficient permissions to export settings.', 403);
        }

        $settings = ModuleManager::getInstance()->getOptionsList();

        $json = wp_json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (false === $json)
        {
            wp_die('Export failed: could not encode settings.');
        }

        $site_slug = sanitize_title(wp_parse_url(home_url(), PHP_URL_HOST));
        $filename  = sprintf(
            '%s-module-settings-%s.json',
            $site_slug,
            gmdate('Ymd-His')
        );

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $json;
        exit;
    }

    private function actionImportModuleSettings(): void
    {
        if (! current_user_can('manage_options'))
        {
            wp_die('You do not have sufficient permissions to import settings.', 'Access Denied', ['response' => 403]);
        }

        if (empty($_FILES['settings_file']['tmp_name']) || ! is_uploaded_file($_FILES['settings_file']['tmp_name']))
        {
            wp_die('No file uploaded or upload failed.', 'Import Error', ['response' => 400]);
        }

        $json = file_get_contents($_FILES['settings_file']['tmp_name']);
        if (!$json)
        {
            wp_die('Failed to read uploaded file.', 'Import Error', ['response' => 400]);
        }

        $settings = json_decode($json, true);
        if (!is_array($settings))
        {
            wp_die('Invalid JSON format.', 'Import Error', ['response' => 400]);
        }

        $redirect_url = \admin_url('admin.php?page=content-egg-modules');

        if (ModuleManager::getInstance()->importOptions($settings))
        {
            $redirect_url = AdminNotice::add2Url($redirect_url, 'module_settings_imported', 'success');
        }
        else
        {
            $redirect_url = AdminNotice::add2Url($redirect_url, 'settings_import_error', 'error');
        }

        AdminHelper::redirect($redirect_url);
    }

    private static function actionExportPluginSettings()
    {
        if (! current_user_can('manage_options'))
        {
            wp_die('You do not have sufficient permissions to export settings.', 403);
        }

        $settings[GeneralConfig::getInstance()->option_name()] = GeneralConfig::getInstance()->getOptionValues();

        $json = wp_json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (false === $json)
        {
            wp_die('Export failed: could not encode settings.');
        }

        $site_slug = sanitize_title(wp_parse_url(home_url(), PHP_URL_HOST));
        $filename  = sprintf(
            '%s-plugin-settings-%s.json',
            $site_slug,
            gmdate('Ymd-His')
        );

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $json;
        exit;
    }

    private function actionImportPluginSettings()
    {
        if (! current_user_can('manage_options'))
        {
            wp_die('You do not have sufficient permissions to import settings.', 'Access Denied', ['response' => 403]);
        }

        if (empty($_FILES['settings_file']['tmp_name']) || ! is_uploaded_file($_FILES['settings_file']['tmp_name']))
        {
            wp_die('No file uploaded or upload failed.', 'Import Error', ['response' => 400]);
        }

        $json = file_get_contents($_FILES['settings_file']['tmp_name']);
        if (!$json)
        {
            wp_die('Failed to read uploaded file.', 'Import Error', ['response' => 400]);
        }

        $settings = json_decode($json, true);
        if (!is_array($settings))
        {
            wp_die('Invalid JSON format.', 'Import Error', ['response' => 400]);
        }

        $redirect_url = \admin_url('admin.php?page=content-egg');

        if (GeneralConfig::getInstance()->importOptions($settings))
        {
            $redirect_url = AdminNotice::add2Url($redirect_url, 'plugin_settings_imported', 'success');
        }
        else
        {
            $redirect_url = AdminNotice::add2Url($redirect_url, 'settings_import_error', 'error');
        }

        AdminHelper::redirect($redirect_url);
    }

    private function actionClearLogoCache()
    {
        if (! current_user_can('manage_options'))
        {
            wp_die('You do not have sufficient permissions.', 'Access Denied', ['response' => 403]);
        }
        LogoHelper::purgeCachedLogos();

        $redirect_url = \admin_url('admin.php?page=content-egg');
        $redirect_url = AdminNotice::add2Url($redirect_url, 'plugin_purged_cached_logos', 'success');

        AdminHelper::redirect($redirect_url);
    }
}

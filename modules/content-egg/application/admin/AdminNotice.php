<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

/**
 * AdminNotice class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class AdminNotice
{
    const GET_NOTICE_PARAM = 'egg-notice';
    const GET_LEVEL_PARAM = 'egg-notice-level';
    const GET_ID_PARAM = 'egg-notice-id';

    protected static $instance = null;

    public function getMassages()
    {
        return array(
            'autoblog_saved' => __('Task for autoblogging is saved.', 'content-egg') . ' <a href="?page=content-egg-autoblog&action=run&id=%%ID%%&_wpnonce=' . \wp_create_nonce('cegg_autoblog_run') . '">' . __('Run now', 'content-egg') . '</a>',
            'autoblog_create_error' => __('While saving task error was occurred.', 'content-egg'),
            'autoblog_csv_file_error' => __('Error while handling file with keywords.', 'content-egg'),
            'autoblog_batch_created' => __('Tasks for autoblogging are saved.', 'content-egg') . ' %%ID%%.',
            'license_reset_error' => __('License can\'t be deactivated. Write to support of plugin.', 'content-egg'),
            'license_reset_success' => __('The license has been deactivated.', 'content-egg') . ' ' . __('You must deactivate and delete Content Egg from your current domain to use plugin on a new domain.', 'content-egg'),
            'module_deleted' => __('The module has been successfully deleted.', 'content-egg'),
            'prefill_stopped' => __('Prefill process was stopped.', 'content-egg'),
            'prefill_started' => __('Prefill has started and is running in the background using WP-Cron. You can safely close this page and return later to check the progress.', 'content-egg'),
            'module_settings_imported' => __('Module settings have been imported.', 'content-egg'),
            'plugin_settings_imported' => __('Plugin settings have been imported.', 'content-egg'),
            'settings_import_error' => __('Import failed: no valid settings were found. Please check your file and try again.', 'content-egg'),
            'feed_reseted' => __('The feed data has been reset. Reloading products in the background...', 'content-egg'),
            'plugin_purged_cached_logos' => __('Cached logos have been purged.', 'content-egg'),
            'product_import_stopped' => __('All pending product import tasks have been stopped.', 'content-egg'),
            'preset_saved'         => __('Preset saved successfully.', 'content-egg'),
            'preset_deleted'       => __('Preset deleted.', 'content-egg'),
            'preset_delete_error'  => __('You must have at least one preset. You cannot delete the last one.', 'content-egg'),
            'preset_in_use'         => __('Cannot delete this preset because it’s still used by active import jobs.', 'content-egg'),
            'import_truncate_success' => __('Cleared all completed and failed jobs from the import queue.', 'content-egg'),
            'import_restart_success' => __('All failed import jobs have been restarted.', 'content-egg'),
            'bulk_import_jobs_added' => __('Bulk import jobs have been added to the queue.', 'content-egg'),
            'feed_import_jobs_added' => __('Feed import jobs have been added to the queue.', 'content-egg'),
            'autoimport_rule_deleted' => __('Auto-import rule has been deleted.', 'content-egg'),
            'autoimport_rule_saved' => __('Auto-import rule has been saved.', 'content-egg'),
            'autoimport_rule_resumed' => __('Auto-import rule has been resumed.', 'content-egg'),
            'autoimport_rule_paused' => __('Auto-import rule has been paused.', 'content-egg'),
            'autoimport_rule_processed' => __('Auto-import rule has been processed.', 'content-egg'),
        );
    }

    public static function getInstance()
    {
        if (self::$instance == null)
            self::$instance = new self;

        return self::$instance;
    }

    private function __construct()
    {
        //$this->adminInit();
    }

    public function adminInit()
    {
        \add_action('admin_notices', array($this, 'displayNotice'));
    }

    public function getMessage($message_id = null)
    {
        if (!$message_id && !empty($_GET[self::GET_NOTICE_PARAM]))
            $message_id = sanitize_key(wp_unslash($_GET[self::GET_NOTICE_PARAM]));
        else
            return '';

        $all = $this->getMassages();
        if (!array_key_exists($message_id, $all))
            return '';

        $message = $all[$message_id];

        if (!empty($_GET[self::GET_ID_PARAM]))
        {
            $id = intval(wp_unslash($_GET[self::GET_ID_PARAM]));
            $message = str_replace('%%ID%%', (string)$id, $message);
        }

        return $message;
    }

    public function displayNotice()
    {
        if (empty($_GET[self::GET_NOTICE_PARAM]))
            return;

        $level = 'info';
        if (!empty($_GET[self::GET_LEVEL_PARAM]))
        {
            $level = sanitize_key(wp_unslash($_GET[self::GET_LEVEL_PARAM]));
            if (!in_array($level, array('error', 'warning', 'info', 'success')))
                $level = 'info';
        }
        echo '<div class="notice notice-' . esc_attr($level) . ' is-dismissible"><p>' . wp_kses_post($this->getMessage()) . '</p></div>';
    }

    public static function add2Url($url, $message, $level = null, $id = null)
    {
        $url = add_query_arg(self::GET_NOTICE_PARAM, $message, $url);
        if ($level)
            $url = add_query_arg(self::GET_LEVEL_PARAM, $level, $url);
        if ($id)
            $url = add_query_arg(self::GET_ID_PARAM, $id, $url);
        return $url;
    }
}

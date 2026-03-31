<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\AdminNotice;
use ContentEgg\application\admin\import\PresetRepository;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\AdminHelper;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\models\ImportQueueModel;



defined('ABSPATH') || exit;

/**
 * FeedImportTab class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class FeedImportTab extends AbstractTab
{

    public function __construct()
    {
        parent::__construct('feed', __('Feed Import', 'content-egg'));
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style('cegg-bootstrap5-full');
    }

    public function render(): void
    {

        if (
            'POST' === $_SERVER['REQUEST_METHOD']
            && isset($_POST['cegg_feed_import_nonce'])
            && wp_verify_nonce($_POST['cegg_feed_import_nonce'], 'cegg_feed_import')
        )
        {
            $this->handleFeedImport();
        }

        $preset_options    = PresetRepository::getList();
        $default_preset_id = (int) PresetRepository::getDefaultId();
        $feed_modules      = ModuleManager::getInstance()->getActiveFeedModules();

        PluginAdmin::getInstance()->render(
            'feed_import',
            compact('preset_options', 'default_preset_id', 'feed_modules')
        );
    }

    private function handleFeedImport(): void
    {
        if (! current_user_can('manage_options'))
        {
            wp_die('You are not allowed to run a feed import.');
        }

        $module_id  = TextHelper::clearId($_POST['import_module']   ?? '');
        $preset_id  = absint($_POST['preset_id']                    ?? 0);
        $months_raw = (int) ($_POST['schedule_offset']   ?? 0);

        if (!$preset_id || !$module_id)
        {
            wp_die('Invalid module or preset ID.');
        }

        $preset = PresetRepository::get($preset_id);
        if (!$preset)
        {
            wp_die('Preset not found.');
        }

        if (!ModuleManager::getInstance()->moduleExists($module_id))
        {
            wp_die('The module does not exist.');
        }

        $scheduled_at = 0;
        $months = in_array($months_raw, BulkImportTab::ALLOWED_MONTHS, true) ? $months_raw : 0;

        $module = ModuleManager::getInstance()->factory($module_id);
        $model = $module->getProductModel();
        $productUrls = $model->getAllUrls();
        if (empty($productUrls))
        {
            $productUrls = [];
        }

        $queue = ImportQueueModel::model();

        @set_time_limit(180);
        foreach ($productUrls as $url)
        {
            if ($months > 0)
            {
                $scheduled_at = BulkImportTab::randomDateWithinMonths($months)->format('Y-m-d H:i:s');
            }

            // Enqueue
            $queue->enqueue(
                $preset_id,
                $module_id,
                [],
                $url,
                0,
                $scheduled_at
            );
        }

        ProductImportScheduler::addScheduleEvent();

        $redirect_url = admin_url('admin.php?page=content-egg-product-import&tab=queue');
        $redirect_url = AdminNotice::add2Url($redirect_url, 'feed_import_jobs_added', 'success');

        AdminHelper::redirect($redirect_url);
    }
}

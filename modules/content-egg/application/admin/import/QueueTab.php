<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\admin\AdminNotice;
use ContentEgg\application\admin\import\AbstractTab;
use ContentEgg\application\admin\import\ProductImportScheduler;
use ContentEgg\application\admin\import\ProductImportService;
use ContentEgg\application\helpers\AdminHelper;
use ContentEgg\application\models\ImportQueueModel;
use ContentEgg\application\Plugin;;

defined('ABSPATH') || exit;

/**
 * QueueTab class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class QueueTab extends AbstractTab
{
    public function __construct()
    {
        parent::__construct('queue', __('Queue', 'content-egg'));
    }

    /**
     * Display Import-Queue status page in WP-Admin
     */
    public function render(): void
    {
        $this->handleAction();
    }

    public function handleAction()
    {
        if (!current_user_can('publish_posts'))
        {
            wp_die(
                'Sorry, you do not have sufficient permissions to access this page.',
                'Access Denied',
                ['response' => 403]
            );
        }

        $action = $_GET['action'] ?? '';

        $valid_actions = [
            'import_status' => [
                'method' => 'actionImportStatus',
            ],
            'import_stop' => [
                'method' => 'actioniImportStop',
                'nonce_action' => 'cegg_import_stop',
                'nonce_key' => 'cegg_import_stop_nonce',
                'method_type' => 'post',
            ],
            'import_run_once' => [
                'method' => 'actionImportRunOnce',
                'nonce_action' => !Plugin::isDevEnvironment() ? 'cegg_import_run_once' : '',
                'nonce_key' => 'cegg_import_run_once_nonce',
                'method_type' => 'post',
            ],
            'import_restart_failed' => [
                'method' => 'actionImportRestartFailed',
                'nonce_action' => 'cegg_import_restart_failed',
                'nonce_key' => 'cegg_import_restart_failed_nonce',
                'method_type' => 'post',
            ],
            'import_truncate' => [
                'method'       => 'actionImportTruncate',
                'nonce_action' => 'cegg_import_truncate',
                'nonce_key'    => '_wpnonce',
                'method_type'  => 'get',
            ],

        ];

        if (isset($valid_actions[$action]))
        {
            $config = $valid_actions[$action];

            if (!empty($config['nonce_action']))
            {
                $source = ($config['method_type'] ?? 'get') === 'post' ? $_POST : $_GET;
                if (empty($source[$config['nonce_key']]) || !wp_verify_nonce($source[$config['nonce_key']], $config['nonce_action']))
                {
                    wp_die(esc_html__('Security check failed.', 'content-egg'));
                }
            }

            $this->{$config['method']}();
            return;
        }

        $this->actionImportStatus();
    }

    private function actionImportStatus()
    {
        \wp_enqueue_style('cegg-bootstrap5-full');

        $queue = \ContentEgg\application\models\ImportQueueModel::model();

        // ---------------------------------------------------------------------
        // 1) Quick stats
        // ---------------------------------------------------------------------
        $stats = [
            'pending' => $queue->countByStatus('pending'),
            'working' => $queue->countByStatus('working'),
            'failed'  => $queue->countByStatus('failed'),
            'done'  => $queue->countByStatus('done'),
        ];

        $last_updated = $queue->getDb()->get_var(
            "SELECT MAX(updated_at) FROM {$queue->tableName()}"
        );

        // ---------------------------------------------------------------------
        // 2) Ensure the runner is scheduled while queue not empty
        // ---------------------------------------------------------------------
        if ($stats['pending'] || $stats['working'])
        {
            ProductImportScheduler::addScheduleEvent();
        }

        // ---------------------------------------------------------------------
        // 3) Build the list-table
        // ---------------------------------------------------------------------
        $table = new \ContentEgg\application\admin\import\ImportQueueTable($queue);
        $table->prepare_items();

        // ---------------------------------------------------------------------
        // 4) Additional indicators
        // ---------------------------------------------------------------------
        $is_in_progress  = ($stats['pending'] + $stats['working']) > 0;
        $is_cron_enabled = ! defined('DISABLE_WP_CRON') || ! DISABLE_WP_CRON;
        $total_count    = $stats['pending'] + $stats['working'] + $stats['failed'] + $stats['done'];

        // consider queue “stuck” if no row updated in N minutes
        $stuck_threshold_minutes = 15;
        $last_ts      = $last_updated ? strtotime($last_updated) : 0;
        $now          = current_time('timestamp');
        $is_stuck     = $is_in_progress && $last_ts && ($now - $last_ts) > $stuck_threshold_minutes * MINUTE_IN_SECONDS;

        // ---------------------------------------------------------------------
        // 5) Render admin view
        // ---------------------------------------------------------------------
        \ContentEgg\application\admin\PluginAdmin::getInstance()->render(
            'import_queue',
            [
                'stats'              => $stats,
                'last_updated'       => $last_updated,
                'table'              => $table,
                'is_in_progress'     => $is_in_progress,
                'is_cron_enabled'    => $is_cron_enabled,
                'is_possibly_stuck'  => $is_stuck,
                'failed_tasks_count' => $stats['failed'],
                'total_count'        => $total_count,
            ]
        );
    }

    private function actionImportRestartFailed()
    {
        $queue = ImportQueueModel::model();
        $queue->restartFailedJobs();

        if ($queue->isInProgress())
        {
            ProductImportScheduler::addScheduleEvent();
        }

        $redirect_url = \admin_url('admin.php?page=content-egg-product-import&tab=queue');
        $redirect_url = AdminNotice::add2Url($redirect_url, 'import_restart_success', 'success');

        AdminHelper::redirect($redirect_url);
    }

    private function actioniImportStop()
    {
        $queue = \ContentEgg\application\models\ImportQueueModel::model();
        $queue->clearPending();

        ProductImportScheduler::clearScheduleEvents();

        $redirect_url = \admin_url('admin.php?page=content-egg-product-import&tab=queue');
        $redirect_url = AdminNotice::add2Url($redirect_url, 'product_import_stopped', 'success');

        AdminHelper::redirect($redirect_url);
    }

    private function actionImportRunOnce()
    {
        @set_time_limit(300);

        if (!Plugin::isDevEnvironment())
        {
            wp_die('This action is only allowed in a development environment.');
        }

        $service = new ProductImportService();
        $service->processBatch(1);

        AdminHelper::redirect(admin_url('admin.php?page=content-egg-product-import&tab=queue'));
    }

    /**
     * Truncate all completed (done|failed) import jobs
     */
    private function actionImportTruncate(): void
    {
        $deleted = ImportQueueModel::model()->clearCompletedJobs();

        $url = add_query_arg([
            'page'      => 'content-egg-product-import',
            'tab'       => 'queue',
        ], admin_url('admin.php'));

        $url = AdminNotice::add2Url($url, 'import_truncate_success', 'success');

        AdminHelper::redirect($url);
    }
}

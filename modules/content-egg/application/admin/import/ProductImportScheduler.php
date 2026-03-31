<?php

namespace ContentEgg\application\admin\import;

defined('\ABSPATH') || exit;

use ContentEgg\application\models\ImportQueueModel;
use ContentEgg\application\Plugin;

/**
 * ProductImportScheduler class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */

class ProductImportScheduler
{
    const CRON_TAG_HEARTBEAT = 'cegg_import_failsafe_heartbeat';
    const CRON_TAG_BATCH = 'cegg_run_import_batch';
    const BATCH_SIZE = 3;

    public static function initAction()
    {
        self::initSchedule();

        add_action(self::CRON_TAG_HEARTBEAT, [__CLASS__, 'processImportBatch']);
        add_action(self::CRON_TAG_BATCH, [__CLASS__, 'processImportBatch']);
    }

    public static function addScheduleEvent($recurrence = 'ten_min')
    {
        if (!wp_next_scheduled(self::CRON_TAG_BATCH))
        {
            wp_schedule_single_event(time() + 5, self::CRON_TAG_BATCH);
        }

        if (!wp_next_scheduled(self::CRON_TAG_HEARTBEAT))
        {
            wp_schedule_event(time() + 60, $recurrence, self::CRON_TAG_HEARTBEAT);
        }
    }

    public static function getBatchSize()
    {
        return apply_filters('cegg_product_import_batch_size', self::BATCH_SIZE);
    }

    public static function initSchedule()
    {
        add_filter('cron_schedules', [__CLASS__, 'addSchedule']);
    }

    public static function addSchedule($schedules)
    {
        $schedules['ten_min'] = [
            'interval' => 600,
            'display'  => __('Every 10 minutes', 'content-egg'),
        ];
        return $schedules;
    }

    public static function processImportBatch()
    {
        // prevent multiple instances
        if (get_transient('cegg_product_import_batch_lock'))
        {
            return;
        }

        set_transient('cegg_product_import_batch_lock', 1, 15 * MINUTE_IN_SECONDS);

        try
        {
            @set_time_limit(1200);
            $service = new \ContentEgg\application\admin\import\ProductImportService();
            $service->processBatch(self::getBatchSize());
        }
        finally
        {
            delete_transient('cegg_product_import_batch_lock');
        }

        // If there are still posts to process, queue the next batch
        if (ImportQueueModel::model()->isInProgress())
        {
            if (!wp_next_scheduled(self::CRON_TAG_BATCH))
            {
                wp_schedule_single_event(time() + 5, self::CRON_TAG_BATCH);
            }
        }
        else
        {
            ProductImportScheduler::clearScheduleEvents();
        }
    }

    public static function clearScheduleEvent()
    {
        self::clearScheduleEvents();
    }

    public static function clearScheduleEvents()
    {
        if (wp_next_scheduled(self::CRON_TAG_HEARTBEAT))
        {
            wp_clear_scheduled_hook(self::CRON_TAG_HEARTBEAT);
        }

        if (wp_next_scheduled(self::CRON_TAG_BATCH))
        {
            wp_clear_scheduled_hook(self::CRON_TAG_BATCH);
        }

        delete_transient('cegg_product_import_batch_lock');
    }

    public static function maybeAddScheduleEvent()
    {
        $queue = ImportQueueModel::model();

        if ($queue->isInProgress())
        {
            ProductImportScheduler::addScheduleEvent();
        }
    }
}

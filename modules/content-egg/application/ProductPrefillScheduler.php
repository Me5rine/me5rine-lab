<?php

namespace ContentEgg\application;

defined('\ABSPATH') || exit;

use ContentEgg\application\models\PrefillQueueModel;;

/**
 * ProductPrefillScheduler class file
 *
 * Handles background processing of product prefill queue.
 * Supports both self-scheduling and periodic triggers.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */

class ProductPrefillScheduler
{
    const CRON_TAG_HEARTBEAT = 'cegg_prefill_failsafe_heartbeat';
    const CRON_TAG_BATCH = 'cegg_run_prefill_batch';
    const BATCH_SIZE = 3;

    public static function initAction()
    {
        self::initSchedule();

        add_action(self::CRON_TAG_HEARTBEAT, [__CLASS__, 'processPrefillBatch']);
        add_action(self::CRON_TAG_BATCH, [__CLASS__, 'processPrefillBatch']);
    }

    public static function addScheduleEvent($recurrence = 'ten_min', $timestamp = null)
    {
        if (!$timestamp)
            $timestamp = time() + 10;

        if (!wp_next_scheduled(self::CRON_TAG_HEARTBEAT))
        {
            wp_schedule_event($timestamp, $recurrence, self::CRON_TAG_HEARTBEAT);
        }
    }

    public static function getBatchSize()
    {
        return apply_filters('cegg_prefill_batch_size', self::BATCH_SIZE);
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

    public static function processPrefillBatch()
    {
        if (Plugin::isDevEnvironment())
        {
            return;
        }

        // prevent multiple instances
        if (get_transient('cegg_prefill_batch_lock'))
        {
            return;
        }

        set_transient('cegg_prefill_batch_lock', 1, 15 * MINUTE_IN_SECONDS);

        try
        {
            @set_time_limit(1200);
            $service = new \ContentEgg\application\components\ProductPrefillService();
            $service->processBatch(self::getBatchSize());
        }
        finally
        {
            delete_transient('cegg_prefill_batch_lock');
        }

        // If there are still posts to process, queue the next batch
        if (PrefillQueueModel::model()->isInProgress())
        {
            if (!wp_next_scheduled(self::CRON_TAG_BATCH))
            {
                wp_schedule_single_event(time() + 10, self::CRON_TAG_BATCH);
            }
        }
        else
        {
            self::clearScheduleEvents();
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

        delete_transient('cegg_prefill_batch_lock');
    }

    public static function maybeAddScheduleEvent()
    {
        $queue = \ContentEgg\application\models\PrefillQueueModel::model();

        if ($queue->isInProgress())
        {
            ProductPrefillScheduler::addScheduleEvent();
        }
    }
}

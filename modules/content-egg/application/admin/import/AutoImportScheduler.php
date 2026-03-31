<?php

namespace ContentEgg\application\admin\import;

defined('\ABSPATH') || exit;

use ContentEgg\application\models\AutoImportRuleModel;
use ContentEgg\application\Plugin;;

/**
 * Auto-Import Scheduler
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

final class AutoImportScheduler
{
    public const CRON_TAG_HEARTBEAT = 'cegg_autoimport_heartbeat';
    public const CRON_TAG_RUN       = 'cegg_autoimport_run';
    public const MAX_RULES_PER_PASS = 3;

    public static function initAction(): void
    {
        self::initSchedule();

        add_action(self::CRON_TAG_HEARTBEAT, [__CLASS__, 'kickOffRun']);
        add_action(self::CRON_TAG_RUN,       [__CLASS__, 'processDueRules']);
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

    public static function addScheduleEvent($recurrence = 'ten_min'): void
    {
        if (! wp_next_scheduled(self::CRON_TAG_RUN))
        {
            wp_schedule_single_event(time() + 30, self::CRON_TAG_RUN);
        }

        if (! wp_next_scheduled(self::CRON_TAG_HEARTBEAT))
        {
            wp_schedule_event(time() + 300, $recurrence, self::CRON_TAG_HEARTBEAT);
        }
    }

    /**
     * Heartbeat simply queues another single-shot run in case
     * something killed the last batch.
     */
    public static function kickOffRun(): void
    {
        if (! wp_next_scheduled(self::CRON_TAG_RUN))
        {
            wp_schedule_single_event(time() + 5, self::CRON_TAG_RUN);
        }
    }

    /* ---------------------------------------------------------
	   Core: process rules that are due
	--------------------------------------------------------- */
    public static function processDueRules(): void
    {
        // Simple transient lock (15 min) to avoid overlaps
        if (get_transient('cegg_autoimport_lock'))
        {
            return;
        }
        set_transient('cegg_autoimport_lock', 1, 15 * MINUTE_IN_SECONDS);

        $limit = (int) apply_filters('cegg_autoimport_rules_limit', self::MAX_RULES_PER_PASS);
        $limit = max(1, min($limit, 20));

        try
        {
            @set_time_limit(800);

            $service = new AutoImportServise();
            $service->processBatch($limit);
        }
        finally
        {
            delete_transient('cegg_autoimport_lock');
        }

        // If there are still due rules, queue another single event immediately
        if (AutoImportRuleModel::model()->hasDueRules())
        {
            wp_schedule_single_event(time() + 30, self::CRON_TAG_RUN);
        }
        elseif (!AutoImportRuleModel::model()->hasActiveRules())
        {
            self::clearScheduleEvents();
        }
    }

    /* ---------------------------------------------------------
	   Utilities
	--------------------------------------------------------- */

    public static function clearScheduleEvents(): void
    {
        wp_clear_scheduled_hook(self::CRON_TAG_HEARTBEAT);
        wp_clear_scheduled_hook(self::CRON_TAG_RUN);
        delete_transient('cegg_autoimport_lock');
    }

    public static function clearScheduleEvent()
    {
        self::clearScheduleEvents();
    }

    public static function maybeAddScheduleEvent()
    {
        $model = AutoImportRuleModel::model();
        if ($model->hasActiveRules())
        {
            self::addScheduleEvent();
        }
    }
}

<?php

namespace ContentEgg\application;

use ContentEgg\application\admin\ClicksMaintenance;
use ContentEgg\application\admin\ProductMapMaintenance;

defined('\ABSPATH') || exit;

/**
 * MaintenanceScheduler class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class MaintenanceScheduler
{
    public const HOOK_HOURLY = 'cegg_hourly_maintenance';
    public const HOOK_DAILY  = 'cegg_daily_maintenance';

    /** Wire handlers for cron hooks */
    public static function initAction(): void
    {
        add_action(self::HOOK_HOURLY, [__CLASS__, 'runHourly']);
        add_action(self::HOOK_DAILY,  [__CLASS__, 'runDaily']);
    }

    /** Schedule events */
    public static function activate(bool $network_wide = false): void
    {
        if (is_multisite() && $network_wide)
        {
            $site_ids = get_sites(['fields' => 'ids']);
            foreach ($site_ids as $site_id)
            {
                switch_to_blog($site_id);
                self::scheduleForCurrentBlog();
                restore_current_blog();
            }
        }
        else
        {
            self::scheduleForCurrentBlog();
        }
    }

    /** Clear events */
    public static function deactivate(bool $network_wide = false): void
    {
        if (is_multisite() && $network_wide)
        {
            $site_ids = get_sites(['fields' => 'ids']);
            foreach ($site_ids as $site_id)
            {
                switch_to_blog($site_id);
                wp_clear_scheduled_hook(self::HOOK_HOURLY);
                wp_clear_scheduled_hook(self::HOOK_DAILY);
                restore_current_blog();
            }
        }
        else
        {
            wp_clear_scheduled_hook(self::HOOK_HOURLY);
            wp_clear_scheduled_hook(self::HOOK_DAILY);
        }
    }

    private static function scheduleForCurrentBlog(): void
    {
        /*
        if (!wp_next_scheduled(self::HOOK_HOURLY))
        {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::HOOK_HOURLY);
        }
        */
        if (!wp_next_scheduled(self::HOOK_DAILY))
        {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', self::HOOK_DAILY);
        }
    }

    /** Prevent overlapping runs with a transient lock. */
    private static function acquireLock(string $name, int $ttl): bool
    {
        $key = 'cegg_lock_' . $name;
        if (get_transient($key))
        {
            return false;
        }
        // Reserve lock
        set_transient($key, 1, $ttl);
        return true;
    }

    private static function releaseLock(string $name): void
    {
        delete_transient('cegg_lock_' . $name);
    }

    /** Hourly runner */
    public static function runHourly(): void
    {
        if (!self::acquireLock('hourly', 10 * MINUTE_IN_SECONDS))
        {
            return;
        }

        if (function_exists('ignore_user_abort'))
        {
            ignore_user_abort(true);
        }
        if (function_exists('set_time_limit'))
        {
            @set_time_limit(60);
        }

        try
        {
            // ... maintenance tasks here ...
        }
        finally
        {
            self::releaseLock('hourly');
        }
    }

    /** Daily runner */
    public static function runDaily(): void
    {
        if (!self::acquireLock('daily', 30 * MINUTE_IN_SECONDS))
        {
            return;
        }

        if (function_exists('ignore_user_abort'))
        {
            ignore_user_abort(true);
        }
        if (function_exists('set_time_limit'))
        {
            @set_time_limit(300);
        }

        try
        {
            ProductMapMaintenance::garbageCollect();
            ClicksMaintenance::runRetention();
        }
        catch (\Throwable $e)
        {
            // log
        }
        finally
        {
            self::releaseLock('daily');
        }
    }
}

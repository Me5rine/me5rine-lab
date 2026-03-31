<?php

namespace ContentEgg\application;

use ContentEgg\application\components\LinkIndexBackfiller;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\models\LinkClicksDailyModel;
use ContentEgg\application\models\LinkIndexModel;



defined('\ABSPATH') || exit;

/**
 * LinkIndexScheduler class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class LinkIndexScheduler
{

    public static function initAction(): void
    {
        add_action('cegg_link_index_backfill_once', [__CLASS__, 'runIndex'], 10, 2);
        add_action('cegg_link_index_delete_module', [__CLASS__, 'runDelete'], 10, 1);
    }

    /**
     * Run the link index backfill.
     *
     * @param string      $mode     'direct' or 'redirect' (default 'redirect')
     * @param string[]|null $modules Optional list of module IDs to limit the scan
     */
    public static function runIndex(string $mode = 'redirect', $modules = null): void
    {
        $mode = in_array($mode, ['direct', 'redirect'], true) ? $mode : 'redirect';

        // Normalize / validate modules (null = all)
        $onlyModules = self::normalizeModulesArg($modules);
        if ($onlyModules !== null)
        {
            $valid = array_keys(ModuleManager::getInstance()->getAffiliateParsers(true, true));
            $onlyModules = array_values(array_intersect($onlyModules, $valid));
            if (!$onlyModules)
            {
                return;
            }
        }

        // Lock per mode + module set
        $lockSeed = 'mode=' . $mode . ';mods=' . ($onlyModules ? implode(',', $onlyModules) : 'ALL');
        $lock = 'cegg_link_index_backfill_lock_' . md5($lockSeed);

        if (get_transient($lock) && !Plugin::isDevEnvironment()) return;
        set_transient($lock, 1, 15 * MINUTE_IN_SECONDS);

        if (function_exists('wp_raise_memory_limit')) wp_raise_memory_limit('admin');
        if (function_exists('set_time_limit')) @set_time_limit(300);

        try
        {
            $bf = new LinkIndexBackfiller($onlyModules);
            $bf->scanProducts(['mode' => $mode]); // honors 'direct' vs 'redirect'
        }
        catch (\Throwable $e)
        {
            error_log('[Content Egg] LinkIndexBackfiller error: ' . $e->getMessage());
        }
        finally
        {
            delete_transient($lock);
        }
    }

    /**
     * Cron handler: delete index rows for given module ID(s).
     * @param mixed $modules Array of module IDs, single string, or null
     */
    public static function runDelete($modules = null): void
    {
        $mods = self::normalizeModulesArg($modules);
        if ($mods === null || !$mods)
        {
            return;
        }

        foreach ($mods as $moduleId)
        {
            $moduleId = (string) $moduleId;
            $lock = 'cegg_link_index_delete_lock_' . md5($moduleId);
            if (get_transient($lock))
            {
                continue; // already running
            }
            set_transient($lock, 1, 10 * MINUTE_IN_SECONDS);

            try
            {
                LinkClicksDailyModel::model()->deleteByModule($moduleId);
                LinkIndexModel::model()->deleteByModule($moduleId);
            }
            catch (\Throwable $e)
            {
                error_log('[Content Egg] LinkIndex/LinkClicksDaily deleteByModule failed for ' . $moduleId . ': ' . $e->getMessage());
            }
            finally
            {
                delete_transient($lock);
            }
        }
    }

    private static function normalizeModulesArg($modules): ?array
    {
        if ($modules === null) return null; // all modules
        if (is_string($modules) && $modules !== '') return [$modules];
        if (is_array($modules))
        {
            return $modules;
        }
        return null;
    }
}

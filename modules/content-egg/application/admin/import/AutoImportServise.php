<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\components\ContentManager;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\PostHelper;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\models\AutoImportRuleModel;
use ContentEgg\application\models\ImportQueueModel;
use ContentEgg\application\Plugin;;

defined('ABSPATH') || exit;

/**
 * AutoImportServise class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link    https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
final class AutoImportServise
{
    /** Pause the rule after this many consecutive errors */
    private const MAX_RULE_CONSECUTIVE_ERRORS = 5;

    /** In-memory log for the current run */
    private array $log = [];

    /* ------------------------------------------------------------------
     * Batch runner
     * ------------------------------------------------------------------*/
    /** Execute up to $limit due rules. */
    public function processBatch(int $limit = 3): void
    {
        $rules = AutoImportRuleModel::model()->findDueRules($limit);
        if (!$rules)
        {
            return;
        }

        foreach ($rules as $idx => $rule)
        {
            if ($idx)
            {
                sleep(1);
            }
            $this->processRule($rule);
        }
    }

    /* ------------------------------------------------------------------
     * Single-rule processing
     * ------------------------------------------------------------------*/
    /** Process one rule end-to-end; may be called standalone. */
    public function processRule(array $rule): void
    {
        $this->resetLog();

        try
        {
            /* --- pre-run stop check --- */
            $stop = $this->getStopReason($rule);
            if ($stop !== null)
            {
                $this->resetLog();
                $this->addLog($stop);
                AutoImportRuleModel::model()->markFinished($rule['id']);
                $this->addLog(__('Rule finished.', 'content-egg'));
                return;
            }

            /* bump schedule early  */
            AutoImportRuleModel::model()->bumpNextRunAt($rule['id']);

            /* --- pick keyword --- */
            $keywords = KeywordCollection::fromJson($rule['keywords_json'] ?? '[]');
            if (!$keywords)
            {
                throw new \RuntimeException(__('No keywords configured.', 'content-egg'));
            }
            $kwObj = KeywordCollection::getNextKeyword($keywords);
            if ($kwObj === null)
            {
                $this->addLog(__('All keywords are disabled.', 'content-egg'));
                $this->addLog(__('Rule finished.', 'content-egg'));
                AutoImportRuleModel::model()->markFinished($rule['id']);
                return;
            }
            $this->addLog(sprintf(__('Using keyword: %s', 'content-egg'), $kwObj->getKeyword()));

            /* --- run import on that keyword --- */
            $count = $this->importKeyword($rule, $kwObj);
            $kwObj->touch($count);

            /* --- handle empty run streak --- */
            $maxEmpty = (int) $rule['max_keyword_no_products'];
            if ($count === 0 && $maxEmpty)
            {
                $runs = $kwObj->getConsecutiveNoProductsFound();
                $this->addLog(sprintf(__('No new products found (run %d)', 'content-egg'), $runs));
                if ($runs >= $maxEmpty)
                {
                    $kwObj->disable();
                    $this->addLog(sprintf(__('Keyword disabled after %d empty runs', 'content-egg'), $runs));
                }
            }

            /* --- persist keywords + stats --- */
            $update = [
                'id'                 => $rule['id'],
                'keywords_json'      => KeywordCollection::toJson($keywords),
                'consecutive_errors' => 0,
            ];
            if ($count > 0)
            {
                $update['consecutive_no_products'] = 0;
                $update['last_success_at'] = current_time('mysql');
                $update['post_count']      = (int) $rule['post_count'] + $count;
            }
            else
            {
                $update['consecutive_no_products'] = (int) $rule['consecutive_no_products'] + 1;
            }
            AutoImportRuleModel::model()->save($update);

            /* --- post-run stop check --- */
            $fresh = AutoImportRuleModel::model()->findByID($rule['id']) ?: $rule;
            $stop2 = $this->getStopReason($fresh);
            if ($stop2 !== null)
            {
                $this->resetLog();
                $this->addLog($stop2);
                $this->addLog(__('Rule finished.', 'content-egg'));
                AutoImportRuleModel::model()->markFinished($rule['id']);
            }
        }
        catch (\Throwable $e)
        {
            $this->handleRuleError($rule, $e);
        }
        finally
        {
            // always persist log history
            AutoImportRuleModel::model()->saveLogHistory($rule['id'], $this->log);
        }
    }

    /* ------------------------------------------------------------------
     * Keyword import
     * ------------------------------------------------------------------*/
    private function importKeyword(array $rule, AutoImportKeyword $kwObj): int
    {
        $products = $this->fetchProductDynamically($rule['module_id'], $kwObj->getKeyword());
        $found    = count($products);
        $this->addLog(sprintf(__('Products found: %d', 'content-egg'), $found));
        if (!$found)
        {
            return 0;
        }

        $queue   = ImportQueueModel::model();
        $queued  = $dupPost = $dupJob = 0;
        foreach ($products as $p)
        {
            if (empty($p['unique_id']))
            {
                continue;
            }
            if (PostHelper::getPostIdByUniqueId($p['unique_id']))
            {
                $dupPost++;
                continue;
            }
            if (ImportQueueModel::model()->existsJobByUniqueId($p['unique_id']))
            {
                $dupJob++;
                continue;
            }
            $queue->enqueue((int) $rule['preset_id'], $rule['module_id'], $p);
            $queued++;
        }
        if ($dupPost)
        {
            $this->addLog(sprintf(__('Skipped existing posts: %d', 'content-egg'), $dupPost));
        }
        if ($dupJob)
        {
            $this->addLog(sprintf(__('Skipped queued items: %d', 'content-egg'), $dupJob));
        }
        if ($queued)
        {
            $this->addLog(sprintf(__('New items queued: %d', 'content-egg'), $queued));

            ProductImportScheduler::addScheduleEvent();
        }

        return $queued;
    }

    /* ------------------------------------------------------------------
     * Remote fetch
     * ------------------------------------------------------------------*/
    protected function fetchProductDynamically(string $moduleId, string $keyword): array
    {
        if (! $moduleId)
        {
            throw new \RuntimeException(esc_html__('No module ID provided.', 'content-egg'));
        }

        if (! $keyword)
        {
            throw new \RuntimeException(esc_html__('No keyword provided.', 'content-egg'));
        }

        $parser = ModuleManager::getInstance()->parserFactory($moduleId);
        $cfg    = $parser->getConfigInstance();

        if ($parser->isAeParser())
            $search_results = 3;
        else
            $search_results = 10;

        $search_results = (int) apply_filters('content_egg_auto_import_search_results', $search_results, $moduleId, $keyword);
        $search_results = min($search_results, 100);
        $opts   = ['entries_per_page' => $search_results];

        $sortByNewestParam = $parser->getSortByNewestParamMap();
        if ($sortByNewestParam)
        {
            $opts = array_merge($opts, $sortByNewestParam);
        }

        $cfg->applyCustomOptions($opts);
        $raw = $parser->doMultipleRequests($keyword);
        return ContentManager::dataPresavePrepare($raw, $moduleId, 0) ?: [];
    }

    /* ------------------------------------------------------------------
     * Error / stop helpers
     * ------------------------------------------------------------------*/
    private function handleRuleError(array $rule, \Throwable $e): void
    {
        $msg = Plugin::isDevEnvironment()
            ? $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
            : TextHelper::truncate($e->getMessage(), 100);
        $this->addLog(sprintf(__('Error: %s', 'content-egg'), $msg));

        $errors = (int) $rule['consecutive_errors'] + 1;
        $data   = [
            'id'                 => $rule['id'],
            'consecutive_errors' => $errors,
        ];

        $limit = self::getMaxRuleConsecutiveErrors();
        if ($errors >= $limit)
        {
            $data['status'] = AutoImportRuleModel::STATUS_PAUSED;
            $this->addLog(sprintf(__('Rule paused after %d consecutive errors.', 'content-egg'), $limit));
        }
        else
        {
            $this->addLog(sprintf(__('Consecutive errors: %d', 'content-egg'), $errors));
        }
        AutoImportRuleModel::model()->save($data);
    }

    private static function getMaxRuleConsecutiveErrors(): int
    {
        return (int) apply_filters('content_egg_auto_import_max_rule_consecutive_errors', self::MAX_RULE_CONSECUTIVE_ERRORS);
    }

    /**
     * Determine why (if at all) a rule should stop.
     */
    private function getStopReason(array $r): ?string
    {
        // 1) Total imports cap
        if (
            !empty($r['stop_after_imports']) &&
            (int)$r['post_count'] >= (int)$r['stop_after_imports']
        )
        {
            return sprintf(
                __('Import limit reached (%d/%d).', 'content-egg'),
                (int)$r['post_count'],
                (int)$r['stop_after_imports']
            );
        }

        // 2) Lifetime cap (days since creation)
        if (!empty($r['stop_after_days']) && !empty($r['created_at']))
        {
            $created = strtotime($r['created_at']);
            $days    = (int)$r['stop_after_days'];
            if (
                $created &&
                time() >= $created + ($days * DAY_IN_SECONDS)
            )
            {
                return sprintf(
                    __('Lifetime limit reached (%d days).', 'content-egg'),
                    $days
                );
            }
        }

        // 3) No-products streak
        if (
            !empty($r['stop_if_no_new_results'])
            && isset($r['consecutive_no_products'])
        )
        {
            $limit  = (int)$r['stop_if_no_new_results'];
            $streak = (int)$r['consecutive_no_products'];
            if ($streak >= $limit)
            {
                return sprintf(
                    __('No new products found in the last %d runs.', 'content-egg'),
                    $streak
                );
            }
        }

        return null;
    }

    private function addLog(string $msg): void
    {
        $this->log[] = $msg;
    }

    private function resetLog(): void
    {
        $this->log = [];
    }
}

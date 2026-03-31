<?php

namespace ContentEgg\application\components;

use ContentEgg\application\helpers\ClickStatsHelper;
use ContentEgg\application\models\LinkIndexModel;



defined('\ABSPATH') || exit;

/**
 * LinkIndexBackfiller class file
 *
 * Backfills the wp_cegg_link_index table from stored Content Egg product meta.
 *
 * Modes:
 *  - default (redirect): scans modules where "set_local_redirect" is ON.
 *  - direct: scans modules where "set_local_redirect" is OFF (for non-redirect click tracking).
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class LinkIndexBackfiller
{
    /** @var LinkIndexModel */
    private $index;

    /** Limit to these modules (optional) */
    private ?array $onlyModules = null;

    public function __construct(?array $onlyModules = null)
    {
        $this->index = LinkIndexModel::model();
        $this->onlyModules = $onlyModules;
    }

    // =========================
    // Public API (Batch Runner)
    // =========================

    /**
     * Scan stored product meta and (up)insert rows into the link index.
     *
     * @param array{mode?:string} $args  e.g. ['mode' => 'direct'] or ['mode' => 'redirect']
     */
    public function scanProducts(array $args = []): void
    {
        global $wpdb;
        $db = $wpdb;

        // 1) Resolve which modules to scan based on mode
        $module_ids = $this->resolveModuleIdsForScan($args);
        if (empty($module_ids))
        {
            return;
        }

        // 2) Build postmeta keys for those modules
        $meta_keys = [];
        foreach ($module_ids as $module_id)
        {
            $meta_keys[] = ContentManager::META_PREFIX_DATA . $module_id;
        }
        if (empty($meta_keys))
        {
            return;
        }

        // 3) Batch through postmeta rows for those keys (include ALL posts, any post_status)
        $per_page = 250;
        $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));

        $sql_base = "
            FROM {$db->postmeta} pm
            INNER JOIN {$db->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key IN ($placeholders)
        ";

        // Count
        $count_sql = $db->prepare("SELECT COUNT(*) $sql_base", $meta_keys);
        $total = (int) $db->get_var($count_sql);
        if ($total <= 0)
        {
            return;
        }

        // Page
        for ($offset = 0; $offset < $total; $offset += $per_page)
        {
            $paged_sql = $db->prepare(
                "SELECT pm.* $sql_base LIMIT %d OFFSET %d",
                array_merge($meta_keys, [$per_page, $offset])
            );

            $products = $db->get_results($paged_sql);
            $this->processProducts($products);
        }
    }

    /**
     * Decide which modules to scan based on mode and optional $onlyModules.
     *
     * @param array{mode?:string} $args
     * @return string[] module ids
     */
    private function resolveModuleIdsForScan(array $args): array
    {
        $mode = isset($args['mode']) ? (string) $args['mode'] : 'redirect';

        if ($mode === 'direct')
        {
            // Modules with local redirect OFF (we still want link index rows for direct tracking)
            $mods = ClickStatsHelper::modulesWithLocalRedirectDisabled(); // id => name
            $ids  = array_keys($mods);
        }
        else
        {
            // Default/redirect mode: modules with local redirect ON
            $mods = ClickStatsHelper::modulesWithLocalRedirectEnabled(); // id => name
            $ids = array_keys($mods);
        }

        // If the backfiller was constructed with a restriction list, intersect it
        if (is_array($this->onlyModules) && $this->onlyModules)
        {
            $ids = array_values(array_intersect($ids, $this->onlyModules));
        }

        return $ids;
    }

    /**
     * @param array<int,\stdClass> $metas Rows from postmeta
     */
    private function processProducts(array $metas): void
    {
        // Collect candidates for this batch
        $items = [];
        foreach ($metas as $meta)
        {
            $data = @unserialize($meta->meta_value);
            if (!is_array($data))
            {
                continue; // corrupted/unexpected format
            }

            foreach ($this->prepareModuleData($data, $meta) as $it)
            {
                if ($this->onlyModules && !in_array($it['module_id'], $this->onlyModules, true))
                {
                    continue;
                }
                $items[] = $it;
            }
        }

        if (!$items)
        {
            return;
        }

        foreach ($items as $it)
        {
            $post_id   = (int) $it['post_id'];
            $module_id = (string) $it['module_id'];
            $unique_id = (string) $it['unique_id'];
            $title     = (string) $it['title'];

            $slug = LinkIndexModel::makeSlug($post_id, $module_id, $unique_id, $title);

            // Upsert into index table
            $this->index->upsertByTriplet($post_id, $module_id, $unique_id, $slug);
        }
    }

    private function prepareModuleData(array $data, \stdClass $meta): array
    {
        $prefix    = ContentManager::META_PREFIX_DATA;
        $module_id = substr((string) $meta->meta_key, strlen($prefix));
        $post_id   = (int) $meta->post_id;

        $items = [];
        foreach ($data as $item)
        {
            if (!is_array($item))
            {
                continue;
            }
            $items[] = [
                'post_id'   => $post_id,
                'module_id' => (string) $module_id,
                'unique_id' => (string) $item['unique_id'],
                'title'     => (string) $item['title'],
            ];
        }

        return $items;
    }
}

<?php

namespace ContentEgg\application\components;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\models\LinkIndexModel;
use ContentEgg\application\components\ModuleManager;

defined('\ABSPATH') || exit;

/**
 * LinkIndexIndexer class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class LinkIndexIndexer
{

    public static function initAction(): void
    {
        add_action('content_egg_save_data', [__CLASS__, 'onSaveData'], 10, 4);

        // Only purge on permanent delete (not trash), so restored posts keep working.
        add_action('deleted_post', [__CLASS__, 'onDeletedPost'], 10, 1);
    }

    public static function onSaveData($data, string $module_id, int $post_id, $is_last_iteration): void
    {
        if (!self::shouldIndexForModule($module_id))
        {
            return;
        }

        $index = LinkIndexModel::model();
        $items = self::normalizeItems($data);

        if (!$items)
        {
            self::deleteByPostAndModule($index, (int) $post_id, $module_id);
            return;
        }

        // De-duplicate by unique_id (keep last occurrence)
        $newById = [];
        foreach ($items as $it)
        {
            $uid = (string) $it['unique_id'];
            $newById[$uid] = ['unique_id' => $uid, 'title' => (string) $it['title']];
        }

        // Existing rows for this post + module
        $existingRows = array_filter(
            $index->listByPost((int) $post_id),
            static function ($r) use ($module_id)
            {
                return is_array($r) && isset($r['module_id']) && $r['module_id'] === $module_id;
            }
        );

        $existingById = [];
        foreach ($existingRows as $r)
        {
            $existingById[(string) $r['unique_id']] = $r;
        }

        // Delete rows no longer present
        $toDelete = array_diff(array_keys($existingById), array_keys($newById));
        foreach ($toDelete as $uid)
        {
            $row = $existingById[$uid];
            $index->deleteRow((int) $row['id']);
        }

        // Create rows for new items; do NOT change slug for existing items to keep redirect URLs stable.
        foreach ($newById as $uid => $it)
        {
            $desiredSlug = isset($existingById[$uid]) ? null : (
                LinkIndexModel::makeSlug((int) $post_id, $module_id, $uid, $it['title'])
            );
            $index->upsertByTriplet((int) $post_id, $module_id, $uid, $desiredSlug);
        }
    }

    /**
     * Remove all index rows for a post on permanent delete.
     */
    public static function onDeletedPost(int $post_id): void
    {
        LinkIndexModel::model()->deleteByPost((int) $post_id);
    }

    private static function normalizeItems($data): array
    {
        if (!is_array($data))
        {
            return [];
        }

        $list = $data;

        $out = [];
        foreach ($list as $item)
        {
            if (!is_array($item))
            {
                continue;
            }
            if (!isset($item['unique_id']))
            {
                continue;
            }
            $out[] = [
                'unique_id' => (string) $item['unique_id'],
                'title'     => (string) ($item['title'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Delete all rows for a (post_id, module_id) pair using the model’s APIs.
     */
    private static function deleteByPostAndModule(LinkIndexModel $index, int $post_id, string $module_id): void
    {
        $rows = $index->listByPost($post_id);
        if (!$rows)
        {
            return;
        }
        foreach ($rows as $r)
        {
            if (isset($r['module_id']) && $r['module_id'] === $module_id)
            {
                $index->deleteRow((int) $r['id']);
            }
        }
    }

    private static function shouldIndexForModule(string $module_id): bool
    {
        $module = ModuleManager::factory($module_id);
        if (!$module) return false;

        $redirectOn = (bool) $module->config('set_local_redirect');
        $directOn   = GeneralConfig::getInstance()->option('clicks_track_direct') === 'enabled';

        return $redirectOn || $directOn;
    }
}

<?php

namespace ContentEgg\application\admin;

use ContentEgg\application\models\ProductMapModel;

defined('\ABSPATH') || exit;

/**
 * ProductMapMaintenance class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ProductMapMaintenance
{
    public static function initAction(): void
    {
        add_action('before_delete_post', [__CLASS__, 'onBeforeDeletePost'], 10, 1);
        add_action('wp_trash_post', [__CLASS__, 'onBeforeDeletePost'], 10, 1);
    }

    public static function onBeforeDeletePost(int $post_id): void
    {
        $map = ProductMapModel::model();

        // If this post is a Bridge Page used as TARGET
        $map->deleteByTarget($post_id);

        // If this post served as a SOURCE context
        $map->deleteBySource($post_id);
    }

    public static function garbageCollect(): void
    {
        $map = ProductMapModel::model();
        // Clean rows whose source post vanished
        $map->cleanupOrphanSources();
        // Clean rows whose target post vanished
        $map->cleanupOrphanTargets();
    }
}

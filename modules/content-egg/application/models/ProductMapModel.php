<?php

namespace ContentEgg\application\models;



defined('\ABSPATH') || exit;

/**
 * ProductMapModel class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 * ProductMapModel — maps a CE item (module_id + unique_id) in a given source post
 * to an on-site target post (PDP or Woo product).
 *
 * - Per-post overrides: row has source_post_id = {post_id}
 * - Canonical mapping for the item: row has source_post_id = 0
 *
 * Click resolution order:
 *  1) (module_id, unique_id, source_post_id = current post)
 *  2) (module_id, unique_id, source_post_id = 0) → Special row that acts as the default target for that item across the whole site.
 *
 */
class ProductMapModel extends Model
{
    public const CANONICAL_SOURCE = 0;

    public function tableName(): string
    {
        return $this->getDb()->prefix . 'cegg_product_map';
    }

    public function getDump(): string
    {
        return sprintf(
            "CREATE TABLE %s (
                id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                module_id        VARCHAR(64)     NOT NULL,
                unique_id        VARCHAR(191)    NOT NULL,
                source_post_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
                target_post_id   BIGINT UNSIGNED NOT NULL,
                created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_item_ctx (module_id, unique_id, source_post_id),
                KEY idx_item     (module_id, unique_id),
                KEY idx_source   (source_post_id),
                KEY idx_target   (target_post_id)
            ) %s;",
            $this->tableName(),
            $this->charset_collate
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function attributeLabels(): array
    {
        return [
            'id'             => 'ID',
            'module_id'      => __('Module', 'content-egg'),
            'unique_id'      => __('Item ID', 'content-egg'),
            'source_post_id' => __('Source Post', 'content-egg'),
            'target_post_id' => __('Target Post', 'content-egg'),
            'created_at'     => __('Created', 'content-egg'),
            'updated_at'     => __('Updated', 'content-egg'),
        ];
    }

    /* ------------------------------------------------------------------
       CRUD / helpers
    ------------------------------------------------------------------ */

    /**
     * Upsert a mapping for (module_id, unique_id, source_post_id) → target_post_id.
     * Preserves created_at on duplicates.
     *
     * @return int Row ID
     */
    public function upsertMapping(string $module_id, string $unique_id, int $source_post_id, int $target_post_id): int
    {
        $db  = $this->getDb();
        $now = current_time('mysql');

        $sql = $db->prepare(
            "INSERT INTO {$this->tableName()}
                (module_id, unique_id, source_post_id, target_post_id, created_at, updated_at)
             VALUES (%s, %s, %d, %d, %s, %s)
             ON DUPLICATE KEY UPDATE
                target_post_id = VALUES(target_post_id),
                updated_at     = VALUES(updated_at)",
            $module_id,
            $unique_id,
            $source_post_id,
            $target_post_id,
            $now,
            $now
        );

        $db->query($sql);

        if ((int) $db->insert_id > 0)
        {
            return (int) $db->insert_id;
        }

        // Existing row updated; return its ID.
        $id = (int) $db->get_var(
            $db->prepare(
                "SELECT id FROM {$this->tableName()}
                 WHERE module_id = %s AND unique_id = %s AND source_post_id = %d
                 LIMIT 1",
                $module_id,
                $unique_id,
                $source_post_id
            )
        );

        return $id;
    }

    /**
     * Fetch one mapping row by composite key.
     */
    public function findOne(string $module_id, string $unique_id, int $source_post_id): ?array
    {
        return $this->getDb()->get_row(
            $this->getDb()->prepare(
                "SELECT * FROM {$this->tableName()}
                 WHERE module_id = %s AND unique_id = %s AND source_post_id = %d
                 LIMIT 1",
                $module_id,
                $unique_id,
                $source_post_id
            ),
            ARRAY_A
        ) ?: null;
    }

    /**
     * Resolve the target_post_id for a click:
     * tries per-post override, then canonical (source_post_id=0).
     *
     * @return int|null
     */
    public function resolveTargetPostId(string $module_id, string $unique_id, int $source_post_id, bool $tryCanonical = true): ?int
    {
        $db  = $this->getDb();
        $tbl = $this->tableName();

        // 1) Per-post override
        $target = $db->get_var(
            $db->prepare(
                "SELECT target_post_id FROM {$tbl}
                 WHERE module_id = %s AND unique_id = %s AND source_post_id = %d
                 LIMIT 1",
                $module_id,
                $unique_id,
                $source_post_id
            )
        );

        if ($target)
        {
            return (int) $target;
        }

        // 2) Canonical
        if ($tryCanonical)
        {
            $target = $db->get_var(
                $db->prepare(
                    "SELECT target_post_id FROM {$tbl}
                     WHERE module_id = %s AND unique_id = %s AND source_post_id = %d
                     LIMIT 1",
                    $module_id,
                    $unique_id,
                    self::CANONICAL_SOURCE
                )
            );

            if ($target)
            {
                return (int) $target;
            }
        }

        return null;
    }

    /**
     * Convenience: set or update the canonical mapping (source_post_id = 0).
     */
    public function setCanonical(string $module_id, string $unique_id, int $target_post_id): int
    {
        return $this->upsertMapping($module_id, $unique_id, self::CANONICAL_SOURCE, $target_post_id);
    }

    /**
     * Delete a single mapping row (by composite key).
     */
    public function deleteOne(string $module_id, string $unique_id, int $source_post_id): bool
    {
        return (bool) $this->getDb()->delete(
            $this->tableName(),
            array(
                'module_id'      => $module_id,
                'unique_id'      => $unique_id,
                'source_post_id' => $source_post_id,
            ),
            array('%s', '%s', '%d')
        );
    }

    /**
     * Delete ALL mappings for an item (both per-post and canonical).
     *
     * @return int rows affected
     */
    public function deleteAllForItem(string $module_id, string $unique_id): int
    {
        $this->getDb()->query(
            $this->getDb()->prepare(
                "DELETE FROM {$this->tableName()}
                 WHERE module_id = %s AND unique_id = %s",
                $module_id,
                $unique_id
            )
        );
        return (int) $this->getDb()->rows_affected;
    }

    /**
     * Delete mappings that point to a given target post (e.g., when PDP/Woo is trashed).
     *
     * @return int rows affected
     */
    public function deleteByTarget(int $target_post_id): int
    {
        $this->getDb()->query(
            $this->getDb()->prepare(
                "DELETE FROM {$this->tableName()} WHERE target_post_id = %d",
                $target_post_id
            )
        );
        return (int) $this->getDb()->rows_affected;
    }

    /**
     * Reassign mappings from one target post to another (merging PDPs).
     *
     * @return int rows affected
     */
    public function reassignTarget(int $from_target_post_id, int $to_target_post_id): int
    {
        if ($from_target_post_id === $to_target_post_id)
        {
            return 0;
        }

        $this->getDb()->query(
            $this->getDb()->prepare(
                "UPDATE {$this->tableName()}
                 SET target_post_id = %d, updated_at = %s
                 WHERE target_post_id = %d",
                $to_target_post_id,
                current_time('mysql'),
                $from_target_post_id
            )
        );

        return (int) $this->getDb()->rows_affected;
    }

    /**
     * List mappings for a given source post (all CE items used in that post).
     */
    public function listBySourcePost(int $source_post_id): array
    {
        $rows = $this->getDb()->get_results(
            $this->getDb()->prepare(
                "SELECT * FROM {$this->tableName()}
                 WHERE source_post_id = %d
                 ORDER BY module_id, unique_id",
                $source_post_id
            ),
            ARRAY_A
        );
        return $rows ? $rows : array();
    }

    /**
     * List all item mappings that point to a given target post.
     */
    public function listByTargetPost(int $target_post_id): array
    {
        $rows = $this->getDb()->get_results(
            $this->getDb()->prepare(
                "SELECT * FROM {$this->tableName()}
                 WHERE target_post_id = %d",
                $target_post_id
            ),
            ARRAY_A
        );
        return $rows ? $rows : array();
    }

    /**
     * Count mappings per item (how many contexts reference this item).
     */
    public function countContextsForItem(string $module_id, string $unique_id): int
    {
        $sql = $this->getDb()->prepare(
            "SELECT COUNT(*) FROM {$this->tableName()}
             WHERE module_id = %s AND unique_id = %s",
            $module_id,
            $unique_id
        );
        return (int) $this->getDb()->get_var($sql);
    }

    /**
     * Cleanup utility: remove per-post mappings whose source post no longer exists.
     * (leaves canonical rows alone)
     *
     * @return int rows affected
     */
    public function cleanupOrphanSources(): int
    {
        $tbl = $this->tableName();
        $sql = "
            DELETE m FROM {$tbl} m
            LEFT JOIN " . $this->getDb()->posts . " p
              ON (p.ID = m.source_post_id)
            WHERE m.source_post_id > 0
              AND p.ID IS NULL
        ";
        $this->getDb()->query($sql);
        return (int) $this->getDb()->rows_affected;
    }

    /** Delete all per-post mappings for a given source post. */
    public function deleteBySource(int $source_post_id): int
    {
        if ($source_post_id <= 0)
        {
            return 0;
        }

        $this->getDb()->query(
            $this->getDb()->prepare(
                "DELETE FROM {$this->tableName()} WHERE source_post_id = %d",
                $source_post_id
            )
        );
        return (int) $this->getDb()->rows_affected;
    }

    /** Cleanup utility: remove mappings whose TARGET post no longer exists. */
    public function cleanupOrphanTargets(): int
    {
        $tbl = $this->tableName();
        $sql = "
        DELETE m FROM {$tbl} m
        LEFT JOIN " . $this->getDb()->posts . " p
          ON (p.ID = m.target_post_id)
        WHERE p.ID IS NULL
    ";
        $this->getDb()->query($sql);
        return (int) $this->getDb()->rows_affected;
    }

    /**
     * Resolve target_post_id per unique_id for a given module in one shot.
     * Precedence: per-post (source_post_id) > canonical (source_post_id = 0).
     *
     * @param string   $module_id
     * @param string[] $unique_ids         Unique per module (assumed)
     * @param int|null $source_post_id     Context post; if null/<=0 resolves canonical-only
     * @return array                       [ unique_id => target_post_id ]
     */
    public function resolveTargetsForModule(string $module_id, array $unique_ids, ?int $source_post_id): array
    {
        global $wpdb;

        if ($module_id === '' || empty($unique_ids))
        {
            return [];
        }

        // Build SQL
        $placeholders = implode(',', array_fill(0, count($unique_ids), '%s'));
        $params       = [$module_id];

        // Limit to canonical or (canonical + per-post) depending on source_post_id
        if (!empty($source_post_id) && (int) $source_post_id > 0)
        {
            $whereSource = 'source_post_id IN (0, %d)';
            $params[]    = (int) $source_post_id;
        }
        else
        {
            $whereSource = 'source_post_id IN (0)';
        }

        $sql = "
        SELECT unique_id, source_post_id, target_post_id
          FROM {$this->tableName()}
         WHERE module_id = %s
           AND {$whereSource}
           AND unique_id IN ({$placeholders})
    ";

        $params = array_merge($params, $unique_ids);
        $rows   = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        if (!$rows)
        {
            return [];
        }

        // Build best mapping per unique_id: set canonical first, then override with per-post row
        $best = []; // unique_id => target_post_id
        foreach ($rows as $r)
        {
            $u = (string) $r['unique_id'];
            $t = (int) $r['target_post_id'];
            $s = (int) $r['source_post_id'];

            if ($s === 0)
            {
                if (!isset($best[$u]))
                {
                    $best[$u] = $t;
                }
            }
            else
            {
                $best[$u] = $t; // per-post overrides canonical
            }
        }

        return $best;
    }

    /**
     * Batch resolve best targets for a module + set of unique_ids, including origin.
     * Returns: [ unique_id => ['target_post_id' => int, 'is_canonical' => bool] ]
     *
     * Order of precedence:
     *   1) per-post override (source_post_id = $source_post_id > 0) -> is_canonical=false
     *   2) canonical (source_post_id = 0)                          -> is_canonical=true
     *
     * @param string   $module_id
     * @param string[] $unique_ids
     * @param int|null $source_post_id
     * @return array<string,array{target_post_id:int,is_canonical:bool}>
     */
    public function resolveTargetsForModuleWithOrigin(string $module_id, array $unique_ids, ?int $source_post_id): array
    {
        $db = $this->getDb();
        $tbl = $this->tableName();

        // sanitize list
        $unique_ids = array_values(array_filter(array_map('strval', $unique_ids)));

        if (!$unique_ids)
        {
            return [];
        }

        // Fetch per-post overrides (if we have a real source context)
        $overrides = [];
        if ($source_post_id && $source_post_id > 0)
        {
            // chunk IN() if needed (kept simple here)
            $in = implode("','", array_map('esc_sql', $unique_ids));
            $sql = "
            SELECT unique_id, target_post_id
              FROM {$tbl}
             WHERE module_id = %s
               AND source_post_id = %d
               AND unique_id IN ('{$in}')
        ";
            $rows = $db->get_results($db->prepare($sql, $module_id, (int)$source_post_id), ARRAY_A);
            foreach ((array)$rows as $r)
            {
                $u = (string)$r['unique_id'];
                $overrides[$u] = (int)$r['target_post_id'];
            }
        }

        // Fetch canonical mappings for the same set
        $canon = [];
        {
            $in = implode("','", array_map('esc_sql', $unique_ids));
            $sql = "
            SELECT unique_id, target_post_id
              FROM {$tbl}
             WHERE module_id = %s
               AND source_post_id = 0
               AND unique_id IN ('{$in}')
        ";
            $rows = $db->get_results($db->prepare($sql, $module_id), ARRAY_A);
            foreach ((array)$rows as $r)
            {
                $u = (string)$r['unique_id'];
                $canon[$u] = (int)$r['target_post_id'];
            }
        }

        // Build result with origin flag
        $out = [];
        foreach ($unique_ids as $u)
        {
            if (isset($overrides[$u]))
            {
                $out[$u] = ['target_post_id' => $overrides[$u], 'is_canonical' => false];
            }
            elseif (isset($canon[$u]))
            {
                $out[$u] = ['target_post_id' => $canon[$u], 'is_canonical' => true];
            }
        }

        return $out;
    }
}

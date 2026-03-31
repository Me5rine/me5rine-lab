<?php

namespace ContentEgg\application\models;;

defined('ABSPATH') || exit;

/**
 * LinkIndexModel — minimal slug → (post_id, module_id, unique_id) index.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class LinkIndexModel extends Model
{
    /** @var string Cache group name */
    private const CACHE_GROUP = 'cegg_link_index';

    public function tableName(): string
    {
        return $this->getDb()->prefix . 'cegg_link_index';
    }

    public function getDump(): string
    {
        return sprintf(
            "CREATE TABLE %s (
                id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id    BIGINT UNSIGNED NOT NULL,
                module_id  VARCHAR(64)     NOT NULL,
                unique_id  VARCHAR(128)    NOT NULL,
                slug       VARCHAR(190)    NOT NULL,
                created_at DATETIME        NOT NULL,
                updated_at DATETIME        NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uq_slug (slug),
                UNIQUE KEY uq_triplet (post_id, module_id, unique_id),
                KEY idx_post (post_id),
                KEY idx_module (module_id)
            ) %s;",
            $this->tableName(),
            $this->charset_collate
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function createRow(int $post_id, string $module_id, string $unique_id, ?string $desired_slug = null): array
    {
        $db = $this->getDb();

        $now   = current_time('mysql');
        $slug  = $this->ensureUniqueSlug($desired_slug ? $this->normalizeSlug($desired_slug) : $this->normalizeSlug($unique_id));

        $data = [
            'post_id'    => (int) $post_id,
            'module_id'  => $module_id,
            'unique_id'  => $unique_id,
            'slug'       => $slug,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $ok = $db->insert($this->tableName(), $data, ['%d', '%s', '%s', '%s', '%s', '%s']);
        if (!$ok)
        {
            if ($this->isDuplicateKeyError($db->last_error))
            {
                $data['slug'] = $this->ensureUniqueSlug($slug, null, true);
                $ok = $db->insert($this->tableName(), $data, ['%d', '%s', '%s', '%s', '%s', '%s']);
            }
            if (!$ok)
            {
                throw new \RuntimeException('Failed to create link index row: ' . esc_html($db->last_error));
            }
        }

        $id = (int) $db->insert_id;
        $row = $this->findById($id);
        $this->primeCache($row);
        return $row;
    }

    public function upsertByTriplet(int $post_id, string $module_id, string $unique_id, ?string $desired_slug = null): array
    {
        $existing = $this->findByTriplet($post_id, $module_id, $unique_id);
        if ($existing)
        {
            if ($desired_slug !== null)
            {
                $newSlug = $this->ensureUniqueSlug($this->normalizeSlug($desired_slug), (int)$existing['id']);
                return $this->updateSlug((int)$existing['id'], $newSlug);
            }
            return $existing;
        }

        return $this->createRow((int) $post_id, $module_id, $unique_id, $desired_slug);
    }

    public function updateSlug(int $id, string $desired_slug): array
    {
        $db = $this->getDb();

        $slug = $this->ensureUniqueSlug($this->normalizeSlug($desired_slug), (int) $id);
        $ok = $db->update(
            $this->tableName(),
            [
                'slug'       => $slug,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => (int) $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($ok === false)
        {
            throw new \RuntimeException('Failed to update slug: ' . esc_html($db->last_error));
        }

        $this->deleteCache((int) $id);
        $row = $this->findById((int) $id);
        $this->primeCache($row);
        return $row;
    }

    public function deleteRow(int $id): void
    {
        $db = $this->getDb();
        $id = (int) $id;

        // Cascade clicks first
        LinkClicksDailyModel::model()->deleteByLinkId($id);

        // Get slug cheaply (avoid full row + priming)
        $slug = $db->get_var(
            $db->prepare("SELECT slug FROM {$this->tableName()} WHERE id = %d", $id)
        );

        // Clear caches deterministically
        wp_cache_delete($id, self::CACHE_GROUP);
        if (!empty($slug))
        {
            wp_cache_delete('slug:' . $slug, self::CACHE_GROUP);
        }

        // Finally delete the index row
        $db->delete($this->tableName(), ['id' => $id], ['%d']);
    }

    public function deleteByPost(int $post_id): int
    {
        $db = $this->getDb();
        $post_id = (int) $post_id;

        // Collect ids+slugs to clear caches
        $rows = $db->get_results(
            $db->prepare("SELECT id, slug FROM {$this->tableName()} WHERE post_id = %d", $post_id),
            ARRAY_A
        );

        if ($rows)
        {
            foreach ($rows as $r)
            {
                wp_cache_delete((int) $r['id'], self::CACHE_GROUP);
                if (!empty($r['slug']))
                {
                    wp_cache_delete('slug:' . $r['slug'], self::CACHE_GROUP);
                }
            }
        }

        // Cascade: delete all click rows for links of this post (fast JOIN)
        LinkClicksDailyModel::model()->deleteByPost($post_id);

        // Bulk delete index rows
        $db->query(
            $db->prepare("DELETE FROM {$this->tableName()} WHERE post_id = %d", $post_id)
        );

        return (int) $db->rows_affected;
    }

    public function findById(int $id): ?array
    {
        if ($cached = wp_cache_get((int) $id, self::CACHE_GROUP))
        {
            return $cached;
        }

        $db = $this->getDb();
        $row = $db->get_row(
            $db->prepare("SELECT * FROM {$this->tableName()} WHERE id = %d", (int) $id),
            ARRAY_A
        );

        if ($row)
        {
            $this->primeCache($row);
        }
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $cacheKey = 'slug:' . $slug;
        if ($cached = wp_cache_get($cacheKey, self::CACHE_GROUP))
        {
            return $cached;
        }

        $db = $this->getDb();
        $row = $db->get_row(
            $db->prepare("SELECT * FROM {$this->tableName()} WHERE slug = %s", $slug),
            ARRAY_A
        );

        if ($row)
        {
            $this->primeCache($row);
        }
        return $row ?: null;
    }

    public function findByTriplet(int $post_id, string $module_id, string $unique_id): ?array
    {
        $db = $this->getDb();
        $row = $db->get_row(
            $db->prepare(
                "SELECT * FROM {$this->tableName()} WHERE post_id = %d AND module_id = %s AND unique_id = %s",
                (int) $post_id,
                $module_id,
                $unique_id
            ),
            ARRAY_A
        );

        if ($row)
        {
            $this->primeCache($row);
        }
        return $row ?: null;
    }

    public function listByPost(int $post_id): array
    {
        $db = $this->getDb();
        $rows = $db->get_results(
            $db->prepare("SELECT * FROM {$this->tableName()} WHERE post_id = %d ORDER BY id ASC", (int) $post_id),
            ARRAY_A
        );
        foreach ($rows as $r)
        {
            $this->primeCache($r);
        }
        return $rows ?: [];
    }

    public function ensureUniqueSlug(string $baseSlug, ?int $excludeId = null, bool $forceBump = false): string
    {
        $slug = $this->truncateForIndex($baseSlug);

        if ($forceBump || $this->slugExists($slug, $excludeId))
        {
            $i = 2;
            $base = $slug;
            $maxLen = 190;

            while (true)
            {
                $candidate = $base . '-' . $i;
                if (\strlen($candidate) > $maxLen)
                {
                    $cut = $maxLen - (strlen((string)$i) + 1);
                    $candidate = (function_exists('mb_substr') ? mb_substr($base, 0, $cut) : substr($base, 0, $cut)) . '-' . $i;
                }
                if (!$this->slugExists($candidate, $excludeId))
                {
                    $slug = $candidate;
                    break;
                }
                $i++;
            }
        }

        return $slug;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $db = $this->getDb();

        if ($excludeId)
        {
            $sql = $db->prepare(
                "SELECT 1 FROM {$this->tableName()} WHERE slug = %s AND id <> %d LIMIT 1",
                $slug,
                (int) $excludeId
            );
        }
        else
        {
            $sql = $db->prepare(
                "SELECT 1 FROM {$this->tableName()} WHERE slug = %s LIMIT 1",
                $slug
            );
        }

        return (bool)$db->get_var($sql);
    }

    public function normalizeSlug(string $slug): string
    {
        if (!function_exists('sanitize_title'))
        {
            require_once \ABSPATH . 'wp-includes/formatting.php';
        }
        $normalized = sanitize_title($slug);
        return $this->truncateForIndex($normalized);
    }

    private function truncateForIndex(string $s): string
    {
        if (strlen($s) <= 190)
        {
            return $s;
        }
        return function_exists('mb_substr') ? mb_substr($s, 0, 190) : substr($s, 0, 190);
    }

    private function isDuplicateKeyError(string $err): bool
    {
        $err = strtolower($err);
        return (strpos($err, 'duplicate') !== false && strpos($err, 'key') !== false)
            || strpos($err, '1062') !== false;
    }

    private function primeCache(array $row): void
    {
        if (!empty($row['id']))
        {
            wp_cache_set((int)$row['id'], $row, self::CACHE_GROUP, 300);
        }
        if (!empty($row['slug']))
        {
            wp_cache_set('slug:' . $row['slug'], $row, self::CACHE_GROUP, 300);
        }
    }

    private function deleteCache(int $id): void
    {
        $row = wp_cache_get((int) $id, self::CACHE_GROUP);
        wp_cache_delete((int) $id, self::CACHE_GROUP);
        if (is_array($row) && !empty($row['slug']))
        {
            wp_cache_delete('slug:' . $row['slug'], self::CACHE_GROUP);
        }
    }

    /**
     * Bulk delete all rows by module_id; returns number of rows removed.
     * Uses a single DELETE for DB efficiency and clears object cache beforehand.
     */
    public function deleteByModule(string $module_id): int
    {
        $db = $this->getDb();

        // Fetch ids+slugs once to invalidate caches, then bulk delete.
        $rows = $db->get_results(
            $db->prepare(
                "SELECT id, slug FROM {$this->tableName()} WHERE module_id = %s",
                $module_id
            ),
            ARRAY_A
        );

        if (!$rows)
        {
            return 0;
        }

        // Invalidate caches (cheap, avoids stale hits for up to TTL)
        foreach ($rows as $r)
        {
            $id = (int) $r['id'];
            wp_cache_delete($id, self::CACHE_GROUP);
            if (!empty($r['slug']))
            {
                wp_cache_delete('slug:' . $r['slug'], self::CACHE_GROUP);
            }
        }

        // Single fast DELETE
        $db->query(
            $db->prepare(
                "DELETE FROM {$this->tableName()} WHERE module_id = %s",
                $module_id
            )
        );

        return (int) $db->rows_affected;
    }

    public static function makeSlug(int $post_id, string $module_id, string $unique_id, string $title): string
    {
        $post_slug = (string) get_post_field('post_name', (int) $post_id);
        $base      = trim($title) !== '' ? $title : ($post_slug ?: 'link');

        // Best-effort transliteration for non-Latin titles
        if ($base !== '' && function_exists('transliterator_transliterate'))
        {
            $base = transliterator_transliterate('Any-Latin; Latin-ASCII', $base);
        }
        elseif ($base !== '' && function_exists('iconv'))
        {
            $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
            if ($tmp !== false)
            {
                $base = $tmp;
            }
        }

        if (!function_exists('sanitize_title'))
        {
            require_once \ABSPATH . 'wp-includes/formatting.php';
        }
        $slugBase = sanitize_title($base);

        // If everything stripped, use a readable fallback
        if ($slugBase === '')
        {
            $slugBase = 'link';
        }

        // Stable 6-char fingerprint; preserves uniqueness
        $finger   = substr(md5($unique_id . '|' . strtolower($module_id)), 0, 6);
        $suffix   = '-' . $finger;

        // Respect 190-char indexed limit and keep suffix intact
        $maxLen = 190;
        $avail  = $maxLen - strlen($suffix);
        if ($avail < 1)
        {
            return substr($suffix, 0, $maxLen);
        }

        $baseTrunc = (function_exists('mb_substr') ? mb_substr($slugBase, 0, $avail) : substr($slugBase, 0, $avail));
        return $baseTrunc . $suffix;
    }
}

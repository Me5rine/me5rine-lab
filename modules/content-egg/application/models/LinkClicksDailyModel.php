<?php

namespace ContentEgg\application\models;

defined('ABSPATH') || exit;

/**
 * LinkClicksDailyModel — aggregated clicks by day for each link (link_id + ymd).
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class LinkClicksDailyModel extends Model
{
    public function tableName(): string
    {
        return $this->getDb()->prefix . 'cegg_link_clicks_daily';
    }

    public function getDump(): string
    {
        return sprintf(
            "CREATE TABLE %s (
                link_id       BIGINT UNSIGNED NOT NULL,
                ymd           DATE            NOT NULL,
                clicks        INT UNSIGNED    NOT NULL DEFAULT 1,
                last_click_at DATETIME        NOT NULL,
                PRIMARY KEY (link_id, ymd),
                KEY ymd (ymd)
            ) %s;",
            $this->tableName(),
            $this->charset_collate
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Upsert a daily counter row.
     *
     * @param int         $link_id
     * @param string      $ymd            Date bucket in 'Y-m-d' (site timezone)
     * @param int         $inc            Increment amount (default 1)
     * @param string|null $last_click_at  DATETIME in site timezone; default: current_time('mysql')
     * @return int                        Rows affected
     */
    public function upsertDaily(int $link_id, string $ymd, int $inc = 1, ?string $last_click_at = null): int
    {
        $db  = $this->getDb();
        $ymd = $this->normalizeYmd($ymd);
        $inc = max(1, (int) $inc);
        $ts  = $last_click_at ?: current_time('mysql');

        // Fast atomic upsert
        $sql = $db->prepare(
            "INSERT INTO {$this->tableName()} (link_id, ymd, clicks, last_click_at)
             VALUES (%d, %s, %d, %s)
             ON DUPLICATE KEY UPDATE
                clicks = clicks + VALUES(clicks),
                last_click_at = VALUES(last_click_at)",
            (int) $link_id,
            $ymd,
            (int) $inc,
            $ts
        );

        $db->query($sql);
        return (int) $db->rows_affected;
    }

    /**
     * Convenience: increment today's bucket for a link (site timezone).
     *
     * @param int $link_id
     * @param int $inc
     * @return int
     */
    public function incrementToday(int $link_id, int $inc = 1): int
    {
        return $this->upsertDaily((int) $link_id, $this->ymdToday(), (int) $inc);
    }

    /**
     * Find a single row by composite PK.
     *
     * @return array{link_id:int,ymd:string,clicks:int,last_click_at:string}|null
     */
    public function findRow(int $link_id, string $ymd): ?array
    {
        $db  = $this->getDb();
        $ymd = $this->normalizeYmd($ymd);

        $row = $db->get_row(
            $db->prepare(
                "SELECT * FROM {$this->tableName()} WHERE link_id = %d AND ymd = %s",
                (int) $link_id,
                $ymd
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * List all daily rows for a link (optionally limited, newest first).
     *
     * @param int $link_id
     * @param int $limit  0 = no limit
     * @return array<int, array>
     */
    public function listByLinkId(int $link_id, int $limit = 0): array
    {
        $db    = $this->getDb();
        $limit = (int) $limit;

        if ($limit > 0)
        {
            $sql = $db->prepare(
                "SELECT * FROM {$this->tableName()} WHERE link_id = %d ORDER BY ymd DESC LIMIT %d",
                (int) $link_id,
                $limit
            );
        }
        else
        {
            $sql = $db->prepare(
                "SELECT * FROM {$this->tableName()} WHERE link_id = %d ORDER BY ymd DESC",
                (int) $link_id
            );
        }

        $rows = $db->get_results($sql, ARRAY_A);
        return $rows ?: [];
    }

    /**
     * Delete all rows for a link_id.
     *
     * @return int Rows deleted
     */
    public function deleteByLinkId(int $link_id): int
    {
        $db = $this->getDb();
        $db->query(
            $db->prepare(
                "DELETE FROM {$this->tableName()} WHERE link_id = %d",
                (int) $link_id
            )
        );
        return (int) $db->rows_affected;
    }

    /**
     * Delete rows older than a given ymd (strictly less than $ymd).
     *
     * @param string $ymd 'Y-m-d'
     * @return int Rows deleted
     */
    public function deleteOlderThan(string $ymd): int
    {
        $db  = $this->getDb();
        $ymd = $this->normalizeYmd($ymd);

        $db->query(
            $db->prepare(
                "DELETE FROM {$this->tableName()} WHERE ymd < %s",
                $ymd
            )
        );
        return (int) $db->rows_affected;
    }

    /**
     * Bulk delete all click rows for links belonging to a post.
     * (Uses link index to resolve link_ids for the post)
     *
     * @return int Rows deleted
     */
    public function deleteByPost(int $post_id): int
    {
        $db         = $this->getDb();
        $indexTable = LinkIndexModel::model()->tableName();

        // Delete via subquery to avoid pulling IDs to PHP
        $db->query(
            $db->prepare(
                "DELETE cd FROM {$this->tableName()} cd
                 INNER JOIN {$indexTable} li ON li.id = cd.link_id
                 WHERE li.post_id = %d",
                (int) $post_id
            )
        );
        return (int) $db->rows_affected;
    }

    /**
     * Aggregates for a single link: today / 7d / 30d / total.
     * Dates are computed in the site timezone to match how buckets are written.
     *
     * @return array{today:int,d7:int,d30:int,total:int}
     */
    public function aggregatesForLink(int $link_id): array
    {
        $db    = $this->getDb();
        $today = $this->ymdToday();
        $from7 = $this->ymdDaysAgo(6);   // includes today => 7 days
        $from30 = $this->ymdDaysAgo(29);  // includes today => 30 days

        $sql = $db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN ymd = %s  THEN clicks END),0) AS today,
                COALESCE(SUM(CASE WHEN ymd >= %s THEN clicks END),0) AS d7,
                COALESCE(SUM(CASE WHEN ymd >= %s THEN clicks END),0) AS d30,
                COALESCE(SUM(clicks),0) AS total
             FROM {$this->tableName()}
             WHERE link_id = %d",
            $today,
            $from7,
            $from30,
            (int) $link_id
        );

        $row = $db->get_row($sql, ARRAY_A) ?: [];
        return [
            'today' => (int) ($row['today'] ?? 0),
            'd7'    => (int) ($row['d7'] ?? 0),
            'd30'   => (int) ($row['d30'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    /**
     * Per-post aggregates per link (slug/module/unique_id + today/d7/d30/total).
     * Returns one row per link in the post, including links with zero clicks.
     *
     * @return array<int, array{
     *   link_id:int, slug:string, module_id:string, unique_id:string,
     *   today:int, d7:int, d30:int, total:int
     * }>
     */
    public function aggregatesForPost(int $post_id): array
    {
        $db         = $this->getDb();
        $indexTable = LinkIndexModel::model()->tableName();
        $clicksTable = $this->tableName();

        $today  = $this->ymdToday();
        $from7  = $this->ymdDaysAgo(6);
        $from30 = $this->ymdDaysAgo(29);

        $sql = $db->prepare(
            "SELECT
                li.id        AS link_id,
                li.slug      AS slug,
                li.module_id AS module_id,
                li.unique_id AS unique_id,
                COALESCE(SUM(CASE WHEN cd.ymd = %s  THEN cd.clicks END), 0) AS today,
                COALESCE(SUM(CASE WHEN cd.ymd >= %s THEN cd.clicks END), 0) AS d7,
                COALESCE(SUM(CASE WHEN cd.ymd >= %s THEN cd.clicks END), 0) AS d30,
                COALESCE(SUM(cd.clicks), 0) AS total
             FROM {$indexTable} li
             LEFT JOIN {$clicksTable} cd ON cd.link_id = li.id
             WHERE li.post_id = %d
             GROUP BY li.id, li.slug, li.module_id, li.unique_id
             ORDER BY d7 DESC, total DESC",
            $today,
            $from7,
            $from30,
            (int) $post_id
        );

        $rows = $db->get_results($sql, ARRAY_A);
        return $rows ?: [];
    }

    // --------------------------
    // Helpers (dates & validation)
    // --------------------------

    /**
     * Normalize/validate 'Y-m-d' input; falls back to today's date on invalid.
     */
    private function normalizeYmd(string $ymd): string
    {
        $ymd = trim($ymd);
        // Basic validation: 'YYYY-MM-DD'
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd))
        {
            return $this->ymdToday();
        }
        return $ymd;
    }

    /**
     * Site-local today's date in 'Y-m-d'.
     */
    private function ymdToday(): string
    {
        $tz  = wp_timezone();
        $now = new \DateTimeImmutable('now', $tz);
        return $now->format('Y-m-d');
    }

    /**
     * Site-local 'Y-m-d' for N days ago (0 = today).
     */
    private function ymdDaysAgo(int $days): string
    {
        $days = max(0, (int) $days);
        $tz   = wp_timezone();
        $dt   = new \DateTimeImmutable('today', $tz);
        if ($days > 0)
        {
            $dt = $dt->sub(new \DateInterval('P' . $days . 'D'));
        }
        return $dt->format('Y-m-d');
    }

    public function deleteByModule(string $module_id): int
    {
        $db         = $this->getDb();
        $indexTable = LinkIndexModel::model()->tableName();

        $db->query(
            $db->prepare(
                "DELETE cd FROM {$this->tableName()} cd
             INNER JOIN {$indexTable} li ON li.id = cd.link_id
             WHERE li.module_id = %s",
                $module_id
            )
        );

        return (int) $db->rows_affected;
    }

    public function deleteByPostAndModule(int $post_id, string $module_id): int
    {
        $db         = $this->getDb();
        $indexTable = LinkIndexModel::model()->tableName();

        $db->query(
            $db->prepare(
                "DELETE cd FROM {$this->tableName()} cd
             INNER JOIN {$indexTable} li ON li.id = cd.link_id
             WHERE li.post_id = %d AND li.module_id = %s",
                (int) $post_id,
                $module_id
            )
        );

        return (int) $db->rows_affected;
    }
}

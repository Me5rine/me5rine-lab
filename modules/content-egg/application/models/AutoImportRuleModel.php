<?php

namespace ContentEgg\application\models;

defined('\ABSPATH') || exit;

/**
 * AutoImportRuleModel — stores “set & forget” auto-import rules.
 *
 * Each rule can watch multiple keywords and periodically
 * enqueue import jobs according to its frequency / stop-conditions.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class AutoImportRuleModel extends Model
{

    /* ------------------------------------------------------------------
	   Constants
	------------------------------------------------------------------ */
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_PAUSED   = 'paused';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_DISABLED = 'disabled';

    /* ------------------------------------------------------------------
	   Table helpers
	------------------------------------------------------------------ */
    public function tableName(): string
    {
        return $this->getDb()->prefix . 'cegg_autoimport_rules';
    }

    /**
     * SQL dump for CREATE TABLE.
     */
    public function getDump(): string
    {
        return sprintf(
            "CREATE TABLE %s (
            id                                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name                                 VARCHAR(255)    NOT NULL,
            preset_id                            BIGINT UNSIGNED NOT NULL,
            module_id                            VARCHAR(64)     NOT NULL,
            status                               ENUM('active','paused','finished','disabled') DEFAULT 'active',
            interval_seconds                     INT UNSIGNED    NOT NULL DEFAULT 86400,
            keywords_json                        LONGTEXT        NOT NULL,
            sort_newest                          TINYINT(1)      NOT NULL DEFAULT 1,
            stop_after_days                      INT UNSIGNED    DEFAULT NULL,
            stop_after_imports                   INT UNSIGNED    DEFAULT NULL,
            stop_if_no_new_results               INT UNSIGNED    DEFAULT NULL,
            max_keyword_no_products              INT UNSIGNED    DEFAULT NULL,
            consecutive_no_products              INT UNSIGNED    NOT NULL DEFAULT 0,
            post_count                           INT UNSIGNED    NOT NULL DEFAULT 0,
            consecutive_errors                   INT UNSIGNED    NOT NULL DEFAULT 0,
            last_success_at                      DATETIME        NULL,
            last_run_at                          DATETIME        NULL,
            next_run_at                          DATETIME        NULL,
            log_history                          LONGTEXT        NULL,
            created_at                           DATETIME        DEFAULT CURRENT_TIMESTAMP,
            updated_at                           DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status     (status),
            KEY idx_next_run   (next_run_at),
            KEY idx_module     (module_id)
        ) %s;",
            $this->tableName(),
            $this->charset_collate
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /* ------------------------------------------------------------------
	   Labels for WP-List-Table, etc.
	------------------------------------------------------------------ */
    public function attributeLabels(): array
    {
        return [
            'id'                 => 'ID',
            'name'               => __('Rule Name', 'content-egg'),
            'module_id'          => __('Module', 'content-egg'),
            'status'             => __('Status', 'content-egg'),
            'frequency'          => __('Frequency', 'content-egg'),
            'post_count'         => __('Imported', 'content-egg'),
            'consecutive_errors' => __('Errors', 'content-egg'),
            'next_run_at'        => __('Next Run', 'content-egg'),
            'last_run_at'        => __('Last Run', 'content-egg'),
            'updated_at'         => __('Updated', 'content-egg'),
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE   => __('Active',   'content-egg'),
            self::STATUS_PAUSED   => __('Paused',   'content-egg'),
            self::STATUS_FINISHED => __('Finished', 'content-egg'),
            self::STATUS_DISABLED => __('Disabled', 'content-egg'),
        ];
    }

    /* ------------------------------------------------------------------
	   CRUD helpers
	------------------------------------------------------------------ */

    /**
     * Insert or update a rule row.
     * Accepts full associative array; auto-handles created_at / updated_at.
     *
     * @return int New/updated ID
     */
    public function save(array $row): int
    {
        $db           = $this->getDb();
        $row['id']    = isset($row['id']) ? (int) $row['id'] : 0;
        $now          = current_time('mysql');
        $row['updated_at'] = $now;

        // JSON-encode keywords if passed as array
        if (isset($row['keywords_json']) && is_array($row['keywords_json']))
        {
            $row['keywords_json'] = wp_json_encode($row['keywords_json'], JSON_UNESCAPED_UNICODE);
        }

        if (0 === $row['id'])
        {
            unset($row['id']);
            $row['created_at'] = $now;
            $db->insert($this->tableName(), $row);
            return (int) $db->insert_id;
        }

        $db->update($this->tableName(), $row, ['id' => $row['id']]);
        return $row['id'];
    }

    /**
     * Fetch one rule by ID.
     */
    public function findByID(int $id): ?array
    {
        return $this->getDb()->get_row(
            $this->getDb()->prepare(
                "SELECT * FROM {$this->tableName()} WHERE id = %d LIMIT 1",
                $id
            ),
            ARRAY_A
        ) ?: null;
    }

    /**
     * Fetch ACTIVE rules that are due to run (next_run_at ≤ NOW).
     *
     */
    public function findDueRules(int $limit = 5): array
    {
        return $this->getDb()->get_results(
            $this->getDb()->prepare(
                "SELECT *
				 FROM {$this->tableName()}
				 WHERE status = %s
				   AND next_run_at IS NOT NULL
				   AND next_run_at <= %s
				 ORDER BY next_run_at ASC
				 LIMIT %d",
                self::STATUS_ACTIVE,
                current_time('mysql'),
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Quick check: are there any active rules whose next_run_at is already due?
     *
     * @return bool
     */
    public function hasDueRules(): bool
    {
        $sql   = $this->getDb()->prepare(
            "SELECT COUNT(*)
               FROM {$this->tableName()}
              WHERE status = %s
                AND next_run_at IS NOT NULL
                AND next_run_at <= %s",
            self::STATUS_ACTIVE,
            current_time('mysql')
        );
        $count = (int) $this->getDb()->get_var($sql);
        return $count > 0;
    }

    /**
     * Quick check: are there any rules currently marked as “active”?
     *
     * @return bool
     */
    public function hasActiveRules(): bool
    {
        $sql = $this->getDb()->prepare(
            "SELECT COUNT(*)
               FROM {$this->tableName()}
              WHERE status = %s",
            self::STATUS_ACTIVE
        );

        $count = (int) $this->getDb()->get_var($sql);
        return $count > 0;
    }

    /**
     * Bump next_run_at forward to now plus the rule's interval_seconds,
     * and record this run time in last_run_at.
     *
     * @param int $id
     * @return bool True if the update affected a row.
     */
    public function bumpNextRunAt(int $id): bool
    {
        $db  = $this->getDb();
        $now = current_time('mysql');
        $sql = $db->prepare(
            "UPDATE {$this->tableName()}
             SET next_run_at = DATE_ADD(%s, INTERVAL interval_seconds SECOND),
                 last_run_at = %s,
                 updated_at  = %s
             WHERE id = %d",
            $now,
            $now,
            $now,
            $id
        );
        return (bool) $db->query($sql);
    }

    /** Increment imported post counter. */
    public function bumpPostCount(int $id, int $add = 1): void
    {
        $this->getDb()->query(
            $this->getDb()->prepare(
                "UPDATE {$this->tableName()}
				 SET post_count = post_count + %d, updated_at = %s
				 WHERE id = %d",
                $add,
                current_time('mysql'),
                $id
            )
        );
    }

    /**
     * Save log history as JSON for a specific rule ID.
     *
     * @param int   $id         Rule ID
     * @param array $logHistory Log entries to save
     * @return bool             True if the update was successful
     */
    public function saveLogHistory(int $id, array $logHistory): bool
    {
        $db = $this->getDb();
        $json = wp_json_encode($logHistory, JSON_UNESCAPED_UNICODE);

        return (bool) $db->update(
            $this->tableName(),
            [
                'log_history' => $json,
                'updated_at'  => current_time('mysql'),
            ],
            ['id' => $id]
        );
    }

    public function markFinished(int $id): void
    {
        $this->save([
            'id'         => $id,
            'status'     => self::STATUS_FINISHED,
            'updated_at' => current_time('mysql'),
        ]);
    }
}

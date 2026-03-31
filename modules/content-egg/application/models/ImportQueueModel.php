<?php

namespace ContentEgg\application\models;

use ContentEgg\application\Plugin;



defined('\ABSPATH') || exit;

/**
 * ImportQueueModel – handles background queue entries for the Product / Post import feature
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ImportQueueModel extends Model
{
    const MAX_ATTEMPTS = 3;
    const STUCK_TIMEOUT = 15;

    public function tableName(): string
    {
        return $this->getDb()->prefix . 'cegg_import_queue';
    }

    public function getDump(): string
    {
        return sprintf(
            "CREATE TABLE %s (
            id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            preset_id          BIGINT UNSIGNED NOT NULL,
            post_id            BIGINT UNSIGNED DEFAULT NULL,
            source_post_id     BIGINT UNSIGNED DEFAULT NULL,
            category_id        BIGINT UNSIGNED DEFAULT NULL,
            payload            LONGTEXT        NULL,
            module_id          VARCHAR(64)     NOT NULL,
            unique_id          VARCHAR(64)     NOT NULL,
            keyword            VARCHAR(255)    NULL,
            status             ENUM('pending','working','done','failed') DEFAULT 'pending',
            attempts           INT UNSIGNED    NOT NULL DEFAULT 0,
            log                LONGTEXT        NULL,
            processing_time    FLOAT           DEFAULT NULL,
            prompt_tokens      INT UNSIGNED    DEFAULT NULL,
            completion_tokens  INT UNSIGNED    DEFAULT NULL,
            ai_cost            DECIMAL(16,10)  DEFAULT NULL,
            scheduled_at       DATETIME        NULL,
            created_at         DATETIME        DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_unique_id       (unique_id),
            KEY idx_scheduled       (scheduled_at),
            KEY idx_module          (module_id),
            KEY idx_preset_id       (preset_id),
            KEY idx_post_id         (post_id),
            KEY idx_category_id     (category_id),
            KEY ix_status_created   (status, created_at, id)
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
            'id'               => 'ID',
            'preset_id'        => __('Preset',         'content-egg'),
            'module_id'        => __('Module',         'content-egg'),
            'keyword'          => __('Keyword / URL',  'content-egg'),
            'status'           => __('Status',         'content-egg'),
            'scheduled_at'     => __('Post Scheduled At',   'content-egg'),
            'processing_time'  => __('Proc. Time',     'content-egg'),
            'prompt_tokens'    => __('Prompt tokens',  'content-egg'),
            'completion_tokens' => __('Completion tokens', 'content-egg'),
            'ai_cost'          => __('AI Cost',        'content-egg'),
            'created_at'       => __('Created',        'content-egg'),
            'updated_at'       => __('Updated',        'content-egg'),
        ];
    }

    /**
     * Insert or update a queue row.
     * @return int New/updated ID
     */
    public function save(array $row): int
    {
        $db = $this->getDb();
        $row['id'] = isset($row['id']) ? (int) $row['id'] : 0;

        // timestamps
        $now = current_time('mysql');
        $row['updated_at'] = $now;

        if ($row['id'] === 0)
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
     * Add a pending job to queue.
     *
     * @param int         $preset_id
     * @param string      $module_id
     * @param array       $payload
     * @param string      $keyword
     * @param int|null    $category_id
     * @param string|null $scheduled_at  MySQL datetime; if null, defaults to now()
     * @param int|null    $source_post_id Optional WP post ID where enqueue was initiated
     *
     * @return int Inserted job ID or 0 on failure
     */
    public function enqueue(
        int     $preset_id,
        string  $module_id,
        array   $payload        = [],
        string  $keyword        = '',
        ?int    $category_id    = null,
        ?string $scheduled_at   = null,
        ?int    $source_post_id = null
    ): int
    {
        $now       = current_time('mysql');
        $json      = !empty($payload) ? wp_json_encode($payload, JSON_UNESCAPED_UNICODE) : null;
        $unique_id = isset($payload['unique_id']) ? (string) $payload['unique_id'] : '';

        $data = [
            'preset_id'    => $preset_id,
            'module_id'    => $module_id,
            'unique_id'    => $unique_id,
            'payload'      => $json,
            'keyword'      => $keyword,
            'category_id'  => $category_id,
            'status'       => 'pending',
            'scheduled_at' => $scheduled_at ?: $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        if (!empty($source_post_id))
        {
            $data['source_post_id'] = (int) $source_post_id;
        }

        $ok = $this->getDb()->insert($this->tableName(), $data);
        if (!$ok)
        {
            return 0;
        }

        return (int) $this->getDb()->insert_id;
    }

    /**
     * Fetch the next batch ready to process (status = 'pending',
     * and attempts < max), mark them 'working' and bump their attempt count.
     *
     * @param int $limit     Maximum number of jobs to claim.
     * @return array         The batch of rows now in 'working' status.
     */
    public function getNextBatch(int $limit = 3): array
    {
        global $wpdb;
        $table       = $this->tableName();
        $now         = current_time('mysql');

        $maxAttempts = (int) apply_filters('cegg_import_max_attempts', self::MAX_ATTEMPTS);

        // 2) Atomically claim up to $limit pending rows under the retry cap
        $updated = $wpdb->query(
            $wpdb->prepare(
                "
            UPDATE {$table}
            SET status     = 'working',
                updated_at = %s,
                attempts   = attempts + 1
            WHERE status     = 'pending'
              AND attempts  < %d
            ORDER BY id ASC
            LIMIT %d
            ",
                $now,
                $maxAttempts,
                $limit
            )
        );

        if (! $updated)
        {
            return [];
        }

        // 3) Re-select exactly those we just claimed (matching on updated_at)
        //    so that even under race conditions we only fetch our slice
        $batch = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT *
            FROM {$table}
            WHERE status     = 'working'
              AND updated_at = %s
            ORDER BY id ASC
            LIMIT %d
            ",
                $now,
                $limit
            ),
            ARRAY_A
        );

        return $batch ?: [];
    }

    public function resetStuckJobs(): void
    {
        global $wpdb;
        $table       = $this->tableName();
        $now         = current_time('mysql');
        $maxAttempts = (int) apply_filters('cegg_import_max_attempts', self::MAX_ATTEMPTS);

        // 1) Mark any pending jobs that exceeded retries as failed and append to their log.
        $message = sprintf(
            "\n" . __("Maximum retry attempts (%d) exceeded.", 'content-egg'),
            $maxAttempts
        );

        // Run one SQL statement, appending to log via CONCAT + COALESCE
        $wpdb->query(
            $wpdb->prepare(
                "
        UPDATE {$table}
        SET
            status     = %s,
            updated_at = %s,
            log        = CONCAT(
                            COALESCE(log, ''),
                            %s
                         )
        WHERE status   = %s
          AND attempts >= %d
        ",
                'failed',
                $now,
                $message,
                'pending',
                $maxAttempts
            )
        );

        // 2) Reset any 'working' jobs that have been hanging for too long back to 'pending'
        //    so they can be retried (but again subject to the attempts cap above).
        $timeout_minutes = apply_filters('cegg_import_stuck_timeout_minutes', self::STUCK_TIMEOUT);

        if (Plugin::isDevEnvironment())
        {
            $timeout_minutes = 0.1; // 6 sec
        }

        $threshold = date('Y-m-d H:i:s', strtotime("-{$timeout_minutes} minutes", current_time('timestamp')));
        $wpdb->query(
            $wpdb->prepare(
                "
            UPDATE {$table}
            SET status     = 'pending',
                updated_at = %s
            WHERE status      = 'working'
              AND updated_at <= %s
            ",
                $now,
                $threshold
            )
        );
    }

    /* ------------------------------------------------------------------
       Status helpers
    ------------------------------------------------------------------ */

    public function markWorking(int $id): bool
    {
        return $this->updateStatus($id, 'working');
    }

    public function markDone(
        int   $queueId,
        int   $createdPostId,
        string $log = '',
        ?float $processingTime = null,
        ?int   $promptTokens = null,
        ?int   $completionTokens = null,
        ?float $aiCost = null
    ): bool
    {
        global $wpdb;

        $table = $this->tableName();
        $now   = current_time('mysql');

        $data    = [
            'post_id'         => $createdPostId,
            'status'          => 'done',
            'log'             => $log,
            'updated_at'      => $now,
        ];
        $formats = ['%d', '%s', '%s', '%s'];

        if ($processingTime !== null)
        {
            $data['processing_time'] = round($processingTime, 3);
            $formats[] = '%f';
        }
        if ($promptTokens !== null)
        {
            $data['prompt_tokens'] = $promptTokens;
            $formats[] = '%d';
        }
        if ($completionTokens !== null)
        {
            $data['completion_tokens'] = $completionTokens;
            $formats[] = '%d';
        }
        if ($aiCost !== null)
        {
            $data['ai_cost'] = round($aiCost, 10);
            $formats[] = '%f';
        }

        // WHERE clause
        $where         = ['id' => $queueId];
        $where_formats = ['%d'];

        // Run the update
        $updated = $wpdb->update(
            $table,
            $data,
            $where,
            $formats,
            $where_formats
        );

        return $updated !== false;
    }

    public function markFailed(
        int $id,
        string $log = '',
        ?float $proc_time = null,
        ?int $prompt = null,
        ?int $comp = null,
        ?float $cost = null
    ): bool
    {
        return $this->updateStatus($id, 'failed', $log, $proc_time, $prompt, $comp, $cost);
    }

    protected function updateStatus(
        int $id,
        string $status,
        string $log = '',
        ?float $proc_time = null,
        ?int $prompt = null,
        ?int $comp = null,
        ?float $cost = null
    ): bool
    {
        $data = [
            'status'     => $status,
            'log'        => $log,
            'updated_at' => current_time('mysql'),
        ];

        // Optional metrics
        if ($proc_time !== null)       $data['processing_time']   = round($proc_time, 3);
        if ($prompt !== null)          $data['prompt_tokens']     = (int) $prompt;
        if ($comp !== null)            $data['completion_tokens'] = (int) $comp;
        if ($cost !== null)            $data['ai_cost']           = round($cost, 10);

        return $this->getDb()->update($this->tableName(), $data, ['id' => $id]) !== false;
    }

    /* ------------------------------------------------------------------
       Counters & housekeeping
    ------------------------------------------------------------------ */

    public function countByStatus(string $status): int
    {
        return (int) $this->getDb()->get_var(
            $this->getDb()->prepare(
                "SELECT COUNT(*) FROM {$this->tableName()} WHERE status = %s",
                $status
            )
        );
    }

    public function countPending(): int
    {
        return $this->countByStatus('pending');
    }

    public function countWorking(): int
    {
        return $this->countByStatus('working');
    }

    public function countFailed(): int
    {
        return $this->countByStatus('failed');
    }

    public function countAll(): int
    {
        return (int) $this->getDb()->get_var("SELECT COUNT(*) FROM {$this->tableName()}");
    }

    public function clearQueue(): void
    {
        $this->getDb()->query("DELETE FROM {$this->tableName()}");
    }

    public function clearPending(): void
    {
        $this->getDb()->delete($this->tableName(), ['status' => 'pending']);
    }

    public function isInProgress(): bool
    {
        $table = $this->tableName();
        $count = (int) $this->getDb()->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status IN ('pending','working')"
        );
        return $count > 0;
    }

    /**
     * Move all failed rows back to pending, resetting metrics.
     *
     * @return int Number of rows reset
     */
    public function restartFailed(): int
    {
        return $this->restartFailedJobs();
    }

    /**
     * Update AI usage metrics incrementally.
     */
    public function accumulateAiStats(int $id, array $stats): bool
    {
        if (!$stats)
        {
            return false;
        }

        $row = $this->getDb()->get_row(
            $this->getDb()->prepare(
                "SELECT prompt_tokens, completion_tokens, ai_cost FROM {$this->tableName()} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        if (!$row)
        {
            return false;
        }

        $data = [
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%s'];

        $fields = [
            'prompt_tokens'     => '%d',
            'completion_tokens' => '%d',
            'ai_cost'           => '%f',
        ];

        foreach ($fields as $key => $f)
        {
            if (!isset($stats[$key]))
            {
                continue;
            }

            $old = floatval($row[$key] ?? 0);
            $add = floatval($stats[$key]);

            $data[$key] = ($key === 'ai_cost')
                ? round($old + $add, 10)
                : intval($old + $add);

            $format[] = $f;
        }

        return $this->getDb()->update(
            $this->tableName(),
            $data,
            ['id' => $id],
            $format,
            ['%d']
        ) !== false;
    }

    /* ------------------------------------------------------------------
       Utility
    ------------------------------------------------------------------ */

    /**
     * Fetch a single row by ID.
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
     * Count queue entries for a given preset that are still pending or working.
     *
     * @param int $preset_id
     * @return int
     */
    public function countActiveByPreset(int $preset_id): int
    {
        $db    = $this->getDb();
        $table = $this->tableName();

        // We only care about jobs that are not yet finished.
        $sql = "
        SELECT COUNT(*)
        FROM {$table}
        WHERE preset_id = %d
          AND status IN (%s, %s)
    ";

        return (int) $db->get_var(
            $db->prepare(
                $sql,
                $preset_id,
                'pending',
                'working'
            )
        );
    }

    /**
     * Delete all jobs with status 'done' or 'failed'.
     *
     * @return int Number of rows deleted.
     */
    public function clearCompletedJobs(): int
    {
        $db    = $this->getDb();
        $table = $this->tableName();

        $sql = $db->prepare(
            "DELETE FROM {$table} WHERE status IN (%s, %s)",
            'done',
            'failed'
        );

        $deleted = $db->query($sql);

        return $deleted === false ? 0 : (int) $deleted;
    }

    /**
     * Prunes old or excess rows from the import-queue log.
     *
     * • Removes rows whose status is 'done' or 'failed'.
     * • Deletes anything older than N days OR outside the M most-recent rows.
     * • Optionally reclaims space with OPTIMIZE TABLE when rows were deleted.
     *
     * @return int Number of rows deleted.
     */
    public function pruneLogs(): int
    {
        global $wpdb;

        $table     = $this->tableName();
        $table     = esc_sql($this->tableName());
        $days      = (int) apply_filters('cegg_import_queue_prune_days',         180);
        $max_rows  = (int) apply_filters('cegg_import_queue_prune_max_rows',     100_000);
        $optimize  = (bool) apply_filters('cegg_import_queue_optimize_after_prune', false);

        $cutoff = date_i18n('Y-m-d H:i:s', strtotime("-{$days} days", current_time('timestamp')));

        $sql = "
        DELETE iq
        FROM   {$table} AS iq
        LEFT   JOIN (
                 SELECT id
                 FROM   (
                        SELECT id
                        FROM   {$table}
                        WHERE  status IN ('done','failed')
                        ORDER  BY created_at DESC
                        LIMIT  %d
                 ) AS r
        ) AS recent ON recent.id = iq.id
        WHERE  iq.status IN ('done','failed')
          AND ( iq.created_at < %s OR recent.id IS NULL )
    ";

        /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name only */
        $deleted = $wpdb->query($wpdb->prepare($sql, $max_rows, $cutoff));

        if ($optimize && $deleted && mt_rand(1, 5) === 1)
        {
            /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name only */
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }

        return (int) $deleted;
    }

    public function restartFailedJobs(): int
    {
        $db    = $this->getDb();
        $table = $this->tableName();

        $updated = $db->update(
            $table,
            [
                'status'            => 'pending',
                'updated_at'        => current_time('mysql'),
                'processing_time'   => null,
                'prompt_tokens'     => null,
                'completion_tokens' => null,
                'ai_cost'           => null,
                'attempts'          => 0,
                'log'               => '',
            ],
            ['status' => 'failed'],                               // WHERE status = 'failed'
            [
                '%s',    // status
                '%s',    // updated_at
                '%d',    // processing_time
                '%d',    // prompt_tokens
                '%d',    // completion_tokens
                '%f',    // ai_cost
                '%d',    // attempts
                '%s',    // log
            ],
            ['%s']                                                // WHERE format
        );

        return $updated !== false ? (int) $updated : 0;
    }

    /**
     * Update the payload field for a specific queue entry.
     */
    public function updatePayload(int $id, array $payload): bool
    {
        $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (false === $json)
        {
            return false;
        }

        // Extract unique_id from payload
        $unique_id = $payload['unique_id'] ?? '';

        $updated = $this->getDb()->update(
            $this->tableName(),
            [
                'payload'   => $json,
                'unique_id' => $unique_id,
            ],   // data
            ['id' => $id],          // where
            ['%s', '%s'],           // data formats
            ['%d']                  // where formats
        );

        return $updated !== false;
    }

    /**
     * Check if a job exists for the given unique_id in pending or working status.
     */
    public function existsJobByUniqueId(string $unique_id): bool
    {
        $count = $this->getDb()->get_var(
            $this->getDb()->prepare(
                "SELECT COUNT(*) FROM {$this->tableName()} WHERE unique_id = %s AND status IN ('pending','working')",
                $unique_id
            )
        );

        return ($count !== null && $count > 0);
    }

    /**
     * Fetch a single row by unique_id.
     *
     * @param string $unique_id
     * @return array|null
     */
    public function findByUniqueId(string $unique_id): ?array
    {
        return $this->getDb()->get_row(
            $this->getDb()->prepare(
                "SELECT * FROM {$this->tableName()} WHERE unique_id = %s LIMIT 1",
                $unique_id
            ),
            ARRAY_A
        ) ?: null;
    }
}

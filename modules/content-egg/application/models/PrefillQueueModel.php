<?php

namespace ContentEgg\application\models;

defined('\ABSPATH') || exit;

/**
 * PrefillQueueModel handles background product prefill queue entries.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class PrefillQueueModel extends Model
{
    public function tableName()
    {
        return $this->getDb()->prefix . 'cegg_prefill_queue';
    }

    public function getDump()
    {
        return sprintf(
            "CREATE TABLE %s (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            config_key VARCHAR(64) NOT NULL,
            status ENUM('pending', 'done', 'failed') DEFAULT 'pending',
            log TEXT NULL,
            processing_time FLOAT DEFAULT NULL,
            prompt_tokens INT UNSIGNED DEFAULT NULL,
            completion_tokens INT UNSIGNED DEFAULT NULL,
            ai_cost DECIMAL(16,10) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uq_post_id (post_id)
        ) %s;",
            $this->tableName(),
            $this->charset_collate
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'post_id'    => __('Post ID', 'content-egg'),
            'status'     => __('Status', 'content-egg'),
            'log'        => __('Log', 'content-egg'),
            'created_at' => __('Created At', 'content-egg'),
            'updated_at' => __('Updated At', 'content-egg'),
        ];
    }

    public function save(array $item)
    {
        $db = $this->getDb();
        $item['id'] = isset($item['id']) ? (int)$item['id'] : 0;

        if (!$item['id'])
        {
            unset($item['id']);
            $item['created_at'] = current_time('mysql');
            $db->insert($this->tableName(), $item);
            return (int)$db->insert_id;
        }
        else
        {
            $db->update($this->tableName(), $item, ['id' => $item['id']]);
            return $item['id'];
        }
    }

    public function addToQueue($post_id, $config_key)
    {
        $data = [
            'post_id'    => $post_id,
            'config_key' => $config_key,
            'status'     => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        return $this->getDb()->replace($this->tableName(), $data) !== false;
    }

    public function getNextBatch($limit = 3)
    {
        $db = $this->getDb();
        $table = $this->tableName();

        $sql = "SELECT * FROM {$table} WHERE status = %s ORDER BY id ASC LIMIT %d";

        return $db->get_results(
            $db->prepare($sql, 'pending', $limit),
            ARRAY_A
        ) ?: [];
    }

    public function markAsDone($post_id, $log = '', $processing_time = null, $prompt_tokens = null, $completion_tokens = null, $ai_cost = null)
    {
        return $this->updateStatus($post_id, 'done', $log, $processing_time, $prompt_tokens, $completion_tokens, $ai_cost);
    }

    public function markAsFailed($post_id, $log = '', $processing_time = null, $prompt_tokens = null, $completion_tokens = null, $ai_cost = null)
    {
        return $this->updateStatus($post_id, 'failed', $log, $processing_time, $prompt_tokens, $completion_tokens, $ai_cost);
    }

    protected function updateStatus($post_id, $status, $log = '', $processing_time = null)
    {
        $data = [
            'status'     => $status,
            'log'        => $log,
            'updated_at' => current_time('mysql'),
        ];

        if ($processing_time !== null)
        {
            $data['processing_time'] = round($processing_time, 3);
        }

        return $this->getDb()->update(
            $this->tableName(),
            $data,
            ['post_id' => $post_id],
            null,
            ['%d']
        ) !== false;
    }

    public function countPending()
    {
        return $this->countByStatus('pending');
    }

    public function countFailed()
    {
        return $this->countByStatus('failed');
    }

    public function countByStatus($status)
    {
        return (int) $this->getDb()->get_var(
            $this->getDb()->prepare(
                "SELECT COUNT(*) FROM {$this->tableName()} WHERE status = %s",
                $status
            )
        );
    }

    public function isInProgress()
    {
        return $this->countPending() > 0;
    }

    public function getLastUpdatedAt()
    {
        return $this->getDb()->get_var(
            "SELECT MAX(updated_at) FROM {$this->tableName()}"
        );
    }

    public function countAll()
    {
        return (int) $this->getDb()->get_var("SELECT COUNT(*) FROM {$this->tableName()}");
    }

    public function clearQueue()
    {
        $this->getDb()->query("DELETE FROM {$this->tableName()}");
    }

    public function clearPending()
    {
        $this->getDb()->delete($this->tableName(), ['status' => 'pending']);
    }

    public function findByPostId($post_id)
    {
        $table = $this->tableName();
        return $this->getDb()->get_row(
            $this->getDb()->prepare("SELECT * FROM {$table} WHERE post_id = %d LIMIT 1", $post_id),
            ARRAY_A
        );
    }

    public function restartFailed()
    {
        $db = $this->getDb();
        $table = $this->tableName();

        $updated = $db->update(
            $table,
            [
                'status' => 'pending',
                'updated_at' => current_time('mysql'),
                'processing_time' => 0,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'ai_cost' => 0,
            ],
            ['status' => 'failed'],
            ['%s', '%s'],
            ['%s']
        );

        return (int) ($updated !== false ? $updated : 0);
    }

    /**
     * Summarise and accumulate AI usage stats for a given post.
     *
     * @param int   $post_id
     * @param array $stats    ['prompt_tokens'=>int, 'completion_tokens'=>int, 'ai_cost'=>float]
     * @return bool
     */
    public function updateAiStat($post_id, array $stats)
    {
        if (!$stats)
        {
            return false;
        }

        $db    = $this->getDb();
        $table = $this->tableName();

        // Fetch existing values
        $row = $db->get_row(
            $db->prepare(
                "SELECT prompt_tokens, completion_tokens, ai_cost FROM {$table} WHERE post_id = %d",
                $post_id
            ),
            ARRAY_A
        );
        if (!$row)
        {
            return false;
        }

        // Compute new totals
        $data   = [];
        $formats = [];

        $fields = [
            'prompt_tokens'    => '%d',
            'completion_tokens' => '%d',
            'ai_cost'          => '%f',
        ];

        foreach ($fields as $key => $format)
        {
            if (! isset($stats[$key]))
            {
                continue;
            }

            // old value or zero
            $old = isset($row[$key]) ? $row[$key] : 0;

            // sum + cast
            if ($key === 'ai_cost')
            {
                $sum = (float) $old + (float) $stats[$key];
                $data[$key] = round($sum, 10);
            }
            else
            {
                $data[$key] = (int) $old + (int) $stats[$key];
            }

            $formats[] = $format;
        }

        if (empty($data))
        {
            return false;
        }

        $data['updated_at'] = current_time('mysql');
        $formats[] = '%s';

        $where   = ['post_id' => $post_id];
        $where_formats = ['%d'];

        return $db->update(
            $table,
            $data,
            $where,
            $formats,
            $where_formats
        ) !== false;
    }
}

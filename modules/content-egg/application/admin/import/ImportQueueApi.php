<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\helpers\PostHelper;
use ContentEgg\application\admin\import\ProductImportScheduler;
use ContentEgg\application\helpers\WooHelper;
use ContentEgg\application\models\ImportQueueModel;
use ContentEgg\application\models\ProductMapModel;
use ContentEgg\application\Plugin;

defined('ABSPATH') || exit;

/**
 * ImportQueueApi - Handles AJAX requests to enqueue new import jobs.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright
 *   Copyright &copy; 2025 keywordrush.com
 */
class ImportQueueApi
{
    public static function init(): void
    {
        // Authenticated users
        add_action('wp_ajax_cegg_import_enqueue', [__CLASS__, 'handle_enqueue']);
    }

    /**
     * AJAX handler to enqueue import job(s).
     * Expects POST:
     *  - nonce           (string)  'cegg_import' nonce field
     *  - preset_id       (int)
     *  - module_id       (string)
     *  - keyword         (string, optional)
     *  - payload         (JSON: either one product object OR an array of product objects; optional)
     *  - post_cat        (int, optional)
     *  - woo_cat         (int, optional)
     *  - scheduled_at    (string MySQL datetime, optional)
     *  - source_post_id  (int, optional) WP post where request originated for bridge pages
     *
     * Response on success:
     *  {
     *    created_count: number,
     *    job_ids: int[],
     *    skipped: [ { unique_id, reason, post_id?, import_job_id? } ],
     *    existing_canonical_count: number
     *  }
     */
    public static function handle_enqueue(): void
    {
        if (Plugin::isInactiveEnvato())
        {
            wp_send_json_error(['message' => __('Plugin is not activated', 'content-egg')], 400);
        }

        // Verify nonce
        if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cegg_import'))
        {
            wp_send_json_error(['message' => __('Invalid nonce', 'content-egg')], 400);
        }

        // Capability check
        if (!current_user_can('edit_posts'))
        {
            wp_send_json_error(['message' => __('Insufficient permissions', 'content-egg')], 403);
        }

        // Gather & sanitize inputs
        $preset_id = isset($_POST['preset_id']) ? absint($_POST['preset_id']) : 0;
        $module_id = isset($_POST['module_id']) ? sanitize_text_field($_POST['module_id']) : '';
        $keyword   = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';

        $category_id = null;
        if (!empty($_POST['post_cat']))
        {
            $category_id = absint($_POST['post_cat']);
        }
        elseif (!empty($_POST['woo_cat']))
        {
            $category_id = absint($_POST['woo_cat']);
        }

        $scheduled_at   = isset($_POST['scheduled_at'])  ? sanitize_text_field($_POST['scheduled_at'])  : null;
        $source_post_id = isset($_POST['source_post_id']) ? absint($_POST['source_post_id']) : null;
        if ($source_post_id === 0)
        {
            $source_post_id = null;
        }

        // Decode payload(s) if provided. Accepts an object (single) or array of objects (multiple).
        $payloads    = [];
        $payload_raw = isset($_POST['payload']) ? $_POST['payload'] : '';
        if ($payload_raw !== '')
        {
            $decoded = json_decode(stripslashes($payload_raw), true);
            if (json_last_error() === JSON_ERROR_NONE)
            {
                if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0]))
                {
                    // Array of products
                    $payloads = $decoded;
                }
                elseif (is_array($decoded))
                {
                    // Single product object
                    $payloads = [$decoded];
                }
            }
        }

        // Strip heavy/derived props from each payload for storage
        if (!empty($payloads))
        {
            foreach ($payloads as $i => $pl)
            {
                if (!is_array($pl))
                {
                    unset($payloads[$i]);
                    continue;
                }
                if (isset($pl['_descriptionText']))  unset($pl['_descriptionText']);
                if (isset($pl['_priceFormatted']))   unset($pl['_priceFormatted']);
                if (isset($pl['_priceOldFormatted'])) unset($pl['_priceOldFormatted']);
                if (isset($pl['_clicks_30d'])) unset($pl['_clicks_30d']);
                if (isset($pl['_clicks'])) unset($pl['_clicks']);
                $payloads[$i] = $pl;
            }
            // Reindex
            $payloads = array_values($payloads);
        }

        // Basic validation
        if (!$preset_id || !$module_id)
        {
            $message = __('Missing required parameters', 'content-egg');
            if (Plugin::isDevEnvironment())
            {
                if (!$preset_id) $message .= ' | Preset ID';
                if (!$module_id) $message .= ' | Module ID';
            }
            wp_send_json_error(['message' => $message], 422);
        }

        $preset = PresetRepository::get($preset_id);
        if (!$preset)
        {
            wp_send_json_error(['message' => __('Preset not found', 'content-egg')], 404);
        }

        $queue = ImportQueueModel::model();

        // If no payload provided at all: create a single keyword-based job.
        if (empty($payloads))
        {
            $job_id = $queue->enqueue($preset_id, $module_id, [], $keyword, $category_id, $scheduled_at, $source_post_id);
            if ($job_id)
            {
                ProductImportScheduler::addScheduleEvent();
                wp_send_json_success([
                    'created_count' => 1,
                    'job_ids'       => [$job_id],
                    'skipped'       => [],
                    'existing_canonical_count' => 0,
                ]);
            }
            wp_send_json_error(['message' => __('Failed to enqueue job', 'content-egg')], 500);
        }

        $created                   = [];
        $skipped                   = [];
        $existing_canonical_count  = 0;
        $avoid_duplicates          = !empty($preset['avoid_duplicates']);
        $avoid_duplicates_gtin     = !empty($preset['avoid_duplicates_gtin']);
        $make_canonical            = !empty($preset['make_canonical']);

        $mapModel = ProductMapModel::model();

        foreach ($payloads as $pl)
        {
            $item_payload = is_array($pl) ? $pl : [];
            $unique_id    = isset($item_payload['unique_id']) ? sanitize_text_field($item_payload['unique_id']) : '';
            $unique_id    = (string) $unique_id;

            // If preset requests canonical creation, and canonical already exists -> treat as success, no enqueue
            if ($make_canonical && $unique_id !== '')
            {
                $canonicalRow = $mapModel->findOne($module_id, $unique_id, ProductMapModel::CANONICAL_SOURCE);
                if ($canonicalRow)
                {
                    $existing_canonical_count++;
                    continue; // skip enqueue
                }
            }

            // Duplicate checks by unique_id
            if ($avoid_duplicates && $unique_id !== '')
            {
                $existing_post_id = PostHelper::getPostIdByUniqueId($unique_id);
                if ($existing_post_id)
                {
                    $skipped[] = [
                        'unique_id' => $unique_id,
                        'reason'    => 'exists_post',
                        'post_id'   => (int) $existing_post_id,
                    ];
                    continue; // skip enqueue for this item
                }

                $existing_job_id = ImportQueueModel::model()->findByUniqueId($unique_id);
                if ($existing_job_id)
                {
                    $skipped[] = [
                        'unique_id'     => $unique_id,
                        'reason'        => 'exists_job',
                        'import_job_id' => (int) $existing_job_id,
                    ];
                    continue; // skip enqueue for this item
                }
            }

            // Duplicate checks by GTIN/EAN
            if ($preset['post_type'] === 'product' && $avoid_duplicates_gtin)
            {
                $gtin = '';
                if (isset($item_payload['gtin']))
                {
                    $gtin = sanitize_text_field($item_payload['gtin']);
                }
                elseif (isset($item_payload['ean']))
                {
                    $gtin = sanitize_text_field($item_payload['ean']);
                }

                if ($gtin !== '')
                {
                    $existing_post_id = WooHelper::getProductIdByGtin($gtin);
                    if ($existing_post_id)
                    {
                        $skipped[] = [
                            'unique_id' => $unique_id,
                            'reason'    => 'exists_post_gtin',
                            'post_id'   => (int) $existing_post_id,
                            'gtin'      => $gtin,
                        ];
                        continue; // skip enqueue for this item
                    }
                }
            }

            // Enqueue this item
            $job_id = $queue->enqueue($preset_id, $module_id, $item_payload, $keyword, $category_id, $scheduled_at, $source_post_id);
            if ($job_id)
            {
                $created[] = (int) $job_id;
            }
            else
            {
                $skipped[] = [
                    'unique_id' => $unique_id,
                    'reason'    => 'enqueue_failed',
                ];
            }
        }

        if (!empty($created))
        {
            ProductImportScheduler::addScheduleEvent();
        }

        // Single-payload request: if no jobs created and we have a single item
        // (Note: we still return success if canonical already existed; this block is for error-style single-item Search and Import tool)
        if (count($payloads) === 1 && empty($created) && empty($existing_canonical_count) && !empty($skipped))
        {
            $first   = $skipped[0];
            $message = __('Import job exists', 'content-egg');
            $code    = 409;
            if (isset($first['reason']) && $first['reason'] === 'exists_post')
            {
                $message = __('Product exists', 'content-egg');
            }
            if (isset($first['reason']) && $first['reason'] === 'exists_post_gtin')
            {
                $message = __('GTIN exists', 'content-egg');
            }
            elseif (isset($first['reason']) && $first['reason'] === 'enqueue_failed')
            {
                $message = __('Failed to enqueue job', 'content-egg');
                $code    = 500;
            }

            $error = ['message' => $message];
            if (!empty($first['unique_id']))     $error['unique_id']     = $first['unique_id'];
            if (!empty($first['post_id']))       $error['post_id']       = $first['post_id'];
            if (!empty($first['import_job_id'])) $error['import_job_id'] = $first['import_job_id'];

            wp_send_json_error($error, $code);
        }

        // Success response with summary
        wp_send_json_success([
            'created_count'            => count($created),
            'job_ids'                  => $created,
            'skipped'                  => $skipped,
            'existing_canonical_count' => $existing_canonical_count,
        ]);
    }
}

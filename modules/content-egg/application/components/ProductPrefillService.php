<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ai\NullPrompt;
use ContentEgg\application\components\ai\PrefillPrompt;
use ContentEgg\application\helpers\ProductHelper;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\models\PrefillQueueModel;
use ContentEgg\application\Plugin;

/**
 * ProductPrefillService class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ProductPrefillService
{
    protected PrefillQueueModel $queue;
    protected PrefillKeywordResolver $keywordResolver;
    protected ContentManipulator $contentManipulator;
    protected PrefillLogger $logger;
    protected $prompt;

    protected $start_time = 0;

    public function __construct()
    {
        $this->queue = PrefillQueueModel::model();
        $this->logger = new PrefillLogger();

        $lang = GeneralConfig::getInstance()->option('ai_language');

        $this->contentManipulator = new ContentManipulator(null, $lang);

        $api_key = GeneralConfig::getInstance()->option('system_ai_key');

        if ($api_key)
        {
            $this->prompt = new PrefillPrompt($api_key, $lang);
        }
        else
        {
            $this->prompt = new NullPrompt($api_key, $lang);
        }

        $this->keywordResolver = new PrefillKeywordResolver($this->logger, $this->prompt);
    }

    public function processBatch(int $limit = 1): void
    {
        $batch = $this->queue->getNextBatch($limit);

        if (empty($batch))
        {
            return;
        }

        foreach ($batch as $i => $item)
        {
            if ($i > 0)
            {
                sleep(3);
            }

            $post_id = (int) $item['post_id'];

            try
            {
                $this->processPost($post_id);
            }
            catch (\Throwable $e)
            {
                $error_message = TextHelper::truncate($e->getMessage(), 300);

                if (Plugin::isDevEnvironment())
                {
                    $error_message .= sprintf(
                        ' [File: %s, Line: %d]',
                        $e->getFile(),
                        $e->getLine()
                    );
                }

                $this->queue->markAsFailed(
                    $post_id,
                    $this->logger->format([
                        'note' => sprintf(__('Exception: %s', 'content-egg'), $error_message),
                    ]),
                    microtime(true) - $this->start_time,
                );
            }
        }
    }

    public function processPost(int $post_id): void
    {
        $this->start_time = microtime(true);

        // 1. Load queue entry
        $row = $this->queue->findByPostId($post_id);
        if (!$row || empty($row['config_key']))
        {
            throw new \RuntimeException(
                esc_html(sprintf('Missing queue config for post ID %d', (int) $post_id))
            );
        }

        $config = get_transient($row['config_key']);

        if (!is_array($config))
        {
            throw new \RuntimeException(
                esc_html(
                    sprintf(
                        'Prefill config not found or expired for key: %s',
                        wp_strip_all_tags($row['config_key'])
                    )
                )
            );
        }

        $keyword_source = $config['keyword_source'] ?? 'post_title';
        if (!Plugin::isPro() && $keyword_source == 'fully_automatic_ai')
        {
            $keyword_source = $config['keyword_source'] = 'post_title';
        }

        // 2. Load post
        $post = get_post($post_id);
        if (!$post || $post->post_status === 'trash')
        {
            throw new \RuntimeException(
                esc_html(sprintf('Post not found or is in trash: ID %d', (int) $post_id))
            );
        }

        // 3. Validate modules
        $available_modules = ModuleManager::getInstance()->getAffiliateParsersList(true, true, true);
        $available_module_ids = array_keys($available_modules);
        $config_modules = $config['modules'] ?? [];
        $modules = array_values(array_intersect($available_module_ids, $config_modules));

        $existing_module_behavior = $config['existing_module_behavior'] ?? 'skip_module';
        $existing_modules = $this->getExistingModuleData($post_id, $modules);

        if ($existing_module_behavior === 'skip_post' && !empty($existing_modules))
        {
            $this->queue->markAsDone(
                $post_id,
                $this->logger->format([
                    'note' => __('Post skipped because existing module data was found:', 'content-egg') . ' ' . implode(', ', ModuleManager::getInstance()->getModuleNamesByIds($existing_modules)),
                ]),
                microtime(true) - $this->start_time,
            );
            return;
        }

        if ($existing_module_behavior === 'skip_module' && !empty($existing_modules) && $keyword_source !== 'fully_automatic_ai')
        {
            $skipped_modules = array_intersect($modules, $existing_modules);
            $modules = array_values(array_diff($modules, $existing_modules));

            if (!empty($skipped_modules))
            {
                $this->logger->notice(sprintf(
                    __('Skipped modules with existing data: %s', 'content-egg'),
                    implode(', ', ModuleManager::getInstance()->getModuleNamesByIds($skipped_modules))
                ));
            }
        }

        if (empty($modules))
        {
            if (!apply_filters('cegg_prefill_continue_without_modules', false) || $keyword_source == 'fully_automatic_ai')
            {
                $this->queue->markAsDone(
                    $post_id,
                    $this->logger->format([
                        'note' => __('No modules to process.', 'content-egg'),
                    ]),
                    microtime(true) - $this->start_time,
                );
                return;
            }
        }

        // *** Automatic AI processing ***
        if ($keyword_source == 'fully_automatic_ai' && Plugin::isPro())
        {
            $automatic_ai = new AutomaticAiProcessor($this->prompt, $this->logger, $this->contentManipulator);
            $automatic_ai->processAndSave($post, $modules);

            $this->queue->markAsDone(
                $post_id,
                $this->logger->format([
                    'keyword_source' => $config['keyword_source'] ?? '',
                ]),
                microtime(true) - $this->start_time,
            );

            return;
        }

        // 4. Resolve Keyword
        $keyword = $this->keywordResolver->resolve($post, $config, $modules);
        if (!$keyword)
        {
            if (!apply_filters('cegg_prefill_continue_without_keyword', false))
            {
                $this->queue->markAsFailed(
                    $post_id,
                    $this->logger->format([
                        'note' => __('No keyword found for prefill.', 'content-egg'),
                    ]),
                    microtime(true) - $this->start_time,
                );
                return;
            }
        }

        // 5. Find products
        $max_products_total = (int)$config['max_products_total'] ? (int)$config['max_products_total'] : 100;
        $total_products_founded = 0;
        $product_counts = [];
        $modules_data = [];
        foreach ($modules as $module_id)
        {
            if (!$keyword)
            {
                continue;
            }

            $parser = ModuleManager::getInstance()->parserFactory($module_id);
            $max_per_module = (int)($config['max_products_per_module'] ?? 0);

            if ($max_per_module <= 0 || $max_per_module > 10)
            {
                $max_per_module = (int)($parser->getConfigInstance()->option('entries_per_page_update') ?: 10);
            }

            $settings = ['entries_per_page' => $max_per_module];

            try
            {
                $parser->getConfigInstance()->applyCustomOptions($settings);
                $data = $parser->doMultipleRequests($keyword);
            }
            catch (\Exception $e)
            {
                $this->logger->notice(sprintf(__('Module error "%s": %s', 'content-egg'), $module_id, TextHelper::truncate($e->getMessage(), 200)));
                continue;
            }

            if (!$data)
            {
                $module_name = ModuleManager::getInstance()->getModuleNameById($module_id);
                $this->logger->notice(sprintf(__('No products found for module "%s".', 'content-egg'), $module_name));
                continue;
            }

            // Assign group
            if (!empty($config['product_group']))
            {
                foreach ($data as &$product)
                {
                    $product->group = $config['product_group'];
                }
            }

            if ($total_products_founded + count($data) > $max_products_total)
            {
                $remaining = $max_products_total - $total_products_founded;
                $data = array_slice($data, 0, $remaining);
            }

            $modules_data[$module_id] = $data;

            $product_counts[$module_id] = count($data);
            $total_products_founded += count($data);

            if ($total_products_founded >= $max_products_total)
            {
                $this->logger->notice(sprintf(__('Max products limit reached: %d', 'content-egg'), $max_products_total));
                break;
            }
        }

        // 5.1 Filter products
        if ($modules_data && $config['ai_relevance_check'])
        {
            $modules_data = $this->filterIrrelevantProducts($modules_data, $post);
        }

        // 5.2 Save products
        $total_products_saved = 0;
        foreach ($modules_data as $module_id => $data)
        {
            ContentManager::saveData($data, $module_id, $post->ID);
            $total_products_saved += count($data);
        }

        if (!$total_products_saved)
        {
            if (!apply_filters('cegg_prefill_continue_without_products', false))
            {
                $this->queue->markAsFailed(
                    $post_id,
                    $this->logger->format([
                        'note' => __('No products added.', 'content-egg'),
                    ]),
                    microtime(true) - $this->start_time,
                );
                return;
            }
        }

        // 6. Insert shortcodes/blocks if configured
        if (!empty($config['shortcode_blocks']) && is_array($config['shortcode_blocks']))
        {
            $this->contentManipulator->injectAndSave($config['shortcode_blocks'], $post);
        }

        // 7. Add custom fields
        if (!empty($config['custom_fields']) && is_array($config['custom_fields']))
        {
            $this->handleCustomFields($config['custom_fields'], $modules_data, $keyword, $post);
        }

        // 8. Finish
        $this->queue->markAsDone(
            $post_id,
            $this->logger->format([
                'keyword' => $keyword  ?? '',
                'keyword_source' => $config['keyword_source'] ?? '',
                'product_counts' => $product_counts ?? [],
                'shortcode_positions' => $this->contentManipulator->getInsertedPositions(),
            ]),
            microtime(true) - $this->start_time,
        );
    }

    protected function getKeywordFromPostTitle(\WP_Post $post): string
    {
        return trim($post->post_title);
    }

    /**
     * Get list of modules that already have product data for a post.
     *
     * @param int $post_id
     * @param array $modules List of module IDs to check
     * @return array List of module IDs with existing data
     */
    protected function getExistingModuleData(int $post_id, array $modules): array
    {
        if (empty($modules))
        {
            return [];
        }

        $existing = [];

        foreach ($modules as $module_id)
        {
            if (ContentManager::isNotEmptyDataExists($post_id, $module_id))
            {
                $existing[] = $module_id;
            }
        }

        return $existing;
    }

    private function handleCustomFields(array $customFields, array $modules_data, string $keyword, \WP_Post $post): void
    {
        $added_fields = [];

        $main_product = ContentManager::getMainProduct($modules_data, 'min_price');
        if (!$main_product)
        {
            return;
        }

        foreach ($customFields as $custom_field)
        {
            if (!$custom_field['key'])
            {
                continue;
            }

            $cf_name = $custom_field['key'];
            $cf_value = $custom_field['value'];

            $cf_value = ProductHelper::replaceImportPatterns($cf_value, $main_product, $main_product, [], $keyword);

            if (!empty($cf_value) && is_string($cf_value))
            {
                update_post_meta($post->ID, $cf_name, $cf_value);
                $added_fields[] = $cf_name;
            }
        }

        if ($added_fields)
        {
            $this->logger->notice("Custom Fields Added: " . implode(', ', $added_fields));
        }
    }

    private function filterIrrelevantProducts(array $modules_data, \WP_Post $post): array
    {
        // 1. Flatten products and build mapping
        $flat = [];
        foreach ($modules_data as $module_id => $products)
        {
            foreach ($products as $prod)
            {
                $flat[] = [
                    'unique_id' => count($flat) + 1,
                    'title'     => isset($prod->title) ? (string) $prod->title : '',
                    'module_id' => $module_id,
                    'data'      => $prod,
                ];
            }
        }

        if (empty($flat))
        {
            return $modules_data;
        }

        // 2. Prepare input for AI
        $promptProducts = array_map(function ($item)
        {
            return [
                'unique_id' => $item['unique_id'],
                'title'     => $item['title'],
            ];
        }, $flat);

        // 3. Call AI to get irrelevant IDs
        $irrelevant = $this->prompt->getIrrelevantProductIDsForArticle(
            $promptProducts,
            $post->post_title,
            $post->post_content
        );

        // 4. Rebuild modules_data without irrelevant items
        $filtered = [];
        foreach ($flat as $item)
        {
            if (!in_array($item['unique_id'], $irrelevant, true))
            {
                $filtered[$item['module_id']][] = $item['data'];
            }
        }

        // 5. Log filtered counts
        $totalFiltered = count($irrelevant);
        if ($totalFiltered > 0)
        {
            $countsByModule = [];
            foreach ($flat as $item)
            {
                if (in_array($item['unique_id'], $irrelevant, true))
                {
                    $countsByModule[$item['module_id']] = ($countsByModule[$item['module_id']] ?? 0) + 1;
                }
            }
            $parts = [];
            foreach ($countsByModule as $moduleId => $count)
            {
                $name = \ContentEgg\application\components\ModuleManager::getInstance()->getModuleNameById($moduleId);
                $parts[] = "{$name}: {$count}";
            }
            $this->logger->notice(
                sprintf(
                    __('Filtered irrelevant products: %d (%s)', 'content-egg'),
                    $totalFiltered,
                    implode(', ', $parts)
                )
            );
        }

        return $filtered;
    }
}

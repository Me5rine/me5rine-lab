<?php

namespace ContentEgg\application\admin\import;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\admin\import\PresetRepository;
use ContentEgg\application\admin\import\ImportLogger;
use ContentEgg\application\components\ContentManager;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\ProductHelper;
use ContentEgg\application\models\ImportQueueModel;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\components\ai\NullPrompt;
use ContentEgg\application\helpers\PostHelper;
use ContentEgg\application\helpers\WooHelper;
use ContentEgg\application\Plugin;
use ContentEgg\application\WooIntegrator;

/**
 * Class ProductImportService
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ProductImportService
{
    const PRODUCT_GROUP = 'ProductImport';

    /** @var ImportQueueModel */
    protected $queue;

    /** @var ImportLogger */
    protected $logger;

    /** @var int job start micro-time */
    protected $startTime = 0;

    protected $productPrompt;
    protected $postPrompt;
    protected $isSysAiEnabled = false;
    protected $isAiEnabled = false;

    public function __construct()
    {
        $this->queue  = ImportQueueModel::model();
        $this->logger = new ImportLogger();

        $lang = GeneralConfig::getInstance()->option('ai_language');
        $system_ai_key = GeneralConfig::getInstance()->option('system_ai_key');

        if ($system_ai_key)
        {
            $this->productPrompt = new ImportProductPrompt($system_ai_key, $lang);
            $this->isSysAiEnabled = true;
        }
        else
        {
            $this->productPrompt = new NullPrompt($system_ai_key, $lang);
            $this->isSysAiEnabled = false;
        }
    }

    public function processBatch(int $limit = 3): void
    {
        // Prune old and excess rows from the import queue log
        if (mt_rand(1, 10) === 1)
        {
            ImportQueueModel::model()->pruneLogs();
        }

        // Rescue any truly stuck jobs back to 'pending'
        $this->queue->resetStuckJobs();

        $jobs = $this->queue->getNextBatch($limit);   // mark rows = 'working'
        if (!$jobs)
        {
            return;
        }

        foreach ($jobs as $idx => $row)
        {
            $this->logger->reset();

            if ($idx)
            {
                sleep(1);
            }

            try
            {
                $this->processJob($row);
            }
            catch (\Throwable $e)
            {
                // generic final safeguard
                $msg = $e->getMessage();

                if (Plugin::isDevEnvironment())
                {
                    $msg .= ' in ' . basename($e->getFile()) . ':' . $e->getLine();
                }
                else
                {
                    $msg = TextHelper::truncate($msg, 400);
                }

                $this->queue->markFailed($row['id'], $this->logger->format([
                    'exception' => $msg,
                ]), microtime(true) - $this->startTime);
            }
        }
    }

    /* --------------------------------------------------------------------
       Single-row processing
    -------------------------------------------------------------------- */
    protected function processJob(array $row): void
    {
        $this->startTime = microtime(true);
        $jobId   = (int) $row['id'];
        $presetId = (int) $row['preset_id'];

        /* --------------------------------------------------------------
           1. Load & validate preset
        -------------------------------------------------------------- */
        $presetPost = get_post($presetId);
        if (!$presetPost || $presetPost->post_status === 'trash')
        {
            throw new \RuntimeException(
                sprintf(
                    'Preset #%s does not exist.',
                    esc_html(sanitize_text_field($presetId))
                )
            );
        }

        $preset = PresetRepository::get($presetId);
        if (!$preset)
        {
            throw new \RuntimeException(
                sprintf(
                    'Preset meta missing for #%s',
                    esc_html(sanitize_text_field($presetId))
                )
            );
        }

        /* --------------------------------------------------------------
           2. Obtain product data
        -------------------------------------------------------------- */
        $product = null;

        if (!empty($row['payload']))
        {
            // preferred: payload was stored at enqueue time
            $product = json_decode($row['payload'], true);
        }

        if (!$product)
        {
            // fallback: fetch on-the-fly via module & keyword
            $product = $this->fetchProductDynamically($row);
            if (!$product)
            {
                throw new \RuntimeException(
                    sprintf(
                        esc_html__('No products found for keyword "%s".', 'content-egg'),
                        esc_html($row['keyword'] ?? 'unknown')
                    )
                );
            }

            // store the fetched product data in the queue payload
            $queue = ImportQueueModel::model();
            $queue->updatePayload($jobId, $product);

            $this->logger->notice(sprintf(
                __('Fetched product: "%s".', 'content-egg'),
                TextHelper::truncate($product['title'], 60) ?? 'unknown'
            ));
        }

        // If a post/product already exists with this unique_id
        if (!empty($preset['avoid_duplicates']))
        {
            $unique_id = isset($product['unique_id'])
                ? sanitize_text_field($product['unique_id'])
                : '';

            if ('' !== $unique_id)
            {
                $existing_id = PostHelper::getPostIdByUniqueId($unique_id);
                if ($existing_id)
                {
                    $msg = sprintf(
                        __('Duplicate product found (Post #%d).', 'content-egg'),
                        $existing_id
                    );
                    $this->queue->markFailed(
                        $row['id'],
                        $this->logger->format(['exception' => $msg]),
                        microtime(true) - $this->startTime
                    );
                    return;
                }
            }
        }

        // Duplicate checks by GTIN/EAN (WooCommerce products only)
        if (! empty($preset['post_type']) && $preset['post_type'] === 'product' && ! empty($preset['avoid_duplicates_gtin']))
        {
            $gtin_raw = '';
            if (! empty($product['gtin']))
            {
                $gtin_raw = (string) $product['gtin'];
            }
            elseif (! empty($product['ean']))
            {
                $gtin_raw = (string) $product['ean'];
            }

            if ($gtin_raw !== '')
            {
                $gtin_sanitized  = sanitize_text_field($gtin_raw);
                $gtin_normalized = apply_filters('cegg_wc_normalize_gtin', $gtin_sanitized);

                if ($gtin_normalized !== '')
                {
                    $existing_post_id = WooHelper::getProductIdByGtin($gtin_normalized);
                    if ($existing_post_id)
                    {
                        $msg = sprintf(
                            __('Duplicate product found by GTIN/EAN (Post #%1$d, EAN: %2$s).', 'content-egg'),
                            absint($existing_post_id),
                            $gtin_sanitized
                        );

                        $this->queue->markFailed(
                            $row['id'],
                            $this->logger->format(['exception' => $msg]),
                            microtime(true) - $this->startTime
                        );
                        return;
                    }
                }
            }
        }

        /* --------------------------------------------------------------
           2.1 Fetch price comparison products
        -------------------------------------------------------------- */
        $comparison_product_data = [];
        if (!empty($preset['price_comparison']) && $preset['price_comparison'] === 'enabled')
        {
            $max = (int) \apply_filters('cegg_import_price_comparison_max_products', 5);
            $comparison_product_data = $this->findPriceComparisonProducts($product, $max);

            if (!empty($comparison_product_data))
            {
                $moduleNames = ModuleManager::getInstance()->getModuleNamesByIds(array_keys($comparison_product_data));

                $totalComparisonProducts = 0;
                foreach ($comparison_product_data as $moduleId => $products)
                {
                    $totalComparisonProducts += count($products);
                }

                $this->logger->notice(
                    sprintf(
                        __('Found %d price comparison products in the modules: %s.', 'content-egg'),
                        $totalComparisonProducts,
                        implode(', ', $moduleNames)
                    )
                );
            }
        }

        /* --------------------------------------------------------------
           3. Create or update WP object
        -------------------------------------------------------------- */
        $createdPostId = $this->createPostFromPreset($preset, $product, $row, $comparison_product_data);

        /* --------------------------------------------------------------
            4. Map bridge page
        -------------------------------------------------------------- */
        if (!empty($row['source_post_id']))
        {
            $target_post_id = (int) $createdPostId;
            $source_post_id = (int) $row['source_post_id'];
            $module_id      = isset($row['module_id']) ? (string) $row['module_id'] : '';
            $unique_id      = isset($product['unique_id']) ? (string) $product['unique_id'] : '';

            if ($target_post_id > 0 && $module_id !== '' && $unique_id !== '')
            {
                $map = \ContentEgg\application\models\ProductMapModel::model();

                $makeCanonical = !empty($preset['make_canonical']) || !empty($row['make_canonical']);

                if ($makeCanonical)
                {
                    // only canonical mapping
                    try
                    {
                        $map->setCanonical($module_id, $unique_id, $target_post_id);
                        $this->logger->notice(__('Canonical bridge mapping created.', 'content-egg'));
                    }
                    catch (\Throwable $e)
                    {
                        $this->logger->notice(
                            sprintf(__('Failed to set canonical bridge mapping. Error: %s', 'content-egg'), $e->getMessage())
                        );
                    }
                }
                else
                {
                    // only per-post mapping
                    try
                    {
                        $map->upsertMapping($module_id, $unique_id, $source_post_id, $target_post_id);
                        $this->logger->notice(__('Per-post bridge mapping created.', 'content-egg'));
                    }
                    catch (\Throwable $e)
                    {
                        $this->logger->notice(
                            sprintf(__('Failed to create bridge mapping. Error: %s', 'content-egg'), $e->getMessage())
                        );
                    }
                }
            }
        }

        /* --------------------------------------------------------------
           5. Update queue row – SUCCESS
        -------------------------------------------------------------- */
        $this->queue->markDone($jobId, $createdPostId, $this->logger->format([
            'post_id'    => $createdPostId,
            'preset_id'  => $presetId,
            'module_id'  => $row['module_id'],
        ]), microtime(true) - $this->startTime);
    }

    /* --------------------------------------------------------------------
       Fetch product live if no payload stored
    -------------------------------------------------------------------- */
    protected function fetchProductDynamically(array $row): ?array
    {
        $moduleId = $row['module_id'];
        $keyword  = $row['keyword'] ?? '';

        if (!$moduleId)
        {
            throw new \RuntimeException(
                esc_html__('No module ID provided.', 'content-egg')
            );
        }

        if (!$keyword)
        {
            throw new \RuntimeException(
                esc_html__('No keyword provided.', 'content-egg')
            );
        }

        $settings = ['entries_per_page' => 1];

        try
        {
            $parser = ModuleManager::getInstance()->parserFactory($moduleId);
            $parser->getConfigInstance()->applyCustomOptions($settings);
            $data = $parser->doMultipleRequests($keyword);
            $data = ContentManager::dataPresavePrepare($data, $moduleId, $post_id = 0);
        }
        catch (\Exception $e)
        {
            $this->logger->notice(sprintf(
                __('Module error "%s": %s', 'content-egg'),
                ModuleManager::getInstance()->getModuleNameById($moduleId),
                TextHelper::truncate($e->getMessage(), 250)
            ));
            return [];
        }

        if ($data)
            return reset($data);
        else
            return [];
    }

    public function findPriceComparisonProducts(array $product, $max = 3)
    {
        if (empty($product['module_id']) || empty($product['unique_id']))
        {
            return [];
        }

        $current_module_id = $product['module_id'];
        $current_unique_id = $product['unique_id'];

        $modules_settings = [
            'Amazon' => [
                'results' => 1,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'AmazonNoApi' => [
                'results' => 1,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Bestbuy' => [
                'results' => 1,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Bolcom' => [
                'results' => 1,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'CjProducts' => [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Ebay2' => [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Kelkoo' => [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Kieskeurignl' => [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Viglink' => [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => true,
            ],
            'TradedoublerProducts' => [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'TradetrackerProducts' => [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Walmart' => [
                'results' => 1,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
            'Webgains' => [
                'results' => 1,
                'is_ean_search' => true,
                'is_url_search' => false,
            ],
        ];

        $active_modules = ModuleManager::getInstance()->getAffiliateParsers(true, true);
        $modules_settings = array_intersect_key($modules_settings, $active_modules);

        foreach ($modules_settings as $module_id => $settings)
        {
            if ($module_id == $current_module_id && $settings['results'] == 1)
            {
                unset($modules_settings[$module_id]);
                continue;
            }
            elseif ($module_id == $current_module_id && $settings['results'] > 1)
            {
                $modules_settings[$module_id]['results'] = $settings['results'] + 1;
            }

            if (strstr($module_id, 'Amazon') && strstr($current_module_id, 'Amazon'))
            {
                unset($modules_settings[$module_id]);
                continue;
            }

            $modules_settings[$module_id]['priority'] = ModuleManager::getInstance()->getModulePriority($module_id);
        }

        // Add active feed modules
        $feed_modules = ModuleManager::getInstance()->getActiveFeedModules();
        foreach ($feed_modules as $module_id => $feed_module)
        {
            $modules_settings[$module_id] = [
                'results' => $max,
                'is_ean_search' => true,
                'is_url_search' => false,
                'priority' => $feed_module->config('module_priority'),
            ];
        }

        // Sorting by priority
        uasort($modules_settings, function ($a, $b)
        {
            return $a['priority'] <=> $b['priority'];
        });

        $amazon_product_found = false;
        $products = [];

        foreach ($modules_settings as $module_id => $settings)
        {
            if ($amazon_product_found && strstr($module_id, 'Amazon'))
                continue;

            if ($product['ean'] && $settings['is_ean_search'])
            {
                $keyword = $product['ean'];
            }
            elseif ($product['orig_url'] && $settings['is_url_search'])
            {
                $keyword = $product['orig_url'];
            }
            else
                continue;

            if ($module_id == 'Amazon' && !Plugin::isDevEnvironment())
            {
                sleep(1);
            }

            $max_per_module = $settings['results'] ?? 1;
            $max_per_module = \apply_filters('cegg_import_price_comparison_max_per_module', $max_per_module, $module_id, $keyword);
            $settings = ['entries_per_page' => $max_per_module];

            try
            {
                $parser = ModuleManager::getInstance()->parserFactory($module_id);
                $parser->getConfigInstance()->applyCustomOptions($settings);
                $data = $parser->doMultipleRequests($keyword);
            }
            catch (\Exception $e)
            {
                $this->logger->notice(sprintf(
                    __('Module error "%s": %s', 'content-egg'),
                    $module_id,
                    TextHelper::truncate($e->getMessage(), 150)
                ));
                continue;
            }

            if (!is_array($data) || empty($data))
            {
                continue;
            }

            $products = array_merge($products, $data);

            // Use only one amazon module
            if (strstr($module_id, 'Amazon'))
            {
                $amazon_product_found = true;
            }

            if (count($products) >= $max)
                break;
        }

        $results = array_slice($products, 0, $max);

        // reformat
        $module_data = [];
        foreach ($results as $product)
        {
            if ($product->module_id == $current_module_id && $product->unique_id == $current_unique_id)
            {
                continue;
            }

            if (!isset($module_data[$product->module_id]))
            {
                $module_data[$product->module_id] = [];
            }

            $module_data[$product->module_id][] = $product;
        }

        return $module_data;
    }

    /* --------------------------------------------------------------------
       Build post/product according to preset meta
    -------------------------------------------------------------------- */
    protected function createPostFromPreset(array $preset, array $product, array $row, array $comparison_product_data = []): int
    {
        if (
            Plugin::isFree()
            && isset($preset['title'])
            && substr_compare($preset['title'], '/Pro', -4, 4) === 0
        )
        {
            throw new \RuntimeException(
                esc_html__('AI content generation presets require the Pro version. Please upgrade to Content Egg Pro to use this preset.', 'content-egg')
            );
        }

        $isWoo = ($preset['post_type'] ?? 'post') === 'product';

        if ($isWoo && !\ContentEgg\application\helpers\WooHelper::isWooActive())
        {
            throw new \RuntimeException(
                esc_html__('WooCommerce is not active. Please install and activate WooCommerce plugin.', 'content-egg')
            );
        }

        $sourceProduct = $product;

        $ai = [
            'AI.title' => '',
            'AI.content'   => '',
            'AI.short_desc'   => '',
        ];

        // ---------- 0.1 AI product processing ----------
        if (! empty($preset['ai_product_content']))
        {
            if (! $this->isSysAiEnabled)
            {
                throw new \RuntimeException(
                    esc_html__('OpenAI integration is not enabled. Please add your OpenAI API key under Content Egg → Settings → AI → OpenAI API Key.', 'content-egg')
                );
            }

            $ai_product_content = $preset['ai_product_content'];

            // Strip the "generate_" prefix:
            $gen_fields = array_map(
                fn(string $gen): string => preg_replace('/^generate_/', '', $gen),
                $ai_product_content
            );

            $product = $this->productPrompt->craftProductData($product, $gen_fields);

            $this->logger->notice(sprintf(
                esc_html__('AI product data generated: %s.', 'content-egg'),
                join(', ', $gen_fields)
            ));
        }

        // ---------- 0.2 AI-Powered Post Content ----------
        if (Plugin::isPro() && (! empty($preset['ai_title']) || ! empty($preset['ai_content']) || ! empty($preset['ai_short_desc'])))
        {
            if (!(bool)GeneralConfig::getInstance()->option('ai_key'))
            {
                throw new \RuntimeException(
                    esc_html__('AI integration is not enabled. Please add your API key under Content Egg → Settings → AI → AI API Key.', 'content-egg')
                );
            }

            $customPrompts = array_intersect_key(
                $preset,
                array_flip(['prompt1', 'prompt2', 'prompt3'])
            );

            $postPrompt = self::createPostPrompt();
            $postPrompt->setSourceProduct($sourceProduct);
            $postPrompt->setProduct($product);
            $postPrompt->setCustomPrompts($customPrompts);

            if (!empty($preset['ai_title']))
            {
                $ai_title_method_key = $preset['ai_title'];
                if ((bool)$postPrompt->getTitleMethod($ai_title_method_key))
                {
                    try
                    {
                        $ai['AI.title'] = $postPrompt->generateTitle($ai_title_method_key);
                        $postPrompt->setPostTitle($ai['AI.title']);
                    }
                    catch (\Exception $e)
                    {
                        throw new \RuntimeException(
                            'AI: Post Title generation error: ' . esc_html($e->getMessage())
                        );
                    }

                    $this->logger->notice(
                        sprintf(
                            esc_html__('AI post title generated: %s.', 'content-egg'),
                            esc_html($ai_title_method_key)
                        )
                    );
                }
            }

            if (!empty($preset['ai_content']))
            {
                $ai_description_method_key = $preset['ai_content'];
                if ((bool)$postPrompt->getDescriptionMethod($ai_description_method_key))
                {
                    try
                    {
                        $ai['AI.content'] = $postPrompt->generateDescription($ai_description_method_key);
                    }
                    catch (\Exception $e)
                    {
                        throw new \RuntimeException(
                            'AI: Post Content generation error: ' . esc_html($e->getMessage())
                        );
                    }

                    $this->logger->notice(sprintf(
                        __('AI post content generated: %s.', 'content-egg'),
                        $ai_description_method_key
                    ));
                }
            }

            if (!empty($preset['ai_short_desc']))
            {
                $ai_short_desc_method_key = $preset['ai_short_desc'];
                if ((bool)$postPrompt->getShortDescriptionMethod($ai_short_desc_method_key))
                {
                    try
                    {
                        $ai['AI.short_desc'] = $postPrompt->generateShortDescription($ai_short_desc_method_key);
                    }
                    catch (\Exception $e)
                    {
                        throw new \RuntimeException(
                            'AI: Post Short Description generation error: ' . esc_html($e->getMessage())
                        );
                    }

                    $this->logger->notice(sprintf(
                        __('AI short desc generated: %s.', 'content-egg'),
                        $ai_short_desc_method_key
                    ));
                }
            }
        }

        // ---------- 1. Resolve title / content via templates ----------
        $titleTemplate = $preset['title_tpl'] ?? '%PRODUCT.title%';
        $bodyTemplate = $preset['body_tpl']  ?? '';
        $wooShortDescTemplate = $preset['woo_short_desc_tpl']  ?? '';

        $postTitle = ProductHelper::replaceImportPatterns($titleTemplate, $sourceProduct, $product, $ai);
        $postBody = ProductHelper::replaceImportPatterns($bodyTemplate, $sourceProduct, $product, $ai);
        $wooShortDesc = ProductHelper::replaceImportPatterns($wooShortDescTemplate, $sourceProduct, $product, $ai);

        // ---------- 2. Construct post array ----------
        $postArr = [
            'post_title'   => $postTitle,
            'post_content' => $postBody,
            'post_status'  => $preset['post_status'] ?? 'draft',
            'post_author'  => $preset['author_id'],
            'post_type'    => $isWoo ? 'product' : 'post',
            'post_name'    => TextHelper::sluggable($postTitle),
        ];

        // ---------- 2.1 Schedule post if needed ----------
        $scheduled = $row['scheduled_at'] ?? '';
        $now_ts    = current_time('timestamp');

        if ($scheduled && strtotime($scheduled) > $now_ts)
        {
            $postArr['post_date'] = $scheduled;
            $postArr['post_date_gmt'] = get_gmt_from_date($scheduled);
            if ($postArr['post_status'] === 'publish')
            {
                $postArr['post_status'] = 'future';
            }

            // Log the scheduled date in the site’s timezone
            $timestamp = mysql2date('U', $scheduled);
            $format    = get_option('date_format') . ' ' . get_option('time_format');
            $when      = date_i18n($format, $timestamp);

            $this->logger->notice(
                sprintf(
                    __('Post scheduled for %s.', 'content-egg'),
                    esc_html($when)
                )
            );
        }

        // ---------- 3. Insert post ----------
        wp_set_current_user($preset['author_id']);
        $postId = wp_insert_post($postArr, true);
        if (is_wp_error($postId))
        {
            throw new \RuntimeException(esc_html($postId->get_error_message()));
        }

        // ---------- 3.1 Set WooCommerce product data ----------
        if ($isWoo)
        {
            if ($wooShortDesc)
            {
                wp_update_post([
                    'ID'           => $postId,
                    'post_excerpt' => wp_kses_post($wooShortDesc),
                ]);
            }

            if (!empty($preset['product_type']) && $preset['product_type'] == 'external')
            {
                $classname = \WC_Product_Factory::get_product_classname($postId, 'external');
                $wooprod = new $classname($postId);
                $wooprod->save();
            }

            // if manual sync is enabled, set the product to be synced
            $product_sync = GeneralConfig::getInstance()->option('woocommerce_product_sync');
            if ($product_sync == 'manually')
            {
                $product['woo_sync'] = 'true';

                if (GeneralConfig::getInstance()->option('woocommerce_attributes_sync'))
                {
                    $product['woo_attr'] = 'true';
                }
            }
        }

        // ---------- 4. Categories ----------
        $priorityCateg = isset($row['category_id']) ? (int) $row['category_id'] : 0;

        if (empty($product['categoryPath']) && !empty($product['category']))
        {
            $product['categoryPath'] = [$product['category']];
        }

        if ($isWoo)
        {
            $helper        = WooHelper::class;
            $taxonomy      = 'product_cat';
            $defaultTermId = (int) $preset['default_woo_cat'];
            $setter        = fn($postId, $termIds) => wp_set_post_terms($postId, $termIds, $taxonomy);
        }
        else
        {
            $helper        = PostHelper::class;
            $taxonomy      = 'category';
            $defaultTermId = (int) $preset['default_cat'];
            $setter        = fn($postId, $termIds) => wp_set_post_categories($postId, $termIds);
        }

        $categoryId = 0;

        /**
         * If a priority category ID is supplied AND exists in the current taxonomy, use it.
         */
        if ($priorityCateg)
        {
            $termExists = term_exists((int) $priorityCateg, $taxonomy);

            if ($termExists && !is_wp_error($termExists))
            {
                $categoryId = (int) (is_array($termExists) ? ($termExists['term_id'] ?? 0) : $termExists);
            }
        }

        /**
         * Otherwise, apply dynamic category creation rules
         */
        if (!$categoryId)
        {
            // “Create” mode: single-level category
            if (
                'create' === (string) ($preset['dynamic_categories'] ?? '')
                && ! empty($product['category'])
            )
            {
                $categoryId = $helper::createCategory($product['category']);
            }
            // “Nested” mode: multi-level path
            elseif (
                'create_nested' === (string) ($preset['dynamic_categories'] ?? '')
                && ! empty($product['categoryPath'])
                && is_array($product['categoryPath'])
            )
            {
                $categoryId = $helper::createNestedCategories($product['categoryPath']);
            }
        }

        //  Finally, fall back to the default term
        if (! $categoryId)
        {
            $categoryId = $defaultTermId;
        }

        $categoryId = absint($categoryId);

        // Apply to the post
        $setter($postId, [$categoryId]);

        // ---------- 5. Save product data ----------
        update_post_meta($postId, '_cegg_import_unique_id', $product['unique_id'] ?? '');

        $group = apply_filters('cegg_import_product_group', self::PRODUCT_GROUP);

        ContentManager::saveData([$product], $product['module_id'], $postId, true, $group);

        // Force sync now after content_egg_save_data fired
        if ($isWoo)
        {
            $preparedData = ContentManager::dataPreviewPrepare([$product], $row['module_id'], $postId);
            $syncProduct = reset($preparedData);
            WooIntegrator::wooSync($syncProduct, $row['module_id'], $postId);
        }

        foreach ($comparison_product_data as $mid => $data)
        {
            ContentManager::saveData($data, $mid, $postId, true, $group);
        }

        // ---------- 6. Custom fields from preset ----------
        if (!empty($preset['custom_fields']) && is_array($preset['custom_fields']))
        {
            $cf_added = [];
            foreach ($preset['custom_fields'] as $cf)
            {
                if (empty($cf['key']))
                {
                    continue;
                }

                $val = ProductHelper::replaceImportPatterns($cf['value'] ?? '', $sourceProduct, $product, $ai);

                $cf_added[] = $cf['key'];

                update_post_meta($postId, $cf['key'], sanitize_text_field($val));
            }

            if ($cf_added)
            {
                $this->logger->notice(sprintf(
                    __('Custom fields added: %s.', 'content-egg'),
                    implode(', ', $cf_added)
                ));
            }
        }

        // ---------- 7. Tags ----------
        if (!empty($preset['tags']))
        {
            $tags = [];
            $preset_tags = TextHelper::getArrayFromCommaList($preset['tags']);
            foreach ($preset_tags as $tag)
            {
                $tag = ProductHelper::replaceImportPatterns($tag, $sourceProduct, $product, $ai);

                if (!empty($tag))
                {
                    $tags[] = sanitize_text_field($tag);
                }
            }

            if (!empty($tags))
            {
                if ($isWoo)
                {
                    wp_set_object_terms($postId, $tags, 'product_tag', true);
                }
                else
                {
                    wp_set_post_tags($postId, $tags, true);
                }

                $this->logger->notice(sprintf(
                    __('Tags added: %d.', 'content-egg'),
                    count($tags)
                ));
            }
        }

        if ($isWoo)
        {
            $label = __('Woo Product ID: %d.', 'content-egg');
        }
        else
        {
            $label = __('Post ID: %d.', 'content-egg');
        }
        $this->logger->notice(sprintf($label, (int) $postId));

        return $postId;
    }

    public static function createPostPrompt(): ImportPostPromptPro
    {
        $config    = GeneralConfig::getInstance();
        $apiKeys   = explode(',', $config->option('ai_key'));
        $api_key   = trim($apiKeys[array_rand($apiKeys)]);
        $model     = $config->option('ai_model');
        $lang      = $config->option('ai_language');
        $temp      = $config->option('ai_temperature');
        $extraOpts = [];

        if ($model === 'openrouter/auto')
        {
            $openList   = $config->option('openrouter_models');
            $extraOpts  = TextHelper::getArrayFromCommaList($openList);
        }

        // reproducible in dev
        if (\ContentEgg\application\Plugin::isDevEnvironment())
        {
            mt_srand(12345678);
        }

        $prompt = new ImportPostPromptPro($api_key, $model, $extraOpts);
        $prompt->setLang($lang);
        $prompt->setTemperature($temp);

        return $prompt;
    }
}

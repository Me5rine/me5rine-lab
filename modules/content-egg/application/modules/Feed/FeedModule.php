<?php

namespace ContentEgg\application\modules\Feed;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\components\AffiliateFeedParserModule;
use ContentEgg\application\components\ai\ModulePrompt;
use ContentEgg\application\components\ModuleName;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\components\ContentProduct;
use ContentEgg\application\components\LinkHandler;

use function ContentEgg\prn;
use function ContentEgg\prnx;

/**
 * FeedModule class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class FeedModule extends AffiliateFeedParserModule
{
    private bool $aiMappingDone = false;

    public function info()
    {
        if (!$name = ModuleName::getInstance()->getName($this->getId()))
        {
            $name = '+ ' . __('Add new', 'content-egg');
        }

        return array(
            'name' => $name . ' [Feed]',
            'docs_uri' => 'https://ce-docs.keywordrush.com/modules/feed-modules',
        );
    }

    public function releaseVersion()
    {
        return '5.2.0';
    }

    public function isFree()
    {
        return true;
    }

    public function getParserType()
    {
        return self::PARSER_TYPE_PRODUCT;
    }

    public function defaultTemplateName()
    {
        return 'data_grid';
    }

    public function isItemsUpdateAvailable()
    {
        return true;
    }

    public function isUrlSearchAllowed()
    {
        return true;
    }

    public function getProductModel()
    {
        $parts = explode('__', strtolower($this->getId()));
        $id = end($parts);
        $classname = '\\ContentEgg\\application\\modules\\Feed\\models\\MyFeed' . $id . 'ProductModel';

        if (!class_exists($classname, true))
        {
            throw new \Exception('Model class does not exist.');
        }

        return $classname::model();
    }

    public function isCompressedFeed(): bool
    {
        if ($this->isZippedFeed() || $this->isGzipFeed())
        {
            return true;
        }

        return false;
    }

    public function getArchiveFormat(): string
    {
        return strtolower((string) $this->config('archive_format'));
    }

    public function isGzipFeed(): bool
    {
        return $this->getArchiveFormat() === 'gz';
    }

    public function isZippedFeed(): bool
    {
        return $this->getArchiveFormat() === 'zip';
    }

    public function getFeedUrl()
    {
        $url = $this->config('feed_url');

        if (!filter_var($url, FILTER_VALIDATE_URL))
        {
            throw new \Exception('Invalid Feed URL');
        }

        return $url;
    }

    public function getProductsTtl()
    {
        $ttl = (int) $this->config('sync_interval', static::PRODUCTS_TTL);

        if ($ttl < 3600)
        {
            $ttl = static::PRODUCTS_TTL;
        }

        $ttl = (int) \apply_filters('cegg_feed_products_ttl', $ttl);
        $ttl = (int) \apply_filters('cegg_feed_products_module_ttl', $ttl, $this->getId());

        return $ttl;
    }

    protected function feedProductPrepare(array $data)
    {
        $format = $this->config('feed_format');

        if (in_array($format, ['csv', 'json'], true))
        {
            $this->maybeAiAutomap($data);
        }

        $mapped_data = $this->mapProduct($data);
        $missed = array();
        if (empty($mapped_data['description']))
        {
            $mapped_data['description'] = '';
        }

        foreach (array_keys($this->getConfigInstance()->mappingFields()) as $field)
        {
            if ($this->getConfigInstance()->isMappingFieldRequared($field) && !isset($mapped_data[$field]))
            {
                $missed[] = $field;
            }
        }

        if ($missed)
        {
            throw new \Exception(
                sprintf(
                    esc_html__(
                        'The following required mapping fields are missing from the feed: %s.',
                        'content-egg'
                    ),
                    esc_html(implode(', ', $missed))
                )
            );
        }

        $product = array();
        $product['id'] = sanitize_text_field($mapped_data['id']);
        $product['title'] = sanitize_text_field($mapped_data['title']);

        if (!$product['id'] || !$product['title'])
        {
            return false;
        }

        if (isset($mapped_data['sale price']) && (float) $mapped_data['sale price'])
        {
            $product['price'] = (float) TextHelper::parsePriceAmount($mapped_data['sale price']);
        }
        else
        {
            $product['price'] = (float) TextHelper::parsePriceAmount($mapped_data['price']);
        }

        $product['stock_status'] = ContentProduct::STOCK_STATUS_UNKNOWN;

        if (!empty($mapped_data['availability']))
        {
            $availability = strtolower($mapped_data['availability']);
            $normalized = strtolower(preg_replace('/[\s_-]+/', '', $availability));
            // Matches: "out of stock", "out-of-stock", "out_of_stock", "outofstock"
            if (strpos($normalized, 'outofstock') !== false)
            {
                $product['stock_status'] = ContentProduct::STOCK_STATUS_OUT_OF_STOCK;
            }
            else
            {
                $product['stock_status'] = ContentProduct::STOCK_STATUS_IN_STOCK;
            }
        }
        elseif (isset($mapped_data['is in stock']) && $mapped_data['is in stock'] !== '')
        {
            if (filter_var($mapped_data['is in stock'], FILTER_VALIDATE_BOOLEAN))
            {
                $product['stock_status'] = ContentProduct::STOCK_STATUS_IN_STOCK;
            }
            else
            {
                $product['stock_status'] = ContentProduct::STOCK_STATUS_OUT_OF_STOCK;
            }
        }

        if (isset($mapped_data['gtin']) && TextHelper::isEan($mapped_data['gtin']))
        {
            $product['ean'] = $mapped_data['gtin'];
        }
        else
        {
            $product['ean'] = '';
        }

        if (!empty($mapped_data['direct link']))
        {
            $product['orig_url'] = $mapped_data['direct link'];
        }
        elseif ($orig_url = TextHelper::findOriginalUrl($mapped_data['affiliate link']))
        {
            $product['orig_url'] = $orig_url;
        }
        else
        {
            $product['orig_url'] = $mapped_data['affiliate link'];
        }

        $product['product'] = serialize((array) $data);

        return $product;
    }

    public function doRequest($keyword, $query_params = array(), $is_autoupdate = false)
    {
        $this->maybeImportProducts();

        if ($is_autoupdate)
            $limit = $this->config('entries_per_page_update');
        else
            $limit = $this->config('entries_per_page');

        $options = array();
        if (!empty($query_params['price_min']))
            $options['price_min'] = (float) $query_params['price_min'];
        if (!empty($query_params['price_min']))
            $options['price_max'] = (float) $query_params['price_max'];

        if (TextHelper::isEan($keyword))
        {
            $results = $this->product_model->searchByEan($keyword, $limit, $options);
        }
        elseif (filter_var($keyword, FILTER_VALIDATE_URL))
        {
            $results = $this->product_model->searchByUrl($keyword, $this->config('partial_url_match'), $limit);
        }
        else
        {
            $options['search_type'] = $this->config('search_type');
            $results = $this->product_model->searchByKeyword($keyword, $limit, $options);
        }

        if (!$results)
            return array();

        return $this->prepareResults($results);
    }

    public function doRequestItems(array $items)
    {
        $this->maybeImportProducts();
        $deeplink = $this->config('deeplink');
        foreach ($items as $key => $item)
        {
            // fix
            if (!$key)
            {
                unset($items[$key]);
                continue;
            }

            $product = $this->product_model->searchById($item['unique_id']);

            if (!$product)
            {
                if ($this->product_model->count())
                {
                    $items[$key]['stock_status'] = ContentProduct::STOCK_STATUS_OUT_OF_STOCK;
                }
                continue;
            }
            if (!$r = unserialize($product['product']))
            {
                continue;
            }

            $r = $this->mapProduct($r);

            if (isset($r['availability']))
                $items[$key]['availability'] = $r['availability'];

            if (isset($r['image ​​link']))
                $items[$key]['img'] = $r['image ​​link'];

            if (isset($r['shipping cost']))
                $items[$key]['shipping_cost'] = self::extractShippingCost($r['shipping cost']);
            else
                $items[$key]['shipping_cost'] = null;

            $items[$key]['stock_status'] = $product['stock_status'];
            if (!empty($r['sale price']))
            {
                $items[$key]['price'] = (float) TextHelper::parsePriceAmount($r['sale price']);
                if (isset($r['price']) && (float) TextHelper::parsePriceAmount($r['price']) > $items[$key]['price'])
                {
                    $items[$key]['priceOld'] = (float) TextHelper::parsePriceAmount($r['price']);
                }
            }
            else
            {
                $items[$key]['price'] = (float) TextHelper::parsePriceAmount($r['price']);
                $items[$key]['priceOld'] = 0;
            }

            $items[$key]['url'] = $r['affiliate link'];

            if ($deeplink)
                $items[$key]['url'] = LinkHandler::createAffUrl($items[$key]['orig_url'], $deeplink, $item);

            if (!empty($r['description']) && \apply_filters('cegg_feed_description_update', false))
                $items[$key]['description'] = $r['description'];
        }

        return $items;
    }

    private function prepareResults($results)
    {
        $data = array();
        $deeplink = $this->config('deeplink');

        foreach ($results as $product)
        {
            if (!$pdata = unserialize($product['product']))
                continue;

            $r = $this->mapProduct($pdata);

            $content = new ContentProduct;

            $content->unique_id = $r['id'];
            $content->title = $r['title'];
            $content->url = $r['affiliate link'];

            if (!empty($r['sale price']))
            {
                $content->price = (float) TextHelper::parsePriceAmount($r['sale price']);
                if (isset($r['price']) && (float) TextHelper::parsePriceAmount($r['price']) > $content->price)
                {
                    $content->priceOld = (float) TextHelper::parsePriceAmount($r['price']);
                }
            }
            else
            {
                $content->price = (float) TextHelper::parsePriceAmount($r['price']);
            }

            if ($content->price)
            {
                $content->price = round($content->price, 2);
            }

            if ($content->priceOld)
            {
                $content->priceOld = round($content->priceOld, 2);
            }

            if (isset($r['currency']) && strlen($r['currency']))
            {
                $content->currencyCode = $r['currency'];
            }
            else
            {
                $content->currencyCode = $this->config('currency');
            }

            if (!empty($r['description']) && $r['title'] != $r['description'])
            {
                $content->description = $r['description'];
            }

            if (!empty($r['short description']))
            {
                $content->short_description = $r['short description'];
            }

            if (!empty($r['subtitle']))
            {
                $content->subtitle = $r['subtitle'];
            }

            $content->images = [];
            foreach (['additional image link', 'additional image link 2', 'additional image link 3', 'additional image link 4'] as $field)
            {
                if (!empty($r[$field]))
                {
                    $content->images = array_merge(
                        $content->images,
                        TextHelper::getArrayFromCommaList($r[$field])
                    );
                }
            }

            if (isset($r['shipping cost']))
                $content->shipping_cost = self::extractShippingCost($r['shipping cost']);
            else
                $content->shipping_cost = null;

            if (isset($r['brand']))
            {
                $content->manufacturer = $r['brand'];
            }

            if (isset($r['isbn']))
            {
                $content->isbn = $r['isbn'];
            }

            if (isset($r['category']))
            {
                $content->category = $r['category'];

                $separators = apply_filters('cegg_feed_category_separators', array('>', '|'), $r);

                if (! empty($separators) && is_array($separators))
                {
                    $escaped = array_map(function ($sep)
                    {
                        return preg_quote($sep, '#');
                    }, $separators);

                    $pattern = '#\s*(?:' . implode('|', $escaped) . ')\s*#';

                    if (preg_match($pattern, $content->category))
                    {
                        $content->categoryPath = preg_split($pattern, $content->category);
                        $content->categoryPath = array_map('trim', $content->categoryPath);
                        $content->categoryPath = array_filter($content->categoryPath, 'strlen');
                        if (! empty($content->categoryPath))
                        {
                            $content->category = end($content->categoryPath);
                        }
                    }
                }
            }

            if (isset($r['availability']))
            {
                $content->availability = $r['availability'];
            }
            if (isset($r['image ​​link']) && filter_var($r['image ​​link'], FILTER_VALIDATE_URL))
            {
                $content->img = $r['image ​​link'];
            }

            $content->orig_url = $product['orig_url'];
            $content->stock_status = $product['stock_status'];

            $content->ean = $product['ean'];

            if ($content->orig_url != $content->url)
            {
                $content->domain = TextHelper::getHostName($content->orig_url);
            }
            else
            {
                $content->domain = $this->config('domain');
            }

            $content->merchant = \apply_filters('cegg_feed_merchant_name', '', $content->domain);

            $content->features = $this->mapAttributes($pdata);

            if ($deeplink)
            {
                $content->url = LinkHandler::createAffUrl($content->orig_url, $deeplink, (array) $content);
            }

            $data[] = $content;
        }

        return $data;
    }

    public function renderSearchPanel()
    {
        $this->render('search_panel', array('module_id' => $this->getId()));
    }

    public function renderResults()
    {
        PluginAdmin::render('_metabox_results', array('module_id' => $this->getId()));
    }

    public function renderSearchResults()
    {
        PluginAdmin::render('_metabox_search_results', array('module_id' => $this->getId()));
    }

    public function renderUpdatePanel()
    {
        $this->render('update_panel', array('module_id' => $this->getId()));
    }

    public function viewDataPrepare($data)
    {
        $deeplink        = (string) $this->config('deeplink');
        $rawParamsString = (string) $this->config('tracking_params');
        $rawParamsString = trim(sanitize_text_field($rawParamsString));

        foreach ($data as $key => $item)
        {
            $baseUrl = '';
            if (!empty($deeplink) && !empty($item['orig_url']))
            {
                $baseUrl = LinkHandler::createAffUrl($item['orig_url'], $deeplink, $item);
            }
            else
            {
                $baseUrl = !empty($item['url']) ? $item['url'] : (!empty($item['orig_url']) ? $item['orig_url'] : '');
            }

            if ($rawParamsString === '')
            {
                $data[$key]['url'] = $baseUrl;
                continue;
            }

            // Parse tracking params: accept "name=value&name2=value2" (also tolerates commas/newlines)
            // Example: clickref={{post_id}}  OR  subId1=mysite1&subId2={{post_id}}
            $pairs = preg_split('/[&\n,]+/', $rawParamsString);
            $resolvedParams = array();
            $paramNames     = array();

            foreach ($pairs as $pair)
            {
                $pair = trim($pair);
                if ($pair === '')
                {
                    continue;
                }

                // Split into name and value at the first "="
                $eqPos = strpos($pair, '=');
                $name  = $eqPos === false ? $pair : substr($pair, 0, $eqPos);
                $valueTemplate = $eqPos === false ? '' : substr($pair, $eqPos + 1);

                // Sanitize name
                $name = trim($name);
                $name = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $name);

                if ($name === '')
                {
                    continue;
                }

                $valueTemplate = trim($valueTemplate);

                // Resolve dynamic placeholders
                $value = (string) LinkHandler::getUrlTemplate($baseUrl, $valueTemplate, $item);

                $resolvedParams[$name] = $value;
                $paramNames[] = $name;
            }

            if (!empty($resolvedParams))
            {
                $url = remove_query_arg($paramNames, $baseUrl);
                $url = add_query_arg($resolvedParams, $url);

                $data[$key]['url'] = esc_url_raw($url);
            }
            else
            {
                $data[$key]['url'] = $baseUrl;
            }
        }

        return parent::viewDataPrepare($data);
    }

    public function mapProduct(array $data)
    {
        $mapping = $this->config('mapping');

        // Awin feeds trick for multiple image support
        if (isset($mapping['additional image link']) && $mapping['additional image link'] === 'alternate_image')
        {
            $mapping['additional image link 2'] = 'alternate_image_two';
            $mapping['additional image link 3'] = 'alternate_image_three';
            $mapping['additional image link 4'] = 'alternate_image_four';
        }

        $mapped_data = array();

        foreach ($mapping as $field => $feed_field)
        {
            // regex syntax: [regex][pattern][feed_field]
            if (strpos($feed_field, '[regex]') === 0)
            {
                $parts = explode('][', $feed_field);
                if (count($parts) == 3)
                {
                    $pattern = trim($parts[1], '[]');
                    $feed_field = trim($parts[2], '[]');

                    if (!isset($data[$feed_field]))
                        continue;

                    if (strpos($pattern, chr(0)) !== false || !trim($pattern))
                        continue;

                    if (@preg_match($pattern, $data[$feed_field], $matches))
                    {
                        if (count($matches) > 1)
                            $mapped_data[$field] = $matches[1];
                        else
                            $mapped_data[$field] = $matches[0];
                    }
                    else
                    {
                        $mapped_data[$field] = '';
                    }
                }
                continue;
            }

            if (isset($data[$feed_field]))
            {
                $mapped_data[$field] = $data[$feed_field];
            }
        }

        return $mapped_data;
    }

    protected function mapAttributes(array $data)
    {
        $mapping = $this->config('mapping');
        if (!isset($mapping['attributes']) || !$mapping['attributes'])
            return array();

        $fieldsToExtract = TextHelper::getArrayFromCommaList($mapping['attributes']);

        $attributes = array();
        foreach ($fieldsToExtract as $field)
        {
            $parts = explode('->', $field);
            if (count($parts) == 2)
            {
                $field = $parts[0];
                $name = $parts[1];
            }
            else
            {
                $name = $field;
            }

            $value = $data[$field];
            $value = preg_replace('/,\s*/', ', ', $value);

            if (isset($data[$field]))
            {
                $attributes[] = array(
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        return $attributes;
    }

    protected function maybeAiAutomap($data)
    {
        // only once per feed
        if ($this->aiMappingDone)
        {
            return null;
        }

        $this->aiMappingDone = true;

        if ($this->getLastImportError())
        {
            throw new \RuntimeException('Cannot perform AI mapping due to previous import errors.');
        }

        $mapping = $this->config('mapping');

        if ($this->getConfigInstance()->isAllRequiredFieldsFilled($mapping))
        {
            return null;
        }

        if ($this->config('auto_mapping') !== 'enabled')
        {
            return null;
        }

        return $this->aiAutomap($data);
    }

    /**
     * Perform AI-driven mapping of raw product data to platform-standard fields.
     *
     * @param array|string $data  A single product row/node in CSV (array), XML (string) or JSON (string) form.
     */
    protected function aiAutomap($data): array
    {
        $prompt     = $this->getPrompt();
        $fields     = $this->buildNormalizedFieldList();
        $excluded   = ['product node', 'attributes', 'short description', 'isbn', 'subtitle'];

        $fieldNames = array_values(array_diff(array_keys($fields), $excluded));

        if ($data instanceof \SimpleXMLElement)
        {
            $data = $data->asXML();
            if ($data === false)
            {
                throw new \RuntimeException('Failed to serialize XML node for AI mapping.');
            }
        }

        try
        {
            $format      = $this->config('feed_format');
            $suggestions = $this->askAiForMapping($prompt, $format, $data, $fieldNames);
        }
        catch (\Throwable $e)
        {
            $message = sprintf(
                /* translators: %s: exception message */
                esc_html__('AI mapping failed: %s', 'content-egg'),
                esc_html($e->getMessage())
            );

            throw new \RuntimeException(
                esc_html($message),
                0
            );
        }

        $suggestions = $this->normaliseSuggestionKeys($suggestions);

        $mapping = $this->applyAiSuggestions($suggestions);
        $this->persistMapping($mapping);

        if (!$this->getConfigInstance()->isAllRequiredFieldsFilled($mapping))
        {
            $missing = implode(', ', $this->getConfigInstance()->missingRequired($mapping));
            throw new \RuntimeException(
                sprintf(
                    esc_html__(
                        'AI mapping did not cover required fields: %s. Please map them manually.',
                        'content-egg'
                    ),
                    esc_html($missing)
                )
            );
        }

        return $mapping;
    }

    /**
     * Instantiate and return a ModulePrompt configured with the API key.
     */
    protected function getPrompt(): ModulePrompt
    {
        $apiKey = GeneralConfig::getInstance()->option('system_ai_key');
        if (!$apiKey)
        {
            throw new \RuntimeException(
                esc_html__(
                    'OpenAI API key is not configured. Please add it under Content Egg → Settings → AI → OpenAI API Key.',
                    'content-egg'
                )
            );
        }

        return new ModulePrompt($apiKey);
    }

    /**
     * Retrieve and normalize the list of allowed mapping fields.
     */
    private function buildNormalizedFieldList(): array
    {
        $fields = $this->getConfigInstance()->mappingFields();
        $normalized = [];

        foreach (array_keys($fields) as $field)
        {
            $normalized[$this->stripControlChars($field)] = true;
        }

        return $normalized;
    }

    /**
     * Remove zero‑width and other control characters from a string.
     */
    private function stripControlChars(string $value): string
    {
        return preg_replace('/\p{Cf}/u', '', $value) ?? $value;
    }

    /**
     * Build the final mapping from AI suggestions.
     */
    private function applyAiSuggestions(array $suggestions): array
    {
        $mapping = [];

        $allFields = array_keys($this->getConfigInstance()->mappingFields());
        $currentMapping = $this->config('mapping');

        if (!empty($this->product_node))
        {
            $allFields[] = 'product node';
            $suggestions['product node'] = $this->product_node;
        }

        foreach ($allFields as $field)
        {
            if (!empty($suggestions[$field]) && $suggestions[$field] !== 'unknown')
            {
                $mapping[$field] = $suggestions[$field];
            }
            else
            {
                if (isset($currentMapping[$field]))
                {
                    $mapping[$field] = $currentMapping[$field];
                }
                else
                {
                    $mapping[$field] = '';
                }
            }
        }

        return $mapping;
    }

    /**
     * Persist the mapping to the database and current configuration instance
     */
    private function persistMapping(array $mapping): void
    {
        $this->getConfigInstance()->set_current('mapping', $mapping);
        FeedConfig::updateOption('mapping', $mapping, 'content-egg_' . $this->getId());
    }

    /**
     * Decide which AI prompt method to call for the given format.
     */
    private function askAiForMapping($prompt, $format, $data, $fieldNames): array
    {
        switch ($format)
        {
            case 'csv':
                return $prompt->suggestFieldsMappingCsv($data, $fieldNames);
            case 'xml':
                return $prompt->suggestFieldsMappingXml($data, $fieldNames);
            case 'json':
                return $prompt->suggestFieldsMappingJson($data, $fieldNames);
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        esc_html__('Unsupported format: %s', 'content-egg'),
                        esc_html($format)
                    )
                );
        }
    }

    private function normaliseSuggestionKeys(array $suggestions): array
    {
        $aliases = [
            'image link' => 'image ​​link',
        ];

        foreach ($aliases as $from => $to)
        {
            if (isset($suggestions[$from]) && !isset($suggestions[$to]))
            {
                $suggestions[$to] = $suggestions[$from];
                unset($suggestions[$from]);
            }
        }

        return $suggestions;
    }

    protected function mapXmlData(\SimpleXMLElement $node): array
    {
        $this->maybeAiAutomap($node);

        return parent::mapXmlData($node);
    }

    public static function getPriceParamMap()
    {
        return [
            'min' => 'price_min',
            'max' => 'price_max',
        ];
    }
}

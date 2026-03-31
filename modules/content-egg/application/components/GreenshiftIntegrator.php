<?php

namespace ContentEgg\application\components;

use ContentEgg\application\BlockShortcode;
use ContentEgg\application\helpers\TemplateHelper;



defined('\ABSPATH') || exit;

/**
 * GreenshiftIntegrator class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class GreenshiftIntegrator extends Content
{
    public static function getAllowedFilters()
    {
        $filters = array(
            'next' => array(
                'name' => 'Next',
                'description' => 'The next parameter is used if you want to divide the entire list into separate blocks.',
                'type' => 'Integer',
            ),
            'limit' => array(
                'name' => 'Limit',
                'description' => 'The limit parameter determines the maximum number of products in the sequence.',
                'type' => 'Integer',
            ),
            'offset' => array(
                'name' => 'Offset',
                'description' => 'The offset parameter specifies where the sequence of products will start. The first product has an offset number of 0.',
                'type' => 'Integer',
            ),
            'modules' => array(
                'name' => 'Modules',
                'description' => 'Enter one or more module IDs, separated by commas.',
                'type' => 'String',
                'allowed_values' => ModuleManager::getInstance()->getParserModuleIdsByTypes('PRODUCT', true),
            ),
            'groups' => array(
                'name' => 'Groups',
                'description' => 'Enter one or more product groups, separated by commas.',
                'type' => 'String',
            ),
            'currency' => array(
                'name' => 'Currency',
                'description' => 'Specify a 3-letter ISO currency code to convert all offers in your product list to a single currency.',
                'type' => 'String',
            ),
            /*
            'post_id' => array(
                'name' => 'Post ID',
                'description' => 'Specify the post ID to display Content Egg data from.',
                'type' => 'Integer',
            ),
            */
        );

        $filters = \apply_filters('cegg_greenshift_allowed_filters', $filters);
        return $filters;
    }

    public static function getAllowedProductFields()
    {
        $fields = array(
            'title',
            'description',
            'img',
            'url',
            'last_update',
            'price',
            'priceOld',
            //'total_price',
            'percentageSaved',
            //'currency',
            //'currencyCode',
            //'manufacturer',
            //'category',
            'merchant',
            'logo',
            'domain',
            'rating',
            //'ratingDecimal',
            //'reviewsCount',
            //'availability',
            //'orig_url',
            'ean',
            'shipping_cost',
            'stock_status',
            'group',
            'unique_id',
        );

        $fields = \apply_filters('cegg_greenshift_allowed_product_fields', $fields);
        return $fields;
    }

    public static function getProductData($post_id, $params = array())
    {
        $params['post_id'] = $post_id;
        $params['template'] = 'greenshift';
        $params['order'] = 'asc';

        $data =  BlockShortcode::getInstance()->viewData($params, '', true);
        if (!$data || !is_array($data))
            return array();

        $all_items = TemplateHelper::sortAllByPrice($data, $params['order']);
        $products = self::prepareProducts($all_items, $post_id);

        $products = \apply_filters('cegg_greenshift_products', $products);

        return $products;
    }

    static private function prepareProducts(array $all_items, $post_id)
    {
        $allowed_fields = self::getAllowedProductFields();
        $allowed_fields = array_combine($allowed_fields, $allowed_fields);

        $results = array();
        foreach ($all_items as $item)
        {
            $r = array_intersect_key($item, $allowed_fields);

            if ($r['price'])
                $r['price'] = TemplateHelper::formatPriceCurrency($item['price'], $item['currencyCode']);
            else
                $r['price'] = '';

            if ($r['priceOld'])
                $r['priceOld'] = TemplateHelper::formatPriceCurrency($item['priceOld'], $item['currencyCode']);
            else
                $r['priceOld'] = '';

            if (!empty($r['total_price']))
                $r['total_price'] = TemplateHelper::formatPriceCurrency($item['total_price'], $item['currencyCode']);
            else
                $r['total_price'] = '';

            if (isset($r['shipping_cost']) && $r['shipping_cost'] !== '')
                $r['shipping_cost'] = TemplateHelper::formatPriceCurrency($item['shipping_cost'], $item['currencyCode']);
            else
                $r['shipping_cost'] = '';

            if (!empty($r['stock_status']))
                $r['stock_status'] = TemplateHelper::getStockStatusStr($item);
            else
                $r['stock_status'] = '';

            if (!empty($r['last_update']))
                $r['last_update'] = TemplateHelper::getLastUpdateFormatted($item['module_id'], $post_id);
            else
                $r['last_update'] = '';

            if (!empty($r['merchant']))
                $r['merchant'] = TemplateHelper::getMerchantName($item);
            else
                $r['merchant'] = '';

            $r['logo'] = TemplateHelper::getMerhantLogoUrl($item, true);

            $results[] = $r;
        }

        return $results;
    }
}

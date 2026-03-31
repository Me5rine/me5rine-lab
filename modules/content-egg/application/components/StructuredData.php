<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ContentProduct;
use ContentEgg\application\helpers\TemplateHelper;
use ContentEgg\application\helpers\TextHelper;



/**
 * StructuredData class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class StructuredData
{
    public static function initAction()
    {
        if (GeneralConfig::getInstance()->option('add_schema_markup') == 'disabled')
            return;

        \add_action('wp_footer', array(__CLASS__, 'maybeOutputStructuredData'), 10);
    }

    public static function maybeOutputStructuredData()
    {
        // enabled for all custom post types other than products
        if (!\is_single() || \is_singular(array('product')))
            return;

        self::outputStructuredData();
    }

    public static function outputStructuredData()
    {
        global $post;

        if (empty($post))
            return;

        if (!$data = self::getStructuredData($post->ID))
            return;

        echo '<script type="application/ld+json">' . \_wp_specialchars(\wp_json_encode($data), ENT_NOQUOTES, 'UTF-8', true) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public static function getStructuredData($post_id)
    {
        if (!$items = ContentManager::getViewProductData($post_id))
            return;

        foreach ($items as $i => $d)
        {
            if (isset($d['stock_status']) && $d['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
                unset($items[$i]);
            if (empty($d['price']))
                unset($items[$i]);
        }
        if (!$items = array_values($items))
            return;

        $items = TemplateHelper::sortByPrice($items);

        $min_price_item = $item = reset($items);
        $max_price_item = end($items);
        $permalink = \get_permalink($post_id);

        $markup = array(
            '@type' => 'Product',
            '@id' => $permalink . '#product',
            'name' => \sanitize_text_field($item['title']),
            'url' => $permalink,
        );

        if ($item['img'])
            $markup['image'] = $item['img'];

        if ($item['ean'])
            $markup['gtin14'] = TextHelper::fixEan($item['ean'], 14);

        if ($item['manufacturer'])
        {
            $markup['brand'] = array(
                '@type' => 'Brand',
                'name' => $item['manufacturer'],
            );
        }

        if (!$min_price_item['converted_price'] || !$max_price_item['converted_price'])
            return;

        if ($min_price_item['converted_price'] == $max_price_item['converted_price'])
        {
            $markup['offers'] = array(
                '@type' => 'Offer',
                'price' => sprintf('%0.2f', $min_price_item['converted_price']),
            );
        }
        else
        {
            $markup['offers'] = array(
                '@type'      => 'AggregateOffer',
                'lowPrice'   => sprintf('%0.2f', $min_price_item['converted_price']),
                'highPrice'  => sprintf('%0.2f', $max_price_item['converted_price']),
                'offerCount' => count($items),
            );
        }

        $markup['offers']['priceCurrency'] = $min_price_item['currencyCode'];

        if ($min_price_item['stock_status'] == ContentProduct::STOCK_STATUS_IN_STOCK)
            $markup['offers']['availability'] = 'https://schema.org/InStock';
        elseif ($min_price_item['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            $markup['offers']['availability'] = 'https://schema.org/OutOfStock';

        $markup = \apply_filters('cegg_structured_data_single', $markup, $post_id, $items);

        return $markup;
    }
}

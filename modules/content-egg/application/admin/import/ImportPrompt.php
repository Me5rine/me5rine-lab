<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\components\ai\ContentHelper;
use ContentEgg\application\components\ai\SystemPrompt;
use ContentEgg\application\helpers\TextHelper;;

defined('\ABSPATH') || exit;

/**
 * ImportPrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ImportPrompt extends SystemPrompt
{
    const MAX_PRODUCT_DESC_CHARS = 3000;

    const MAX_REVIEW_LENGTH = 2000;
    const MAX_JOINED_REVIEWS_LENGTH = 8000;

    public static function prepareProductData(array $product, array $fields = []): array
    {
        if (empty($fields))
        {
            $fields = [
                'title',
                'description',
                'price',
                'userRating',
                'userReviewsCount',
                'specifications',
                //'userReviews',
            ];
        }

        $data = [];

        foreach ($fields as $field)
        {
            if ($field === 'title')
            {
                $data['title'] = (string) TextHelper::truncate($product['title'] ?? '', 350);
            }
            elseif ($field === 'description')
            {
                $maxChars   = apply_filters('cegg_import_ai_max_desc_chars', self::MAX_PRODUCT_DESC_CHARS);
                $plainText  = ContentHelper::htmlToText($product['description'] ?? '');
                $data['description'] = TextHelper::truncate($plainText, $maxChars);
            }
            elseif ($field === 'price')
            {
                $data['price'] = self::getPriceWithCurrency($product);
            }
            elseif ($field === 'url')
            {
                $data['url'] = ! empty($product['orig_url'])
                    ? esc_url_raw($product['orig_url'])
                    : '';
            }
            elseif ($field === 'specifications' && $specs = self::joinSpecs($product))
            {
                $data['specifications'] = $specs;
            }
            elseif ($field === 'userRating')
            {
                $data['userRating'] = ! empty($product['userRating'])
                    ? $product['ratingDecimal'] . '/5'
                    : '';
            }
            elseif ($field === 'userReviewsCount')
            {
                $data['userReviewsCount'] = ! empty($product['reviewsCount'])
                    ? $product['reviewsCount']
                    : '';
            }
            elseif ($field === 'userReviews' && $reviews = self::joinReviews($product))
            {
                $data['userReviews'] = $reviews;
            }
            elseif (isset($product[$field]))
            {
                $data[$field] = $product[$field];
            }
            elseif (isset($product['data'][$field]))
            {
                $data[$field] = $product['data'][$field];
            }
            elseif (isset($product['extra'][$field]))
            {
                $data[$field] = $product['extra'][$field];
            }
            else
            {
                // ensure every key exists
                $data[$field] = '';
            }
        }

        $data = array_filter($data, function ($value)
        {
            return $value !== '' && $value !== null;
        });

        return $data;
    }

    public static function getProductDataJsonStr(array $product, array $fields = []): string
    {
        $productData = self::prepareProductData($product, $fields);

        if (empty($productData))
        {
            return '';
        }

        if (count($fields) === 1)
        {
            $productData = reset($productData);
        }

        $json = json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false)
        {
            return '';
        }

        // Wrap only when no specific fields were requested
        if (empty($fields))
        {
            return "\n<product>\n{$json}\n</product>";
        }

        return $json;
    }

    public static function getPriceWithCurrency(array $product, $aproximate = false)
    {
        if (!empty($product['price']))
            $price = $product['price'];
        else
            return '';

        if ($aproximate)
            $price = round($price);

        if (isset($product['currencyCode']))
            $currencyCode = $product['currencyCode'];
        else
            $currencyCode = '';

        if ($currencyCode == 'USD')
            $price = '$' . $price;
        else
            $price = $price . ' ' . $currencyCode;

        return $price;
    }

    public static function joinSpecs(array $product)
    {
        if (empty($product['features']) || !is_array($product['features']))
            return '';

        $features = $product['features'];

        if (!$features)
            return '';

        $results = array();
        foreach ($features as $feature)
        {
            $results[] = $feature['name'] . ': ' . $feature['value'];
        }
        $specs_joined = join("\n", $results);

        $specs_joined = TextHelper::truncate($specs_joined, self::MAX_PRODUCT_DESC_CHARS);

        return $specs_joined;
    }

    public static function joinReviews(array $product)
    {
        if (empty($product['extra']['comments']) || !is_array($product['extra']['comments']))
            return '';

        $reviews = $product['extra']['comments'];

        $results = array();
        $length = 0;
        foreach ($reviews as $review)
        {
            if (mb_strlen($review['comment'], 'UTF-8') < 20)
                continue;

            $r = $review['comment'];
            $r = preg_replace('/\n{3,}/', "\n", $r);

            $r = TextHelper::truncate($r, self::MAX_REVIEW_LENGTH);
            $results[] = $r;
            $length += mb_strlen($r, 'UTF-8');

            if ($length >= self::MAX_JOINED_REVIEWS_LENGTH)
                break;
        }

        $reviews_joined = join("\n---\n", $results);
        $reviews_joined = trim($reviews_joined, " .!,?\n\t");
        $reviews_joined .= '.';

        return $reviews_joined;
    }
}

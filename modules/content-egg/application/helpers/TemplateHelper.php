<?php

namespace ContentEgg\application\helpers;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ContentManager;
use ContentEgg\application\models\PriceHistoryModel;
use ContentEgg\application\helpers\ArrayHelper;
use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\components\ContentProduct;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\libs\amazon\AmazonLocales;
use ContentEgg\application\models\LinkIndexModel;
use ContentEgg\application\Translator;

/**
 * TemplateHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */
class TemplateHelper
{

    const MERHANT_LOGO_DIR = 'ce-logos';
    const IMG_SMALL = 'small';
    const IMG_MEDIUM = 'medium';
    const IMG_LARGE = 'large';
    const IMG_ORIGINAL = 'original';

    static $global_id = 0;
    static $shop_info = null;
    static $shop_coupons = null;
    static $merchnat_info = null;
    static $star_svg_definited = false;
    static $product_fields = null;
    static $coupon_offcanvas = array();
    static $shop_info_offcanvas = array();
    static $price_history_lowest_item = null;
    static $price_history_highest_item = null;
    static $price_history_since = null;
    static $delivery_at_checkout = false;
    private static $needsClicksJs = false;

    public static function formatPriceCurrency($price, $currencyCode, $before_symbol = '', $after_symbol = '')
    {
        if (!$price)
            return '';

        $decimal_sep = __('number_format_decimal_point', 'content-egg-tpl');
        $thousand_sep = __('number_format_thousands_sep', 'content-egg-tpl');
        if ($decimal_sep == 'number_format_decimal_point')
        {
            $decimal_sep = null;
        }
        if ($thousand_sep == 'number_format_thousands_sep')
        {
            $thousand_sep = null;
        }

        return CurrencyHelper::getInstance()->currencyFormat($price, $currencyCode, $thousand_sep, $decimal_sep, $before_symbol, $after_symbol);
    }

    public static function currencyTyping($c)
    {
        return CurrencyHelper::getInstance()->getSymbol($c);
    }

    /*
     * @deprecated
     */

    public static function number_format_i18n($number, $decimals = 0, $currency = null)
    {
        $decimal_sep = __('number_format_decimal_point', 'content-egg-tpl');
        $thousand_sep = __('number_format_thousands_sep', 'content-egg-tpl');
        if ($decimal_sep == 'number_format_decimal_point')
        {
            $decimal_sep = null;
        }
        if ($thousand_sep == 'number_format_thousands_sep')
        {
            $thousand_sep = null;
        }

        return CurrencyHelper::getInstance()->numberFormat($number, $currency, $thousand_sep, $decimal_sep, $decimals);
    }

    /*
     * @deprecated
     */

    public static function price_format_i18n($number, $currency = null)
    {
        return self::number_format_i18n($number, $decimal = null, $currency);
    }

    public static function truncate($string, $length = 80, $etc = '...', $charset = 'UTF-8', $break_words = false, $middle = false)
    {
        if ($length == 0)
        {
            return '';
        }

        if (mb_strlen($string, 'UTF-8') > $length)
        {
            $length -= min($length, mb_strlen($etc, 'UTF-8'));
            if (!$break_words && !$middle)
            {
                $string = preg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $length + 1, $charset));
            }
            if (!$middle)
            {
                return mb_substr($string, 0, $length, $charset) . $etc;
            }
            else
            {
                return mb_substr($string, 0, $length / 2, $charset) . $etc . mb_substr($string, -$length / 2, $charset);
            }
        }
        else
        {
            return $string;
        }
    }

    static public function getTimeLeft($end_time_gmt, $return_array = false)
    {
        $current_time = strtotime(gmdate("M d Y H:i:s"));
        $timeleft = strtotime($end_time_gmt) - $current_time;
        if ($timeleft < 0)
        {
            return '';
        }

        $days_left = floor($timeleft / 86400);
        $hours_left = floor(($timeleft - $days_left * 86400) / 3600);
        $min_left = floor(($timeleft - $days_left * 86400 - $hours_left * 3600) / 60);
        if ($return_array)
        {
            return array(
                'days' => $days_left,
                'hours' => $hours_left,
                'min' => $min_left,
            );
        }

        if ($days_left)
        {
            return $days_left . __('d', 'content-egg-tpl') . ' ';
        }
        elseif ($hours_left)
        {
            return $hours_left . __('h', 'content-egg-tpl') . ' ';
        }
        elseif ($min_left)
        {
            return $min_left . __('m', 'content-egg-tpl');
        }
        else
        {
            return '<1' . __('m', 'content-egg-tpl');
        }
    }

    public static function filterData($data, $field_name, $field_values, $extra = false, $inverse = false)
    {
        $results = array();
        foreach ($data as $key => $d)
        {
            if ($extra)
            {
                if (!isset($d['extra']) || !isset($d['extra'][$field_name]))
                {
                    continue;
                }
                $value = $d['extra'][$field_name];
            }
            else
            {
                if (!isset($d[$field_name]))
                {
                    continue;
                }
                $value = $d[$field_name];
            }
            if (!is_array($field_values))
            {
                $field_values = array($field_values);
            }

            if (!$inverse && in_array($value, $field_values))
            {
                $results[$key] = $d;
            }
            elseif ($inverse && !in_array($value, $field_values))
            {
                $results[$key] = $d;
            }
        }

        return $results;
    }

    public static function formatDatetime($datetime, $type = 'mysql', $separator = ' ')
    {
        if ('mysql' == $type)
        {
            return mysql2date(get_option('date_format'), $datetime) . $separator . mysql2date(get_option('time_format'), $datetime);
        }
        else
        {
            return date_i18n(get_option('date_format'), $datetime) . $separator . date_i18n(get_option('time_format'), $datetime);
        }
    }

    public static function formatDate($timestamp, $gmt = false)
    {
        if (!$timestamp)
            return '';

        if (!is_numeric($timestamp) && $t = strtotime($timestamp))
            $timestamp = $t;

        return date_i18n(get_option('date_format'), $timestamp, $gmt);
    }

    public static function splitAttributeName($attribute)
    {
        return trim(preg_replace('/([A-Z])([a-z])/', ' $1$2', $attribute));
    }

    public static function getAmazonLink($itemLinks, $description)
    {
        // api 5 fix
        if (!is_array($itemLinks) || !$itemLinks)
        {
            return '';
        }

        foreach ($itemLinks as $link)
        {
            if ($link['Description'] == $description)
            {
                return $link['URL'];
            }
        }

        return false;
    }

    public static function getLastUpdate($module_id, $post_id = null)
    {
        if (!$post_id)
        {
            global $post;
            if (!$post)
                return 0;
            $post_id = $post->ID;
        }

        $res = \get_post_meta($post_id, ContentManager::META_PREFIX_LAST_ITEMS_UPDATE . $module_id, true);
        $res2 = \get_post_meta($post_id, ContentManager::META_PREFIX_LAST_BYKEYWORD_UPDATE . $module_id, true);

        if ($res2 && $res2 > $res)
            $res = $res2;

        if (!$res)
            $res = time();

        return $res;
    }

    public static function dateFormatFromGmtAmazon($module_id, $timestamp, $time = true)
    {
        if ($module_id == 'AmazonNoApi')
        {
            $module = ModuleManager::factory($module_id);
            if ($module->config('hide_prices') == 'hide')
                return '';
        }

        return self::dateFormatFromGmt($timestamp, $time);
    }

    public static function dateFormatFromGmt($timestamp, $time = true)
    {
        $format = \get_option('date_format');
        if ($time)
        {
            $format .= ' ' . \get_option('time_format');
        }

        // last update date stored in gmt, convert into local time
        $timestamp = strtotime(\get_date_from_gmt(date('Y-m-d H:i:s', $timestamp)));

        return \date_i18n($format, $timestamp);
    }

    public static function getLastUpdateFormattedAmazon(array $data, $time = true)
    {
        if (isset($data['Amazon']))
        {
            $item = current($data['Amazon']);
        }
        elseif (isset($data['AmazonNoApi']))
        {
            $module = ModuleManager::factory('AmazonNoApi');
            if ($module->config('hide_prices') == 'hide')
                return '';

            $item = current($data['AmazonNoApi']);
        }
        else
        {
            foreach ($data as $item)
            {
                if (isset($item['module_id']) && strstr($item['module_id'], 'Amazon'))
                    break;
            }
        }

        if (empty($item['last_update']))
            return false;

        $last_update = $item['last_update'];

        return self::dateFormatFromGmt($last_update, $time);
    }

    public static function getLastUpdateFormatted($module_id, $post_id = null, $time = true)
    {
        if ($module_id == 'AmazonNoApi')
        {
            $module = ModuleManager::factory('AmazonNoApi');
            if ($module->config('hide_prices') == 'hide')
                return '';
        }

        if (!$post_id || $post_id === true) // $post_id === true - fix func params...
        {
            global $post;
            $post_id = $post->ID;
        }

        $last_update = self::getLastUpdate($module_id, $post_id);

        return self::dateFormatFromGmt($last_update, $time);
    }

    public static function filterDataByType($data, $type)
    {
        $results = array();
        foreach ($data as $module_id => $items)
        {
            $module = \ContentEgg\application\components\ModuleManager::getInstance()->factory($module_id);
            if ($module->getParserType() == $type)
            {
                $results[$module_id] = $items;
            }
        }

        return $results;
    }

    public static function filterDataByModule($data, $module_ids)
    {
        if (!is_array($module_ids))
        {
            $module_ids = array($module_ids);
        }
        $results = array();

        foreach ($data as $module_id => $items)
        {
            if (in_array($module_id, $module_ids))
            {
                $results[$module_id] = $items;
            }
        }

        return $results;
    }

    public static function priceHistoryPrices($unique_id, $plugin_id, $limit = 5)
    {
        $prices = PriceHistoryModel::model()->getLastPrices($unique_id, $plugin_id, $limit);
        $results = array();
        foreach ($prices as $price)
        {
            $results[] = array(
                'date' => strtotime($price['create_date']),
                'price' => $price['price'],
            );
        }

        return $results;
    }

    public static function priceHistoryMax($unique_id, $module_id)
    {
        if (!$price = PriceHistoryModel::model()->getMaxPrice($unique_id, $module_id))
        {
            return null;
        }

        return array('price' => $price['price'], 'date' => strtotime($price['create_date']));
    }

    public static function priceHistoryMin($unique_id, $module_id)
    {
        if (!$price = PriceHistoryModel::model()->getMinPrice($unique_id, $module_id))
        {
            return null;
        }

        return array('price' => $price['price'], 'date' => strtotime($price['create_date']));
    }

    public static function priceHistorySinceDate($unique_id, $module_id)
    {
        if (!$date = PriceHistoryModel::model()->getFirstDateValue($unique_id, $module_id))
        {
            return null;
        }

        return strtotime($date);
    }

    public static function priceChangesProducts($limit = 5)
    {
        $params = array(
            //'select' => 'DISTINCT unique_id',
            'order' => 'create_date DESC',
            'where' => 'post_id IS NOT NULL',
            'group' => 'unique_id',
            'limit' => $limit,
        );
        $prices = PriceHistoryModel::model()->findAll($params);
        $products = array();
        // find products
        foreach ($prices as $price)
        {
            if ($prod = ContentManager::getProductbyUniqueId($price['unique_id'], $price['module_id'], $price['post_id']))
            {
                $products[] = $prod;
            }
        }

        return $products;
    }

    public static function priceHistoryMorrisChart($unique_id, $module_id, $days = 180, array $options = array(), $htmlOptions = array())
    {
        $where = PriceHistoryModel::model()->prepareWhere(
            (array('unique_id = %s AND module_id = %s', array($unique_id, $module_id))),
            false
        );
        $params = array(
            'select' => 'date(create_date) as date, price as price',
            'where' => $where . ' AND TIMESTAMPDIFF( DAY, create_date, "' . \current_time('mysql') . '") <= ' . $days,
            'order' => 'date ASC'
        );
        $results = PriceHistoryModel::model()->findAll($params);
        $results = array_reverse($results);
        $prices = array();

        foreach ($results as $key => $r)
        {
            if ($key > 0 && $results[$key - 1]['date'] == $r['date'])
                continue;

            $price = array(
                'date' => $r['date'],
                'price' => $r['price'],
            );
            $prices[] = $price;
        }

        if (!$prices)
        {
            global $post;
            if (empty($post))
                return;

            $item = ContentManager::getProductbyUniqueId($unique_id, $module_id, $post->ID);
            if ($item['price'])
            {
                $prices[] = array(
                    'date' => date('Y-m-d'),
                    'price' => $item['price']
                );

                $prices[] = array(
                    'date' => date('Y-m-d', strtotime(sprintf('-%d days', $days))),
                    'price' => $item['price']
                );
            }
        }

        $data = array(
            'chartType' => 'Area',
            'data' => $prices,
            'xkey' => 'date',
            'ykeys' => array('price'),
            'labels' => array(Translator::__('Price')),
        );
        $options = array_merge($data, $options);

        $id = $module_id . '-' . $unique_id . '-chart' . rand(0, 10000);
        self::viewMorrisChart($id, $options, $htmlOptions);
    }

    public static function viewMorrisChart($id, array $options, $htmlOptions = array('style' => 'height: 250px;'))
    {
        \wp_enqueue_style('morrisjs');
        \wp_enqueue_script('morrisjs');

        if (!empty($options['chartType']) && in_array($options['chartType'], array('Line', 'Area', 'Donut', 'Bar'), true))
        {
            $chartType = $options['chartType'];
            unset($options['chartType']);
        }
        else
        {
            $chartType = 'Line';
        }
        $options['element'] = $id;

        // Pull out JS callbacks (unencoded functions)
        $jsCallbacks = array();
        if (isset($options['_js']) && is_array($options['_js']))
        {
            $jsCallbacks = $options['_js'];
            unset($options['_js']);
        }

        // Build HTML attrs
        $html_attr = '';
        // merge our left-to-right hint into style if style already provided
        $dirStyle = 'direction: ltr;';
        if (isset($htmlOptions['style']))
        {
            $htmlOptions['style'] = $dirStyle . ' ' . $htmlOptions['style'];
        }
        else
        {
            $htmlOptions['style'] = $dirStyle . ' height: 250px;';
        }
        foreach ($htmlOptions as $name => $value)
        {
            $html_attr .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
        }

        // Print container
        echo '<div id="' . esc_attr($id) . '"' . $html_attr . '></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        // Print script: JSON options + inject raw callbacks
        $json = wp_json_encode($options, JSON_UNESCAPED_SLASHES);
        echo '<script>';
        echo 'jQuery(function($){';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe JSON output from wp_json_encode().
        echo 'var opts = ' . $json . ';';
        // inject callbacks as real functions
        foreach ($jsCallbacks as $key => $fn)
        {
            // $fn should be a raw JS function string like "function(y,data){ ... }"
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intentionally outputting raw JS function strings
            echo 'opts[' . wp_json_encode((string) $key) . '] = ' . $fn . ';';
        }
        echo 'new Morris.' . esc_html($chartType) . '(opts);';
        echo '});';
        echo '</script>';
    }

    public static function isPriceAlertAllowed($unique_id = null, $module_id = null)
    {
        return \ContentEgg\application\PriceAlert::isPriceAlertAllowed($unique_id, $module_id);
    }

    public static function getCurrencyPos($currency)
    {
        return CurrencyHelper::getInstance()->getCurrencyPos($currency);
    }

    public static function getCurrencySymbol($currency)
    {
        return CurrencyHelper::getInstance()->getSymbol($currency);
    }

    public static function getCurrencyName($currency)
    {
        return CurrencyHelper::getInstance()->getName($currency);
    }

    public static function getMerchantLogoUrl(array $item, $blank_on_error = false, $color_mode = 'light')
    {
        return LogoHelper::getMerchantLogoUrl($item, $blank_on_error, $color_mode);
    }

    public static function getMerhantLogoUrl(array $item, $blank_on_error = false)
    {
        return self::getMerchantLogoUrl($item, $blank_on_error);
    }

    public static function getMerchantIconUrl(array $item, $blank_on_error = false)
    {
        return LogoHelper::getMerchantIconUrl($item, $blank_on_error);
    }

    public static function getMerhantIconUrl(array $item, $blank_on_error = false)
    {
        return self::getMerchantIconUrl($item, $blank_on_error);
    }

    public static function getMerchantName(array $item, $print = false)
    {
        return self::getMerhantName($item, $print);
    }

    public static function getMerhantName(array $item, $print = false, $small = false)
    {
        $name = '';

        if (!empty($item['merchant']) && (empty($item['domain']) || \apply_filters('cegg_merchant_name_priority', false)))
            $name = $item['merchant'];
        else
        {
            $name = ucfirst($item['domain']);

            if ($name == 'Aliexpress.com')
                $name = 'Aliexpress';
            elseif ($name == 'Flipkart.com')
                $name = 'Flipkart';
            elseif ($name == 'Ebay.com')
                $name = 'eBay';
            elseif (strstr($name, 'Ebay.'))
                $name = $name = 'eBay';
        }

        if ($name == 'eBay' && $item['merchant'] != 'eBay')
            $name = $item['merchant'];

        if ($print)
        {
            if ($small)
                echo '<small>';
            echo \esc_html($name);
            if ($small)
                echo '</small>';
        }
        else
        {
            $name = \apply_filters('cegg_merchant_name', $name, $item['domain']);
            return $name;
        }
    }

    public static function getMerchantLogoDir()
    {
        $uploads = \wp_upload_dir();
        $logo_dir = \trailingslashit($uploads['basedir']) . self::MERHANT_LOGO_DIR;
        if (is_dir($logo_dir))
        {
            return $logo_dir;
        }

        if (\wp_mkdir_p($logo_dir))
        {
            return $logo_dir;
        }
        else
        {
            return false;
        }
    }

    public static function getBlankImg()
    {
        return \ContentEgg\PLUGIN_RES . '/img/blank.gif';
    }

    public static function mergeData(array $data)
    {
        $all_items = array();

        foreach ($data as $module_id => $items)
        {
            foreach ($items as $item_ar)
            {
                $item_ar['module_id'] = $module_id;
                $all_items[] = $item_ar;
            }
        }

        return $all_items;
    }

    public static function getMaxPriceItem(array $data)
    {
        if (!$data)
            return false;

        return $data[ArrayHelper::getMaxKeyAssoc($data, 'price', true)];
    }

    public static function getMinPriceItem(array $data)
    {
        if (!$data)
            return false;

        return $data[ArrayHelper::getMinKeyAssoc($data, 'price', true)];
    }

    public static function getCommonCurrencyCode($data)
    {
        $first = reset($data);
        $currency = $first['currencyCode'];
        foreach ($data as $d)
        {
            if (!empty($d['currencyCode']) && $d['currencyCode'] != $currency)
            {
                return false;
            }
        }

        return $currency;
    }

    public static function getShopsList($data)
    {
        $list = array();
        foreach ($data as $d)
        {
            if (!isset($list[$d['domain']]))
            {
                if (!empty($d['merchant']))
                {
                    $list[$d['domain']] = $d['merchant'];
                }
                else
                {
                    $list[$d['domain']] = self::getNameFromDomain($d['domain']);
                }
            }
        }

        return $list;
    }

    public static function getNameFromDomain($domain)
    {
        $parts = explode('.', $domain);
        $merchant = $parts[0];
        if ($merchant == 'ebay')
            return 'eBay';
        elseif ($merchant == 'amazon')
            return ucfirst($domain);

        return ucfirst($merchant);
    }

    public static function sortByPrice(array $data, $order = 'asc', $field = 'price')
    {
        if (!in_array($order, array('asc', 'desc')))
            $order = 'asc';

        if (!in_array($field, array('price', 'discount', 'total_price')))
            $field = 'price';

        // convert all prices to one currency
        $currency_codes = array();
        foreach ($data as $d)
        {
            if (empty($d['currencyCode']))
                continue;

            if (!isset($currency_codes[$d['currencyCode']]))
                $currency_codes[$d['currencyCode']] = 1;
            else
                $currency_codes[$d['currencyCode']]++;
        }
        arsort($currency_codes);
        $base_currency = key($currency_codes);
        foreach ($data as $key => $d)
        {
            $rate = 1;
            if (!empty($d['currencyCode']) && $d['currencyCode'] != $base_currency)
                $rate = CurrencyHelper::getCurrencyRate($d['currencyCode'], $base_currency);

            if (!$rate)
                $rate = 1;

            if (isset($d['price']))
            {
                if ($field == 'discount')
                {
                    if (!empty($d['priceOld']))
                        $data[$key]['converted_price'] = (float) ($d['priceOld'] - $d['price']) * $rate;
                    else
                        $data[$key]['converted_price'] = 0.00001;
                }
                elseif ($field == 'total_price')
                    $data[$key]['converted_price'] = ((float) $d['price'] + (float) $d['shipping_cost']) * $rate;
                else
                    $data[$key]['converted_price'] = (float) $d['price'] * $rate;
            }
            else
            {
                $data[$key]['converted_price'] = 0;
                $data[$key]['price'] = 0;
                if ($field == 'discount' || $field == 'total_price')
                    $data[$key]['converted_price'] = 99999999999;
            }
            if (isset($d['stock_status']) && $d['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            {
                if ($field == 'discount')
                    $data[$key]['converted_price'] = -1;
                else
                    $data[$key]['converted_price'] = 0;
            }
        }

        // modules priority
        $modules_priority = array();
        foreach ($data as $d)
        {
            $module_id = $d['module_id'];

            if (isset($modules_priority[$module_id]))
                continue;

            if (!ModuleManager::getInstance()->moduleExists($module_id))
                continue;

            $module = ModuleManager::getInstance()->factory($module_id);
            $modules_priority[$module_id] = (int) $module->config('priority');
        }

        // sort by price and priority
        usort($data, function ($a, $b) use ($modules_priority)
        {
            if (!$a['price'] && !$b['price'])
                return $modules_priority[$a['module_id']] - $modules_priority[$b['module_id']];

            if (!$a['converted_price'] && !$b['converted_price'] && $a['price'] && $b['price'])
                return ($a['price'] < $b['price']) ? -1 : 1;

            if ($a['converted_price'] == -1 && $b['converted_price'] == -1 && $a['price'] && $b['price'])
                return ($a['price'] > $b['price']) ? -1 : 1;

            if (!$a['converted_price'])
                return 1;

            if (!$b['converted_price'])
                return -1;

            if ($a['converted_price'] == $b['converted_price'])
                return $modules_priority[$a['module_id']] - $modules_priority[$b['module_id']];

            if ($modules_priority[$a['module_id']] != $modules_priority[$b['module_id']])
            {
                if ($a['converted_price'] >= 30 && $b['converted_price'] >= 30 && abs($a['converted_price'] - $b['converted_price']) < 1)
                    return $modules_priority[$a['module_id']] - $modules_priority[$b['module_id']];
            }

            return ($a['converted_price'] < $b['converted_price']) ? -1 : 1;
        });

        if ($order == 'desc')
            $data = array_reverse($data);

        return $data;
    }

    public static function getNumberFromTitle($title)
    {
        if (!$title)
            return false;

        if ($title[0] !== '[')
            return false;

        if (preg_match('~^\[(\d+)\]~', $title, $matches))
            return (int) $matches[1];
        else
            return false;
    }

    public static function fixNumberedTitle($title)
    {
        $title = preg_replace('~^\[\d+\](.+)~', '$1', $title);
        $title = trim($title);
        return $title;
    }

    public static function mergeAndSort(array $data, $order = 'asc', $field = 'price')
    {
        if ($field)
            return self::sortAllByPrice($data, $order, $field);

        $items = self::mergeAll($data);

        if (self::isNumbered($items))
            return TemplateHelper::sortByNumber($items, $order);
        else
            return TemplateHelper::sortByBadgeAndPriority($items);
    }

    public static function sortByBadgeAndPriority(array $items)
    {
        $modules_priority = array();
        foreach ($items as $i => $item)
        {
            $module_id = $item['module_id'];

            if (!isset($modules_priority[$module_id]))
            {
                if (ModuleManager::getInstance()->moduleExists($module_id))
                {
                    $module = ModuleManager::getInstance()->factory($module_id);
                    $modules_priority[$module_id] = (int) $module->config('priority');
                }
                else
                    $modules_priority[$module_id] = 0;
            }

            $items[$i]['module_priority'] = $modules_priority[$module_id];
        }

        usort($items, array(self::class, 'compareByBadgeAndPriority'));
        return $items;
    }

    private static function compareByBadgeAndPriority($a, $b)
    {
        $a_has_badge = !empty($a['badge']);
        $b_has_badge = !empty($b['badge']);

        if ($a_has_badge == $b_has_badge)
            return $a['module_priority'] <=> $b['module_priority'];

        return $b_has_badge <=> $a_has_badge;
    }

    public static function sortAllByPrice(array $data, $order = 'asc', $field = 'price')
    {
        $items = self::mergeAll($data);

        if (self::isNumbered($items))
            return TemplateHelper::sortByNumber($items, $order);
        else
            return TemplateHelper::sortByPrice($items, $order, $field);
    }

    public static function isNumbered(array $data)
    {
        foreach ($data as $d)
        {
            if ($d['number'] && $d['number'] != 999)
                return true;
        }

        return false;
    }

    public static function sortByNumber(array $data, $order = 'asc')
    {
        usort($data, function ($a, $b)
        {
            if ($a['number'] > $b['number'])
                return 1;
            elseif ($a['number'] < $b['number'])
                return -1;
            return 0;
        });

        if ($order == 'desc')
            $data = array_reverse($data);

        return $data;
    }

    public static function mergeAll(array $data)
    {
        $all_items = array();
        foreach ($data as $module_id => $items)
        {
            foreach ($items as $item_ar)
            {
                $item_ar['module_id'] = $module_id;
                $all_items[] = $item_ar;
            }
        }

        return $all_items;
    }

    public static function buyNowBtnText($print = true, array $item = array(), $forced_text = '')
    {
        if (self::isLinkedToBridge($item))
        {
            return self::btnText('btn_text_bridge', __('See Details', 'content-egg-tpl'), $print, $item, $forced_text);
        }
        else
        {
            return self::btnText('btn_text_buy_now', __('BUY NOW', 'content-egg-tpl'), $print, $item, $forced_text);
        }
    }

    public static function couponBtnText($print = true, array $item = array(), $forced_text = '')
    {
        return self::btnText('btn_text_coupon', __('Shop Sale', 'content-egg-tpl'), $print, $item, $forced_text);
    }

    public static function getCurrentUserEmail()
    {
        if (!$current_user = wp_get_current_user())
        {
            return '';
        }

        return $current_user->user_email;
    }

    public static function getDaysAgo($ptime)
    {
        $etime = current_time('timestamp') - $ptime;
        if ($etime < 1)
        {
            return '';
        }
        $d = $etime / (24 * 60 * 60);

        if ($d < 1)
        {
            return Translator::__('today');
        }
        $d = ceil($d);

        if ($d > 1)
        {
            return sprintf(Translator::__('%d days ago'), $d);
        }
        else
        {
            return sprintf(Translator::__('%d day ago'), $d);
        }
    }

    public static function getPostDisclimerText($force_default = false)
    {
        if (!$force_default && $d = GeneralConfig::getInstance()->option('post_disclaimer_text'))
            return $d;
        else
            return __('This post contains affiliate links. Purchases may earn me a commission at no extra cost to you.', 'content-egg-tpl');
    }

    public static function getBlockDisclimerText($force_default = false)
    {
        if (!$force_default && $d = GeneralConfig::getInstance()->option('block_disclaimer_text'))
            return $d;
        else
            return __('I may earn a commission at no cost to you.', 'content-egg-tpl');
    }

    public static function getAmazonPriceDisclimerText($force_default = false)
    {
        if (!$force_default && $d = GeneralConfig::getInstance()->option('disclaimer_text'))
            return $d;
        else
            return
                __('Product prices and availability are accurate as of the date/time indicated and are subject to change. Any price and availability information displayed on Amazon at the time of purchase will apply to the purchase of this product.', 'content-egg-tpl')
                . ' '
                . __('As an Amazon associate I earn from qualifying purchases.', 'content-egg-tpl');
    }

    public static function getAmazonDisclaimer()
    {
        return self::getAmazonPriceDisclimerText();
    }

    public static function printAmazonDisclaimer()
    {
        echo '<i class="egg-ico-info-circle cegg-disclaimer" ' . self::buildTagParams(array('title' => self::getAmazonDisclaimer())) . '></i>'; // phpcs:ignore
    }

    public static function btnText($option_name, $default, $print = true, array $item = array(), $forced_text = '')
    {
        if ($forced_text)
        {
            $text = $forced_text;
        }
        else
        {
            $text = GeneralConfig::getInstance()->option($option_name);
            if (!$text)
            {
                $text = $default;
            }
        }

        $text = self::replacePatterns($text, $item);

        if (!$print)
        {
            return $text;
        }

        echo \esc_attr($text);
    }

    public static function replacePatterns($template, array $item)
    {
        if (!$item)
            return $template;

        if (!preg_match_all('/%[a-zA-Z0-9_\.\,\(\)]+%/', $template, $matches))
            return $template;

        $replace = array();
        foreach ($matches[0] as $pattern)
        {
            if (stristr($pattern, '%PRICE%'))
            {
                if (!empty($item['price']) && $item['currencyCode'])
                {
                    $replace[$pattern] = TemplateHelper::formatPriceCurrency($item['price'], $item['currencyCode']);
                }
                else
                {
                    $replace[$pattern] = '';
                }
                continue;
            }
            if (stristr($pattern, '%MERCHANT%'))
            {
                if ($merchant = TemplateHelper::getMerhantName($item))
                {
                    $replace[$pattern] = $merchant;
                }
                else
                {
                    $replace[$pattern] = '';
                }
                continue;
            }
            if (stristr($pattern, '%DOMAIN%'))
            {
                if (!empty($item['domain']))
                {
                    $replace[$pattern] = $item['domain'];
                }
                else
                {
                    $replace[$pattern] = TemplateHelper::getMerhantName($item);
                }
                continue;
            }
            if (stristr($pattern, '%STOCK_STATUS%'))
            {
                $replace[$pattern] = TemplateHelper::getStockStatusStr($item);
                continue;
            }
        }

        return str_ireplace(array_keys($replace), array_values($replace), $template);
    }

    public static function getStockStatusClass(array $item)
    {
        if (!isset($item['stock_status']))
            return '';

        if ($item['stock_status'] == ContentProduct::STOCK_STATUS_IN_STOCK)
            return 'instock';
        elseif ($item['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            return 'outofstock';
        elseif ($item['stock_status'] == ContentProduct::STOCK_STATUS_UNKNOWN)
            return 'unknown';
        else
            return '';
    }

    public static function getStockStatusClass5(array $item)
    {
        if (!isset($item['stock_status']))
            return '';

        if ($item['stock_status'] == ContentProduct::STOCK_STATUS_IN_STOCK)
            return 'text-success';
        elseif ($item['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            return 'text-danger';
        elseif ($item['stock_status'] == ContentProduct::STOCK_STATUS_UNKNOWN)
            return 'text-body-secondary';
        else
            return '';
    }

    public static function getStockStatusStr(array $item)
    {
        if (!isset($item['stock_status']))
            return '';

        $show_status = GeneralConfig::getInstance()->option('show_stock_status');
        if ($show_status == 'hide_status')
            return '';
        elseif ($show_status == 'show_outofstock' && $item['stock_status'] == ContentProduct::STOCK_STATUS_IN_STOCK)
            return '';
        elseif ($show_status == 'show_instock' && $item['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            return '';

        if ($item['stock_status'] == ContentProduct::STOCK_STATUS_IN_STOCK)
            return TemplateHelper::__('in stock');
        elseif ($item['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            return TemplateHelper::__('out of stock');
        else
            return '';
    }

    public static function getPrivacyUrl()
    {
        if ($id = \get_option('wp_page_for_privacy_policy', ''))
        {
            return \get_permalink($id);
        }
        else
        {
            return '';
        }
    }

    public static function getGroupsList(array $all_items, $sort_groups = array())
    {
        if (!isset($all_items[0]))
        {
            $all_items = TemplateHelper::sortAllByPrice($all_items);
        }

        $groups = array_unique(array_column($all_items, 'group'));
        $groups = array_filter($groups);
        $groups = array_values($groups);

        if ($sort_groups)
        {
            $res = array();
            foreach ($sort_groups as $g)
            {
                if (in_array($g, $groups))
                {
                    $res[] = $g;
                }
            }
            $res = array_values($res);
            return $res;
        }
        else
        {
            natsort($groups);
            $groups = array_values($groups);
            return $groups;
        }
    }

    public static function filterByGroup(array $data, $group)
    {
        $res = array();
        foreach ($data as $plugin_id => $d)
        {
            $r = array_filter($d, function ($data) use ($group)
            {
                return isset($data) && $data['group'] == $group;
            });
            if ($r)
            {
                $res[$plugin_id] = $r;
            }
        }
        return $res;
    }

    public static function filterItemsByGroup(array $items, $group)
    {
        return array_values(array_filter($items, function ($item) use ($group)
        {
            return $item['group'] === $group;
        }));
    }

    public static function generateGlobalId($prefix)
    {

        return $prefix . self::$global_id++;
    }

    public static function isModuleDataExist($items, $module_ids)
    {
        if (!is_array($module_ids))
            $module_ids = array($module_ids);

        foreach ($module_ids as $module_id)
        {
            foreach ($items as $item)
            {
                if (isset($item['module_id']) && $item['module_id'] == $module_id)
                    return true;
            }
        }
        return false;
    }

    public static function isCashbackTrakerActive()
    {
        if (class_exists('\CashbackTracker\application\Plugin'))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function getCashbackStr(array $product)
    {
        if (GeneralConfig::getInstance()->option('cashback_integration') != 'enabled')
            return '';

        if (!self::isCashbackTrakerActive())
            return '';

        return \CashbackTracker\application\components\DeeplinkGenerator::getCashbackStrByUrl($product['url']);
    }

    public static function prepareParamHideVisible($param)
    {
        if (!$param)
            return array();

        $allowed = array(
            'price',
            'priceOld',
            'domain',
            'title',
            'stock_status',
            'img',
            'merchant',
            'description',
            'button',
            'percentageSaved',
            'badge',
            'merchant',
            'promo',
            'rating',
            'disclaimer',
            'price_update',
            'number',
            'prime',
            'coupons',
            'shop_info',
            'new_used_price',
            'logo',
            'subtitle',
            'shipping_cost',
            'delivery_at_checkout',
            'startDate',
            'endDate',
            'code',
            'coupon_reveal',
        );

        $param = TextHelper::getArrayFromCommaList($param);

        if (in_array('price', $param) && !in_array('priceOld', $param))
            $param[] = 'priceOld';

        return array_intersect($param, $allowed);
    }

    public static function eanParamPrepare($ean)
    {
        if (!$ean)
            return array();

        $ean = TextHelper::getArrayFromCommaList($ean);
        $result = array();
        foreach ($ean as $e)
        {
            if (TextHelper::isEan($e))
                $result[] = TextHelper::fixEan($e);
        }

        return $result;
    }

    public static function printRel($echo = true)
    {
        if (!$rel = self::getRelValue())
        {
            return;
        }

        $res = ' rel="' . \esc_attr($rel) . '"';
        if ($echo)
        {
            echo $res; // phpcs:ignore
        }
        else
        {
            return $res;
        }
    }

    public static function getRelValue()
    {
        $rel = GeneralConfig::getInstance()->option('rel_attribute');

        return join(' ', $rel);
    }

    /**
     * GA4-safe helpers (length-limited, multibyte-aware).
     */
    private static function mb_len(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
    }

    private static function mb_sub(string $s, int $start, int $len): string
    {
        return function_exists('mb_substr') ? mb_substr($s, $start, $len, 'UTF-8') : substr($s, $start, $len);
    }

    /**
     * Trim to max length, preserving UTF-8 where possible.
     */
    private static function clamp_len(string $s, int $max): string
    {
        if ($max <= 0)
        {
            return '';
        }
        if (self::mb_len($s) <= $max)
        {
            return $s;
        }
        return self::mb_sub($s, 0, $max);
    }

    /**
     * GA4: sanitize + clamp event name to ≤ 40 chars.
     * Uses WP's sanitize_key (lowercase a-z0-9_), then clamps.
     * Falls back to 'event' if empty after sanitization.
     */
    private static function ga4_event_name(string $raw, string $fallback = 'event'): string
    {
        $name = sanitize_key($raw);
        if ($name === '')
        {
            $name = sanitize_key($fallback);
        }
        // Clamp to GA4 max length
        $name = self::clamp_len($name, 40);
        // Final fallback if somehow empty
        return $name !== '' ? $name : 'event';
    }

    /**
     * GA4: sanitize + clamp parameter name to ≤ 40 chars.
     */
    private static function ga4_param_name(string $raw): string
    {
        $key = sanitize_key($raw);
        return self::clamp_len($key, 40);
    }

    /**
     * GA4: normalize any value to a JS-safe representation,
     * clamping stringy values to ≤ 100 chars BEFORE escaping.
     */
    private static function ga4_normalize_value($val)
    {
        // Keep numeric/bool types as-is.
        if (is_int($val) || is_float($val))
        {
            return $val;
        }
        if (is_bool($val))
        {
            return $val;
        }

        // For arrays/objects, stringify to JSON then clamp.
        if (is_array($val) || is_object($val))
        {
            $val = wp_json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($val))
            {
                $val = '';
            }
        }
        else
        {
            $val = (string) $val;
        }

        // Clamp to GA4 string limit BEFORE escaping for JS.
        $val = self::clamp_len($val, 100);
        return $val;
    }

    /**
     * Build GA4 onclick JS for affiliate clicks.
     *
     * @param array $item product item data.
     * @return string JS snippet for onclick attribute, or empty string.
     */
    public static function getGtagClickEvent(array $item): string
    {
        if (GeneralConfig::getInstance()->option('send_ga_click_event') !== 'enabled')
        {
            return '';
        }

        $product_title = isset($item['title'])     ? (string) $item['title']     : 'Product Name';
        $module_id     = isset($item['module_id']) ? (string) $item['module_id'] : '';
        $merchant      = isset($item['merchant'])  ? (string) $item['merchant']  : '';
        $unique_id     = isset($item['unique_id']) ? (string) $item['unique_id'] : '';
        $post_id       = isset($item['post_id'])   ? (int) $item['post_id']      : 0;

        // Base params
        $params = array(
            'cegg_product_title' => $product_title,
            'cegg_module'        => $module_id,
            'cegg_merchant'      => $merchant,
            'cegg_unique_id'     => $unique_id,
            'cegg_post_id'       => $post_id,
        );

        // Allow devs to add/modify parameters
        $params = apply_filters('cegg_gtag_event_params', $params, $item);

        // Drop only truly empty strings/nulls (keep 0/false)
        $params = array_filter($params, static function ($v)
        {
            return $v !== '' && $v !== null;
        });

        // Event name (sanitize + clamp to GA4 limit)
        $default_event_name = 'cegg_affiliate_click';
        $event_name_raw     = apply_filters('cegg_gtag_event_name', $default_event_name, $item);
        $event_name_raw     = (string) ($event_name_raw ?: $default_event_name);
        $event_name         = self::ga4_event_name($event_name_raw, $default_event_name);

        // Build JS object literal with QUOTED keys and properly typed values
        $seenKeys = array(); // to avoid duplicate keys after clamping
        $parts    = array();

        foreach ($params as $key => $val)
        {
            $key = self::ga4_param_name((string) $key);
            if ($key === '')
            {
                continue;
            }

            // Dedup if truncation caused a collision: append numeric suffix up to limit.
            if (isset($seenKeys[$key]))
            {
                $suffix = 2;
                $base   = $key;
                // Try to append _2, _3 ... while keeping ≤ 40 chars.
                while (isset($seenKeys[$key]))
                {
                    $try = $base . '_' . $suffix;
                    if (self::mb_len($try) > 40)
                    {
                        // Trim base to make space for suffix, then retry
                        $room = 40 - (self::mb_len('_' . (string)$suffix));
                        $base = self::clamp_len($base, max(1, $room));
                        $try  = $base . '_' . $suffix;
                    }
                    $key = $try;
                    $suffix++;
                    if ($suffix > 99)
                    { // give up after reasonable attempts
                        break;
                    }
                }
            }
            $seenKeys[$key] = true;

            // Normalize/clamp value to GA4 rules
            $norm = self::ga4_normalize_value($val);

            // Always quote the key
            $quotedKey = "'" . $key . "'";

            if (is_int($norm) || is_float($norm))
            {
                $parts[] = $quotedKey . ':' . $norm; // numeric unquoted
            }
            elseif (is_bool($norm))
            {
                $parts[] = $quotedKey . ':' . ($norm ? 'true' : 'false');
            }
            else
            {
                // Escape for single-quoted JS string AFTER clamping
                $parts[] = $quotedKey . ":'" . esc_js($norm) . "'";
            }
        }

        $params_js = '{' . implode(',', $parts) . '}';

        // Final JS call
        return "gtag('event','{$event_name}',{$params_js});";
    }

    public static function printRating(array $item, $size = 'default')
    {
        if (!$item['rating'])
            return;

        if (!in_array($size, array('small', 'big', 'default')))
            $size = 'default';

        $rating = $item['rating'] * 20;
        echo '<span class="egg-stars-container egg-stars-' . esc_attr($size) . ' egg-stars-' . esc_attr($rating) . '">★★★★★</span>';
    }

    public static function getButtonColor()
    {
        if (!$color = \wp_strip_all_tags(GeneralConfig::getInstance()->option('button_color')))
        {
            $color = '#d9534f';
        }

        return $color;
    }

    public static function getPriceColor()
    {
        if (!$color = \wp_strip_all_tags(GeneralConfig::getInstance()->option('price_color')))
        {
            $color = '#dc3545';
        }

        return $color;
    }

    public static function getButtonColorHower()
    {
        return TemplateHelper::adjustBrightness(TemplateHelper::getButtonColor(), -0.15);
    }

    public static function adjustBrightness($hexCode, $adjustPercent)
    {
        $hexCode = ltrim($hexCode, '#');

        if (strlen($hexCode) == 3)
        {
            $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
        }

        $hexCode = array_map('hexdec', str_split($hexCode, 2));

        foreach ($hexCode as &$color)
        {
            $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
            $adjustAmount = ceil($adjustableLimit * $adjustPercent);

            $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
        }

        return '#' . implode($hexCode);
    }

    public static function findAmazonLocales(array $items)
    {
        $locales = array();
        foreach ($items as $item)
        {
            if (!isset($item['extra']['locale']))
            {
                continue;
            }
            if (!in_array($item['extra']['locale'], $locales))
            {
                $locales[] = $item['extra']['locale'];
            }
        }

        return $locales;
    }

    /*
     * @link: https://webservices.amazon.com/paapi5/documentation/add-to-cart-form.html
     */

    public static function generateAddAllToCartUrl(array $items, $locale)
    {
        $url = 'https://www.' . AmazonLocales::getDomain($locale) . '/gp/aws/cart/add.html?';

        $i = 1;
        foreach ($items as $item)
        {
            if (!isset($item['extra']['locale']) || $item['extra']['locale'] != $locale)
            {
                continue;
            }

            if ($i > 1)
            {
                $url .= '&';
            }

            $url .= 'ASIN.' . $i . '=' . $item['extra']['ASIN'] . '&Quantity.' . $i . '=1';
            $i++;
        }

        $url .= '&AssociateTag=' . self::getAssociateTagForAmazonLocale($locale, $item['module_id']);

        return $url;
    }

    public static function getAssociateTagForAmazonLocale($locale, $module_id = 'Amazon')
    {
        if ($module_id == 'AmazonNoApi')
        {
            $module = ModuleManager::factory('AmazonNoApi');
        }
        else
        {
            $module = ModuleManager::factory('Amazon');
        }

        return $module->getAssociateTagForLocale($locale);
    }

    public static function __($str)
    {
        return Translator::translate($str);
    }

    public static function esc_html_e($str)
    {
        echo esc_html(Translator::translate($str));
    }

    public static function displayImage(array $item, $max_width = 0, $max_height = 0, array $params = array())
    {
        if (!isset($item['img']))
            return;

        $params['src'] = self::getOptimizedImage($item, $max_width, $max_height);

        $params['decoding'] = 'async';
        $params['loading'] = 'lazy';

        if (!empty($item['title']))
            $params['alt'] = $item['title'];
        elseif (!empty($item['_alt']))
            $params['alt'] = $item['_alt'];

        echo '<img ' . self::buildTagParams($params) . ' />'; // phpcs:ignore
    }

    public static function buildTagParams($params = array())
    {
        $res = '';
        $i = 0;

        foreach ($params as $key => $value)
        {
            if ($i > 0)
                $res .= ' ';

            $res .= \esc_attr($key) . '="' . \esc_attr($value) . '"';
            $i++;
        }

        return $res;
    }

    public static function getImageSizesRatio(array $item, $max_width, $max_height)
    {
        if ($item['module_id'] == 'Amazon' && strpos($item['img'], 'https://m.media-amazon.com') !== false)
        {
            if (!isset($item['extra']['primaryImages']))
            {
                return array();
            }

            $width = $item['extra']['primaryImages']['Large']['Width'];
            $height = $item['extra']['primaryImages']['Large']['Height'];

            if (!$max_width)
            {
                $max_width = $width;
            }
            if (!$max_height)
            {
                $max_height = $height;
            }

            $ratio = $width / $height;

            if ($ratio > 1 && $width > $max_width)
            {
                return array('width' => round($max_width), 'height' => round($max_width / $ratio));
            }
            else
            {
                return array('width' => round($max_height * $ratio), 'height' => round($max_height));
            }
        }

        return array();
    }

    public static function getOptimizedImage(array $item, $max_width = 0, $max_height = 0)
    {
        $item['img'] = preg_replace('/\._AC_SL\d+_\./', '._SS520_.', $item['img']);
        $item['img'] = preg_replace('/\._SL\d+_\./', '._SS520_.', $item['img']);

        if ($item['module_id'] == 'Amazon' && strpos($item['img'], 'https://m.media-amazon.com') !== false)
        {
            if (!isset($item['extra']['primaryImages']))
                return $item['img'];

            if ($max_height && $max_height <= 160)
                return $item['extra']['primaryImages']['Medium']['URL'];
            elseif ($max_height && $max_height <= 75)
                return $item['extra']['primaryImages']['Small']['URL'];
            else
                return $item['img'];
        }

        return $item['img'];
    }

    public static function generateStaticRatings($count, $post_id = null)
    {
        if (!$post_id)
        {
            global $post;
            if (!empty($post->ID))
                $post_id = $post->ID;
            else
                $post_id = $count;
        }

        $ratings = array();
        mt_srand($post_id);
        $rating = 10;
        for ($i = 0; $i < $count; $i++)
        {
            if ($i <= 3)
                $rand = mt_rand(0, 6) / 10;
            elseif ($count > 9 && $i > 4)
                $rand = mt_rand(0, 3) / 10;
            elseif ($i > 8)
                $rand = mt_rand(0, 4) / 10;
            else
                $rand = mt_rand(0, 10) / 10;

            $rating = round($rating - $rand, 2);
            $ratings[] = $rating;
        }

        return $ratings;
    }

    public static function printProgressRing($value)
    {
        if ($value <= 0)
            return;

        $p = round($value * 100 / 10);
        $r1 = round($p * 314 / 100);
        $r2 = 314 - $r1;

        echo '<svg width="75" height="75" viewBox="0 0 120 120"><circle cx="60" cy="60" r="50" fill="none" stroke="#E1E1E1" stroke-width="12"/><circle cx="60" cy="60" r="50" transform="rotate(-90 60 60)" fill="none" stroke-dashoffset="314" stroke-dasharray="314"  stroke="dodgerblue" stroke-width="12" ><animate attributeName="stroke-dasharray" dur="3s" values="0,314;' . esc_attr($r1) . ',' . esc_attr($r2) . '" fill="freeze" /></circle><text x="60" y="63" fill="black" text-anchor="middle" dy="7" font-size="28">' . esc_html($value) . '</text></svg>';
    }

    public static function getChance($position, $max = 1)
    {
        global $post;
        if (!empty($post->ID))
        {
            $post_id = $post->ID;
        }
        else
        {
            $post_id = time();
        }
        mt_srand($post_id + $position);

        return mt_rand(0, 1);
    }

    public static function getShopInfo(array $item)
    {
        if (!isset($item['domain']))
            return;

        $domain = $item['domain'];

        if (self::$shop_info === null)
        {
            $merchants = GeneralConfig::getInstance()->option('merchants');
            if (!$merchants)
                $merchants = array();
            foreach ($merchants as $merchant)
            {

                $d = \apply_filters('cegg_shop_info', \do_shortcode($merchant['shop_info']), $domain);
                self::$shop_info[$merchant['name']] = $d;
            }
        }

        if (isset(self::$shop_info[$domain]))
            return self::$shop_info[$domain];
        else
            return '';
    }

    public static function getShopCoupons(array $item)
    {
        if (!isset($item['domain']))
            return '';

        $domain = $item['domain'];

        if (self::$shop_coupons === null)
        {
            $merchants = GeneralConfig::getInstance()->option('merchants');
            if (!$merchants)
                $merchants = array();

            foreach ($merchants as $merchant)
            {
                if (!isset($merchant['shop_coupons']))
                    continue;

                $d = \apply_filters('cegg_shop_coupons', \do_shortcode($merchant['shop_coupons']), $domain);
                self::$shop_coupons[$merchant['name']] = $d;
            }
        }

        if (isset(self::$shop_coupons[$domain]))
            return self::$shop_coupons[$domain];
        else
            return '';
    }

    public static function printMerchantInfo($item)
    {
        $name = TemplateHelper::getMerhantName($item);

        if (self::getShopInfo($item))
        {
            $text = $name;
            self::printShopInfo($item, array(), $text);
        }
        else
            TemplateHelper::getMerhantName($item, true, true);

        self::printShopCoupons($item);
    }

    public static function printShopInfo(array $item, array $p = array(), $text = '')
    {
        if (!self::getShopInfo($item))
            return;

        $popup_type = GeneralConfig::getInstance()->option('popup_type');

        if ($popup_type == 'popover')
            self::printShopInfoPopover($item, $p, $text);
        else
            self::printShopInfoModal($item, $p, $text);
    }

    public static function printShopCoupons(array $item, $text = '')
    {
        if (!self::getShopCoupons($item))
            return;

        if (!$text)
            $text = '[' . TemplateHelper::__('coupons') . ']';

        self::printShopCouponsModal($item, $text);
    }

    public static function printShopInfoModal(array $item, array $p = array(), $text = '', $modal_id = null, $modal_label = null)
    {
        if (!$shop_info = self::getShopInfo($item))
            return;

        \wp_enqueue_script('bootstrap-modal');

        if (!$modal_id)
            $modal_id = TemplateHelper::generateGlobalId('cegg-modal-');
        if (!$modal_label)
            $modal_label = TemplateHelper::generateGlobalId('cegg-modal-label');

        if ($text)
        {
            echo '<span class="egg-ico-info-circle" data-toggle="modal" data-target="#' . esc_attr($modal_id) . '">';
            echo ' <small style="cursor: pointer;text-decoration: underline dotted;">' . esc_html($text) . '</small>';
        }
        echo '</span>';

        echo '<div class="modal fade" id="' . esc_attr($modal_id) . '" tabindex="-1" role="dialog" aria-labelledby="' . esc_attr($modal_label) . '">';
        echo '<div class="modal-dialog" role="document">';
        echo '<div class="modal-content cegg-modal-coupons">';
        echo '<div class="modal-header" style="position: sticky; top: 0; background-color: inherit; z-index: 1055;">';
        echo '<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="padding: 0px 5px 0px 5px"><span aria-hidden="true">&times;</span></button>';
        echo '<h4 class="modal-title" id=" ' . esc_attr($modal_label) . '">';
        echo '<span>' . esc_html(self::getMerhantName($item)) . '</span>';
        echo '</h4>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo $shop_info; // phpcs:ignore
        echo '</div></div></div></div>';
    }

    public static function printShopCouponsModal(array $item, $text, $modal_id = null, $modal_label = null)
    {
        if (!$shop_coupons = self::getShopCoupons($item))
            return;

        \wp_enqueue_script('bootstrap-modal');

        if (!$modal_id)
            $modal_id = TemplateHelper::generateGlobalId('cegg-modal-');
        if (!$modal_label)
            $modal_label = TemplateHelper::generateGlobalId('cegg-modal-label');

        echo '<span class="text-success cegg-coupons-link" data-toggle="modal" data-target="#' . esc_attr($modal_id) . '">';
        if ($text)
            echo ' <small style="cursor: pointer;text-decoration: underline dotted;">' . esc_html($text) . '</small>';
        echo '</span>';

        echo '<div class="modal fade" id="' . esc_attr($modal_id) . '" tabindex="-1" role="dialog" aria-labelledby="' . esc_attr($modal_label) . '">';
        echo '<div class="modal-dialog" role="document">';
        echo '<div class="modal-content cegg-modal-shop-info">';
        echo '<div class="modal-header" style="position: sticky; top: 0; background-color: inherit; z-index: 1055;">';
        echo '<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="padding: 0px 5px 0px 5px"><span aria-hidden="true">&times;</span></button>';
        echo '<h4 class="modal-title" id=" ' . esc_attr($modal_label) . '">';
        echo '<span>' . esc_html(self::getMerhantName($item)) . '</span>';
        echo '</h4>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo $shop_coupons; // phpcs:ignore
        echo '</div></div></div></div>';
    }

    public static function printShopInfoPopover(array $item, array $p = array(), $text = '')
    {
        if (!$shop_info = self::getShopInfo($item))
            return;

        $params = array(
            'data-toggle' => 'cegg-popover',
            'data-html' => 'true',
            'data-placement' => 'left',
            'data-title' => self::getMerhantName($item),
            'data-content' => $shop_info,
            'tabindex' => '0',
            'data-trigger' => 'focus',
        );

        $params = array_merge($params, $p);

        echo '<span class="egg-ico-info-circle" ' . self::buildTagParams($params) . '>'; // phpcs:ignore
        if ($text)
            echo ' <small style="cursor: pointer;text-decoration: underline dotted;">' . esc_html($text) . '</small>';
        echo '</span>';
    }

    public static function getMerchnatInfo(array $item)
    {
        if (!isset($item['domain']))
        {
            return array();
        }

        $domain = $item['domain'];

        if (self::$merchnat_info === null)
        {
            $merchants = GeneralConfig::getInstance()->option('merchants');
            if (!$merchants)
            {
                $merchants = array();
            }
            foreach ($merchants as $merchant)
            {
                self::$merchnat_info[$merchant['name']] = $merchant;
            }
        }

        if (isset(self::$merchnat_info[$domain]))
        {
            return self::$merchnat_info[$domain];
        }
        else
        {
            return array();
        }
    }

    public static function t($s)
    {
        return Translator::__($s);
    }

    public static function selectItemByBadge(array $items)
    {
        foreach ($items as $item)
        {
            if ($item['badge'])
                return $item;
        }

        return reset($items);
    }

    public static function selectItemByDescription(array $items)
    {
        $min_len = 999999;
        $selected = null;
        foreach ($items as $item)
        {
            if (!$item['description'])
                continue;

            if (mb_strlen($item['description'], 'UTF-8') < $min_len)
            {
                $min_len = mb_strlen($item['description'], 'UTF-8');
                $selected = $item;
            }
        }
        if (!$selected)
            return reset($items);

        return $selected;
    }

    public static function getGallery(array $data, $limit = 12, $offset = 0)
    {
        $images = array();
        foreach ($data as $items)
        {
            foreach ($items as $item)
            {

                if (!empty($item['extra']['images']))
                    $gallery = $item['extra']['images'];
                elseif (!empty($item['images']))
                    $gallery = $item['images'];
                elseif (!empty($item['extra']['imageSet']) && is_array($item['extra']['imageSet']))
                {
                    $gallery = array();
                    foreach ($item['extra']['imageSet'] as $g)
                    {
                        if (isset($g['LargeImage']))
                            $gallery[] = $g['LargeImage'];
                    }
                }
                else
                    continue;

                foreach ($gallery as $g)
                {
                    $images[] = array(
                        'url' => $item['url'],
                        'uri' => $g,
                        'img' => $g,
                        'title' => $item['title'],
                        'alt' => $item['title'],
                        'module_id' => $item['module_id'],
                    );
                }
            }
        }

        if (!$limit)
            $limit = 12;

        $images = array_slice($images, $offset, $limit);

        return $images;
    }

    public static function convertRatingScale($OldValue, $OldMin = 1, $OldMax = 5, $NewMin = 1, $NewMax = 10)
    {
        if (!$OldValue)
            return 0;

        $r =  ((($OldValue - $OldMin) * ($NewMax - $NewMin)) / ($OldMax - $OldMin)) + $NewMin;

        if ($r >= $NewMax)
            return $OldValue;

        return $r;
    }

    public static function convertRatingScale10($x, $beta = 0.5)
    {
        if ($x < 1 || $x > 5)
            return $x;

        $y = 1 + 9 * pow((($x - 1) / 4), $beta);

        return round($y, 1);
    }

    public static function isPriceAvailable(array $items)
    {
        foreach ($items as $item)
        {
            if ($item['price'])
                return true;
        }

        return false;
    }

    public static function colorMode($params = array())
    {
        $color_mode_general = GeneralConfig::getInstance()->option('color_mode');

        if (!empty($params['color_mode']))
            $color_mode_shortcode = $params['color_mode'];
        else
            $color_mode_shortcode = '';

        if ($color_mode_shortcode && $color_mode_shortcode != $color_mode_general)
            echo ' data-bs-theme="' . esc_attr($color_mode_shortcode) . '"';
        elseif ($color_mode_general !== 'light')
            echo ' data-bs-theme="' . esc_attr($color_mode_general) . '"';
    }

    public static function badge(array $item, $classes = array(), array $params = array())
    {
        if (empty($item['badge']))
            return;

        if (!is_array($classes))
            $classes = array($classes);

        if (!empty($params['border']))
            $style = '--border: ' . $params['border'] . 'px';
        else
            $style = '';

        $icon_html = '';
        $badge_text = $item['badge'];
        $badge_parts = explode(':', $badge_text, 2);
        $badge_text = end($badge_parts);
        if (count($badge_parts) == 2)
        {
            $badge_icon = $badge_parts[0];
            $icon_html = IconHelper::getIconByName($badge_icon);
        }

        $badge = '<div';
        if ($style)
            $badge .= ' style="' . esc_attr($style) . '"';
        $badge .= ' class="' . esc_attr(join(' ', $classes)) . '">';
        if ($icon_html)
            $badge .= $icon_html . ' ';
        $badge .= esc_html(TemplateHelper::truncate($badge_text, 60));
        $badge .= '</div>';

        echo wp_kses($badge, IconHelper::allowedTags());
    }

    public static function badge1(array $item, array $params = array(), $position = 'left')
    {
        if (!empty($item['badge_color']))
            $color = $item['badge_color'];
        else
            $color = 'primary';

        $classes = array(
            'cegg-badge-' . $position,
            'cegg-badge-' . $color,
            'text-bg-' . $color,
        );

        self::badge($item, $classes, $params);
    }

    public static function badge2(array $item, array $params = array(), $position = 'left')
    {
        if (!empty($item['badge_color']))
            $color = $item['badge_color'];
        else
            $color = 'primary';

        $classes = array(
            'cegg-badge-' . $position,
            'cegg-badge-' . $color,
            'text-bg-' . $color,
            'cegg-badge-sm',
        );

        self::badge($item, $classes, $params);
    }

    public static function badge3(array $item, array $params = array())
    {
        if (!empty($item['badge_color']))
            $color = $item['badge_color'];
        else
            $color = 'primary';

        $classes = array(
            'badge',
            'badge-' . $color,
            'text-bg-' . $color,
            'rounded-0',
        );

        self::badge($item, $classes, $params);
    }

    public static function rowCols(array $params, $default)
    {
        $classes = array();
        $breakpoints = array('xs', 'sm', 'md', 'lg', 'xl', 'xxl');

        foreach ($breakpoints as $breakpoint)
        {
            $param = 'cols_' . $breakpoint;
            if (empty($params[$param]) || $params[$param] < 1 || $params[$param] > 12)
                continue;

            $class = 'row-cols';
            if ($breakpoint != 'xs')
                $class .= '-' . $breakpoint;

            $class .= '-' . $params[$param];
            $classes[] = $class;
        }

        if ($classes)
            $class_str = join(' ', $classes);
        else
            $class_str = $default;

        echo esc_attr(' ' . $class_str . ' ');
    }

    public static function getColOrder(array $params, $position)
    {
        if (empty($params['cols_order']) || empty($params['cols_order'][$position - 1]))
            return $position;

        $order = $params['cols_order'][$position - 1];

        if ($order > 5)
            $order = 5;
        elseif ($order < 1)
            $order = 1;

        return $order;
    }

    public static function colsOrder(array $params, $position, $breakpoint = '', $force_default = false)
    {
        if (empty($params['cols_order']) && !$force_default)
            return;

        if (empty($params['cols_order']) && $force_default)
            $params['cols_order'] = $force_default;

        $order = self::getColOrder($params, $position);
        $class = ' order-';
        if ($breakpoint)
            $class .= $breakpoint . '-';
        $class .= $order;

        echo esc_attr(' ' . $class);
    }

    public static function tabsType(array $params, $default)
    {
        if (!empty($params['tabs_type']))
            echo esc_attr('nav-' . $params['tabs_type']);
        else
            echo esc_attr($default);
    }

    public static function oldPrice(array $item, $params = array())
    {
        if (empty($item['priceOld']))
            return;

        echo esc_html(TemplateHelper::formatPriceCurrency($item['priceOld'], $item['currencyCode']));
    }

    public static function price(array $item, $params = array())
    {
        if (empty($item['price']))
            return;

        echo esc_html(TemplateHelper::formatPriceCurrency($item['price'], $item['currencyCode']));
    }

    public static function currencyCode(array $item, $params = array())
    {
        echo esc_html($item['currencyCode']);
    }

    public static function shippingCost(array $item)
    {
        if (!isset($item['shipping_cost']) || $item['shipping_cost'] == '')
        {
            self::$delivery_at_checkout = true;
            echo '<span class="text-nowrap">' . esc_html(TemplateHelper::__('+ Delivery *')) . '</span>';
        }
        else
        {
            if (is_numeric($item['shipping_cost']) && (float) $item['shipping_cost'] == 0)
                echo '<span class="text-success">' . esc_html(TemplateHelper::__('Free delivery')) . '</span>';
            else
                echo wp_kses(sprintf(TemplateHelper::__('%s incl. delivery'),  '<b>' . TemplateHelper::formatPriceCurrency($item['total_price'], $item['currencyCode']) . '</b>'), array('b' => array()));
        }
    }

    public static function deliveryAtCheckout()
    {
        echo esc_html(TemplateHelper::__('* Delivery cost shown at checkout.'));
    }

    public static function priceClass(array $item, $params = array())
    {
        if ($item['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            echo ' text-body-tertiary';
    }

    public static function newUsedPrice(array $item, $separator = ', ')
    {
        $new_price = !empty($item['extra']['lowestNewPrice']) ? $item['extra']['lowestNewPrice'] : 0;
        $used_price = !empty($item['extra']['lowestUsedPrice']) ? $item['extra']['lowestUsedPrice'] : 0;

        if ($new_price && $item['extra']['totalNew'] > 1)
        {
            echo esc_html(sprintf(TemplateHelper::__('%d new from %s'), $item['extra']['totalNew'], TemplateHelper::formatPriceCurrency($new_price, $item['currencyCode'])));
            if (!empty($item['extra']['totalUsed']) && $separator)
                echo  wp_kses($separator, array('br' => array()));
        }

        if (!empty($item['extra']['totalUsed']))
            echo esc_html(sprintf(TemplateHelper::__('%d used from %s'), $item['extra']['totalUsed'], TemplateHelper::formatPriceCurrency($used_price, $item['currencyCode'])));
    }

    public static function merchant(array $item)
    {
        if ($merchant = self::getMerchantName($item))
            echo esc_html($merchant);
    }

    public static function title(array $item, $class_str = '', $default_tag = 'div', array $params = array(), $truncate = 160)
    {
        if (isset($params['_number']))
        {
            self::titleWithNumber($item, $class_str, $default_tag, $params, $truncate);
            return;
        }

        echo '<';
        TemplateHelper::titleTag($params, $default_tag);
        if ($class_str)
            echo ' class="' . esc_attr($class_str) . '"';
        echo '>';
        echo esc_html(TemplateHelper::truncate($item['title'], $truncate));
        echo '</';
        TemplateHelper::titleTag($params, $default_tag);
        echo '>';
    }

    public static function titleWithNumber(array $item, $class_str = '', $default_tag = 'div', array $params = array(), $truncate = 250)
    {
        if (isset($params['_number']))
        {
            if (!empty($params['start_number']))
                $number = (int) $params['_number'] + (int) $params['start_number'];
            else
                $number = (int) $params['_number']++;
        }
        else
            $number = 1;

        echo '<div class="d-flex align-items-center mb-3">';
        echo '<div class="me-3">';
        echo '<div class="cegg-numhead-circle d-flex justify-content-center align-items-center rounded-circle fw-bold bg-danger text-white" style="width: 40px; height: 40px; font-size: 24px;">';
        echo '<span>' . esc_html($number) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<';
        TemplateHelper::titleTag($params, $default_tag);
        if ($class_str)
            echo ' class="' . esc_attr($class_str) . '"';
        echo '>';
        echo esc_html(TemplateHelper::truncate($item['title'], $truncate));
        echo '</';
        TemplateHelper::titleTag($params, $default_tag);
        echo '>';

        echo '</div>';
    }

    public static function subtitle(array $item, $truncate = 160)
    {
        echo esc_html(TemplateHelper::truncate($item['subtitle'], $truncate));
    }

    public static function description(array $item, $truncate = null)
    {
        if ($truncate)
            $item['description'] = TextHelper::truncateHtml($item['description'], $truncate);

        echo wp_kses_post($item['description']);
    }

    public static function stockStatus(array $item)
    {
        echo '<span class="text-body-secondary">';
        echo '<span class="' . esc_attr(TemplateHelper::getStockStatusClass5($item)) . '">';

        if ($item['stock_status'] == ContentProduct::STOCK_STATUS_IN_STOCK)
            echo '<span class="me-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/></svg></span>';

        echo '</span>';
        echo esc_html(TemplateHelper::getStockStatusStr($item));
        echo '</span>';
    }

    public static function prime(array $item)
    {
        if (empty($item['extra']['IsPrimeEligible']))
            return;

        $prime = '<span class="cegg-prime-badge position-relative badge bg-info" style="padding-left: 0.7rem;padding-right: 0.55rem;">';
        $prime .= 'PRIME';

        if (!empty($item['extra']['primePrice']))
            $prime .= ': ' . TemplateHelper::formatPriceCurrency($item['extra']['primePrice'], $item['currencyCode']);

        $prime .= '<span class="position-absolute top-50 start-0 translate-middle  border border-light rounded-circle bg-warning p-1"><span class="visually-hidden">PRIME</span></span>';
        $prime .= '</span>';

        echo wp_kses_post($prime);
    }

    public static function imgRatio(array $params, $default)
    {
        if (!empty($params['img_ratio']))
            $ratio = 'ratio-' . $params['img_ratio'];
        else
            $ratio = $default;

        echo esc_attr(' ' . $ratio . ' ');
    }

    public static function linkAttr(array $item, array $params = array(), array $custom_tag_params = array())
    {
        $tag_params = array();

        if ($rel = TemplateHelper::getRelValue())
        {
            $tag_params['rel'] = $rel;
        }

        if ($onclick_event = TemplateHelper::getGtagClickEvent($item))
        {
            $tag_params['onclick'] = $onclick_event;
        }

        if (!self::isLinkedToBridge($item))
        {
            $tag_params['target'] = '_blank';
        }

        $tag_params['href'] = $item['url'];

        // Inject beacon attributes for direct (non-redirect) links
        $beacon_attrs = self::buildDirectClickBeaconAttrs($item);

        // Merge order: base → beacon → custom (custom wins on conflicts)
        $tag_params = array_merge($tag_params, $beacon_attrs, $custom_tag_params);

        // Ensure 'cegg-click' class is present if beacon is active (even when custom class provided)
        if (isset($beacon_attrs['data-cegg-click']))
        {
            $tag_params['class'] = trim(($tag_params['class'] ?? '') . ' cegg-click');
        }

        echo self::arrayToTagParameters($tag_params); // phpcs:ignore
    }

    public static function openATag(array $item, array $params = array(), array $custom_tag_params = array())
    {
        echo '<a ';
        self::linkAttr($item, $params, $custom_tag_params);
        echo '>';
    }

    public static function closeATag()
    {
        echo '</a>';
    }

    public static function link($anchor, array $item, array $params = array(), array $custom_tag_params = array())
    {
        echo '<a ';
        self::linkAttr($item, $params, $custom_tag_params);
        echo '>' . esc_html($anchor) . '</a>';
    }

    public static function button(array $item, array $params = array(), array $custom_params = array(), $type = 'link', $is_coupon_btn = false)
    {
        $classes = array('btn');

        if (!empty($params['btn_variant']))
            $variant = $params['btn_variant'];
        else
            $variant = GeneralConfig::getInstance()->option('btn_variant');

        $classes[] = 'btn-' . $variant;

        if (!isset($custom_params['class']))
            $custom_params['class'] = '';

        if ($custom_params['class'])
            $custom_params['class'] .= ' ';

        $custom_params['class'] .= join(' ', $classes);

        if ($is_coupon_btn)
            $btn_text = TemplateHelper::couponBtnText(false, $item, $params['btn_text']);
        else
            $btn_text = TemplateHelper::buyNowBtnText(false, $item, $params['btn_text']);

        if ($params['btn_text'] == '%Buy Now%')
            $btn_text = TemplateHelper::btnText('btn_text_buy_now', __('BUY NOW', 'content-egg-tpl'), 0, $item);

        if ($type == 'button')
        {
            echo '<button ';
            echo self::arrayToTagParameters($custom_params); // phpcs:ignore
            echo '>' . esc_html($btn_text) . '</button>';
        }
        else
            self::link($btn_text, $item, $params, $custom_params);
    }

    public static function ratingStars(array $item, $display_value = true)
    {
        if (!empty($item['ratingDecimal']))
            $rating = $item['ratingDecimal'];
        elseif (!empty($item['rating']))
            $rating = $item['rating'];
        else
            return;

        $rating = round($rating, 1);

        $star_rating = (float) $rating;
        if ($star_rating == 10)
            $star_rating = 5;
        if ($star_rating > 5 && $star_rating < 10)
            $star_rating = TemplateHelper::convertRatingScale($star_rating, 1, 10, 1, 5);
        if ($star_rating < 0 || $star_rating > 5)
            $star_rating = 0;
        $star_rating = round($star_rating, 1);

        if (!$star_rating)
            return;

        echo '<div class="cegg-rating-stars" style="--rating: ' . esc_attr($star_rating) . '">';
        if ($display_value)
            echo '<span class="cegg-rating-value ps-2 text-body-secondary">' . esc_html(number_format($rating, 1)) . '</span>';
        echo '</div>';
    }

    public static function getRatingValueScale10(array $item)
    {
        if (empty($item['ratingDecimal']) && isset($item['extra']['data']['ratingDecimal']))
            $item['ratingDecimal'] = TemplateHelper::convertRatingScale10($item['extra']['data']['ratingDecimal']);

        if ($item['ratingDecimal'] && ($item['group'] !== 'Roundup' || $item['ratingDecimal'] < 5))
            $item['ratingDecimal'] = TemplateHelper::convertRatingScale10($item['ratingDecimal']);

        if (!$item['ratingDecimal'])
            return 0;

        $rating = $item['ratingDecimal'];
        $rating = round($rating, 1);

        return $rating;
    }

    public static function promo(array $item, $display_icon = true)
    {
        if ($display_icon)
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-check me-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0" /><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1z" /></svg>' . ' ';
        echo esc_html($item['promo']);
    }

    public static function logo(array $item, array $params = array(), $class_str = '')
    {
        if (!empty($params['color_mode']))
            $color_mode = 'dark';
        else
            $color_mode = GeneralConfig::getInstance()->option('color_mode');

        if (!$logo_uri = TemplateHelper::getMerchantLogoUrl($item, false, $color_mode))
            return;

        echo '<img class="cegg-merhant-logo';
        if ($class_str)
            echo ' ' . esc_attr($class_str);

        echo '" src="' . esc_url($logo_uri) . '" alt="' . esc_attr(self::getMerchantName($item)) . '" />';
    }

    public static function icon(array $item, array $params = array(), $class_str = '')
    {
        if (!$icon_uri = TemplateHelper::getMerchantIconUrl($item, false))
            return;

        echo '<img class="cegg-merchant-icon';
        if ($class_str)
            echo ' ' . esc_attr($class_str);

        echo '" src="' . esc_url($icon_uri) . '" alt="' . esc_attr(self::getMerchantName($item)) . '" />';
    }

    public static function cashback(array $item, $display_icon = true)
    {
        if (!$cashback_str = TemplateHelper::getCashbackStr($item))
            return;

        if ($display_icon)
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-plus" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 7.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V12a.5.5 0 0 1-1 0v-1.5H6a.5.5 0 0 1 0-1h1.5V8a.5.5 0 0 1 .5-.5"/><path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/></svg>' . ' ';

        echo esc_html($cashback_str);
    }

    public static function number($item, array $params, $number, $variant = 'primary')
    {
        if (!empty($params['start_number']))
            $number += $params['start_number'];
        else
            $number++;

        $parts = explode('-', $variant);

        if (count($parts) == 2)
            $outline = true;
        else
            $outline = false;

        if (!empty($params['border_color']))
            $variant = $params['border_color'];
        else
            $variant = end($parts);

        if (!$params['border'])
            $border_class = 'border-1';
        elseif ($params['border'] <= 3)
            $border_class = ' border-' . $params['border'];
        else
            $border_class = 'border-2';

        if ($outline)
            echo '<div class="cegg-circle ' . esc_attr($border_class) . ' border-' . esc_attr($variant) . ' text-' . esc_attr($variant) . ' bg-body">';
        else
            echo '<div class="rounded-circle text-bg-' . esc_attr($variant) . ' fw-bolder d-flex justify-content-center align-items-center" style="width: 2rem; height: 2rem;">';

        echo esc_html($number);
        echo '</div>';
    }
    public static function border(array $params, $default = '')
    {
        if ($params['border'] === '')
            $class_str = $default;
        else
        {
            $class_str = 'border';
            $class_str .= ' border-' . $params['border'];
        }

        if ($params['border_color'])
            $class_str .= ' border-' . $params['border_color'];

        echo esc_attr(' ' . $class_str);
    }

    public static function borderColor(array $params, $default = '')
    {
        if ($params['border'] === 0)
            return;

        if ($params['border_color'] === '')
        {
            $class_str = $default;
        }
        else
        {
            $classes = array();
            $classes[] = 'border-' . $params['border_color'] . '';
            $class_str = join(' ', $classes);
        }

        echo esc_attr(' ' . $class_str);
    }

    public static function disclaimer()
    {
        echo wp_kses_post(TemplateHelper::getBlockDisclimerText());
    }

    public static function titleTag(array $params, $default = 'div')
    {
        if (!empty($params['title_tag']))
            echo esc_html($params['title_tag']);
        elseif ($default)
            echo esc_html($default);
        else
            echo 'div';
    }

    public static function priceUpdateAmazon(array $items, $price_disclaimer = true)
    {
        if (!$date = TemplateHelper::getLastUpdateFormattedAmazon($items))
            return;

        echo wp_kses_post(sprintf(Translator::translate('Amazon price updated:') . ' <span class="text-nowrap">' . $date));

        if ($price_disclaimer)
        {
            $disclaimer_text = TemplateHelper::getAmazonPriceDisclimerText();
            echo '<a href="#" class="ms-1 text-decoration-none text-body-secondary" title="' . esc_attr($disclaimer_text) . '" onclick="event.preventDefault(); alert(this.title);">';
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>';
            echo '</a>';
        }

        echo '</span>';
    }

    public static function arrayToTagParameters($array)
    {
        $attributes = '';
        foreach ($array as $key => $value)
        {
            $key = esc_attr($key);
            $value = esc_attr($value);
            $attributes .= $key . '="' . $value . '" ';
        }

        return trim($attributes);
    }

    public static function isAmazonPriceExists(array $items)
    {
        foreach ($items as $item)
        {
            if (strstr($item['module_id'], 'Amazon') && (float)$item['price'])
                return true;
        }

        return false;
    }

    public static function isVisibleDisclaimer(array $params, array $items = array())
    {
        $field = 'disclaimer';

        if (isset($params['visible']) && in_array($field, $params['visible']))
            return true;

        if (isset($params['hide']) && in_array($field, $params['hide']))
            return false;

        if ($items && self::isAllItemsBridged($items))
            return false;

        if (GeneralConfig::getInstance()->option('product_block_disclaimer') == 'enabled')
            return true;
        else
            return false;
    }

    public static function isVisiblePriceUpdate(array $params, array $items = array())
    {
        if (isset($params['hide']) && in_array('price', $params['hide']))
            return false;

        $field = 'price_update';

        if (in_array($params['template'], array('block_top_listing', 'block_top_listing_show_more')) && !in_array('price', $params['visible']))
            return false;

        if ($items && !self::isAmazonPriceExists($items))
            return false;

        if (isset($params['visible']) && in_array($field, $params['visible']))
            return true;

        if (isset($params['hide']) && in_array($field, $params['hide']))
            return false;

        if (GeneralConfig::getInstance()->option('amazon_price_update_display') == 'enabled')
            return true;
        else
            return false;
    }

    public static function isVisibleDisclaimerOrPriceUpdate(array $items, array $params)
    {
        return self::isVisibleDisclaimer($params, $items) || self::isVisiblePriceUpdate($params, $items);
    }

    public static function isVisible(array $item, $field, array $params, array $items = array(), $default = true)
    {
        if ($default == false && isset($params['visible']) && !in_array($field, $params['visible']))
            return false;

        if ($field == 'disclaimer')
            return self::isVisibleDisclaimer($params, $items);

        if ($field == 'price_update')
            return self::isVisiblePriceUpdate($params, $items);

        if ($field == 'coupons' && !self::getShopCoupons($item))
            return false;

        if ($field == 'shop_info' && !self::getShopInfo($item))
            return false;

        if ($field == 'delivery_at_checkout')
        {
            $ret = self::$delivery_at_checkout;
            self::$delivery_at_checkout = false;
            return $ret;
        }

        if ($field == 'new_used_price')
        {
            if (empty($item['extra']['totalNew']) || (int)$item['extra']['totalNew'] <= 1)
                return false;

            if (!self::isVisible($item, 'price', $params, $items, $default))
                return false;

            $new_price = !empty($item['extra']['lowestNewPrice']) ? $item['extra']['lowestNewPrice'] : 0;
            $used_price = !empty($item['extra']['lowestUsedPrice']) ? $item['extra']['lowestUsedPrice'] : 0;

            if (!$new_price && !$used_price)
                return false;
        }

        if (!$item)
            return false;

        if (isset($params['hide']) && in_array($field, $params['hide']))
            return false;

        if ($field == 'percentageSaved' && in_array('price', $params['hide']))
            return false;

        if ($field == 'prime' && empty($item['extra']['IsPrimeEligible']))
            return false;

        elseif ($field == 'percentageSaved' && empty($item['price']))
            return false;

        elseif ($field == 'priceOld' && empty($item['price']))
            return false;

        elseif ($field == 'merchant' && !TemplateHelper::getMerchantName($item))
            return false;

        elseif ($field == 'merchant' && TemplateHelper::getMerchantName($item))
            return true;

        elseif ($field == 'logo' && !TemplateHelper::getMerchantLogoUrl($item))
            return false;

        elseif ($field == 'logo' && TemplateHelper::getMerchantLogoUrl($item))
            return true;

        elseif ($field == 'shipping_cost' && isset($params['visible']) && in_array($field, $params['visible']))
            return true;

        if ($field == 'price' && !$item['price'])
        {
            foreach ($items as $it)
            {
                if (isset($it['price']) && $it['price'])
                    return true;
            }

            return false;
        }

        if (isset($item[$field]) && !$item[$field])
            return false;

        if (self::$product_fields === null)
        {
            $instance = new ContentProduct;
            self::$product_fields = array_keys(get_object_vars($instance));
        }

        if (in_array($field, self::$product_fields) && empty($item[$field]))
            return false;

        if (isset($params['visible']) && in_array($field, $params['visible']))
            return true;

        return $default;
    }

    public static function conditionClass($condition, $class1, $class2)
    {
        if ($condition)
            echo esc_attr(' ' . $class1);
        else
            echo esc_attr(' ' . $class2);
    }

    public static function couponsOffcanvas(array $item)
    {
        if (!$merchant_name = TemplateHelper::getMerchantName($item))
            return;

        if (!$shop_coupons = self::getShopCoupons($item))
            return;

        $id = 'cegg-coupons-' . TextHelper::clear($merchant_name);
        $label = 'cegg-coupons-label-' . TextHelper::clear($merchant_name);

        if (!isset(self::$coupon_offcanvas[$merchant_name]))
        {
            \wp_enqueue_script('cegg-bootstrap5');

            echo '<div class="offcanvas offcanvas-start" tabindex="-1" id="' . esc_attr($id) . '" aria-labelledby="' . esc_attr($label) . '">';
            echo '<div class="offcanvas-header">';
            echo '<h6 class="offcanvas-title" id="' . esc_attr($label) . '">' . esc_html($merchant_name) . '</h6>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="offcanvas-body">';
            echo '<div>';
            echo wp_kses_post($shop_coupons);
            echo '</div>';
            echo '</div>';
            echo '</div>';

            self::$coupon_offcanvas[$merchant_name] = true;
        }
    }

    public static function coupons(array $item)
    {
        if (!$merchant_name = TemplateHelper::getMerchantName($item))
            return;

        if (!isset(self::$coupon_offcanvas[$merchant_name]))
            self::couponsOffcanvas($item);

        $id = 'cegg-coupons-' . TextHelper::clear($merchant_name);

        echo '<a data-bs-toggle="offcanvas" href="#' . esc_attr($id) . '" aria-controls="' . esc_attr($id) . '" class="icon-link icon-link-hover link-secondary text-body-secondary link-underline-opacity-25 link-underline-opacity-100-hover">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tags" viewBox="0 0 16 16"><path d="M3 2v4.586l7 7L14.586 9l-7-7zM2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586z" /><path d="M5.5 5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m0 1a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M1 7.086a1 1 0 0 0 .293.707L8.75 15.25l-.043.043a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 0 7.586V3a1 1 0 0 1 1-1z" /></svg>';
        echo esc_html(TemplateHelper::__('Coupons'));
        echo '</a>';
    }

    public static function shopInfoOffcanvas(array $item)
    {
        if (!$merchant_name = TemplateHelper::getMerchantName($item))
            return;

        if (!$shop_info = self::getShopInfo($item))
            return;

        $id = 'cegg-shop_info-' . TextHelper::clear($merchant_name);
        $label = 'cegg-shop_info-label-' . TextHelper::clear($merchant_name);

        if (!isset(self::$shop_info_offcanvas[$merchant_name]))
        {
            \wp_enqueue_script('cegg-bootstrap5');

            echo '<div class="offcanvas offcanvas-start" tabindex="-1" id="' . esc_attr($id) . '" aria-labelledby="' . esc_attr($label) . '">';
            echo '<div class="offcanvas-header">';
            echo '<h6 class="offcanvas-title" id="' . esc_attr($label) . '">' . esc_html($merchant_name) . '</h6>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="offcanvas-body">';
            echo '<div>';
            echo wp_kses_post($shop_info);
            echo '</div>';
            echo '</div>';
            echo '</div>';

            self::$shop_info_offcanvas[$merchant_name] = true;
        }
    }

    public static function shopInfo(array $item)
    {
        if (!$merchant_name = TemplateHelper::getMerchantName($item))
            return;

        if (!isset(self::$shop_info_offcanvas[$merchant_name]))
            self::shopInfoOffcanvas($item);

        $id = 'cegg-shop_info-' . TextHelper::clear($merchant_name);

        echo '<a data-bs-toggle="offcanvas" href="#' . esc_attr($id) . '" aria-controls="' . esc_attr($id) . '" class="icon-link icon-link-hover link-secondary text-body-secondary link-underline-opacity-25 link-underline-opacity-100-hover">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-square d-none d-sm-block" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>';
        echo esc_html($merchant_name);
        echo '</a>';
    }

    public static function getLowestPriceItem(array $items)
    {
        $items = TemplateHelper::sortByPrice($items);
        $item = reset($items);
        return $item;
    }

    public static function getItemPriceHistory($unique_id, $module_id, $currency = '', $days = 180)
    {
        $where = PriceHistoryModel::model()->prepareWhere(
            (array('unique_id = %s AND module_id = %s', array($unique_id, $module_id))),
            false
        );
        $params = array(
            'select' => 'date(create_date) as date, price as price',
            'where' => $where . ' AND TIMESTAMPDIFF( DAY, create_date, "' . \current_time('mysql') . '") <= ' . $days,
            'order' => 'date ASC'
        );
        $results = PriceHistoryModel::model()->findAll($params);

        $prices = array();

        foreach ($results as $key => $r)
        {
            if ($key > 0 && $results[$key - 1]['date'] == $r['date'])
                continue;

            $price = array(
                'date' => $r['date'],
                'price' => (float)$r['price'],
            );
            $prices[] = $price;
        }

        global $post;
        if (empty($post))
            return $prices;

        $item = ContentManager::getProductbyUniqueId($unique_id, $module_id, $post->ID);
        if ($item['price'])
        {
            $prices[] = array(
                'date' => date('Y-m-d'),
                'price' => (float)$item['price']
            );
        }

        if ($currency && $item['currencyCode'] != $currency)
        {
            foreach ($prices as $i => $p)
            {
                $prices[$i]['price'] =  CurrencyHelper::getInstance()->convertCurrency($p['price'], $item['currencyCode'], $currency);
            }
        }

        return $prices;
    }

    public static function getItemsPriceHistory(array $items, $currency = '', $days = 180)
    {
        self::$price_history_lowest_item = null;
        self::$price_history_highest_item = null;
        self::$price_history_since = null;

        $priceHistory = array();
        foreach ($items as $item)
        {
            if (!$data = self::getItemPriceHistory($item['unique_id'], $item['module_id'], $currency, $days))
                continue;

            if (!$merchant = self::getMerchantName($item))
                continue;

            if (!isset($priceHistory[$merchant]))
                $priceHistory[$merchant] = array();

            $priceHistory[$merchant] = array_merge($priceHistory[$merchant], $data);
        }

        $lowestPrices = array();
        $lastKnownPrices = array();

        $currentDate = new \DateTime();
        $dateAgo = (clone $currentDate)->modify('-' . $days . ' days');

        for ($date = clone $dateAgo; $date <= $currentDate; $date->modify('+1 day'))
        {
            $dateString = $date->format('Y-m-d');

            foreach ($priceHistory as $merchant => $prices)
            {
                $priceOnDate = null;
                foreach ($prices as $priceData)
                {
                    if ($priceData['date'] === $dateString)
                    {
                        $priceOnDate = floatval($priceData['price']);
                        break;
                    }
                }

                if ($priceOnDate === null && isset($lastKnownPrices[$merchant]))
                    $priceOnDate = $lastKnownPrices[$merchant];

                if ($priceOnDate !== null)
                {
                    $lastKnownPrices[$merchant] = $priceOnDate;

                    if (!isset($lowestPrices[$dateString]) || $priceOnDate < $lowestPrices[$dateString]['price'])
                    {
                        $lowestPrices[$dateString] = array(
                            'price' => $priceOnDate,
                            'merchant' => $merchant
                        );
                    }
                }
            }
        }

        // remove items when the price has not changed compared to the previous day
        $filtered_price_history = array();
        $last_price = null;
        $lowest_item = null;
        $highest_item = null;
        $latest_date = array_key_last($lowestPrices);
        foreach ($lowestPrices as $date => $data)
        {
            if (self::$price_history_since === null)
                self::$price_history_since = strtotime($date);

            if ($lowest_item === null || $data['price'] <= $lowest_item['price'])
            {
                $lowest_item = $data;
                $lowest_item['date'] = $date;
            }

            if ($highest_item === null || $data['price'] > $highest_item['price'])
            {
                $highest_item = $data;
                $highest_item['date'] = $date;
            }

            if ($date === $latest_date || $last_price !== $data['price'])
            {
                $filtered_price_history[$date] = $data;
                $last_price = $data['price'];
            }
        }

        $lowest_item['currencyCode'] = $currency;
        $highest_item['currencyCode'] = $currency;
        self::$price_history_lowest_item = $lowest_item;
        self::$price_history_highest_item = $highest_item;

        return $filtered_price_history;
    }

    public static function getPriceHistoryLowestItem()
    {
        return self::$price_history_lowest_item;
    }

    public static function getPriceHistoryHighestItem()
    {
        return self::$price_history_highest_item;
    }

    public static function getPriceHistorySince()
    {
        return self::$price_history_since;
    }

    public static function getDeliveryAtCheckout()
    {
        return self::$delivery_at_checkout;
    }

    public static function chartjs(array $items, array $params = array(), $days = 180)
    {
        if (!$items)
            return;

        if (!empty($params['currency']))
            $currency = $params['currency'];
        else
            $currency = $items[0]['currencyCode'];

        if (!$lowestPrices = self::getItemsPriceHistory($items, $currency, $days))
            return;

        $dates = array_map(function ($date)
        {
            return date_i18n(get_option('date_format'), strtotime($date));
        }, array_keys($lowestPrices));

        $prices = array_column($lowestPrices, 'price');
        $merchants = array_column($lowestPrices, 'merchant');

        $canvas_id = TemplateHelper::generateGlobalId('cegg-price-history-chart-');

        \wp_enqueue_script('cegg-chartjs');

        $locale = get_locale();
        $locale = str_replace('_', '-', $locale);

        $localized_data = [
            'dates' => $dates,
            'prices' => $prices,
            'merchants' => $merchants,
            'currency' => $currency,
            'dateFormat' => get_option('date_format'),
            'locale' => $locale,
        ];
        $data_json = wp_json_encode($localized_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        ob_start();
?>
        <canvas id="<?php echo esc_attr($canvas_id); ?>" height="100" aria-label="price history chart" role="img" data-price-history="<?php echo esc_attr($data_json); ?>"></canvas>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('<?php echo esc_attr($canvas_id); ?>');
                const {
                    dates,
                    prices,
                    merchants,
                    currency,
                    locale
                } = JSON.parse(ctx.dataset.priceHistory);

                const rootStyles = getComputedStyle(document.documentElement);
                const borderColor = rootStyles.getPropertyValue('--cegg-primary').trim();
                const rgb = rootStyles.getPropertyValue('--cegg-primary-rgb').trim();
                const backgroundColor = `rgba(${rgb}, 0.2)`;
                const computedStyles = getComputedStyle(ctx);
                const bodyColorRgb = computedStyles.getPropertyValue('--cegg-body-color-rgb').trim();
                const color = `rgb(${bodyColorRgb})`;
                const gridColor = `rgba(${bodyColorRgb}, 0.1)`;

                const data = {
                    labels: dates,
                    datasets: [{
                        data: prices,
                        stepped: 'before',
                        borderColor: borderColor,
                        backgroundColor: backgroundColor,
                        fill: true,
                        borderWidth: 1,
                        radius: 1,
                        tension: 0.1
                    }]
                };

                const config = {
                    type: 'line',
                    data: data,
                    options: {
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            x: {
                                type: 'category',
                                ticks: {
                                    color: color,
                                    autoSkip: true,
                                    maxTicksLimit: 8
                                },
                                grid: {
                                    color: gridColor,
                                }
                            },
                            y: {
                                title: {
                                    display: false,
                                },
                                beginAtZero: false,
                                ticks: {
                                    color: color,
                                    autoSkip: true,
                                    maxTicksLimit: 6,
                                    callback: function(value, index, values) {
                                        return new Intl.NumberFormat(locale, {
                                            style: 'currency',
                                            currency: currency
                                        }).format(value);
                                    }
                                },
                                grid: {
                                    color: gridColor,
                                },
                            }
                        },
                        plugins: {
                            legend: {
                                display: false,
                                labels: {
                                    color: color,
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        const price = tooltipItem.raw;
                                        const merchant = merchants[tooltipItem.dataIndex];
                                        const formattedPrice = new Intl.NumberFormat(locale, {
                                            style: 'currency',
                                            currency: currency
                                        }).format(price);
                                        return `${merchant}: ${formattedPrice}`;
                                    }
                                }
                            },
                        }
                    }
                };

                const priceHistoryChart = new Chart(ctx, config);
            });
        </script>
<?php
        $code = ob_get_clean();
        echo self::minifyBasic($code); // phpcs:ignore
    }

    public static function minifyBasic($input)
    {
        $output = preg_replace('/\s+/', ' ', $input);
        return trim($output);
    }

    public static function ratingRing(array $item)
    {
        if (empty($item['ratingDecimal']))
            return;

        $rating = floatval($item['ratingDecimal']);
        $rating = max(0, min(10, $rating));

        $percentage = ($rating / 10) * 100;

        $size = 75;
        $strokeWidth = 8;
        $radius = ($size / 2) - ($strokeWidth / 2);
        $circumference = 2 * M_PI * $radius;

        $offset = $circumference - ($percentage / 100 * $circumference);

        echo '<div style="width: ' . esc_attr($size) . 'px; height: ' . esc_attr($size) . 'px; position: relative;">';
        echo '<svg width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" style="transform: rotate(-90deg);">';
        // Background circle
        echo '<circle cx="' . esc_attr($size / 2) . '" cy="' . esc_attr($size / 2) . '" r="' . esc_attr($radius) . '" stroke="#e6e6e6" stroke-width="' . esc_attr($strokeWidth) . '" fill="none" />';
        // Progress circle
        echo '<circle cx="' . esc_attr($size / 2) . '" cy="' . esc_attr($size / 2) . '" r="' . esc_attr($radius) . '" stroke="currentColor" class="text-primary" stroke-width="' . esc_attr($strokeWidth) . '" fill="none" stroke-dasharray="' . esc_attr($circumference) . '" stroke-dashoffset="' . esc_attr($offset) . '" />';
        echo '</svg>';
        // Rating text
        echo '<div style="font-size: 20px; position: absolute; top: 0; left: 0; width: ' . esc_attr($size) . 'px; height: ' . esc_attr($size) . 'px; display: flex; align-items: center; justify-content: center;">';
        echo '<span>' . esc_html($rating) . '</span>';
        echo '</div>';
        echo '</div>';
    }

    public static function ratingProgress(array $item)
    {
        if (! $rating = self::getRatingValueScale10($item))
        {
            return;
        }

        $rating      = max(0, min(10, $rating));
        $percentage  = ($rating / 10) * 100;
        $percentage_attr = esc_attr($percentage . '%');
        $aria_now    = esc_attr((string) round($percentage));
        $aria_label  = esc_attr('Product rating: ' . $rating . ' out of 10');

        $output  = '<div class="progress" role="progressbar" aria-label="' . $aria_label . '"';
        $output .= ' aria-valuenow="' . $aria_now . '" aria-valuemin="0" aria-valuemax="100" style="height:7px">';
        $output .= '<div class="progress-bar" style="width:' . $percentage_attr . ';"></div>';
        $output .= '</div>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe, fully escaped HTML output
        echo $output;
    }

    static public function addShopInfoOffcanvases(array $items, array $params, $default = true)
    {
        foreach ($items as $item)
        {
            if (TemplateHelper::isVisible($item, 'shop_info', $params, $items, $default))
                TemplateHelper::shopInfoOffcanvas($item);
        }
    }

    static public function addCouponOffcanvases(array $items, array $params, $default = true)
    {
        foreach ($items as $item)
        {
            if (TemplateHelper::isVisible($item, 'coupons', $params, $items, $default))
                TemplateHelper::shopInfoOffcanvas($item);
        }
    }

    /**
     * Reduce items to N per group based on a selection mode.
     */
    public static function pickByGroups(array $data, $group_pick = 'cheapest', $group_limit = 1)
    {
        // 1) Collect all items across modules, grouped by 'group' (fallback to _no_group)
        $grouped_items = [];
        foreach ($data as $module_items)
        {
            foreach ($module_items as $item)
            {
                if (empty($item['unique_id']))
                {
                    continue;
                }
                $g = (!empty($item['group'])) ? $item['group'] : '_no_group';
                $grouped_items[$g][] = $item;
            }
        }

        // 2) For each group, pick up to $group_limit items according to $group_pick
        $allowed_ids = [];
        foreach ($grouped_items as $group => $items)
        {
            switch ($group_pick)
            {
                case 'cheapest':
                    $sorted = TemplateHelper::sortByPrice($items, 'asc');
                    break;
                case 'priciest':
                    $sorted = TemplateHelper::sortByPrice($items, 'desc');
                    break;
                default: // 'random'
                    $sorted = $items;
                    shuffle($sorted);
                    break;
            }

            // Take first N after sorting/shuffling
            $selected = array_slice(array_values($sorted), 0, $group_limit);
            foreach ($selected as $sel)
            {
                if (!empty($sel['unique_id']))
                {
                    $allowed_ids[$sel['unique_id']] = true;
                }
            }
        }

        // 3) Rebuild $data keeping only allowed ids, preserving original module grouping and order
        $filtered = [];
        foreach ($data as $module_id => $items)
        {
            foreach ($items as $item)
            {
                if (!empty($item['unique_id']) && isset($allowed_ids[$item['unique_id']]))
                {
                    $filtered[$module_id][] = $item;
                }
            }

            if (empty($filtered[$module_id]))
            {
                unset($filtered[$module_id]);
            }
        }

        return $filtered;
    }

    public static function isLinkedToBridge(array $item)
    {
        if (!empty($item['bridge_url']))
        {
            return true;
        }

        return false;
    }

    public static function isAllItemsBridged(array $items)
    {
        foreach ($items as $item)
        {
            if (!self::isLinkedToBridge($item))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Build data-attributes for the client-side click beacon (non-redirect tracking).
     * Emits ONLY the link_id (no triplet fallback). Returns [] when unavailable/disabled.
     */
    private static function buildDirectClickBeaconAttrs(array $item): array
    {
        // Feature flags
        if (
            GeneralConfig::getInstance()->option('clicks_track_direct') !== 'enabled' ||
            self::isLinkedToBridge($item)
        )
        {
            return [];
        }

        // Redirect links
        if (!empty($item['aff_url']) && $item['aff_url'] !== $item['url'])
        {
            return [];
        }

        // Resolve context to look up link_id from the index
        global $post;
        $post_id   = !empty($item['post_id'])   ? (int) $item['post_id']   : (($post && !empty($post->ID)) ? (int) $post->ID : 0);
        $module_id = !empty($item['module_id']) ? (string) $item['module_id'] : '';
        $unique_id = !empty($item['unique_id']) ? (string) $item['unique_id'] : '';

        if ($post_id <= 0 || $module_id === '' || $unique_id === '')
        {
            return [];
        }
        // Per-request cache: post -> module -> unique_id -> link_id
        static $linkIdCache = [];
        if (!isset($linkIdCache[$post_id]))
        {
            $linkIdCache[$post_id] = [];
            $rows = LinkIndexModel::model()->listByPost($post_id);
            if (is_array($rows))
            {
                foreach ($rows as $r)
                {
                    $mid = (string) $r['module_id'];
                    $uid = (string) $r['unique_id'];
                    $linkIdCache[$post_id][$mid][$uid] = (int) $r['id'];
                }
            }
        }

        $link_id = (int) ($linkIdCache[$post_id][$module_id][$unique_id] ?? 0);
        if ($link_id <= 0)
        {
            return [];
        }

        self::markClicksJsNeeded(); // ensure JS is printed in footer once

        // Expose link_id + nonce to the frontend
        return [
            'data-cegg-click'   => '1',
            'data-cegg-link-id' => (string) $link_id,
        ];
    }

    /** Mark that the clicks beacon JS is needed on this page. */
    private static function markClicksJsNeeded(): void
    {
        if (self::$needsClicksJs) return;
        self::$needsClicksJs = true;
        add_action('wp_footer', [__CLASS__, 'printClicksInlineScript'], 99);
    }

    public static function printClicksInlineScript(): void
    {
        if (!self::$needsClicksJs || is_admin())
        {
            return;
        }

        $endpoint = esc_url_raw(rest_url('cegg/v1/click'));

        // js minifyed
        echo '<script>' . '"use strict";!function(){var e="' . esc_js($endpoint) . '",t=new WeakMap;function n(n){var a=function(e){for(;e&&e!==document;){if("A"===e.tagName&&e.dataset&&"1"===e.dataset.ceggClick&&e.dataset.ceggLinkId)return e;e=e.parentNode}return null}(n.target);if(a){var c=Date.now();c-(t.get(a)||0)<800||(t.set(a,c),function(t){var n=parseInt(t.dataset.ceggLinkId,10);if(n){var a=JSON.stringify({link_id:n});try{if(navigator.sendBeacon){var c=new Blob([a],{type:"application/json"});navigator.sendBeacon(e,c)}else fetch(e,{method:"POST",headers:{"Content-Type":"application/json"},body:a,keepalive:!0,credentials:"omit",cache:"no-store"}).catch((function(){}))}catch(e){}}}(a))}}document.addEventListener("click",n,{capture:!0,passive:!0}),document.addEventListener("auxclick",(function(e){1!==e.button&&2!==e.button||n(e)}),{capture:!0,passive:!0})}();' . '</script>';
    }
}

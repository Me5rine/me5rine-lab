<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TextHelper;



/**
 * LinkHandler class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class LinkHandler
{

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Deeplink & more...
     */
    public static function createAffUrl($url, $deeplink, $item = array(), $subid = '')
    {
        // custom filter
        $filtered = \apply_filters('cegg_create_affiliate_link', $url, $deeplink);
        if ($filtered != $url)
        {
            return $url;
        }

        // profitshare fix. return if url already created
        if (!empty($item['url']) && strstr($item['url'], '/l.profitshare.ro/'))
        {
            return $item['url'];
        }
        // lomadee fix. return if url already created
        if (!empty($item['url']) && strstr($item['url'], '/redir.lomadee.com/') && !strstr($item['url'], 'https://redir.lomadee.com/v2/deeplink?url='))
        {
            return $item['url'];
        }
        // coupang fix. return if url already created
        if (!empty($item['url']) && (strstr($item['url'], 'https://coupa.ng/') || strstr($item['url'], 'https://link.coupang.com')))
        {
            return $item['url'];
        }

        $deeplink = self::getMultiDeeplink($deeplink, $url);

        if (!$deeplink)
        {
            $result = $url;
        }
        elseif (substr(trim($deeplink), 0, 7) == '[regex]')
        {
            // regex preg_replace
            $result = self::getRegexReplace($url, $deeplink);
        }
        elseif (substr(trim($deeplink), 0, 13) == '[profitshare]')
        {
            // ProfitShare link creator
            $result = self::getProfitshareLink($url, $deeplink, $item);
        }
        elseif (substr(trim($deeplink), 0, 9) == '[lomadee]')
        {
            // Lomadee link creator
            $result = self::getLomadeeLink($url, $deeplink, $item);
        }
        elseif (substr(trim($deeplink), 0, 13) == '[trovaprezzi]')
        {
            // Trovaprezzi link creator
            $result = self::getTrovaprezziLink($url, $deeplink, $item);
        }
        elseif (substr(trim($deeplink), 0, 9) == '[coupang]')
        {
            // Coupang link creator
            $result = self::getCoupangLink($url, $deeplink, $item);
        }
        elseif (strstr($deeplink, '{{') && strstr($deeplink, '}}'))
        {
            // template deeplink
            $result = self::getUrlTemplate($url, $deeplink, $item);
        }
        elseif (!preg_match('/^https?:\/\//i', $deeplink))
        {
            // url with tail
            $result = self::getUrlWithTail($url, $deeplink);
        }
        elseif (!strstr($deeplink, '%PRODUCT.'))
        {
            $result = $deeplink . urlencode($url);
        }
        else
        {
            $result = $deeplink;
        }

        if ($subid)
        {
            $result = self::getUrlWithTail($result, $subid);
        }

        $result = self::replaceProductTags($result, $item);

        return $result;
    }

    public static function getUrlWithTail($url, $tail)
    {
        // replace params in URL
        parse_str($tail, $vars);
        if (count($vars) == 1 && strstr($tail, '='))
        {
            return \add_query_arg($vars, $url);
        }

        $tail = preg_replace('/^[?&]/', '', $tail);

        $query = parse_url($url, PHP_URL_QUERY);
        if ($query)
        {
            $url .= '&';
        }
        else
        {
            $url .= '?';
        }

        parse_str($tail, $tail_array);
        $url .= http_build_query($tail_array);

        return $url;
    }

    /**
     * Build URL by replacing template placeholders.
     *
     * @param string $url
     * @param string $template
     * @param array  $item
     * @return string
     */
    public static function getUrlTemplate($url, $template, $item = array())
    {
        // --- Base (existing) placeholders - keep exact behavior for BC ---
        $template = str_replace('{{url}}', $url, $template);
        $template = str_replace('{{url_encoded}}', urlencode($url), $template);
        $template = str_replace('{{url_base64}}', base64_encode($url), $template);

        global $post;

        // Resolve post_id & item id from $item or current post
        $post_id = 0;
        if (!empty($item) && isset($item['post_id']))
        {
            $post_id = (int) $item['post_id'];
        }
        elseif (!empty($post) && isset($post->ID))
        {
            $post_id = (int) $post->ID;
        }

        if ($post_id)
        {
            $template = str_replace('{{post_id}}', urlencode((string) $post_id), $template);
        }

        if (!empty($item) && !empty($item['unique_id']))
        {
            $template = str_replace('{{item_unique_id}}', urlencode((string) $item['unique_id']), $template);
        }

        if (!empty($post))
        {
            $author_id    = (int) $post->post_author;
            $user         = \get_user_by('ID', $author_id);
            $author_login = $user ? (string) $user->data->user_login : '';
            // Keep legacy placeholders URL-encoded (BC)
            $template = str_replace('{{author_id}}', urlencode((string) $author_id), $template);
            $template = str_replace('{{author_login}}', urlencode($author_login), $template);
        }

        // ==========================================================
        // New placeholders (+ automatic _encoded, _base64, _slug, _subid)
        // ==========================================================
        $addVariants = static function (array &$map, string $key, $value): void
        {
            $val = (string) ($value ?? '');
            $map['{{' . $key . '}}']          = $val;
            $map['{{' . $key . '_encoded}}']  = $val === '' ? '' : urlencode($val);
            $map['{{' . $key . '_base64}}']   = $val === '' ? '' : base64_encode($val);

            // Slug only for non-URLs (BC with prior behavior)
            if ($val !== '' && strpos($val, '://') === false)
            {
                $map['{{' . $key . '_slug}}'] = sanitize_title($val);
            }

            // NEW: SubID-safe variants (common-denominator length & encoding)
            $defaultMax = (int) apply_filters('cegg_subid_default_max_len', 100);
            $map['{{' . $key . '_subid}}']    = self::makeSubIdSafe($val, $defaultMax);
            $map['{{' . $key . '_subid64}}']  = self::makeSubIdSafe($val, 64);
            $map['{{' . $key . '_subid32}}']  = self::makeSubIdSafe($val, 32);
        };

        $repl = [];

        // --- Item / Product fields (from $item example) ---
        if (is_array($item))
        {
            if (isset($item['title']))         $addVariants($repl, 'item_title', (string) $item['title']);
            if (!empty($item['manufacturer'])) $addVariants($repl, 'item_brand', (string) $item['manufacturer']);
            if (!empty($item['sku']))          $addVariants($repl, 'item_sku',  (string) $item['sku']);
            if (!empty($item['ean']))          $addVariants($repl, 'item_ean',  (string) $item['ean']);
            if (!empty($item['upc']))          $addVariants($repl, 'item_upc',  (string) $item['upc']);
            if (!empty($item['isbn']))         $addVariants($repl, 'item_isbn', (string) $item['isbn']);

            if (isset($item['price']))         $repl['{{item_price}}']    = (string) $item['price'];
            if (isset($item['currencyCode']))  $repl['{{item_currency}}'] = (string) $item['currencyCode'];

            if (!empty($item['domain']))       $addVariants($repl, 'item_domain', (string) $item['domain']);
            if (!empty($item['module_id']))    $addVariants($repl, 'item_module_id', (string) $item['module_id']);
            if (!empty($item['group']))        $addVariants($repl, 'item_group', (string) $item['group']);
        }

        // --- Page / Post context ---
        $post_obj   = $post_id ? get_post($post_id) : (is_object($post) ? $post : null);
        $permalink  = ($post_obj && !is_wp_error($post_obj)) ? get_permalink($post_obj) : '';
        $post_title = ($post_obj && !is_wp_error($post_obj)) ? (string) $post_obj->post_title : '';
        $post_slug  = ($post_obj && !is_wp_error($post_obj)) ? (string) $post_obj->post_name  : '';
        $post_type  = ($post_obj && !is_wp_error($post_obj)) ? (string) $post_obj->post_type  : '';

        if ($post_title !== '') $addVariants($repl, 'post_title', $post_title);
        if ($post_slug  !== '') $addVariants($repl, 'post_slug',  $post_slug);
        if ($post_type  !== '') $addVariants($repl, 'post_type',  $post_type);
        if ($permalink  !== '')
        {
            $addVariants($repl, 'post_url',          $permalink);
            $addVariants($repl, 'post_url_relative', wp_make_link_relative($permalink));
        }

        if ($post_obj && isset($post_obj->post_author))
        {
            $display = get_the_author_meta('display_name', (int) $post_obj->post_author);
            if (!empty($display)) $addVariants($repl, 'post_author', (string) $display);
        }

        if ($post_obj)
        {
            $post_date = get_the_date('Y-m-d', $post_obj);
            if ($post_date) $repl['{{post_date}}'] = $post_date;
        }

        // --- Site / global ---
        $repl['{{site_name}}']   = (string) get_bloginfo('name');
        $home_host               = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        $repl['{{site_domain}}'] = $home_host ?: '';
        $repl['{{site_locale}}'] = (string) get_locale();

        // --- Time / utility ---
        $now_ts                 = (int) current_time('timestamp'); // local
        $repl['{{date}}']       = gmdate('Y-m-d', $now_ts + (int) get_option('gmt_offset') * HOUR_IN_SECONDS);
        $repl['{{timestamp}}']  = (string) $now_ts;
        if (function_exists('wp_generate_uuid4'))
        {
            $repl['{{uuid}}'] = wp_generate_uuid4();
        }

        $uid_for_hash          = is_array($item) && !empty($item['unique_id']) ? (string) $item['unique_id'] : '';
        $hash_source           = $post_id . ':' . $uid_for_hash;
        $repl['{{post_hash}}'] = substr(sha1($hash_source), 0, 8);

        // Auto-variants for common text placeholders (already added via $addVariants)
        $autoVariantKeys = [
            'site_name',
            'site_domain',
            'post_title',
            'post_slug',
            'post_type',
            'post_author',
            'item_title',
            'item_brand',
            'item_sku',
            'item_ean',
            'item_upc',
            'item_isbn',
            'item_domain',
            'item_module_id',
            'item_group',
            'post_url',
            'post_url_relative',
        ];
        foreach ($autoVariantKeys as $k)
        {
            if (isset($repl['{{' . $k . '}}']))
            {
                $addVariants($repl, $k, $repl['{{' . $k . '}}']);
            }
        }

        if (!empty($repl))
        {
            $template = strtr($template, $repl);
        }

        return $template;
    }

    /**
     * Produce a SubID-safe ASCII token (alnum + _ . -), collapse separators,
     * trim, and cap to $maxLen. If truncated, append a short hash for stability.
     * Defaults: 100 chars (common denominator across networks).
     *
     * @param string $value
     * @param int    $maxLen
     * @return string
     */
    private static function makeSubIdSafe(string $value, int $maxLen = 100): string
    {
        if ($maxLen <= 0) return '';

        // Strip tags/entities; attempt transliteration to ASCII
        $s = wp_strip_all_tags($value);
        $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        if (function_exists('iconv'))
        {
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($t !== false) $s = $t;
        }

        // Keep [A-Za-z0-9_.-], collapse others to '-'
        $s = preg_replace('/[^A-Za-z0-9_.-]+/', '-', (string) $s);
        $s = preg_replace('/-+/', '-', $s);
        $s = trim($s, '-._');

        if ($s === '') $s = 'na';

        // Cap length, append short hash if truncated
        if (strlen($s) > $maxLen)
        {
            $hash = substr(md5($value), 0, 6);
            $keep = max(0, $maxLen - (strlen($hash) + 1));
            $s = ($keep > 0 ? substr($s, 0, $keep) . '-' : '') . $hash;
        }

        return $s;
    }

    public static function getRegexReplace($url, $regex)
    {
        $regex = trim($regex);

        $parts = explode('][', $regex);
        if (count($parts) != 3)
        {
            return $url;
        }

        $pattern = $parts[1];
        $replacement = substr($parts[2], 0, -1);

        // null character allows a premature regex end and "/../e" injection
        if (strpos($pattern, chr(0)) !== false || !trim($pattern))
        {
            return $url;
        }

        if ($result = @preg_replace($pattern, $replacement, $url))
        {
            return $result;
        }
        else
        {
            return $url;
        }
    }

    public static function getProfitshareLink($url, $regex, $item = array())
    {
        $regex = trim($regex);
        $parts = explode('][', $regex);
        if (count($parts) != 3)
        {
            return $url;
        }

        $api_user = $parts[1];
        $api_key = rtrim($parts[2], ']');

        $api_url = 'http://api.profitshare.ro/affiliate-links/?';
        $query_string = '';

        $spider = curl_init();
        curl_setopt($spider, CURLOPT_HEADER, false);
        curl_setopt($spider, CURLOPT_URL, $api_url . $query_string);
        curl_setopt($spider, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($spider, CURLOPT_TIMEOUT, 30);
        curl_setopt($spider, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($spider, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($spider, CURLOPT_USERAGENT, 'Content Egg WP Plugin (https://www.keywordrush.com/contentegg)');

        $data = array();
        $name = 'CE:' . TextHelper::getHostName($url);
        if (!empty($item['title']))
        {
            $name .= ' ' . $item['title'];
        }
        $data[] = array(
            'name' => $name,
            'url' => $url
        );

        curl_setopt($spider, CURLOPT_POST, true);
        curl_setopt($spider, CURLOPT_POSTFIELDS, http_build_query($data));

        $profitshare_login = array('api_user' => $api_user, 'api_key' => $api_key,);
        $date = gmdate('D, d M Y H:i:s T', time());
        $signature_string = 'POSTaffiliate-links/?' . $query_string . '/' . $profitshare_login['api_user'] . $date;
        $auth = hash_hmac('sha1', $signature_string, $profitshare_login['api_key']);

        $extra_headers = array(
            "Date: {$date}",
            "X-PS-Client: {$profitshare_login['api_user']}",
            "X-PS-Accept: json",
            "X-PS-Auth: {$auth}"
        );

        curl_setopt($spider, CURLOPT_HTTPHEADER, $extra_headers);

        $output = curl_exec($spider);
        if (!$output)
        {
            return $url;
        }

        $result = json_decode($output, true);

        if (!$result)
        {
            return $url;
        }
        if (isset($result['result'][0]['ps_url']))
        {
            return $result['result'][0]['ps_url'];
        }
        else
        {
            return $url;
        }
    }

    public static function getLomadeeLink($url, $regex, $item = array())
    {
        $regex = trim($regex);
        $parts = explode('][', $regex);
        if (count($parts) != 2)
        {
            return $url;
        }

        $sourceId = rtrim($parts[1], ']');
        $api_url = 'https://api.lomadee.com/v2/15071999399311f734bd1/deeplink/_create?sourceId=' . urlencode($sourceId) . '&url=' . urlencode($url);

        $response = \wp_remote_get($api_url);
        if (\is_wp_error($response))
        {
            return $url;
        }
        $response_code = (int) \wp_remote_retrieve_response_code($response);
        if ($response_code != 200)
        {
            return $url;
        }
        $output = \wp_remote_retrieve_body($response);
        $result = json_decode($output, true);
        if (!$result)
        {
            return $url;
        }
        if (isset($result['deeplinks'][0]['deeplink']))
        {
            return $result['deeplinks'][0]['deeplink'];
        }
        else
        {
            return $url;
        }
    }

    public static function getTrovaprezziLink($url, $regex, $item = array())
    {
        /**
         * Note: tracking links include a token in order to ensure that offers are updated as much as possible.
         * This token expires in 12 hours!  Therefore   you need to set your script to update your feed at least
         * once each 11 hours  , in order to guarantee the correct click tracking!
         */
        if (strstr($item['url'], 'splash?impression') && time() - $item['last_update'] < 111 * 3600)
        {
            return $item['url'];
        }

        $regex = trim($regex);
        $parts = explode('][', $regex);
        if (count($parts) != 2)
        {
            return $url;
        }

        /*
          $path = parse_url($url, PHP_URL_PATH);
          $path = trim($path, "/");
          $path = preg_replace('/\.aspx$/', '', $path);
          $path = explode('/', $path);
          $path = end($path);
          $path = explode('-', $path);
          $path = end($path);
          $keyword = $path;
         *
         */

        $keyword = $item['title'];
        $keyword = strtolower($keyword);
        $keyword = str_replace(' ', '_', $keyword);

        $partnerId = rtrim($parts[1], ']');
        $api_url = 'https://quickshop.shoppydoo.it/' . urlencode($partnerId) . '/' . urlencode($keyword) . '.aspx?format=json&sort=price';

        $response = \wp_remote_get($api_url);
        if (\is_wp_error($response))
        {
            return $url;
        }
        $response_code = (int) \wp_remote_retrieve_response_code($response);
        if ($response_code != 200)
        {
            return $url;
        }
        $output = \wp_remote_retrieve_body($response);
        $result = json_decode($output, true);
        if (!$result)
        {
            return $url;
        }
        if (isset($result['offers'][0]['url']))
        {
            return $result['offers'][0]['url'];
        }
        else
        {
            return $url;
        }
    }

    public static function getCoupangLink($url, $regex, $item = array())
    {
        $regex = trim($regex);
        $parts = explode('][', $regex);
        if (count($parts) != 3)
        {
            return $url;
        }

        $ACCESS_KEY = $parts[1];
        $SECRET_KEY = rtrim($parts[2], ']');

        //date_default_timezone_set("GMT+0");

        $datetime = date("ymd") . 'T' . date("His") . 'Z';
        $method = "POST";
        $path = "/v2/providers/affiliate_open_api/apis/openapi/v1/deeplink";
        $message = $datetime . $method . str_replace("?", "", $path);
        $algorithm = "HmacSHA256";

        $signature = hash_hmac('sha256', $message, $SECRET_KEY);

        $authorization = "CEA algorithm=HmacSHA256, access-key=" . $ACCESS_KEY . ", signed-date=" . $datetime . ", signature=" . $signature;

        $rurl = 'https://api-gateway.coupang.com' . $path;

        $strjson = '{"coupangUrls": ["' . $url . '"]}';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $rurl);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type:  application/json;charset=UTF-8",
            "Authorization:" . $authorization
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $strjson);
        $output = curl_exec($curl);

        if (!$output)
        {
            return $url;
        }

        $result = json_decode($output, true);
        if (!$result)
        {
            return $url;
        }
        if (isset($result['data'][0]['shortenUrl']))
        {
            return $result['data'][0]['shortenUrl'];
        }
        else
        {
            return $url;
        }
    }

    public static function getMultiDeeplink($deeplink, $url)
    {
        if (!strstr($deeplink, ';') || strstr($deeplink, 'ad.doubleclick'))
        {
            return $deeplink;
        }

        $url_host = TextHelper::urlHost($url);
        $deeplink_array = str_getcsv($deeplink, ';');
        $default = '';
        foreach ($deeplink_array as $da)
        {
            $parts = explode(':', $da, 2);

            // default deeplink
            if (count($parts) == 1)
            {
                $default = trim($da);
            }
            elseif (count($parts) == 2)
            {
                if (!$default)
                {
                    $default = trim($parts[1]);
                }

                $host = $parts[0];
                $host = preg_replace('/^https?:\/\//', '', $host);
                $host = preg_replace('/^www\./', '', $host);

                if ($host == $url_host)
                {
                    return trim($parts[1]);
                }
            }
        }

        return $default;
    }

    static public function replaceProductTags($template, array $item)
    {
        if (!$item)
            return $template;

        if (!stristr($template, '%PRODUCT.'))
            return $template;

        if (!preg_match_all('/(%PRODUCT\.[a-zA-Z0-9_\.\,\(\)]+%)/', $template, $matches))
            return $template;

        $replace = array();
        foreach ($matches[1] as $pattern)
        {
            $replace[$pattern] = '';
            $pattern_parts = explode('.', $pattern);
            $var_name = $pattern_parts[1];
            $var_name = rtrim($var_name, '%');
            $var_name = \sanitize_text_field($var_name);

            if (strtoupper($var_name) == 'EXTRA' && isset($pattern_parts[2]) && is_scalar($item[$var_name]))
            {
                $extra_var = rtrim($pattern_parts[2], '%');
                $extra_var = \sanitize_text_field($extra_var);

                if (isset($item['extra'][$extra_var]))
                    $replace[$pattern] = urlencode($item['extra'][$extra_var]);
            }
            elseif (isset($item[$var_name]) && is_scalar($item[$var_name]))
                $replace[$pattern] = urlencode($item[$var_name]);
        }

        return str_ireplace(array_keys($replace), array_values($replace), $template);
    }
}

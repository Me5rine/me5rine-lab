<?php

namespace ContentEgg\application;

use ContentEgg\application\admin\GeneralConfig;

defined('\ABSPATH') || exit;

/**
 * ImageProxy class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ImageProxy
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    public function init()
    {
        if (GeneralConfig::getInstance()->option('image_proxy') !== 'enabled')
        {
            return;
        }

        add_action('wp_ajax_nopriv_ce_proxy_image', [$this, 'proxyImage']);
        add_action('wp_ajax_ce_proxy_image', [$this, 'proxyImage']);
    }

    public function proxyImage()
    {
        // 1. Validate nonce
        if (!isset($_GET['_nonce']) || !wp_verify_nonce(sanitize_key($_GET['_nonce']), 'ce_proxy_image'))
        {
            wp_die('Access Denied.', 'Error', ['response' => 400]);
        }

        // 2. Validate the 'url' parameter
        if (!isset($_GET['url']))
        {
            wp_die('Access Denied.', 'Error', ['response' => 400]);
        }

        $image_url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
        if (!$image_url)
        {
            wp_die('Access Denied.', 'Error', ['response' => 400]);
        }

        // Block if domain is not from Amazon
        if (!self::isValidDomain($image_url))
        {
            wp_die('Access Denied.', 'Error', ['response' => 400]);
        }

        $parsed_url = parse_url($image_url);
        if (filter_var(gethostbyname($parsed_url['host']), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)
        {
            wp_die('Access Denied.', 'Error', ['response' => 400]);
        }

        // 3. Do a HEAD request to get content type
        $head = curl_init();
        curl_setopt($head, CURLOPT_URL, $image_url);
        curl_setopt($head, CURLOPT_NOBODY, true);
        curl_setopt($head, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($head, CURLOPT_TIMEOUT, 5);
        curl_setopt($head, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($head, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CE ImageProxy/1.0)');
        curl_exec($head);

        $http_code    = curl_getinfo($head, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($head, CURLINFO_CONTENT_TYPE);
        $content_length = curl_getinfo($head, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($head);

        if ($http_code !== 200)
        {
            wp_die(sprintf('Failed to fetch image (HTTP %d).', esc_html($http_code)), esc_html__('Error', 'content-egg'), ['response' => 500]);
        }

        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!$content_type || !in_array($content_type, $allowed_mime))
        {
            wp_die('Failed to fetch valid image type.', 'Error', ['response' => 500]);
        }

        $max_size = 1 * 1024 * 1024; // 1 MB
        if ($content_length > 0 && $content_length > $max_size)
        {
            wp_die('Image file is too large.', 'Error', ['response' => 500]);
        }

        // 4. Send headers BEFORE streaming the data
        header('Content-Type: ' . $content_type);
        header('Cache-Control: public, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        header('Connection: close');

        // 5. Perform the GET request to stream the actual image
        $ch = curl_init($image_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // We'll stream directly
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CE ImageProxy/1.0)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            'Connection: close'
        ]);

        // Stream directly to output
        $fp = fopen('php://output', 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);

        // Execute the cURL request
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        exit;
    }

    public static function isValidDomain($image_url)
    {
        // Allowed Amazon domains
        $allowed_domains = [
            'images-amazon.com',
            'm.media-amazon.com',
            'ssl-images-amazon.com',
        ];

        $parsed_url = parse_url($image_url);
        if (!isset($parsed_url['host']))
        {
            return false;
        }

        $host = strtolower($parsed_url['host']);

        if (in_array($host, $allowed_domains))
        {
            return true;
        }

        return false;
    }

    public static function isLocalImageUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host))
        {
            return true;
        }

        $local_host = parse_url(home_url(), PHP_URL_HOST);

        return (strcasecmp($host, $local_host) === 0);
    }

    public static function generateProxyImageUrl($image_url)
    {
        if (!filter_var($image_url, FILTER_VALIDATE_URL))
        {
            return $image_url;
        }

        $proxy_url = add_query_arg(
            [
                'action' => 'ce_proxy_image',
                '_nonce'  => wp_create_nonce('ce_proxy_image'),
                'url'    => urlencode($image_url),
            ],
            admin_url('admin-ajax.php')
        );

        return $proxy_url;
    }

    public static function maybeGenerateProxyImageUrl($image_url)
    {
        if (self::isLocalImageUrl($image_url))
        {
            return $image_url;
        }

        if (!ImageProxy::isValidDomain($image_url))
        {
            return $image_url;
        }

        return self::generateProxyImageUrl($image_url);
    }
}

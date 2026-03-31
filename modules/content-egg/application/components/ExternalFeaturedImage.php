<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\FeaturedImage;
use ContentEgg\application\helpers\WooHelper;
use ContentEgg\application\ImageProxy;
use ContentEgg\application\WooIntegrator;



/**
 * ExternalFeaturedImage class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ExternalFeaturedImage
{

    const EXTERNAL_URL_META = '_cegg_thumbnail_external';
    const FAKE_INT_START = '99998';

    public static function initAction()
    {
        self::initFeaturedImage();
        self::initGallery();
    }

    private static function initFeaturedImage()
    {
        if (GeneralConfig::getInstance()->option('external_featured_images') == 'disabled')
        {
            return;
        }

        \add_filter('get_post_metadata', array(__CLASS__, 'getFakeThumbnailId'), 10, 4);

        if (\is_admin())
        {
            \add_filter('admin_post_thumbnail_html', [__CLASS__, 'adminThumbnail']);
        }

        \add_filter('wp_get_attachment_image_src', array(__CLASS__, 'replaceImageSrc'), 10, 4);
        \add_filter('woocommerce_product_get_image_id', array(__CLASS__, 'getFakeImageId'), 99, 2);
        \add_filter('post_thumbnail_html', array(__CLASS__, 'replaceThumbnail'), 10, 5);
        \add_action('wpseo_add_opengraph_images', array(__CLASS__, 'addOpengraphImage'));
        \add_action('woocommerce_structured_data_product', array(__CLASS__, 'addStructuredDataProduct'), 10, 2);
        \add_action('content_egg_save_data', array(__CLASS__, 'setImage'), 13, 4);

        // external featured images in admin area
        \add_action('rest_api_init', function ()
        {
            \add_filter('rest_pre_dispatch', [__CLASS__, 'interceptMediaRequest'], 10, 3);
        });
    }

    private static function initGallery()
    {
        if (!WooHelper::isWooActive())
        {
            return;
        }

        if (GeneralConfig::getInstance()->option('woocommerce_sync_gallery') !== 'external')
        {
            return;
        }

        \add_filter('wp_get_attachment_image_src', array(__CLASS__, 'replaceImageSrc'), 10, 4);
        \add_filter('woocommerce_product_get_gallery_image_ids', array(__CLASS__, 'getFakeGalleryIds'), 99, 2);
        \add_action('content_egg_save_data', array(__CLASS__, 'setExternalGallery'), 13, 4);
    }

    private static function generateFakeId($post_id, $image_key = 0)
    {
        if ($image_key > 9)
            $image_key = 9;

        $max_len = strlen(strval(PHP_INT_MAX)) - 1;
        $post_id_len = strlen(strval($post_id));

        $fake_id = self::FAKE_INT_START;
        $pad = max(0, $max_len - $post_id_len - strlen($fake_id) - 1);
        $fake_id .= str_repeat('0', $pad);
        $fake_id .= $post_id;
        $fake_id .= $image_key;

        return $fake_id;
    }

    private static function getRealId($post_id)
    {
        if (strlen(strval($post_id)) != strlen(strval(PHP_INT_MAX)) - 1)
            return false;

        if (substr((string) $post_id, 0, strlen(self::FAKE_INT_START)) != self::FAKE_INT_START)
            return false;

        $real = substr_replace((string) $post_id, '', 0, strlen(self::FAKE_INT_START));
        $real = substr($real, 0, -1);
        return (int) $real;
    }

    public static function setImage($data, $module_id, $post_id, $is_last_iteration)
    {
        if (\get_post_type($post_id) == 'product')
        {
            return;
        }

        if (!$is_last_iteration)
        {
            return;
        }

        self::setExternalFeaturedImage($post_id);
    }

    public static function setExternalFeaturedImage($post_id, $item = null)
    {
        if (GeneralConfig::getInstance()->option('external_featured_images') == 'enabled_internal_priority' && self::hasInternalImage($post_id))
        {
            return false;
        }

        if (!$item)
        {
            $data = FeaturedImage::getData($post_id);
            if (!$data)
            {
                return;
            }
            $item = $data[0];
        }
        if (empty($item['img']))
        {
            return;
        }

        $img_url = $item['img'];

        return self::updateExternalMeta($img_url, $post_id);
    }

    public static function updateExternalMeta($url, $post_id, $image_key = 0)
    {
        $meta = \get_post_meta($post_id, self::EXTERNAL_URL_META, true);

        if ($meta && isset($meta[$image_key]) && $meta[$image_key]['url'] == $url)
            return true;

        if (!$meta)
            $meta = array();

        // deprecated format
        if (isset($meta['url']))
            $meta = array();

        $meta[$image_key]['url'] = $url;

        $width = $height = 0;
        if (ini_get('allow_url_fopen'))
            list($width, $height) = @getimagesize($url);
        $meta[$image_key]['width'] = $width;
        $meta[$image_key]['height'] = $height;

        return \update_post_meta($post_id, self::EXTERNAL_URL_META, $meta);
    }

    public static function adminThumbnail($html)
    {
        global $post;
        if (empty($post) || !$external_img = self::getExternalImageMeta($post->ID))
        {
            return $html;
        }

        if (empty($external_img['url']))
        {
            return $html;
        }

        $html .= '<div><img class="size-post-thumbnail" src="' . \esc_url($external_img['url']) . '">';
        $html .= '<p class="howto">' . __('External featured image', 'content-egg') . '</p></div>';

        return $html;
    }

    public static function getFakeImageId($value, $product)
    {
        if (GeneralConfig::getInstance()->option('external_featured_images') == 'enabled_internal_priority' && self::hasInternalImage($product->get_id()))
        {
            return $value;
        }

        $product_id = $product->get_id();

        if (self::getExternalImageMeta($product_id))
        {
            return self::generateFakeId($product_id);
        }
        else
        {
            return $value;
        }
    }

    public static function getExternalImageMeta($post_id, $image_key = 0)
    {
        if (!$meta = \get_post_meta($post_id, self::EXTERNAL_URL_META, true))
        {
            return false;
        }

        // deprecated format
        if ($image_key === 0 && isset($meta['url']))
        {
            return $meta;
        }

        if (isset($meta[$image_key]))
        {
            return $meta[$image_key];
        }
        else
        {
            return false;
        }
    }

    public static function getFakeThumbnailId($value, $object_id, $meta_key, $single)
    {
        if ($meta_key != '_thumbnail_id')
        {
            return $value;
        }

        if (GeneralConfig::getInstance()->option('external_featured_images') == 'enabled_internal_priority' && self::hasInternalImage($object_id))
        {
            return $value;
        }

        if (self::getExternalImageMeta($object_id, 0))
        {
            return self::generateFakeId($object_id, 0);
        }
        else
        {
            return $value;
        }
    }

    public static function replaceImageSrc($image, $attachment_id, $size, $icon)
    {
        if (!$post_id = self::getRealId($attachment_id))
            return $image;

        $image_key = self::getRealImageKey($attachment_id);

        if (!$external_img = self::getExternalImageMeta($post_id, $image_key))
            return $image;

        if (empty($external_img['url']))
            return $image;

        $external_url = $external_img['url'];
        if (GeneralConfig::getInstance()->option('image_proxy') == 'enabled')
        {
            $proxied = ImageProxy::maybeGenerateProxyImageUrl($external_url);
        }
        else
        {
            $proxied = $external_url;
        }

        if ($image_size = self::getImageSize($size))
            return array($proxied, $image_size['width'], $image_size['height'], $image_size['crop']);
        else
        {
            if (!empty($external_img['width']))
                $width = $external_img['width'];
            else
                $width = 800;

            if (!empty($external_img['height']))
                $height = $external_img['height'];
            else
                $height = 600;

            return array($proxied, $width, $height, false);
        }
    }

    public static function getImageSize($size)
    {
        if (is_array($size))
        {
            return array(
                'width' => isset($size[0]) ? $size[0] : null,
                'height' => isset($size[1]) ? $size[1] : null,
                'crop' => isset($size[2]) ? $size[2] : null,
            );
        }

        global $_wp_additional_image_sizes;
        if (isset($_wp_additional_image_sizes[$size]))
        {
            return $_wp_additional_image_sizes[$size];
        }

        $default = array('thumbnail', 'medium', 'medium_large', 'large');
        if (in_array($size, $default))
        {
            return array(
                'width' => \get_option("{$size}_size_w"),
                'height' => \get_option("{$size}_size_h"),
                'crop' => \get_option("{$size}_crop"),
            );
        }

        return array();
    }

    public static function replaceThumbnail($html, $post_id, $post_thumbnail_id, $size, $attr = array())
    {
        if (!$external_img = self::getExternalImageMeta($post_id))
            return $html;

        if (GeneralConfig::getInstance()->option('external_featured_images') == 'enabled_internal_priority' && self::hasInternalImage($post_id))
            return $html;

        if (empty($external_img['url']))
            return $html;

        $url = $external_img['url'];

        if (GeneralConfig::getInstance()->option('image_proxy') == 'enabled')
        {
            $url = ImageProxy::maybeGenerateProxyImageUrl($url);
        }

        $alt = \get_post_field('post_title', $post_id);
        $class = 'cegg-external-img wp-post-image';
        $attr = array('alt' => $alt, 'class' => $class);
        //$attr = \apply_filters('wp_get_attachment_image_attributes', $attr, $size);
        $attr = array_map('esc_attr', $attr);
        $html = sprintf('<img src="%s"', esc_url($url));
        foreach ($attr as $name => $value)
        {
            $html .= " $name=" . '"' . $value . '"';
        }
        $html .= ' />';

        return $html;
    }

    public static function hasInternalImage($object_id)
    {
        $meta_type = 'post';
        $meta_key = '_thumbnail_id';

        $meta_cache = \wp_cache_get($object_id, $meta_type . '_meta');
        if (!$meta_cache)
        {
            $meta_cache = \update_meta_cache($meta_type, array($object_id));
            $meta_cache = $meta_cache[$object_id];
        }

        if (isset($meta_cache[$meta_key]))
        {
            $meta_value = $meta_cache[$meta_key][0];
        }
        else
        {
            $meta_value = false;
        }

        if ($meta_value)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function getExternalUrl($post_id)
    {
        if (!$external_img = self::getExternalImageMeta($post_id, 0))
            return false;
        return $external_img['url'];
    }

    public static function addOpengraphImage($object)
    {
        if (!$post_id = \get_the_ID())
        {
            return;
        }

        if (!$external_url = self::getExternalUrl($post_id))
        {
            return;
        }

        $object->add_image($external_url);
    }

    public static function addStructuredDataProduct($markup, $product)
    {
        if (!empty($markup['image']))
        {
            return $markup;
        }

        if (!$external_url = self::getExternalUrl($product->get_id()))
        {
            return $markup;
        }

        $markup['image'] = $external_url;

        return $markup;
    }

    public static function getFakeGalleryIds($value, $objProduct)
    {
        $objectId = $objProduct->get_id();

        if (get_post_type($objectId) !== 'product')
        {
            return $value;
        }

        if (!\apply_filters('cegg_skip_internal_gallery_priority_check', false))
        {
            // if the product already has internal gallery images, do not use external images
            if (self::hasInternalGallery($objectId))
                return $value;
        }

        $product = WooIntegrator::getSyncItem($objectId);
        if (!$product)
        {
            return $value;
        }

        $images = $product['images'] ?? [];

        if (!is_array($images) || empty($images))
        {
            return $value;
        }

        $fakeIds = [];
        foreach (array_slice($images, 0, 9) as $index => $image)
        {
            $fakeIds[] = self::generateFakeId($objectId, $index + 1);
        }

        return $fakeIds;
    }

    private static function getRealImageKey($post_id)
    {
        $post_id = (string) $post_id;
        return (int) ($post_id[strlen($post_id) - 1]);
    }

    public static function setExternalGallery($data, $module_id, $post_id, $is_last_iteration)
    {
        if (\get_post_type($post_id) !== 'product')
        {
            return;
        }

        if (!$is_last_iteration)
        {
            return;
        }

        $product = WooIntegrator::getSyncItem($post_id);

        if (!$product)
        {
            return;
        }

        $images = $product['images'] ?? [];

        if (!$images)
        {
            return;
        }

        $objProduct = \wc_get_product($post_id);

        if (!$objProduct)
        {
            return;
        }

        if (!\apply_filters('cegg_skip_internal_gallery_priority_check', false))
        {
            // if the product already has internal (and not fake) gallery images, do not set external images
            if (ExternalFeaturedImage::hasRealGalleryImages($objProduct))
            {
                return;
            }
        }

        $images = array_values($images);
        $images = array_slice($images, 0, 9); // limit to 9 images
        $fake_ids = [];
        foreach ($images as $key => $img_url)
        {
            $image_key = $key + 1;

            self::updateExternalMeta($img_url, $post_id, $image_key);

            // generate fake ID for this slot
            $fake_id = self::generateFakeId($post_id, $image_key);
            $fake_ids[] = $fake_id;
        }

        // ---- Write fake IDs into WooCommerce’s internal gallery meta
        update_post_meta($post_id, '_product_image_gallery', implode(',', $fake_ids));
    }

    public static function hasInternalGallery($object_id)
    {
        return self::hasMeta($object_id, '_product_image_gallery');
    }

    public static function hasMeta($object_id, $meta_key, $meta_type = 'post')
    {
        if (!$meta_cache = \wp_cache_get($object_id, $meta_type . '_meta'))
        {
            $meta_cache = \update_meta_cache($meta_type, array($object_id));
            $meta_cache = $meta_cache[$object_id];
        }

        if (isset($meta_cache[$meta_key]))
            $meta_value = $meta_cache[$meta_key][0];
        else
            $meta_value = false;

        if ($meta_value)
            return true;
        else
            return false;
    }

    /**
     * Return true if this product already has one or more
     * real (non‐fake) gallery image attachments.
     *
     * @param \WC_Product $objProduct
     * @return bool
     */
    public static function hasRealGalleryImages($objProduct)
    {
        $ids = $objProduct->get_gallery_image_ids();
        if (empty($ids))
        {
            return false;
        }

        foreach ($ids as $attachment_id)
        {
            // getRealId returns an int for fake IDs, false for genuine WP attachment IDs
            if (self::getRealId($attachment_id) === false)
            {
                // Optionally double‐check that this ID exists and is an image:
                $mime = get_post_mime_type($attachment_id);
                if ($mime && strpos($mime, 'image/') === 0)
                {
                    return true;
                }
            }
        }

        return false;
    }

    public static function interceptMediaRequest($pre, \WP_REST_Server $server, \WP_REST_Request $request)
    {
        // 1) Only GET /wp/v2/media/{digits}
        if ($request->get_method() !== 'GET')
        {
            return $pre;
        }
        $route = $request->get_route();
        if (! preg_match('#^/wp/v2/media/(\d+)$#', $route, $m))
        {
            return $pre;
        }

        $fake_id   = $m[1];
        $image_key = self::getRealImageKey($fake_id);

        // 2) Figure out *which post* is being edited by looking at the Referer header
        $referer = $request->get_header('referer');
        $post_id = false;
        if ($referer && preg_match('/[?&]post=([0-9]+)/', $referer, $refm))
        {
            $post_id = (int) $refm[1];
        }

        if (! $post_id)
        {
            return $pre;
        }

        // 3) Now fetch the external‐image meta *for that post* and slot
        $meta = self::getExternalImageMeta($post_id, $image_key);
        if (empty($meta['url']))
        {
            return $pre;  // let WP return its 404
        }

        // 4) Build and return a minimal REST response
        $width  = ! empty($meta['width'])  ? $meta['width']  : null;
        $height = ! empty($meta['height']) ? $meta['height'] : null;
        $data = [
            'id'           => (int) $fake_id,
            'media_type'   => 'image',
            //'mime_type'    => 'image/jpeg',
            //'title'        => ['rendered' => get_the_title($post_id)],
            'alt_text'     => '',
            'source_url'   => $meta['url'],
            'media_details' => [
                'width'  => $width,
                'height' => $height,
                'file'   => $meta['url'],
                'sizes'  => [],
            ],
        ];

        return new \WP_REST_Response($data, 200);
    }
}

<?php

namespace ContentEgg\application;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\helpers\WooHelper;



defined('\ABSPATH') || exit;

/**
 * GalleryScheduler class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class GalleryScheduler
{
    public const PENDING_GALLERY_META = '_pending_gallery_images';

    public static function initAction(): void
    {
        add_action('woocommerce_before_single_product', [__CLASS__, 'maybeShowGalleryAsyncNotice']);
        add_action('process_pending_gallery_images', [__CLASS__, 'downloadGalleryImages']);
    }

    /**
     * Store external image URLs and schedule their download.
     */
    public static function maybeSchedulePendingImages(int $post_id, array $item): void
    {
        if (GeneralConfig::getInstance()->option('woocommerce_sync_gallery') !== 'local')
        {
            return;
        }

        if (empty($item['images']) || !is_array($item['images']))
        {
            return;
        }

        if (get_post_meta($post_id, '_product_image_gallery', true))
        {
            return;
        }

        $pending_images = array_unique(array_filter($item['images']));
        if (empty($pending_images))
        {
            return;
        }

        $max_images = (int) apply_filters('cegg_max_local_gallery_images', 10);
        if (count($pending_images) > $max_images)
        {
            $pending_images = array_slice($pending_images, 0, $max_images);
        }

        update_post_meta($post_id, self::PENDING_GALLERY_META, $pending_images);

        if (function_exists('as_has_scheduled_action') && function_exists('as_schedule_single_action'))
        {
            if (! \as_has_scheduled_action(
                'process_pending_gallery_images',
                [$post_id],
                'gallery_processing'
            ))
            {
                \as_schedule_single_action(
                    time() + 10,
                    'process_pending_gallery_images',
                    [$post_id],
                    'gallery_processing'
                );
            }
        }
    }

    /**
     * Action callback (single-item): download & attach gallery images.
     *
     * @param int $product_id The WooCommerce product ID.
     */
    public static function downloadGalleryImages(int $product_id): void
    {
        if (!WooHelper::isWooActive())
        {
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product)
        {
            return;
        }

        $images = get_post_meta($product_id, self::PENDING_GALLERY_META, true);
        if (empty($images) || !is_array($images))
        {
            return;
        }

        // Remove the meta
        delete_post_meta($product_id, self::PENDING_GALLERY_META);

        // Re-check in case the option is already disabled
        if (GeneralConfig::getInstance()->option('woocommerce_sync_gallery') !== 'local')
        {
            return;
        }

        $media_ids = WooHelper::uploadMedias($images, $product->get_id(), $product->get_title());
        if (!empty($media_ids))
        {
            $product->set_gallery_image_ids($media_ids);
            $product->save();
        }
    }

    /**
     * Show a user-friendly notice while images are still downloading.
     */
    public static function maybeShowGalleryAsyncNotice(): void
    {
        if (is_admin())
        {
            return;
        }

        $product_id = get_the_ID();
        if (!$product_id || !current_user_can('edit_product', $product_id))
        {
            return;
        }

        if (GeneralConfig::getInstance()->option('woocommerce_sync_gallery') !== 'local')
        {
            return;
        }

        $images = get_post_meta($product_id, self::PENDING_GALLERY_META, true);
        if (empty($images) || !is_array($images))
        {
            return;
        }

        $count   = count($images);
        $message = sprintf(
            _n(
                'Gallery images are loading asynchronously in the background. %s image is currently queued for this product.',
                'Gallery images are loading asynchronously in the background. %s images are currently queued for this product.',
                $count,
                'content-egg'
            ),
            number_format_i18n($count)
        );

        wc_print_notice(esc_html($message), 'notice');
    }
}

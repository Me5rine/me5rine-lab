<?php

namespace ContentEgg\application;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ContentManager;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\models\LinkClicksDailyModel;
use ContentEgg\application\models\LinkIndexModel;
use ContentEgg\application\vendor\CrawlerDetect\CrawlerDetect;

/**
 * LocalRedirector class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

/**
 * LocalRedirector
 *
 * - Builds local redirect URLs for CE items when module setting "set_local_redirect" is enabled
 * - Front-end handler for /{prefix}/{slug} -> redirects to item's affiliate URL
 */
class LocalRedirector
{
    public const DEFAULT_PREFIX = 'go';
    public const QUERY_VAR      = 'cegg_slug';

    /**
     * Wire rewrite + handler.
     */
    public static function initAction(): void
    {
        add_action('init', [__CLASS__, 'addRewrite']);
        add_filter('query_vars', [__CLASS__, 'addQueryVar']);
        add_action('template_redirect', [__CLASS__, 'handleRedirect']);
    }

    /**
     * Add rewrite rule: /{prefix}/{slug}
     * Allows forcing a specific $prefix (e.g., right after settings change).
     */
    public static function addRewrite(?string $prefix = null): void
    {
        [$pattern, $query] = self::ruleParts($prefix);
        add_rewrite_rule($pattern, $query, 'top');
    }

    private static function ruleParts(?string $prefix = null): array
    {
        $p = $prefix ?? self::getPrefix();

        if (!$p)
        {
            $p = self::getPrefix();
        }

        $pattern = '^' . preg_quote($p, '#') . '/([^/]+)/?$';
        $query   = 'index.php?' . self::QUERY_VAR . '=$matches[1]';

        return [$pattern, $query];
    }

    public static function addQueryVar(array $vars): array
    {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * Build a local redirect URL for the given item, or return the direct affiliate URL.
     */
    public static function localUrlForItem(array $item): string
    {
        global $post;

        // Resolve post_id
        if (!empty($item['post_id']))
        {
            $post_id = (int) $item['post_id'];
        }
        elseif ($post && !empty($post->ID))
        {
            $post_id = (int) $post->ID;
        }
        else
        {
            $post_id = null;
        }

        // Resolve module_id
        $module_id = !empty($item['module_id']) ? (string) $item['module_id'] : null;

        // If we can't determine context, fall back to direct URL
        $direct = (string) ($item['url'] ?? '');

        if ($post_id === null || $module_id === null)
        {
            return $direct;
        }

        // Per-request cache of slugs for this post to avoid N queries
        static $slugCache = [];
        if (!isset($slugCache[$post_id]))
        {
            $slugCache[$post_id] = [];
            $rows = LinkIndexModel::model()->listByPost($post_id);
            if (is_array($rows))
            {
                foreach ($rows as $r)
                {
                    $mid = (string) $r['module_id'];
                    $uid = (string) $r['unique_id'];
                    $slugCache[$post_id][$mid][$uid] = (string) $r['slug'];
                }
            }
        }

        $uid  = (string) $item['unique_id'];
        $slug = $slugCache[$post_id][$module_id][$uid] ?? null;

        // If no slug yet (edge/race), fall back to direct URL; do not write on frontend
        if ($slug === null)
        {
            return $direct;
        }

        if (self::usingPrettyPermalinks())
        {
            return home_url('/' . self::getPrefix() . '/' . rawurlencode($slug) . '/');
        }

        // Fallback for Plain permalinks
        return home_url(add_query_arg(self::QUERY_VAR, $slug, '/'));
    }

    /**
     * Front controller for redirects.
     * Looks up slug -> (post_id, module_id, unique_id) and redirects to the affiliate URL.
     * Also records a daily click (if click stats are enabled).
     */
    public static function handleRedirect(): void
    {
        $slug = get_query_var(self::QUERY_VAR);
        if (!$slug)
        {
            return;
        }

        $model = LinkIndexModel::model();
        $row   = $model->findBySlug((string) $slug);
        if (!$row)
        {
            status_header(404);
            nocache_headers();
            exit;
        }

        $target = self::resolveAffiliateUrl((int) $row['post_id'], (string) $row['module_id'], (string) $row['unique_id']);
        if (!$target)
        {
            status_header(404);
            nocache_headers();
            exit;
        }

        // Record a click (optional; controlled by setting)
        if (
            GeneralConfig::getInstance()->option('clicks_track_redirect') === 'enabled'
            && self::isTrackableRequest()
        )
        {
            try
            {
                LinkClicksDailyModel::model()->incrementToday((int) $row['id']);
            }
            catch (\Throwable $e)
            {
                // Silently ignore tracking failures to not block the redirect
            }
        }

        // Passthrough all query parameters if enabled
        if (GeneralConfig::getInstance()->option('redirect_pass_parameters') == 'enabled' && !empty($_SERVER['QUERY_STRING']))
        {
            parse_str($_SERVER['QUERY_STRING'], $params);
            if ($params)
            {
                $target = \add_query_arg($params, $target);
            }
        }

        header('X-Robots-Tag: noindex, nofollow', true);
        nocache_headers();

        $opt  = GeneralConfig::getInstance()->option('redirect_status_code');
        $code = (int) $opt;
        $code = (int) \apply_filters('cegg_local_redirect_code', $code);

        wp_redirect($target, $code);
        exit;
    }

    private static function resolveAffiliateUrl(int $post_id, string $module_id, string $unique_id): ?string
    {
        $item = ContentManager::getProductbyUniqueId($unique_id, $module_id, $post_id);
        return isset($item['aff_url']) && is_string($item['aff_url']) ? $item['aff_url'] : null;
    }

    /**
     * Fetch and sanitize the redirect prefix from GeneralConfig, fallback to DEFAULT_PREFIX.
     */
    private static function getPrefix(): string
    {
        $prefix = (string) GeneralConfig::getInstance()->option('redirect_prefix');
        $prefix = TextHelper::clear($prefix);
        if ($prefix === '')
        {
            $prefix = self::DEFAULT_PREFIX;
        }

        return strtolower($prefix);
    }

    /**
     * Call on activation or version bump to write the rule to .htaccess/nginx config.
     */
    public static function flushRules($prefix = null): void
    {
        self::addRewrite($prefix);
        flush_rewrite_rules(false);
    }

    private static function usingPrettyPermalinks(): bool
    {
        return (bool) get_option('permalink_structure');
    }

    private static function isTrackableRequest(): bool
    {
        // Only count real navigations
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET')
        {
            return false;
        }

        // Skip obvious prefetch/prerender requests
        if (!empty($_SERVER['HTTP_PURPOSE']) && stripos($_SERVER['HTTP_PURPOSE'], 'prefetch') !== false) return false;
        if (!empty($_SERVER['HTTP_X_PURPOSE']) && stripos($_SERVER['HTTP_X_PURPOSE'], 'prefetch') !== false) return false;
        if (!empty($_SERVER['HTTP_SEC_PURPOSE']) && stripos($_SERVER['HTTP_SEC_PURPOSE'], 'prefetch') !== false) return false;
        if (!empty($_SERVER['HTTP_X_MOZ']) && stripos($_SERVER['HTTP_X_MOZ'], 'prefetch') !== false) return false;

        static $isRealVisitor = null;               // per-request cache
        if ($isRealVisitor !== null) return $isRealVisitor;

        try
        {
            $cd = new CrawlerDetect();
            $isRealVisitor = !$cd->isCrawler();
        }
        catch (\Throwable $e)
        {
            $isRealVisitor = true; // fail-open for tracking so redirects never break
        }

        return $isRealVisitor;
    }
}

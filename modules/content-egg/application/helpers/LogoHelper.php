<?php

namespace ContentEgg\application\helpers;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ModuleManager;



defined('\ABSPATH') || exit;

class LogoHelper
{
    public const MERCHANT_LOGO_DIR = 'ce-logos';

    /** Default Clearbit template */
    private const CLEARBIT_TEMPLATE = 'https://logo.clearbit.com/%s?size=128';

    private static array $logos = [];

    public static function getMerchantLogoUrl(array $item, $blankOnError = false, $colorMode = 'light')
    {

        // Respect module‑level config that disables large logos
        if (!empty($item['module_id']) && self::isLargeLogoDisabled($item['module_id']))
        {
            return $blankOnError ? self::getBlankImg() : false;
        }

        // Resolve preferred remote source (CDN / explicit link)
        $remoteUrl = self::resolveRemoteLogoUrl($item);
        if ($remoteUrl === '')
        {
            return $blankOnError ? self::getBlankImg() : false;
        }

        $hotlinking = (string) GeneralConfig::getInstance()->option('logo_hotlinking') === 'enabled';

        // Always run through the local‑cache resolver to honour precedence list.
        return self::getMerchantImageUrl(
            $item,
            '', // no filename prefix
            $remoteUrl,
            $blankOnError,
            $colorMode,
            $hotlinking
        );
    }

    /** Determine if the module disables large logos. */
    private static function isLargeLogoDisabled($moduleId)
    {
        $parser = ModuleManager::getInstance()->parserFactory($moduleId);
        if (!$parser->getConfigInstance()->option_exists('show_large_logos'))
        {
            return false;
        }
        return !(bool) filter_var($parser->config('show_large_logos'), FILTER_VALIDATE_BOOLEAN);
    }

    /** Build remote URL based on chosen provider or explicit logo. */
    private static function resolveRemoteLogoUrl(array $item)
    {
        // Explicit logo URL takes absolute priority as remote source.
        /*
        if (!empty($item['logo']) && filter_var($item['logo'], FILTER_VALIDATE_URL))
        {
            return $item['logo'];
        }
        */

        // Need domain for provider‑based logo.
        if (empty($item['domain']))
        {
            return '';
        }

        // Extract host portion.
        $host = parse_url((string) $item['domain'], PHP_URL_HOST);
        if ($host === false || $host === null)
        {
            $host = preg_replace('#^https?://#', '', (string) $item['domain']);
        }
        if ($host === '' || $host === null)
        {
            return '';
        }

        // Choose provider template.
        $provider = (string) GeneralConfig::getInstance()->option('logo_source');
        switch ($provider)
        {
            case 'brandfetch':
                $cid = (string) GeneralConfig::getInstance()->option('brandfetch_client_id');
                return sprintf('https://cdn.brandfetch.io/%s/fallback/lettermark/h/200/w/200?c=%s', urlencode($host), urlencode($cid));
            case 'logodev':
                $key = (string) GeneralConfig::getInstance()->option('logodev_key');
                return sprintf('https://img.logo.dev/%s?token=%s&size=200&format=webp&fallback=monogram', urlencode($host), urlencode($key));
            case 'clearbit':
            default:
                // fall through to default
                break;
        }

        // Fallback to Clearbit template.
        return sprintf(self::CLEARBIT_TEMPLATE, urlencode($host));
    }

    /** Return blank placeholder path. */
    private static function getBlankImg()
    {
        return \ContentEgg\PLUGIN_RES . '/img/blank.gif';
    }

    /**
     * Core resolver that enforces precedence list.
     */
    private static function getMerchantImageUrl(
        array $item,
        $prefix,
        $remoteUrl,
        $blankOnError = true,
        $colorMode = 'light',
        $hotlinking = false
    )
    {

        /* ------------------------------------------------------------------
         * 1. Offer‑module specific logo
         * ------------------------------------------------------------------ */
        if (!$prefix)
        {
            if (!empty($item['logo']) && $item['module_id'] === 'Offer')
            {
                return $item['logo'];
            }
        }

        /* ------------------------------------------------------------------
         * 2. Admin custom logo override
         * ------------------------------------------------------------------ */
        if (!$prefix)
        {
            if (isset($item['domain']) && ($custom = self::getCustomLogo($item['domain'])))
            {
                return $custom;
            }
        }

        /* ------------------------------------------------------------------
         * 3. Already‑cached local logo (plugin assets or uploads cache)
         * ------------------------------------------------------------------ */

        $fileName = self::buildFileName($item, $prefix);

        if ($fileName !== '')
        {
            // Dark‑mode shipped asset
            $darkPath = \ContentEgg\PLUGIN_PATH . "res/logos/dark-{$fileName}";
            if ($colorMode === 'dark' && file_exists($darkPath))
            {
                return \ContentEgg\PLUGIN_RES . "/logos/dark-{$fileName}";
            }

            // Light‑mode shipped asset
            $lightPath = \ContentEgg\PLUGIN_PATH . "res/logos/{$fileName}";
            if (file_exists($lightPath))
            {
                return \ContentEgg\PLUGIN_RES . "/logos/{$fileName}";
            }

            // Uploads cache
            $logoDir = self::getMerchantLogoDir();
            if ($logoDir)
            {
                $uploads  = wp_upload_dir();
                $logoPath = $logoDir . DIRECTORY_SEPARATOR . $fileName;
                if (file_exists($logoPath))
                {
                    return $uploads['baseurl'] . '/' . self::MERCHANT_LOGO_DIR . '/' . $fileName;
                }
            }
        }

        /* ------------------------------------------------------------------
         * 4. CDN hot‑link (if enabled)
         * ------------------------------------------------------------------ */
        if ($hotlinking)
        {
            return $remoteUrl;
        }

        /* ------------------------------------------------------------------
         * 5. Download & cache (hot‑linking disabled)
         * ------------------------------------------------------------------ */
        if ($fileName === '')
        {
            return $blankOnError ? self::getBlankImg() : false;
        }

        $logoDir = $logoDir ?? self::getMerchantLogoDir();
        if (!$logoDir)
        {
            return $blankOnError ? self::getBlankImg() : false;
        }

        $uploads = wp_upload_dir();
        $logoUrl = $uploads['baseurl'] . '/' . self::MERCHANT_LOGO_DIR . '/' . $fileName;

        // Attempt download
        if ($remoteUrl && ImageHelper::downloadImg($remoteUrl, $logoDir, $fileName, '', true))
        {
            return $logoUrl;
        }

        // Mark failure to avoid repeated attempts
        @copy(\ContentEgg\PLUGIN_PATH . 'res/img/blank.gif', $logoDir . DIRECTORY_SEPARATOR . $fileName);

        return $blankOnError ? self::getBlankImg() : false;
    }

    /** Generate deterministic filename for caching. */
    private static function buildFileName(array $item, $prefix = '')
    {
        $ext = 'png';
        if (!empty($item['domain']))
        {
            $domain = $item['domain'];
            if (strpos($domain, 'amazon.') !== false && !$prefix)
            {
                return 'amazon.webp';
            }
            if (strpos($domain, 'ebay.') !== false && !$prefix)
            {
                return 'ebay.webp';
            }
            return $prefix . str_replace('.', '-', $domain) . '.' . $ext;
        }
        if (!empty($item['logo']))
        {
            return $prefix . md5($item['logo']) . '.' . $ext;
        }
        return '';
    }

    /** Fetch admin‑configured custom logo */
    public static function getCustomLogo($domain)
    {
        if (self::$logos === [])
        {
            $stored = (array) GeneralConfig::getInstance()->option('logos');
            foreach ($stored as $logo)
            {
                if (isset($logo['name'], $logo['value']))
                {
                    self::$logos[$logo['name']] = $logo['value'];
                }
            }
        }
        return self::$logos[$domain] ?? false;
    }

    /** Ensure uploads/ce‑logos directory exists and return its path. */
    public static function getMerchantLogoDir()
    {
        $uploads = wp_upload_dir();
        $dir     = trailingslashit($uploads['basedir']) . self::MERCHANT_LOGO_DIR;
        return is_dir($dir) || wp_mkdir_p($dir) ? $dir : false;
    }

    public static function getMerchantIconUrl(array $item, $blankOnError = false)
    {
        $prefix = 'icon_';

        // Respect module‑level config: show_small_logos
        if (!empty($item['module_id']))
        {
            $parser = ModuleManager::getInstance()->parserFactory($item['module_id']);
            if (
                $parser->getConfigInstance()->option_exists('show_small_logos') &&
                !filter_var($parser->config('show_small_logos'), FILTER_VALIDATE_BOOLEAN)
            )
            {
                return $blankOnError ? self::getBlankImg() : false;
            }
        }

        // Need a domain to build the favicon URL
        if (empty($item['domain']))
        {
            return $blankOnError ? self::getBlankImg() : false;
        }

        // Normalise domain (strip leading protocol)
        $domain = preg_replace('#^https?://#', '', (string) $item['domain']);
        $remoteUrl = sprintf(
            'https://t2.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=https://%s&size=16',
            urlencode($domain)
        );

        return self::getMerchantImageUrl(
            $item,
            $prefix,
            $remoteUrl,
            $blankOnError,
            'light',
            false,
        );
    }

    public static function purgeCachedLogos()
    {
        $dir = self::getMerchantLogoDir();
        if (!$dir)
        {
            return 0;
        }

        $deleted = 0;
        foreach (glob($dir . '/*') ?: [] as $path)
        {
            // Safety: process only regular files within the cache directory
            if (!is_file($path) || strpos(realpath($path), realpath($dir)) !== 0)
            {
                continue;
            }

            if (@unlink($path))
            {
                $deleted++;
            }
        }

        return $deleted;
    }
}

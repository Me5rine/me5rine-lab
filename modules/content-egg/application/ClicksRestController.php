<?php

namespace ContentEgg\application;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\models\LinkClicksDailyModel;

/**
 * ClicksRestController — records click counts for direct (non-redirect) links via REST.
 * Route: /wp-json/cegg/v1/click
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ClicksRestController extends \WP_REST_Controller
{
    public const VERSION = 1;
    public const BASE    = 'click';

    /** @var string */
    protected $namespace;
    /** @var string */
    protected $rest_base;

    private static $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->namespace = 'cegg/v' . self::VERSION;
        $this->rest_base = self::BASE;
    }

    /**
     * Wire route if direct click tracking is enabled.
     */
    public function init(): void
    {
        // Only register endpoint if direct tracking are enabled
        if (GeneralConfig::getInstance()->option('clicks_track_direct') !== 'enabled')
        {
            return;
        }

        \add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * /cegg/v1/click (POST)
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => \WP_REST_Server::CREATABLE, // POST
                'callback'            => [__CLASS__, 'handle'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'link_id' => ['type' => 'integer', 'required' => true],
                ],
            ]
        );
    }

    /**
     * Handle beacon: increments today's aggregate for the given link_id.
     * Returns 204 on success/ignored to minimize overhead.
     */
    public static function handle(\WP_REST_Request $req): \WP_REST_Response
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'POST';
        if ($method !== 'POST')
        {
            return new \WP_REST_Response(null, 405);
        }
        if (!self::isSameOriginRequest())
        {
            return new \WP_REST_Response(null, 403);
        }
        if (self::isPrefetchRequest())
        {
            return new \WP_REST_Response(null, 204);
        }

        $link_id = (int) $req->get_param('link_id');
        if ($link_id <= 0)
        {
            return new \WP_REST_Response(null, 400);
        }

        // Increment daily aggregate; ignore failures to keep UX snappy
        try
        {
            LinkClicksDailyModel::model()->incrementToday($link_id);
        }
        catch (\Throwable $e)
        {
            // swallow
        }

        // No payload needed
        return new \WP_REST_Response(null, 204);
    }

    /** Ensure the beacon comes from our own pages. */
    private static function isSameOriginRequest(): bool
    {
        $site = wp_parse_url(home_url());
        $siteScheme = strtolower($site['scheme'] ?? 'https');
        $siteHost   = strtolower(rtrim($site['host'] ?? '', '.'));
        $sitePort   = isset($site['port'])
            ? (int) $site['port']
            : ($siteScheme === 'https' ? 443 : 80);

        // 1) Fetch Metadata (best signal; not JS-spoofable)
        $sfs = strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
        if ($sfs === 'same-origin')
        {
            return true;
        }

        // Helper to compare scheme+host+port
        $isSame = static function (string $value) use ($siteScheme, $siteHost, $sitePort): bool
        {
            if ($value === '' || $value === 'null') return false;
            $p = wp_parse_url($value);
            if (!$p || empty($p['host'])) return false;

            $sch = strtolower($p['scheme'] ?? $siteScheme);
            $hst = strtolower(rtrim($p['host'], '.'));
            $prt = isset($p['port']) ? (int) $p['port'] : ($sch === 'https' ? 443 : 80);

            return $hst === $siteHost && $prt === $sitePort && $sch === $siteScheme;
        };

        // 2) Origin header (present on many POSTs/fetch/beacon)
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($isSame($origin))
        {
            return true;
        }

        // 3) Referer fallback (can be stripped by policy/privacy)
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($isSame($ref))
        {
            return true;
        }

        return false;
    }

    private static function isPrefetchRequest(): bool
    {
        foreach (['HTTP_PURPOSE', 'HTTP_X_PURPOSE', 'HTTP_SEC_PURPOSE', 'HTTP_X_MOZ'] as $k)
        {
            if (!empty($_SERVER[$k]) && stripos($_SERVER[$k], 'prefetch') !== false)
            {
                return true;
            }
        }
        return false;
    }
}

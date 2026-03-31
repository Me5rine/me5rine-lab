<?php

namespace ContentEgg\application\admin;

defined('\ABSPATH') || exit;

use ContentEgg\application\Plugin;
use ContentEgg\application\components\ContentManager;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\ClickStatsHelper;

/**
 * ClicksStatsController — admin dashboard for affiliate clicks statistics
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 * Range-first: all stats reflect ONLY the selected date range.
 */
class ClicksStatsController
{
    public const slug = 'content-egg-stats';

    public function __construct()
    {
        if (!ClickStatsHelper::isEnabled())
        {
            return;
        }
        \add_action('admin_menu', [$this, 'add_admin_menu']);
        \add_action('admin_init', [$this, 'remove_http_referer']);
    }

    /** Prevent nested _wp_http_referer on refresh */
    public function remove_http_referer(): void
    {
        global $pagenow;

        if (
            $pagenow === 'admin.php'
            && isset($_GET['page'])
            && $_GET['page'] === self::slug
            && !empty($_GET['_wp_http_referer'])
            && isset($_SERVER['REQUEST_URI'])
        )
        {
            \wp_safe_redirect(\remove_query_arg(['_wp_http_referer', '_wpnonce'], esc_url_raw(\wp_unslash($_SERVER['REQUEST_URI']))));
            exit;
        }
    }

    public function add_admin_menu(): void
    {
        $menu_title = esc_html__('Clicks Statistics', 'content-egg');

        \add_submenu_page(
            Plugin::slug,
            __('Clicks Statistics', 'content-egg') . ' &lsaquo; Content Egg',
            $menu_title,
            'publish_posts',
            self::slug,
            [$this, 'actionIndex']
        );
    }

    /** Dashboard (Range-first) */
    public function actionIndex(): void
    {
        if (Plugin::isInactiveEnvato())
        {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Clicks Statistics', 'content-egg') . '</h1>';
            echo '<div class="notice notice-error"><p>'
                . esc_html__('You need to activate the plugin first to view Clicks Statistics.', 'content-egg')
                . '</p></div>';

            $activate_url = admin_url('admin.php?page=content-egg-lic');
            echo '<p><a href="' . esc_url($activate_url) . '" class="button button-primary">'
                . esc_html__('Activate now', 'content-egg') . '</a></p>';

            echo '</div></div>';
            return;
        }

        \wp_enqueue_style('cegg-bootstrap5-full');
        \wp_enqueue_style('cegg-bootstrap-icons', PluginAdmin::res('/admin/bootstrap/css/bootstrap-icons.min.css'), [], Plugin::version());

        // Filters
        $filters = $this->readFilters(); // range/module/date_from/date_to/range_label

        // Summary for selected range + previous equal-length range
        $rangeSummary = $this->getRangeSummary($filters['date_from'], $filters['date_to'], $filters['module_id']);
        $rangeComparison = $this->buildRangeComparison($filters['date_from'], $filters['date_to'], $filters['module_id'], $rangeSummary['range_total'], $rangeSummary['prev_total']);

        // Chart (selected range only)
        $chart = $this->buildChartOptions($filters['date_from'], $filters['date_to'], $filters['module_id']);

        // Top lists (selected range only)
        $limit     = (int) apply_filters('cegg_clicks_stats_top_items_limit', 20);
        $top_links = $this->getTopLinks($filters['date_from'], $filters['date_to'], $filters['module_id'], $limit);
        $top_posts = $this->getTopPosts($filters['date_from'], $filters['date_to'], $filters['module_id'], $limit);

        // Zero-click lists (selected range)
        $no_click_links = $this->getNoClickLinks($filters['date_from'], $filters['date_to'], $filters['module_id'], $limit);
        $no_click_posts = $this->getNoClickPosts($filters['date_from'], $filters['date_to'], $filters['module_id'], $limit);

        // Modules for dropdown (already filtered by tracking mode)
        $modules = ClickStatsHelper::modulesForStatsFilter();

        // Tracking overview
        $overview = $this->buildTrackingOverview();

        // Donut: clicks share by module (selected range)
        $moduleShareChart = $this->buildModuleShareChartOptions($filters['date_from'], $filters['date_to'], $filters['module_id']);

        PluginAdmin::getInstance()->render('stats_index', [
            'filters'                 => $filters,
            'range_summary'           => $rangeSummary,       // ['range_total','prev_total','days','range_label','date_from','date_to','today_in_range']
            'range_comparison'        => $rangeComparison,    // ['label','class','icon','pct','abs','sign']
            'modules'                 => $modules,
            'clicksChartOptions'      => $chart,
            'top_links'               => $top_links,          // rows with 'clicks' (range) and 'prev_clicks'
            'top_posts'               => $top_posts,          // rows with 'clicks' (range) and 'prev_clicks'
            'no_click_links'          => $no_click_links,
            'no_click_posts'          => $no_click_posts,
            'track_redirect_enabled'  => $overview['track_redirect_enabled'],
            'track_direct_enabled'    => $overview['track_direct_enabled'],
            'mods_redirect'           => $overview['mods_redirect'],
            'mods_direct'             => $overview['mods_direct'],
            'mods_none'               => $overview['mods_none'],
            'moduleShareChartOptions' => $moduleShareChart,
        ]);
    }

    // =================
    // Range-first core
    // =================

    /**
     * Range summary:
     *   - range_total: clicks in [date_from, date_to]
     *   - prev_total : clicks in previous range of the same length
     *   - days       : number of days in range
     *   - today_in_range: bool (whether range end is today)
     */
    private function getRangeSummary(string $date_from, string $date_to, string $module_id = ''): array
    {
        $days = max(1, $this->daysInRange($date_from, $date_to));

        $range_total = $this->sumClicksBetween($date_from, $date_to, $module_id);

        $prev_from = $this->ymdShift($date_from, -$days);
        $prev_to   = $this->ymdShift($date_to,   -$days);
        $prev_total = $this->sumClicksBetween($prev_from, $prev_to, $module_id);

        // Is range end "today" (site-local)?
        $now_ts   = \current_time('timestamp');
        $tzshift  = (int) get_option('gmt_offset') * HOUR_IN_SECONDS;
        $todayYmd = gmdate('Y-m-d', $now_ts + $tzshift);
        $today_in_range = ($date_to === $todayYmd);

        return [
            'range_total'     => (int) $range_total,
            'prev_total'      => (int) $prev_total,
            'days'            => $days,
            'date_from'       => $date_from,
            'date_to'         => $date_to,
            'range_label'     => $date_from . ' → ' . $date_to,
            'today_in_range'  => $today_in_range,
        ];
    }

    /**
     * Build range-vs-previous comparison descriptor for the UI.
     */
    private function buildRangeComparison(string $date_from, string $date_to, string $module_id, int $current, int $previous): array
    {
        // Reuse delta formatter
        $delta = $this->makeDelta($current, $previous, __('previous period', 'content-egg'), false);

        // Add raw numbers for optional tooltips / progress bars
        $sign = 0;
        if ($current > $previous) $sign = 1;
        elseif ($current < $previous) $sign = -1;

        $abs = $current - $previous;
        $pct = ($previous > 0) ? round(($abs / $previous) * 100) : null;

        return [
            'label' => $delta['label'],
            'class' => $delta['class'],
            'icon'  => $delta['icon'],
            'pct'   => $pct,
            'abs'   => $abs,
            'sign'  => $sign,
        ];
    }

    // ===========
    // Data reads
    // ===========

    /**
     * Sum clicks for a date window (inclusive), optionally filtered by module_id.
     * NOTE: This aggregates from the daily table; it intentionally includes all posts,
     * regardless of post_status. (Per-item views exclude trashed where relevant.)
     */
    private function sumClicksBetween(string $fromYmd, string $toYmd, string $module_id = ''): int
    {
        global $wpdb;
        $lc  = $wpdb->prefix . 'cegg_link_clicks_daily';
        $idx = $wpdb->prefix . 'cegg_link_index';

        if ($module_id !== '')
        {
            $sql = $wpdb->prepare(
                "SELECT COALESCE(SUM(lc.clicks),0)
                 FROM {$lc} lc
                 INNER JOIN {$idx} li ON li.id = lc.link_id
                 WHERE lc.ymd BETWEEN %s AND %s
                   AND li.module_id = %s",
                $fromYmd,
                $toYmd,
                $module_id
            );
        }
        else
        {
            $sql = $wpdb->prepare(
                "SELECT COALESCE(SUM(clicks),0)
                 FROM {$lc}
                 WHERE ymd BETWEEN %s AND %s",
                $fromYmd,
                $toYmd
            );
        }
        $sum = (int) $wpdb->get_var($sql);
        return $sum > 0 ? $sum : 0;
    }

    /** One-day sum helper */
    private function sumDay(string $ymd, string $module_id = ''): int
    {
        return $this->sumClicksBetween($ymd, $ymd, $module_id);
    }

    /** Days in an inclusive Y-m-d range. */
    private function daysInRange(string $fromYmd, string $toYmd): int
    {
        $a = strtotime($fromYmd . ' 00:00:00');
        $b = strtotime($toYmd   . ' 00:00:00');
        if ($a === false || $b === false) return 0;
        return (int) (abs(($b - $a) / DAY_IN_SECONDS)) + 1;
    }

    /** Build a single delta descriptor (shared formatter) */
    private function makeDelta(int $current, int $previous, string $refLabel, bool $suppressNew = false): array
    {
        if ($previous <= 0)
        {
            if ($suppressNew)
            {
                return ['label' => '', 'class' => 'text-muted', 'icon' => 'bi-dash'];
            }
            if ($current <= 0)
            {
                return ['label' => __('0% from ', 'content-egg') . $refLabel, 'class' => 'text-muted', 'icon' => 'bi-dash'];
            }
            return ['label' => __('new from ', 'content-egg') . $refLabel, 'class' => 'text-success', 'icon' => 'bi-arrow-up-right'];
        }

        $diff = $current - $previous;
        if ($diff === 0)
        {
            return ['label' => __('0% from ', 'content-egg') . $refLabel, 'class' => 'text-muted', 'icon' => 'bi-dash'];
        }

        $pct  = round(($diff / $previous) * 100);
        $sign = $pct > 0 ? '+' : '';
        return [
            'label' => sprintf(__('%s%d%% from ', 'content-egg'), $sign, $pct) . $refLabel,
            'class' => $pct > 0 ? 'text-success' : 'text-danger',
            'icon'  => $pct > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right',
        ];
    }

    /** Clicks chart data (daily series) for Morris — range only */
    private function buildChartOptions(string $date_from, string $date_to, string $module_id = ''): array
    {
        global $wpdb;
        $lc  = $wpdb->prefix . 'cegg_link_clicks_daily';
        $idx = $wpdb->prefix . 'cegg_link_index';

        if ($module_id !== '')
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT lc.ymd AS d, SUM(lc.clicks) AS c
                     FROM {$lc} lc INNER JOIN {$idx} li ON li.id = lc.link_id
                     WHERE lc.ymd BETWEEN %s AND %s AND li.module_id = %s
                     GROUP BY lc.ymd ORDER BY lc.ymd ASC",
                    $date_from,
                    $date_to,
                    $module_id
                ),
                ARRAY_A
            );
        }
        else
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ymd AS d, SUM(clicks) AS c
                     FROM {$lc}
                     WHERE ymd BETWEEN %s AND %s
                     GROUP BY ymd ORDER BY ymd ASC",
                    $date_from,
                    $date_to
                ),
                ARRAY_A
            );
        }

        // Index by date for fast fill
        $byDate = [];
        foreach ((array) $rows as $r)
        {
            $byDate[(string) $r['d']] = (int) $r['c'];
        }

        // Fill all days in range with zeros if missing
        $series = [];
        $day = $date_from;
        while ($day <= $date_to)
        {
            $series[] = [
                'date'   => $day,
                'clicks' => (int) ($byDate[$day] ?? 0),
            ];
            $day = $this->ymdShift($day, 1);
        }

        return [
            'chartType' => 'Area',
            'data'      => $series,
            'xkey'      => 'date',
            'ykeys'     => ['clicks'],
            'labels'    => [__('Clicks', 'content-egg')],
            'lineColors'         => ['#1a73e8'],
            'fillOpacity'        => 0.15,
            'pointFillColors'    => ['#ffffff'],
            'pointStrokeColors'  => ['#1a73e8'],
            'lineWidth'          => 2,
        ];
    }

    /**
     * Top links (products) — range only.
     * Also includes prev_clicks for the previous equal-length range (for deltas in UI).
     */
    private function getTopLinks(string $date_from, string $date_to, string $module_id = '', int $limit = 15): array
    {
        global $wpdb;
        $lc   = $wpdb->prefix . 'cegg_link_clicks_daily';
        $idx  = $wpdb->prefix . 'cegg_link_index';
        $post = $wpdb->posts;

        // Current range
        if ($module_id !== '')
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT li.id AS link_id, li.post_id, li.slug, li.module_id, li.unique_id,
                            SUM(lc.clicks) AS clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id AND p.post_status <> 'trash'
                     INNER JOIN {$lc} lc ON lc.link_id = li.id AND lc.ymd BETWEEN %s AND %s
                     WHERE li.module_id = %s
                     GROUP BY li.id
                     ORDER BY clicks DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $module_id,
                    $limit
                ),
                ARRAY_A
            );
        }
        else
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT li.id AS link_id, li.post_id, li.slug, li.module_id, li.unique_id,
                            SUM(lc.clicks) AS clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id AND p.post_status <> 'trash'
                     INNER JOIN {$lc} lc ON lc.link_id = li.id AND lc.ymd BETWEEN %s AND %s
                     GROUP BY li.id
                     ORDER BY clicks DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $limit
                ),
                ARRAY_A
            );
        }

        // Previous equal-length range for the same link set (to compute deltas if needed)
        $days = max(1, $this->daysInRange($date_from, $date_to));
        $prev_from = $this->ymdShift($date_from, -$days);
        $prev_to   = $this->ymdShift($date_to,   -$days);

        $linkIds = array_map(static fn($r) => (int)$r['link_id'], (array) $rows);
        $prevClicksByLink = [];
        if ($linkIds)
        {
            $in = implode(',', array_fill(0, count($linkIds), '%d'));
            $sqlPrev = $wpdb->prepare(
                "SELECT lc.link_id, SUM(lc.clicks) AS clicks
                 FROM {$lc} lc
                 WHERE lc.link_id IN ($in)
                   AND lc.ymd BETWEEN %s AND %s
                 GROUP BY lc.link_id",
                array_merge($linkIds, [$prev_from, $prev_to])
            );
            $prevRows = $wpdb->get_results($sqlPrev, ARRAY_A);
            foreach ((array)$prevRows as $pr)
            {
                $prevClicksByLink[(int)$pr['link_id']] = (int)$pr['clicks'];
            }
        }

        // Enrich with product title + edit URL anchored to unique_id; fallback to humanized slug
        $out = [];
        foreach ((array) $rows as $r)
        {
            $pid       = (int) $r['post_id'];
            $mid       = (string) $r['module_id'];
            $uid       = (string) $r['unique_id'];
            $slugLabel = ucwords(str_replace('-', ' ', (string) $r['slug']));

            $product = ContentManager::getProductbyUniqueId($uid, $mid, $pid);
            $title   = isset($product['title']) && $product['title'] !== '' ? (string) $product['title'] : $slugLabel;

            $lid     = (int) $r['link_id'];
            $prev    = (int) ($prevClicksByLink[$lid] ?? 0);

            $out[] = [
                'link_id'       => $lid,
                'title'         => $title,
                'post_edit_url' => $pid > 0 ? admin_url('post.php?post=' . $pid . '&action=edit#' . rawurlencode($mid . '-' . $uid)) : '',
                'clicks'        => (int) $r['clicks'], // range clicks
                'prev_clicks'   => $prev,              // previous equal-length range
            ];
        }
        return $out;
    }

    /**
     * Top posts — range only.
     * Also includes prev_clicks for the previous equal-length range.
     */
    private function getTopPosts(string $date_from, string $date_to, string $module_id = '', int $limit = 15): array
    {
        global $wpdb;
        $lc   = $wpdb->prefix . 'cegg_link_clicks_daily';
        $idx  = $wpdb->prefix . 'cegg_link_index';
        $post = $wpdb->posts;

        if ($module_id !== '')
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT li.post_id, SUM(lc.clicks) AS clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id AND p.post_status <> 'trash'
                     INNER JOIN {$lc} lc ON lc.link_id = li.id AND lc.ymd BETWEEN %s AND %s
                     WHERE li.module_id = %s
                     GROUP BY li.post_id
                     ORDER BY clicks DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $module_id,
                    $limit
                ),
                ARRAY_A
            );
        }
        else
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT li.post_id, SUM(lc.clicks) AS clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id AND p.post_status <> 'trash'
                     INNER JOIN {$lc} lc ON lc.link_id = li.id AND lc.ymd BETWEEN %s AND %s
                     GROUP BY li.post_id
                     ORDER BY clicks DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $limit
                ),
                ARRAY_A
            );
        }

        // Previous equal-length range for these posts
        $days = max(1, $this->daysInRange($date_from, $date_to));
        $prev_from = $this->ymdShift($date_from, -$days);
        $prev_to   = $this->ymdShift($date_to,   -$days);

        $postIds = array_map(static fn($r) => (int)$r['post_id'], (array) $rows);
        $prevByPost = [];
        if ($postIds)
        {
            $in = implode(',', array_fill(0, count($postIds), '%d'));
            // Sum clicks across links that belong to these posts during the previous range
            $sqlPrev = $wpdb->prepare(
                "SELECT li.post_id, SUM(lc.clicks) AS clicks
                 FROM {$idx} li
                 INNER JOIN {$lc} lc ON lc.link_id = li.id
                 WHERE li.post_id IN ($in)
                   AND lc.ymd BETWEEN %s AND %s
                 GROUP BY li.post_id",
                array_merge($postIds, [$prev_from, $prev_to])
            );
            $prevRows = $wpdb->get_results($sqlPrev, ARRAY_A);
            foreach ((array)$prevRows as $pr)
            {
                $prevByPost[(int)$pr['post_id']] = (int)$pr['clicks'];
            }
        }

        $out = [];
        foreach ((array) $rows as $r)
        {
            $pid = (int) $r['post_id'];
            if ($pid <= 0) continue;

            $title = get_the_title($pid);
            if ($title === '') $title = __('(no title)', 'content-egg');

            $out[] = [
                'post_id'       => $pid,
                'post_title'    => $title,
                'post_edit_url' => admin_url('post.php?post=' . $pid . '&action=edit'),
                'clicks'        => (int) $r['clicks'],               // range clicks
                'prev_clicks'   => (int) ($prevByPost[$pid] ?? 0),   // previous equal-length range
            ];
        }
        return $out;
    }

    // ======================
    // Zero-click diagnostics
    // ======================

    /**
     * Products (links) with ZERO clicks in the selected window.
     * Excludes trashed posts.
     *
     * @return array<int,array{link_id:int,post_id:int,module_id:string,unique_id:string,title:string,post_edit_url:string}>
     */
    private function getNoClickLinks(string $date_from, string $date_to, string $module_id = '', int $limit = 25): array
    {
        global $wpdb;
        $lc   = $wpdb->prefix . 'cegg_link_clicks_daily';
        $idx  = $wpdb->prefix . 'cegg_link_index';
        $post = $wpdb->posts;

        if ($module_id !== '')
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT li.id AS link_id, li.post_id, li.module_id, li.unique_id, li.slug,
                            COALESCE(SUM(lc.clicks),0) AS clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id AND p.post_status <> 'trash'
                     LEFT JOIN {$lc} lc
                            ON lc.link_id = li.id
                           AND lc.ymd BETWEEN %s AND %s
                     WHERE li.module_id = %s
                     GROUP BY li.id
                     HAVING clicks = 0
                     ORDER BY li.updated_at DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $module_id,
                    $limit
                ),
                ARRAY_A
            );
        }
        else
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT li.id AS link_id, li.post_id, li.module_id, li.unique_id, li.slug,
                            COALESCE(SUM(lc.clicks),0) AS clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id AND p.post_status <> 'trash'
                     LEFT JOIN {$lc} lc
                            ON lc.link_id = li.id
                           AND lc.ymd BETWEEN %s AND %s
                     GROUP BY li.id
                     HAVING clicks = 0
                     ORDER BY li.updated_at DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $limit
                ),
                ARRAY_A
            );
        }

        $out = [];
        foreach ((array)$rows as $r)
        {
            $pid   = (int)$r['post_id'];
            $mid   = (string)$r['module_id'];
            $uid   = (string)$r['unique_id'];
            $slug  = (string)$r['slug'];

            // Resolve product title from Content Egg meta; fallback to humanized slug
            $title = '';
            try
            {
                $product = \ContentEgg\application\components\ContentManager::getProductbyUniqueId($uid, $mid, $pid);
                if (is_array($product) && !empty($product['title']))
                {
                    $title = (string)$product['title'];
                }
            }
            catch (\Throwable $e)
            {
                // ignore, fallback below
            }
            if ($title === '')
            {
                $title = ucwords(str_replace('-', ' ', $slug));
            }

            $out[] = [
                'link_id'       => (int)$r['link_id'],
                'post_id'       => $pid,
                'module_id'     => $mid,
                'unique_id'     => $uid,
                'title'         => $title,
                'post_edit_url' => $pid > 0 ? \admin_url('post.php?post=' . $pid . '&action=edit#' . rawurlencode($mid . '-' . $uid)) : '',
            ];
        }

        return $out;
    }

    /**
     * Posts where ALL links recorded ZERO clicks in the selected window.
     * Excludes trashed posts.
     *
     * @return array<int,array{post_id:int,post_title:string,post_edit_url:string,links_count:int}>
     */
    private function getNoClickPosts(string $date_from, string $date_to, string $module_id = '', int $limit = 25): array
    {
        global $wpdb;
        $lc   = $wpdb->prefix . 'cegg_link_clicks_daily';
        $idx  = $wpdb->prefix . 'cegg_link_index';
        $post = $wpdb->posts;

        if ($module_id !== '')
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.ID AS post_id,
                            p.post_title,
                            COUNT(DISTINCT li.id) AS links_count,
                            COALESCE(SUM(lc.clicks),0) AS total_clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id
                     LEFT JOIN {$lc} lc
                            ON lc.link_id = li.id
                           AND lc.ymd BETWEEN %s AND %s
                     WHERE li.module_id = %s
                       AND p.post_status <> 'trash'
                     GROUP BY p.ID
                     HAVING total_clicks = 0
                     ORDER BY p.post_date DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $module_id,
                    $limit
                ),
                ARRAY_A
            );
        }
        else
        {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.ID AS post_id,
                            p.post_title,
                            COUNT(DISTINCT li.id) AS links_count,
                            COALESCE(SUM(lc.clicks),0) AS total_clicks
                     FROM {$idx} li
                     INNER JOIN {$post} p ON p.ID = li.post_id
                     LEFT JOIN {$lc} lc
                            ON lc.link_id = li.id
                           AND lc.ymd BETWEEN %s AND %s
                     WHERE p.post_status <> 'trash'
                     GROUP BY p.ID
                     HAVING total_clicks = 0
                     ORDER BY p.post_date DESC
                     LIMIT %d",
                    $date_from,
                    $date_to,
                    $limit
                ),
                ARRAY_A
            );
        }

        $out = [];
        foreach ((array)$rows as $r)
        {
            $pid = (int)$r['post_id'];
            $out[] = [
                'post_id'       => $pid,
                'post_title'    => isset($r['post_title']) ? (string)$r['post_title'] : __('(no title)', 'content-egg'),
                'post_edit_url' => $pid > 0 ? \admin_url('post.php?post=' . $pid . '&action=edit') : '',
                'links_count'   => isset($r['links_count']) ? (int)$r['links_count'] : 0,
            ];
        }

        return $out;
    }

    // ===============
    // Misc utilities
    // ===============

    /** Shift a Y-m-d string by N days (can be negative). */
    private function ymdShift(string $ymd, int $deltaDays): string
    {
        $ts = strtotime($ymd . ' 00:00:00');
        $shift = $ts + ($deltaDays * DAY_IN_SECONDS);
        return gmdate('Y-m-d', $shift);
    }

    /** Return the later (max) of two Y-m-d strings */
    private function maxYmd(string $a, string $b): string
    {
        return ($a > $b) ? $a : $b;
    }

    /**
     * Build Morris Donut options for module share in the selected period.
     */
    private function buildModuleShareChartOptions(string $date_from, string $date_to, string $module_id = ''): array
    {
        global $wpdb;
        $lc  = $wpdb->prefix . 'cegg_link_clicks_daily';
        $idx = $wpdb->prefix . 'cegg_link_index';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT li.module_id, SUM(lc.clicks) AS clicks
                 FROM {$lc} lc
                 INNER JOIN {$idx} li ON li.id = lc.link_id
                 WHERE lc.ymd BETWEEN %s AND %s
                 GROUP BY li.module_id
                 ORDER BY clicks DESC",
                $date_from,
                $date_to
            ),
            ARRAY_A
        );

        if (!$rows) return [];

        // Map module_id -> readable name
        $nameMap = ModuleManager::getInstance()->getAffiliteModulesList(true);

        // Top N + others
        $N = 7;
        $top   = array_slice($rows, 0, $N);
        $rest  = array_slice($rows, $N);

        $data = [];
        foreach ($top as $r)
        {
            $mid = (string) $r['module_id'];
            $val = (int) $r['clicks'];
            if ($val <= 0) continue;
            $label = $nameMap[$mid] ?? $mid;
            $data[] = ['label' => $label, 'value' => $val];
        }
        if ($rest)
        {
            $others = array_sum(array_map(static function ($r)
            {
                return (int)$r['clicks'];
            }, $rest));
            if ($others > 0)
            {
                $data[] = ['label' => __('Others', 'content-egg'), 'value' => $others];
            }
        }

        if (!$data) return [];

        return [
            'chartType' => 'Donut',
            'colors'    => ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f', '#edc949', '#af7aa1', '#ff9da7', '#9c755f', '#bab0ab'],
            'data'      => $data, // [{label,value}, ...]
            '_js'       => [
                'formatter' => 'function (y, data) {
                    var total = 0;
                    for (var i = 0; i < this.data.length; i++) total += this.data[i].value;
                    var pct = total ? Math.round((y / total) * 100) : 0;
                    return y + " (" + pct + "%)";
                }'
            ],
        ];
    }

    // ==============
    // Filters / UX
    // ==============

    /**
     * Filters:
     * - range: today|7d|30d|custom (default 30d)
     * - from, to (YYYY-MM-DD) if custom
     * - module_id (optional)
     */
    private function readFilters(): array
    {
        $range = isset($_GET['range']) ? sanitize_text_field(\wp_unslash($_GET['range'])) : '30d';
        $allowed = ['today', '7d', '30d', '90d', 'this_month', 'last_month', 'custom'];
        if (!in_array($range, $allowed, true))
        {
            $range = '30d';
        }

        $module_id = '';
        if (isset($_GET['module_id']))
        {
            $module_id = sanitize_text_field(\wp_unslash($_GET['module_id']));
        }

        [$date_from, $date_to, $label] = $this->computeDateRange($range);

        if ($range === 'custom')
        {
            $from = isset($_GET['from']) ? sanitize_text_field(\wp_unslash($_GET['from'])) : '';
            $to   = isset($_GET['to'])   ? sanitize_text_field(\wp_unslash($_GET['to']))   : '';
            if ($this->isValidYmd($from) && $this->isValidYmd($to) && $from <= $to)
            {
                $date_from = $from;
                $date_to   = $to;
                $label     = $from . ' → ' . $to;
            }
        }

        return [
            'range'       => $range,
            'date_from'   => $date_from,
            'date_to'     => $date_to,
            'range_label' => $label,
            'module_id'   => $module_id,
        ];
    }

    /** Inclusive date range in site-local terms */
    private function computeDateRange(string $range): array
    {
        $now_ts   = \current_time('timestamp'); // local TZ
        $tzshift  = (int) get_option('gmt_offset') * HOUR_IN_SECONDS;

        // Helpers in local (by shifting into “local” before gmdate)
        $local_ts = $now_ts + $tzshift;
        $todayYmd = gmdate('Y-m-d', $local_ts);

        switch ($range)
        {
            case 'today':
                return [$todayYmd, $todayYmd, __('Today', 'content-egg')];

            case '7d':
                $from = gmdate('Y-m-d', $local_ts - 6 * DAY_IN_SECONDS);
                return [$from, $todayYmd, __('Last 7 days', 'content-egg')];

            case '30d':
                $from = gmdate('Y-m-d', $local_ts - 29 * DAY_IN_SECONDS);
                return [$from, $todayYmd, __('Last 30 days', 'content-egg')];

            case '90d':
                $from = gmdate('Y-m-d', $now_ts - 89 * DAY_IN_SECONDS + $tzshift);
                return [$from, $todayYmd, __('Last 90 days', 'content-egg')];

            case 'this_month':
                // Month-to-date: first day of current month through today
                $from = gmdate('Y-m-01', $local_ts);
                return [$from, $todayYmd, __('This month', 'content-egg')];

            case 'last_month':
                // Full previous month (1st through last day)
                $y  = (int) gmdate('Y', $local_ts);
                $m  = (int) gmdate('n', $local_ts); // 1–12
                $py = ($m === 1) ? ($y - 1) : $y;
                $pm = ($m === 1) ? 12 : ($m - 1);
                $days = cal_days_in_month(CAL_GREGORIAN, $pm, $py);

                $from = sprintf('%04d-%02d-01', $py, $pm);
                $to   = sprintf('%04d-%02d-%02d', $py, $pm, $days);
                return [$from, $to, __('Last month', 'content-egg')];

            case 'custom':
                // Placeholder; caller may override if valid
                $from = gmdate('Y-m-d', $local_ts - 29 * DAY_IN_SECONDS);
                return [$from, $todayYmd, __('Custom range', 'content-egg')];

            default:
                // Fallback to 30d
                $from = gmdate('Y-m-d', $local_ts - 29 * DAY_IN_SECONDS);
                return [$from, $todayYmd, __('Last 30 days', 'content-egg')];
        }
    }

    /** YYYY-MM-DD validator */
    private function isValidYmd(string $v): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v))
        {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $v));
        return checkdate($m, $d, $y);
    }

    /**
     * Build tracking overview using ClickStatsHelper convenience methods.
     *
     * @return array{
     *   track_redirect_enabled:bool,
     *   track_direct_enabled:bool,
     *   mods_redirect:array<string,string>,
     *   mods_direct:array<string,string>,
     *   mods_none:array<string,string>
     * }
     */
    private function buildTrackingOverview(): array
    {
        // Global toggles
        $gc = GeneralConfig::getInstance();
        $trackRedirect = ($gc->option('clicks_track_redirect') === 'enabled');
        $trackDirect   = ($gc->option('clicks_track_direct') === 'enabled');

        // All affiliate modules (id => name)
        $allModules = ModuleManager::getInstance()->getAffiliteModulesList(true);
        if (!is_array($allModules))
        {
            $allModules = [];
        }

        // Modules tracked by each mode
        $mods_redirect = $trackRedirect ? ClickStatsHelper::modulesWithLocalRedirectEnabled()  : [];
        $mods_direct   = $trackDirect   ? ClickStatsHelper::modulesWithLocalRedirectDisabled() : [];

        // Modules without tracking = all − (redirect ∪ direct)
        $trackedIds = array_unique(array_merge(array_keys($mods_redirect), array_keys($mods_direct)));
        $mods_none  = $allModules;
        foreach ($trackedIds as $mid)
        {
            unset($mods_none[$mid]);
        }

        return [
            'track_redirect_enabled' => $trackRedirect,
            'track_direct_enabled'   => $trackDirect,
            'mods_redirect'          => $mods_redirect,
            'mods_direct'            => $mods_direct,
            'mods_none'              => $mods_none,
        ];
    }

    /** Render a small delta badge HTML for current vs previous period (with title showing previous value). */
    public static function renderDeltaBadge(int $current, int $previous): string
    {
        $prevLabel = sprintf(
            /* translators: %s is the previous period clicks number */
            __('Previous: %s', 'content-egg'),
            number_format_i18n($previous)
        );
        $titleAttr = ' title="' . esc_attr($prevLabel) . '" aria-label="' . esc_attr($prevLabel) . '"';

        if ($previous <= 0)
        {
            if ($current <= 0)
            {
                return '<span class="small text-muted ms-2"' . $titleAttr . '><i class="bi bi-dash"></i> 0%</span>';
            }
            // New vs no previous data
            return '<span class="small text-success ms-2"' . $titleAttr . '><i class="bi bi-arrow-up-right"></i> ' .
                esc_html__('new', 'content-egg') . '</span>';
        }

        $diff = $current - $previous;
        if ($diff === 0)
        {
            return '<span class="small text-muted ms-2"' . $titleAttr . '><i class="bi bi-dash"></i> 0%</span>';
        }

        $pct     = (int) round(($diff / $previous) * 100);
        $up      = $pct > 0;
        $cls     = $up ? 'text-success' : 'text-danger';
        $icon    = $up ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
        $display = abs($pct) . '%'; // no +/– sign, direction is conveyed by color and icon

        return '<span class="small ' . esc_attr($cls) . ' ms-2"' . $titleAttr . '><i class="bi ' . esc_attr($icon) .
            '"></i> ' . esc_html($display) . '</span>';
    }
}

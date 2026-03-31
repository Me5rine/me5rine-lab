<?php

namespace ContentEgg\application\helpers;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\models\LinkIndexModel;
use ContentEgg\application\models\LinkClicksDailyModel;

/**
 * ClickStatsHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */

class ClickStatsHelper
{
	/** @var array<int, array<string, array<string,int>>> post_id => module_id => unique_id => link_id */
	private static $linkIdCache = [];

	/** @var array<int, array{today:int,d7:int,d30:int,total:int}> link_id => aggregates */
	private static $aggCache = [];

	/**
	 * Whether any click stats are enabled (redirect or direct).
	 */
	public static function isEnabled(): bool
	{
		static $enabled = null;
		if ($enabled !== null)
		{
			return $enabled;
		}

		$cfg = GeneralConfig::getInstance();

		$enabled = in_array('enabled', [
			(string) $cfg->option('clicks_track_direct'),
			(string) $cfg->option('clicks_track_redirect'),
		], true);

		return $enabled;
	}

	/**
	 * Retention in days (0 => unlimited).
	 */
	public static function retentionDays(): int
	{
		$raw = GeneralConfig::getInstance()->option('clicks_retention_days');
		$days = (int) $raw;
		return ($raw === '' || $days < 0) ? 0 : $days;
	}

	/**
	 * Label for the "7d" window respecting retention (e.g., "5d" if retention=5).
	 */
	public static function label7(): string
	{
		$r = self::retentionDays();
		return ($r > 0 && $r < 7) ? sprintf(__('%dd', 'content-egg'), $r) : __('7d', 'content-egg');
	}

	/**
	 * Label for the "30d" window respecting retention (e.g., "10d" if retention=10).
	 */
	public static function label30(): string
	{
		$r = self::retentionDays();
		return ($r > 0 && $r < 30) ? sprintf(__('%dd', 'content-egg'), $r) : __('30d', 'content-egg');
	}

	/**
	 * Resolve link_id from a (post_id, module_id, unique_id) triplet.
	 * Uses a per-request cache populated via listByPost() for minimal queries.
	 */
	public static function linkIdForTriplet(int $post_id, string $module_id, string $unique_id): int
	{
		$post_id   = (int) $post_id;
		$module_id = (string) $module_id;
		$unique_id = (string) $unique_id;

		if ($post_id <= 0 || $module_id === '' || $unique_id === '')
		{
			return 0;
		}

		if (!isset(self::$linkIdCache[$post_id]))
		{
			self::$linkIdCache[$post_id] = [];
			$rows = LinkIndexModel::model()->listByPost($post_id);
			if (is_array($rows))
			{
				foreach ($rows as $r)
				{
					$mid = (string) $r['module_id'];
					$uid = (string) $r['unique_id'];
					self::$linkIdCache[$post_id][$mid][$uid] = (int) $r['id'];
				}
			}
		}

		return (int) (self::$linkIdCache[$post_id][$module_id][$unique_id] ?? 0);
	}

	/**
	 * Resolve link_id from an item array with keys: post_id, module_id, unique_id.
	 */
	public static function linkIdForItem(array $item): int
	{
		$post_id   = isset($item['post_id']) ? (int) $item['post_id'] : 0;
		$module_id = isset($item['module_id']) ? (string) $item['module_id'] : '';
		$unique_id = isset($item['unique_id']) ? (string) $item['unique_id'] : '';
		return self::linkIdForTriplet($post_id, $module_id, $unique_id);
	}

	/**
	 * Get aggregates for a link_id with per-request memoization.
	 * @return array{today:int,d7:int,d30:int,total:int}
	 */
	public static function aggregatesForLink(int $link_id): array
	{
		$link_id = (int) $link_id;
		if ($link_id <= 0)
		{
			return ['today' => 0, 'd7' => 0, 'd30' => 0, 'total' => 0];
		}

		if (!isset(self::$aggCache[$link_id]))
		{
			$agg = LinkClicksDailyModel::model()->aggregatesForLink($link_id);
			if (!is_array($agg))
			{
				$agg = ['today' => 0, 'd7' => 0, 'd30' => 0, 'total' => 0];
			}
			// Normalize + cast
			self::$aggCache[$link_id] = [
				'today' => (int) ($agg['today'] ?? 0),
				'd7'    => (int) ($agg['d7']    ?? 0),
				'd30'   => (int) ($agg['d30']   ?? 0),
				'total' => (int) ($agg['total'] ?? 0),
			];
		}

		return self::$aggCache[$link_id];
	}

	/**
	 * Get aggregates for an item (resolves link_id internally).
	 * @return array{today:int,d7:int,d30:int,total:int}
	 */
	public static function aggregatesForItem(array $item): array
	{
		$link_id = self::linkIdForItem($item);
		return self::aggregatesForLink($link_id);
	}

	/**
	 * Render a compact HTML badge suitable for admin lists or metabox rows.
	 * Default mode: "30d | total" (labels auto-adjust to retention).
	 *
	 * @param array{today:int,d7:int,d30:int,total:int} $agg
	 * @param array{mode?:string,class?:string,title?:string} $opts
	 *        mode: '30d_total' (default) | 'today_total' | 'full' (today/7d/30d/total)
	 * @return string HTML
	 */
	public static function renderBadge(array $agg, array $opts = []): string
	{
		if (!self::isEnabled())
		{
			return '<span class="na">–</span>';
		}

		$mode  = isset($opts['mode']) ? (string) $opts['mode'] : '30d_total';
		$class = 'cegg-clicks-badge ' . trim((string) ($opts['class'] ?? ''));
		$title = isset($opts['title']) ? (string) $opts['title'] : '';

		$t = [
			'today' => number_format_i18n((int) ($agg['today'] ?? 0)),
			'd7'    => number_format_i18n((int) ($agg['d7']    ?? 0)),
			'd30'   => number_format_i18n((int) ($agg['d30']   ?? 0)),
			'total' => number_format_i18n((int) ($agg['total'] ?? 0)),
		];

		if ($mode === 'today_total')
		{
			$ttl = $title !== '' ? $title
				: esc_attr__('Today | Total', 'content-egg');
			return sprintf(
				'<span class="%s" title="%s"><strong>%s</strong> | <em>%s</em></span>',
				esc_attr($class),
				esc_attr($ttl),
				$t['today'],
				$t['total']
			);
		}

		if ($mode === 'full')
		{
			$lbl7  = self::label7();
			$lbl30 = self::label30();
			$ttl   = $title !== '' ? $title
				: sprintf(
					/* translators: placeholders are window labels, e.g. 7d/30d or 5d/10d */
					esc_attr__('Today / %s / %s / Total', 'content-egg'),
					$lbl7,
					$lbl30
				);
			return sprintf(
				'<span class="%s" title="%s"><strong>%s</strong> / %s / %s / <em>%s</em></span>',
				esc_attr($class),
				esc_attr($ttl),
				$t['today'],
				$t['d7'],
				$t['d30'],
				$t['total']
			);
		}

		// Default: 30d | total (label adjusts to retention)
		$lbl30 = self::label30();
		$ttl   = $title !== '' ? $title
			: sprintf(
				/* translators: %s is the 30d label adjusted to retention */
				esc_attr__('%s | Total', 'content-egg'),
				$lbl30
			);

		return sprintf(
			'<span class="%s" title="%s">%s | <em>%s</em></span>',
			esc_attr($class),
			esc_attr($ttl),
			$t['d30'],
			$t['total']
		);
	}

	/**
	 * (Optional) Warm up the link-id cache for a post to reduce queries when rendering many items.
	 */
	public static function warmCacheForPost(int $post_id): void
	{
		$post_id = (int) $post_id;
		if ($post_id <= 0 || isset(self::$linkIdCache[$post_id]))
		{
			return;
		}
		self::$linkIdCache[$post_id] = [];
		$rows = LinkIndexModel::model()->listByPost($post_id);
		if (is_array($rows))
		{
			foreach ($rows as $r)
			{
				$mid = (string) $r['module_id'];
				$uid = (string) $r['unique_id'];
				self::$linkIdCache[$post_id][$mid][$uid] = (int) $r['id'];
			}
		}
	}

	/**
	 * Return module list suitable for the stats filter dropdown,
	 * based on global tracking toggles and each module's redirect setting.
	 *
	 * @return array<string,string> module_id => module_name
	 */
	public static function modulesForStatsFilter(): array
	{
		$track_direct   = GeneralConfig::getInstance()->option('clicks_track_direct') === 'enabled';
		$track_redirect = GeneralConfig::getInstance()->option('clicks_track_redirect') === 'enabled';

		// Both modes enabled => all modules are eligible
		if ($track_direct && $track_redirect)
		{
			$all = ModuleManager::getInstance()->getAffiliteModulesList(true) ?: [];
			return apply_filters('cegg_clicks_modules_for_filter', $all);
		}

		// Direct-only => modules with local redirect OFF
		if ($track_direct && !$track_redirect)
		{
			$disabled = self::modulesWithLocalRedirectDisabled();
			return apply_filters('cegg_clicks_modules_for_filter', $disabled);
		}

		// Redirect-only => modules with local redirect ON
		if ($track_redirect && !$track_direct)
		{
			$enabled = self::modulesWithLocalRedirectEnabled();
			return apply_filters('cegg_clicks_modules_for_filter', $enabled);
		}

		// Neither mode (shouldn't happen if feature is off)
		return apply_filters('cegg_clicks_modules_for_filter', []);
	}

	/**
	 * Modules where per-module setting "set_local_redirect" is ON.
	 *
	 * @return array<string,string> module_id => module_name
	 */
	public static function modulesWithLocalRedirectEnabled(): array
	{
		$sets = self::splitByLocalRedirect();
		return $sets['enabled'];
	}

	/**
	 * Modules where per-module setting "set_local_redirect" is OFF.
	 *
	 * @return array<string,string> module_id => module_name
	 */
	public static function modulesWithLocalRedirectDisabled(): array
	{
		$sets = self::splitByLocalRedirect();
		return $sets['disabled'];
	}

	/**
	 * Split all affiliate modules into two buckets by the "set_local_redirect" flag.
	 *
	 * @return array{enabled: array<string,string>, disabled: array<string,string>}
	 */
	private static function splitByLocalRedirect(): array
	{
		static $cache = null;
		if (is_array($cache))
		{
			return $cache;
		}

		$all = ModuleManager::getInstance()->getAffiliteModulesList(true) ?: [];

		$enabled  = [];
		$disabled = [];

		foreach ($all as $module_id => $name)
		{
			$module = ModuleManager::getInstance()->factory($module_id);
			if (!$module)
			{
				continue;
			}
			$local_on = (bool) $module->config('set_local_redirect');
			if ($local_on)
			{
				$enabled[$module_id] = $name;
			}
			else
			{
				$disabled[$module_id] = $name;
			}
		}

		// Cache for this request
		$cache = [
			'enabled'  => $enabled,
			'disabled' => $disabled,
		];

		return $cache;
	}
}

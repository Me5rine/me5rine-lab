<?php

namespace ContentEgg\application\helpers;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\Plugin;;

/**
 * AdminHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */
class AdminHelper
{

	public static function getCategoryList()
	{
		$taxonomy = array('category');

		// @todo: widget is initialized before woo? taxonomy does not exist
		if (in_array('product', GeneralConfig::getInstance()->option('post_types')) && \taxonomy_exists('product_cat'))
		{
			$taxonomy[] = 'product_cat';
		}

		$cat_args   = array('taxonomy' => $taxonomy, 'orderby' => 'name', 'order' => 'asc', 'hide_empty' => false);
		$categories = \get_terms($cat_args);

		$results = array();
		foreach ($categories as $key => $category)
		{
			$results[$category->term_id] = $category->name;
			if ($category->taxonomy == 'product_cat')
			{
				$results[$category->term_id] .= ' [product]';
			}
		}

		return $results;
	}

	/**
	 * Tabs as sections
	 */
	public static function doTabsSections($page)
	{
		global $wp_settings_sections, $wp_settings_fields;

		if (!isset($wp_settings_sections[$page]))
		{
			return;
		}

		echo '<div id="cegg-tabs">';
		echo '<ul>';
		$i = 1;
		foreach ((array) $wp_settings_sections[$page] as $section)
		{
			echo '<li><a href="#tabs-' . esc_attr($i) . '">' . esc_html($section['title']) . '</a></li>';
			$i++;
		}
		echo '</ul>';
		$i = 1;
		foreach ((array) $wp_settings_sections[$page] as $section)
		{
			echo '<div id="tabs-' . esc_attr($i) . '">';
			echo '<table class="form-table" role="presentation">';
			\do_settings_fields($page, $section['id']);
			echo '</table>';
			echo '</div>';
			$i++;
		}
		echo '</div>';
		echo '<script type="text/javascript">' . 'jQuery(document).ready(function($){$(\'#cegg-tabs\').tabs();});' . '</script>';
	}

	public static function getProductModules()
	{
		$modules = ModuleManager::getInstance()->getConfigurableModules();
		$results = array();
		foreach ($modules as $module)
		{
			if ($module->isDeprecated() && !$module->isActive())
			{
				continue;
			}

			if ($module->isAffiliateParser() && $module->isProductParser() && !$module->isAeParser() && !$module->isFeedParser())
			{
				$results[] = $module;
			}
		}

		return $results;
	}

	public static function getAeProductModules()
	{
		$modules = ModuleManager::getInstance()->getConfigurableModules();
		$results = array();
		foreach ($modules as $module)
		{
			if ($module->isDeprecated() && !$module->isActive())
			{
				continue;
			}

			if ($module->isAffiliateParser() && $module->isProductParser() && $module->isAeParser())
			{
				$results[] = $module;
			}
		}

		return $results;
	}

	public static function getFeedProductModules()
	{
		$modules = ModuleManager::getInstance()->getConfigurableModules();
		$results = array();

		foreach ($modules as $module)
		{
			if ($module->isDeprecated() && !$module->isActive())
			{
				continue;
			}

			if ($module->isAffiliateParser() && $module->isProductParser() && $module->isFeedParser())
			{
				$results[] = $module;
			}
		}

		return $results;
	}

	public static function getCouponModules()
	{
		$modules = ModuleManager::getInstance()->getConfigurableModules();
		$results = array();
		foreach ($modules as $module)
		{
			if ($module->isDeprecated() && !$module->isActive())
			{
				continue;
			}

			if ($module->isAffiliateParser() && $module->isCouponParser())
			{
				$results[] = $module;
			}
		}

		return $results;
	}

	public static function getContentModules()
	{
		$modules = ModuleManager::getInstance()->getConfigurableModules();
		$results = array();
		foreach ($modules as $module)
		{
			if ($module->isDeprecated() && !$module->isActive())
			{
				continue;
			}

			if (!$module->isAffiliateParser())
			{
				$results[] = $module;
			}
		}

		return $results;
	}

	public static function isAiEnabled()
	{
		if (GeneralConfig::getInstance()->option('ai_key'))
			return true;
		else
			return false;
	}

	public static function getPostCategoryList()
	{
		$terms = \get_terms(array(
			'taxonomy' => 'category',
			'hide_empty' => false,
		));

		if (!$terms || \is_wp_error($terms))
		{
			return array();
		}

		$categories = array();

		foreach ($terms as $term)
		{
			$categories[$term->term_id . '.'] = $term->name . ' [' . $term->term_id . ']';
		}

		return $categories;
	}

	public static function getSysAiWarning()
	{
		$has_ai_api_key = (bool) GeneralConfig::getInstance()->option('system_ai_key');

		if (! $has_ai_api_key)
		{
			$openai_url = 'https://platform.openai.com/api-keys';
			$settings_url = admin_url('admin.php?page=content-egg');

			$ai_warning = sprintf(
				'<div class="cegg-warning-box" role="alert">'
					.   '<strong>%s</strong> %s '
					.   '<a href="%s" target="_blank" rel="noopener noreferrer" class="cegg-link">%s</a>. '
					.   '<a href="%s" class="cegg-link">%s</a>'
					. '</div>',
				esc_html__('Warning:',           'content-egg'),
				esc_html__('You need to set up an', 'content-egg'),
				esc_url($openai_url),
				esc_html__('OpenAI API key',     'content-egg'),
				esc_url($settings_url),
				esc_html__('Go to Settings',     'content-egg')
			);
		}
		else
		{
			$ai_warning = '';
		}

		return $ai_warning;
	}

	public static function getAiWarning()
	{
		$has_ai_api_key = (bool) GeneralConfig::getInstance()->option('ai_key');

		if (! $has_ai_api_key)
		{
			$settings_url = admin_url('admin.php?page=content-egg');

			$ai_warning = sprintf(
				'<div class="cegg-warning-box" role="alert">'
					. '<strong>%s</strong> %s <i>%s</i>. '
					. '<a href="%s" class="cegg-link">%s</a>'
					. '</div>',
				esc_html__('Warning:', 'content-egg'),
				esc_html__('You need to set up an', 'content-egg'),
				esc_html__('AI API key', 'content-egg'),
				esc_url($settings_url),
				esc_html__('Go to Settings', 'content-egg')
			);
		}
		else
		{
			$ai_warning = '';
		}

		return $ai_warning;
	}

	public static function echoPlaceholderDescription(): void
	{
		$text = esc_html__('Available placeholders:', 'content-egg');

		$placeholders = [
			'<code>%AI.title%</code>',
			'<code>%AI.content%</code>',
			'<code>%PRODUCT.title%</code>',
			'<code>%PRODUCT.price%</code>',
			'<code>%PRODUCT.domain%</code>',
			'<code>%PRODUCT.url%</code>',
			'<code>%PRODUCT.keyword%</code>',
			'<code>%PRODUCT.ATTRIBUTE.attribute-name%</code>',
			'<code>%RANDOM(1,10)%</code>',
		];

		$html = sprintf(
			'<p class="description" style="margin-top: 0.8em;">%s %s</p>',
			$text,
			implode(', ', $placeholders)
		);

		echo wp_kses($html, [
			'p'    => ['class' => true, 'style' => true],
			'code' => [],
		]);
	}

	public static function getProFeatureWarning(): string
	{
		if (Plugin::isPro())
		{
			return '';
		}

		$url = Plugin::pluginPricingUrl('ce_pro_badge', 'pro_badge_click');
		return sprintf('<a href="%s" target="_blank" class="cegg-pro-link"><span class="cegg-badge cegg-badge-pro">PRO Feature</span></a>', esc_url($url));
	}

	public static function redirect($url, $status = 302, $fallback = '')
	{
		$default   = $fallback ?: home_url('/');
		$location  = wp_validate_redirect($url, $default);

		if (! $location)
		{
			wp_die(esc_html__('Invalid redirect URL.', 'text-domain'));
		}

		if (! headers_sent())
		{
			wp_safe_redirect($location, $status);
			exit;
		}

		// Fallback: headers already sent.
		nocache_headers();

		$js_url = wp_json_encode($location);

		echo '<!doctype html><html><head>';
		printf(
			'<meta http-equiv="refresh" content="%s">',
			esc_attr('0;url=' . $location)
		);
		echo '</head><body>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe JSON-encoded redirect URL.
		printf('<script>window.location.href=%s;</script>', $js_url);
		echo '</body></html>';
		exit;
	}
}

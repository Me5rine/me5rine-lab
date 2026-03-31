<?php

namespace ContentEgg\application\helpers;

use ContentEgg\application\admin\import\ImportPrompt;



defined('\ABSPATH') || exit;

/**
 * ProductHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */
class ProductHelper
{

	/**
	 * Replace import placeholders in a template string.
	 *
	 * Supported tokens
	 *   %KEYWORD%
	 *   %RANDOM(min,max)%
	 *   %SOURCE% / %PRODUCT%
	 *   %SOURCE.FIELD% / %PRODUCT.FIELD%
	 *   %SOURCE.specifications% / %SOURCE.reviews%
	 *   %PRODUCT.specifications% / %PRODUCT.reviews%
	 *   %SOURCE.extra.KEY% / %PRODUCT.extra.KEY%
	 *   %SOURCE.ATTRIBUTE.NAME% / %PRODUCT.ATTRIBUTE.NAME%
	 */
	public static function replaceImportPatterns(
		string $template,
		array  $sourceProduct,
		array  $product,
		array  $ai = [],
		string $keyword = ''
	): string
	{
		if ($template === '')
		{
			return '';
		}

		// Process spin-syntax first (“{a|b|c}” → one random variant)
		$template = TextHelper::spin($template);

		// Fast-path: no %…% tokens present
		if (!preg_match_all('/%([^%]+?)%/', $template, $matches))
		{
			return $template;
		}

		$replacements = [];

		foreach ($matches[0] as $i => $placeholder)
		{
			// Avoid doing the same work twice
			if (isset($replacements[$placeholder]))
			{
				continue;
			}

			// Upper-case variant used for routing; original kept for replacement
			$token = strtoupper($matches[1][$i]);

			// ---------- 0.  %KEYWORD% ----------
			if ($token === 'KEYWORD')
			{
				$replacements[$placeholder] = (string) $keyword;
				continue;
			}

			// ---------- 1. %RANDOM(min,max)% ----------
			if (strpos($token, 'RANDOM(') === 0)
			{
				$replacements[$placeholder] = self::resolveRandomPlaceholder($token);
				continue;
			}

			// ---------- 2. %SOURCE…% / %PRODUCT…% ----------
			$segments = explode('.', $token);
			$root     = array_shift($segments);

			if ($root === 'SOURCE' || $root === 'PRODUCT')
			{
				$record = ($root === 'SOURCE') ? $sourceProduct : $product;

				// 2-a. Whole-object placeholders — %PRODUCT% / %SOURCE%
				if (empty($segments))
				{
					$replacements[$placeholder] = ImportPrompt::getProductDataJsonStr($record);
					continue;
				}

				// 2-b. Specifications / reviews shortcuts
				$firstSeg = strtoupper($segments[0]);

				if ($firstSeg === 'SPECIFICATIONS')
				{
					$replacements[$placeholder] = ImportPrompt::getProductDataJsonStr($record, ['specifications']);
					continue;
				}

				if ($firstSeg === 'REVIEWS')
				{
					$replacements[$placeholder] = ImportPrompt::getProductDataJsonStr($record, ['userReviews']);
					continue;
				}

				// 2-c. Nested placeholders
				$replacements[$placeholder] = self::resolveProductPlaceholder($record, $segments);
				continue;
			}

			// ---------- 3. %AI.title% / %AI.content% ----------
			if ($root === 'AI' && !empty($segments))
			{
				$replacements[$placeholder] = self::resolveAIPlaceholder($segments[0], $ai);
				continue;
			}

			// ---------- 4. Unknown token ----------
			$replacements[$placeholder] = $placeholder;
		}

		return strtr($template, $replacements);
	}

	/**
	 * Resolves %AI.title% and %AI.content% from the provided AI data.
	 */
	private static function resolveAIPlaceholder(string $field, array $ai): string
	{
		$key     = 'ai.' . strtolower($field);
		$aiLower = array_change_key_case($ai, CASE_LOWER);

		return $aiLower[$key] ?? '';
	}

	/**
	 * Handle %RANDOM(min,max)% placeholders.
	 */
	private static function resolveRandomPlaceholder(string $token): int
	{
		if (preg_match('/RANDOM\((\d+),(\d+)\)/', $token, $m))
		{
			[$min, $max] = [(int) $m[1], (int) $m[2]];
			if ($min > $max)
			{
				[$min, $max] = [$max, $min]; // swap if out of order
			}
		}
		else
		{
			[$min, $max] = [0, 9999999];
		}

		return random_int($min, $max);
	}

	/**
	 * Handle %SOURCE.*% and %PRODUCT.*% placeholders (case-insensitive).
	 */
	private static function resolveProductPlaceholder(
		array $data,
		array $path
	): string
	{
		if (empty($path))
		{
			return ''; // Fallback only — whole-object handled earlier
		}

		// Normalize everything to lower-case
		$key           = strtolower($path[0]);
		$dataLower     = array_change_key_case($data, CASE_LOWER);
		$extraLower    = !empty($dataLower['extra']) && is_array($dataLower['extra'])
			? array_change_key_case($dataLower['extra'], CASE_LOWER)
			: [];
		$dataFieldLower = !empty($dataLower['data']) && is_array($dataLower['data'])
			? array_change_key_case($dataLower['data'], CASE_LOWER)
			: [];

		/* 1) ATTRIBUTE lookup: %TYPE.ATTRIBUTE.NAME% */
		if (
			$key === 'attribute'
			&& isset($path[1])
			&& !empty($data['features'])
			&& is_array($data['features'])
		)
		{
			$wanted = strtolower(sanitize_text_field($path[1]));
			foreach ($data['features'] as $attrPair)
			{
				if (
					!empty($attrPair['name'])
					&& strtolower($attrPair['name']) === $wanted
				)
				{
					return (string) ($attrPair['value'] ?? '');
				}
			}
			return '';
		}

		/* 2) EXTRA lookup: %TYPE.extra.KEY% */
		if ($key === 'extra' && isset($path[1]))
		{
			return (string) ($extraLower[strtolower($path[1])] ?? '');
		}

		/* 3) Direct field or nested data: %TYPE.FIELD% or %TYPE.data.FIELD% */
		if (isset($dataLower[$key]))
		{
			return (string) $dataLower[$key];
		}
		if (isset($dataFieldLower[$key]))
		{
			return (string) $dataFieldLower[$key];
		}

		return '';
	}

	// Deprecated method but used in autoblogging
	public static function replacePatterns($template, array $modules_data, $keyword, $module_keywords = array(), $main_product = null, $extra_params = array())
	{
		if (!$template)
		{
			return '';
		}

		$template = TextHelper::spin($template);

		$replace = array();

		if ($extra_params)
		{
			foreach ($extra_params as $key => $value)
			{
				$replace['%' . $key . '%'] = $value;
			}

			$template = str_ireplace(array_keys($replace), array_values($replace), $template);
			$replace = array();
		}

		if (!preg_match_all('/%[a-zA-Z0-9_\.\,\(\)]+%/', $template, $matches))
		{
			return $template;
		}

		foreach ($matches[0] as $pattern)
		{
			$pattern_parts = explode('.', $pattern);

			// random
			if (stristr($pattern, '%RANDOM'))
			{
				preg_match('/%RANDOM\((\d+),(\d+)\)%/', $pattern, $rmatches);
				if ($rmatches)
				{
					$replace[$pattern] = rand((int) $rmatches[1], (int) $rmatches[2]);
				}
				else
				{
					$replace[$pattern] = rand(0, 9999999);
				}
				continue;
			}

			// keyword
			if (stristr($pattern, '%KEYWORD%'))
			{
				$replace[$pattern] = $keyword;
				continue;
			}

			// module keyword
			if (stristr($pattern, '%KEYWORD.'))
			{
				$module_id = rtrim($pattern_parts[1], '%');
				$module_id = str_replace(' ', '', $module_id); // name -> id
				if (isset($module_keywords[$module_id]))
				{
					$replace[$pattern] = $module_keywords[$module_id];
				}
				else
				{
					$replace[$pattern] = '';
				}
				continue;
			}

			// main product
			if (stristr($pattern, '%PRODUCT.'))
			{
				if (!$main_product)
				{
					$replace[$pattern] = '';
					continue;
				}

				if (count($pattern_parts) == 3 && strtoupper($pattern_parts[1]) == 'ATTRIBUTE' && isset($pattern_parts[2]))
				{
					$attribute_name = rtrim($pattern_parts[2], '%');
					$attribute_name = \sanitize_text_field($attribute_name);

					foreach ($main_product['features'] as $feature)
					{
						if ($feature['name'] == $attribute_name)
						{
							$replace[$pattern] = $feature['value'];
							break;
						}
					}

					continue;
				}

				$extra = false;
				if (strstr($pattern, '.extra.'))
				{
					$tpattern = str_replace('.extra.', '.', $pattern);
					$extra = true;
				}
				else
					$tpattern = $pattern;

				$pattern_parts = explode('.', $tpattern);
				$var_name = $pattern_parts[1];
				$var_name = rtrim($var_name, '%');

				if (!$extra && isset($main_product[$var_name]))
					$replace[$pattern] = $main_product[$var_name];
				elseif ($extra && isset($main_product['extra'][$var_name]))
					$replace[$pattern] = $main_product['extra'][$var_name];
				elseif ($extra && isset($main_product['extra']['data'][$var_name]))
					$replace[$pattern] = $main_product['extra']['data'][$var_name];
			}

			// module data
			if (!stristr($pattern, '%PRODUCT.'))
			{
				$extra = false;
				if (strstr($pattern, '.extra.'))
				{
					$tpattern = str_replace('.extra.', '.', $pattern);
					$extra = true;
				}
				else
					$tpattern = $pattern;

				$pattern_parts = explode('.', $tpattern);

				if (count($pattern_parts) == 3 && is_numeric($pattern_parts[1]))
				{
					$index = (int) $pattern_parts[1]; // Amazon.0.title
					$var_name = $pattern_parts[2];
				}
				elseif (count($pattern_parts) == 2)
				{
					$index = 0; // Amazon.title
					$var_name = $pattern_parts[1];
				}
				else
				{
					$replace[$pattern] = '';
					continue;
				}
				$module_id = ltrim($pattern_parts[0], '%');
				$var_name = rtrim($var_name, '%');

				if (array_key_exists($module_id, $modules_data) && isset($modules_data[$module_id][$index]))
				{
					if (!$extra && property_exists($modules_data[$module_id][$index], $var_name))
						$replace[$pattern] = $modules_data[$module_id][$index]->$var_name;
					elseif ($extra && property_exists($modules_data[$module_id][$index]->extra, $var_name))
						$replace[$pattern] = $modules_data[$module_id][$index]->extra->$var_name;
					elseif ($extra && isset($modules_data[$module_id][$index]->extra->data[$var_name]))
						$replace[$pattern] = $modules_data[$module_id][$index]->extra->data[$var_name];
				}
			}

			if (!isset($replace[$pattern]))
				$replace[$pattern] = '';

			if (!is_scalar($replace[$pattern]))
				$replace[$pattern] = '';

			if ($replace[$pattern] === null)
				$replace[$pattern] = '';
		}

		$res = str_ireplace(array_keys($replace), array_values($replace), $template);

		$res = trim($res);
		return $res;
	}
}

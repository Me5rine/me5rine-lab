<?php

namespace ContentEgg\application\helpers;



defined('\ABSPATH') || exit;

/**
 * PostHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */
class PostHelper
{
	/**
	 * Create (or find) a category
	 */
	public static function createCategory($category): int
	{
		if (! is_array($category))
		{
			$category = [(string) $category];
		}

		$category = array_filter(
			array_map('sanitize_text_field', $category),
			static fn($v) => $v !== ''
		);

		if (empty($category))
		{
			return 0;
		}

		return self::createNestedCategories($category);
	}

	public static function createNestedCategories(array $categoryPath): int
	{
		if (empty($categoryPath))
		{
			return 0;
		}

		if (! function_exists('wp_create_category'))
		{
			require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
		}

		$parent  = 0;
		$term_id = 0;

		foreach ($categoryPath as $part)
		{
			$part = sanitize_text_field($part);

			$existing = term_exists($part, 'category', $parent);

			if ($existing)
			{
				$term_id = is_array($existing) ? (int) $existing['term_id'] : (int) $existing;
			}
			else
			{
				$term_id = wp_create_category($part, $parent);
				if (is_wp_error($term_id) || ! $term_id)
				{
					return 0;
				}
			}

			$parent = $term_id;
		}

		return $term_id;
	}

	public static function getPostIdByUniqueId($unique_id, $post_types = ['post', 'product']): int
	{
		$unique_id  = (string) $unique_id;
		$post_types = (array) $post_types;

		// except 'trash'
		$statuses = ['publish', 'pending', 'draft', 'future', 'private'];

		$posts = get_posts([
			'post_type'      => $post_types,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_status'    => $statuses,
			'meta_query'     => [
				[
					'key'     => '_cegg_import_unique_id',
					'value'   => $unique_id,
					'compare' => '=',
				],
			],
		]);

		return ! empty($posts) ? (int) $posts[0] : 0;
	}

	public static function postExistsByUniqueId(string $unique_id, $post_types = ['post', 'product']): bool
	{
		return self::getPostIdByUniqueId($unique_id, $post_types) > 0;
	}
}

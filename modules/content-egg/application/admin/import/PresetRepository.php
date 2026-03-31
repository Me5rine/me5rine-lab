<?php

namespace ContentEgg\application\admin\import;

use ContentEgg\application\Plugin;;

defined('ABSPATH') || exit;

/**
 * PresetRepository
 *
 * Provides cached access to Preset Post Type data so the same information
 * is not pulled from the database multiple times during a single request.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class PresetRepository
{
    /**
     * Meta‑key that stores the preset payload.
     */
    public const META_KEY = '_cegg_preset';

    /** @var array<int,array>|null  In‑request cache of presets indexed by post ID. */
    private static ?array $cachePresets = null;

    /** @var array<int,array{id:int,title:string,type:string}>|null In‑request cache of the presets list. */
    private static ?array $cacheList = null;

    /** @var int|null Cached default preset ID. */
    private static ?int $cacheDefaultId = null;

    /**
     * Register the preset CPT + form and attach cache‑invalidation hooks.
     */
    public static function init(): void
    {
        PresetPostType::register();
        PresetForm::register();

        // Bust the cache whenever a preset is created/updated/deleted.
        add_action('save_post_' . PresetPostType::POST_TYPE, [self::class, 'clearCache']);
        add_action('deleted_post', [self::class, 'onDeletedPost']);
    }

    /**
     * Flushes all in‑request caches.
     */
    public static function clearCache(): void
    {
        self::$cachePresets   = null;
        self::$cacheList      = null;
        self::$cacheDefaultId = null;
    }

    /**
     * Invalidates caches after a post is deleted (only if it is a preset).
     */
    public static function onDeletedPost(int $postId): void
    {
        if (get_post_type($postId) === PresetPostType::POST_TYPE)
        {
            self::clearCache();
        }
    }

    /**
     * Return all presets as [ id => metaArray ].
     */
    public static function all(): array
    {
        if (self::$cachePresets !== null)
        {
            return self::$cachePresets;
        }

        $ids = get_posts([
            'post_type'      => PresetPostType::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $presets = [];
        foreach ($ids as $pid)
        {
            $meta = get_post_meta($pid, self::META_KEY, true) ?: [];
            $name = get_the_title($pid) ?: __('Untitled Preset', 'content-egg');
            $presets[$pid] = array_merge(['name' => $name], $meta);
        }

        return self::$cachePresets = $presets;
    }

    /**
     * Fetch a single preset by ID.
     */
    public static function get(int $id): ?array
    {
        // Serve from cache if available.
        if (self::$cachePresets !== null && array_key_exists($id, self::$cachePresets))
        {
            return self::$cachePresets[$id];
        }

        $meta = get_post_meta($id, self::META_KEY, true) ?: null;
        $meta['title'] = get_the_title($id);

        // Warm the cache for subsequent calls inside the same request.
        if (self::$cachePresets !== null)
        {
            self::$cachePresets[$id] = $meta ?? [];
        }

        return $meta;
    }

    public static function getById(int $id): ?array
    {
        return self::get($id);
    }

    /**
     * Clears the “use_default” flag on all presets except the given one.
     *
     * @param int $currentId The ID of the preset to remain the default.
     */
    public static function clearDefaultPreset(int $currentId): void
    {
        $ids = get_posts([
            'post_type'      => PresetPostType::POST_TYPE,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ]);

        foreach ($ids as $pid)
        {
            if ($pid === $currentId)
            {
                continue;
            }

            $meta = get_post_meta($pid, self::META_KEY, true);
            if (empty($meta['use_default']))
            {
                continue; // Already not default.
            }

            $meta['use_default'] = false;
            update_post_meta($pid, self::META_KEY, $meta);
        }

        self::clearCache();
    }

    /**
     * Return all presets as an array of [ 'id' => …, 'title' => …, 'type' => … ].
     */
    public static function getList(): array
    {
        if (self::$cacheList !== null)
        {
            return self::$cacheList;
        }

        $presets = self::all();
        if (!$presets)
        {
            return self::$cacheList = [];
        }

        $posts = get_posts([
            'post__in'       => array_keys($presets),
            'orderby'        => 'post__in',
            'posts_per_page' => -1,
            'post_type'      => PresetPostType::POST_TYPE,
            'post_status'    => 'publish',
        ]);

        $list = [];
        foreach ($posts as $post)
        {
            $id   = (int) $post->ID;
            $meta = $presets[$id];

            $list[] = [
                'id'    => $id,
                'title' => $post->post_title ?: __('Untitled Preset', 'content-egg'),
                'type'  => $meta['post_type'] ?? 'post',
            ];
        }

        return self::$cacheList = $list;
    }

    /**
     * Return the ID of the preset marked as default, or null if none.
     */
    public static function getDefaultPresetId(): ?int
    {
        if (self::$cacheDefaultId !== null)
        {
            return self::$cacheDefaultId;
        }

        foreach (self::all() as $id => $meta)
        {
            if (!empty($meta['use_default']))
            {
                return self::$cacheDefaultId = $id;
            }
        }

        return self::$cacheDefaultId = null;
    }

    /**
     * Alias of getDefaultPresetId()
     *
     * @return int|null Default preset ID or null when none set.
     */
    public static function getDefaultId(): ?int
    {
        return self::getDefaultPresetId();
    }

    public static function maybeInstallBuiltInPresets(): void
    {
        // Bail out if the built‑in presets are already installed
        $existing = get_posts([
            'post_type'      => PresetPostType::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);

        if (! empty($existing))
        {
            return;
        }

        self::installBuiltInPresets();
    }

    /**
     * Install the plugin’s built-in presets.
     */
    public static function installBuiltInPresets(): void
    {
        PresetPostType::register();

        foreach (self::getBuiltInPresets() as $preset)
        {
            // 1) Clean up the title
            $title = sanitize_text_field($preset['title']);

            // 2) Author fallback
            $author_id = get_current_user_id() ?: (
                ($admin = get_user_by('login', 'admin'))
                ? (int) $admin->ID
                : 0
            );

            // 3) Build & clean meta
            $meta       = (array) $preset['data'];
            $meta['author_id']       = $author_id;
            $meta['default_cat']     = absint(get_option('default_category', 1));
            $meta['default_woo_cat'] = absint(get_option('default_product_cat', 1));
            $clean_meta = PresetForm::build_clean_array($meta);

            // 4) Prepare post args
            $post_arr = [
                'post_type'   => PresetPostType::POST_TYPE,
                'post_title'  => $title,
                'post_name'   => sanitize_title($title),
                'post_status' => 'publish',
                'post_author' => $author_id,
                'meta_input'  => [
                    PresetRepository::META_KEY => $clean_meta,
                ],
            ];

            // 5) Insert
            wp_insert_post($post_arr, true);
        }
    }

    public static function getBuiltInPresets(): array
    {
        $presets = self::getBuiltInPresetsData();
        $isFree  = Plugin::isFree();

        if ($isFree)
        {
            foreach ($presets as &$preset)
            {
                if (!empty($preset['pro']))
                {
                    $preset['title'] .= ' /Pro';
                }
            }
            unset($preset);
        }

        return $presets;
    }

    public static function getBuiltInPresetsData(): array
    {
        return [
            [
                'title' => __('Simple post', 'content-egg'),
                'pro' => false,
                'data'  => [
                    'post_type'          => 'post',
                    'product_type'       => 'external',
                    'dynamic_categories' => 'none',
                    'post_status'        => 'publish',
                    'tags'               => '',
                    'price_comparison'   => 'disabled',
                    'title_tpl'          => '%PRODUCT.title%',
                    'body_tpl'           => <<<'HTML'
[content-egg-block template=item_simple visible=description groups=ProductImport]
HTML,
                    'woo_short_desc_tpl' => '',
                    'ai_product_content' => [
                        'generate_short_title',
                        'generate_short_description',
                        'generate_subtitle',
                        'generate_rating',
                        'generate_badge',
                        'generate_badge_icon',
                    ],
                    'ai_title'           => '',
                    'ai_content'         => '',
                    'ai_short_desc'      => '',
                    'prompt1'            => '',
                    'prompt2'            => '',
                    'prompt3'            => '',
                    'avoid_duplicates'   => true,
                    'use_default'        => true,
                ],
            ],

            [
                'title' => __('Simple Woo Listing', 'content-egg'),
                'pro' => false,
                'data'  => [
                    'post_type'          => 'product',
                    'product_type'       => 'external',
                    'dynamic_categories' => 'none',
                    'post_status'        => 'publish',
                    'tags'               => '',
                    'price_comparison'   => 'disabled',
                    'title_tpl'          => '%PRODUCT.title%',
                    'body_tpl'           => '',
                    'woo_short_desc_tpl' => '',
                    'ai_product_content' => ['generate_attributes'],
                    'ai_title'           => '',
                    'ai_content'         => '',
                    'ai_short_desc'      => '',
                    'prompt1'            => '',
                    'prompt2'            => '',
                    'prompt3'            => '',
                    'avoid_duplicates'   => true,
                    'use_default'        => false,
                ],
            ],
            [
                'title' => __('Simple Price Comparison', 'content-egg'),
                'pro' => false,
                'data'  => [
                    'post_type'          => 'product',
                    'product_type'       => 'external',
                    'dynamic_categories' => 'none',
                    'post_status'        => 'publish',
                    'tags'               => '',
                    'price_comparison'   => 'enabled',
                    'title_tpl'          => '%PRODUCT.title%',
                    'body_tpl'           => <<<'HTML'
%PRODUCT.description%

[content-egg-block template=price_alert]

[content-egg-block template=price_history]

[content-egg-block template=offers_logo_btn]

[content-egg-block template=price_statistics]
HTML,
                    'woo_short_desc_tpl' => <<<'HTML'
%AI.short_desc%

[content-egg-block template=price_comparison limit=5]
HTML,
                    'ai_product_content' => [
                        'generate_short_title',
                        'generate_short_description',
                    ],
                    'ai_title'           => '',
                    'ai_content'         => '',
                    'ai_short_desc'      => '',
                    'prompt1'            => '',
                    'prompt2'            => '',
                    'prompt3'            => '',
                    'avoid_duplicates'   => true,
                    'use_default'        => false,
                ],
            ],

            [
                'title' => __('Question-article, AI-Powered', 'content-egg'),
                'pro' => true,
                'data'  => [
                    'post_type'          => 'post',
                    'product_type'       => 'external',
                    'dynamic_categories' => 'none',
                    'post_status'        => 'publish',
                    'tags'               => '',
                    'price_comparison'   => 'disabled',
                    'title_tpl'          => '%AI.title%',
                    'body_tpl'           => <<<'HTML'
[content-egg-block template=item_simple visible=description groups=ProductImport]

%AI.content%

[content-egg-block template=offers_list groups=ProductImport]
HTML,
                    'woo_short_desc_tpl' => '',
                    'ai_product_content' => [
                        'generate_short_title',
                        'generate_short_description',
                        'generate_subtitle',
                        'generate_rating',
                        'generate_badge',
                        'generate_badge_icon',
                    ],
                    'ai_title'           => 'generate_question_title',
                    'ai_content'         => 'write_article',
                    'ai_short_desc'      => '',
                    'prompt1'            => '',
                    'prompt2'            => '',
                    'prompt3'            => '',
                    'avoid_duplicates'   => true,
                    'use_default'        => false,
                ],
            ],
            [
                'title' => __('Product Review, AI-Powered', 'content-egg'),
                'pro' => true,
                'data'  => [
                    'post_type'          => 'post',
                    'product_type'       => 'external',
                    'dynamic_categories' => 'none',
                    'post_status'        => 'publish',
                    'author_id'          => get_current_user_id(),
                    'tags'               => '',
                    'price_comparison'   => 'enabled',
                    'title_tpl'          => '%AI.title%',
                    'body_tpl'           => <<<'HTML'
[content-egg-block template=review_box groups=ProductImport]

%AI.content%

[content-egg-block template=product_images_row cols=2 products=%PRODUCT.unique_id%]

[content-egg-block template=offers_logo groups=ProductImport]
HTML,
                    'woo_short_desc_tpl' => '',
                    'ai_product_content' => [
                        'generate_short_title',
                        'generate_short_description',
                        'generate_subtitle',
                        'generate_rating',
                        'generate_badge',
                        'generate_badge_icon',
                    ],
                    'ai_title'           => 'generate_review_title',
                    'ai_content'         => 'write_review',
                    'ai_short_desc'      => '',
                    'prompt1'            => '',
                    'prompt2'            => '',
                    'prompt3'            => '',
                    'avoid_duplicates'   => true,
                    'use_default'        => false,
                ],
            ],
            [
                'title' => __('Woo Listing, AI-Powered', 'content-egg'),
                'pro' => true,
                'data'  => [
                    'post_type'          => 'product',
                    'product_type'       => 'external',
                    'dynamic_categories' => 'none',
                    'post_status'        => 'publish',
                    'tags'               => '',
                    'price_comparison'   => 'disabled',
                    'title_tpl'          => '%AI.title%',
                    'body_tpl'           => '%AI.content%',
                    'woo_short_desc_tpl' => '%AI.short_desc%',
                    'ai_product_content' => ['generate_attributes'],
                    'ai_title'           => 'improve',
                    'ai_content'         => 'improve',
                    'ai_short_desc'      => 'bullet_points',
                    'prompt1'            => '',
                    'prompt2'            => '',
                    'prompt3'            => '',
                    'avoid_duplicates'   => true,
                    'use_default'        => false,
                ],
            ],
            [
                'title' => __('Price Comparison, AI-Powered', 'content-egg'),
                'pro' => true,
                'data'  => [
                    'post_type'          => 'product',
                    'product_type'       => 'external',
                    'dynamic_categories' => 'none',
                    'post_status'        => 'publish',
                    'tags'               => '',
                    'price_comparison'   => 'enabled',
                    'title_tpl'          => '%AI.title%',
                    'body_tpl'           => <<<'HTML'
%AI.content%

[content-egg-block template=price_alert]

[content-egg-block template=price_history]

[content-egg-block template=offers_logo_btn]

[content-egg-block template=price_statistics]
HTML,
                    'woo_short_desc_tpl' => <<<'HTML'
%AI.short_desc%

[content-egg-block template=price_comparison limit=5]
HTML,
                    'ai_product_content' => ['generate_short_title'],
                    'ai_title'           => 'prompt1',
                    'ai_content'         => 'prompt2',
                    'ai_short_desc'      => 'generate_short_description',
                    'prompt1'            => <<<'PROMPT'
You’re building a concise, SEO‑friendly product title for a price‑comparison website.
- If available, include the brand, model, and a standout feature.
- Keep it under 65 characters.
- Make it easy for users to scan in a grid of similar items.

Source product data:
• Title: '%PRODUCT.title%'
• Specifications: '%PRODUCT.specifications%'

Rewrite to produce the final title.
PROMPT,
                    'prompt2'            => <<<'PROMPT'
You’re crafting a product description for a price‑comparison platform.
- Don’t include an H1 title.
- Start with a 1‑sentence overview that highlights the biggest benefit.
- Follow with 3‑5 bullet points listing key specs (e.g., dimensions, capacity, speed).
- Optimize for SEO by naturally including keywords.

Source product data:
• Title: '%PRODUCT.title%'
• Description: '%PRODUCT.description%'
• Specifications: '%PRODUCT.specifications%'
PROMPT,
                    'prompt3'            => '',
                    'avoid_duplicates'   => true,
                    'use_default'        => false,
                ],
            ],
        ];
    }
}

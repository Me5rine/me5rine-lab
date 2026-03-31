<?php

defined('ABSPATH') || exit;

$settings = is_array($settings ?? null) ? $settings : [];

// Helper for convenience inside markup.
$def  = static function ($key, $fallback = '') use ($settings)
{
    return $settings[$key] ?? $fallback;
};

// -------------------------------------------------------------------------
// Determine post types the plugin supports and pick the current selection.
// -------------------------------------------------------------------------
$supported_types   = \ContentEgg\application\admin\GeneralConfig::getInstance()->option('post_types');
$all_public_types  = get_post_types(['public' => true], 'names');
$post_types        = array_intersect($supported_types, $all_public_types);
$selected_post_type = $def('post_type', reset($post_types));

?>

<div class="wrap cegg5-container">
    <h1 class="h3">
        <?php echo esc_html__('Product Prefill Tool', 'content-egg'); ?>
        <a href="<?php echo esc_url('https://ce-docs.keywordrush.com/set-up-products/fill-tool'); ?>"
            target="_blank" rel="noopener noreferrer"
            class="link-secondary text-decoration-none small ms-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94" />
            </svg>
        </a>
    </h1>
    <p class="description text-muted">
        <?php echo esc_html__('Use this tool to automatically add products to your existing posts.', 'content-egg'); ?>
        <?php echo esc_html__('In the first step, you’ll filter posts for prefill. In the next step, you’ll configure your prefill settings.', 'content-egg'); ?>
    </p>

    <h2 class="h5 mt-4"><?php echo esc_html__('Select Posts to Prefill', 'content-egg'); ?></h2>

    <form method="get" action="">
        <?php wp_nonce_field('prefill_config_nonce'); ?>
        <input type="hidden" name="page" value="content-egg-product-prefill">
        <input type="hidden" name="action" value="prefill_config">

        <table class="form-table" role="presentation">
            <!-- Post Type -------------------------------------------------- -->
            <tr>
                <th scope="row"><label for="post_type"><?php esc_html_e('Post Type', 'content-egg'); ?></label></th>
                <td>
                    <select name="_post_type" id="post_type" class="form-select w-auto">
                        <?php foreach ($post_types as $post_type) : ?>
                            <option value="<?php echo esc_attr($post_type); ?>" <?php selected($post_type, $selected_post_type); ?>>
                                <?php echo esc_html(get_post_type_object($post_type)->labels->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Post Status ---------------------------------------------- -->
            <?php
            $post_statuses = [
                'publish' => __('Published', 'content-egg'),
                'draft'   => __('Draft', 'content-egg'),
                'pending' => __('Pending', 'content-egg'),
                'future'  => __('Scheduled', 'content-egg'),
                'private' => __('Private', 'content-egg'),
            ];
            $current_status = $def('post_status', 'publish');
            ?>
            <tr>
                <th scope="row"><label for="post_status"><?php esc_html_e('Post Status', 'content-egg'); ?></label></th>
                <td>
                    <select name="post_status" id="post_status" class="form-select w-auto">
                        <?php foreach ($post_statuses as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $current_status); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Category (standard posts) -------------------------------- -->
            <tr id="category-row-post">
                <th scope="row"><label for="category_post"><?php esc_html_e('Category', 'content-egg'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories([
                        'show_option_all' => __('All Categories', 'content-egg'),
                        'name'            => 'category_post',
                        'id'              => 'category_post',
                        'orderby'         => 'name',
                        'hide_empty'      => false,
                        'taxonomy'        => 'category',
                        'class'           => 'form-select w-auto',
                        'selected'        => (int) $def('category_post', 0),
                    ]);
                    ?>
                </td>
            </tr>

            <!-- Category (Woo products) ---------------------------------- -->
            <tr id="category-row-product">
                <th scope="row"><label for="category_product"><?php esc_html_e('Product Category', 'content-egg'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories([
                        'show_option_all' => __('All Product Categories', 'content-egg'),
                        'name'            => 'category_product',
                        'id'              => 'category_product',
                        'orderby'         => 'name',
                        'hide_empty'      => true,
                        'taxonomy'        => 'product_cat',
                        'class'           => 'form-select w-auto',
                        'selected'        => (int) $def('category_product', 0),
                    ]);
                    ?>
                </td>
            </tr>

            <!-- Author ---------------------------------------------------- -->
            <tr>
                <th scope="row"><label for="author"><?php esc_html_e('Author', 'content-egg'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_users([
                        'name'            => 'author',
                        'show_option_all' => __('All Authors', 'content-egg'),
                        'capability'      => 'edit_posts',
                        'class'           => 'form-select w-auto',
                        'selected'        => (int) $def('author', 0),
                    ]);
                    ?>
                </td>
            </tr>

            <!-- Publish Date Range --------------------------------------- -->
            <tr>
                <th scope="row"><label for="date_from"><?php esc_html_e('Publish Date Range', 'content-egg'); ?></label></th>
                <td>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($def('date_from')); ?>" class="form-control w-auto d-inline-block" />
                    &mdash;
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($def('date_to')); ?>" class="form-control w-auto d-inline-block" />
                </td>
            </tr>

            <!-- CE data filter ------------------------------------------- -->
            <?php $current_filter = $def('ce_filter', 'all'); ?>
            <tr>
                <th scope="row"><label for="ce_filter"><?php esc_html_e('Filter by Existing Product Data', 'content-egg'); ?></label></th>
                <td>
                    <select name="ce_filter" id="ce_filter" class="form-select w-auto">
                        <option value="all" <?php selected('all', $current_filter); ?>><?php esc_html_e('All Posts', 'content-egg'); ?></option>
                        <option value="with_data" <?php selected('with_data', $current_filter); ?>><?php esc_html_e('Posts With Content Egg Data', 'content-egg'); ?></option>
                        <option value="without_data" <?php selected('without_data', $current_filter); ?>><?php esc_html_e('Posts Without Content Egg Data', 'content-egg'); ?></option>
                    </select>
                </td>
            </tr>

            <!-- Keywords ------------------------------------------------- -->
            <tr>
                <th scope="row"><label for="keywords"><?php esc_html_e('Search by Keyword', 'content-egg'); ?></label></th>
                <td>
                    <input type="text" name="keywords" id="keywords" value="<?php echo esc_attr($def('keywords')); ?>" placeholder="<?php esc_attr_e('e.g. laptop, travel, fitness', 'content-egg'); ?>" class="form-control" />
                </td>
            </tr>

            <!-- Include Post IDs ---------------------------------------- -->
            <tr>
                <th scope="row"><label for="post__in"><?php esc_html_e('Include Post IDs', 'content-egg'); ?></label></th>
                <td>
                    <input type="text" name="post__in" id="post__in" value="<?php echo esc_attr(implode(',', (array) $def('post__in', []))); ?>" placeholder="1,2,3" class="form-control" />
                </td>
            </tr>

            <!-- Exclude Post IDs ---------------------------------------- -->
            <tr>
                <th scope="row"><label for="post__not_in"><?php esc_html_e('Exclude Post IDs', 'content-egg'); ?></label></th>
                <td>
                    <input type="text" name="post__not_in" id="post__not_in" value="<?php echo esc_attr(implode(',', (array) $def('post__not_in', []))); ?>" placeholder="4,5,6" class="form-control" />
                </td>
            </tr>

            <!-- Post Limit ---------------------------------------------- -->
            <tr>
                <th scope="row"><label for="post_limit"><?php esc_html_e('Post Limit', 'content-egg'); ?></label></th>
                <td>
                    <input type="number" name="post_limit" id="post_limit" value="<?php echo esc_attr($def('post_limit', 0)); ?>" class="form-control w-auto" />
                </td>
            </tr>

            <!-- Offset -------------------------------------------------- -->
            <tr>
                <th scope="row"><label for="offset"><?php esc_html_e('Offset', 'content-egg'); ?></label></th>
                <td>
                    <input type="number" name="offset" id="offset" value="<?php echo esc_attr($def('offset', 0)); ?>" class="form-control w-auto" />
                </td>
            </tr>
        </table>

        <?php submit_button(__('Filter Posts', 'content-egg')); ?>
    </form>
</div>

<!-- JavaScript to toggle the correct category row on load & change -->
<script>
    "use strict";
    document.addEventListener("DOMContentLoaded", () => {
        const postTypeSelect = document.getElementById("post_type");
        const categoryRowPost = document.getElementById("category-row-post");
        const categoryRowProduct = document.getElementById("category-row-product");

        if (!postTypeSelect || !categoryRowPost || !categoryRowProduct) {
            return;
        }

        const toggleCategoryRows = () => {
            const selected = postTypeSelect.value;
            categoryRowPost.style.display = selected === "post" ? "" : "none";
            categoryRowProduct.style.display = selected === "product" ? "" : "none";
        };

        toggleCategoryRows();
        postTypeSelect.addEventListener("change", toggleCategoryRows);
    });
</script>
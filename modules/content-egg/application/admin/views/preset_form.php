<?php

use ContentEgg\application\admin\import\ImportPostPromptPro;
use ContentEgg\application\admin\import\ImportPostPromptFree;
use ContentEgg\application\helpers\AdminHelper;

$sys_ai_warning = \ContentEgg\application\helpers\AdminHelper::getSysAiWarning();
$ai_warning = \ContentEgg\application\helpers\AdminHelper::getAiWarning();

$provider = ($is_pro) ? ImportPostPromptPro::class : ImportPostPromptFree::class;

?>

<h2 class="mb-2">
    <?php
    echo $is_edit
        ? sprintf(esc_html__('Edit Preset: %s', 'content-egg'), esc_html($post->post_title))
        : esc_html__('Add New Preset', 'content-egg'); ?>
</h2>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('cegg_save_preset', 'cegg_preset_nonce'); ?>
    <input type="hidden" name="action" value="cegg_save_preset">
    <input type="hidden" name="preset_id" value="<?php echo esc_attr($id); ?>">

    <table class="form-table" role="presentation">
        <tbody>
            <!-- Preset Name -->
            <tr>
                <th><label for="preset_name"><?php esc_html_e('Preset Name', 'content-egg'); ?></label></th>
                <td>
                    <input
                        type="text"
                        id="preset_name"
                        name="preset_name"
                        class="regular-text"
                        value="<?php echo $is_edit ? esc_attr($post->post_title) : ''; ?>"
                        required>
                </td>
            </tr>

            <!-- Post Type -->
            <tr>
                <th><label for="cegg_preset_post_type"><?php esc_html_e('Post Type', 'content-egg'); ?></label></th>
                <td>
                    <select id="cegg_preset_post_type" name="cegg_preset[post_type]">
                        <option value="post" <?php selected($data['post_type'], 'post'); ?>><?php esc_html_e('Post', 'content-egg'); ?></option>
                        <option value="product" <?php selected($data['post_type'], 'product'); ?>><?php esc_html_e('Woo Product', 'content-egg'); ?></option>
                    </select>
                </td>
            </tr>

            <!-- Woo Options -->
            <tr class="cegg-product-only">
                <th><label for="cegg_preset_product_type"><?php esc_html_e('WooCommerce Product Type', 'content-egg'); ?></label></th>
                <td>
                    <select id="cegg_preset_product_type" name="cegg_preset[product_type]">
                        <option value="external" <?php selected($data['product_type'], 'external'); ?>><?php esc_html_e('External/Affiliate Product', 'content-egg'); ?></option>
                        <option value="simple" <?php selected($data['product_type'], 'simple'); ?>><?php esc_html_e('Simple Product', 'content-egg'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('External products can’t be added to the cart. Customers will use your affiliate link to buy on the merchant’s site.', 'content-egg'); ?></p>
                    <p class="description">
                        <?php
                        esc_html_e(
                            'To automatically synchronize product images and prices with WooCommerce, go to Content Egg → Settings → WooCommerce. Ensure that the required modules are enabled under the "Automatic Synchronization Modules" section.',
                            'content-egg'
                        ); ?>
                    </p>

                </td>
            </tr>

            <tr class="cegg-product-only">
                <th><label for="cegg_preset_default_woo_cat"><?php esc_html_e('Default Woo Category', 'content-egg'); ?></label></th>
                <td>
                    <?php $woo_cats = \ContentEgg\application\helpers\WooHelper::getWooCategoryList(); ?>
                    <select id="cegg_preset_default_woo_cat" name="cegg_preset[default_woo_cat]">
                        <?php foreach ($woo_cats as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($data['default_woo_cat'] ?? '', $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>

                </td>
            </tr>

            <!-- Post Options -->
            <tr class="cegg-post-only">
                <th><label for="cegg_preset_default_cat"><?php esc_html_e('Default Category', 'content-egg'); ?></label></th>
                <td>
                    <?php $post_cats = \ContentEgg\application\helpers\AdminHelper::getPostCategoryList(); ?>
                    <select id="cegg_preset_default_cat" name="cegg_preset[default_cat]">
                        <?php foreach ($post_cats as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($data['default_cat'] ?? '', $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Dynamic Categories -->
            <tr>
                <th scope="row">
                    <label for="cegg_preset_dynamic_categories">
                        <?php esc_html_e('Dynamic Categories', 'content-egg'); ?>
                    </label>
                </th>
                <td>
                    <select
                        id="cegg_preset_dynamic_categories"
                        name="cegg_preset[dynamic_categories]">
                        <option value="none" <?php selected($data['dynamic_categories'] ?? '', 'none'); ?>>
                            <?php esc_html_e('Do not create', 'content-egg'); ?>
                        </option>
                        <option value="create" <?php selected($data['dynamic_categories'] ?? '', 'create'); ?>>
                            <?php esc_html_e('Create category', 'content-egg'); ?>
                        </option>
                        <option value="create_nested" <?php selected($data['dynamic_categories'] ?? '', 'create_nested'); ?>>
                            <?php esc_html_e('Create nested categories', 'content-egg'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php
                        esc_html_e(
                            'Automatically create categories based on the product’s category data.',
                            'content-egg'
                        ); ?>
                    </p>
                </td>
            </tr>

            <!-- Post Status -->
            <tr>
                <th><label for="cegg_preset_post_status"><?php esc_html_e('Post Status', 'content-egg'); ?></label></th>
                <td>
                    <select id="cegg_preset_post_status" name="cegg_preset[post_status]">
                        <option value="publish" <?php selected($data['post_status'], 'publish'); ?>><?php esc_html_e('Published', 'content-egg'); ?></option>
                        <option value="pending" <?php selected($data['post_status'], 'pending'); ?>><?php esc_html_e('Pending Review', 'content-egg'); ?></option>
                        <option value="draft" <?php selected($data['post_status'], 'draft'); ?>><?php esc_html_e('Draft', 'content-egg'); ?></option>
                    </select>
                </td>
            </tr>

            <!-- Author -->
            <?php if (current_user_can('manage_options')) : ?>
                <!-- Author -->
                <tr>
                    <th><label for="cegg_preset_author_id"><?php esc_html_e('Author', 'content-egg'); ?></label></th>
                    <td>
                        <?php
                        wp_dropdown_users([
                            'name'             => 'cegg_preset[author_id]',
                            'id'               => 'cegg_preset_author_id',
                            'selected'         => $data['author_id'] ?? get_current_user_id(),
                            'capability'       => 'edit_posts',
                        ]);
                        ?>
                    </td>
                </tr>
            <?php endif; ?>

            <!-- Title Template -->
            <tr>
                <th><label for="preset_title_tpl"><?php esc_html_e('Post Title Template', 'content-egg'); ?></label></th>
                <td>
                    <input
                        type="text"
                        id="preset_title_tpl"
                        name="cegg_preset[title_tpl]"
                        class="widefat"
                        value="<?php echo esc_attr($data['title_tpl'] ?? ''); ?>">
                    <?php \ContentEgg\application\helpers\AdminHelper::echoPlaceholderDescription(); ?>

                </td>
            </tr>

            <!-- Body Template -->
            <tr>
                <th><label for="cegg_preset_body_tpl"><?php esc_html_e('Post Body Template', 'content-egg'); ?></label></th>
                <td>
                    <?php
                    wp_enqueue_editor();

                    $editor_id = 'cegg_preset_body_tpl';
                    $content   = $data['body_tpl'] ?? '';

                    wp_editor(
                        $content,
                        $editor_id,
                        [
                            'textarea_name' => 'cegg_preset[body_tpl]',
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny'         => false,
                            'tinymce'       => true,
                            'quicktags'     => true,    // enable Text (HTML) tab
                        ]
                    );
                    ?>
                    <?php \ContentEgg\application\helpers\AdminHelper::echoPlaceholderDescription(); ?>
                    <p class="description"><?php esc_html_e('Use shortcodes to add product blocks to the post body.', 'content-egg'); ?></p>
                </td>
            </tr>

            <!-- Woo Short Description Template -->
            <tr class="cegg-product-only">
                <th><label for="cegg_preset_woo_short_desc_tpl"><?php esc_html_e('Woo Short Description Template', 'content-egg'); ?></label></th>
                <td>
                    <?php
                    wp_enqueue_editor();

                    $editor_id = 'cegg_preset_woo_short_desc_tpl';
                    $content   = $data['woo_short_desc_tpl'] ?? '';

                    wp_editor(
                        $content,
                        $editor_id,
                        [
                            'textarea_name' => 'cegg_preset[woo_short_desc_tpl]',
                            'textarea_rows' => 5,
                            'media_buttons' => true,
                            'teeny'         => false,
                            'tinymce'       => true,
                            'quicktags'     => true,
                        ]
                    );
                    ?>
                    <p class="description">
                        <?php esc_html_e('Use %AI.short_desc% and other placeholders.', 'content-egg'); ?>
                    </p>

                </td>
            </tr>

            <!-- Tags -->
            <tr>
                <th><label for="cegg_preset_tags"><?php esc_html_e('Tags', 'content-egg'); ?></label></th>
                <td>
                    <input
                        type="text"
                        id="cegg_preset_tags"
                        name="cegg_preset[tags]"
                        class="widefat"
                        value="<?php echo esc_attr($data['tags'] ?? ''); ?>"
                        placeholder="<?php esc_attr_e('Comma-separated tags', 'content-egg'); ?>">
                    <p class="description" style="margin-top: 0.8em">
                        <?php esc_html_e('Add tags to the post. You can use static text or placeholders.', 'content-egg'); ?>
                    </p>
                </td>
            </tr>

            <!-- Price comparison -->
            <tr>
                <th><label for="cegg_preset_price_comparison"><?php esc_html_e('Price comparison', 'content-egg'); ?></label></th>
                <td>
                    <select id="cegg_preset_price_comparison" name="cegg_preset[price_comparison]">
                        <option value="enabled" <?php selected($data['price_comparison'], 'enabled'); ?>><?php esc_html_e('Enabled', 'content-egg'); ?></option>
                        <option value="disabled" <?php selected($data['price_comparison'], 'disabled'); ?>><?php esc_html_e('Disabled', 'content-egg'); ?></option>
                    </select>
                    <p class="description">
                        <?php
                        esc_html_e(
                            'Enable this option to add the same product from multiple modules when supported.',
                            'content-egg'
                        ); ?>
                    </p>

                </td>
            </tr>

            <!-- AI-Powered Product Fields -->
            <tr>
                <th scope="row">
                    <?php esc_html_e('AI-Powered Product Fields', 'content-egg'); ?>
                </th>
                <td>
                    <?php
                    $checks = [
                        'generate_short_title'       => __('Generate Short Title', 'content-egg'),
                        'generate_short_description' => __('Generate Short Description', 'content-egg'),
                        'generate_subtitle'          => __('Generate Subtitle', 'content-egg'),
                        'generate_rating'            => __('Generate Rating', 'content-egg'),
                        'generate_badge'             => __('Generate Badge', 'content-egg'),
                        'generate_badge_icon'        => __('Generate Badge Icon', 'content-egg'),
                        'generate_attributes'        => __('Generate Attributes', 'content-egg'),
                        'generate_keyword'           => __('Generate Search Keyword', 'content-egg'),
                    ];
                    $selected = (array) ($data['ai_product_content'] ?? []);
                    foreach ($checks as $value => $label): ?>
                        <label for="cegg_ai_prod_<?php echo esc_attr($value); ?>">
                            <input
                                type="checkbox"
                                id="cegg_ai_prod_<?php echo esc_attr($value); ?>"
                                name="cegg_preset[ai_product_content][]"
                                value="<?php echo esc_attr($value); ?>"
                                <?php checked(in_array($value, $selected, true)); ?> />
                            <?php echo esc_html($label); ?>
                        </label><br />
                    <?php endforeach; ?>

                    <p class="description" style="margin-top:.5em;">
                        <?php esc_html_e(
                            'This will modify the product data stored by Content Egg. Enable these only if you plan to use Content Egg shortcodes to make your product data unique and beautifully formatted.',
                            'content-egg'
                        ); ?>
                    </p>

                    <?php echo wp_kses_post($sys_ai_warning); ?>
                </td>
            </tr>

            <!-- AI Generator: Post Title -->
            <tr>
                <th scope="row">
                    <label for="cegg_preset_ai_title">
                        <?php esc_html_e('AI-Powered Post Title', 'content-egg'); ?>
                        <?php if (!$is_pro) : ?>
                            <br><?php echo wp_kses_post(AdminHelper::getProFeatureWarning()); ?>
                        <?php endif; ?>
                    </label>
                </th>
                <td>
                    <select
                        id="cegg_preset_ai_title"
                        name="cegg_preset[ai_title]"
                        <?php disabled(! $is_pro); ?>>
                        <?php
                        $options = array_merge(
                            ['' => __('Disabled', 'content-egg')],
                            $provider::getTitleMethodOptions()
                        );
                        $sel = $data['ai_title'] ?? '';
                        foreach ($options as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($sel, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e(
                            'Use the %AI.title% placeholder in the Post Title Template to apply the AI-generated title.',
                            'content-egg'
                        ); ?>
                    </p>

                    <?php if ($is_pro) : ?>
                        <p class="cegg-pro-warning"><?php echo wp_kses_post($ai_warning); ?></p>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- AI Generator: Post Content -->
            <tr>
                <th scope="row">
                    <label for="cegg_preset_ai_content">
                        <?php esc_html_e('AI-Powered Post Content', 'content-egg'); ?>
                        <?php if (!$is_pro) : ?>
                            <br><?php echo wp_kses_post(AdminHelper::getProFeatureWarning()); ?>
                        <?php endif; ?>
                    </label>
                </th>
                <td>
                    <select
                        id="cegg_preset_ai_content"
                        name="cegg_preset[ai_content]"
                        <?php disabled(! $is_pro); ?>>
                        <?php
                        $options = array_merge(
                            ['' => __('Disabled', 'content-egg')],
                            $provider::getDescriptionMethodOptions()
                        );
                        $sel = $data['ai_content'] ?? '';
                        foreach ($options as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($sel, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e(
                            'Use the %AI.content% placeholder in the Post Body Template to insert AI-generated content.',
                            'content-egg'
                        ); ?>
                    </p>

                    <?php if ($is_pro) : ?>
                        <p class="cegg-pro-warning"><?php echo wp_kses_post($ai_warning); ?></p>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- AI Generator: Woo Short Description -->
            <tr class="cegg-product-only">
                <th scope="row">
                    <label for="cegg_preset_ai_short_desc">
                        <?php esc_html_e('AI-Powered Short Description', 'content-egg'); ?>
                        <?php if (!$is_pro) : ?>
                            <br><?php echo wp_kses_post(AdminHelper::getProFeatureWarning()); ?>
                        <?php endif; ?>
                    </label>
                </th>
                <td>
                    <select
                        id="cegg_preset_ai_short_desc"
                        name="cegg_preset[ai_short_desc]"
                        <?php disabled(! $is_pro); ?>>
                        <?php
                        $options = array_merge(
                            ['' => __('Disabled', 'content-egg')],
                            $provider::getShortDescriptionMethodOptions()
                        );
                        $sel = $data['ai_short_desc'] ?? '';
                        foreach ($options as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($sel, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e(
                            'Use the %AI.short_desc% placeholder in the Woo Short Description Template to insert AI-generated text.',
                            'content-egg'
                        ); ?>
                    </p>

                    <?php if ($is_pro) : ?>
                        <p class="cegg-pro-warning"><?php echo wp_kses_post($ai_warning); ?></p>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Custom Prompt #1 -->
            <tr>
                <th scope="row"><label for="cegg_preset_prompt1"><?php esc_html_e('Custom Prompt #1', 'content-egg'); ?></label></th>
                <td>
                    <textarea
                        id="cegg_preset_prompt1"
                        name="cegg_preset[prompt1]"
                        class="widefat"
                        <?php disabled(! $is_pro); ?>
                        rows="3"><?php echo esc_textarea($data['prompt1'] ?? ''); ?></textarea>

                    <?php \ContentEgg\application\helpers\AdminHelper::echoPlaceholderDescription(); ?>

                </td>
            </tr>

            <!-- Custom Prompt #2 -->
            <tr>
                <th scope="row"><label for="cegg_preset_prompt2"><?php esc_html_e('Custom Prompt #2', 'content-egg'); ?></label></th>
                <td>
                    <textarea
                        id="cegg_preset_prompt2"
                        name="cegg_preset[prompt2]"
                        class="widefat"
                        <?php disabled(! $is_pro); ?>
                        rows="3"><?php echo esc_textarea($data['prompt2'] ?? ''); ?></textarea>
                </td>
            </tr>

            <!-- Custom Prompt #3 -->
            <tr>
                <th scope="row"><label for="cegg_preset_prompt3"><?php esc_html_e('Custom Prompt #3', 'content-egg'); ?></label></th>
                <td>
                    <textarea
                        id="cegg_preset_prompt3"
                        name="cegg_preset[prompt3]"
                        class="widefat"
                        <?php disabled(! $is_pro); ?>
                        rows="3"><?php echo esc_textarea($data['prompt3'] ?? ''); ?></textarea>
                </td>
            </tr>

            <!-- Custom Fields -->
            <tr>
                <th><label for="cegg_preset_custom_fields"><?php esc_html_e('Add Custom Meta Fields', 'content-egg'); ?></label></th>
                <td>
                    <?php
                    $existing_fields = array_values(array_filter((array)($data['custom_fields'] ?? []), function ($row)
                    {
                        return is_array($row) && (isset($row['key']) || isset($row['value']));
                    }));
                    $loop_count  = max(1, count($existing_fields));
                    $next_index  = count($existing_fields) > 0 ? count($existing_fields) : 1;
                    ?>
                    <div id="cegg_preset_custom_fields" data-next-index="<?php echo esc_attr($next_index); ?>">
                        <div class="cegg-fields">
                            <?php for ($i = 0; $i < $loop_count; $i++): ?>
                                <p class="cegg-custom-field-row">
                                    <input
                                        type="text"
                                        name="cegg_preset[custom_fields][<?php echo esc_attr($i); ?>][key]"
                                        class="regular-text"
                                        value="<?php echo esc_attr($existing_fields[$i]['key'] ?? ''); ?>"
                                        placeholder="<?php esc_attr_e('Field Name', 'content-egg'); ?>">
                                    <input
                                        type="text"
                                        name="cegg_preset[custom_fields][<?php echo esc_attr($i); ?>][value]"
                                        class="regular-text"
                                        value="<?php echo esc_attr($existing_fields[$i]['value'] ?? ''); ?>"
                                        placeholder="<?php esc_attr_e('Field Value (e.g. %PRODUCT.title%)', 'content-egg'); ?>">
                                    <button type="button" class="btn-link cegg-remove-field">&times;</button>
                                </p>
                            <?php endfor; ?>
                        </div>

                        <p style="margin-top:.5em;">
                            <button type="button" class="button" id="cegg_add_custom_field"><?php esc_html_e('Add field', 'content-egg'); ?></button>
                        </p>

                        <script type="text/template" id="cegg_custom_field_tpl">
                            <p class="cegg-custom-field-row">
                                <input
                                    type="text"
                                    name="cegg_preset[custom_fields][__index__][key]"
                                    class="regular-text"
                                    value=""
                                    placeholder="<?php echo esc_attr__('Field Name', 'content-egg'); ?>">
                                <input
                                    type="text"
                                    name="cegg_preset[custom_fields][__index__][value]"
                                    class="regular-text"
                                    value=""
                                    placeholder="<?php echo esc_attr__('Field Value (e.g. %PRODUCT.title%)', 'content-egg'); ?>">
                                <button type="button" class="btn-link cegg-remove-field">&times;</button>
                            </p>
                        </script>

                        <?php \ContentEgg\application\helpers\AdminHelper::echoPlaceholderDescription(); ?>
                    </div>
                </td>
            </tr>

            <!-- Avoid Duplicates (by Content Egg product unique_id) -->
            <tr>
                <th>
                    <label for="cegg_preset_avoid_duplicates">
                        <?php esc_html_e('Avoid Duplicates by product unique_id', 'content-egg'); ?>
                    </label>
                </th>
                <td>
                    <!-- Always submit a value -->
                    <input type="hidden" name="cegg_preset[avoid_duplicates]" value="0">
                    <input
                        type="checkbox"
                        id="cegg_preset_avoid_duplicates"
                        name="cegg_preset[avoid_duplicates]"
                        value="1"
                        <?php checked(! empty($data['avoid_duplicates'])); ?>>
                    <label for="cegg_preset_avoid_duplicates">
                        <?php esc_html_e('Skip import if a post or product created from the same Content Egg item already exists.', 'content-egg'); ?>
                    </label>

                </td>
            </tr>

            <!-- Avoid Duplicates by WooCommerce GTIN (EAN) -->
            <tr class="cegg-product-only">
                <th>
                    <label for="cegg_preset_avoid_duplicates_gtin">
                        <?php esc_html_e('Avoid Duplicates by WooCommerce GTIN/EAN', 'content-egg'); ?>
                    </label>
                </th>
                <td>
                    <!-- Always submit a value -->
                    <input type="hidden" name="cegg_preset[avoid_duplicates_gtin]" value="0">
                    <input
                        type="checkbox"
                        id="cegg_preset_avoid_duplicates_gtin"
                        name="cegg_preset[avoid_duplicates_gtin]"
                        value="1"
                        <?php checked(! empty($data['avoid_duplicates_gtin'])); ?>>
                    <label for="cegg_preset_avoid_duplicates_gtin">
                        <?php esc_html_e('Skip import if the product GTIN/EAN matches an existing WooCommerce product.', 'content-egg'); ?>
                    </label>

                    <p class="description">
                        <?php
                        esc_html_e(
                            'Tip: You can enable GTIN synchronization in Content Egg → Settings → WooCommerce → Sync GTIN.',
                            'content-egg'
                        );
                        ?>
                    </p>
                </td>
            </tr>

            <!-- Canonical Bridge Pages -->
            <tr>
                <th><label for="cegg_preset_make_canonical"><?php esc_html_e('Canonical Bridge Pages', 'content-egg'); ?></label></th>
                <td>
                    <!-- Ensure a value is always submitted -->
                    <input type="hidden" name="cegg_preset[make_canonical]" value="0">

                    <input
                        type="checkbox"
                        id="cegg_preset_make_canonical"
                        name="cegg_preset[make_canonical]"
                        value="1"
                        <?php checked($data['make_canonical'] ?? false); ?>>
                    <label for="cegg_preset_make_canonical">
                        <?php esc_html_e('Make imported Bridge Pages canonical', 'content-egg'); ?>
                    </label>
                    <p class="description">
                        <?php
                        esc_html_e(
                            'When you use this preset from the post editor to create Bridge Pages, each one is set as the site-wide default (canonical) destination for its product. As a result, any existing links to the same product across your site will redirect to the newly created Bridge Page.',
                            'content-egg'
                        ); ?>
                    </p>

                </td>
            </tr>

            </tr>

            <!-- Use as Default Preset -->
            <tr>
                <th><label for="cegg_preset_use_default"><?php esc_html_e('Use as Default Preset', 'content-egg'); ?></label></th>
                <td>
                    <!-- Ensure a value is always submitted -->
                    <input type="hidden" name="cegg_preset[use_default]" value="0">

                    <input
                        type="checkbox"
                        id="cegg_preset_use_default"
                        name="cegg_preset[use_default]"
                        value="1"
                        <?php checked($data['use_default'] ?? false); ?>>
                    <label for="cegg_preset_use_default"><?php esc_html_e('Use this preset by default when importing products', 'content-egg'); ?></label>
                </td>
            </tr>

        </tbody>
    </table>

    <?php submit_button($is_edit ? esc_html__('Update Preset', 'content-egg') : esc_html__('Create Preset', 'content-egg')); ?>
</form>

<script>
    "use strict";
    (function($) {
        const toggleFields = () => {
            const pt = $("#cegg_preset_post_type").val();
            if (pt === "product") {
                $(".cegg-product-only").show();
                $(".cegg-post-only").hide();
            } else {
                $(".cegg-product-only").hide();
                $(".cegg-post-only").show();
            }
        };

        $(document).ready(() => {
            toggleFields();
            $("#cegg_preset_post_type").on("change", toggleFields);

            // --- Custom Fields: add/remove rows ---
            const $wrap = $("#cegg_preset_custom_fields");
            const $list = $wrap.find(".cegg-fields");

            $("#cegg_add_custom_field").on("click", () => {
                const idx = parseInt($wrap.attr("data-next-index"), 10) || 0;
                const tpl = $("#cegg_custom_field_tpl").html().replace(/__index__/g, idx);
                $list.append(tpl);
                $wrap.attr("data-next-index", idx + 1);
            });

            $wrap.on("click", ".cegg-remove-field", (e) => {
                e.preventDefault();
                $(e.currentTarget).closest(".cegg-custom-field-row").remove();
            });
        });
    })(jQuery);
</script>
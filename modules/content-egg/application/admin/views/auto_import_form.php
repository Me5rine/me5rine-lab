<?php

use ContentEgg\application\models\AutoImportRuleModel;
use ContentEgg\application\Plugin;

defined('ABSPATH') || exit; ?>

<h2>
    <?php echo $is_edit
        ? esc_html__('Edit Auto-Import Rule', 'content-egg')
        : esc_html__('Add Auto-Import Rule', 'content-egg'); ?>
</h2>
<div class="cegg5-container">

    <form method="post" class="mt-3" action="<?php echo esc_url(add_query_arg('noheader', 'true')); ?>">
        <?php wp_nonce_field('cegg_autoimport', 'cegg_autoimport_nonce'); ?>
        <input type="hidden" name="ai_action" value="save">
        <input type="hidden" name="rule_id" value="<?php echo (int) $rule['id']; ?>">

        <div class="row g-4">

            <!-- Rule name -->
            <div class="col-12 col-md-6">
                <label for="ai_name" class="form-label">
                    <?php esc_html_e('Rule name', 'content-egg'); ?>
                </label>
                <input
                    type="text"
                    id="ai_name"
                    name="ai_name"
                    class="form-control"
                    value="<?php echo esc_attr($rule['name']); ?>"
                    required>
            </div>

            <!-- Status -->
            <div class="col-12 col-md-6">
                <label for="status" class="form-label">
                    <?php esc_html_e('Status', 'content-egg'); ?>
                </label>
                <select id="status" name="status" class="form-select">
                    <?php foreach (AutoImportRuleModel::getStatusOptions() as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($rule['status'] ?? AutoImportRuleModel::STATUS_ACTIVE, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Preset -->
            <div class="col-12 col-md-6">
                <label for="preset_id" class="form-label">
                    <?php esc_html_e('Import preset', 'content-egg'); ?>
                </label>
                <select id="preset_id" name="preset_id" class="form-select">
                    <?php foreach ($preset_options as $p) : ?>
                        <option value="<?php echo esc_attr($p['id']); ?>" <?php selected($p['id'], $rule['preset_id']); ?>>
                            <?php
                            printf(
                                '%s [%s]',
                                esc_html($p['title']),
                                esc_html($p['type'])
                            );
                            ?> </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Module -->
            <div class="col-12 col-md-6">
                <label for="module_id" class="form-label">
                    <?php esc_html_e('Module', 'content-egg'); ?>
                </label>
                <select id="module_id" name="module_id" class="form-select" required>
                    <?php foreach ($modules as $m) : ?>
                        <option value="<?php echo esc_attr($m['module_id']); ?>" <?php selected($m['module_id'], $rule['module_id']); ?>>
                            <?php echo esc_html($m['module_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Interval -->
            <div class="col-12 col-md-6">
                <label for="interval_seconds" class="form-label">
                    <?php esc_html_e('Run every…', 'content-egg'); ?>
                </label>
                <select id="interval_seconds" name="interval_seconds" class="form-select" required>
                    <?php
                    $options = [
                        3600     => __('1 hour',        'content-egg'),
                        21600    => __('6 hours',       'content-egg'),
                        43200    => __('12 hours',      'content-egg'),
                        86400    => __('Daily',         'content-egg'),
                        172800   => __('Every 2 days',  'content-egg'),
                        259200   => __('Every 3 days',  'content-egg'),
                        604800   => __('Weekly',        'content-egg'),
                        1209600  => __('Every 2 weeks', 'content-egg'),
                        2592000  => __('Monthly',       'content-egg'),
                        7776000  => __('Quarterly',     'content-egg'),
                    ];
                    if (Plugin::isDevEnvironment())
                    {
                        $options[5] = __('Every 5 seconds [dev]', 'content-egg');
                    }
                    foreach ($options as $secs => $label) : ?>
                        <option value="<?php echo esc_attr($secs); ?>" <?php selected($rule['interval_seconds'], $secs); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    <?php esc_html_e('How often this rule enqueues imports.', 'content-egg'); ?>
                </div>
            </div>

            <!-- Sort by newest first -->
            <div class="col-12 col-md-6">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="sort_newest" name="sort_newest" value="1" <?php checked($rule['sort_newest'], 1); ?>>
                    <label class="form-check-label" for="sort_newest">
                        <?php esc_html_e('Sort by newest first', 'content-egg'); ?>
                    </label>
                </div>
                <div class="form-text">
                    <?php esc_html_e('When checked, fetch newest products first.', 'content-egg'); ?><br>
                    <?php esc_html_e('Note: Not all modules support sorting by newest — default sort order will be applied in such cases.', 'content-egg'); ?>
                </div>
            </div>

            <!-- Keywords -->
            <div class="col-12">
                <label for="keywords" class="form-label">
                    <?php esc_html_e('Keywords', 'content-egg'); ?>
                </label>
                <textarea id="keywords" name="keywords" rows="5" class="form-control" placeholder="<?php esc_attr_e('One per line…', 'content-egg'); ?>"><?php echo esc_textarea($keywords); ?></textarea>
                <div class="form-text">
                    <?php esc_html_e('Each rule run will process a single keyword at a time.', 'content-egg'); ?>
                </div>
            </div>

            <!-- Layout the stop/disable settings in two columns -->
            <!-- Stop after X days -->
            <div class="col-12 col-md-6">
                <label for="stop_after_days" class="form-label">
                    <?php esc_html_e('Stop after X days', 'content-egg'); ?>
                </label>
                <input type="number" min="1" class="form-control" id="stop_after_days" name="stop_after_days" value="<?php echo esc_attr($rule['stop_after_days']); ?>">
                <div class="form-text">
                    <?php esc_html_e('Automatically pause this rule after X days.', 'content-egg'); ?>
                </div>
            </div>

            <!-- Stop after importing X products -->
            <div class="col-12 col-md-6">
                <label for="stop_after_imports" class="form-label">
                    <?php esc_html_e('Stop after importing X products', 'content-egg'); ?>
                </label>
                <input type="number" min="1" class="form-control" id="stop_after_imports" name="stop_after_imports" value="<?php echo esc_attr($rule['stop_after_imports']); ?>">
                <div class="form-text">
                    <?php esc_html_e('Automatically pause after this many products have been imported.', 'content-egg'); ?>
                </div>
            </div>

            <!-- Stop if no new products for X runs -->
            <div class="col-12 col-md-6">
                <label for="stop_if_no_new_results" class="form-label">
                    <?php esc_html_e('Stop if no new products for X runs', 'content-egg'); ?>
                </label>
                <input type="number" min="1" class="form-control" id="stop_if_no_new_results" name="stop_if_no_new_results" value="<?php echo esc_attr($rule['stop_if_no_new_results']); ?>">
                <div class="form-text">
                    <?php esc_html_e('Automatically pause if no new products are found in consecutive runs.', 'content-egg'); ?>
                </div>
            </div>

            <!-- Disable keyword after no products for X runs -->
            <div class="col-12 col-md-6">
                <label for="max_keyword_no_products" class="form-label">
                    <?php esc_html_e('Disable keyword after no products for X runs', 'content-egg'); ?>
                </label>
                <input type="number" min="1" class="form-control" id="max_keyword_no_products" name="max_keyword_no_products" value="<?php echo esc_attr($rule['max_keyword_no_products']); ?>">
                <div class="form-text">
                    <?php esc_html_e('Automatically disable a keyword after X consecutive runs with no new products.', 'content-egg'); ?>
                </div>
            </div>

        </div><!-- /.row -->

        <button type="submit" class="btn btn-primary mt-4">
            <?php echo $is_edit
                ? esc_html__('Update Rule', 'content-egg')
                : esc_html__('Create Rule', 'content-egg'); ?>
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-import&tab=autoimport')); ?>" class="btn btn-secondary mt-4 ms-2">
            <?php esc_html_e('Cancel', 'content-egg'); ?>
        </a>
    </form>
</div>

<script>
    "use strict";
    document.addEventListener('DOMContentLoaded', () => {
        const textarea = document.getElementById('keywords');
        if (textarea) {
            const editor = wp.codeEditor.initialize(textarea, {
                codemirror: {
                    mode: 'null',
                    lineNumbers: true
                }
            });
            window.importKeywordsEditor = editor.codemirror;
        }
    });
</script>
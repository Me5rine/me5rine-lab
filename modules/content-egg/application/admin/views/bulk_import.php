<?php

defined('\ABSPATH') || exit;
?>

<div class="cegg5-container">
    <form action="<?php echo esc_url(add_query_arg('noheader', 'true')); ?>" id="cegg-bulk-import" class="mt-4" method="post">
        <?php wp_nonce_field('cegg_bulk_import', 'cegg_bulk_import_nonce'); ?>

        <div class="row g-4">

            <!-- Import Preset --------------------------------------------->
            <div class="col-12 col-md-6">
                <label for="import_preset" class="form-label">
                    <?php esc_html_e('Import Preset', 'content-egg'); ?>
                </label>
                <select id="import_preset" name="preset_id" class="form-select">
                    <?php foreach ($preset_options as $preset) : ?>
                        <option
                            value="<?php echo esc_attr($preset['id']); ?>"
                            data-type="<?php echo esc_attr($preset['type']); ?>"
                            <?php selected($preset['id'], $default_preset_id); ?>>
                            <?php
                            printf(
                                '%s [%s]',
                                esc_html($preset['title']),
                                esc_html($preset['type'])
                            );
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Post Category ------------->
            <div id="group_post_cat" class="col-12 col-md-6">
                <label for="import_post_cat" class="form-label">
                    <?php esc_html_e('Import to category', 'content-egg'); ?>
                </label>
                <select id="import_post_cat" name="post_cat" class="form-select">
                    <option value=""><?php esc_html_e('Choose…', 'content-egg'); ?></option>
                    <?php foreach ($post_cat_options as $cat_id => $cat_name) : ?>
                        <option value="<?php echo esc_attr($cat_id); ?>">
                            <?php echo esc_html($cat_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Woo Category ----------->
            <div id="group_woo_cat" class="col-12 col-md-6" style="display: none;">
                <label for="import_woo_cat" class="form-label">
                    <?php esc_html_e('Import to category', 'content-egg'); ?>
                </label>
                <select id="import_woo_cat" name="woo_cat" class="form-select">
                    <option value=""><?php esc_html_e('Choose…', 'content-egg'); ?></option>
                    <?php foreach ($woo_cat_options as $cat_id => $cat_name) : ?>
                        <option value="<?php echo esc_attr($cat_id); ?>">
                            <?php echo esc_html($cat_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Module ---------------------------------------------------->
            <div class="col-12 col-md-6">
                <label for="import_module" class="form-label">
                    <?php esc_html_e('Module', 'content-egg'); ?>
                </label>
                <select id="import_module" name="module_id" class="form-select">
                    <?php foreach ($module_meta as $mod) : ?>
                        <option
                            value="<?php echo esc_attr($mod['module_id']); ?>"
                            data-has-url-search="<?php echo $mod['has_url_search'] ? 1 : 0; ?>">
                            <?php echo esc_html($mod['module_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Schedule publication ---------------------------------->
            <div class="col-12 col-md-6">
                <label for="import_schedule" class="form-label">
                    <?php esc_html_e('Publish when…', 'content-egg'); ?>
                </label>

                <select id="import_schedule" name="schedule_offset" class="form-select">
                    <option value="0"><?php esc_html_e('Immediately',         'content-egg'); ?></option>
                    <option value="1"><?php esc_html_e('Within 1 month',      'content-egg'); ?></option>
                    <option value="3"><?php esc_html_e('Within 3 months',     'content-egg'); ?></option>
                    <option value="4"><?php esc_html_e('Within 4 months',     'content-egg'); ?></option>
                    <option value="5"><?php esc_html_e('Within 5 months',     'content-egg'); ?></option>
                    <option value="6"><?php esc_html_e('Within 6 months',     'content-egg'); ?></option>
                    <option value="7"><?php esc_html_e('Within 7 months',     'content-egg'); ?></option>
                    <option value="8"><?php esc_html_e('Within 8 months',     'content-egg'); ?></option>
                    <option value="9"><?php esc_html_e('Within 9 months',     'content-egg'); ?></option>
                    <option value="10"><?php esc_html_e('Within 10 months',     'content-egg'); ?></option>
                    <option value="11"><?php esc_html_e('Within 11 months',     'content-egg'); ?></option>
                    <option value="12"><?php esc_html_e('Within 12 months',     'content-egg'); ?></option>
                </select>
            </div>

            <!-- Keywords / URLs ------------------------------------------>
            <div class="col-12">
                <label
                    for="import_keywords"
                    id="keywords_label"
                    class="form-label">
                    <?php esc_html_e('Keywords / Product URLs', 'content-egg'); ?>
                </label>
                <textarea
                    style="display: none;"
                    id="import_keywords"
                    name="keywords"
                    rows="6"
                    class="form-control"
                    placeholder="<?php esc_attr_e('One per line…', 'content-egg'); ?>"></textarea>

                <p class="description" style="margin-top: 0.8em;">
                    <?php esc_html_e('Each keyword will generate a separate post.', 'content-egg'); ?>
                </p>

            </div>

        </div><!-- /.row -->

        <button
            type="submit"
            id="cegg-bulk-import-submit"
            class="btn btn-success mt-4">
            <?php esc_html_e('Start Import', 'content-egg'); ?>
        </button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const textarea = document.getElementById('import_keywords');
            if (textarea) {
                const editor = wp.codeEditor.initialize(textarea, {
                    codemirror: {
                        mode: 'null',
                        lineNumbers: true,
                        placeholder: textarea.placeholder,
                    }
                });
                window.importKeywordsEditor = editor.codemirror;
            }
            const form = document.getElementById('cegg-bulk-import');
            const submitBtn = document.getElementById('cegg-bulk-import-submit');
            const presetSelect = document.getElementById('import_preset');
            const postCatGroup = document.getElementById('group_post_cat');
            const wooCatGroup = document.getElementById('group_woo_cat');
            const moduleSelect = document.getElementById('import_module');
            const keywordsLabel = document.getElementById('keywords_label');
            const keywordsField = document.getElementById('import_keywords');

            const tKeywordsUrls = '<?php echo esc_js(__('Keywords / Product URLs', 'content-egg')); ?>';
            const tKeywordsOnly = '<?php echo esc_js(__('Keywords', 'content-egg')); ?>';

            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.textContent = '<?php echo esc_js(__('Importing…', 'content-egg')); ?>';
            });

            function toggleCatFields() {
                const type = presetSelect.options[presetSelect.selectedIndex].dataset.type;
                postCatGroup.style.display = (type === 'post') ? '' : 'none';
                wooCatGroup.style.display = (type === 'product') ? '' : 'none';
            }

            function updateKeywordsLabel() {
                const allowUrls = moduleSelect
                    .options[moduleSelect.selectedIndex]
                    .dataset.hasUrlSearch === '1';

                keywordsLabel.textContent = allowUrls ?
                    tKeywordsUrls :
                    tKeywordsOnly;

            }

            presetSelect.addEventListener('change', toggleCatFields);
            moduleSelect.addEventListener('change', updateKeywordsLabel);

            toggleCatFields();
            updateKeywordsLabel();
        });
    </script>

</div>
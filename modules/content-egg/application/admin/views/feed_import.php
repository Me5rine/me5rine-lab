<?php defined('\ABSPATH') || exit; ?>

<div class="cegg5-container">
    <form action="<?php echo esc_url(add_query_arg('noheader', 'true')); ?>" id="cegg-feed-import" method="post" class="mt-4">
        <?php wp_nonce_field('cegg_feed_import', 'cegg_feed_import_nonce'); ?>

        <!-- ─────────────────── Preset selector ─────────────────── -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6">
                <label for="preset_id" class="form-label">
                    <?php esc_html_e('Import Preset', 'content-egg'); ?>
                </label>
                <select id="preset_id" name="preset_id" class="form-select">
                    <?php foreach ($preset_options as $preset) : ?>
                        <option
                            value="<?php echo esc_attr($preset['id']); ?>"
                            data-type="<?php echo esc_attr($preset['type']); ?>"
                            <?php selected($preset['id'], $default_preset_id); ?>>
                            <?php printf(
                                '%s [%s]',
                                esc_html($preset['title']),
                                esc_html($preset['type'])
                            ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ─────────────── Schedule selector ─────────────── -->
            <div class="col-12 col-md-6">
                <label for="import_schedule" class="form-label">
                    <?php esc_html_e('Publish when…', 'content-egg'); ?>
                </label>
                <select id="import_schedule" name="schedule_offset" class="form-select">
                    <?php
                    for ($m = 0; $m <= 12; $m++)
                    {
                        printf(
                            '<option value="%1$s">%2$s</option>',
                            esc_attr($m),
                            (0 === $m)
                                ? esc_html__('Immediately', 'content-egg')
                                : sprintf(
                                    /* translators: %d = number of months */
                                    esc_html__('Within %d month(s)', 'content-egg'),
                                    intval($m)
                                )
                        );
                    }
                    ?>
                </select>

            </div>
        </div><!-- /.row -->

        <!-- ────────────────── Feed modules table ────────────────── -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Module', 'content-egg'); ?></th>
                    <th><?php esc_html_e('Status', 'content-egg'); ?></th>
                    <th class="text-end"><?php esc_html_e('Products', 'content-egg'); ?></th>
                    <th class="text-center"><?php esc_html_e('Actions', 'content-egg'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feed_modules)) : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No feed modules are active.', 'content-egg'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($feed_modules as $module) : ?>
                        <?php
                        $module_id       = $module->getId();
                        $module_name     = $module->getName();
                        $in_progress     = $module->isImportInProgress();
                        $scheduled       = $module->isImportScheduled();
                        $last_error      = $module->getLastImportError();
                        $last_import     = $module->getLastImportDateReadable();
                        $product_count   = $module->getProductCount();

                        $status_txt = $in_progress  ? __('Feed sync in progress', 'content-egg')
                            : ($scheduled ? __('Feed sync scheduled', 'content-egg')
                                : ($last_error ? __('Error', 'content-egg') : __('Ready', 'content-egg')));

                        /* -------- Dynamic warnings based on product count -------- */
                        $warning_html = '';
                        if ($product_count >= 10000)
                        {
                            $warning_html = '<span class="badge bg-danger ms-1">'
                                . esc_html__('Huge feed', 'content-egg') . '</span>';
                        }
                        elseif ($product_count >= 3000)
                        {
                            $warning_html = '<span class="badge bg-warning text-dark ms-1">'
                                . esc_html__('Large feed', 'content-egg') . '</span>';
                        }

                        /* -------- Confirmation text -------- */
                        $confirm_text = __('This will generate a separate post for each product in the feed.', 'content-egg');
                        if ($product_count >= 10000)
                        {
                            $confirm_text .= ' ' . __('Feed contains more than 10,000 products. Consider importing in stages.', 'content-egg');
                        }
                        elseif ($product_count >= 3000)
                        {
                            $confirm_text .= ' ' . __('Large feed detected. Ensure your hosting can handle it.', 'content-egg');
                        }
                        $confirm_text .= ' ' . __('Do you want to continue?', 'content-egg');
                        ?>
                        <tr>
                            <!-- Module name (link) -->
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=content-egg-modules--' . $module_id)); ?>">
                                    <strong><?php echo esc_html($module_name); ?></strong>
                                </a>
                                <?php echo $warning_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                            </td>

                            <!-- Status -->
                            <td>
                                <?php
                                if ($in_progress)
                                {
                                    echo '<span class="text-info">' . esc_html($status_txt) . '</span>';
                                }
                                elseif ($scheduled)
                                {
                                    echo '<span class="text-warning">' . esc_html($status_txt) . '</span>';
                                }
                                elseif ($last_error)
                                {
                                    echo '<span class="text-danger">' . esc_html($status_txt) . '</span>';
                                }
                                else
                                {
                                    echo esc_html($status_txt);
                                }
                                ?>

                                <?php if ($last_error) : ?>
                                    <div class="text-muted small"><?php echo esc_html($last_error); ?></div>
                                <?php endif; ?>

                                <?php if ($last_import) : ?>
                                    <div class="text-muted mt-1 small">
                                        <?php
                                        printf(
                                            /* translators: %s = date & time */
                                            esc_html__('Last feed sync: %s', 'content-egg'),
                                            esc_html($last_import)
                                        );
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Product count -->
                            <td class="text-end">
                                <?php
                                echo $last_import
                                    ? esc_html(number_format_i18n($product_count))
                                    : '—';
                                ?>
                            </td>

                            <!-- Action button -->
                            <td class="text-center">
                                <button
                                    type="submit"
                                    name="import_module"
                                    value="<?php echo esc_attr($module_id); ?>"
                                    class="btn btn-sm btn-success cegg-feed-import-btn"
                                    title="<?php echo esc_attr__('Import all products from this feed', 'content-egg'); ?>"
                                    <?php disabled(! $last_import || ! $product_count); ?>
                                    onclick='return ceggFeedConfirm(this, <?php echo wp_json_encode($confirm_text); ?>);'>
                                    <?php esc_html_e('Import All', 'content-egg'); ?>
                                </button>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<!-- ───────────── JS helper to confirm + disable button + spinner ───────────── -->
<script>
    function ceggFeedConfirm(btn, msg) {
        if (!confirm(msg)) {
            return false;
        }
        // queue the disable+spinner *after* the native submit
        setTimeout(function() {
            btn.disabled = true;
            btn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
                '<?php echo esc_js(__('Importing…', 'content-egg')); ?>';
        }, 0);
        return true; // allow the browser to submit
    }
</script>
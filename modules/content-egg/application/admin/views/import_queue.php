<?php

use ContentEgg\application\Plugin;;

defined('\ABSPATH') || exit;
?>

<style>
    /* Scoped styles for the ImportQueueTable */
    div[id="import-queue-list"] .wp-list-table th,
    div[id="import-queue-list"] .wp-list-table td {
        vertical-align: middle;
        padding: 8px 10px;
    }

    /* Post (title)  */
    div[id="import-queue-list"] .column-post_id {
        width: 30%;
        max-width: 30%;
    }

    /* Product image column (fixed size) */
    div[id="import-queue-list"] .column-product {
        width: 60px;
        text-align: center;
        padding: 5px !important;
    }

    .cegg-queue-product-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        display: block;
        margin: 0 auto;
    }

    /* Module column */
    div[id="import-queue-list"] .column-module_id {
        width: 10%;
        max-width: 10%;
    }

    /* Preset column */
    div[id="import-queue-list"] .column-preset_id {
        width: 10%;
        max-width: 10%;
    }

    /* Status column */
    div[id="import-queue-list"] .column-status {
        width: 10%;
        max-width: 10%;
        text-align: center;
    }

    /* Updated date column */
    div[id="import-queue-list"] .column-updated_at {
        width: 12%;
        max-width: 12%;
        white-space: nowrap;
    }

    /* Log column – cap at the remaining ~30% */
    div[id="import-queue-list"] .column-log {
        width: 30%;
        max-width: 30%;
        white-space: pre-wrap;
        word-break: break-word;
        font-family: monospace;
        font-size: 12px;
        overflow: hidden;
    }
</style>

<div class="cegg5-container">

    <div class="mt-4 mb-2">
        <?php if ($last_updated): ?>
            <?php
            $timestamp = strtotime($last_updated);
            $time_diff = human_time_diff($timestamp, current_time('timestamp'));
            ?>
            <em>
                <?php echo esc_html__('Last activity:', 'content-egg') . ' ' .
                    esc_html(sprintf(__('%s ago', 'content-egg'), $time_diff)); ?>
            </em>
        <?php endif; ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-import&tab=queue')); ?>"
            class="page-title-action">
            <?php esc_html_e('Refresh Status', 'content-egg'); ?>
        </a>
    </div>

    <?php if ($is_possibly_stuck): ?>
        <div class="alert alert-danger mt-3" role="alert">
            <strong><?php esc_html_e('Warning:', 'content-egg'); ?></strong>
            <?php esc_html_e("The Import Queue hasn't made progress in the expected time window.", 'content-egg'); ?>
            <?php esc_html_e('Please verify your WP-Cron or server-side cron setup.', 'content-egg'); ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_cron_enabled && !Plugin::isDevEnvironment()): ?>

        <div class="alert alert-warning mt-3" role="alert">
            <strong><?php esc_html_e('Important:', 'content-egg'); ?></strong>
            <?php esc_html_e('WordPress Cron is disabled (DISABLE_WP_CRON is set to true).', 'content-egg'); ?><br>
            <?php esc_html_e('The Import Queue relies on WP-Cron to process tasks in the background.', 'content-egg'); ?><br>
            <?php esc_html_e('If you are using a server-side cron job or an external cron service, you can safely ignore this warning.', 'content-egg'); ?>
        </div>
    <?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th class="col-6 col-md-3"><?php esc_html_e('Status', 'content-egg'); ?></th>
                <th class="col-6"><?php esc_html_e('Count', 'content-egg'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php esc_html_e('Done', 'content-egg'); ?></td>
                <td><?php echo esc_html(number_format_i18n($stats['done'])); ?></td>
            </tr>
            <tr>
                <td><?php esc_html_e('Pending', 'content-egg'); ?></td>
                <td><?php echo esc_html(number_format_i18n($stats['pending'])); ?></td>
            </tr>
            <tr>
                <td><?php esc_html_e('Working', 'content-egg'); ?></td>
                <td><?php echo esc_html(number_format_i18n($stats['working'])); ?></td>
            </tr>
            <tr>
                <td><?php esc_html_e('Failed', 'content-egg'); ?></td>
                <td><?php echo esc_html(number_format_i18n($stats['failed'])); ?></td>
            </tr>

        </tbody>
    </table>

    <div class="d-flex gap-2 align-items-center">

        <?php if ($stats['pending'] > 0): ?>
            <!-- Stop all -->
            <form method="post"
                action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-import&tab=queue&action=import_stop&noheader=true')); ?>">
                <?php wp_nonce_field('cegg_import_stop', 'cegg_import_stop_nonce'); ?>
                <p class="submit">
                    <input type="submit"
                        class="button"
                        value="<?php esc_attr_e('Stop All Tasks', 'content-egg'); ?>"
                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to stop all pending tasks?', 'content-egg'); ?>');">
                </p>
            </form>
        <?php endif; ?>

        <?php if (!empty($failed_tasks_count)): ?>

            <!-- Restart failed -->
            <form method="post"
                action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-import&tab=queue&action=import_restart_failed&noheader=true')); ?>">
                <?php wp_nonce_field('cegg_import_restart_failed', 'cegg_import_restart_failed_nonce'); ?>
                <p class="submit">
                    <input type="submit"
                        class="button button-danger"
                        value="<?php esc_attr_e('Restart Failed Tasks', 'content-egg'); ?>">
                </p>
            </form>

        <?php endif; ?>

        <?php if (Plugin::isDevEnvironment() && $is_in_progress): ?>
            <!-- Run next (dev only) -->
            <form method="post"
                action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-import&tab=queue&action=import_run_once&noheader=true')); ?>">
                <?php wp_nonce_field('cegg_import_run_once', 'cegg_import_run_once_nonce'); ?>
                <p class="submit">
                    <input type="submit" class="button"
                        value="<?php esc_attr_e('Process Next Job', 'content-egg'); ?>">
                </p>
            </form>
        <?php endif; ?>

    </div>

    <?php if ($total_count): ?>
        <?php
        $truncate_url = wp_nonce_url(
            add_query_arg(
                [
                    'page'   => 'content-egg-product-import',
                    'tab'    => 'queue',
                    'action' => 'import_truncate',
                    'noheader' => 'true',
                ],
                admin_url('admin.php')
            ),
            'cegg_import_truncate'
        );
        ?>
        <div class="d-flex align-items-center fs-5 mt-3">
            <span><?php esc_html_e('Import Queue', 'content-egg'); ?></span>
            <?php if ($stats['failed'] || $stats['done']): ?>
                <a
                    href="<?php echo esc_url($truncate_url); ?>"
                    class="ms-2 link-secondary"
                    title="<?php esc_attr_e('Clear all completed and failed import jobs', 'content-egg'); ?>"
                    onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset the import queue log?', 'content-egg')); ?>');">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                    </svg>
                    <span class="visually-hidden"><?php esc_html_e('Truncate import queue', 'content-egg'); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <div id="import-queue-list" class="wp-list-table widefat striped">
            <?php $table->views(); ?>
            <?php $table->display(); ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($is_in_progress && ! Plugin::isDevEnvironment()) : ?>
    <script>
        (function() {
            "use strict";
            // auto-refresh every 60 s (page 1 only) to show live progress
            setTimeout(function() {
                const url = new URL(window.location.href);
                url.searchParams.delete("egg-notice");
                url.searchParams.delete("egg-notice-level");
                const paged = parseInt(url.searchParams.get("paged") || "1", 10);
                history.replaceState({}, document.title, url.toString());
                if (paged < 2) {
                    window.location.reload();
                }
            }, 60000);
        })();
    </script>
<?php endif; ?>
<?php

use ContentEgg\application\Plugin;

defined('\ABSPATH') || exit; ?>

<div class="wrap cegg5-container">

    <h1 class="h3">
        <?php echo esc_html(__('Product Prefill Tool', 'content-egg')); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-prefill&action=prefill_status')); ?>" class="page-title-action">
            <?php esc_html_e('Refresh Status', 'content-egg'); ?>
        </a>
    </h1>

    <?php if ($last_updated): ?>
        <?php
        $timestamp = strtotime($last_updated);
        $time_diff = human_time_diff($timestamp, current_time('timestamp'));
        ?>
        <p><em><?php echo esc_html__('Last activity:', 'content-egg') . ' ' . esc_html(sprintf(__('%s ago', 'content-egg'), $time_diff)); ?></em></p>
    <?php endif; ?>

    <?php if ($is_possibly_stuck) : ?>
        <div class="alert alert-danger mt-3" role="alert">
            <strong><?php esc_html_e('Warning:', 'content-egg'); ?></strong>
            <?php esc_html_e("It appears that the Prefill task is running but hasn't made progress in over 15 minutes.", 'content-egg'); ?><br>
            <?php esc_html_e("This may indicate a problem with WordPress Cron (WP-Cron) or server-side cron setup.", 'content-egg'); ?><br>
            <?php esc_html_e("Please verify that your cron system is functioning correctly.", 'content-egg'); ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_cron_enabled && !Plugin::isDevEnvironment()) : ?>
        <div class="alert alert-warning mt-3" role="alert">
            <strong><?php esc_html_e('Important:', 'content-egg'); ?></strong>
            <?php esc_html_e('WordPress Cron is disabled (DISABLE_WP_CRON is set to true).', 'content-egg'); ?><br>
            <?php esc_html_e('The Prefill Tool relies on WP-Cron to process tasks in the background.', 'content-egg'); ?><br>
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
                <td><?php esc_html_e('Pending', 'content-egg'); ?></td>
                <td><?php echo esc_html(number_format_i18n($stats['pending'])); ?></td>
            </tr>
            <tr>
                <td><?php esc_html_e('Done', 'content-egg'); ?></td>
                <td><?php echo esc_html(number_format_i18n($stats['done'])); ?></td>
            </tr>
            <tr>
                <td><?php esc_html_e('Failed', 'content-egg'); ?></td>
                <td><?php echo esc_html(number_format_i18n($stats['failed'])); ?></td>
            </tr>
        </tbody>

    </table>

    <?php if ($is_in_progress): ?>
        <div class="d-flex gap-2 align-items-center">
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-prefill&action=prefill_stop&noheader=true')); ?>">
                <?php wp_nonce_field('cegg_prefill_stop', 'cegg_prefill_stop_nonce'); ?>
                <p class="submit">
                    <input type="submit" class="button" value="<?php esc_attr_e('Stop All Tasks', 'content-egg'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to stop all pending tasks?', 'content-egg'); ?>');">
                </p>
            </form>

            <?php if (Plugin::isDevEnvironment()): ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-prefill&action=prefill_run_once&noheader=true')); ?>">
                    <?php wp_nonce_field('cegg_prefill_run_once', 'cegg_prefill_run_once_nonce'); ?>
                    <p class="submit">
                        <input type="submit" class="button" value="<?php esc_attr_e('Process Next Post', 'content-egg'); ?>">
                    </p>
                </form>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="d-flex gap-2 align-items-center">
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-prefill&action=prefill_restart&noheader=true')); ?>">
                <?php wp_nonce_field('cegg_prefill_restart', 'cegg_prefill_restart_nonce'); ?>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Start New Prefill', 'content-egg'); ?>">
                </p>
            </form>

            <?php if (!empty($failed_tasks_count)): ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-prefill&action=prefill_restart_failed&noheader=true')); ?>">
                    <?php wp_nonce_field('cegg_prefill_restart_failed', 'cegg_prefill_restart_failed_nonce'); ?>
                    <p class="submit">
                        <input type="submit" class="button button-danger" value="<?php esc_attr_e('Restart Failed Tasks', 'content-egg'); ?>">
                    </p>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <h2 class="h5"><?php esc_html_e('Log', 'content-egg'); ?></h2>
    <?php $table->views(); ?>
    <?php $table->display(); ?>
</div>

<style>
    table.wp-list-table .column-status {
        width: 15ch;
        max-width: 15ch;
        white-space: nowrap;
        text-align: center;
    }

    table.wp-list-table .column-updated_at {
        width: 20ch;
        max-width: 20ch;
        white-space: nowrap;
    }

    table.wp-list-table .column-log {
        max-width: 400px;
        white-space: pre-wrap;
        word-break: break-word;
        font-family: monospace;
        font-size: 12px;
    }
</style>

<?php if ($is_in_progress && ! Plugin::isDevEnvironment()) : ?>
    <script>
        "use strict";
        setTimeout(() => {
            const url = new URL(window.location.href);

            url.searchParams.delete("egg-notice");
            url.searchParams.delete("egg-notice-level");

            const paged = parseInt(url.searchParams.get("paged") || "1", 10);

            window.history.replaceState({}, document.title, url.toString());

            if (paged < 2) {
                window.location.reload();
            }
        }, 50000);
    </script>
<?php endif; ?>
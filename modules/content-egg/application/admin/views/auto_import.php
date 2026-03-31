<?php

use ContentEgg\application\Plugin;

defined('\ABSPATH') || exit; ?>
<div class="cegg5-container" id="auto-import-rules-list">

    <?php if ($is_possibly_stuck) : ?>
        <div class="alert alert-danger mt-3" role="alert">
            <strong><?php esc_html_e('Warning:', 'content-egg'); ?></strong>
            <?php esc_html_e("One or more Auto-Import rules haven't run on schedule.", 'content-egg'); ?>
            <?php esc_html_e('Please verify your WP-Cron or server-side cron setup.', 'content-egg'); ?>
        </div>
    <?php endif; ?>

    <?php if (Plugin::isDevEnvironment()): ?>

        <div class="d-flex gap-2 align-items-center">

            <!-- Run next (dev only) -->
            <form method="post"
                action="<?php echo esc_url(admin_url('admin.php?page=content-egg-product-import&tab=autoimport&noheader=true')); ?>">
                <?php wp_nonce_field('cegg_autoimport', 'cegg_autoimport_nonce'); ?>
                <input type="hidden" name="ai_action" value="run_once">
                <p class="submit">
                    <input type="submit" class="button"
                        value="<?php esc_attr_e('Process Next Rule', 'content-egg'); ?>">
                </p>
            </form>

        </div>
    <?php endif; ?>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe output from WP_List_Table::display().
    echo $table->display();
    ?>
</div>

<style>
    div[id="auto-import-rules-list"] .wp-list-table th.column-name,
    div[id="auto-import-rules-list"] .wp-list-table td.column-name {
        width: 20%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    div[id="auto-import-rules-list"] .wp-list-table th.column-module_id,
    div[id="auto-import-rules-list"] .wp-list-table td.column-module_id {
        width: 10%;
        white-space: nowrap;
    }

    div[id="auto-import-rules-list"] .wp-list-table th.column-preset_id,
    div[id="auto-import-rules-list"] .wp-list-table td.column-preset_id {
        width: 15%;
    }

    div[id="auto-import-rules-list"] .wp-list-table th.column-status,
    div[id="auto-import-rules-list"] .wp-list-table td.column-status {
        width: 8%;
        white-space: nowrap;
    }

    div[id="auto-import-rules-list"] .wp-list-table th.column-post_count,
    div[id="auto-import-rules-list"] .wp-list-table td.column-post_count {
        width: 9%;
        white-space: nowrap;
    }

    div[id="auto-import-rules-list"] .wp-list-table th.column-next_run_at,
    div[id="auto-import-rules-list"] .wp-list-table td.column-next_run_at {
        width: 12%;
        white-space: nowrap;
    }

    div[id="auto-import-rules-list"] .wp-list-table th.column-log_history,
    div[id="auto-import-rules-list"] .wp-list-table td.column-log_history {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
</style>
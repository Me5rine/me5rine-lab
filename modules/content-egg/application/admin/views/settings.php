<?php defined('\ABSPATH') || exit; ?>

<?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    <div class="cegg-maincol">
    <?php endif; ?>
    <div class="wrap cegg5-container">
        <div class="cegg5-container">
            <h2 class="h4 d-flex align-items-center justify-content-between mb-2 mt-4" style="height: 30px;">
                <span><?php esc_html_e('Content Egg Settings', 'content-egg'); ?></span>
                <div class="d-flex align-items-center">

                    <!-- Export button -->
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="my-0 py-0">
                        <?php wp_nonce_field('cegg_export-plugin-settings'); ?>
                        <input type="hidden" name="page" value="content-egg-tools">
                        <input type="hidden" name="action" value="export-plugin-settings">

                        <button
                            type="submit"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                            aria-label="<?php esc_attr_e('Export plugin settings', 'content-egg'); ?>" aria-label="<?php esc_attr_e('Export plugin settings', 'content-egg'); ?>"
                            title="<?php esc_attr_e('Export plugin settings', 'content-egg'); ?>" aria-label="<?php esc_attr_e('Export plugin settings', 'content-egg'); ?>">
                            <i class="dashicons dashicons-migrate" aria-hidden="true"></i>
                            <span class="ms-1"><?php esc_html_e('Export', 'content-egg'); ?></span>
                        </button>
                    </form>

                    <!-- Import button -->
                    <form action="<?php echo esc_url(admin_url('admin.php')); ?>" method="post" enctype="multipart/form-data" class="ms-2 me-3 my-0 py-0">
                        <?php wp_nonce_field('cegg_import-plugin-settings'); ?>
                        <input type="hidden" name="page" value="content-egg-tools">
                        <input type="hidden" name="action" value="import-plugin-settings">

                        <label for="cegg-import-file" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center mb-0" title="<?php esc_attr_e('Import plugin settings', 'content-egg'); ?>">
                            <i class="dashicons dashicons-upload me-1" aria-hidden="true"></i><?php esc_html_e('Import', 'content-egg'); ?>
                        </label>

                        <input type="file" id="cegg-import-file" name="settings_file" accept=".json" required class="d-none" onchange="this.form.submit();" aria-label="<?php esc_attr_e('Choose JSON file to import', 'content-egg'); ?>">
                    </form>

                    <?php include __DIR__ . '/_version_badge.php'; ?>

                </div>

            </h2>
        </div>
        <?php \settings_errors(); ?>
        <form action="options.php" method="POST">
            <?php \settings_fields($page_slug); ?>
            <?php \ContentEgg\application\helpers\AdminHelper::doTabsSections($page_slug); ?>
            <?php \submit_button(); ?>
        </form>
    </div>

    <?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    </div>
    <?php include('_promo_box.php'); ?>
<?php endif; ?>
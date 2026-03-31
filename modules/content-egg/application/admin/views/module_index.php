<?php defined('\ABSPATH') || exit; ?>

<?php
function _cegg_print_module_item(array $modules)
{
    foreach ($modules as $module)
    {
        // start the flex container
        echo '<a href="?page=' . esc_attr($module->getConfigInstance()->page_slug()) . '" '
            . 'class="list-group-item list-group-item-action d-flex align-items-center">';

        // --- status dot ---
        if ($module->isDeprecated())
        {
            $dotColor = 'var(--cegg-warning,#ffc107)';
            $dotTitle = __('Deprecated', 'content-egg');
        }
        elseif ($module->isActive())
        {
            $dotColor = '#20c997';
            $dotTitle = __('Active', 'content-egg');
        }
        else
        {
            $dotColor = '#adb5bd';
            $dotTitle = __('Inactive', 'content-egg');
        }
        echo '<span style="'
            . 'display:inline-block;'
            . 'width:0.5rem;height:0.5rem;'
            . 'border-radius:50%;'
            . 'background-color:' . esc_attr($dotColor) . ';'
            . '" title="' . esc_attr($dotTitle) . '"></span>';

        // --- module name (this span will grow to fill all space) ---
        echo '<span class="flex-grow-1 ms-2">'
            . esc_html($module->getName())
            . '</span>';

        // --- right‐aligned badges ---
        if ($module->isDeprecated())
        {
            echo '<span class="badge rounded-pill text-bg-warning ms-2">'
                . esc_html(__('Deprecated', 'content-egg'))
                . '</span>';
        }
        if ($module->isNew() && ! $module->isFeedParser())
        {
            echo '<span class="badge rounded-pill text-bg-info ms-2">'
                . esc_html(__('New', 'content-egg'))
                . '</span>';
        }

        echo '</a>';
    }
}

?>

<?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    <div class="cegg-maincol">
    <?php endif; ?>

    <div class="wrap">
        <div class="cegg5-container">
            <h2 class="h4 d-flex align-items-center justify-content-between mb-2 mt-4" style="height: 30px;">
                <span><?php esc_html_e('Module Settings', 'content-egg'); ?></span>

                <div class="d-flex align-items-center">

                    <!-- Export button -->
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="my-0 py-0">
                        <?php wp_nonce_field('cegg_export-module-settings'); ?>
                        <input type="hidden" name="page" value="content-egg-tools">
                        <input type="hidden" name="action" value="export-module-settings">

                        <button
                            type="submit"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                            aria-label="<?php esc_attr_e('Export module settings', 'content-egg'); ?>" aria-label="<?php esc_attr_e('Export module settings', 'content-egg'); ?>"
                            title="<?php esc_attr_e('Export module settings', 'content-egg'); ?>" aria-label="<?php esc_attr_e('Export module settings', 'content-egg'); ?>">
                            <i class="dashicons dashicons-migrate" aria-hidden="true"></i>
                            <span class="ms-1"><?php esc_html_e('Export', 'content-egg'); ?></span>
                        </button>
                    </form>

                    <!-- Import button -->
                    <form action="<?php echo esc_url(admin_url('admin.php')); ?>" method="post" enctype="multipart/form-data" class="ms-2 me-3 my-0 py-0">
                        <?php wp_nonce_field('cegg_import-module-settings'); ?>
                        <input type="hidden" name="page" value="content-egg-tools">
                        <input type="hidden" name="action" value="import-module-settings">

                        <label for="cegg-import-file" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center mb-0" title="<?php esc_attr_e('Import module settings', 'content-egg'); ?>">
                            <i class="dashicons dashicons-upload me-1" aria-hidden="true"></i><?php esc_html_e('Import', 'content-egg'); ?>
                        </label>

                        <input type="file" id="cegg-import-file" name="settings_file" accept=".json" required class="d-none" onchange="this.form.submit();" aria-label="<?php esc_attr_e('Choose JSON file to import', 'content-egg'); ?>">
                    </form>

                    <?php include __DIR__ . '/_version_badge.php'; ?>

                </div>
            </h2>

        </div>
        <h2 class="nav-tab-wrapper">
            <a href="?page=content-egg-modules" class="nav-tab<?php if (!empty($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) == 'content-egg-modules') echo ' nav-tab-active'; ?>">
                <span class="dashicons dashicons-menu-alt3"></span>
            </a>
            <?php foreach (ContentEgg\application\components\ModuleManager::getInstance()->getConfigurableModules(true) as $m) : ?>
                <?php if ($m->isDeprecated() && !$m->isActive()) continue; ?>
                <?php $c = $m->getConfigInstance(); ?>
                <a href="?page=<?php echo \esc_attr($c->page_slug()); ?>" class="nav-tab<?php if (!empty($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) == $c->page_slug()) echo ' nav-tab-active'; ?>">
                    <span<?php if ($m->isDeprecated()) : ?> style="color: darkgray;" <?php endif; ?>>
                        <?php echo \esc_html($m->getName()); ?>
                        </span>
                </a>
            <?php endforeach; ?>
        </h2>

        <div class="cegg5-container">

            <div class="row mt-4">
                <div class="col-md-4 col-xs-12">

                    <h3 class="h5"><?php esc_html_e('Product modules', 'content-egg'); ?></h3>
                    <div class="list-group list-group-flush">
                        <?php _cegg_print_module_item(\ContentEgg\application\helpers\AdminHelper::getProductModules()); ?>
                    </div>

                </div>
                <div class="col-md-4 col-xs-12">

                    <h3 class="h5 d-flex align-items-center justify-content-between">
                        <?php esc_html_e('Feed modules', 'content-egg'); ?>
                        <a href="<?php echo esc_url('https://ce-docs.keywordrush.com/modules/feed-modules'); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-muted small d-inline-flex align-items-center"
                            style="font-size: 13px;"
                            title="<?php esc_attr_e('View documentation', 'content-egg'); ?>">
                            <i class="bi bi-question-circle me-2"></i>
                        </a>
                    </h3>
                    <div class="list-group">
                        <?php _cegg_print_module_item(\ContentEgg\application\helpers\AdminHelper::getFeedProductModules()); ?>
                    </div>

                    <?php if (\ContentEgg\application\Plugin::isFree()) : ?>
                        <p class="description cegg-pro-notice small py-1 pt-2">
                            <?php
                            printf(
                                esc_html__('Free version supports up to 3 Feed modules. Upgrade to Pro for 50 modules and more features. %s', 'content-egg'),
                                '<a href="' . esc_url(\ContentEgg\application\Plugin::pluginPricingUrl('ce_feed_modules', 'feed_modules_notice')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Go PRO →', 'content-egg') . '</a>'
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                    <h3 class="h5 mt-4"><?php esc_html_e('Affiliate Egg modules', 'content-egg'); ?></h3>
                    <?php if ($modules = \ContentEgg\application\helpers\AdminHelper::getAeProductModules()) : ?>
                        <div class="list-group">
                            <?php _cegg_print_module_item($modules); ?>
                        </div>
                    <?php else: ?>
                        <p class="description cegg-pro-notice small py-1">
                            <?php
                            $activate_url = admin_url('admin.php?page=content-egg-ae-integration');
                            printf(
                                wp_kses(
                                    __('Affiliate Egg integration is not activated. <a href="%s">Activate&nbsp;&rarr;</a>', 'content-egg'),
                                    array('a' => array('href' => array()))
                                ),
                                esc_url($activate_url)
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                    <h3 class="h5 mt-4"><?php esc_html_e('Coupon modules', 'content-egg'); ?></h3>
                    <div class="list-group">
                        <?php _cegg_print_module_item(\ContentEgg\application\helpers\AdminHelper::getCouponModules()); ?>
                    </div>

                </div>

                <div class="col-md-4 col-xs-12">

                    <h3 class="h5"><?php esc_html_e('Content modules', 'content-egg'); ?></h3>
                    <div class="list-group">
                        <?php _cegg_print_module_item(\ContentEgg\application\helpers\AdminHelper::getContentModules()); ?>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    </div>
    <?php include('_promo_box.php'); ?>
<?php endif; ?>
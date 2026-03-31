<?php

use ContentEgg\application\components\ModuleCloneManager;

defined('\ABSPATH') || exit; ?>
<?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    <div class="cegg-maincol">
    <?php endif; ?>
    <div class="wrap">
        <div class="cegg5-container">
            <h2 class="h4 d-flex align-items-center justify-content-between mb-2 mt-4" style="height: 30px;">

                <span><?php esc_html_e('Module Settings', 'content-egg'); ?></span>
                <div class="d-flex align-items-center">
                    <?php include __DIR__ . '/_version_badge.php'; ?>
                </div>

            </h2>
        </div>
        <h2 class="nav-tab-wrapper">
            <a href="?page=content-egg-modules" class="nav-tab<?php if (!empty($_GET['page']) && $_GET['page'] == 'content-egg-modules') echo ' nav-tab-active'; ?>">
                <span class="dashicons dashicons-menu-alt3"></span>
            </a>
            <?php foreach (ContentEgg\application\components\ModuleManager::getInstance()->getConfigurableModules(true) as $m) : ?>
                <?php if ($m->isDeprecated() && !$m->isActive()) continue; ?>
                <?php $c = $m->getConfigInstance(); ?>
                <a href="?page=<?php echo \esc_attr($c->page_slug()); ?>" class="nav-tab<?php if (!empty($_GET['page']) && $_GET['page'] == $c->page_slug()) echo ' nav-tab-active'; ?>">
                    <span<?php if ($m->isDeprecated()) : ?> style="color: darkgray;" <?php endif; ?>>
                        <?php echo \esc_html($m->getName()); ?>
                        </span>
                </a>
            <?php endforeach; ?>
        </h2>

        <div class="cegg-wrap">
            <div class="cegg-maincol">
                <h3>
                    <?php if ($module->isFeedParser() && !$module->isActive()) : ?>
                        <?php esc_html_e('Add new feed module', 'content-egg'); ?>
                    <?php else : ?>
                        <?php echo \esc_html(sprintf(__('%s Settings', 'content-egg'), $module->getName())); ?>
                    <?php endif; ?>

                    <?php if ($docs_uri = $module->getDocsUri()) echo sprintf('<a target="_blank" class="page-title-action" href="%s">' . esc_html(__('Documentation', 'content-egg')) . '</a>', esc_url_raw($docs_uri)); ?>

                </h3>

                <?php if ($module->isDeprecated()) : ?>
                    <div class="cegg-warning">

                        <?php if ($module->getId() != 'Amazon' && $module->getId() != 'AmazonNoApi') : ?>
                            <strong>
                                <?php esc_html_e('WARNING:', 'content-egg'); ?>
                                <?php esc_html_e('This module is deprecated', 'content-egg'); ?>
                                (<a target="_blank" href="<?php echo esc_url_raw(\ContentEgg\application\Plugin::pluginDocsUrl()); ?>/modules/deprecatedmodules"><?php esc_html_e('what does this mean', 'content-egg'); ?></a>).
                            </strong>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <?php if (!empty($module) && $requirements = $module->requirements()) : ?>
                    <div class="cegg-warning">
                        <strong>
                            <?php echo esc_html_e('WARNING:', 'content-egg'); ?>
                            <?php esc_html_e('This module cannot be activated!', 'content-egg') ?>
                            <?php esc_html_e('Please fix the following error(s):', 'content-egg') ?>
                            <ul>
                                <li><?php echo wp_kses_post(join('</li><li>', $requirements)); ?></li>
                            </ul>

                        </strong>
                    </div>
                <?php endif; ?>

                <?php \settings_errors(); ?>
                <form action="options.php" method="POST">
                    <?php \settings_fields($config->page_slug()); ?>
                    <table class="form-table">
                        <?php \do_settings_sections($config->page_slug()); ?>
                    </table>
                    <?php \submit_button(); ?>
                </form>

            </div>

            <div class="cegg-rightcol">

                <pre><?php echo esc_html(__('Module ID:', 'content-egg')); ?> <?php echo esc_html($module->getId()); ?></pre>

                <div>

                    <?php if (ModuleCloneManager::isCloningAllowed($module->getId())): ?>
                        <hr style="margin-bottom: 20px;">

                        <a class="page-title-action" href="<?php echo esc_url_raw(
                                                                wp_nonce_url(
                                                                    get_admin_url(
                                                                        get_current_blog_id(),
                                                                        'admin.php?page=content-egg-modules&action=clone&module=' . urlencode($module->getId())
                                                                    ),
                                                                    'ce_clone_module_action'
                                                                )
                                                            ); ?>">
                            <?php esc_html_e('Clone This Module', 'content-egg'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($module->isFeedParser() || $module->isClone()): ?>
                        <hr style="margin-bottom: 20px;">
                        <a class="button-link-delete"
                            href="<?php echo esc_url_raw(
                                        wp_nonce_url(
                                            get_admin_url(
                                                get_current_blog_id(),
                                                'admin.php?page=content-egg-modules&action=delete_clone&module=' . urlencode($module->getId())
                                            ),
                                            'ce_remove_module_action'
                                        )
                                    ); ?>"
                            onclick="return confirm('Are you sure you want to delete this module? This action will PERMANENTLY REMOVE all module settings and associated products!');">
                            <?php esc_html_e('Delete This Module', 'content-egg'); ?>
                        </a>

                    <?php endif; ?>

                    <?php if (! empty($module) && $module->isFeedModule()) :
                        $last_import    = $module->getLastImportDateReadable();
                        $product_count  = (int) $module->getProductCount();
                        $last_error     = $module->getLastImportError();
                        $is_import_in_progress = $module->isImportInProgress();
                        $is_import_scheduled = $module->isImportScheduled();
                        $tools_page_url = admin_url('admin.php?page=content-egg-tools');
                    ?>

                        <ul class="ce-feed-info" style="margin-top:20px;">
                            <?php if ($last_import) : ?>
                                <li>
                                    <?php
                                    printf(
                                        esc_html__('Total products: %s', 'content-egg'),
                                        esc_html(number_format_i18n($product_count))
                                    );
                                    ?>

                                </li>
                                <li>
                                    <?php printf(
                                        esc_html__('Last feed sync: %s', 'content-egg'),
                                        esc_html($last_import)
                                    ); ?>

                                </li>

                            <?php endif; ?>

                            <?php if ($last_error) : ?>
                                <li class="error" style="color: red;"><?php printf(
                                                                            esc_html__('Last error: %s', 'content-egg'),
                                                                            esc_html($last_error)
                                                                        ); ?></li>
                            <?php endif; ?>
                        </ul>

                        <?php if ($is_import_in_progress) : ?>
                            <div class="notice notice-warning inline ce-feed-status">
                                <p>
                                    <strong><?php esc_html_e('Feed sync in progress.', 'content-egg'); ?></strong>
                                    <?php esc_html_e('Please wait until it completes.', 'content-egg'); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_import_scheduled) : ?>
                            <div class="notice notice-info inline ce-feed-status">
                                <p>
                                    <strong><?php esc_html_e('Feed sync scheduled.', 'content-egg'); ?></strong>
                                    <?php esc_html_e('It will run automatically soon.', 'content-egg'); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_import_in_progress || $is_import_scheduled) : ?>
                            <div class="ce-refresh-action" style="margin-top:20px;">
                                <button
                                    type="button"
                                    class="page-title-action"
                                    onclick="window.location.reload();">
                                    <?php esc_html_e('Refresh Feed Status', 'content-egg'); ?>
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Export Links -->
                        <?php if ($last_import && $product_count) : ?>
                            <hr />
                            <div class="ce-export-actions" style="margin-top:20px;">
                                <?php
                                $exports = [
                                    'url'             => __('Export product URLs', 'content-egg'),
                                    'ean'             => __('Export product EANs', 'content-egg'),
                                    'ean_duplicate'   => __('Export duplicate EANs', 'content-egg'),
                                ];

                                foreach ($exports as $field => $label) :
                                    $raw_url   = add_query_arg([
                                        'action' => 'feed-export',
                                        'field'  => $field,
                                        'module' => rawurlencode($module->getId()),
                                    ], $tools_page_url);
                                    $nonce_url = wp_nonce_url($raw_url, 'cegg_feed-export');
                                ?>
                                    <div>
                                        <a href="<?php echo esc_url($nonce_url); ?>" class="page-title-action" target="_blank" rel="noopener">
                                            <?php echo esc_html($label); ?>
                                        </a>
                                    </div>
                                    <br />
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Reset Feed Button -->
                        <?php if ($last_import && !$is_import_in_progress) : ?>
                            <div class="ce-reset-action">
                                <?php
                                $reset_raw   = add_query_arg([
                                    'action' => 'feed-reset',
                                    'module' => rawurlencode($module->getId()),
                                ], $tools_page_url);
                                $reset_url   = wp_nonce_url($reset_raw, 'cegg_feed-reset');
                                ?>
                                <a href="<?php echo esc_url($reset_url); ?>" class="page-title-action" rel="noopener">
                                    <?php esc_html_e('Reload Feed Data Now', 'content-egg'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>

    <?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    </div>
    <?php include('_promo_box.php'); ?>
<?php endif; ?>
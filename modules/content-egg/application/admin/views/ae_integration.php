<?php defined('\ABSPATH') || exit; ?>
<?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    <div class="cegg-maincol">
    <?php endif; ?>
    <div class="wrap cegg5-container">
        <h1 class="h3">
            <?php esc_html_e('Affiliate Egg Integration', 'content-egg') ?>
        </h1>
        <?php settings_errors(); ?>

        <?php if (!\ContentEgg\application\admin\AeIntegrationConfig::isAEIntegrationPosible()) : ?>
            <div class="card border-info mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <?php esc_html_e('Affiliate Egg Integration', 'content-egg'); ?>
                    </h5>

                    <p class="card-text">
                        <?php
                        // Translators: %1$s = Affiliate Egg URL
                        printf(
                            wp_kses(
                                __('<a href="%1$s" target="_blank" rel="noopener">Affiliate Egg</a> is another plugin offered by our team for adding affiliate products to your website. The key advantages of Affiliate Egg include:', 'content-egg'),
                                ['a' => ['href' => [], 'target' => [], 'rel' => []]]
                            ),
                            esc_url('https://www.keywordrush.com/affiliateegg?utm_source=cegg&utm_medium=referral&utm_campaign=ce_ae_integration&utm_content=ae_integration_link')
                        );
                        ?>
                    </p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item">
                            <?php esc_html_e('No API access required. The plugin extracts data directly from store websites.', 'content-egg'); ?>
                        </li>
                        <li class="list-group-item">
                            <?php esc_html_e('Custom parsers can be created for nearly any store.', 'content-egg'); ?>
                        </li>
                        <li class="list-group-item">
                            <?php esc_html_e('Parsers integrate seamlessly as separate modules within Content Egg for price updates, comparisons, templates, and other advanced features.', 'content-egg'); ?>
                        </li>
                    </ul>

                    <p class="card-text">
                        <?php esc_html_e('Enable these features by activating Affiliate Egg modules within Content Egg.', 'content-egg'); ?>

                    </p>

                    <a
                        href="<?php echo esc_url('https://www.keywordrush.com/affiliateegg?utm_source=cegg&utm_medium=referral&utm_campaign=ce_ae_integration&utm_content=ae_integration_link'); ?>"
                        target="_blank"
                        rel="noopener"
                        class="btn btn-outline-primary">
                        <?php esc_html_e('Read more...', 'content-egg'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!ContentEgg\application\admin\AeIntegrationConfig::isAEIntegrationPosible()) : ?>
            <div>
                <b><?php esc_html_e('Follow these steps to get started', 'content-egg'); ?>:</b>
                <ol>
                    <li>
                        <?php
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: URL to Affiliate Egg Pro website */
                                __('Install and activate <a target="_blank" href="%s">Affiliate Egg Pro</a>', 'content-egg'),
                                esc_url('https://www.keywordrush.com/affiliateegg')
                            ),
                            array('a' => array('href' => array(), 'target' => array()))
                        );
                        ?>
                    </li>
                </ol>
            </div>
        <?php else : ?>
            <form action="options.php" method="POST">
                <?php settings_fields($page_slug); ?>
                <table class="form-table">
                    <?php \do_settings_fields($page_slug, 'default'); ?>
                </table>
                <?php submit_button(); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php if (\ContentEgg\application\Plugin::isInactiveEnvato()) : ?>
    </div>
    <?php include('_promo_box.php'); ?>
<?php endif; ?>
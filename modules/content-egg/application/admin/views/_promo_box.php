<?php defined('\ABSPATH') || exit; ?>
<div class="cegg-rightcol">

    <?php if (\ContentEgg\application\Plugin::isFree()) : ?>
        <div class="cegg-box" style="margin-top: 95px;">
            <div class="cegg-box-container">
                <a target="_blank" href="https://www.keywordrush.com/bundles?ref=BUNDLE25&utm_source=cegg&utm_medium=referral&utm_campaign=bundle">
                    <img title="Limited-Time Exclusive: Save Big on All Plugin Bundles!" alt="Limited-Time Exclusive: Save Big on All Plugin Bundles!" width="100%" src="<?php echo esc_attr(\ContentEgg\PLUGIN_RES); ?>/img/bundle25.webp">
                </a>
            </div>
        </div>

    <?php endif; ?>

    <?php if (\ContentEgg\application\Plugin::isEnvato()) : ?>
        <?php
        $url = \ContentEgg\application\Plugin::pluginPricingUrl('ce_activation_sidebar', 'envato_box');
        ?>
        <div class="cegg-box" style="margin-top: 95px; padding: 25px 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
            <h2 style="margin-bottom: 10px;"><?php esc_html_e('Activate Your Plugin', 'content-egg'); ?></h2>
            <p style="margin-bottom: 20px;">
                <?php esc_html_e('Activate your license to enjoy premium features, automatic updates, and dedicated official support.', 'content-egg'); ?>
            </p>

            <!-- Primary action -->
            <p style="margin-bottom: 15px;">
                <a class="button button-primary" href="<?php echo esc_url(get_admin_url(get_current_blog_id(), 'admin.php?page=content-egg-lic')); ?>">
                    <?php esc_html_e('Activate Now', 'content-egg'); ?>
                </a>
            </p>

            <!-- Secondary actions -->
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;">
                <a class="button button-secondary" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Buy License', 'content-egg'); ?>
                </a>
                <a class="button button-secondary" href="https://www.keywordrush.com/bundles?utm_source=cepro&utm_medium=referral&utm_campaign=ce_activation_sidebar&utm_content=envato_bundle_box" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Bundle Offers', 'content-egg'); ?>
                </a>
            </div>
        </div>

    <?php endif; ?>

</div>
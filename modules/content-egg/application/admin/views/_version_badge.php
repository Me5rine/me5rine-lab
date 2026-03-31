<div class="d-flex align-items-center gap-2">
    <?php if (! \ContentEgg\application\Plugin::isPro()) : ?>
        <a
            href="<?php echo esc_url(\ContentEgg\application\Plugin::pluginLandingUrl('ce_version_button', 'go_pro_button')); ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-sm me-3 btn-outline-success cegg-go-pro-link">
            <?php esc_html_e('Go PRO', 'content-egg'); ?>
            <span class="dashicons dashicons-external" aria-hidden="true"></span>
        </a>
    <?php endif; ?>

    <span class="badge <?php echo \ContentEgg\application\Plugin::isPro() ? 'bg-dark' : 'bg-secondary'; ?> text-light py-1 px-2 fs-6">
        <?php echo \ContentEgg\application\Plugin::isPro()
            ? esc_html__('Pro', 'content-egg')
            : esc_html__('Free', 'content-egg'); ?>
        <small class="ms-1 small" aria-label="<?php esc_attr_e('Version', 'content-egg'); ?>">
            <?php printf(esc_html__('v%s', 'content-egg'), esc_html(\ContentEgg\application\Plugin::version())); ?>

            <?php if (\ContentEgg\application\Plugin::isPro() && \ContentEgg\application\components\LManager::getInstance()->isExpired()) : ?>
                <?php
                $purchase_uri = '/product/purchase/1017';
                $renew_url   = \ContentEgg\application\Plugin::website . '/login?return=' . urlencode($purchase_uri);
                ?>
                <a
                    href="<?php echo esc_url($renew_url); ?>"
                    target="_blank"
                    rel="noopener"
                    class="text-decoration-underline">
                    <span
                        class="badge text-bg-warning ms-2 py-0 px-1 fs-7"
                        style="font-size:0.95rem;"
                        title="<?php esc_attr_e('Renew now', 'content-egg'); ?>">
                        <?php esc_html_e('Expired', 'content-egg'); ?>
                    </span>
                </a>

            <?php endif; ?>
        </small>
    </span>
</div>
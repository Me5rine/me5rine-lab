<?php
defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TemplateHelper;

?>

<div class="cegg-list-card cegg-card<?php echo $i < count($items) - 1 ? ' border-bottom-0' : ''; ?><?php TemplateHelper::border($params); ?>">

    <?php if ($this->isVisible('number', false)): ?>
        <div class="position-absolute top-50 z-5 start-0 translate-middle">
            <?php TemplateHelper::number($item, $params, $i, 'outline-secondary'); ?>
        </div>
    <?php endif; ?>

    <div class="row align-items-center p-3<?php TemplateHelper::conditionClass($this->isVisible('new_used_price'), 'pb-1', '') ?>">

        <?php if ($this->isVisible('logo')): ?>
            <div class="col-4 col-md-2  text-center<?php TemplateHelper::colsOrder($params, 1, '', true); ?>" style="max-width: 130px;">
                <?php if ($this->isVisible('logo')): ?>
                    <div class="px-2">
                        <div class="ratio<?php TemplateHelper::imgRatio($params, 'ratio-16x9'); ?>">
                            <?php TemplateHelper::logo($item, $params, 'object-fit-scale'); ?>
                        </div>
                    </div>
                    <?php if (!TemplateHelper::getMerchantLogoUrl($item) && $this->isVisible('merchant') && !$this->isVisible('shop_info')): ?>
                        <div class="cegg-merchant fs-6 text-body-secondary text-truncate">
                            <small><?php TemplateHelper::merchant($item); ?></small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($this->isVisible('title')): ?>
            <div class="cegg-store-logos-col col-12 col-md order-first <?php TemplateHelper::colsOrder($params, 2, 'md', true); ?>">
                <div class="cegg-list-card-body">

                    <?php if ($this->isVisible('badge')): ?>
                        <?php TemplateHelper::badge3($item); ?>
                    <?php endif; ?>

                    <?php TemplateHelper::title($item, 'card-title fs-6 fw-normal cegg-text-truncate-responsive', 'div', $params); ?>

                    <?php if ($this->isVisible('subtitle')): ?>
                        <div class="card-subtitle fs-6 text-body-secondary cegg-text-truncate-responsive"><?php TemplateHelper::subtitle($item); ?></div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('rating', false)): ?>
                        <div class="fs-5">
                            <?php TemplateHelper::ratingStars($item, true); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('promo')): ?>
                        <div class="cegg-card-promo text-success small">
                            <?php TemplateHelper::promo($item); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('description', false)): ?>
                        <div class="cegg-desc-small card-text small lh-sm  pt-2"><?php echo \wp_kses_post($item['description']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->isVisible('price')): ?>
            <div class="col<?php if ($this->isVisible('title')) echo esc_attr(' col-md-2'); ?> text-center<?php TemplateHelper::colsOrder($params, 3, '', true); ?>">

                <?php if ($this->isVisible('percentageSaved', false)): ?>
                    <div class="badge bg-danger">-<?php echo esc_html($item['percentageSaved']); ?>%</div>
                <?php endif; ?>

                <div class="cegg-card-price lh-1">

                    <div class="cegg-price text-nowrap fs-5 lh-1 mb-0<?php TemplateHelper::priceClass($item); ?>">
                        <?php TemplateHelper::price($item); ?>
                    </div>

                    <?php if ($this->isVisible('priceOld')): ?>
                        <del class="cegg-old-price fs-6 text-body-tertiary fw-normal"><?php TemplateHelper::oldPrice($item); ?></del>
                    <?php endif; ?>

                    <?php if ($this->isVisible('shipping_cost', false)): ?>
                        <div class="cegg-shipping-cost pt-2 small text-body-secondary">
                            <?php TemplateHelper::shippingCost($item); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('stock_status', true)): ?>
                        <div class="cegg-stock-status pt-2 small">
                            <?php TemplateHelper::stockStatus($item); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('prime', false)): ?>
                        <div class="pt-2 small">
                            <?php TemplateHelper::prime($item); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

        <div class="col col-md text-center align-items-center<?php TemplateHelper::colsOrder($params, 4, '', true); ?>" style="max-width: 200px;">
            <?php if ($this->isVisible('button')): ?>
                <div class="d-grid gap-2">
                    <?php TemplateHelper::button($item, $params, array('class' => 'stretched-link d-block w-100')); ?>
                </div>
            <?php else: ?>
                <?php TemplateHelper::link(' ', $item, $params, array('class' => 'stretched-link me-0')); ?>
            <?php endif; ?>
            <?php if ($this->isVisible('shop_info')) : ?>
                <div class="position-relative fs-6 z-3 small text-truncate">
                    <small><?php TemplateHelper::shopInfo($item); ?></small>
                </div>
            <?php elseif ($this->isVisible('merchant')): ?>
                <div class="cegg-merchant small fs-6 text-body-secondary text-truncate">
                    <small><?php TemplateHelper::merchant($item); ?></small>
                </div>
            <?php endif; ?>
            <?php if ($this->isVisible('coupons', false)) : ?>
                <div class="position-relative fs-6 z-3 small text-truncate">
                    <small><?php TemplateHelper::coupons($item); ?></small>
                </div>
            <?php endif; ?>

        </div>

        <?php
        if ($this->isVisible('new_used_price')): ?>
            <div class="col-12 order-last">
                <div class="small text-body-secondary">
                    <small>
                        <?php TemplateHelper::newUsedPrice($item, ', '); ?>
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>
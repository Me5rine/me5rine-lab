<?php
defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TemplateHelper;

?>

<div class="cegg-item-card cegg-card <?php TemplateHelper::border($params, 'border'); ?>">

    <?php if ($this->isVisible('number', false)): ?>
        <div class="position-absolute top-50 z-3 start-0 translate-middle">
            <?php TemplateHelper::number($item, $params, $i); ?>
        </div>
    <?php endif; ?>

    <?php $badge_position = (TemplateHelper::getColOrder($params, 1) == 1) ? 'left' : 'right'; ?>
    <?php if ($this->isVisible('badge')) TemplateHelper::badge1($item, $params, $badge_position); ?>

    <div class="row p-3">
        <div class="cegg-item-card-img-col <?php TemplateHelper::conditionClass(TemplateHelper::getColOrder($params, 1) == 1, 'col-md-6', 'col-md-4'); ?> position-relative<?php TemplateHelper::colsOrder($params, 1, 'md'); ?>" style="max-width: 400px;">

            <?php if ($this->isVisible('img')): ?>
                <div class="position-relative">

                    <?php if ($this->isVisible('percentageSaved', false)): ?>
                        <div class="badge bg-danger rounded-1 position-absolute bottom-0 end-0 z-3">-<?php echo esc_html($item['percentageSaved']); ?>%</div>
                    <?php endif; ?>

                    <?php TemplateHelper::openATag($item); ?>
                    <div class="ratio<?php TemplateHelper::imgRatio($params, 'ratio-1x1'); ?>">
                        <?php TemplateHelper::displayImage($item, 350, 350, array('class' => 'object-fit-scale rounded')); ?>
                    </div>
                    <?php TemplateHelper::closeATag(); ?>

                </div>
            <?php endif; ?>

        </div>

        <div class="col<?php TemplateHelper::conditionClass(TemplateHelper::getColOrder($params, 2) == 1, 'ps-xl-5', 'ps-xl-3'); ?><?php TemplateHelper::colsOrder($params, 2, 'md'); ?>">

            <?php if ($this->isVisible('title')): ?>
                <?php TemplateHelper::title($item, 'card-title h4 fw-normal  cegg-text-truncate-2 pt-2 pt-md-3', 'h3', $params); ?>
            <?php else: ?>
                <div class="mb-5"></div>
            <?php endif; ?>
            <?php if ($this->isVisible('subtitle')): ?>
                <div class="cegg-item-subtitle fs-6 text-body-secondary cegg-text-truncate-2"><?php TemplateHelper::subtitle($item); ?></div>
            <?php endif; ?>

            <?php if ($this->isVisible('rating')): ?>
                <div class="pt-0 fs-4">
                    <?php TemplateHelper::ratingStars($item, true); ?>
                </div>
            <?php endif; ?>

            <?php if ($this->isVisible('price')): ?>
                <div class="cegg-card-price lh-1 mt-3 mb-2 hstack gap-4">

                    <div>
                        <span class="cegg-price fs-4<?php TemplateHelper::priceClass($item); ?>">
                            <?php TemplateHelper::price($item); ?>
                        </span>
                        <?php if ($this->isVisible('priceOld')): ?>
                            <del class="cegg-old-price text-body-tertiary fs-5 fw-normal"><?php TemplateHelper::oldPrice($item); ?></del>
                        <?php endif; ?>
                    </div>

                    <?php if ($this->isVisible('prime', true)): ?>
                        <span class="fs-6 mt-1">
                            <?php TemplateHelper::prime($item); ?>
                        </span>
                    <?php endif; ?>

                </div>

                <?php if ($this->isVisible('stock_status', true)): ?>
                    <div class="cegg-stock-status small">
                        <?php TemplateHelper::stockStatus($item); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($this->isVisible('promo')): ?>
                <div class="cegg-card-promo text-success small pt-1">
                    <?php TemplateHelper::promo($item); ?>
                </div>
            <?php endif; ?>

            <?php if ($this->isVisible('cashback')): ?>
                <div class="cegg-card-cashback text-success small pt-1">
                    <?php TemplateHelper::cashback($item); ?>
                </div>
            <?php endif; ?>

            <?php if ($this->isVisible('button')): ?>
                <div class="cegg-card-button p-0 pt-3 d-grid col-sm-12 col-md-8">

                    <?php TemplateHelper::button($item, $params); ?>
                </div>
                <div class="clearfix"></div>

            <?php endif; ?>

            <?php if ($this->isVisible('merchant')): ?>
                <div class="cegg-merchant fs-6 text-body-secondary pt-2">

                    <?php if ($this->isVisible('shop_info')) : ?>
                        <?php TemplateHelper::shopInfo($item); ?>
                    <?php else: ?>
                        <span class="text-truncate"><?php TemplateHelper::merchant($item); ?></span>
                    <?php endif; ?>

                    <?php if ($this->isVisible('coupons')) : ?>
                        <span class="ps-3">
                            <?php TemplateHelper::coupons($item); ?>
                        </span>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
            <?php if ($this->isVisible('description', false)): ?>
                <div class="cegg-desc-small small lh-sm pt-3"><?php TemplateHelper::description($item); ?></div>
            <?php endif; ?>

        </div>
    </div>
</div>
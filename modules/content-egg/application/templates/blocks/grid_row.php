<?php
defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TemplateHelper;

?>

<div class="col">
    <div class="cegg-grid-card cegg-card h-100 p-3<?php TemplateHelper::border($params); ?>">

        <?php if ($this->isVisible('number', false)): ?>
            <div class="position-absolute z-3 top-0 start-50 translate-middle translate-middle">
                <?php TemplateHelper::number($item, $params, $i, 'outline-primary'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->isVisible('badge')) TemplateHelper::badge2($item); ?>

        <?php if ($this->isVisible('percentageSaved')): ?>
            <div class="badge bg-danger rounded-1 position-absolute top-0 end-0 z-3 mt-1 me-2 mt-lg-2 me-lg-2">-<?php echo esc_html($item['percentageSaved']); ?>%</div>
        <?php endif; ?>

        <?php if ($this->isVisible('img')): ?>
            <?php
            if ($params['cols_xs'] == 1)
                $default_ratio = 'ratio-16x9';
            elseif ($params['cols'] == 2)
                $default_ratio = 'ratio-16x9';
            else
                $default_ratio = 'ratio-1x1';

            ?>
            <div class="ratio<?php TemplateHelper::imgRatio($params, $default_ratio); ?>">
                <?php TemplateHelper::displayImage($item, 190, 170, array('class' => 'card-img-top object-fit-scale rounded')); ?>
            </div>
        <?php endif; ?>

        <div class="card-body p-0 mt-2">
            <?php if ($this->isVisible('shop_info')) : ?>
                <div class="position-relative fs-6 z-3 small text-truncate">
                    <small><?php TemplateHelper::shopInfo($item); ?></small>
                </div>
            <?php elseif ($this->isVisible('merchant')): ?>
                <div class="cegg-merchant small fs-6 text-body-secondary text-truncate">
                    <small><?php TemplateHelper::merchant($item); ?></small>
                </div>
            <?php endif; ?>

            <?php if ($this->isVisible('rating')): ?>
                <div class="pt-0 fs-5">
                    <?php TemplateHelper::ratingStars($item, true); ?>
                </div>
            <?php endif; ?>

            <?php if ($this->isVisible('price')): ?>

                <div class="cegg-card-price lh-1 pt-3 pb-2">

                    <div class="hstack gap-3">

                        <div>
                            <span class="cegg-price fs-5 lh-1 mb-0<?php TemplateHelper::priceClass($item); ?>">
                                <?php TemplateHelper::price($item); ?>
                            </span>

                            <?php if ($this->isVisible('priceOld')): ?>
                                <del class="cegg-old-price fs-6 text-body-tertiary fw-normal me-1"><?php TemplateHelper::oldPrice($item); ?></del>
                            <?php endif; ?>
                        </div>

                        <?php if ($this->isVisible('prime', true)): ?>
                            <div class="pt-2 pt-md-0 small">
                                <?php TemplateHelper::prime($item); ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <?php if ($this->isVisible('stock_status', false)): ?>
                        <div class="cegg-stock-status pt-2 small">
                            <?php TemplateHelper::stockStatus($item); ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <?php if ($this->isVisible('promo')): ?>
                <div class="cegg-card-promo text-success small pt-1">
                    <?php TemplateHelper::promo($item); ?>
                </div>
            <?php endif; ?>

            <?php if ($this->isVisible('title')): ?>
                <?php TemplateHelper::title($item, 'card-title fs-6 fw-normal lh-base cegg-hover-title cegg-text-truncate-2 pt-2', 'div', $params); ?>
            <?php endif; ?>

            <?php if ($this->isVisible('subtitle')): ?>
                <div class="card-subtitle fs-6 text-body-secondary cegg-text-truncate-2"><?php TemplateHelper::subtitle($item); ?></div>
            <?php endif; ?>

            <?php if ($this->isVisible('description', false)): ?>
                <div class="cegg-desc-small card-text small lh-sm pt-3"><?php echo \wp_kses_post($item['description']); ?></div>
            <?php endif; ?>

        </div>
        <?php if ($this->isVisible('button')): ?>
            <div class="cegg-card-button pt-3">
                <div class="d-grid">
                    <?php TemplateHelper::button($item, $params, array('class' => 'stretched-link')); ?>
                </div>
            </div>
        <?php else: ?>
            <?php TemplateHelper::link(' ', $item, $params, array('class' => 'stretched-link')); ?>
        <?php endif; ?>

    </div>
</div>
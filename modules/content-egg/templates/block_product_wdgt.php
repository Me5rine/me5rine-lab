<?php
/*
 * Name: Product widget
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

if (!$params['cols_xs'])
    $params['cols_xs'] = 1;
if (!$params['border'])
    $params['border'] = 0;

$this->setParams($params);
TemplateHelper::addShopInfoOffcanvases($items, $params);

?>

<div class="container px-0 mb-4 mt-1" <?php $this->colorMode(); ?>>
    <div class="row g-3<?php TemplateHelper::rowCols($params, 'row-cols-1'); ?>">
        <?php foreach ($items as $i => $item): ?>

            <?php $this->setItem($item, $i); ?>

            <div class="col">
                <div class="cegg-card h-100 text-center p-3<?php TemplateHelper::border($params); ?>">

                    <?php if ($this->isVisible('title')): ?>
                        <?php TemplateHelper::title($item, 'card-title h5 lh-base cegg-hover-title cegg-text-truncate-2 mb-4 align-self-center', 'div', $params); ?>
                    <?php endif; ?>

                    <?php if ($this->isVisible('number', false)): ?>
                        <div class="position-absolute z-3 top-0 start-50 translate-middle translate-middle">
                            <?php TemplateHelper::number($item, $params, $i, 'outline-primary'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('badge')) TemplateHelper::badge2($item); ?>

                    <?php if ($this->isVisible('img')): ?>

                        <div class="ratio<?php TemplateHelper::imgRatio($params, 'ratio-4x3'); ?>">
                            <?php TemplateHelper::displayImage($item, 190, 170, array('class' => 'card-img-top object-fit-scale rounded')); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card-body p-0">

                        <?php if ($this->isVisible('rating', false)): ?>
                            <div class="pt-0 fs-5">
                                <?php TemplateHelper::ratingStars($item, true); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('price')): ?>

                            <div class="cegg-card-price lh-1 pt-3 pb-2 text-center">

                                <div class="gap-3 mt-2 mb-2">

                                    <span class="cegg-price fs-5 lh-1 mb-0<?php TemplateHelper::priceClass($item); ?>">
                                        <?php TemplateHelper::price($item); ?>
                                    </span>

                                    <?php if ($this->isVisible('prime', false)): ?>
                                        <div class="pt-2 pt-md-0 small">
                                            <?php TemplateHelper::prime($item); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($this->isVisible('stock_status', false)): ?>
                                        <div class="cegg-stock-status pt-2 small">
                                            <?php TemplateHelper::stockStatus($item); ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                                <div class="fs-6 fw-normal">

                                    <?php if ($this->isVisible('priceOld')): ?>
                                        <del class="cegg-old-price text-body-tertiary me-1"><?php TemplateHelper::oldPrice($item); ?></del>
                                    <?php endif; ?>

                                    <?php if ($this->isVisible('percentageSaved')): ?>
                                        <span class="text-danger">-<?php echo esc_html($item['percentageSaved']); ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('promo')): ?>
                            <div class="cegg-card-promo text-success small pt-1">
                                <?php TemplateHelper::promo($item); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('subtitle')): ?>
                            <div class="card-subtitle align-self-center fs-6 text-body-secondary cegg-text-truncate-2"><?php TemplateHelper::subtitle($item); ?></div>
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
                    <?php if ($this->isVisible('shop_info')) : ?>
                        <div class="fs-6 z-3 small text-truncate align-self-center mt-1">
                            <small><?php TemplateHelper::shopInfo($item); ?></small>
                        </div>
                    <?php elseif ($this->isVisible('merchant')): ?>
                        <div class="cegg-merchant small fs-6 text-body-secondary text-truncate align-self-center mt-1">
                            <small><?php TemplateHelper::merchant($item); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

    <?php if ($this->isVisibleDisclaimerOrPriceUpdate()): ?>
        <div class="row mt-1 g-2 fst-italic text-body-secondary lh-1">
            <?php if ($this->isVisible('price_update')): ?>
                <div class="col cegg-price-disclaimer">
                    <small><?php TemplateHelper::priceUpdateAmazon($items); ?></small>
                </div>
            <?php endif; ?>
            <?php if ($this->isVisible('disclaimer')): ?>
                <div class="col-12 cegg-block-disclaimer">
                    <small><?php TemplateHelper::disclaimer(); ?></small>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
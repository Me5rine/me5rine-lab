<?php
/*
 * Name: List
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

TemplateHelper::addShopInfoOffcanvases($items, $params);

?>
<div class="container px-0 mb-5 mt-1 cegg-list" <?php $this->colorMode(); ?>>
    <?php foreach ($items as $i => $item): ?>
        <?php $this->setItem($item, $i); ?>

        <div class="cegg-list-card cegg-card <?php if (!$is_shortcode): ?> mt-4<?php endif; ?>  <?php echo $i < count($items) - 1 ? ' mb-3' : ''; ?><?php TemplateHelper::border($params); ?>">

            <?php if ($this->isVisible('number', true)): ?>
                <div class="position-absolute z-3 translate-middle <?php if ($is_shortcode): ?> top-50 start-0<?php else: ?> top-0 start-50<?php endif; ?>">
                    <?php TemplateHelper::number($item, $params, $i, 'danger'); ?>
                </div>
            <?php endif; ?>

            <div class="row p-2 p-md-3">

                <div class="col-3 <?php if ($is_shortcode): ?> col-md-2<?php endif; ?> align-self-center">

                    <?php if ($this->isVisible('img')): ?>
                        <div class="position-relative">

                            <div class="ratio<?php TemplateHelper::imgRatio($params, 'ratio-1x1'); ?>">
                                <?php TemplateHelper::displayImage($item, 190, 170, array('class' => 'object-fit-scale rounded')); ?>
                            </div>

                        </div>

                    <?php endif; ?>

                </div>
                <div class="col align-self-center">
                    <div class="cegg-list-card-body">

                        <?php if ($this->isVisible('badge', false)): ?>
                            <?php TemplateHelper::badge3($item); ?>
                        <?php endif; ?>

                        <?php if ($this->isVisible('title')): ?>
                            <?php TemplateHelper::title($item, 'card-title fs-6 fw-normal cegg-text-truncate-2', 'div', $params); ?>
                        <?php endif; ?>

                        <?php if ($this->isVisible('subtitle', $is_shortcode)): ?>
                            <div class="card-subtitle fs-6 text-body-secondary cegg-text-truncate-2"><?php TemplateHelper::subtitle($item); ?></div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('promo', false)): ?>
                            <div class="cegg-card-promo text-success small pt-1">
                                <?php TemplateHelper::promo($item); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('description', false)): ?>
                            <div class="cegg-desc-small card-text small lh-sm  pt-3"><?php echo \wp_kses_post($item['description']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-auto <?php if ($is_shortcode): ?> px-md-3<?php endif; ?> text-end align-self-center">

                    <?php if ($item['price']): ?>

                        <?php if ($item['_price_movers']['discount_value']): ?>
                            <div class="cegg-old-price fs-6 text-body-tertiary fw-normal me-1"><s title="<?php echo \esc_attr(TemplateHelper::getDaysAgo($item['_price_movers']['price_old_date'])); ?>"><?php echo esc_html(TemplateHelper::formatPriceCurrency($item['_price_movers']['price_old'], $item['currencyCode'])); ?></s></div>
                        <?php endif; ?>
                        <span class="cegg-card-price fs-5 lh-1 mb-0"" title=" <?php echo \esc_attr(sprintf(TemplateHelper::__('as of %s'), TemplateHelper::dateFormatFromGmt($item['last_update']))); ?>"><?php echo esc_html(TemplateHelper::formatPriceCurrency($item['price'], $item['currencyCode'])); ?></span>

                    <?php endif; ?>

                    <?php if ($item['_price_movers']['discount_value'] > 0): ?>
                        <div class="text-success cegg-discount-value fs-6">
                            &#9660;<?php echo esc_html(TemplateHelper::formatPriceCurrency($item['_price_movers']['discount_value'], $item['currencyCode'])); ?>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="col-6 align-self-center offset-3 <?php if ($is_shortcode): ?> col-md-3 offset-md-0<?php endif; ?>  pe-3 text-center">

                    <?php if ($item['_price_movers']['discount_percent'] > 0): ?>
                        <div class="text-danger fw-bolder">
                            <span class="fs-5"><?php echo esc_html($item['_price_movers']['discount_percent']); ?>%</span>
                            <span class="fs-6">
                                <?php TemplateHelper::esc_html_e('OFF'); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('button', $is_shortcode)): ?>
                        <div class="cegg-card-button pt-1">
                            <div class="d-grid"> <?php TemplateHelper::button($item, $params, array('class' => 'stretched-link')); ?></div>
                        </div>
                    <?php else: ?>
                        <?php TemplateHelper::link(' ', $item, $params, array('class' => 'stretched-link')); ?>
                    <?php endif; ?>
                    <?php if ($this->isVisible('shop_info', $is_shortcode)) : ?>
                        <div class="position-relative fs-6 z-3 small text-truncate">
                            <small><?php TemplateHelper::shopInfo($item); ?></small>
                        </div>
                    <?php elseif ($this->isVisible('merchant')): ?>
                        <div class="cegg-merchant small fs-6 text-body-secondary text-truncate">
                            <small><?php TemplateHelper::merchant($item); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php endforeach; ?>

</div>
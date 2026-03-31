<?php
/*
 * Name: Grid
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

if (!$is_shortcode)
    $params['cols_xs'] = 1;

?>
<div class="container px-0 mb-5 pt-2" <?php $this->colorMode(); ?>>

    <div class="row g-3 <?php if ($is_shortcode): ?> row-gap-2<?php endif; ?><?php TemplateHelper::rowCols($params, 'row-cols-2 row-cols-md-3'); ?>">

        <?php foreach ($items as $i => $item): ?>
            <?php $this->setItem($item, $i); ?>
            <div class="col">
                <div class="cegg-grid-card cegg-card h-100 p-3<?php TemplateHelper::border($params); ?>">

                    <?php if ($this->isVisible('number')): ?>
                        <div class="position-absolute z-3 <?php if ($is_shortcode): ?> translate-middle top-0 start-50 <?php else: ?> top-0 start-0 pt-2 ps-2<?php endif; ?>">

                            <?php TemplateHelper::number($item, $params, $i, 'danger'); ?>
                        </div>
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

                    <div class="card-body p-0">
                        <?php if ($this->isVisible('merchant')): ?>
                            <div class="cegg-merchant small fs-6 text-body-secondary text-truncate">
                                <small><?php TemplateHelper::merchant($item); ?></small>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('rating')): ?>
                            <div class="pt-0 fs-5">
                                <?php TemplateHelper::ratingStars($item, true); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('title')): ?>
                            <?php TemplateHelper::title($item, 'card-title fs-6 fw-normal lh-base cegg-hover-title cegg-text-truncate-2 pt-2', 'div', $params); ?>
                        <?php endif; ?>

                        <?php if ($this->isVisible('subtitle', false)): ?>
                            <div class="card-subtitle fs-6 text-body-secondary cegg-text-truncate-2"><?php TemplateHelper::subtitle($item); ?></div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('description', false)): ?>
                            <div class="cegg-desc-small card-text small lh-sm pt-3"><?php echo \wp_kses_post($item['description']); ?></div>
                        <?php endif; ?>

                    </div>

                    <div class="row">
                        <?php if ($item['_price_movers']['discount_percent'] > 0): ?>
                            <div class="col-auto lh-1 text-danger fw-bolder">
                                <span class="fs-5"><?php echo esc_html($item['_price_movers']['discount_percent']); ?>%</span>
                                <div class="fs-6">
                                    <?php TemplateHelper::esc_html_e('OFF'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col lh-1">

                            <?php if ($item['price']): ?>

                                <?php if ($item['_price_movers']['discount_value']): ?>
                                    <div class="cegg-old-price fs-6 text-body-tertiary fw-normal me-1"><s title="<?php echo \esc_attr(TemplateHelper::getDaysAgo($item['_price_movers']['price_old_date'])); ?>"><?php echo esc_html(TemplateHelper::formatPriceCurrency($item['_price_movers']['price_old'], $item['currencyCode'])); ?></s></div>
                                <?php endif; ?>
                                <span class="cegg-card-price fs-5 lh-1 mb-0" title="<?php echo \esc_attr(sprintf(TemplateHelper::__('as of %s'), TemplateHelper::dateFormatFromGmt($item['last_update']))); ?>"><?php echo esc_html(TemplateHelper::formatPriceCurrency($item['price'], $item['currencyCode'])); ?></span>

                            <?php endif; ?>

                            <?php if ($item['_price_movers']['discount_value'] > 0): ?>
                                <span class="text-success cegg-discount-value ms-1 fs-6 lh-1">
                                    &#9660;<?php echo esc_html(TemplateHelper::formatPriceCurrency($item['_price_movers']['discount_value'], $item['currencyCode'])); ?>
                                </span>
                            <?php endif; ?>

                        </div>
                    </div>

                    <?php if ($this->isVisible('button', false)): ?>
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
        <?php endforeach; ?>
    </div>
</div>
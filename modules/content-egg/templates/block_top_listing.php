<?php
/*
 * Name: Top listings
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;
use ContentEgg\application\helpers\ArrayHelper;

defined('\ABSPATH') || exit;

TemplateHelper::addShopInfoOffcanvases($items, $params);

$ratings = TemplateHelper::generateStaticRatings(count($items));

foreach ($items as $i => $item)
{
    if (!isset($item['group']))
        $item['group'] = '';

    if (empty($item['ratingDecimal']) && isset($item['extra']['data']['ratingDecimal']))
        $items[$i]['ratingDecimal'] = $item['ratingDecimal'] = TemplateHelper::convertRatingScale10($item['extra']['data']['ratingDecimal']);

    if (empty($item['ratingDecimal']))
        $items[$i]['ratingDecimal'] = $ratings[$i];
    elseif ($item['ratingDecimal'] && ($item['group'] !== 'Roundup' || $item['ratingDecimal'] < 5))
        $items[$i]['ratingDecimal'] = TemplateHelper::convertRatingScale10($item['ratingDecimal']);

    $items[$i]['rating'] = $items[$i]['ratingDecimal'];
}

if (TemplateHelper::isNumbered($items))
    $items = TemplateHelper::sortByNumber($items, $order);
elseif ($item['group'] !== 'Roundup')
    $items = ArrayHelper::sortByField($items, 'ratingDecimal', 'desc');

?>

<div <?php if (!empty($_container_id)) echo 'id="cegg-top-listing-' . esc_html($_container_id) . '"'; ?> class="container px-0 mb-5 mt-1 cegg-list" <?php $this->colorMode(); ?>>
    <?php foreach ($items as $i => $item): ?>
        <?php $this->setItem($item, $i); ?>

        <div class="cegg-list-card cegg-card <?php echo $i < count($items) - 1 ? ' mb-3' : ''; ?><?php TemplateHelper::border($params); ?>" cegg-listing-position="<?php echo esc_attr($i + 1); ?>">

            <?php if ($this->isVisible('number', true)): ?>
                <div class="position-absolute top-50 z-3 start-0 translate-middle">
                    <?php TemplateHelper::number($item, $params, $i, 'danger'); ?>
                </div>
            <?php endif; ?>

            <div class="row p-2 p-md-3">
                <?php if ($this->isVisible('img')): ?>
                    <div class="cegg-list-card-img-col col-3 col-md-2 align-self-center" style="max-width: 150px;">
                        <div class="position-relative">
                            <div class="ratio<?php TemplateHelper::imgRatio($params, 'ratio-1x1'); ?>">
                                <?php TemplateHelper::displayImage($item, 190, 170, array('class' => 'object-fit-scale rounded')); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col align-self-center">
                    <div class="cegg-list-card-body">

                        <?php if ($this->isVisible('badge')): ?>
                            <?php TemplateHelper::badge3($item); ?>
                        <?php endif; ?>

                        <?php if ($this->isVisible('title')): ?>
                            <?php TemplateHelper::title($item, 'card-title fs-6 fw-normal cegg-text-truncate-2', 'div', $params); ?>
                        <?php endif; ?>

                        <?php if ($this->isVisible('subtitle')): ?>
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
                <?php if ($this->isVisible('rating', true)): ?>
                    <div class="col-auto px-md-3 align-self-center">
                        <?php TemplateHelper::ratingRing($item); ?>
                    </div>
                <?php endif; ?>
                <div class="col-6 col-md-auto align-self-center offset-3 offset-md-0 pe-3 text-center">

                    <?php if ($this->isVisible('price', false)): ?>
                        <div class="cegg-card-price lh-1 mt-1 ">

                            <div class="hstack justify-content-md-center gap-2">
                                <?php if ($this->isVisible('priceOld')): ?>
                                    <del class="cegg-old-price fs-6 text-body-tertiary fw-normal"><?php TemplateHelper::oldPrice($item); ?></del>

                                <?php endif; ?>
                                <div class="cegg-price fs-5 lh-1 mb-0<?php TemplateHelper::priceClass($item); ?>">
                                    <?php TemplateHelper::price($item); ?>
                                </div>
                            </div>
                            <div class="hstack justify-content-md-center gap-2">

                                <?php if ($this->isVisible('prime', false)): ?>
                                    <div class="pt-2 small">
                                        <?php TemplateHelper::prime($item); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($this->isVisible('stock_status', false)): ?>
                                    <div class="cegg-stock-status pt-2 small">
                                        <?php TemplateHelper::stockStatus($item); ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                        </div>
                    <?php endif; ?>

                    <?php if ($this->isVisible('button')): ?>
                        <div class="cegg-card-button pt-3">
                            <div class="d-grid"> <?php TemplateHelper::button($item, $params, array('class' => 'stretched-link')); ?></div>
                        </div>
                    <?php else: ?>
                        <?php TemplateHelper::link(' ', $item, $params, array('class' => 'stretched-link')); ?>
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
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php $this->renderBlock('disclaimer'); ?>
</div>
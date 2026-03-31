<?php
/*
 * Name: Product card with price comparison popup
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

\wp_enqueue_script('cegg-bootstrap5');

$cheapest = reset($items);
$item = TemplateHelper::selectItemByDescription($items);
?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <?php $this->setItem($item, 1); ?>
    <div class="cegg-item-card cegg-card <?php TemplateHelper::border($params, 'border'); ?>">

        <?php if ($this->isVisible('number', false)): ?>
            <div class="position-absolute top-50 z-3 start-0 translate-middle">
                <?php TemplateHelper::number($item, $params, $i); ?>
            </div>
        <?php endif; ?>

        <?php $badge_position = (TemplateHelper::getColOrder($params, 1) == 1) ? 'left' : 'right'; ?>
        <?php if ($this->isVisible('badge')) TemplateHelper::badge1($item, $params, $badge_position); ?>

        <div class="row p-3">
            <div class="cegg-item-card-img-col <?php TemplateHelper::conditionClass(TemplateHelper::getColOrder($params, 1) == 1, 'col-md-5', 'col-md-5'); ?> position-relative<?php TemplateHelper::colsOrder($params, 1, 'md'); ?>" style="max-width: 400px;">

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
                    <?php TemplateHelper::title($item, 'card-title h4 fw-normal  cegg-text-truncate-2 pt-3', 'h1', $params); ?>
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

                <?php if ($this->isVisible('description')): ?>
                    <div class="cegg-desc-small small lh-sm pt-3"><?php TemplateHelper::description($item); ?></div>
                <?php endif; ?>

                <?php
                $this->setItem($cheapest);
                if ($this->isVisible('price')): ?>
                    <div class="cegg-card-price lh-1 mt-3 mb-2">
                        <?php if (count($items) > 1) : ?>
                            <?php echo esc_html(TemplateHelper::__('from')); ?>
                        <?php endif; ?>
                        <span class="cegg-price fs-4<?php TemplateHelper::priceClass($cheapest); ?>">
                            <?php TemplateHelper::price($cheapest); ?>
                        </span>
                    </div>
                <?php
                endif;
                $this->setItem($item);
                ?>

                <div class="d-grid">
                    <?php $this->renderPartial('block_popup_button', array('btn_class' => 'col-12 col-md-8')); ?>

                </div>

            </div>
        </div>
    </div>

</div>
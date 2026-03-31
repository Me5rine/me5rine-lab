<?php
defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TemplateHelper;



?>

<div class="row">
    <div class="col-12">
        <?php TemplateHelper::chartjs($items, $params, 182); ?>
    </div>
</div>

<?php
$lowest_over_time = TemplateHelper::getPriceHistoryLowestItem();
if (!$lowest_over_time)
    return;
$highest_over_time = TemplateHelper::getPriceHistoryHighestItem();

if (!$highest_over_time)
    return;
$since = TemplateHelper::getPriceHistorySince();
$lowest_now = TemplateHelper::getLowestPriceItem($items);
if (!$lowest_now)
    return;
$this->setItem($lowest_now);
?>

<?php if ($lowest_over_time): ?>
    <div class="cegg-card mt-4">
        <div class="row rounded-3 p-4">

            <div class="col col-md-4">

                <div class="d-flex align-items-center">
                    <span class="text-primary me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-up-right-circle" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.854 10.803a.5.5 0 1 1-.708-.707L9.243 6H6.475a.5.5 0 1 1 0-1h3.975a.5.5 0 0 1 .5.5v3.975a.5.5 0 1 1-1 0V6.707z" />
                        </svg>
                    </span>
                    <?php TemplateHelper::esc_html_e('Highest Price'); ?>
                </div>

                <span class="fs-5 ps-md-4"><?php TemplateHelper::price($highest_over_time); ?></span>
                <span class="small text-body-secondary ms-1"><?php echo esc_html($highest_over_time['merchant']); ?></span>

                <div class="small ps-md-4 text-body-tertiary">
                    <?php echo esc_html(TemplateHelper::formatDate($highest_over_time['date'])); ?>
                </div>

            </div>

            <div class="col">

                <div class="d-flex align-items-center">
                    <span class="text-primary me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-right-circle" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.854 5.146a.5.5 0 1 0-.708.708L9.243 9.95H6.475a.5.5 0 1 0 0 1h3.975a.5.5 0 0 0 .5-.5V6.475a.5.5 0 1 0-1 0v2.768z" />
                        </svg>
                    </span>
                    <?php TemplateHelper::esc_html_e('Lowest Price'); ?>
                </div>

                <span class="fs-5 ps-md-4"><?php TemplateHelper::price($lowest_over_time); ?></span>
                <span class="small text-body-secondary ms-1"><?php echo esc_html($lowest_over_time['merchant']); ?></span>
                <div class="small ps-md-4 text-body-tertiary">
                    <?php echo esc_html(TemplateHelper::formatDate($lowest_over_time['date'])); ?>
                </div>

            </div>

            <div class="col-12 col-md-4 mt-3 mt-md-0">

                <div class="d-flex align-items-center">
                    <span class="text-primary me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                            <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05" />
                        </svg>
                    </span>
                    <?php TemplateHelper::esc_html_e('Current Price'); ?>
                </div>
                <span class="fs-5 ps-md-4"><?php TemplateHelper::price($lowest_now); ?></span>
                <span class="small ms-1">
                    <?php TemplateHelper::openATag($lowest_now, $params, array('class' => 'icon-link icon-link-hover')); ?>
                    <?php TemplateHelper::merchant($lowest_now); ?>
                    <?php if ($lowest_now['price'] <= 999): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                        </svg>
                    <?php endif; ?>
                    <?php TemplateHelper::closeATag($lowest_now); ?>

                </span>

                <div class="small ps-md-4 text-body-tertiary">
                    <?php echo esc_html(TemplateHelper::formatDate($lowest_now['last_update'])); ?>
                </div>

            </div>
        </div>

    </div>

    <div class="row g-2 fst-italic text-body-secondary mt-2 lh-1 ">
        <div class="col text-md-end order-md-2 cegg-price-disclaimer">
            <small><?php echo esc_html(sprintf(TemplateHelper::__('Since %s'), TemplateHelper::formatDate($since))); ?></small>
        </div>
        <?php if ($this->isVisible('disclaimer')): ?>
            <div class="col-12 col-md-auto cegg-block-disclaimer">
                <small><?php TemplateHelper::disclaimer(); ?></small>
            </div>
        <?php endif; ?>

    </div>

<?php endif; ?>
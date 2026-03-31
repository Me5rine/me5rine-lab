<?php
defined('\ABSPATH') || exit;
/*
  Name: Universal
 */

use ContentEgg\application\helpers\TemplateHelper;

?>
<?php if (!function_exists('cegg_coupons_css_enqueue')): ?>
    <?php function cegg_coupons_css_enqueue()
    { ?>
        <style>
            .cegg_coupon_hidden_code {
                overflow: hidden;
                z-index: 1;
                width: 100%;
                height: 38px;
                border: 2px dashed rgba(var(--cegg-primary-rgb));
                background-color: var(--cegg-primary-bg-subtle) !important;
                line-height: 34px;
            }

            .cegg_coupon_hidden_code:before {
                position: absolute;
                right: 6px;
                display: block;
                content: attr(data-code);
                white-space: nowrap;
            }

            .cegg_coupon_btn_txt:before {
                border-top: 38px solid rgba(var(--cegg-primary-rgb), var(--cegg-bg-opacity));
                border-right: 14px solid transparent;
                border-left: 0 solid transparent;
                content: "";
                display: inherit;
                height: 0;
                position: absolute;
                left: 100%;
                top: 0;
                transition: all .25s ease-out;
                width: 0;
            }

            .cegg_coupon_btn_txt {
                position: relative;
                z-index: 1;
                display: block;
                height: 38px;
                font-size: 16px;
                color: #fff;
                line-height: 38px;
                text-shadow: 0 1px 0 rgb(0 0 0 / 20%);
                padding: 0 10px;
                background: #0d6efd;
                text-align: center;
                transition: width 0.5s ease;
            }

            .cegg_coupon_btn_txt {
                width: calc(100% - 32px);
                cursor: pointer;
            }

            .cegg_coupon_btn:hover .cegg_coupon_btn_txt {
                width: calc(100% - 45px);
            }
        </style>

    <?php } ?>
    <?php cegg_coupons_css_enqueue();
    ?>
<?php endif; ?>
<div class="container px-0 mb-5 mt-1 cegg-coupon-list" <?php $this->colorMode(); ?>>

    <?php if ($data = TemplateHelper::filterData($items, 'linkType', 'Text Link', true)): ?>

        <?php foreach ($data as $i => $item): ?>
            <?php $this->setItem($item, $i); ?>

            <div class="cegg-coupon-list-card cegg-card <?php echo $i < count($items) - 1 ? ' mb-3' : ''; ?><?php TemplateHelper::border($params); ?>">
                <div class="row p-2 px-md-3">

                    <div class="col align-self-center">

                        <?php if ($this->isVisible('title')): ?>
                            <?php TemplateHelper::title($item, 'card-title h5 fw-normal cegg-text-truncate-2', 'div', $params); ?>
                        <?php endif; ?>

                        <?php if ($this->isVisible('subtitle')): ?>
                            <div class="card-subtitle fs-6 text-body-secondary cegg-text-truncate-2"><?php TemplateHelper::subtitle($item); ?></div>
                        <?php endif; ?>

                        <?php if ($this->isVisible('description')): ?>
                            <div class="cegg-desc-small card-text small lh-sm"><?php echo \wp_kses_post($item['description']); ?></div>
                        <?php endif; ?>

                        <?php if ($item['endDate']): ?>
                            <div class="text-muted small mt-2">
                                <em><?php echo esc_html__('Ends:', 'content-egg-tpl'); ?> <?php echo esc_html(TemplateHelper::formatDate($item['endDate'])); ?></em>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto align-self-center py-3 text-center">

                        <?php if ($item['extra']['couponCode']): ?>
                            <div class="cegg_coupon_hidden_code rounded rounded-2 fw-medium mb-2"><?php echo \esc_attr($item['extra']['couponCode']); ?></div>
                        <?php endif; ?>
                        <div class="d-grid"> <?php TemplateHelper::button($item, $params, array('class' => 'stretched-link'), 'link', true); ?></div>

                        <?php if ($item['extra']['advertiserSite']): ?>
                            <div class="cegg-merchant small fs-6 text-body-secondary text-truncate">
                                <small><?php echo esc_html($item['extra']['advertiserSite']); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

    <?php if ($data = TemplateHelper::filterData($items, 'linkType', 'Banner', true)): ?>
        <div class="row">
            <?php foreach ($data as $item): ?>
                <?php $this->setItem($item, $i); ?>

                <div class="col-md-6 mb-4">
                    <a <?php TemplateHelper::printRel(); ?> target="_blank" href="<?php echo esc_url_raw($item['url']); ?>">
                        <img src="<?php echo esc_attr($item['img']); ?>"
                            alt="<?php echo esc_attr($item['title']); ?>" class="img-fluid" />
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $remainingData = TemplateHelper::filterData($items, 'linkType', array('Text Link', 'Banner'), true, true);
    if ($remainingData):
    ?>
        <?php foreach ($remainingData as $item): ?>
            <div class="row">
                <div class="col-12">
                    <?php echo wp_kses_post($item['extra']['linkHtml']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
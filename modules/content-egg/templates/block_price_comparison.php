<?php
/*
 * Name: Price comparison widget
 * Modules:
 * Module Types: PRODUCT
 *
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <table class="table table-sm table-bordered table-hover mb-0">
        <tbody>

            <?php foreach ($items as $i => $item): ?>
                <?php $this->setItem($item, $i); ?>

                <tr>
                    <?php if ($this->isVisible('merchant')): ?>
                        <td class="col-4 p-0 ps-2 align-middle">
                            <?php TemplateHelper::openATag($item, $params, array('class' => 'text-body text-decoration-none')); ?>
                            <div class="text-nowrap text-truncate">
                                <?php TemplateHelper::icon($item, $params, 'me-1'); ?>
                                <?php TemplateHelper::merchant($item); ?>
                            </div>
                            <?php TemplateHelper::closeATag(); ?>
                        </td>
                    <?php endif; ?>
                    <td class="col-4 text-center align-middle">
                        <?php if ($this->isVisible('price')): ?>

                            <?php TemplateHelper::openATag($item, $params, array('class' => 'text-body text-decoration-none')); ?>
                            <div>
                                <span class="fw-medium<?php TemplateHelper::priceClass($item); ?>"><?php TemplateHelper::price($item); ?></span>
                                <?php if ($this->isVisible('priceOld', false)): ?>
                                    <del><?php TemplateHelper::oldPrice($item); ?></del>
                                <?php endif; ?>

                                <?php if ($this->isVisible('new_used_price')): ?>
                                    <div class="small text-body-secondary lh-sm">
                                        <small><?php TemplateHelper::newUsedPrice($item, '<br>'); ?></small>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->isVisible('stock_status', false)): ?>
                                    <div class="small text-body-secondary lh-sm">
                                        <small><?php TemplateHelper::stockStatus($item); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php TemplateHelper::closeATag(); ?>
                        <?php endif; ?>

                    </td>
                    <td class="col-4 text-center p-0 align-middle bg-primary text-nowrap">
                        <?php TemplateHelper::openATag($item, $params, array('class' => 'text-decoration-none')); ?>
                        <div class="btn btn-primary rounded-0 w-100 h-100">
                            <?php TemplateHelper::buyNowBtnText(true, $item, $btn_text); ?>
                        </div>
                        <?php TemplateHelper::closeATag(); ?>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>

    <?php $this->renderBlock('disclaimer'); ?>

</div>
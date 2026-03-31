<?php
/*
 * Name: Sorted offers list with product images
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

TemplateHelper::addShopInfoOffcanvases($items, $params);
TemplateHelper::addCouponOffcanvases($items, $params);

?>
<div class="cegg5-container cegg-offers_list">
    <div class="container px-0 mb-5 mt-1 cegg-list" <?php $this->colorMode(); ?>>
        <?php foreach ($items as $i => $item): ?>
            <?php $this->setItem($item, $i); ?>
            <?php $this->renderBlock('list_row'); ?>
        <?php endforeach; ?>

        <?php $this->renderBlock('disclaimer'); ?>
    </div>
</div>
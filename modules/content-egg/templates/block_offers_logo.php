<?php
/*
 * Name: Sorted offers list with store logos
 * Modules:
 * Module Types: PRODUCT
 *
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

TemplateHelper::addShopInfoOffcanvases($items, $params);
TemplateHelper::addCouponOffcanvases($items, $params, false);

?>

<div class="container px-0 mb-5 mt-1 cegg-list" <?php $this->colorMode(); ?>>

    <?php foreach ($items as $i => $item): ?>
        <?php $this->setItem($item, $i); ?>
        <?php $this->renderBlock('offer_row'); ?>
    <?php endforeach; ?>

    <?php $this->renderBlock('disclaimer'); ?>
</div>
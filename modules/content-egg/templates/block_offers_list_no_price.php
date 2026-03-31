<?php
/*
 * Name: Sorted offers list with no prices
 * Module Types: PRODUCT
 *
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;


$params['hide'][] = 'price';
$this->setParams($params);

TemplateHelper::addShopInfoOffcanvases($items, $params);
TemplateHelper::addCouponOffcanvases($items, $params);

?>

<div class="container px-0 mb-5 mt-1 cegg-list" <?php $this->colorMode(); ?>>
    <?php foreach ($items as $i => $item): ?>
        <?php $this->setItem($item, $i); ?>
        <?php $this->renderBlock('list_row'); ?>
    <?php endforeach; ?>

    <?php $this->renderBlock('disclaimer'); ?>
</div>
<?php
/*
 * Name: Grid with prices
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

TemplateHelper::addShopInfoOffcanvases($items, $params);

?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <div class="row g-3<?php TemplateHelper::rowCols($params, 'row-cols-2 row-cols-md-3'); ?>">
        <?php foreach ($items as $i => $item): ?>

            <?php $this->setItem($item, $i); ?>
            <?php $this->renderBlock('grid_row'); ?>

        <?php endforeach; ?>
    </div>
    <?php $this->renderBlock('disclaimer'); ?>
</div>
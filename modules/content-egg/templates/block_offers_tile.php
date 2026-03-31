<?php
/*
 * Name: Grid without price
 * Modules:
 * Module Types: PRODUCT
 *
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

?>

<?php
$params['hide'] = array_merge($params['hide'], array('price', 'button'));
$this->setParams($params);
?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <div class="row g-3<?php TemplateHelper::rowCols($params, 'row-cols-2 row-cols-md-4'); ?>">
        <?php foreach ($items as $i => $item): ?>
            <?php $this->setItem($item, $i); ?>
            <?php $this->renderBlock('grid_row'); ?>
        <?php endforeach; ?>
    </div>
    <?php $this->renderBlock('disclaimer'); ?>
</div>
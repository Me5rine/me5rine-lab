<?php
/*
 * Name: Product card
 * Module Types: PRODUCT
 */

defined('\ABSPATH') || exit; 

?>

<?php foreach ($items as $i => $item): ?>
  <div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <?php $this->setItem($item, $i); ?>
    <?php $this->renderBlock('item_row', array('item' => $item)); ?>
    <?php $this->renderBlock('disclaimer'); ?>
  </div>
<?php endforeach; ?>
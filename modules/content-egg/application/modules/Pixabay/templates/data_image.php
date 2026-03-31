<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;
/*
  Name: Image
 */

?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
  <div class="row">
    <?php foreach ($items as $item): ?>
      <div class="col-md-12 mb-4">
        <?php TemplateHelper::displayImage($item, 0, 0, array('class' => 'img-thumbnail')); ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
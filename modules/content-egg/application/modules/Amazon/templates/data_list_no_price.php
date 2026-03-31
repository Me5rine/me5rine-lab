<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

/*
  Name: List with no prices
 */

$params['hide'][] = 'price';

$params['visible'][] = 'number';

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
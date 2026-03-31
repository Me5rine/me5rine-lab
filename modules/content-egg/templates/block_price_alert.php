<?php
/*
 * Name: Price drop alert
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;
use ContentEgg\application\helpers\TextHelper;

defined('\ABSPATH') || exit;

?>

<?php
$item = reset($items);
$module_id = $item['module_id'];
if (!$title)
    $title = TemplateHelper::__('Set Alert for') . ' ' . TextHelper::truncate($item['title'], 80) . ' - ' . TemplateHelper::formatPriceCurrency($item['price'], $item['currencyCode']);
?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>

    <?php $this->renderBlock('price_alert_inline', array('item' => $item, 'title' => $title)); ?>

</div>
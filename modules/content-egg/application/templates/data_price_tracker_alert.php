<?php
/*
  Name: Price tracker & alert
 */

use ContentEgg\application\helpers\TemplateHelper;

__('Price tracker & alert', 'content-egg-tpl');

defined('\ABSPATH') || exit;
?>

<?php
if (!$params['cols_order'])
{
    $params['cols_order'] = '2,1';
    $this->setParams($params);
}
?>

<?php foreach ($items as $i => $item): ?>
    <div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?> id="<?php echo \esc_attr($item['unique_id']); ?>">

        <?php $this->setItem($item, $i); ?>
        <?php $this->renderBlock('item_row', array('item' => $item, 'items' => array($item))); ?>

        <div class="mt-4">
            <?php $this->renderBlock('price_alert_inline', array('item' => $item, 'items' => array($item))); ?>
        </div>
        <div class="mt-4 pb-2 text-body">
            <?php $this->renderBlock('price_history', array('item' => $item, 'items' => array($item))); ?>
        </div>

    </div>
<?php endforeach; ?>
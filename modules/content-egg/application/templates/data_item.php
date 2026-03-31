<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;
?>

<?php foreach ($items as $i => $item): ?>
    <div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>

        <?php $this->setItem($item, $i); ?>
        <?php
        $item_row_params = $params;
        $item_row_params['hide'][] = 'description';
        $this->setParams($item_row_params);
        ?>
        <?php $this->renderBlock('item_row', array('item' => $item, 'params' => $item_row_params)); ?>
        <?php $this->setParams($params); ?>
        <?php $this->renderBlock('disclaimer'); ?>

        <div class="row">
            <div class="col">

                <?php $this->renderPartialModule('_item_details_top', array('Flipkart'), array('item' => $item)); ?>
                <?php $this->renderBlock('item_features', array('item' => $item)); ?>

                <?php if ($this->isVisible('description')): ?>
                    <div class="egg-description text-body my-4"><?php TemplateHelper::description($item); ?></div>
                <?php endif; ?>

                <?php $this->renderPartialModule('_item_details_bottom', array('Udemy'), array('item' => $item)); ?>
                <?php if ($item['module_id'] !== 'AmazonNoApi') $this->renderBlock('item_reviews', array('item' => $item)); ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
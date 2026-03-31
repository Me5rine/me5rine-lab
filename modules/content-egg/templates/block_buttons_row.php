<?php
/*
 * Name: Buttons row
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

if (!empty($params['btn_text']))
    $btn_text = $params['btn_text'];
else
    $btn_text = '';
?>

<div class="container px-0 my-3" <?php $this->colorMode(); ?>>
    <div class="row g-3<?php TemplateHelper::rowCols($params, 'row-cols-1 row-cols-md-3'); ?>">

        <?php foreach ($items as $i => $btn_item): ?>
            <?php
            if (empty($btn_text))
            {
                if ($btn_item['price'])
                    $params['btn_text'] = sprintf(TemplateHelper::__('%s at %s'), '%PRICE%', '%MERCHANT%');
                elseif ($item['module_id'] == 'Udemy')
                    $params['btn_text'] = sprintf(TemplateHelper::__('View on %s'), '%MERCHANT%');
                else
                    $params['btn_text'] = sprintf(TemplateHelper::__('View Price at %s'), '%MERCHANT%');
            }
            ?>
            <?php $this->setItem($btn_item, $i); ?>
            <div class="col">
                <?php if ($this->isVisible('button')): ?>
                    <div class="d-grid">
                        <?php TemplateHelper::button($btn_item, $params); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </div>
</div>
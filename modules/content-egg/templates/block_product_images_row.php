<?php
/*
 * Name: Product images row
 * Modules:
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;;

defined('\ABSPATH') || exit;

if (!$images = TemplateHelper::getGallery($data, $cols, $params['start_number']))
    return;
?>
<div class="container px-0 mb-5 mt-4" <?php $this->colorMode(); ?>>
    <div class="row g-3<?php TemplateHelper::rowCols($params, 'row-cols-2 row-cols-md-2'); ?>">
        <?php foreach ($images as $item) : ?>

            <div class="col">
                <?php TemplateHelper::openATag($item); ?>
                <div class="ratio<?php TemplateHelper::imgRatio($params, 'ratio-1x1'); ?>">
                    <?php TemplateHelper::displayImage($item, 190, 170, array('class' => 'object-fit-scale img-thumbnail')); ?>
                </div>
                <?php TemplateHelper::closeATag(); ?>

            </div>
        <?php endforeach; ?>
    </div>
</div>
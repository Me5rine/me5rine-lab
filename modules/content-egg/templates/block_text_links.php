<?php
/*
 * Name: Text links
 * Modules:
 * Module Types: PRODUCT
 *
 */

use ContentEgg\application\helpers\TemplateHelper;
use ContentEgg\application\helpers\TextHelper;

defined('\ABSPATH') || exit;

?>
<div class="container px-0 mb-5 pt-2 text-body" <?php $this->colorMode(); ?>>
    <ul>
        <?php
        foreach ($items as $i => $item) : ?>
            <?php $this->setItem($item, $i); ?>
            <?php $item['title'] = TextHelper::truncate($item['title'], 80); ?>
            <li>
                <?php TemplateHelper::openATag($item); ?>
                <?php TemplateHelper::title($item, '', 'span', $params); ?>
                <?php TemplateHelper::closeATag(); ?>

                <?php if (!empty($item['price']) && $this->isVisible('price')): ?>
                    <strong class="<?php TemplateHelper::priceClass($item); ?>">
                        &mdash; <?php TemplateHelper::price($item); ?>
                    </strong>

                    <?php if ($this->isVisible('priceOld')): ?>
                        <del class="cegg-old-price text-body-tertiary"><?php TemplateHelper::oldPrice($item); ?></del>
                    <?php endif; ?>
                <?php endif; ?>

            </li>
        <?php endforeach; ?>
    </ul>
    <?php $this->renderBlock('disclaimer'); ?>

</div>
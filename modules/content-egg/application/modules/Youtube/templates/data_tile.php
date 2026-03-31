<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;
/*
  Name: Tile
 */
?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <div class="row g-3">

        <?php foreach ($items as $i => $item): ?>
            <?php $this->setItem($item, $i); ?>

            <div class="col-12 col-md-6 text-body">
                <?php if ($this->isVisible('title', false)): ?>
                    <?php TemplateHelper::title($item, 'card-title h6 fw-normal mb-3', 'div', $params); ?>
                <?php endif; ?>

                <div class="ratio ratio-16x9">
                    <iframe loading="lazy" width="560" height="315" src="https://www.youtube.com/embed/<?php echo esc_attr($item['extra']['guid']); ?>?rel=0" frameborder="0" allowfullscreen></iframe>
                </div>

                <?php if ($this->isVisible('description', false)): ?>
                    <div class="cegg-desc-small small lh-sm mt-3"><?php TemplateHelper::description($item); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>
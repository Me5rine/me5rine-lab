<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;
/*
  Name: Large responsive
 */
?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <?php foreach ($items as $i => $item): ?>
        <?php $this->setItem($item, $i); ?>
        <div class="row">
            <div class="col text-body">

                <?php if ($this->isVisible('title')): ?>
                    <?php TemplateHelper::title($item, 'card-title h4 fw-normal mb-3', 'h4', $params); ?>
                <?php endif; ?>

                <div class="ratio ratio-16x9">
                    <iframe loading="lazy" width="560" height="315" src="https://www.youtube.com/embed/<?php echo esc_attr($item['extra']['guid']); ?>?rel=0" frameborder="0" allowfullscreen></iframe>
                </div>

                <?php if ($this->isVisible('description')): ?>
                    <div class="cegg-desc-small small lh-sm mt-3"><?php TemplateHelper::description($item); ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
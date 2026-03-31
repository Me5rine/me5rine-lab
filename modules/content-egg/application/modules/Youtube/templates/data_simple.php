<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;
/*
  Name: Simple
 */

?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <?php foreach ($items as $i => $item): ?>
        <?php $this->setItem($item, $i); ?>
        <div class="row">
            <div class="col text-body">
                <iframe loading="lazy" width="560" height="315" src="https://www.youtube.com/embed/<?php echo esc_attr($item['extra']['guid']); ?>" frameborder="0" allowfullscreen></iframe>

                <?php if ($this->isVisible('title')): ?>
                    <?php TemplateHelper::title($item, 'card-title h4 fw-normal', 'h4', $params); ?>
                <?php endif; ?>

                <?php if ($this->isVisible('description')): ?>
                    <div class="cegg-desc-small small lh-sm pt-2"><?php TemplateHelper::description($item); ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
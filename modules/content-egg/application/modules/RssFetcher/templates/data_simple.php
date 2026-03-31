<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;
/*
  Name: Simple
 */
__('Simple', 'content-egg');
?>

<div class="container px-0 mb-5 mt-1 cegg-list" <?php $this->colorMode(); ?>>
    <?php foreach ($items as $item): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="card-title h5">
                    <?php TemplateHelper::openATag($item, $params, array('class' => 'fw-bolder')); ?>
                    <?php echo esc_html($item['title']); ?>
                    <?php TemplateHelper::closeATag(); ?>
                </div>
                <p class="card-text">
                    <?php echo wp_kses_post($item['description']); ?>
                </p>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php
defined('\ABSPATH') || exit;
/*
  Name: Simple
 */
__('Simple', 'content-egg');
?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
  <div class="row">
    <?php foreach ($items as $item): ?>
      <div class="col-md-12 pb-3">
        <img src="<?php echo esc_url($item['img']); ?>" <?php if (! empty($item['keyword'])): ?> alt="<?php echo esc_attr($item['keyword']); ?>" <?php endif; ?> class="img-thumbnail" />
        <div class="text-center">
          <p class="small"><?php echo esc_html(sprintf(__('Source: %s', 'content-egg'), esc_attr($item['extra']['source']))); ?></p>
          <div class="h4"><?php echo esc_html($item['title']); ?></div>
          <p><?php echo wp_kses_post($item['description']); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
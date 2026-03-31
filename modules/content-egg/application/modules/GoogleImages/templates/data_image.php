<?php
defined('\ABSPATH') || exit;
/*
  Name: Image
 */

?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
  <div class="row">
    <?php foreach ($items as $item): ?>
      <div class="col-md-12 mb-4">
        <img src="<?php echo esc_url($item['img']); ?>" <?php if (! empty($item['keyword'])): ?> alt="<?php echo esc_attr($item['keyword']); ?>" <?php endif; ?> class="img-thumbnail" />
      </div>
    <?php endforeach; ?>
  </div>
</div>
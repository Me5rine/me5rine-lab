<?php
/**
 * Template grille – variables: $items, $show_price, $show_image
 */
if (!defined('ABSPATH') || empty($items) || !is_array($items)) {
    return;
}
?>
<div class="me5rine-affiliate-links me5rine-affiliate-links--grid">
    <div class="me5rine-affiliate-links__grid">
        <?php foreach ($items as $item) : ?>
            <div class="me5rine-affiliate-links__card">
                <a href="<?php echo esc_url($item['url']); ?>" class="me5rine-affiliate-links__link" target="_blank" rel="noopener noreferrer sponsored">
                    <?php if (!empty($item['img_url']) && $show_image) : ?>
                        <span class="me5rine-affiliate-links__img-wrap">
                            <img src="<?php echo esc_url($item['img_url']); ?>" alt="" class="me5rine-affiliate-links__img" loading="lazy" />
                        </span>
                    <?php endif; ?>
                    <span class="me5rine-affiliate-links__title"><?php echo esc_html($item['title']); ?></span>
                    <?php if (!empty($item['price']) && $show_price) : ?>
                        <span class="me5rine-affiliate-links__price"><?php echo esc_html($item['price']); ?></span>
                        <?php if (!empty($item['price_old'])) : ?>
                            <del class="me5rine-affiliate-links__price-old"><?php echo esc_html($item['price_old']); ?></del>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

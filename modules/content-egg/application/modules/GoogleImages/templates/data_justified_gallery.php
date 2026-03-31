<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;
/*
 * Name: Gallery
 *
 * @link: http://miromannino.github.io/Justified-Gallery/
 */

?>

<?php wp_enqueue_style('egg-justified-gallery', ContentEgg\PLUGIN_RES . '/justified_gallery/justifiedGallery.min.css'); ?>
<?php wp_enqueue_script('egg-justified-gallery', ContentEgg\PLUGIN_RES . '/justified_gallery/jquery.justifiedGallery.min.js', array('jquery')); ?>
<?php wp_enqueue_style('egg-color-box', ContentEgg\PLUGIN_RES . '/colorbox/colorbox.css'); ?>
<?php wp_enqueue_script('egg-color-box', ContentEgg\PLUGIN_RES . '/colorbox/jquery.colorbox-min.js', array('jquery')); ?>

<?php
$rand = rand(0, 100000);
?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>
    <div class="cegg-gimages-gallery">
        <?php foreach ($items as $item): ?>
            <a href="<?php echo esc_url($item['img']); ?>" rel="gallery<?php echo esc_attr($rand); ?>">
                <?php TemplateHelper::displayImage($item); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
    jQuery(document).ready(function() {

        jQuery('.cegg-gimages-gallery').justifiedGallery({
            rowHeight: 160,
            lastRow: 'nojustify',
            margins: 1,
        }).on('jg.complete', function() {
            jQuery(this).find('a').colorbox({
                maxWidth: '80%',
                maxHeight: '80%',
                opacity: 0.8,
                transition: 'elastic',
                current: ''
            });
        });
    });
</script>
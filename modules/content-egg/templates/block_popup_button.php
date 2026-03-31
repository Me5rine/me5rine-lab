<?php
/*
 * Name: Button with price comparison popup
 * Modules:
 * Module Types: PRODUCT
 *
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

$item = TemplateHelper::selectItemByDescription($items);
$modal_id = TemplateHelper::generateGlobalId('cegg-popup-');
$modal_label = TemplateHelper::generateGlobalId('cegg-popup-label');

\wp_enqueue_script('cegg-bootstrap5');

if (empty($btn_class))
    $btn_class = 'col-6 col-md-4';

TemplateHelper::addShopInfoOffcanvases($items, $params);
TemplateHelper::addCouponOffcanvases($items, $params, false);

?>

<?php if (count($items) == 1) : ?>
    <div class="container px-0 mb-2 mt-2" <?php $this->colorMode(); ?>>
        <div class="row">
            <div class="col">
                <?php TemplateHelper::button($item, $params, array('class1' => $btn_class)); ?>
            </div>
        </div>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="container px-0 mb-2 mt-2" <?php $this->colorMode(); ?>>
    <div class="row">
        <div class="col">
            <?php
            if (!$params['btn_text'])
                $params['btn_text'] = TemplateHelper::t('Shop %d Offers');

            if (strstr($params['btn_text'], '%d'))
                $params['btn_text'] = sprintf($params['btn_text'], count($items));

            TemplateHelper::button($item, $params, array('data-bs-toggle' => 'modal', 'data-bs-target' => '#' . $modal_id), 'button');
            ?>
        </div>
    </div>

</div>

<!-- Modal -->
<div class="modal fade" id="<?php echo esc_attr($modal_id); ?>" tabindex="-1" aria-labelledby="<?php echo esc_attr($modal_label); ?>" aria-hidden="true" <?php $this->colorMode(); ?>>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title text-body-secondary fs-6 text-truncate" id="<?php echo esc_attr($modal_label); ?>">
                    <?php echo esc_html($item['title']); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container px-0 mb-5 mt-1 cegg-list">
                    <?php foreach ($items as $i => $item): ?>
                        <?php $this->setItem($item, $i); ?>
                        <?php $this->renderBlock('offer_row'); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <?php $this->renderBlock('disclaimer'); ?>
            </div>
        </div>
    </div>
</div>
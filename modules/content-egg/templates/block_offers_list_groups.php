<?php
/*
 * Name: Sorted offers list with product images with grouped tabs
 * Module Types: PRODUCT
 *
 */

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

if (!$groups = TemplateHelper::getGroupsList($data, $groups))
{
    $this->renderPartial('block_offers_list');
    return;
}

\wp_enqueue_script('cegg-bootstrap5');

$group_ids = array();

TemplateHelper::addShopInfoOffcanvases($items, $params);
TemplateHelper::addCouponOffcanvases($items, $params);

?>

<div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>

    <ul class="nav <?php TemplateHelper::tabsType($params, 'nav-underline'); ?>" role="tablist" style="margin: 0px;">
        <?php foreach ($groups as $g => $group): ?>
            <?php $group_ids[$g] = TemplateHelper::generateGlobalId('cegg-list-'); ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link<?php if ($g == 0): ?> active<?php endif; ?>" id="<?php echo \esc_attr($group_ids[$g]); ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo \esc_attr($group_ids[$g]); ?>" type="button" role="tab" aria-controls="<?php echo \esc_attr($group_ids[$g]); ?>" aria-selected="true"><?php echo \esc_html($group); ?></a></button>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content mt-3">
        <?php foreach ($groups as $g => $group): ?>
            <div class="tab-pane fade<?php if ($g == 0): ?> show active<?php endif; ?>" id="<?php echo \esc_attr($group_ids[$g]); ?>" role="tabpanel" aria-labelledby="<?php echo \esc_attr($group_ids[$g]); ?>-tab">
                <?php $filtered_items = TemplateHelper::filterItemsByGroup($items, $group); ?>
                <?php $this->setItems($filtered_items); ?>
                <?php foreach ($filtered_items as $i => $item): ?>
                    <?php $this->setItem($item, $i); ?>
                    <?php $this->renderBlock('list_row'); ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php $this->renderBlock('disclaimer'); ?>

</div>
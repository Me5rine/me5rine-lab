<?php
/*
 * Name: Review box
 * Module Types: PRODUCT
 */

defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TemplateHelper;

$item = TemplateHelper::selectItemByBadge($items);
if ($title)
  $item['title'] = $title;

if (!empty($params['btn_text']))
  $btn_text = $params['btn_text'];
else
  $btn_text = '';

if (TemplateHelper::isAllItemsBridged($items))
{
  $this->params['hide'][] = 'price_update';
}

if (!empty($item['group']) && strpos($item['group'], 'RoundupProduct') !== false)
  $items = TemplateHelper::sortByPrice($items);

?>

<div class="container px-0 mb-5 mt-5" <?php $this->colorMode(); ?>>
  <?php $this->setItem($item, 0); ?>

  <div class="cegg-review-box cegg-card <?php TemplateHelper::border($params, 'border'); ?>">

    <?php $badge_position = (TemplateHelper::getColOrder($params, 1) == 1) ? 'left' : 'left'; ?>
    <?php if ($this->isVisible('badge')) TemplateHelper::badge1($item, $params, $badge_position); ?>

    <div class="row p-3">

      <div class="col-12 pt-3 <?php TemplateHelper::conditionClass(TemplateHelper::getColOrder($params, 2) == 1, 'ps-xl-4', ''); ?>">
        <?php if ($this->isVisible('title')): ?>
          <?php if ($this->isVisible('number', false)): ?>
            <?php $params['_number'] = 0; ?>
          <?php endif; ?>
          <?php TemplateHelper::title($item, 'card-title h4 fw-normal mt-1 mb-3', 'h3', $params, 120); ?>
        <?php endif; ?>
      </div>

      <div class="cegg-review-box-img-col <?php TemplateHelper::conditionClass(TemplateHelper::getColOrder($params, 2) == 1, 'col-md-5', 'col-md-6'); ?><?php TemplateHelper::colsOrder($params, 1, 'md'); ?>" style="max-width: 400px;">

        <?php if ($this->isVisible('img')): ?>
          <div class="position-relative">

            <?php if ($this->isVisible('percentageSaved', false)): ?>
              <div class="badge bg-danger rounded-1 position-absolute bottom-0 start-0 z-3">-<?php echo esc_html($item['percentageSaved']); ?>%</div>
            <?php endif; ?>

            <?php TemplateHelper::openATag($item); ?>
            <div class="ratio<?php TemplateHelper::imgRatio($params, 'ratio-1x1'); ?>">
              <?php TemplateHelper::displayImage($item, 350, 350, array('class' => 'object-fit-scale rounded')); ?>
            </div>
            <?php TemplateHelper::closeATag(); ?>

          </div>
        <?php endif; ?>

      </div>

      <div class="col<?php TemplateHelper::conditionClass(TemplateHelper::getColOrder($params, 2) == 1, 'ps-xl-4', 'ps-xl-2'); ?><?php TemplateHelper::colsOrder($params, 2, 'md'); ?>">

        <?php if ($this->isVisible('subtitle', true) || $this->isVisible('rating', true)): ?>
          <div class="container-fluid px-0 mt-2 mb-2">
            <div class="d-flex justify-content-between align-items-center w-100 mb-2">

              <?php if ($this->isVisible('subtitle', true)): ?>
                <div class="cegg-review-box-wrapper flex-grow-1 me-3">
                  <div class="cegg-review-box-verdict fs-5">
                    <?php TemplateHelper::subtitle($item); ?>
                  </div>
                  <?php if ($this->isVisible('rating', true)): ?>
                    <div class="mt-2">
                      <?php TemplateHelper::ratingProgress($item); ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($this->isVisible('rating', true) && $r = TemplateHelper::getRatingValueScale10($item)): ?>
                <div class="text-center">
                  <div class="cegg-review-box-score text-bg-primary rounded-1 text-center">
                    <?php echo esc_html(floor($r) == $r ? number_format($r, 0) : number_format($r, 1)); ?><span>/10</span>
                    <div class="cegg-review-box-expert text-bg-dark rounded-1 rounded-top-0">
                      <?php TemplateHelper::esc_html_e('EXPERT SCORE'); ?>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

            </div>
          </div>
        <?php endif; ?>

        <?php if ($this->isVisible('description', true)): ?>
          <div class="cegg-desc lh-base mt-4"><?php TemplateHelper::description($item); ?></div>
        <?php endif; ?>

        <div class="d-grid gap-2 mt-4" style="max-width: 450px;">

          <?php foreach ($items as $i => $btn_item): ?>
            <?php
            if (empty($btn_text))
            {
              if (TemplateHelper::isLinkedToBridge($btn_item))
              {
                $btn_text = TemplateHelper::buyNowBtnText(false, $item);
              }
              elseif ($btn_item['price'])
                $params['btn_text'] = sprintf(TemplateHelper::__('%s at %s'), '%PRICE%', '%MERCHANT%');
              elseif ($item['module_id'] == 'Udemy')
                $params['btn_text'] = sprintf(TemplateHelper::__('View on %s'), '%MERCHANT%');
              else
                $params['btn_text'] = sprintf(TemplateHelper::__('View Price at %s'), '%MERCHANT%');
            }
            ?>
            <?php $this->setItem($btn_item, $i); ?>
            <?php if ($this->isVisible('button')): ?>
              <?php TemplateHelper::button($btn_item, $params); ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
  <?php $this->renderBlock('disclaimer'); ?>

</div>
<?php

use ContentEgg\application\helpers\TemplateHelper;
?>

<?php if ($this->isVisibleDisclaimerOrPriceUpdate()): ?>
    <div class="row g-2 fst-italic text-body-secondary mt-2 lh-1">
        <?php if ($this->isVisible('price_update')): ?>
            <div class="col text-md-end order-md-2 cegg-price-disclaimer">
                <small><?php TemplateHelper::priceUpdateAmazon($items); ?></small>
            </div>
        <?php endif; ?>
        <?php if ($this->isVisible('disclaimer')): ?>
            <div class="col-12 col-md-auto cegg-block-disclaimer">
                <small><?php TemplateHelper::disclaimer(); ?></small>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($this->isVisible('delivery_at_checkout')): ?>
    <div class="row fst-italic text-body-secondary mt-2 lh-1">
        <div class="col cegg-delivery-at-checkout<?php if (!$this->isVisible('disclaimer')): ?> text-md-end<?php endif; ?>">
            <small><?php TemplateHelper::deliveryAtCheckout(); ?></small>
        </div>
    </div>
<?php endif; ?>
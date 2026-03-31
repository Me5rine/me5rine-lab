<?php defined('\ABSPATH') || exit; ?>

<div class="list-group list-group-numbered" style="max-height: 600px;overflow-y: scroll;">

    <a class="list-group-item list-group-item-action d-flex justify-content-start align-items-start" ng-class="{'disabled' : result.added}" ng-click="add(result, '<?php echo esc_attr($module_id); ?>')" repeat-done ng-repeat="result in models.<?php echo esc_attr($module_id); ?>.results">
        <div class="ms-2 me-auto d-flex justify-content-start align-items-center1">
            <div ng-show="result.img" class="cegg-res-image-container me-3 text-center">
                <img class="img-thumbnail cegg-res-product-image mx-auto" ng-src="{{result.img}}">
            </div>
            <div>
                <div ng-show="result.title" class="fw-bold">{{result.title| limitTo: 120}}{{result.title.length > 120 ? '&hellip;' : ''}}</div>
                <div ng-show="result._descriptionText" class="mt-1 small text-muted">{{result._descriptionText| limitTo: 250}}{{result.description.length > 250 ? '&hellip;' : ''}}</div>

                <div class="mt-2">
                    <span ng-show="result._priceFormatted"><b ng-bind-html="result._priceFormatted"></b> <strike ng-show="result._priceOldFormatted" ng-bind-html="result._priceOldFormatted"></strike></span>
                    <span ng-show="result.domain" class="small text-muted ms-2"><img src="https://www.google.com/s2/favicons?domain=https://{{result.domain}}"> {{result.domain}}</span>
                    <span ng-show="result.features.length" class="ms-2"><small class="small text-muted"><?php esc_html_e('Attributes:', 'content-egg'); ?> {{result.features.length}}</small></span>
                    <span ng-show="result.ean" class="ms-2"><small class="small text-muted"><?php esc_html_e('EAN:', 'content-egg'); ?> {{result.ean}}</small></span>
                    <?php if ($module_id == 'Amazon' || $module_id == 'AmazonNoApi' || $module_id == 'Ebay2') : ?>
                        <div class="text-muted small">
                            <small class="text-success me-2" ng-show="result.promo">{{result.promo}}</small>
                            <small class="text-primary me-2" ng-show="result.extra.DealAccessType=='PRIME_EXCLUSIVE'"><?php _e('Prime exclusive', 'content-egg'); ?></small>
                            <small class="text-primary me-2" ng-show="result.extra.DealAccessType=='PRIME_EARLY_ACCESS'"><?php _e('Prime early access', 'content-egg'); ?></small>
                            <small class="text-muted" ng-show="result.extra.AvailabilityMessage">{{result.extra.AvailabilityMessage}}</small>
                        </div>
                    <?php endif; ?>

                </div>
                <div ng-show="result.code">
                    <?php esc_html_e('Coupon code:', 'content-egg'); ?> <em>{{result.code}}</em>
                    - <span ng-show="result.startDate">{{result.startDate * 1000|date:'mediumDate'}} - {{result.endDate * 1000|date:'mediumDate'}}</span>
                </div>
            </div>

        </div>
    </a>
</div>
<?php

use ContentEgg\application\helpers\AdminHelper;

defined('\ABSPATH') || exit; ?>
<?php
\wp_nonce_field('contentegg_metabox', 'contentegg_nonce');
$tpl_manager = ContentEgg\application\components\BlockTemplateManager::getInstance();
$templates = $tpl_manager->getTemplatesList(true);
if (!$global_keyword = \get_post_meta($post->ID, '_cegg_global_autoupdate_keyword', true))
    $global_keyword = '';

?>

<script>
    "use strict";
    jQuery(document).ready(function($) {

        $(document).on('click', '#cegg_update_lists, #cegg_update_prices', function(e) {
            e.preventDefault();
            var this_btn = $(this);
            $('#cegg_update_lists, #cegg_update_prices, .button, .btn').attr('disabled', true);
            var nonce = $('#contentegg_nonce').val();
            $('body').addClass('cegg_wait');

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'cegg_update_products',
                    btn: this_btn.attr('id'),
                    contentegg_nonce: nonce,
                    post_id: <?php echo \esc_attr($post->ID); ?>
                },
                success: function(data) {
                    location.reload();
                },
                error: function(errorThrown) {
                    location.reload();
                },
                timeout: 180000
            });
            return false;
        });
    });
</script>

<div class="row mt-3 pb-3">
    <div class="col">

        <div class="input-group input-group-sm">

            <input style="flex-basis: 20%;" class="form-control form-control-sm shortcode-input cegg-copy-input" ng-model="blockShortcode" select-on-click readonly type="text" />
            <button class="btn btn-outline-secondary cegg-copy-button" type="button" title="Copy to clipboard"><i class="bi bi-copy"></i></button>

            <select class="form-control form-control-sm ms-3" ng-init="blockShortcodeBuillder.template = '<?php echo esc_attr(key($templates)); ?>'; buildBlockShortcode();" ng-model="blockShortcodeBuillder.template" ng-change="buildBlockShortcode();">
                <?php foreach ($templates as $id => $name) : ?>
                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>

            <select ng-show="productGroups.length" class="form-control form-control-sm" ng-model="blockShortcodeBuillder.group" ng-change="buildBlockShortcode();">
                <option value="">- <?php esc_html_e('Groups', 'content-egg'); ?> ({{productGroups.length}}) -</option>
                <option ng-repeat="group in productGroups" value="{{group}}">{{group}}</option>
            </select>

            <a target="_blank" href="https://ce-docs.keywordrush.com/frontend/how-content-is-displayed" class="btn btn-sm btn-secondary ms-3" title="<?php esc_html_e('View plugin documentation', 'content-egg'); ?>">
                <i class="bi bi-question-circle"></i>
            </a>
        </div>
    </div>
</div>
<div class="row mt-2">
    <div class="col text-end">
        <div class="input-group input-group-sm">
            <input type="text" ng-model="newProductGroup" select-on-click on-enter="addProductGroup()" class="form-control form-control-sm" placeholder="<?php esc_html_e('Add a product group', 'content-egg'); ?>" aria-label="<?php esc_html_e('Add a product group ', 'content-egg'); ?>">
            <button ng-disabled="!newProductGroup" ng-click="addProductGroup()" type="button" class="btn btn-sm btn-outline-primary" aria-label="Add">
                <i class="bi bi-plus"></i>
            </button>
            <button ng-show="productGroups.length"
                title="<?php esc_html_e('Remove all product groups', 'content-egg'); ?>"
                ng-click="confirmAndRemoveProductGroups()"
                type="button"
                class="btn btn-sm btn-outline-danger ms-1"
                aria-label="Remove">
                <i class="bi bi-trash3"></i>
            </button>

            <?php if (AdminHelper::isAiEnabled()) : ?>
                <button ng-show="global_isAddedResults()" class="btn btn-outline-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span ng-show="aiProcessingSmartGroups" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <i class="bi bi-magic"></i>
                    <?php esc_html_e('Smart groups', 'content-egg'); ?>
                </button>
                <ul class="dropdown-menu cegg-ai-tools">
                    <li class="small m-0"><a ng-click="smartGroups('auto')" class="dropdown-item"><?php echo esc_html(__('Auto-Groups', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('price_comparison')" class="dropdown-item"><?php echo esc_html(__('Price Comparison', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('product_category')" class="dropdown-item"><?php echo esc_html(__('By Shopping Category', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('features')" class="dropdown-item"><?php echo esc_html(__('By Features', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('brand')" class="dropdown-item"><?php echo esc_html(__('By Brand', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('price_range')" class="dropdown-item"><?php echo esc_html(__('By Price Range', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('by_usage')" class="dropdown-item"><?php echo esc_html(__('By Usage', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('age_group')" class="dropdown-item"><?php echo esc_html(__('By Age Group', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('material_ingredients')" class="dropdown-item"><?php echo esc_html(__('By Material or Ingredients', 'content-egg')); ?></a></li>
                    <li class="small m-0"><a ng-click="smartGroups('size_volume')" class="dropdown-item"><?php echo esc_html(__('By Size or Volume', 'content-egg')); ?></a></li>
                </ul>
            <?php endif; ?>
            <input class="form-control form-control-sm ms-3" name="globalUpdateKeyword" value="<?php echo esc_attr($global_keyword); ?>" type="text" placeholder="<?php esc_html_e('Global auto-update keyword', 'content-egg'); ?>" title="<?php esc_html_e('Global auto-update keyword for all active modules', 'content-egg'); ?>">

            <?php if ($keywordsExist || $global_keyword) : ?>
                <input type="submit" id="cegg_update_lists" class="btn btn-sm btn-outline-primary ms-3" value="<?php esc_html_e('Refresh listings', 'content-egg'); ?>" title="<?php esc_html_e('Refresh all product listings using auto-update keywords', 'content-egg'); ?>">
            <?php endif; ?>
            <?php if ($dataExist) : ?>
                <input type="submit" id="cegg_update_prices" class="btn btn-sm btn-outline-primary ms-3" value="<?php esc_html_e('Update prices', 'content-egg'); ?>" title="<?php esc_html_e('Force update all product prices', 'content-egg'); ?>">
            <?php endif; ?>

        </div>
    </div>
</div>
<div class="col-md-12 text-danger small mt-2" ng-show="smartGroupsError">
    {{smartGroupsError}}
</div>

<div class="row mt-3">
    <div class="input-group input-group-sm">
        <input ng-disabled="processCounter" type="text" ng-model="global_keywords" select-on-click on-enter="global_findAll()" class="form-control" placeholder="<?php echo esc_attr('Keyword to search all modules', 'content-egg'); ?>" aria-label="<?php echo esc_attr('Keyword to search all modules', 'content-egg'); ?>">
        <button ng-disabled='processCounter || !global_keywords' ng-click="global_findAll()" type="button" class="btn btn-primary" aria-label="Find">
            <i class="bi bi-search"></i>
        </button>
        <button ng-show='!processCounter && global_isSearchResults()' ng-click="global_addAll()" type="button" class="btn btn-outline-primary"><?php echo esc_attr('Add all', 'content-egg'); ?></button>
        <button ng-show='global_isAddedResults()' ng-click="global_deleteAll()" ng-confirm-click="<?php esc_html_e('Are you sure you want to delete results from all modules?', 'content-egg'); ?>" type="button" class="btn btn-outline-danger ms-3"><?php echo esc_attr('Remove all', 'content-egg'); ?></button>

        <?php
        $post_id = get_the_ID();
        $prefill_url = wp_nonce_url(add_query_arg(['page' => 'content-egg-product-prefill', 'action' => 'prefill_config', 'post__in' => $post_id], admin_url('admin.php')), 'prefill_config_nonce');
        $confirm_msg = __('Existing product data will be replaced. Are you sure you want to proceed?', 'content-egg');
        ?>
        <a href="<?php echo esc_url($prefill_url); ?>" class="btn btn-outline-primary ms-3" onclick="return confirm('<?php echo esc_js($confirm_msg); ?>');">
            <?php esc_html_e('Prefill', 'content-egg'); ?>
        </a>
    </div>
</div>

<!-- Toast container -->
<div id="cegg-toast-container"
    class="toast-container position-fixed p-3 mb-3"
    style="z-index:1000000;"></div>

<style>
    #cegg-toast-container {
        right: calc(1rem + 280px);
        bottom: 1rem;
        z-index: 1000000;
    }

    @media (max-width: 782px) {
        #cegg-toast-container {
            bottom: .75rem;
            right: .75rem;
        }
    }
</style>

<script>
    "use strict";
    jQuery(document).ready(function() {
        jQuery(document).on('click', '.cegg-copy-button', function() {
            var copyButton = jQuery(this);
            var input = copyButton.siblings('.cegg-copy-input');
            var copyText = input.val();

            navigator.clipboard.writeText(copyText).then(function() {
                var icon = copyButton.find('i');
                icon.removeClass('bi-copy').addClass('bi-check');

                setTimeout(function() {
                    icon.removeClass('bi-check').addClass('bi-copy');
                }, 1000);
            });
        });
    });
</script>
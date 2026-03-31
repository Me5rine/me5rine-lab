<?php defined('\ABSPATH') || exit; ?>

<?php
$tpl_manager = ContentEgg\application\components\ModuleTemplateManager::getInstance($module_id);
$templates = $tpl_manager->getTemplatesList(true);
?>

<div ng-controllerTMP="<?php echo esc_attr($module_id); ?>Controller">
    <input type="hidden" name="cegg_data[<?php echo esc_attr($module_id); ?>]" ng-value="models.<?php echo esc_attr($module_id); ?>.added | json" />
    <input type="hidden" name="cegg_updateKeywords[<?php echo esc_attr($module_id); ?>]" ng-value="updateKeywords.<?php echo esc_attr($module_id); ?>" />
    <input type="hidden" name="cegg_updateParams[<?php echo esc_attr($module_id); ?>]" ng-value="updateParams.<?php echo esc_attr($module_id); ?>| json" />

    <nav class="mt-5 mb-4 small">
        <div class="nav nav-tabs" id="nav-tab-<?php echo esc_attr($module_id); ?>" role="tablist">
            <button class="nav-link" ng-class="{'active': activeResultTabs.<?php echo esc_attr($module_id); ?>}" id="nav-products-tab-<?php echo esc_attr($module_id); ?>" data-bs-toggle="tab" data-bs-target="#nav-products-<?php echo esc_attr($module_id); ?>" type="button" role="tab" aria-controls="nav-products-<?php echo esc_attr($module_id); ?>" aria-selected="{{activeResultTabs.<?php echo esc_attr($module_id); ?>}}" ng-click="activeResultTabs.<?php echo esc_attr($module_id); ?>=true">
                <?php echo esc_html($module->getName()); ?>
                <span ng-show="models.<?php echo esc_attr($module_id); ?>.added.length" class="badge" ng-class="{'text-bg-danger':models.<?php echo esc_attr($module_id); ?>.added_changed, 'text-bg-dark':!models.<?php echo esc_attr($module_id); ?>.added_changed}">{{models.<?php echo esc_attr($module_id); ?>.added.length}}</span>
            </button>
            <button class="nav-link" ng-class="{'active': !activeResultTabs.<?php echo esc_attr($module_id); ?>}" id="nav-search-tab-<?php echo esc_attr($module_id); ?>" data-bs-toggle="tab" data-bs-target="#nav-search-<?php echo esc_attr($module_id); ?>" type="button" role="tab" aria-controls="nav-search-<?php echo esc_attr($module_id); ?>" aria-selected="{{!activeResultTabs.<?php echo esc_attr($module_id); ?>}}" ng-click="activeResultTabs.<?php echo esc_attr($module_id); ?>=false"><?php esc_html_e('Search', 'content-egg'); ?></button>
        </div>
    </nav>
    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane" ng-class="{'show active': activeResultTabs.<?php echo esc_attr($module_id); ?>}" id="nav-products-<?php echo esc_attr($module_id); ?>" role="tabpanel" aria-labelledby="nav-products-tab-<?php echo esc_attr($module_id); ?>" tabindex="0">

            <div class="row mb-2">
                <div class="pe-0 col-lg-8 col-md-7 col-sm-12">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-sm shortcode-input cegg-copy-input" ng-model="shortcodes.<?php echo esc_attr($module_id); ?>" select-on-click readonly type="text" style="flex-basis: 35%;" />
                        <button class="btn btn-outline-secondary cegg-copy-button" type="button" title="Copy to clipboard"><i class="bi bi-copy"></i></button>

                        <?php if ($templates) : ?>
                            <select class="form-control form-control-sm ms-1" ng-model="selectedTemplate_<?php echo esc_attr($module_id); ?>" ng-change="buildShortcode('<?php echo esc_attr($module_id); ?>', selectedTemplate_<?php echo esc_attr($module_id); ?>, selectedGroup_<?php echo esc_attr($module_id); ?>);">
                                <option value="">&larr; <?php esc_html_e('Shortcode Template', 'content-egg'); ?></option>
                                <?php foreach ($templates as $id => $name) : ?>
                                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <select ng-show="productGroups.length" class="form-control form-control-sm" ng-model="selectedGroup_<?php echo esc_attr($module_id); ?>" ng-change="buildShortcode('<?php echo esc_attr($module_id); ?>', selectedTemplate_<?php echo esc_attr($module_id); ?>, selectedGroup_<?php echo esc_attr($module_id); ?>);">
                            <option value="">&larr; <?php esc_html_e('Group', 'content-egg'); ?></option>
                            <option ng-repeat="group in productGroups" value="{{group}}">{{group}}</option>
                        </select>

                    </div>
                </div>
                <div class="ps-0 col-lg-4 col-md-5 col-sm-12">
                    <div class="input-group input-group-sm float-end" style="width: auto;">
                        <?php if ($module->isAffiliateParser()) : ?>
                            <input class="form-control form-control-sm" id="updateKeyword_<?php echo esc_attr($module_id); ?>" type="text" ng-model="updateKeywords.<?php echo esc_attr($module_id); ?>" placeholder="<?php esc_html_e('Autoupdate keyword', 'content-egg'); ?>" title="<?php esc_html_e('Keyword for automated product list update', 'content-egg'); ?>" />
                            <?php $module->renderUpdatePanel(); ?>
                        <?php elseif ($module->isParser()) : ?>
                            <input class="form-control form-control-sm" id="updateKeyword_<?php echo esc_attr($module_id); ?>" type="text" ng-model="updateKeywords.<?php echo esc_attr($module_id); ?>" placeholder="<?php esc_html_e('Autoupdate keyword', 'content-egg'); ?>" title="<?php esc_html_e('Keyword for automated content update', 'content-egg'); ?>" />
                        <?php endif; ?>

                        <?php if (stristr($module_id, 'Amazon') || $module_id == 'Bolcom') : ?>
                            <button title="<?php esc_html_e('Copy the product IDs for use in Too Much Niche articles', 'content-egg'); ?>" type="button" class="btn btn-sm btn-outline-primary ms-2" ng-click="copyProductIdsToClipboard('<?php echo esc_attr($module_id); ?>', $event)" ng-show='models.<?php echo esc_attr($module_id); ?>.added.length'><i class="bi bi-magnet"></i></span></button>
                        <?php endif; ?>
                        <button title="<?php esc_html_e('Remove all', 'content-egg'); ?>" type="button" class="btn btn-sm btn-outline-danger ms-2" ng-click="deleteAll('<?php echo esc_attr($module_id); ?>')" ng-confirm-click="<?php esc_html_e('Are you sure you want to delete all the results?', 'content-egg'); ?>" ng-show='models.<?php echo esc_attr($module_id); ?>.added.length'><i class="bi bi-trash3"></i></span></button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div ng-show="!models.<?php echo esc_attr($module_id); ?>.added.length && !models.<?php echo esc_attr($module_id); ?>.processing" class="alert alert-secondary text-center small"><?php esc_html_e('No module data...', 'content-egg'); ?></div>
                    <?php $module->renderResults(); ?>
                </div>
            </div>

        </div>
        <div class="tab-pane" ng-class="{'show active': !activeResultTabs.<?php echo esc_attr($module_id); ?>}" id="nav-search-<?php echo esc_attr($module_id); ?>" role="tabpanel" aria-labelledby="nav-search-tab-<?php echo esc_attr($module_id); ?>" tabindex="0">
            <div class="row mt-4">
                <div class="col-md-6 col-sm-12">

                    <div class="input-group input-group-sm" ng-show="!models.<?php echo esc_attr($module_id); ?>.processing">
                        <?php $module->isUrlSearchAllowed() ? $placeholder = __('Keyword or Product URL', 'content-egg') : $placeholder = __('Keyword to search', 'content-egg'); ?>
                        <?php $placeholder = \apply_filters('content_egg_keyword_input_placeholder', $placeholder, $module_id); ?>
                        <input type="text" select-on-click ng-model="keywords.<?php echo esc_attr($module_id); ?>" on-enter="find('<?php echo esc_attr($module_id); ?>')" class="form-control form-control-sm" placeholder="<?php echo \esc_attr($placeholder); ?>" />
                        <button title="<?php echo esc_html(__('Search', 'content-egg')); ?>" ng-disabled="!keywords.<?php echo esc_attr($module_id); ?>" ng-click="find('<?php echo esc_attr($module_id); ?>')" type="button" class="btn btn-primary" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>

                        <?php if (stristr($module_id, 'Amazon') || $module_id == 'Bolcom') : ?>
                            <button title="<?php esc_html_e('Copy the product IDs for use in Too Much Niche articles', 'content-egg'); ?>" type="button" class="btn btn-sm btn-outline-primary" ng-click="copyKeywordProductIdsToClipboard('<?php echo esc_attr($module_id); ?>', $event)" ng-disabled="!keywords.<?php echo esc_attr($module_id); ?> || !models.<?php echo esc_attr($module_id); ?>.added.length"><i class="bi bi-magnet"></i></button>
                        <?php endif; ?>

                        <?php if (\apply_filters('cegg_enable_autoupdate_keyword_button', false) && $module->isAffiliateParser()) : ?>
                            <button title="<?php echo esc_html(__('Add the keyword as "auto-update keyword"', 'content-egg')); ?>" ng-disabled="!keywords.<?php echo esc_attr($module_id); ?>" ng-click="setUpdateKeyword('<?php echo esc_attr($module_id); ?>', $event)" type="button" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-circle-dotted"></i>
                            </button>
                        <?php endif; ?>

                        <a class='btn btn-outline-primary' ng-click="addAll('<?php echo esc_attr($module_id); ?>')" ng-show='models.<?php echo esc_attr($module_id); ?>.results.length > 0 && !models.<?php echo esc_attr($module_id); ?>.processing'><?php esc_html_e('Add all', 'content-egg'); ?></a>

                    </div>

                    <?php if ($module->isFeedModule() && $module->isImportTime()) : ?>
                        <img ng-show="models.<?php echo esc_attr($module_id); ?>.processing" src="<?php echo esc_url(\ContentEgg\PLUGIN_RES) . '/img/importing.gif' ?>" />
                        <span class="small" ng-show="models.<?php echo esc_attr($module_id); ?>.processing">
                            <?php esc_html_e('Loading data feed... Please wait...', 'content-egg'); ?>
                        </span>
                    <?php else : ?>
                        <div ng-show="models.<?php echo esc_attr($module_id); ?>.processing" class="ms-2">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="input-group input-group-sm" ng-show="!models.<?php echo esc_attr($module_id); ?>.processing">
                        <?php $module->renderSearchPanel(); ?>
                    </div>
                </div>
            </div>

            <div class="row mt-3" ng-show="!models.<?php echo esc_attr($module_id); ?>.processing">
                <div class="col small">
                    <?php $module->renderSearchResults(); ?>

                    <div ng-show="!models.<?php echo esc_attr($module_id); ?>.processing && models.<?php echo esc_attr($module_id); ?>.loaded && models.<?php echo esc_attr($module_id); ?>.results.length == 0 && !models.<?php echo esc_attr($module_id); ?>.error" class="alert alert-secondary small"><?php esc_html_e('No results found...', 'content-egg'); ?></div>
                    <div ng-show="models.<?php echo esc_attr($module_id); ?>.error && !models.<?php echo esc_attr($module_id); ?>.processing" class="alert alert-warning small"><?php esc_html_e('Error:', 'content-egg'); ?>
                        <span ng-bind-html="models.<?php echo esc_attr($module_id); ?>.error"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
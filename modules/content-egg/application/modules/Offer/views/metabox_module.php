<?php

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\admin\import\PresetRepository;
use ContentEgg\application\helpers\AdminHelper;

defined('\ABSPATH') || exit; ?>

<?php
$tpl_manager = ContentEgg\application\components\ModuleTemplateManager::getInstance($module_id);
$templates = $tpl_manager->getTemplatesList(true);
$is_woo = (\get_post_type($GLOBALS['post']->ID) == 'product') ? true : false;
$isAffiliateParser = $module->isAffiliateParser();
if ($isAffiliateParser && $module->isProductParser())
    $isProductParser = true;
else
    $isProductParser = false;

$prompt1 = GeneralConfig::getInstance()->option('prompt1');
$prompt2 = GeneralConfig::getInstance()->option('prompt2');
$prompt3 = GeneralConfig::getInstance()->option('prompt3');
$prompt4 = GeneralConfig::getInstance()->option('prompt4');

$preset_options     = PresetRepository::getList();
$default_preset_id  = (int) PresetRepository::getDefaultId();
?>

<div ng-controllerTMP="<?php echo esc_attr($module_id); ?>Controller">
    <input type="hidden" name="cegg_data[<?php echo esc_attr($module_id); ?>]" ng-value="models.<?php echo esc_attr($module_id); ?>.added | json" />
    <input type="hidden" name="cegg_updateKeywords[<?php echo esc_attr($module_id); ?>]" ng-value="updateKeywords.<?php echo esc_attr($module_id); ?>" />
    <input type="hidden" name="cegg_updateParams[<?php echo esc_attr($module_id); ?>]" ng-value="updateParams.<?php echo esc_attr($module_id); ?>| json" />

    <nav class="mt-5 mb-4 small">
        <div class="nav nav-tabs" id="nav-tab-<?php echo esc_attr($module_id); ?>" role="tablist">
            <button class="nav-link active" id="nav-products-tab-<?php echo esc_attr($module_id); ?>" data-bs-toggle="tab" data-bs-target="#nav-products-<?php echo esc_attr($module_id); ?>" type="button" role="tab" aria-controls="nav-products-<?php echo esc_attr($module_id); ?>" aria-selected="{{activeResultTabs.<?php echo esc_attr($module_id); ?>}}">
                <?php echo esc_html($module->getName()); ?>
                <span ng-show="models.<?php echo esc_attr($module_id); ?>.added.length" class="badge" ng-class="{'text-bg-danger':models.<?php echo esc_attr($module_id); ?>.added_changed, 'text-bg-dark':!models.<?php echo esc_attr($module_id); ?>.added_changed}">{{models.<?php echo esc_attr($module_id); ?>.added.length}}</span>
            </button>
        </div>
    </nav>
    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane show active" id="nav-products-<?php echo esc_attr($module_id); ?>" role="tabpanel" aria-labelledby="nav-products-tab-<?php echo esc_attr($module_id); ?>" tabindex="0">

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
                    <div class="float-end">
                        <a class='btn btn-primary btn-sm' ng-click="addBlank('<?php echo esc_attr($module_id); ?>')"><i class="bi bi-plus-square"></i> <?php esc_html_e('Add offer', 'content-egg'); ?></a>
                        <button title="<?php esc_html_e('Remove all', 'content-egg'); ?>" type="button" class="btn btn-sm btn-outline-danger ms-2" ng-click="deleteAll('<?php echo esc_attr($module_id); ?>')" ng-confirm-click="<?php esc_html_e('Are you sure you want to delete all the results?', 'content-egg'); ?>" ng-show='models.<?php echo esc_attr($module_id); ?>.added.length'><i class="bi bi-trash3"></i></span></button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div ng-show="!models.<?php echo esc_attr($module_id); ?>.added.length && !models.<?php echo esc_attr($module_id); ?>.processing" class="alert alert-secondary text-center small"><?php esc_html_e('No module data...', 'content-egg'); ?></div>
                    <?php if ($isProductParser) : ?>
                        <div class="row mt-0 pt-0 mb-3 cegg-ai-tools" ng-if="models['<?php echo esc_attr($module_id); ?>'].added.length">

                            <div class="col-md-12 d-flex align-items-center justify-content-start">

                                <?php if (AdminHelper::isAiEnabled()) : ?>
                                    <div class="input-group input-group-sm flex-grow-0 w-auto flex-nowrap me-3">

                                        <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span ng-show="aiProcessingTitle['<?php echo esc_attr($module_id); ?>']" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            <?php esc_html_e('AI Title', 'content-egg'); ?>
                                            <span ng-show="selected['<?php echo esc_attr($module_id); ?>'] > 0">({{selectedCount('<?php echo esc_attr($module_id); ?>')}})</span>
                                            <span ng-show="selected['<?php echo esc_attr($module_id); ?>'] == 0">(<?php esc_html_e('all', 'content-egg'); ?>)</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($prompt1) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'prompt1', '')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 1)); ?></a></li><?php endif; ?>
                                            <?php if ($prompt2) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'prompt2', '')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 2)); ?></a></li><?php endif; ?>
                                            <?php if ($prompt3) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'prompt3', '')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 3)); ?></a></li><?php endif; ?>
                                            <?php if ($prompt4) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'prompt4', '')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 4)); ?></a></li><?php endif; ?>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'shorten', '')" class="dropdown-item"><?php esc_html_e('Shorten', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'rephrase', '')" class="dropdown-item"><?php esc_html_e('Rephrase', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'translate', '')" class="dropdown-item"><?php esc_html_e('Translate', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', 'subtitle_perfect_for', '')" class="dropdown-item"><?php esc_html_e('Generate subtitle', 'content-egg'); ?></a></li>
                                        </ul>
                                        <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span ng-show="aiProcessingDescription['<?php echo esc_attr($module_id); ?>']" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            <?php esc_html_e('AI Description', 'content-egg'); ?>
                                            <span ng-show="selected['<?php echo esc_attr($module_id); ?>'] > 0">({{selectedCount('<?php echo esc_attr($module_id); ?>')}})</span>
                                            <span ng-show="selected['<?php echo esc_attr($module_id); ?>'] == 0">(<?php esc_html_e('all', 'content-egg'); ?>)</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($prompt1) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'prompt1')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 1)); ?></a></li><?php endif; ?>
                                            <?php if ($prompt2) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'prompt2')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 2)); ?></a></li><?php endif; ?>
                                            <?php if ($prompt3) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'prompt3')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 3)); ?></a></li><?php endif; ?>
                                            <?php if ($prompt4) : ?><li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'prompt4')" class="dropdown-item"><?php echo esc_html(sprintf(__('Custom prompt #%d', 'content-egg'), 4)); ?></a></li><?php endif; ?>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'rewrite')" class="dropdown-item"><?php esc_html_e('Rewrite', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'paraphrase')" class="dropdown-item"><?php esc_html_e('Paraphrase', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'translate')" class="dropdown-item"><?php esc_html_e('Translate', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'summarize')" class="dropdown-item"><?php esc_html_e('Summarize', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'bullet_points')" class="dropdown-item"><?php esc_html_e('Bullet points', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'bullet_points_compact')" class="dropdown-item"><?php esc_html_e('Bullet points (concise)', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'write_review')" class="dropdown-item"><?php esc_html_e('Write a review', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'write_article')" class="dropdown-item"><?php esc_html_e('Write an article', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'write_buyers_guide')" class="dropdown-item"><?php esc_html_e('Write a buyer\'s guide', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'write_paragraphs')" class="dropdown-item"><?php esc_html_e('Write a few paragraphs', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'craft_description')" class="dropdown-item"><?php esc_html_e('Craft a product description', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'write_how_to_use')" class="dropdown-item"><?php esc_html_e('Write a how to use instruction', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'turn_into_advertising')" class="dropdown-item"><?php esc_html_e('Turn into advertising', 'content-egg'); ?></a></li>
                                            <li class="small m-0"><a ng-click="ai('<?php echo esc_attr($module_id); ?>', '', 'cta_text')" class="dropdown-item"><?php esc_html_e('Generate CTA text', 'content-egg'); ?></a></li>
                                        </ul>
                                        <button ng-disabled="!models['<?php echo esc_attr($module_id); ?>'].undo.length || aiProcessingDescription['<?php echo esc_attr($module_id); ?>'] || aiProcessingTitle['<?php echo esc_attr($module_id); ?>']" ng-click="aiUndo('<?php echo esc_attr($module_id); ?>')" type="button" class="btn btn-sm btn-outline-info" title="<?php echo esc_attr('Undo', 'content-egg'); ?>"><i class="bi bi-arrow-counterclockwise"></i></button>

                                    </div>
                                <?php endif; ?>

                                <div class="pdp-import-dropdown">

                                    <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span ng-show="isProcessingImport['<?php echo esc_attr($module_id); ?>']" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        <?php esc_html_e('Import as Bridge Page', 'content-egg'); ?>
                                        <span ng-show="selected['<?php echo esc_attr($module_id); ?>'] > 0">({{selectedCount('<?php echo esc_attr($module_id); ?>')}})</span>
                                        <span ng-show="selected['<?php echo esc_attr($module_id); ?>'] == 0">(<?php esc_html_e('all', 'content-egg'); ?>)</span>
                                    </button>
                                    <ul class="dropdown-menu">

                                        <?php foreach ($preset_options as $p) : ?>
                                            <li class="small m-0">
                                                <a class="dropdown-item" ng-click="import('<?php echo esc_attr($module_id); ?>', '<?php echo esc_attr($p['id']); ?>')">
                                                    <?php
                                                    printf(
                                                        '%s [%s]',
                                                        esc_html($p['title']),
                                                        esc_html($p['type'])
                                                    );
                                                    ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>

                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-12 text-danger small mt-2" ng-show="models.<?php echo esc_attr($module_id); ?>.aiError">
                                {{models.<?php echo esc_attr($module_id); ?>.aiError}}
                            </div>
                        </div>
                    <?php endif; ?>
                    <div ng-model="models.<?php echo esc_attr($module_id); ?>.added" ui-sortable="sortableOptions" ng-if="models.<?php echo esc_attr($module_id); ?>.added.length" id="<?php echo \esc_attr($module->getId()); ?>" style="max-height: 600px;overflow-y: scroll;padding-right: 15px;">
                        <div class="row g-0 mb-3 pb-0 pe-1 pt-0" ng-repeat="data in models.<?php echo esc_attr($module_id); ?>.added" ng-class="{'bg-light': $even}">
                            <div class="col-md-1 pe-1 col-xs-12 text-center small " id="<?php echo \esc_attr($module->getId()); ?>-{{data.unique_id}}">

                                <div class="ratio cegg-thumbnail-ratio">
                                    <img ng-src="{{data.img}}" class="img-thumbnail w-100 h-100" style="object-fit:contain;" />
                                    <span class="badge text-bg-light position-absolute top-0 start-0 m-1"
                                        style="width:auto; height:auto;">
                                        {{$index + 1}}
                                    </span>
                                </div>
                                <div class="container text-center mt-2">
                                    <div class="row justify-content-center g-1">
                                        <div class="col">

                                            <button
                                                type="button"
                                                class="btn btn-outline-info btn-sm cegg-btn-xs"
                                                ng-class="{'active': data._selected}"
                                                ng-click="data._selected = !data._selected"
                                                aria-pressed="{{!!data._selected}}"
                                                title="<?php echo esc_attr__('Select for import / AI', 'content-egg'); ?>"
                                                aria-label="<?php echo esc_attr__('Select for import / AI', 'content-egg'); ?>">
                                                <i class="bi bi-check-square"></i>
                                            </button>

                                        </div>
                                        <div class="col">

                                            <!-- Drag to reorder -->
                                            <div
                                                class="cegg-item-handle btn btn-outline-info btn-sm cegg-btn-xs"
                                                style="cursor: move;"
                                                title="<?php esc_html_e('Drag to reorder', 'content-egg'); ?>"
                                                aria-label="<?php esc_attr_e('Drag to reorder', 'content-egg'); ?>">
                                                <i class="bi bi-arrows-vertical"></i>
                                            </div>

                                        </div>
                                        <div class="col">
                                            <!-- Insert item ID -->
                                            <button
                                                type="button"
                                                class="btn btn-outline-info btn-sm cegg-btn-xs"
                                                style="cursor: copy;"
                                                title="<?php esc_html_e('Insert item ID into shortcode', 'content-egg'); ?>"
                                                aria-label="<?php esc_attr_e('Insert item ID into shortcode', 'content-egg'); ?>"
                                                ng-click="addIdToShortcode('<?php echo esc_attr($module_id); ?>',
                            selectedTemplate_<?php echo esc_attr($module_id); ?>,
                            selectedGroup_<?php echo esc_attr($module_id); ?>,
                            data.unique_id, $event);">
                                                <i class="bi bi-key"></i>
                                            </button>
                                        </div>
                                        <div class="col">
                                            <!-- Copy shortcode -->
                                            <button type="button"
                                                class="btn btn-outline-info btn-sm cegg-btn-xs"
                                                title="<?php esc_html_e('Copy the description shortcode', 'content-egg'); ?>"
                                                aria-label="<?php esc_attr_e('Copy the description shortcode', 'content-egg'); ?>"
                                                ng-click="copyDescriptionShortcode('<?php echo esc_attr($module_id); ?>', data.unique_id, $event)">
                                                <i class="bi bi-code-square"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9 col-xs-12">
                                <div class="input-group input-group-sm">
                                    <input style="flex-basis: 35%;" type="text" placeholder="<?php esc_html_e('Title', 'content-egg'); ?>" ng-model="data.title" class="form-control">
                                    <input type="text" placeholder="<?php esc_html_e('Price', 'content-egg'); ?>" ng-model="data.price" class="form-control">
                                    <input type="text" placeholder="<?php esc_html_e('Old Price', 'content-egg'); ?>" ng-model="data.priceOld" class="form-control">
                                    <select class="form-control" ng-model="data.currencyCode" ng-init="data.currencyCode = data.currencyCode || '<?php echo esc_attr($module->getConfigInstance()->option('default_currency')); ?>'">
                                        <?php foreach (\ContentEgg\application\helpers\CurrencyHelper::getCurrenciesList() as $currency) : ?>
                                            <option value="<?php echo \esc_attr($currency); ?>"><?php echo \esc_html($currency); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="input-group input-group-sm mt-1">
                                    <input style="flex-basis: 35%;" type="text" placeholder="<?php esc_html_e('Product URL', 'content-egg'); ?> (<?php esc_html_e('required', 'content-egg'); ?>)" ng-model="data.orig_url" class="form-control">
                                    <input type="text" placeholder="<?php esc_html_e('Merchant name', 'content-egg'); ?>" ng-model="data.merchant" class="form-control">
                                    <input type="text" placeholder="<?php esc_html_e('Domain', 'content-egg'); ?>" ng-model="data.domain" class="form-control">
                                    <input type="text" placeholder="<?php esc_html_e('Rating (e.g., 4.5)', 'content-egg'); ?>" ng-model="data.ratingDecimal" title="<?php esc_html_e('Product Rating (X out of 5)', 'content-egg'); ?>" class="form-control">

                                </div>

                                <div class="input-group input-group-sm mt-1">
                                    <input type="text" placeholder="<?php esc_html_e('Product Image URL', 'content-egg'); ?>" ng-model="data.img" class="form-control">
                                    <input type="text" placeholder="<?php esc_html_e('Merchant Logo URL (optional)', 'content-egg'); ?>" ng-model="data.logo" class="form-control">
                                </div>

                                <div class="input-group input-group-sm mt-1">
                                    <input type="text" placeholder="<?php esc_html_e('Custom XPath Price Selector', 'content-egg'); ?>" ng-model="data.extra.priceXpath" class="form-control">
                                    <input type="text" placeholder="<?php esc_html_e('Custom Deeplink', 'content-egg'); ?>" ng-model="data.extra.deeplink" class="form-control">
                                </div>

                                <div class="input-group input-group-sm mt-1 small text-danger" ng-show="data.extra.last_error">
                                    <span ng-show="data.last_update"><i title="<?php esc_html_e('Last XPath error', 'content-egg'); ?>" class="bi bi-exclamation-triangle"></i> <?php esc_html_e('Last XPath error', 'content-egg'); ?>: {{data.extra.last_error}}</abbr></span>
                                </div>

                                <div class="row mt-1">
                                    <div class="col" ng-if="!isHtmlContent(data.description)">
                                        <textarea type="text" placeholder="<?php esc_html_e('Description', 'content-egg'); ?>" rows="2" ng-model="data.description" class="form-control form-control-sm"></textarea>
                                    </div>
                                    <div class="col" ng-if="isHtmlContent(data.description)">
                                        <textarea ui-tinymce="tinymceOptions" ng-model="data.description"></textarea>
                                    </div>
                                </div>

                                <?php if ($isAffiliateParser) : ?>
                                    <div class="row">
                                        <div class="col mt-1">
                                            <div class="input-group input-group-sm">

                                                <input type="number" min="0" max="999" placeholder="<?php esc_html_e('Order', 'content-egg'); ?>" title="<?php esc_html_e('Order Number: Lower Value - Product Ranks Higher in the List', 'content-egg'); ?>" ng-model="data.order_num" class="form-control">
                                                <input style="flex-basis: 25%;" type="text" placeholder="<?php esc_html_e('Subtitle', 'content-egg'); ?>" title="<?php esc_html_e('Subtitle', 'content-egg'); ?>" ng-model="data.subtitle" class="form-control">
                                                <select class="form-control" ng-model="data.badge_color" title="<?php esc_html_e('Badge Color', 'content-egg'); ?>">
                                                    <option value="">- <?php esc_html_e('Badge color', 'content-egg'); ?> -</option>
                                                    <option value="primary">primary</option>
                                                    <option value="secondary">secondary</option>
                                                    <option value="success">success</option>
                                                    <option value="danger">danger</option>
                                                    <option value="warning">warning</option>
                                                    <option value="info">info</option>
                                                    <option value="light">light</option>
                                                    <option value="dark">dark</option>
                                                </select>
                                                <input type="text" placeholder="<?php esc_html_e('Badge (e.g., Our pick)', 'content-egg'); ?>" title="<?php esc_html_e('Badge Label', 'content-egg'); ?>" ng-model="data.badge" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isAffiliateParser) : ?>
                                    <div class="row small p-0 m-0 pt-1">
                                        <div class="col">

                                            <?php if ($is_woo) : ?>
                                                <div class="mt-2 hstack gap-3">
                                                    <label><input ng-true-value="'true'" type="checkbox" ng-model="data.woo_sync" name="woo_sync" ng-change="wooRadioChange(data.unique_id, 'woo_sync')"> <?php esc_html_e('Woo synchronization', 'content-egg'); ?></label>
                                                    <label ng-show="data.features.length">
                                                        <input ng-true-value="'true'" type="checkbox" ng-model="data.woo_attr" name="woo_attr" ng-change="wooRadioChange(data.unique_id, 'woo_attr')"> <?php esc_html_e('Woo attributes', 'content-egg'); ?>: {{data.features.length}}
                                                        <a class="link-dark" data-bs-toggle="collapse" href="#ceggFeatures{{$index}}" role="button" aria-expanded="false" aria-controls="ceggFeatures{{$index}}"><i class="bi bi-pencil-square"></i></a>
                                                    </label>
                                                </div>
                                            <?php else : ?>
                                                <div ng-class="result.ean ? 'mt-2' : 'mt-1'" ng-show="data.features.length">
                                                    <?php esc_html_e('Attributes:', 'content-egg'); ?> {{data.features.length}}
                                                    <a class="text-muted" data-bs-toggle="collapse" href="#ceggFeatures{{$index}}" role="button" aria-expanded="false" aria-controls="ceggFeatures{{$index}}"><i class="bi bi-pencil-square"></i></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div ng-show="data.ean" class="col-4 p-0 m-0">
                                            <div class="input-group input-group-sm">
                                                <input class="form-control form-control-sm cegg-copy-input" readonly type="text" title="EAN" select-on-click ng-model="data.ean" />
                                                <button class="btn btn-outline-secondary cegg-copy-button" type="button" title="Copy to clipboard"><i class="bi bi-copy"></i></button>
                                            </div>
                                        </div>

                                    </div>
                                <?php endif; ?>

                                <div id="ceggFeatures{{$index}}" ng-show="data.features.length" class="row collapse mt-2 mb-3">
                                    <div class="col-md-12" ng-repeat="feature in data.features">
                                        <div class="input-group input-group-sm">
                                            <input type="text" ng-model="feature.name" class="form-control">
                                            <input type="text" ng-model="feature.value" class="form-control">
                                            <button class="btn btn-outline-secondary" ng-click="data.features.splice($index, 1)" aria-label="Delete">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 small ps-2 ps-xl-3">
                                <button class="btn-close float-end mt-1" aria-label="Close" ng-click="delete(data, '<?php echo esc_attr($module_id); ?>')" title="<?php esc_attr('Remove', 'content-egg'); ?>"></button>

                                <div class="mt-1 d-flex align-items-center" ng-if="data.last_update">
                                    <!-- Stock status -->
                                    <span class="me-2" ng-if="data.stock_status == 1"
                                        title="<?php echo esc_attr__('In stock', 'content-egg'); ?>">
                                        <i class="bi bi-bag-check text-success" aria-hidden="true"></i>
                                        <span class="visually-hidden"><?php echo esc_html__('In stock', 'content-egg'); ?></span>
                                    </span>

                                    <span class="me-2" ng-if="data.stock_status == -1"
                                        title="<?php echo esc_attr__('Out of stock', 'content-egg'); ?>">
                                        <i class="bi bi-bag-dash-fill text-danger" aria-hidden="true"></i>
                                        <span class="visually-hidden"><?php echo esc_html__('Out of stock', 'content-egg'); ?></span>
                                    </span>

                                    <!-- Last updated -->
                                    <time
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="<?php echo esc_attr__('Last updated:', 'content-egg'); ?> {{ (data.last_update * 1000) | date:'medium' }}"
                                        datetime="{{ (data.last_update * 1000) | date:'yyyy-MM-ddTHH:mm:ssZ' }}">
                                        {{ (data.last_update * 1000) | date:'shortDate' }}
                                    </time>
                                </div>

                                <div class="small text-muted mt-2 text-truncate"
                                    ng-if="data.orig_url || data.aff_url || data.url"
                                    ng-init="href = (data.orig_url || data.aff_url || data.url)">
                                    <a class="link-dark text-decoration-none d-inline-flex align-items-center text-truncate"
                                        ng-href="{{href}}"
                                        target="_blank"
                                        rel="noopener"
                                        ng-attr-title="<?php echo esc_attr__('Go to', 'content-egg'); ?> {{ data.domain || href }}">
                                        <img ng-if="data.domain"
                                            ng-src="https://www.google.com/s2/favicons?domain={{data.domain}}"
                                            width="16" height="16" class="me-1" alt="">
                                        <span class="text-truncate">
                                            <span ng-if="data.domain">{{data.domain}}</span>
                                            <span ng-if="!data.domain"><?php esc_html_e('Open link', 'content-egg'); ?></span>
                                        </span>
                                        <sup class="ms-1"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></sup>
                                    </a>
                                </div>

                                <!-- Bridge -->
                                <div ng-if="data.target_post_id"
                                    class="small lh-1 d-flex align-items-center text-truncate mt-2"
                                    style="min-width:0;">
                                    <i class="bi bi-link-45deg"
                                        ng-class="data.is_canonical_bridge ? 'text-primary' : 'text-success'"
                                        aria-hidden="true"
                                        ng-attr-title="{{ data.is_canonical_bridge
                                        ? '<?php echo esc_js(__('Canonical Bridge Page (site-wide)', 'content-egg')); ?>'
                                        : '<?php echo esc_js(__('Bridge Page (this post only)', 'content-egg')); ?>' }}">
                                    </i>

                                    <a class="ms-1 text-muted text-decoration-none text-truncate"
                                        ng-href="<?php echo esc_url(admin_url('post.php')); ?>?post={{data.target_post_id}}&action=edit"
                                        target="_blank" rel="noopener"
                                        ng-attr-title="{{ data.is_canonical_bridge
                                            ? '<?php echo esc_js(__('Edit Canonical Bridge Page', 'content-egg')); ?>'
                                            : '<?php echo esc_js(__('Edit Bridge Page', 'content-egg')); ?>' }}">
                                        #{{data.target_post_id}}
                                    </a>

                                    <span class="visually-hidden" ng-if="data.is_canonical_bridge">
                                        <?php echo esc_html__('Canonical bridge', 'content-egg'); ?>
                                    </span>
                                </div>

                                <?php if ($isAffiliateParser) : ?>
                                    <div ng-show="productGroups.length" class="mt-3">
                                        <select ng-model="data.group" class="form-control form-control-sm">
                                            <option value="">- <?php esc_html_e('Product group', 'content-egg'); ?> -</option>
                                            <option ng-repeat="group in productGroups" ng-value="group">{{group}}</option>
                                        </select>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                    <?php //.results
                    ?>
                </div>
            </div>

        </div>

    </div>

</div>
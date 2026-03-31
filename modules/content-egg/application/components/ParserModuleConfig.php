<?php

namespace ContentEgg\application\components;

use ContentEgg\application\models\LinkIndexModel;



defined('\ABSPATH') || exit;

/**
 * ParserModuleConfig abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
abstract class ParserModuleConfig extends ModuleConfig
{
    public function options()
    {
        $tpl_manager = ModuleTemplateManager::getInstance($this->module_id);
        $options = array(
            'is_active' => array(
                'title' => __('Activate Module', 'content-egg') . ' **',
                'description' => __('Enable', 'content-egg'),
                'callback' => array($this, 'render_checkbox'),
                'default' => 0,
                'section' => 'default',
                'validator' => array(
                    array(
                        'call' => array($this, 'checkRequirements'),
                        'message' => __('Could not activate.', 'content-egg'),
                    ),
                ),
            ),
            'embed_at' => array(
                'title' => __('Auto-embedding', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'shortcode' => __('Use shortcodes only', 'content-egg'),
                    'post_top' => __('Embed at the beginning of the post', 'content-egg'),
                    'post_bottom' => __('Embed at the end of the post', 'content-egg'),
                ),
                'default' => 'shortcode',
                'section' => 'default',
            ),
            'priority' => array(
                'title' => __('Priority', 'content-egg'),
                'description' => __('Priority determines the order of modules for auto-embedding in a post, with 0 being the highest priority. This setting also affects price sorting.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => 10,
                'validator' => array(
                    'trim',
                    'absint',
                ),
                'section' => 'default',
            ),
            'template' => array(
                'title' => __('Template', 'content-egg'),
                'description' => __('Select the module template to be used by default.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => $tpl_manager->getTemplatesList(),
                'default' => $this->getModuleInstance()->defaultTemplateName(),
                'section' => 'default',
            ),
            'tpl_title' => array(
                'title' => __('Title', 'content-egg') . ' ' . __('(deprecated)', 'content-egg'),
                'description' => __('Templates can include the title when displaying data.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'trim',
                ),
                'section' => 'default',
            ),
            'featured_image' => array(
                'title' => __('Featured Image', 'content-egg'),
                'description' => __('Automatically set the featured image for the post using product images from this module.', 'content-egg'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Do not set', 'content-egg'),
                    'first' => __('First image', 'content-egg'),
                    'second' => __('Second image', 'content-egg'),
                    'rand' => __('Random image', 'content-egg'),
                    'last' => __('Last image', 'content-egg'),
                ),
                'default' => '',
                'section' => 'default',
            ),

            'ttl' => array(
                'title'       => __('Update by Keyword', 'content-egg'),
                'description' => __('Cache lifetime in seconds. After this period, content will be updated if a keyword is set for updating. Set to \'0\' to disable updates.', 'content-egg'),
                'callback'    => array($this, 'render_input'),
                'default'     => 0,
                'validator'   => array(
                    'trim',
                    'absint',
                ),
                'section'     => 'default',
            ),
            'update_mode' => array(
                'title'            => __('Update Mode', 'content-egg'),
                'description'      => __('Choose how content updates are triggered.', 'content-egg'),
                'callback'         => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    'visit'      => __('Page View', 'content-egg'),
                    'cron'       => __('Cron Job', 'content-egg'),
                    'visit_cron' => __('Page View + Cron Job', 'content-egg'),
                ),
                'default'          => 'visit',

            )
        );

        if ($this->getModuleInstance()->isClone())
        {
            $options['feed_name'] = array(
                'title' => __('Clone Module Name', 'content-egg') . ' <span class="cegg_required">*</span>',
                'callback' => array($this, 'render_input'),
                'default' => '',
                'validator' => array(
                    'trim',
                    '\sanitize_text_field',
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'required'),
                        'message' => sprintf(__('The field "%s" can not be empty.', 'content-egg'), __('Feed name', 'content-egg')),
                    ),
                    array(
                        'call' => array($this, 'saveModuleName'),
                        'type' => 'filter',
                    ),
                ),
            );
        }

        return array_merge(parent::options(), $options);
    }

    public function checkRequirements($value)
    {
        if ($this->getModuleInstance()->requirements())
            return false;
        else
            return true;
    }

    protected static function moveRequiredUp(array $options): array
    {
        $required   = [];
        $neutral    = [];
        $deprecated = [];

        // 1) Partition into three buckets, preserving keys
        foreach ($options as $key => $opt)
        {
            $title = $opt['title'];

            if (strpos($title, '*') !== false)
            {
                $required[$key] = $opt;
            }
            elseif (strpos($title, 'deprecated') !== false)
            {
                $deprecated[$key] = $opt;
            }
            else
            {
                $neutral[$key] = $opt;
            }
        }

        // 2) Re-assemble, preserving keys and order within each bucket
        $sorted = $required + $neutral + $deprecated;

        // 3) Strip only the ** markers from titles
        foreach ($sorted as $key => $opt)
        {
            $sorted[$key]['title'] = trim(str_replace('**', '', $opt['title']));
        }

        return $sorted;
    }

    public function applyCustomOptions(array $settings)
    {
        foreach ($settings as $name => $value)
        {
            if (isset($this->option_values[$name]))
                $this->option_values[$name] = $value;
        }
    }

    public function applayCustomOptions(array $settings)
    {
        $this->applyCustomOptions($settings);
    }

    public function saveModuleName($value)
    {
        ModuleName::getInstance()->saveName($this->getModuleId(), $value);
        return $value;
    }

    public function processLinkIndexBackfiller($value)
    {
        $old = (string) $this->option('set_local_redirect');
        $new = (string) $value;

        if ($new === $old)
        {
            return $value;
        }

        $moduleId = (string) $this->getModuleId();

        if (!(bool) $value)
        {
            // DISABLING: cancel pending redirect backfills for this module and schedule async deletion
            while ($ts = wp_next_scheduled('cegg_link_index_backfill_once', ['redirect', [$moduleId]]))
            {
                wp_unschedule_event($ts, 'cegg_link_index_backfill_once', ['redirect', [$moduleId]]);
            }
            while ($ts = wp_next_scheduled('cegg_link_index_delete_module', [$moduleId]))
            {
                wp_unschedule_event($ts, 'cegg_link_index_delete_module', [$moduleId]);
            }
            if (!wp_next_scheduled('cegg_link_index_delete_module', [$moduleId]))
            {
                wp_schedule_single_event(time() + 60, 'cegg_link_index_delete_module', [$moduleId]);
            }
            return $value;
        }

        // ENABLING: cancel pending deletions and schedule a redirect backfill for this module
        while ($ts = wp_next_scheduled('cegg_link_index_delete_module', [$moduleId]))
        {
            wp_unschedule_event($ts, 'cegg_link_index_delete_module', [$moduleId]);
        }
        if (!wp_next_scheduled('cegg_link_index_backfill_once', ['redirect', [$moduleId]]))
        {
            wp_schedule_single_event(time() + 15, 'cegg_link_index_backfill_once', ['redirect', [$moduleId]]);
        }

        return $value;
    }
}

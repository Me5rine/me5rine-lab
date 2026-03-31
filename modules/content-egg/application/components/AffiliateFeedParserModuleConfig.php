<?php

namespace ContentEgg\application\components;



defined('\ABSPATH') || exit;

/**
 * ParserModuleConfig abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
abstract class AffiliateFeedParserModuleConfig extends AffiliateParserModuleConfig
{

    public function options()
    {
        $options = array_merge(parent::options(), array(
            'entries_per_page' => array(
                'title' => __('Results', 'content-egg'),
                'description' => __('Specify the number of results to display for a single search query.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => 10,
                'validator' => array(
                    'trim',
                    'absint',
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'less_than_equal_to'),
                        'arg' => 200,
                        'message' => sprintf(__('The field "%s" can not be more than %d.', 'content-egg'), 'Results', 200),
                    ),
                ),
            ),
            'entries_per_page_update' => array(
                'title' => __('Results for updates', 'content-egg'),
                'description' => __('Specify the number of results for automatic updates and autoblogging.', 'content-egg'),
                'callback' => array($this, 'render_input'),
                'default' => 6,
                'validator' => array(
                    'trim',
                    'absint',
                    array(
                        'call' => array('\ContentEgg\application\helpers\FormValidator', 'less_than_equal_to'),
                        'arg' => 200,
                        'message' => sprintf(__('The field "%s" can not be more than %d.', 'content-egg'), 'Results', 200),
                    ),
                ),
            ),
            'partial_url_match' => array(
                'title' => __('Search Partial URL', 'content-egg'),
                'description' => __('Partial URL matching', 'content-egg')
                    . '<p class="description">' . __('Allows you to search for products using a portion of the URL.', 'content-egg') . '</p>',
                'callback' => array($this, 'render_checkbox'),
                'default' => true,
                'section' => 'default',
            ),
            'save_img' => array(
                'title' => __('Save images', 'content-egg'),
                'description' => __('Save images on server', 'content-egg'),
                'callback' => array($this, 'render_checkbox'),
                'default' => false,
                'section' => 'default',
            )
        ));

        $options['update_mode']['dropdown_options'] = array(
            'cron' => __('Cron', 'content-egg') . ' (' . __('recommended', 'content-egg') . ')',
            'visit' => __('Page view', 'content-egg'),
            'visit_cron' => __('Page view + Cron', 'content-egg'),
        );

        $options['update_mode']['default'] = 'cron';
        $options['ttl_items']['default'] = 86400;

        // reset feed data when setting are changed
        $options['update_mode']['validator'][] = array(
            'call' => array($this, 'resetFeedData'),
        );

        return $options;
    }

    public function resetFeedData()
    {
        $is_active = $this->get_submitted_value('is_active');
        $module = $this->getModuleInstance();
        $module->refreshFeedData($is_active);

        return true;
    }
}

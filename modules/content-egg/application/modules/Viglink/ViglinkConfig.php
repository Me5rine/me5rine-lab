<?php

namespace ContentEgg\application\modules\Viglink;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\AffiliateParserModuleConfig;;

/**
 * AffiliatewindowConfig class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ViglinkConfig extends AffiliateParserModuleConfig
{

	public function options()
	{
		$options = array(
			'apiKey' => array(
				'title' => 'API Key <span class="cegg_required">*</span>',
				'description' => sprintf(__('You can find your API Key in your dashboard <a target="_blank" href="%s">here</a>.', 'content-egg'), 'https://platform.sovrn.com/commerce/settings/site?tab=approved'),
				'callback' => array($this, 'render_input'),
				'default' => '',
				'validator' => array(
					'trim',
					array(
						'call'  => array('\ContentEgg\application\helpers\FormValidator', 'required'),
						'when' => 'is_active',
						'message' => sprintf(__('The field "%s" can not be empty.', 'content-egg'), 'API Key'),
					),
				),
			),
			'secretKey' => array(
				'title'       => 'Secret Key <span class="cegg_required">*</span>',
				'description' => sprintf(__('You can find your Secret Key in your dashboard <a target="_blank" href="%s">here</a>.', 'content-egg'), 'https://platform.sovrn.com/commerce/settings/site?tab=approved'),
				'callback'    => array($this, 'render_password'),
				'default'     => '',
				'validator'   => array(
					'trim',
					array(
						'call'    => array('\ContentEgg\application\helpers\FormValidator', 'required'),
						'when'    => 'is_active',
						'message' => sprintf(__('The field "%s" can not be empty.', 'content-egg'), 'Secret Key'),
					),
				),
			),
			'market' => array(
				'title' => __('Market', 'content-egg') . ' <span class="cegg_required">*</span>',
				'description' => __('The Market is a currency-language pair that usually corresponds to the region where the products are sold.', 'content-egg'),
				'callback' => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					'usd_en' => 'usd_en',
					'gbp_en' => 'gbp_en',
					'aud_en' => 'aud_en',
					'cad_en' => 'cad_en',
					'eur_de' => 'eur_de',
					'eur_it' => 'eur_it',
					'eur_fr' => 'eur_fr',
					'eur_es' => 'eur_es',
					'eur_nl' => 'eur_nl',
					'chf_de' => 'chf_de',
				),
				'default' => 'usd_en',
			),
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
						'message' => sprintf(__('The field "%s" can not be more than %d.', 'content-egg'), 'Results', 100),
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
						'message' => sprintf(__('The field "%s" can not be more than %d.', 'content-egg'), 'Results for updates', 100),
					),
				),
			),
			'priceFrom'               => array(
				'title'       => __('Price From', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
				),
				'metaboxInit' => true,
			),
			'priceTo'                 => array(
				'title'       => __('Price To', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
				),
				'metaboxInit' => true,
			),
			'stock_status'            => array(
				'title'            => __('Stock status', 'content-egg'),
				'description'      => __('Set the status if the product is not found.', 'content-egg'),
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					'unknown'      => 'Unknown',
					'out_of_stock' => 'Out of stock',
				),
				'default'          => 'unknown',
			),
			'save_img'                => array(
				'title'       => __('Save images', 'content-egg'),
				'description' => __('Save images localy', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
			),
		);

		$parent = parent::options();
		$options = array_merge($parent, $options);
		$options['update_mode']['default']   = 'cron';

		return self::moveRequiredUp($options);
	}
}

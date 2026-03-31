<?php

namespace ContentEgg\application\components;;

defined('\ABSPATH') || exit;

/**
 * ParserModuleConfig abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
abstract class AffiliateParserModuleConfig extends ParserModuleConfig
{

	public function options()
	{
		$options = array();

		if ($this->getModuleInstance()->isItemsUpdateAvailable())
		{
			$options['ttl_items'] = array(
				'title'       => __('Price Update', 'content-egg'),
				'description' => __("Set the interval in seconds for updating prices. Use '0' to disable price updates.", 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => 259200,
				'validator'   => array(
					'trim',
					'absint',
				),
				'section'     => 'default',
			);
		}

		$options['ttl'] = array(
			'title'       => __('Update by Keyword', 'content-egg'),
			'description' => __('Cache lifetime in seconds. After this period, products will be updated if a keyword is set for updating. Set to \'0\' to disable updates.', 'content-egg'),
			'callback'    => array($this, 'render_input'),
			'default'     => 604800,
			'validator'   => array(
				'trim',
				'absint',
			),
			'section'     => 'default',
		);

		$options['update_mode'] = array(
			'title'            => __('Update Mode', 'content-egg'),
			'description'      => __('Choose how product updates are triggered.', 'content-egg'),
			'callback'         => array($this, 'render_dropdown'),
			'dropdown_options' => array(
				'visit'      => __('Page View', 'content-egg'),
				'cron'       => __('Cron Job', 'content-egg'),
				'visit_cron' => __('Page View + Cron Job', 'content-egg'),
			),
			'default'          => 'visit',
		);

		$options['set_local_redirect'] = [
			'title'       => esc_html__('Link Cloaking', 'content-egg'),
			'description' => esc_html__('Route affiliate links through local redirect URLs', 'content-egg')
				. '<p class="description">' . esc_html__('Note:', 'content-egg') . ' '
				. esc_html__('After enabling, the plugin will index existing product links in the background. On larger sites this can take several minutes.', 'content-egg')
				. '</p>',
			'callback' => array($this, 'render_dropdown'),
			'dropdown_options' => array(
				'0' => __('Disabled', 'content-egg'),
				'1' => __('Enabled', 'content-egg'),
			),
			'default'     => '0',
			'validator'   => [
				['call' => [$this, 'processLinkIndexBackfiller'], 'type' => 'filter'],
			],
			'section'     => 'default',
		];

		if (stripos($this->module_id, 'amazon') !== false)
		{
			$options['set_local_redirect']['description'] .=
				'<p class="description">' . __('Warning:', 'content-egg') . ' ' .
				__('Using local redirects for Amazon links can violate the Amazon Associates Program policies. Enable this only if you understand the risks.', 'content-egg') .
				'</p>';
		}

		return
			array_merge(
				parent::options(),
				$options
			);
	}
}

<?php

namespace ContentEgg\application\modules\Youtube;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ParserModuleConfig;

/**
 * YoutubeConfig class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class YoutubeConfig extends ParserModuleConfig
{
	public function options()
	{
		$options = array(
			'api_key' => array(
				'title'       => 'API Key <span class="cegg_required">*</span>',
				'description' => __('API access key. You can get in Google <a href="http://code.google.com/apis/console">API console</a>.', 'content-egg'),
				'callback'    => array($this, 'render_password'),
				'default'     => '',
				'validator'   => array(
					'trim',
					array(
						'call'    => array('\ContentEgg\application\helpers\FormValidator', 'required'),
						'when'    => 'is_active',
						'message' => __('The "API Key" can not be empty', 'content-egg'),
					),
				),
				'section'     => 'default',
			),
			'entries_per_page'        => array(
				'title'       => __('Results', 'content-egg'),
				'description' => __('Specify the number of results to display for a single search query', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '5',
				'validator'   => array(
					'trim',
					'absint',
				),
				'section'     => 'default',
			),
			'entries_per_page_update' => array(
				'title'       => __('Results for autoupdates ', 'content-egg'),
				'description' => __('Maximum number of results returned for keyword autoupdates and other automatic searches.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => 3,
				'validator'   => array(
					'trim',
					'absint',
				),
				'section'     => 'default',
			),
			'order'                   => array(
				'title'            => __('Sorting', 'content-egg'),
				'description'      => '',
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					'date'      => __('Date', 'content-egg'),
					'rating'    => __('Rating', 'content-egg'),
					'relevance' => __('Relevance', 'content-egg'),
					'title'     => __('Title', 'content-egg'),
					'viewCount' => __('Views', 'content-egg'),
				),
				'default'          => 'relevance',
				'section'          => 'default',
				'metaboxInit'      => true,
			),
			'license'                 => array(
				'title'            => __('Type of license', 'content-egg'),
				'description'      => __('Many videos on Youtube have Creative Commons license. <a href="http://www.google.com/support/youtube/bin/answer.py?answer=1284989">Know more</a>.', 'content-egg'),
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					'any'            => __('Any license', 'content-egg'),
					'creativeCommon' => __('Creative Commons license', 'content-egg'),
					'youtube'        => __('Standard license', 'content-egg'),
				),
				'default'          => 'any',
				'section'          => 'default',
				'metaboxInit'      => true,
			),
			'description_size'        => array(
				'title'       => __('Trim description', 'content-egg'),
				'description' => __('Description size in characters (0 - do not cut)', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '280',
				'validator'   => array(
					'trim',
					'absint',
				),
				'section'     => 'default',
			),
		);
		$parent  = parent::options();

		$options = array_merge($parent, $options);

		return self::moveRequiredUp($options);
	}
}

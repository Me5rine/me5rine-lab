<?php

namespace ContentEgg\application\modules\Amazon;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\AffiliateParserModuleConfig;
use ContentEgg\application\admin\GeneralConfig;

/**
 * AmazonConfig class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class AmazonConfig extends AffiliateParserModuleConfig
{

	public function options()
	{
		$options = array(
			'access_key_id'           => array(
				'title'       => 'Access Key ID <span class="cegg_required">*</span>',
				'description' => __('Your Access Key ID which uniquely identifies you.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
					array(
						'call'    => array('\ContentEgg\application\helpers\FormValidator', 'required'),
						'when'    => 'is_active',
						'message' => __('The "Access Key ID" can not be empty', 'content-egg'),
					),
				),
				'section'     => 'default',
			),
			'secret_access_key'       => array(
				'title'       => 'Secret Access Key <span class="cegg_required">*</span>',
				'description' => __('A key that is used in conjunction with the Access Key ID to cryptographically sign an API request. To retrieve your Access Key ID or Secret Access Key, refer to <a target="_blank" href="https://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingDev.html">Becoming Product Advertising API Developer</a>.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
					array(
						'call'    => array('\ContentEgg\application\helpers\FormValidator', 'required'),
						'when'    => 'is_active',
						'message' => __('The "Secret Access Key" can not be empty.', 'content-egg'),
					),
				),
				'section'     => 'default',
			),
			'associate_tag'           => array(
				'title'       => __('Default Associate Tag', 'content-egg') . ' <span class="cegg_required">*</span>',
				'description' => __('An alphanumeric token that uniquely identifies you as an Associate. To obtain an Associate Tag, refer to <a target="_blank" href="https://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingAssociate.html">Becoming an Associate</a>.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
					array(
						'call'    => array('\ContentEgg\application\helpers\FormValidator', 'required'),
						'when'    => 'is_active',
						'message' => __('The "Tracking ID" can not be empty.', 'content-egg'),
					),
				),
				'section'     => 'default',
				'metaboxInit' => true,
			),
			'forced_urls_update'      => array(
				'title'       => __('Forced links update', 'content-egg'),
				'description' => __('Override/update existing links with new Tracking ID.', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
			'locale'                  => array(
				'title'            => __('Default locale', 'content-egg'),
				'description'      => __('The branch/locale of Amazon. Each branch requires a separate registration in certain affiliate program.', 'content-egg'),
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => self::getLocalesList(),
				'default'          => self::getDefaultLocale(),
				'section'          => 'default',
			),
			'entries_per_page' => array(
				'title'       => __('Results', 'content-egg'),
				'description' => __('Specify the number of results to display for a single search query. It may take additional time to retrieve more than 10 results in one request.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => 10,
				'validator'   => array(
					'trim',
					'absint',
					array(
						'call'    => array('\ContentEgg\application\helpers\FormValidator', 'less_than_equal_to'),
						'arg'     => 50,
						'message' => __('The field "Results" cannot exceed 50.', 'content-egg'),
					),
				),
				'section'     => 'default',
			),

			'entries_per_page_update' => array(
				'title'       => __('Results for Updates and Autoblogging', 'content-egg'),
				'description' => __('Specify the number of results for automatic updates and autoblogging. It may take additional time to retrieve more than 10 results in one request.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => 3,
				'validator'   => array(
					'trim',
					'absint',
					array(
						'call'    => array('\ContentEgg\application\helpers\FormValidator', 'less_than_equal_to'),
						'arg'     => 50,
						'message' => __('The field "Results" cannot exceed 50.', 'content-egg'),
					),
				),
				'section'     => 'default',
			),

			'link_type' => array(
				'title'            => __('Link Type', 'content-egg'),
				'description'      => __('Select the type of partner links. Learn more about the Amazon <a target="_blank" href="https://affiliate-program.amazon.com/gp/associates/help/t2/a11">90-day cookie</a>.', 'content-egg'),
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					'product'     => __('Product Page', 'content-egg'),
					'add_to_cart' => __('Add to Cart', 'content-egg'),
				),
				'default'          => 'product',
				'section'          => 'default',
			),

			'search_index'            => array(
				'title'            => __('Categories for search', 'content-egg'),
				'description'      => __('The list of categories for US Amazon. For local branches some of categories may be not available. If you do not set category for searching, no other filtering options in addition to searching for the keyword (for example, the minimal price or sorting) will not working. ', 'content-egg')
					. ' ' . __('Search by EAN require a Category to be specified.', 'content-egg'),
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					'All'                 => '[ All ]',
					'Blended'             => '[ Blended ]',
					'Music'               => '[ Music ]',
					'Video'               => '[ Video ]',
					'Apparel'             => 'Apparel',
					'Automotive'          => 'Automotive',
					'Baby'                => 'Baby',
					'Beauty'              => 'Beauty',
					'Books'               => 'Books',
					'Classical'           => 'Classical',
					'DigitalMusic'        => 'DigitalMusic',
					'DVD'                 => 'DVD',
					'Electronics'         => 'Electronics',
					'GourmetFood'         => 'GourmetFood',
					'Grocery'             => 'Grocery',
					'HealthPersonalCare'  => 'HealthPersonalCare',
					'HomeGarden'          => 'HomeGarden',
					'Industrial'          => 'Industrial',
					'Jewelry'             => 'Jewelry',
					'KindleStore'         => 'KindleStore',
					'Kitchen'             => 'Kitchen',
					'Magazines'           => 'Magazines',
					'Merchants'           => 'Merchants',
					'Miscellaneous'       => 'Miscellaneous',
					'MP3Downloads'        => 'MP3Downloads',
					'MusicalInstruments'  => 'MusicalInstruments',
					'MusicTracks'         => 'MusicTracks',
					'OfficeProducts'      => 'OfficeProducts',
					'OutdoorLiving'       => 'OutdoorLiving',
					'PCHardware'          => 'PCHardware',
					'PetSupplies'         => 'PetSupplies',
					'Photo'               => 'Photo',
					'Shoes'               => 'Shoes',
					'Software'            => 'Software',
					'SportingGoods'       => 'SportingGoods',
					'Tools'               => 'Tools',
					'Toys'                => 'Toys',
					'UnboxVideo'          => 'UnboxVideo',
					'VHS'                 => 'VHS',
					'VideoGames'          => 'VideoGames',
					'Watches'             => 'Watches',
					'Wireless'            => 'Wireless',
					'WirelessAccessories' => 'WirelessAccessories'
				),
				'default'          => 'All',
				'section'          => 'default',
			),
			'sort'                    => array(
				'title'       => __('Sorting order', 'content-egg'),
				'description' => __('Sorting variants depend on locale and chosed category. List of all available values you can find <a href="http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/index.html?APPNDX_SortValuesArticle.html">here</a>.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
				),
				'section'     => 'default',
			),
			'brouse_node'             => array(
				'title'       => __('Brouse node', 'content-egg'),
				'description' => __('Integer ID "node" on Amazon. The search will be made only in this "node".', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
				),
				'section'     => 'default',
			),
			'title'                   => array(
				'title'       => __('Search in title', 'content-egg'),
				'description' => __('The search will produce only by product name.', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
			'merchant_id'             => array(
				'title'       => __('Only Amazon', 'content-egg'),
				'description' => __('Select products that are selling by Amazon. Other sellers are excluded from the search.', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
			'minimum_price'           => array(
				'title'       => __('Minimal price', 'content-egg'),
				'description' => __('Example, 8.99', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
				),
				'metaboxInit' => true,
			),
			'maximum_price'           => array(
				'title'       => __('Maximal price', 'content-egg'),
				'description' => __('Example, 98.50', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
				),
				'metaboxInit' => true,
			),
			'min_percentage_off'      => array(
				'title'            => __('Minimal discount', 'content-egg'),
				'description'      => __('Choose products with discount. You must set category of product. Note, that this option works not for all categories.', 'content-egg'),
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					''    => __('Any', 'content-egg'),
					'5%'  => '5%',
					'10%' => '10%',
					'15%' => '15%',
					'20%' => '20%',
					'25%' => '25%',
					'30%' => '30%',
					'35%' => '35%',
					'40%' => '40%',
					'45%' => '45%',
					'50%' => '50%',
					'60%' => '60%',
					'70%' => '70%',
					'80%' => '80%',
					'90%' => '90%',
					'95%' => '95%',
				),
				'default'          => '',
				'section'          => 'default',
				'metaboxInit'      => true,
			),
			'customer_reviews'        => array(
				'title'       => __('Customer reviews', 'content-egg'),
				'description' => __('Get user reviews. Reviews will be in iframe. Iframe url is valid only 24 hours, please, use autoupdating function with less than 24 hour to keep actual url.', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
			'truncate_reviews_at'     => array(
				'title'       => __('Cut reviews', 'content-egg'),
				'description' => __('Number of characters for one review. 0 - the maximal length of the text.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => 500,
				'validator'   => array(
					'trim',
					'absint',
				),
				'section'     => 'default',
			),
			'editorial_reviews'       => array(
				'title'       => __('Parse description', 'content-egg'),
				'description' => __('Parse description of products from seller', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
			'editorial_reviews_type'  => array(
				'title'            => __('Type of description', 'content-egg'),
				'description'      => '',
				'callback'         => array($this, 'render_dropdown'),
				'dropdown_options' => array(
					'allow_all'    => __('Like on Amazon', 'content-egg'),
					'safe_html'    => __('Safe HTML', 'content-egg'),
					'allowed_tags' => __('Only allowed HTML tags', 'content-egg'),
					'text'         => __('Text only', 'content-egg'),
				),
				'default'          => 'All',
				'section'          => 'default',
			),
			'editorial_reviews_size'  => array(
				'title'       => __('Size of description', 'content-egg'),
				'description' => __('The maximum size of the item description. 0 - do not cut.', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => 1000,
				'validator'   => array(
					'trim',
					'absint',
				),
				'section'     => 'default',
			),
			'https_img'               => array(
				'title'       => __('Use images with https', 'content-egg'),
				'description' => __('Rewrite url of images with https. Use it if you have SSL on your domain', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
			'save_img'                => array(
				'title'       => __('Save images', 'content-egg'),
				'description' => __('Save images on server', 'content-egg') . ' ' . __('Enabling this option violates rules of API.', 'content-egg'),
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
			'show_small_logos'        => array(
				'title'       => __('Small logos', 'content-egg'),
				'description' => __('Show small logos', 'content-egg') . '<p class="description">' . sprintf(__('Read more: <a target="_blank" href="%s">Amazon brand usage guidelines</a>.', 'content-egg'), 'https://advertising.amazon.com/ad-specs/en/policy/brand-usage') . '</p>',
				'callback'    => array($this, 'render_checkbox'),
				'default'     => false,
				'section'     => 'default',
			),
		);

		foreach (self::getLocalesList() as $locale_id => $locale_name)
		{
			$options['associate_tag_' . $locale_id] = array(
				'title'       => sprintf(__('Associate Tag for %s locale', 'content-egg'), $locale_name),
				'description' => __('Type here your tracking ID for this locale if you need multiple locale parsing', 'content-egg'),
				'callback'    => array($this, 'render_input'),
				'default'     => '',
				'validator'   => array(
					'trim',
				),
				'metaboxInit' => true,
			);
		}

		$parent                         = parent::options();
		$parent['ttl_items']['default'] = 86400;

		return array_merge($parent, $options);
	}

	public static function getLocalesList()
	{
		return array(
			'us' => 'US',
			'uk' => 'UK',
			'de' => 'DE',
			'jp' => 'JP',
			'cn' => 'CN',
			'fr' => 'FR',
			'it' => 'IT',
			'es' => 'ES',
			'ca' => 'CA',
			'br' => 'BR',
			'in' => 'IN',
			'mx' => 'MX',
			'au' => 'AU'
		);
	}

	public static function getDefaultLocale()
	{
		return 'us';
	}

	public static function getActiveLocalesList()
	{
		$locales = self::getLocalesList();
		$active  = array();

		$default            = self::getInstance()->option('locale');
		$active[$default] = $locales[$default];

		foreach ($locales as $locale => $name)
		{
			if ($locale == $default)
			{
				continue;
			}
			if (self::getInstance()->option('associate_tag_' . $locale))
			{
				$active[$locale] = $name;
			}
		}

		return $active;
	}

	public static function getDomainByLocale($locale)
	{
		$domains = array(
			'us' => 'amazon.com',
			'uk' => 'amazon.co.uk',
			'de' => 'amazon.de',
			'jp' => 'amazon.jp',
			'cn' => 'amazon.cn',
			'fr' => 'amazon.fr',
			'it' => 'amazon.it',
			'es' => 'amazon.es',
			'ca' => 'amazon.ca',
			'br' => 'amazon.com.br',
			'in' => 'amazon.in',
			'mx' => 'amazon.com.mx',
			'au' => 'amazon.com.au',
		);
		if (isset($domains[$locale]))
		{
			return $domains[$locale];
		}
		else
		{
			return 'amazon.com';
		}
	}
}

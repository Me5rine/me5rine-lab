<?php

namespace ContentEgg\application\modules\Pixabay;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ParserModule;
use ContentEgg\application\libs\pixabay\PixabaySearch;
use ContentEgg\application\components\Content;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\admin\GeneralConfig;;

/**
 * PixabayModule class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class PixabayModule extends ParserModule
{

	public function info()
	{
		return array(
			'name'          => 'Pixabay',
			'description'   => __('Search photo with free license CC0 Public Domain on pixabay.com', 'content-egg'),
			'api_agreement' => 'https://pixabay.com/api/docs/',
		);
	}

	public function getParserType()
	{
		return self::PARSER_TYPE_IMAGE;
	}

	public function defaultTemplateName()
	{
		return 'data_image';
	}

	public function isFree()
	{
		return true;
	}

	public static function availableLanguages()
	{
		return array(
			'cs',
			'da',
			'de',
			'en',
			'es',
			'fr',
			'id',
			'it',
			'hu',
			'nl',
			'no',
			'pl',
			'pt',
			'ro',
			'sk',
			'fi',
			'sv',
			'tr',
			'vi',
			'th',
			'bg',
			'ru',
			'el',
			'ja',
			'ko',
			'zh'
		);
	}

	public function doRequest($keyword, $query_params = array(), $is_autoupdate = false)
	{
		if ($is_autoupdate)
		{
			$options['per_page'] = $this->config('entries_per_page_update');
		}
		else
		{
			$options['per_page'] = $this->config('entries_per_page');
		}

		$options['image_type']  = $this->config('image_type');
		$options['orientation'] = $this->config('orientation');
		if ($this->config('category'))
		{
			$options['category'] = $this->config('category');
		}
		$options['order'] = $this->config('order');
		if ($this->config('editors_choice'))
		{
			$options['editors_choice'] = true;
		}
		if ($this->config('safesearch'))
		{
			$options['safesearch'] = true;
		}

		$lang = GeneralConfig::getInstance()->option('lang');
		if (in_array($lang, self::availableLanguages()))
		{
			$options['lang'] = $lang;
		}

		try
		{
			$api_client = new PixabaySearch($this->config('key'));
			$results    = $api_client->search($keyword, $options);
		}
		catch (Exception $e)
		{
			throw new \Exception(esc_html(wp_strip_all_tags($e->getMessage())));
		}

		if (!isset($results['hits']))
		{
			return array();
		}

		return $this->prepareResults($results['hits']);
	}

	private function prepareResults($results)
	{
		$data = array();
		foreach ($results as $key => $r)
		{
			$content            = new Content;
			$content->unique_id = $r['id'];
			$content->title     = strip_tags($r['tags']);
			$content->url       = $r['pageURL'];
			$size               = $this->config('size');

			if ($size == 'large' && isset($r['largeImageURL']))
				$content->img = $r['largeImageURL'];
			else
				$content->img = $r['webformatURL'];

			$extra = new ExtraDataPixabay;
			ExtraDataPixabay::fillAttributes($extra, $r);
			$content->extra = $extra;
			$data[]         = $content;
		}

		return $data;
	}

	public function renderResults()
	{
		PluginAdmin::render('_metabox_results', array('module_id' => $this->getId()));
	}

	public function renderSearchResults()
	{
		PluginAdmin::render('_metabox_search_results_images', array('module_id' => $this->getId()));
	}
}

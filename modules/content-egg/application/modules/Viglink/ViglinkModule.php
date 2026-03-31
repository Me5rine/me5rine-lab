<?php

namespace ContentEgg\application\modules\Viglink;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\AffiliateParserModule;
use ContentEgg\application\libs\viglink\ViglinkApi;
use ContentEgg\application\components\ContentProduct;
use ContentEgg\application\admin\PluginAdmin;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\components\LinkHandler;;

/**
 * ViglinkModule class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ViglinkModule extends AffiliateParserModule
{

	private $api_client = null;

	public function info()
	{
		return array(
			'name'        => 'Sovrn',
			'docs_uri'    => 'https://ce-docs.keywordrush.com/modules/affiliate/viglink',
		);
	}

	public function releaseVersion()
	{
		return '12.15.0';
	}

	public function isFree()
	{
		return true;
	}

	public function isUrlSearchAllowed()
	{
		return true;
	}

	public function isItemsUpdateAvailable()
	{
		return true;
	}

	public function getParserType()
	{
		return self::PARSER_TYPE_PRODUCT;
	}

	public function defaultTemplateName()
	{
		return 'grid';
	}

	public function doRequest($keyword, $query_params = array(), $is_autoupdate = false)
	{
		$options = array();
		if ($is_autoupdate)
			$options['limit'] = $this->config('entries_per_page_update');
		else
			$options['limit'] = $this->config('entries_per_page');

		if ($price_filter = $this->getPriceFilter($query_params))
			$options['price-range'] = $price_filter;

		if (TextHelper::isAsin($keyword) || TextHelper::isEan($keyword))
			$options['barcode'] = $keyword;
		elseif (TextHelper::isUrl($keyword))
			$options['plainlink'] = $keyword;
		else
			$options['search-keywords'] = $keyword;

		$results = $this->getApiClient()->search($options);

		if (!$results || !is_array($results))
			return array();

		return $this->prepareResults($results);
	}

	public function doRequestItems(array $items)
	{
		foreach ($items as $key => $item)
		{
			$options = array();
			$options['limit'] = 10;
			$options['search-keywords'] = $item['title'];

			try
			{
				$results = $this->getApiClient()->search($options);
			}
			catch (\Exception $e)
			{
				continue;
			}

			if (!$results || !is_array($results))
				return array();

			$results = $this->prepareResults($results);

			$product = null;
			foreach ($results as $i => $r)
			{
				if ($this->isProductsMatch($item, $r))
				{
					$product = $r;
					break;
				}
			}

			if (!$product)
			{
				if ($this->config('stock_status') == 'out_of_stock')
					$items[$key]['stock_status'] = ContentProduct::STOCK_STATUS_OUT_OF_STOCK;
				else
					$items[$key]['stock_status'] = ContentProduct::STOCK_STATUS_UNKNOWN;

				continue;
			}

			// assign new price
			$items[$key]['stock_status'] = ContentProduct::STOCK_STATUS_IN_STOCK;
			$items[$key]['price'] = $product->price;
			$items[$key]['priceOld'] = $product->priceOld;
			$items[$key]['url'] = $product->url;
		}

		return $items;
	}

	private function prepareResults($results)
	{
		$data = array();

		foreach ($results as $key => $r)
		{
			$content = new ContentProduct;

			if (empty($r['id']))
			{
				continue;
			}

			$content->unique_id = $r['id'];
			$content->title = $r['name'];
			$content->img = $r['image'];

			$content->price = (float)$r['salePrice'];
			if ((float)$r['retailPrice'] != $content->price)
				$content->priceOld = (float)$r['retailPrice'];
			else
				$content->priceOld = 0;

			$content->currencyCode = $r['currency'];

			if (isset($r['merchant']['name']))
				$content->merchant = $r['merchant']['name'];

			if ($domain = self::getMerchantDomain($content->merchant))
				$content->domain = $domain;
			else
				$content->domain = $content->merchant;

			if (isset($r['merchant']['logo']))
				$content->logo = $r['merchant']['logo'];

			$content->url = $r['deeplink'];

			if ($content->price)
				$content->stock_status = ContentProduct::STOCK_STATUS_UNKNOWN;

			$content->extra = new ExtraDataViglink();
			ExtraDataViglink::fillAttributes($content->extra, $r);

			$data[] = $content;
		}

		return $data;
	}

	private function getApiClient()
	{
		if ($this->api_client === null)
		{
			$this->api_client = new ViglinkApi($this->config('apiKey'), $this->config('secretKey'), $this->config('market'));
		}

		return $this->api_client;
	}

	public function renderResults()
	{
		PluginAdmin::render('_metabox_results', array('module_id' => $this->getId()));
	}

	public function renderSearchResults()
	{
		PluginAdmin::render('_metabox_search_results', array('module_id' => $this->getId()));
	}

	public function renderSearchPanel()
	{
		$this->render('search_panel', array('module_id' => $this->getId()));
	}

	public function renderUpdatePanel()
	{
		$this->render('update_panel', array('module_id' => $this->getId()));
	}

	protected function getPriceFilter(array $query_params)
	{
		if (!empty($query_params['priceFrom']))
			$priceFrom = round((float) $query_params['priceFrom'], 2);
		elseif ($this->config('priceFrom'))
			$priceFrom = round((float) $this->config('priceFrom'), 2);
		else
			$priceFrom = '*';

		if (!empty($query_params['priceTo']))
			$priceTo = round((float) $query_params['priceTo'], 2);
		elseif ($this->config('priceTo'))
			$priceTo = round((float) $this->config('priceTo'), 2);
		else
			$priceTo = '*';

		if ($priceFrom == '*' && $priceTo == '*')
			return '';

		return $priceFrom . '-' . $priceTo;
	}

	public function isProductsMatch(array $p1, ContentProduct $p2)
	{
		$p2 = json_decode(json_encode($p2), true);

		if ($p1['url'] == $p2['url'])
			return true;

		if ($p1['title'] == $p2['title'] && $p1['domain'] == $p2['domain'])
			return true;

		if ($p1['title'] == $p2['title'] && $p1['merchant'] == $p2['merchant'])
			return true;

		if ($p1['img'] && $p1['img'] == $p2['img'])
			return true;

		return false;
	}

	public static function getMerchantDomain($merchant)
	{
		$list = self::getMerchantDomains();
		if (isset($list[$merchant]))
			return $list[$merchant];
		else
			return false;
	}

	public static function getMerchantDomains()
	{
		$m2d = array(
			'Best Buy' => 'bestbuy.com',
			'Walmart' => 'walmart.com',
			'Adorama' => 'adorama.com',
			'B&H Photo Video' => 'bhphotovideo.com',
			'Kohl\'s' => 'kohls.com',
			'Bloomingdale' => 'bloomingdales.com',
			'Crutchfield' => 'crutchfield.com',
			'Apple' => 'apple.com',
			'QVC' => 'qvc.com',
			'Belk' => 'belk.com',
			'REVOLVE' => 'revolve.com',
			'Macy\'s' => 'macys.com',
			'Urban Outfitters' => 'urbanoutfitters.com',
			'DICK\'S Sporting Goods' => 'dickssportinggoods.com',
			'Tractor Supply' => 'tractorsupply.com',
			'JCPenney' => 'jcpenney.com',
			'Verizon' => 'verizon.com',
		);

		return \apply_filters('cegg_viglink_merchant2domain', $m2d);
	}

	public static function getPriceParamMap()
	{
		return [
			'min' => 'priceFrom',
			'max' => 'priceTo',
		];
	}
}

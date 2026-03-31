<?php

namespace ContentEgg\application\libs\viglink;

defined('\ABSPATH') || exit;

use ContentEgg\application\libs\RestClient;

/**
 * ViglinkApi class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 * @link: https://developer.sovrn.com/reference/product-affiliate-api
 *
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'RestClient.php';

class ViglinkApi extends RestClient
{
	protected static $timeout = 40; //sec

	const API_URI_BASE = 'https://comparisons.sovrn.com/api/affiliate/v3.5/sites/{site-api-key}/compare/prices/{market}';

	protected $apiKey;
	protected $secretKey;
	protected $market;
	protected $_responseTypes = array(
		'json',
	);

	public function __construct($apiKey, $secretKey, $market, $response_type = 'json')
	{
		$this->apiKey = $apiKey;
		$this->secretKey = $secretKey;
		$this->market = $market;
		$this->setResponseType($response_type);
		$url = self::API_URI_BASE;
		$url = str_replace('{site-api-key}', urlencode($this->apiKey), $url);
		$url = str_replace('{market}', urlencode($this->market), $url);
		$this->setUri($url);
	}

	/**
	 * Product Search
	 * @link: https://developer.sovrn.com/reference/product-affiliate-api
	 */
	public function search(array $options)
	{
		$response = $this->restGet('/by/accuracy', $options);
		return $this->_decodeResponse($response);
	}

	public function restGet($path, array $query = null)
	{
		$this->setCustomHeaders(array('Authorization' => 'secret ' . $this->secretKey));
		return parent::restGet($path, $query);
	}
}

<?php

namespace ContentEgg\application\modules\Amazon;

defined('\ABSPATH') || exit;

/**
 * ExtraAmazonCustomerReviews class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
//параметры из AmazonProduct->parseCustomerReviews
class ExtraAmazonCustomerReviews
{

	public $IFrameURL;
	public $HasReviews;
	public $AverageRating;
	public $TotalReviews;
	public $reviews = array();
}

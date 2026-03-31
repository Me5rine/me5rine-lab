<?php

namespace ContentEgg\application\modules\CjLinks;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ExtraData;

/**
 * ExtraDataCjLinks class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ExtraDataCjLinks extends ExtraData
{

	public $advertiserId;
	public $advertiserName;
	public $advertiserSite;
	public $creativeHeight;
	public $creativeWidth;
	public $language;
	public $linkHtml;
	public $destination;
	public $linkName;
	public $linkType;
	public $promotionStartDate;
	public $promotionEndDate;
	public $promotionType;
	public $couponCode;
	public $category;
}

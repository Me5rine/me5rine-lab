<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

/**
 * ContentCoupon class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ContentCoupon extends Content
{

	public $code;
	public $startDate;
	public $endDate;
	public $domain;
	public $merchant;
	public $logo;
}

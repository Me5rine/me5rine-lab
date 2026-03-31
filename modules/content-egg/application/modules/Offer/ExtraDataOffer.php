<?php

namespace ContentEgg\application\modules\AE;

defined('\ABSPATH') || exit;

use ContentEgg\application\components\ExtraData;

/**
 * ExtraDataAE class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ExtraDataAE extends ExtraData
{

	public $features = array();
	public $comments = array();
	public $images = array();
	public $data = array();
	public $last_error = array();
}

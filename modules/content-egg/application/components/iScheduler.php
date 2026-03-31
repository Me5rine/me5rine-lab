<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

/**
 * Scheduler interface file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
interface iScheduler
{

	public static function getCronTag();

	public static function run();
}

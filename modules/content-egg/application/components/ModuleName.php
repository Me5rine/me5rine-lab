<?php

namespace ContentEgg\application\components;



defined('\ABSPATH') || exit;

/**
 * ModuleName class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ModuleName
{

	const OPTION_NAME = 'cegg_feed_names';

	private static $instance;
	protected static $feeds = array();

	private function __construct()
	{
		$this->init();
	}

	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function init()
	{
		self::$feeds = \get_option(self::OPTION_NAME, array());
	}

	public function getName($module_id)
	{
		if (isset(self::$feeds[$module_id]))
		{
			return self::$feeds[$module_id];
		}
		else
		{
			return false;
		}
	}

	public function saveName($module_id, $name)
	{
		self::$feeds[$module_id] = $name;
		\update_option(self::OPTION_NAME, self::$feeds);
	}

	public function deleteName($module_id)
	{
		if (isset(self::$feeds[$module_id]))
		{
			unset(self::$feeds[$module_id]);
			\update_option(self::OPTION_NAME, self::$feeds);
			return true;
		}
		return false;
	}
}

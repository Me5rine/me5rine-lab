<?php

namespace ContentEgg\application\components\command;

defined('\ABSPATH') || exit;

/**
 * CommandInterface class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
interface CommandInterface
{

	/**
	 * Executes the command.
	 *
	 * @param array $arguments
	 * @param array $options
	 */
	public function __invoke($arguments, $options);

	/**
	 * Get the command name.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get the positional and associative arguments a command accepts.
	 *
	 * @return array
	 */
	public function getSynopsis();

	/**
	 * Get the command description.
	 *
	 * @return string
	 */
	public function getDescription();
}

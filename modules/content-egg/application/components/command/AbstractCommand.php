<?php

namespace ContentEgg\application\components\command;

defined('\ABSPATH') || exit;

/**
 * AbstractCommand class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
abstract class AbstractCommand implements CommandInterface
{
	/**
	 * {@inheritdoc}
	 */
	final public function getName()
	{
		return sprintf('cegg %s', $this->getCommandName());
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSynopsis()
	{
		return [];
	}

	/**
	 * Get the "cegg" command name.
	 *
	 * @return string
	 */
	abstract protected function getCommandName();
}

<?php

namespace ContentEgg\application\components\command;

defined('\ABSPATH') || exit;

/**
 * CommandFactory class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class CommandFactory
{
	static public function initAction()
	{
		if (!defined('\WP_CLI') || !\WP_CLI || !class_exists('\WP_CLI'))
			return;

		\add_action('cli_init', [__CLASS__, 'registerCommands']);
	}

	static public function registerCommands()
	{
		$command = new UpdateByKeywordCommand();
		self::registerCommand($command);
	}

	static public function registerCommand(CommandInterface $command)
	{
		\WP_CLI::add_command($command->getName(), $command, [
			'shortdesc' => $command->getDescription(),
			'synopsis' => $command->getSynopsis(),
		]);
	}
}

<?php

namespace ContentEgg\application\components\command;

use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\ModuleUpdateScheduler;
use ContentEgg\application\components\ContentManager;
use ContentEgg\application\components\stopwatch\Stopwatch;;

defined('\ABSPATH') || exit;

/**
 * UpdateByKeywordCommand class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class UpdateByKeywordCommand extends AbstractCommand
{
	protected $fp;

	/**
	 * {@inheritdoc}
	 */
	public function __invoke($arguments, $options)
	{
		$stopwatch = new Stopwatch();
		$stopwatch->start();

		$module_id = $arguments[0];
		$ttl = (int) $arguments[1];

		if (!ModuleManager::getInstance()->isModuleActive($module_id))
			\WP_CLI::error(sprintf('The "%s" module either doesn\'t exist or is not active.', $module_id));

		$this->lock($module_id);

		$not_older_than = abs((int) $options['not_older_than']);
		$limit = abs((int) $options['limit']);
		$sleep = abs((int) $options['sleep']);

		if (isset($options['force_feed_import']))
			$force_feed_import = filter_var($options['force_feed_import'], FILTER_VALIDATE_BOOLEAN);
		else
			$force_feed_import = false;

		if ($limit > 5000)
			$limit = 5000;

		if (!$post_ids = self::getPostIdsToUpdate($module_id, $ttl, $limit, $not_older_than))
		{
			\WP_CLI::success('No posts require updating.');
			$this->releaseLock();
			exit(0);
		}

		@set_time_limit(14400);

		$post_count = count($post_ids);
		$progress = \WP_CLI\Utils\make_progress_bar('Updating Posts', $post_count);

		$success_count = 0;
		$error_count = 0;
		$nodata_count = 0;
		$i = 0;
		foreach ($post_ids as $post_id)
		{
			if ($i && $sleep)
				sleep($sleep);

			$r = ContentManager::updateByKeyword($post_id, $module_id, true, $force_feed_import);

			if ($r == 1)
				$success_count++;
			elseif ($r == -1)
				$nodata_count++;
			else
				$error_count++;

			$progress->tick();
			$i++;
		}

		$this->releaseLock();
		$progress->finish();
		$elapsed = $stopwatch->elapsed();

		\WP_CLI::line("");
		\WP_CLI::success($post_count . ' posts updated!');
		\WP_CLI::line(sprintf('Ok: %d', $success_count));
		\WP_CLI::line(sprintf('No data: %d', $nodata_count));
		\WP_CLI::line(sprintf('Errors: %d', $error_count));

		\WP_CLI::line(sprintf('Average time per post: %.4f sec', $elapsed / $post_count));
		\WP_CLI::line(sprintf('Total time: %.4f sec', $elapsed));
		\WP_CLI::line("");
	}

	static public function getPostIdsToUpdate($module_id, $ttl, $limit, $not_older_than)
	{
		global $wpdb;

		$meta_key_keyword = ModuleUpdateScheduler::addKeywordPrefix($module_id);
		$meta_key_keyword_global = '_cegg_global_autoupdate_keyword';
		$meta_key_last_bykeyword_update = ModuleUpdateScheduler::addByKeywordUpdatePrefix($module_id);
		$time = time();

		$sql = "SELECT last_bykeyword_update.post_id
            FROM    {$wpdb->postmeta} last_bykeyword_update
            INNER JOIN  {$wpdb->postmeta} keyword
            ON last_bykeyword_update.post_id = keyword.post_id
                AND (keyword.meta_key = %s OR keyword.meta_key = %s)";

		if ($not_older_than)
			$sql .= " INNER JOIN  {$wpdb->posts} post ON last_bykeyword_update.post_id = post.ID";

		$sql .= " WHERE last_bykeyword_update.meta_key = %s";

		if ($not_older_than)
			$sql .= sprintf('  AND (TIMESTAMPDIFF(DAY, post_date, "' . \current_time('mysql') . '") <= %d)', $not_older_than);

		if ($ttl)
			$sql .= " AND (last_bykeyword_update.meta_value IS NULL OR {$time} - last_bykeyword_update.meta_value  > {$ttl})";

		$sql .= sprintf(" ORDER BY last_bykeyword_update.meta_value ASC LIMIT %d", $limit);

		$query = $wpdb->prepare($sql, $meta_key_keyword, $meta_key_keyword_global, $meta_key_last_bykeyword_update);

		if (!$post_ids = $wpdb->get_col($query))
			return array();
		else
			return $post_ids;
	}

	public function initAction()
	{
		if (!defined('\WP_CLI') || !\WP_CLI || !class_exists('\WP_CLI'))
			return;

		\add_action('cli_init', 'registerCommands');
	}

	public function registerCommands()
	{
		\WP_CLI::add_command('cegg', '\ContentEgg\application\component\CliCommand');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDescription()
	{
		return 'Initiate post updates using auto-update keywords.';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSynopsis()
	{
		return [
			[
				'type' => 'positional',
				'name' => 'module_id',
				'description' => 'The module ID to update.',
			],
			[
				'type' => 'positional',
				'name' => 'ttl',
				'description' => 'Force update TTL.',
				'default' => 3600,
			],
			[
				'type' => 'assoc',
				'name' => 'limit',
				'description' => 'The maximum number of posts to update.',
				'default' => 10,
			],
			[
				'type' => 'assoc',
				'name' => 'not_older_than',
				'description' => 'Select posts not older than N days.',
				'default' => 0,
			],
			[
				'type' => 'assoc',
				'name' => 'sleep',
				'description' => 'Time delay setting, in seconds, between the execution or handling of each post.',
				'default' => 0,
			],
			[
				'type' => 'flag',
				'name' => 'force_feed_import',
				'description' => 'Whether or not to force feed import for the Feed module.',
				'optional' => true,
				'default' => false,
			],

		];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getCommandName()
	{
		return 'update-by-keyword';
	}

	protected function lock($module_id)
	{
		$upload_dir = \wp_upload_dir();
		$file_path = trailingslashit($upload_dir['basedir']) . 'cegg-' . $this->getCommandName() . '-' . $module_id . '.pid';
		$this->fp = fopen($file_path, "w+");
		if (!flock($this->fp, LOCK_EX | LOCK_NB))
		{
			\WP_CLI::warning("Couldn't get the lock. Process is already running.");
			exit(0);
		}
	}

	public function releaseLock()
	{
		flock($this->fp, LOCK_UN);
		fclose($this->fp);
	}
}

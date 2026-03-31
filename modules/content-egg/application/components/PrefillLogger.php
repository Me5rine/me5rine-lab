<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TextHelper;

/**
 * PrefillLogger class
 * Collects and formats prefill log messages.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class PrefillLogger
{
    protected array $notices = [];

    /**
     * Add a notice to the log.
     *
     * @param string $message
     */
    public function notice(string $message): void
    {
        $this->notices[] = trim($message);
    }

    /**
     * Return the final formatted log.
     *
     * @param array $details Additional structured log data
     * @return string
     */
    public function format(array $details = []): string
    {
        $log = '';

        if (!empty($details['keyword']))
        {
            $log .= "Keyword: " . TextHelper::truncate($details['keyword'], 100) . "\n";
        }

        if (!empty($details['keyword_source']))
        {
            $log .= "Keyword Source: " . $details['keyword_source'] . "\n";
        }

        if (!empty($this->notices))
        {
            $log .= implode("\n", $this->notices) . "\n";
        }

        if (!empty($details['note']))
        {
            $log .= $details['note'] . "\n";
        }

        if (!empty($details['product_counts']) && is_array($details['product_counts']))
        {
            $total = array_sum($details['product_counts']);
            $modules = [];

            foreach ($details['product_counts'] as $module_id => $count)
            {
                $modules[] = ModuleManager::getInstance()->getModuleNameById($module_id) . ": {$count}";
            }

            $log .= "Products Added: {$total} (" . implode(', ', $modules) . ")\n";
        }

        if (!empty($details['shortcode_positions']) && is_array($details['shortcode_positions']))
        {
            $log .= "Shortcodes Inserted: " . implode(', ', $details['shortcode_positions']) . "\n";
        }

        return trim($log);
    }
}

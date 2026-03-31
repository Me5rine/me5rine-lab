<?php

namespace ContentEgg\application\admin\import;;

defined('ABSPATH') || exit;

/**
 * ImportLogger class
 * Collects and formats import log messages.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link    https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class ImportLogger
{
    /** @var string[] */
    protected array $notices = [];

    /**
     * Add a one-line notice to the log.
     *
     * @param string $message
     */
    public function notice(string $message): void
    {
        $this->notices[] = trim($message);
    }

    public function reset(): void
    {
        $this->notices = [];
    }

    public function format(array $details = []): string
    {
        $rows = [];

        if (! empty($details['exception']))
        {
            $rows[] = 'Error: ' . esc_html($details['exception']);
        }

        if (! empty($details['preset']))
        {
            $rows[] = 'Preset: ' . esc_html($details['preset']);
        }

        if (! empty($details['action']))
        {
            $rows[] = 'Action: ' . esc_html($details['action']);
        }

        if (! empty($details['duplicate']))
        {
            $rows[] = 'Skipped: duplicate found';
        }

        if (! empty($this->notices))
        {
            foreach ($this->notices as $notice)
            {
                $rows[] = esc_html($notice);
            }
        }

        if (! empty($details['custom_fields']) && is_array($details['custom_fields']))
        {
            $fields = array_keys($details['custom_fields']);
            $rows[] = 'Custom fields set: ' . implode(', ', array_map('esc_html', $fields));
        }

        if (empty($rows))
        {
            return '&mdash;';
        }

        return join("\n", $rows);
    }
}

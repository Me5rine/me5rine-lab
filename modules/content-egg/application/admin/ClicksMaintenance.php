<?php

namespace ContentEgg\application\admin;

use ContentEgg\application\models\LinkClicksDailyModel;

defined('\ABSPATH') || exit;

/**
 * ClicksMaintenance class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class ClicksMaintenance
{

    /**
     * Purge aggregated click rows older than the configured retention period.
     *
     * @return int Rows deleted
     */
    public static function runRetention(): int
    {
        $raw  = GeneralConfig::getInstance()->option('clicks_retention_days');
        $days = (int) $raw;

        if ($raw === '' || $days <= 0)
        {
            return 0; // disabled
        }

        // Site-timezone cutoff (keep last N days incl. today)
        $tz  = wp_timezone();
        $cut = (new \DateTimeImmutable('today', $tz))
            ->sub(new \DateInterval('P' . $days . 'D'))
            ->format('Y-m-d');

        return LinkClicksDailyModel::model()->deleteOlderThan($cut);
    }
}

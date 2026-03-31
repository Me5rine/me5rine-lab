<?php

namespace ContentEgg\application\admin\import;

defined('ABSPATH') || exit;

/**
 * KeywordCollection class
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link    https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

final class KeywordCollection
{
    /** @return AutoImportKeyword[] */
    public static function fromLines(array $lines): array
    {
        return array_map(
            fn($k) => new AutoImportKeyword(['keyword' => $k]),
            $lines
        );
    }

    /** @param AutoImportKeyword[] $items */
    public static function toJson(array $items): string
    {
        return wp_json_encode(
            array_map(fn($obj) => $obj->toArray(), $items),
            JSON_UNESCAPED_UNICODE
        );
    }

    /** @return AutoImportKeyword[] */
    public static function fromJson(string $json): array
    {
        $arr = json_decode($json, true) ?: [];
        return array_map(fn($row) => new AutoImportKeyword($row), $arr);
    }

    /** Decode JSON blob to an array of lines for the textarea */
    public static function toLines(string $json): array
    {
        $rows = json_decode($json, true) ?: [];
        return array_column($rows, 'keyword');
    }

    /**
     * @param string[] $lines
     * @param AutoImportKeyword[] $existing
     * @return AutoImportKeyword[]  merged
     */
    public static function mergeWithLines(array $lines, array $existing): array
    {
        $byKeyword = [];
        foreach ($existing as $kwObj)
        {
            $byKeyword[$kwObj->getKeyword()] = $kwObj;
        }

        $result = [];
        foreach ($lines as $line)
        {
            $key = trim($line);
            if ($key === '')
            {
                continue;
            }
            if (isset($byKeyword[$key]))
            {
                // preserve stats
                $result[] = $byKeyword[$key];
            }
            else
            {
                // brand-new keyword
                $result[] = new AutoImportKeyword(['keyword' => $key]);
            }
        }

        return $result;
    }

    /**
     * Find the next active keyword to process: the one with the oldest lastRun (or never run).
     *
     * @param AutoImportKeyword[] $items
     * @return AutoImportKeyword|null
     */
    public static function getNextKeyword(array $items): ?AutoImportKeyword
    {
        // only the enabled ones
        $active = array_filter(
            $items,
            fn(AutoImportKeyword $kw): bool => !$kw->isDisabled()
        );

        if (empty($active))
        {
            return null;
        }

        // sort by lastRun ascending, treating null as oldest
        usort($active, function (AutoImportKeyword $a, AutoImportKeyword $b): int
        {
            $aRun = $a->getLastRun();
            $bRun = $b->getLastRun();

            if ($aRun === $bRun)
            {
                return 0;
            }
            if ($aRun === null)
            {
                return -1;
            }
            if ($bRun === null)
            {
                return 1;
            }
            return strtotime($aRun) <=> strtotime($bRun);
        });

        return $active[0];
    }
}

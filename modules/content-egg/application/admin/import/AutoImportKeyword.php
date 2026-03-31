<?php

namespace ContentEgg\application\admin\import;

defined('ABSPATH') || exit;

/**
 * AutoImportKeyword class
 * Represents a single keyword in an Auto-Import rule,
 * tracking its run history and no-result streak.
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link    https://www.keywordrush.com
 * @copyright Copyright © 2025 keywordrush.com
 */
class AutoImportKeyword
{
    private string  $keyword;
    private ?string $lastRun                    = null;
    private ?string $lastResultsAddedDate       = null;
    private int     $productsImported           = 0;
    private int     $consecutiveNoProductsFound = 0;
    private bool    $disabled                   = false;

    public function __construct(array $data)
    {
        $this->keyword                    = (string) $data['keyword'];
        $this->lastRun                    = $data['last_run']             ?? null;
        $this->lastResultsAddedDate       = $data['last_results_added']   ?? null;
        $this->productsImported           = (int) ($data['products_imported'] ?? 0);
        $this->consecutiveNoProductsFound = (int) ($data['consecutive_no_results'] ?? 0);
        $this->disabled                   = (bool) ($data['disabled']         ?? false);
    }

    public function toArray(): array
    {
        return [
            'keyword'                => $this->keyword,
            'last_run'               => $this->lastRun,
            'last_results_added'     => $this->lastResultsAddedDate,
            'products_imported'      => $this->productsImported,
            'consecutive_no_results' => $this->consecutiveNoProductsFound,
            'disabled'               => $this->disabled,
        ];
    }

    public function touch(int $count): void
    {
        $now = current_time('mysql');
        $this->lastRun = $now;

        if ($count > 0)
        {
            $this->lastResultsAddedDate       = $now;
            $this->productsImported          += $count;
            $this->consecutiveNoProductsFound = 0;
        }
        else
        {
            $this->consecutiveNoProductsFound++;
        }
    }

    // ==== Getters ====

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getLastRun(): ?string
    {
        return $this->lastRun;
    }

    public function getLastResultsAddedDate(): ?string
    {
        return $this->lastResultsAddedDate;
    }

    public function getProductsImported(): int
    {
        return $this->productsImported;
    }

    public function getConsecutiveNoProductsFound(): int
    {
        return $this->consecutiveNoProductsFound;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    // ==== Controls ====

    public function disable(): void
    {
        if ($this->disabled)
        {
            return; // already disabled
        }

        $this->keyword = '[' . $this->keyword . ']';
        $this->disabled = true;
    }

    public function enable(): void
    {
        if (!$this->disabled)
        {
            return;
        }

        // Remove brackets if they exist
        if (
            substr($this->keyword, 0, 1) === '[' &&
            substr($this->keyword, -1) === ']'
        )
        {
            $this->keyword = substr($this->keyword, 1, -1);
        }

        $this->disabled = false;
    }
}

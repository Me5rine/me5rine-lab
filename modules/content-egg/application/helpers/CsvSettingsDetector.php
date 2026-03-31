<?php

namespace ContentEgg\application\helpers;

use function ContentEgg\prnx;

defined('\ABSPATH') || exit;

/**
 * CsvSettingsDetector class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */

/**
 * CSV settings detector that:
 *  - Prefers TABs if they appear at all and parsing looks sane (even if tabs are few)
 *  - Scores delimiters by consistency across lines (low variance) and how many lines parse into >= 2 fields
 *  - Chooses the best enclosure (" or ') per delimiter
 *
 * Usage:
 *   $detector = new CsvSettingsDetector();
 *   $settings = $detector->detect('/path/to/file.csv');
 *   // $settings = ['delimiter' => "\t", 'enclosure' => '"'];
 */
class CsvSettingsDetector
{
	/** @var string[] */
	private array $candidateDelimiters;

	/** @var string[] */
	private array $candidateEnclosures;

	private int $maxSampleLines;
	private bool $preferTabs;

	/**
	 * @param string[] $candidateDelimiters
	 * @param string[] $candidateEnclosures
	 */
	public function __construct(
		array $candidateDelimiters = ["\t", ";", ",", "|"],
		array $candidateEnclosures = ['"', "'"],
		int $maxSampleLines = 50,
		bool $preferTabs = true
	)
	{
		$this->candidateDelimiters = $candidateDelimiters;
		$this->candidateEnclosures = $candidateEnclosures;
		$this->maxSampleLines      = max(1, $maxSampleLines);
		$this->preferTabs          = $preferTabs;
	}

	/**
	 * Detect best delimiter & enclosure for a CSV-like file path.
	 * Returns ['delimiter' => string, 'enclosure' => string]
	 */
	public function detect(string $filePath): array
	{
		$lines = $this->readSampleLines($filePath, $this->maxSampleLines);
		if (empty($lines))
		{
			// Fallback
			return ['delimiter' => ',', 'enclosure' => '"'];
		}

		// --- Fast path for TABs (prefer even if scarce) ---
		if ($this->preferTabs && $this->hasChar($lines, "\t"))
		{
			$tabStats = $this->scoreDelimiter($lines, "\t", $this->candidateEnclosures);
			$minLinesForOk = max(2, (int)ceil(count($lines) * 0.5));
			if ($tabStats['median'] >= 2 && $tabStats['lines_ge2'] >= $minLinesForOk)
			{
				return ['delimiter' => "\t", 'enclosure' => $tabStats['best_enclosure']];
			}
		}

		// --- Score all candidates; pick the highest ---
		$best = [
			'score' => -INF,
			'delimiter' => ',',
			'enclosure' => '"',
		];

		foreach ($this->candidateDelimiters as $d)
		{
			$s = $this->scoreDelimiter($lines, $d, $this->candidateEnclosures);

			// Small bias toward TABs even without the fast-path
			if ($d === "\t")
			{
				$s['score'] += 1.0;
			}

			if ($s['score'] > $best['score'])
			{
				$best = [
					'score' => $s['score'],
					'delimiter' => $d,
					'enclosure' => $s['best_enclosure'],
				];
			}
		}

		return ['delimiter' => $best['delimiter'], 'enclosure' => $best['enclosure']];
	}

	// ----------------- Internals -----------------

	/** Read up to $max CSV *records* (newline-safe), supporting " and ' as enclosures. */
	private function readSampleLines(string $filePath, int $max): array
	{
		$h = @fopen($filePath, 'r');
		if (!$h)
		{
			return [];
		}

		$out   = [];
		$first = true;
		$buf   = '';

		// Try both enclosures; prefer " then '
		$enclosures = ['"', "'"];

		while (!feof($h) && count($out) < $max)
		{
			$chunk = fgets($h);
			if ($chunk === false)
			{
				break;
			}
			$buf .= $chunk;

			$matchedEnc = null;
			if ($this->csvRecordIsCompleteMulti($buf, $enclosures, $matchedEnc))
			{
				$record = rtrim($buf, "\r\n");

				if ($first)
				{
					$first = false;
					// Remove UTF-8 BOM if present
					$record = preg_replace('/^\xEF\xBB\xBF/u', '', $record);
				}

				if ($record !== '')
				{
					$out[] = $record;
				}

				$buf = '';
			}
		}

		if ($buf !== '' && count($out) < $max)
		{
			$matchedEnc = null;
			if ($this->csvRecordIsCompleteMulti($buf, $enclosures, $matchedEnc))
			{
				$record = rtrim($buf, "\r\n");
				if ($first)
				{
					$record = preg_replace('/^\xEF\xBB\xBF/u', '', $record);
				}
				if ($record !== '')
				{
					$out[] = $record;
				}
			}
		}

		fclose($h);
		return $out;
	}

	/**
	 * Returns true if $s ends outside quotes for at least one of the given enclosures.
	 * Sets $matchedEnc to the enclosure that appears balanced (prefers the first that actually appears).
	 */
	private function csvRecordIsCompleteMulti(string $s, array $enclosures = ['"', "'"], ?string &$matchedEnc = null): bool
	{
		$hasAnyQuote = false;
		foreach ($enclosures as $enc)
		{
			if (strpos($s, $enc) !== false)
			{
				$hasAnyQuote = true;
				break;
			}
		}

		foreach ($enclosures as $enc)
		{
			// Remove doubled quotes ("" or '') which are escaped enclosures
			$withoutEscaped = str_replace($enc . $enc, '', $s);
			$balanced = (substr_count($withoutEscaped, $enc) % 2) === 0;

			// Prefer an enclosure that actually appears in the buffer
			if ($balanced && strpos($s, $enc) !== false)
			{
				$matchedEnc = $enc;
				return true;
			}
		}

		// If there are no quote chars at all, treat as balanced (unquoted CSV)
		if (!$hasAnyQuote)
		{
			$matchedEnc = $enclosures[0];
			return true;
		}

		return false;
	}

	/** Does any line contain the given character? */
	private function hasChar(array $lines, string $ch): bool
	{
		foreach ($lines as $l)
		{
			if (strpos($l, $ch) !== false)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Score a delimiter across possible enclosures.
	 * Returns:
	 *  [
	 *    'score' => float,
	 *    'best_enclosure' => string,
	 *    'median' => float,
	 *    'lines_ge2' => int
	 *  ]
	 */
	private function scoreDelimiter(array $lines, string $delimiter, array $enclosures): array
	{
		$best = [
			'score' => -INF,
			'best_enclosure' => '"',
			'median' => 1.0,
			'lines_ge2' => 0,
		];

		foreach ($enclosures as $enc)
		{
			$counts   = [];
			$lines_ge2 = 0;

			foreach ($lines as $line)
			{
				// str_getcsv is quote-aware
				$fields = str_getcsv($line, $delimiter, $enc);
				$c = is_array($fields) ? count($fields) : 1;
				$counts[] = $c;
				if ($c >= 2)
				{
					$lines_ge2++;
				}
			}

			$median = $this->median($counts);
			$stdev  = $this->stdev($counts);
			$ones   = count($counts) - $lines_ge2;

			// Scoring:
			//  + Reward many lines that split into >= 2 fields  -> 3.0 each
			//  + Reward higher median field count (capped)      -> 1.0 * min(median, 20)
			//  - Penalize high variance (inconsistent splits)   -> -2.0 * stdev
			//  - Slight penalty for lines stuck at 1 field      -> -0.25 * ones
			$score = (3.0 * $lines_ge2) + (1.0 * min($median, 20)) - (2.0 * $stdev) - (0.25 * $ones);

			if ($score > $best['score'])
			{
				$best = [
					'score' => $score,
					'best_enclosure' => $enc,
					'median' => $median,
					'lines_ge2' => $lines_ge2,
				];
			}
		}

		return $best;
	}

	/** Median of numeric array. */
	private function median(array $values): float
	{
		if (empty($values))
		{
			return 0.0;
		}
		sort($values);
		$n   = count($values);
		$mid = (int) floor(($n - 1) / 2);
		return ($n % 2)
			? (float) $values[$mid]
			: (($values[$mid] + $values[$mid + 1]) / 2.0);
	}

	/** Sample standard deviation (n-1). */
	private function stdev(array $values): float
	{
		$n = count($values);
		if ($n <= 1)
		{
			return 0.0;
		}
		$mean  = array_sum($values) / $n;
		$sumSq = 0.0;
		foreach ($values as $v)
		{
			$sumSq += ($v - $mean) * ($v - $mean);
		}
		return sqrt($sumSq / ($n - 1));
	}
}

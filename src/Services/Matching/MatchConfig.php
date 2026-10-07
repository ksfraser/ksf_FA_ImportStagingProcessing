<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services\Matching;

/**
 * MatchConfig — tunables for duplicate detection.
 *
 * Modelled on ksf_bank_import's `duplicate_window_days` setting, which is passed
 * into its candidate query rather than hardcoded. Two deliberate differences:
 *
 *  - The window is TWO-SIDED (+/- N days). bank_import's duplicate-file check is
 *    one-sided (`upload_date >= DATE_SUB(NOW(), INTERVAL N DAY)`), which is
 *    right for "have I already uploaded this file" but wrong here: a source
 *    system can report a transaction dated slightly in the future relative to
 *    FA (timezone skew, delayed capture), and a one-sided window would silently
 *    miss those.
 *  - Amount tolerance is absolute (currency units), not the percentage the
 *    existing matchAmount() uses, because payment matching needs "the same
 *    amount" to be a hard gate rather than a soft score.
 *
 * Values resolve from FA company preferences when available, so they can be
 * tuned per company without a redeploy; otherwise the documented defaults apply.
 *
 * PHP 7.3 compatible: no typed properties, no property promotion.
 *
 * @package ksf_FA_ImportStagingProcessing
 */
class MatchConfig
{
    /** Days either side of the staged date to search for candidates. */
    const PREF_WINDOW_DAYS = 'ISU_MATCH_WINDOW_DAYS';
    /** Absolute per-currency-unit tolerance for "same amount". */
    const PREF_AMOUNT_TOLERANCE = 'ISU_MATCH_AMOUNT_TOLERANCE';
    /** Confidence at or above which a match may be applied without review. */
    const PREF_AUTO_APPROVE = 'ISU_MATCH_AUTO_APPROVE';
    /** Confidence at or above which a match is flagged for human review. */
    const PREF_REVIEW = 'ISU_MATCH_REVIEW';
    /** Minimum line-item agreement for an invoice to be considered the same. */
    const PREF_LINE_THRESHOLD = 'ISU_MATCH_LINE_THRESHOLD';
    /** Whether a soundex name agreement counts toward a customer match. */
    const PREF_NAME_SOUNDEX = 'ISU_MATCH_NAME_SOUNDEX';

    const DEFAULT_WINDOW_DAYS = 7;
    const DEFAULT_AMOUNT_TOLERANCE = 0.01;
    const DEFAULT_AUTO_APPROVE = 0.95;
    const DEFAULT_REVIEW = 0.80;
    const DEFAULT_LINE_THRESHOLD = 0.90;

    /** @var int */
    private $windowDays;
    /** @var float */
    private $amountTolerance;
    /** @var float */
    private $autoApprove;
    /** @var float */
    private $review;
    /** @var float */
    private $lineThreshold;
    /** @var bool */
    private $nameSoundex;

    /**
     * @param array $overrides Optional key => value overrides
     */
    public function __construct(array $overrides = array())
    {
        $read = $this->reader();

        $this->windowDays = $this->intOr(
            $this->lookup($overrides, self::PREF_WINDOW_DAYS, $read),
            self::DEFAULT_WINDOW_DAYS
        );
        // A window of 0 would match nothing, which silently disables dedupe.
        $this->windowDays = max(1, $this->windowDays);

        $this->amountTolerance = max(
            0.0,
            $this->floatOr(
                $this->lookup($overrides, self::PREF_AMOUNT_TOLERANCE, $read),
                self::DEFAULT_AMOUNT_TOLERANCE
            )
        );

        $this->autoApprove = $this->clamp01($this->floatOr(
            $this->lookup($overrides, self::PREF_AUTO_APPROVE, $read),
            self::DEFAULT_AUTO_APPROVE
        ));

        $this->review = $this->clamp01($this->floatOr(
            $this->lookup($overrides, self::PREF_REVIEW, $read),
            self::DEFAULT_REVIEW
        ));
        if ($this->review > $this->autoApprove) {
            // A review band above the auto-approve threshold can never be
            // reached; clamp so the two thresholds stay ordered.
            $this->review = $this->autoApprove;
        }

        $this->lineThreshold = $this->clamp01($this->floatOr(
            $this->lookup($overrides, self::PREF_LINE_THRESHOLD, $read),
            self::DEFAULT_LINE_THRESHOLD
        ));

        $this->nameSoundex = $this->boolOr(
            $this->lookup($overrides, self::PREF_NAME_SOUNDEX, $read),
            true
        );
    }

    /** @return int */
    public function getWindowDays()
    {
        return $this->windowDays;
    }

    /** @return float */
    public function getAmountTolerance()
    {
        return $this->amountTolerance;
    }

    /** @return float */
    public function getAutoApproveThreshold()
    {
        return $this->autoApprove;
    }

    /** @return float */
    public function getReviewThreshold()
    {
        return $this->review;
    }

    /** @return float */
    public function getLineThreshold()
    {
        return $this->lineThreshold;
    }

    /** @return bool */
    public function usesSoundex()
    {
        return $this->nameSoundex;
    }

    /**
     * Earliest date a candidate may carry, given the staged record's date.
     *
     * @param string $date 'Y-m-d'
     * @return string 'Y-m-d'
     */
    public function windowStart($date)
    {
        return $this->shift($date, -1 * $this->windowDays);
    }

    /**
     * Latest date a candidate may carry, given the staged record's date.
     *
     * @param string $date 'Y-m-d'
     * @return string 'Y-m-d'
     */
    public function windowEnd($date)
    {
        return $this->shift($date, $this->windowDays);
    }

    /**
     * Whether two amounts are "the same amount" for matching purposes.
     *
     * @param float $a
     * @param float $b
     * @return bool
     */
    public function amountsMatch($a, $b)
    {
        // Compare with a small epsilon: float subtraction is not exact, so
        // abs(100.00 - 100.01) evaluates to 0.010000000000005 and an amount
        // exactly AT the tolerance would otherwise fail.
        $epsilon = 1e-9;

        return abs((float)$a - (float)$b) <= ($this->amountTolerance + $epsilon);
    }

    /**
     * Whether a confidence score should be auto-approved.
     *
     * @param float $confidence
     * @return bool
     */
    public function shouldAutoApprove($confidence)
    {
        return (float)$confidence >= $this->autoApprove;
    }

    /**
     * Whether a confidence score needs human review.
     *
     * @param float $confidence
     * @return bool
     */
    public function needsReview($confidence)
    {
        $confidence = (float)$confidence;

        return $confidence >= $this->review && $confidence < $this->autoApprove;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            self::PREF_WINDOW_DAYS => $this->windowDays,
            self::PREF_AMOUNT_TOLERANCE => $this->amountTolerance,
            self::PREF_AUTO_APPROVE => $this->autoApprove,
            self::PREF_REVIEW => $this->review,
            self::PREF_LINE_THRESHOLD => $this->lineThreshold,
            self::PREF_NAME_SOUNDEX => $this->nameSoundex,
        );
    }

    /**
     * Shift a date by N days, tolerating an unparseable value.
     *
     * @param string $date
     * @param int    $days
     * @return string
     */
    private function shift($date, $days)
    {
        $time = strtotime((string)$date);
        if ($time === false) {
            $time = time();
        }

        return date('Y-m-d', strtotime(sprintf('%+d days', (int)$days), $time));
    }

    /**
     * Override wins, then company preference, then null.
     *
     * @param array    $overrides
     * @param string   $key
     * @param callable $read
     * @return mixed|null
     */
    private function lookup(array $overrides, $key, $read)
    {
        if (array_key_exists($key, $overrides)) {
            return $overrides[$key];
        }

        $value = $read($key);

        return $value === null || $value === '' ? null : $value;
    }

    /**
     * Reads a company preference, degrading quietly outside FA.
     *
     * @return callable
     */
    private function reader()
    {
        return function ($key) {
            if (!function_exists('get_company_pref')) {
                return null;
            }

            $value = get_company_pref($key);

            return $value === false ? null : $value;
        };
    }

    /**
     * @param mixed $value
     * @param int   $default
     * @return int
     */
    private function intOr($value, $default)
    {
        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * @param mixed $value
     * @param float $default
     * @return float
     */
    private function floatOr($value, $default)
    {
        return is_numeric($value) ? (float)$value : $default;
    }

    /**
     * @param mixed $value
     * @param bool  $default
     * @return bool
     */
    private function boolOr($value, $default)
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $value = strtolower(trim((string)$value));

        return in_array($value, array('1', 'true', 'yes', 'on'), true);
    }

    /**
     * @param float $value
     * @return float
     */
    private function clamp01($value)
    {
        if ($value < 0.0) {
            return 0.0;
        }

        return $value > 1.0 ? 1.0 : $value;
    }
}
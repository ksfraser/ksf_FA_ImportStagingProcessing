<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services\Matching;

/**
 * LineItemComparer — compares staged order lines against an FA invoice's lines.
 *
 * WHY THIS EXISTS. Matching an imported Woo/Square order to an existing FA
 * invoice on totals alone is unreliable. The two sides legitimately compute
 * different figures for the same order: FA may add freight, round tax per line
 * versus per invoice, apply a sales discount, or carry a coupon that the source
 * system folded into its line prices. Two invoices for the same order can
 * therefore differ in total while being obviously the same order.
 *
 * So matching is TWO-STAGE, exactly as the import review requires:
 *
 *   1. Fetch candidates cheaply — debtor, date window, and a loose total
 *      tolerance. This is a shortlist, never a verdict.
 *   2. Score the shortlist on LINE AGREEMENT: same stock codes, same quantities,
 *      same unit prices, and compatible discounts. The best line score decides.
 *
 * A candidate is only reported as the same invoice when its line score reaches
 * `MatchConfig::getLineThreshold()`. Totals contribute to the score but cannot
 * on their own confirm a match.
 *
 * PHP 7.3 compatible: no typed properties, no property promotion.
 *
 * @package ksf_FA_ImportStagingProcessing
 */
class LineItemComparer
{
    /** Weight of an identical stock code within the line score. */
    const WEIGHT_STOCK = 0.35;
    /** Weight of matching quantity. */
    const WEIGHT_QUANTITY = 0.25;
    /** Weight of matching unit price. */
    const WEIGHT_PRICE = 0.25;
    /** Weight of discount compatibility. */
    const WEIGHT_DISCOUNT = 0.15;

    /** @var Similarity */
    private $similarity;

    public function __construct(Similarity $similarity = null)
    {
        $this->similarity = $similarity !== null ? $similarity : new Similarity();
    }

    /**
     * Score how closely staged lines match an existing invoice's lines.
     *
     * Matching is one-to-one and order-independent: each staged line claims at
     * most one existing line, so a duplicated line in the source system cannot
     * be satisfied by the same FA line twice.
     *
     * @param array $stagedLines  [['stock_id','quantity','price','discount'], ...]
     * @param array $existingLines Same shape, from FA
     * @param float $priceTolerance Absolute per-unit tolerance
     * @return array ['score'=>float, 'matched'=>int, 'staged_count'=>int,
     *               'lines'=>[per-line detail], 'unmatched'=>[staged lines]]
     */
    public function compare(array $stagedLines, array $existingLines, $priceTolerance = 0.01)
    {
        $stagedLines = $this->normaliseLines($stagedLines);
        $existingLines = $this->normaliseLines($existingLines);

        $stagedCount = count($stagedLines);
        if ($stagedCount === 0) {
            // Nothing to compare: an absence of evidence, not a match.
            return array(
                'score' => 0.0,
                'matched' => 0,
                'staged_count' => 0,
                'lines' => array(),
                'unmatched' => array(),
            );
        }

        $usedExisting = array();
        $perLine = array();
        $matched = 0;
        $scoreSum = 0.0;
        $unmatched = array();

        foreach ($stagedLines as $staged) {
            $bestIndex = null;
            $bestScore = 0.0;

            foreach ($existingLines as $index => $existing) {
                if (isset($usedExisting[$index])) {
                    continue;
                }

                $score = $this->lineScore($staged, $existing, $priceTolerance);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestIndex = $index;
                }
            }

            $perLine[] = array(
                'staged' => $staged,
                'existing' => $bestIndex === null ? null : $existingLines[$bestIndex],
                'score' => $bestScore,
            );

            if ($bestIndex === null) {
                $unmatched[] = $staged;
                continue;
            }

            $usedExisting[$bestIndex] = true;
            $scoreSum += $bestScore;
            if ($bestScore > 0.0) {
                $matched++;
            }
        }

        return array(
            'score' => $scoreSum / $stagedCount,
            'matched' => $matched,
            'staged_count' => $stagedCount,
            'lines' => $perLine,
            'unmatched' => $unmatched,
        );
    }

    /**
     * Whether two line sets agree closely enough to be the same order.
     *
     * @param array $stagedLines
     * @param array $existingLines
     * @param MatchConfig $config
     * @return bool
     */
    public function isSameOrder(array $stagedLines, array $existingLines, MatchConfig $config)
    {
        $result = $this->compare(
            $stagedLines,
            $existingLines,
            $config->getAmountTolerance()
        );

        return $result['score'] >= $config->getLineThreshold();
    }

    /**
     * Score a single staged line against a single existing line, 0.0 - 1.0.
     *
     * @param array $staged
     * @param array $existing
     * @param float $priceTolerance
     * @return float
     */
    private function lineScore(array $staged, array $existing, $priceTolerance)
    {
        // Stock code must agree before anything else counts. Without it, a
        // coincidental price+qty collision could score highly.
        if (!$this->stockMatches($staged['stock_id'], $existing['stock_id'])) {
            return 0.0;
        }

        $score = self::WEIGHT_STOCK;
        $score += self::WEIGHT_QUANTITY * $this->quantityScore($staged['quantity'], $existing['quantity']);
        $score += self::WEIGHT_PRICE * $this->priceScore($staged['price'], $existing['price'], $priceTolerance);
        $score += self::WEIGHT_DISCOUNT * $this->discountScore(
            $staged['discount'],
            $existing['discount'],
            $priceTolerance
        );

        return min(1.0, $score);
    }

    /**
     * @param string $a
     * @param string $b
     * @return bool
     */
    private function stockMatches($a, $b)
    {
        if ($a === '' || $b === '') {
            return false;
        }

        return strcasecmp(trim((string)$a), trim((string)$b)) === 0;
    }

    /**
     * Quantities agree exactly, or not at all.
     *
     * @param float $a
     * @param float $b
     * @return float
     */
    private function quantityScore($a, $b)
    {
        $diff = abs((float)$a - (float)$b);

        return $diff < 0.000001 ? 1.0 : 0.0;
    }

    /**
     * @param float $a
     * @param float $b
     * @param float $tolerance
     * @return float
     */
    private function priceScore($a, $b, $tolerance)
    {
        $a = (float)$a;
        $b = (float)$b;

        if (abs($a - $b) <= (float)$tolerance) {
            return 1.0;
        }

        // A proportional fallback, so a 10% price drift degrades the score
        // rather than collapsing it to zero.
        $max = max(abs($a), abs($b));
        if ($max == 0.0) {
            return 1.0;
        }

        $ratio = abs($a - $b) / $max;

        return $ratio >= 1.0 ? 0.0 : max(0.0, 1.0 - ($ratio / 0.10));
    }

    /**
     * Discounts are compatible when both are absent/zero, or within tolerance.
     *
     * @param float $a
     * @param float $b
     * @param float $tolerance
     * @return float
     */
    private function discountScore($a, $b, $tolerance)
    {
        $a = (float)$a;
        $b = (float)$b;

        $aZero = abs($a) < 0.000001;
        $bZero = abs($b) < 0.000001;
        if ($aZero && $bZero) {
            return 1.0;
        }
        if ($aZero !== $bZero) {
            // One side discounted and the other not is a real disagreement.
            return 0.0;
        }

        return abs($a - $b) <= (float)$tolerance ? 1.0 : 0.0;
    }

    /**
     * Coerce caller-supplied lines into a comparable shape.
     *
     * @param array $lines
     * @return array
     */
    private function normaliseLines(array $lines)
    {
        $out = array();

        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $stockId = '';
            foreach (array('stock_id', 'stockid', 'stock', 'part_id', 'code') as $key) {
                if (isset($line[$key]) && $line[$key] !== '') {
                    $stockId = (string)$line[$key];
                    break;
                }
            }

            $quantity = 0.0;
            foreach (array('quantity', 'qty', 'Quantity') as $key) {
                if (isset($line[$key]) && $line[$key] !== '') {
                    $quantity = (float)$line[$key];
                    break;
                }
            }

            $price = 0.0;
            foreach (array('price', 'unit_price', 'rate', 'Price') as $key) {
                if (isset($line[$key]) && $line[$key] !== '') {
                    $price = (float)$line[$key];
                    break;
                }
            }

            $discount = 0.0;
            foreach (array('discount', 'discount_percent', 'Discount') as $key) {
                if (isset($line[$key]) && $line[$key] !== '') {
                    $discount = (float)$line[$key];
                    break;
                }
            }

            if ($stockId === '' && $price == 0.0) {
                // Nothing identifying on this line; skip rather than guess.
                continue;
            }

            $out[] = array(
                'stock_id' => $stockId,
                'quantity' => $quantity,
                'price' => $price,
                'discount' => $discount,
            );
        }

        return $out;
    }
}
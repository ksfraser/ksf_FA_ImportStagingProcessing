<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit\Services\Matching;

use ksfraser\FrontAccounting\ImportStaging\Services\Matching\LineItemComparer;
use ksfraser\FrontAccounting\ImportStaging\Services\Matching\MatchConfig;
use PHPUnit\Framework\TestCase;

/**
 * @package ksf_FA_ImportStagingProcessing
 */
class LineItemComparerTest extends TestCase
{
    /** @var LineItemComparer */
    private $comparer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comparer = new LineItemComparer();
    }

    /**
     * @return array
     */
    private function staged()
    {
        return array(
            array('stock_id' => 'SKU-A', 'quantity' => 2, 'price' => 10.00),
            array('stock_id' => 'SKU-B', 'quantity' => 1, 'price' => 5.50),
        );
    }

    public function testIdenticalLinesScoreOne(): void
    {
        $result = $this->comparer->compare($this->staged(), $this->staged());

        $this->assertSame(1.0, round($result['score'], 6));
        $this->assertSame(2, $result['matched']);
        $this->assertSame(2, $result['staged_count']);
        $this->assertSame(array(), $result['unmatched']);
    }

    /**
     * Line order between the two systems is irrelevant.
     */
    public function testMatchingIsOrderIndependent(): void
    {
        $reversed = array_reverse($this->staged());

        $result = $this->comparer->compare($this->staged(), $reversed);

        $this->assertSame(1.0, round($result['score'], 6));
        $this->assertSame(2, $result['matched']);
    }

    /**
     * The point of comparing lines rather than totals: the two systems can
     * legitimately report DIFFERENT totals for the same order. Tax rounding and
     * invoice-level freight/coupon do not appear in the line comparison at all,
     * so the lines still agree and the order is recognised as the same.
     */
    public function testLineAgreementSurvivesTotalDrift(): void
    {
        // FA's invoice carries the same lines; its total differs only because of
        // tax/freight that the source system computed differently.
        $existing = $this->staged();

        $result = $this->comparer->compare($this->staged(), $existing);

        $this->assertSame(1.0, round($result['score'], 6));
        $this->assertTrue($this->comparer->isSameOrder($this->staged(), $existing, new MatchConfig()));
    }

    /**
     * An explicit line-level discount carried on both sides still agrees.
     */
    public function testMatchingLineLevelDiscountsAgree(): void
    {
        $lines = array(
            array('stock_id' => 'SKU-A', 'quantity' => 2, 'price' => 10.00, 'discount' => 1.00),
        );

        $this->assertTrue(
            $this->comparer->isSameOrder($lines, $lines, new MatchConfig()),
            'the discount is recorded on both sides, so the lines agree'
        );
    }

    /**
     * A large line-price drop is NOT auto-confirmed. It could be a coupon or it
     * could be a different price entirely; the score lands below the threshold
     * so a human decides. This is the line comparison earning its keep.
     */
    public function testLargeLinePriceDriftIsFlaggedForReviewNotConfirmed(): void
    {
        $staged = $this->staged();
        $existing = array(
            array('stock_id' => 'SKU-A', 'quantity' => 2, 'price' => 10.00),
            array('stock_id' => 'SKU-B', 'quantity' => 1, 'price' => 4.50), // was 5.50
        );

        $result = $this->comparer->compare($staged, $existing);
        $config = new MatchConfig();

        $this->assertFalse(
            $this->comparer->isSameOrder($staged, $existing, $config),
            'an 18% price drop must not be silently auto-matched'
        );
        $this->assertGreaterThan(
            $config->getReviewThreshold(),
            $result['score'],
            'but it should still score high enough to reach a human'
        );
    }

    /**
     * A modest price difference is absorbed.
     */
    public function testSmallLinePriceDriftStillMatches(): void
    {
        $staged = $this->staged();
        $existing = array(
            array('stock_id' => 'SKU-A', 'quantity' => 2, 'price' => 10.00),
            array('stock_id' => 'SKU-B', 'quantity' => 1, 'price' => 5.49),
        );

        $this->assertTrue($this->comparer->isSameOrder($staged, $existing, new MatchConfig()));
    }

    public function testQuantityDriftIsNotTheSameOrder(): void
    {
        $existing = array(
            array('stock_id' => 'SKU-A', 'quantity' => 5, 'price' => 10.00),
            array('stock_id' => 'SKU-B', 'quantity' => 1, 'price' => 5.50),
        );

        $this->assertFalse(
            $this->comparer->isSameOrder($this->staged(), $existing, new MatchConfig())
        );
    }

    public function testCompletelyDifferentOrderScoresZero(): void
    {
        $existing = array(array('stock_id' => 'SKU-Z', 'quantity' => 9, 'price' => 99.00));

        $result = $this->comparer->compare($this->staged(), $existing);

        $this->assertSame(0.0, $result['score']);
        $this->assertSame(0, $result['matched']);
    }

    public function testMissingLinesReduceTheScore(): void
    {
        $existing = array(array('stock_id' => 'SKU-A', 'quantity' => 2, 'price' => 10.00));

        $result = $this->comparer->compare($this->staged(), $existing);

        $this->assertSame(1, $result['matched']);
        $this->assertSame(2, $result['staged_count']);
        $this->assertFalse($this->comparer->isSameOrder($this->staged(), $existing, new MatchConfig()));
    }

    /**
     * One staged line must not be satisfied twice by the same FA line.
     */
    public function testLinesMatchOneToOne(): void
    {
        // Two identical staged lines against ONE existing line.
        $staged = array(
            array('stock_id' => 'SKU-A', 'quantity' => 1, 'price' => 10.00),
            array('stock_id' => 'SKU-A', 'quantity' => 1, 'price' => 10.00),
        );
        $existing = array(array('stock_id' => 'SKU-A', 'quantity' => 1, 'price' => 10.00));

        $result = $this->comparer->compare($staged, $existing);

        $this->assertSame(1, $result['matched']);
        $this->assertCount(1, $result['unmatched']);
    }

    public function testEmptyStagedLinesIsNotAMatch(): void
    {
        $result = $this->comparer->compare(array(), $this->staged());

        $this->assertSame(0.0, $result['score']);
        $this->assertSame(0, $result['staged_count']);
    }

    public function testAlternateLineKeySpellingsAreAccepted(): void
    {
        $staged = array(array('stockid' => 'SKU-A', 'qty' => 2, 'unit_price' => 10.00));
        $existing = array(array('stock_id' => 'SKU-A', 'quantity' => 2, 'price' => 10.00));

        $result = $this->comparer->compare($staged, $existing);

        $this->assertSame(1.0, round($result['score'], 6));
    }

    public function testStockCodeMustAgreeRegardlessOfPriceAndQuantity(): void
    {
        // Same qty and price, different item: a coincidence, not the same order.
        $staged = array(array('stock_id' => 'SKU-A', 'quantity' => 2, 'price' => 10.00));
        $existing = array(array('stock_id' => 'SKU-B', 'quantity' => 2, 'price' => 10.00));

        $this->assertSame(0.0, $this->comparer->compare($staged, $existing)['score']);
    }

    public function testLinesWithNothingIdentifiableAreSkipped(): void
    {
        $staged = array(
            array('description' => 'Frothing widget'),
            array('stock_id' => 'SKU-A', 'quantity' => 1, 'price' => 5.00),
        );

        $result = $this->comparer->compare($staged, $staged);

        $this->assertSame(1, $result['staged_count'], 'unidentifiable lines must not be counted');
    }

    public function testPriceWithinToleranceMatches(): void
    {
        $staged = array(array('stock_id' => 'SKU-A', 'quantity' => 1, 'price' => 10.00));
        $existing = array(array('stock_id' => 'SKU-A', 'quantity' => 1, 'price' => 10.005));

        $this->assertSame(1.0, round($this->comparer->compare($staged, $existing, 0.01)['score'], 6));
    }

    public function testPerLineDetailIsReported(): void
    {
        $result = $this->comparer->compare($this->staged(), $this->staged());

        $this->assertCount(2, $result['lines']);
        foreach ($result['lines'] as $line) {
            $this->assertArrayHasKey('staged', $line);
            $this->assertArrayHasKey('existing', $line);
            $this->assertArrayHasKey('score', $line);
        }
    }
}
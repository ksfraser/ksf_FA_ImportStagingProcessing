<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit\Services\Matching;

use ksfraser\FrontAccounting\ImportStaging\Services\Matching\MatchConfig;
use PHPUnit\Framework\TestCase;

/**
 * @package ksf_FA_ImportStagingProcessing
 */
class MatchConfigTest extends TestCase
{
    public function testDefaultsAreAppliedOutsideFa(): void
    {
        $config = new MatchConfig();

        $this->assertSame(MatchConfig::DEFAULT_WINDOW_DAYS, $config->getWindowDays());
        $this->assertSame(MatchConfig::DEFAULT_AMOUNT_TOLERANCE, $config->getAmountTolerance());
        $this->assertSame(MatchConfig::DEFAULT_AUTO_APPROVE, $config->getAutoApproveThreshold());
        $this->assertSame(MatchConfig::DEFAULT_REVIEW, $config->getReviewThreshold());
        $this->assertSame(MatchConfig::DEFAULT_LINE_THRESHOLD, $config->getLineThreshold());
        $this->assertTrue($config->usesSoundex());
    }

    /**
     * The window is TWO-SIDED: a source system can report a transaction dated
     * slightly ahead of FA, and a one-sided window would miss it.
     */
    public function testWindowIsTwoSided(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_WINDOW_DAYS => 7));

        $this->assertSame('2026-09-28', $config->windowStart('2026-10-05'));
        $this->assertSame('2026-10-12', $config->windowEnd('2026-10-05'));
    }

    public function testWindowHonoursConfiguredDays(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_WINDOW_DAYS => 30));

        $this->assertSame('2026-09-05', $config->windowStart('2026-10-05'));
        $this->assertSame('2026-11-04', $config->windowEnd('2026-10-05'));
    }

    /**
     * A zero window would match nothing, silently disabling dedupe.
     */
    public function testZeroWindowIsFlooredAtOneDay(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_WINDOW_DAYS => 0));

        $this->assertSame(1, $config->getWindowDays());
    }

    public function testNegativeWindowIsFlooredAtOneDay(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_WINDOW_DAYS => -5));

        $this->assertSame(1, $config->getWindowDays());
    }

    public function testUnparseableDateFallsBackToToday(): void
    {
        $config = new MatchConfig();
        $today = date('Y-m-d');

        $this->assertSame(
            date('Y-m-d', strtotime('-7 days', strtotime($today))),
            $config->windowStart('not-a-date')
        );
    }

    public function testAmountToleranceIsAbsolute(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_AMOUNT_TOLERANCE => 0.01));

        $this->assertTrue($config->amountsMatch(100.00, 100.01));
        $this->assertFalse($config->amountsMatch(100.00, 100.50));
    }

    public function testZeroToleranceRequiresExactAmount(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_AMOUNT_TOLERANCE => 0));

        $this->assertTrue($config->amountsMatch(50.00, 50.00));
        $this->assertFalse($config->amountsMatch(50.00, 50.01));
    }

    /**
     * A review band above the auto-approve threshold could never be reached.
     */
    public function testReviewThresholdIsClampedBelowAutoApprove(): void
    {
        $config = new MatchConfig(array(
            MatchConfig::PREF_AUTO_APPROVE => 0.80,
            MatchConfig::PREF_REVIEW => 0.99,
        ));

        $this->assertSame(0.80, $config->getReviewThreshold());
    }

    public function testThresholdsAreClampedToUnitRange(): void
    {
        $config = new MatchConfig(array(
            MatchConfig::PREF_AUTO_APPROVE => 5.0,
            MatchConfig::PREF_REVIEW => -1.0,
            MatchConfig::PREF_LINE_THRESHOLD => 99.0,
        ));

        $this->assertSame(1.0, $config->getAutoApproveThreshold());
        $this->assertSame(0.0, $config->getReviewThreshold());
        $this->assertSame(1.0, $config->getLineThreshold());
    }

    public function testAutoApproveAndReviewBands(): void
    {
        $config = new MatchConfig(array(
            MatchConfig::PREF_AUTO_APPROVE => 0.95,
            MatchConfig::PREF_REVIEW => 0.80,
        ));

        $this->assertTrue($config->shouldAutoApprove(0.95));
        $this->assertTrue($config->shouldAutoApprove(1.0));
        $this->assertFalse($config->shouldAutoApprove(0.94));

        $this->assertFalse($config->needsReview(0.95), 'auto-approved is not also "needs review"');
        $this->assertTrue($config->needsReview(0.85));
        $this->assertFalse($config->needsReview(0.10));
    }

    public function testSoundexCanBeDisabled(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_NAME_SOUNDEX => 'no'));

        $this->assertFalse($config->usesSoundex());
    }

    public function testSoundexAcceptsTruthyStrings(): void
    {
        foreach (array('1', 'true', 'TRUE', 'yes', 'on') as $truthy) {
            $config = new MatchConfig(array(MatchConfig::PREF_NAME_SOUNDEX => $truthy));
            $this->assertTrue($config->usesSoundex(), "expected '{$truthy}' to enable soundex");
        }
    }

    public function testNonNumericOverridesFallBackToDefaults(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_WINDOW_DAYS => 'soon'));

        $this->assertSame(MatchConfig::DEFAULT_WINDOW_DAYS, $config->getWindowDays());
    }

    public function testToArrayRoundTrips(): void
    {
        $config = new MatchConfig(array(MatchConfig::PREF_WINDOW_DAYS => 14));
        $array = $config->toArray();

        $this->assertSame(14, $array[MatchConfig::PREF_WINDOW_DAYS]);

        $rebuilt = new MatchConfig($array);
        $this->assertSame($config->toArray(), $rebuilt->toArray());
    }
}
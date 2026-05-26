<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\ImportStaging\Services\MatchingService;

class MatchingServiceTest extends TestCase
{
    private MatchingService $service;

    protected function setUp(): void
    {
        $this->service = new MatchingService(0.95, 0.80);
    }

    public function testAutoApproveHighConfidence(): void
    {
        $this->assertTrue($this->service->autoApprove(0.95));
        $this->assertTrue($this->service->autoApprove(1.0));
    }

    public function testDoesNotAutoApproveLowConfidence(): void
    {
        $this->assertFalse($this->service->autoApprove(0.94));
        $this->assertFalse($this->service->autoApprove(0.5));
    }

    public function testNeedsReviewCorrectRange(): void
    {
        $this->assertTrue($this->service->needsReview(0.80));
        $this->assertTrue($this->service->needsReview(0.90));
        $this->assertTrue($this->service->needsReview(0.94));
        $this->assertFalse($this->service->needsReview(0.95));
        $this->assertFalse($this->service->needsReview(0.79));
    }

    public function testExactAmountMatch(): void
    {
        $score = $this->service->matchAmount(100.00, 100.00);
        $this->assertEquals(1.0, $score);
    }

    public function testAmountMatchWithinTolerance(): void
    {
        $score = $this->service->matchAmount(100.00, 100.50);
        $this->assertGreaterThan(0.9, $score);
    }

    public function testAmountMismatch(): void
    {
        $score = $this->service->matchAmount(100.00, 50.00);
        $this->assertLessThan(0.6, $score);
    }

    public function testExactDateMatch(): void
    {
        $score = $this->service->matchDate('2026-05-26', '2026-05-26');
        $this->assertEquals(1.0, $score);
    }

    public function testDateMatchWithinTolerance(): void
    {
        $score = $this->service->matchDate('2026-05-26', '2026-05-25');
        $this->assertGreaterThan(0.5, $score);
    }

    public function testDateMismatch(): void
    {
        $score = $this->service->matchDate('2026-05-26', '2026-01-01');
        $this->assertLessThan(0.5, $score);
    }

    public function testExactNameMatch(): void
    {
        $score = $this->service->matchName('John Doe', 'John Doe');
        $this->assertEquals(1.0, $score);
    }

    public function testPartialNameMatch(): void
    {
        $score = $this->service->matchName('John Doe', 'John D.');
        $this->assertGreaterThan(0.5, $score);
    }

    public function testCompleteMatchProducesHighConfidence(): void
    {
        $staged = [
            'total_amount' => 100.00,
            'transaction_date' => '2026-05-26',
            'customer_name' => 'John Doe',
            'source_transaction_id' => 'txn_001',
        ];
        $existing = [
            'total_amount' => 100.00,
            'transaction_date' => '2026-05-26',
            'customer_name' => 'John Doe',
            'source_transaction_id' => 'txn_001',
        ];
        $result = $this->service->matchCandidates($staged, [$existing]);
        $this->assertGreaterThanOrEqual(0.95, $result['confidence']);
        $this->assertEquals('exact', $result['match_type']);
    }

    public function testNoExistingRecordsReturnsUnmatched(): void
    {
        $result = $this->service->matchCandidates(['total_amount' => 100], []);
        $this->assertEquals(0.0, $result['confidence']);
        $this->assertEquals('unmatched', $result['match_type']);
    }
}

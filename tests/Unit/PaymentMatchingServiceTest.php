<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\ImportStaging\Services\MatchingService;

class PaymentMatchingServiceTest extends TestCase
{
    private MatchingService $service;

    protected function setUp(): void
    {
        $this->service = new MatchingService(0.95, 0.80);
    }

    public function testExactPaymentMatchProducesHighConfidence(): void
    {
        $payment = [
            'amount' => 100.00,
            'payment_date' => '2026-05-26',
            'reference' => 'CHK-001',
            'payment_method' => 'credit_card',
        ];
        $faRecord = [
            'amount' => 100.00,
            'payment_date' => '2026-05-26',
            'reference' => 'CHK-001',
            'payment_method' => 'credit_card',
        ];
        $confidence = $this->service->calculatePaymentMatchScore($payment, $faRecord);
        $this->assertGreaterThanOrEqual(0.95, $confidence);
        $this->assertEquals('exact', $this->service->determinePaymentMatchType($confidence));
    }

    public function testPartialPaymentMatch(): void
    {
        $payment = [
            'amount' => 100.00,
            'payment_date' => '2026-05-26',
            'reference' => 'CHK-001',
        ];
        $faRecord = [
            'amount' => 50.00,
            'payment_date' => '2026-05-25',
            'reference' => 'CHK-001',
        ];
        $confidence = $this->service->calculatePaymentMatchScore($payment, $faRecord);
        $this->assertGreaterThan(0.0, $confidence);
        $this->assertLessThan(0.95, $confidence);
    }

    public function testNoPaymentMatchReturnsLowConfidence(): void
    {
        $payment = [
            'amount' => 100.00,
            'payment_date' => '2026-05-26',
            'reference' => 'CHK-001',
        ];
        $faRecord = [
            'amount' => 500.00,
            'payment_date' => '2026-01-01',
            'reference' => 'ZZZ-999',
        ];
        $confidence = $this->service->calculatePaymentMatchScore($payment, $faRecord);
        $this->assertLessThan(0.5, $confidence);
        $this->assertEquals('partial', $this->service->determinePaymentMatchType($confidence));
    }

    public function testEmptyRecordsReturnZeroConfidence(): void
    {
        $confidence = $this->service->calculatePaymentMatchScore([], []);
        $this->assertEquals(0.0, $confidence);
    }

    public function testPaymentMatchTypeNone(): void
    {
        $this->assertEquals('none', $this->service->determinePaymentMatchType(0.0));
    }

    public function testPaymentMatchTypePartial(): void
    {
        $this->assertEquals('partial', $this->service->determinePaymentMatchType(0.5));
    }

    public function testPaymentMatchTypeFuzzy(): void
    {
        $this->assertEquals('fuzzy', $this->service->determinePaymentMatchType(0.85));
    }

    public function testPaymentMatchTypeExact(): void
    {
        $this->assertEquals('exact', $this->service->determinePaymentMatchType(0.95));
    }

    public function testAmountMismatchPenalizesScore(): void
    {
        $payment = [
            'amount' => 100.00,
            'payment_date' => '2026-05-26',
        ];
        $faRecord = [
            'amount' => 150.00,
            'payment_date' => '2026-05-26',
        ];
        $confidence = $this->service->calculatePaymentMatchScore($payment, $faRecord);
        $this->assertLessThan(0.9, $confidence);
        $this->assertGreaterThan(0.5, $confidence);
    }
}

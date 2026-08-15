<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Validators\PaymentValidator;

class PaymentValidatorTest extends TestCase
{
    private PaymentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PaymentValidator();
    }

    public function testValidPaymentPasses(): void
    {
        $result = $this->validator->validate([
            'source' => 'square_api',
            'amount' => 100.00,
            'fee' => 3.00,
            'net_amount' => 97.00,
            'payment_date' => '2026-05-26',
            'payment_method' => 'credit_card',
        ]);
        $this->assertTrue($result->isSuccess());
    }

    public function testPaymentMissingSourceFails(): void
    {
        $result = $this->validator->validate([
            'amount' => 100.00,
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testPaymentNonNumericAmountFails(): void
    {
        $result = $this->validator->validate([
            'source' => 'square_api',
            'amount' => 'abc',
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testPaymentAmountBelowMinimumFails(): void
    {
        $result = $this->validator->validate([
            'source' => 'square_api',
            'amount' => 0.001,
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testPaymentInvalidDateFails(): void
    {
        $result = $this->validator->validate([
            'source' => 'square_api',
            'amount' => 100.00,
            'payment_date' => 'not-a-date',
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testPaymentNegativeFeeFails(): void
    {
        $result = $this->validator->validate([
            'source' => 'square_api',
            'amount' => 100.00,
            'fee' => -5.00,
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testPaymentNetAmountMismatchGeneratesWarning(): void
    {
        $result = $this->validator->validate([
            'source' => 'square_api',
            'amount' => 100.00,
            'fee' => 3.00,
            'net_amount' => 90.00,
        ]);
        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->hasWarnings());
    }

    public function testPaymentUnknownMethodGeneratesWarning(): void
    {
        $result = $this->validator->validate([
            'source' => 'square_api',
            'amount' => 50.00,
            'payment_method' => 'crypto',
        ]);
        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->hasWarnings());
    }

    public function testMinimalValidPaymentPasses(): void
    {
        $result = $this->validator->validate([
            'source' => 'woocommerce',
            'amount' => 25.00,
        ]);
        $this->assertTrue($result->isSuccess());
    }

    public function testValidPaymentMethodsDoNotGenerateWarning(): void
    {
        $methods = ['credit_card', 'cash', 'gift_card', 'check', 'other'];
        foreach ($methods as $method) {
            $result = $this->validator->validate([
                'source' => 'square_api',
                'amount' => 100.00,
                'payment_method' => $method,
            ]);
            $this->assertTrue($result->isSuccess());
            $this->assertFalse($result->hasWarnings());
        }
    }
}

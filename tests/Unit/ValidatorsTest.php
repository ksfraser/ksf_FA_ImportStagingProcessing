<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Validators\TransactionValidator;
use ksfraser\FrontAccounting\ImportStaging\Validators\CustomerValidator;

class ValidatorsTest extends TestCase
{
    private TransactionValidator $txnValidator;
    private CustomerValidator $custValidator;

    protected function setUp(): void
    {
        $this->txnValidator = new TransactionValidator();
        $this->custValidator = new CustomerValidator();
    }

    public function testValidTransactionPasses(): void
    {
        $result = $this->txnValidator->validate([
            'source' => 'woocommerce',
            'total_amount' => 100.00,
            'transaction_date' => '2026-05-26',
        ]);
        $this->assertTrue($result->isSuccess());
    }

    public function testTransactionMissingSourceFails(): void
    {
        $result = $this->txnValidator->validate([
            'total_amount' => 100.00,
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testTransactionNonNumericAmountFails(): void
    {
        $result = $this->txnValidator->validate([
            'source' => 'woocommerce',
            'total_amount' => 'abc',
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testTransactionNegativeAmountFails(): void
    {
        $result = $this->txnValidator->validate([
            'source' => 'woocommerce',
            'total_amount' => -10,
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testTransactionInvalidDateFails(): void
    {
        $result = $this->txnValidator->validate([
            'source' => 'woocommerce',
            'total_amount' => 100,
            'transaction_date' => 'not-a-date',
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testValidCustomerPasses(): void
    {
        $result = $this->custValidator->validate([
            'source' => 'woocommerce',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $this->assertTrue($result->isSuccess());
    }

    public function testCustomerMissingNameFails(): void
    {
        $result = $this->custValidator->validate([
            'source' => 'square_api',
        ]);
        $this->assertFalse($result->isSuccess());
    }

    public function testCustomerInvalidEmailGeneratesWarning(): void
    {
        $result = $this->custValidator->validate([
            'source' => 'woocommerce',
            'name' => 'John Doe',
            'email' => 'not-an-email',
        ]);
        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->hasWarnings());
    }

    public function testCustomerNameTooLongFails(): void
    {
        $result = $this->custValidator->validate([
            'source' => 'woocommerce',
            'name' => str_repeat('x', 256),
        ]);
        $this->assertFalse($result->isSuccess());
    }
}

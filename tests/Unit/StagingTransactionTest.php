<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\ImportStaging\Models\StagingTransaction;

class StagingTransactionTest extends TestCase
{
    public function testCanCreateTransaction(): void
    {
        $txn = new StagingTransaction('woocommerce');
        $this->assertEquals('woocommerce', $txn->getSource());
        $this->assertEquals('staged', $txn->getStatus());
        $this->assertEquals('CAD', $txn->getCurrency());
    }

    public function testCanSetAndGetProperties(): void
    {
        $txn = new StagingTransaction('square_api');
        $txn->setTotalAmount(100.00);
        $txn->setTaxAmount(13.00);
        $txn->setSourceTransactionId('txn_123');
        $txn->setTransactionDate(new \DateTimeImmutable('2026-05-26'));
        $this->assertEquals(100.00, $txn->getTotalAmount());
        $this->assertEquals(13.00, $txn->getTaxAmount());
        $this->assertEquals('txn_123', $txn->getSourceTransactionId());
        $this->assertEquals('2026-05-26', $txn->getTransactionDate()->format('Y-m-d'));
    }

    public function testCanConvertToArray(): void
    {
        $txn = new StagingTransaction('paypal');
        $txn->setTotalAmount(50.00);
        $txn->setCurrency('USD');
        $array = $txn->toArray();
        $this->assertEquals('paypal', $array['source']);
        $this->assertEquals(50.00, $array['total_amount']);
        $this->assertEquals('USD', $array['currency']);
    }

    public function testCanCreateFromArray(): void
    {
        $txn = StagingTransaction::fromArray([
            'source' => 'square_csv',
            'source_transaction_id' => 'csv_001',
            'total_amount' => 75.50,
            'currency' => 'CAD',
            'status' => 'processed',
            'fa_invoice_no' => 1001,
        ]);
        $this->assertEquals('square_csv', $txn->getSource());
        $this->assertEquals('csv_001', $txn->getSourceTransactionId());
        $this->assertEquals(75.50, $txn->getTotalAmount());
        $this->assertEquals('processed', $txn->getStatus());
        $this->assertEquals(1001, $txn->getFaInvoiceNo());
    }

    public function testCanSetFaReferences(): void
    {
        $txn = new StagingTransaction('woocommerce');
        $txn->setFaInvoiceNo(500);
        $txn->setFaDebtorNo(42);
        $this->assertEquals(500, $txn->getFaInvoiceNo());
        $this->assertEquals(42, $txn->getFaDebtorNo());
    }

    public function testCanSetMatchConfidence(): void
    {
        $txn = new StagingTransaction('square_api');
        $txn->setMatchConfidence(0.95);
        $this->assertEquals(0.95, $txn->getMatchConfidence());
    }
}

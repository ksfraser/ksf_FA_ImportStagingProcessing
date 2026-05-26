<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\ImportStaging\Models\StagingPayment;
use Ksfraser\ImportStaging\Models\StagingPaymentMatch;

class StagingPaymentTest extends TestCase
{
    public function testCanCreatePayment(): void
    {
        $payment = new StagingPayment('square_api');
        $this->assertEquals('square_api', $payment->getSource());
        $this->assertEquals('staged', $payment->getStatus());
        $this->assertEquals('CAD', $payment->getCurrency());
        $this->assertEquals(0.0, $payment->getAmount());
        $this->assertEquals(0.0, $payment->getFee());
        $this->assertEquals(0.0, $payment->getNetAmount());
    }

    public function testCanSetAndGetProperties(): void
    {
        $payment = new StagingPayment('square_csv');
        $payment->setAmount(150.00);
        $payment->setFee(4.50);
        $payment->setNetAmount(145.50);
        $payment->setPaymentMethod('credit_card');
        $payment->setCardBrand('Visa');
        $payment->setPanSuffix('1234');
        $payment->setReference('CHK-001');
        $payment->setPaymentDate(new \DateTimeImmutable('2026-05-26'));

        $this->assertEquals(150.00, $payment->getAmount());
        $this->assertEquals(4.50, $payment->getFee());
        $this->assertEquals(145.50, $payment->getNetAmount());
        $this->assertEquals('credit_card', $payment->getPaymentMethod());
        $this->assertEquals('Visa', $payment->getCardBrand());
        $this->assertEquals('1234', $payment->getPanSuffix());
        $this->assertEquals('CHK-001', $payment->getReference());
        $this->assertEquals('2026-05-26', $payment->getPaymentDate()->format('Y-m-d'));
    }

    public function testCanConvertToArray(): void
    {
        $payment = new StagingPayment('paypal');
        $payment->setAmount(200.00);
        $payment->setFee(6.50);
        $payment->setPaymentMethod('other');
        $array = $payment->toArray();

        $this->assertEquals('paypal', $array['source']);
        $this->assertEquals(200.00, $array['amount']);
        $this->assertEquals(6.50, $array['fee']);
        $this->assertEquals('other', $array['payment_method']);
    }

    public function testCanCreateFromArray(): void
    {
        $payment = StagingPayment::fromArray([
            'source' => 'woocommerce',
            'source_payment_id' => 'pay_123',
            'amount' => 75.00,
            'fee' => 2.50,
            'net_amount' => 72.50,
            'payment_method' => 'credit_card',
            'card_brand' => 'Mastercard',
            'pan_suffix' => '9876',
            'status' => 'matched',
        ]);

        $this->assertEquals('woocommerce', $payment->getSource());
        $this->assertEquals('pay_123', $payment->getSourcePaymentId());
        $this->assertEquals(75.00, $payment->getAmount());
        $this->assertEquals(2.50, $payment->getFee());
        $this->assertEquals(72.50, $payment->getNetAmount());
        $this->assertEquals('credit_card', $payment->getPaymentMethod());
        $this->assertEquals('Mastercard', $payment->getCardBrand());
        $this->assertEquals('9876', $payment->getPanSuffix());
        $this->assertEquals('matched', $payment->getStatus());
    }

    public function testCanSetFaReferences(): void
    {
        $payment = new StagingPayment('square_api');
        $payment->setFaTransType(1);
        $payment->setFaTransNo(500);
        $payment->setFaBankAccount('1010');
        $payment->setMatchConfidence(0.95);

        $this->assertEquals(1, $payment->getFaTransType());
        $this->assertEquals(500, $payment->getFaTransNo());
        $this->assertEquals('1010', $payment->getFaBankAccount());
        $this->assertEquals(0.95, $payment->getMatchConfidence());
    }

    public function testCanLinkToStagingTransaction(): void
    {
        $payment = new StagingPayment('square_api');
        $payment->setStagingTransactionId(42);
        $payment->setSourceTransactionId('txn_789');

        $this->assertEquals(42, $payment->getStagingTransactionId());
        $this->assertEquals('txn_789', $payment->getSourceTransactionId());
    }
}

class StagingPaymentMatchTest extends TestCase
{
    public function testCanCreatePaymentMatch(): void
    {
        $match = new StagingPaymentMatch(1, 'none');
        $this->assertEquals(1, $match->getStagingPaymentId());
        $this->assertEquals('none', $match->getMatchType());
        $this->assertEquals('matched', $match->getMatchStatus());
        $this->assertEquals('system', $match->getMatchedBy());
    }

    public function testCanSetAndGetProperties(): void
    {
        $match = new StagingPaymentMatch(5, 'exact');
        $match->setMatchConfidence(0.98);
        $match->setFaTransType(0);
        $match->setFaTransNo(1001);
        $match->setFaBankAccount('1020');
        $match->setMatchStatus('matched');
        $match->setNotes('Auto-matched by system');

        $this->assertEquals(5, $match->getStagingPaymentId());
        $this->assertEquals('exact', $match->getMatchType());
        $this->assertEquals(0.98, $match->getMatchConfidence());
        $this->assertEquals(0, $match->getFaTransType());
        $this->assertEquals(1001, $match->getFaTransNo());
        $this->assertEquals('1020', $match->getFaBankAccount());
        $this->assertEquals('Auto-matched by system', $match->getNotes());
    }

    public function testCanConvertToArray(): void
    {
        $match = new StagingPaymentMatch(3, 'fuzzy');
        $match->setMatchConfidence(0.85);
        $match->setMatchStatus('needs_review');
        $array = $match->toArray();

        $this->assertEquals(3, $array['staging_payment_id']);
        $this->assertEquals('fuzzy', $array['match_type']);
        $this->assertEquals(0.85, $array['match_confidence']);
        $this->assertEquals('needs_review', $array['match_status']);
    }

    public function testCanCreateFromArray(): void
    {
        $match = StagingPaymentMatch::fromArray([
            'staging_payment_id' => 10,
            'match_type' => 'manual',
            'match_confidence' => 1.0,
            'fa_trans_type' => 1,
            'fa_trans_no' => 2002,
            'match_status' => 'matched',
            'matched_by' => 'admin',
        ]);

        $this->assertEquals(10, $match->getStagingPaymentId());
        $this->assertEquals('manual', $match->getMatchType());
        $this->assertEquals(1.0, $match->getMatchConfidence());
        $this->assertEquals(1, $match->getFaTransType());
        $this->assertEquals(2002, $match->getFaTransNo());
        $this->assertEquals('admin', $match->getMatchedBy());
    }
}

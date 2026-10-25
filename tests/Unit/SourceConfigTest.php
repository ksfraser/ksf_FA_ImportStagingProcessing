<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Config\SourceConfig;

/**
 * Unit tests for SourceConfig value object.
 *
 * @package Ksfraser\FrontAccounting\ImportStaging\Tests\Unit
 * @since 2.0.0
 * @BABOK Related: FR-ISU-021 SourceConfig value object
 */
class SourceConfigTest extends TestCase
{
    public function testConstructionWithDefaults(): void
    {
        $config = new SourceConfig('square', 100, 200);

        $this->assertSame('square', $config->getSource());
        $this->assertSame(100, $config->getGlAccount());
        $this->assertSame(200, $config->getBankAccount());
        $this->assertSame(0, $config->getCashGl());
        $this->assertSame(0, $config->getXferToGl());
        $this->assertSame(0, $config->getXferToBank());
        $this->assertSame(0, $config->getCashBank());
        $this->assertSame(0, $config->getDefaultPayCard());
        $this->assertSame(0, $config->getDefaultPayCash());
        $this->assertFalse($config->isUseCardAsBranch());
        $this->assertFalse($config->isAllowSkuChange());
    }

    public function testFromArrayAndToArrayRoundTrip(): void
    {
        $data = [
            'source'            => 'woocommerce',
            'gl_account'        => 50,
            'cash_gl'           => 51,
            'xfer_to_gl'        => 52,
            'bank_account'      => 60,
            'xfer_to_bank'      => 61,
            'cash_bank'         => 62,
            'default_pay_card'  => 70,
            'default_pay_cash'  => 71,
            'useCardAsBranch'   => true,
            'allowSkuChange'    => true,
        ];

        $config = SourceConfig::fromArray($data);
        $this->assertSame($data, $config->toArray());
    }

    public function testFromArrayWithMissingKeysUsesDefaults(): void
    {
        $config = SourceConfig::fromArray(['source' => 'paypal', 'gl_account' => 5, 'bank_account' => 6]);

        $this->assertSame('paypal', $config->getSource());
        $this->assertSame(5, $config->getGlAccount());
        $this->assertSame(6, $config->getBankAccount());
        $this->assertSame(0, $config->getCashGl());
        $this->assertFalse($config->isUseCardAsBranch());
    }

    public function testWithGlAccountReturnsClone(): void
    {
        $original = new SourceConfig('square', 100, 200);
        $modified = $original->withGlAccount(300);

        $this->assertNotSame($original, $modified);
        $this->assertSame(100, $original->getGlAccount());
        $this->assertSame(300, $modified->getGlAccount());
        $this->assertSame(200, $modified->getBankAccount());
    }

    public function testWithBankAccountReturnsClone(): void
    {
        $original = new SourceConfig('square', 100, 200);
        $modified = $original->withBankAccount(400);

        $this->assertNotSame($original, $modified);
        $this->assertSame(200, $original->getBankAccount());
        $this->assertSame(400, $modified->getBankAccount());
        $this->assertSame(100, $modified->getGlAccount());
    }

    public function testWithCashGlReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withCashGl(10);

        $this->assertSame(0, $config->getCashGl());
        $this->assertSame(10, $modified->getCashGl());
    }

    public function testWithXferToGlReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withXferToGl(11);

        $this->assertSame(0, $config->getXferToGl());
        $this->assertSame(11, $modified->getXferToGl());
    }

    public function testWithXferToBankReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withXferToBank(12);

        $this->assertSame(0, $config->getXferToBank());
        $this->assertSame(12, $modified->getXferToBank());
    }

    public function testWithCashBankReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withCashBank(13);

        $this->assertSame(0, $config->getCashBank());
        $this->assertSame(13, $modified->getCashBank());
    }

    public function testWithDefaultPayCardReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withDefaultPayCard(14);

        $this->assertSame(0, $config->getDefaultPayCard());
        $this->assertSame(14, $modified->getDefaultPayCard());
    }

    public function testWithDefaultPayCashReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withDefaultPayCash(15);

        $this->assertSame(0, $config->getDefaultPayCash());
        $this->assertSame(15, $modified->getDefaultPayCash());
    }

    public function testWithUseCardAsBranchReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withUseCardAsBranch(true);

        $this->assertFalse($config->isUseCardAsBranch());
        $this->assertTrue($modified->isUseCardAsBranch());
    }

    public function testWithAllowSkuChangeReturnsClone(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $modified = $config->withAllowSkuChange(true);

        $this->assertFalse($config->isAllowSkuChange());
        $this->assertTrue($modified->isAllowSkuChange());
    }

    public function testGetGlForAmountPositiveUsesGlAccount(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $config = $config->withCashGl(150);

        $this->assertSame(100, $config->getGlForAmount(50.00));
    }

    public function testGetGlForAmountNegativeUsesCashGl(): void
    {
        $config = new SourceConfig('square', 100, 200);
        $config = $config->withCashGl(150);

        $this->assertSame(150, $config->getGlForAmount(-10.00));
    }

    public function testGetGlForAmountFallsBackToGlAccount(): void
    {
        $config = new SourceConfig('square', 100, 200);

        $this->assertSame(100, $config->getGlForAmount(-10.00));
    }

    public function testEmptySourceAllowed(): void
    {
        $config = new SourceConfig();

        $this->assertSame('', $config->getSource());
    }
}

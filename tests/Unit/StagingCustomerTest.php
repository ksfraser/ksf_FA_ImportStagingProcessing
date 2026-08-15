<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingCustomer;

class StagingCustomerTest extends TestCase
{
    public function testCanCreateCustomer(): void
    {
        $customer = new StagingCustomer('woocommerce');
        $this->assertEquals('woocommerce', $customer->getSource());
        $this->assertEquals('staged', $customer->getStatus());
    }

    public function testCanSetAndGetProperties(): void
    {
        $customer = new StagingCustomer('square_api');
        $customer->setName('John Doe');
        $customer->setEmail('john@example.com');
        $customer->setSourceCustomerId('cus_123');
        $this->assertEquals('John Doe', $customer->getName());
        $this->assertEquals('john@example.com', $customer->getEmail());
        $this->assertEquals('cus_123', $customer->getSourceCustomerId());
    }

    public function testCanConvertToArray(): void
    {
        $customer = new StagingCustomer('paypal');
        $customer->setName('Jane Doe');
        $customer->setEmail('jane@example.com');
        $array = $customer->toArray();
        $this->assertEquals('paypal', $array['source']);
        $this->assertEquals('Jane Doe', $array['name']);
        $this->assertEquals('jane@example.com', $array['email']);
    }

    public function testCanCreateFromArray(): void
    {
        $customer = StagingCustomer::fromArray([
            'source' => 'square_csv',
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'status' => 'matched',
            'fa_debtor_no' => 42,
        ]);
        $this->assertEquals('square_csv', $customer->getSource());
        $this->assertEquals('Bob Smith', $customer->getName());
        $this->assertEquals('bob@example.com', $customer->getEmail());
        $this->assertEquals('matched', $customer->getStatus());
        $this->assertEquals(42, $customer->getFaDebtorNo());
    }

    public function testStatusDefaultsToStaged(): void
    {
        $customer = new StagingCustomer('bank');
        $this->assertEquals('staged', $customer->getStatus());
    }

    public function testCanUpdateStatus(): void
    {
        $customer = new StagingCustomer('woocommerce');
        $customer->setStatus('processed');
        $this->assertEquals('processed', $customer->getStatus());
    }
}

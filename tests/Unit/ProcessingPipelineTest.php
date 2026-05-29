<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\ImportStaging\Services\ProcessingPipeline;
use Ksfraser\ImportStaging\Contracts\ProcessingResult;
use Ksfraser\ImportStaging\DAO\StagingCustomerDAO;
use Ksfraser\ImportStaging\DAO\StagingTransactionDAO;
use Ksfraser\ImportStaging\DAO\StagingPaymentDAO;
use Ksfraser\ImportStaging\DAO\StagingLineItemDAO;
use Ksfraser\ImportStaging\DAO\StagingLogDAO;
use Ksfraser\ImportStaging\Models\StagingCustomer;
use Ksfraser\ImportStaging\Models\StagingTransaction;
use Ksfraser\ImportStaging\Models\StagingPayment;

class ProcessingPipelineTest extends TestCase
{
    private $customerDAO;
    private $transactionDAO;
    private $paymentDAO;
    private $lineItemDAO;
    private $logDAO;
    private ProcessingPipeline $pipeline;

    protected function setUp(): void
    {
        $this->customerDAO = $this->createMock(StagingCustomerDAO::class);
        $this->transactionDAO = $this->createMock(StagingTransactionDAO::class);
        $this->paymentDAO = $this->createMock(StagingPaymentDAO::class);
        $this->lineItemDAO = $this->createMock(StagingLineItemDAO::class);
        $this->logDAO = $this->createMock(StagingLogDAO::class);
        $this->pipeline = new ProcessingPipeline(
            $this->customerDAO,
            $this->transactionDAO,
            $this->paymentDAO,
            $this->lineItemDAO,
            $this->logDAO
        );
    }

    public function testCanProcessReturnsTrueForApproved(): void
    {
        $this->assertTrue($this->pipeline->canProcess(['status' => 'approved']));
        $this->assertTrue($this->pipeline->canProcess(['status' => 'matched']));
        $this->assertTrue($this->pipeline->canProcess(['status' => 'reconciled']));
    }

    public function testCanProcessReturnsFalseForStaged(): void
    {
        $this->assertFalse($this->pipeline->canProcess(['status' => 'staged']));
        $this->assertFalse($this->pipeline->canProcess(['status' => 'failed']));
        $this->assertFalse($this->pipeline->canProcess(['status' => '']));
    }

    public function testProcessAllWithNoRecords(): void
    {
        $this->customerDAO->method('findByStatus')->willReturn([]);
        $this->transactionDAO->method('findByStatus')->willReturn([]);
        $this->paymentDAO->method('findByStatus')->willReturn([]);

        $result = $this->pipeline->processAll();
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0, $result->getRecordId());
    }

    public function testProcessAllWithoutFaReportsNoSuccess(): void
    {
        $customer = new StagingCustomer('woocommerce');
        $customer->setId(1);
        $customer->setName('Test Customer');

        $this->customerDAO->method('findByStatus')->willReturn([$customer]);
        $this->transactionDAO->method('findByStatus')->willReturn([]);
        $this->paymentDAO->method('findByStatus')->willReturn([]);

        $this->logDAO->expects($this->atLeastOnce())->method('log');

        $result = $this->pipeline->processAll();
        $this->assertFalse($result->isSuccess());
        $this->assertCount(1, $result->getErrors());
    }

    public function testProcessDispatchCustomerType(): void
    {
        $result = $this->pipeline->process(
            ['name' => 'Test', 'status' => 'approved'],
            ['type' => 'customer']
        );
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('customer_failed', $result->getAction());
    }

    public function testProcessDispatchPaymentType(): void
    {
        $result = $this->pipeline->process(
            ['amount' => 100, 'status' => 'reconciled'],
            ['type' => 'payment']
        );
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('payment_failed', $result->getAction());
    }

    public function testProcessDispatchDefaultType(): void
    {
        $result = $this->pipeline->process(['status' => 'matched']);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('unsupported_type', $result->getAction());
    }

    public function testProcessAllHandlesMultipleRecordTypes(): void
    {
        $customer = new StagingCustomer('woocommerce');
        $customer->setId(10);
        $customer->setName('Multi Customer');

        $transaction = new StagingTransaction('woocommerce');
        $transaction->setId(20);
        $transaction->setTotalAmount(150.00);
        $transaction->setCustomerName('Multi Customer');

        $payment = new StagingPayment('square_api');
        $payment->setId(30);
        $payment->setAmount(150.00);
        $payment->setNetAmount(147.00);
        $payment->setReference('MULTI-001');

        $this->customerDAO->method('findByStatus')->willReturn([$customer]);
        $this->transactionDAO->method('findByStatus')->willReturn([$transaction]);
        $this->paymentDAO->method('findByStatus')->willReturn([$payment]);

        $this->logDAO->expects($this->atLeast(3))->method('log');

        $result = $this->pipeline->processAll();
        $this->assertFalse($result->isSuccess());
        $this->assertGreaterThanOrEqual(3, count($result->getErrors()) + ($result->getRecordId() ?? 0));
    }

    public function testProcessPipelineWithSourceFilter(): void
    {
        $this->customerDAO->expects($this->once())
            ->method('findByStatus')
            ->with('approved', 'woocommerce')
            ->willReturn([]);

        $this->transactionDAO->expects($this->once())
            ->method('findByStatus')
            ->with('approved', 'woocommerce')
            ->willReturn([]);

        $this->paymentDAO->expects($this->once())
            ->method('findByStatus')
            ->with('reconciled', 'woocommerce')
            ->willReturn([]);

        $this->pipeline->processAll('woocommerce');
    }

    public function testProcessMethodWithDefaultType(): void
    {
        $record = ['status' => 'matched'];
        $result = $this->pipeline->process($record, []);
        $this->assertFalse($result->isSuccess());
    }
}

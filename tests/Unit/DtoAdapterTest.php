<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Services\DtoAdapter;
use ksfraser\FrontAccounting\ImportStaging\Services\StagingService;
use ksfraser\FrontAccounting\ImportStaging\Contracts\TransactionRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\CustomerRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingTransaction;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingCustomer;
use Ksfraser\StagingDto\StagingOrder;
use Ksfraser\StagingDto\StagingPayment;
use Ksfraser\StagingDto\StagingCustomer as StagingCustomerDto;
use Ksfraser\StagingDto\StagingExistsQuery;
use Ksfraser\StagingDto\StagingExistsResult;

/**
 * Unit tests for DtoAdapter.
 *
 * @package Ksfraser\FrontAccounting\ImportStaging\Tests\Unit
 * @since 1.1.0
 */
class DtoAdapterTest extends TestCase
{
    private $stagingService;
    private $transactionDAO;
    private $customerDAO;
    private $paymentDAO;
    private DtoAdapter $adapter;

    protected function setUp(): void
    {
        $this->stagingService = $this->createMock(StagingService::class);
        $this->transactionDAO = $this->createMock(TransactionRepositoryInterface::class);
        $this->customerDAO = $this->createMock(CustomerRepositoryInterface::class);
        $this->paymentDAO = $this->createMock(PaymentRepositoryInterface::class);

        $this->adapter = new DtoAdapter(
            $this->stagingService,
            $this->transactionDAO,
            $this->customerDAO,
            $this->paymentDAO
        );
    }

    public function testStageOrderSuccess(): void
    {
        $dto = new StagingOrder(
            'square',
            'sq_txn_123',
            100.00,
            'USD',
            'completed',
            'card',
            [['sku' => 'ITEM001', 'quantity' => 2, 'unit_price' => 25.00]],
            'cust_001',
            ['street' => '123 Main St'],
            ['street' => '456 Oak Ave']
        );

        $stagingTxn = $this->createMock(StagingTransaction::class);
        $stagingTxn->method('getId')->willReturn(42);
        $stagingTxn->method('getStatus')->willReturn('staged');

        $this->stagingService->expects($this->once())
            ->method('stageOrUpdateTransaction')
            ->with(
                $this->callback(function ($data) {
                    return $data['source'] === 'square'
                        && $data['source_transaction_id'] === 'sq_txn_123'
                        && $data['total_amount'] === 100.00
                        && $data['currency'] === 'USD'
                        && isset($data['line_items']);
                }),
                $this->equalTo('square')
            )
            ->willReturn($stagingTxn);

        $result = $this->adapter->stageEntity($dto);

        $this->assertInstanceOf(StagingExistsResult::class, $result);
        $this->assertTrue($result->getExists());
        $this->assertEquals(42, $result->getStagingId());
        $this->assertEquals('staged', $result->getStatus());
    }

    public function testStageOrderFailure(): void
    {
        $dto = new StagingOrder('square', 'sq_txn_456', 50.00, 'USD', 'pending', 'card');

        $this->stagingService->expects($this->once())
            ->method('stageOrUpdateTransaction')
            ->willThrowException(new \RuntimeException('DB error'));

        $result = $this->adapter->stageEntity($dto);

        $this->assertInstanceOf(StagingExistsResult::class, $result);
        $this->assertFalse($result->getExists());
        $this->assertEquals('error', $result->getStatus());
        $this->assertEquals('DB error', $result->getMessage());
    }

    public function testStagePaymentSuccess(): void
    {
        $dto = new StagingPayment(
            'square',
            'sq_pay_123',
            75.00,
            'USD',
            'completed',
            'card',
            'sq_txn_123',
            'sq_inv_123'
        );

        $stagingPayment = $this->createMock(
            \ksfraser\FrontAccounting\ImportStaging\Models\StagingPayment::class
        );
        $stagingPayment->method('getId')->willReturn(99);
        $stagingPayment->method('getStatus')->willReturn('staged');

        $this->stagingService->expects($this->once())
            ->method('stageOrUpdatePayment')
            ->willReturn($stagingPayment);

        $result = $this->adapter->stageEntity($dto);

        $this->assertTrue($result->getExists());
        $this->assertEquals(99, $result->getStagingId());
    }

    public function testStageCustomerSuccess(): void
    {
        $dto = new StagingCustomerDto(
            'woo',
            'woo_cust_456',
            'jane@example.com',
            '555-6789',
            'Jane',
            'Doe',
            'Acme Inc'
        );

        $stagingCustomer = $this->createMock(StagingCustomer::class);
        $stagingCustomer->method('getId')->willReturn(55);
        $stagingCustomer->method('getStatus')->willReturn('staged');

        $this->stagingService->expects($this->once())
            ->method('stageOrUpdateCustomer')
            ->willReturn($stagingCustomer);

        $result = $this->adapter->stageEntity($dto);

        $this->assertTrue($result->getExists());
        $this->assertEquals(55, $result->getStagingId());
    }

    public function testStageUnsupportedDtoThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported DTO type');

        // Use a concrete subclass that isn't handled
        $dto = new \Ksfraser\StagingDto\StagingTax('square', 'sq_tax_1', 'VAT', 0.20);
        $this->adapter->stageEntity($dto);
    }

    public function testStagingExistsTransactionFound(): void
    {
        $query = new StagingExistsQuery('square', 'sq_txn_123', 'transaction');

        $stagingTxn = $this->createMock(StagingTransaction::class);
        $stagingTxn->method('getId')->willReturn(42);
        $stagingTxn->method('getStatus')->willReturn('staged');

        $this->transactionDAO->expects($this->once())
            ->method('findBySource')
            ->with('square', 'sq_txn_123')
            ->willReturn($stagingTxn);

        $result = $this->adapter->stagingExists($query);

        $this->assertTrue($result->getExists());
        $this->assertEquals(42, $result->getStagingId());
        $this->assertEquals('staged', $result->getStatus());
    }

    public function testStagingExistsTransactionNotFound(): void
    {
        $query = new StagingExistsQuery('square', 'sq_txn_999', 'transaction');

        $this->transactionDAO->expects($this->once())
            ->method('findBySource')
            ->willReturn(null);

        $result = $this->adapter->stagingExists($query);

        $this->assertFalse($result->getExists());
        $this->assertEquals(0, $result->getStagingId());
    }

    public function testStagingExistsCustomerFound(): void
    {
        $query = new StagingExistsQuery('woo', 'woo_cust_123', 'customer');

        $stagingCustomer = $this->createMock(StagingCustomer::class);
        $stagingCustomer->method('getId')->willReturn(10);
        $stagingCustomer->method('getStatus')->willReturn('staged');

        $this->customerDAO->expects($this->once())
            ->method('findBySource')
            ->with('woo', 'woo_cust_123')
            ->willReturn($stagingCustomer);

        $result = $this->adapter->stagingExists($query);

        $this->assertTrue($result->getExists());
        $this->assertEquals(10, $result->getStagingId());
    }

    public function testStagingExistsPaymentFound(): void
    {
        $query = new StagingExistsQuery('square', 'sq_pay_123', 'payment');

        $stagingPayment = $this->createMock(
            \ksfraser\FrontAccounting\ImportStaging\Models\StagingPayment::class
        );
        $stagingPayment->method('getId')->willReturn(77);
        $stagingPayment->method('getStatus')->willReturn('staged');

        $this->paymentDAO->expects($this->once())
            ->method('findBySource')
            ->with('square', 'sq_pay_123')
            ->willReturn($stagingPayment);

        $result = $this->adapter->stagingExists($query);

        $this->assertTrue($result->getExists());
        $this->assertEquals(77, $result->getStagingId());
    }

    public function testStagingExistsUnknownEntityType(): void
    {
        $query = new StagingExistsQuery('square', 'sq_123', 'unknown_type');

        $result = $this->adapter->stagingExists($query);

        $this->assertFalse($result->getExists());
        $this->assertStringContainsString('Unknown entity type', $result->getMessage());
    }
}

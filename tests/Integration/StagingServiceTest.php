<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Services\StagingService;
use ksfraser\FrontAccounting\ImportStaging\Services\MatchingService;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentMatchDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingLineItemDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingLogDAO;
use ksfraser\FrontAccounting\ImportStaging\Validators\TransactionValidator;
use ksfraser\FrontAccounting\ImportStaging\Validators\CustomerValidator;
use ksfraser\FrontAccounting\ImportStaging\Validators\PaymentValidator;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\DuplicateTransactionException;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\InvalidSourceException;

class StagingServiceTest extends TestCase
{
    private StagingService $service;
    private StagingTransactionDAO $transactionDAO;
    private StagingLogDAO $logDAO;

    protected function setUp(): void
    {
        $tablePrefix = '0_test_';
        $db = $this->createMock(\ksf_ModulesDAO::class);
        $stmt = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['fetch_all'])
            ->getMock();
        $stmt->method('fetch_all')->willReturn([]);
        $db->method('query')->willReturn($stmt);
        $this->transactionDAO = new StagingTransactionDAO($tablePrefix, $db);
        $customerDAO = new StagingCustomerDAO($tablePrefix, $db);
        $paymentDAO = new StagingPaymentDAO($tablePrefix, $db);
        $paymentMatchDAO = new StagingPaymentMatchDAO($tablePrefix, $db);
        $lineItemDAO = new StagingLineItemDAO($tablePrefix, $db);
        $this->logDAO = new StagingLogDAO($tablePrefix, $db);
        $txnValidator = new TransactionValidator();
        $custValidator = new CustomerValidator();
        $paymentValidator = new PaymentValidator();
        $matchingService = new MatchingService();
        $this->service = new StagingService(
            $customerDAO, $this->transactionDAO, $paymentDAO, $paymentMatchDAO,
            $lineItemDAO, $this->logDAO, $txnValidator, $custValidator, $paymentValidator,
            $matchingService
        );
    }

    public function testInvalidSourceThrowsException(): void
    {
        $this->expectException(InvalidSourceException::class);
        $this->service->stageTransaction(['total_amount' => 100], 'invalid_source');
    }

    public function testEmptySourceThrowsException(): void
    {
        $this->expectException(InvalidSourceException::class);
        $this->service->stageTransaction(['total_amount' => 100], '');
    }

    public function testProcessQueueWithNoRecordsReturnsNoOp(): void
    {
        $result = $this->service->processQueue();
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('no_records', $result->getAction());
    }
}

<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\ImportStaging\Services\StagingService;
use Ksfraser\ImportStaging\Services\MatchingService;
use Ksfraser\ImportStaging\DAO\StagingCustomerDAO;
use Ksfraser\ImportStaging\DAO\StagingTransactionDAO;
use Ksfraser\ImportStaging\DAO\StagingLogDAO;
use Ksfraser\ImportStaging\Validators\TransactionValidator;
use Ksfraser\ImportStaging\Validators\CustomerValidator;
use Ksfraser\ImportStaging\Exceptions\DuplicateTransactionException;
use Ksfraser\ImportStaging\Exceptions\InvalidSourceException;

class StagingServiceTest extends TestCase
{
    private StagingService $service;
    private StagingTransactionDAO $transactionDAO;
    private StagingLogDAO $logDAO;

    protected function setUp(): void
    {
        $tablePrefix = '0_test_';
        $db = $this->createMock(\ksf_ModulesDAO::class);
        $this->transactionDAO = new StagingTransactionDAO($tablePrefix, $db);
        $customerDAO = new StagingCustomerDAO($tablePrefix, $db);
        $this->logDAO = new StagingLogDAO($tablePrefix, $db);
        $txnValidator = new TransactionValidator();
        $custValidator = new CustomerValidator();
        $matchingService = new MatchingService();
        $this->service = new StagingService(
            $customerDAO, $this->transactionDAO, $this->logDAO,
            $txnValidator, $custValidator, $matchingService
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

<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingNotFoundException;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\InvalidSourceException;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\DuplicateTransactionException;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\MappingException;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\ProcessingException;

class StagingExceptionTest extends TestCase
{
    public function testStagingExceptionNotFound(): void
    {
        $e = StagingException::notFound(42);
        $this->assertStringContainsString('42', $e->getMessage());
    }

    public function testStagingExceptionInvalidSource(): void
    {
        $e = StagingException::invalidSource('bad_source');
        $this->assertStringContainsString('bad_source', $e->getMessage());
    }

    public function testStagingExceptionDuplicateTransaction(): void
    {
        $e = StagingException::duplicateTransaction('txn_001');
        $this->assertStringContainsString('txn_001', $e->getMessage());
    }

    public function testStagingExceptionValidationFailed(): void
    {
        $e = StagingException::validationFailed(['Field x required', 'Field y required']);
        $this->assertStringContainsString('Field x required', $e->getMessage());
    }

    public function testStagingNotFoundExceptionById(): void
    {
        $e = StagingNotFoundException::byId(42, 'transaction');
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertInstanceOf(StagingException::class, $e);
    }

    public function testInvalidSourceException(): void
    {
        $e = InvalidSourceException::unknownSource('bad_source');
        $this->assertStringContainsString('bad_source', $e->getMessage());
        $this->assertInstanceOf(StagingException::class, $e);
    }

    public function testDuplicateTransactionException(): void
    {
        $e = DuplicateTransactionException::forSource('woocommerce', 'order_123');
        $this->assertStringContainsString('order_123', $e->getMessage());
        $this->assertInstanceOf(StagingException::class, $e);
    }

    public function testMappingException(): void
    {
        $e = MappingException::fieldNotFound('square_api', 'total');
        $this->assertStringContainsString('total', $e->getMessage());
        $this->assertInstanceOf(StagingException::class, $e);
    }

    public function testProcessingException(): void
    {
        $e = ProcessingException::stageFailed(42, 'Invalid data');
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertInstanceOf(StagingException::class, $e);
    }

    public function testExceptionHierarchy(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new StagingException('test'));
        $this->assertInstanceOf(\RuntimeException::class, new InvalidSourceException('test'));
        $this->assertInstanceOf(StagingException::class, new InvalidSourceException('test'));
    }
}

<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ProcessingResult;

class ProcessingResultTest extends TestCase
{
    public function testCanCreateSuccess(): void
    {
        $result = ProcessingResult::success(42, 'staged', 1001);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(42, $result->getRecordId());
        $this->assertEquals('staged', $result->getAction());
        $this->assertEquals(1001, $result->getFaReferenceNo());
    }

    public function testCanCreateFailure(): void
    {
        $result = ProcessingResult::failure(42, 'process', ['Error occurred']);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(['Error occurred'], $result->getErrors());
    }

    public function testSuccessHasNoErrors(): void
    {
        $result = ProcessingResult::success(1, 'test');
        $this->assertEmpty($result->getErrors());
    }

    public function testFailureHasNoFaReference(): void
    {
        $result = ProcessingResult::failure(1, 'test', ['error']);
        $this->assertNull($result->getFaReferenceNo());
    }

    public function testProcessedAtDefaultsToNow(): void
    {
        $result = new ProcessingResult(true, 1, 'test');
        $this->assertInstanceOf(\DateTimeInterface::class, $result->getProcessedAt());
    }

    public function testCanStoreMetadata(): void
    {
        $result = new ProcessingResult(true, 1, 'test', null, [], null, null, ['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $result->getMetadata());
    }
}

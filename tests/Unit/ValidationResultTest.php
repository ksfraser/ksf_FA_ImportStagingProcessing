<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationResult;

class ValidationResultTest extends TestCase
{
    public function testValidResult(): void
    {
        $result = ValidationResult::valid();
        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->getErrors());
        $this->assertFalse($result->hasErrors());
    }

    public function testInvalidResultWithErrors(): void
    {
        $result = ValidationResult::invalid(['Field is required']);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(['Field is required'], $result->getErrors());
        $this->assertTrue($result->hasErrors());
    }

    public function testInvalidResultWithWarnings(): void
    {
        $result = ValidationResult::invalid(['Field is required'], ['Email not valid']);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(['Email not valid'], $result->getWarnings());
        $this->assertTrue($result->hasWarnings());
    }

    public function testValidResultHasNoWarnings(): void
    {
        $result = ValidationResult::valid();
        $this->assertEmpty($result->getWarnings());
        $this->assertFalse($result->hasWarnings());
    }
}

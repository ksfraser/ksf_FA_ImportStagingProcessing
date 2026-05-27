<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Integration;

use PHPUnit\Framework\TestCase;

class HooksTest extends TestCase
{
    private \hooks_ksf_FA_ImportStagingProcessing $hooks;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/hooks.php';
        $this->hooks = new \hooks_ksf_FA_ImportStagingProcessing();
    }

    public function testGetModuleConstants(): void
    {
        $data = [];
        $result = $this->hooks->getModuleConstants($data);
        $this->assertArrayHasKey('KSF_IMPORT_STAGING_MODULE_NAME', $result);
        $this->assertEquals('ksf_FA_ImportStagingProcessing', $result['KSF_IMPORT_STAGING_MODULE_NAME']);
        $this->assertArrayHasKey('KSF_IMPORT_STAGING_CAPABILITIES', $result);
    }

    public function testGetModuleCapabilities(): void
    {
        $data = [];
        $result = $this->hooks->getModuleCapabilities($data);
        $this->assertArrayHasKey('staging', $result);
        $this->assertArrayHasKey('matching', $result);
        $this->assertArrayHasKey('mapping', $result);
        $this->assertArrayHasKey('audit', $result);
        $this->assertEquals('Stage customers, transactions, and payments from any source', $result['staging']['description']);
    }

    public function testHasCapabilityStaging(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'staging']);
        $this->assertTrue($result);
        $this->assertTrue($data['has_capability']);
    }

    public function testHasCapabilityUnknown(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'nonexistent']);
        $this->assertFalse($result);
    }

    public function testHasCapabilityNoCapabilityReturnsFalse(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data);
        $this->assertFalse($result);
        $this->assertArrayHasKey('error', $data);
    }

    public function testRespondToCapabilityRequestCapabilities(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'capabilities']);
        $this->assertArrayHasKey('staging', $result);
    }

    public function testRespondToCapabilityRequestConstants(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'constants']);
        $this->assertArrayHasKey('KSF_IMPORT_STAGING_MODULE_NAME', $result);
    }

    public function testRespondToCapabilityRequestHasCapability(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'has:staging']);
        $this->assertTrue($result);
    }

    public function testRespondToCapabilityRequestUnknownReturnsNull(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'unknown']);
        $this->assertNull($result);
        $this->assertArrayHasKey('error', $data);
    }
}

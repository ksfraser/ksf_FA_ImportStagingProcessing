<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingMapping;

class StagingMappingTest extends TestCase
{
    public function testCanCreateMapping(): void
    {
        $mapping = new StagingMapping('woocommerce', 'billing_email', 'email');
        $this->assertEquals('woocommerce', $mapping->getSource());
        $this->assertEquals('billing_email', $mapping->getSourceField());
        $this->assertEquals('email', $mapping->getTargetField());
    }

    public function testDefaultsAreCorrect(): void
    {
        $mapping = new StagingMapping('square_api', 'square_name', 'name');
        $this->assertEquals('none', $mapping->getTransform());
        $this->assertFalse($mapping->isRequired());
        $this->assertNull($mapping->getDefaultValue());
    }

    public function testCanSetTransformAndRequired(): void
    {
        $mapping = new StagingMapping('paypal', 'payer_name', 'name');
        $mapping->setTransform('normalize');
        $mapping->setIsRequired(true);
        $mapping->setDefaultValue('Unknown');
        $this->assertEquals('normalize', $mapping->getTransform());
        $this->assertTrue($mapping->isRequired());
        $this->assertEquals('Unknown', $mapping->getDefaultValue());
    }

    public function testCanConvertToArray(): void
    {
        $mapping = new StagingMapping('square_csv', 'Item', 'name');
        $mapping->setTransform('map');
        $mapping->setIsRequired(true);
        $array = $mapping->toArray();
        $this->assertEquals('square_csv', $array['source']);
        $this->assertEquals('Item', $array['source_field']);
        $this->assertEquals('name', $array['target_field']);
        $this->assertEquals('map', $array['transform']);
        $this->assertTrue($array['is_required']);
    }

    public function testCanCreateFromArray(): void
    {
        $mapping = StagingMapping::fromArray([
            'source' => 'woocommerce',
            'source_field' => 'billing_phone',
            'target_field' => 'phone',
            'transform' => 'normalize',
            'default_value' => '000-000-0000',
            'is_required' => false,
        ]);
        $this->assertEquals('woocommerce', $mapping->getSource());
        $this->assertEquals('billing_phone', $mapping->getSourceField());
        $this->assertEquals('phone', $mapping->getTargetField());
        $this->assertEquals('normalize', $mapping->getTransform());
        $this->assertEquals('000-000-0000', $mapping->getDefaultValue());
        $this->assertFalse($mapping->isRequired());
    }
}

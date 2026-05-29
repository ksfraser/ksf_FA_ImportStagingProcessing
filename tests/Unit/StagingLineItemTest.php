<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\ImportStaging\Models\StagingLineItem;

class StagingLineItemTest extends TestCase
{
    public function testCanCreateWithDefaults(): void
    {
        $item = new StagingLineItem();
        $this->assertEquals(1, $item->getQuantity());
        $this->assertEquals(0.0, $item->getUnitPrice());
        $this->assertEquals(0.0, $item->getTotalAmount());
        $this->assertEquals(0.0, $item->getTaxAmount());
        $this->assertEquals('CAD', $item->getCurrency());
        $this->assertEquals('staged', $item->getStatus());
        $this->assertEquals([], $item->getAttributes());
    }

    public function testCanSetAndGetProperties(): void
    {
        $item = new StagingLineItem();
        $item->setStagingTransactionId(10);
        $item->setSource('woocommerce');
        $item->setLineNumber(1);
        $item->setSku('WIDGET-001');
        $item->setName('Super Widget');
        $item->setDescription('A fine widget');
        $item->setItemType('product');
        $item->setQuantity(2.0);
        $item->setUnitPrice(29.99);
        $item->setTaxAmount(7.80);
        $item->setTaxPercent(13.0);
        $item->setDiscountAmount(5.0);
        $item->setDiscountPercent(10.0);
        $item->setTotalAmount(59.98);
        $item->setCurrency('CAD');

        $this->assertEquals(10, $item->getStagingTransactionId());
        $this->assertEquals('woocommerce', $item->getSource());
        $this->assertEquals(1, $item->getLineNumber());
        $this->assertEquals('WIDGET-001', $item->getSku());
        $this->assertEquals('Super Widget', $item->getName());
        $this->assertEquals('A fine widget', $item->getDescription());
        $this->assertEquals('product', $item->getItemType());
        $this->assertEquals(2.0, $item->getQuantity());
        $this->assertEquals(29.99, $item->getUnitPrice());
        $this->assertEquals(7.80, $item->getTaxAmount());
        $this->assertEquals(13.0, $item->getTaxPercent());
        $this->assertEquals(5.0, $item->getDiscountAmount());
        $this->assertEquals(10.0, $item->getDiscountPercent());
        $this->assertEquals(59.98, $item->getTotalAmount());
        $this->assertEquals('CAD', $item->getCurrency());
    }

    public function testCanSetSourceVersioningFields(): void
    {
        $item = new StagingLineItem();
        $item->setSourceId('li_abc123');
        $item->setSourceUpdatedAt(new \DateTimeImmutable('2026-05-26 10:30:00'));

        $this->assertEquals('li_abc123', $item->getSourceId());
        $this->assertEquals('2026-05-26 10:30:00', $item->getSourceUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function testCanConvertToArray(): void
    {
        $item = new StagingLineItem();
        $item->setStagingTransactionId(5);
        $item->setSource('square_api');
        $item->setLineNumber(2);
        $item->setSku('ITEM-002');
        $item->setName('Square Item');
        $item->setQuantity(1.0);
        $item->setUnitPrice(100.00);
        $item->setTotalAmount(100.00);

        $array = $item->toArray();
        $this->assertEquals(5, $array['staging_transaction_id']);
        $this->assertEquals('square_api', $array['source']);
        $this->assertEquals('ITEM-002', $array['sku']);
        $this->assertEquals('Square Item', $array['name']);
        $this->assertEquals(1.0, $array['quantity']);
        $this->assertEquals(100.00, $array['unit_price']);
        $this->assertEquals(100.00, $array['total_amount']);
        $this->assertEquals('staged', $array['status']);
    }

    public function testCanCreateFromArray(): void
    {
        $item = StagingLineItem::fromArray([
            'staging_transaction_id' => 5,
            'source' => 'square_csv',
            'source_id' => 'csv_li_001',
            'source_updated_at' => '2026-05-26 12:00:00',
            'line_number' => 1,
            'sku' => 'CSV-001',
            'name' => 'CSV Item',
            'description' => 'Imported from CSV',
            'item_type' => 'product',
            'quantity' => 3,
            'unit_price' => 15.00,
            'tax_amount' => 5.85,
            'tax_percent' => 13.0,
            'discount_amount' => 3.00,
            'discount_percent' => 5.0,
            'total_amount' => 45.00,
            'currency' => 'CAD',
            'status' => 'approved',
            'attributes' => ['variation_id' => 'var_999', 'barcode' => '490123456789'],
        ]);

        $this->assertEquals(5, $item->getStagingTransactionId());
        $this->assertEquals('square_csv', $item->getSource());
        $this->assertEquals('csv_li_001', $item->getSourceId());
        $this->assertEquals('2026-05-26 12:00:00', $item->getSourceUpdatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(1, $item->getLineNumber());
        $this->assertEquals('CSV-001', $item->getSku());
        $this->assertEquals('CSV Item', $item->getName());
        $this->assertEquals('Imported from CSV', $item->getDescription());
        $this->assertEquals('product', $item->getItemType());
        $this->assertEquals(3, $item->getQuantity());
        $this->assertEquals(15.00, $item->getUnitPrice());
        $this->assertEquals(5.85, $item->getTaxAmount());
        $this->assertEquals(13.0, $item->getTaxPercent());
        $this->assertEquals(3.00, $item->getDiscountAmount());
        $this->assertEquals(5.0, $item->getDiscountPercent());
        $this->assertEquals(45.00, $item->getTotalAmount());
        $this->assertEquals('CAD', $item->getCurrency());
        $this->assertEquals('approved', $item->getStatus());
        $this->assertEquals('var_999', $item->getAttribute('variation_id'));
        $this->assertEquals('490123456789', $item->getAttribute('barcode'));
    }

    public function testCanManageAttributes(): void
    {
        $item = new StagingLineItem();
        $item->setAttribute('variation_id', 'var_888');
        $item->setAttribute('manufacturer', 'Acme Corp');

        $this->assertEquals('var_888', $item->getAttribute('variation_id'));
        $this->assertEquals('Acme Corp', $item->getAttribute('manufacturer'));

        $item->setAttributes(['color' => 'red', 'size' => 'XL']);
        $this->assertEquals(['color' => 'red', 'size' => 'XL'], $item->getAttributes());
        $this->assertEquals('red', $item->getAttribute('color'));
        $this->assertEquals('XL', $item->getAttribute('size'));
    }

    public function testCanSetStatus(): void
    {
        $item = new StagingLineItem();
        $this->assertEquals('staged', $item->getStatus());
        $item->setStatus('approved');
        $this->assertEquals('approved', $item->getStatus());
        $item->setStatus('processed');
        $this->assertEquals('processed', $item->getStatus());
    }

    public function testCanSetId(): void
    {
        $item = new StagingLineItem();
        $this->assertNull($item->getId());
        $item->setId(42);
        $this->assertEquals(42, $item->getId());
    }

    public function testFromArrayHandlesAlternateFieldNames(): void
    {
        $item = StagingLineItem::fromArray([
            'staging_transaction_id' => 1,
            'source' => 'square_api',
            'line_number' => 1,
            'stock_id' => 'ALT-SKU',
            'item_name' => 'Alt Name',
            'unitPrice' => 10.0,
            'taxAmount' => 1.30,
            'taxPercent' => 13.0,
            'totalAmount' => 10.0,
            'discountAmount' => 0.0,
            'discountPercent' => 0.0,
        ]);

        $this->assertEquals('ALT-SKU', $item->getSku());
        $this->assertEquals('Alt Name', $item->getName());
        $this->assertEquals(10.0, $item->getUnitPrice());
        $this->assertEquals(1.30, $item->getTaxAmount());
        $this->assertEquals(13.0, $item->getTaxPercent());
        $this->assertEquals(10.0, $item->getTotalAmount());
    }
}

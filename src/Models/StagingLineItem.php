<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Models;

/**
 * Represents a staged line item belonging to a staging_transaction.
 *
 * Each line item carries source, source_id, and source_updated_at for
 * versioning/upsert. Source-specific fields beyond the core schema
 * are stored in staging_line_item_attributes (name-value pairs).
 *
 * @requirement FR-06 Line Item Staging
 * @UML Note: Part of staging model in ProjectDocs/UML.md
 */
class StagingLineItem
{
    private ?int $id;
    private int $stagingTransactionId;
    private string $source;
    private ?string $sourceId;
    private ?\DateTimeInterface $sourceUpdatedAt;
    private int $lineNumber;
    private ?string $sku;
    private string $name;
    private ?string $description;
    private ?string $itemType;
    private float $quantity;
    private float $unitPrice;
    private float $taxAmount;
    private float $taxPercent;
    private float $discountAmount;
    private float $discountPercent;
    private float $totalAmount;
    private string $currency;
    private string $status;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    /** @var array Key-value pairs for source-specific extra fields */
    private array $attributes = [];

    public function __construct()
    {
        $this->id = null;
        $this->sourceId = null;
        $this->sourceUpdatedAt = null;
        $this->sku = null;
        $this->description = null;
        $this->itemType = null;
        $this->quantity = 1;
        $this->unitPrice = 0.0;
        $this->taxAmount = 0.0;
        $this->taxPercent = 0.0;
        $this->discountAmount = 0.0;
        $this->discountPercent = 0.0;
        $this->totalAmount = 0.0;
        $this->currency = 'CAD';
        $this->status = 'staged';
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    public static function fromArray(array $data): self
    {
        $item = new self();
        $item->id = isset($data['id']) ? (int)$data['id'] : null;
        $item->stagingTransactionId = (int)($data['staging_transaction_id'] ?? 0);
        $item->source = (string)($data['source'] ?? '');
        $item->sourceId = $data['source_id'] ?? null;
        $item->lineNumber = (int)($data['line_number'] ?? 0);
        $item->sku = $data['sku'] ?? $data['stock_id'] ?? null;
        $item->name = (string)($data['name'] ?? $data['item_name'] ?? '');
        $item->description = $data['description'] ?? null;
        $item->itemType = $data['item_type'] ?? null;
        $item->quantity = (float)($data['quantity'] ?? 1);
        $item->unitPrice = (float)($data['unit_price'] ?? $data['unitPrice'] ?? 0.0);
        $item->taxAmount = (float)($data['tax_amount'] ?? $data['taxAmount'] ?? 0.0);
        $item->taxPercent = (float)($data['tax_percent'] ?? $data['taxPercent'] ?? 0.0);
        $item->discountAmount = (float)($data['discount_amount'] ?? $data['discountAmount'] ?? 0.0);
        $item->discountPercent = (float)($data['discount_percent'] ?? $data['discountPercent'] ?? 0.0);
        $item->totalAmount = (float)($data['total_amount'] ?? $data['totalAmount'] ?? 0.0);
        $item->currency = (string)($data['currency'] ?? 'CAD');
        $item->status = (string)($data['status'] ?? 'staged');

        if (isset($data['source_updated_at'])) {
            $item->sourceUpdatedAt = $data['source_updated_at'] instanceof \DateTimeInterface
                ? $data['source_updated_at']
                : new \DateTimeImmutable($data['source_updated_at']);
        }
        if (isset($data['created_at'])) {
            $item->createdAt = $data['created_at'] instanceof \DateTimeInterface
                ? $data['created_at']
                : new \DateTimeImmutable($data['created_at']);
        }
        if (isset($data['updated_at'])) {
            $item->updatedAt = $data['updated_at'] instanceof \DateTimeInterface
                ? $data['updated_at']
                : new \DateTimeImmutable($data['updated_at']);
        }

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            $item->attributes = $data['attributes'];
        }

        return $item;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'staging_transaction_id' => $this->stagingTransactionId,
            'source' => $this->source,
            'source_id' => $this->sourceId,
            'source_updated_at' => $this->sourceUpdatedAt ? $this->sourceUpdatedAt->format('Y-m-d H:i:s') : null,
            'line_number' => $this->lineNumber,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'item_type' => $this->itemType,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'tax_amount' => $this->taxAmount,
            'tax_percent' => $this->taxPercent,
            'discount_amount' => $this->discountAmount,
            'discount_percent' => $this->discountPercent,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
            'attributes' => $this->attributes,
        ];
    }

    // Getters

    public function getId(): ?int { return $this->id; }
    public function getStagingTransactionId(): int { return $this->stagingTransactionId; }
    public function getSource(): string { return $this->source; }
    public function getSourceId(): ?string { return $this->sourceId; }
    public function getSourceUpdatedAt(): ?\DateTimeInterface { return $this->sourceUpdatedAt; }
    public function getLineNumber(): int { return $this->lineNumber; }
    public function getSku(): ?string { return $this->sku; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getItemType(): ?string { return $this->itemType; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getTaxPercent(): float { return $this->taxPercent; }
    public function getDiscountAmount(): float { return $this->discountAmount; }
    public function getDiscountPercent(): float { return $this->discountPercent; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function getAttributes(): array { return $this->attributes; }
    public function getAttribute(string $key): ?string { return $this->attributes[$key] ?? null; }

    // Setters

    public function setId(int $id): void { $this->id = $id; }
    public function setStagingTransactionId(int $id): void { $this->stagingTransactionId = $id; }
    public function setSource(string $source): void { $this->source = $source; }
    public function setSourceId(?string $id): void { $this->sourceId = $id; }
    public function setSourceUpdatedAt(?\DateTimeInterface $dt): void { $this->sourceUpdatedAt = $dt; }
    public function setLineNumber(int $num): void { $this->lineNumber = $num; }
    public function setSku(?string $sku): void { $this->sku = $sku; }
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(?string $desc): void { $this->description = $desc; }
    public function setItemType(?string $type): void { $this->itemType = $type; }
    public function setQuantity(float $qty): void { $this->quantity = $qty; }
    public function setUnitPrice(float $price): void { $this->unitPrice = $price; }
    public function setTaxAmount(float $amount): void { $this->taxAmount = $amount; }
    public function setTaxPercent(float $pct): void { $this->taxPercent = $pct; }
    public function setDiscountAmount(float $amount): void { $this->discountAmount = $amount; }
    public function setDiscountPercent(float $pct): void { $this->discountPercent = $pct; }
    public function setTotalAmount(float $amount): void { $this->totalAmount = $amount; }
    public function setCurrency(string $currency): void { $this->currency = $currency; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setCreatedAt(?\DateTimeInterface $dt): void { $this->createdAt = $dt; }
    public function setUpdatedAt(?\DateTimeInterface $dt): void { $this->updatedAt = $dt; }
    public function setAttributes(array $attrs): void { $this->attributes = $attrs; }
    public function setAttribute(string $key, string $value): void { $this->attributes[$key] = $value; }
}

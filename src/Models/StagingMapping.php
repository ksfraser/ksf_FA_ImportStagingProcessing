<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Models;

class StagingMapping
{
    private ?int $id;
    private string $source;
    private string $sourceField;
    private string $targetField;
    private string $transform;
    private ?string $defaultValue;
    private bool $isRequired;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    public function __construct(string $source, string $sourceField, string $targetField)
    {
        $this->source = $source;
        $this->sourceField = $sourceField;
        $this->targetField = $targetField;
        $this->id = null;
        $this->transform = 'none';
        $this->isRequired = false;
        $this->defaultValue = null;
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getSource(): string { return $this->source; }
    public function getSourceField(): string { return $this->sourceField; }
    public function getTargetField(): string { return $this->targetField; }

    public function getTransform(): string { return $this->transform; }
    public function setTransform(string $transform): void { $this->transform = $transform; }

    public function getDefaultValue(): ?string { return $this->defaultValue; }
    public function setDefaultValue(?string $value): void { $this->defaultValue = $value; }

    public function isRequired(): bool { return $this->isRequired; }
    public function setIsRequired(bool $required): void { $this->isRequired = $required; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_field' => $this->sourceField,
            'target_field' => $this->targetField,
            'transform' => $this->transform,
            'default_value' => $this->defaultValue,
            'is_required' => $this->isRequired,
        ];
    }

    public static function fromArray(array $data): self
    {
        $mapping = new self(
            $data['source'],
            $data['source_field'],
            $data['target_field']
        );
        if (isset($data['id'])) $mapping->setId((int)$data['id']);
        if (isset($data['transform'])) $mapping->setTransform($data['transform']);
        if (isset($data['default_value'])) $mapping->setDefaultValue($data['default_value']);
        if (isset($data['is_required'])) $mapping->setIsRequired((bool)$data['is_required']);
        return $mapping;
    }
}

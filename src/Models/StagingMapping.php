<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Models;

/**
 * Data model for a field mapping configuration per source.
 *
 * Maps a source field to a target FA field with an optional transform and
 * default value. Used by the staging pipeline to normalize inbound data.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-006-001 staging_mapping config table
 * @BABOK Related: FR-ISU-006-002 field mapping per source
 * @BABOK Related: NFR-ISU-003 TB_PREF convention
 */
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

    /**
     * @param string $source
     * @param string $sourceField
     * @param string $targetField
     * @since 2.0.0
     * @BABOK Related: FR-ISU-006-002 mapping creation
     */
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

    /**
     * @return array<string, mixed>
     * @since 2.0.0
     * @BABOK Related: FR-ISU-006-001 mapping serialization
     */
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

    /**
     * @param array $data
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-006-001 mapping hydration
     */
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

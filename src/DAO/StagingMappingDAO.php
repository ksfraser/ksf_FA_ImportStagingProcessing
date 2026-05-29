<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DAO;

use Ksfraser\ImportStaging\Models\StagingMapping;

class StagingMappingDAO
{
    private string $tablePrefix;
    private string $tableName;
    private \ksf_ModulesDAO $db;

    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tablePrefix = $tablePrefix;
        $this->tableName = $tablePrefix . 'staging_mapping';
        $this->db = $db;
    }

    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            source VARCHAR(32) NOT NULL,
            source_field VARCHAR(128) NOT NULL,
            target_field VARCHAR(128) NOT NULL,
            transform VARCHAR(32) DEFAULT 'none',
            default_value VARCHAR(255) DEFAULT NULL,
            is_required TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source (source),
            UNIQUE KEY idx_source_mapping (source, source_field)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    public function insert(StagingMapping $mapping): int
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->tableName} (source, source_field, target_field, transform, default_value, is_required)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE target_field = VALUES(target_field), transform = VALUES(transform),
                    default_value = VALUES(default_value), is_required = VALUES(is_required)";
        $this->db->query($sql, [
            $mapping->getSource(),
            $mapping->getSourceField(),
            $mapping->getTargetField(),
            $mapping->getTransform(),
            $mapping->getDefaultValue(),
            $mapping->isRequired() ? 1 : 0,
        ]);
        $id = (int)$this->db->insertId();
        if ($id === 0) {
            $existing = $this->findBySourceField($mapping->getSource(), $mapping->getSourceField());
            $id = $existing ? $existing->getId() : 0;
        }
        $mapping->setId($id);
        return $id;
    }

    public function findBySource(string $source): array
    {
        $this->ensureTableExists();
        $sql = "SELECT * FROM {$this->tableName} WHERE source = ?";
        $rows = $this->db->query($sql, [$source])->fetchAll();
        return array_map(fn($row) => StagingMapping::fromArray($row), $rows);
    }

    public function findBySourceField(string $source, string $sourceField): ?StagingMapping
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE source = ? AND source_field = ?";
        $row = $this->db->query($sql, [$source, $sourceField])->fetchAssoc();
        return $row ? StagingMapping::fromArray($row) : null;
    }

    public function getMappingArray(string $source): array
    {
        $mappings = $this->findBySource($source);
        $map = [];
        foreach ($mappings as $mapping) {
            $map[$mapping->getSourceField()] = [
                'target' => $mapping->getTargetField(),
                'transform' => $mapping->getTransform(),
                'default' => $mapping->getDefaultValue(),
                'required' => $mapping->isRequired(),
            ];
        }
        return $map;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    public function deleteBySource(string $source): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE source = ?";
        $this->db->query($sql, [$source]);
    }
}

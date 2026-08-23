<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\DAO;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingLineItem;
use ksfraser\FrontAccounting\ImportStaging\Contracts\LineItemRepositoryInterface;

/**
 * DAO for staging_line_items table.
 *
 * Core columns are stored in staging_line_items; source-specific extra fields
 * go into staging_line_item_attributes as name-value pairs.
 *
 * @requirement FR-06 Line Item Staging
 * @UML Note: Class diagram in ProjectDocs/UML.md
 */
class StagingLineItemDAO implements LineItemRepositoryInterface
{
    private string $tableName;
    private string $attrTableName;
    private \ksf_ModulesDAO $db;

    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tableName = $tablePrefix . 'staging_line_items';
        $this->attrTableName = $tablePrefix . 'staging_line_item_attributes';
        $this->db = $db;
    }

    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            staging_transaction_id INT(11) NOT NULL,
            source VARCHAR(32) NOT NULL,
            source_id VARCHAR(64) DEFAULT NULL,
            source_updated_at DATETIME DEFAULT NULL,
            line_number INT(11) NOT NULL DEFAULT 0,
            sku VARCHAR(64) DEFAULT NULL,
            name VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT DEFAULT NULL,
            item_type VARCHAR(32) DEFAULT NULL,
            quantity DECIMAL(15,4) NOT NULL DEFAULT 1.0000,
            unit_price DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            tax_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            tax_percent DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
            discount_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            discount_percent DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
            total_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            currency VARCHAR(8) NOT NULL DEFAULT 'CAD',
            status VARCHAR(16) NOT NULL DEFAULT 'staged',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_transaction (staging_transaction_id),
            KEY idx_source (source, source_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $this->db->query($sql);

        $attrSql = "CREATE TABLE IF NOT EXISTS {$this->attrTableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            line_item_id INT(11) NOT NULL,
            attribute_key VARCHAR(64) NOT NULL,
            attribute_value TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_line_item (line_item_id),
            KEY idx_key (attribute_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $this->db->query($attrSql);
    }

    public function insert(StagingLineItem $item): int
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->tableName}
                (staging_transaction_id, source, source_id, source_updated_at,
                 line_number, sku, name, description, item_type,
                 quantity, unit_price, tax_amount, tax_percent,
                 discount_amount, discount_percent, total_amount, currency, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $item->getStagingTransactionId(),
            $item->getSource(),
            $item->getSourceId(),
            $item->getSourceUpdatedAt() ? $item->getSourceUpdatedAt()->format('Y-m-d H:i:s') : null,
            $item->getLineNumber(),
            $item->getSku(),
            $item->getName(),
            $item->getDescription(),
            $item->getItemType(),
            $item->getQuantity(),
            $item->getUnitPrice(),
            $item->getTaxAmount(),
            $item->getTaxPercent(),
            $item->getDiscountAmount(),
            $item->getDiscountPercent(),
            $item->getTotalAmount(),
            $item->getCurrency(),
            $item->getStatus(),
        ]);
        $id = (int)db_insert_id();

        $this->insertAttributes($id, $item->getAttributes());

        return $id;
    }

    public function findByTransactionId(int $transactionId): array
    {
        $sql = "SELECT * FROM {$this->tableName}
                WHERE staging_transaction_id = ?
                ORDER BY line_number ASC";
        $result = $this->db->query($sql, [$transactionId]);
        if ($result === null) {
            return [];
        }
        $rows = $result->fetch_all();
        $items = [];
        foreach ($rows as $row) {
            $item = $this->rowToItem($row);
            $item->setAttributes($this->getAttributes($item->getId()));
            $items[] = $item;
        }
        return $items;
    }

    public function findBySource(string $source, ?string $sourceId = null): array
    {
        $conditions = ["source = ?"];
        $params = [$source];
        if ($sourceId !== null) {
            $conditions[] = "source_id = ?";
            $params[] = $sourceId;
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->tableName} WHERE {$where} ORDER BY line_number ASC";
        $result = $this->db->query($sql, $params);
        if ($result === null) {
            return [];
        }
        $rows = $result->fetch_all();
        $items = [];
        foreach ($rows as $row) {
            $item = $this->rowToItem($row);
            $item->setAttributes($this->getAttributes($item->getId()));
            $items[] = $item;
        }
        return $items;
    }

    public function findByStatus(string $status, ?string $source = null): array
    {
        $conditions = ["status = ?"];
        $params = [$status];
        if ($source !== null) {
            $conditions[] = "source = ?";
            $params[] = $source;
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->tableName} WHERE {$where} ORDER BY id ASC";
        $result = $this->db->query($sql, $params);
        if ($result === null) {
            return [];
        }
        $rows = $result->fetch_all();
        $items = [];
        foreach ($rows as $row) {
            $item = $this->rowToItem($row);
            $item->setAttributes($this->getAttributes($item->getId()));
            $items[] = $item;
        }
        return $items;
    }

    public function updateStatus(int $id, string $status, ?string $error = null): void
    {
        $sql = "UPDATE {$this->tableName}
                SET status = ?
                WHERE id = ?";
        $this->db->query($sql, [$status, $id]);
    }

    public function updateBySource(StagingLineItem $item): void
    {
        $sql = "UPDATE {$this->tableName}
                SET line_number = ?,
                    sku = ?,
                    name = ?,
                    description = ?,
                    item_type = ?,
                    quantity = ?,
                    unit_price = ?,
                    tax_amount = ?,
                    tax_percent = ?,
                    discount_amount = ?,
                    discount_percent = ?,
                    total_amount = ?,
                    currency = ?,
                    updated_at = NOW()
                WHERE source = ?
                  AND source_id = ?";
        $this->db->query($sql, [
            $item->getLineNumber(),
            $item->getSku(),
            $item->getName(),
            $item->getDescription(),
            $item->getItemType(),
            $item->getQuantity(),
            $item->getUnitPrice(),
            $item->getTaxAmount(),
            $item->getTaxPercent(),
            $item->getDiscountAmount(),
            $item->getDiscountPercent(),
            $item->getTotalAmount(),
            $item->getCurrency(),
            $item->getSource(),
            $item->getSourceId(),
        ]);
    }

    public function deleteByTransactionId(int $transactionId): void
    {
        $subSql = "SELECT id FROM {$this->tableName} WHERE staging_transaction_id = ?";
        $result = $this->db->query($subSql, [$transactionId]);
        if ($result !== null) {
            $rows = $result->fetch_all();
            foreach ($rows as $row) {
                $this->deleteAttributes((int)$row['id']);
            }
        }
        $sql = "DELETE FROM {$this->tableName} WHERE staging_transaction_id = ?";
        $this->db->query($sql, [$transactionId]);
    }

    private function insertAttributes(int $lineItemId, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $sql = "INSERT INTO {$this->attrTableName}
                    (line_item_id, attribute_key, attribute_value)
                    VALUES (?, ?, ?)";
            $this->db->query($sql, [$lineItemId, $key, (string)$value]);
        }
    }

    private function getAttributes(int $lineItemId): array
    {
        $sql = "SELECT attribute_key, attribute_value FROM {$this->attrTableName}
                WHERE line_item_id = ?";
        $result = $this->db->query($sql, [$lineItemId]);
        if ($result === null) {
            return [];
        }
        $rows = $result->fetch_all();
        $attrs = [];
        foreach ($rows as $row) {
            $attrs[$row['attribute_key']] = $row['attribute_value'];
        }
        return $attrs;
    }

    private function deleteAttributes(int $lineItemId): void
    {
        $sql = "DELETE FROM {$this->attrTableName} WHERE line_item_id = ?";
        $this->db->query($sql, [$lineItemId]);
    }

    private function rowToItem(array $row): StagingLineItem
    {
        return StagingLineItem::fromArray([
            'id' => $row['id'],
            'staging_transaction_id' => $row['staging_transaction_id'],
            'source' => $row['source'],
            'source_id' => $row['source_id'] ?? null,
            'source_updated_at' => $row['source_updated_at'] ?? null,
            'line_number' => $row['line_number'],
            'sku' => $row['sku'] ?? null,
            'name' => $row['name'] ?? '',
            'description' => $row['description'] ?? null,
            'item_type' => $row['item_type'] ?? null,
            'quantity' => $row['quantity'],
            'unit_price' => $row['unit_price'],
            'tax_amount' => $row['tax_amount'],
            'tax_percent' => $row['tax_percent'],
            'discount_amount' => $row['discount_amount'],
            'discount_percent' => $row['discount_percent'],
            'total_amount' => $row['total_amount'],
            'currency' => $row['currency'],
            'status' => $row['status'],
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ]);
    }
}

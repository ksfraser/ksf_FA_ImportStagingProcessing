<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\DAO;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingCustomer;

class StagingCustomerDAO
{
    private string $tablePrefix;
    private string $tableName;
    private \ksf_ModulesDAO $db;

    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tablePrefix = $tablePrefix;
        $this->tableName = $tablePrefix . 'staging_customers';
        $this->db = $db;
    }

    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            source VARCHAR(32) NOT NULL,
            source_customer_id VARCHAR(64) DEFAULT NULL,
            name VARCHAR(255) DEFAULT NULL,
            email VARCHAR(128) DEFAULT NULL,
            phone VARCHAR(32) DEFAULT NULL,
            address_line1 VARCHAR(128) DEFAULT NULL,
            address_line2 VARCHAR(128) DEFAULT NULL,
            city VARCHAR(64) DEFAULT NULL,
            province VARCHAR(64) DEFAULT NULL,
            postal_code VARCHAR(16) DEFAULT NULL,
            country VARCHAR(64) DEFAULT NULL,
            raw_json LONGTEXT DEFAULT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'staged',
            fa_debtor_no INT(11) DEFAULT NULL,
            error_log TEXT DEFAULT NULL,
            source_updated_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_source_customer (source, source_customer_id),
            KEY idx_status (status),
            KEY idx_fa_debtor (fa_debtor_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    public function insert(StagingCustomer $customer): int
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->tableName} 
            (source, source_customer_id, name, email, phone, address_line1, address_line2, city, province, postal_code, country, raw_json, status, source_updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $customer->getSource(),
            $customer->getSourceCustomerId(),
            $customer->getName(),
            $customer->getEmail(),
            $customer->getPhone(),
            $customer->getAddressLine1(),
            $customer->getAddressLine2(),
            $customer->getCity(),
            $customer->getProvince(),
            $customer->getPostalCode(),
            $customer->getCountry(),
            $customer->getRawJson(),
            $customer->getStatus(),
            $customer->getSourceUpdatedAt() ? $customer->getSourceUpdatedAt()->format('Y-m-d H:i:s') : null,
        ]);
        $id = (int)db_insert_id();
        $customer->setId($id);
        return $id;
    }

    public function findById(int $id): ?StagingCustomer
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $row = $this->db->query($sql, [$id])->fetch_assoc();
        return $row ? StagingCustomer::fromArray($row) : null;
    }

    public function findBySource(string $source, string $sourceCustomerId): ?StagingCustomer
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE source = ? AND source_customer_id = ?";
        $row = $this->db->query($sql, [$source, $sourceCustomerId])->fetch_assoc();
        return $row ? StagingCustomer::fromArray($row) : null;
    }

    public function findByStatus(string $status, ?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT * FROM {$this->tableName} WHERE status = ? AND source = ? ORDER BY created_at ASC";
            $rows = $this->db->query($sql, [$status, $source])->fetch_all();
        } else {
            $sql = "SELECT * FROM {$this->tableName} WHERE status = ? ORDER BY created_at ASC";
            $rows = $this->db->query($sql, [$status])->fetch_all();
        }
        return array_map(fn($row) => StagingCustomer::fromArray($row), $rows);
    }

    public function updateStatus(int $id, string $status, ?string $error = null): void
    {
        if ($error) {
            $sql = "UPDATE {$this->tableName} SET status = ?, error_log = ? WHERE id = ?";
            $this->db->query($sql, [$status, $error, $id]);
        } else {
            $sql = "UPDATE {$this->tableName} SET status = ? WHERE id = ?";
            $this->db->query($sql, [$status, $id]);
        }
    }

    public function updateBySource(StagingCustomer $customer): bool
    {
        $sql = "UPDATE {$this->tableName} SET
            name = ?, email = ?, phone = ?, address_line1 = ?, address_line2 = ?,
            city = ?, province = ?, postal_code = ?, country = ?, raw_json = ?,
            status = ?, fa_debtor_no = ?, error_log = ?, source_updated_at = ?
            WHERE source = ? AND source_customer_id = ?";
        $this->db->query($sql, [
            $customer->getName(),
            $customer->getEmail(),
            $customer->getPhone(),
            $customer->getAddressLine1(),
            $customer->getAddressLine2(),
            $customer->getCity(),
            $customer->getProvince(),
            $customer->getPostalCode(),
            $customer->getCountry(),
            $customer->getRawJson(),
            $customer->getStatus(),
            $customer->getFaDebtorNo(),
            $customer->getErrorLog(),
            $customer->getSourceUpdatedAt() ? $customer->getSourceUpdatedAt()->format('Y-m-d H:i:s') : null,
            $customer->getSource(),
            $customer->getSourceCustomerId(),
        ]);
        return $this->db->affectedRows() > 0;
    }

    public function findByEmail(string $email): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE email = ? ORDER BY created_at DESC";
        $rows = $this->db->query($sql, [$email])->fetch_all();
        return array_map(fn($row) => StagingCustomer::fromArray($row), $rows);
    }

    public function countByStatus(?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT status, COUNT(*) as count FROM {$this->tableName} WHERE source = ? GROUP BY status";
            $rows = $this->db->query($sql, [$source])->fetch_all();
        } else {
            $sql = "SELECT status, COUNT(*) as count FROM {$this->tableName} GROUP BY status";
            $rows = $this->db->query($sql)->fetch_all();
        }
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['count'];
        }
        return $result;
    }
}

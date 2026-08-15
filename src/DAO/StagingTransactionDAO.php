<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\DAO;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingTransaction;

class StagingTransactionDAO
{
    private string $tablePrefix;
    private string $tableName;
    private \ksf_ModulesDAO $db;

    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tablePrefix = $tablePrefix;
        $this->tableName = $tablePrefix . 'staging_transactions';
        $this->db = $db;
    }

    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            source VARCHAR(32) NOT NULL,
            source_transaction_id VARCHAR(64) DEFAULT NULL,
            source_order_id VARCHAR(64) DEFAULT NULL,
            source_payment_id VARCHAR(64) DEFAULT NULL,
            transaction_date DATE DEFAULT NULL,
            total_amount DECIMAL(15,2) DEFAULT 0.00,
            tax_amount DECIMAL(15,2) DEFAULT 0.00,
            tip_amount DECIMAL(15,2) DEFAULT 0.00,
            discount_amount DECIMAL(15,2) DEFAULT 0.00,
            shipping_amount DECIMAL(15,2) DEFAULT 0.00,
            currency VARCHAR(8) DEFAULT 'CAD',
            customer_name VARCHAR(255) DEFAULT NULL,
            customer_email VARCHAR(128) DEFAULT NULL,
            customer_id VARCHAR(64) DEFAULT NULL,
            raw_json LONGTEXT DEFAULT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'staged',
            match_confidence DECIMAL(5,4) DEFAULT NULL,
            fa_invoice_no INT(11) DEFAULT NULL,
            fa_debtor_no INT(11) DEFAULT NULL,
            error_log TEXT DEFAULT NULL,
            source_updated_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_source_transaction (source, source_transaction_id),
            KEY idx_status (status),
            KEY idx_date (transaction_date),
            KEY idx_match_confidence (match_confidence),
            KEY idx_fa_invoice (fa_invoice_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    public function insert(StagingTransaction $transaction): int
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->tableName} 
            (source, source_transaction_id, source_order_id, source_payment_id, transaction_date,
             total_amount, tax_amount, tip_amount, discount_amount, shipping_amount, currency,
             customer_name, customer_email, customer_id, raw_json, status, source_updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $transaction->getSource(),
            $transaction->getSourceTransactionId(),
            $transaction->getSourceOrderId(),
            $transaction->getSourcePaymentId(),
            $transaction->getTransactionDate() ? $transaction->getTransactionDate()->format('Y-m-d') : null,
            $transaction->getTotalAmount(),
            $transaction->getTaxAmount(),
            $transaction->getTipAmount(),
            $transaction->getDiscountAmount(),
            $transaction->getShippingAmount(),
            $transaction->getCurrency(),
            $transaction->getCustomerName(),
            $transaction->getCustomerEmail(),
            $transaction->getCustomerId(),
            $transaction->getRawJson(),
            $transaction->getStatus(),
            $transaction->getSourceUpdatedAt() ? $transaction->getSourceUpdatedAt()->format('Y-m-d H:i:s') : null,
        ]);
        $id = (int)db_insert_id();
        $transaction->setId($id);
        return $id;
    }

    public function findById(int $id): ?StagingTransaction
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $row = $this->db->query($sql, [$id])->fetch_assoc();
        return $row ? StagingTransaction::fromArray($row) : null;
    }

    public function findBySource(string $source, string $sourceTransactionId): ?StagingTransaction
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE source = ? AND source_transaction_id = ?";
        $row = $this->db->query($sql, [$source, $sourceTransactionId])->fetch_assoc();
        return $row ? StagingTransaction::fromArray($row) : null;
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
        return array_map(fn($row) => StagingTransaction::fromArray($row), $rows);
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, ?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT * FROM {$this->tableName} WHERE transaction_date BETWEEN ? AND ? AND source = ? ORDER BY transaction_date ASC";
            $rows = $this->db->query($sql, [$from->format('Y-m-d'), $to->format('Y-m-d'), $source])->fetch_all();
        } else {
            $sql = "SELECT * FROM {$this->tableName} WHERE transaction_date BETWEEN ? AND ? ORDER BY transaction_date ASC";
            $rows = $this->db->query($sql, [$from->format('Y-m-d'), $to->format('Y-m-d')])->fetch_all();
        }
        return array_map(fn($row) => StagingTransaction::fromArray($row), $rows);
    }

    public function updateStatus(int $id, string $status, ?float $confidence = null, ?string $error = null): void
    {
        $fields = ['status = ?'];
        $params = [$status];
        if ($confidence !== null) {
            $fields[] = 'match_confidence = ?';
            $params[] = $confidence;
        }
        if ($error !== null) {
            $fields[] = 'error_log = ?';
            $params[] = $error;
        }
        $params[] = $id;
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }

    public function updateFaReference(int $id, int $invoiceNo, ?int $debtorNo = null): void
    {
        if ($debtorNo !== null) {
            $sql = "UPDATE {$this->tableName} SET fa_invoice_no = ?, fa_debtor_no = ? WHERE id = ?";
            $this->db->query($sql, [$invoiceNo, $debtorNo, $id]);
        } else {
            $sql = "UPDATE {$this->tableName} SET fa_invoice_no = ? WHERE id = ?";
            $this->db->query($sql, [$invoiceNo, $id]);
        }
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

    public function updateBySource(StagingTransaction $transaction): bool
    {
        $sql = "UPDATE {$this->tableName} SET
            source_order_id = ?, source_payment_id = ?, transaction_date = ?,
            total_amount = ?, tax_amount = ?, tip_amount = ?, discount_amount = ?, shipping_amount = ?,
            currency = ?, customer_name = ?, customer_email = ?, customer_id = ?, raw_json = ?,
            status = ?, match_confidence = ?, fa_invoice_no = ?, fa_debtor_no = ?, error_log = ?,
            source_updated_at = ?
            WHERE source = ? AND source_transaction_id = ?";
        $this->db->query($sql, [
            $transaction->getSourceOrderId(),
            $transaction->getSourcePaymentId(),
            $transaction->getTransactionDate() ? $transaction->getTransactionDate()->format('Y-m-d') : null,
            $transaction->getTotalAmount(),
            $transaction->getTaxAmount(),
            $transaction->getTipAmount(),
            $transaction->getDiscountAmount(),
            $transaction->getShippingAmount(),
            $transaction->getCurrency(),
            $transaction->getCustomerName(),
            $transaction->getCustomerEmail(),
            $transaction->getCustomerId(),
            $transaction->getRawJson(),
            $transaction->getStatus(),
            $transaction->getMatchConfidence(),
            $transaction->getFaInvoiceNo(),
            $transaction->getFaDebtorNo(),
            $transaction->getErrorLog(),
            $transaction->getSourceUpdatedAt() ? $transaction->getSourceUpdatedAt()->format('Y-m-d H:i:s') : null,
            $transaction->getSource(),
            $transaction->getSourceTransactionId(),
        ]);
        return $this->db->affectedRows() > 0;
    }

    public function getQueueForProcessing(?string $source = null, int $limit = 100): array
    {
        $conditions = ["status IN ('staged', 'validated')"];
        $params = [];
        if ($source) {
            $conditions[] = 'source = ?';
            $params[] = $source;
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->tableName} WHERE {$where} ORDER BY created_at ASC LIMIT ?";
        $params[] = $limit;
        $rows = $this->db->query($sql, $params)->fetch_all();
        return array_map(fn($row) => StagingTransaction::fromArray($row), $rows);
    }
}

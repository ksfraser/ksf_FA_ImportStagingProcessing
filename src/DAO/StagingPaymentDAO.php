<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DAO;

use Ksfraser\ImportStaging\Models\StagingPayment;

class StagingPaymentDAO
{
    private string $tablePrefix;
    private string $tableName;
    private \ksf_ModulesDAO $db;

    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tablePrefix = $tablePrefix;
        $this->tableName = $tablePrefix . 'staging_payments';
        $this->db = $db;
    }

    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            source VARCHAR(32) NOT NULL,
            source_payment_id VARCHAR(64) DEFAULT NULL,
            source_transaction_id VARCHAR(64) DEFAULT NULL,
            staging_transaction_id INT(11) DEFAULT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(8) DEFAULT 'CAD',
            fee DECIMAL(15,2) DEFAULT 0.00,
            net_amount DECIMAL(15,2) DEFAULT 0.00,
            payment_method VARCHAR(32) DEFAULT NULL,
            payment_date DATE DEFAULT NULL,
            reference VARCHAR(128) DEFAULT NULL,
            card_brand VARCHAR(32) DEFAULT NULL,
            pan_suffix VARCHAR(4) DEFAULT NULL,
            card_entry_method VARCHAR(16) DEFAULT NULL,
            raw_json LONGTEXT DEFAULT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'staged',
            match_confidence DECIMAL(5,4) DEFAULT NULL,
            fa_trans_type INT(11) DEFAULT NULL,
            fa_trans_no INT(11) DEFAULT NULL,
            fa_bank_account VARCHAR(32) DEFAULT NULL,
            error_log TEXT DEFAULT NULL,
            source_updated_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_source_payment (source, source_payment_id),
            KEY idx_status (status),
            KEY idx_payment_date (payment_date),
            KEY idx_payment_method (payment_method),
            KEY idx_staging_transaction (staging_transaction_id),
            KEY idx_match_confidence (match_confidence),
            KEY idx_fa_reference (fa_trans_type, fa_trans_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    public function insert(StagingPayment $payment): int
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->tableName}
            (source, source_payment_id, source_transaction_id, staging_transaction_id,
             amount, currency, fee, net_amount,
             payment_method, payment_date, reference,
             card_brand, pan_suffix, card_entry_method, raw_json, status, source_updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $payment->getSource(),
            $payment->getSourcePaymentId(),
            $payment->getSourceTransactionId(),
            $payment->getStagingTransactionId(),
            $payment->getAmount(),
            $payment->getCurrency(),
            $payment->getFee(),
            $payment->getNetAmount(),
            $payment->getPaymentMethod(),
            $payment->getPaymentDate() ? $payment->getPaymentDate()->format('Y-m-d') : null,
            $payment->getReference(),
            $payment->getCardBrand(),
            $payment->getPanSuffix(),
            $payment->getCardEntryMethod(),
            $payment->getRawJson(),
            $payment->getStatus(),
            $payment->getSourceUpdatedAt() ? $payment->getSourceUpdatedAt()->format('Y-m-d H:i:s') : null,
        ]);
        $id = (int)$this->db->insertId();
        $payment->setId($id);
        return $id;
    }

    public function findById(int $id): ?StagingPayment
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $row = $this->db->query($sql, [$id])->fetchAssoc();
        return $row ? StagingPayment::fromArray($row) : null;
    }

    public function findBySource(string $source, string $sourcePaymentId): ?StagingPayment
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE source = ? AND source_payment_id = ?";
        $row = $this->db->query($sql, [$source, $sourcePaymentId])->fetchAssoc();
        return $row ? StagingPayment::fromArray($row) : null;
    }

    public function findByStatus(string $status, ?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT * FROM {$this->tableName} WHERE status = ? AND source = ? ORDER BY created_at ASC";
            $rows = $this->db->query($sql, [$status, $source])->fetchAll();
        } else {
            $sql = "SELECT * FROM {$this->tableName} WHERE status = ? ORDER BY created_at ASC";
            $rows = $this->db->query($sql, [$status])->fetchAll();
        }
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, ?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT * FROM {$this->tableName} WHERE payment_date BETWEEN ? AND ? AND source = ? ORDER BY payment_date ASC";
            $rows = $this->db->query($sql, [$from->format('Y-m-d'), $to->format('Y-m-d'), $source])->fetchAll();
        } else {
            $sql = "SELECT * FROM {$this->tableName} WHERE payment_date BETWEEN ? AND ? ORDER BY payment_date ASC";
            $rows = $this->db->query($sql, [$from->format('Y-m-d'), $to->format('Y-m-d')])->fetchAll();
        }
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
    }

    public function findByTransaction(int $stagingTransactionId): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE staging_transaction_id = ? ORDER BY amount DESC";
        $rows = $this->db->query($sql, [$stagingTransactionId])->fetchAll();
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
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

    public function updateFaReference(int $id, int $faTransType, int $faTransNo, ?string $faBankAccount = null): void
    {
        if ($faBankAccount !== null) {
            $sql = "UPDATE {$this->tableName} SET fa_trans_type = ?, fa_trans_no = ?, fa_bank_account = ? WHERE id = ?";
            $this->db->query($sql, [$faTransType, $faTransNo, $faBankAccount, $id]);
        } else {
            $sql = "UPDATE {$this->tableName} SET fa_trans_type = ?, fa_trans_no = ? WHERE id = ?";
            $this->db->query($sql, [$faTransType, $faTransNo, $id]);
        }
    }

    public function countByStatus(?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT status, COUNT(*) as count FROM {$this->tableName} WHERE source = ? GROUP BY status";
            $rows = $this->db->query($sql, [$source])->fetchAll();
        } else {
            $sql = "SELECT status, COUNT(*) as count FROM {$this->tableName} GROUP BY status";
            $rows = $this->db->query($sql)->fetchAll();
        }
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['count'];
        }
        return $result;
    }

    public function updateBySource(StagingPayment $payment): bool
    {
        $sql = "UPDATE {$this->tableName} SET
            source_transaction_id = ?, staging_transaction_id = ?,
            amount = ?, currency = ?, fee = ?, net_amount = ?,
            payment_method = ?, payment_date = ?, reference = ?,
            card_brand = ?, pan_suffix = ?, card_entry_method = ?, raw_json = ?,
            status = ?, match_confidence = ?, fa_trans_type = ?, fa_trans_no = ?,
            fa_bank_account = ?, error_log = ?, source_updated_at = ?
            WHERE source = ? AND source_payment_id = ?";
        $this->db->query($sql, [
            $payment->getSourceTransactionId(),
            $payment->getStagingTransactionId(),
            $payment->getAmount(),
            $payment->getCurrency(),
            $payment->getFee(),
            $payment->getNetAmount(),
            $payment->getPaymentMethod(),
            $payment->getPaymentDate() ? $payment->getPaymentDate()->format('Y-m-d') : null,
            $payment->getReference(),
            $payment->getCardBrand(),
            $payment->getPanSuffix(),
            $payment->getCardEntryMethod(),
            $payment->getRawJson(),
            $payment->getStatus(),
            $payment->getMatchConfidence(),
            $payment->getFaTransType(),
            $payment->getFaTransNo(),
            $payment->getFaBankAccount(),
            $payment->getErrorLog(),
            $payment->getSourceUpdatedAt() ? $payment->getSourceUpdatedAt()->format('Y-m-d H:i:s') : null,
            $payment->getSource(),
            $payment->getSourcePaymentId(),
        ]);
        return $this->db->affectedRows() > 0;
    }

    public function getQueueForReconciliation(?string $source = null, int $limit = 100): array
    {
        $conditions = ["status IN ('staged', 'validated', 'matched')"];
        $params = [];
        if ($source) {
            $conditions[] = 'source = ?';
            $params[] = $source;
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->tableName} WHERE {$where} ORDER BY payment_date ASC LIMIT ?";
        $params[] = $limit;
        $rows = $this->db->query($sql, $params)->fetchAll();
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
    }
}

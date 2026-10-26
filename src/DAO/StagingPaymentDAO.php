<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\DAO;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingPayment;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentRepositoryInterface;

/**
 * DAO for the staging_payments table.
 *
 * Implements PaymentRepositoryInterface. Provides CRUD and query operations
 * for unified payment staging records from all import sources.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-003-001 staging_payments table
 * @BABOK Related: FR-ISU-003-002 tenders, fees, net amounts
 * @BABOK Related: FR-ISU-003-003 net_amount = amount − fee
 * @BABOK Related: FR-ISU-005-002 duplicate detection (findBySource)
 * @BABOK Related: FR-ISU-005-004 idempotent update (updateBySource)
 * @BABOK Related: FR-ISU-013-001 reconciliation input
 * @BABOK Related: FR-ISU-013-002 fa_trans_type / fa_trans_no mapping
 * @BABOK Related: FR-ISU-017-001 0_ company prefix
 * @BABOK Related: NFR-ISU-003 TB_PREF convention
 * @BABOK Related: NFR-ISU-009 Repository pattern
 */
class StagingPaymentDAO implements PaymentRepositoryInterface
{
    private string $tablePrefix;
    private string $tableName;
    private \ksf_ModulesDAO $db;

    /**
     * @param string $tablePrefix
     * @param \ksf_ModulesDAO $db
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 table name construction
     */
    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tablePrefix = $tablePrefix;
        $this->tableName = $tablePrefix . 'staging_payments';
        $this->db = $db;
    }

    /**
     * Ensure the staging_payments table exists (idempotent CREATE).
     *
     * @since 2.0.0
     * @BABOK Related: FR-ISU-017-001 install schema
     */
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

    /**
     * Insert a new staging payment.
     *
     * @param StagingPayment $payment
     * @return int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 insert
     */
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
        $id = (int)db_insert_id();
        $payment->setId($id);
        return $id;
    }

    /**
     * @BABOK Related: FR-ISU-003
     */
    public function findById(int $id): ?StagingPayment
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $row = $this->db->query($sql, [$id])->fetch_assoc();
        return $row ? StagingPayment::fromArray($row) : null;
    }

    /**
     * Find a staging payment by source + source_payment_id (duplicate check).
     *
     * @param string $source
     * @param string $sourcePaymentId
     * @return StagingPayment|null
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-002 findBySource
     */
    public function findBySource(string $source, string $sourcePaymentId): ?StagingPayment
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE source = ? AND source_payment_id = ?";
        $row = $this->db->query($sql, [$source, $sourcePaymentId])->fetch_assoc();
        return $row ? StagingPayment::fromArray($row) : null;
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
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
    }

    /**
     * @BABOK Related: FR-ISU-003
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, ?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT * FROM {$this->tableName} WHERE payment_date BETWEEN ? AND ? AND source = ? ORDER BY payment_date ASC";
            $rows = $this->db->query($sql, [$from->format('Y-m-d'), $to->format('Y-m-d'), $source])->fetch_all();
        } else {
            $sql = "SELECT * FROM {$this->tableName} WHERE payment_date BETWEEN ? AND ? ORDER BY payment_date ASC";
            $rows = $this->db->query($sql, [$from->format('Y-m-d'), $to->format('Y-m-d')])->fetch_all();
        }
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
    }

    /**
     * Find all staging payments linked to a staging transaction.
     *
     * @param int $stagingTransactionId
     * @return StagingPayment[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 findByTransaction
     */
    public function findByTransaction(int $stagingTransactionId): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE staging_transaction_id = ? ORDER BY amount DESC";
        $rows = $this->db->query($sql, [$stagingTransactionId])->fetch_all();
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
    }

    /**
     * Update the status and optional confidence/error of a staging payment.
     *
     * @param int         $id
     * @param string      $status
     * @param float|null  $confidence
     * @param string|null $error
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updateStatus
     */
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

    /**
     * Write the FA payment reference onto a staging payment.
     *
     * @param int $id
     * @param int $faTransType
     * @param int $faTransNo
     * @param string|null $faBankAccount
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 updateFaReference
     */
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

    /**
     * Count staging payments grouped by status.
     *
     * @param string|null $source
     * @return array<string,int>
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 countByStatus
     */
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

    /**
     * Update a staging payment by source + source_payment_id (idempotent upsert).
     *
     * @param StagingPayment $payment
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 updateBySource
     */
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

    /**
     * Get payments ready for reconciliation (staged, validated, or matched).
     *
     * @param string|null $source
     * @param int $limit
     * @return StagingPayment[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 getQueueForReconciliation
     */
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
        $rows = $this->db->query($sql, $params)->fetch_all();
        return array_map(fn($row) => StagingPayment::fromArray($row), $rows);
    }
}

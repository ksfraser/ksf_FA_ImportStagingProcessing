<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\DAO;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingPaymentMatch;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentMatchRepositoryInterface;

class StagingPaymentMatchDAO implements PaymentMatchRepositoryInterface
{
    private string $tablePrefix;
    private string $tableName;
    private \ksf_ModulesDAO $db;

    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tablePrefix = $tablePrefix;
        $this->tableName = $tablePrefix . 'staging_payment_matches';
        $this->db = $db;
    }

    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            staging_payment_id INT(11) NOT NULL,
            match_type VARCHAR(16) NOT NULL,
            match_confidence DECIMAL(5,4) DEFAULT NULL,
            fa_trans_type INT(11) DEFAULT NULL,
            fa_trans_no INT(11) DEFAULT NULL,
            fa_bank_account VARCHAR(32) DEFAULT NULL,
            match_status VARCHAR(16) NOT NULL DEFAULT 'matched',
            matched_by VARCHAR(32) DEFAULT 'system',
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_staging_payment (staging_payment_id),
            KEY idx_match_status (match_status),
            KEY idx_fa_reference (fa_trans_type, fa_trans_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    public function insert(StagingPaymentMatch $match): int
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->tableName}
            (staging_payment_id, match_type, match_confidence,
             fa_trans_type, fa_trans_no, fa_bank_account,
             match_status, matched_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $match->getStagingPaymentId(),
            $match->getMatchType(),
            $match->getMatchConfidence(),
            $match->getFaTransType(),
            $match->getFaTransNo(),
            $match->getFaBankAccount(),
            $match->getMatchStatus(),
            $match->getMatchedBy(),
            $match->getNotes(),
        ]);
        $id = (int)db_insert_id();
        $match->setId($id);
        return $id;
    }

    public function findById(int $id): ?StagingPaymentMatch
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $row = $this->db->query($sql, [$id])->fetch_assoc();
        return $row ? StagingPaymentMatch::fromArray($row) : null;
    }

    public function findByPaymentId(int $stagingPaymentId): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE staging_payment_id = ? ORDER BY created_at DESC";
        $rows = $this->db->query($sql, [$stagingPaymentId])->fetch_all();
        return array_map(fn($row) => StagingPaymentMatch::fromArray($row), $rows);
    }

    public function findByStatus(string $matchStatus): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE match_status = ? ORDER BY created_at DESC";
        $rows = $this->db->query($sql, [$matchStatus])->fetch_all();
        return array_map(fn($row) => StagingPaymentMatch::fromArray($row), $rows);
    }

    public function updateStatus(int $id, string $matchStatus, ?string $notes = null): void
    {
        if ($notes !== null) {
            $sql = "UPDATE {$this->tableName} SET match_status = ?, notes = ? WHERE id = ?";
            $this->db->query($sql, [$matchStatus, $notes, $id]);
        } else {
            $sql = "UPDATE {$this->tableName} SET match_status = ? WHERE id = ?";
            $this->db->query($sql, [$matchStatus, $id]);
        }
    }

    public function getLatestByPaymentId(int $stagingPaymentId): ?StagingPaymentMatch
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE staging_payment_id = ? ORDER BY created_at DESC LIMIT 1";
        $row = $this->db->query($sql, [$stagingPaymentId])->fetch_assoc();
        return $row ? StagingPaymentMatch::fromArray($row) : null;
    }
}

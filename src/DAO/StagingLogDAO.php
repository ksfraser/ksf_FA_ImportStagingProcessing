<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DAO;

class StagingLogDAO
{
    private string $tablePrefix;
    private string $tableName;
    private \ksf_ModulesDAO $db;

    public function __construct(string $tablePrefix, \ksf_ModulesDAO $db)
    {
        $this->tablePrefix = $tablePrefix;
        $this->tableName = $tablePrefix . 'staging_log';
        $this->db = $db;
    }

    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            record_type VARCHAR(32) NOT NULL,
            record_id INT(11) NOT NULL,
            action VARCHAR(32) NOT NULL,
            source VARCHAR(32) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_record (record_type, record_id),
            KEY idx_action (action),
            KEY idx_source (source),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
    }

    public function log(string $recordType, int $recordId, string $action, ?string $source = null, array $details = []): int
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->tableName} (record_type, record_id, action, source, details)
                VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $recordType,
            $recordId,
            $action,
            $source,
            !empty($details) ? json_encode($details) : null,
        ]);
        return (int)$this->db->insertId();
    }

    public function findByRecord(string $recordType, int $recordId): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE record_type = ? AND record_id = ? ORDER BY created_at DESC";
        return $this->db->query($sql, [$recordType, $recordId])->fetchAll();
    }

    public function findByAction(string $action, ?string $source = null, int $limit = 100): array
    {
        if ($source) {
            $sql = "SELECT * FROM {$this->tableName} WHERE action = ? AND source = ? ORDER BY created_at DESC LIMIT ?";
            return $this->db->query($sql, [$action, $source, $limit])->fetchAll();
        }
        $sql = "SELECT * FROM {$this->tableName} WHERE action = ? ORDER BY created_at DESC LIMIT ?";
        return $this->db->query($sql, [$action, $limit])->fetchAll();
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, ?string $action = null): array
    {
        if ($action) {
            $sql = "SELECT * FROM {$this->tableName} WHERE created_at BETWEEN ? AND ? AND action = ? ORDER BY created_at DESC";
            return $this->db->query($sql, [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'), $action])->fetchAll();
        }
        $sql = "SELECT * FROM {$this->tableName} WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC";
        return $this->db->query($sql, [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])->fetchAll();
    }

    public function getRecent(int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY created_at DESC LIMIT ?";
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    public function countByAction(?string $source = null): array
    {
        if ($source) {
            $sql = "SELECT action, COUNT(*) as count FROM {$this->tableName} WHERE source = ? GROUP BY action";
            $rows = $this->db->query($sql, [$source])->fetchAll();
        } else {
            $sql = "SELECT action, COUNT(*) as count FROM {$this->tableName} GROUP BY action";
            $rows = $this->db->query($sql)->fetchAll();
        }
        $result = [];
        foreach ($rows as $row) {
            $result[$row['action']] = (int)$row['count'];
        }
        return $result;
    }
}

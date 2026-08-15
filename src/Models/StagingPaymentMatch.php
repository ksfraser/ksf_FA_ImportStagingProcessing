<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Models;

class StagingPaymentMatch
{
    private ?int $id;
    private int $stagingPaymentId;
    private string $matchType;
    private ?float $matchConfidence;
    private ?int $faTransType;
    private ?int $faTransNo;
    private ?string $faBankAccount;
    private string $matchStatus;
    private string $matchedBy;
    private ?string $notes;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    public function __construct(int $stagingPaymentId, string $matchType = 'none')
    {
        $this->stagingPaymentId = $stagingPaymentId;
        $this->matchType = $matchType;
        $this->matchStatus = 'matched';
        $this->matchedBy = 'system';
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getStagingPaymentId(): int { return $this->stagingPaymentId; }

    public function getMatchType(): string { return $this->matchType; }
    public function setMatchType(string $type): void { $this->matchType = $type; }

    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    public function setMatchConfidence(?float $confidence): void { $this->matchConfidence = $confidence; }

    public function getFaTransType(): ?int { return $this->faTransType; }
    public function setFaTransType(?int $type): void { $this->faTransType = $type; }

    public function getFaTransNo(): ?int { return $this->faTransNo; }
    public function setFaTransNo(?int $no): void { $this->faTransNo = $no; }

    public function getFaBankAccount(): ?string { return $this->faBankAccount; }
    public function setFaBankAccount(?string $acct): void { $this->faBankAccount = $acct; }

    public function getMatchStatus(): string { return $this->matchStatus; }
    public function setMatchStatus(string $status): void { $this->matchStatus = $status; }

    public function getMatchedBy(): string { return $this->matchedBy; }
    public function setMatchedBy(string $by): void { $this->matchedBy = $by; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): void { $this->notes = $notes; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $dt): void { $this->createdAt = $dt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $dt): void { $this->updatedAt = $dt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'staging_payment_id' => $this->stagingPaymentId,
            'match_type' => $this->matchType,
            'match_confidence' => $this->matchConfidence,
            'fa_trans_type' => $this->faTransType,
            'fa_trans_no' => $this->faTransNo,
            'fa_bank_account' => $this->faBankAccount,
            'match_status' => $this->matchStatus,
            'matched_by' => $this->matchedBy,
            'notes' => $this->notes,
        ];
    }

    public static function fromArray(array $data): self
    {
        $match = new self(
            (int)($data['staging_payment_id'] ?? 0),
            $data['match_type'] ?? 'none'
        );
        if (isset($data['id'])) $match->setId((int)$data['id']);
        if (isset($data['match_confidence'])) $match->setMatchConfidence((float)$data['match_confidence']);
        if (isset($data['fa_trans_type'])) $match->setFaTransType((int)$data['fa_trans_type']);
        if (isset($data['fa_trans_no'])) $match->setFaTransNo((int)$data['fa_trans_no']);
        if (isset($data['fa_bank_account'])) $match->setFaBankAccount($data['fa_bank_account']);
        if (isset($data['match_status'])) $match->setMatchStatus($data['match_status']);
        if (isset($data['matched_by'])) $match->setMatchedBy($data['matched_by']);
        if (isset($data['notes'])) $match->setNotes($data['notes']);
        return $match;
    }
}

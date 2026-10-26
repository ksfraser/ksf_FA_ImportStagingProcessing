<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Models;

/**
 * Data model for a payment-to-FA transaction match record.
 *
 * Records the match type, confidence, FA transaction reference, and match
 * status for a staged payment during reconciliation.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-013-001 payment reconciliation match record
 * @BABOK Related: FR-ISU-013-002 match_type / match_confidence scoring
 * @BABOK Related: FR-ISU-014-001 auto-reconcile match status
 * @BABOK Related: NFR-ISU-003 TB_PREF convention
 */
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

    /**
     * @param int $stagingPaymentId
     * @param string $matchType
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 match record creation
     */
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

    /**
     * @param string $type
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_type
     */
    public function getMatchType(): string { return $this->matchType; }
    /**
     * @param string $type
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_type
     */
    public function setMatchType(string $type): void { $this->matchType = $type; }

    /**
     * @return ?float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_confidence
     */
    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    /**
     * @param ?float $confidence
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_confidence
     */
    public function setMatchConfidence(?float $confidence): void { $this->matchConfidence = $confidence; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_type
     */
    public function getFaTransType(): ?int { return $this->faTransType; }
    /**
     * @param ?int $type
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_type
     */
    public function setFaTransType(?int $type): void { $this->faTransType = $type; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_no
     */
    public function getFaTransNo(): ?int { return $this->faTransNo; }
    /**
     * @param ?int $no
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_no
     */
    public function setFaTransNo(?int $no): void { $this->faTransNo = $no; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_bank_account
     */
    public function getFaBankAccount(): ?string { return $this->faBankAccount; }
    /**
     * @param ?string $acct
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_bank_account
     */
    public function setFaBankAccount(?string $acct): void { $this->faBankAccount = $acct; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_status
     */
    public function getMatchStatus(): string { return $this->matchStatus; }
    /**
     * @param string $status
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_status
     */
    public function setMatchStatus(string $status): void { $this->matchStatus = $status; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 matched_by
     */
    public function getMatchedBy(): string { return $this->matchedBy; }
    /**
     * @param string $by
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 matched_by
     */
    public function setMatchedBy(string $by): void { $this->matchedBy = $by; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 notes
     */
    public function getNotes(): ?string { return $this->notes; }
    /**
     * @param ?string $notes
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 notes
     */
    public function setNotes(?string $notes): void { $this->notes = $notes; }

    /**
     * @return ?\DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 created_at
     */
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    /**
     * @param ?\DateTimeInterface $dt
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 created_at
     */
    public function setCreatedAt(?\DateTimeInterface $dt): void { $this->createdAt = $dt; }

    /**
     * @return ?\DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updated_at
     */
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    /**
     * @param ?\DateTimeInterface $dt
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updated_at
     */
    public function setUpdatedAt(?\DateTimeInterface $dt): void { $this->updatedAt = $dt; }

    /**
     * @return array<string, mixed>
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 match record serialization
     */
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

    /**
     * @param array $data
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 match record hydration
     */
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

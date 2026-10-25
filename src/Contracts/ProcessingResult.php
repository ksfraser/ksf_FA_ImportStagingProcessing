<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

/**
 * Result object for processing operations.
 *
 * Carries success flag, record ID, action name, FA reference, errors,
 * match confidence, and processed timestamp. Returned by the processing
 * pipeline and service operations.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-012-001 processing result
 * @BABOK Related: FR-ISU-013-001 reconciliation result
 * @BABOK Related: NFR-ISU-009 Value Object pattern
 */
class ProcessingResult
{
    /**
     * @param bool $success
     * @param int|null $recordId
     * @param string $action
     * @param int|null $faReferenceNo
     * @param array $errors
     * @param float|null $matchConfidence
     * @param \DateTimeInterface|null $processedAt
     * @param array $metadata
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 constructor
     */
    public function __construct(
        bool $success,
        ?int $recordId = null,
        string $action = '',
        ?int $faReferenceNo = null,
        array $errors = [],
        ?float $matchConfidence = null,
        ?\DateTimeInterface $processedAt = null,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->recordId = $recordId;
        $this->action = $action;
        $this->faReferenceNo = $faReferenceNo;
        $this->errors = $errors;
        $this->matchConfidence = $matchConfidence;
        $this->processedAt = $processedAt ?? new \DateTimeImmutable();
        $this->metadata = $metadata;
    }

    /**
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 isSuccess
     */
    public function isSuccess(): bool { return $this->success; }
    /**
     * @return int|null
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 getRecordId
     */
    public function getRecordId(): ?int { return $this->recordId; }
    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 getAction
     */
    public function getAction(): string { return $this->action; }
    /**
     * @return int|null
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 getFaReferenceNo
     */
    public function getFaReferenceNo(): ?int { return $this->faReferenceNo; }
    /**
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 getErrors
     */
    public function getErrors(): array { return $this->errors; }
    /**
     * @return float|null
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 getMatchConfidence
     */
    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    /**
     * @return \DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 getProcessedAt
     */
    public function getProcessedAt(): \DateTimeInterface { return $this->processedAt; }
    /**
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 getMetadata
     */
    public function getMetadata(): array { return $this->metadata; }

    /**
     * Create a successful processing result.
     *
     * @param int $recordId
     * @param string $action
     * @param int|null $faRef
     * @param float|null $confidence
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 success
     */
    public static function success(int $recordId, string $action, ?int $faRef = null, float $confidence = null): self
    {
        return new self(true, $recordId, $action, $faRef, [], $confidence);
    }

    /**
     * Create a failed processing result.
     *
     * @param int $recordId
     * @param string $action
     * @param array $errors
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 failure
     */
    public static function failure(int $recordId, string $action, array $errors): self
    {
        return new self(false, $recordId, $action, null, $errors);
    }
}

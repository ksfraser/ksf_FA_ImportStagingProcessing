<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

/**
 * Contract for matching staged records against existing FA records.
 *
 * Implementations provide scoring-based candidate matching with
 * auto-approve and needs-review thresholds.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-009-001 matching
 * @BABOK Related: FR-ISU-010-001 auto-approve threshold
 * @BABOK Related: FR-ISU-011-001 review threshold
 * @BABOK Related: NFR-ISU-009 Strategy pattern
 */
interface MatchingServiceInterface
{
    /**
     * Match a staged record against candidate existing records.
     *
     * @param array $stagedRecord    The staged record
     * @param array $existingRecords Candidate existing records
     * @return array Match result with confidence and candidates
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-001 matchCandidates
     */
    public function matchCandidates(array $stagedRecord, array $existingRecords): array;

    /**
     * Determine whether a confidence score qualifies for auto-approval.
     *
     * @param float $confidence Match confidence (0.0–1.0)
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-010-001 autoApprove
     */
    public function autoApprove(float $confidence): bool;

    /**
     * Determine whether a confidence score needs manual review.
     *
     * @param float $confidence Match confidence (0.0–1.0)
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-011-001 needsReview
     */
    public function needsReview(float $confidence): bool;

    /**
     * Approve a match by ID.
     *
     * @param string $matchId Match record ID
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 approveMatch
     */
    public function approveMatch(string $matchId): void;

    /**
     * Reject a match by ID with a reason.
     *
     * @param string $matchId Match record ID
     * @param string $reason  Rejection reason
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 rejectMatch
     */
    public function rejectMatch(string $matchId, string $reason): void;
}

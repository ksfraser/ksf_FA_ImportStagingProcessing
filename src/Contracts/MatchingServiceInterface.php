<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Contracts;

interface MatchingServiceInterface
{
    public function matchCandidates(array $stagedRecord, array $existingRecords): array;

    public function autoApprove(float $confidence): bool;

    public function needsReview(float $confidence): bool;

    public function approveMatch(string $matchId): void;

    public function rejectMatch(string $matchId, string $reason): void;
}

<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Services;

use Ksfraser\ImportStaging\Contracts\MatchingServiceInterface;

class MatchingService implements MatchingServiceInterface
{
    private float $autoApproveThreshold;
    private float $reviewThreshold;

    public function __construct(float $autoApproveThreshold = 0.95, float $reviewThreshold = 0.80)
    {
        $this->autoApproveThreshold = $autoApproveThreshold;
        $this->reviewThreshold = $reviewThreshold;
    }

    public function matchCandidates(array $stagedRecord, array $existingRecords): array
    {
        if (empty($existingRecords)) {
            return [
                'confidence' => 0.0,
                'match_type' => 'unmatched',
                'details' => 'No existing records to match against',
                'candidates' => [],
            ];
        }
        $candidates = [];
        foreach ($existingRecords as $record) {
            $score = $this->calculateMatchScore($stagedRecord, $record);
            $candidates[] = [
                'record' => $record,
                'score' => $score,
                'details' => $this->getMatchDetails($stagedRecord, $record),
            ];
        }
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        $bestScore = $candidates[0]['score'] ?? 0.0;
        $matchType = $this->determineMatchType($bestScore);
        return [
            'confidence' => $bestScore,
            'match_type' => $matchType,
            'details' => $candidates[0]['details'] ?? [],
            'candidates' => array_slice($candidates, 0, 5),
        ];
    }

    public function autoApprove(float $confidence): bool
    {
        return $confidence >= $this->autoApproveThreshold;
    }

    public function needsReview(float $confidence): bool
    {
        return $confidence >= $this->reviewThreshold && $confidence < $this->autoApproveThreshold;
    }

    public function approveMatch(string $matchId): void
    {
    }

    public function rejectMatch(string $matchId, string $reason): void
    {
    }

    public function calculateMatchScore(array $staged, array $existing): float
    {
        $score = 0.0;
        $amountWeight = 0.4;
        $dateWeight = 0.25;
        $customerWeight = 0.25;
        $referenceWeight = 0.1;
        if (isset($staged['total_amount'], $existing['total_amount'])) {
            $score += $this->matchAmount((float)$staged['total_amount'], (float)$existing['total_amount']) * $amountWeight;
        }
        if (isset($staged['transaction_date'], $existing['transaction_date'])) {
            $score += $this->matchDate($staged['transaction_date'], $existing['transaction_date']) * $dateWeight;
        }
        if (isset($staged['customer_name'], $existing['customer_name'])) {
            $score += $this->matchName($staged['customer_name'], $existing['customer_name']) * $customerWeight;
        }
        if (isset($staged['source_transaction_id'], $existing['source_transaction_id'])) {
            $score += $this->exactMatch($staged['source_transaction_id'], $existing['source_transaction_id']) * $referenceWeight;
        }
        $totalWeight = $amountWeight + $dateWeight + $customerWeight + $referenceWeight;
        return $totalWeight > 0 ? $score / $totalWeight : 0.0;
    }

    public function matchAmount(float $amountA, float $amountB): float
    {
        if ($amountA == 0 && $amountB == 0) {
            return 1.0;
        }
        $diff = abs($amountA - $amountB);
        $max = max(abs($amountA), abs($amountB));
        if ($max == 0) {
            return 0.0;
        }
        $tolerance = $max * 0.01;
        if ($diff <= $tolerance) {
            return 1.0;
        }
        return max(0.0, 1.0 - ($diff / $max));
    }

    public function matchDate(string $dateA, string $dateB): float
    {
        try {
            $dtA = new \DateTimeImmutable($dateA);
            $dtB = new \DateTimeImmutable($dateB);
            $diff = abs($dtA->getTimestamp() - $dtB->getTimestamp());
            $tolerance = 86400;
            if ($diff <= $tolerance) {
                return 1.0;
            }
            return max(0.0, 1.0 - ($diff / $tolerance));
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    public function matchByCustomer(array $custA, array $custB): float
    {
        $scores = [];
        if (isset($custA['email'], $custB['email'])) {
            $scores[] = $this->exactMatch($custA['email'], $custB['email']);
        }
        if (isset($custA['name'], $custB['name'])) {
            $scores[] = $this->matchName($custA['name'], $custB['name']);
        }
        if (isset($custA['phone'], $custB['phone'])) {
            $scores[] = $this->matchPhone($custA['phone'], $custB['phone']);
        }
        if (empty($scores)) {
            return 0.0;
        }
        return array_sum($scores) / count($scores);
    }

    public function matchName(string $nameA, string $nameB): float
    {
        $normalizedA = strtolower(trim(preg_replace('/\s+/', ' ', $nameA)));
        $normalizedB = strtolower(trim(preg_replace('/\s+/', ' ', $nameB)));
        if ($normalizedA === $normalizedB) {
            return 1.0;
        }
        $similarity = 0.0;
        similar_text($normalizedA, $normalizedB, $similarity);
        return $similarity / 100.0;
    }

    public function matchPhone(string $phoneA, string $phoneB): float
    {
        $cleanA = preg_replace('/[^0-9]/', '', $phoneA);
        $cleanB = preg_replace('/[^0-9]/', '', $phoneB);
        if ($cleanA === $cleanB) {
            return 1.0;
        }
        if (substr($cleanA, -7) === substr($cleanB, -7)) {
            return 0.7;
        }
        return 0.0;
    }

    public function exactMatch(string $valueA, string $valueB): float
    {
        return $valueA === $valueB ? 1.0 : 0.0;
    }

    private function determineMatchType(float $score): string
    {
        if ($score >= $this->autoApproveThreshold) {
            return 'exact';
        }
        if ($score >= $this->reviewThreshold) {
            return 'fuzzy';
        }
        if ($score > 0.0) {
            return 'partial';
        }
        return 'unmatched';
    }

    private function getMatchDetails(array $staged, array $existing): array
    {
        return [
            'amount_match' => isset($staged['total_amount'], $existing['total_amount'])
                ? $this->matchAmount((float)$staged['total_amount'], (float)$existing['total_amount'])
                : 0.0,
            'date_match' => isset($staged['transaction_date'], $existing['transaction_date'])
                ? $this->matchDate($staged['transaction_date'], $existing['transaction_date'])
                : 0.0,
            'name_match' => isset($staged['customer_name'], $existing['customer_name'])
                ? $this->matchName($staged['customer_name'], $existing['customer_name'])
                : 0.0,
        ];
    }
}

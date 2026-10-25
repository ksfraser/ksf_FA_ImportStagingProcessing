<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services;

use ksfraser\FrontAccounting\ImportStaging\Contracts\MatchingServiceInterface;

/**
 * Scoring-based matching service for staged records.
 *
 * Implements MatchingServiceInterface. Computes match confidence between
 * staged records and existing FA records using weighted factors (amount,
 * date, customer, reference). Provides auto-approve (>=95%) and
 * needs-review (80-95%) thresholds.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-009-001 matching algorithm
 * @BABOK Related: FR-ISU-009-002 scoring-based matching
 * @BABOK Related: FR-ISU-010-001 auto-approve >=95% threshold
 * @BABOK Related: FR-ISU-010-002 threshold configurable
 * @BABOK Related: FR-ISU-011-001 review 80-95% threshold
 * @BABOK Related: FR-ISU-011-002 threshold configurable
 * @BABOK Related: FR-ISU-013-002 payment match scoring (40/25/20/15)
 * @BABOK Related: NFR-ISU-009 Strategy pattern
 */
class MatchingService implements MatchingServiceInterface
{
    private float $autoApproveThreshold;
    private float $reviewThreshold;

    private const CUSTOMER_MATCH_THRESHOLD = 50.0;

    private const CUSTOMER_SCORE_EMAIL = 30.0;
    private const CUSTOMER_SCORE_PHONE = 25.0;
    private const CUSTOMER_SCORE_NAME = 20.0;
    private const CUSTOMER_SCORE_CONTACT_NAME = 20.0;
    private const CUSTOMER_SCORE_ADDRESS = 15.0;

    /**
     * @param float $autoApproveThreshold
     * @param float $reviewThreshold
     * @since 2.0.0
     * @BABOK Related: FR-ISU-010-001 autoApproveThreshold
     * @BABOK Related: FR-ISU-011-001 reviewThreshold
     */
    public function __construct(float $autoApproveThreshold = 0.95, float $reviewThreshold = 0.80)
    {
        $this->autoApproveThreshold = $autoApproveThreshold;
        $this->reviewThreshold = $reviewThreshold;
    }

    /**
     * Match a staged record against candidate existing records.
     *
     * Scores each candidate and returns the best match with confidence,
     * match type, and top 5 candidates.
     *
     * @param array $stagedRecord
     * @param array $existingRecords
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-001 matchCandidates
     */
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

    /**
     * Determine whether a confidence score qualifies for auto-approval (>=95%).
     *
     * @param float $confidence
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-010-001 autoApprove
     */
    public function autoApprove(float $confidence): bool
    {
        return $confidence >= $this->autoApproveThreshold;
    }

    /**
     * Determine whether a confidence score needs manual review (80-95%).
     *
     * @param float $confidence
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-011-001 needsReview
     */
    public function needsReview(float $confidence): bool
    {
        return $confidence >= $this->reviewThreshold && $confidence < $this->autoApproveThreshold;
    }

    /**
     * Approve a match by ID (no-op placeholder for interface compliance).
     *
     * @param string $matchId
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 approveMatch
     */
    public function approveMatch(string $matchId): void
    {
    }

    /**
     * Reject a match by ID with a reason (no-op placeholder for interface compliance).
     *
     * @param string $matchId
     * @param string $reason
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 rejectMatch
     */
    public function rejectMatch(string $matchId, string $reason): void
    {
    }

    /**
     * Calculate the match score between a staged record and an existing record.
     *
     * Weights: amount 40%, date 25%, customer 25%, reference 10%.
     *
     * @param array $staged
     * @param array $existing
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 calculateMatchScore
     */
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

    /**
     * Match two monetary amounts with 1% tolerance.
     *
     * @param float $amountA
     * @param float $amountB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchAmount
     */
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

    /**
     * Match two dates within a 1-day tolerance.
     *
     * @param string $dateA
     * @param string $dateB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchDate
     */
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

    /**
     * Match staged records against existing records by customer fields.
     *
     * @param array $staged
     * @param array $existingRecords
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchByCustomer
     */
    public function matchByCustomer(array $staged, array $existingRecords): array
    {
        $candidates = [];
        foreach ($existingRecords as $record) {
            $score = $this->calculateCustomerMatchScore($staged, $record);
            $candidates[] = [
                'debtor_no'  => $record['debtor_no'] ?? null,
                'branch_ref' => $record['branch_ref'] ?? null,
                'name'       => $record['name'] ?? null,
                'company'    => $record['company'] ?? $record['name'] ?? null,
                'email'      => $record['email'] ?? null,
                'phone'      => $record['phone'] ?? null,
                'score'      => $score,
            ];
        }
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        return $candidates;
    }

    /**
     * Calculate a customer match score (email/phone/name/address/weighted).
     *
     * @param array $staged
     * @param array $existing
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 calculateCustomerMatchScore
     */
    public function calculateCustomerMatchScore(array $staged, array $existing): float
    {
        $score = 0.0;

        if (!empty($staged['email']) && !empty($existing['email'])) {
            if (strcasecmp(trim($staged['email']), trim($existing['email'])) === 0) {
                $score += self::CUSTOMER_SCORE_EMAIL;
            }
        }

        if (!empty($staged['email']) && !empty($existing['branch_email'])) {
            if (strcasecmp(trim($staged['email']), trim($existing['branch_email'])) === 0) {
                $score += self::CUSTOMER_SCORE_EMAIL;
            }
        }

        if (!empty($staged['phone']) && !empty($existing['phone'])) {
            $cleanStaged = preg_replace('/[^0-9]/', '', $staged['phone']);
            $cleanExisting = preg_replace('/[^0-9]/', '', $existing['phone']);
            if ($cleanStaged !== '' && $cleanStaged === $cleanExisting) {
                $score += self::CUSTOMER_SCORE_PHONE;
            }
        }

        if (!empty($staged['company']) && !empty($existing['name'])) {
            if ($this->fuzzyMatch($staged['company'], $existing['name'])) {
                $score += self::CUSTOMER_SCORE_NAME;
            }
        }

        $stagedContact = trim(($staged['first_name'] ?? '') . ' ' . ($staged['last_name'] ?? ''));
        if ($stagedContact !== '' && !empty($existing['contact_name'])) {
            if ($this->fuzzyMatch($stagedContact, $existing['contact_name'])) {
                $score += self::CUSTOMER_SCORE_CONTACT_NAME;
            }
        }

        if (!empty($staged['address1']) && !empty($existing['br_address'])) {
            if ($this->addressMatch($staged['address1'], $existing['br_address'])) {
                $score += self::CUSTOMER_SCORE_ADDRESS;
            }
        }

        return min(100.0, $score);
    }

    /**
     * Fuzzy-match two strings (exact, substring, or levenshtein < 20%).
     *
     * @param string $a
     * @param string $b
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 fuzzyMatch
     */
    public function fuzzyMatch(string $a, string $b): bool
    {
        $a = strtolower(trim(preg_replace('/\s+/', ' ', $a)));
        $b = strtolower(trim(preg_replace('/\s+/', ' ', $b)));

        if ($a === $b) {
            return true;
        }
        if (strpos($a, $b) !== false || strpos($b, $a) !== false) {
            return true;
        }

        $dist = levenshtein($a, $b);
        $len = max(strlen($a), strlen($b));
        return $len > 0 && ($dist / $len) < 0.2;
    }

    /**
     * Match two addresses (substring containment either direction).
     *
     * @param string $addrA
     * @param string $addrB
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 addressMatch
     */
    public function addressMatch(string $addrA, string $addrB): bool
    {
        $normA = strtolower(trim(preg_replace('/\s+/', ' ', $addrA)));
        $normB = strtolower(trim(preg_replace('/\s+/', ' ', $addrB)));
        return strpos($normA, $normB) !== false || strpos($normB, $normA) !== false;
    }

    /**
     * Match two names using similar_text percentage.
     *
     * @param string $nameA
     * @param string $nameB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchName
     */
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

    /**
     * Match two phone numbers (exact or last-7-digit match).
     *
     * @param string $phoneA
     * @param string $phoneB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchPhone
     */
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

    /**
     * Exact match two strings (1.0 if equal, 0.0 otherwise).
     *
     * @param string $valueA
     * @param string $valueB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 exactMatch
     */
    public function exactMatch(string $valueA, string $valueB): float
    {
        return $valueA === $valueB ? 1.0 : 0.0;
    }

    /**
     * Calculate a payment match score against an FA record.
     *
     * Weights: amount 40%, date 25%, reference 20%, method 15%.
     *
     * @param array $payment
     * @param array $faRecord
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 calculatePaymentMatchScore
     */
    public function calculatePaymentMatchScore(array $payment, array $faRecord): float
    {
        $score = 0.0;
        $amountWeight = 0.4;
        $dateWeight = 0.25;
        $referenceWeight = 0.20;
        $methodWeight = 0.15;

        if (isset($payment['amount'], $faRecord['amount'])) {
            $score += $this->matchAmount((float)$payment['amount'], (float)$faRecord['amount']) * $amountWeight;
        }
        if (isset($payment['payment_date'], $faRecord['payment_date'])) {
            $score += $this->matchDate($payment['payment_date'], $faRecord['payment_date']) * $dateWeight;
        }
        if (isset($payment['reference'], $faRecord['reference'])) {
            $score += $this->matchName($payment['reference'], $faRecord['reference']) * $referenceWeight;
        }
        if (isset($payment['payment_method'], $faRecord['payment_method'])) {
            $score += $this->exactMatch($payment['payment_method'], $faRecord['payment_method']) * $methodWeight;
        }

        $totalWeight = $amountWeight + $dateWeight + $referenceWeight + $methodWeight;
        return $totalWeight > 0 ? $score / $totalWeight : 0.0;
    }

    /**
     * Determine a payment match type from a score.
     *
     * @param float $score
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-014-001 determinePaymentMatchType
     */
    public function determinePaymentMatchType(float $score): string
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
        return 'none';
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

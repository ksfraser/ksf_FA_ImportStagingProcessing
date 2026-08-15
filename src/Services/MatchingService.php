<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services;

use ksfraser\FrontAccounting\ImportStaging\Contracts\MatchingServiceInterface;

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

    public function addressMatch(string $addrA, string $addrB): bool
    {
        $normA = strtolower(trim(preg_replace('/\s+/', ' ', $addrA)));
        $normB = strtolower(trim(preg_replace('/\s+/', ' ', $addrB)));
        return strpos($normA, $normB) !== false || strpos($normB, $normA) !== false;
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

<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services;

use ksfraser\FrontAccounting\ImportStaging\Contracts\MatchingServiceInterface;
use ksfraser\FrontAccounting\Common\ContactResolver\Contract\ContactResolverInterface;
use ksfraser\FrontAccounting\Common\Matching\GenericMatcher;
use ksfraser\FrontAccounting\Common\Matching\Matcher;
use ksfraser\FrontAccounting\Common\Matching\MatchingConfig;

/**
 * Scoring-based matching service for staged records.
 *
 * Delegates core scoring to the generic matcher in ksf_FA_Common
 * (ksfraser\FrontAccounting\Common\Matching\GenericMatcher), configured with
 * ISP field-name mappings (total_amount→amount, transaction_date→date,
 * customer_name→name, source_transaction_id→reference) and ISP weights
 * (amount 40%, date 25%, customer 25%, reference 10%).
 *
 * ISP-specific methods (matchByCustomer, calculateCustomerMatchScore,
 * matchPhone, addressMatch, etc.) are retained for backward compatibility.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-009-001 matching algorithm
 * @BABOK Related: FR-ISU-009-002 scoring-based matching
 * @BABOK Related: FR-ISU-010-001 auto-approve >=95% threshold
 * @BABOK Related: FR-ISU-010-002 threshold configurable
 * @BABOK Related: FR-ISU-011-001 review 80-95% threshold
 * @BABOK Related: FR-ISU-011-002 threshold configurable
 * @BABOK Related: FR-ISU-013-002 payment match scoring (40/25/20/15)
 * @BABOK Related: NFR-COM-MATCH-001 generic matcher delegation
 * @BABOK Related: NFR-COM-MATCH-002 weighted scoring engine
 * @BABOK Related: FR-COM-MATCH-025 ContactResolver person resolution
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

    /** @var Matcher */
    private $matcher;

    /**
     * @param float $autoApproveThreshold
     * @param float $reviewThreshold
     * @param ContactResolverInterface|null $contactResolver
     * @since 2.0.0
     * @BABOK Related: FR-ISU-010-001 autoApproveThreshold
     * @BABOK Related: FR-ISU-011-001 reviewThreshold
     */
    public function __construct(float $autoApproveThreshold = 0.95, float $reviewThreshold = 0.80, ContactResolverInterface $contactResolver = null)
    {
        $this->autoApproveThreshold = $autoApproveThreshold;
        $this->reviewThreshold = $reviewThreshold;

        $config = new MatchingConfig(array(
            'field_map'          => array(
                'person_id'  => 'person_id',
                'email'      => 'email',
                'amount'     => 'total_amount',
                'date'       => 'transaction_date',
                'name'       => 'customer_name',
                'phone'      => 'phone',
                'reference'  => 'source_transaction_id',
            ),
            'weights'            => array(
                'person_id'  => 0.25,
                'email'      => 0.20,
                'amount'     => 0.15,
                'date'       => 0.10,
                'name'       => 0.20,
                'phone'      => 0.05,
                'reference'  => 0.05,
            ),
            'auto_approve'       => $autoApproveThreshold,
            'review'             => $reviewThreshold,
            'amount_tolerance'   => 0.01,
            'date_tolerance_days'=> 1,
            'name_soundex'       => true,
        ));
        $this->matcher = new Matcher($config, $contactResolver);
    }

    /**
     * Match a staged record against candidate existing records.
     *
     * Delegates to GenericMatcher from ksf_FA_Common via the Matcher wrapper,
     * which applies weighted scoring on person_id (25%), email (20%), name
     * (20%), phone (5%), amount (15%), date (10%), and reference (5%).
     * Person_id is resolved from email/name via ContactResolver if available.
     * Returns results in the legacy array format for backward compatibility.
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

        $result = $this->matcher->match($stagedRecord, $existingRecords);

        $candidates = array();
        foreach ($result->getCandidates() as $candidate) {
            $candidates[] = [
                'record'  => $candidate->getRecord(),
                'score'   => $candidate->getConfidence(),
                'details' => $candidate->getFieldDetails(),
            ];
        }

        return [
            'confidence' => $result->getConfidence(),
            'match_type' => $result->getMatchType(),
            'details'    => $result->getDetails(),
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
        return $this->matcher->autoApprove($confidence);
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
        return $this->matcher->needsReview($confidence);
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
     * Delegates to GenericMatcher's per-field scoring via Matcher wrapper.
     * Weights: person_id 25%, email 20%, amount 15%, date 10%, name 20%,
     * phone 5%, reference 5%.
     *
     * @param array $staged
     * @param array $existing
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 calculateMatchScore
     */
    public function calculateMatchScore(array $staged, array $existing): float
    {
        $result = $this->matcher->match($staged, [$existing]);
        return $result->getConfidence();
    }

    /**
     * Match two monetary amounts with 1% tolerance.
     *
     * Delegated to GenericMatcher::scoreAmount().
     *
     * @param float $amountA
     * @param float $amountB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchAmount
     */
    public function matchAmount(float $amountA, float $amountB): float
    {
        return $this->matcher->scoreAmount($amountA, $amountB);
    }

    /**
     * Match two dates within a 1-day tolerance.
     *
     * Delegated to Matcher::scoreDate() (via GenericMatcher).
     *
     * @param string $dateA
     * @param string $dateB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchDate
     */
    public function matchDate(string $dateA, string $dateB): float
    {
        return $this->matcher->scoreDate($dateA, $dateB);
    }

    /**
     * Match staged records against existing records by customer fields.
     *
     * ISP-specific customer matching (email, phone, company, contact name,
     * address) not covered by the generic matcher. Retained as-is.
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
     * ISP-specific, not covered by generic matcher. Retained as-is.
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
     * Match two names using soundex + levenshtein.
     *
     * Delegated to GenericMatcher::scoreName().
     *
     * @param string $nameA
     * @param string $nameB
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-002 matchName
     */
    public function matchName(string $nameA, string $nameB): float
    {
        return $this->matcher->scoreName($nameA, $nameB);
    }

    /**
     * Match two phone numbers (exact or last-7-digit match).
     *
     * ISP-specific, retained as-is.
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
     * Delegated to GenericMatcher::scoreReference() for exact match logic.
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
     * Match two addresses (substring containment either direction).
     *
     * ISP-specific, retained as-is.
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
     * Fuzzy-match two strings (exact, substring, or levenshtein < 20%).
     *
     * ISP-specific, retained as-is.
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
     * Calculate a payment match score against an FA record.
     *
     * Weights: amount 40%, date 25%, reference 20%, method 15%. ISP-specific
     * payment scoring not covered by the generic matcher (uses payment_method
     * field). Retained as-is.
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

    /**
     * @BABOK Related: FR-ISU-009
     */
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

    /**
     * @BABOK Related: FR-ISU-009
     */
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

<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services\Dispatch;

use ksfraser\FrontAccounting\ImportStaging\Services\Matching\MatchConfig;

/**
 * SearchInvoker — asks "whoever can find this?" across every active module.
 *
 * Dispatches search capabilities (SEARCH_CUSTOMER, SEARCH_PAYMENT) via
 * hook_invoke_all so every matching provider answers. A lookup legitimately
 * has multiple answers (FA debtor, HRM employee, CRM contact, RBAC login);
 * taking only the first would hide the collision dedupe exists to catch.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-009-001 cross-module search
 * @BABOK Related: FR-ISU-018-002 SearchInvoker dispatch
 * @BABOK Related: NFR-ISU-009 Observer pattern
 */
class SearchInvoker
{
    /** @var MatchConfig|null */
    private $config;
    /** @var callable|null */
    private $invokeAll;

    /**
     * @param MatchConfig|null $config    Supplies the +/-N day window
     * @param callable|null    $invokeAll Defaults to FA's hook_invoke_all
     * @since 2.0.0
     * @BABOK Related: FR-ISU-018-002 constructor
     */
    public function __construct(MatchConfig $config = null, $invokeAll = null)
    {
        $this->config = $config !== null ? $config : new MatchConfig();
        $this->invokeAll = $invokeAll;
    }

    /**
     * Search for candidates across every active module.
     *
     * @param string     $capability e.g. 'SEARCH_CUSTOMER'
     * @param array      $criteria   Query terms (query/name/email/amount/reference...)
     * @param string|null $date      'Y-m-d' anchor for the two-sided window
     * @return array ['candidates'=>array, 'untagged'=>int, 'capability'=>string,
     *               'searched'=>bool, 'window'=>array|null]
     */
    /**
     * Search for candidates across every active module.
     *
     * @param string     $capability e.g. 'SEARCH_CUSTOMER'
     * @param array      $criteria   Query terms (query/name/email/amount/reference...)
     * @param string|null $date      'Y-m-d' anchor for the two-sided window
     * @return array ['candidates'=>array, 'untagged'=>int, 'capability'=>string,
     *               'searched'=>bool, 'window'=>array|null]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-018-002 search
     */
    public function search($capability, array $criteria, $date = null)
    {
        $empty = array(
            'candidates' => array(),
            'untagged' => 0,
            'capability' => $capability,
            'searched' => false,
            'window' => null,
        );

        $invoke = $this->invokeAll;
        if ($invoke === null) {
            if (!function_exists('hook_invoke_all')) {
                return $empty;
            }
            $invoke = function ($method, &$data) {
                return hook_invoke_all($method, $data);
            };
        }

        $request = $this->buildRequest($criteria, $date);

        $replies = $invoke($capability, $request);

        if (!is_array($replies)) {
            return $empty;
        }

        $candidates = array();
        $untagged = 0;

        foreach ($replies as $reply) {
            // hook_invoke_all also collects non-list replies, so tolerate a
            // responder that returns an envelope instead of a bare list.
            $rows = $this->extractRows($reply);
            if ($rows === null) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if (!isset($row['_module']) || $row['_module'] === '') {
                    // Merge stripped the provenance; flag rather than invent it.
                    $untagged++;
                    $row['_module'] = 'unknown';
                }

                $candidates[] = $row;
            }
        }

        $candidates = $this->dedupe($candidates);

        return array(
            'candidates' => $candidates,
            'untagged' => $untagged,
            'capability' => $capability,
            'searched' => true,
            'window' => $date === null
                ? null
                : array('from' => $request['date_from'], 'to' => $request['date_to']),
        );
    }

    /**
     * Build the request, adding the two-sided window and matching hints.
     *
     * @param array       $criteria
     * @param string|null $date
     * @return array
     */
    /**
     * @BABOK Related: FR-ISU-009
     */
    private function buildRequest(array $criteria, $date)
    {
        $request = $criteria;

        if ($date !== null && $date !== '') {
            $request['date_from'] = $this->config->windowStart($date);
            $request['date_to'] = $this->config->windowEnd($date);
            $request['window_days'] = $this->config->getWindowDays();
        }

        if (!isset($request['amount_tolerance']) && isset($criteria['amount'])) {
            $request['amount_tolerance'] = $this->config->getAmountTolerance();
        }

        // Ask providers for soundex-style name folding unless it was declined.
        if (!array_key_exists('soundex', $request)) {
            $request['soundex'] = $this->config->usesSoundex();
        }

        return $request;
    }

    /**
     * Pull a list of rows out of whatever shape a responder returned.
     *
     * @param mixed $reply
     * @return array|null
     */
    /**
     * @BABOK Related: FR-ISU-009
     */
    private function extractRows($reply)
    {
        if (!is_array($reply)) {
            return null;
        }

        if (isset($reply['results']) && is_array($reply['results'])) {
            return $reply['results'];
        }

        // A bare candidate row (a single hit) rather than a list.
        if (isset($reply['_module']) || isset($reply['fa_debtor_no']) || isset($reply['debtor_no'])) {
            return array($reply);
        }

        // Anything else: treat as a list only if it is not a plain map of
        // scalars, which would be a malformed reply.
        foreach ($reply as $value) {
            if (is_array($value)) {
                return $reply;
            }
        }

        return null;
    }

    /**
     * Remove rows describing the same underlying record found by two modules.
     *
     * The SAME person can legitimately appear twice -- an HRM employee who is
     * also an FA debtor -- and a reviewer should see that as one entity with two
     * sources rather than as two candidates to choose between. Rows are keyed on
     * module + entity + native id, so distinct records never collapse, and a
     * row without an id is never collapsed (its absence of identity is not
     * evidence of duplication).
     *
     * @param array $candidates
     * @return array
     */
    /**
     * @BABOK Related: FR-ISU-009
     */
    private function dedupe(array $candidates)
    {
        $seen = array();
        $out = array();

        foreach ($candidates as $candidate) {
            $entity = isset($candidate['_entity']) ? (string)$candidate['_entity'] : '';
            $id = null;
            foreach (array('fa_debtor_no', 'debtor_no', 'id', 'person_id', 'employee_id', 'fa_payment_no', 'invoice_no') as $key) {
                if (isset($candidate[$key]) && $candidate[$key] !== '' && $candidate[$key] !== 0) {
                    $id = $key . '=' . $candidate[$key];
                    break;
                }
            }

            if ($id === null) {
                $out[] = $candidate;
                continue;
            }

            $key = $candidate['_module'] . '|' . $entity . '|' . $id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $candidate;
        }

        return $out;
    }
}
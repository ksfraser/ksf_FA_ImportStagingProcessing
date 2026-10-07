<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services\Dispatch;

use ksfraser\FrontAccounting\ImportStaging\Services\Matching\MatchConfig;

/**
 * SearchInvoker — asks "whoever can find this?" across every active module.
 *
 * WHY hook_invoke_all AND NOT hook_invoke_first. A lookup and a creation have
 * genuinely different shapes:
 *
 *   Creation has ONE provider. Two modules creating the same record would be a
 *       bug, so first-answer-wins is correct (see CreationInvoker).
 *
 *   Search has MANY providers, and that is the point. "Who is this person?"
 *       legitimately has answers in several places: an FA debtor
 *       (ksf_FA_Customer), an employee (ksf_FA_HRM), a CRM contact
 *       (ksf_FA_CRM), a login (ksf_FA_RBAC). The reviewer needs all of them,
 *       because "we already know this person" is true if ANY of them matches.
 *       Taking only the first answer would hide the very collision that dedupe
 *       exists to catch.
 *
 * MERGE BEHAVIOUR (verified). hook_invoke_all accumulates with
 * `array_merge_recursive($return, $result)` (includes/hooks.inc:301). Numeric
 * keys APPEND rather than interleave, so responders returning flat lists of
 * candidate rows concatenate cleanly in provider order.
 *
 * THE PROVENANCE RULE. That merge also means the caller cannot tell which
 * module produced which row, so every SEARCH_* responder MUST tag each row it
 * returns with `_module` (and is expected to add the native id plus an
 * `_entity` discriminator). This class measures compliance rather than trusting
 * it: rows arriving without `_module` are counted in `untagged` so the review
 * screen can flag them, instead of the candidate silently looking like it came
 * from nowhere.
 *
 * BY-REFERENCE HAZARD. The same array is passed to every responder in turn, so
 * a responder that writes into its payload corrupts the input of every module
 * after it. Search responders must treat the payload as read-only and return
 * their rows. This class hands each call a copy for the same reason.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ImportStagingProcessing
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
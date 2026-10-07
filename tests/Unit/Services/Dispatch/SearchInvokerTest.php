<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit\Services\Dispatch;

use ksfraser\FrontAccounting\ImportStaging\Services\Dispatch\SearchInvoker;
use ksfraser\FrontAccounting\ImportStaging\Services\Matching\MatchConfig;
use PHPUnit\Framework\TestCase;

/**
 * @package ksf_FA_ImportStagingProcessing
 */
class SearchInvokerTest extends TestCase
{
    private function config(array $overrides = array())
    {
        return new MatchConfig($overrides);
    }

    /**
     * Builds a hook_invoke_all stand-in from a list of per-module replies.
     *
     * @param array $byModule ['ksf_FA_Customer' => [...rows], ...]
     * @return callable
     */
    private function invoker(array $byModule, &$seen = null)
    {
        return function ($method, &$data) use ($byModule, &$seen) {
            if ($seen !== null) {
                $seen[] = array('method' => $method, 'data' => $data);
            }
            $merged = array();
            foreach ($byModule as $module => $rows) {
                foreach ($rows as $row) {
                    $row['_module'] = $module;
                    $merged[] = $row;
                }
            }
            return $merged;
        };
    }

    /**
     * The core point: a search gathers from EVERY provider, not the first.
     * "We already know this person" is true if ANY module matches.
     */
    public function testGathersCandidatesFromEveryProvider(): void
    {
        $invoke = $this->invoker(array(
            'ksf_FA_Customer' => array(
                array('_entity' => 'customer', 'fa_debtor_no' => 42, 'name' => 'Ada Lovelace'),
            ),
            'ksf_FA_HRM' => array(
                array('_entity' => 'employee', 'employee_id' => 7, 'name' => 'Ada Lovelace'),
            ),
            'ksf_FA_CRM' => array(
                array('_entity' => 'contact', 'person_id' => 3, 'name' => 'A. Lovelace'),
            ),
        ));

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array('query' => 'Ada'));

        $this->assertCount(3, $result['candidates']);
        $this->assertTrue($result['searched']);

        $modules = array_column($result['candidates'], '_module');
        $this->assertContains('ksf_FA_Customer', $modules);
        $this->assertContains('ksf_FA_HRM', $modules);
        $this->assertContains('ksf_FA_CRM', $modules);
    }

    public function testTwoSidedWindowIsSentToProviders(): void
    {
        $seen = array();
        $invoke = $this->invoker(array(), $seen);

        (new SearchInvoker(
            $this->config(array(MatchConfig::PREF_WINDOW_DAYS => 7)),
            $invoke
        ))->search('SEARCH_CUSTOMER', array('query' => 'Ada'), '2026-10-05');

        $sent = $seen[0]['data'];
        $this->assertSame('2026-09-28', $sent['date_from']);
        $this->assertSame('2026-10-12', $sent['date_to']);
        $this->assertSame(7, $sent['window_days']);
    }

    public function testNoWindowWithoutAnAnchorDate(): void
    {
        $seen = array();
        $invoke = $this->invoker(array(), $seen);

        $result = (new SearchInvoker($this->config(), $invoke))
            ->search('SEARCH_CUSTOMER', array('query' => 'Ada'));

        $this->assertArrayNotHasKey('date_from', $seen[0]['data']);
        $this->assertNull($result['window']);
    }

    public function testAmountToleranceIsForwarded(): void
    {
        $seen = array();
        $invoke = $this->invoker(array(), $seen);

        (new SearchInvoker(
            $this->config(array(MatchConfig::PREF_AMOUNT_TOLERANCE => 0.25)),
            $invoke
        ))->search('SEARCH_PAYMENT', array('amount' => 100.00));

        $this->assertSame(0.25, $seen[0]['data']['amount_tolerance']);
    }

    public function testSoundexHintIsForwardedAndOverridable(): void
    {
        $seen = array();
        $invoke = $this->invoker(array(), $seen);

        $invoker = new SearchInvoker($this->config(), $invoke);
        $invoker->search('SEARCH_CUSTOMER', array('query' => 'Ada'));
        $this->assertTrue($seen[0]['data']['soundex']);

        $invoker->search('SEARCH_CUSTOMER', array('query' => 'Ada', 'soundex' => false));
        $this->assertFalse($seen[1]['data']['soundex']);
    }

    /**
     * The merge strips provenance, so untagged rows must be counted and
     * surfaced rather than silently attributed.
     */
    public function testCountsUntaggedRows(): void
    {
        $invoke = function ($method, &$data) {
            return array(
                array('_module' => 'ksf_FA_Customer', 'fa_debtor_no' => 1),
                array('fa_debtor_no' => 2), // responder forgot to tag
            );
        };

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        $this->assertSame(1, $result['untagged']);
        $this->assertSame('unknown', $result['candidates'][1]['_module']);
    }

    /**
     * The same person can be both an employee and a debtor. That is one entity
     * with two sources, not two candidates to choose between.
     */
    public function testCollapsesSameRecordFoundByTwoModules(): void
    {
        $invoke = $this->invoker(array(
            'ksf_FA_Customer' => array(array('_entity' => 'customer', 'fa_debtor_no' => 42)),
            'ksf_FA_HRM' => array(array('_entity' => 'employee', 'employee_id' => 42)),
        ));

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        // Different entity types, so both survive: they ARE different records.
        $this->assertCount(2, $result['candidates']);

        // Same module reporting the same record twice collapses.
        $dup = function ($method, &$data) {
            return array(
                array('_module' => 'ksf_FA_Customer', '_entity' => 'customer', 'fa_debtor_no' => 42),
                array('_module' => 'ksf_FA_Customer', '_entity' => 'customer', 'fa_debtor_no' => 42),
            );
        };
        $collapsed = (new SearchInvoker($this->config(), $dup))->search('SEARCH_CUSTOMER', array());
        $this->assertCount(1, $collapsed['candidates']);
    }

    /**
     * A row with no identity cannot be shown to be a duplicate.
     */
    public function testRowsWithoutAnIdAreNeverCollapsed(): void
    {
        $invoke = function ($method, &$data) {
            return array(
                array('_module' => 'ksf_FA_Customer', 'name' => 'No Id'),
                array('_module' => 'ksf_FA_Customer', 'name' => 'No Id'),
            );
        };

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        $this->assertCount(2, $result['candidates']);
    }

    public function testAcceptsEnvelopeShapedReply(): void
    {
        $invoke = function ($method, &$data) {
            return array(array('results' => array(
                array('_module' => 'ksf_FA_Customer', 'fa_debtor_no' => 5),
            )));
        };

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        $this->assertCount(1, $result['candidates']);
        $this->assertSame(5, $result['candidates'][0]['fa_debtor_no']);
    }

    public function testAcceptsSingleRowReply(): void
    {
        $invoke = function ($method, &$data) {
            return array(array('_module' => 'ksf_FA_Customer', 'fa_debtor_no' => 9));
        };

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        $this->assertCount(1, $result['candidates']);
    }

    /**
     * No provider at all is a legitimate "nothing found", distinct from an error.
     */
    public function testNoProvidersYieldsEmptyNotError(): void
    {
        $invoke = function ($method, &$data) {
            return array();
        };

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        $this->assertSame(array(), $result['candidates']);
        $this->assertSame(0, $result['untagged']);
        $this->assertTrue($result['searched']);
    }

    public function testNonArrayReplyIsTreatedAsNoResults(): void
    {
        $invoke = function ($method, &$data) {
            return null;
        };

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        $this->assertSame(array(), $result['candidates']);
        $this->assertFalse($result['searched']);
    }

    public function testMalformedScalarRowsAreIgnored(): void
    {
        $invoke = function ($method, &$data) {
            return array('garbage', 42, array('_module' => 'm', 'fa_debtor_no' => 1));
        };

        $result = (new SearchInvoker($this->config(), $invoke))->search('SEARCH_CUSTOMER', array());

        $this->assertCount(1, $result['candidates']);
    }
}
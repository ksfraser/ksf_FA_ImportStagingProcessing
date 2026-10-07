<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Unit\Services\Dispatch;

use ksfraser\FrontAccounting\ImportStaging\Services\Dispatch\CreationInvoker;
use PHPUnit\Framework\TestCase;

/**
 * @package ksf_FA_ImportStagingProcessing
 */
class CreationInvokerTest extends TestCase
{
    public function testSuccessfulCreationIsHandled(): void
    {
        $invoke = function ($method, &$data) {
            return array(
                'success' => true,
                'fa_debtor_no' => 42,
                '_module' => 'ksf_FA_Customer',
            );
        };

        $result = (new CreationInvoker($invoke))->create('CREATE_CUSTOMER', array('name' => 'Ada'));

        $this->assertTrue($result['handled']);
        $this->assertTrue($result['ok']);
        $this->assertSame(42, $result['response']['fa_debtor_no']);
        $this->assertSame('ksf_FA_Customer', $result['module']);
    }

    /**
     * A provider that declines returns null; the walk continues to the next.
     * Critically this must NOT be read as success.
     */
    public function testDeclinedIsNotSuccess(): void
    {
        $invoke = function ($method, &$data) {
            return null;
        };

        $result = (new CreationInvoker($invoke))->create('CREATE_CUSTOMER', array('name' => 'Ada'));

        $this->assertFalse($result['handled']);
        $this->assertFalse($result['ok']);
        $this->assertNull($result['response']);
        $this->assertStringContainsString('no active module', $result['error']);
    }

    /**
     * A provider that answered but reported failure is HANDLED, so the caller
     * can tell "CRM tried and the name was blank" from "nothing was installed".
     */
    public function testExplicitFailureIsHandledButNotOk(): void
    {
        $invoke = function ($method, &$data) {
            return array(
                'success' => false,
                'error' => 'name is required',
                '_module' => 'ksf_FA_Customer',
            );
        };

        $result = (new CreationInvoker($invoke))->create('CREATE_CUSTOMER', array());

        $this->assertTrue($result['handled']);
        $this->assertFalse($result['ok']);
        $this->assertSame('name is required', $result['response']['error']);
    }

    /**
     * A non-array reply is a contract violation, never a success.
     */
    public function testNonArrayReplyIsRejected(): void
    {
        $invoke = function ($method, &$data) {
            return 42;
        };

        $result = (new CreationInvoker($invoke))->create('CREATE_CUSTOMER', array());

        $this->assertFalse($result['handled']);
        $this->assertStringContainsString('rather than an array', $result['error']);
    }

    /**
     * The walk stops on the first non-null answer, which is FA's behaviour.
     * The module that answered is reported back so ISU can record it.
     */
    public function testStopsAtFirstAnswerAndReportsIt(): void
    {
        $calls = 0;
        $invoke = function ($method, &$data) use (&$calls) {
            $calls++;
            return array('success' => true, '_module' => 'ksf_suitecrm');
        };

        $result = (new CreationInvoker($invoke))->create('CREATE_CUSTOMER', array());

        $this->assertSame(1, $calls, 'the walk stops once a module answers');
        $this->assertSame('ksf_suitecrm', $result['module']);
    }

    /**
     * A responder that writes into the payload must not alter what the next
     * module in the walk receives.
     */
    public function testPayloadMutationDoesNotLeakToCallers(): void
    {
        $invoke = function ($method, &$data) {
            $data['injected'] = 'corrupted';
            return array('success' => true);
        };

        $payload = array('name' => 'Ada');
        (new CreationInvoker($invoke))->create('CREATE_CUSTOMER', $payload);

        $this->assertSame(array('name' => 'Ada'), $payload);
    }

    public function testCapabilityNameIsPassedThrough(): void
    {
        $seen = null;
        $invoke = function ($method, &$data) use (&$seen) {
            $seen = $method;
            return array('success' => true);
        };

        (new CreationInvoker($invoke))->create('CREATE_SALES_INVOICE', array());

        $this->assertSame('CREATE_SALES_INVOICE', $seen);
    }

    /**
     * Capability discovery: which modules ADVERTISE this capability.
     */
    public function testProvidersAreDiscovered(): void
    {
        $invoke = function ($method, &$data) {
            return array(
                array(
                    '_module' => 'ksf_FA_Customer',
                    'capabilities' => array(
                        'customer_create' => array('methods' => array('CREATE_CUSTOMER', 'GET_CUSTOMER')),
                    ),
                ),
                array(
                    '_module' => 'ksf_FA_Payment',
                    'capabilities' => array(
                        'payment_create' => array('methods' => array('CREATE_PAYMENT')),
                    ),
                ),
            );
        };

        $providers = (new CreationInvoker(null, $invoke))->providersOf('CREATE_CUSTOMER');

        $this->assertCount(1, $providers);
        $this->assertSame('ksf_FA_Customer', $providers[0]['module']);
        $this->assertContains('CREATE_CUSTOMER', $providers[0]['methods']);
    }

    /**
     * Two providers for one capability means the winner depends on registry
     * order. That must be visible, not silent.
     */
    public function testAmbiguousProviderIsDetected(): void
    {
        $invoke = function ($method, &$data) {
            return array(
                array('_module' => 'ksf_FA_Customer', 'capabilities' => array(
                    'customer_create' => array('methods' => array('CREATE_CUSTOMER')),
                )),
                array('_module' => 'ksf_suitecrm', 'capabilities' => array(
                    'migrate_create' => array('methods' => array('CREATE_CUSTOMER')),
                )),
            );
        };

        $invoker = new CreationInvoker(null, $invoke);

        $this->assertTrue($invoker->hasAmbiguousProvider('CREATE_CUSTOMER'));
        $this->assertFalse($invoker->hasAmbiguousProvider('CREATE_PAYMENT'));
    }

    public function testProviderDiscoveryIgnoresUntaggedReplies(): void
    {
        $invoke = function ($method, &$data) {
            return array(
                array('capabilities' => array('x' => array('methods' => array('CREATE_CUSTOMER')))),
            );
        };

        $this->assertSame(array(), (new CreationInvoker(null, $invoke))->providersOf('CREATE_CUSTOMER'));
    }

    public function testProviderDiscoverySurvivesNoHooks(): void
    {
        // Both callables omitted and no FA present: discovery must not fatal.
        $this->assertSame(array(), (new CreationInvoker())->providersOf('CREATE_CUSTOMER'));
    }
}
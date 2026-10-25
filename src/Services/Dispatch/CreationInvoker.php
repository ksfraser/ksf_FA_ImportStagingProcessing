<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services\Dispatch;

/**
 * CreationInvoker — asks "whoever can create this?" without naming a module.
 *
 * Dispatches creation capabilities (CREATE_CUSTOMER, CREATE_PAYMENT,
 * CREATE_SALES_INVOICE) via hook_invoke_first so exactly one provider answers.
 * ISU names the capability, not the module — a future data-migration module
 * can answer CREATE_CUSTOMER without ISU being edited.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-012-002 CREATE_CUSTOMER delegation
 * @BABOK Related: FR-ISU-013-001 CREATE_PAYMENT delegation
 * @BABOK Related: NFR-ISU-009 Factory pattern
 * @BABOK Related: NFR-ISU-007 traceability (capability, not module, named)
 */
class CreationInvoker
{
    /** @var callable|null */
    private $invokeFirst;
    /** @var callable|null */
    private $invokeAll;

    /**
     * @param callable|null $invokeFirst Defaults to FA's hook_invoke_first
     * @param callable|null $invokeAll   Defaults to FA's hook_invoke_all (discovery only)
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-002 constructor
     */
    public function __construct($invokeFirst = null, $invokeAll = null)
    {
        $this->invokeFirst = $invokeFirst;
        $this->invokeAll = $invokeAll;
    }

    /**
     * Invoke a creation capability and return the responder's reply.
     *
     * @param string $capability e.g. 'CREATE_CUSTOMER'
     * @param array  $payload
     * @return array ['handled'=>bool, 'response'=>array|null, 'module'=>string|null]
     */
    /**
     * Invoke a creation capability and return the responder's reply.
     *
     * @param string $capability e.g. 'CREATE_CUSTOMER'
     * @param array  $payload
     * @return array ['handled'=>bool, 'response'=>array|null, 'module'=>string|null]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-002 create
     */
    public function create($capability, array $payload)
    {
        // The payload crosses the boundary by reference, so hand over a copy:
        // a responder that writes into $payload must not be able to alter what
        // the next module in the walk receives.
        $request = $payload;

        $invoke = $this->invokeFirst;
        if ($invoke === null) {
            if (!function_exists('hook_invoke_first')) {
                return $this->unhandled($capability, 'hook_invoke_first() unavailable');
            }
            $invoke = function ($method, &$data) {
                return hook_invoke_first($method, $data);
            };
        }

        $response = $invoke($capability, $request);

        if (!is_array($response)) {
            // null => declined or no provider; anything else non-array is a
            // contract violation. Either way, nothing was created.
            return $this->unhandled(
                $capability,
                is_null($response)
                    ? 'no active module provided ' . $capability
                    : 'responder returned ' . gettype($response) . ' rather than an array'
            );
        }

        // An explicit failure reply is still a handled call -- the provider
        // answered, it just reported failure. Surface it verbatim.
        if (isset($response['success']) && !$response['success']) {
            return array(
                'handled' => true,
                'response' => $response,
                'module' => $this->moduleOf($response),
                'ok' => false,
            );
        }

        return array(
            'handled' => true,
            'response' => $response,
            'module' => $this->moduleOf($response),
            'ok' => true,
        );
    }

    /**
     * Which active modules advertise a capability.
     *
     * Uses the capability protocol every module is required to implement
     * (getModuleCapabilities). Advisory: it cannot see a module that declares
     * no capabilities, and it cannot be trusted to enforce the single-provider
     * rule -- it exists so the import review screen can show whether creation
     * is available before a reviewer tries it, and so a deployment with two
     * providers is visible rather than silent.
     *
     * @param string $capability
     * @return array List of ['module'=>string, 'methods'=>array]
     */
    /**
     * Which active modules advertise a capability (discovery via hook_invoke_all).
     *
     * @param string $capability
     * @return array List of ['module'=>string, 'methods'=>array]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-018-001 providersOf
     */
    public function providersOf($capability)
    {
        $invoke = $this->invokeAll;
        if ($invoke === null) {
            if (!function_exists('hook_invoke_all')) {
                return array();
            }
            $invoke = function ($method, &$data) {
                return hook_invoke_all($method, $data);
            };
        }

        $request = array('capability' => $capability);
        $replies = $invoke('getModuleCapabilities', $request);

        if (!is_array($replies)) {
            return array();
        }

        $providers = array();
        foreach ($replies as $reply) {
            if (!is_array($reply)) {
                continue;
            }

            $module = isset($reply['_module']) ? (string)$reply['_module'] : null;
            if ($module === null) {
                continue;
            }

            $capabilities = isset($reply['capabilities']) && is_array($reply['capabilities'])
                ? $reply['capabilities']
                : array();

            // Tolerate both shapes: ['payment_create'=>['methods'=>[...]]] and a
            // flat method list.
            $methods = array();
            foreach ($capabilities as $key => $value) {
                if (is_array($value) && isset($value['methods']) && is_array($value['methods'])) {
                    if ($key === $capability || in_array($capability, $value['methods'], true)) {
                        $methods = array_merge($methods, $value['methods']);
                    }
                } elseif ($value === $capability) {
                    $methods[] = $capability;
                }
            }

            if ($methods !== array()) {
                $providers[] = array('module' => $module, 'methods' => array_values(array_unique($methods)));
            }
        }

        return $providers;
    }

    /**
     * Whether more than one active module claims the capability.
     *
     * @param string $capability
     * @return bool
     */
    /**
     * Whether more than one active module claims the capability.
     *
     * @param string $capability
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-018-001 hasAmbiguousProvider
     */
    public function hasAmbiguousProvider($capability)
    {
        return count($this->providersOf($capability)) > 1;
    }

    /**
     * @param string $capability
     * @param string $reason
     * @return array
     */
    private function unhandled($capability, $reason)
    {
        return array(
            'handled' => false,
            'response' => null,
            'module' => null,
            'ok' => false,
            'error' => $reason,
        );
    }

    /**
     * Which module answered, per the tagging contract.
     *
     * @param array $response
     * @return string|null
     */
    private function moduleOf(array $response)
    {
        return isset($response['_module']) ? (string)$response['_module'] : null;
    }
}
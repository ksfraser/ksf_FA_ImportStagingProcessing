<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services\Dispatch;

/**
 * CreationInvoker — asks "whoever can create this?" without naming a module.
 *
 * WHY hook_invoke_first AND NOT hook_invoke. FA offers three dispatchers:
 *
 *   hook_invoke($ext, $method, ...)     one NAMED module; null if inactive
 *   hook_invoke_first($method, ...)     first active module to answer
 *   hook_invoke_all($method, ...)       every module, results merged
 *
 * `hook_invoke` hardcodes the owner, which would couple ISU to whichever module
 * happens to provide the capability today. That is wrong here: a future
 * `ksf_suitecrm` data-migration module is expected to be able to answer
 * CREATE_CUSTOMER instead, and ISU should not need editing when it does.
 *
 * `hook_invoke_first` is exactly the capability dispatch FA provides for this --
 * it walks the active modules and stops at the first non-null answer.
 *
 * DEPLOYMENT RULE. `hook_invoke_first` stops at the first responder in
 * `$installed_extensions` registry order, so two modules implementing the same
 * CREATE_* would make the winner dependent on registry ordering. Exactly one
 * module must provide each creation capability. That is a deployment
 * invariant, not something the call site can enforce, so
 * `providersOf()` below exists to audit it.
 *
 * DECLINE PROTOCOL. A responder that cannot handle the request must return
 * `null` so the walk continues. Returning `false` STOPS the walk, because
 * `isset(false)` is true -- see includes/hooks.inc:324.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ImportStagingProcessing
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
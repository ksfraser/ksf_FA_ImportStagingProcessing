<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Http;

/**
 * Centralized router for ksf_FA_ImportStagingProcessing.
 *
 * All module UI requests flow through ksf_import_staging.php?action=<name>.
 * The router performs:
 *   1. Route resolution (action name -> handler class)
 *   2. Centralized access validation (single security check before rendering)
 *   3. Help context resolution per action
 *
 * This eliminates the per-page access validation anti-pattern where each
 * pages/*.php file independently sets $page_security and includes session.inc.
 *
 * @since 2.1.0
 * @BABOK Related: FR-ISU-015-001 centralized router
 * @BABOK Related: NFR-ISU-004 page security
 * @BABOK Related: NFR-ISU-005 SRP route dispatch
 */
class ImportStagingRouter
{
    /** @var array<string, array{handler: string, security: string|null, help: string}> */
    private $routes = [
        'dashboard'     => ['handler' => DashboardAction::class,     'security' => 'SA_ksf_FA_ImportStagingProcessing',      'help' => 'Import Staging Dashboard'],
        'staging'       => ['handler' => StagingAction::class,       'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Staging Management'],
        'config'        => ['handler' => ConfigAction::class,        'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'ISU Configuration'],
        'matching'      => ['handler' => MatchingAction::class,      'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Transaction Matching'],
        'processing'    => ['handler' => ProcessingAction::class,    'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Processing Pipeline'],
        'customers'     => ['handler' => StagingAction::class,       'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Staged Customers'],
        'transactions'  => ['handler' => StagingAction::class,       'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Staged Transactions'],
        'payments'      => ['handler' => StagingAction::class,       'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Staged Payments'],
        'audit'         => ['handler' => StagingAction::class,       'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Processing Audit'],
        'mapping'       => ['handler' => ConfigAction::class,        'security' => 'SA_ksf_FA_ImportStagingProcessing_UI',   'help' => 'Field Mapping'],
    ];

    /**
     * Get the security area required for an action.
     *
     * @param string $action Action name (dashboard, staging, config, etc.)
     * @return string|null Security area constant or null for public actions
     */
    public function getSecurityAreaForAction(string $action): ?string
    {
        $route = $this->routes[$action] ?? $this->routes['dashboard'];
        return $route['security'];
    }

    /**
     * Get the help context for an action.
     *
     * @param string $action Action name
     * @return string Help context string
     */
    public function getHelpContextForAction(string $action): string
    {
        $route = $this->routes[$action] ?? $this->routes['dashboard'];
        return $route['help'];
    }

    /**
     * Resolve an action to its handler class name.
     *
     * @param string $action Action name
     * @return string Handler class FQCN
     */
    public function resolveHandler(string $action): string
    {
        $route = $this->routes[$action] ?? $this->routes['dashboard'];
        return $route['handler'];
    }

    /**
     * Check if an action is a known route.
     *
     * @param string $action Action name
     * @return bool True if the action is registered
     */
    public function hasRoute(string $action): bool
    {
        return isset($this->routes[$action]);
    }

    /**
     * Dispatch an action: instantiate the handler and call its run() method.
     *
     * The handler is responsible for rendering its content between
     * start_page()/end_page() which are called by the entry point.
     *
     * @param string $action Action name
     * @param array $request $_REQUEST merged with action-specific params
     * @return void
     * @throws \InvalidArgumentException if the action is unknown
     */
    public function dispatch(string $action, array $request = []): void
    {
        if (!$this->hasRoute($action)) {
            $action = 'dashboard';
        }

        $handlerClass = $this->resolveHandler($action);
        $handler = new $handlerClass($this);
        $handler->run($action, $request);
    }

    /**
     * Get all registered route names.
     *
     * @return string[]
     */
    public function getRoutes(): array
    {
        return array_keys($this->routes);
    }
}

<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Http\Actions;

use ksfraser\FrontAccounting\ImportStaging\Http\ImportStagingRouter;

/**
 * Base class for SRP action handlers.
 *
 * Each action handler is responsible for rendering its own content
 * between start_page()/end_page() (called by the entry point).
 *
 * Handlers receive the router so they can resolve sub-routes and
 * security areas as needed.
 *
 * @since 2.1.0
 * @BABOK Related: NFR-ISU-005 SRP action handlers
 */
abstract class BaseAction
{
    /** @var ImportStagingRouter */
    protected $router;

    public function __construct(ImportStagingRouter $router)
    {
        $this->router = $router;
    }

    /**
     * Run the action: render content for the given action/sub-action.
     *
     * @param string $action Top-level action name
     * @param array $request Request parameters ($_GET + $_POST merged)
     * @return void
     */
    abstract public function run(string $action, array $request): void;

    /**
     * Get a request parameter with a default.
     *
     * @param string $key Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    protected function param(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Get an integer request parameter.
     *
     * @param string $key Parameter name
     * @param int $default Default value
     * @return int
     */
    protected function intParam(string $key, int $default = 0): int
    {
        $val = $_REQUEST[$key] ?? $default;
        return (int)$val;
    }
}

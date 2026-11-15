<?php
declare(strict_types=1);

/**
 * Module entry point page for ksf_FA_ImportStagingProcessing.
 *
 * Centralized entry point: all module UI requests flow through
 * ksf_import_staging.php?action=<name>&sub_action=<name>.
 *
 * Bootstrap:
 *   1. Load Composer autoloader
 *   2. Set $page_security based on action
 *   3. Include FA session.inc
 *   4. Dispatch to ImportStagingRouter which validates access and renders
 *
 * No per-page access validation — the router validates once before routing.
 *
 * @since 2.1.0
 * @BABOK Related: FR-ISU-015-001 centralized router
 * @BABOK Related: NFR-ISU-004 page security
 * @BABOK Related: NFR-ISU-005 SRP route dispatch
 */
require_once __DIR__ . '/vendor/autoload.php';

use ksfraser\FrontAccounting\ImportStaging\Http\ImportStagingRouter;

$path_to_root = '../..';

// Resolve action (default: dashboard)
$action = $_REQUEST['action'] ?? 'dashboard';

// Build router and resolve security area before including session.inc
$router = new ImportStagingRouter();
$page_security = $router->getSecurityAreaForAction($action);

// Include FA session.inc (validates user, sets $page_security context)
include_once($path_to_root . '/includes/session.inc');

// Add module-specific access extensions (registers our security_areas)
add_access_extensions();

// Validate the user can access this action's security area
if (!isset($_SESSION["wa_current_user"]) || !$_SESSION["wa_current_user"]->can_access_page($page_security)) {
    display_error(_('The security settings on your account do not permit you to access this function.'));
    end_page();
    exit;
}

// Page header — FA 2.4.3 provides page(), not start_page(). Define start_page
// as a compatibility wrapper if it doesn't already exist.
if (!function_exists('start_page')) {
    function start_page($title, $no_menu = false, $is_index = false, $onload = "", $js = "", $css = '')
    {
        page($title, $no_menu, $is_index, $onload, $js, false, $css);
    }
}

$help_context = $router->getHelpContextForAction($action);
start_page($help_context);

// Dispatch to the SRP action handler — catch exceptions to avoid blank 500
try {
    $router->dispatch($action, $_REQUEST);
} catch (\Throwable $e) {
    display_error(_('ISP router error: ') . $e->getMessage());
}

// Page footer
end_page();

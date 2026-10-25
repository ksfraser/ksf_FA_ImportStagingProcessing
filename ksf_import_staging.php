<?php
declare(strict_types=1);

/**
 * Module entry point page for ksf_FA_ImportStagingProcessing.
 *
 * Sets up session, security area, and includes FA session.inc.
 *
 * @since 2.0.0
 * @BABOK Related: NFR-ISU-004 security ($page_security)
 * @BABOK Related: NFR-ISU-002 FA 2.4.x integration
 */
$path = dirname(__FILE__);
$autoload = $path . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$page_security = 'SA_ksf_FA_ImportStagingProcessing';
$path_to_root = __DIR__ . '/..';

include_once($path_to_root . '/includes/session.inc');

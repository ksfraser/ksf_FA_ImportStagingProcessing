<?php
$page_security = 'SA_ksf_FA_ImportStagingProcessing_UI';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

// Redirect legacy entry point to ISU staging
header('Location: ' . $path_to_root . '/modules/ksf_FA_ImportStagingProcessing_UI/process_statements.php');
exit;

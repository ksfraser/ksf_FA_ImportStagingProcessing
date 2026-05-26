<?php
declare(strict_types=1);

$path = dirname(__FILE__);
$autoload = $path . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$page_security = 'SA_ksf_FA_ImportStagingProcessing';
$path_to_root = __DIR__ . '/..';

include_once($path_to_root . '/includes/session.inc');

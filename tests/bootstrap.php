<?php
declare(strict_types=1);

$baseDir = __DIR__ . '/../';

// -----------------------------------------------------------------------
// Load FAMock — provides FA function stubs (db_query, constants, session)
// -----------------------------------------------------------------------
$famockPaths = [
    // Local vendor path (our cloned FAMock with hooks class)
    $baseDir . 'vendor/ksfraser/famock/php/FAMock.php',
    // Container path (FA dev environment, older version)
    '/home/kevin/.local/share/containers/storage/overlay/ef24ed184dcad6ec9bb2f29b490ac4114f491238c6c7938666448ee84527ac51/diff/var/www/html/modules/ksf_FA_Assets/vendor/ksfraser/famock/php/FAMock.php',
];
foreach ($famockPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

// Ensure hooks class is defined (FAMock should provide it, but define a minimal stub if not)
if (!class_exists('hooks')) {
    class hooks
    {
        public $module_name = '';

        public function install_tables() { return true; }
        public function install_access() { return true; }
        public function activate_extension($company, $check_only = true) { return true; }
        public function deactivate_extension($company) { return true; }
    }
}

// -----------------------------------------------------------------------
// Load hooks.php for integration tests (requires hooks base class from FAMock)
// -----------------------------------------------------------------------
require_once dirname(__DIR__, 1) . '/hooks.php';

// -----------------------------------------------------------------------
// Define FA hooks function not provided by FAMock
// -----------------------------------------------------------------------
if (!function_exists('hook_invoke_first')) {
    function hook_invoke_first($event, &$data, $opts = null) {
        return null; // No modules installed during testing
    }
}
if (!function_exists('hook_invoke_all')) {
    function hook_invoke_all($event, &$data, $opts = null) {
        return null;
    }
}
if (!function_exists('db_num_rows')) {
    function db_num_rows($result) {
        return is_object($result) && method_exists($result, 'count')
            ? $result->count()
            : (is_array($result) ? count($result) : 0);
    }
}
if (!function_exists('db_num_affected_rows')) {
    function db_num_affected_rows() {
        return db_affected_rows();
    }
}

// -----------------------------------------------------------------------
// Define ksf_ModulesDAO stub for integration tests
// -----------------------------------------------------------------------
if (!class_exists('ksf_ModulesDAO')) {
    class ksf_ModulesDAO
    {
        public function query($sql, $errorMsg = null) { return true; }
    }
}

// -----------------------------------------------------------------------
// PSR-4 autoloader for the main project source
// -----------------------------------------------------------------------
spl_autoload_register(function ($class) use ($baseDir) {
    $prefixes = [
        'Ksfraser\\ImportStaging\\' => $baseDir . 'src/',
    ];
    foreach ($prefixes as $prefix => $srcDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $file = $srcDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        }
    }
});

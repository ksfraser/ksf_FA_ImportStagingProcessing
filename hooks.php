<?php
declare(strict_types=1);

define('SS_ksf_FA_ImportStagingProcessing', 109 << 8);

define('KSF_IMPORT_STAGING_MODULE_NAME', 'ksf_FA_ImportStagingProcessing');
define('KSF_IMPORT_STAGING_CAPABILITIES', 'staging,matching,mapping,audit');

class hooks_ksf_FA_ImportStagingProcessing extends hooks
{
    function __construct()
    {
        $this->module_name = 'ksf_FA_ImportStagingProcessing';
    }

    public function getModuleConstants(&$data, $opts = null)
    {
        $constants = [
            'KSF_IMPORT_STAGING_MODULE_NAME' => KSF_IMPORT_STAGING_MODULE_NAME,
            'KSF_IMPORT_STAGING_CAPABILITIES' => KSF_IMPORT_STAGING_CAPABILITIES,
        ];
        $data['constants'] = $constants;
        return $constants;
    }

    public function getModuleCapabilities(&$data, $opts = null)
    {
        $capabilities = [
            'staging' => [
                'description' => 'Stage customers and transactions from any source',
                'methods' => ['stageCustomer', 'stageTransaction', 'getStagedCustomers', 'getStagedTransactions', 'updateStatus'],
            ],
            'matching' => [
                'description' => 'Match and process staged transactions',
                'methods' => ['matchTransactions', 'processQueue', 'approveMatch', 'rejectMatch'],
            ],
            'mapping' => [
                'description' => 'Manage field mapping configuration',
                'methods' => ['getMappings', 'saveMapping', 'getMapping'],
            ],
            'audit' => [
                'description' => 'Access processing audit trail',
                'methods' => ['getLog', 'getLogByRecord', 'getLogByAction'],
            ],
        ];
        $data['capabilities'] = $capabilities;
        return $capabilities;
    }

    public function hasCapability(&$data, $opts = null)
    {
        $capability = $opts['capability'] ?? $data['capability'] ?? null;
        if ($capability === null) {
            $data['has_capability'] = false;
            $data['error'] = 'No capability specified';
            return false;
        }
        $capabilities = ['staging', 'matching', 'mapping', 'audit'];
        $hasCapability = in_array($capability, $capabilities);
        $data['has_capability'] = $hasCapability;
        $data['capability_checked'] = $capability;
        return $hasCapability;
    }

    public function respondToCapabilityRequest(&$data, $opts = null)
    {
        $request = $opts['request'] ?? $data['request'] ?? 'capabilities';
        $data['request'] = $request;
        $data['module'] = $this->module_name;

        if (strpos($request, 'staging:') === 0) {
            return $this->handleStagingRequest(substr($request, 8), $data, $opts);
        }
        if (strpos($request, 'matching:') === 0) {
            return $this->handleMatchingRequest(substr($request, 9), $data, $opts);
        }
        if (strpos($request, 'mapping:') === 0) {
            return $this->handleMappingRequest(substr($request, 8), $data, $opts);
        }
        if (strpos($request, 'audit:') === 0) {
            return $this->handleAuditRequest(substr($request, 6), $data, $opts);
        }

        switch ($request) {
            case 'capabilities':
                return $this->getModuleCapabilities($data, $opts);
            case 'constants':
                return $this->getModuleConstants($data, $opts);
            case (strpos($request, 'has:') === 0):
                $capability = substr($request, 4);
                return $this->hasCapability($data, ['capability' => $capability]);
            default:
                $data['error'] = 'Unknown request type: ' . $request;
                return null;
        }
    }

    private function handleStagingRequest($action, &$data, $opts)
    {
        $service = $this->getStagingService();
        switch ($action) {
            case 'stageCustomer':
                $source = $opts['source'] ?? $data['source'] ?? '';
                $customerData = $opts['customer'] ?? $data['customer'] ?? [];
                try {
                    $result = $service->stageCustomer($customerData, $source);
                    $data['result'] = $result->toArray();
                    $data['success'] = true;
                } catch (\Exception $e) {
                    $data['error'] = $e->getMessage();
                    $data['success'] = false;
                }
                return $data['result'] ?? null;
            case 'stageTransaction':
                $source = $opts['source'] ?? $data['source'] ?? '';
                $txnData = $opts['transaction'] ?? $data['transaction'] ?? [];
                try {
                    $result = $service->stageTransaction($txnData, $source);
                    $data['result'] = $result->toArray();
                    $data['success'] = true;
                } catch (\Exception $e) {
                    $data['error'] = $e->getMessage();
                    $data['success'] = false;
                }
                return $data['result'] ?? null;
            case 'getStagedCustomers':
                $filters = $opts['filters'] ?? $data['filters'] ?? [];
                $data['result'] = array_map(fn($c) => $c->toArray(), $service->getStagedCustomers($filters));
                return $data['result'];
            case 'getStagedTransactions':
                $filters = $opts['filters'] ?? $data['filters'] ?? [];
                $data['result'] = array_map(fn($t) => $t->toArray(), $service->getStagedTransactions($filters));
                return $data['result'];
            case 'updateStatus':
                $id = (int)($opts['id'] ?? $data['id'] ?? 0);
                $status = $opts['status'] ?? $data['status'] ?? '';
                $error = $opts['error'] ?? $data['error'] ?? null;
                $service->updateStatus($id, $status, $error);
                $data['success'] = true;
                return true;
            default:
                $data['error'] = 'Unknown staging action: ' . $action;
                return null;
        }
    }

    private function handleMatchingRequest($action, &$data, $opts)
    {
        switch ($action) {
            case 'processQueue':
                $source = $opts['source'] ?? $data['source'] ?? null;
                $service = $this->getStagingService();
                $result = $service->processQueue($source);
                $data['result'] = [
                    'success' => $result->isSuccess(),
                    'record_id' => $result->getRecordId(),
                    'action' => $result->getAction(),
                    'errors' => $result->getErrors(),
                ];
                $data['success'] = $result->isSuccess();
                return $data['result'];
            default:
                $data['error'] = 'Unknown matching action: ' . $action;
                return null;
        }
    }

    private function handleMappingRequest($action, &$data, $opts)
    {
        $data['error'] = 'Mapping actions not yet implemented in hooks handler: ' . $action;
        return null;
    }

    private function handleAuditRequest($action, &$data, $opts)
    {
        $data['error'] = 'Audit actions not yet implemented in hooks handler: ' . $action;
        return null;
    }

    private function getStagingService()
    {
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();
        $customerDAO = new \Ksfraser\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
        $transactionDAO = new \Ksfraser\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
        $logDAO = new \Ksfraser\ImportStaging\DAO\StagingLogDAO($tablePrefix, $db);
        $txnValidator = new \Ksfraser\ImportStaging\Validators\TransactionValidator();
        $custValidator = new \Ksfraser\ImportStaging\Validators\CustomerValidator();
        $matchingService = new \Ksfraser\ImportStaging\Services\MatchingService();
        return new \Ksfraser\ImportStaging\Services\StagingService(
            $customerDAO, $transactionDAO, $logDAO, $txnValidator, $custValidator, $matchingService
        );
    }

    function install_tabs($app)
    {
    }

    function install_access()
    {
        $security_sections[SS_ksf_FA_ImportStagingProcessing] = _("Import Staging");
        $security_areas['SA_ksf_FA_ImportStagingProcessing'] = array(
            SS_ksf_FA_ImportStagingProcessing | 108, _("Import Staging Processing")
        );
        $security_areas['SA_ksf_FA_ImportStagingProcessingVIEW'] = array(
            SS_ksf_FA_ImportStagingProcessing | 1, _("View Import Staging")
        );
        $security_areas['SA_ksf_FA_ImportStagingProcessingMANAGE'] = array(
            SS_ksf_FA_ImportStagingProcessing | 2, _("Manage Import Staging")
        );
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only = true)
    {
        if (file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            $updates = array('install.sql' => array($this->module_name));
            return $this->update_databases($company, $updates, $check_only);
        }
        try {
            $this->ensure_composer_dependencies();
        } catch (\Exception $e) {
        }
        return true;
    }

    function install_options($app)
    {
        global $path_to_root;
    }

    private function ensure_composer_dependencies()
    {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        if (file_exists($autoload_path)) {
            return;
        }
        $composer_path = $module_dir . '/composer.json';
        if (!file_exists($composer_path)) {
            return;
        }
        chdir($module_dir);
        $output = array();
        $return_code = 0;
        exec('composer install --no-interaction --prefer-dist --ignore-platform-req=php 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            error_log('KSF Module: composer install failed: ' . implode("\n", $output));
        }
    }
}

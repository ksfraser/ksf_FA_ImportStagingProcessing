<?php
declare(strict_types=1);

define('SS_ksf_FA_ImportStagingProcessing', 109 << 8);

define('KSF_IMPORT_STAGING_MODULE_NAME', 'ksf_FA_ImportStagingProcessing');
define('KSF_IMPORT_STAGING_CAPABILITIES', 'staging,matching,mapping,audit,reconciliation');

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
                'description' => 'Stage customers, transactions, and payments from any source',
                'methods' => ['stageCustomer', 'stageTransaction', 'stagePayment', 'getStagedCustomers', 'getStagedTransactions', 'getStagedPayments', 'updateStatus'],
                'events' => ['STAGE_CUSTOMER', 'STAGE_TRANSACTION', 'STAGE_PAYMENT'],
            ],
            'matching' => [
                'description' => 'Match and process staged transactions',
                'methods' => ['matchTransactions', 'processQueue', 'approveMatch', 'rejectMatch'],
            ],
            'reconciliation' => [
                'description' => 'Reconcile staged payments against FA bank/debtor transactions',
                'methods' => ['reconcilePayment', 'reconcilePaymentQueue', 'getStagedPayments', 'getPaymentMatchHistory', 'getPaymentStatusCounts'],
            ],
            'mapping' => [
                'description' => 'Manage field mapping configuration',
                'methods' => ['getMappings', 'saveMapping', 'getMapping'],
            ],
            'processing' => [
                'description' => 'Process staged records into FA via PROCESS_STAGING event (creates customers, processes payments)',
                'methods' => ['processStaging', 'processCustomer', 'processPayment', 'getProcessedIds'],
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
        $capabilities = ['staging', 'matching', 'mapping', 'audit', 'reconciliation', 'processing'];
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
        if (strpos($request, 'reconciliation:') === 0) {
            return $this->handleReconciliationRequest(substr($request, 15), $data, $opts);
        }
        if (strpos($request, 'processing:') === 0) {
            return $this->handleProcessingRequest(substr($request, 11), $data, $opts);
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
                if (!$this->authorizeAction('create', 'staging_customer')) {
                    $data['error'] = 'Unauthorized: insufficient permissions to stage customers';
                    $data['success'] = false;
                    return null;
                }
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
                if (!$this->authorizeAction('create', 'staging_transaction')) {
                    $data['error'] = 'Unauthorized: insufficient permissions to stage transactions';
                    $data['success'] = false;
                    return null;
                }
                try {
                    $result = $service->stageTransaction($txnData, $source);
                    $data['result'] = $result->toArray();
                    $data['success'] = true;
                } catch (\Exception $e) {
                    $data['error'] = $e->getMessage();
                    $data['success'] = false;
                }
                return $data['result'] ?? null;
            case 'stagePayment':
                $source = $opts['source'] ?? $data['source'] ?? '';
                $paymentData = $opts['payment'] ?? $data['payment'] ?? [];
                $stagingTransactionId = isset($opts['staging_transaction_id'])
                    ? (int)$opts['staging_transaction_id']
                    : (isset($data['staging_transaction_id']) ? (int)$data['staging_transaction_id'] : null);
                if (!$this->authorizeAction('create', 'staging_payment')) {
                    $data['error'] = 'Unauthorized: insufficient permissions to stage payments';
                    $data['success'] = false;
                    return null;
                }
                try {
                    $result = $service->stagePayment($paymentData, $source, $stagingTransactionId);
                    $data['result'] = $result->toArray();
                    $data['success'] = true;
                } catch (\Exception $e) {
                    $data['error'] = $e->getMessage();
                    $data['success'] = false;
                }
                return $data['result'] ?? null;
            case 'stageOrUpdatePayment':
                $source = $opts['source'] ?? $data['source'] ?? '';
                $paymentData = $opts['payment'] ?? $data['payment'] ?? [];
                $stagingTransactionId = isset($opts['staging_transaction_id'])
                    ? (int)$opts['staging_transaction_id']
                    : (isset($data['staging_transaction_id']) ? (int)$data['staging_transaction_id'] : null);
                if (!$this->authorizeAction('create', 'staging_payment')) {
                    $data['error'] = 'Unauthorized: insufficient permissions to stage payments';
                    $data['success'] = false;
                    return null;
                }
                try {
                    $result = $service->stageOrUpdatePayment($paymentData, $source, $stagingTransactionId);
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
            case 'getStagedPayments':
                $filters = $opts['filters'] ?? $data['filters'] ?? [];
                $data['result'] = array_map(fn($p) => $p->toArray(), $service->getStagedPayments($filters));
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

    private function handleReconciliationRequest($action, &$data, $opts)
    {
        $service = $this->getStagingService();
        switch ($action) {
            case 'reconcilePayment':
                $paymentId = (int)($opts['payment_id'] ?? $data['payment_id'] ?? 0);
                $faRecord = $opts['fa_record'] ?? $data['fa_record'] ?? [];
                $result = $service->reconcilePayment($paymentId, $faRecord);
                $data['result'] = [
                    'success' => $result->isSuccess(),
                    'record_id' => $result->getRecordId(),
                    'action' => $result->getAction(),
                    'errors' => $result->getErrors(),
                ];
                $data['success'] = $result->isSuccess();
                return $data['result'];
            case 'reconcilePaymentQueue':
                $source = $opts['source'] ?? $data['source'] ?? null;
                $result = $service->reconcilePaymentQueue($source);
                $data['result'] = [
                    'success' => $result->isSuccess(),
                    'record_id' => $result->getRecordId(),
                    'action' => $result->getAction(),
                    'errors' => $result->getErrors(),
                ];
                $data['success'] = $result->isSuccess();
                return $data['result'];
            case 'getStagedPayments':
                $filters = $opts['filters'] ?? $data['filters'] ?? [];
                $data['result'] = array_map(
                    fn($p) => $p->toArray(),
                    $service->getStagedPayments($filters)
                );
                return $data['result'];
            case 'getPaymentMatchHistory':
                $paymentId = (int)($opts['payment_id'] ?? $data['payment_id'] ?? 0);
                $data['result'] = array_map(
                    fn($m) => $m->toArray(),
                    $service->getPaymentMatchHistory($paymentId)
                );
                return $data['result'];
            case 'getPaymentStatusCounts':
                $source = $opts['source'] ?? $data['source'] ?? null;
                $data['result'] = $service->getPaymentStatusCounts($source);
                return $data['result'];
            default:
                $data['error'] = 'Unknown reconciliation action: ' . $action;
                return null;
        }
    }

    /**
     * PROCESS_STAGING — Event handler invoked via hook_invoke_first()
     *
     * Processes all staged customers, transactions, and payments into FA.
     * Delegates to ksf_FA_Customer and ksf_FA_Payment modules via
     * CREATE_CUSTOMER and CREATE_PAYMENT events.
     *
     * Called by:
     *   $data = ['source' => 'woocommerce'];  // optional filter
     *   $result = hook_invoke_first('PROCESS_STAGING', $data);
     *
     * @param array &$data Optional source filter
     * @param array|null $opts Options
     * @return array Result summary
     */
    public function PROCESS_STAGING(&$data, $opts = null)
    {
        if (!$this->authorizeAction('create', 'staging_record')) {
            $data['error'] = 'Unauthorized: insufficient permissions to process staging records';
            $data['success'] = false;
            return null;
        }

        try {
            $pipeline = $this->getProcessingPipeline();
            $source = $opts['source'] ?? $data['source'] ?? null;
            $result = $pipeline->processAll($source);
            $data['result'] = [
                'success' => $result->isSuccess(),
                'record_id' => $result->getRecordId(),
                'action' => $result->getAction(),
                'errors' => $result->getErrors(),
                'processed_ids' => $pipeline->getProcessedIds(),
            ];
            $data['success'] = $result->isSuccess();
            return $data['result'];
        } catch (\Exception $e) {
            error_log('ksf_FA_ImportStagingProcessing: PROCESS_STAGING failed: ' . $e->getMessage());
            $data['error'] = $e->getMessage();
            $data['success'] = false;
            return null;
        }
    }

    /**
     * STAGE_CUSTOMER — Event handler invoked via hook_invoke_first()
     *
     * Stages a customer record into the staging table for later processing.
     *
     * Called by:
     *   $data = ['source' => 'woocommerce', 'customer' => ['name' => '...', 'email' => '...']];
     *   $result = hook_invoke_first('STAGE_CUSTOMER', $data);
     *
     * @param array &$data Must contain 'source' and 'customer' keys
     * @param array|null $opts Options
     * @return array|null Serialized StagingCustomer or null on failure
     */
    public function STAGE_CUSTOMER(&$data, $opts = null)
    {
        $source = $opts['source'] ?? $data['source'] ?? '';
        $customerData = $opts['customer'] ?? $data['customer'] ?? [];

        if (!$this->authorizeAction('create', 'staging_customer')) {
            $data['error'] = 'Unauthorized';
            $data['success'] = false;
            return null;
        }

        try {
            $service = $this->getStagingService();
            $result = $service->stageCustomer($customerData, $source);
            $arr = $result->toArray();
            $arr['_event'] = 'CUSTOMER_STAGED';
            $arr['_module'] = $this->module_name;
            $data['result'] = $arr;
            $data['success'] = true;
            return $arr;
        } catch (\Exception $e) {
            error_log('STAGE_CUSTOMER failed: ' . $e->getMessage());
            $data['error'] = $e->getMessage();
            $data['success'] = false;
            return null;
        }
    }

    /**
     * STAGE_TRANSACTION — Event handler invoked via hook_invoke_first()
     *
     * Stages a transaction record into the staging table for later processing.
     *
     * Called by:
     *   $data = ['source' => 'square_api', 'transaction' => ['total_amount' => 100.00, ...]];
     *   $result = hook_invoke_first('STAGE_TRANSACTION', $data);
     *
     * @param array &$data Must contain 'source' and 'transaction' keys
     * @param array|null $opts Options
     * @return array|null Serialized StagingTransaction or null on failure
     */
    public function STAGE_TRANSACTION(&$data, $opts = null)
    {
        $source = $opts['source'] ?? $data['source'] ?? '';
        $txnData = $opts['transaction'] ?? $data['transaction'] ?? [];

        if (!$this->authorizeAction('create', 'staging_transaction')) {
            $data['error'] = 'Unauthorized';
            $data['success'] = false;
            return null;
        }

        try {
            $service = $this->getStagingService();
            $result = $service->stageTransaction($txnData, $source);
            $arr = $result->toArray();
            $arr['_event'] = 'TRANSACTION_STAGED';
            $arr['_module'] = $this->module_name;
            $data['result'] = $arr;
            $data['success'] = true;
            return $arr;
        } catch (\Exception $e) {
            error_log('STAGE_TRANSACTION failed: ' . $e->getMessage());
            $data['error'] = $e->getMessage();
            $data['success'] = false;
            return null;
        }
    }

    /**
     * STAGE_PAYMENT — Event handler invoked via hook_invoke_first()
     *
     * Stages a payment record into the staging table for later reconciliation
     * and processing.
     *
     * Called by:
     *   $data = ['source' => 'paypal', 'payment' => ['amount' => 50.00, ...]];
     *   $result = hook_invoke_first('STAGE_PAYMENT', $data);
     *
     * @param array &$data Must contain 'source' and 'payment' keys
     * @param array|null $opts Options
     * @return array|null Serialized StagingPayment or null on failure
     */
    public function STAGE_PAYMENT(&$data, $opts = null)
    {
        $source = $opts['source'] ?? $data['source'] ?? '';
        $paymentData = $opts['payment'] ?? $data['payment'] ?? [];
        $stagingTransactionId = isset($opts['staging_transaction_id'])
            ? (int) $opts['staging_transaction_id']
            : (isset($data['staging_transaction_id']) ? (int) $data['staging_transaction_id'] : null);

        if (!$this->authorizeAction('create', 'staging_payment')) {
            $data['error'] = 'Unauthorized';
            $data['success'] = false;
            return null;
        }

        try {
            $service = $this->getStagingService();
            $result = $service->stagePayment($paymentData, $source, $stagingTransactionId);
            $arr = $result->toArray();
            $arr['_event'] = 'PAYMENT_STAGED';
            $arr['_module'] = $this->module_name;
            $data['result'] = $arr;
            $data['success'] = true;
            return $arr;
        } catch (\Exception $e) {
            error_log('STAGE_PAYMENT failed: ' . $e->getMessage());
            $data['error'] = $e->getMessage();
            $data['success'] = false;
            return null;
        }
    }

    private function handleProcessingRequest($action, &$data, $opts)
    {
        switch ($action) {
            case 'processStaging':
                return $this->PROCESS_STAGING($data, $opts);
            case 'processAll':
                $pipeline = $this->getProcessingPipeline();
                $source = $opts['source'] ?? $data['source'] ?? null;
                $result = $pipeline->processAll($source);
                $data['result'] = [
                    'success' => $result->isSuccess(),
                    'record_id' => $result->getRecordId(),
                    'action' => $result->getAction(),
                    'errors' => $result->getErrors(),
                    'processed_ids' => $pipeline->getProcessedIds(),
                ];
                $data['success'] = $result->isSuccess();
                return $data['result'];
            case 'getProcessedIds':
                $data['result'] = [];
                return $data['result'];
            default:
                $data['error'] = 'Unknown processing action: ' . $action;
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
        $paymentDAO = new \Ksfraser\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
        $paymentMatchDAO = new \Ksfraser\ImportStaging\DAO\StagingPaymentMatchDAO($tablePrefix, $db);
        $lineItemDAO = new \Ksfraser\ImportStaging\DAO\StagingLineItemDAO($tablePrefix, $db);
        $logDAO = new \Ksfraser\ImportStaging\DAO\StagingLogDAO($tablePrefix, $db);
        $txnValidator = new \Ksfraser\ImportStaging\Validators\TransactionValidator();
        $custValidator = new \Ksfraser\ImportStaging\Validators\CustomerValidator();
        $paymentValidator = new \Ksfraser\ImportStaging\Validators\PaymentValidator();
        $matchingService = new \Ksfraser\ImportStaging\Services\MatchingService();
        return new \Ksfraser\ImportStaging\Services\StagingService(
            $customerDAO, $transactionDAO, $paymentDAO, $paymentMatchDAO,
            $lineItemDAO, $logDAO, $txnValidator, $custValidator, $paymentValidator, $matchingService
        );
    }

    private function getProcessingPipeline()
    {
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();
        $customerDAO = new \Ksfraser\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
        $transactionDAO = new \Ksfraser\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
        $paymentDAO = new \Ksfraser\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
        $lineItemDAO = new \Ksfraser\ImportStaging\DAO\StagingLineItemDAO($tablePrefix, $db);
        $logDAO = new \Ksfraser\ImportStaging\DAO\StagingLogDAO($tablePrefix, $db);
        return new \Ksfraser\ImportStaging\Services\ProcessingPipeline(
            $customerDAO, $transactionDAO, $paymentDAO, $lineItemDAO, $logDAO
        );
    }

    /**
     * Check authorization via hook_invoke_first('authorize').
     *
     * Calls the ksf_FA_RBAC authorize event if available. Returns true
     * (allow) when no RBAC module is installed or no user context exists.
     */
    private function authorizeAction(
        string $action,
        string $resourceType = 'staging_record',
        ?int $resourceId = null
    ): bool {
        if (!function_exists('hook_invoke_first')) {
            return true;
        }

        $userId = null;
        if (isset($_SESSION) && isset($_SESSION['wa_current_user']->user)) {
            $userId = (int) $_SESSION['wa_current_user']->user;
        }

        if ($userId === null) {
            return true;
        }

        $authData = [
            'user_id'       => $userId,
            'action'        => $action,
            'module'        => 'staging',
            'resource_type' => $resourceType,
        ];
        if ($resourceId !== null) {
            $authData['resource_id'] = $resourceId;
        }

        $result = hook_invoke_first('authorize', $authData);
        return $result !== false;
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

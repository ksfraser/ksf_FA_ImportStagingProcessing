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
                'methods' => ['stageCustomer', 'stageTransaction', 'stagePayment', 'stageEntity', 'stagingExists', 'getStagedCustomers', 'getStagedTransactions', 'getStagedPayments', 'getById', 'delete', 'getStatusCounts', 'updateFields', 'updateStatus', 'getItemsByTransaction', 'deleteLineItemsByTransaction'],
                'events' => ['STAGE_CUSTOMER', 'STAGE_TRANSACTION', 'STAGE_PAYMENT', 'STAGE_ENTITY', 'STAGING_EXISTS'],
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
            case 'getById':
                $id = (int)($opts['id'] ?? $data['id'] ?? 0);
                $entityType = $opts['entity_type'] ?? $data['entity_type'] ?? 'transaction';
                $result = $this->getStagingById($id, $entityType, $service);
                $data['result'] = $result;
                return $result;
            case 'delete':
                $id = (int)($opts['id'] ?? $data['id'] ?? 0);
                $entityType = $opts['entity_type'] ?? $data['entity_type'] ?? 'transaction';
                $this->deleteStagingRecord($id, $entityType, $service);
                $data['success'] = true;
                return true;
            case 'getStatusCounts':
                $source = $opts['source'] ?? $data['source'] ?? null;
                $env = $opts['environment'] ?? $data['environment'] ?? null;
                $result = $this->getStatusCountsForSource($source, $env, $service);
                $data['result'] = $result;
                return $result;
            case 'updateFields':
                $id = (int)($opts['id'] ?? $data['id'] ?? 0);
                $fields = $opts['fields'] ?? $data['fields'] ?? [];
                $entityType = $opts['entity_type'] ?? $data['entity_type'] ?? 'transaction';
                $this->updateStagingFields($id, $fields, $entityType, $service);
                $data['success'] = true;
                return true;
            case 'getItemsByTransaction':
                $stagingId = (int)($opts['staging_id'] ?? $data['staging_id'] ?? 0);
                $result = $this->getItemsByTransactionId($stagingId, $service);
                $data['result'] = $result;
                return $result;
            case 'deleteLineItemsByTransaction':
                $stagingId = (int)($opts['staging_id'] ?? $data['staging_id'] ?? 0);
                $service->deleteLineItemsByTransaction($stagingId);
                $data['success'] = true;
                return true;
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

    /**
     * STAGE_ENTITY — Generic hook for staging any DTO subclass.
     *
     * Accepts ksfraser/staging-dto DTOs and routes to appropriate staging method.
     * Replaces type-specific STAGE_* events with one polymorphic entry point.
     *
     * Called by:
     *   $dto = new StagingOrder('square', 'sq_123', 100.00, 'USD', 'completed', 'card');
     *   $result = hook_invoke('ksf_FA_ImportStagingProcessing_UI', 'stageEntity', $dto);
     *
     * @param mixed &$data The DTO object (passed by reference for hook compatibility)
     * @param array|null $opts Options
     * @return array|null StagingExistsResult as array, or null on failure
     */
    public function STAGE_ENTITY(&$data, $opts = null)
    {
        if (!$data instanceof \Ksfraser\StagingDto\StagingEntity) {
            $data['error'] = 'stageEntity requires a StagingEntity DTO instance';
            $data['success'] = false;
            return null;
        }

        if (!$this->authorizeAction('create', 'staging_' . (new \ReflectionClass($data))->getShortName())) {
            $data['error'] = 'Unauthorized';
            $data['success'] = false;
            return null;
        }

        try {
            $adapter = $this->getDtoAdapter();
            $result = $adapter->stageEntity($data);
            $arr = $result->toArray();
            $arr['_event'] = 'ENTITY_STAGED';
            $arr['_module'] = $this->module_name;
            $arr['_dto_type'] = (new \ReflectionClass($data))->getShortName();
            $data['result'] = $arr;
            $data['success'] = $result->getExists();
            return $arr;
        } catch (\Exception $e) {
            error_log('STAGE_ENTITY failed: ' . $e->getMessage());
            $data['error'] = $e->getMessage();
            $data['success'] = false;
            return null;
        }
    }

    /**
     * STAGING_EXISTS — Check if a staging entity exists.
     *
     * Accepts a StagingExistsQuery DTO and returns StagingExistsResult.
     *
     * Called by:
     *   $query = new StagingExistsQuery('square', 'sq_txn_123', 'transaction');
     *   $result = hook_invoke('ksf_FA_ImportStagingProcessing_UI', 'stagingExists', $query);
     *
     * @param mixed &$data The query DTO object
     * @param array|null $opts Options
     * @return array|null StagingExistsResult as array
     */
    public function STAGING_EXISTS(&$data, $opts = null)
    {
        if (!$data instanceof \Ksfraser\StagingDto\StagingExistsQuery) {
            $data['error'] = 'stagingExists requires a StagingExistsQuery DTO instance';
            $data['success'] = false;
            return null;
        }

        try {
            $adapter = $this->getDtoAdapter();
            $result = $adapter->stagingExists($data);
            $arr = $result->toArray();
            $arr['_event'] = 'ENTITY_EXISTS_CHECKED';
            $arr['_module'] = $this->module_name;
            $data['result'] = $arr;
            $data['success'] = true;
            return $arr;
        } catch (\Exception $e) {
            error_log('STAGING_EXISTS failed: ' . $e->getMessage());
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

    private function ensureRuntimeAutoload()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $moduleDir = dirname(__FILE__);
        $autoloadPath = $moduleDir . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
        $daoFile = $moduleDir . '/vendor/ksfraser/ksf-modules-dao/src/ksf_ModulesDAO.php';
        if (file_exists($daoFile)) {
            require_once $daoFile;
        }
        $loaded = true;
    }

    private function getStagingService()
    {
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();
        $customerDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
        $transactionDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
        $paymentDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
        $paymentMatchDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentMatchDAO($tablePrefix, $db);
        $lineItemDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingLineItemDAO($tablePrefix, $db);
        $logDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingLogDAO($tablePrefix, $db);
        $txnValidator = new \ksfraser\FrontAccounting\ImportStaging\Validators\TransactionValidator();
        $custValidator = new \ksfraser\FrontAccounting\ImportStaging\Validators\CustomerValidator();
        $paymentValidator = new \ksfraser\FrontAccounting\ImportStaging\Validators\PaymentValidator();
        $matchingService = new \ksfraser\FrontAccounting\ImportStaging\Services\MatchingService();
        return new \ksfraser\FrontAccounting\ImportStaging\Services\StagingService(
            $customerDAO, $transactionDAO, $paymentDAO, $paymentMatchDAO,
            $lineItemDAO, $logDAO, $txnValidator, $custValidator, $paymentValidator, $matchingService
        );
    }

    private function getDtoAdapter()
    {
        $stagingService = $this->getStagingService();
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();
        $transactionDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
        $customerDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
        $paymentDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
        return new \ksfraser\FrontAccounting\ImportStaging\Services\DtoAdapter(
            $stagingService, $transactionDAO, $customerDAO, $paymentDAO
        );
    }

    private function getStagingById(int $id, string $entityType, $service): ?array
    {
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();

        switch ($entityType) {
            case 'transaction':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
                $record = $dao->findById($id);
                return $record ? $record->toArray() : null;
            case 'customer':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
                $record = $dao->findById($id);
                return $record ? $record->toArray() : null;
            case 'payment':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
                $record = $dao->findById($id);
                return $record ? $record->toArray() : null;
            default:
                return null;
        }
    }

    private function deleteStagingRecord(int $id, string $entityType, $service): void
    {
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();

        switch ($entityType) {
            case 'transaction':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
                $dao->delete($id);
                break;
            case 'customer':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
                $dao->delete($id);
                break;
            case 'payment':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
                $dao->delete($id);
                break;
        }
    }

    private function getStatusCountsForSource(?string $source, ?string $env, $service): array
    {
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();
        $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
        return $dao->countByStatus($source);
    }

    private function updateStagingFields(int $id, array $fields, string $entityType, $service): void
    {
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();

        switch ($entityType) {
            case 'transaction':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
                $record = $dao->findById($id);
                if ($record) {
                    foreach ($fields as $key => $value) {
                        $method = 'set' . str_replace('_', '', ucwords($key, '_'));
                        if (method_exists($record, $method)) {
                            $record->$method($value);
                        }
                    }
                    $dao->update($record);
                }
                break;
            case 'customer':
                $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
                $record = $dao->findById($id);
                if ($record) {
                    foreach ($fields as $key => $value) {
                        $method = 'set' . str_replace('_', '', ucwords($key, '_'));
                        if (method_exists($record, $method)) {
                            $record->$method($value);
                        }
                    }
                    $dao->update($record);
                }
                break;
        }
    }

    private function getItemsByTransactionId(int $stagingId, $service): array
    {
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();
        $dao = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingLineItemDAO($tablePrefix, $db);
        $items = $dao->findByTransactionId($stagingId);
        return array_map(fn($item) => $item->toArray(), $items);
    }

    private function getProcessingPipeline()
    {
        $this->ensureRuntimeAutoload();
        $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $db = new \ksf_ModulesDAO();
        $customerDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
        $transactionDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
        $paymentDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
        $lineItemDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingLineItemDAO($tablePrefix, $db);
        $logDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingLogDAO($tablePrefix, $db);
        return new \ksfraser\FrontAccounting\ImportStaging\Services\ProcessingPipeline(
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

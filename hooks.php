<?php
declare(strict_types=1);

/**
 * FA extension hooks for ksf_FA_ImportStagingProcessing.
 *
 * Implements the standard 4-method discovery contract
 * (getModuleConstants, getModuleCapabilities, hasCapability,
 * respondToCapabilityRequest) plus STAGE_* / STAGING_EXISTS DTO-based
 * responders and STAGE_CUSTOMER / STAGE_TRANSACTION / STAGE_PAYMENT /
 * PROCESS_STAGING event handlers.
 *
 * All DB access goes through native FA db_* calls via ksf_ModulesDAO —
 * PDO is never used at FA runtime (HARD RULE per AGENTS_ARCH.md §10).
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-018-001 getModuleConstants
 * @BABOK Related: FR-ISU-019-001 getModuleCapabilities
 * @BABOK Related: FR-ISU-020-001 hasCapability
 * @BABOK Related: FR-ISU-020-002 respondToCapabilityRequest
 * @BABOK Related: FR-ISU-020-003 staging/matching/reconciliation/processing dispatch
 * @BABOK Related: FR-ISU-009-002 STAGE_ENTITY / STAGING_EXISTS DTO responders
 * @BABOK Related: FR-ISU-012-001 PROCESS_STAGING event handler
 * @BABOK Related: FR-ISU-017-001 install schema via activate_extension
 * @BABOK Related: FR-ISU-015-003 lapp/rapp menu registration
 * @BABOK Related: NFR-ISU-007 @BABOK traceability
 * @BABOK Related: NFR-ISU-010 native db_* calls only
 */
define('SS_ksf_FA_ImportStagingProcessing', 109 << 8);

define('KSF_IMPORT_STAGING_MODULE_NAME', 'ksf_FA_ImportStagingProcessing');
define('KSF_IMPORT_STAGING_CAPABILITIES', 'staging,matching,mapping,audit,reconciliation');

class hooks_ksf_FA_ImportStagingProcessing extends hooks
{
    function __construct()
    {
        $this->module_name = 'ksf_FA_ImportStagingProcessing';
        $this->registerMenuItems();
    }

    /**
     * Register left/right menu items so the module is reachable from the FA UI.
     *
     * @BABOK Related: FR-ISU-015-003 lapp/rapp menu registration
     */
    private function registerMenuItems(): void
    {
        if (!function_exists('lapp') && !function_exists('rapp')) {
            return;
        }

        $modulePath = 'modules/' . KSF_IMPORT_STAGING_MODULE_NAME;

        lapp('import_staging', $modulePath . '/ksf_import_staging.php', _('Import Staging'), 'SA_ksf_FA_ImportStagingProcessing');
        lapp('import_staging', $modulePath . '/pages/config.php', _('ISU Configuration'), 'SA_ksf_FA_ImportStagingProcessing_UI');

        rapp('import_staging', $modulePath . '/ksf_import_staging.php', _('Staging Dashboard'), 'SA_ksf_FA_ImportStagingProcessing');
        rapp('import_staging', $modulePath . '/pages/config.php', _('ISU Config'), 'SA_ksf_FA_ImportStagingProcessing_UI');
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
                return $this->handleStageCustomer($data, $opts, $service);
            case 'stageTransaction':
                return $this->handleStageTransaction($data, $opts, $service);
            case 'stagePayment':
                return $this->handleStagePayment($data, $opts, $service);
            case 'stageOrUpdatePayment':
                return $this->handleStageOrUpdatePayment($data, $opts, $service);
            case 'getStagedCustomers':
                return $this->handleGetStagedCustomers($data, $opts, $service);
            case 'getStagedTransactions':
                return $this->handleGetStagedTransactions($data, $opts, $service);
            case 'getStagedPayments':
                return $this->handleGetStagedPayments($data, $opts, $service);
            case 'getById':
                return $this->handleGetById($data, $opts, $service);
            case 'delete':
                return $this->handleDelete($data, $opts, $service);
            case 'getStatusCounts':
                return $this->handleGetStatusCounts($data, $opts, $service);
            case 'updateFields':
                return $this->handleUpdateFields($data, $opts, $service);
            case 'getItemsByTransaction':
                return $this->handleGetItemsByTransaction($data, $opts, $service);
            case 'deleteLineItemsByTransaction':
                return $this->handleDeleteLineItemsByTransaction($data, $opts, $service);
            case 'updateStatus':
                return $this->handleUpdateStatus($data, $opts, $service);
            default:
                $data['error'] = 'Unknown staging action: ' . $action;
                return null;
        }
    }

    private function handleStageCustomer(&$data, $opts, $service)
    {
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
    }

    private function handleStageTransaction(&$data, $opts, $service)
    {
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
    }

    private function handleStagePayment(&$data, $opts, $service)
    {
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
    }

    private function handleStageOrUpdatePayment(&$data, $opts, $service)
    {
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
    }

    private function handleGetStagedCustomers(&$data, $opts, $service)
    {
        $filters = $opts['filters'] ?? $data['filters'] ?? [];
        $data['result'] = array_map(fn($c) => $c->toArray(), $service->getStagedCustomers($filters));
        return $data['result'];
    }

    private function handleGetStagedTransactions(&$data, $opts, $service)
    {
        $filters = $opts['filters'] ?? $data['filters'] ?? [];
        $data['result'] = array_map(fn($t) => $t->toArray(), $service->getStagedTransactions($filters));
        return $data['result'];
    }

    private function handleGetStagedPayments(&$data, $opts, $service)
    {
        $filters = $opts['filters'] ?? $data['filters'] ?? [];
        $data['result'] = array_map(fn($p) => $p->toArray(), $service->getStagedPayments($filters));
        return $data['result'];
    }

    private function handleGetById(&$data, $opts, $service)
    {
        $id = (int)($opts['id'] ?? $data['id'] ?? 0);
        $entityType = $opts['entity_type'] ?? $data['entity_type'] ?? 'transaction';
        $result = $this->getStagingById($id, $entityType, $service);
        $data['result'] = $result;
        return $result;
    }

    private function handleDelete(&$data, $opts, $service)
    {
        $id = (int)($opts['id'] ?? $data['id'] ?? 0);
        $entityType = $opts['entity_type'] ?? $data['entity_type'] ?? 'transaction';
        $this->deleteStagingRecord($id, $entityType, $service);
        $data['success'] = true;
        return true;
    }

    private function handleGetStatusCounts(&$data, $opts, $service)
    {
        $source = $opts['source'] ?? $data['source'] ?? null;
        $env = $opts['environment'] ?? $data['environment'] ?? null;
        $result = $this->getStatusCountsForSource($source, $env, $service);
        $data['result'] = $result;
        return $result;
    }

    private function handleUpdateFields(&$data, $opts, $service)
    {
        $id = (int)($opts['id'] ?? $data['id'] ?? 0);
        $fields = $opts['fields'] ?? $data['fields'] ?? [];
        $entityType = $opts['entity_type'] ?? $data['entity_type'] ?? 'transaction';
        $this->updateStagingFields($id, $fields, $entityType, $service);
        $data['success'] = true;
        return true;
    }

    private function handleGetItemsByTransaction(&$data, $opts, $service)
    {
        $stagingId = (int)($opts['staging_id'] ?? $data['staging_id'] ?? 0);
        $result = $this->getItemsByTransactionId($stagingId, $service);
        $data['result'] = $result;
        return $result;
    }

    private function handleDeleteLineItemsByTransaction(&$data, $opts, $service)
    {
        $stagingId = (int)($opts['staging_id'] ?? $data['staging_id'] ?? 0);
        $service->deleteLineItemsByTransaction($stagingId);
        $data['success'] = true;
        return true;
    }

    private function handleUpdateStatus(&$data, $opts, $service)
    {
        $id = (int)($opts['id'] ?? $data['id'] ?? 0);
        $status = $opts['status'] ?? $data['status'] ?? '';
        $error = $opts['error'] ?? $data['error'] ?? null;
        $service->updateStatus($id, $status, $error);
        $data['success'] = true;
        return true;
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
        static $service = null;
        if ($service === null) {
            $this->ensureRuntimeAutoload();
            $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
            $db = new \ksf_ModulesDAO();
            $customerDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
            $transactionDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
            $paymentDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
            $paymentMatchDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentMatchDAO($tablePrefix, $db);
            $lineItemDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingLineItemDAO($tablePrefix, $db);
            $logDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingLogDAO($tablePrefix, $db);
            $service = new \ksfraser\FrontAccounting\ImportStaging\Services\StagingService(
                $customerDAO, $transactionDAO, $paymentDAO, $paymentMatchDAO,
                $lineItemDAO, $logDAO, new \ksfraser\FrontAccounting\ImportStaging\Validators\TransactionValidator(),
                new \ksfraser\FrontAccounting\ImportStaging\Validators\CustomerValidator(),
                new \ksfraser\FrontAccounting\ImportStaging\Validators\PaymentValidator(),
                new \ksfraser\FrontAccounting\ImportStaging\Services\MatchingService()
            );
        }
        return $service;
    }

    private function getDtoAdapter()
    {
        static $adapter = null;
        if ($adapter === null) {
            $this->ensureRuntimeAutoload();
            $tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
            $db = new \ksf_ModulesDAO();
            $transactionDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO($tablePrefix, $db);
            $customerDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO($tablePrefix, $db);
            $paymentDAO = new \ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO($tablePrefix, $db);
            return new \ksfraser\FrontAccounting\ImportStaging\Services\DtoAdapter(
                $this->getStagingService(), $transactionDAO, $customerDAO, $paymentDAO
            );
        }
        return $adapter;
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
}
# Inter-Module Message Passing — ksf_FA_ImportStagingProcessing

## 1. Standard Protocol

All ksf modules implement 4 standard hook methods for inter-module communication, following the pattern documented in `AGENTS_MODULE_COMMUNICATION_ADDENDUM.md`:

1. `getModuleConstants(&$data, $opts)` — Returns module constants
2. `getModuleCapabilities(&$data, $opts)` — Returns capabilities with descriptions
3. `hasCapability(&$data, $opts)` — Checks for specific capability
4. `respondToCapabilityRequest(&$data, $opts)` — Generic responder

---

## 2. Module Identity

| Property | Value |
|----------|-------|
| Module Name | `ksf_FA_ImportStagingProcessing` |
| Constants Defined | `KSF_IMPORT_STAGING_MODULE_NAME`, `KSF_IMPORT_STAGING_CAPABILITIES` |

---

## 3. Capabilities

| Capability | Description | Methods |
|------------|-------------|---------|
| `staging` | Stage customers and transactions from any source | `stageCustomer`, `stageTransaction`, `getStagedCustomers`, `getStagedTransactions`, `updateStatus` |
| `matching` | Match and process staged transactions | `matchTransactions`, `processQueue`, `approveMatch`, `rejectMatch` |
| `mapping` | Manage field mapping configuration | `getMappings`, `saveMapping`, `getMapping` |
| `audit` | Access processing audit trail | `getLog`, `getLogByRecord`, `getLogByAction` |

---

## 4. Message Flow

### Source Module Stages Data

```
Source Module                      ImportStaging Module
     │                                     │
     │  hook_invoke('ksf_FA_ImportStagingProcessing',
     │    'respondToCapabilityRequest',
     │    $data,
     │    ['request' => 'staging:stageTransaction',
     │     'source' => 'woocommerce',
     │     'transaction' => $txnData])
     │────────────────────────────────────>│
     │                                     │
     │                                     │  (validates, inserts, logs)
     │                                     │
     │<── $data['result'] = StagingTransaction ──│
```

### Source Module Queries Staged Data

```
Source Module                      ImportStaging Module
     │                                     │
     │  hook_invoke('ksf_FA_ImportStagingProcessing',
     │    'respondToCapabilityRequest',
     │    $data,
     │    ['request' => 'staging:getStagedTransactions',
     │     'filters' => ['source' => 'square_api', 'status' => 'staged']])
     │────────────────────────────────────>│
     │                                     │
     │<── $data['result'] = [StagingTransaction[]] ──│
```

### Processing Initiation

```
Admin/System                       ImportStaging Module
     │                                     │
     │  hook_invoke('ksf_FA_ImportStagingProcessing',
     │    'respondToCapabilityRequest',
     │    $data,
     │    ['request' => 'matching:processQueue',
     │     'source' => 'square_api'])
     │────────────────────────────────────>│
     │                                     │
     │<── $data['result'] = ProcessingResult ──│
```

---

## 5. Direct Programmatic Access

Modules that require `ksfraser/import-staging` via Composer can use DAO classes directly:

```php
use Ksfraser\ImportStaging\DAO\StagingTransactionDAO;

$dao = new StagingTransactionDAO(TB_PREF);
$dao->insert([
    'source' => 'woocommerce',
    'source_transaction_id' => $orderId,
    'total_amount' => $total,
    'status' => 'staged'
]);
```

---

## 6. Hook Method Implementations

### getModuleConstants
```php
public function getModuleConstants(&$data, $opts = null) {
    $constants = [
        'KSF_IMPORT_STAGING_MODULE_NAME' => KSF_IMPORT_STAGING_MODULE_NAME,
        'KSF_IMPORT_STAGING_CAPABILITIES' => KSF_IMPORT_STAGING_CAPABILITIES,
    ];
    $data['constants'] = $constants;
    return $constants;
}
```

### getModuleCapabilities
```php
public function getModuleCapabilities(&$data, $opts = null) {
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
```

### hasCapability
```php
public function hasCapability(&$data, $opts = null) {
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
```

### respondToCapabilityRequest
```php
public function respondToCapabilityRequest(&$data, $opts = null) {
    $request = $opts['request'] ?? $data['request'] ?? 'capabilities';
    $data['request'] = $request;
    $data['module'] = $this->module_name;

    // Handle staging:stageTransaction format
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
        case 'capabilities': return $this->getModuleCapabilities($data, $opts);
        case 'constants': return $this->getModuleConstants($data, $opts);
        case (strpos($request, 'has:') === 0):
            return $this->hasCapability($data, ['capability' => substr($request, 4)]);
        default:
            $data['error'] = 'Unknown request type: ' . $request;
            return null;
    }
}
```

---

## 7. Error Handling

### Standard Error Response
```php
$data['error'] = 'Error description';
$data['error_code'] = 'ERROR_CODE';
$data['result'] = null;
```

### Exception Mapping
| Hook Error | Exception | HTTP Equivalent |
|------------|-----------|-----------------|
| Record not found | StagingNotFoundException | 404 |
| Invalid source | InvalidSourceException | 400 |
| Duplicate | DuplicateTransactionException | 409 |
| Mapping failure | MappingException | 422 |
| Processing failure | ProcessingException | 500 |

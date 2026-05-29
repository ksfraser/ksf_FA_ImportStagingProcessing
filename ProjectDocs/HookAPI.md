# Hook API Surface

## Event Handlers (hook_invoke_first)

### STAGE_CUSTOMER
Stage a customer record into staging_customers for later processing.

```php
$data = ['source' => 'woocommerce', 'customer' => ['name' => '...', 'email' => '...']];
$result = hook_invoke_first('STAGE_CUSTOMER', $data);
// $result: serialized StagingCustomer array with _event='CUSTOMER_STAGED', _module='ksf_FA_ImportStagingProcessing'
```

### STAGE_TRANSACTION
Stage a transaction record into staging_transactions for later processing.

```php
$data = ['source' => 'square_api', 'transaction' => ['total_amount' => 100.00, 'currency' => 'CAD']];
$result = hook_invoke_first('STAGE_TRANSACTION', $data);
// $result: serialized StagingTransaction array with _event='TRANSACTION_STAGED', _module='ksf_FA_ImportStagingProcessing'
```

### STAGE_PAYMENT
Stage a payment record into staging_payments for later reconciliation.

```php
$data = ['source' => 'paypal', 'payment' => ['amount' => 50.00, 'fee' => 1.50]];
$result = hook_invoke_first('STAGE_PAYMENT', $data);
// $result: serialized StagingPayment array with _event='PAYMENT_STAGED', _module='ksf_FA_ImportStagingProcessing'
```

### PROCESS_STAGING
Process all staged customers, transactions, and payments into FA entities.
Delegates to ksf_FA_Customer and ksf_FA_Payment modules via their event handlers.

```php
$data = ['source' => 'woocommerce']; // optional source filter
$result = hook_invoke_first('PROCESS_STAGING', $data);
// $result: ['success' => bool, 'record_id' => int|null, 'action' => string, 'errors' => array, 'processed_ids' => array]
```

## Capability Requests (respondToCapabilityRequest)

### staging:*
All staging operations via the capability request system.
Prefix with `staging:` for the action.

```
request = 'staging:stageCustomer'       // stage customer (same as STAGE_CUSTOMER)
request = 'staging:stageTransaction'    // stage transaction (same as STAGE_TRANSACTION)
request = 'staging:stagePayment'        // stage payment (same as STAGE_PAYMENT)
request = 'staging:stageOrUpdateCustomer'   // upsert customer by source + source_customer_id
request = 'staging:stageOrUpdateTransaction' // upsert transaction by source + source_transaction_id
request = 'staging:stageOrUpdatePayment'     // upsert payment by source + source_payment_id
request = 'staging:getStagedCustomers'       // get staged customers (filters via opts)
request = 'staging:getStagedTransactions'    // get staged transactions (filters via opts)
request = 'staging:getStagedPayments'        // get staged payments (filters via opts)
request = 'staging:updateStatus'             // update status of a staging record
```

Usage:
```php
$data = [];
$result = hook_invoke('ksf_FA_ImportStagingProcessing', 'respondToCapabilityRequest', $data, [
    'request' => 'staging:stageCustomer',
    'source' => 'woocommerce',
    'customer' => ['name' => 'Test', 'email' => 'test@example.com'],
]);
```

### processing:*
```
request = 'processing:processStaging'   // process all staged records (same as PROCESS_STAGING)
```

## Event Handlers from Other Modules (for PROCESS_STAGING pipeline)

The PROCESS_STAGING pipeline calls these events per record type.
Install one of the following modules to handle them, or provide your own handler via hook_invoke_first.

| Record Type | Event Called | Expected Module |
|-------------|-------------|-----------------|
| customer    | CREATE_CUSTOMER | ksf_FA_Customer |
| payment     | CREATE_PAYMENT  | ksf_FA_Payment  |
| transaction | CREATE_SALES_INVOICE | ksf_FA_SalesInvoice |

If no module handles the event, the pipeline logs an error and continues.

## RBAC Authorization

All staging operations call `hook_invoke_first('authorize', $authData)` before execution.
If no RBAC module (ksf_FA_RBAC) is installed, authorization defaults to **allow**.

```php
$authData = [
    'user_id' => 1,
    'action' => 'create',        // create|view|edit|delete
    'module' => 'staging',
    'resource_type' => 'staging_customer',  // staging_customer|staging_transaction|staging_payment|staging_record
];
$authorized = hook_invoke_first('authorize', $authData);
```

# Ledga PHP SDK

The official PHP SDK for the [Ledga.io](https://ledga.io) API. Ledga provides programmatic double-entry ledgers for finance, gaming, and multi-tenant SaaS applications.

## Requirements

- PHP 8.1 or later
- Composer
- Guzzle HTTP client (installed automatically)

## Installation

Install the SDK via Composer:

```bash
composer require ledga/ledga-php
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use Ledga\Api\LedgaClient;

$ledga = new LedgaClient('your-api-key');

// Create a transaction
$transaction = $ledga->transactions->create([
    'description' => 'Payment received',
    'effective_date' => '2025-01-02',
    'entries' => [
        ['account_code' => '1000', 'type' => 'debit', 'amount' => '100.00'],
        ['account_code' => '4000', 'type' => 'credit', 'amount' => '100.00'],
    ],
]);

echo "Transaction created: " . $transaction->id;
```

## Configuration

### Basic Configuration

```php
use Ledga\Api\LedgaClient;

// Production (default)
$ledga = new LedgaClient('your-api-key');

// Custom base URL (for development or self-hosted)
$ledga = new LedgaClient(
    apiKey: 'your-api-key',
    baseUrl: 'http://localhost:15080',
    timeout: 60
);
```

### Available Options

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `apiKey` | string | required | Your Ledga API key |
| `baseUrl` | string | `https://ledga.io` | API base URL |
| `timeout` | int | 30 | Request timeout in seconds |

## Usage

### Accounts

```php
// List all accounts
$accounts = $ledga->accounts->list();
foreach ($accounts->data as $account) {
    echo $account->code . ': ' . $account->name . "\n";
}

// List with filters
$assetAccounts = $ledga->accounts->list([
    'type' => 'asset',
    'active' => true,
]);

// Auto-pagination (iterate through all pages)
foreach ($ledga->accounts->all() as $account) {
    echo $account->code . ': ' . $account->name . "\n";
}

// Get a single account
$account = $ledga->accounts->get('account-uuid');

// Create an account
$account = $ledga->accounts->create([
    'code' => '1000',
    'name' => 'Cash',
    'type' => 'asset',
]);

// Update an account
$account = $ledga->accounts->update('account-uuid', [
    'name' => 'Cash - Updated',
]);

// Delete an account
$ledga->accounts->delete('account-uuid');

// Get account balance
$balance = $ledga->accounts->getBalance('account-uuid');
echo "Available: " . $balance->available;
echo "Settled: " . $balance->settled;
echo "Pending: " . $balance->pending;

// Get account by code (instead of UUID)
$account = $ledga->accounts->getByCode('1000');

// Get balance by account code
$balance = $ledga->accounts->getBalanceByCode('1000');

// Get account entries with rolling balance
$entries = $ledga->accounts->getEntries('account-uuid', [
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
]);
foreach ($entries->data as $entry) {
    echo $entry->amount . ' -> Balance: ' . $entry->balanceAfter . "\n";
}

// Get balance history over time
$history = $ledga->accounts->getBalanceHistory('account-uuid', [
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
]);
echo "Ending balance: " . $history->endingBalance;
```

### Transactions

```php
// Create a transaction
$transaction = $ledga->transactions->create([
    'description' => 'Invoice payment',
    'effective_date' => '2025-01-02',
    'reference' => 'INV-001',
    'layer' => 'SETTLED',
    'entries' => [
        ['account_code' => '1000', 'type' => 'debit', 'amount' => '500.00'],
        ['account_code' => '1200', 'type' => 'credit', 'amount' => '500.00'],
    ],
]);

// List transactions
$transactions = $ledga->transactions->list([
    'status' => 'posted',
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
]);

// Get a transaction
$transaction = $ledga->transactions->get('transaction-uuid');

// Reverse a transaction
$reversal = $ledga->transactions->reverse('transaction-uuid', [
    'reason' => 'Customer refund',
    'date' => '2025-01-15',
]);

// Create multiple transactions in a batch (max 100)
$response = $ledga->transactions->createBatch([
    [
        'idempotency_key' => 'tx-001',
        'description' => 'Payment 1',
        'effective_date' => '2025-01-15',
        'entries' => [
            ['account_code' => '1000', 'type' => 'debit', 'amount' => '100.00'],
            ['account_code' => '4000', 'type' => 'credit', 'amount' => '100.00'],
        ],
    ],
    [
        'idempotency_key' => 'tx-002',
        'description' => 'Payment 2',
        'effective_date' => '2025-01-15',
        'entries' => [
            ['account_code' => '1000', 'type' => 'debit', 'amount' => '200.00'],
            ['account_code' => '4000', 'type' => 'credit', 'amount' => '200.00'],
        ],
    ],
]);

// Check batch results
echo "Accepted: " . $response->accepted . "/" . $response->total . "\n";

if ($response->hasRejections()) {
    foreach ($response->getRejected() as $result) {
        echo "Failed: " . $result->idempotencyKey . " - " . $result->error . "\n";
    }
}
```

### Transaction Codes (Templates)

```php
// Create a reusable transaction template
$code = $ledga->transactionCodes->create([
    'code' => 'PAYMENT',
    'name' => 'Customer Payment',
    'params_schema' => [
        'required' => ['amount'],
        'properties' => [
            'amount' => ['type' => 'string'],
        ],
    ],
    'entries_template' => [
        'entries' => [
            ['account_code' => '1000', 'type' => 'debit', 'amount' => '{params.amount}'],
            ['account_code' => '4000', 'type' => 'credit', 'amount' => '{params.amount}'],
        ],
    ],
]);

// Execute a transaction code
$transaction = $ledga->transactionCodes->execute('code-uuid', [
    'amount' => '250.00',
    'effective_date' => '2025-01-02',
]);
```

### Journals

```php
// Create a journal
$journal = $ledga->journals->create([
    'code' => 'SALES',
    'name' => 'Sales Journal',
]);

// List journals
$journals = $ledga->journals->list();

// Close a journal
$journal = $ledga->journals->close('journal-uuid');
```

### Account Sets

```php
// Create an account set for reporting
$set = $ledga->accountSets->create([
    'code' => 'OPERATING_EXPENSES',
    'name' => 'Operating Expenses',
]);

// List account sets
$sets = $ledga->accountSets->list();
```

### Reports

```php
// Trial balance
$trialBalance = $ledga->reports->trialBalance([
    'as_of_date' => '2025-01-31',
]);

// Balance sheet
$balanceSheet = $ledga->reports->balanceSheet([
    'as_of_date' => '2025-01-31',
]);

// Income statement
$incomeStatement = $ledga->reports->incomeStatement([
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
]);

// General ledger
$generalLedger = $ledga->reports->generalLedger([
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
    'account_id' => 'account-uuid',
]);
```

## Pagination

The SDK supports both manual and automatic pagination.

### Manual Pagination

```php
$page = $ledga->accounts->list(['limit' => 25]);

foreach ($page->data as $account) {
    // Process account
}

// Check for more pages
if ($page->hasMore()) {
    $nextPage = $page->nextPage();
}

// Navigate backwards
if ($page->hasPrevious()) {
    $prevPage = $page->previousPage();
}
```

### Automatic Pagination

```php
// Iterate through all items across all pages
foreach ($ledga->accounts->all(['type' => 'asset']) as $account) {
    // Process account
}

// Collect all items into an array
$allAccounts = $ledga->accounts->all()->toArray();
```

## Error Handling

The SDK throws specific exceptions for different error scenarios:

```php
use Ledga\Api\Exceptions\LedgaAuthenticationException;
use Ledga\Api\Exceptions\LedgaAuthorizationException;
use Ledga\Api\Exceptions\LedgaNotFoundException;
use Ledga\Api\Exceptions\LedgaValidationException;
use Ledga\Api\Exceptions\LedgaConflictException;
use Ledga\Api\Exceptions\LedgaRateLimitException;
use Ledga\Api\Exceptions\LedgaServerException;
use Ledga\Api\Exceptions\LedgaException;

try {
    $account = $ledga->accounts->create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => 'asset',
    ]);
} catch (LedgaValidationException $e) {
    // Handle validation errors (400/422)
    foreach ($e->getErrors() as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "\n";
    }
} catch (LedgaAuthenticationException $e) {
    // Handle authentication errors (401)
    echo "Invalid API key";
} catch (LedgaAuthorizationException $e) {
    // Handle authorization errors (403)
    echo "Insufficient permissions";
} catch (LedgaNotFoundException $e) {
    // Handle not found errors (404)
    echo "Resource not found";
} catch (LedgaConflictException $e) {
    // Handle conflict errors (409)
    echo "Idempotency key conflict";
} catch (LedgaRateLimitException $e) {
    // Handle rate limiting (429)
    $retryAfter = $e->getRetryAfter();
    echo "Rate limited. Retry after $retryAfter seconds";
} catch (LedgaServerException $e) {
    // Handle server errors (5xx)
    echo "Server error: " . $e->getMessage();
} catch (LedgaException $e) {
    // Catch-all for any Ledga API error
    echo "Error: " . $e->getMessage();
}
```

## Domain Concepts

### Account Types

| Type | Normal Balance | Description |
|------|----------------|-------------|
| `asset` | Debit | Resources owned (cash, inventory, receivables) |
| `liability` | Credit | Obligations owed (payables, loans) |
| `equity` | Credit | Owner's stake in the business |
| `revenue` | Credit | Income earned |
| `expense` | Debit | Costs incurred |

### Transaction Layers

| Layer | Description |
|-------|-------------|
| `SETTLED` | Finalized transactions |
| `PENDING` | Transactions awaiting settlement |
| `ENCUMBRANCE` | Reserved funds (holds, commitments) |

### Transaction Status

| Status | Description |
|--------|-------------|
| `pending` | Transaction submitted, awaiting processing |
| `posted` | Transaction successfully recorded |
| `void` | Transaction voided |
| `failed` | Transaction failed validation |
| `reversed` | Transaction has been reversed |

## Testing

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis (PHPStan level 7)
composer analyse

# Run code style check (PSR-12)
composer cs-check

# Run all quality checks
composer quality
```

## Support

- Documentation: [https://ledga.io/docs](https://ledga.io/docs)
- Email: support@ledga.io

## License

This SDK is released under the MIT License. See [LICENSE](LICENSE) for details.

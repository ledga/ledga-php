> [!WARNING]
> The Ledga PHP SDK is pre-1.0.0. APIs may change without notice while we iterate towards a stable release. Avoid relying on long-term compatibility at this stage.

# Ledga PHP SDK

[![CI](https://github.com/ledga/ledga-php/actions/workflows/ci.yml/badge.svg)](https://github.com/ledga/ledga-php/actions/workflows/ci.yml)

The official PHP SDK for the [Ledga.io](https://ledga.io) API. Ledga provides programmatic double-entry ledgers for finance, gaming, and multi-tenant SaaS applications.

## Requirements

- PHP 8.2 or later
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
    'category' => 'system', // 'system' (internal/GL) or 'customer' (end-user balance)
]);

// Update an account
$account = $ledga->accounts->update('account-uuid', [
    'name' => 'Cash - Updated',
]);

// Delete an account
$ledga->accounts->delete('account-uuid');

// Get account balance
$balance = $ledga->accounts->getBalance('account-uuid');
echo "Settled: " . $balance->settled;   // confirmed cleared funds
echo "Pending: " . $balance->pending;   // not yet spendable
echo "Overdue: " . $balance->overdue;   // encumbrances past their due date
echo "Future: " . $balance->future;     // encumbrances due today or later

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

`POST /transactions` is asynchronous: `create()` and `createFromCode()` return a `TransactionAcknowledgement` (the server has accepted the request but not yet committed entries). Poll `get($ack->id)` once you need the durable record.

```php
// Mode 1 — explicit entries
$ack = $ledga->transactions->create([
    'description' => 'Invoice payment',
    'effective_date' => '2025-01-02',
    'idempotency_key' => 'inv-001',
    'reference' => 'INV-001',
    'layer' => 'settled',
    'entries' => [
        ['account_code' => '1000', 'type' => 'debit', 'amount' => '500.00'],
        ['account_code' => '1200', 'type' => 'credit', 'amount' => '500.00'],
    ],
]);
echo $ack->id . " " . $ack->status->value; // "<uuid> pending"

// Mode 2 — invoke a transaction code template
$ack = $ledga->transactions->createFromCode(
    'BOOK_TRANSFER',
    [
        'amount' => '100.00',
        'from_account' => '1000',
        'to_account' => '4000',
    ],
    [
        'description' => 'Customer payment',
        'effective_date' => '2025-01-02',
        'idempotency_key' => 'cp-001',
    ],
);

// Once the ack is produced, fetch the full Transaction
$transaction = $ledga->transactions->get($ack->id);

// List transactions
$transactions = $ledga->transactions->list([
    'status' => 'posted',
    'from_date' => '2025-01-01',
    'to_date' => '2025-01-31',
]);

// Get a transaction
$transaction = $ledga->transactions->get('transaction-uuid');

// Reverse a transaction
$reversal = $ledga->transactions->reverse('transaction-uuid', [
    'reason' => 'Customer refund',
    'date' => '2025-01-15',
]);

// Void a posted transaction (no contra; entries are untouched)
$voided = $ledga->transactions->void('transaction-uuid', ['reason' => 'rescheduled']);
echo $voided->status->value . " " . $voided->voidReason; // "void rescheduled"

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

### Correlating transactions

Link a transaction to the one it follows with `correlation_source_id` and `correlation_type`. Send both or neither: either one alone is a `422`. The source must be a transaction in the same ledger. `reversal`, `string_transfer` and `string_convert` are reserved for the API's own links, so a `correlation_type` of any of them is a `422`. To label a transaction without linking it, use `attributes`.

```php
// Void the old schedule item, then post its replacement linked to it
$ledga->transactions->void($oldId, ['reason' => 'rescheduled']);

$ack = $ledga->transactions->create([
    'description' => 'Replacement encumbrance',
    'effective_date' => '2026-02-15',
    'idempotency_key' => 'resched-001',
    'layer' => 'encumbrance',
    'correlation_source_id' => $oldId,
    'correlation_type' => 'reschedule',
    'entries' => [
        ['account_code' => '1300', 'type' => 'debit', 'amount' => '250.00'],
        ['account_code' => '4000', 'type' => 'credit', 'amount' => '250.00'],
    ],
]);
```

**The correlation handle.** Every transaction has one: its `correlation_id`, or its own id when it has none. A linked transaction sent without a `correlation_id` takes its source's handle, so one handle finds both. An explicit `correlation_id` (at most 50 characters) wins, and then the source's handle does not find the new transaction. `$ack->correlationId` is the handle, and reads return the same value as `$transaction->correlationId`.

**Reading the link back:**

```php
$transaction = $ledga->transactions->get($ack->id);

$transaction->correlationId;       // the handle, never null
$transaction->correlationSourceId; // $oldId
$transaction->correlationType;     // "reschedule"
```

The link fields are both set or both `null`. A reversal reads its original as `correlationSourceId`, with `correlationType` `reversal`. The fields are `null` when the API predates them.

**Looking up a trail.** `byCorrelation()` returns every transaction with a handle, oldest first (posting order). `list(['correlation_id' => …])` filters the same way but orders by effective date, newest first.

```php
$page = $ledga->transactions->byCorrelation($ack->correlationId, ['limit' => 25]);

foreach ($ledga->transactions->allByCorrelation($ack->correlationId) as $transaction) {
    echo $transaction->id . " " . $transaction->correlationType . "\n";
}
```

The handle is sent URL-encoded in the query string, so values holding `/`, `%` or spaces work. An unknown handle, or another ledger's, returns an empty page, not a 404.

**Batches.** `createBatch()` items take the same three fields. A lone `correlation_source_id` or `correlation_type`, or a reserved `correlation_type`, on any item refuses the whole batch with a `422`. A source not found in the ledger rejects only that item (`errorCode` `validation_error`); an item cannot link to a transaction created in the same batch.

### Transaction Codes (Templates)

Trancodes are reusable transaction templates. Once created, post a transaction against one with [`transactions->createFromCode()`](#transactions).

```php
// Create a parameterised template
$code = $ledga->transactionCodes->create([
    'code' => 'BOOK_TRANSFER',
    'name' => 'Internal book transfer',
    'entries_template' => [
        'entries' => [
            ['account' => '{params.from_account}', 'type' => 'debit',  'amount' => '{params.amount}'],
            ['account' => '{params.to_account}',   'type' => 'credit', 'amount' => '{params.amount}'],
        ],
    ],
]);

// Update name / template (PUT is full-replacement)
$code = $ledga->transactionCodes->update($code->id, [
    'name' => 'Customer Payment v2',
    'entries_template' => $code->entriesTemplate,
]);

// Pre-flight params against the template's params_schema before invoking
$valid = $ledga->transactionCodes->validateParams($code->id, [
    'amount' => '100.00',
    'from_account' => '1000',
    'to_account' => '4000',
]);

// Retire a trancode — one-way transition, no reactivate route
$code = $ledga->transactionCodes->deprecate($code->id);
assert($code->status === Ledga\Api\Enums\TransactionCodeStatus::Deprecated);
```

Trancodes are append-only: `code` and `status` are immutable on PUT, and there is no `delete` route. Use `deprecate()` to retire one.

### Journals

```php
// Create a journal
$journal = $ledga->journals->create([
    'code' => 'SALES',
    'name' => 'Sales Journal',
]);

// List journals
$journals = $ledga->journals->list();
```

### Account Sets

Account sets group accounts for reporting. A set can contain accounts or other sets, so hierarchies nest.

```php
use Ledga\Api\Enums\AccountSetMemberType;

// List account sets (manual pagination)
$sets = $ledga->accountSets->list(['search' => 'expense']);
foreach ($sets->data as $set) {
    echo $set->code . ': ' . $set->name . "\n";
}

// Auto-pagination (iterate through all pages)
foreach ($ledga->accountSets->all() as $set) {
    echo $set->code . "\n";
}

// Get a single account set
$set = $ledga->accountSets->get('set-uuid');

// Create an account set
$set = $ledga->accountSets->create([
    'code' => 'OPERATING_EXPENSES',
    'name' => 'Operating Expenses',
    'description' => 'All recurring operating costs',  // optional
]);

// Update an account set
$set = $ledga->accountSets->update('set-uuid', [
    'name' => 'Operating Expenses (Group)',
]);

// Delete an account set
$ledga->accountSets->delete('set-uuid');

// Add members — an account, or another set (sets can nest)
$member = $ledga->accountSets->addMember('set-uuid', AccountSetMemberType::Account, 'account-uuid');
$member = $ledga->accountSets->addMember('set-uuid', AccountSetMemberType::AccountSet, 'child-set-uuid');
echo $member->memberType->value;  // "account_set"
echo $member->memberId;           // "child-set-uuid"

// Remove a member
$ledga->accountSets->removeMember('set-uuid', AccountSetMemberType::Account, 'account-uuid');

// Every account in the set, recursing through nested sets.
// Returns a flat list<Account> — not paginated.
$accounts = $ledga->accountSets->getAccounts('set-uuid');
foreach ($accounts as $account) {
    echo $account->code . ': ' . $account->balance . "\n";
}

// Aggregate balance across all member accounts (nested sets included)
$balance = $ledga->accountSets->getBalance('set-uuid');
echo $balance->totalBalance;  // "1234.56" (major units, as a string)
echo $balance->currency;      // "GBP"
echo $balance->accountCount;  // 3
```

### Reports

```php
// Trial balance
$trialBalance = $ledga->reports->trialBalance([
    'as_of_date' => '2025-01-31',
]);

// Income statement
$incomeStatement = $ledga->reports->incomeStatement([
    'from_date' => '2025-01-01',
    'to_date' => '2025-01-31',
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
| `settled` | Finalized transactions |
| `pending` | Transactions awaiting settlement |
| `encumbrance` | Reserved funds (holds, commitments) |

The wire format is canonical lowercase on both sides. The API normalises any casing on request (`Settled`, `SETTLED`, `settled` all accepted), so passing `TransactionLayer::Settled->value` directly into a create payload works as expected. Responses always render the canonical lowercase form.

### Transaction Status

| Status | Description |
|--------|-------------|
| `pending` | Transaction submitted, awaiting processing |
| `posted` | Transaction successfully recorded |
| `void` | Transaction voided |
| `reversed` | Transaction has been reversed |
| `draft` | In the API's status list; no endpoint sets it yet |
| `verified` | Acknowledgement of a replayed `idempotency_key` only: the transaction passed integrity verification |
| `failed` | Acknowledgement of a replayed `idempotency_key` only: the transaction failed integrity verification |

`create()` returns `pending` for a new submission. A retry with the same `idempotency_key` may instead return the existing transaction's verification status (`pending`, `verified`, `failed` or `reversed`), which is not its status. Use `get($ack->id)` to read the status.

## Response shape and contract

- The Ledga API wraps every single-resource response in a `{"success": true, "data": {...}}` envelope. The SDK strips this at the boundary; resource DTOs expose flat properties.
- Cursor pagination metadata lives at `meta.pagination.{next_cursor, previous_cursor, limit, has_more}`. Use the `PaginatedResponse->nextCursor`, `prevCursor`, `perPage`, and `hasMore()` accessors.
- `Account::$category` is an `AccountCategory` enum. Compare with cases, not strings: `$account->category === AccountCategory::System`.
- Transaction codes are served at `/api/v1/trancodes`. Supported methods: `list`, `all`, `get`, `create`, `update`, `validateParams`, `deprecate`. Trancodes are append-only — `code` and `status` are immutable on PUT, and `deprecate()` is a one-way transition (no reactivate). `TransactionCode::$status` is a `TransactionCodeStatus` enum (`Active`, `Deprecated`).
- `POST /transactions` is asynchronous and returns a `TransactionAcknowledgement` (id, status, idempotency key, correlation id, message). Both modes — explicit entries (`create()`) and trancode invocation (`createFromCode()`) — funnel through this endpoint. Use `transactions->get($ack->id)` to fetch the durable transaction once accepted.

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

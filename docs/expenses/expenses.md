# Expenses

List and filter expense documents in Teamleader Focus.

## Overview

The Expenses resource is a **read-only listing view** that aggregates incoming invoices, incoming credit notes, and
receipts into a single filterable feed. To create, update, or delete individual documents, use the dedicated
resources: `incomingInvoices`, `incomingCreditNotes`, or `receipts`.

Access via `Teamleader::expenses()`.

> **This resource does not create documents.** It only lists them.
>
> **Response gives `source.id` + `source.type`** — pass `source.id` to the corresponding resource to fetch the full
> document.
>
> **`payment_statuses` values here are `paid` / `unpaid`** — different from the `unknown`, `paid`, `not_paid` values on
> the individual expense resources.

## Endpoint

`expenses`

## Capabilities

| Capability  | Supported                                                   |
|-------------|-------------------------------------------------------------|
| Pagination  | ✅ Supported                                                 |
| Filtering   | ✅ Supported                                                 |
| Sorting     | ✅ Supported (`document_date`, `due_date`, `supplier_name`)  |
| Sideloading | ❌ Not supported                                             |
| Creation    | ❌ Use `incomingInvoices`, `incomingCreditNotes`, `receipts` |
| Update      | ❌ Use individual resources                                  |
| Deletion    | ❌ Use individual resources                                  |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All expenses
$expenses = Teamleader::expenses()->list();

// Pending incoming invoices
$expenses = Teamleader::expenses()->list([
    'source_types'   => ['incomingInvoice'],
    'review_statuses'=> ['pending'],
]);

// With pagination and sorting
$expenses = Teamleader::expenses()->list([], [
    'sort'        => [['field' => 'document_date', 'order' => 'desc']],
    'page_size'   => 50,
    'page_number' => 1,
]);
```

Sort fields are **validated** — `InvalidArgumentException` for any field
outside `document_date`, `due_date`, `supplier_name`.

String filter values are coerced to arrays internally (e.g. `'pending'` becomes `['pending']`).

---

## Helper Methods

### Review status shortcuts

```php
$pending  = Teamleader::expenses()->pending();   // review_statuses: ['pending']
$approved = Teamleader::expenses()->approved();  // review_statuses: ['approved']
$refused  = Teamleader::expenses()->refused();   // review_statuses: ['refused']
```

### Payment status shortcuts

```php
$paid   = Teamleader::expenses()->paid();    // payment_statuses: ['paid']
$unpaid = Teamleader::expenses()->unpaid();  // payment_statuses: ['unpaid']
```

### Bookkeeping status shortcuts

```php
$sent    = Teamleader::expenses()->sent();     // bookkeeping_statuses: ['sent']
$notSent = Teamleader::expenses()->notSent();  // bookkeeping_statuses: ['not_sent']
```

### `bySourceType(string|array $sourceTypes)`

```php
$invoices    = Teamleader::expenses()->bySourceType('incomingInvoice');
$creditNotes = Teamleader::expenses()->bySourceType('incomingCreditNote');
$receipts    = Teamleader::expenses()->bySourceType('receipt');
$mixed       = Teamleader::expenses()->bySourceType(['incomingInvoice', 'receipt']);
```

### `bySupplier(string $type, string $id)`

Validates `$type` is `company` or `contact` before the request.

```php
$expenses = Teamleader::expenses()->bySupplier('company', 'company-uuid');
$expenses = Teamleader::expenses()->bySupplier('contact', 'contact-uuid');
```

### `byDepartment(string|array $departmentIds)`

```php
$expenses = Teamleader::expenses()->byDepartment('dept-uuid');
$expenses = Teamleader::expenses()->byDepartment(['dept-uuid-1', 'dept-uuid-2']);
```

### `byDateRange(string $startDate, string $endDate)`

Applies a `document_date` filter with `operator: between`.

```php
$expenses = Teamleader::expenses()->byDateRange('2025-01-01', '2025-03-31');
$expenses = Teamleader::expenses()->byDateRange('2025-01-01', '2025-03-31', [
    'source_types' => ['incomingInvoice'],
]);
```

### `byPaidAtRange(string $startDate, string $endDate)`

Applies a `paid_at` filter with `operator: between`.

```php
$expenses = Teamleader::expenses()->byPaidAtRange('2025-01-01', '2025-03-31');
```

### `searchByTerm(string $term)`

Searches by document number and supplier name (case-insensitive).

```php
$expenses = Teamleader::expenses()->searchByTerm('Acme');
```

---

## Filters

| Filter                 | Type   | Description                                                    |
|------------------------|--------|----------------------------------------------------------------|
| `term`                 | string | Search by document number and supplier name (case-insensitive) |
| `source_types`         | array  | `incomingInvoice`, `incomingCreditNote`, `receipt`             |
| `review_statuses`      | array  | `pending`, `approved`, `refused`                               |
| `bookkeeping_statuses` | array  | `sent`, `not_sent`                                             |
| `payment_statuses`     | array  | `paid`, `unpaid`                                               |
| `department_ids`       | array  | One or more department UUIDs                                   |
| `supplier`             | object | `{type: company\|contact, id: uuid}` — type is validated       |
| `document_date`        | object | Date operator filter (see below)                               |
| `paid_at`              | object | Date operator filter (see below)                               |

### Date filter operators

`document_date` and `paid_at` both accept an operator object. `buildDateFilter()` throws `InvalidArgumentException`
if `operator` is missing.

| Operator   | Additional keys             | Description                |
|------------|-----------------------------|----------------------------|
| `is_empty` | —                           | Documents with no date set |
| `equals`   | `value` (YYYY-MM-DD)        | Exact match                |
| `before`   | `value` (YYYY-MM-DD)        | Before a date              |
| `after`    | `value` (YYYY-MM-DD)        | After a date               |
| `between`  | `start`, `end` (YYYY-MM-DD) | Within a range             |

```php
// Exact date
$expenses = Teamleader::expenses()->list([
    'document_date' => ['operator' => 'equals', 'value' => '2025-04-01'],
]);

// Before a date
$expenses = Teamleader::expenses()->list([
    'document_date' => ['operator' => 'before', 'value' => '2025-04-01'],
]);

// No document date set
$expenses = Teamleader::expenses()->list([
    'document_date' => ['operator' => 'is_empty'],
]);

// Paid within a range
$expenses = Teamleader::expenses()->list([
    'paid_at' => ['operator' => 'between', 'start' => '2025-01-01', 'end' => '2025-03-31'],
]);
```

---

## Sorting

| Field           | Description                  |
|-----------------|------------------------------|
| `document_date` | Date on the document         |
| `due_date`      | Payment due date             |
| `supplier_name` | Supplier name alphabetically |

```php
$expenses = Teamleader::expenses()->list([], [
    'sort' => [['field' => 'document_date', 'order' => 'desc']],
]);
```

---

## Response Structure

Each item has a `source` object pointing to the underlying document:

```php
[
    'data' => [
        [
            'source'              => ['type' => 'incomingInvoice', 'id' => 'invoice-uuid'],
            'title'               => 'Software licences Q2',
            'supplier'            => ['type' => 'company', 'id' => 'company-uuid'],   // nullable
            'document_number'     => 'INV-2025-042',   // nullable
            'document_date'       => '2025-04-01',      // nullable
            'due_date'            => '2025-05-01',       // nullable
            'currency'            => ['code' => 'EUR'],
            'total'               => ['tax_exclusive' => ['amount' => 1000.0], 'tax_inclusive' => ['amount' => 1210.0]],
            'company_entity'      => ['type' => 'company_entity', 'id' => 'entity-uuid'], // nullable
            'file'                => ['type' => 'file', 'id' => 'file-uuid'],              // nullable
            'payment_reference'   => '+++123/4567/89012+++',  // nullable
            'review_status'       => 'approved',   // pending | approved | refused
            'bookkeeping_status'  => 'not_sent',   // sent | not_sent
            'iban_number'         => 'BE68539007547034',   // nullable
            'payment_status'      => 'not_paid',   // unknown | paid | partially_paid | not_paid
            'paid_amount'         => null,          // nullable
            'paid_at'             => null,          // nullable
        ],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 150],
]
```

To fetch the full document from a list result:

```php
foreach ($expenses['data'] as $expense) {
    $sourceId   = $expense['source']['id'];
    $sourceType = $expense['source']['type'];

    if ($sourceType === 'incomingInvoice') {
        $full = Teamleader::incomingInvoices()->info($sourceId);
    } elseif ($sourceType === 'incomingCreditNote') {
        $full = Teamleader::incomingCreditNotes()->info($sourceId);
    } else {
        $full = Teamleader::receipts()->info($sourceId);
    }
}
```

---

## Usage Examples

### Process all approved, unsent invoices

```php
$expenses = Teamleader::expenses()->list([
    'source_types'        => ['incomingInvoice'],
    'review_statuses'     => ['approved'],
    'bookkeeping_statuses'=> ['not_sent'],
]);

foreach ($expenses['data'] as $expense) {
    Teamleader::incomingInvoices()->sendToBookkeeping($expense['source']['id']);
}
```

### Monthly expense report

```php
$expenses = Teamleader::expenses()->byDateRange('2025-04-01', '2025-04-30');

$totals = ['incomingInvoice' => 0, 'incomingCreditNote' => 0, 'receipt' => 0];
foreach ($expenses['data'] as $expense) {
    $totals[$expense['source']['type']] += $expense['total']['tax_inclusive']['amount'] ?? 0;
}
```

---

## Error Handling

```php
use InvalidArgumentException;

// Missing operator on date filter
try {
    Teamleader::expenses()->list([
        'document_date' => ['start' => '2025-01-01', 'end' => '2025-03-31'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Date filter requires an operator: is_empty, between, equals, before, after'
}

// Invalid supplier type
try {
    Teamleader::expenses()->bySupplier('team', 'uuid');
} catch (InvalidArgumentException $e) {
    // "Invalid supplier type 'team'. Must be one of: company, contact"
}

// Invalid sort field
try {
    Teamleader::expenses()->list([], ['sort' => [['field' => 'created_at']]]);
} catch (InvalidArgumentException $e) {
    // "Invalid sort field 'created_at'. Available fields: document_date, due_date, supplier_name"
}
```

---

## Related Resources

- [[Incoming-Invoices]] — Create, update, delete incoming invoices
- [[Incoming-Credit-Notes]] — Create, update, delete incoming credit notes
- [[Receipts]] — Create, update, delete receipts
- [[Bookkeeping-Submissions]] — Track bookkeeping submission history

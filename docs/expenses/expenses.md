# Expenses

Manage expenses overview in Teamleader Focus.

## Overview

The Expenses resource provides read-only access to all expense-related documents in your Teamleader account. This resource aggregates data from incoming invoices, incoming credit notes, and receipts into a single searchable interface.

**Important:** This resource is read-only. To create, update, or delete expense documents, use the specific resources: `incomingInvoices()`, `incomingCreditNotes()`, or `receipts()`.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`expenses`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (use specific resources)
- **Update**: ❌ Not Supported (use specific resources)
- **Deletion**: ❌ Not Supported (use specific resources)

## Available Methods

### `list()`

Get a list of expenses with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for pagination and sorting

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all expenses
$expenses = Teamleader::expenses()->list();

// Get expenses with filters
$expenses = Teamleader::expenses()->list([
    'review_statuses' => ['pending'],
    'source_types' => ['incomingInvoice']
]);

// With pagination
$expenses = Teamleader::expenses()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// With sorting
$expenses = Teamleader::expenses()->list([], [
    'sort' => [['field' => 'document_date', 'order' => 'desc']]
]);
```

## Helper Methods

The Expenses resource provides convenient helper methods:

### Review Status Methods

```php
// Get expenses pending review
$pending = Teamleader::expenses()->pending();

// Get approved expenses
$approved = Teamleader::expenses()->approved();

// Get refused expenses
$refused = Teamleader::expenses()->refused();
```

### Payment Status Methods

```php
// Get paid expenses
$paid = Teamleader::expenses()->paid();

// Get unpaid expenses
$unpaid = Teamleader::expenses()->unpaid();
```

### Source Type Methods

```php
// Get only incoming invoices
$invoices = Teamleader::expenses()->bySourceType('incomingInvoice');

// Get only incoming credit notes
$creditNotes = Teamleader::expenses()->bySourceType('incomingCreditNote');

// Get only receipts
$receipts = Teamleader::expenses()->bySourceType('receipt');

// Get multiple types
$documents = Teamleader::expenses()->bySourceType(['incomingInvoice', 'receipt']);
```

### Supplier Methods

```php
// Get expenses from a specific company supplier
$expenses = Teamleader::expenses()->bySupplier('company', 'company-uuid');

// Get expenses from a specific contact supplier
$expenses = Teamleader::expenses()->bySupplier('contact', 'contact-uuid');

// Combine with additional filters
$expenses = Teamleader::expenses()->bySupplier('company', 'company-uuid', [
    'review_statuses' => ['approved']
]);
```

### Department Methods

```php
// Get expenses for a single department
$expenses = Teamleader::expenses()->byDepartment('department-uuid');

// Get expenses for multiple departments
$expenses = Teamleader::expenses()->byDepartment(['dept-uuid-1', 'dept-uuid-2']);
```

### Bookkeeping Status Methods

```php
// Get expenses sent to bookkeeping
$sent = Teamleader::expenses()->sent();

// Get expenses not sent to bookkeeping
$notSent = Teamleader::expenses()->notSent();
```

### Search Methods

```php
// Search by document number or supplier name
$results = Teamleader::expenses()->searchByTerm('Acme Corp');

// Search with additional filters
$results = Teamleader::expenses()->searchByTerm('Office', [
    'review_statuses' => ['approved']
]);
```

### Date Range Methods

```php
// Get expenses within a document date range
$expenses = Teamleader::expenses()->byDateRange('2024-01-01', '2024-12-31');

// With additional filters
$expenses = Teamleader::expenses()->byDateRange(
    '2024-01-01',
    '2024-12-31',
    ['source_types' => ['incomingInvoice']]
);

// Get expenses paid within a date range
$expenses = Teamleader::expenses()->byPaidAtRange('2024-01-01', '2024-12-31');
```

## Filtering

Available filters for the `list()` method:

| Filter | Type | Description |
|---|---|---|
| `term` | string | Search by document number and supplier name (case-insensitive) |
| `source_types` | string[] | `incomingInvoice`, `incomingCreditNote`, `receipt` |
| `review_statuses` | string[] | `pending`, `approved`, `refused` |
| `bookkeeping_statuses` | string[] | `sent`, `not_sent` |
| `payment_statuses` | string[] | `paid`, `unpaid` |
| `department_ids` | string[] | One or more department UUIDs |
| `supplier` | object | Object with `type` (`company` or `contact`) and `id` |
| `document_date` | object | Date filter with operator (see below) |
| `paid_at` | object | Payment date filter with operator (see below) |

### Date Filter Operators

Both `document_date` and `paid_at` accept the same operator structure:

| Operator | Required Fields | Description |
|---|---|---|
| `is_empty` | none | Expenses with no date set |
| `equals` | `value` | Exact date match |
| `before` | `value` | Before a specific date |
| `after` | `value` | After a specific date |
| `between` | `start`, `end` | Within a date range |

**Examples:**
```php
// Approved, unpaid invoices not yet in bookkeeping
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'review_statuses' => ['approved'],
    'bookkeeping_statuses' => ['not_sent'],
    'payment_statuses' => ['unpaid'],
]);

// Expenses from a specific supplier within a date range
$expenses = Teamleader::expenses()->list([
    'supplier' => ['type' => 'company', 'id' => 'company-uuid'],
    'document_date' => [
        'operator' => 'between',
        'start' => '2024-01-01',
        'end' => '2024-12-31',
    ]
]);

// Expenses paid after a specific date
$expenses = Teamleader::expenses()->list([
    'paid_at' => ['operator' => 'after', 'value' => '2024-06-01']
]);

// Expenses without a document date
$expenses = Teamleader::expenses()->list([
    'document_date' => ['operator' => 'is_empty']
]);
```

## Sorting

Available sort fields:

| Field | Description |
|---|---|
| `document_date` | Sort by document date |
| `due_date` | Sort by due date |
| `supplier_name` | Sort by supplier name |

**Example:**
```php
// Sort by document date, newest first
$expenses = Teamleader::expenses()->list([], [
    'sort' => [['field' => 'document_date', 'order' => 'desc']]
]);

// Sort by supplier name ascending
$expenses = Teamleader::expenses()->list([], [
    'sort' => [['field' => 'supplier_name', 'order' => 'asc']]
]);
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "source": {
        "type": "incomingInvoice",
        "id": "invoice-uuid"
      },
      "origin": {
        "type": "user",
        "id": "user-uuid"
      },
      "title": "Monthly Services",
      "supplier": {
        "type": "company",
        "id": "company-uuid"
      },
      "document_number": "INV-2024/001",
      "document_date": "2024-01-15",
      "due_date": "2024-02-15",
      "currency": {
        "code": "EUR"
      },
      "total": {
        "tax_exclusive": {
          "amount": 1000.00
        },
        "tax_inclusive": {
          "amount": 1210.00
        }
      },
      "company_entity": {
        "type": "company_entity",
        "id": "entity-uuid"
      },
      "file": {
        "type": "file",
        "id": "file-uuid"
      },
      "payment_reference": "+++123/4567/89012+++",
      "review_status": "approved",
      "bookkeeping_status": "not_sent",
      "iban_number": "BE68539007547034",
      "payment_status": "not_paid",
      "paid_amount": null,
      "paid_at": null
    }
  ],
  "meta": {
    "page": {
      "size": 20,
      "number": 1
    },
    "matches": 150
  }
}
```

### Key Response Fields

| Field | Nullable | Description |
|---|---|---|
| `source.type` | No | `incomingInvoice`, `incomingCreditNote`, or `receipt` |
| `source.id` | No | UUID of the underlying document |
| `origin.type` | No | `user` or `peppolIncomingDocument` |
| `supplier` | Yes | Object with `type` and `id` |
| `document_number` | Yes | Document reference number |
| `document_date` | Yes | Date on the document |
| `due_date` | Yes | Payment due date |
| `company_entity` | Yes | Associated company entity |
| `file` | Yes | Attached file reference |
| `payment_reference` | Yes | Structured payment reference |
| `review_status` | No | `pending`, `approved`, or `refused` |
| `bookkeeping_status` | No | `sent` or `not_sent` |
| `iban_number` | Yes | Supplier IBAN |
| `payment_status` | No | `unknown`, `paid`, `partially_paid`, or `not_paid` |
| `paid_amount` | Yes | Total amount paid so far |
| `paid_at` | Yes | Date of last payment |

> **Note:** `meta` is only included when `includes=pagination` is passed in the request.

## Usage Examples

### Get All Pending Expenses

```php
$pending = Teamleader::expenses()->pending();

foreach ($pending['data'] as $expense) {
    echo $expense['title'] . ' - ' . $expense['document_number'] . PHP_EOL;
}
```

### Find Unpaid Invoices From a Supplier

```php
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'supplier' => ['type' => 'company', 'id' => 'company-uuid'],
    'payment_statuses' => ['unpaid'],
]);
```

### Search for Specific Supplier

```php
$results = Teamleader::expenses()->searchByTerm('Acme Corporation');

if (isset($results['data']) && count($results['data']) > 0) {
    echo 'Found ' . count($results['data']) . ' expenses from Acme Corporation';
}
```

### Get Approved But Not Sent to Bookkeeping

```php
$expenses = Teamleader::expenses()->list([
    'review_statuses' => ['approved'],
    'bookkeeping_statuses' => ['not_sent'],
]);
```

### Filter by Date Range and Type

```php
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'document_date' => [
        'operator' => 'between',
        'start' => '2024-01-01',
        'end' => '2024-01-31',
    ],
]);
```

### Get Expenses Paid This Month

```php
$expenses = Teamleader::expenses()->byPaidAtRange(
    date('Y-m-01'),
    date('Y-m-t')
);
```

### Paginate Through All Expenses

```php
$page = 1;
$allExpenses = [];

do {
    $response = Teamleader::expenses()->list([], [
        'page_size' => 100,
        'page_number' => $page,
    ]);

    $allExpenses = array_merge($allExpenses, $response['data']);
    $page++;

} while (count($response['data']) === 100);
```

## Common Use Cases

### Expense Approval Workflow

```php
// Get all pending expenses sorted by document date
$pending = Teamleader::expenses()->list(
    ['review_statuses' => ['pending']],
    ['sort' => [['field' => 'document_date', 'order' => 'asc']]]
);

foreach ($pending['data'] as $expense) {
    $type = $expense['source']['type'];
    $id = $expense['source']['id'];

    if ($type === 'incomingInvoice') {
        Teamleader::incomingInvoices()->approve($id);
    } elseif ($type === 'incomingCreditNote') {
        Teamleader::incomingCreditNotes()->approve($id);
    } elseif ($type === 'receipt') {
        Teamleader::receipts()->approve($id);
    }
}
```

### Monthly Expense Report

```php
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$expenses = Teamleader::expenses()->byDateRange($startOfMonth, $endOfMonth, [
    'review_statuses' => ['approved'],
]);

$total = 0;
foreach ($expenses['data'] as $expense) {
    $total += $expense['total']['tax_inclusive']['amount'];
}

echo 'Total approved expenses for ' . date('F Y') . ': €' . number_format($total, 2);
```

### Find Expenses Not Yet Sent to Bookkeeping

```php
$notSent = Teamleader::expenses()->list([
    'review_statuses' => ['approved'],
    'bookkeeping_statuses' => ['not_sent'],
]);

foreach ($notSent['data'] as $expense) {
    $type = $expense['source']['type'];
    $id = $expense['source']['id'];

    if ($type === 'incomingInvoice') {
        Teamleader::incomingInvoices()->sendToBookkeeping($id);
    }
}
```

### Department Expense Summary

```php
$departmentIds = ['dept-uuid-1', 'dept-uuid-2'];

$expenses = Teamleader::expenses()->byDepartment($departmentIds, [
    'review_statuses' => ['approved'],
    'document_date' => [
        'operator' => 'between',
        'start' => '2024-01-01',
        'end' => '2024-12-31',
    ],
]);
```

## Best Practices

1. **Use Specific Resources for Modifications**: Always use `incomingInvoices()`, `incomingCreditNotes()`, or `receipts()` to create, update, or delete expense documents.

2. **Reference `source` Not Root**: The response uses `source.type` and `source.id` — not `source_type` / `source_id` at the root level.
```php
// Correct
$type = $expense['source']['type'];
$id   = $expense['source']['id'];
```

3. **Efficient Filtering**: Combine filters to reduce data returned.
```php
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'review_statuses' => ['pending'],
    'payment_statuses' => ['unpaid'],
]);
```

4. **Pagination for Large Datasets**: Always paginate for large result sets.
```php
$expenses = Teamleader::expenses()->list([], [
    'page_size' => 100,
    'page_number' => 1,
]);
```

5. **Use Helper Methods**: Take advantage of helper methods for common queries.
```php
// Preferred
$pending = Teamleader::expenses()->pending();
$unpaid  = Teamleader::expenses()->unpaid();
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $expenses = Teamleader::expenses()->list([
        'supplier' => ['type' => 'company', 'id' => 'company-uuid'],
        'review_statuses' => ['approved'],
    ]);

    if (empty($expenses['data'])) {
        // No matching expenses found
    }

} catch (\InvalidArgumentException $e) {
    // Invalid filter values (e.g. wrong supplier type, bad sort field)
    Log::error('Invalid filter: ' . $e->getMessage());

} catch (\Exception $e) {
    Log::error('Failed to fetch expenses: ' . $e->getMessage());
}
```

## Related Resources

- **[Incoming Invoices](incoming-invoices.md)** - Create and manage incoming invoices
- **[Incoming Credit Notes](incoming-creditnotes.md)** - Create and manage incoming credit notes
- **[Receipts](receipts.md)** - Create and manage expense receipts
- **[Bookkeeping Submissions](bookkeeping-submissions.md)** - Track bookkeeping submissions
- **[Companies](../crm/companies.md)** - Supplier information

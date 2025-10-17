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
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (use specific resources)
- **Update**: ❌ Not Supported (use specific resources)
- **Deletion**: ❌ Not Supported (use specific resources)

## Available Methods

### `list()`

Get a list of expenses with optional filtering and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for pagination

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
// Get expenses within a date range
$expenses = Teamleader::expenses()->byDateRange('2024-01-01', '2024-12-31');

// With additional filters
$expenses = Teamleader::expenses()->byDateRange(
    '2024-01-01',
    '2024-12-31',
    ['source_types' => ['incomingInvoice']]
);
```

## Filtering

Available filters for expenses:

- `term` - Search by document number and supplier name (case-insensitive)
- `source_types` - Array of source types: `incomingInvoice`, `incomingCreditNote`, `receipt`
- `review_statuses` - Array of review statuses: `pending`, `approved`, `refused`
- `bookkeeping_statuses` - Array of bookkeeping statuses: `sent`, `not_sent`
- `document_date` - Filter by document date with operators:
    - `is_empty` - Find expenses without a date
    - `between` - Date range (requires `start` and `end`)
    - `equals` - Exact date match
    - `before` - Before a specific date
    - `after` - After a specific date

**Example:**
```php
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice', 'receipt'],
    'review_statuses' => ['approved'],
    'bookkeeping_statuses' => ['not_sent'],
    'document_date' => [
        'operator' => 'between',
        'start' => '2024-01-01',
        'end' => '2024-12-31'
    ]
]);
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "uuid",
      "source_type": "incomingInvoice",
      "source_id": "invoice-uuid",
      "title": "Monthly Services",
      "document_number": "INV-2024/001",
      "document_date": "2024-01-15",
      "supplier": {
        "type": "company",
        "id": "company-uuid"
      },
      "currency": {
        "code": "EUR"
      },
      "total": {
        "tax_exclusive": {
          "amount": 1000.00,
          "currency": "EUR"
        },
        "tax_inclusive": {
          "amount": 1210.00,
          "currency": "EUR"
        }
      },
      "review_status": "approved",
      "bookkeeping_status": "sent",
      "created_at": "2024-01-15T10:00:00+00:00",
      "updated_at": "2024-01-20T14:30:00+00:00"
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

## Usage Examples

### Get All Pending Expenses

```php
$pending = Teamleader::expenses()->pending();

foreach ($pending['data'] as $expense) {
    echo $expense['title'] . ' - ' . $expense['document_number'] . PHP_EOL;
}
```

### Search for Specific Supplier

```php
$results = Teamleader::expenses()->searchByTerm('Acme Corporation');

if (isset($results['data']) && count($results['data']) > 0) {
    echo "Found " . count($results['data']) . " expenses from Acme Corporation";
}
```

### Get Approved But Not Sent to Bookkeeping

```php
$expenses = Teamleader::expenses()->list([
    'review_statuses' => ['approved'],
    'bookkeeping_statuses' => ['not_sent']
]);
```

### Filter by Date Range and Type

```php
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'document_date' => [
        'operator' => 'between',
        'start' => '2024-01-01',
        'end' => '2024-01-31'
    ]
]);
```

### Paginate Through All Expenses

```php
$page = 1;
$allExpenses = [];

do {
    $response = Teamleader::expenses()->list([], [
        'page_size' => 100,
        'page_number' => $page
    ]);
    
    $allExpenses = array_merge($allExpenses, $response['data']);
    $page++;
    
} while (count($response['data']) === 100);
```

## Common Use Cases

### Expense Approval Workflow

```php
// Get all pending expenses
$pending = Teamleader::expenses()->pending();

// Process each one
foreach ($pending['data'] as $expense) {
    // Based on source type, use the appropriate resource to approve
    if ($expense['source_type'] === 'incomingInvoice') {
        Teamleader::incomingInvoices()->approve($expense['source_id']);
    } elseif ($expense['source_type'] === 'receipt') {
        Teamleader::receipts()->approve($expense['source_id']);
    }
}
```

### Monthly Expense Report

```php
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$expenses = Teamleader::expenses()->byDateRange($startOfMonth, $endOfMonth, [
    'review_statuses' => ['approved']
]);

$total = 0;
foreach ($expenses['data'] as $expense) {
    $total += $expense['total']['tax_inclusive']['amount'];
}

echo "Total approved expenses for " . date('F Y') . ": €" . number_format($total, 2);
```

### Find Expenses Not Yet Sent to Bookkeeping

```php
$notSent = Teamleader::expenses()->list([
    'review_statuses' => ['approved'],
    'bookkeeping_statuses' => ['not_sent']
]);

foreach ($notSent['data'] as $expense) {
    // Send to bookkeeping based on type
    if ($expense['source_type'] === 'incomingInvoice') {
        Teamleader::incomingInvoices()->sendToBookkeeping($expense['source_id']);
    }
}
```

## Best Practices

1. **Use Specific Resources for Modifications**: Always use `incomingInvoices()`, `incomingCreditNotes()`, or `receipts()` to create, update, or delete expense documents

2. **Efficient Filtering**: Use specific filters to reduce the amount of data returned
```php
// Good - specific filters
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'review_statuses' => ['pending']
]);

// Avoid - fetching everything
$all = Teamleader::expenses()->list();
```

3. **Pagination for Large Datasets**: Always use pagination when dealing with large numbers of expenses
```php
$expenses = Teamleader::expenses()->list([], [
    'page_size' => 100,
    'page_number' => 1
]);
```

4. **Check Source Type Before Actions**: Always verify the source type before performing actions
```php
$expense = $expenses['data'][0];

switch ($expense['source_type']) {
    case 'incomingInvoice':
        Teamleader::incomingInvoices()->approve($expense['source_id']);
        break;
    case 'receipt':
        Teamleader::receipts()->approve($expense['source_id']);
        break;
}
```

5. **Use Helper Methods**: Take advantage of helper methods for common queries
```php
// Use helper
$pending = Teamleader::expenses()->pending();

// Instead of
$pending = Teamleader::expenses()->list(['review_statuses' => ['pending']]);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $expenses = Teamleader::expenses()->list([
        'review_statuses' => ['approved']
    ]);
    
    if (empty($expenses['data'])) {
        // No approved expenses found
    }
    
} catch (\Exception $e) {
    // Handle API errors
    Log::error('Failed to fetch expenses: ' . $e->getMessage());
}
```

## Related Resources

- **[Incoming Invoices](incoming-invoices.md)** - Create and manage incoming invoices
- **[Incoming Credit Notes](incoming-creditnotes.md)** - Create and manage incoming credit notes
- **[Receipts](receipts.md)** - Create and manage expense receipts
- **[Bookkeeping Submissions](bookkeeping-submissions.md)** - Track bookkeeping submissions
- **[Companies](../crm/companies.md)** - Supplier information

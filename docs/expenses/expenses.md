# Expenses

Manage expenses in Teamleader Focus. This resource provides read-only access to expense information including incoming invoices, credit notes, and receipts from your Teamleader account.

## Endpoint

`expenses`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of expenses with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination options

**Example:**
```php
$expenses = $teamleader->expenses()->list([
    'review_statuses' => ['approved']
]);
```

### `pending()`

Get expenses with pending review status.

**Example:**
```php
$pendingExpenses = $teamleader->expenses()->pending();
```

### `approved()`

Get expenses with approved review status.

**Example:**
```php
$approvedExpenses = $teamleader->expenses()->approved();
```

### `refused()`

Get expenses with refused review status.

**Example:**
```php
$refusedExpenses = $teamleader->expenses()->refused();
```

### `bySourceType()`

Get expenses by source type (incoming invoice, credit note, or receipt).

**Parameters:**
- `sourceTypes` (array|string): One or more source types

**Example:**
```php
// Single type
$invoices = $teamleader->expenses()->bySourceType('incomingInvoice');

// Multiple types
$expenses = $teamleader->expenses()->bySourceType(['incomingInvoice', 'receipt']);
```

### `searchByTerm()`

Search expenses by document number or supplier name.

**Parameters:**
- `term` (string): Search term (case-insensitive)
- `additionalFilters` (array): Additional filters to apply

**Example:**
```php
$expenses = $teamleader->expenses()->searchByTerm('supplier name');
```

### `byDateRange()`

Get expenses within a specific date range.

**Parameters:**
- `startDate` (string): Start date (ISO format)
- `endDate` (string): End date (ISO format)
- `additionalFilters` (array): Additional filters to apply

**Example:**
```php
$expenses = $teamleader->expenses()->byDateRange('2024-01-01', '2024-12-31');
```

### `sent()`

Get expenses that have been sent to bookkeeping.

**Example:**
```php
$sentExpenses = $teamleader->expenses()->sent();
```

### `notSent()`

Get expenses that have not been sent to bookkeeping.

**Example:**
```php
$notSentExpenses = $teamleader->expenses()->notSent();
```

## Filtering

### Available Filters

- **`term`**: Search by document number and supplier name (case-insensitive)
- **`source_types`**: Filter by expense source type(s)
    - Possible values: `incomingInvoice`, `incomingCreditNote`, `receipt`
- **`review_statuses`**: Filter by review status(es)
    - Possible values: `pending`, `approved`, `refused`
- **`bookkeeping_statuses`**: Filter by bookkeeping status(es)
    - Possible values: `sent`, `not_sent`
- **`document_date`**: Filter by document date with various operators
    - Operators: `is_empty`, `between`, `equals`, `before`, `after`

### Filter Examples

```php
// Filter by review status
$approvedExpenses = $teamleader->expenses()->list([
    'review_statuses' => ['approved']
]);

// Filter by multiple review statuses
$expenses = $teamleader->expenses()->list([
    'review_statuses' => ['pending', 'approved']
]);

// Filter by source type
$invoices = $teamleader->expenses()->list([
    'source_types' => ['incomingInvoice']
]);

// Filter by bookkeeping status
$notSentExpenses = $teamleader->expenses()->list([
    'bookkeeping_statuses' => ['not_sent']
]);

// Search by term
$searchResults = $teamleader->expenses()->list([
    'term' => 'Office Supplies Inc'
]);

// Filter by document date (specific date)
$expensesOnDate = $teamleader->expenses()->list([
    'document_date' => [
        'operator' => 'equals',
        'value' => '2024-01-15'
    ]
]);

// Filter by document date range
$expensesInRange = $teamleader->expenses()->list([
    'document_date' => [
        'operator' => 'between',
        'start' => '2024-01-01',
        'end' => '2024-12-31'
    ]
]);

// Filter by date (before)
$expensesBefore = $teamleader->expenses()->list([
    'document_date' => [
        'operator' => 'before',
        'value' => '2024-12-31'
    ]
]);

// Combine filters
$filteredExpenses = $teamleader->expenses()->list([
    'review_statuses' => ['approved'],
    'source_types' => ['incomingInvoice'],
    'bookkeeping_statuses' => ['not_sent']
]);
```

## Pagination

Expenses support standard pagination:

```php
// Get first page with default page size (20)
$expenses = $teamleader->expenses()->list();

// Custom page size and page number
$expenses = $teamleader->expenses()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// Access pagination metadata
$result = $teamleader->expenses()->list([], [
    'page_size' => 20,
    'page_number' => 1
]);

$pagination = $result['meta']['page'] ?? [];
$totalMatches = $result['meta']['matches'] ?? 0;
```

## Usage Examples

### Basic List

Get all expenses with default settings:

```php
$expenses = $teamleader->expenses()->list();
```

### Filtered by Status

Get pending expenses:

```php
$pendingExpenses = $teamleader->expenses()->pending();
```

### Search by Supplier

Search for expenses by supplier name:

```php
$expenses = $teamleader->expenses()->searchByTerm('Acme Corp');
```

### Get Invoices Only

Get only incoming invoices:

```php
$invoices = $teamleader->expenses()->bySourceType('incomingInvoice');
```

### Date Range Query

Get expenses for a specific date range:

```php
$q1Expenses = $teamleader->expenses()->byDateRange('2024-01-01', '2024-03-31');
```

### Complex Query

Get approved incoming invoices that haven't been sent to bookkeeping:

```php
$expenses = $teamleader->expenses()->list([
    'review_statuses' => ['approved'],
    'source_types' => ['incomingInvoice'],
    'bookkeeping_statuses' => ['not_sent']
], [
    'page_size' => 50
]);
```

### Pagination Loop

Process all expenses across multiple pages:

```php
$allExpenses = [];
$pageNumber = 1;

do {
    $result = $teamleader->expenses()->list([], [
        'page_size' => 100,
        'page_number' => $pageNumber
    ]);
    
    $expenses = $result['data'] ?? [];
    $allExpenses = array_merge($allExpenses, $expenses);
    
    $hasMore = count($expenses) === 100;
    $pageNumber++;
} while ($hasMore);
```

## Error Handling

The expenses resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->expenses()->list();

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Expenses API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Response Structure

```json
{
    "data": [
        {
            "source": {
                "type": "incomingInvoice",
                "id": "expense-uuid"
            },
            "origin": {
                "type": "user",
                "id": "user-uuid"
            },
            "title": "Office Supplies",
            "supplier": {
                "type": "company",
                "id": "company-uuid"
            },
            "document_number": "INV-2024-001",
            "document_date": "2024-01-15",
            "due_date": "2024-02-15",
            "currency": {
                "code": "EUR"
            },
            "total": {
                "tax_exclusive": {
                    "amount": 100.00
                },
                "tax_inclusive": {
                    "amount": 121.00
                }
            },
            "company_entity": {
                "type": "department",
                "id": "department-uuid"
            },
            "file": {
                "type": "file",
                "id": "file-uuid"
            },
            "payment_reference": "REF123",
            "review_status": "approved",
            "bookkeeping_status": "not_sent",
            "iban_number": "BE12 3456 7890 1234"
        }
    ],
    "meta": {
        "page": {
            "size": 20,
            "number": 1
        },
        "matches": 42
    }
}
```

## Data Fields

### Source
- **`type`**: Source type (`incomingInvoice`, `incomingCreditNote`, `receipt`)
- **`id`**: Expense UUID

### Origin
- **`type`**: Origin type (`user`, `peppolIncomingDocument`)
- **`id`**: Origin UUID

### Basic Information
- **`title`**: Expense title
- **`document_number`**: Document reference number (nullable)
- **`document_date`**: Date of the document (nullable)
- **`due_date`**: Payment due date (nullable)
- **`payment_reference`**: Payment reference (nullable)
- **`iban_number`**: Bank account IBAN (nullable)

### Supplier
- **`type`**: Supplier type (`company`, `contact`)
- **`id`**: Supplier UUID
- Note: Entire supplier object is nullable

### Financial Information
- **`currency.code`**: Currency code (EUR, USD, etc.)
- **`total.tax_exclusive.amount`**: Amount excluding tax (nullable)
- **`total.tax_inclusive.amount`**: Amount including tax (nullable)

### Status Fields
- **`review_status`**: Review approval status (`pending`, `approved`, `refused`)
- **`bookkeeping_status`**: Bookkeeping sync status (`not_sent`, `sent`)

### Related Entities
- **`company_entity`**: Department information (nullable)
- **`file`**: Attached file reference (nullable)

## Rate Limiting

Expenses API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Expenses are **read-only** in the Teamleader API via this endpoint
- No create, update, or delete operations are supported
- Expenses don't support sideloading/includes
- Sorting is not available for expenses
- The `term` filter searches both document numbers and supplier names
- Document dates can be filtered with various operators for flexible date queries
- Source types represent different expense document types
- Review status indicates approval workflow state
- Bookkeeping status tracks whether the expense has been synced to accounting

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ExpenseController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $expenses = $teamleader->expenses()->list([
            'review_statuses' => ['approved']
        ]);
        
        return view('expenses.index', compact('expenses'));
    }
    
    public function pending(TeamleaderSDK $teamleader)
    {
        $expenses = $teamleader->expenses()->pending();
        
        return view('expenses.pending', compact('expenses'));
    }
    
    public function search(Request $request, TeamleaderSDK $teamleader)
    {
        $term = $request->input('search');
        $expenses = $teamleader->expenses()->searchByTerm($term);
        
        return view('expenses.search', compact('expenses', 'term'));
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

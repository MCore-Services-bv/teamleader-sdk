# Incoming Invoices

Manage incoming invoices (purchase invoices) in Teamleader Focus.

## Overview

The Incoming Invoices resource allows you to manage purchase invoices from your suppliers in Teamleader. These invoices represent expenses that your company needs to pay and can be sent to your bookkeeping system for processing.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [info()](#info)
    - [create() / add()](#create--add)
    - [update()](#update)
    - [delete()](#delete)
    - [approve()](#approve)
    - [refuse()](#refuse)
    - [sendToBookkeeping()](#sendtobookkeeping)
- [Valid Values](#valid-values)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`incomingInvoices`

## Capabilities

- **Pagination**: ❌ Not Supported (use Expenses for listing)
- **Filtering**: ❌ Not Supported (use Expenses for filtering)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `info()`

Get detailed information about a specific incoming invoice.

**Parameters:**
- `id` (string): The incoming invoice UUID

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$invoice = Teamleader::incomingInvoices()->info('invoice-uuid');
```

### `create()` / `add()`

Create a new incoming invoice.

**Required fields:**
- `title` (string): Invoice title/description
- `currency.code` (string): Currency code (e.g., EUR, USD, GBP)
- `total` (object): Invoice total with either:
    - `tax_exclusive.amount` (decimal): Amount excluding tax, OR
    - `tax_inclusive.amount` (decimal): Amount including tax

**Optional fields:**
- `supplier_id` (string): Supplier company UUID
- `document_number` (string): Invoice reference number
- `invoice_date` (string): Invoice date (YYYY-MM-DD)
- `due_date` (string): Payment due date (YYYY-MM-DD)
- `payment_reference` (string): Payment reference/structured communication
- `description` (string): Additional notes or description
- `review_status` (string): `pending`, `approved`, or `refused`

**Example:**
```php
// Basic invoice
$invoice = Teamleader::incomingInvoices()->create([
    'title' => 'Monthly Services',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 1000.00
        ]
    ]
]);

// Complete invoice
$invoice = Teamleader::incomingInvoices()->add([
    'title' => 'Office Supplies',
    'supplier_id' => 'company-uuid',
    'document_number' => 'INV-2024/001',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'payment_reference' => '+++123/4567/89012+++',
    'description' => 'Various office supplies for Q1',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 2500.00
        ]
    ],
    'review_status' => 'pending'
]);
```

### `update()`

Update an existing incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `data` (array): Fields to update (same as create)

**Example:**
```php
Teamleader::incomingInvoices()->update('invoice-uuid', [
    'title' => 'Updated Invoice Title',
    'due_date' => '2024-03-15',
    'review_status' => 'approved'
]);
```

### `delete()`

Delete an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
Teamleader::incomingInvoices()->delete('invoice-uuid');
```

### `approve()`

Approve an incoming invoice for payment.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
Teamleader::incomingInvoices()->approve('invoice-uuid');
```

### `refuse()`

Refuse/reject an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
Teamleader::incomingInvoices()->refuse('invoice-uuid');
```

### `sendToBookkeeping()`

Send an approved invoice to your bookkeeping system.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
Teamleader::incomingInvoices()->sendToBookkeeping('invoice-uuid');
```

## Valid Values

### Currency Codes

Supported currencies: `BAM`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CZK`, `DKK`, `EUR`, `GBP`, `INR`, `ISK`, `JPY`, `MAD`, `MXN`, `NOK`, `PEN`, `PLN`, `RON`, `SEK`, `TRY`, `USD`, `ZAR`

Get the list programmatically:
```php
$currencies = Teamleader::incomingInvoices()->getValidCurrencyCodes();
```

### Review Statuses

- `pending` - Awaiting review
- `approved` - Approved for payment
- `refused` - Rejected/refused

Get the list programmatically:
```php
$statuses = Teamleader::incomingInvoices()->getValidReviewStatuses();
```

## Response Structure

### Invoice Object

```json
{
  "id": "invoice-uuid",
  "title": "Monthly Services",
  "supplier": {
    "type": "company",
    "id": "company-uuid"
  },
  "document_number": "INV-2024/001",
  "invoice_date": "2024-01-15",
  "due_date": "2024-02-15",
  "payment_reference": "+++123/4567/89012+++",
  "description": "Various office supplies",
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
```

## Usage Examples

### Create Invoice from Email

```php
// Parse invoice details from email
$invoiceData = [
    'title' => 'Web Hosting Services',
    'supplier_id' => $supplierId,
    'document_number' => $extractedInvoiceNumber,
    'invoice_date' => $extractedDate,
    'due_date' => date('Y-m-d', strtotime($extractedDate . ' +30 days')),
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => $extractedAmount
        ]
    ]
];

$invoice = Teamleader::incomingInvoices()->create($invoiceData);
```

### Complete Approval Workflow

```php
// Get pending invoices using the Expenses resource
$pending = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'review_statuses' => ['pending']
]);

foreach ($pending['data'] as $expense) {
    $invoice = Teamleader::incomingInvoices()->info($expense['source_id']);
    
    // Validate invoice details
    if ($invoice['total']['tax_inclusive']['amount'] < 5000) {
        // Auto-approve small invoices
        Teamleader::incomingInvoices()->approve($expense['source_id']);
        
        // Send to bookkeeping
        Teamleader::incomingInvoices()->sendToBookkeeping($expense['source_id']);
    } else {
        // Flag for manual review
        echo "Invoice {$invoice['document_number']} requires manual approval\n";
    }
}
```

### Update Invoice Details

```php
$invoiceId = 'invoice-uuid';

// Get current invoice
$invoice = Teamleader::incomingInvoices()->info($invoiceId);

// Update specific fields
Teamleader::incomingInvoices()->update($invoiceId, [
    'due_date' => date('Y-m-d', strtotime('+60 days')),
    'description' => $invoice['description'] . ' - Payment terms extended'
]);
```

### Bulk Approve and Send

```php
$invoiceIds = ['invoice-1', 'invoice-2', 'invoice-3'];

foreach ($invoiceIds as $invoiceId) {
    try {
        // Approve
        Teamleader::incomingInvoices()->approve($invoiceId);
        
        // Send to bookkeeping
        Teamleader::incomingInvoices()->sendToBookkeeping($invoiceId);
        
        echo "Successfully processed invoice: {$invoiceId}\n";
        
    } catch (Exception $e) {
        Log::error("Failed to process invoice {$invoiceId}: " . $e->getMessage());
    }
}
```

### Create Invoice with Validation

```php
function createValidatedInvoice(array $data)
{
    // Validate required fields
    $required = ['title', 'currency', 'total'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new InvalidArgumentException("Missing required field: {$field}");
        }
    }
    
    // Validate currency code
    $validCurrencies = Teamleader::incomingInvoices()->getValidCurrencyCodes();
    if (!in_array($data['currency']['code'], $validCurrencies)) {
        throw new InvalidArgumentException("Invalid currency code");
    }
    
    // Create invoice
    return Teamleader::incomingInvoices()->create($data);
}
```

## Common Use Cases

### Invoice Processing Pipeline

```php
// 1. Create invoice
$invoice = Teamleader::incomingInvoices()->create([
    'title' => 'Monthly Subscription',
    'supplier_id' => 'supplier-uuid',
    'document_number' => 'INV-2024/001',
    'invoice_date' => date('Y-m-d'),
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_exclusive' => ['amount' => 500.00]]
]);

// 2. Review and approve
Teamleader::incomingInvoices()->approve($invoice['data']['id']);

// 3. Send to bookkeeping
Teamleader::incomingInvoices()->sendToBookkeeping($invoice['data']['id']);

// 4. Verify submission
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice($invoice['data']['id']);
$latestStatus = $submissions['data'][0]['status'];

if ($latestStatus === 'confirmed') {
    echo "Invoice successfully processed!";
}
```

### Handle Overdue Invoices

```php
// Get overdue invoices using Expenses
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'document_date' => [
        'operator' => 'before',
        'value' => date('Y-m-d', strtotime('-30 days'))
    ]
]);

foreach ($expenses['data'] as $expense) {
    $invoice = Teamleader::incomingInvoices()->info($expense['source_id']);
    
    if ($invoice['review_status'] === 'pending') {
        // Send reminder
        sendPaymentReminder($invoice);
    }
}
```

### Monthly Invoice Report

```php
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$expenses = Teamleader::expenses()->byDateRange($startOfMonth, $endOfMonth, [
    'source_types' => ['incomingInvoice']
]);

$totalAmount = 0;
$approvedCount = 0;

foreach ($expenses['data'] as $expense) {
    $totalAmount += $expense['total']['tax_inclusive']['amount'];
    
    if ($expense['review_status'] === 'approved') {
        $approvedCount++;
    }
}

echo "Monthly Invoice Summary:\n";
echo "Total Invoices: " . count($expenses['data']) . "\n";
echo "Approved: {$approvedCount}\n";
echo "Total Amount: €" . number_format($totalAmount, 2) . "\n";
```

## Best Practices

1. **Use Expenses for Listing**: To list or search invoices, use the Expenses resource
```php
// Good - use Expenses for listing
$invoices = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice']
]);

// Then get details if needed
$details = Teamleader::incomingInvoices()->info($invoiceId);
```

2. **Always Approve Before Sending**: Invoices should be approved before sending to bookkeeping
```php
Teamleader::incomingInvoices()->approve($invoiceId);
Teamleader::incomingInvoices()->sendToBookkeeping($invoiceId);
```

3. **Validate Data Before Creating**: Use the validation helper
```php
$data = [
    'title' => 'Invoice',
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_exclusive' => ['amount' => 1000.00]]
];

// Validates and creates
$invoice = Teamleader::incomingInvoices()->create($data);
```

4. **Handle Bookkeeping Submissions**: Check submission status after sending
```php
Teamleader::incomingInvoices()->sendToBookkeeping($invoiceId);

// Wait a moment for processing
sleep(2);

$submissions = Teamleader::bookkeepingSubmissions()->forInvoice($invoiceId);
$status = $submissions['data'][0]['status'] ?? 'unknown';
```

5. **Use Descriptive Titles**: Make invoices easy to identify
```php
// Good
'title' => 'AWS Cloud Services - January 2024'

// Avoid
'title' => 'Invoice'
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $invoice = Teamleader::incomingInvoices()->create([
        'title' => 'Monthly Services',
        'currency' => ['code' => 'EUR'],
        'total' => ['tax_exclusive' => ['amount' => 1000.00]]
    ]);
    
    // Approve and send
    Teamleader::incomingInvoices()->approve($invoice['data']['id']);
    Teamleader::incomingInvoices()->sendToBookkeeping($invoice['data']['id']);
    
} catch (\InvalidArgumentException $e) {
    // Validation error
    Log::error('Invalid invoice data: ' . $e->getMessage());
    
} catch (\Exception $e) {
    // API error
    Log::error('Failed to create/process invoice: ' . $e->getMessage());
}

// Handle bookkeeping failures
try {
    Teamleader::incomingInvoices()->sendToBookkeeping($invoiceId);
    
    sleep(2);
    
    $submissions = Teamleader::bookkeepingSubmissions()->forInvoice($invoiceId);
    
    if ($submissions['data'][0]['status'] === 'failed') {
        $error = $submissions['data'][0]['error']['message'];
        Log::error("Bookkeeping submission failed: {$error}");
        // Handle the error...
    }
    
} catch (\Exception $e) {
    Log::error('Error sending to bookkeeping: ' . $e->getMessage());
}
```

## Related Resources

- **[Expenses](expenses.md)** - List and search incoming invoices
- **[Bookkeeping Submissions](bookkeeping-submissions.md)** - Track submission status
- **[Companies](../crm/companies.md)** - Manage suppliers
- **[Incoming Credit Notes](incoming-creditnotes.md)** - Related credit notes
- **[Receipts](receipts.md)** - Other expense types

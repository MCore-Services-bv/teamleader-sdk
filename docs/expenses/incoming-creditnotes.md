# Incoming Credit Notes

Manage incoming credit notes (purchase credit notes) in Teamleader Focus.

## Overview

The Incoming Credit Notes resource allows you to manage credit notes received from your suppliers in Teamleader. These represent corrections or refunds on previously issued purchase invoices and can be sent to your bookkeeping system for processing.

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

`incomingCreditNotes`

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

Get detailed information about a specific incoming credit note.

**Parameters:**
- `id` (string): The incoming credit note UUID

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$creditNote = Teamleader::incomingCreditNotes()->info('credit-note-uuid');
```

### `create()` / `add()`

Create a new incoming credit note.

**Required fields:**
- `title` (string): Credit note title/description
- `currency.code` (string): Currency code (e.g., EUR, USD, GBP)
- `total` (object): Credit note total with either:
    - `tax_exclusive.amount` (decimal): Amount excluding tax, OR
    - `tax_inclusive.amount` (decimal): Amount including tax

**Optional fields:**
- `supplier_id` (string): Supplier company UUID
- `document_number` (string): Credit note reference number
- `credit_note_date` (string): Credit note date (YYYY-MM-DD)
- `description` (string): Additional notes or description
- `related_invoice_id` (string): UUID of the related incoming invoice
- `review_status` (string): `pending`, `approved`, or `refused`

**Example:**
```php
// Basic credit note
$creditNote = Teamleader::incomingCreditNotes()->create([
    'title' => 'Return Credit',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 250.00
        ]
    ]
]);

// Complete credit note
$creditNote = Teamleader::incomingCreditNotes()->add([
    'title' => 'Product Return Credit',
    'supplier_id' => 'company-uuid',
    'document_number' => 'CN-2024/001',
    'credit_note_date' => '2024-01-20',
    'description' => 'Credit for returned defective products',
    'related_invoice_id' => 'original-invoice-uuid',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 500.00
        ]
    ],
    'review_status' => 'pending'
]);
```

### `update()`

Update an existing incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID
- `data` (array): Fields to update (same as create)

**Example:**
```php
Teamleader::incomingCreditNotes()->update('credit-note-uuid', [
    'title' => 'Updated Credit Note Title',
    'credit_note_date' => '2024-01-25',
    'review_status' => 'approved'
]);
```

### `delete()`

Delete an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->delete('credit-note-uuid');
```

### `approve()`

Approve an incoming credit note for processing.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->approve('credit-note-uuid');
```

### `refuse()`

Refuse/reject an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->refuse('credit-note-uuid');
```

### `sendToBookkeeping()`

Send an approved credit note to your bookkeeping system.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->sendToBookkeeping('credit-note-uuid');
```

## Valid Values

### Currency Codes

Supported currencies: `BAM`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CZK`, `DKK`, `EUR`, `GBP`, `INR`, `ISK`, `JPY`, `MAD`, `MXN`, `NOK`, `PEN`, `PLN`, `RON`, `SEK`, `TRY`, `USD`, `ZAR`

Get the list programmatically:
```php
$currencies = Teamleader::incomingCreditNotes()->getValidCurrencyCodes();
```

### Review Statuses

- `pending` - Awaiting review
- `approved` - Approved for processing
- `refused` - Rejected/refused

Get the list programmatically:
```php
$statuses = Teamleader::incomingCreditNotes()->getValidReviewStatuses();
```

## Response Structure

### Credit Note Object

```json
{
  "id": "credit-note-uuid",
  "title": "Product Return Credit",
  "supplier": {
    "type": "company",
    "id": "company-uuid"
  },
  "document_number": "CN-2024/001",
  "credit_note_date": "2024-01-20",
  "description": "Credit for returned defective products",
  "related_invoice": {
    "type": "incoming_invoice",
    "id": "invoice-uuid"
  },
  "currency": {
    "code": "EUR"
  },
  "total": {
    "tax_exclusive": {
      "amount": 500.00,
      "currency": "EUR"
    },
    "tax_inclusive": {
      "amount": 605.00,
      "currency": "EUR"
    }
  },
  "review_status": "approved",
  "bookkeeping_status": "sent",
  "created_at": "2024-01-20T10:00:00+00:00",
  "updated_at": "2024-01-21T14:30:00+00:00"
}
```

## Usage Examples

### Create Credit Note for Returned Items

```php
// Original invoice
$invoiceId = 'invoice-uuid';
$invoice = Teamleader::incomingInvoices()->info($invoiceId);

// Create credit note for partial return
$creditNote = Teamleader::incomingCreditNotes()->create([
    'title' => 'Partial Return Credit',
    'supplier_id' => $invoice['supplier']['id'],
    'document_number' => 'CN-2024/005',
    'credit_note_date' => date('Y-m-d'),
    'description' => 'Credit for 3 defective units',
    'related_invoice_id' => $invoiceId,
    'currency' => $invoice['currency'],
    'total' => [
        'tax_exclusive' => [
            'amount' => 300.00
        ]
    ]
]);

// Approve and process
Teamleader::incomingCreditNotes()->approve($creditNote['data']['id']);
Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNote['data']['id']);
```

### Process Multiple Credit Notes

```php
// Get pending credit notes using Expenses
$pending = Teamleader::expenses()->list([
    'source_types' => ['incomingCreditNote'],
    'review_statuses' => ['pending']
]);

foreach ($pending['data'] as $expense) {
    $creditNote = Teamleader::incomingCreditNotes()->info($expense['source_id']);
    
    // Auto-approve small credit notes
    if ($creditNote['total']['tax_inclusive']['amount'] < 1000) {
        Teamleader::incomingCreditNotes()->approve($expense['source_id']);
        Teamleader::incomingCreditNotes()->sendToBookkeeping($expense['source_id']);
        
        echo "Processed credit note: {$creditNote['document_number']}\n";
    }
}
```

### Link Credit Note to Original Invoice

```php
// Find the original invoice
$originalInvoice = Teamleader::expenses()->searchByTerm('INV-2024/001', [
    'source_types' => ['incomingInvoice']
]);

if (!empty($originalInvoice['data'])) {
    $invoiceId = $originalInvoice['data'][0]['source_id'];
    
    // Create linked credit note
    $creditNote = Teamleader::incomingCreditNotes()->create([
        'title' => 'Discount Adjustment',
        'supplier_id' => $originalInvoice['data'][0]['supplier']['id'],
        'related_invoice_id' => $invoiceId,
        'currency' => ['code' => 'EUR'],
        'total' => ['tax_exclusive' => ['amount' => 100.00]]
    ]);
}
```

### Update Credit Note Before Approval

```php
$creditNoteId = 'credit-note-uuid';

// Get current credit note
$creditNote = Teamleader::incomingCreditNotes()->info($creditNoteId);

// Update with corrected information
Teamleader::incomingCreditNotes()->update($creditNoteId, [
    'document_number' => 'CN-2024/001-CORRECTED',
    'description' => $creditNote['description'] . ' - Amount corrected',
    'total' => [
        'tax_exclusive' => [
            'amount' => 550.00  // Updated amount
        ]
    ]
]);

// Now approve
Teamleader::incomingCreditNotes()->approve($creditNoteId);
```

### Bulk Process Credit Notes

```php
$creditNoteIds = ['cn-1', 'cn-2', 'cn-3'];

foreach ($creditNoteIds as $creditNoteId) {
    try {
        // Approve
        Teamleader::incomingCreditNotes()->approve($creditNoteId);
        
        // Send to bookkeeping
        Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNoteId);
        
        echo "Successfully processed credit note: {$creditNoteId}\n";
        
    } catch (Exception $e) {
        Log::error("Failed to process credit note {$creditNoteId}: " . $e->getMessage());
    }
}
```

## Common Use Cases

### Reconcile Credit Notes with Invoices

```php
// Get all approved credit notes
$creditNotes = Teamleader::expenses()->list([
    'source_types' => ['incomingCreditNote'],
    'review_statuses' => ['approved']
]);

foreach ($creditNotes['data'] as $expense) {
    $creditNote = Teamleader::incomingCreditNotes()->info($expense['source_id']);
    
    if (isset($creditNote['related_invoice']['id'])) {
        $invoice = Teamleader::incomingInvoices()->info(
            $creditNote['related_invoice']['id']
        );
        
        echo "Credit Note: {$creditNote['document_number']}\n";
        echo "Against Invoice: {$invoice['document_number']}\n";
        echo "Credit Amount: {$creditNote['total']['tax_inclusive']['amount']}\n";
        echo "---\n";
    }
}
```

### Monthly Credit Note Report

```php
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$creditNotes = Teamleader::expenses()->byDateRange($startOfMonth, $endOfMonth, [
    'source_types' => ['incomingCreditNote']
]);

$totalCreditAmount = 0;
$approvedCount = 0;

foreach ($creditNotes['data'] as $expense) {
    $totalCreditAmount += $expense['total']['tax_inclusive']['amount'];
    
    if ($expense['review_status'] === 'approved') {
        $approvedCount++;
    }
}

echo "Monthly Credit Note Summary:\n";
echo "Total Credit Notes: " . count($creditNotes['data']) . "\n";
echo "Approved: {$approvedCount}\n";
echo "Total Credit Amount: €" . number_format($totalCreditAmount, 2) . "\n";
```

### Automated Credit Note Processing

```php
// Get all pending credit notes
$pending = Teamleader::expenses()->list([
    'source_types' => ['incomingCreditNote'],
    'review_statuses' => ['pending']
]);

foreach ($pending['data'] as $expense) {
    $creditNote = Teamleader::incomingCreditNotes()->info($expense['source_id']);
    
    // Business rules for auto-approval
    $autoApprove = false;
    
    // Rule 1: Small amounts under threshold
    if ($creditNote['total']['tax_inclusive']['amount'] < 100) {
        $autoApprove = true;
    }
    
    // Rule 2: Has related invoice
    if (isset($creditNote['related_invoice']['id'])) {
        $invoice = Teamleader::incomingInvoices()->info(
            $creditNote['related_invoice']['id']
        );
        
        // Credit is less than 10% of original invoice
        if ($creditNote['total']['tax_inclusive']['amount'] < 
            ($invoice['total']['tax_inclusive']['amount'] * 0.1)) {
            $autoApprove = true;
        }
    }
    
    if ($autoApprove) {
        Teamleader::incomingCreditNotes()->approve($expense['source_id']);
        Teamleader::incomingCreditNotes()->sendToBookkeeping($expense['source_id']);
        
        echo "Auto-approved: {$creditNote['document_number']}\n";
    }
}
```

## Best Practices

1. **Use Expenses for Listing**: To list or search credit notes, use the Expenses resource
```php
// Good - use Expenses for listing
$creditNotes = Teamleader::expenses()->list([
    'source_types' => ['incomingCreditNote']
]);

// Then get details if needed
$details = Teamleader::incomingCreditNotes()->info($creditNoteId);
```

2. **Link to Original Invoices**: Always link credit notes to their original invoices when possible
```php
$creditNote = Teamleader::incomingCreditNotes()->create([
    'title' => 'Return Credit',
    'related_invoice_id' => $originalInvoiceId,  // Important!
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_exclusive' => ['amount' => 250.00]]
]);
```

3. **Approve Before Sending**: Always approve credit notes before sending to bookkeeping
```php
Teamleader::incomingCreditNotes()->approve($creditNoteId);
Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNoteId);
```

4. **Verify Submission Status**: Check bookkeeping submission status after sending
```php
Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNoteId);

sleep(2);

$submissions = Teamleader::bookkeepingSubmissions()->forCreditNote($creditNoteId);
$status = $submissions['data'][0]['status'] ?? 'unknown';
```

5. **Use Descriptive Titles**: Make credit notes easy to identify and understand
```php
// Good
'title' => 'Return Credit - Defective Products - INV-2024/001'

// Avoid
'title' => 'Credit Note'
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $creditNote = Teamleader::incomingCreditNotes()->create([
        'title' => 'Product Return',
        'currency' => ['code' => 'EUR'],
        'total' => ['tax_exclusive' => ['amount' => 250.00]]
    ]);
    
    // Approve and send
    Teamleader::incomingCreditNotes()->approve($creditNote['data']['id']);
    Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNote['data']['id']);
    
} catch (\InvalidArgumentException $e) {
    // Validation error
    Log::error('Invalid credit note data: ' . $e->getMessage());
    
} catch (\Exception $e) {
    // API error
    Log::error('Failed to create/process credit note: ' . $e->getMessage());
}

// Handle bookkeeping failures
try {
    Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNoteId);
    
    sleep(2);
    
    $submissions = Teamleader::bookkeepingSubmissions()->forCreditNote($creditNoteId);
    
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

- **[Expenses](expenses.md)** - List and search incoming credit notes
- **[Bookkeeping Submissions](bookkeeping-submissions.md)** - Track submission status
- **[Companies](../crm/companies.md)** - Manage suppliers
- **[Incoming Invoices](incoming-invoices.md)** - Related invoices
- **[Receipts](receipts.md)** - Other expense types

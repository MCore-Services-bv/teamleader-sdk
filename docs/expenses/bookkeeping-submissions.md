# Bookkeeping Submissions

Manage bookkeeping submissions for expense documents in Teamleader Focus.

## Overview

The Bookkeeping Submissions resource provides read-only access to the history of bookkeeping submissions for expense documents (incoming invoices, incoming credit notes, and receipts). When an expense document is sent to bookkeeping, a submission record is created to track the status and details of that submission.

**Important:** This resource is read-only and requires a `subject` filter. You cannot create, update, or delete bookkeeping submissions directly. Submissions are automatically created when you send expense documents to bookkeeping.

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

`bookkeepingSubmissions`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Required (subject with id and type)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (automatic)
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get bookkeeping submissions for a specific expense document. The `subject` filter is required.

**Parameters:**
- `filters` (array): Required filters
    - `subject` (object, required): Document to get submissions for
        - `id` (string): Document UUID
        - `type` (string): `incoming_invoice`, `incoming_credit_note`, or `receipt`

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get submissions for a document
$submissions = Teamleader::bookkeepingSubmissions()->list([
    'subject' => [
        'id' => 'invoice-uuid',
        'type' => 'incoming_invoice'
    ]
]);
```

## Helper Methods

The Bookkeeping Submissions resource provides convenient helper methods:

### `forDocument()`

Get submissions for any expense document type.

```php
$submissions = Teamleader::bookkeepingSubmissions()->forDocument(
    'document-uuid',
    'incoming_invoice'
);
```

### `forInvoice()`

Get submissions for an incoming invoice.

```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');
```

### `forCreditNote()`

Get submissions for an incoming credit note.

```php
$submissions = Teamleader::bookkeepingSubmissions()->forCreditNote('credit-note-uuid');
```

### `forReceipt()`

Get submissions for a receipt.

```php
$submissions = Teamleader::bookkeepingSubmissions()->forReceipt('receipt-uuid');
```

### `byStatus()`

Filter submissions by status for a specific document.

```php
// Get only confirmed submissions
$confirmed = Teamleader::bookkeepingSubmissions()->byStatus(
    'invoice-uuid',
    'incoming_invoice',
    'confirmed'
);

// Get failed submissions
$failed = Teamleader::bookkeepingSubmissions()->byStatus(
    'invoice-uuid',
    'incoming_invoice',
    'failed'
);
```

Valid statuses:
- `sending` - Submission is in progress
- `confirmed` - Successfully sent to bookkeeping
- `failed` - Submission failed

### `getStats()`

Get statistics about submissions for a document.

```php
$stats = Teamleader::bookkeepingSubmissions()->getStats('invoice-uuid', 'incoming_invoice');

// Returns:
// [
//     'total_submissions' => 3,
//     'confirmed_count' => 2,
//     'failed_count' => 1,
//     'sending_count' => 0,
//     'latest_submission' => [...],
//     'first_submission' => [...]
// ]
```

## Filtering

The `subject` filter is required for all bookkeeping submission queries:

- `subject.id` (string, required) - UUID of the expense document
- `subject.type` (string, required) - Document type:
    - `incoming_invoice`
    - `incoming_credit_note`
    - `receipt`

**Example:**
```php
$submissions = Teamleader::bookkeepingSubmissions()->list([
    'subject' => [
        'id' => 'document-uuid',
        'type' => 'incoming_invoice'
    ]
]);
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "submission-uuid",
      "subject": {
        "type": "incoming_invoice",
        "id": "invoice-uuid"
      },
      "status": "confirmed",
      "created_at": "2024-01-20T10:00:00+00:00",
      "confirmed_at": "2024-01-20T10:05:00+00:00",
      "reference": "BK-2024-001",
      "bookkeeping_account": {
        "id": "account-uuid",
        "code": "6000",
        "description": "Purchases"
      }
    },
    {
      "id": "submission-uuid-2",
      "subject": {
        "type": "incoming_invoice",
        "id": "invoice-uuid"
      },
      "status": "failed",
      "created_at": "2024-01-15T14:00:00+00:00",
      "error": {
        "code": "validation_error",
        "message": "Missing required field: purchase_order"
      }
    }
  ]
}
```

### Submission Statuses

- **sending** - The submission is currently being processed
- **confirmed** - Successfully sent and confirmed by bookkeeping system
- **failed** - The submission failed (includes error details)

## Usage Examples

### Check Submission History

```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');

if (count($submissions['data']) > 0) {
    $latest = $submissions['data'][0];
    echo "Latest submission status: " . $latest['status'];
    
    if ($latest['status'] === 'confirmed') {
        echo "\nSent to bookkeeping on: " . $latest['confirmed_at'];
        echo "\nReference: " . $latest['reference'];
    }
}
```

### Handle Failed Submissions

```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');

foreach ($submissions['data'] as $submission) {
    if ($submission['status'] === 'failed') {
        Log::error('Bookkeeping submission failed', [
            'submission_id' => $submission['id'],
            'invoice_id' => $submission['subject']['id'],
            'error' => $submission['error']['message']
        ]);
        
        // Handle the error and potentially retry
    }
}
```

### Get Submission Statistics

```php
$stats = Teamleader::bookkeepingSubmissions()->getStats(
    'invoice-uuid',
    'incoming_invoice'
);

echo "Total Submissions: {$stats['total_submissions']}" . PHP_EOL;
echo "Confirmed: {$stats['confirmed_count']}" . PHP_EOL;
echo "Failed: {$stats['failed_count']}" . PHP_EOL;
echo "In Progress: {$stats['sending_count']}" . PHP_EOL;

if ($stats['latest_submission']) {
    echo "Latest Status: {$stats['latest_submission']['status']}" . PHP_EOL;
}
```

### Verify Successful Submission

```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');

$hasConfirmedSubmission = false;
foreach ($submissions['data'] as $submission) {
    if ($submission['status'] === 'confirmed') {
        $hasConfirmedSubmission = true;
        break;
    }
}

if ($hasConfirmedSubmission) {
    echo "Invoice successfully sent to bookkeeping";
} else {
    echo "Invoice not yet sent or submission failed";
}
```

### Monitor Recent Submissions

```php
$invoices = Teamleader::incomingInvoices()->list([
    'bookkeeping_statuses' => ['sent']
]);

foreach ($invoices['data'] as $invoice) {
    $submissions = Teamleader::bookkeepingSubmissions()->forInvoice($invoice['id']);
    
    if (count($submissions['data']) > 0) {
        $latest = $submissions['data'][0];
        
        echo "Invoice: {$invoice['document_number']} - ";
        echo "Status: {$latest['status']}" . PHP_EOL;
        
        if ($latest['status'] === 'failed') {
            echo "  Error: {$latest['error']['message']}" . PHP_EOL;
        }
    }
}
```

## Common Use Cases

### Audit Trail for Bookkeeping

```php
function getBookkeepingAuditTrail($documentId, $documentType)
{
    $submissions = Teamleader::bookkeepingSubmissions()->forDocument(
        $documentId,
        $documentType
    );
    
    $auditTrail = [];
    foreach ($submissions['data'] as $submission) {
        $auditTrail[] = [
            'date' => $submission['created_at'],
            'status' => $submission['status'],
            'reference' => $submission['reference'] ?? null,
            'confirmed_at' => $submission['confirmed_at'] ?? null,
            'error' => $submission['error'] ?? null
        ];
    }
    
    return $auditTrail;
}
```

### Retry Failed Submissions

```php
$submissions = Teamleader::bookkeepingSubmissions()->byStatus(
    'invoice-uuid',
    'incoming_invoice',
    'failed'
);

if (count($submissions['data']) > 0) {
    // Get the invoice details to understand the error
    $invoice = Teamleader::incomingInvoices()->info('invoice-uuid');
    
    // Fix any issues with the invoice
    // Then retry sending to bookkeeping
    Teamleader::incomingInvoices()->sendToBookkeeping('invoice-uuid');
}
```

### Dashboard Statistics

```php
$invoices = Teamleader::incomingInvoices()->list([
    'review_statuses' => ['approved']
]);

$totalSubmissions = 0;
$failedSubmissions = 0;
$confirmedSubmissions = 0;

foreach ($invoices['data'] as $invoice) {
    $stats = Teamleader::bookkeepingSubmissions()->getStats(
        $invoice['id'],
        'incoming_invoice'
    );
    
    $totalSubmissions += $stats['total_submissions'];
    $failedSubmissions += $stats['failed_count'];
    $confirmedSubmissions += $stats['confirmed_count'];
}

echo "Dashboard Statistics:" . PHP_EOL;
echo "Total Submissions: {$totalSubmissions}" . PHP_EOL;
echo "Confirmed: {$confirmedSubmissions}" . PHP_EOL;
echo "Failed: {$failedSubmissions}" . PHP_EOL;
```

## Best Practices

1. **Always Check Latest Submission**: Submissions are returned in reverse chronological order, so the first item is the most recent
```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');
$latestStatus = $submissions['data'][0]['status'] ?? null;
```

2. **Use Helper Methods**: Use specific helper methods for cleaner code
```php
// Good
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');

// Avoid
$submissions = Teamleader::bookkeepingSubmissions()->list([
    'subject' => ['id' => 'invoice-uuid', 'type' => 'incoming_invoice']
]);
```

3. **Handle Missing Submissions**: Not all documents have submissions
```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');

if (empty($submissions['data'])) {
    // Document has never been sent to bookkeeping
}
```

4. **Monitor Failed Submissions**: Implement monitoring for failed submissions
```php
$stats = Teamleader::bookkeepingSubmissions()->getStats('invoice-uuid', 'incoming_invoice');

if ($stats['failed_count'] > 0) {
    // Alert or log the failed submissions
}
```

5. **Use Stats for Quick Checks**: The `getStats()` method is efficient for getting overview information
```php
$stats = Teamleader::bookkeepingSubmissions()->getStats('invoice-uuid', 'incoming_invoice');
$hasConfirmed = $stats['confirmed_count'] > 0;
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');
    
    if (empty($submissions['data'])) {
        // No submissions found - document never sent to bookkeeping
        echo "This invoice has not been sent to bookkeeping yet";
    }
    
} catch (\InvalidArgumentException $e) {
    // Missing required subject filter
    Log::error('Invalid bookkeeping submission query: ' . $e->getMessage());
    
} catch (\Exception $e) {
    // Handle API errors
    Log::error('Failed to fetch bookkeeping submissions: ' . $e->getMessage());
}

// Handle failed submission with retry logic
try {
    $stats = Teamleader::bookkeepingSubmissions()->getStats('invoice-uuid', 'incoming_invoice');
    
    if ($stats['failed_count'] > 0) {
        // Attempt to resend
        Teamleader::incomingInvoices()->sendToBookkeeping('invoice-uuid');
    }
    
} catch (\Exception $e) {
    Log::error('Failed to retry bookkeeping submission: ' . $e->getMessage());
}
```

## Related Resources

- **[Incoming Invoices](incoming-invoices.md)** - Send invoices to bookkeeping
- **[Incoming Credit Notes](incoming-creditnotes.md)** - Send credit notes to bookkeeping
- **[Receipts](receipts.md)** - Send receipts to bookkeeping
- **[Expenses](expenses.md)** - Overview of all expense documents

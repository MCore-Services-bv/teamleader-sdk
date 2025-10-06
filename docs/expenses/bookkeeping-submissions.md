# Bookkeeping Submissions

Manage bookkeeping submissions for expense documents in Teamleader Focus. This resource provides read-only access to submission records that track when expense documents (incoming invoices, credit notes, and receipts) are sent to your bookkeeping system.

## Endpoint

`bookkeepingSubmissions`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported (subject filter is REQUIRED)
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Important Notes

⚠️ **REQUIRED FILTER**: This endpoint requires a `subject` filter with both `id` (document UUID) and `type` (document type) fields. You cannot list all submissions without specifying a document.

## Available Methods

### `list()`

Get bookkeeping submissions for a specific financial document. **Requires subject filter.**

**Parameters:**
- `filters` (array): Must include `subject` with `id` and `type`
- `options` (array): Additional options (not used for this endpoint)

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->list([
    'subject' => [
        'id' => 'document-uuid',
        'type' => 'incoming_invoice'
    ]
]);
```

### `forDocument()`

Get submissions for a specific financial document by ID and type.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document (`incoming_invoice`, `incoming_credit_note`, or `receipt`)

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->forDocument(
    'document-uuid',
    'incoming_invoice'
);
```

### `forInvoice()`

Get submissions for an incoming invoice (convenience method).

**Parameters:**
- `invoiceId` (string): UUID of the incoming invoice

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->forInvoice('invoice-uuid');
```

### `forCreditNote()`

Get submissions for an incoming credit note (convenience method).

**Parameters:**
- `creditNoteId` (string): UUID of the incoming credit note

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->forCreditNote('credit-note-uuid');
```

### `forReceipt()`

Get submissions for a receipt (convenience method).

**Parameters:**
- `receiptId` (string): UUID of the receipt

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->forReceipt('receipt-uuid');
```

### `byStatus()`

Filter submissions by status for a specific document.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document
- `status` (string): Status to filter by (`sending`, `confirmed`, or `failed`)

**Example:**
```php
$confirmed = $teamleader->bookkeepingSubmissions()->byStatus(
    'document-uuid',
    'incoming_invoice',
    'confirmed'
);
```

### `confirmed()`

Get confirmed submissions for a document.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->confirmed(
    'document-uuid',
    'incoming_invoice'
);
```

### `failed()`

Get failed submissions for a document.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->failed(
    'document-uuid',
    'incoming_invoice'
);
```

### `sending()`

Get submissions currently being sent for a document.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document

**Example:**
```php
$submissions = $teamleader->bookkeepingSubmissions()->sending(
    'document-uuid',
    'incoming_invoice'
);
```

### `latest()`

Get the most recent submission for a document.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document

**Returns:** Single submission array or null if none exist

**Example:**
```php
$latestSubmission = $teamleader->bookkeepingSubmissions()->latest(
    'document-uuid',
    'incoming_invoice'
);

if ($latestSubmission) {
    echo "Latest status: " . $latestSubmission['status'];
    echo "Sent to: " . $latestSubmission['email_address'];
}
```

### `hasConfirmed()`

Check if a document has any confirmed submissions.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document

**Returns:** Boolean

**Example:**
```php
$isConfirmed = $teamleader->bookkeepingSubmissions()->hasConfirmed(
    'document-uuid',
    'incoming_invoice'
);

if ($isConfirmed) {
    echo "Document has been successfully sent to bookkeeping";
}
```

### `hasFailed()`

Check if a document has any failed submissions.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document

**Returns:** Boolean

**Example:**
```php
$hasFailed = $teamleader->bookkeepingSubmissions()->hasFailed(
    'document-uuid',
    'incoming_invoice'
);

if ($hasFailed) {
    echo "Warning: Document has failed bookkeeping submissions";
}
```

### `statistics()`

Get comprehensive statistics for all submissions of a document.

**Parameters:**
- `documentId` (string): UUID of the financial document
- `documentType` (string): Type of document

**Returns:** Array with statistics including total count, status breakdown, email addresses, and latest/first submissions

**Example:**
```php
$stats = $teamleader->bookkeepingSubmissions()->statistics(
    'document-uuid',
    'incoming_invoice'
);

echo "Total submissions: " . $stats['total'];
echo "Confirmed: " . $stats['by_status']['confirmed'];
echo "Failed: " . $stats['by_status']['failed'];
echo "Email addresses: " . implode(', ', $stats['email_addresses']);
```

## Filtering

### Required Filter

The `subject` filter is **REQUIRED** and must include both fields:

- **`subject.id`** (string, required): UUID of the financial document
- **`subject.type`** (string, required): Type of document
    - Possible values: `incoming_invoice`, `incoming_credit_note`, `receipt`

### Filter Examples

```php
// Using the list method directly (not recommended - use convenience methods instead)
$submissions = $teamleader->bookkeepingSubmissions()->list([
    'subject' => [
        'id' => 'f9a1c2e4-8d3b-4a5e-9f1c-2e4d8a3b5f1c',
        'type' => 'incoming_invoice'
    ]
]);

// Recommended: Use convenience methods
$invoiceSubmissions = $teamleader->bookkeepingSubmissions()->forInvoice('invoice-uuid');
$creditNoteSubmissions = $teamleader->bookkeepingSubmissions()->forCreditNote('credit-note-uuid');
$receiptSubmissions = $teamleader->bookkeepingSubmissions()->forReceipt('receipt-uuid');
```

## Document Types

Valid document types for the `subject.type` field:

- **`incoming_invoice`**: Incoming invoices from suppliers
- **`incoming_credit_note`**: Credit notes received from suppliers
- **`receipt`**: Receipt documents

## Submission Statuses

Bookkeeping submissions can have three statuses:

- **`sending`**: Submission is currently being sent to the bookkeeping system
- **`confirmed`**: Submission was successfully sent and confirmed
- **`failed`**: Submission failed to send

## Usage Examples

### Basic Usage

Get all submissions for an incoming invoice:

```php
$submissions = $teamleader->bookkeepingSubmissions()->forInvoice('invoice-uuid');

foreach ($submissions['data'] as $submission) {
    echo "Submission ID: " . $submission['id'] . "\n";
    echo "Status: " . $submission['status'] . "\n";
    echo "Sent to: " . $submission['email_address'] . "\n";
    echo "Created: " . $submission['created_at'] . "\n\n";
}
```

### Check Submission Status

Check if a document was successfully sent:

```php
$documentId = 'invoice-uuid';
$documentType = 'incoming_invoice';

if ($teamleader->bookkeepingSubmissions()->hasConfirmed($documentId, $documentType)) {
    echo "✓ Document successfully sent to bookkeeping";
} elseif ($teamleader->bookkeepingSubmissions()->hasFailed($documentId, $documentType)) {
    echo "✗ Document submission failed - manual intervention needed";
} else {
    echo "⟳ Document submission in progress or not yet sent";
}
```

### Get Latest Submission Status

Get the most recent submission to check current status:

```php
$latest = $teamleader->bookkeepingSubmissions()->latest('invoice-uuid', 'incoming_invoice');

if ($latest) {
    switch ($latest['status']) {
        case 'confirmed':
            echo "Document successfully sent on " . $latest['created_at'];
            break;
        case 'failed':
            echo "Last submission failed - sent to " . $latest['email_address'];
            break;
        case 'sending':
            echo "Submission currently in progress";
            break;
    }
}
```

### Get Submission Statistics

Get comprehensive statistics for a document:

```php
$stats = $teamleader->bookkeepingSubmissions()->statistics('invoice-uuid', 'incoming_invoice');

echo "Submission Summary:\n";
echo "Total attempts: " . $stats['total'] . "\n";
echo "Confirmed: " . $stats['by_status']['confirmed'] . "\n";
echo "Failed: " . $stats['by_status']['failed'] . "\n";
echo "Sending: " . $stats['by_status']['sending'] . "\n";
echo "\nEmail addresses used:\n";
foreach ($stats['email_addresses'] as $email) {
    echo "  - " . $email . "\n";
}

if ($stats['latest_submission']) {
    echo "\nLatest submission: " . $stats['latest_submission']['status'];
    echo " on " . $stats['latest_submission']['created_at'];
}
```

### Filter by Status

Get only failed submissions for a document:

```php
$failedSubmissions = $teamleader->bookkeepingSubmissions()->failed(
    'invoice-uuid',
    'incoming_invoice'
);

if (!empty($failedSubmissions['data'])) {
    echo "Failed submissions detected:\n";
    foreach ($failedSubmissions['data'] as $submission) {
        echo "- Failed on: " . $submission['created_at'] . "\n";
        echo "  Email: " . $submission['email_address'] . "\n";
    }
}
```

### Working with Different Document Types

```php
// For incoming invoices
$invoiceSubmissions = $teamleader->bookkeepingSubmissions()->forInvoice('invoice-uuid');

// For credit notes
$creditNoteSubmissions = $teamleader->bookkeepingSubmissions()->forCreditNote('credit-note-uuid');

// For receipts
$receiptSubmissions = $teamleader->bookkeepingSubmissions()->forReceipt('receipt-uuid');

// Generic method for any document type
$submissions = $teamleader->bookkeepingSubmissions()->forDocument(
    'document-uuid',
    'incoming_invoice'
);
```

## Error Handling

The bookkeeping submissions resource follows standard SDK error handling:

```php
try {
    $submissions = $teamleader->bookkeepingSubmissions()->forInvoice('invoice-uuid');
    
    if (isset($submissions['error']) && $submissions['error']) {
        $errorMessage = $submissions['message'] ?? 'Unknown error';
        Log::error("Bookkeeping submissions API error: {$errorMessage}");
    }
} catch (InvalidArgumentException $e) {
    // Handle validation errors (e.g., missing required fields)
    Log::error("Validation error: " . $e->getMessage());
}
```

### Validation Errors

The SDK will throw `InvalidArgumentException` for:

- Missing subject filter
- Invalid document type
- Invalid status value
- Missing document ID

## Response Structure

```json
{
    "data": [
        {
            "id": "submission-uuid",
            "subject": {
                "id": "document-uuid",
                "type": "incoming_invoice"
            },
            "email_address": "bookkeeping@company.com",
            "status": "confirmed",
            "created_at": "2024-01-15T10:30:00+00:00"
        },
        {
            "id": "submission-uuid-2",
            "subject": {
                "id": "document-uuid",
                "type": "incoming_invoice"
            },
            "email_address": "accounting@company.com",
            "status": "failed",
            "created_at": "2024-01-14T15:20:00+00:00"
        }
    ]
}
```

## Data Fields

### Submission Fields

- **`id`**: UUID of the bookkeeping submission
- **`subject.id`**: UUID of the financial document
- **`subject.type`**: Type of document (`incoming_invoice`, `incoming_credit_note`, `receipt`)
- **`email_address`**: Email address where the submission was sent
- **`status`**: Current status (`sending`, `confirmed`, `failed`)
- **`created_at`**: Timestamp when the submission was created (ISO 8601 format)

## Rate Limiting

Bookkeeping submissions API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Bookkeeping submissions are **read-only** - you cannot create, update, or delete submissions via the API
- The `subject` filter with both `id` and `type` is **REQUIRED** for all queries
- No pagination is available - all submissions for a document are returned
- Sorting is not supported - use the `latest()` method or client-side sorting
- Submissions track the history of attempts to send documents to your bookkeeping system
- Multiple submissions may exist for a single document (e.g., after retries)
- The `created_at` timestamp shows when each submission attempt was made
- Use the `statistics()` method to get a comprehensive overview of all submissions

## Best Practices

1. **Always check the latest status** before assuming a document was sent:
   ```php
   $latest = $teamleader->bookkeepingSubmissions()->latest($id, $type);
   $status = $latest['status'] ?? 'unknown';
   ```

2. **Use convenience methods** instead of the raw `list()` method:
   ```php
   // Good
   $submissions = $teamleader->bookkeepingSubmissions()->forInvoice($id);
   
   // Less readable
   $submissions = $teamleader->bookkeepingSubmissions()->list([
       'subject' => ['id' => $id, 'type' => 'incoming_invoice']
   ]);
   ```

3. **Monitor failed submissions** to identify issues:
   ```php
   if ($teamleader->bookkeepingSubmissions()->hasFailed($id, $type)) {
       // Alert or log for manual review
   }
   ```

4. **Use statistics for reporting**:
   ```php
   $stats = $teamleader->bookkeepingSubmissions()->statistics($id, $type);
   // Build dashboards or reports from the statistics
   ```

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class BookkeepingController extends Controller
{
    public function checkSubmissionStatus(TeamleaderSDK $teamleader, string $expenseId)
    {
        $latest = $teamleader->bookkeepingSubmissions()->latest(
            $expenseId,
            'incoming_invoice'
        );
        
        if (!$latest) {
            return response()->json(['status' => 'not_submitted']);
        }
        
        return response()->json([
            'status' => $latest['status'],
            'sent_to' => $latest['email_address'],
            'timestamp' => $latest['created_at']
        ]);
    }
    
    public function submissionHistory(TeamleaderSDK $teamleader, string $expenseId)
    {
        $submissions = $teamleader->bookkeepingSubmissions()->forInvoice($expenseId);
        
        return view('bookkeeping.history', [
            'submissions' => $submissions['data'],
            'expense_id' => $expenseId
        ]);
    }
    
    public function statistics(TeamleaderSDK $teamleader)
    {
        // Get statistics for multiple documents
        $expenses = Expense::all();
        $stats = [];
        
        foreach ($expenses as $expense) {
            $stats[$expense->id] = $teamleader->bookkeepingSubmissions()->statistics(
                $expense->teamleader_id,
                'incoming_invoice'
            );
        }
        
        return view('bookkeeping.statistics', compact('stats'));
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

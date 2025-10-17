# Receipts

Manage expense receipts in Teamleader Focus.

## Overview

The Receipts resource allows you to manage expense receipts in Teamleader. Receipts represent smaller expenses (typically without VAT details) such as meals, parking, office supplies, and other day-to-day business expenses that can be sent to your bookkeeping system.

**Note:** Unlike invoices and credit notes, receipts use **tax-inclusive** amounts only as they typically don't itemize VAT.

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

`receipts`

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

Get detailed information about a specific receipt.

**Parameters:**
- `id` (string): The receipt UUID

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$receipt = Teamleader::receipts()->info('receipt-uuid');
```

### `create()` / `add()`

Create a new expense receipt.

**Required fields:**
- `title` (string): Receipt title/description
- `currency.code` (string): Currency code (e.g., EUR, USD, GBP)
- `total.tax_inclusive.amount` (decimal): Total amount including any tax

**Optional fields:**
- `supplier_id` (string): Supplier company UUID
- `document_number` (string): Receipt reference number
- `receipt_date` (string): Receipt date (YYYY-MM-DD)
- `description` (string): Additional notes or description
- `review_status` (string): `pending`, `approved`, or `refused`

**Example:**
```php
// Basic receipt
$receipt = Teamleader::receipts()->create([
    'title' => 'Office Lunch',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => 45.50
        ]
    ]
]);

// Using add() helper
$receipt = Teamleader::receipts()->add([
    'title' => 'Parking Fee',
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_inclusive' => ['amount' => 15.00]]
]);

// Complete receipt
$receipt = Teamleader::receipts()->add([
    'title' => 'Business Dinner',
    'supplier_id' => 'restaurant-uuid',
    'document_number' => 'REC-2024/001',
    'receipt_date' => '2024-01-15',
    'description' => 'Client meeting at Restaurant XYZ',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => 125.00
        ]
    ],
    'review_status' => 'pending'
]);
```

### `update()`

Update an existing receipt.

**Parameters:**
- `id` (string): Receipt UUID
- `data` (array): Fields to update (same as create)

**Example:**
```php
Teamleader::receipts()->update('receipt-uuid', [
    'title' => 'Updated Receipt Title',
    'receipt_date' => '2024-01-16',
    'description' => 'Additional details added',
    'review_status' => 'approved'
]);
```

### `delete()`

Delete a receipt.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->delete('receipt-uuid');
```

### `approve()`

Approve a receipt for processing.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->approve('receipt-uuid');
```

### `refuse()`

Refuse/reject a receipt.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->refuse('receipt-uuid');
```

### `sendToBookkeeping()`

Send an approved receipt to your bookkeeping system.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->sendToBookkeeping('receipt-uuid');
```

## Valid Values

### Currency Codes

Supported currencies: `BAM`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CZK`, `DKK`, `EUR`, `GBP`, `INR`, `ISK`, `JPY`, `MAD`, `MXN`, `NOK`, `PEN`, `PLN`, `RON`, `SEK`, `TRY`, `USD`, `ZAR`

Get the list programmatically:
```php
$currencies = Teamleader::receipts()->getValidCurrencyCodes();
```

### Review Statuses

- `pending` - Awaiting review
- `approved` - Approved for processing
- `refused` - Rejected/refused

Get the list programmatically:
```php
$statuses = Teamleader::receipts()->getValidReviewStatuses();
```

## Response Structure

### Receipt Object

```json
{
  "id": "receipt-uuid",
  "title": "Business Lunch",
  "supplier": {
    "type": "company",
    "id": "company-uuid"
  },
  "document_number": "REC-2024/001",
  "receipt_date": "2024-01-15",
  "description": "Client meeting lunch",
  "currency": {
    "code": "EUR"
  },
  "total": {
    "tax_inclusive": {
      "amount": 125.00,
      "currency": "EUR"
    }
  },
  "review_status": "approved",
  "bookkeeping_status": "sent",
  "created_at": "2024-01-15T14:00:00+00:00",
  "updated_at": "2024-01-16T10:30:00+00:00"
}
```

## Usage Examples

### Create Receipt from Photo/Scan

```php
// Extract data from OCR or manual entry
$receiptData = [
    'title' => 'Taxi to Client Meeting',
    'receipt_date' => '2024-01-15',
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_inclusive' => ['amount' => 35.50]],
    'description' => 'Meeting with Acme Corp at their office'
];

$receipt = Teamleader::receipts()->create($receiptData);

// Attach scanned image (using Files resource)
Teamleader::files()->upload(
    'receipt-scan.jpg',
    'receipt',
    $receipt['data']['id']
);
```

### Process Employee Expense Receipts

```php
// Get pending receipts using Expenses
$pending = Teamleader::expenses()->list([
    'source_types' => ['receipt'],
    'review_statuses' => ['pending']
]);

foreach ($pending['data'] as $expense) {
    $receipt = Teamleader::receipts()->info($expense['source_id']);
    
    // Business rules for approval
    $approved = false;
    
    // Auto-approve small amounts
    if ($receipt['total']['tax_inclusive']['amount'] < 50) {
        $approved = true;
    }
    
    // Check if has proper documentation
    $files = Teamleader::files()->forSubject('receipt', $expense['source_id']);
    if (!empty($files['data'])) {
        $approved = true;
    }
    
    if ($approved) {
        Teamleader::receipts()->approve($expense['source_id']);
        Teamleader::receipts()->sendToBookkeeping($expense['source_id']);
        
        echo "Approved receipt: {$receipt['title']}\n";
    } else {
        echo "Requires manual review: {$receipt['title']}\n";
    }
}
```

### Batch Create Receipts

```php
$receipts = [
    ['title' => 'Parking', 'amount' => 15.00],
    ['title' => 'Office Supplies', 'amount' => 45.90],
    ['title' => 'Coffee Meeting', 'amount' => 12.50]
];

foreach ($receipts as $data) {
    try {
        $receipt = Teamleader::receipts()->create([
            'title' => $data['title'],
            'receipt_date' => date('Y-m-d'),
            'currency' => ['code' => 'EUR'],
            'total' => ['tax_inclusive' => ['amount' => $data['amount']]]
        ]);
        
        echo "Created receipt: {$data['title']}\n";
        
    } catch (Exception $e) {
        Log::error("Failed to create receipt: " . $e->getMessage());
    }
}
```

### Monthly Receipt Report

```php
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$receipts = Teamleader::expenses()->byDateRange($startOfMonth, $endOfMonth, [
    'source_types' => ['receipt']
]);

$totalAmount = 0;
$approvedAmount = 0;
$categoryTotals = [];

foreach ($receipts['data'] as $expense) {
    $amount = $expense['total']['tax_inclusive']['amount'];
    $totalAmount += $amount;
    
    if ($expense['review_status'] === 'approved') {
        $approvedAmount += $amount;
    }
    
    // Categorize by title keywords
    $title = strtolower($expense['title']);
    if (strpos($title, 'parking') !== false) {
        $categoryTotals['parking'] = ($categoryTotals['parking'] ?? 0) + $amount;
    } elseif (strpos($title, 'lunch') !== false || strpos($title, 'dinner') !== false) {
        $categoryTotals['meals'] = ($categoryTotals['meals'] ?? 0) + $amount;
    } else {
        $categoryTotals['other'] = ($categoryTotals['other'] ?? 0) + $amount;
    }
}

echo "Monthly Receipt Summary:\n";
echo "Total Receipts: " . count($receipts['data']) . "\n";
echo "Total Amount: €" . number_format($totalAmount, 2) . "\n";
echo "Approved Amount: €" . number_format($approvedAmount, 2) . "\n";
echo "\nBy Category:\n";
foreach ($categoryTotals as $category => $amount) {
    echo "  " . ucfirst($category) . ": €" . number_format($amount, 2) . "\n";
}
```

### Update Receipt with Additional Info

```php
$receiptId = 'receipt-uuid';

// Get current receipt
$receipt = Teamleader::receipts()->info($receiptId);

// Add more details
Teamleader::receipts()->update($receiptId, [
    'description' => $receipt['description'] . ' | Client: Acme Corp',
    'document_number' => 'EXP-2024-001'
]);
```

## Common Use Cases

### Employee Expense Reimbursement

```php
// Create receipt for employee expense
$receipt = Teamleader::receipts()->create([
    'title' => 'Travel Expenses - Conference',
    'receipt_date' => '2024-01-20',
    'description' => 'Train tickets and meals during Tech Conference 2024',
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_inclusive' => ['amount' => 245.00]]
]);

$receiptId = $receipt['data']['id'];

// Approve immediately for trusted employees
Teamleader::receipts()->approve($receiptId);
Teamleader::receipts()->sendToBookkeeping($receiptId);

// Notify employee
sendEmployeeNotification([
    'receipt_id' => $receiptId,
    'status' => 'approved',
    'amount' => 245.00
]);
```

### Receipt Validation Workflow

```php
function validateAndProcessReceipt($receiptId)
{
    $receipt = Teamleader::receipts()->info($receiptId);
    
    // Validation rules
    $errors = [];
    
    // Check if date is not in the future
    if (strtotime($receipt['receipt_date']) > time()) {
        $errors[] = 'Receipt date cannot be in the future';
    }
    
    // Check if amount is reasonable
    if ($receipt['total']['tax_inclusive']['amount'] > 1000) {
        $errors[] = 'Amount exceeds approval limit';
    }
    
    // Check if has supporting documentation
    $files = Teamleader::files()->forSubject('receipt', $receiptId);
    if (empty($files['data'])) {
        $errors[] = 'Missing receipt image/scan';
    }
    
    if (!empty($errors)) {
        // Refuse with reason
        Teamleader::receipts()->refuse($receiptId);
        return ['status' => 'refused', 'errors' => $errors];
    }
    
    // All checks passed
    Teamleader::receipts()->approve($receiptId);
    Teamleader::receipts()->sendToBookkeeping($receiptId);
    
    return ['status' => 'approved'];
}
```

### Expense Category Analysis

```php
$receipts = Teamleader::expenses()->list([
    'source_types' => ['receipt'],
    'review_statuses' => ['approved']
]);

$categories = [
    'meals' => ['lunch', 'dinner', 'breakfast', 'coffee', 'restaurant'],
    'transport' => ['taxi', 'uber', 'parking', 'train', 'bus'],
    'office' => ['supplies', 'stationery', 'equipment'],
    'other' => []
];

$totals = array_fill_keys(array_keys($categories), 0);

foreach ($receipts['data'] as $expense) {
    $title = strtolower($expense['title']);
    $amount = $expense['total']['tax_inclusive']['amount'];
    $categorized = false;
    
    foreach ($categories as $category => $keywords) {
        if ($category === 'other') continue;
        
        foreach ($keywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                $totals[$category] += $amount;
                $categorized = true;
                break 2;
            }
        }
    }
    
    if (!$categorized) {
        $totals['other'] += $amount;
    }
}

echo "Expense Analysis:\n";
foreach ($totals as $category => $total) {
    if ($total > 0) {
        echo ucfirst($category) . ": €" . number_format($total, 2) . "\n";
    }
}
```

### Automated Receipt Processing from Email

```php
// Process receipts sent via email
function processReceiptEmail($emailData)
{
    // Extract receipt information from email
    $receipt = Teamleader::receipts()->create([
        'title' => $emailData['subject'],
        'receipt_date' => date('Y-m-d'),
        'description' => $emailData['body'],
        'currency' => ['code' => 'EUR'],
        'total' => [
            'tax_inclusive' => [
                'amount' => extractAmountFromEmail($emailData['body'])
            ]
        ]
    ]);
    
    $receiptId = $receipt['data']['id'];
    
    // Attach email attachments as files
    if (!empty($emailData['attachments'])) {
        foreach ($emailData['attachments'] as $attachment) {
            Teamleader::files()->upload(
                $attachment['name'],
                'receipt',
                $receiptId
            );
        }
    }
    
    // Auto-approve small amounts
    if ($receipt['data']['total']['tax_inclusive']['amount'] < 100) {
        Teamleader::receipts()->approve($receiptId);
        Teamleader::receipts()->sendToBookkeeping($receiptId);
    }
    
    return $receiptId;
}
```

## Best Practices

1. **Use Expenses for Listing**: To list or search receipts, use the Expenses resource
```php
// Good - use Expenses for listing
$receipts = Teamleader::expenses()->list([
    'source_types' => ['receipt']
]);

// Then get details if needed
$details = Teamleader::receipts()->info($receiptId);
```

2. **Always Use Tax-Inclusive Amounts**: Receipts require `tax_inclusive` amounts
```php
// Correct
'total' => [
    'tax_inclusive' => [
        'amount' => 45.50
    ]
]

// Wrong - will fail
'total' => [
    'tax_exclusive' => [
        'amount' => 45.50
    ]
]
```

3. **Attach Supporting Documentation**: Always attach receipt images
```php
$receipt = Teamleader::receipts()->create($data);

// Upload receipt image
Teamleader::files()->upload(
    'receipt.jpg',
    'receipt',
    $receipt['data']['id']
);
```

4. **Use Descriptive Titles**: Make receipts easy to identify
```php
// Good
'title' => 'Client Lunch - Acme Corp - Restaurant XYZ'

// Avoid
'title' => 'Receipt'
```

5. **Validate Before Approving**: Implement validation rules
```php
$receipt = Teamleader::receipts()->info($receiptId);

// Check date, amount, documentation
if (isValid($receipt)) {
    Teamleader::receipts()->approve($receiptId);
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $receipt = Teamleader::receipts()->create([
        'title' => 'Office Lunch',
        'currency' => ['code' => 'EUR'],
        'total' => ['tax_inclusive' => ['amount' => 45.50]]
    ]);
    
    // Approve and send
    Teamleader::receipts()->approve($receipt['data']['id']);
    Teamleader::receipts()->sendToBookkeeping($receipt['data']['id']);
    
} catch (\InvalidArgumentException $e) {
    // Validation error (e.g., missing required field)
    Log::error('Invalid receipt data: ' . $e->getMessage());
    
} catch (\Exception $e) {
    // API error
    Log::error('Failed to create/process receipt: ' . $e->getMessage());
}

// Handle validation errors
try {
    // Attempt to create with tax_exclusive (wrong for receipts)
    $receipt = Teamleader::receipts()->create([
        'title' => 'Receipt',
        'currency' => ['code' => 'EUR'],
        'total' => ['tax_exclusive' => ['amount' => 45.50]]
    ]);
    
} catch (\InvalidArgumentException $e) {
    // Will fail - receipts require tax_inclusive
    echo "Error: " . $e->getMessage();
    
    // Correct approach
    $receipt = Teamleader::receipts()->create([
        'title' => 'Receipt',
        'currency' => ['code' => 'EUR'],
        'total' => ['tax_inclusive' => ['amount' => 45.50]]
    ]);
}

// Handle bookkeeping failures
try {
    Teamleader::receipts()->sendToBookkeeping($receiptId);
    
    sleep(2);
    
    $submissions = Teamleader::bookkeepingSubmissions()->forReceipt($receiptId);
    
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

- **[Expenses](expenses.md)** - List and search receipts
- **[Bookkeeping Submissions](bookkeeping-submissions.md)** - Track submission status
- **[Companies](../crm/companies.md)** - Manage suppliers
- **[Files](../files/files.md)** - Attach receipt images
- **[Incoming Invoices](incoming-invoices.md)** - For larger supplier invoices
- **[Incoming Credit Notes](incoming-creditnotes.md)** - For supplier credits

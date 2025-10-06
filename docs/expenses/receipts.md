# Receipts

Manage expense receipts in Teamleader Focus. This resource provides operations for creating, updating, and managing expense receipts.

## Endpoint

`receipts`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `add()`

Create a new expense receipt.

**Parameters:**
- `data` (array): Receipt data including title, supplier, amounts, and other details

**Required Fields:**
- `title`: Receipt title
- `currency.code`: Currency code (e.g., "EUR")
- `total.tax_inclusive`: Total amount including tax

**Optional Fields:**
- `supplier_id`: Supplier UUID
- `document_number`: Receipt document number
- `receipt_date`: Receipt date
- `company_entity_id`: Company entity UUID
- `file_id`: Attached file UUID

**Example:**
```php
$receipt = $teamleader->receipts()->add([
    'title' => 'Office Lunch',
    'supplier_id' => 'supplier-uuid-here',
    'document_number' => 'REC-2024-001',
    'receipt_date' => '2024-01-15',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => 45.50
        ]
    ],
    'file_id' => 'file-uuid-here'
]);
```

### `update()`

Update an existing receipt.

**Parameters:**
- `id` (string): Receipt UUID
- `data` (array): Receipt data to update

**Example:**
```php
$teamleader->receipts()->update('receipt-uuid-here', [
    'title' => 'Updated Receipt Title',
    'receipt_date' => '2024-01-16',
    'total' => [
        'tax_inclusive' => [
            'amount' => 52.00
        ]
    ]
]);
```

### `info()`

Get detailed information about a specific receipt.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
$receipt = $teamleader->receipts()->info('receipt-uuid-here');
```

### `delete()`

Delete a receipt.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
$teamleader->receipts()->delete('receipt-uuid-here');
```

### `approve()`

Approve a receipt.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
$teamleader->receipts()->approve('receipt-uuid-here');
```

### `refuse()`

Refuse a receipt.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
$teamleader->receipts()->refuse('receipt-uuid-here');
```

### `markAsPendingReview()`

Mark a receipt as pending review.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
$teamleader->receipts()->markAsPendingReview('receipt-uuid-here');
```

### `sendToBookkeeping()`

Send a receipt to bookkeeping for processing.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
$teamleader->receipts()->sendToBookkeeping('receipt-uuid-here');
```

## Currency Codes

Supported currency codes for the `currency.code` field:

- BAM (Bosnia and Herzegovina Convertible Mark)
- CAD (Canadian Dollar)
- CHF (Swiss Franc)
- CLP (Chilean Peso)
- CNY (Chinese Yuan)
- COP (Colombian Peso)
- CZK (Czech Koruna)
- DKK (Danish Krone)
- EUR (Euro)
- GBP (British Pound)
- INR (Indian Rupee)
- ISK (Icelandic Krona)
- JPY (Japanese Yen)
- MAD (Moroccan Dirham)
- MXN (Mexican Peso)
- NOK (Norwegian Krone)
- PEN (Peruvian Sol)
- PLN (Polish Zloty)
- RON (Romanian Leu)
- SEK (Swedish Krona)
- TRY (Turkish Lira)
- USD (US Dollar)
- ZAR (South African Rand)

## Review Status Values

Receipts can have the following review statuses:

- `pending`: Awaiting review
- `approved`: Approved for processing
- `refused`: Refused/rejected

## Usage Examples

### Create a Complete Receipt

Create a new expense receipt with all details:

```php
$receipt = $teamleader->receipts()->add([
    'title' => 'Business Lunch with Client',
    'supplier_id' => '9d4fde95-9b6b-4c41-ae71-6c5f70bc2fc7',
    'document_number' => 'REST-INV-2024-123',
    'receipt_date' => '2024-01-15',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => 78.50
        ]
    ],
    'company_entity_id' => 'entity-uuid-here',
    'file_id' => 'file-uuid-here'
]);

$receiptId = $receipt['data']['id'];
```

### Update Receipt Details

Update specific fields of an existing receipt:

```php
$teamleader->receipts()->update('receipt-uuid-here', [
    'title' => 'Team Lunch Meeting',
    'receipt_date' => '2024-01-16',
    'total' => [
        'tax_inclusive' => [
            'amount' => 85.00
        ]
    ]
]);
```

### Receipt Approval Workflow

Complete workflow for reviewing and approving a receipt:

```php
$receiptId = 'receipt-uuid-here';

// Get receipt details
$receipt = $teamleader->receipts()->info($receiptId);

// Mark as pending review
$teamleader->receipts()->markAsPendingReview($receiptId);

// After review, approve or refuse
if ($receiptIsValid) {
    $teamleader->receipts()->approve($receiptId);
    
    // Send to bookkeeping
    $teamleader->receipts()->sendToBookkeeping($receiptId);
} else {
    $teamleader->receipts()->refuse($receiptId);
}
```

### Create Simple Receipt

Create a basic receipt with minimal information:

```php
$receipt = $teamleader->receipts()->add([
    'title' => 'Coffee & Supplies',
    'receipt_date' => '2024-01-20',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => 15.75
        ]
    ]
]);
```

### Retrieve and Display Receipt Information

Get detailed information about a receipt:

```php
$receipt = $teamleader->receipts()->info('receipt-uuid-here');

// Access receipt data
$title = $receipt['data']['title'];
$documentNumber = $receipt['data']['document_number'];
$totalAmount = $receipt['data']['total']['tax_inclusive']['amount'];
$reviewStatus = $receipt['data']['review_status'];
$currencyCode = $receipt['data']['currency']['code'];

// Check supplier information
if (isset($receipt['data']['supplier'])) {
    $supplierType = $receipt['data']['supplier']['type'];
    $supplierId = $receipt['data']['supplier']['id'];
}
```

## Data Fields

### Response Data (from info method)

- **`id`**: Receipt UUID
- **`title`**: Receipt title
- **`origin`**: Origin information (type, id)
- **`supplier`**: Supplier information (type: company or contact, id) - nullable
- **`document_number`**: Receipt document number - nullable
- **`receipt_date`**: Receipt date - nullable
- **`currency`**: Currency object with code
- **`total`**: Total amounts object
    - **`tax_inclusive`**: Amount including tax - nullable
        - **`amount`**: Tax-inclusive amount (number)
- **`company_entity`**: Company entity information (type, id)
- **`file`**: Attached file information (type, id) - nullable
- **`review_status`**: Review status (pending, approved, refused)

## Error Handling

The receipts resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->receipts()->add([
    'title' => 'New Receipt',
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_inclusive' => ['amount' => 50.00]]
]);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Receipts API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Receipts API calls count towards your overall Teamleader API rate limit:

- **List operations**: Not supported
- **Info operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Status change operations** (approve, refuse, markAsPendingReview, sendToBookkeeping): 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- The `add()` method returns the created receipt data including the new UUID
- Receipts only support **tax-inclusive** amounts (no tax-exclusive option)
- Receipts do not have due dates, payment references, or IBAN numbers (unlike invoices)
- If `company_entity_id` is not provided, the default company entity will be used
- The `supplier` field in responses can be either a company or contact type
- Review status workflow: pending → approved/refused
- Approved receipts can be sent to bookkeeping
- Some operations (approve, refuse, markAsPendingReview, sendToBookkeeping) return no content (204 response)
- Receipts are typically used for smaller expense items and purchases without formal invoices
- Always attach a file (scanned receipt/photo) using the `file_id` field for proper documentation

## Differences from Invoices

Receipts are simpler than invoices with some key differences:

- **No tax-exclusive amounts**: Only `tax_inclusive` is supported
- **No payment tracking**: No `due_date`, `payment_reference`, or `iban_number` fields
- **Simpler workflow**: Designed for quick expense tracking
- **Use case**: Small purchases, petty cash, expense reports

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ReceiptController extends Controller
{
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'supplier_id' => 'nullable|string',
            'amount' => 'required|numeric',
            'receipt_date' => 'required|date',
            'file_id' => 'nullable|string'
        ]);
        
        $receipt = $teamleader->receipts()->add([
            'title' => $validated['title'],
            'supplier_id' => $validated['supplier_id'],
            'receipt_date' => $validated['receipt_date'],
            'currency' => ['code' => 'EUR'],
            'total' => [
                'tax_inclusive' => [
                    'amount' => $validated['amount']
                ]
            ],
            'file_id' => $validated['file_id']
        ]);
        
        return redirect()
            ->route('receipts.show', $receipt['data']['id'])
            ->with('success', 'Receipt created successfully');
    }
    
    public function approve(TeamleaderSDK $teamleader, string $id)
    {
        $teamleader->receipts()->approve($id);
        
        return back()->with('success', 'Receipt approved successfully');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

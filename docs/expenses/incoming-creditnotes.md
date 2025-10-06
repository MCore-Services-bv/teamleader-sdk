# Incoming Credit Notes

Manage incoming credit notes in Teamleader Focus. This resource provides operations for creating, updating, and managing incoming credit notes from suppliers.

## Endpoint

`incomingCreditNotes`

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

Create a new incoming credit note.

**Parameters:**
- `data` (array): Credit note data including title, supplier, amounts, and other details

**Required Fields:**
- `title`: Credit note title
- `currency.code`: Currency code (e.g., "EUR")
- `total`: Total amounts (either tax_exclusive or tax_inclusive)

**Optional Fields:**
- `supplier_id`: Supplier UUID
- `document_number`: Credit note document number
- `invoice_date`: Credit note date
- `due_date`: Due date
- `company_entity_id`: Company entity UUID
- `file_id`: Attached file UUID
- `payment_reference`: Payment reference
- `iban_number`: IBAN number

**Example:**
```php
$creditNote = $teamleader->incomingCreditNotes()->add([
    'title' => 'Supplier Credit Note',
    'supplier_id' => 'supplier-uuid-here',
    'document_number' => 'CN-2024-001',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 500.00
        ]
    ],
    'payment_reference' => 'REF123456',
    'iban_number' => 'BE68539007547034'
]);
```

### `update()`

Update an existing incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID
- `data` (array): Credit note data to update

**Example:**
```php
$teamleader->incomingCreditNotes()->update('creditnote-uuid-here', [
    'title' => 'Updated Credit Note Title',
    'due_date' => '2024-03-15',
    'total' => [
        'tax_exclusive' => [
            'amount' => 600.00
        ]
    ]
]);
```

### `info()`

Get detailed information about a specific incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$creditNote = $teamleader->incomingCreditNotes()->info('creditnote-uuid-here');
```

### `delete()`

Delete an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$teamleader->incomingCreditNotes()->delete('creditnote-uuid-here');
```

### `approve()`

Approve an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$teamleader->incomingCreditNotes()->approve('creditnote-uuid-here');
```

### `refuse()`

Refuse an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$teamleader->incomingCreditNotes()->refuse('creditnote-uuid-here');
```

### `markAsPendingReview()`

Mark an incoming credit note as pending review.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$teamleader->incomingCreditNotes()->markAsPendingReview('creditnote-uuid-here');
```

### `sendToBookkeeping()`

Send an incoming credit note to bookkeeping.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$teamleader->incomingCreditNotes()->sendToBookkeeping('creditnote-uuid-here');
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

Incoming credit notes can have the following review statuses:

- `pending`: Awaiting review
- `approved`: Approved for processing
- `refused`: Refused/rejected

## Usage Examples

### Create a Complete Credit Note

Create a new incoming credit note with all details:

```php
$creditNote = $teamleader->incomingCreditNotes()->add([
    'title' => 'Return Credit Note',
    'supplier_id' => '9d4fde95-9b6b-4c41-ae71-6c5f70bc2fc7',
    'document_number' => 'SUP-CN-2024-123',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 750.00
        ],
        'tax_inclusive' => [
            'amount' => 907.50
        ]
    ],
    'company_entity_id' => 'entity-uuid-here',
    'file_id' => 'file-uuid-here',
    'payment_reference' => '+++123/4567/89012+++',
    'iban_number' => 'BE68539007547034'
]);

$creditNoteId = $creditNote['data']['id'];
```

### Update Credit Note Details

Update specific fields of an existing credit note:

```php
$teamleader->incomingCreditNotes()->update('creditnote-uuid-here', [
    'title' => 'Updated Credit Note Title',
    'due_date' => '2024-03-01',
    'payment_reference' => 'NEW-REF-123'
]);
```

### Credit Note Approval Workflow

Complete workflow for reviewing and approving a credit note:

```php
$creditNoteId = 'creditnote-uuid-here';

// Get credit note details
$creditNote = $teamleader->incomingCreditNotes()->info($creditNoteId);

// Mark as pending review
$teamleader->incomingCreditNotes()->markAsPendingReview($creditNoteId);

// After review, approve or refuse
if ($creditNoteIsValid) {
    $teamleader->incomingCreditNotes()->approve($creditNoteId);
    
    // Send to bookkeeping
    $teamleader->incomingCreditNotes()->sendToBookkeeping($creditNoteId);
} else {
    $teamleader->incomingCreditNotes()->refuse($creditNoteId);
}
```

### Create Credit Note with Tax-Inclusive Amount

When you have the total including tax:

```php
$creditNote = $teamleader->incomingCreditNotes()->add([
    'title' => 'Product Return Credit',
    'supplier_id' => 'supplier-uuid-here',
    'invoice_date' => '2024-01-20',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => 363.00
        ]
    ]
]);
```

### Retrieve and Display Credit Note Information

Get detailed information about a credit note:

```php
$creditNote = $teamleader->incomingCreditNotes()->info('creditnote-uuid-here');

// Access credit note data
$title = $creditNote['data']['title'];
$documentNumber = $creditNote['data']['document_number'];
$totalAmount = $creditNote['data']['total']['tax_exclusive']['amount'];
$reviewStatus = $creditNote['data']['review_status'];
$currencyCode = $creditNote['data']['currency']['code'];

// Check supplier information
if (isset($creditNote['data']['supplier'])) {
    $supplierType = $creditNote['data']['supplier']['type'];
    $supplierId = $creditNote['data']['supplier']['id'];
}
```

## Data Fields

### Response Data (from info method)

- **`id`**: Credit note UUID
- **`title`**: Credit note title
- **`origin`**: Origin information (type: user or peppolIncomingDocument, id)
- **`supplier`**: Supplier information (type: company or contact, id) - nullable
- **`document_number`**: Credit note document number - nullable
- **`invoice_date`**: Credit note date - nullable
- **`due_date`**: Due date - nullable
- **`currency`**: Currency object with code
- **`total`**: Total amounts object
    - **`tax_exclusive`**: Amount excluding tax - nullable
    - **`tax_inclusive`**: Amount including tax - nullable
- **`company_entity`**: Company entity information (type, id)
- **`file`**: Attached file information (type, id) - nullable
- **`payment_reference`**: Payment reference - nullable
- **`review_status`**: Review status (pending, approved, refused)
- **`iban_number`**: IBAN number - nullable

## Error Handling

The incoming credit notes resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->incomingCreditNotes()->add([
    'title' => 'New Credit Note',
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_exclusive' => ['amount' => 500]]
]);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Incoming Credit Notes API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Incoming Credit Notes API calls count towards your overall Teamleader API rate limit:

- **List operations**: Not supported
- **Info operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Status change operations** (approve, refuse, markAsPendingReview, sendToBookkeeping): 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- The `add()` method returns the created credit note data including the new UUID
- Either `tax_exclusive` or `tax_inclusive` amount is required in the `total` object
- If `company_entity_id` is not provided, the default company entity will be used
- The `supplier` field in responses can be either a company or contact type
- The `origin` field indicates how the credit note was created (user or via PEPPOL)
- Review status workflow: pending → approved/refused
- Approved credit notes can be sent to bookkeeping
- Some operations (approve, refuse, markAsPendingReview, sendToBookkeeping) return no content (204 response)
- Credit notes represent amounts to be credited back, typically for returns or corrections

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class IncomingCreditNoteController extends Controller
{
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'supplier_id' => 'nullable|string',
            'amount' => 'required|numeric',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date'
        ]);
        
        $creditNote = $teamleader->incomingCreditNotes()->add([
            'title' => $validated['title'],
            'supplier_id' => $validated['supplier_id'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'currency' => ['code' => 'EUR'],
            'total' => [
                'tax_exclusive' => [
                    'amount' => $validated['amount']
                ]
            ]
        ]);
        
        return redirect()
            ->route('creditnotes.show', $creditNote['data']['id'])
            ->with('success', 'Credit note created successfully');
    }
    
    public function approve(TeamleaderSDK $teamleader, string $id)
    {
        $teamleader->incomingCreditNotes()->approve($id);
        
        return back()->with('success', 'Credit note approved successfully');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

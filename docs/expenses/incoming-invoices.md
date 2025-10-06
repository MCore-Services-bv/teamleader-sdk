# Incoming Invoices

Manage incoming invoices in Teamleader Focus. This resource provides operations for creating, updating, and managing incoming invoices from suppliers.

## Endpoint

`incomingInvoices`

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

Create a new incoming invoice.

**Parameters:**
- `data` (array): Invoice data including title, supplier, amounts, and other details

**Required Fields:**
- `title`: Invoice title
- `currency.code`: Currency code (e.g., "EUR")
- `total`: Total amounts (either tax_exclusive or tax_inclusive)

**Optional Fields:**
- `supplier_id`: Supplier UUID
- `document_number`: Invoice document number
- `invoice_date`: Invoice date
- `due_date`: Due date
- `company_entity_id`: Company entity UUID
- `file_id`: Attached file UUID
- `payment_reference`: Payment reference
- `iban_number`: IBAN number

**Example:**
```php
$invoice = $teamleader->incomingInvoices()->add([
    'title' => 'Office Supplies Invoice',
    'supplier_id' => 'supplier-uuid-here',
    'document_number' => 'INV-2024-001',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 1000.00
        ]
    ],
    'payment_reference' => 'REF123456',
    'iban_number' => 'BE68539007547034'
]);
```

### `update()`

Update an existing incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `data` (array): Invoice data to update

**Example:**
```php
$teamleader->incomingInvoices()->update('invoice-uuid-here', [
    'title' => 'Updated Invoice Title',
    'due_date' => '2024-03-15',
    'total' => [
        'tax_exclusive' => [
            'amount' => 1200.00
        ]
    ]
]);
```

### `info()`

Get detailed information about a specific incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$invoice = $teamleader->incomingInvoices()->info('invoice-uuid-here');
```

### `delete()`

Delete an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$teamleader->incomingInvoices()->delete('invoice-uuid-here');
```

### `approve()`

Approve an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$teamleader->incomingInvoices()->approve('invoice-uuid-here');
```

### `refuse()`

Refuse an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$teamleader->incomingInvoices()->refuse('invoice-uuid-here');
```

### `markAsPendingReview()`

Mark an incoming invoice as pending review.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$teamleader->incomingInvoices()->markAsPendingReview('invoice-uuid-here');
```

### `sendToBookkeeping()`

Send an incoming invoice to bookkeeping.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$teamleader->incomingInvoices()->sendToBookkeeping('invoice-uuid-here');
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

Incoming invoices can have the following review statuses:

- `pending`: Awaiting review
- `approved`: Approved for processing
- `refused`: Refused/rejected

## Usage Examples

### Create a Complete Invoice

Create a new incoming invoice with all details:

```php
$invoice = $teamleader->incomingInvoices()->add([
    'title' => 'Monthly Service Invoice',
    'supplier_id' => '9d4fde95-9b6b-4c41-ae71-6c5f70bc2fc7',
    'document_number' => 'SUP-INV-2024-123',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 2500.00
        ],
        'tax_inclusive' => [
            'amount' => 3025.00
        ]
    ],
    'company_entity_id' => 'entity-uuid-here',
    'file_id' => 'file-uuid-here',
    'payment_reference' => '+++123/4567/89012+++',
    'iban_number' => 'BE68539007547034'
]);

$invoiceId = $invoice['data']['id'];
```

### Update Invoice Details

Update specific fields of an existing invoice:

```php
$teamleader->incomingInvoices()->update('invoice-uuid-here', [
    'title' => 'Updated Invoice Title',
    'due_date' => '2024-03-01',
    'payment_reference' => 'NEW-REF-123'
]);
```

### Invoice Approval Workflow

Complete workflow for reviewing and approving an invoice:

```php
$invoiceId = 'invoice-uuid-here';

// Get invoice details
$invoice = $teamleader->incomingInvoices()->info($invoiceId);

// Mark as pending review
$teamleader->incomingInvoices()->markAsPendingReview($invoiceId);

// After review, approve or refuse
if ($invoiceIsValid) {
    $teamleader->incomingInvoices()->approve($invoiceId);
    
    // Send to bookkeeping
    $teamleader->incomingInvoices()->sendToBookkeeping($invoiceId);
} else {
    $teamleader->incomingInvoices()->refuse($invoiceId);
}
```

### Create Invoice with Tax-Inclusive Amount

When you have the total including tax:

```php
$invoice = $teamleader->incomingInvoices()->add([
    'title' => 'Equipment Purchase',
    'supplier_id' => 'supplier-uuid-here',
    'invoice_date' => '2024-01-20',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_inclusive' => [
            'amount' => 1210.00
        ]
    ]
]);
```

### Retrieve and Display Invoice Information

Get detailed information about an invoice:

```php
$invoice = $teamleader->incomingInvoices()->info('invoice-uuid-here');

// Access invoice data
$title = $invoice['data']['title'];
$documentNumber = $invoice['data']['document_number'];
$totalAmount = $invoice['data']['total']['tax_exclusive']['amount'];
$reviewStatus = $invoice['data']['review_status'];
$currencyCode = $invoice['data']['currency']['code'];

// Check supplier information
if (isset($invoice['data']['supplier'])) {
    $supplierType = $invoice['data']['supplier']['type'];
    $supplierId = $invoice['data']['supplier']['id'];
}
```

## Data Fields

### Response Data (from info method)

- **`id`**: Invoice UUID
- **`title`**: Invoice title
- **`origin`**: Origin information (type: user or peppolIncomingDocument, id)
- **`supplier`**: Supplier information (type: company or contact, id) - nullable
- **`document_number`**: Invoice document number - nullable
- **`invoice_date`**: Invoice date - nullable
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

The incoming invoices resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->incomingInvoices()->add([
    'title' => 'New Invoice',
    'currency' => ['code' => 'EUR'],
    'total' => ['tax_exclusive' => ['amount' => 1000]]
]);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Incoming Invoices API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Incoming Invoices API calls count towards your overall Teamleader API rate limit:

- **List operations**: Not supported
- **Info operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Status change operations** (approve, refuse, markAsPendingReview, sendToBookkeeping): 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- The `add()` method returns the created invoice data including the new UUID
- Either `tax_exclusive` or `tax_inclusive` amount is required in the `total` object
- If `company_entity_id` is not provided, the default company entity will be used
- The `supplier` field in responses can be either a company or contact type
- The `origin` field indicates how the invoice was created (user or via PEPPOL)
- Review status workflow: pending → approved/refused
- Approved invoices can be sent to bookkeeping
- Some operations (approve, refuse, markAsPendingReview, sendToBookkeeping) return no content (204 response)

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class IncomingInvoiceController extends Controller
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
        
        $invoice = $teamleader->incomingInvoices()->add([
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
            ->route('invoices.show', $invoice['data']['id'])
            ->with('success', 'Invoice created successfully');
    }
    
    public function approve(TeamleaderSDK $teamleader, string $id)
    {
        $teamleader->incomingInvoices()->approve($id);
        
        return back()->with('success', 'Invoice approved successfully');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

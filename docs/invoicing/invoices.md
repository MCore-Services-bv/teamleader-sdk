# Invoices

Manage invoices in Teamleader Focus.

## Overview

The Invoices resource provides comprehensive management of invoices in your Teamleader account. You can create draft invoices, book them, update both draft and booked invoices, credit them (fully or partially), register payments, and download invoices in various formats.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create() / draft()](#create--draft)
    - [update()](#update)
    - [updateBooked()](#updatebooked)
    - [book()](#book)
    - [copy()](#copy)
    - [credit()](#credit)
    - [creditPartially()](#creditpartially)
    - [registerPayment()](#registerpayment)
    - [download()](#download)
    - [sendViaPeppol()](#sendviapeppol)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`invoices`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ✅ Supported
- **Creation**: ✅ Supported (draft)
- **Update**: ✅ Supported (draft and booked)
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of invoices with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for sorting and pagination

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all invoices
$invoices = Teamleader::invoices()->list();

// Get invoices with filters
$invoices = Teamleader::invoices()->list([
    'invoice_date_after' => '2024-01-01',
    'invoice_date_before' => '2024-12-31'
]);

// With sorting and pagination
$invoices = Teamleader::invoices()->list([], [
    'sort' => 'invoice_date',
    'sort_order' => 'desc',
    'page_size' => 50,
    'page_number' => 2
]);
```

### `info()`

Get detailed information about a specific invoice.

**Parameters:**
- `id` (string): The invoice UUID
- `includes` (string|array, optional): Related resources to include

**Available includes:**
- `late_fees` - Include late fee calculations

**Example:**
```php
// Basic invoice information
$invoice = Teamleader::invoices()->info('invoice-uuid');

// With late fees
$invoice = Teamleader::invoices()->info('invoice-uuid', 'late_fees');
```

### `create()` / `draft()`

Create a new draft invoice. The `draft()` method is an alias for `create()`.

**Required fields:**
- `invoicee` (object): Invoice recipient information
- `grouped_lines` (array): Invoice line items
- `invoice_date` (string): Invoice date (YYYY-MM-DD)

**Optional fields:**
- `department_id` (string): Department UUID
- `payment_term` (object): Payment terms
- `discounts` (array): Discounts to apply
- `note` (string): Internal note
- `purchase_order_number` (string): PO number
- Additional invoice configuration fields

**Example:**
```php
$invoice = Teamleader::invoices()->create([
    'department_id' => 'dept-uuid',
    'invoice_date' => '2024-02-01',
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'for_attention_of' => [
            'name' => 'John Doe',
            'contact_id' => 'contact-uuid'
        ]
    ],
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Products'
            ],
            'line_items' => [
                [
                    'quantity' => 2,
                    'description' => 'Product A',
                    'unit_price' => [
                        'amount' => 50.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ],
    'payment_term' => [
        'type' => 'after_invoice_date',
        'days' => 30
    ]
]);
```

### `update()`

Update a draft invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `data` (array): Fields to update

**Example:**
```php
$result = Teamleader::invoices()->update('invoice-uuid', [
    'note' => 'Updated internal note',
    'purchase_order_number' => 'PO-12345'
]);
```

### `updateBooked()`

Update a booked invoice (if allowed in your Teamleader settings).

**Parameters:**
- `id` (string): Invoice UUID
- `data` (array): Fields to update (limited to specific fields)

**Example:**
```php
$result = Teamleader::invoices()->updateBooked('invoice-uuid', [
    'note' => 'Updated note for booked invoice'
]);
```

### `book()`

Book a draft invoice, making it official and immutable.

**Parameters:**
- `id` (string): Invoice UUID
- `on` (string): Booking date (YYYY-MM-DD format)

**Example:**
```php
$result = Teamleader::invoices()->book('invoice-uuid', '2024-02-01');
```

### `copy()`

Create a new draft invoice based on an existing invoice.

**Parameters:**
- `id` (string): Invoice UUID to copy

**Example:**
```php
$newInvoice = Teamleader::invoices()->copy('invoice-uuid');
```

### `credit()`

Credit an invoice completely, creating a credit note.

**Parameters:**
- `id` (string): Invoice UUID
- `creditNoteDate` (string): Credit note date (YYYY-MM-DD format)

**Example:**
```php
$creditNote = Teamleader::invoices()->credit('invoice-uuid', '2024-02-15');
```

### `creditPartially()`

Credit an invoice partially, creating a credit note for specific lines.

**Parameters:**
- `id` (string): Invoice UUID
- `creditNoteDate` (string): Credit note date (YYYY-MM-DD format)
- `groupedLines` (array): Lines to credit
- `discounts` (array, optional): Discounts to apply to credit note

**Example:**
```php
$creditNote = Teamleader::invoices()->creditPartially(
    'invoice-uuid',
    '2024-02-15',
    [
        [
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Product A - Return',
                    'unit_price' => [
                        'amount' => 50.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ]
);
```

### `registerPayment()`

Register a payment for an invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `amount` (float): Payment amount
- `paidAt` (string): Payment date (YYYY-MM-DD format)
- `paymentMethodId` (string, optional): Payment method UUID

**Example:**
```php
$result = Teamleader::invoices()->registerPayment(
    'invoice-uuid',
    250.00,
    '2024-02-10',
    'payment-method-uuid'
);
```

### `download()`

Download an invoice in a specific format.

**Parameters:**
- `id` (string): Invoice UUID
- `format` (string, optional): Format type (default: 'pdf')
    - `pdf` - PDF document
    - `ubl/e-fff` - UBL E-FFF format
    - `ubl/peppol_bis_3` - UBL Peppol BIS 3.0 format

**Returns:** Array with `location` (download URL) and `expires` (expiration timestamp)

**Example:**
```php
// Download as PDF
$download = Teamleader::invoices()->download('invoice-uuid');
$pdfUrl = $download['location'];

// Download as UBL
$download = Teamleader::invoices()->download('invoice-uuid', 'ubl/e-fff');
```

### `sendViaPeppol()`

Send an invoice via the Peppol network.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$result = Teamleader::invoices()->sendViaPeppol('invoice-uuid');
```

## Helper Methods

The Invoices resource provides convenient helper methods:

### Status-based Methods

```php
// Get draft invoices
$drafts = Teamleader::invoices()->drafts();

// Get outstanding (unpaid) invoices
$outstanding = Teamleader::invoices()->outstanding();

// Get matched (paid) invoices
$matched = Teamleader::invoices()->matched();

// Get overdue invoices
$overdue = Teamleader::invoices()->overdue();
```

### Customer-based Methods

```php
// Get invoices for a specific company
$invoices = Teamleader::invoices()->forCompany('company-uuid');

// Get invoices for a specific contact
$invoices = Teamleader::invoices()->forContact('contact-uuid');

// Get invoices for any customer type
$invoices = Teamleader::invoices()->forCustomer('company', 'company-uuid');
```

### Department and Project Methods

```php
// Get invoices for a department
$invoices = Teamleader::invoices()->forDepartment('dept-uuid');

// Get invoices for a project
$invoices = Teamleader::invoices()->forProject('project-uuid');
```

### Date Range Methods

```php
// Get invoices within a date range
$invoices = Teamleader::invoices()->forDateRange('2024-01-01', '2024-12-31');

// Get invoices for a specific month
$invoices = Teamleader::invoices()->forMonth('2024-02');

// Get invoices for a specific year
$invoices = Teamleader::invoices()->forYear(2024);
```

## Filtering

Available filters for invoices:

- `ids` - Array of invoice UUIDs
- `department_id` - Filter by department UUID
- `purchase_order_number` - Filter by PO number
- `invoice_number` - Filter by invoice number
- `status` - Filter by status (draft, outstanding, matched)
- `customer` - Filter by customer (object with type and id)
- `invoice_date_after` - Date (inclusive, YYYY-MM-DD)
- `invoice_date_before` - Date (exclusive, YYYY-MM-DD)
- `project_id` - Filter by project UUID
- `updated_since` - ISO 8601 datetime

**Example:**
```php
$invoices = Teamleader::invoices()->list([
    'status' => ['outstanding'],
    'invoice_date_after' => '2024-01-01',
    'department_id' => 'dept-uuid'
]);
```

## Sorting

Available sort fields:

- `invoice_number` - Sort by invoice number
- `invoice_date` - Sort by invoice date

**Example:**
```php
$invoices = Teamleader::invoices()->list([], [
    'sort' => 'invoice_date',
    'sort_order' => 'desc'
]);
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "uuid",
      "department": {
        "type": "department",
        "id": "uuid"
      },
      "invoice_number": "2024/001",
      "invoice_date": "2024-02-01",
      "status": "outstanding",
      "paid": false,
      "paid_at": null,
      "invoicee": {
        "name": "Company Name",
        "vat_number": "BE0123456789",
        "customer": {
          "type": "company",
          "id": "uuid"
        }
      },
      "total": {
        "tax_exclusive": {
          "amount": 100.00,
          "currency": "EUR"
        },
        "tax_inclusive": {
          "amount": 121.00,
          "currency": "EUR"
        },
        "payable": {
          "amount": 121.00,
          "currency": "EUR"
        }
      },
      "created_at": "2024-02-01T10:00:00+00:00",
      "updated_at": "2024-02-01T10:00:00+00:00"
    }
  ]
}
```

### Info Response

```json
{
  "data": {
    "id": "uuid",
    "department": {...},
    "invoice_number": "2024/001",
    "invoice_date": "2024-02-01",
    "status": "outstanding",
    "paid": false,
    "invoicee": {...},
    "payment_term": {
      "type": "after_invoice_date",
      "days": 30
    },
    "grouped_lines": [
      {
        "section": {
          "title": "Products"
        },
        "line_items": [
          {
            "product": null,
            "quantity": 2,
            "description": "Product A",
            "extended_description": null,
            "unit_price": {
              "amount": 50.00,
              "tax": "excluding"
            },
            "tax": {
              "type": "tax_rate",
              "id": "uuid"
            },
            "total": {
              "amount": 100.00,
              "currency": "EUR"
            }
          }
        ]
      }
    ],
    "total": {...},
    "taxes": [...],
    "created_at": "2024-02-01T10:00:00+00:00",
    "updated_at": "2024-02-01T10:00:00+00:00"
  }
}
```

## Usage Examples

### Create and Book an Invoice

```php
// Step 1: Create draft
$invoice = Teamleader::invoices()->draft([
    'department_id' => 'dept-uuid',
    'invoice_date' => '2024-02-01',
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'grouped_lines' => [
        [
            'line_items' => [
                [
                    'quantity' => 5,
                    'description' => 'Consulting Services',
                    'unit_price' => [
                        'amount' => 100.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ]
]);

// Step 2: Book the invoice
$result = Teamleader::invoices()->book($invoice['data']['id'], '2024-02-01');
```

### Handle Partial Payment

```php
$invoiceId = 'invoice-uuid';
$totalAmount = 1000.00;

// Register first payment
Teamleader::invoices()->registerPayment($invoiceId, 500.00, '2024-02-10');

// Register second payment
Teamleader::invoices()->registerPayment($invoiceId, 500.00, '2024-02-20');
```

### Create Credit Note for Returns

```php
// Full credit
$creditNote = Teamleader::invoices()->credit('invoice-uuid', '2024-02-15');

// Partial credit for specific items
$creditNote = Teamleader::invoices()->creditPartially(
    'invoice-uuid',
    '2024-02-15',
    [
        [
            'line_items' => [
                [
                    'quantity' => 2,
                    'description' => 'Product A - Returned',
                    'unit_price' => [
                        'amount' => 50.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ]
);
```

### Generate Monthly Invoice Report

```php
$monthlyInvoices = Teamleader::invoices()->forMonth('2024-02');

$totalRevenue = 0;
foreach ($monthlyInvoices['data'] as $invoice) {
    if ($invoice['status'] !== 'draft') {
        $totalRevenue += $invoice['total']['tax_exclusive']['amount'];
    }
}

echo "Total Revenue for February 2024: €" . number_format($totalRevenue, 2);
```

## Common Use Cases

### 1. Invoice Creation Workflow

```php
// Create draft
$draft = Teamleader::invoices()->draft($invoiceData);

// Review and update if needed
Teamleader::invoices()->update($draft['data']['id'], [
    'note' => 'Additional internal note'
]);

// Book when ready
Teamleader::invoices()->book($draft['data']['id'], date('Y-m-d'));

// Download for sending
$download = Teamleader::invoices()->download($draft['data']['id']);
```

### 2. Payment Tracking

```php
// Get all outstanding invoices
$outstanding = Teamleader::invoices()->outstanding();

foreach ($outstanding['data'] as $invoice) {
    $dueDate = date('Y-m-d', strtotime($invoice['invoice_date'] . ' +30 days'));
    
    if ($dueDate < date('Y-m-d')) {
        echo "Overdue: " . $invoice['invoice_number'] . "\n";
    }
}
```

### 3. Customer Invoice History

```php
$customerInvoices = Teamleader::invoices()
    ->forCompany('company-uuid')
    ->list([], [
        'sort' => 'invoice_date',
        'sort_order' => 'desc'
    ]);

$totalBilled = 0;
$totalPaid = 0;

foreach ($customerInvoices['data'] as $invoice) {
    if ($invoice['status'] !== 'draft') {
        $totalBilled += $invoice['total']['tax_inclusive']['amount'];
        
        if ($invoice['paid']) {
            $totalPaid += $invoice['total']['tax_inclusive']['amount'];
        }
    }
}

$outstanding = $totalBilled - $totalPaid;
```

## Best Practices

### 1. Always Validate Before Booking

```php
// Create draft first
$invoice = Teamleader::invoices()->draft($data);

// Review
$review = Teamleader::invoices()->info($invoice['data']['id']);

// Book only after validation
if ($this->validateInvoice($review['data'])) {
    Teamleader::invoices()->book($invoice['data']['id'], date('Y-m-d'));
}
```

### 2. Handle Payment Methods Correctly

```php
// Get available payment methods first
$paymentMethods = Teamleader::paymentMethods()->list();

// Use correct payment method ID when registering
Teamleader::invoices()->registerPayment(
    'invoice-uuid',
    $amount,
    $paymentDate,
    $paymentMethods['data'][0]['id']
);
```

### 3. Use Grouped Lines Effectively

```php
$invoice = Teamleader::invoices()->create([
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Hardware'
            ],
            'line_items' => [
                // Hardware items
            ]
        ],
        [
            'section' => [
                'title' => 'Services'
            ],
            'line_items' => [
                // Service items
            ]
        ]
    ]
]);
```

### 4. Track Credit Notes

```php
// Always associate credit notes with original invoice
$creditNote = Teamleader::invoices()->credit($invoiceId, $creditDate);

// Store relationship in your system
$this->storeCreditNoteRelation(
    $invoiceId,
    $creditNote['data']['id']
);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $invoice = Teamleader::invoices()->book('invoice-uuid', '2024-02-01');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Invalid invoice data', $e->getErrors());
    } elseif ($e->getCode() === 404) {
        // Invoice not found
        Log::error('Invoice does not exist');
    } else {
        // Other error
        Log::error('Failed to book invoice', ['error' => $e->getMessage()]);
    }
}
```

## Related Resources

- [Credit Notes](credit-notes.md) - Manage credit notes
- [Payment Methods](payment-methods.md) - Payment method information
- [Payment Terms](payment-terms.md) - Payment term configuration
- [Tax Rates](tax-rates.md) - Tax rate information
- [Companies](../crm/companies.md) - Customer management
- [Contacts](../crm/contacts.md) - Contact management
- [Projects](../projects/projects.md) - Project management

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
- **Deletion**: ✅ Supported (draft or last booked invoice only)

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
- `late_fees` — Include late fee calculations (`totals.due_incasso_inclusive`, `totals.fixed_late_fee`, `totals.interest`)

**Example:**
```php
// Basic invoice information
$invoice = Teamleader::invoices()->info('invoice-uuid');

// With late fees
$invoice = Teamleader::invoices()->info('invoice-uuid', 'late_fees');
```

### `create()` / `draft()`

Create a new draft invoice. Note: `draft()` is a **helper method** that lists draft-status invoices — use `create()` to create a new invoice.

**Required fields:**
- `invoicee` (object): Invoice recipient — must contain `customer` with `type` and `id`
- `department_id` (string): Department UUID
- `payment_term` (object): Must contain `type`; `days` required unless type is `cash`
- `grouped_lines` (array): At least one section with `line_items`

**Optional fields:**
- `currency` (object): `code` (currency code) and optional `exchange_rate`
- `project_id` (string): Project UUID to link the invoice to
- `purchase_order_number` (string)
- `invoice_date` (string): YYYY-MM-DD
- `discounts` (array): Invoice-level discounts
- `note` (string): Internal notes
- `expected_payment_method` (object): `method` and optional `reference`
- `custom_fields` (array): Custom field values
- `document_template_id` (string)
- `delivery_date` (string|null): YYYY-MM-DD — the delivery/service date printed on the invoice

**Example:**
```php
$invoice = Teamleader::invoices()->create([
    'department_id' => 'dept-uuid',
    'invoice_date' => '2024-02-01',
    'delivery_date' => '2024-01-31',
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'for_attention_of' => [
            'name' => 'John Doe'
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

Update a draft invoice. Booked invoices cannot be updated with this method.

**Parameters:**
- `id` (string): Invoice UUID
- `data` (array): Fields to update

**Updatable fields include:**
- `invoicee`, `payment_term`, `currency`, `project_id`, `purchase_order_number`
- `grouped_lines`, `invoice_date`, `note`, `discounts`, `expected_payment_method`
- `custom_fields`, `document_template_id`
- `delivery_date` (string|null): YYYY-MM-DD — update or clear the delivery/service date

**Example:**
```php
$result = Teamleader::invoices()->update('invoice-uuid', [
    'note' => 'Updated internal note',
    'purchase_order_number' => 'PO-12345',
    'delivery_date' => '2024-01-31'
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
            'section' => ['title' => 'Returns'],
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
- `payment` (array): Payment data with `amount` and `currency`
- `paidAt` (string): Payment datetime (ISO 8601 format)
- `paymentMethodId` (string, optional): Payment method UUID

**Example:**
```php
$result = Teamleader::invoices()->registerPayment(
    'invoice-uuid',
    ['amount' => 250.00, 'currency' => 'EUR'],
    '2024-02-10T00:00:00+00:00',
    'payment-method-uuid'
);
```

### `download()`

Download an invoice in a specific format.

**Parameters:**
- `id` (string): Invoice UUID
- `format` (string, optional): Format type (default: `pdf`)
    - `pdf` — PDF document
    - `ubl/e-fff` — UBL E-FFF format
    - `ubl/peppol_bis_3` — UBL Peppol BIS 3.0 format

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

Send an invoice via the Peppol network. After sending, track the submission status via the `peppol_status` field in `info()` or `list()`.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
$result = Teamleader::invoices()->sendViaPeppol('invoice-uuid');

// Check submission status afterwards
$invoice = Teamleader::invoices()->info('invoice-uuid');
echo $invoice['data']['peppol_status']; // e.g. "sent", "application_accepted"
```

## Helper Methods

The Invoices resource provides convenient helper methods:

### Status-based Methods

```php
// Get draft invoices
$drafts = Teamleader::invoices()->draft();

// Get outstanding (unpaid) invoices
$outstanding = Teamleader::invoices()->outstanding();

// Get matched (paid) invoices
$matched = Teamleader::invoices()->matched();
```

### Customer-based Methods

```php
// Get invoices for a specific company
$invoices = Teamleader::invoices()->forCustomer('company', 'company-uuid');

// Get invoices for a specific contact
$invoices = Teamleader::invoices()->forCustomer('contact', 'contact-uuid');
```

### Department and Project Methods

```php
// Get invoices for a department
$invoices = Teamleader::invoices()->forDepartment('dept-uuid');

// Get invoices for a project
$invoices = Teamleader::invoices()->forProject('project-uuid');
```

### Search and Date Methods

```php
// Search by term (invoice number, PO number, payment reference, invoicee)
$invoices = Teamleader::invoices()->search('Interesting invoice');

// Get invoices updated since a datetime
$invoices = Teamleader::invoices()->updatedSince('2024-01-01T00:00:00+00:00');
```

## Filtering

Available filters for invoices:

- `ids` — Array of invoice UUIDs
- `term` — Search on invoice number, PO number, payment reference, invoicee
- `invoice_number` — Full invoice number (fiscal year / number)
- `department_id` — Filter by department UUID
- `deal_id` — Filter by deal UUID
- `project_id` — Filter by project UUID
- `subscription_id` — Filter by subscription UUID
- `status` — Array of statuses: `draft`, `outstanding`, `matched`
- `updated_since` — ISO 8601 datetime
- `purchase_order_number` — PO number
- `payment_reference` — Payment reference
- `invoice_date_after` — Date (inclusive, YYYY-MM-DD)
- `invoice_date_before` — Date (inclusive, YYYY-MM-DD)
- `customer` — Object with `type` (`contact` or `company`) and `id`

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

- `invoice_number` — Sort by invoice number (default)
- `invoice_date` — Sort by invoice date

Default sort order: `desc`

**Example:**
```php
$invoices = Teamleader::invoices()->list([], [
    'sort' => [
        ['field' => 'invoice_date', 'order' => 'desc']
    ]
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
      "due_on": "2024-03-03",
      "paid": false,
      "paid_at": null,
      "sent": true,
      "purchase_order_number": null,
      "payment_reference": "+++084/2613/66074+++",
      "invoicee": {
        "name": "Company Name",
        "vat_number": "BE0123456789",
        "customer": {
          "type": "company",
          "id": "uuid"
        },
        "for_attention_of": null
      },
      "total": {
        "tax_exclusive": { "amount": 100.00, "currency": "EUR" },
        "tax_inclusive": { "amount": 121.00, "currency": "EUR" },
        "payable": { "amount": 121.00, "currency": "EUR" },
        "due": { "amount": 121.00, "currency": "EUR" }
      },
      "currency_exchange_rate": null,
      "deal": null,
      "project": null,
      "subscription": null,
      "file": null,
      "delivery_date": "2024-01-31",
      "peppol_status": null,
      "created_at": "2024-02-01T10:00:00+00:00",
      "updated_at": "2024-02-01T10:00:00+00:00",
      "web_url": "https://focus.teamleader.eu/invoice_detail.php?id=uuid"
    }
  ]
}
```

**Fields of note in list response:**
- `subscription` (object|null) — Present when the invoice was generated by a subscription: `{id, type}`. Null otherwise.
- `delivery_date` (string|null) — Delivery/service date in YYYY-MM-DD format.
- `peppol_status` (string|null) — Current Peppol submission status. See [Peppol Status Values](#peppol-status-values).

### Info Response

```json
{
  "data": {
    "id": "uuid",
    "department": { "type": "department", "id": "uuid" },
    "invoice_number": "2024/001",
    "invoice_date": "2024-02-01",
    "status": "outstanding",
    "due_on": "2024-03-03",
    "paid": false,
    "paid_at": null,
    "sent": true,
    "purchase_order_number": null,
    "invoicee": {
      "name": "Company Name",
      "vat_number": "BE0123456789",
      "customer": { "type": "company", "id": "uuid" },
      "for_attention_of": null,
      "email": null,
      "national_identification_number": null
    },
    "discounts": [],
    "grouped_lines": [
      {
        "section": { "title": "Products" },
        "line_items": [
          {
            "product": null,
            "quantity": 2,
            "description": "Product A",
            "extended_description": null,
            "unit": null,
            "unit_price": { "amount": 50.00, "tax": "excluding" },
            "tax": { "type": "taxRate", "id": "uuid" },
            "discount": null,
            "total": {
              "tax_exclusive": { "amount": 100.00, "currency": "EUR" },
              "tax_exclusive_before_discount": { "amount": 100.00, "currency": "EUR" },
              "tax_inclusive": { "amount": 121.00, "currency": "EUR" },
              "tax_inclusive_before_discount": { "amount": 121.00, "currency": "EUR" }
            },
            "product_category": null,
            "withheld_tax": null
          }
        ]
      }
    ],
    "payment_term": { "type": "after_invoice_date", "days": 30 },
    "payments": [],
    "payment_reference": null,
    "note": null,
    "currency": "EUR",
    "currency_exchange_rate": null,
    "expected_payment_method": null,
    "total": {
      "tax_exclusive": { "amount": 100.00, "currency": "EUR" },
      "tax_exclusive_before_discount": { "amount": 100.00, "currency": "EUR" },
      "tax_inclusive": { "amount": 121.00, "currency": "EUR" },
      "tax_inclusive_before_discount": { "amount": 121.00, "currency": "EUR" },
      "taxes": [],
      "withheld_taxes": [],
      "payable": { "amount": 121.00, "currency": "EUR" },
      "due": { "amount": 121.00, "currency": "EUR" }
    },
    "file": null,
    "deal": null,
    "project": null,
    "on_hold_since": null,
    "custom_fields": [],
    "document_template": null,
    "delivery_date": "2024-01-31",
    "peppol_status": null,
    "created_at": "2024-02-01T10:00:00+00:00",
    "updated_at": "2024-02-01T10:00:00+00:00"
  }
}
```

**Fields of note in info response:**
- `delivery_date` (string|null) — Delivery/service date in YYYY-MM-DD format.
- `peppol_status` (string|null) — Current Peppol submission status. See [Peppol Status Values](#peppol-status-values).

> **Note:** The `subscription` field is only present in the **list** response, not in `info`.

### Peppol Status Values

The `peppol_status` field is `null` until `sendViaPeppol()` is called. Once submitted, it cycles through these values:

| Value | Meaning |
|-------|---------|
| `sending` | Submission in progress |
| `sending_failed` | Submission failed |
| `sent` | Successfully submitted to the Peppol network |
| `application_acknowledged` | Acknowledged by recipient's application |
| `application_accepted` | Accepted by recipient's application |
| `application_rejected` | Rejected by recipient's application |
| `receiver_acknowledged` | Acknowledged by receiver |
| `receiver_accepted` | Accepted by receiver |
| `receiver_rejected` | Rejected by receiver |
| `receiver_is_processing` | Being processed by receiver |
| `receiver_awaits_feedback` | Awaiting feedback from receiver |
| `receiver_conditionally_accepted` | Conditionally accepted by receiver |
| `receiver_paid` | Receiver has marked as paid |

## Usage Examples

### Create and Book an Invoice

```php
// Step 1: Create draft with delivery date
$invoice = Teamleader::invoices()->create([
    'department_id' => 'dept-uuid',
    'invoice_date' => '2024-02-01',
    'delivery_date' => '2024-01-31',
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'grouped_lines' => [
        [
            'section' => ['title' => 'Services'],
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
    ],
    'payment_term' => [
        'type' => 'after_invoice_date',
        'days' => 30
    ]
]);

// Step 2: Book the invoice
$result = Teamleader::invoices()->book($invoice['data']['id'], '2024-02-01');
```

### Handle Partial Payment

```php
$invoiceId = 'invoice-uuid';

// Register first payment
Teamleader::invoices()->registerPayment(
    $invoiceId,
    ['amount' => 500.00, 'currency' => 'EUR'],
    '2024-02-10T00:00:00+00:00'
);

// Register second payment
Teamleader::invoices()->registerPayment(
    $invoiceId,
    ['amount' => 500.00, 'currency' => 'EUR'],
    '2024-02-20T00:00:00+00:00'
);
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
            'section' => ['title' => 'Returns'],
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

### Send via Peppol and Track Status

```php
// Send the invoice
Teamleader::invoices()->sendViaPeppol('invoice-uuid');

// Poll for updated status
$invoice = Teamleader::invoices()->info('invoice-uuid');
$status = $invoice['data']['peppol_status'];

if ($status === 'application_accepted') {
    echo 'Invoice accepted by recipient';
} elseif ($status === 'application_rejected') {
    echo 'Invoice rejected — check with recipient';
} elseif ($status === 'sending_failed') {
    echo 'Submission failed — retry or check Peppol settings';
}
```

### Generate Monthly Invoice Report

```php
$monthlyInvoices = Teamleader::invoices()->list([
    'invoice_date_after' => '2024-02-01',
    'invoice_date_before' => '2024-02-29',
]);

$totalRevenue = 0;
foreach ($monthlyInvoices['data'] as $invoice) {
    if ($invoice['status'] !== 'draft') {
        $totalRevenue += $invoice['total']['tax_exclusive']['amount'];
    }
}

echo 'Total Revenue for February 2024: €' . number_format($totalRevenue, 2);
```

## Common Use Cases

### 1. Invoice Creation Workflow

```php
// Create draft
$draft = Teamleader::invoices()->create($invoiceData);

// Review and update if needed
Teamleader::invoices()->update($draft['data']['id'], [
    'note' => 'Additional internal note',
    'delivery_date' => '2024-01-31'
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
    if ($invoice['due_on'] < date('Y-m-d')) {
        echo 'Overdue: ' . $invoice['invoice_number'] . "\n";
    }
}
```

### 3. Customer Invoice History

```php
$customerInvoices = Teamleader::invoices()->forCustomer('company', 'company-uuid');

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

### 4. Subscription-Generated Invoices

```php
// Find all invoices generated by subscriptions
$allInvoices = Teamleader::invoices()->list();

$subscriptionInvoices = array_filter(
    $allInvoices['data'],
    fn($invoice) => $invoice['subscription'] !== null
);

// Or filter directly by subscription UUID
$subInvoices = Teamleader::invoices()->list([
    'subscription_id' => 'subscription-uuid'
]);
```

## Best Practices

### 1. Always Validate Before Booking

```php
// Create draft first
$invoice = Teamleader::invoices()->create($data);

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
    ['amount' => $amount, 'currency' => 'EUR'],
    $paymentDate,
    $paymentMethods['data'][0]['id']
);
```

### 3. Use Grouped Lines Effectively

```php
$invoice = Teamleader::invoices()->create([
    'grouped_lines' => [
        [
            'section' => ['title' => 'Hardware'],
            'line_items' => [
                // Hardware items
            ]
        ],
        [
            'section' => ['title' => 'Services'],
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
- [Subscriptions](subscriptions.md) - Subscription management

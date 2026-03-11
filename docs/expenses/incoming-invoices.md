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
    - [markAsPendingReview()](#markaspendingreview)
    - [sendToBookkeeping()](#sendtobookkeeping)
    - [listPayments()](#listpayments)
    - [registerPayment()](#registerpayment)
    - [removePayment()](#removepayment)
    - [updatePayment()](#updatepayment)
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
- **Payment Management**: ✅ Supported

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
- `iban_number` (string): IBAN number for payment
- `company_entity_id` (string): Company entity UUID (defaults to primary entity)
- `file_id` (string): Attached file UUID

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
    'iban_number' => 'BE68539007547034',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 2500.00
        ]
    ]
]);
```

### `update()`

Update an existing incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `data` (array): Fields to update (same optional fields as create)

**Example:**
```php
Teamleader::incomingInvoices()->update('invoice-uuid', [
    'title' => 'Updated Invoice Title',
    'due_date' => '2024-03-15',
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

### `markAsPendingReview()`

Reset an invoice back to pending review status.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
Teamleader::incomingInvoices()->markAsPendingReview('invoice-uuid');
```

### `sendToBookkeeping()`

Send an approved invoice to your bookkeeping system.

**Parameters:**
- `id` (string): Invoice UUID

**Example:**
```php
Teamleader::incomingInvoices()->sendToBookkeeping('invoice-uuid');
```

### `listPayments()`

List all payments registered against an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID

**Returns:** An array of payment objects plus a meta total.

**Example:**
```php
$payments = Teamleader::incomingInvoices()->listPayments('invoice-uuid');

// $payments['data'] contains individual payment records
// $payments['meta']['total']['amount'] contains the total paid amount
```

### `registerPayment()`

Register a payment for an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `payment` (array): Payment details
    - `amount` (float, required): Payment amount — e.g. `123.30`
    - `currency` (string, required): Currency code — e.g. `EUR`
- `paidAt` (string, required): ISO 8601 datetime when payment was made
- `paymentMethodId` (string|null): Optional payment method UUID
- `remark` (string|null): Optional remark

**Returns:** Created payment with `data.type` and `data.id`.

**Example:**
```php
$payment = Teamleader::incomingInvoices()->registerPayment(
    'invoice-uuid',
    ['amount' => 1210.00, 'currency' => 'EUR'],
    '2024-02-01T10:00:00Z',
    'payment-method-uuid',
    'Paid via bank transfer'
);
```

### `removePayment()`

Remove a specific payment from an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `paymentId` (string): Payment UUID to remove

**Example:**
```php
Teamleader::incomingInvoices()->removePayment('invoice-uuid', 'payment-uuid');
```

### `updatePayment()`

Update an existing payment on an incoming invoice.

**Parameters:**
- `id` (string): Invoice UUID
- `paymentId` (string): Payment UUID to update
- `payment` (array): Updated payment details
    - `amount` (float, required): Updated amount
    - `currency` (string, required): Currency code
- `paidAt` (string|null): Optional updated ISO 8601 datetime
- `paymentMethodId` (string|null): Optional payment method UUID
- `remark` (string|null): Optional remark

**Example:**
```php
Teamleader::incomingInvoices()->updatePayment(
    'invoice-uuid',
    'payment-uuid',
    ['amount' => 600.00, 'currency' => 'EUR'],
    '2024-02-05T14:00:00Z'
);
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

### Payment Statuses

Returned by the `info()` endpoint as `payment_status`:

- `unknown` - Payment status cannot be determined
- `paid` - Invoice has been fully paid
- `partially_paid` - Invoice has been partially paid
- `not_paid` - Invoice has not been paid

Get the list programmatically:
```php
$statuses = Teamleader::incomingInvoices()->getValidPaymentStatuses();
```

## Response Structure

### Invoice Object (info)

```json
{
  "data": {
    "id": "invoice-uuid",
    "title": "Monthly Services",
    "origin": {
      "type": "user",
      "id": "user-uuid"
    },
    "supplier": {
      "type": "company",
      "id": "company-uuid"
    },
    "document_number": "INV-2024/001",
    "invoice_date": "2024-01-15",
    "due_date": "2024-02-15",
    "currency": {
      "code": "EUR"
    },
    "total": {
      "tax_exclusive": {
        "amount": 1000.00
      },
      "tax_inclusive": {
        "amount": 1210.00
      }
    },
    "company_entity": {
      "type": "company_entity",
      "id": "entity-uuid"
    },
    "file": null,
    "payment_reference": "+++123/4567/89012+++",
    "review_status": "approved",
    "iban_number": "BE68539007547034",
    "payment_status": "partially_paid"
  }
}
```

### Payment Object (listPayments)

```json
{
  "data": [
    {
      "id": "payment-uuid",
      "payment": {
        "amount": 500.00,
        "currency": "EUR"
      },
      "paid_at": "2024-02-01T10:00:00+00:00",
      "payment_method": {
        "type": "payment_method",
        "id": "method-uuid"
      },
      "remark": "First instalment"
    }
  ],
  "meta": {
    "total": {
      "amount": 500.00
    }
  }
}
```

## Usage Examples

### Create Invoice from Email

```php
$invoiceData = [
    'title' => 'Web Hosting Services',
    'supplier_id' => $supplierId,
    'document_number' => $extractedInvoiceNumber,
    'invoice_date' => $extractedDate,
    'due_date' => date('Y-m-d', strtotime($extractedDate . '+30 days')),
    'currency' => ['code' => 'EUR'],
    'total' => [
        'tax_inclusive' => ['amount' => $extractedAmount]
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

    if ($invoice['data']['total']['tax_inclusive']['amount'] < 5000) {
        Teamleader::incomingInvoices()->approve($expense['source_id']);
        Teamleader::incomingInvoices()->sendToBookkeeping($expense['source_id']);
    } else {
        echo "Invoice {$invoice['data']['document_number']} requires manual approval\n";
    }
}
```

### Register and Track Payments

```php
$invoiceId = 'invoice-uuid';

// Check current payment status
$invoice = Teamleader::incomingInvoices()->info($invoiceId);
echo "Payment status: " . $invoice['data']['payment_status']; // e.g. not_paid

// Register a partial payment
Teamleader::incomingInvoices()->registerPayment(
    $invoiceId,
    ['amount' => 500.00, 'currency' => 'EUR'],
    '2024-02-01T10:00:00Z',
    null,
    'First instalment'
);

// Register the remaining payment
Teamleader::incomingInvoices()->registerPayment(
    $invoiceId,
    ['amount' => 710.00, 'currency' => 'EUR'],
    '2024-02-15T10:00:00Z',
    null,
    'Final payment'
);

// Verify all payments
$payments = Teamleader::incomingInvoices()->listPayments($invoiceId);
echo "Total paid: " . $payments['meta']['total']['amount'];
```

### Correct a Wrong Payment

```php
$invoiceId = 'invoice-uuid';

// List payments to find the incorrect one
$payments = Teamleader::incomingInvoices()->listPayments($invoiceId);
$wrongPaymentId = $payments['data'][0]['id'];

// Update with the correct amount
Teamleader::incomingInvoices()->updatePayment(
    $invoiceId,
    $wrongPaymentId,
    ['amount' => 1210.00, 'currency' => 'EUR'],
    '2024-02-01T10:00:00Z'
);
```

### Remove an Incorrect Payment

```php
$payments = Teamleader::incomingInvoices()->listPayments('invoice-uuid');
$paymentId = $payments['data'][0]['id'];

Teamleader::incomingInvoices()->removePayment('invoice-uuid', $paymentId);
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

// 4. Register payment once paid
Teamleader::incomingInvoices()->registerPayment(
    $invoice['data']['id'],
    ['amount' => 605.00, 'currency' => 'EUR'],
    date('c')
);
```

### Check Unpaid Invoices

```php
$expenses = Teamleader::expenses()->list([
    'source_types' => ['incomingInvoice'],
    'review_statuses' => ['approved']
]);

foreach ($expenses['data'] as $expense) {
    $invoice = Teamleader::incomingInvoices()->info($expense['source_id']);

    if (in_array($invoice['data']['payment_status'], ['not_paid', 'partially_paid'])) {
        echo "Unpaid invoice: {$invoice['data']['document_number']}\n";

        $payments = Teamleader::incomingInvoices()->listPayments($expense['source_id']);
        echo "  Paid so far: " . $payments['meta']['total']['amount'] . "\n";
    }
}
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

3. **Use payment_status for Quick Checks**: Instead of summing listPayments manually, use the `payment_status` field from `info()` to quickly determine whether an invoice needs attention
```php
$invoice = Teamleader::incomingInvoices()->info($invoiceId);

if ($invoice['data']['payment_status'] === 'not_paid') {
    // Trigger payment reminder or register payment
}
```

4. **Handle Bookkeeping Submissions**: Check submission status after sending
```php
Teamleader::incomingInvoices()->sendToBookkeeping($invoiceId);

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

    Teamleader::incomingInvoices()->approve($invoice['data']['id']);
    Teamleader::incomingInvoices()->sendToBookkeeping($invoice['data']['id']);

} catch (\InvalidArgumentException $e) {
    Log::error('Invalid invoice data: ' . $e->getMessage());

} catch (\Exception $e) {
    Log::error('Failed to create/process invoice: ' . $e->getMessage());
}

// Handle payment errors
try {
    Teamleader::incomingInvoices()->registerPayment(
        $invoiceId,
        ['amount' => 1210.00, 'currency' => 'EUR'],
        '2024-02-01T10:00:00Z'
    );

} catch (\InvalidArgumentException $e) {
    // Missing required payment fields or invalid currency
    Log::error('Invalid payment data: ' . $e->getMessage());

} catch (\Exception $e) {
    Log::error('Failed to register payment: ' . $e->getMessage());
}

// Handle bookkeeping failures
try {
    Teamleader::incomingInvoices()->sendToBookkeeping($invoiceId);

    sleep(2);

    $submissions = Teamleader::bookkeepingSubmissions()->forInvoice($invoiceId);

    if ($submissions['data'][0]['status'] === 'failed') {
        $error = $submissions['data'][0]['error']['message'];
        Log::error("Bookkeeping submission failed: {$error}");
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

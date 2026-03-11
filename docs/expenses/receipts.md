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

`receipts`

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

Get detailed information about a specific receipt.

**Parameters:**
- `id` (string): The receipt UUID

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$receipt = Teamleader::receipts()->info('receipt-uuid');

// Access fields
echo $receipt['data']['title'];
echo $receipt['data']['review_status'];  // pending, approved, refused
echo $receipt['data']['payment_status']; // unknown, paid, not_paid
```

### `create()` / `add()`

Create a new expense receipt.

**Parameters:**
- `title` (string, required): Receipt title
- `supplier_id` (string|null): Supplier UUID
- `document_number` (string|null): Reference number on the receipt
- `receipt_date` (string|null): Date of the receipt (YYYY-MM-DD)
- `currency` (object, required):
    - `code` (string, required): Currency code — e.g. `EUR`
- `total` (object, required):
    - `tax_inclusive` (object|null):
        - `amount` (float): Total amount including tax
- `company_entity_id` (string|null): Company entity UUID — defaults to your main entity
- `file_id` (string|null): Attached file UUID

**Returns:** `data.type` and `data.id` of the created receipt.

**Example:**
```php
$receipt = Teamleader::receipts()->create([
    'title' => 'Client Lunch',
    'supplier_id' => 'supplier-uuid',
    'document_number' => 'REC-001',
    'receipt_date' => '2024-01-15',
    'currency' => ['code' => 'EUR'],
    'total' => [
        'tax_inclusive' => ['amount' => 45.50]
    ],
    'file_id' => 'file-uuid',
]);

$receiptId = $receipt['data']['id'];
```

### `update()`

Update an existing receipt.

**Parameters:**
- `id` (string, required): Receipt UUID
- All other fields from `create()` are optional

**Example:**
```php
Teamleader::receipts()->update('receipt-uuid', [
    'title' => 'Client Lunch — Acme Corp',
    'receipt_date' => '2024-01-16',
    'total' => [
        'tax_inclusive' => ['amount' => 52.00]
    ],
]);
```

### `delete()`

Delete a receipt permanently.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->delete('receipt-uuid');
```

### `approve()`

Approve a receipt, moving it from `pending` to `approved` review status.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->approve('receipt-uuid');
```

### `refuse()`

Refuse a receipt, moving it to `refused` review status.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->refuse('receipt-uuid');
```

### `markAsPendingReview()`

Reset a receipt back to `pending` review status.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->markAsPendingReview('receipt-uuid');
```

### `sendToBookkeeping()`

Send an approved receipt to your bookkeeping system for processing.

**Parameters:**
- `id` (string): Receipt UUID

**Example:**
```php
Teamleader::receipts()->sendToBookkeeping('receipt-uuid');
```

### `listPayments()`

List all payments registered against a receipt.

**Parameters:**
- `id` (string): Receipt UUID

**Returns:** An array of payment objects plus a meta total.

**Example:**
```php
$payments = Teamleader::receipts()->listPayments('receipt-uuid');

// $payments['data'] contains individual payment records
// $payments['meta']['total']['amount'] contains the total paid amount
foreach ($payments['data'] as $payment) {
    echo $payment['payment']['amount'];
    echo $payment['paid_at'];
    echo $payment['remark'] ?? '';
}
```

### `registerPayment()`

Register a payment for a receipt.

**Parameters:**
- `id` (string): Receipt UUID
- `payment` (array): Payment details
    - `amount` (float, required): Payment amount — e.g. `45.50`
    - `currency` (string, required): Currency code — e.g. `EUR`
- `paidAt` (string, required): ISO 8601 datetime when payment was made
- `paymentMethodId` (string|null): Optional payment method UUID
- `remark` (string|null): Optional remark

**Returns:** Created payment with `data.type` and `data.id`.

**Example:**
```php
$payment = Teamleader::receipts()->registerPayment(
    'receipt-uuid',
    ['amount' => 45.50, 'currency' => 'EUR'],
    '2024-01-15T12:00:00Z',
    'payment-method-uuid',
    'Paid with company card'
);
```

### `removePayment()`

Remove a specific payment from a receipt.

**Parameters:**
- `id` (string): Receipt UUID
- `paymentId` (string): Payment UUID to remove

**Example:**
```php
Teamleader::receipts()->removePayment('receipt-uuid', 'payment-uuid');
```

### `updatePayment()`

Update an existing payment on a receipt.

**Parameters:**
- `id` (string): Receipt UUID
- `paymentId` (string): Payment UUID to update
- `payment` (array): Updated payment details
    - `amount` (float, required): Updated amount
    - `currency` (string, required): Currency code
- `paidAt` (string|null): Optional updated ISO 8601 datetime
- `paymentMethodId` (string|null): Optional payment method UUID
- `remark` (string|null): Optional remark

**Example:**
```php
Teamleader::receipts()->updatePayment(
    'receipt-uuid',
    'payment-uuid',
    ['amount' => 52.00, 'currency' => 'EUR'],
    '2024-01-15T14:00:00Z'
);
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
- `approved` - Approved
- `refused` - Rejected/refused

Get the list programmatically:
```php
$statuses = Teamleader::receipts()->getValidReviewStatuses();
```

### Payment Statuses

Returned by the `info()` endpoint as `payment_status`:

- `unknown` - Payment status cannot be determined
- `paid` - Receipt has been fully paid
- `not_paid` - Receipt has not been paid

Get the list programmatically:
```php
$statuses = Teamleader::receipts()->getValidPaymentStatuses();
```

## Response Structure

### Receipt Object (info)

```json
{
  "data": {
    "id": "receipt-uuid",
    "title": "Client Lunch",
    "origin": {
      "type": "user",
      "id": "user-uuid"
    },
    "supplier": {
      "type": "company",
      "id": "company-uuid"
    },
    "document_number": "REC-001",
    "receipt_date": "2024-01-15",
    "currency": {
      "code": "EUR"
    },
    "total": {
      "tax_inclusive": {
        "amount": 45.50
      }
    },
    "company_entity": {
      "type": "company",
      "id": "entity-uuid"
    },
    "file": {
      "type": "file",
      "id": "file-uuid"
    },
    "review_status": "approved",
    "payment_status": "paid"
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
        "amount": 45.50,
        "currency": "EUR"
      },
      "paid_at": "2024-01-15T12:00:00Z",
      "payment_method": {
        "type": "payment_method",
        "id": "payment-method-uuid"
      },
      "remark": "Paid with company card"
    }
  ],
  "meta": {
    "total": {
      "amount": 45.50
    }
  }
}
```

## Usage Examples

### Full Receipt Workflow

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// 1. Create the receipt
$receipt = Teamleader::receipts()->create([
    'title' => 'Client Lunch — Acme Corp',
    'supplier_id' => 'restaurant-company-uuid',
    'document_number' => 'REC-2024/001',
    'receipt_date' => '2024-01-15',
    'currency' => ['code' => 'EUR'],
    'total' => [
        'tax_inclusive' => ['amount' => 45.50]
    ],
]);

$receiptId = $receipt['data']['id'];

// 2. Approve it
Teamleader::receipts()->approve($receiptId);

// 3. Register payment
Teamleader::receipts()->registerPayment(
    $receiptId,
    ['amount' => 45.50, 'currency' => 'EUR'],
    date('c'),
    null,
    'Paid with company card'
);

// 4. Send to bookkeeping
Teamleader::receipts()->sendToBookkeeping($receiptId);
```

### Correct a Wrong Payment

```php
$receiptId = 'receipt-uuid';

// Find the incorrect payment
$payments = Teamleader::receipts()->listPayments($receiptId);
$wrongPaymentId = $payments['data'][0]['id'];

// Update with the correct amount
Teamleader::receipts()->updatePayment(
    $receiptId,
    $wrongPaymentId,
    ['amount' => 52.00, 'currency' => 'EUR'],
    '2024-01-15T14:00:00Z'
);
```

### Remove an Incorrect Payment

```php
$payments = Teamleader::receipts()->listPayments('receipt-uuid');
$paymentId = $payments['data'][0]['id'];

Teamleader::receipts()->removePayment('receipt-uuid', $paymentId);
```

### Refuse and Reset a Receipt

```php
// Refuse an incorrect receipt
Teamleader::receipts()->refuse('receipt-uuid');

// Later, reset to pending for re-review
Teamleader::receipts()->markAsPendingReview('receipt-uuid');

// Update with corrections and re-approve
Teamleader::receipts()->update('receipt-uuid', [
    'total' => ['tax_inclusive' => ['amount' => 38.00]],
]);

Teamleader::receipts()->approve('receipt-uuid');
```

## Common Use Cases

### Bulk Receipt Processing

```php
$expenseList = Teamleader::expenses()->list([
    'source_types' => ['receipt'],
    'review_statuses' => ['pending'],
]);

foreach ($expenseList['data'] as $expense) {
    $receipt = Teamleader::receipts()->info($expense['source_id']);

    // Auto-approve receipts under €50
    if ($receipt['data']['total']['tax_inclusive']['amount'] <= 50) {
        Teamleader::receipts()->approve($expense['source_id']);
        Teamleader::receipts()->sendToBookkeeping($expense['source_id']);
    }
}
```

### Check Unpaid Receipts

```php
$expenses = Teamleader::expenses()->list([
    'source_types' => ['receipt'],
    'review_statuses' => ['approved'],
]);

foreach ($expenses['data'] as $expense) {
    $receipt = Teamleader::receipts()->info($expense['source_id']);

    if ($receipt['data']['payment_status'] === 'not_paid') {
        echo "Unpaid receipt: {$receipt['data']['title']}\n";

        $payments = Teamleader::receipts()->listPayments($expense['source_id']);
        echo "  Paid so far: " . $payments['meta']['total']['amount'] . "\n";
    }
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

2. **Always Use Tax-Inclusive Amounts**: Receipts only support `tax_inclusive` totals
```php
// Correct
'total' => ['tax_inclusive' => ['amount' => 45.50]]

// Wrong - will fail validation
'total' => ['tax_exclusive' => ['amount' => 45.50]]
```

3. **Use Descriptive Titles**: Make receipts easy to identify
```php
// Good
'title' => 'Client Lunch - Acme Corp - Restaurant XYZ'

// Avoid
'title' => 'Receipt'
```

4. **Validate Before Approving**: Implement approval rules appropriate to your workflow
```php
$receipt = Teamleader::receipts()->info($receiptId);

if ($receipt['data']['total']['tax_inclusive']['amount'] <= 100) {
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

    Teamleader::receipts()->approve($receipt['data']['id']);
    Teamleader::receipts()->sendToBookkeeping($receipt['data']['id']);

} catch (\InvalidArgumentException $e) {
    // Validation error (e.g., missing required field, invalid currency)
    Log::error('Invalid receipt data: ' . $e->getMessage());

} catch (\Exception $e) {
    // API error
    Log::error('Failed to process receipt: ' . $e->getMessage());
}
```

## Related Resources

- [Expenses](expenses.md) — List and filter receipts alongside other expense types
- [Incoming Invoices](incoming-invoices.md) — Manage supplier invoices with line-item VAT detail
- [Incoming Credit Notes](incoming-creditnotes.md) — Manage credit notes from suppliers

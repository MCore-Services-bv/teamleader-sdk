# Incoming Credit Notes

Manage incoming credit notes (purchase credit notes) in Teamleader Focus.

## Overview

The Incoming Credit Notes resource allows you to manage credit notes received from your suppliers in Teamleader. These represent corrections or refunds on previously issued purchase invoices and can be sent to your bookkeeping system for processing.

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

`incomingCreditNotes`

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

Get detailed information about a specific incoming credit note. The response includes `payment_status` and `iban_number`.

**Parameters:**
- `id` (string): The incoming credit note UUID

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$creditNote = Teamleader::incomingCreditNotes()->info('credit-note-uuid');

// Access payment status
echo $creditNote['data']['payment_status']; // unknown, paid, or not_paid
echo $creditNote['data']['iban_number'];     // nullable
```

### `create()` / `add()`

Create a new incoming credit note.

**Required fields:**
- `title` (string): Credit note title/description
- `currency.code` (string): Currency code (e.g., EUR, USD, GBP)
- `total` (object): Credit note total with either:
    - `tax_exclusive.amount` (decimal): Amount excluding tax, OR
    - `tax_inclusive.amount` (decimal): Amount including tax

**Optional fields:**
- `supplier_id` (string): Supplier company UUID
- `document_number` (string): Credit note reference number
- `invoice_date` (string): Invoice date (YYYY-MM-DD)
- `due_date` (string): Payment due date (YYYY-MM-DD)
- `company_entity_id` (string): Company entity UUID (defaults to primary entity)
- `file_id` (string): Attached file UUID
- `payment_reference` (string): Payment reference/structured communication
- `iban_number` (string): IBAN number

**Example:**
```php
// Basic credit note
$creditNote = Teamleader::incomingCreditNotes()->create([
    'title' => 'Return Credit January',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 500.00
        ]
    ]
]);

// Complete credit note
$creditNote = Teamleader::incomingCreditNotes()->add([
    'title' => 'Partial Return Credit',
    'supplier_id' => 'company-uuid',
    'document_number' => 'CN-2024/001',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'payment_reference' => '+++123/4567/89012+++',
    'iban_number' => 'BE68539007547034',
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => [
            'amount' => 750.00
        ]
    ]
]);
```

### `update()`

Update an existing incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID
- `data` (array): Fields to update

**Updatable fields:** `title`, `supplier_id`, `document_number`, `invoice_date`, `due_date`, `currency`, `total`, `company_entity_id`, `file_id`, `payment_reference`, `iban_number`

**Example:**
```php
Teamleader::incomingCreditNotes()->update('credit-note-uuid', [
    'title' => 'Updated Credit Note Title',
    'due_date' => '2024-03-15',
]);
```

### `delete()`

Delete an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->delete('credit-note-uuid');
```

### `approve()`

Approve an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->approve('credit-note-uuid');
```

### `refuse()`

Refuse/reject an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->refuse('credit-note-uuid');
```

### `markAsPendingReview()`

Mark an incoming credit note as pending review.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->markAsPendingReview('credit-note-uuid');
```

### `sendToBookkeeping()`

Send an approved credit note to your bookkeeping system.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
Teamleader::incomingCreditNotes()->sendToBookkeeping('credit-note-uuid');
```

### `listPayments()`

List all registered payments for an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID

**Example:**
```php
$payments = Teamleader::incomingCreditNotes()->listPayments('credit-note-uuid');

foreach ($payments['data'] as $payment) {
    echo "Payment ID: {$payment['id']}\n";
    echo "Amount: {$payment['payment']['amount']} {$payment['payment']['currency']}\n";
    echo "Paid at: {$payment['payment']['paid_at']}\n";
}

// Total amount paid
echo "Total paid: {$payments['meta']['total']['amount']}\n";
```

### `registerPayment()`

Register a payment for an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID
- `payment` (array): Payment data
    - `amount` (number, required): Payment amount (e.g., 123.3)
    - `currency` (string, required): Currency code (e.g., EUR)
- `paidAt` (string): ISO 8601 datetime of when the payment was made
- `paymentMethodId` (string|null): Optional payment method UUID
- `remark` (string|null): Optional remark/note

**Returns:** `data.type` and `data.id` of the created payment.

**Example:**
```php
// Basic payment
$result = Teamleader::incomingCreditNotes()->registerPayment(
    'credit-note-uuid',
    ['amount' => 500.00, 'currency' => 'EUR'],
    '2024-01-20T10:00:00+00:00'
);

// Payment with method and remark
$result = Teamleader::incomingCreditNotes()->registerPayment(
    'credit-note-uuid',
    ['amount' => 250.00, 'currency' => 'EUR'],
    '2024-01-20T10:00:00+00:00',
    'payment-method-uuid',
    'Partial payment received via bank transfer'
);

$paymentId = $result['data']['id'];
```

### `removePayment()`

Remove a specific payment from an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID
- `paymentId` (string): Payment UUID to remove

**Example:**
```php
Teamleader::incomingCreditNotes()->removePayment('credit-note-uuid', 'payment-uuid');
```

### `updatePayment()`

Update an existing payment on an incoming credit note.

**Parameters:**
- `id` (string): Credit note UUID
- `paymentId` (string): Payment UUID to update
- `payment` (array): Updated payment data
    - `amount` (number, required): Payment amount
    - `currency` (string, required): Currency code
- `paidAt` (string|null): Optional updated ISO 8601 payment datetime
- `paymentMethodId` (string|null): Optional payment method UUID
- `remark` (string|null): Optional remark/note

**Example:**
```php
Teamleader::incomingCreditNotes()->updatePayment(
    'credit-note-uuid',
    'payment-uuid',
    ['amount' => 300.00, 'currency' => 'EUR'],
    '2024-01-21T09:00:00+00:00',
    null,
    'Corrected payment amount'
);
```

## Valid Values

### Currency Codes

Supported currencies: `BAM`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CZK`, `DKK`, `EUR`, `GBP`, `INR`, `ISK`, `JPY`, `MAD`, `MXN`, `NOK`, `PEN`, `PLN`, `RON`, `SEK`, `TRY`, `USD`, `ZAR`

```php
$currencies = Teamleader::incomingCreditNotes()->getValidCurrencyCodes();
```

### Review Statuses

- `pending` - Awaiting review
- `approved` - Approved
- `refused` - Rejected/refused

```php
$statuses = Teamleader::incomingCreditNotes()->getValidReviewStatuses();
```

### Payment Statuses

- `unknown` - Payment status not yet determined
- `paid` - Fully paid
- `not_paid` - Not yet paid

```php
$paymentStatuses = Teamleader::incomingCreditNotes()->getValidPaymentStatuses();
```

## Response Structure

### Credit Note Object (`info`)

```json
{
  "data": {
    "id": "credit-note-uuid",
    "title": "Return Credit January",
    "origin": {},
    "supplier": {
      "type": "company",
      "id": "company-uuid"
    },
    "document_number": "CN-2024/001",
    "invoice_date": "2024-01-15",
    "due_date": "2024-02-15",
    "currency": {
      "code": "EUR"
    },
    "total": {
      "tax_exclusive": {
        "amount": 500.00
      },
      "tax_inclusive": {
        "amount": 605.00
      }
    },
    "company_entity": {
      "type": "company",
      "id": "entity-uuid"
    },
    "file": null,
    "payment_reference": "+++123/4567/89012+++",
    "review_status": "approved",
    "iban_number": "BE68539007547034",
    "payment_status": "paid"
  }
}
```

### Payments List Object (`listPayments`)

```json
{
  "data": [
    {
      "id": "payment-uuid",
      "payment": {
        "amount": 500.00,
        "currency": "EUR",
        "paid_at": "2024-01-20T10:00:00+00:00",
        "payment_method": {
          "type": "paymentMethod",
          "id": "payment-method-uuid"
        },
        "remark": "Bank transfer"
      }
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

### Create and Process a Credit Note

```php
// Create credit note
$creditNote = Teamleader::incomingCreditNotes()->create([
    'title' => 'Partial Return Credit',
    'supplier_id' => 'company-uuid',
    'document_number' => 'CN-2024/005',
    'invoice_date' => date('Y-m-d'),
    'currency' => ['code' => 'EUR'],
    'total' => [
        'tax_exclusive' => ['amount' => 300.00]
    ]
]);

$creditNoteId = $creditNote['data']['id'];

// Approve and send to bookkeeping
Teamleader::incomingCreditNotes()->approve($creditNoteId);
Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNoteId);
```

### Register and Manage Payments

```php
$creditNoteId = 'credit-note-uuid';

// Register a payment
$result = Teamleader::incomingCreditNotes()->registerPayment(
    $creditNoteId,
    ['amount' => 500.00, 'currency' => 'EUR'],
    now()->toIso8601String(),
    null,
    'Received via SEPA transfer'
);
$paymentId = $result['data']['id'];

// Check payment status
$creditNote = Teamleader::incomingCreditNotes()->info($creditNoteId);
echo "Payment status: {$creditNote['data']['payment_status']}"; // paid

// List all payments
$payments = Teamleader::incomingCreditNotes()->listPayments($creditNoteId);
echo "Total paid: {$payments['meta']['total']['amount']}";

// Correct a payment amount
Teamleader::incomingCreditNotes()->updatePayment(
    $creditNoteId,
    $paymentId,
    ['amount' => 450.00, 'currency' => 'EUR'],
    null,
    null,
    'Corrected amount after bank confirmation'
);

// Remove a payment if entered in error
Teamleader::incomingCreditNotes()->removePayment($creditNoteId, $paymentId);
```

### Check Payment Status Before Processing

```php
$creditNote = Teamleader::incomingCreditNotes()->info('credit-note-uuid');

if ($creditNote['data']['payment_status'] === 'not_paid') {
    // Register the payment
    Teamleader::incomingCreditNotes()->registerPayment(
        'credit-note-uuid',
        ['amount' => $creditNote['data']['total']['tax_inclusive']['amount'], 'currency' => 'EUR'],
        now()->toIso8601String()
    );
}
```

### Process Multiple Credit Notes

```php
$pending = Teamleader::expenses()->list([
    'source_types' => ['incomingCreditNote'],
    'review_statuses' => ['pending']
]);

foreach ($pending['data'] as $expense) {
    $creditNote = Teamleader::incomingCreditNotes()->info($expense['source_id']);

    if ($creditNote['data']['total']['tax_inclusive']['amount'] < 1000) {
        Teamleader::incomingCreditNotes()->approve($expense['source_id']);
        Teamleader::incomingCreditNotes()->sendToBookkeeping($expense['source_id']);

        echo "Processed: {$creditNote['data']['document_number']}\n";
    }
}
```

## Common Use Cases

### Full Credit Note Workflow

```php
// 1. Create
$creditNote = Teamleader::incomingCreditNotes()->create([...]);
$id = $creditNote['data']['id'];

// 2. Review and approve
Teamleader::incomingCreditNotes()->approve($id);

// 3. Register payment received
Teamleader::incomingCreditNotes()->registerPayment(
    $id,
    ['amount' => 500.00, 'currency' => 'EUR'],
    now()->toIso8601String()
);

// 4. Send to bookkeeping
Teamleader::incomingCreditNotes()->sendToBookkeeping($id);
```

### Monthly Credit Note Report

```php
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$creditNotes = Teamleader::expenses()->byDateRange($startOfMonth, $endOfMonth, [
    'source_types' => ['incomingCreditNote']
]);

$totalAmount = 0;
$approvedCount = 0;
$paidCount = 0;

foreach ($creditNotes['data'] as $expense) {
    $creditNote = Teamleader::incomingCreditNotes()->info($expense['source_id']);
    $totalAmount += $creditNote['data']['total']['tax_inclusive']['amount'];

    if ($creditNote['data']['review_status'] === 'approved') {
        $approvedCount++;
    }

    if ($creditNote['data']['payment_status'] === 'paid') {
        $paidCount++;
    }
}

echo "Total Credit Notes: " . count($creditNotes['data']) . "\n";
echo "Approved: {$approvedCount}\n";
echo "Paid: {$paidCount}\n";
echo "Total Amount: €" . number_format($totalAmount, 2) . "\n";
```

## Best Practices

1. **Use Expenses for Listing**: To list or search credit notes, use the Expenses resource
```php
$creditNotes = Teamleader::expenses()->list([
    'source_types' => ['incomingCreditNote']
]);
```

2. **Approve Before Sending**: Always approve credit notes before sending to bookkeeping
```php
Teamleader::incomingCreditNotes()->approve($creditNoteId);
Teamleader::incomingCreditNotes()->sendToBookkeeping($creditNoteId);
```

3. **Check `payment_status` After Registering**: After registering a payment, re-fetch `info()` to confirm the status updated to `paid`
```php
Teamleader::incomingCreditNotes()->registerPayment($id, $payment, $paidAt);
$updated = Teamleader::incomingCreditNotes()->info($id);
// $updated['data']['payment_status'] should now be 'paid'
```

4. **Use `listPayments()` for Reconciliation**: Before registering a payment, check if payments already exist to avoid duplicates
```php
$existing = Teamleader::incomingCreditNotes()->listPayments($creditNoteId);
if (empty($existing['data'])) {
    Teamleader::incomingCreditNotes()->registerPayment(...);
}
```

5. **Store Payment IDs**: Keep track of payment UUIDs returned by `registerPayment()` so you can update or remove them later if needed

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $creditNote = Teamleader::incomingCreditNotes()->create([
        'title' => 'Return Credit',
        'currency' => ['code' => 'EUR'],
        'total' => ['tax_exclusive' => ['amount' => 500.00]]
    ]);

    $id = $creditNote['data']['id'];

    Teamleader::incomingCreditNotes()->approve($id);
    Teamleader::incomingCreditNotes()->sendToBookkeeping($id);

} catch (\InvalidArgumentException $e) {
    // Validation error (missing required field, invalid currency, etc.)
    Log::error('Invalid credit note data: ' . $e->getMessage());

} catch (\Exception $e) {
    // API error
    Log::error('Failed to process credit note: ' . $e->getMessage());
}
```

## Related Resources

- **[Expenses](expenses.md)** - List and search incoming credit notes
- **[Bookkeeping Submissions](bookkeeping-submissions.md)** - Track bookkeeping submission status
- **[Incoming Invoices](incoming-invoices.md)** - Related incoming invoices
- **[Receipts](receipts.md)** - Other expense types
- **[Companies](../crm/companies.md)** - Manage suppliers

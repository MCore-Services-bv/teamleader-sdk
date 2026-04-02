# Incoming Invoices

Manage incoming (purchase) invoices from suppliers in Teamleader Focus.

## Overview

The Incoming Invoices resource manages purchase invoices you receive from suppliers. These move through a review workflow (pending → approved/refused) before being sent to bookkeeping.

Access via `Teamleader::incomingInvoices()`.

> **`list()` throws** `InvalidArgumentException`. Use the `Expenses` resource to list and filter expense documents.
>
> **`create()` posts to `incomingInvoices.add`** — not `.create`. `create()` is an alias for `add()`.
>
> **Payment statuses include `partially_paid`** — unique to this resource vs incoming credit notes and receipts.

## Endpoint

`incomingInvoices`

## Capabilities

| Capability | Supported |
|---|---|
| Pagination | ❌ Not supported — use `Expenses` resource |
| Filtering | ❌ Not supported — use `Expenses` resource |
| Sorting | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation | ✅ Supported |
| Update | ✅ Supported |
| Deletion | ✅ Supported |
| Payment management | ✅ Supported |

---

## Methods

### `info(string $id)`

Throws if `$id` is empty. Returns full invoice detail including `payment_status` and `iban_number`.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$invoice = Teamleader::incomingInvoices()->info('invoice-uuid');

$paymentStatus = $invoice['data']['payment_status']; // unknown | paid | partially_paid | not_paid
$ibanNumber    = $invoice['data']['iban_number'];     // nullable
$reviewStatus  = $invoice['data']['review_status'];   // pending | approved | refused
```

---

### `add(array $data)` / `create(array $data)`

Creates a new incoming invoice. Posts to `incomingInvoices.add`. `create()` is an alias.

**Required fields (validated before the request):**

| Field | Notes |
|---|---|
| `title` | Invoice title |
| `currency.code` | Valid currency code |
| `total` | Must include at least `tax_exclusive` or `tax_inclusive` |

```php
$invoice = Teamleader::incomingInvoices()->add([
    'title'           => 'Software licences Q2',
    'supplier_id'     => 'company-uuid',     // optional
    'document_number' => 'INV-2025-042',     // optional
    'invoice_date'    => '2025-04-01',        // optional
    'due_date'        => '2025-05-01',        // optional
    'currency'        => ['code' => 'EUR'],
    'total'           => [
        'tax_exclusive' => ['amount' => 1000.0],
        'tax_inclusive' => ['amount' => 1210.0],
    ],
    'payment_reference' => 'REF-123',         // optional
    'file_id'           => 'file-uuid',       // optional
]);

$id = $invoice['data']['id'];
```

---

### `update(string $id, array $data)`

Merges `['id' => $id]` with `$data` before posting. Validates currency if provided.

```php
Teamleader::incomingInvoices()->update('invoice-uuid', [
    'title'    => 'Updated title',
    'due_date' => '2025-05-15',
]);
```

---

### `delete(string $id)`

```php
Teamleader::incomingInvoices()->delete('invoice-uuid');
```

---

### `approve(string $id)`

```php
Teamleader::incomingInvoices()->approve('invoice-uuid');
```

### `refuse(string $id)`

```php
Teamleader::incomingInvoices()->refuse('invoice-uuid');
```

### `markAsPendingReview(string $id)`

```php
Teamleader::incomingInvoices()->markAsPendingReview('invoice-uuid');
```

### `sendToBookkeeping(string $id)`

```php
Teamleader::incomingInvoices()->sendToBookkeeping('invoice-uuid');
```

---

## Payment Methods

### `listPayments(string $id)`

Returns all payments with a `meta.total.amount` summary.

```php
$payments = Teamleader::incomingInvoices()->listPayments('invoice-uuid');

$totalPaid = $payments['meta']['total']['amount'];

foreach ($payments['data'] as $payment) {
    echo $payment['payment']['amount'] . ' ' . $payment['payment']['currency'];
}
```

### `registerPayment(string $id, array $payment, string $paidAt, ?string $paymentMethodId = null, ?string $remark = null)`

`$paidAt` is required. `$payment` must have `amount` (numeric) and `currency` (valid code) — both validated before the request.

```php
Teamleader::incomingInvoices()->registerPayment(
    'invoice-uuid',
    ['amount' => 1210.0, 'currency' => 'EUR'],
    '2025-04-15T10:00:00+02:00',
    'payment-method-uuid',  // optional
    'Paid via bank transfer' // optional
);
```

### `removePayment(string $id, string $paymentId)`

```php
Teamleader::incomingInvoices()->removePayment('invoice-uuid', 'payment-uuid');
```

### `updatePayment(string $id, string $paymentId, array $payment, ?string $paidAt = null, ?string $paymentMethodId = null, ?string $remark = null)`

`$paidAt` is optional on update.

```php
Teamleader::incomingInvoices()->updatePayment(
    'invoice-uuid',
    'payment-uuid',
    ['amount' => 605.0, 'currency' => 'EUR'],
    null,
    null,
    'Corrected to partial amount'
);
```

---

## Valid Values

**Payment statuses** (`payment_status` on `info()` response):
`unknown`, `paid`, `partially_paid`, `not_paid`

**Review statuses** (`review_status` on `info()` response):
`pending`, `approved`, `refused`

---

## Usage Examples

### Full incoming invoice workflow

```php
// Create
$invoice = Teamleader::incomingInvoices()->add([
    'title'    => 'Cloud hosting Q2',
    'currency' => ['code' => 'EUR'],
    'total'    => ['tax_exclusive' => ['amount' => 500.0], 'tax_inclusive' => ['amount' => 605.0]],
]);

$id = $invoice['data']['id'];

// Approve
Teamleader::incomingInvoices()->approve($id);

// Register payment
Teamleader::incomingInvoices()->registerPayment(
    $id,
    ['amount' => 605.0, 'currency' => 'EUR'],
    now()->toIso8601String()
);

// Send to bookkeeping
Teamleader::incomingInvoices()->sendToBookkeeping($id);
```

---

## Error Handling

```php
use InvalidArgumentException;

// list() not supported
try {
    Teamleader::incomingInvoices()->list();
} catch (InvalidArgumentException $e) {
    // 'The list method is not supported for incoming invoices. Use info() to get a specific invoice.'
}

// Missing required fields on create
try {
    Teamleader::incomingInvoices()->add(['title' => 'Test']);
} catch (InvalidArgumentException $e) {
    // 'currency.code is required for incoming invoices'
}

// Invalid currency on registerPayment
try {
    Teamleader::incomingInvoices()->registerPayment('uuid', ['amount' => 100, 'currency' => 'XYZ'], '2025-04-01T00:00:00+02:00');
} catch (InvalidArgumentException $e) {
    // 'Invalid payment currency. Must be one of: ...'
}
```

---

## Related Resources

- [[Bookkeeping-Submissions]] — Track submission history per invoice
- [[Incoming-Credit-Notes]] — Supplier credit notes (same workflow)
- [[Receipts]] — Smaller expense receipts (same workflow)
- [[Payment-Methods]] — Optional on `registerPayment()`

# Receipts

Manage expense receipts in Teamleader Focus.

## Overview

The Receipts resource manages smaller business expenses — meals, parking, office supplies, travel — that don't carry detailed VAT line items. They follow the same review workflow as Incoming Invoices and Incoming Credit Notes.

Access via `Teamleader::receipts()`.

> **`list()` throws** `InvalidArgumentException`. Use the `Expenses` resource to list and filter expense documents.
>
> **`create()` posts to `receipts.add`** — not `.create`. `create()` is an alias for `add()`.
>
> **Only `total.tax_inclusive` is accepted** — there is no `tax_exclusive` option on receipts. Passing `tax_exclusive` on create throws `InvalidArgumentException`.
>
> **Payment statuses: `unknown`, `paid`, `not_paid` only** — there is no `partially_paid`.

## Endpoint

`receipts`

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

Throws if `$id` is empty. Returns full receipt detail including `review_status` and `payment_status`.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$receipt = Teamleader::receipts()->info('receipt-uuid');

$title         = $receipt['data']['title'];
$reviewStatus  = $receipt['data']['review_status'];  // pending | approved | refused
$paymentStatus = $receipt['data']['payment_status']; // unknown | paid | not_paid
```

---

### `add(array $data)` / `create(array $data)`

Creates a new receipt. Posts to `receipts.add`. `create()` is an alias.

**Required fields (validated before the request):**

| Field | Notes |
|---|---|
| `title` | Receipt title |
| `currency.code` | Valid currency code |
| `total.tax_inclusive` | Tax-inclusive total — **only accepted form** |

```php
$receipt = Teamleader::receipts()->add([
    'title'           => 'Client lunch — Ghent',
    'supplier_id'     => 'restaurant-uuid',    // optional
    'document_number' => 'REC-2025-041',        // optional
    'receipt_date'    => '2025-04-08',           // optional
    'currency'        => ['code' => 'EUR'],
    'total'           => [
        'tax_inclusive' => ['amount' => 67.50],
    ],
    'company_entity_id' => 'entity-uuid',        // optional — defaults to main entity
    'file_id'           => 'file-uuid',          // optional
]);

$id = $receipt['data']['id'];
```

---

### `update(string $id, array $data)`

Merges `['id' => $id]` with `$data` before posting. Validates currency if provided.

```php
Teamleader::receipts()->update('receipt-uuid', [
    'title' => 'Client dinner — Ghent',
    'total' => ['tax_inclusive' => ['amount' => 72.00]],
]);
```

---

### `delete(string $id)`

```php
Teamleader::receipts()->delete('receipt-uuid');
```

---

### `approve(string $id)`

```php
Teamleader::receipts()->approve('receipt-uuid');
```

### `refuse(string $id)`

```php
Teamleader::receipts()->refuse('receipt-uuid');
```

### `markAsPendingReview(string $id)`

Resets a refused receipt back to pending for re-review.

```php
Teamleader::receipts()->markAsPendingReview('receipt-uuid');
```

### `sendToBookkeeping(string $id)`

```php
Teamleader::receipts()->sendToBookkeeping('receipt-uuid');
```

---

## Payment Methods

### `listPayments(string $id)`

Returns all payments with a `meta.total.amount` summary.

```php
$payments = Teamleader::receipts()->listPayments('receipt-uuid');

$totalPaid = $payments['meta']['total']['amount'];

foreach ($payments['data'] as $payment) {
    echo $payment['payment']['amount'] . ' ' . $payment['payment']['currency'];
    echo ' — remark: ' . ($payment['remark'] ?? 'none');
}
```

### `registerPayment(string $id, array $payment, string $paidAt, ?string $paymentMethodId = null, ?string $remark = null)`

`$paidAt` is required. `$payment` must have `amount` (numeric) and `currency` (valid code) — both validated before the request.

```php
Teamleader::receipts()->registerPayment(
    'receipt-uuid',
    ['amount' => 67.50, 'currency' => 'EUR'],
    '2025-04-08T12:30:00+02:00',
    'payment-method-uuid',    // optional
    'Paid with company card'  // optional
);
```

### `removePayment(string $id, string $paymentId)`

```php
Teamleader::receipts()->removePayment('receipt-uuid', 'payment-uuid');
```

### `updatePayment(string $id, string $paymentId, array $payment, ?string $paidAt = null, ?string $paymentMethodId = null, ?string $remark = null)`

`$paidAt` is optional on update.

```php
Teamleader::receipts()->updatePayment(
    'receipt-uuid',
    'payment-uuid',
    ['amount' => 72.00, 'currency' => 'EUR'],
    null,
    null,
    'Corrected after checking receipt'
);
```

---

## Valid Values

**Payment statuses** (`payment_status` on `info()` response):
`unknown`, `paid`, `not_paid`

> **No `partially_paid`** — that status exists only on Incoming Invoices.

**Review statuses** (`review_status` on `info()` response):
`pending`, `approved`, `refused`

---

## Usage Examples

### Full receipt workflow

```php
// Submit a receipt
$receipt = Teamleader::receipts()->add([
    'title'    => 'Parking at client site',
    'currency' => ['code' => 'EUR'],
    'total'    => ['tax_inclusive' => ['amount' => 12.0]],
]);

$id = $receipt['data']['id'];

// Approve (under-limit auto-approve flow)
Teamleader::receipts()->approve($id);

// Register payment
Teamleader::receipts()->registerPayment(
    $id,
    ['amount' => 12.0, 'currency' => 'EUR'],
    now()->toIso8601String(),
    null,
    'Paid cash, reimbursed'
);

// Send to bookkeeping
Teamleader::receipts()->sendToBookkeeping($id);
```

### Correct an entry and reprocess

```php
// Refuse the incorrect receipt
Teamleader::receipts()->refuse('receipt-uuid');

// Update with the correct amount
Teamleader::receipts()->update('receipt-uuid', [
    'total' => ['tax_inclusive' => ['amount' => 15.50]],
]);

// Reset and re-approve
Teamleader::receipts()->markAsPendingReview('receipt-uuid');
Teamleader::receipts()->approve('receipt-uuid');
Teamleader::receipts()->sendToBookkeeping('receipt-uuid');
```

---

## Error Handling

```php
use InvalidArgumentException;

// list() not supported
try {
    Teamleader::receipts()->list();
} catch (InvalidArgumentException $e) {
    // 'The list method is not supported for receipts. Use info() to get a specific receipt.'
}

// Missing tax_inclusive on create
try {
    Teamleader::receipts()->add([
        'title'    => 'Test',
        'currency' => ['code' => 'EUR'],
        'total'    => ['tax_exclusive' => ['amount' => 50.0]], // wrong
    ]);
} catch (InvalidArgumentException $e) {
    // 'total.tax_inclusive is required for receipts'
}

// Missing paid_at on registerPayment
try {
    Teamleader::receipts()->registerPayment('uuid', ['amount' => 50, 'currency' => 'EUR'], '');
} catch (InvalidArgumentException $e) {
    // 'paid_at is required when registering a payment'
}
```

---

## Related Resources

- [[Bookkeeping-Submissions]] — Track submission history per receipt
- [[Incoming-Invoices]] — Larger purchase invoices (same workflow, adds `partially_paid`)
- [[Incoming-Credit-Notes]] — Supplier credit notes (same workflow)
- [[Payment-Methods]] — Optional on `registerPayment()`

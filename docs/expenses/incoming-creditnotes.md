# Incoming Credit Notes

Manage incoming (purchase) credit notes from suppliers in Teamleader Focus.

## Overview

The Incoming Credit Notes resource manages credit notes received from suppliers — corrections or refunds on previously issued purchase invoices. They follow the same review workflow as Incoming Invoices: pending → approved/refused → bookkeeping.

Access via `Teamleader::incomingCreditNotes()`.

> **`list()` throws** `InvalidArgumentException`. Use the `Expenses` resource to list and filter expense documents.
>
> **`create()` posts to `incomingCreditNotes.add`** — not `.create`. `create()` is an alias for `add()`.
>
> **Payment statuses: `unknown`, `paid`, `not_paid` only** — there is no `partially_paid` here. That status exists only on Incoming Invoices.

## Endpoint

`incomingCreditNotes`

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

Throws if `$id` is empty. Returns full credit note detail including `payment_status` and `iban_number`.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$creditNote = Teamleader::incomingCreditNotes()->info('credit-note-uuid');

$paymentStatus = $creditNote['data']['payment_status']; // unknown | paid | not_paid
$ibanNumber    = $creditNote['data']['iban_number'];     // nullable
$reviewStatus  = $creditNote['data']['review_status'];   // pending | approved | refused
```

---

### `add(array $data)` / `create(array $data)`

Creates a new incoming credit note. Posts to `incomingCreditNotes.add`. `create()` is an alias.

**Required fields (validated before the request):**

| Field | Notes |
|---|---|
| `title` | Credit note title |
| `currency.code` | Valid currency code |
| `total` | Must include at least `tax_exclusive` or `tax_inclusive` |

```php
$creditNote = Teamleader::incomingCreditNotes()->add([
    'title'           => 'Credit for returned goods',
    'supplier_id'     => 'company-uuid',     // optional
    'document_number' => 'CN-2025-007',       // optional
    'invoice_date'    => '2025-04-05',         // optional
    'currency'        => ['code' => 'EUR'],
    'total'           => [
        'tax_exclusive' => ['amount' => 200.0],
        'tax_inclusive' => ['amount' => 242.0],
    ],
    'file_id' => 'file-uuid',                  // optional
]);

$id = $creditNote['data']['id'];
```

---

### `update(string $id, array $data)`

Merges `['id' => $id]` with `$data` before posting. Validates currency if provided.

```php
Teamleader::incomingCreditNotes()->update('credit-note-uuid', [
    'title' => 'Updated credit note title',
]);
```

---

### `delete(string $id)`

```php
Teamleader::incomingCreditNotes()->delete('credit-note-uuid');
```

---

### `approve(string $id)`

```php
Teamleader::incomingCreditNotes()->approve('credit-note-uuid');
```

### `refuse(string $id)`

```php
Teamleader::incomingCreditNotes()->refuse('credit-note-uuid');
```

### `markAsPendingReview(string $id)`

Resets a refused credit note back to pending for re-review.

```php
Teamleader::incomingCreditNotes()->markAsPendingReview('credit-note-uuid');
```

### `sendToBookkeeping(string $id)`

```php
Teamleader::incomingCreditNotes()->sendToBookkeeping('credit-note-uuid');
```

---

## Payment Methods

### `listPayments(string $id)`

Returns all payments with a `meta.total.amount` summary.

```php
$payments = Teamleader::incomingCreditNotes()->listPayments('credit-note-uuid');

$totalPaid = $payments['meta']['total']['amount'];

foreach ($payments['data'] as $payment) {
    echo $payment['payment']['amount'] . ' ' . $payment['payment']['currency'];
    echo ' — ' . $payment['paid_at'];
}
```

### `registerPayment(string $id, array $payment, string $paidAt, ?string $paymentMethodId = null, ?string $remark = null)`

`$paidAt` is required. `$payment` must have `amount` (numeric) and `currency` (valid code) — both validated before the request.

```php
Teamleader::incomingCreditNotes()->registerPayment(
    'credit-note-uuid',
    ['amount' => 242.0, 'currency' => 'EUR'],
    '2025-04-10T10:00:00+02:00',
    'payment-method-uuid',   // optional
    'Received as bank credit' // optional
);
```

### `removePayment(string $id, string $paymentId)`

```php
Teamleader::incomingCreditNotes()->removePayment('credit-note-uuid', 'payment-uuid');
```

### `updatePayment(string $id, string $paymentId, array $payment, ?string $paidAt = null, ?string $paymentMethodId = null, ?string $remark = null)`

`$paidAt` is optional on update.

```php
Teamleader::incomingCreditNotes()->updatePayment(
    'credit-note-uuid',
    'payment-uuid',
    ['amount' => 121.0, 'currency' => 'EUR'],
    null,
    null,
    'Corrected to half amount'
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

### Full incoming credit note workflow

```php
// Create
$cn = Teamleader::incomingCreditNotes()->add([
    'title'    => 'Returned goods credit',
    'currency' => ['code' => 'EUR'],
    'total'    => ['tax_exclusive' => ['amount' => 200.0], 'tax_inclusive' => ['amount' => 242.0]],
]);

$id = $cn['data']['id'];

// Review and approve
Teamleader::incomingCreditNotes()->approve($id);

// Register receipt of the credit
Teamleader::incomingCreditNotes()->registerPayment(
    $id,
    ['amount' => 242.0, 'currency' => 'EUR'],
    now()->toIso8601String()
);

// Send to bookkeeping
Teamleader::incomingCreditNotes()->sendToBookkeeping($id);
```

### Refuse and reset for correction

```php
Teamleader::incomingCreditNotes()->refuse('credit-note-uuid');

// After the supplier reissues:
Teamleader::incomingCreditNotes()->update('credit-note-uuid', [
    'total' => ['tax_exclusive' => ['amount' => 180.0]],
]);

Teamleader::incomingCreditNotes()->markAsPendingReview('credit-note-uuid');
Teamleader::incomingCreditNotes()->approve('credit-note-uuid');
```

---

## Error Handling

```php
use InvalidArgumentException;

// list() not supported
try {
    Teamleader::incomingCreditNotes()->list();
} catch (InvalidArgumentException $e) {
    // 'The list method is not supported for incoming credit notes. Use info() to get a specific credit note.'
}

// Missing required fields on create
try {
    Teamleader::incomingCreditNotes()->add(['title' => 'Test']);
} catch (InvalidArgumentException $e) {
    // 'currency.code is required'
}

// paid_at missing on registerPayment
try {
    Teamleader::incomingCreditNotes()->registerPayment('uuid', ['amount' => 100, 'currency' => 'EUR'], '');
} catch (InvalidArgumentException $e) {
    // 'paid_at is required when registering a payment'
}
```

---

## Related Resources

- [[Bookkeeping-Submissions]] — Track submission history per credit note
- [[Incoming-Invoices]] — Incoming invoices (same workflow, adds `partially_paid`)
- [[Receipts]] — Smaller expense receipts (same workflow)
- [[Payment-Methods]] — Optional on `registerPayment()`

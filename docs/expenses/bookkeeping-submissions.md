# Bookkeeping Submissions

Read bookkeeping submission history for expense documents in Teamleader Focus.

## Overview

The Bookkeeping Submissions resource tracks the history of submissions made when expense documents (incoming invoices, incoming credit notes, receipts) are sent to bookkeeping. Submissions are created automatically when `sendToBookkeeping()` is called — they cannot be created or modified through this resource.

Access via `Teamleader::bookkeepingSubmissions()`.

> **`subject` filter is required.** `list()` throws `InvalidArgumentException` without it.
>
> **`info()` throws** `InvalidArgumentException` — there is no single-item endpoint. Use the helper methods instead.

## Endpoint

`bookkeepingSubmissions`

## Capabilities

| Capability | Supported |
|---|---|
| Pagination | ❌ Not supported |
| Filtering | ✅ Required (`subject`) |
| Sorting | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation | ❌ Automatic (via `sendToBookkeeping()`) |
| Update | ❌ Not supported |
| Deletion | ❌ Not supported |

---

## Methods

### `list(array $filters = [], array $options = [])`

Requires a `subject` filter with both `id` and `type`. Throws `InvalidArgumentException` if either is missing or if `type` is not one of the valid values.

**Valid `subject.type` values:** `incoming_invoice`, `incoming_credit_note`, `receipt`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$submissions = Teamleader::bookkeepingSubmissions()->list([
    'subject' => [
        'id'   => 'document-uuid',
        'type' => 'incoming_invoice',
    ],
]);
```

---

## Helper Methods

Prefer these over calling `list()` directly.

### `forDocument(string $documentId, string $documentType)`

Validates `$documentType` before the request.

```php
$submissions = Teamleader::bookkeepingSubmissions()->forDocument('doc-uuid', 'incoming_invoice');
$submissions = Teamleader::bookkeepingSubmissions()->forDocument('doc-uuid', 'receipt');
```

### `forInvoice(string $invoiceId)`

```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');
```

### `forCreditNote(string $creditNoteId)`

```php
$submissions = Teamleader::bookkeepingSubmissions()->forCreditNote('credit-note-uuid');
```

### `forReceipt(string $receiptId)`

```php
$submissions = Teamleader::bookkeepingSubmissions()->forReceipt('receipt-uuid');
```

### `byStatus(string $documentId, string $documentType, string $status)`

Filters the full submission list to a specific status. Validates status before the request.

**Valid statuses:** `sending`, `confirmed`, `failed`

```php
$failed = Teamleader::bookkeepingSubmissions()->byStatus('doc-uuid', 'incoming_invoice', 'failed');
```

---

## Filters

| Filter | Type | Required | Description |
|---|---|---|---|
| `subject` | object | ✅ Yes | `{id: uuid, type: incoming_invoice\|incoming_credit_note\|receipt}` |

---

## Response Structure

```php
[
    'data' => [
        [
            'id'         => 'submission-uuid',
            'status'     => 'confirmed',  // sending | confirmed | failed
            'created_at' => '2025-04-01T10:00:00+02:00',
        ],
    ],
]
```

---

## Usage Examples

### Check whether the last submission succeeded

```php
$submissions = Teamleader::bookkeepingSubmissions()->forInvoice('invoice-uuid');

$latest = $submissions['data'][0] ?? null;

if ($latest && $latest['status'] === 'failed') {
    // Retry
    Teamleader::incomingInvoices()->sendToBookkeeping('invoice-uuid');
}
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing subject filter
try {
    Teamleader::bookkeepingSubmissions()->list();
} catch (InvalidArgumentException $e) {
    // 'The subject filter is required for bookkeeping submissions...'
}

// Invalid subject type
try {
    Teamleader::bookkeepingSubmissions()->forDocument('uuid', 'outgoing_invoice');
} catch (InvalidArgumentException $e) {
    // "Invalid document type 'outgoing_invoice'. Must be one of: incoming_invoice, incoming_credit_note, receipt"
}

// info() not supported
try {
    Teamleader::bookkeepingSubmissions()->info('uuid');
} catch (InvalidArgumentException $e) {
    // 'Bookkeeping submissions do not support individual info requests...'
}
```

---

## Related Resources

- [[Incoming-Invoices]] — `sendToBookkeeping()` creates a submission
- [[Incoming-Credit-Notes]] — `sendToBookkeeping()` creates a submission
- [[Receipts]] — `sendToBookkeeping()` creates a submission

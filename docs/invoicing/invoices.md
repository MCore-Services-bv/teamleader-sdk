# Invoices

Manage invoices in Teamleader Focus.

## Overview

The Invoices resource handles the full invoice lifecycle: draft → book → send → payment. Invoices can be credited fully
or partially, downloaded in multiple formats, and sent via email or the Peppol e-invoicing network.

Access via `Teamleader::invoices()`.

> **`create()` posts to `invoices.draft`** — not `invoices.create`. This is the internal API endpoint for creating a
> draft invoice.
>
> **Sideloading key:** pass includes via `options['includes']` (plural) in `list()`, or as the second argument
> to `info()`.

## Endpoint

`invoices`

## Capabilities

| Capability  | Supported                                        |
|-------------|--------------------------------------------------|
| Pagination  | ✅ Supported                                      |
| Filtering   | ✅ Supported                                      |
| Sorting     | ✅ Supported (`invoice_number`, `invoice_date`)   |
| Sideloading | ✅ Supported (`late_fees`)                        |
| Creation    | ✅ Supported (creates draft)                      |
| Update      | ✅ Supported (draft and booked)                   |
| Deletion    | ✅ Supported (draft only, or last booked invoice) |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$invoices = Teamleader::invoices()->list();

$invoices = Teamleader::invoices()->list([
    'status'               => ['outstanding'],
    'invoice_date_after'   => '2025-01-01',
    'invoice_date_before'  => '2025-03-31',
], [
    'sort'        => [['field' => 'invoice_date', 'order' => 'desc']],
    'page_size'   => 50,
    'page_number' => 1,
]);

// With sideloading in list
$invoices = Teamleader::invoices()->list([], ['includes' => 'late_fees']);
```

> **Status filter:** always pass as an array — `['draft']`, `['outstanding']`, `['matched']`. A string value is coerced
> to an array internally.

---

### `info(string $id, mixed $includes = null)`

```php
$invoice = Teamleader::invoices()->info('invoice-uuid');

// With late fees sideload
$invoice = Teamleader::invoices()->info('invoice-uuid', 'late_fees');
```

---

### `create(array $data)`

Creates a **draft** invoice. Posts to `invoices.draft`. Four fields are required and validated before the request:

| Required field  | Notes                                                                   |
|-----------------|-------------------------------------------------------------------------|
| `invoicee`      | Must include `customer.type` (`contact` or `company`) and `customer.id` |
| `department_id` | Department UUID                                                         |
| `payment_term`  | Object with `type` (`cash`, `end_of_month`, `after_invoice_date`)       |
| `grouped_lines` | Array of line item groups                                               |

```php
$invoice = Teamleader::invoices()->create([
    'department_id' => 'dept-uuid',
    'invoice_date'  => '2025-04-01',
    'delivery_date' => '2025-03-31',   // optional — service/delivery date on invoice
    'invoicee'      => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid'],
    ],
    'payment_term'  => ['type' => 'after_invoice_date', 'days' => 30],
    'grouped_lines' => [
        [
            'section'    => ['title' => 'Services'],
            'line_items' => [[
                'quantity'    => 5,
                'description' => 'Consulting',
                'unit_price'  => ['amount' => 150.0, 'tax' => 'excluding'],
                'tax_rate_id' => 'tax-rate-uuid',
            ]],
        ],
    ],
]);
```

---

### `update(mixed $id, array $data)`

Updates a draft invoice. The `id` is injected into the request body.

```php
Teamleader::invoices()->update('invoice-uuid', [
    'note'          => 'Updated note',
    'delivery_date' => '2025-03-28',
]);
```

---

### `updateBooked(string $id, array $data)`

Updates a booked invoice. Only available when the "editing booked invoices" option is enabled in Teamleader settings.
The `id` is injected into the request body.

```php
Teamleader::invoices()->updateBooked('invoice-uuid', ['note' => 'Correction note']);
```

---

### `book(string $id, string $on)`

Books a draft invoice on a specific date.

```php
Teamleader::invoices()->book('invoice-uuid', '2025-04-01');
```

---

### `copy(string $id)`

Creates a new draft by copying an existing invoice.

```php
$newDraft = Teamleader::invoices()->copy('invoice-uuid');
```

---

### `credit(string $id, string $creditNoteDate)`

Fully credits a booked invoice, creating a credit note.

```php
Teamleader::invoices()->credit('invoice-uuid', '2025-04-15');
```

---

### `creditPartially(string $id, string $creditNoteDate, array $groupedLines, ?array $discounts = null)`

Partially credits an invoice for specific line items. `$groupedLines` is validated before the request.

```php
Teamleader::invoices()->creditPartially('invoice-uuid', '2025-04-15', [
    [
        'section'    => ['title' => 'Returns'],
        'line_items' => [[
            'quantity'    => 2,
            'description' => 'Returned product',
            'unit_price'  => ['amount' => 50.0, 'tax' => 'excluding'],
            'tax_rate_id' => 'tax-rate-uuid',
        ]],
    ],
]);
```

---

### `registerPayment(string $id, array $payment, string $paidAt, ?string $paymentMethodId = null)`

Registers a payment. `$paidAt` is **required** (ISO 8601 datetime). `$payment` must have `amount` and a valid `currency`
code — both validated before the request.

```php
Teamleader::invoices()->registerPayment(
    'invoice-uuid',
    ['amount' => 500.0, 'currency' => 'EUR'],
    '2025-04-10T00:00:00+02:00',
    'payment-method-uuid'  // optional
);
```

---

### `removePayments(string $id)`

Removes all registered payments, marking the invoice as unpaid.

```php
Teamleader::invoices()->removePayments('invoice-uuid');
```

---

### `send(string $id, array $content, array $recipients, ?array $attachments = null)`

Sends an invoice by email. Validated before the request: `content.subject`, `content.body`, and at least
one `recipients.to` entry are all required.

```php
Teamleader::invoices()->send(
    'invoice-uuid',
    ['subject' => 'Your invoice', 'body' => 'Please find attached.'],
    [
        'to'  => [['email' => 'client@example.com', 'customer' => ['type' => 'company', 'id' => 'company-uuid']]],
        'cc'  => [],
        'bcc' => [],
    ]
);
```

---

### `download(string $id, string $format = 'pdf')`

Returns a temporary download URL. Throws `InvalidArgumentException` for invalid formats.

**Valid formats:** `pdf`, `ubl/e-fff`, `ubl/peppol_bis_3`

```php
$result = Teamleader::invoices()->download('invoice-uuid', 'pdf');
$url    = $result['data']['location'];
```

---

### `sendViaPeppol(string $id)`

Submits an invoice to the Peppol e-invoicing network. Poll `info()` afterwards to track `peppol_status`.

```php
Teamleader::invoices()->sendViaPeppol('invoice-uuid');

$invoice = Teamleader::invoices()->info('invoice-uuid');
$status  = $invoice['data']['peppol_status']; // 'sending', 'sent', 'application_accepted', ...
```

---

### `delete(string $id)`

Deletes a draft invoice or the last booked invoice.

```php
Teamleader::invoices()->delete('invoice-uuid');
```

---

## Helper Methods

| Method                                  | Filter applied                                            |
|-----------------------------------------|-----------------------------------------------------------|
| `draft()`                               | `status: ['draft']`                                       |
| `outstanding()`                         | `status: ['outstanding']`                                 |
| `matched()`                             | `status: ['matched']`                                     |
| `forCustomer(string $type, string $id)` | `customer` — validates type                               |
| `forProject(string $projectId)`         | `project_id`                                              |
| `forDepartment(string $departmentId)`   | `department_id`                                           |
| `search(string $term)`                  | `term` (invoice number, PO number, payment ref, invoicee) |
| `updatedSince(string $datetime)`        | `updated_since`                                           |

---

## Filters

| Filter                  | Type   | Description                                                     |
|-------------------------|--------|-----------------------------------------------------------------|
| `ids`                   | array  | Filter by invoice UUIDs                                         |
| `term`                  | string | Searches invoice number, PO number, payment reference, invoicee |
| `invoice_number`        | string | Exact invoice number                                            |
| `department_id`         | string | Department UUID                                                 |
| `deal_id`               | string | Deal UUID                                                       |
| `project_id`            | string | Project UUID                                                    |
| `subscription_id`       | string | Subscription UUID                                               |
| `status`                | array  | `draft`, `outstanding`, `matched`                               |
| `updated_since`         | string | ISO 8601 datetime                                               |
| `purchase_order_number` | string | PO number                                                       |
| `payment_reference`     | string | Payment reference                                               |
| `invoice_date_after`    | string | Date inclusive (YYYY-MM-DD)                                     |
| `invoice_date_before`   | string | Date inclusive (YYYY-MM-DD)                                     |
| `customer`              | object | `{type: contact\|company, id: uuid}`                            |

---

## Sideloading

| Include     | Description                                                                                       |
|-------------|---------------------------------------------------------------------------------------------------|
| `late_fees` | Late fee calculations: `totals.due_incasso_inclusive`, `totals.fixed_late_fee`, `totals.interest` |

---

## Peppol Status Values

`peppol_status` is `null` until `sendViaPeppol()` is called. After submission:

| Status                                                                                    | Meaning                            |
|-------------------------------------------------------------------------------------------|------------------------------------|
| `sending`                                                                                 | In progress                        |
| `sending_failed`                                                                          | Failed before reaching the network |
| `sent`                                                                                    | Reached the network                |
| `application_acknowledged` / `application_accepted` / `application_rejected`              | Application-level response         |
| `receiver_acknowledged` / `receiver_accepted` / `receiver_rejected`                       | Receiver-level response            |
| `receiver_is_processing` / `receiver_awaits_feedback` / `receiver_conditionally_accepted` | Intermediate states                |
| `receiver_paid`                                                                           | Receiver marked as paid            |

---

## Usage Examples

### Full invoice workflow

```php
// Create draft
$draft = Teamleader::invoices()->create([...]);
$id    = $draft['data']['id'];

// Review — update if needed
Teamleader::invoices()->update($id, ['note' => 'Final version']);

// Book and send
Teamleader::invoices()->book($id, date('Y-m-d'));
Teamleader::invoices()->send($id, ['subject' => 'Invoice', 'body' => 'Please see attached.'], [
    'to' => [['email' => 'client@example.com']],
]);

// Register payment when received
Teamleader::invoices()->registerPayment($id, ['amount' => 750.0, 'currency' => 'EUR'], now()->toIso8601String());
```

### Get all outstanding invoices for a company

```php
$invoices = Teamleader::invoices()->forCustomer('company', 'company-uuid', ['status' => ['outstanding']]);
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing required field on create
try {
    Teamleader::invoices()->create(['department_id' => 'uuid']);
} catch (InvalidArgumentException $e) {
    // 'invoicee is required'
}

// Invalid download format
try {
    Teamleader::invoices()->download('uuid', 'docx');
} catch (InvalidArgumentException $e) {
    // "Invalid format 'docx'. Must be one of: pdf, ubl/e-fff, ubl/peppol_bis_3"
}

// Invalid payment currency
try {
    Teamleader::invoices()->registerPayment('uuid', ['amount' => 100, 'currency' => 'XYZ'], '2025-04-01T00:00:00+02:00');
} catch (InvalidArgumentException $e) {
    // 'Invalid currency code. Must be one of: ...'
}
```

---

## Related Resources

- [[Credit-Notes]] — Created via `credit()` and `creditPartially()`
- [[Subscriptions]] — Subscriptions auto-generate invoices
- [[Payment-Methods]] — Used in `registerPayment()`
- [[Payment-Terms]] — Used in `create()` and `update()`
- [[Tax-Rates]] — Used on line items
- [[Withholding-Tax-Rates]] — Optional on invoices
- [[Companies]] — Invoice customers
- [[Contacts]] — Invoice customers
- [[Filtering]] — Filter and pagination reference

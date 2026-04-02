# Subscriptions

Manage recurring subscriptions in Teamleader Focus.

## Overview

Subscriptions automatically generate invoices on a billing cycle. Each billing run can produce a draft, book it, or book and send it via email, Peppol, or postal service. Subscriptions cannot be deleted — use `deactivate()` instead.

Access via `Teamleader::subscriptions()`.

> **No `delete()`:** Subscriptions use `deactivate()`. `update()` and `deactivate()` both return empty (HTTP 204).
>
> **`starts_on` and `billing_cycle`** can only be changed before the first invoice has been generated.

## Endpoint

`subscriptions`

## Capabilities

| Capability | Supported |
|---|---|
| Pagination | ✅ Supported |
| Filtering | ✅ Supported |
| Sorting | ✅ Supported (`title`, `created_at`, `status`) |
| Sideloading | ❌ Not supported |
| Creation | ✅ Supported |
| Update | ✅ Supported |
| Deletion | ❌ Use `deactivate()` |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$subscriptions = Teamleader::subscriptions()->list();

$subscriptions = Teamleader::subscriptions()->list(
    ['status' => ['active'], 'department_id' => 'dept-uuid'],
    ['sort' => [['field' => 'title', 'order' => 'asc']], 'page_size' => 50]
);
```

> **Status filter:** must be an array. Values are validated: `active`, `deactivated`.

---

### `info(string $id)`

```php
$sub = Teamleader::subscriptions()->info('subscription-uuid');
```

---

### `create(array $data)`

Seven fields are required and validated before the request:

| Required field | Notes |
|---|---|
| `invoicee` | Must include `customer.type` (`contact` or `company`) and `customer.id` |
| `starts_on` | `YYYY-MM-DD` |
| `billing_cycle` | `periodicity.unit` validated: `week`, `month`, `year` |
| `title` | Subscription title |
| `grouped_lines` | Array of line item groups |
| `payment_term` | `type` validated: `cash`, `end_of_month`, `after_invoice_date` |
| `invoice_generation` | `action` validated: `draft`, `book`, `book_and_send` |

When `invoice_generation.action` is `book_and_send`, `sending_methods` is required. Each method's `method` is validated: `email`, `peppol`, `postal_service`.

```php
$sub = Teamleader::subscriptions()->create([
    'invoicee'     => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
    'department_id'=> 'dept-uuid',
    'starts_on'    => '2025-05-01',
    'ends_on'      => '2026-04-30',  // optional
    'billing_cycle'=> [
        'periodicity'   => ['unit' => 'month', 'period' => 1],
        'days_in_advance' => 7,
    ],
    'title'        => 'Monthly Retainer',
    'grouped_lines'=> [[
        'section'    => ['title' => 'Services'],
        'line_items' => [[
            'quantity'    => 1,
            'description' => 'Monthly retainer fee',
            'unit_price'  => ['amount' => 2500.0, 'tax' => 'excluding'],
            'tax_rate_id' => 'tax-rate-uuid',
        ]],
    ]],
    'payment_term' => ['type' => 'after_invoice_date', 'days' => 30],
    'invoice_generation' => [
        'action'          => 'book_and_send',
        'sending_methods' => [['method' => 'email']],
    ],
]);
```

---

### `update(mixed $id, array $data)`

The `id` is injected into the request body. Returns empty (HTTP 204). All fields are optional.

```php
Teamleader::subscriptions()->update('subscription-uuid', [
    'title'    => 'Updated Retainer',
    'ends_on'  => '2025-12-31',
    'invoice_generation' => ['action' => 'book'],
]);
```

> **`starts_on` and `billing_cycle`** can only be updated if no invoices have been generated yet.

---

### `deactivate(string $id)`

Stops the subscription from generating future invoices. Returns empty (HTTP 204).

```php
Teamleader::subscriptions()->deactivate('subscription-uuid');
```

---

## Helper Methods

| Method | Filter applied |
|---|---|
| `active()` | `status: ['active']` |
| `deactivated()` | `status: ['deactivated']` |
| `forCustomer(string $type, string $id)` | `customer` — validates type |
| `forDepartment(string $departmentId)` | `department_id` |
| `forDeal(string $dealId)` | `deal_id` |
| `forInvoice(string $invoiceId)` | `invoice_id` — find subscription that generated an invoice |
| `byIds(array $ids)` | `ids` — throws if empty |

```php
$subs = Teamleader::subscriptions()->active();
$subs = Teamleader::subscriptions()->forCustomer('company', 'company-uuid');
$subs = Teamleader::subscriptions()->forInvoice('invoice-uuid'); // trace back to source subscription
```

---

## Filters

| Filter | Type | Description |
|---|---|---|
| `ids` | array | Filter by subscription UUIDs |
| `invoice_id` | string | Subscriptions that generated this invoice |
| `deal_id` | string | Subscriptions created from this deal |
| `department_id` | string | Department UUID |
| `customer` | object | `{type: contact\|company, id: uuid}` |
| `status` | array | `active`, `deactivated` — validated |

---

## Sorting

| Field | Description |
|---|---|
| `title` | Subscription title |
| `created_at` | Creation datetime |
| `status` | Status value |

---

## Sending Methods

When `invoice_generation.action` is `book_and_send`, the `sending_methods` array controls delivery. Multiple methods can be combined.

**Valid values:** `email`, `peppol`, `postal_service`

```php
'invoice_generation' => [
    'action'          => 'book_and_send',
    'sending_methods' => [
        ['method' => 'email'],
        ['method' => 'peppol'],
    ],
],
```

---

## Usage Examples

### Create and later deactivate

```php
$sub = Teamleader::subscriptions()->create([...]);
$id  = $sub['data']['id'];

// Update title
Teamleader::subscriptions()->update($id, ['title' => 'Revised Retainer']);

// Stop generating invoices
Teamleader::subscriptions()->deactivate($id);
```

### Find which subscription generated an invoice

```php
$subs = Teamleader::subscriptions()->forInvoice('invoice-uuid');
$sourceSubscription = $subs['data'][0] ?? null;
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing required field
try {
    Teamleader::subscriptions()->create(['title' => 'Test']);
} catch (InvalidArgumentException $e) {
    // "Field 'invoicee' is required for subscription creation"
}

// Invalid billing cycle unit
try {
    Teamleader::subscriptions()->create([
        ...,
        'billing_cycle' => ['periodicity' => ['unit' => 'quarter', 'period' => 1]],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid billing cycle unit. Must be one of: week, month, year'
}

// Invalid sending method
try {
    Teamleader::subscriptions()->create([
        ...,
        'invoice_generation' => [
            'action'          => 'book_and_send',
            'sending_methods' => [['method' => 'fax']],
        ],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid sending method. Must be one of: email, peppol, postal_service'
}
```

---

## Related Resources

- [[Invoices]] — Subscriptions auto-generate invoices
- [[Payment-Methods]] — Used on subscription payments
- [[Payment-Terms]] — Used on subscription invoicee configuration
- [[Tax-Rates]] — Used on subscription line items
- [[Companies]] — Subscription customers
- [[Contacts]] — Subscription customers
- [[Filtering]] — Filter and pagination reference

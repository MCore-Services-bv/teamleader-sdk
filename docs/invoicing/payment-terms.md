# Payment Terms

Read payment term definitions in Teamleader Focus.

## Overview

Payment terms define when invoices are due. They are used when creating invoices and subscriptions. The response
includes a `meta.default` key identifying the account's default term.

Access via `Teamleader::payment_terms()`.

> **SDK key uses underscores:** `payment_terms` — not camelCase.
>
> **No filters, pagination, or sorting.** `list()` posts with an empty body and returns all terms in a single response.
> All helper methods are **client-side** — they call `list()` then filter in PHP.

## Endpoint

`paymentTerms`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ❌ Not supported |
| Filtering   | ❌ Not supported |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ❌ Not supported |
| Update      | ❌ Not supported |
| Deletion    | ❌ Not supported |

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns all payment terms in a single response. Filters and options are accepted for signature compatibility but
ignored.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$terms = Teamleader::payment_terms()->list();

// Access the default term UUID
$defaultId = $terms['meta']['default'];
```

---

## Helper Methods

All helpers call `list()` then filter in PHP.

### `getDefault()`

Returns the default payment term object, or `null` if none is set.

```php
$default = Teamleader::payment_terms()->getDefault();
```

### `getDefaultId()`

Returns the UUID of the default payment term, or `null`.

```php
$defaultId = Teamleader::payment_terms()->getDefaultId();
```

### `findByType(string $type)`

Returns all terms matching the given type. Throws `InvalidArgumentException` for invalid types.

**Valid types:** `cash`, `end_of_month`, `after_invoice_date`

```php
$cashTerms     = Teamleader::payment_terms()->findByType('cash');
$standardTerms = Teamleader::payment_terms()->findByType('after_invoice_date');
```

### `findByDays(int $days, ?string $type = null)`

Returns the first term matching the given number of days, optionally filtered by type. Returns `null` if not found.

```php
$term30 = Teamleader::payment_terms()->findByDays(30);
$term30 = Teamleader::payment_terms()->findByDays(30, 'after_invoice_date');
```

### Type shortcuts

```php
$cashTerms        = Teamleader::payment_terms()->cash();
$endOfMonthTerms  = Teamleader::payment_terms()->endOfMonth();
$afterInvoiceTerms= Teamleader::payment_terms()->afterInvoiceDate();
```

### `asOptions()`

Returns flat `[id => formatted_description]` map. Uses `formatPaymentTermDescription()` internally.

```php
$options = Teamleader::payment_terms()->asOptions();
// ['uuid-1' => 'Cash (immediate payment)', 'uuid-2' => '30 days after invoice date', ...]
```

### `exists(string $id)` / `find(string $id)` / `isValidType(string $type)`

```php
$exists = Teamleader::payment_terms()->exists('term-uuid');
$term   = Teamleader::payment_terms()->find('term-uuid'); // returns array or null
$valid  = Teamleader::payment_terms()->isValidType('cash');
```

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'uuid', 'type' => 'cash',              'days' => 0,  'description' => 'Cash'],
        ['id' => 'uuid', 'type' => 'after_invoice_date', 'days' => 30, 'description' => '30 days'],
        ['id' => 'uuid', 'type' => 'end_of_month',       'days' => 0,  'description' => 'End of month'],
    ],
    'meta' => ['default' => 'uuid-of-default-term'],
]
```

---

## Usage Examples

```php
// Get the default term and use it on an invoice
$defaultTermId = Teamleader::payment_terms()->getDefaultId();

Teamleader::invoices()->create([
    ...,
    'payment_term' => ['type' => 'after_invoice_date', 'days' => 30],
]);

// Cache terms (they rarely change)
$terms = Cache::remember('tl_payment_terms', 86400, fn() => Teamleader::payment_terms()->list());
```

---

## Related Resources

- [[Invoices]] — `payment_term` is required on invoice creation
- [[Subscriptions]] — `payment_term` is required on subscription creation
- [[Payment-Methods]] — How invoices are paid, not when

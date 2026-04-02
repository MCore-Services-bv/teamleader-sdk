# Payment Methods

Read payment method definitions in Teamleader Focus.

## Overview

The Payment Methods resource provides read-only access to the payment methods configured in your account (bank transfer,
direct debit, credit card, etc.). Methods can be active or archived.

Access via `Teamleader::payment_methods()`.

> **SDK key uses underscores:** `payment_methods` — not camelCase.

## Endpoint

`paymentMethods`

## Capabilities

| Capability  | Supported                     |
|-------------|-------------------------------|
| Pagination  | ✅ Supported                   |
| Filtering   | ✅ Supported (`ids`, `status`) |
| Sorting     | ❌ Not supported               |
| Sideloading | ❌ Not supported               |
| Creation    | ❌ Not supported               |
| Update      | ❌ Not supported               |
| Deletion    | ❌ Not supported               |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$methods = Teamleader::payment_methods()->list();

$methods = Teamleader::payment_methods()->list(
    ['status' => ['active']],
    ['page_size' => 50, 'page_number' => 1]
);
```

> **Status filter:** must be an array. Values are validated: `active`, `archived`.

---

### `info(string $id)`

```php
$method = Teamleader::payment_methods()->info('method-uuid');
```

---

## Helper Methods

### Status shortcuts

```php
$active   = Teamleader::payment_methods()->active();
$archived = Teamleader::payment_methods()->archived();
```

### `byIds(array $ids)`

Throws `InvalidArgumentException` if array is empty.

```php
$methods = Teamleader::payment_methods()->byIds(['uuid-1', 'uuid-2']);
```

### `findByName(string $name, bool $activeOnly = true)`

**Client-side** — calls `list()` then searches in PHP (case-insensitive). Searches active methods only by default.

```php
$method = Teamleader::payment_methods()->findByName('Bank Transfer');
$method = Teamleader::payment_methods()->findByName('Cash', false); // include archived
```

### `all(array $filters = [], int $maxPages = 10)`

Paginates up to 10 pages (100 per page). Not guaranteed exhaustive for very large lists.

```php
$all = Teamleader::payment_methods()->all();
$all = Teamleader::payment_methods()->all(['status' => ['active']]);
```

### `asOptions(bool $activeOnly = true)`

Returns flat `[id => name]` map. Calls `all()` internally.

```php
$options = Teamleader::payment_methods()->asOptions();
// ['uuid-1' => 'Bank Transfer', 'uuid-2' => 'Direct Debit', ...]

$allOptions = Teamleader::payment_methods()->asOptions(false); // include archived
```

### `exists(string $id)`

Returns `true` if a method with that UUID exists.

```php
$exists = Teamleader::payment_methods()->exists('method-uuid');
```

---

## Filters

| Filter   | Type  | Description                        |
|----------|-------|------------------------------------|
| `ids`    | array | Filter by payment method UUIDs     |
| `status` | array | `active` or `archived` — validated |

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'uuid', 'name' => 'Bank Transfer', 'status' => 'active'],
        ['id' => 'uuid', 'name' => 'Direct Debit',  'status' => 'active'],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 4],
]
```

---

## Usage Examples

```php
// Get method for a register payment call
$method = Teamleader::payment_methods()->findByName('Bank Transfer');
Teamleader::invoices()->registerPayment('invoice-uuid', ['amount' => 500.0, 'currency' => 'EUR'], now()->toIso8601String(), $method['id']);

// Cache methods
$methods = Cache::remember('tl_payment_methods', 3600, fn() => Teamleader::payment_methods()->all());
```

---

## Related Resources

- [[Invoices]] — Payment methods used in `registerPayment()`
- [[Payment-Terms]] — Invoice due-date configuration
- [[Subscriptions]] — Subscription payment configuration

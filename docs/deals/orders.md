# Orders

Retrieve orders in Teamleader Focus.

## Overview

The Orders resource provides read-only access to orders in your account. Orders are typically created when a quotation
is accepted or entered manually in Teamleader. No creation, update, or deletion is available through this endpoint.

The `list()` endpoint returns a summary per order. The `info()` endpoint returns full detail including `grouped_lines`.

## Endpoint

`orders`

## Capabilities

| Capability  | Supported                              |
|-------------|----------------------------------------|
| Pagination  | ❌ Not supported — all results returned |
| Filtering   | ✅ Supported (`ids` only)               |
| Sorting     | ❌ Not supported                        |
| Sideloading | ✅ Supported (`custom_fields`)          |
| Creation    | ❌ Not supported                        |
| Update      | ❌ Not supported                        |
| Deletion    | ❌ Not supported                        |

> **Sideloading key:** `list()` passes includes via the `options['includes']` key (plural), not `options['include']`.
> The fluent `->with()` / `->withCustomFields()` methods work for `info()`.

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All orders
$orders = Teamleader::orders()->list();

// Specific orders by ID
$orders = Teamleader::orders()->list([
    'ids' => ['order-uuid-1', 'order-uuid-2'],
]);

// With custom fields — note 'includes' (plural)
$orders = Teamleader::orders()->list([], [
    'includes' => 'custom_fields',
]);
```

---

### `info(string $id, mixed $includes = null)`

Returns the full order including `grouped_lines`. Fluent methods and the `$includes` parameter both work here.

```php
$order = Teamleader::orders()->info('order-uuid');

$order = Teamleader::orders()->info('order-uuid', 'custom_fields');

$order = Teamleader::orders()->withCustomFields()->info('order-uuid');
```

---

## Helper Methods

### `byIds(array $ids)`

```php
$orders = Teamleader::orders()->byIds(['order-uuid-1', 'order-uuid-2']);
```

### `getPaymentTermTypes()`

Returns the local list of valid payment term types — **no API call**.

```php
$types = Teamleader::orders()->getPaymentTermTypes();
// ['cash', 'end_of_month', 'after_invoice_date']
```

### `getSupplierTypes()`

Returns the local list of valid supplier types — **no API call**.

```php
$types = Teamleader::orders()->getSupplierTypes();
// ['company', 'contact']
```

---

## Filters

| Filter | Type  | Description           |
|--------|-------|-----------------------|
| `ids`  | array | Filter by order UUIDs |

---

## Sideloading

| Include         | Description                       |
|-----------------|-----------------------------------|
| `custom_fields` | Custom field values for the order |

On `list()`, pass as `options['includes']`. On `info()`, pass via the `$includes` argument or the fluent `->with()`
method.

---

## Response Structure

### `list()` — summary per order

```php
[
    'data' => [
        [
            'id'            => 'order-uuid',
            'name'          => 'Project Services Q1',
            'order_date'    => '2025-01-15',      // nullable
            'order_number'  => 32,                 // nullable
            'delivery_date' => '2025-01-22',       // nullable
            'payment_term'  => [
                'type' => 'after_invoice_date',    // cash | end_of_month | after_invoice_date
                'days' => 30,
            ],
            'total' => [
                'tax_exclusive'              => ['amount' => 1000.0, 'currency' => 'EUR'],
                'tax_inclusive'              => ['amount' => 1210.0, 'currency' => 'EUR'],
                'purchase_price_tax_exclusive' => ['amount' => 750.0, 'currency' => 'EUR'],
            ],
            'supplier'   => ['type' => 'company', 'id' => 'company-uuid'],
            'department' => ['type' => 'department', 'id' => 'department-uuid'],
            'deal'       => ['type' => 'deal', 'id' => 'deal-uuid'],
            'assignee'   => ['type' => 'user', 'id' => 'user-uuid'],
            'web_url'    => 'https://focus.teamleader.eu/order_detail.php?id=order-uuid',
        ],
    ],
]
```

### `info()` — full detail

Includes everything from `list()` plus a `grouped_lines` array with the same structure as quotation line items, and
per-line `project`, `group`, and `purchase_price` fields.

---

## Usage Examples

### Fetch all orders for a deal

```php
// Orders don't have a deal_id filter — fetch all and filter locally
$allOrders = Teamleader::orders()->list();

$dealOrders = array_filter($allOrders['data'], function ($order) use ($dealId) {
    return ($order['deal']['id'] ?? null) === $dealId;
});
```

### Read custom fields

```php
$order = Teamleader::orders()->withCustomFields()->info('order-uuid');

foreach ($order['data']['custom_fields'] ?? [] as $field) {
    echo $field['definition']['id'] . ': ' . $field['value'] . "\n";
}
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $order = Teamleader::orders()->info('order-uuid');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
}
```

---

## Related Resources

- [[Quotations]] — Orders are created when quotations are accepted
- [[Deals]] — Orders are linked to deals
- [[Filtering]] — General filter reference

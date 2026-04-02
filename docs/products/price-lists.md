# Price Lists

Read price list definitions in Teamleader Focus.

## Overview

Price lists define customer-specific or segment-specific pricing that can override the default product selling price.
They are read-only through the API.

Access via `Teamleader::priceLists()`.

> **No pagination.** `list()` returns all matching price lists in a single response.
>
> **Only filter is `ids`** — no department or status filters.

## Endpoint

`priceLists`

## Capabilities

| Capability  | Supported                |
|-------------|--------------------------|
| Pagination  | ❌ Not supported          |
| Filtering   | ✅ Supported (`ids` only) |
| Sorting     | ❌ Not supported          |
| Sideloading | ❌ Not supported          |
| Creation    | ❌ Not supported          |
| Update      | ❌ Not supported          |
| Deletion    | ❌ Not supported          |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All price lists
$priceLists = Teamleader::priceLists()->list();

// Filter by specific UUIDs
$priceLists = Teamleader::priceLists()->list([
    'ids' => ['price-list-uuid-1', 'price-list-uuid-2'],
]);
```

---

## Helper Methods

### `byIds(array $ids)`

Throws `InvalidArgumentException` if the array is empty.

```php
$priceLists = Teamleader::priceLists()->byIds(['uuid-1', 'uuid-2']);
```

---

## Filters

| Filter | Type  | Description                |
|--------|-------|----------------------------|
| `ids`  | array | Filter by price list UUIDs |

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'uuid', 'name' => 'Wholesale'],
        ['id' => 'uuid', 'name' => 'Retail'],
        ['id' => 'uuid', 'name' => 'Partner'],
    ],
]
```

---

## Usage Examples

```php
// Build a [id => name] map for a form select
$lists    = Teamleader::priceLists()->list();
$options  = array_column($lists['data'], 'name', 'id');

// Cache price lists
$lists = Cache::remember('tl_price_lists', 86400, fn() => Teamleader::priceLists()->list());
```

---

## Error Handling

```php
use InvalidArgumentException;

// Empty ids array on byIds()
try {
    Teamleader::priceLists()->byIds([]);
} catch (InvalidArgumentException $e) {
    // 'At least one price list ID must be provided'
}
```

---

## Related Resources

- [[Products]] — Products may be priced via price lists
- [[Contacts]] — Price lists can be assigned to contacts
- [[Companies]] — Price lists can be assigned to companies

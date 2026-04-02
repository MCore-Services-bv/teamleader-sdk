# Product Categories

Read product category definitions in Teamleader Focus.

## Overview

Product categories organise products and carry ledger account numbers per department for accounting purposes. They are
read-only through the API and must be managed in the Teamleader Focus web interface.

Access via `Teamleader::productCategories()`.

> **No pagination.** `list()` returns all categories for the given filter in a single response.
>
> **Response includes `ledgers` array** — one entry per department, each with a `ledger_account_number` for bookkeeping.

## Endpoint

`productCategories`

## Capabilities

| Capability  | Supported                     |
|-------------|-------------------------------|
| Pagination  | ❌ Not supported               |
| Filtering   | ✅ Supported (`department_id`) |
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

// All categories
$categories = Teamleader::productCategories()->list();

// Filtered by department
$categories = Teamleader::productCategories()->list([
    'department_id' => 'dept-uuid',
]);
```

---

## Helper Methods

### `forDepartment(string $departmentId)`

```php
$categories = Teamleader::productCategories()->forDepartment('dept-uuid');
```

---

## Filters

| Filter          | Type   | Description     |
|-----------------|--------|-----------------|
| `department_id` | string | Department UUID |

---

## Response Structure

```php
[
    'data' => [
        [
            'id'      => 'category-uuid',
            'name'    => 'Asian Flowers',
            'ledgers' => [
                [
                    'department'           => ['type' => 'department', 'id' => 'dept-uuid'],
                    'ledger_account_number' => '70100',
                ],
            ],
        ],
    ],
]
```

---

## Usage Examples

```php
// Get category for product creation
$categories = Teamleader::productCategories()->forDepartment('dept-uuid');
$categoryId = $categories['data'][0]['id'];

Teamleader::products()->create([
    'name'                => 'Product A',
    'product_category_id' => $categoryId,
    // ...
]);

// Build a [id => name] map
$map = array_column($categories['data'], 'name', 'id');

// Cache categories
$categories = Cache::remember('tl_product_categories', 3600, fn() =>
    Teamleader::productCategories()->list()
);
```

---

## Related Resources

- [[Products]] — Products are assigned a `product_category_id`
- [[Tax-Rates]] — Also department-scoped, similar pattern
- [[Commercial-Discounts]] — Also department-scoped

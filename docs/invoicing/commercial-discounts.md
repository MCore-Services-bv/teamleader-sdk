# Commercial Discounts

Read commercial discount definitions in Teamleader Focus.

## Overview

Commercial discounts are named percentage or fixed discounts configured in Teamleader settings and applied at invoice or
quotation level. They are department-scoped and read-only through the API.

Access via `Teamleader::commercialDiscounts()`.

> **No ID on response objects.** Commercial discount entries contain only `name` and `department`. `asOptions()`
> therefore uses the discount name as both key and value in its map.
>
> **No pagination or sorting.** `list()` returns all discounts for a department in a single response.

## Endpoint

`commercialDiscounts`

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

// All discounts
$discounts = Teamleader::commercialDiscounts()->list();

// For a specific department
$discounts = Teamleader::commercialDiscounts()->list(['department_id' => 'dept-uuid']);
```

---

## Helper Methods

### `forDepartment(string $departmentId)`

```php
$discounts = Teamleader::commercialDiscounts()->forDepartment('dept-uuid');
```

### `findByName(string $name, ?string $departmentId = null, bool $exactMatch = true)`

**Client-side** via `list()`. Case-insensitive. Default is exact match.

```php
$discount = Teamleader::commercialDiscounts()->findByName('Early payment');
$discount = Teamleader::commercialDiscounts()->findByName('early', null, false); // partial
$discount = Teamleader::commercialDiscounts()->findByName('Trade', 'dept-uuid');
```

### `search(string $searchTerm, ?string $departmentId = null)`

**Client-side** partial name match. Returns an array of matching discounts.

```php
$matches = Teamleader::commercialDiscounts()->search('discount');
```

### `asOptions(?string $departmentId = null)`

Returns `[name => name]` — name is used as both key and value because there is no ID field on the response.

```php
$options = Teamleader::commercialDiscounts()->asOptions();
// ['Early payment' => 'Early payment', 'Trade' => 'Trade', ...]

$options = Teamleader::commercialDiscounts()->asOptions('dept-uuid');
```

### `names(?string $departmentId = null)`

Returns a plain array of discount names.

```php
$names = Teamleader::commercialDiscounts()->names();
// ['Early payment', 'Trade', 'Partner']
```

### `groupedByDepartment()`

Returns `[departmentId => ['department' => ..., 'discounts' => [...]]]`.

```php
$grouped = Teamleader::commercialDiscounts()->groupedByDepartment();
```

### `exists(string $name, ?string $departmentId = null)`

Returns `true` if a discount with that name exists. Calls `findByName()` internally.

```php
$exists = Teamleader::commercialDiscounts()->exists('Early payment');
```

---

## Filters

| Filter          | Type   | Description               |
|-----------------|--------|---------------------------|
| `department_id` | string | Filter by department UUID |

---

## Response Structure

> **Note:** There is no `id` field. Name is the only unique identifier.

```php
[
    'data' => [
        ['name' => 'Early payment', 'department' => ['type' => 'department', 'id' => 'dept-uuid']],
        ['name' => 'Trade',         'department' => ['type' => 'department', 'id' => 'dept-uuid']],
        ['name' => 'Partner',       'department' => ['type' => 'department', 'id' => 'dept-uuid']],
    ],
]
```

---

## Usage Examples

```php
// Verify a discount exists before applying it to an invoice
$exists = Teamleader::commercialDiscounts()->exists('Early payment', 'dept-uuid');

// Get names for a form select
$names = Teamleader::commercialDiscounts()->names('dept-uuid');

// Cache per department
$discounts = Cache::remember("tl_discounts_{$deptId}", 3600, fn() =>
    Teamleader::commercialDiscounts()->forDepartment($deptId)
);
```

---

## Related Resources

- [[Invoices]] — Discounts applied at invoice level
- [[Quotations]] — Discounts applied at quotation level
- [[Tax-Rates]] — Per-line tax rates (department-scoped)

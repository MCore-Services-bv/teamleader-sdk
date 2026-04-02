# Tax Rates

Read tax rate definitions in Teamleader Focus.

## Overview

Tax rates define the VAT or sales tax percentages applied to invoice line items. They are department-scoped — different
departments may have different available rates.

Access via `Teamleader::taxRates()`.

## Endpoint

`taxRates`

## Capabilities

| Capability  | Supported                                            |
|-------------|------------------------------------------------------|
| Pagination  | ✅ Supported                                          |
| Filtering   | ✅ Supported (`department_id`)                        |
| Sorting     | ✅ Supported (`department_id`, `rate`, `description`) |
| Sideloading | ❌ Not supported                                      |
| Creation    | ❌ Not supported                                      |
| Update      | ❌ Not supported                                      |
| Deletion    | ❌ Not supported                                      |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$rates = Teamleader::taxRates()->list();

$rates = Teamleader::taxRates()->list(
    ['department_id' => 'dept-uuid'],
    ['sort' => [['field' => 'rate', 'order' => 'asc']], 'page_size' => 50]
);
```

Sort fields are validated — `InvalidArgumentException` for any field outside `department_id`, `rate`, `description`.

---

### `info(string $id)`

```php
$rate = Teamleader::taxRates()->info('tax-rate-uuid');
```

---

## Helper Methods

### `forDepartment(string $departmentId)`

```php
$rates = Teamleader::taxRates()->forDepartment('dept-uuid');
```

### `findByRate(float $rate, ?string $departmentId = null)`

**Client-side** via `all()`. Float comparison uses a tolerance of `0.0001`.

```php
$rate21 = Teamleader::taxRates()->findByRate(0.21);                        // 21%
$rate21 = Teamleader::taxRates()->findByRate(0.21, 'dept-uuid');           // scoped to department
```

### `findByDescription(string $description, ?string $departmentId = null, bool $exactMatch = true)`

**Client-side** via `list()`. Default is exact match (case-insensitive).

```php
$rate = Teamleader::taxRates()->findByDescription('21%');
$rate = Teamleader::taxRates()->findByDescription('vat', null, false); // partial match
```

### `all(array $filters = [], int $maxPages = 10)`

Paginates up to 10 pages (100 per page). Not guaranteed exhaustive for very large sets.

```php
$all = Teamleader::taxRates()->all();
$all = Teamleader::taxRates()->all(['department_id' => 'dept-uuid']);
```

### `asOptions(?string $departmentId = null)`

Returns flat `[id => description]` map. Calls `all()` internally.

```php
$options = Teamleader::taxRates()->asOptions();
// ['uuid-1' => '21%', 'uuid-2' => '6%', 'uuid-3' => '0%']

$options = Teamleader::taxRates()->asOptions('dept-uuid');
```

### `sortedByRate()` / `sortedByDescription()`

Convenience sort wrappers.

```php
$rates = Teamleader::taxRates()->sortedByRate();
$rates = Teamleader::taxRates()->sortedByDescription(['department_id' => 'dept-uuid'], 'desc');
```

### `groupedByDepartment()`

Returns `[departmentId => ['department' => ..., 'tax_rates' => [...]]]`.

```php
$grouped = Teamleader::taxRates()->groupedByDepartment();
```

### `exists(string $id)`

```php
$exists = Teamleader::taxRates()->exists('tax-rate-uuid');
```

---

## Filters

| Filter          | Type   | Description               |
|-----------------|--------|---------------------------|
| `department_id` | string | Filter by department UUID |

---

## Sorting

| Field           | Description      |
|-----------------|------------------|
| `department_id` | Department UUID  |
| `rate`          | Tax rate value   |
| `description`   | Rate description |

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'uuid', 'description' => '21%', 'rate' => 0.21, 'department' => ['type' => 'department', 'id' => 'dept-uuid']],
        ['id' => 'uuid', 'description' => '6%',  'rate' => 0.06, 'department' => ['type' => 'department', 'id' => 'dept-uuid']],
        ['id' => 'uuid', 'description' => '0%',  'rate' => 0.0,  'department' => ['type' => 'department', 'id' => 'dept-uuid']],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 3],
]
```

---

## Usage Examples

```php
// Find the 21% rate for a department
$rate = Teamleader::taxRates()->findByRate(0.21, 'dept-uuid');

// Use on invoice line item
Teamleader::invoices()->create([..., 'grouped_lines' => [[
    'line_items' => [[..., 'tax_rate_id' => $rate['id']]],
]]]);

// Cache per department
$rates = Cache::remember("tl_tax_rates_{$deptId}", 3600, fn() =>
    Teamleader::taxRates()->asOptions($deptId)
);
```

---

## Related Resources

- [[Invoices]] — `tax_rate_id` used on line items
- [[Subscriptions]] — `tax_rate_id` used on line items
- [[Withholding-Tax-Rates]] — Separate tax withheld at source
- [[Commercial-Discounts]] — Department-scoped discounts

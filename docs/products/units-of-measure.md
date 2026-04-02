# Units of Measure

Read unit of measure definitions in Teamleader Focus.

## Overview

Units of measure define how products are quantified — pieces, hours, kilograms, metres, etc. They are read-only through
the API and must be configured in the Teamleader Focus web interface.

Access via `Teamleader::unitsOfMeasure()`.

> **No filters, pagination, or sorting.** `list()` posts with an empty body and returns all units in a single response.
> All helpers are **client-side**.

## Endpoint

`unitsOfMeasure`

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

Returns all units in a single response.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$units = Teamleader::unitsOfMeasure()->list();
```

---

## Helper Methods

All helpers are **client-side** — they call `list()` then search or transform in PHP.

### `findByName(string $name)`

Case-insensitive exact match. Returns the matching unit array or `null`.

```php
$unit = Teamleader::unitsOfMeasure()->findByName('piece');
$unit = Teamleader::unitsOfMeasure()->findByName('Hour'); // case-insensitive
```

### `findById(string $id)`

Returns the unit with that UUID or `null`.

```php
$unit = Teamleader::unitsOfMeasure()->findById('unit-uuid');
```

### `asOptions()`

Returns flat `[id => name]` map.

```php
$options = Teamleader::unitsOfMeasure()->asOptions();
// ['uuid-1' => 'piece', 'uuid-2' => 'kilogram', 'uuid-3' => 'hour']
```

### `asCollection()`

Returns a Laravel `Collection` for fluent manipulation.

```php
$metreLike = Teamleader::unitsOfMeasure()->asCollection()
    ->filter(fn($u) => str_contains(strtolower($u['name']), 'meter'));
```

### `exists(string $name)`

Returns `true` if a unit with that name exists. Calls `findByName()` internally.

```php
$exists = Teamleader::unitsOfMeasure()->exists('piece');
```

### `count()`

Returns the total number of configured units.

```php
$total = Teamleader::unitsOfMeasure()->count();
```

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'uuid', 'name' => 'piece'],
        ['id' => 'uuid', 'name' => 'kilogram'],
        ['id' => 'uuid', 'name' => 'hour'],
        ['id' => 'uuid', 'name' => 'meter'],
    ],
]
```

---

## Usage Examples

```php
// Look up the unit and use it on a product
$unit = Teamleader::unitsOfMeasure()->findByName('hour');

if (!$unit) {
    throw new \Exception("Unit 'hour' not found — configure it in Teamleader first.");
}

Teamleader::products()->create([
    'name'               => 'Consulting Hour',
    'unit_of_measure_id' => $unit['id'],
    // ...
]);

// Cache units (they rarely change)
$options = Cache::remember('tl_units_of_measure', 86400, fn() =>
    Teamleader::unitsOfMeasure()->asOptions()
);
```

---

## Related Resources

- [[Products]] — `unit_of_measure_id` is set on product creation
- [[Product-Categories]] — Companion reference resource for products
- [[Price-Lists]] — Companion reference resource for products

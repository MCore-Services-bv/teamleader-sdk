# Materials

Manage materials in Teamleader Focus projects (v2).

## Overview

Materials represent physical items, supplies, or other non-time-based cost elements within a project. They track
estimated and actual quantities, pricing, and billing status.

Access via `Teamleader::materials()`.

> **No `delete()` method** — `$supportsDeletion = false`. Materials cannot be deleted through the API.
>
> **`create()` returns HTTP 201** with `data.{id, type}`.
>
> **`update()` returns HTTP 204** (no body).
>
> **`quantity`** = actual quantity used. **`quantity_estimated`** = planned quantity.

## Endpoint

`projects-v2/materials`

## Capabilities

| Capability  | Supported                |
|-------------|--------------------------|
| Pagination  | ❌ Not supported          |
| Filtering   | ✅ Supported (`ids` only) |
| Sorting     | ❌ Not supported          |
| Sideloading | ❌ Not supported          |
| Creation    | ✅ Supported              |
| Update      | ✅ Supported              |
| Deletion    | ❌ Not supported          |

---

## Methods

### `list(array $filters = [], array $options = [])`

Only `ids` filter is supported.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$materials = Teamleader::materials()->list(['ids' => ['mat-uuid-1', 'mat-uuid-2']]);
```

---

### `info(string $id)`

```php
$material = Teamleader::materials()->info('material-uuid');
```

---

### `create(array $data)`

Two fields are required:

| Required     | Notes          |
|--------------|----------------|
| `project_id` | Project UUID   |
| `title`      | Material title |

**Billing methods (validated when provided):** `fixed_price`, `unit_price`, `non_billable`

**Status values (validated when provided):** `to_do`, `in_progress`, `on_hold`, `done`

```php
$material = Teamleader::materials()->create([
    'project_id'         => 'project-uuid',
    'title'              => 'Copper pipe',
    'description'        => '15mm copper pipe, per metre',
    'billing_method'     => 'unit_price',
    'quantity_estimated' => 25,
    'unit_price'         => ['amount' => 8.50, 'currency' => 'EUR'],
    'group_id'           => 'group-uuid',   // optional
    'product_id'         => 'product-uuid', // optional — link to product catalogue
    'assignees'          => [['type' => 'user', 'id' => 'user-uuid']],
    'start_date'         => '2025-05-01',
    'end_date'           => '2025-05-31',
]);

$id = $material['data']['id'];
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body. Returns HTTP 204. All fields are optional.

Passing `null` for nullable fields (e.g. `description`, `unit_price`) clears them.

```php
// Update actual quantity after work is done
Teamleader::materials()->update('material-uuid', [
    'quantity' => 22,
    'status'   => 'done',
]);

// Clear a nullable field
Teamleader::materials()->update('material-uuid', [
    'description' => null,
]);
```

---

## Helper Methods

### `byIds(array $ids)`

```php
$materials = Teamleader::materials()->byIds(['uuid-1', 'uuid-2']);
```

---

## Filters

| Filter | Type  | Description              |
|--------|-------|--------------------------|
| `ids`  | array | Filter by material UUIDs |

---

## Usage Examples

### Track estimated vs actual usage

```php
// Create with estimate
$material = Teamleader::materials()->create([
    'project_id'         => 'project-uuid',
    'title'              => 'Steel bolts (box of 50)',
    'billing_method'     => 'unit_price',
    'quantity_estimated' => 10,
    'unit_price'         => ['amount' => 12.0, 'currency' => 'EUR'],
]);

// Update with actual after work is done
Teamleader::materials()->update($material['data']['id'], [
    'quantity' => 8,
    'status'   => 'done',
]);
```

---

## Error Handling

```php
use InvalidArgumentException;

// Missing required field
try {
    Teamleader::materials()->create(['project_id' => 'uuid']); // missing title
} catch (InvalidArgumentException $e) {
    // 'title is required for creating a material'
}

// Invalid billing method
try {
    Teamleader::materials()->create([
        'project_id'     => 'uuid',
        'title'          => 'Test',
        'billing_method' => 'hourly',
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid billing_method. Must be one of: fixed_price, unit_price, non_billable'
}
```

---

## Related Resources

- [[Projects]] — Parent project
- [[Groups]] — Materials can be assigned to groups
- [[Project-Lines]] — Unified listing including materials
- [[Products]] — `product_id` can link a material to the product catalogue

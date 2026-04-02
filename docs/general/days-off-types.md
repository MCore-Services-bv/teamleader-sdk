# Day Off Types

Manage day off type definitions in Teamleader Focus.

## Overview

The Day Off Types resource lets you create, update, delete, and list the leave categories used in your account —
vacation, sick leave, parental leave, and so on. Each type has a name, an optional color, and an optional date validity
window.

`info()` is not overridden and falls through to the base class behaviour. `list()` takes no filters or pagination — all
types are returned in a single response.

## Endpoint

`dayOffTypes`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ❌ Not supported |
| Filtering   | ❌ Not supported |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ✅ Supported     |
| Update      | ✅ Supported     |
| Deletion    | ✅ Supported     |

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns all day off types. Filters and options are accepted for signature compatibility but ignored — the API returns
all types in one response.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$types = Teamleader::dayOffTypes()->list();
```

---

### `create(array $data)`

Creates a new day off type. `name` is the only required field. `color` and `date_validity` are optional but validated if
provided.

**Required:**

| Field  | Type   | Description                    |
|--------|--------|--------------------------------|
| `name` | string | Display name of the leave type |

**Optional:**

| Field                 | Type   | Description                                  |
|-----------------------|--------|----------------------------------------------|
| `color`               | string | Hex color code — must match `#RRGGBB` format |
| `date_validity`       | array  | Validity window — see below                  |
| `date_validity.from`  | string | Start date in `YYYY-MM-DD` format            |
| `date_validity.until` | string | End date in `YYYY-MM-DD` format              |

```php
// Name only
$type = Teamleader::dayOffTypes()->create([
    'name' => 'Sick Leave',
]);

// With color
$type = Teamleader::dayOffTypes()->create([
    'name'  => 'Vacation',
    'color' => '#00B2B2',
]);

// With validity window
$type = Teamleader::dayOffTypes()->create([
    'name'           => 'Summer Leave',
    'color'          => '#FFB600',
    'date_validity'  => [
        'from'  => '2025-06-01',
        'until' => '2025-08-31',
    ],
]);
```

---

### `update(mixed $id, array $data)`

Updates a day off type. The `id` is injected into the request body before posting. Any field can be updated — all are
optional.

```php
Teamleader::dayOffTypes()->update('type-uuid', [
    'name'  => 'Annual Leave',
    'color' => '#0055FF',
]);

// Update validity only
Teamleader::dayOffTypes()->update('type-uuid', [
    'date_validity' => [
        'from'  => '2025-07-01',
        'until' => '2025-09-30',
    ],
]);
```

---

### `delete(mixed $id)`

Deletes a day off type by UUID.

```php
Teamleader::dayOffTypes()->delete('type-uuid');
```

---

## Helper Methods

### `createWithValidity(string $name, ?string $color, ?string $fromDate, ?string $untilDate)`

Convenience wrapper for creating a type with a validity window in a single call.

```php
$type = Teamleader::dayOffTypes()->createWithValidity(
    'Summer Friday',
    '#FFA500',
    '2025-06-01',
    '2025-08-31'
);
```

### `updateValidity(string $id, string $fromDate, ?string $untilDate = null)`

Updates only the validity window of an existing type.

```php
Teamleader::dayOffTypes()->updateValidity('type-uuid', '2025-07-01', '2025-09-30');
```

### `bulkCreate(array $dayOffTypes)`

Creates multiple types in a loop. Failures are caught per-entry and returned as error objects — they do not throw.

```php
$results = Teamleader::dayOffTypes()->bulkCreate([
    ['name' => 'Vacation',   'color' => '#00B2B2'],
    ['name' => 'Sick Leave', 'color' => '#FF6B6B'],
    ['name' => 'Personal',   'color' => '#FFB600'],
]);

// Each entry: ['index' => 0, 'success' => true, 'data' => [...]]
// or:         ['index' => 1, 'success' => false, 'error' => '...', 'data' => [...]]
```

### `getCommonColors()`

Returns a curated map of hex codes to colour names — no API call. Useful for building UI colour pickers.

```php
$colors = Teamleader::dayOffTypes()->getCommonColors();
// ['#00B2B2' => 'Teal', '#FF6B6B' => 'Red', ...]
```

---

## Validation

`create()` and `update()` run `validateData()` before the request:

- `color` must match `/^#[0-9A-Fa-f]{6}$/` if provided
- `date_validity.from` must match `YYYY-MM-DD` if provided
- `date_validity.until` must match `YYYY-MM-DD` if provided
- Empty strings, nulls, and empty arrays are stripped before sending

An `InvalidArgumentException` is thrown for any violation.

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'             => 'type-uuid',
            'name'           => 'Vacation',
            'color'          => '#00B2B2',
            'date_validity'  => null, // or ['from' => '...', 'until' => '...']
        ],
    ],
]
```

### `create()` / `update()` response

```php
[
    'data' => [
        'type' => 'dayOffType',
        'id'   => 'type-uuid',
    ],
]
```

---

## Usage Examples

### Build a leave type select list

```php
$types = Teamleader::dayOffTypes()->list();

$options = array_column($types['data'], 'name', 'id');
// ['uuid-1' => 'Vacation', 'uuid-2' => 'Sick Leave', ...]
```

### Initialise standard leave types for a new account

```php
Teamleader::dayOffTypes()->bulkCreate([
    ['name' => 'Annual Leave',   'color' => '#00B2B2'],
    ['name' => 'Sick Leave',     'color' => '#FF6B6B'],
    ['name' => 'Personal Day',   'color' => '#FFB600'],
    ['name' => 'Parental Leave', 'color' => '#BB8FCE'],
    ['name' => 'Unpaid Leave',   'color' => '#808080'],
]);
```

### Cache leave types

```php
$types = Cache::remember('tl_day_off_types', 3600, function () {
    return Teamleader::dayOffTypes()->list();
});
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid color format — thrown before the request
try {
    Teamleader::dayOffTypes()->create([
        'name'  => 'Test',
        'color' => 'red', // must be #RRGGBB
    ]);
} catch (InvalidArgumentException $e) {
    // 'Color must be a valid hex color code (e.g., #00B2B2)'
    Log::error($e->getMessage());
}

// Missing name — thrown before the request
try {
    Teamleader::dayOffTypes()->create(['color' => '#00B2B2']);
} catch (InvalidArgumentException $e) {
    // 'Name is required for creating a day off type'
    Log::error($e->getMessage());
}

// API-level errors
try {
    Teamleader::dayOffTypes()->delete('type-uuid');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Days-Off]] — Applies these types when importing user leave
- [[Users]] — `listDaysOff()` returns leave records that reference these types
- [[Closing-Days]] — Company-wide closures (not per-user leave)

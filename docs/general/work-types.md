# Work Types

Read work type information in Teamleader Focus.

## Overview

The Work Types resource provides read-only access to the work type definitions in your Teamleader account. Work types
categorise time tracking entries and can carry hourly rates for billing purposes.

Work types cannot be created, updated, or deleted through the API. The `info()` method is not supported — use `list()`
with an `ids` filter or the `byIds()` helper to retrieve specific work types.

## Endpoint

`workTypes`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ✅ Supported     |
| Filtering   | ✅ Supported     |
| Sorting     | ✅ Supported     |
| Sideloading | ❌ Not supported |
| Creation    | ❌ Not supported |
| Update      | ❌ Not supported |
| Deletion    | ❌ Not supported |

> **Note on `info()`:** Calling `info()` throws an `InvalidArgumentException`. Use `byIds(['uuid'])` instead.

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns work types matching the given filters, with optional sorting and pagination.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All work types
$workTypes = Teamleader::workTypes()->list();

// Search by name
$workTypes = Teamleader::workTypes()->list([
    'term' => 'consulting',
]);

// With pagination
$workTypes = Teamleader::workTypes()->list([], [
    'page_size'   => 50,
    'page_number' => 1,
]);

// Sorted by name
$workTypes = Teamleader::workTypes()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']],
]);

// Combined
$workTypes = Teamleader::workTypes()->list(
    ['term' => 'development'],
    ['sort' => [['field' => 'name', 'order' => 'asc']], 'page_size' => 50]
);
```

---

## Helper Methods

### `search(string $term)`

Shorthand for `list(['term' => $term])`.

```php
$workTypes = Teamleader::workTypes()->search('design');
```

### `byIds(array $ids)`

Shorthand for `list(['ids' => $ids])`. Use this instead of `info()`.

```php
$workTypes = Teamleader::workTypes()->byIds(['uuid-1', 'uuid-2']);
```

### `sortedByName(string $order = 'asc', array $filters = [])`

Returns work types sorted by name. Accepts an optional filters array.

```php
// All work types A–Z
$workTypes = Teamleader::workTypes()->sortedByName();

// Z–A
$workTypes = Teamleader::workTypes()->sortedByName('desc');

// Filtered + sorted
$workTypes = Teamleader::workTypes()->sortedByName('asc', ['term' => 'consulting']);
```

### `paginate(int $pageSize = 20, int $pageNumber = 1, array $filters = [])`

Explicit pagination shorthand. Accepts an optional filters array.

```php
$workTypes = Teamleader::workTypes()->paginate(50, 1);

// With filters
$workTypes = Teamleader::workTypes()->paginate(50, 2, ['term' => 'design']);
```

---

## Filters

### `ids`

Filter by an array of work type UUIDs.

```php
$workTypes = Teamleader::workTypes()->list([
    'ids' => ['uuid-1', 'uuid-2'],
]);
```

### `term`

Search by work type name.

```php
$workTypes = Teamleader::workTypes()->list([
    'term' => 'development',
]);
```

---

## Sorting

The only supported sort field is `name`.

| Field  | Description                           |
|--------|---------------------------------------|
| `name` | Sort alphabetically by work type name |

```php
// Full array form
$workTypes = Teamleader::workTypes()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']],
]);

// String shorthand (also accepted)
$workTypes = Teamleader::workTypes()->list([], [
    'sort' => 'name',
]);
```

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'   => 'work-type-uuid',
            'name' => 'Consulting',
        ],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 8,
    ],
]
```

---

## Usage Examples

### Build a work type select list

```php
$workTypes = Teamleader::workTypes()->sortedByName();

$options = array_column($workTypes['data'], 'name', 'id');
// ['uuid-1' => 'Consulting', 'uuid-2' => 'Development', ...]
```

### Look up a single work type

```php
// info() is not supported — use byIds() instead
$result    = Teamleader::workTypes()->byIds(['work-type-uuid']);
$workType  = $result['data'][0] ?? null;
```

### Cache work types

Work types change infrequently. Cache them to avoid unnecessary API calls:

```php
$workTypes = Cache::remember('tl_work_types', 3600, function () {
    return Teamleader::workTypes()->sortedByName();
});
```

### Paginate through all work types

```php
$all  = [];
$page = 1;

do {
    $response = Teamleader::workTypes()->list([], [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// info() throws immediately — use byIds() instead
try {
    $workType = Teamleader::workTypes()->info('work-type-uuid'); // throws InvalidArgumentException
} catch (InvalidArgumentException $e) {
    $result = Teamleader::workTypes()->byIds(['work-type-uuid']);
}

// Standard error handling for list()
try {
    $workTypes = Teamleader::workTypes()->list(['term' => 'consulting']);
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Time-Tracking]] — Time entries are categorised by work type
- [[Users]] — Users can have an external rate per work type
- [[Filtering]] — Filter and pagination reference

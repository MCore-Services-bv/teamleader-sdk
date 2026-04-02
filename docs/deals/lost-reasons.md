# Lost Reasons

Read lost reason definitions for deals in Teamleader Focus.

## Overview

The Lost Reasons resource provides read-only access to the reasons that can be recorded when a deal is marked as lost.
Lost reasons are managed in the Teamleader interface and cannot be created or modified through the API.

Access via `Teamleader::lostReasons()`.

> **`info()` is simulated.** There is no dedicated info endpoint — `info($id)` calls `list(['ids' => [$id]])` internally
> and returns the first result.

## Endpoint

`lostReasons`

## Capabilities

| Capability  | Supported                 |
|-------------|---------------------------|
| Pagination  | ✅ Supported               |
| Filtering   | ✅ Supported (`ids` only)  |
| Sorting     | ✅ Supported (`name` only) |
| Sideloading | ❌ Not supported           |
| Creation    | ❌ Not supported           |
| Update      | ❌ Not supported           |
| Deletion    | ❌ Not supported           |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All lost reasons
$reasons = Teamleader::lostReasons()->list();

// Specific reasons by ID
$reasons = Teamleader::lostReasons()->list([
    'ids' => ['reason-uuid-1', 'reason-uuid-2'],
]);

// Sorted by name, with pagination
$reasons = Teamleader::lostReasons()->list([], [
    'sort'        => [['field' => 'name', 'order' => 'asc']],
    'page_size'   => 50,
    'page_number' => 1,
]);
```

---

### `info(string $id)`

Simulated via `list(['ids' => [$id]])`. Returns the first result wrapped as `['data' => [...]]`, or an empty data key if
not found.

```php
$reason = Teamleader::lostReasons()->info('reason-uuid');
```

---

## Helper Methods

### `all()`

Returns all lost reasons sorted alphabetically, handling pagination internally.

```php
$reasons = Teamleader::lostReasons()->all();
```

### `byIds(array $ids)`

```php
$reasons = Teamleader::lostReasons()->byIds(['reason-uuid-1', 'reason-uuid-2']);
```

### `exists(string $id)`

Returns `true` if a lost reason with that UUID exists. Uses `list(['ids' => [$id]])` internally.

```php
$exists = Teamleader::lostReasons()->exists('reason-uuid');
```

### `getName(string $id)`

Returns the name of a lost reason, or `null` if not found. Uses `list(['ids' => [$id]])` internally.

```php
$name = Teamleader::lostReasons()->getName('reason-uuid');
// 'Price too high' or null
```

### `getSelectOptions()`

Returns a flat `[id => name]` map for form dropdowns.

```php
$options = Teamleader::lostReasons()->getSelectOptions();
// ['uuid-1' => 'Price too high', 'uuid-2' => 'Went with competitor', ...]
```

---

## Filters

| Filter | Type  | Description            |
|--------|-------|------------------------|
| `ids`  | array | Filter by reason UUIDs |

---

## Sorting

Only `name` is a supported sort field.

```php
$reasons = Teamleader::lostReasons()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']],
]);
```

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'reason-uuid', 'name' => 'Price too high'],
        ['id' => 'reason-uuid', 'name' => 'Went with competitor'],
        ['id' => 'reason-uuid', 'name' => 'No budget'],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 8],
]
```

---

## Usage Examples

### Build a lost-reason dropdown for a form

```php
$options = Teamleader::lostReasons()->getSelectOptions();
// ['uuid' => 'Price too high', 'uuid' => 'Went with competitor', ...]
```

### Mark a deal as lost with a reason

```php
$options = Teamleader::lostReasons()->getSelectOptions();
$priceReasonId = array_search('Price too high', $options);

if ($priceReasonId) {
    Teamleader::deals()->lose('deal-uuid', $priceReasonId, 'Budget was 40% below minimum');
}
```

### Cache lost reasons

```php
$reasons = Cache::remember('tl_lost_reasons', 3600, function () {
    return Teamleader::lostReasons()->all();
});
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $reasons = Teamleader::lostReasons()->list();
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Deals]] — Lost reasons are passed to `Deals::lose()` via `$reasonId`
- [[Filtering]] — General filter reference

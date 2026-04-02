# Call Outcomes

Read call outcome definitions in Teamleader Focus.

## Overview

The Call Outcomes resource provides read-only access to the outcome categories available for completed calls. Outcomes
are defined in Teamleader and cannot be created or modified through the API.

Access via `Teamleader::callOutcomes()`.

## Endpoint

`callOutcomes`

## Capabilities

| Capability  | Supported                |
|-------------|--------------------------|
| Pagination  | ✅ Supported              |
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

$outcomes = Teamleader::callOutcomes()->list();

$outcomes = Teamleader::callOutcomes()->list([
    'ids' => ['outcome-uuid-1', 'outcome-uuid-2'],
]);

$outcomes = Teamleader::callOutcomes()->list([], [
    'page_size' => 50, 'page_number' => 1,
]);
```

---

### `info(string $id)`

```php
$outcome = Teamleader::callOutcomes()->info('outcome-uuid');
```

---

## Helper Methods

### `byIds(array $ids)`

```php
$outcomes = Teamleader::callOutcomes()->byIds(['outcome-uuid-1', 'outcome-uuid-2']);
```

### `findByName(string $name)`

**Client-side** — calls `list()` then searches in PHP (case-insensitive). Returns the matching outcome array or `null`.

```php
$outcome = Teamleader::callOutcomes()->findByName('Positive');
// returns array or null
```

---

## Filters

| Filter | Type  | Description             |
|--------|-------|-------------------------|
| `ids`  | array | Filter by outcome UUIDs |

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'outcome-uuid', 'name' => 'Positive'],
        ['id' => 'outcome-uuid', 'name' => 'Negative'],
        ['id' => 'outcome-uuid', 'name' => 'No answer'],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 5],
]
```

> **`selectOptions()` format:** Returns `[['value' => uuid, 'label' => name], ...]` — an array of objects, not a
> flat `[id => name]` map.

---

## Usage Examples

### Build an outcome selector

```php
$outcomes = Teamleader::callOutcomes()->list();

// For a plain [id => name] map
$options = array_column($outcomes['data'], 'name', 'id');
```

### Complete a call with the right outcome

```php
$outcome = Teamleader::callOutcomes()->findByName('Positive');

if ($outcome) {
    Teamleader::calls()->complete('call-uuid', $outcome['id'], 'Client confirmed.');
}
```

### Cache outcomes

```php
$outcomes = Cache::remember('tl_call_outcomes', 3600, function () {
    return Teamleader::callOutcomes()->list();
});
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $outcomes = Teamleader::callOutcomes()->list();
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Calls]] — Outcomes are passed to `Calls::complete()` via `$outcomeId`
- [[Activity-Types]] — Activity categorisation for events and meetings

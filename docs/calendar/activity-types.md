# Activity Types

Read activity type definitions in Teamleader Focus.

## Overview

The Activity Types resource provides read-only access to the activity type categories used on calendar events and
meetings. Types are defined in Teamleader and cannot be created or modified through the API.

Access via `Teamleader::activityTypes()`.

## Endpoint

`activityTypes`

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

$types = Teamleader::activityTypes()->list();

$types = Teamleader::activityTypes()->list([
    'ids' => ['type-uuid-1', 'type-uuid-2'],
]);

$types = Teamleader::activityTypes()->list([], [
    'page_size' => 50, 'page_number' => 1,
]);
```

---

### `info(string $id)`

```php
$type = Teamleader::activityTypes()->info('type-uuid');
```

---

## Helper Methods

### `byIds(array $ids)`

```php
$types = Teamleader::activityTypes()->byIds(['type-uuid-1', 'type-uuid-2']);
```

### `all(array $options = [])`

Calls `list()` with `page_size: 100`. For accounts with more than 100 activity types, this will not return everything —
use paginated `list()` calls instead.

```php
$types = Teamleader::activityTypes()->all();
```

### `exists(string $id)`

Returns `true` if an activity type with that UUID exists. Calls `byIds([$id])` internally.

```php
$exists = Teamleader::activityTypes()->exists('type-uuid');
```

### `findByName(string $name)`

**Client-side** — calls `list()` then searches in PHP (case-insensitive). Returns the matching type array or `null`.

```php
$type = Teamleader::activityTypes()->findByName('Client Meeting');
// returns array or null
```

### `selectOptions(array $options = [])`

Returns `[['value' => uuid, 'label' => name], ...]` — an array of objects (not a flat `[id => name]` map). Calls `all()`
internally.

```php
$options = Teamleader::activityTypes()->selectOptions();
// [['value' => 'uuid', 'label' => 'Client Meeting'], ...]

// For a plain [id => name] map
$map = array_column($options, 'label', 'value');
```

---

## Filters

| Filter | Type  | Description                   |
|--------|-------|-------------------------------|
| `ids`  | array | Filter by activity type UUIDs |

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'type-uuid', 'name' => 'Client Meeting'],
        ['id' => 'type-uuid', 'name' => 'Internal Sync'],
        ['id' => 'type-uuid', 'name' => 'Training'],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 8],
]
```

---

## Usage Examples

### Build a type selector for event creation

```php
$types = Teamleader::activityTypes()->list();
$map   = array_column($types['data'], 'name', 'id');
// ['uuid-1' => 'Client Meeting', 'uuid-2' => 'Internal Sync', ...]
```

### Find a type and use it on create

```php
$type = Teamleader::activityTypes()->findByName('Client Meeting');

if ($type) {
    Teamleader::calenderEvents()->create([
        'title'            => 'Discovery call',
        'activity_type_id' => $type['id'],
        'starts_at'        => '2025-05-10T10:00:00+02:00',
        'ends_at'          => '2025-05-10T11:00:00+02:00',
    ]);
}
```

### Cache activity types

```php
$types = Cache::remember('tl_activity_types', 86400, function () {
    return Teamleader::activityTypes()->list();
});
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $types = Teamleader::activityTypes()->list();
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Calendar-Events]] — `activity_type_id` is required on event creation
- [[Meetings]] — Activity type can be set on meetings via `activity_type_id`
- [[Call-Outcomes]] — Outcome definitions for calls (parallel concept)

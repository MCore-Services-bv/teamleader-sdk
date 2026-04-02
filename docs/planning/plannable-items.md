# Plannable Items

Retrieve plannable items from Teamleader Focus.

## Overview

A plannable item is Teamleader's planning abstraction over an underlying source entity — typically a project task or
group. Each item exposes duration metrics (total, planned, unplanned) that [[Reservations]] consume, and three
orthogonal status dimensions you can filter on independently.

Access via `Teamleader::plannableItems()`.

> **Read-only.** Plannable items are created automatically when tasks or groups are created; they cannot be created,
> updated, or deleted through this resource.
>
> **`info()` throws if `$id` is empty.** If you have a task UUID but not a plannable item UUID, use `infoBySource()`
> instead.
>
> **Three separate status filters** — `status`, `completion_statuses`, and `planned_time_statuses` are each
> independently validated. They can be combined freely.

## Endpoint

`plannableItems`

## Capabilities

| Capability  | Supported                                        |
|-------------|--------------------------------------------------|
| Pagination  | ✅ Supported                                      |
| Filtering   | ✅ Supported                                      |
| Sorting     | ✅ Supported (`id`, `end_date`, `total_duration`) |
| Sideloading | ❌ Not supported                                  |
| Creation    | ❌ Not supported                                  |
| Update      | ❌ Not supported                                  |
| Deletion    | ❌ Not supported                                  |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$items = Teamleader::plannableItems()->list();

// Filter active unplanned items, sorted by end date
$items = Teamleader::plannableItems()->list(
    [
        'status'               => ['active'],
        'planned_time_statuses'=> ['unplanned'],
    ],
    ['sort' => [['field' => 'end_date', 'order' => 'asc']], 'page_size' => 50]
);

// Filter by project and assignee
$items = Teamleader::plannableItems()->list([
    'project_ids' => ['project-uuid'],
    'assignees'   => [['type' => 'user', 'id' => 'user-uuid']],
]);
```

---

### `info(mixed $id)`

Throws `InvalidArgumentException` if `$id` is empty.

```php
$item = Teamleader::plannableItems()->info('plannable-item-uuid');
```

---

### `infoBySource(string $sourceType, string $sourceId)`

Look up a plannable item by the type and UUID of its underlying source entity. Both arguments are validated as
non-empty.

```php
// Look up the plannable item from a task UUID
$item = Teamleader::plannableItems()->infoBySource('task', 'task-uuid');
$plannableItemId = $item['data']['id'];

// Use the plannable item ID to create a reservation
Teamleader::reservations()->create([
    'plannable_item_id' => $plannableItemId,
    'date'              => '2025-05-12',
    'duration'          => ['value' => 240, 'unit' => 'minutes'],
    'assignee'          => ['type' => 'user', 'id' => 'user-uuid'],
]);
```

---

## Helper Methods

| Method                          | Filter applied                           |
|---------------------------------|------------------------------------------|
| `active()`                      | `status: ['active']`                     |
| `unplanned()`                   | `planned_time_statuses: ['unplanned']`   |
| `overbooked()`                  | `planned_time_statuses: ['overbooked']`  |
| `forProject(string $projectId)` | `project_ids: [$projectId]`              |
| `forUser(string $userId)`       | `assignees: [{type: user, id: $userId}]` |

All helpers accept optional `$filters` and `$options` to merge additional constraints:

```php
$items = Teamleader::plannableItems()->active();
$items = Teamleader::plannableItems()->unplanned(['project_ids' => ['uuid']]);
$items = Teamleader::plannableItems()->overbooked();
$items = Teamleader::plannableItems()->forProject('project-uuid');
$items = Teamleader::plannableItems()->forUser('user-uuid');
```

---

## Filters

| Filter                  | Type   | Description                                                                    |
|-------------------------|--------|--------------------------------------------------------------------------------|
| `ids`                   | array  | Filter by plannable item UUIDs                                                 |
| `status`                | array  | **Validated:** `active`, `deactivated`                                         |
| `term`                  | string | Search by title/name                                                           |
| `start_date`            | string | YYYY-MM-DD — validated                                                         |
| `end_date`              | string | YYYY-MM-DD — validated                                                         |
| `project_ids`           | array  | Filter by project UUIDs                                                        |
| `assignees`             | array  | `[{type: user\|team, id: uuid}]`                                               |
| `work_type_ids`         | array  | Filter by work type UUIDs                                                      |
| `completion_statuses`   | array  | **Validated:** `to_do`, `done`                                                 |
| `planned_time_statuses` | array  | **Validated:** `unplanned`, `partially_planned`, `fully_planned`, `overbooked` |

The three validated filter groups (`status`, `completion_statuses`, `planned_time_statuses`)
throw `InvalidArgumentException` for unrecognised values.

---

## Sorting

Sort field passed as `options['sort']` — array of `{field, order}` objects.

| Field            | Description                         |
|------------------|-------------------------------------|
| `id`             | Plannable item UUID (default order) |
| `end_date`       | End date                            |
| `total_duration` | Total estimated duration            |

```php
$items = Teamleader::plannableItems()->list([], [
    'sort' => [['field' => 'end_date', 'order' => 'asc']],
]);
```

---

## Error Handling

```php
use InvalidArgumentException;

// Empty ID on info()
try {
    Teamleader::plannableItems()->info('');
} catch (InvalidArgumentException $e) {
    // 'Plannable item ID is required. To look up by source, use infoBySource() instead.'
}

// Invalid status value
try {
    Teamleader::plannableItems()->list(['status' => ['archived']]);
} catch (InvalidArgumentException $e) {
    // 'Invalid status: archived. Must be one of: active, deactivated'
}

// Invalid planned_time_status
try {
    Teamleader::plannableItems()->list(['planned_time_statuses' => ['overdue']]);
} catch (InvalidArgumentException $e) {
    // 'Invalid planned_time_status: overdue. Must be one of: unplanned, ...'
}
```

---

## Related Resources

- [[Reservations]] — Create reservations for plannable items
- [[User-Availability]] — Check capacity before planning
- [[Project-Tasks]] — Source entities that generate plannable items
- [[Groups]] — Project groups that can also be plannable

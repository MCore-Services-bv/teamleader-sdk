# Plannable Items

Retrieve plannable items from Teamleader Focus.

## Overview

A plannable item is Teamleader's planning abstraction over an underlying source entity (such as a task or project group). Each item exposes three duration metrics — total, planned, and unplanned — which the [Reservations](reservations.md) resource uses to schedule work.

This resource is **read-only**. Plannable items are created and managed via their source entities (tasks, project groups, etc.).

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [infoBySource()](#infobysource)
- [Convenience Methods](#convenience-methods)
- [Filters Reference](#filters-reference)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`plannableItems`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

---

## Available Methods

### `list()`

List plannable items with optional filters, sorting, and pagination.

**Parameters:**
- `filters` (array, optional): Filters to narrow results — see [Filters Reference](#filters-reference)
- `options` (array, optional):
    - `page_size` (int): Results per page (default: 20)
    - `page_number` (int): Page number (default: 1)
    - `sort` (array): Sort configuration — see [Sorting](#sorting)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all plannable items
$items = Teamleader::plannableItems()->list();

// Filter by status
$items = Teamleader::plannableItems()->list([
    'status' => ['active'],
]);

// Filter by project
$items = Teamleader::plannableItems()->list([
    'project_ids' => ['project-uuid'],
]);

// Filter unplanned items for a user
$items = Teamleader::plannableItems()->list([
    'planned_time_statuses' => ['unplanned'],
    'assignees' => [
        ['type' => 'user', 'id' => 'user-uuid'],
    ],
]);

// With pagination and sorting
$items = Teamleader::plannableItems()->list([], [
    'page_size'   => 50,
    'page_number' => 1,
    'sort'        => [['field' => 'end_date', 'order' => 'asc']],
]);
```

---

### `info()`

Get a single plannable item by its UUID.

**Parameters:**
- `id` (string, required): Plannable item UUID

**Example:**
```php
$item = Teamleader::plannableItems()->info('018d79a1-2b99-7fbd-b323-500b01305371');
```

---

### `infoBySource()`

Get a single plannable item using the underlying source entity's type and ID, when the plannable item UUID is not known.

**Parameters:**
- `sourceType` (string, required): Type of the source entity (e.g. `task`)
- `sourceId` (string, required): UUID of the source entity

**Example:**
```php
$item = Teamleader::plannableItems()->infoBySource(
    'task',
    'eab232c6-49b2-4b7e-a977-5e1148dad471'
);

$plannableItemId = $item['data']['id'];
```

---

## Convenience Methods

### `active()`

Get active plannable items.

```php
$items = Teamleader::plannableItems()->active();

// With additional filters
$items = Teamleader::plannableItems()->active([
    'project_ids' => ['project-uuid'],
]);
```

### `unplanned()`

Get items with no planned time yet.

```php
$items = Teamleader::plannableItems()->unplanned();
```

### `overbooked()`

Get items where planned time exceeds the estimated total duration.

```php
$items = Teamleader::plannableItems()->overbooked();
```

### `forProject()`

Get all plannable items belonging to a specific project.

```php
$items = Teamleader::plannableItems()->forProject('project-uuid');
```

### `forUser()`

Get all plannable items assigned to a specific user.

```php
$items = Teamleader::plannableItems()->forUser('user-uuid');
```

---

## Filters Reference

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | `string[]` | Filter by plannable item UUIDs |
| `status` | `string[]` | `active`, `deactivated` |
| `term` | `string` | Search by name/title |
| `start_date` | `string` | Filter items from this date (YYYY-MM-DD) |
| `end_date` | `string` | Filter items up to this date (YYYY-MM-DD) |
| `project_ids` | `string[]` | Filter by project UUIDs |
| `assignees` | `object[]` | Filter by assignees `{type: user\|team, id: UUID}`. Pass `null` for unassigned |
| `work_type_ids` | `string[]` | Filter by work type UUIDs |
| `completion_statuses` | `string[]` | `to_do`, `done` |
| `planned_time_statuses` | `string[]` | `unplanned`, `partially_planned`, `fully_planned`, `overbooked` |

---

## Sorting

The `list()` method accepts a `sort` option. Sort can be expressed in multiple ways:

```php
// Simple string (defaults to asc)
$options = ['sort' => 'end_date'];

// String with direction
$options = ['sort' => 'end_date:desc'];

// Single sort object
$options = ['sort' => ['field' => 'end_date', 'order' => 'asc']];

// Multiple sort fields
$options = ['sort' => [
    ['field' => 'end_date', 'order' => 'asc'],
    ['field' => 'total_duration', 'order' => 'desc'],
]];
```

**Valid sort fields:**

| Field | Description |
|-------|-------------|
| `id` | Sort by plannable item ID (default) |
| `end_date` | Sort by end date |
| `total_duration` | Sort by total estimated duration |

---

## Response Structure

### `list()` Response

```json
{
    "data": [
        {
            "id": "018d55af-d0d7-76be-8185-ee970a7f3826",
            "source": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "task"
            },
            "total_duration":     { "unit": "minutes", "value": 120 },
            "planned_duration":   { "unit": "minutes", "value": 60 },
            "unplanned_duration": { "unit": "minutes", "value": 60 }
        }
    ]
}
```

### `info()` Response

```json
{
    "data": {
        "id": "018d55af-d0d7-76be-8185-ee970a7f3826",
        "source": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "task"
        },
        "total_duration":     { "unit": "minutes", "value": 120 },
        "planned_duration":   { "unit": "minutes", "value": 60 },
        "unplanned_duration": { "unit": "minutes", "value": 60 }
    }
}
```

---

## Usage Examples

### Find a plannable item from a task, then create a reservation

```php
// Resolve plannable item from a known task UUID
$item = Teamleader::plannableItems()->infoBySource('task', $taskId);
$plannableItemId = $item['data']['id'];

// Check there is still time to plan
$unplanned = $item['data']['unplanned_duration']['value'];

if ($unplanned >= 60) {
    Teamleader::reservations()->create([
        'plannable_item_id' => $plannableItemId,
        'date'              => '2024-06-01',
        'duration'          => ['value' => 60, 'unit' => 'minutes'],
        'assignee'          => ['type' => 'user', 'id' => $userId],
    ]);
}
```

### Build a planning backlog (active, not fully planned)

```php
$backlog = Teamleader::plannableItems()->list([
    'status'                => ['active'],
    'planned_time_statuses' => ['unplanned', 'partially_planned'],
    'completion_statuses'   => ['to_do'],
], [
    'sort' => [['field' => 'end_date', 'order' => 'asc']],
]);
```

### Paginate through all items for a project

```php
$allItems = [];
$page     = 1;

do {
    $response = Teamleader::plannableItems()->forProject('project-uuid', [], [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $allItems = array_merge($allItems, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $item = Teamleader::plannableItems()->info('plannable-item-uuid');
} catch (InvalidArgumentException $e) {
    // Missing ID, invalid filter value, etc.
    Log::error('Validation error: '.$e->getMessage());
} catch (TeamleaderException $e) {
    Log::error('API error: '.$e->getMessage());
}
```

---

## Related Resources

- **[Reservations](reservations.md)** — Schedule time against plannable items
- **[User Availability](user-availability.md)** — Check capacity before planning
- **[Work Types](../general/work-types.md)** — Filter by work type
- **[Projects](../projects/projects.md)** — Filter by project

# Reservations

Manage planning reservations in Teamleader Focus.

## Overview

A reservation assigns a plannable item to a user or team for a specific date and duration. Reservations are the building
blocks of the planning board — they consume the available capacity exposed by the [[User-Availability]] resource.

Access via `Teamleader::reservations()`.

> **Duration unit must be `minutes`** — it is the only valid value and is validated before the request.
>
> **To filter for unassigned reservations**, pass `[null]` inside the `assignees` array — not an empty array.
>
> **`source_types` filter is validated** — only `call`, `closingDay`, `dayOffType`, `externalEvent`, `meeting`, `task`
> are accepted.
>
> **`create()` returns HTTP 201** with `data.{id, type}`. **`update()` and `delete()` return HTTP 204** (no body).

## Endpoint

`reservations`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ✅ Supported     |
| Filtering   | ✅ Supported     |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ✅ Supported     |
| Update      | ✅ Supported     |
| Deletion    | ✅ Supported     |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All reservations
$reservations = Teamleader::reservations()->list();

// Filter by date range
$reservations = Teamleader::reservations()->list([
    'start_date' => '2025-05-01',
    'end_date'   => '2025-05-31',
]);

// Filter by plannable items
$reservations = Teamleader::reservations()->list([
    'plannable_item_ids' => ['item-uuid-1', 'item-uuid-2'],
]);

// Filter by assignee
$reservations = Teamleader::reservations()->list([
    'assignees' => [['type' => 'user', 'id' => 'user-uuid']],
]);

// Unassigned reservations — pass [null], NOT []
$reservations = Teamleader::reservations()->list([
    'assignees' => [null],
]);

// Filter by source type
$reservations = Teamleader::reservations()->list([
    'source_types' => ['task', 'meeting'],
]);

// With pagination
$reservations = Teamleader::reservations()->list([], ['page_size' => 50, 'page_number' => 2]);
```

---

### `create(array $data)`

All four fields are required and validated:

| Required            | Type   | Notes                                               |
|---------------------|--------|-----------------------------------------------------|
| `plannable_item_id` | string | UUID of the plannable item                          |
| `date`              | string | YYYY-MM-DD — validated                              |
| `duration`          | object | `{value: number, unit: 'minutes'}` — unit validated |
| `assignee`          | object | `{type: user\|team, id: uuid}` — type validated     |

Returns `data.{id, type}` on HTTP 201.

```php
$reservation = Teamleader::reservations()->create([
    'plannable_item_id' => 'item-uuid',
    'date'              => '2025-05-12',
    'duration'          => ['value' => 120, 'unit' => 'minutes'],
    'assignee'          => ['type' => 'user', 'id' => 'user-uuid'],
]);

$reservationId = $reservation['data']['id'];
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body. All fields optional. Validated when present.

Returns HTTP 204 (no body).

```php
// Reschedule
Teamleader::reservations()->update('reservation-uuid', [
    'date'     => '2025-05-15',
    'duration' => ['value' => 90, 'unit' => 'minutes'],
]);

// Reassign
Teamleader::reservations()->update('reservation-uuid', [
    'assignee' => ['type' => 'team', 'id' => 'team-uuid'],
]);
```

---

### `delete(mixed $id)`

Throws `InvalidArgumentException` if `$id` is empty. Returns HTTP 204.

```php
Teamleader::reservations()->delete('reservation-uuid');
```

---

## Helper Methods

| Method                                     | Behaviour                                          |
|--------------------------------------------|----------------------------------------------------|
| `forUser(string $userId)`                  | `assignees: [{type: user, id: $userId}]`           |
| `forTeam(string $teamId)`                  | `assignees: [{type: team, id: $teamId}]`           |
| `forDateRange(string $start, string $end)` | `start_date` + `end_date` filters; dates validated |
| `unassigned()`                             | `assignees: [null]`                                |

```php
$reservations = Teamleader::reservations()->forUser('user-uuid');
$reservations = Teamleader::reservations()->forTeam('team-uuid');
$reservations = Teamleader::reservations()->forDateRange('2025-05-01', '2025-05-31');
$reservations = Teamleader::reservations()->unassigned();
```

---

## Filters

| Filter               | Type   | Description                                                                       |
|----------------------|--------|-----------------------------------------------------------------------------------|
| `plannable_item_ids` | array  | Filter by plannable item UUIDs                                                    |
| `start_date`         | string | YYYY-MM-DD — validated                                                            |
| `end_date`           | string | YYYY-MM-DD — validated                                                            |
| `assignees`          | array  | `[{type: user\|team, id: uuid}]` or `[null]` for unassigned                       |
| `sources`            | array  | `[{type: string, id: uuid}]`                                                      |
| `source_types`       | array  | Validated: `call`, `closingDay`, `dayOffType`, `externalEvent`, `meeting`, `task` |

---

## Response Structure

```php
// list()
[
    'data' => [
        [
            'id'             => 'reservation-uuid',
            'plannable_item' => ['type' => 'plannableItem', 'id' => 'item-uuid'],
            'date'           => '2025-05-12',
            'duration'       => ['unit' => 'minutes', 'value' => 120],
            'assignee'       => ['type' => 'user', 'id' => 'user-uuid'],
            'origin'         => ['type' => 'task', 'id' => 'task-uuid'],  // nullable
        ],
    ],
]

// create()
['data' => ['id' => 'reservation-uuid', 'type' => 'reservation']]  // HTTP 201
```

---

## Usage Examples

### Plan a task for a user

```php
// Look up the plannable item from the task
$item = Teamleader::plannableItems()->infoBySource('task', 'task-uuid');
$plannableItemId = $item['data']['id'];

// Create the reservation
Teamleader::reservations()->create([
    'plannable_item_id' => $plannableItemId,
    'date'              => '2025-05-12',
    'duration'          => ['value' => 240, 'unit' => 'minutes'],
    'assignee'          => ['type' => 'user', 'id' => 'user-uuid'],
]);
```

### Get this week's reservations for a user

```php
$reservations = Teamleader::reservations()->forUser('user-uuid');
// Combine with forDateRange if needed:
$reservations = Teamleader::reservations()->list([
    'assignees'  => [['type' => 'user', 'id' => 'user-uuid']],
    'start_date' => '2025-05-12',
    'end_date'   => '2025-05-16',
]);
```

---

## Error Handling

```php
use InvalidArgumentException;

// Wrong duration unit
try {
    Teamleader::reservations()->create([
        'plannable_item_id' => 'uuid',
        'date'              => '2025-05-12',
        'duration'          => ['value' => 2, 'unit' => 'hours'],  // invalid
        'assignee'          => ['type' => 'user', 'id' => 'uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid duration unit: hours. Must be one of: minutes'
}

// Invalid source_type
try {
    Teamleader::reservations()->list(['source_types' => ['project']]);
} catch (InvalidArgumentException $e) {
    // 'Invalid source_type: project. Must be one of: call, closingDay, ...'
}

// Invalid date format
try {
    Teamleader::reservations()->forDateRange('01/05/2025', '31/05/2025');
} catch (InvalidArgumentException $e) {
    // 'start_date must be in YYYY-MM-DD format'
}
```

---

## Related Resources

- [[Plannable-Items]] — Find items to reserve
- [[User-Availability]] — Check capacity before creating reservations

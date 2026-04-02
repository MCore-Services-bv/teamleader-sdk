# Tasks

Manage tasks in Teamleader Focus.

## Overview

The Tasks resource manages standalone tasks — to-do items that can be assigned to users, linked to customers or legacy
milestones, and scheduled in the calendar. This is **not** the same as [[Project-Tasks]], which manages work items
inside Projects v2.

Access via `Teamleader::tasks()`.

> **Not to be confused with [[Project-Tasks]]** — this resource uses endpoint `tasks`; project tasks
> use `projects-v2/tasks`.
>
> **Only sort field is `name`.** No other sort fields are recognised.
>
> **`schedule()` validates ISO 8601 datetime format** — non-matching strings throw before the request.
>
> **`unassigned()` passes `user_id: null`** explicitly — not an empty filter.

## Endpoint

`tasks`

## Capabilities

| Capability  | Supported                 |
|-------------|---------------------------|
| Pagination  | ✅ Supported               |
| Filtering   | ✅ Supported               |
| Sorting     | ✅ Supported (`name` only) |
| Sideloading | ❌ Not supported           |
| Creation    | ✅ Supported               |
| Update      | ✅ Supported               |
| Deletion    | ✅ Supported               |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$tasks = Teamleader::tasks()->list();

$tasks = Teamleader::tasks()->list(
    ['completed' => false, 'user_id' => 'user-uuid'],
    ['sort' => [['field' => 'name', 'order' => 'asc']], 'page_size' => 50]
);
```

---

### `info(mixed $id)`

```php
$task = Teamleader::tasks()->info('task-uuid');
```

---

### `create(array $data)`

Three fields are required and validated:

| Required       | Notes                  |
|----------------|------------------------|
| `title`        | Task title             |
| `due_on`       | YYYY-MM-DD — validated |
| `work_type_id` | Work type UUID         |

```php
$task = Teamleader::tasks()->create([
    'title'        => 'Follow up with client',
    'due_on'       => '2025-06-30',
    'work_type_id' => 'work-type-uuid',
    'description'  => 'Discuss renewal options',
    'user_id'      => 'user-uuid',
    'customer'     => ['type' => 'company', 'id' => 'company-uuid'],
]);
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body.

```php
Teamleader::tasks()->update('task-uuid', ['title' => 'Follow up tomorrow', 'due_on' => '2025-07-01']);
```

---

### `delete(mixed $id)`

```php
Teamleader::tasks()->delete('task-uuid');
```

---

### `complete(string $id)` / `reopen(string $id)`

```php
Teamleader::tasks()->complete('task-uuid');
Teamleader::tasks()->reopen('task-uuid');
```

---

### `schedule(string $id, string $startsAt, string $endsAt)`

Schedules a task in the calendar. Both datetimes validated as ISO 8601 (`YYYY-MM-DDTHH:MM:SS+HH:MM`).

```php
Teamleader::tasks()->schedule(
    'task-uuid',
    '2025-06-30T09:00:00+02:00',
    '2025-06-30T10:00:00+02:00'
);
```

---

## Helper Methods

| Method                                  | Filter sent                             |
|-----------------------------------------|-----------------------------------------|
| `forUser(string $userId)`               | `user_id: $userId`                      |
| `unassigned()`                          | `user_id: null` (explicit null)         |
| `completed()`                           | `completed: true`                       |
| `incomplete()`                          | `completed: false`                      |
| `scheduled()`                           | `scheduled: true`                       |
| `forMilestone(string $milestoneId)`     | `milestone_id: $milestoneId`            |
| `forCustomer(string $type, string $id)` | `customer: {type, id}` — type validated |
| `dueBetween(string $from, string $by)`  | `due_from` + `due_by` — dates validated |
| `search(string $term)`                  | `term: $term`                           |
| `byIds(array $ids)`                     | `ids: $ids`                             |

```php
Teamleader::tasks()->incomplete();
Teamleader::tasks()->forUser('user-uuid');
Teamleader::tasks()->forCustomer('company', 'company-uuid');
Teamleader::tasks()->dueBetween('2025-06-01', '2025-06-30');
```

---

## Filters

| Filter         | Type         | Description                                           |
|----------------|--------------|-------------------------------------------------------|
| `ids`          | array        | Filter by task UUIDs                                  |
| `user_id`      | string\|null | Assigned user UUID; pass `null` for unassigned        |
| `milestone_id` | string       | Legacy milestone UUID                                 |
| `completed`    | bool         | Completion status                                     |
| `scheduled`    | bool         | Scheduled in calendar                                 |
| `due_by`       | string       | Due on or before this date (YYYY-MM-DD)               |
| `due_from`     | string       | Due on or after this date (YYYY-MM-DD)                |
| `term`         | string       | Searches in description                               |
| `customer`     | object       | `{type: contact\|company, id: uuid}` — type validated |

---

## Error Handling

```php
use InvalidArgumentException;

// Missing required field
try {
    Teamleader::tasks()->create(['title' => 'Test', 'due_on' => '2025-06-30']);
    // missing work_type_id
} catch (InvalidArgumentException $e) {
    // 'work_type_id is required for creating a task'
}

// Invalid datetime on schedule()
try {
    Teamleader::tasks()->schedule('task-uuid', '2025-06-30 09:00:00', '2025-06-30 10:00:00');
} catch (InvalidArgumentException $e) {
    // 'starts_at must be in ISO 8601 format (e.g., 2025-02-04T16:00:00+00:00)'
}

// Invalid customer type
try {
    Teamleader::tasks()->forCustomer('team', 'uuid');
} catch (InvalidArgumentException $e) {
    // 'Invalid customer type. Must be one of: contact, company'
}
```

---

## Related Resources

- [[Project-Tasks]] — Tasks inside Projects v2 (different endpoint, different fields)
- [[Legacy-Milestones]] — Tasks can be linked to legacy milestones via `milestone_id`
- [[Work-Types]] — `work_type_id` required on create
- [[Time-Tracking]] — Log time against tasks

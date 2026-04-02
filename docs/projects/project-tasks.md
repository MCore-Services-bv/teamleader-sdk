# Project Tasks

Manage tasks in Teamleader Focus projects (v2).

## Overview

Project tasks are the primary work items within a project. They support billing, time estimates, assignees, and a full
status lifecycle.

Access via `Teamleader::projectTasks()`.

> **`billing_method: work_type_rate`** additionally requires `work_type_id`.
>
> **`delete()` requires a strategy** — defaults to `unlink_time_tracking`.
>
> **`duplicate()` uses `origin_id`** parameter name.
>
> **No sorting.**

## Endpoint

`projects-v2/tasks`

## Capabilities

| Capability  | Supported                |
|-------------|--------------------------|
| Pagination  | ✅ Supported              |
| Filtering   | ✅ Supported (`ids` only) |
| Sorting     | ❌ Not supported          |
| Sideloading | ❌ Not supported          |
| Creation    | ✅ Supported              |
| Update      | ✅ Supported              |
| Deletion    | ✅ Supported              |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$tasks = Teamleader::projectTasks()->list();

$tasks = Teamleader::projectTasks()->list(
    ['ids' => ['task-uuid-1', 'task-uuid-2']],
    ['page_size' => 50, 'page_number' => 1]
);
```

---

### `info(string $id)`

```php
$task = Teamleader::projectTasks()->info('task-uuid');
```

---

### `create(array $data)`

Three fields are required and validated:

| Required         | Notes                     |
|------------------|---------------------------|
| `project_id`     | Project UUID              |
| `title`          | Task title                |
| `billing_method` | See billing methods below |

**Billing methods (validated):**

| Method               | Notes                                                          |
|----------------------|----------------------------------------------------------------|
| `user_rate`          | Bills at the assigned user's rate                              |
| `work_type_rate`     | Bills at work type rate — also requires `work_type_id`         |
| `custom_rate`        | Custom hourly rate — provide `custom_rate: {amount, currency}` |
| `fixed_price`        | Fixed price — provide `fixed_price: {amount, currency}`        |
| `parent_fixed_price` | Inherits parent group's fixed price                            |
| `non_billable`       | Not billable                                                   |

**Status values (validated when provided):** `to_do`, `in_progress`, `on_hold`, `done`

**Time estimate units (validated):** `hours`, `minutes`, `seconds`

```php
$task = Teamleader::projectTasks()->create([
    'project_id'     => 'project-uuid',
    'title'          => 'Build login flow',
    'billing_method' => 'custom_rate',
    'custom_rate'    => ['amount' => 95.0, 'currency' => 'EUR'],
    'status'         => 'to_do',
    'time_estimated' => ['value' => 8, 'unit' => 'hours'],
    'group_id'       => 'group-uuid',   // optional — assign to a group
    'assignees'      => [['type' => 'user', 'id' => 'user-uuid']],
]);

// work_type_rate requires work_type_id
$task = Teamleader::projectTasks()->create([
    'project_id'     => 'project-uuid',
    'title'          => 'Code review',
    'billing_method' => 'work_type_rate',
    'work_type_id'   => 'work-type-uuid',
]);
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body. All fields except `id` are optional.

```php
Teamleader::projectTasks()->update('task-uuid', [
    'status'      => 'in_progress',
    'description' => 'Updated description',
]);
```

---

### `delete(mixed $id, string $deleteStrategy = 'unlink_time_tracking')`

**Delete strategies (validated):**

| Strategy               | Behaviour                                       |
|------------------------|-------------------------------------------------|
| `unlink_time_tracking` | Default — keeps time entries, removes task link |
| `delete_time_tracking` | Deletes the task and all time entries           |

```php
Teamleader::projectTasks()->delete('task-uuid');
Teamleader::projectTasks()->delete('task-uuid', 'delete_time_tracking');
```

---

### `duplicate(string $originId)`

Creates a copy of the task (without time trackings).

```php
$copy = Teamleader::projectTasks()->duplicate('task-uuid');
```

---

### `assign(string $taskId, string $assigneeType, string $assigneeId)` / `unassign()`

Assignee type validated: `user`, `team`.

```php
Teamleader::projectTasks()->assign('task-uuid', 'user', 'user-uuid');
Teamleader::projectTasks()->assignUser('task-uuid', 'user-uuid');
Teamleader::projectTasks()->assignTeam('task-uuid', 'team-uuid');
Teamleader::projectTasks()->unassignUser('task-uuid', 'user-uuid');
Teamleader::projectTasks()->unassignTeam('task-uuid', 'team-uuid');
```

---

## Helper Methods

```php
Teamleader::projectTasks()->byIds(['uuid-1', 'uuid-2']);
Teamleader::projectTasks()->updateStatus('task-uuid', 'done');
```

---

## Error Handling

```php
use InvalidArgumentException;

// work_type_rate without work_type_id
try {
    Teamleader::projectTasks()->create([
        'project_id'     => 'uuid',
        'title'          => 'Review',
        'billing_method' => 'work_type_rate',
        // missing work_type_id
    ]);
} catch (InvalidArgumentException $e) {
    // 'work_type_id is required when billing_method is work_type_rate'
}

// Invalid time unit
try {
    Teamleader::projectTasks()->create([
        ...,
        'time_estimated' => ['value' => 2, 'unit' => 'days'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid time unit. Must be one of: hours, minutes, seconds'
}
```

---

## Related Resources

- [[Projects]] — Parent project
- [[Groups]] — Groups that contain tasks
- [[Project-Lines]] — Unified listing of tasks, materials, groups
- [[Materials]] — Materials within a project

# Legacy Milestones

Manage milestones (phases) within legacy projects in Teamleader Focus.

## Overview

Legacy Milestones are the phases of a legacy project. Each milestone has its own timeline, billing method, and
responsible user. Milestones act as containers for tasks.

Access via `Teamleader::legacyMilestones()`.

> **Only available on Legacy Projects accounts.** Check with `Teamleader::accounts()->isUsingLegacyProjects()`.
>
> **`billing_method` is optional on create** — but validated when
> provided (`non_invoiceable`, `time_and_materials`, `fixed_price`).
>
> **Sort fields are silently clamped** — any invalid sort field falls back to `due_on`.
>
> **`close()` closes all open tasks** (meetings stay open). Closing the last open milestone also closes the project.
>
> **`open()` reopens the project** if it was closed.

## Endpoint

`milestones`

## Capabilities

| Capability  | Supported                           |
|-------------|-------------------------------------|
| Pagination  | ✅ Supported                         |
| Filtering   | ✅ Supported                         |
| Sorting     | ✅ Supported (`starts_on`, `due_on`) |
| Sideloading | ❌ Not supported                     |
| Creation    | ✅ Supported                         |
| Update      | ✅ Supported                         |
| Deletion    | ✅ Supported                         |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$milestones = Teamleader::legacyMilestones()->list();

$milestones = Teamleader::legacyMilestones()->list(
    ['project_id' => 'project-uuid', 'status' => 'open'],
    ['sort' => [['field' => 'due_on', 'order' => 'asc']], 'page_size' => 20]
);
```

---

### `info(string $id)`

Throws `InvalidArgumentException` if `$id` is empty.

```php
$milestone = Teamleader::legacyMilestones()->info('milestone-uuid');
```

---

### `create(array $data)`

Four fields are required:

| Required              | Notes                             |
|-----------------------|-----------------------------------|
| `project_id`          | Project UUID                      |
| `name`                | Milestone name                    |
| `due_on`              | Due date (YYYY-MM-DD) — validated |
| `responsible_user_id` | Responsible user UUID             |

`billing_method` is **optional** — but validated when provided. `starts_on` is also optional and validated when
provided.

**Billing methods (validated when provided):** `non_invoiceable`, `time_and_materials`, `fixed_price`

```php
$milestone = Teamleader::legacyMilestones()->create([
    'project_id'          => 'project-uuid',
    'name'                => 'Phase 2: Development',
    'starts_on'           => '2025-06-01',
    'due_on'              => '2025-07-31',
    'responsible_user_id' => 'user-uuid',
    'billing_method'      => 'time_and_materials',
    'description'         => 'Main development phase',
]);
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body. Throws if `$id` is empty.

```php
Teamleader::legacyMilestones()->update('milestone-uuid', [
    'name'   => 'Phase 2 — Updated',
    'due_on' => '2025-08-15',
]);
```

---

### `delete(mixed $id)`

Throws if `$id` is empty.

```php
Teamleader::legacyMilestones()->delete('milestone-uuid');
```

---

### `close(string $id)`

Closes all open tasks within the milestone. Open meetings remain open. If this is the last open milestone on the
project, the project itself is also closed.

```php
Teamleader::legacyMilestones()->close('milestone-uuid');
```

---

### `open(string $id)`

Reopens the milestone. If the project was closed, it is also reopened.

```php
Teamleader::legacyMilestones()->open('milestone-uuid');
```

---

## Helper Methods

### `forProject(string $projectId)`

```php
$milestones = Teamleader::legacyMilestones()->forProject('project-uuid');
```

### `dueBetween(string $startDate, string $endDate)`

Applies `due_after` + `due_before` filters.

```php
$milestones = Teamleader::legacyMilestones()->dueBetween('2025-04-01', '2025-06-30');

// With additional filters
$milestones = Teamleader::legacyMilestones()->dueBetween(
    '2025-04-01', '2025-06-30',
    ['status' => 'open']
);
```

---

## Filters

| Filter       | Type   | Description               |
|--------------|--------|---------------------------|
| `ids`        | array  | Filter by milestone UUIDs |
| `project_id` | string | Filter by project UUID    |
| `status`     | string | `open`, `closed`          |
| `due_before` | string | YYYY-MM-DD                |
| `due_after`  | string | YYYY-MM-DD                |
| `term`       | string | Search milestone name     |

---

## Sorting

Valid sort fields: `starts_on`, `due_on`. **Invalid field values silently fall back to `due_on`** — no exception is
thrown.

```php
$milestones = Teamleader::legacyMilestones()->list([], [
    'sort' => [['field' => 'due_on', 'order' => 'asc']],
]);
```

---

## Error Handling

```php
use InvalidArgumentException;

// Empty ID
try {
    Teamleader::legacyMilestones()->info('');
} catch (InvalidArgumentException $e) {
    // 'Milestone ID cannot be empty'
}

// Missing required field
try {
    Teamleader::legacyMilestones()->create([
        'project_id' => 'uuid', 'name' => 'Phase 1', 'due_on' => '2025-05-31',
        // missing responsible_user_id
    ]);
} catch (InvalidArgumentException $e) {
    // "Field 'responsible_user_id' is required for milestone creation"
}

// Invalid billing_method
try {
    Teamleader::legacyMilestones()->create([
        'project_id' => 'uuid', 'name' => 'Phase 1', 'due_on' => '2025-05-31',
        'responsible_user_id' => 'user-uuid', 'billing_method' => 'hourly',
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid billing_method. Must be one of: non_invoiceable, time_and_materials, fixed_price'
}

// Invalid date format
try {
    Teamleader::legacyMilestones()->create([
        'project_id' => 'uuid', 'name' => 'Phase 1',
        'due_on' => '31-05-2025', // wrong format
        'responsible_user_id' => 'user-uuid',
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid due_on date format. Use Y-m-d format.'
}
```

---

## Related Resources

- [[Legacy-Projects]] — Parent legacy project
- [[Accounts]] — Check which project version the account uses
- [[Groups]] — Equivalent concept in Projects v2

# Groups

Manage project groups in Teamleader Focus (Projects v2).

## Overview

Groups organise tasks and materials within a project into phases or logical sections. They can carry their own billing
method, colour, dates, and assignees.

Access via `Teamleader::groups()`.

> **`update()` billing_method uses a special object** — not a plain string. Pass `{value, update_strategy}`.
>
> **`delete()` requires a strategy** — defaults to `ungroup_tasks_and_materials`.
>
> **`duplicate()` uses `origin_id`** parameter name, not `id`.
>
> **No pagination.**

## Endpoint

`projects-v2/groups`

## Capabilities

| Capability  | Supported                         |
|-------------|-----------------------------------|
| Pagination  | ❌ Not supported                   |
| Filtering   | ✅ Supported (`ids`, `project_id`) |
| Sorting     | ❌ Not supported                   |
| Sideloading | ❌ Not supported                   |
| Creation    | ✅ Supported                       |
| Update      | ✅ Supported                       |
| Deletion    | ✅ Supported                       |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$groups = Teamleader::groups()->list(['project_id' => 'project-uuid']);
$groups = Teamleader::groups()->list(['ids' => ['group-uuid-1', 'group-uuid-2']]);
```

---

### `info(string $id)`

```php
$group = Teamleader::groups()->info('group-uuid');
```

---

### `create(array $data)`

Required fields validated before the request:

| Required     | Notes        |
|--------------|--------------|
| `project_id` | Project UUID |
| `title`      | Group title  |

**Billing methods:** `time_and_materials`, `fixed_price`, `parent_fixed_price`, `non_billable`

`fixed_price` billing requires a `fixed_price: {amount, currency}` object.

```php
$group = Teamleader::groups()->create([
    'project_id'     => 'project-uuid',
    'title'          => 'Phase 1: Discovery',
    'description'    => 'Research and requirements gathering',
    'billing_method' => 'fixed_price',
    'fixed_price'    => ['amount' => 5000.0, 'currency' => 'EUR'],
    'color'          => '#00B2B2',
    'start_date'     => '2025-05-01',
    'end_date'       => '2025-05-31',
]);
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body. When updating `billing_method`, it must be an object with `value`
and `update_strategy` — not a plain string.

**Update strategies for `billing_method`:** `none`, `cascade`

```php
Teamleader::groups()->update('group-uuid', [
    'title' => 'Phase 1 — Updated',
]);

// Updating billing method requires the special object form
Teamleader::groups()->update('group-uuid', [
    'billing_method' => [
        'value'           => 'time_and_materials',
        'update_strategy' => 'cascade',  // propagate to child tasks/materials
    ],
]);
```

---

### `delete(mixed $id, string $deleteStrategy = 'ungroup_tasks_and_materials')`

**Delete strategies (validated):**

| Strategy                                            | Behaviour                                           |
|-----------------------------------------------------|-----------------------------------------------------|
| `ungroup_tasks_and_materials`                       | Default — moves items out of the group              |
| `delete_tasks_and_materials`                        | Deletes the group and all its items                 |
| `delete_tasks_materials_and_unbilled_timetrackings` | Deletes group, items, and any unbilled time entries |

```php
Teamleader::groups()->delete('group-uuid');
Teamleader::groups()->delete('group-uuid', 'delete_tasks_and_materials');
```

---

### `duplicate(string $originId)`

Creates a copy of the group and its tasks/materials (without time trackings).

```php
$copy = Teamleader::groups()->duplicate('group-uuid');
```

---

### `assign(string $groupId, string $assigneeType, string $assigneeId)` / `unassign()`

Assignee type validated: `user`, `team`. Shortcuts available:

```php
Teamleader::groups()->assign('group-uuid', 'user', 'user-uuid');
Teamleader::groups()->assignUser('group-uuid', 'user-uuid');
Teamleader::groups()->assignTeam('group-uuid', 'team-uuid');
Teamleader::groups()->unassignUser('group-uuid', 'user-uuid');
Teamleader::groups()->unassignTeam('group-uuid', 'team-uuid');
```

---

## Helper Methods

### `forProject(string $projectId)`

```php
$groups = Teamleader::groups()->forProject('project-uuid');
```

---

## Filters

| Filter       | Type   | Description            |
|--------------|--------|------------------------|
| `ids`        | array  | Filter by group UUIDs  |
| `project_id` | string | Filter by project UUID |

---

## Error Handling

```php
use InvalidArgumentException;

// Missing required field
try {
    Teamleader::groups()->create(['project_id' => 'uuid']); // missing title
} catch (InvalidArgumentException $e) {
    // 'title is required'
}

// Wrong billing_method format on update
try {
    Teamleader::groups()->update('uuid', ['billing_method' => 'time_and_materials']); // wrong — needs object
} catch (InvalidArgumentException $e) {
    // 'billing_method must be an object with value and update_strategy'
}

// Invalid delete strategy
try {
    Teamleader::groups()->delete('uuid', 'archive');
} catch (InvalidArgumentException $e) {
    // 'Invalid delete strategy. Must be one of: ...'
}
```

---

## Related Resources

- [[Projects]] — Parent project
- [[Project-Lines]] — List all lines in a project including groups
- [[Project-Tasks]] — Tasks that live inside groups
- [[Materials]] — Materials that live inside groups

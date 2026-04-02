# Project Lines

Unified view of tasks, materials, and groups within a project.

## Overview

Project Lines provides a single endpoint to list all line items in a project (tasks, materials, and groups) and to
manage group membership. For creating, updating, or deleting individual items use their dedicated resources.

Access via `Teamleader::projectLines()`.

> **`list()` throws without `project_id`** — it is required in every call.
>
> **Filter structure is nested:** optional filters go inside a `filter` key, not at the top level.
>
> **Fluent interface is available** — chain `forProject()`, filter methods, then call `get()`.

## Endpoint

`projects-v2/projectLines`

## Capabilities

| Capability  | Supported                                                                        |
|-------------|----------------------------------------------------------------------------------|
| Pagination  | ❌ Not supported                                                                  |
| Filtering   | ✅ Supported (`project_id` required; optional `filter.types`, `filter.assignees`) |
| Sorting     | ❌ Not supported                                                                  |
| Sideloading | ❌ Not supported                                                                  |
| Creation    | ❌ Use `projectTasks`, `materials`, `groups`                                      |
| Update      | ❌ Use specific resources                                                         |
| Deletion    | ❌ Use specific resources                                                         |

---

## Methods

### `list(array $filters = [], array $options = [])`

`project_id` is required at the top level. Optional sub-filters go inside `filter`:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All lines for a project
$lines = Teamleader::projectLines()->list(['project_id' => 'project-uuid']);

// Filter by line type
$lines = Teamleader::projectLines()->list([
    'project_id' => 'project-uuid',
    'filter'     => ['types' => ['nextgenTask', 'nextgenMaterial']],
]);

// Filter by assignee
$lines = Teamleader::projectLines()->list([
    'project_id' => 'project-uuid',
    'filter'     => ['assignees' => [['type' => 'user', 'id' => 'user-uuid']]],
]);

// Get unassigned lines (pass null for assignees)
$lines = Teamleader::projectLines()->list([
    'project_id' => 'project-uuid',
    'filter'     => ['assignees' => null],
]);
```

**Valid line types (validated):** `nextgenTask`, `nextgenMaterial`, `nextgenProjectGroup`

---

### `addToGroup(string $lineId, string $groupId)`

Moves a task or material into a group. Groups cannot be nested.

```php
Teamleader::projectLines()->addToGroup('line-uuid', 'group-uuid');
```

---

### `removeFromGroup(string $lineId)`

Removes a task or material from its current group.

```php
Teamleader::projectLines()->removeFromGroup('line-uuid');
```

---

## Fluent Interface

Chain filters and terminate with `get()`:

```php
// Tasks only
$tasks = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->tasksOnly()
    ->get();

// Materials only
$materials = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->materialsOnly()
    ->get();

// Groups only
$groups = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->groupsOnly()
    ->get();

// Assigned to a user
$userLines = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->assignedTo('user', 'user-uuid')
    ->get();

// Unassigned
$unassigned = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->unassigned();    // unassigned() terminates directly — no ->get() needed
```

**Fluent methods:
** `forProject()`, `tasksOnly()`, `materialsOnly()`, `groupsOnly()`, `ofType(array)`, `assignedTo(string $type, string $id)`, `unassigned()`, `get()`

---

## Response Structure

```php
[
    'data' => [
        [
            'line'  => ['type' => 'nextgenTask',     'id' => 'task-uuid'],
            'group' => ['type' => 'nextgenProjectGroup', 'id' => 'group-uuid'], // null if not in a group
        ],
        [
            'line'  => ['type' => 'nextgenMaterial', 'id' => 'material-uuid'],
            'group' => null,
        ],
    ],
]
```

`addToGroup()` and `removeFromGroup()` return empty (HTTP 204).

---

## Usage Examples

### Organise tasks into a group

```php
// Get all ungrouped tasks
$unassigned = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->tasksOnly()
    ->unassigned();

// Create a group
$group = Teamleader::groups()->create([
    'project_id' => 'project-uuid',
    'title'      => 'Phase 1',
]);

// Move tasks into the group
foreach ($unassigned['data'] as $line) {
    Teamleader::projectLines()->addToGroup($line['line']['id'], $group['data']['id']);
}
```

### Move a line to a different group

```php
Teamleader::projectLines()->removeFromGroup('line-uuid');
Teamleader::projectLines()->addToGroup('line-uuid', 'new-group-uuid');
```

---

## Error Handling

```php
use InvalidArgumentException;

// Missing project_id
try {
    Teamleader::projectLines()->list([]);
} catch (InvalidArgumentException $e) {
    // 'project_id is required. Use forProject() method or provide project_id in filters.'
}

// Invalid line type in filter
try {
    Teamleader::projectLines()->list([
        'project_id' => 'uuid',
        'filter'     => ['types' => ['task']],  // wrong — must be 'nextgenTask'
    ]);
} catch (InvalidArgumentException $e) {
    // "Invalid line type: task. Must be one of: nextgenTask, nextgenMaterial, nextgenProjectGroup"
}
```

---

## Related Resources

- [[Projects]] — Parent project
- [[Groups]] — Create and manage groups
- [[Project-Tasks]] — Create and manage tasks
- [[Materials]] — Create and manage materials

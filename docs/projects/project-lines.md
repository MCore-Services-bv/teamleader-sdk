# Project Lines

Manage project lines (tasks, materials, and groups) in Teamleader Focus projects. This resource allows you to list project lines with filtering and manage group assignments.

## Resource Path
`packages/mcore-services/teamleader-sdk/src/Resources/Projects/ProjectLines.php`

## API Endpoint
- Base: `projects-v2/projectLines`
- Methods: `.list`, `.addToGroup`, `.removeFromGroup`

## Overview

Project lines represent the work items within a project, including:
- **Tasks** (`nextgenTask`) - Work items to be completed
- **Materials** (`nextgenMaterial`) - Resources or materials used
- **Groups** (`nextgenProjectGroup`) - Organizational containers for tasks and materials

## Available Methods

### Listing Project Lines

#### `list(array $filters = [], array $options = []): array`

List all lines for a specific project with optional filtering.

**Parameters:**
- `$filters['project_id']` (string, required) - The project UUID
- `$filters['filter']['types']` (array, optional) - Filter by line types
- `$filters['filter']['assignees']` (array|null, optional) - Filter by assignees (null for unassigned)

**Example:**
```php
// Get all lines for a project
$lines = $teamleader->projectLines()->list([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6'
]);

// Get only tasks
$tasks = $teamleader->projectLines()->list([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'filter' => [
        'types' => ['nextgenTask']
    ]
]);

// Get lines assigned to specific user
$userLines = $teamleader->projectLines()->list([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'filter' => [
        'assignees' => [
            [
                'type' => 'user',
                'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'
            ]
        ]
    ]
]);

// Get unassigned lines
$unassigned = $teamleader->projectLines()->list([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'filter' => [
        'assignees' => null
    ]
]);
```

**Response:**
```php
[
    'data' => [
        [
            'line' => [
                'type' => 'nextgenTask',
                'id' => 'a14a464d-320a-49bb-b6ee-b510c7f4f66c'
            ],
            'group' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'nextgenProjectGroup'
            ]
        ],
        [
            'line' => [
                'type' => 'nextgenMaterial',
                'id' => 'b25b575e-431c-5abb-c6ff-c621d8e5g77d'
            ],
            'group' => null  // Not in a group
        ]
    ]
]
```

### Group Management

#### `addToGroup(string $lineId, string $groupId): array`

Add an existing task or material to a group.

**Parameters:**
- `$lineId` (string, required) - The UUID of the task or material (may not be a group)
- `$groupId` (string, required) - The UUID of the group

**Example:**
```php
$result = $teamleader->projectLines()->addToGroup(
    'a14a464d-320a-49bb-b6ee-b510c7f4f66c',  // line ID
    '0daf76e6-5141-4fb0-866f-01916a873a38'   // group ID
);
```

**Response:**
204 No Content on success

---

#### `removeFromGroup(string $lineId): array`

Remove a task or material from the group it is currently in.

**Parameters:**
- `$lineId` (string, required) - The UUID of the task or material (may not be a group)

**Example:**
```php
$result = $teamleader->projectLines()->removeFromGroup(
    'a14a464d-320a-49bb-b6ee-b510c7f4f66c'
);
```

**Response:**
204 No Content on success

## Fluent Interface Methods

The ProjectLines resource provides a fluent interface for building queries:

### `forProject(string $projectId): self`

Set the project to query lines from.

```php
$lines = $teamleader->projectLines()
    ->forProject('49b403be-a32e-0901-9b1c-25214f9027c6')
    ->get();
```

### `ofType(array $types): self`

Filter by specific line types.

```php
$lines = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->ofType(['nextgenTask', 'nextgenMaterial'])
    ->get();
```

### `tasksOnly(): self`

Filter to show only tasks.

```php
$tasks = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->tasksOnly()
    ->get();
```

### `materialsOnly(): self`

Filter to show only materials.

```php
$materials = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->materialsOnly()
    ->get();
```

### `groupsOnly(): self`

Filter to show only groups.

```php
$groups = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->groupsOnly()
    ->get();
```

### `assignedTo(string $type, string $id): self`

Filter by assignee.

**Parameters:**
- `$type` - Either 'user' or 'team'
- `$id` - The UUID of the user or team

```php
$lines = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->assignedTo('user', 'user-uuid')
    ->get();
```

### `unassigned(?string $projectId = null): array`

Get unassigned lines. Can be used with or without prior fluent calls.

```php
// Direct usage
$unassigned = $teamleader->projectLines()->unassigned('project-uuid');

// With fluent interface
$unassigned = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->tasksOnly()
    ->unassigned();
```

### `get(): array`

Execute the query with all pending filters.

```php
$lines = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->tasksOnly()
    ->assignedTo('user', 'user-uuid')
    ->get();
```

## Line Types

Valid line types for filtering:
- `nextgenTask` - Tasks
- `nextgenMaterial` - Materials
- `nextgenProjectGroup` - Groups

## Assignee Types

Valid assignee types:
- `user` - Individual user
- `team` - Team

## Common Usage Patterns

### Get all tasks for a project
```php
$tasks = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->tasksOnly()
    ->get();
```

### Get unassigned materials
```php
$unassigned = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->materialsOnly()
    ->unassigned();
```

### Get lines assigned to a specific team
```php
$teamLines = $teamleader->projectLines()
    ->forProject('project-uuid')
    ->assignedTo('team', 'team-uuid')
    ->get();
```

### Organize lines into groups
```php
// Add a task to a group
$teamleader->projectLines()->addToGroup(
    'task-id',
    'group-id'
);

// Remove from group later
$teamleader->projectLines()->removeFromGroup('task-id');
```

### Complex filtering
```php
$lines = $teamleader->projectLines()->list([
    'project_id' => 'project-uuid',
    'filter' => [
        'types' => ['nextgenTask', 'nextgenMaterial'],
        'assignees' => [
            ['type' => 'user', 'id' => 'user-uuid-1'],
            ['type' => 'team', 'id' => 'team-uuid-1']
        ]
    ]
]);
```

## Response Structure

### List Response
```php
[
    'data' => [
        [
            'line' => [
                'type' => 'nextgenTask|nextgenMaterial|nextgenProjectGroup',
                'id' => 'line-uuid'
            ],
            'group' => [  // nullable - null if not in a group
                'id' => 'group-uuid',
                'type' => 'nextgenProjectGroup'
            ]
        ]
    ]
]
```

### addToGroup / removeFromGroup Response
These methods return a 204 No Content response on success.

## Resource Capabilities

- ❌ Create (use Tasks or Materials resources)
- ❌ Update
- ❌ Delete
- ✅ List with filtering
- ✅ Group management
- ❌ Pagination
- ❌ Sorting
- ❌ Sideloading
- ✅ Fluent interface

## Notes

- The `project_id` parameter is always required for listing lines
- Lines can be organized into groups for better project structure
- Only tasks and materials can be added to groups (not other groups)
- Removing a line from a group doesn't delete the line, just ungroups it
- To get unassigned lines, pass `null` as the assignees filter value
- For creating, updating, or deleting individual lines, use the respective Tasks or Materials resources

## Error Handling

```php
use InvalidArgumentException;

try {
    // project_id is required
    $lines = $teamleader->projectLines()->list();
} catch (InvalidArgumentException $e) {
    // Handle missing project_id
}

try {
    // Invalid line type
    $lines = $teamleader->projectLines()->list([
        'project_id' => 'uuid',
        'filter' => ['types' => ['invalid_type']]
    ]);
} catch (InvalidArgumentException $e) {
    // Handle invalid line type
}
```

## Related Resources

- **Tasks** - Create and manage individual tasks
- **Materials** - Create and manage individual materials
- **Projects** - Manage the parent projects

## API Documentation

For complete API documentation, visit:
https://developer.teamleader.eu/api/projects-v2/projectlines

# Project Lines

Manage project lines (unified view of tasks, materials, and groups).

## Overview

The Project Lines resource provides a unified interface to view and manage all line items within a project: tasks, materials, and groups. It also handles group membership operations.

**Note:** Use specific resources (ProjectTasks, Materials, Groups) for creation. This resource is primarily for listing and group operations.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Filtering](#filtering)
- [Usage Examples](#usage-examples)
- [Related Resources](#related-resources)

## Endpoint

`projects-v2/projectLines`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (use specific resources)
- **Update**: ❌ Not Supported (use specific resources)
- **Deletion**: ❌ Not Supported (use specific resources)

## Available Methods

### `list()`

**Required:** `project_id` must be provided.

```php
// Get all lines for a project
$lines = Teamleader::projectLines()->list([
    'project_id' => 'project-uuid'
]);

// Filter by type
$lines = Teamleader::projectLines()->list([
    'project_id' => 'project-uuid',
    'filter' => [
        'types' => ['nextgenTask', 'nextgenMaterial']
    ]
]);

// Filter by assignee
$lines = Teamleader::projectLines()->list([
    'project_id' => 'project-uuid',
    'filter' => [
        'assignees' => [
            ['type' => 'user', 'id' => 'user-uuid']
        ]
    ]
]);

// Get unassigned lines
$lines = Teamleader::projectLines()->list([
    'project_id' => 'project-uuid',
    'filter' => [
        'assignees' => null
    ]
]);
```

### `addToGroup()`

Add a task or material to a group.

```php
Teamleader::projectLines()->addToGroup('line-uuid', 'group-uuid');
```

### `removeFromGroup()`

Remove a task or material from its current group.

```php
Teamleader::projectLines()->removeFromGroup('line-uuid');
```

## Filtering

**Line Types:**
- `nextgenTask`
- `nextgenMaterial`
- `nextgenProjectGroup`

**Filters:**
- `types` - Array of line types
- `assignees` - Array of assignee objects, or `null` for unassigned

## Fluent Interface

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Chain filters
$tasks = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->tasksOnly()
    ->get();

$materials = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->materialsOnly()
    ->get();

$groups = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->groupsOnly()
    ->get();

// Filter by assignee
$userLines = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->assignedTo('user', 'user-uuid')
    ->get();

// Get unassigned
$unassigned = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->unassigned()
    ->get();
```

## Response Structure

```json
{
  "data": [
    {
      "line": {
        "type": "nextgenTask",
        "id": "task-uuid"
      },
      "group": {
        "type": "nextgenProjectGroup",
        "id": "group-uuid"
      }
    },
    {
      "line": {
        "type": "nextgenMaterial",
        "id": "material-uuid"
      },
      "group": null
    }
  ]
}
```

## Usage Examples

### Get All Project Lines

```php
$lines = Teamleader::projectLines()->forProject('project-uuid')->get();

foreach ($lines['data'] as $line) {
    $type = $line['line']['type'];
    $id = $line['line']['id'];
    $inGroup = isset($line['group']) ? 'Yes' : 'No';
    
    echo "{$type}: {$id} (In Group: {$inGroup})\n";
}
```

### Organize Tasks into Groups

```php
$projectId = 'project-uuid';

// Get all tasks
$tasks = Teamleader::projectLines()
    ->forProject($projectId)
    ->tasksOnly()
    ->get();

// Create group
$group = Teamleader::groups()->create([
    'project_id' => $projectId,
    'title' => 'Development Phase'
]);

$groupId = $group['data']['id'];

// Add tasks to group
foreach ($tasks['data'] as $task) {
    if ($task['group'] === null) {
        Teamleader::projectLines()->addToGroup(
            $task['line']['id'],
            $groupId
        );
    }
}
```

### Move Lines Between Groups

```php
$lineId = 'line-uuid';
$newGroupId = 'new-group-uuid';

// Remove from current group
Teamleader::projectLines()->removeFromGroup($lineId);

// Add to new group
Teamleader::projectLines()->addToGroup($lineId, $newGroupId);
```

### Get Unassigned Work

```php
$unassigned = Teamleader::projectLines()
    ->forProject('project-uuid')
    ->unassigned()
    ->get();

echo "Unassigned items:\n";
foreach ($unassigned['data'] as $line) {
    echo "- {$line['line']['type']}: {$line['line']['id']}\n";
}
```

## Best Practices

1. **Always Provide project_id**: Required for all operations
```php
// Good
$lines = Teamleader::projectLines()->forProject('uuid')->get();

// Will fail
$lines = Teamleader::projectLines()->list();
```

2. **Use Fluent Interface**: More readable
```php
// Good
$tasks = Teamleader::projectLines()
    ->forProject('uuid')
    ->tasksOnly()
    ->get();

// Works but less clear
$tasks = Teamleader::projectLines()->list([
    'project_id' => 'uuid',
    'filter' => ['types' => ['nextgenTask']]
]);
```

3. **Use Specific Resources for Creation**: Don't try to create via projectLines
```php
// Good - use specific resource
$task = Teamleader::projectTasks()->create([...]);

// Wrong - not supported
$task = Teamleader::projectLines()->create([...]);
```

## Related Resources

- **[Projects](projects.md)** - Parent projects
- **[Project Tasks](project-tasks.md)** - Create and manage tasks
- **[Materials](materials.md)** - Create and manage materials
- **[Groups](groups.md)** - Create and manage groups

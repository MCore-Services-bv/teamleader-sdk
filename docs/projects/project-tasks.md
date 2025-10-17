# Project Tasks

Manage tasks in Teamleader Focus projects.

## Overview

The Project Tasks resource provides full CRUD operations for managing tasks within projects. Tasks represent work items that can be assigned to users or teams, tracked for time, and billed to customers using various billing methods.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`projects-v2/tasks`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get tasks with optional filtering and pagination.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all tasks
$tasks = Teamleader::projectTasks()->list();

// Filter by IDs
$tasks = Teamleader::projectTasks()->list([
    'ids' => ['task-uuid-1', 'task-uuid-2']
]);

// With pagination
$tasks = Teamleader::projectTasks()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);
```

### `info()`

Get detailed information about a specific task.

```php
$task = Teamleader::projectTasks()->info('task-uuid');
```

### `create()`

Create a new task.

**Required fields:**
- `project_id` (string): Project UUID
- `title` (string): Task title
- `billing_method` (string): Billing method

**Billing methods:**
- `user_rate` - Bill at user's hourly rate
- `work_type_rate` - Bill at work type rate
- `custom_rate` - Bill at custom rate (requires `custom_rate`)
- `fixed_price` - Fixed price (requires `fixed_price`)
- `parent_fixed_price` - Use parent group's fixed price
- `non_billable` - Not billable

```php
// Basic task
$task = Teamleader::projectTasks()->create([
    'project_id' => 'project-uuid',
    'title' => 'Design homepage mockup',
    'billing_method' => 'user_rate'
]);

// Complete task
$task = Teamleader::projectTasks()->create([
    'project_id' => 'project-uuid',
    'title' => 'Implement payment gateway',
    'description' => 'Integrate Stripe payment processing',
    'billing_method' => 'custom_rate',
    'custom_rate' => [
        'amount' => 75.00,
        'currency' => 'EUR',
        'time_unit' => 'hours'
    ],
    'status' => 'to_do',
    'estimated_duration' => [
        'value' => 8,
        'unit' => 'hours'
    ]
]);
```

### `update()`

Update an existing task.

```php
Teamleader::projectTasks()->update('task-uuid', [
    'title' => 'Updated Task Title',
    'status' => 'in_progress',
    'description' => 'Updated description'
]);
```

### `delete()`

Delete a task.

**Delete strategies:**
- `unlink_time_tracking` (default) - Unlink time trackings
- `delete_time_tracking` - Delete all time trackings

```php
// Delete with default strategy
Teamleader::projectTasks()->delete('task-uuid');

// Delete and remove time trackings
Teamleader::projectTasks()->delete('task-uuid', 'delete_time_tracking');
```

### `assign()`

Assign a user or team to a task.

```php
// Assign user
Teamleader::projectTasks()->assign('task-uuid', 'user', 'user-uuid');

// Assign team
Teamleader::projectTasks()->assign('task-uuid', 'team', 'team-uuid');
```

### `unassign()`

Remove an assignee from a task.

```php
Teamleader::projectTasks()->unassign('task-uuid', 'user', 'user-uuid');
```

### `duplicate()`

Duplicate a task (without time trackings).

```php
$newTask = Teamleader::projectTasks()->duplicate('task-uuid');
```

## Helper Methods

### Status Filters

```php
// Get tasks by status
$todo = Teamleader::projectTasks()->todo();
$inProgress = Teamleader::projectTasks()->inProgress();
$onHold = Teamleader::projectTasks()->onHold();
$done = Teamleader::projectTasks()->done();
```

## Filtering

Available filters:

- `ids` - Array of task UUIDs

**Example:**
```php
$tasks = Teamleader::projectTasks()->list([
    'ids' => ['task-1', 'task-2', 'task-3']
]);
```

## Response Structure

```json
{
  "id": "task-uuid",
  "project": {
    "type": "nextgenProject",
    "id": "project-uuid"
  },
  "title": "Design homepage mockup",
  "description": "Create mockup for new homepage design",
  "status": "in_progress",
  "billing_method": "user_rate",
  "estimated_duration": {
    "value": 8.0,
    "unit": "hours"
  },
  "assignees": [
    {
      "type": "user",
      "id": "user-uuid"
    }
  ],
  "created_at": "2024-01-15T10:00:00+00:00",
  "updated_at": "2024-01-20T14:30:00+00:00"
}
```

## Usage Examples

### Create Task with Estimate

```php
$task = Teamleader::projectTasks()->create([
    'project_id' => 'project-uuid',
    'title' => 'Write API documentation',
    'description' => 'Document all endpoints with examples',
    'billing_method' => 'user_rate',
    'estimated_duration' => [
        'value' => 16,
        'unit' => 'hours'
    ],
    'status' => 'to_do'
]);

// Assign to developer
Teamleader::projectTasks()->assign(
    $task['data']['id'],
    'user',
    'developer-uuid'
);
```

### Track Task Progress

```php
$taskId = 'task-uuid';

// Start task
Teamleader::projectTasks()->update($taskId, [
    'status' => 'in_progress'
]);

// Complete task
Teamleader::projectTasks()->update($taskId, [
    'status' => 'done'
]);
```

### Get User's Tasks

```php
// Note: You'll need to filter this in your code
// as the API doesn't support assignee filtering directly
$allTasks = Teamleader::projectTasks()->list();
$userId = 'user-uuid';

$userTasks = array_filter($allTasks['data'], function($task) use ($userId) {
    if (!isset($task['assignees'])) return false;
    
    foreach ($task['assignees'] as $assignee) {
        if ($assignee['type'] === 'user' && $assignee['id'] === $userId) {
            return true;
        }
    }
    return false;
});
```

### Duplicate Task for New Sprint

```php
$originalTask = Teamleader::projectTasks()->info('task-uuid');

$newTask = Teamleader::projectTasks()->duplicate($originalTask['data']['id']);

// Update for new sprint
Teamleader::projectTasks()->update($newTask['data']['id'], [
    'title' => $originalTask['data']['title'] . ' - Sprint 2',
    'status' => 'to_do'
]);
```

## Common Use Cases

### Sprint Planning

```php
$sprintTasks = [
    ['title' => 'User authentication', 'hours' => 8],
    ['title' => 'Database schema', 'hours' => 4],
    ['title' => 'API endpoints', 'hours' => 12],
];

foreach ($sprintTasks as $taskData) {
    $task = Teamleader::projectTasks()->create([
        'project_id' => $projectId,
        'title' => $taskData['title'],
        'billing_method' => 'user_rate',
        'estimated_duration' => [
            'value' => $taskData['hours'],
            'unit' => 'hours'
        ],
        'status' => 'to_do'
    ]);
    
    echo "Created: {$taskData['title']}\n";
}
```

### Task Status Report

```php
$tasks = Teamleader::projectTasks()->list();

$stats = [
    'to_do' => 0,
    'in_progress' => 0,
    'on_hold' => 0,
    'done' => 0
];

$totalEstimated = 0;

foreach ($tasks['data'] as $task) {
    $stats[$task['status']]++;
    
    if (isset($task['estimated_duration'])) {
        $totalEstimated += $task['estimated_duration']['value'];
    }
}

echo "Task Statistics:\n";
foreach ($stats as $status => $count) {
    echo "  " . ucwords(str_replace('_', ' ', $status)) . ": {$count}\n";
}
echo "\nTotal Estimated Hours: {$totalEstimated}\n";
```

## Best Practices

1. **Always Set Billing Method**: Required for creation
```php
$task = Teamleader::projectTasks()->create([
    'project_id' => 'uuid',
    'title' => 'Task',
    'billing_method' => 'user_rate'  // Required
]);
```

2. **Use Estimates**: Help with project planning
```php
'estimated_duration' => [
    'value' => 8,
    'unit' => 'hours'
]
```

3. **Assign Tasks**: Make it clear who's responsible
```php
$task = Teamleader::projectTasks()->create($data);
Teamleader::projectTasks()->assign($task['data']['id'], 'user', 'user-uuid');
```

4. **Update Status**: Keep task status current
```php
Teamleader::projectTasks()->update($taskId, ['status' => 'in_progress']);
```

5. **Choose Appropriate Delete Strategy**:
```php
// Keep time trackings
Teamleader::projectTasks()->delete($taskId, 'unlink_time_tracking');

// Remove everything
Teamleader::projectTasks()->delete($taskId, 'delete_time_tracking');
```

## Error Handling

```php
try {
    $task = Teamleader::projectTasks()->create([
        'project_id' => 'project-uuid',
        'title' => 'New Task',
        'billing_method' => 'user_rate'
    ]);
    
} catch (\InvalidArgumentException $e) {
    Log::error('Invalid task data: ' . $e->getMessage());
} catch (\Exception $e) {
    Log::error('Failed to create task: ' . $e->getMessage());
}
```

## Related Resources

- **[Projects](projects.md)** - Parent projects
- **[Groups](groups.md)** - Organize tasks into groups
- **[Project Lines](project-lines.md)** - View all project lines
- **[Materials](materials.md)** - Project materials
- **[Time Tracking](../time-tracking/time-tracking.md)** - Track time on tasks

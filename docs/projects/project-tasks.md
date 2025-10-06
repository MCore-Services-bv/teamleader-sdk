# Project Tasks

Manage tasks in Teamleader Focus projects (New Projects API v2). This resource provides complete CRUD operations for managing project tasks, including assignment, status tracking, time estimation, billing, and budgeting.

## Endpoint

`projects-v2/tasks`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported (by IDs)
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported (with strategy options)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of tasks with optional filtering.

**Parameters:**
- `filters` (array): Array of filters to apply
    - `ids` (array): Array of task UUIDs to filter by
- `options` (array): Pagination options
    - `page_size` (int): Number of items per page (default: 20)
    - `page_number` (int): Page number (default: 1)

**Example:**
```php
// List all tasks
$tasks = $teamleader->projectTasks()->list();

// Filter by specific IDs
$tasks = $teamleader->projectTasks()->list([
    'ids' => ['task-uuid-1', 'task-uuid-2']
]);

// With pagination
$tasks = $teamleader->projectTasks()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

### `info()`

Get detailed information about a specific task.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
$task = $teamleader->projectTasks()->info('task-uuid-here');
```

### `create()`

Create a new task. Only `title` and `project_id` are required; all other properties are optional.

**Required Parameters:**
- `project_id` (string): Project UUID
- `title` (string): Task title

**Optional Parameters:**
- `group_id` (string): Group UUID (if omitted, task is not added to a group)
- `work_type_id` (string): Work type UUID (required if billing_method is `work_type_rate`)
- `task_type_id` (string): DEPRECATED - Use `work_type_id` instead
- `description` (string): Task description
- `billing_method` (string): One of: `user_rate`, `work_type_rate`, `custom_rate`, `fixed_price`, `parent_fixed_price`, `non_billable`
- `fixed_price` (object): Fixed price with `amount` and `currency`
- `external_budget` (object): External budget with `amount` and `currency`
- `internal_budget` (object): Internal budget with `amount` and `currency`
- `custom_rate` (object): Custom rate with `amount` and `currency`
- `start_date` (string): Start date in Y-m-d format (e.g., "2023-01-18")
- `end_date` (string): End date in Y-m-d format (e.g., "2023-03-22")
- `time_estimated` (object): Estimated time with `value` and `unit` (hours, minutes, or seconds)
- `assignees` (array): Array of assignee objects with `type` (user/team) and `id`

**Example:**
```php
$task = $teamleader->projectTasks()->create([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'title' => 'Write API documentation',
    'description' => 'Complete documentation for all endpoints',
    'billing_method' => 'user_rate',
    'start_date' => '2023-01-18',
    'end_date' => '2023-03-22',
    'time_estimated' => [
        'value' => 480,
        'unit' => 'minutes'
    ],
    'assignees' => [
        [
            'type' => 'user',
            'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'
        ]
    ]
]);
```

**Example with Fixed Price:**
```php
$task = $teamleader->projectTasks()->create([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'title' => 'Design new logo',
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 1500.00,
        'currency' => 'EUR'
    ],
    'external_budget' => [
        'amount' => 1500.00,
        'currency' => 'EUR'
    ]
]);
```

### `update()`

Update an existing task. All attributes except `id` are optional. Providing `null` will clear that value from the task (for properties that are nullable).

**Parameters:**
- `id` (string): Task UUID
- `data` (array): Array of data to update (same structure as create, all optional)

**Example:**
```php
// Update task status
$task = $teamleader->projectTasks()->update('task-uuid', [
    'status' => 'in_progress'
]);

// Update multiple fields
$task = $teamleader->projectTasks()->update('task-uuid', [
    'title' => 'Updated task title',
    'description' => 'New description',
    'end_date' => '2023-04-15',
    'time_estimated' => [
        'value' => 600,
        'unit' => 'minutes'
    ]
]);

// Clear a nullable field by setting it to null
$task = $teamleader->projectTasks()->update('task-uuid', [
    'description' => null
]);
```

### `delete()`

Delete a task with a strategy for handling associated time tracking.

**Parameters:**
- `id` (string): Task UUID
- `deleteStrategy` (string): One of:
    - `unlink_time_tracking` (default): Unlinks time tracking from task
    - `delete_time_tracking`: Deletes associated time tracking

**Example:**
```php
// Delete task and unlink time tracking (default)
$teamleader->projectTasks()->delete('task-uuid');

// Delete task and delete all time tracking
$teamleader->projectTasks()->delete('task-uuid', 'delete_time_tracking');
```

### `assign()`

Assign a user or team to a task.

**Parameters:**
- `taskId` (string): Task UUID
- `assigneeType` (string): Either "user" or "team"
- `assigneeId` (string): UUID of the user or team

**Example:**
```php
// Assign a user
$teamleader->projectTasks()->assign(
    'task-uuid',
    'user',
    'user-uuid'
);

// Assign a team
$teamleader->projectTasks()->assign(
    'task-uuid',
    'team',
    'team-uuid'
);
```

### `unassign()`

Unassign a user or team from a task.

**Parameters:**
- `taskId` (string): Task UUID
- `assigneeType` (string): Either "user" or "team"
- `assigneeId` (string): UUID of the user or team

**Example:**
```php
// Unassign a user
$teamleader->projectTasks()->unassign(
    'task-uuid',
    'user',
    'user-uuid'
);

// Unassign a team
$teamleader->projectTasks()->unassign(
    'task-uuid',
    'team',
    'team-uuid'
);
```

### `duplicate()`

Duplicate a task without its time trackings.

**Parameters:**
- `originId` (string): UUID of the task to duplicate

**Example:**
```php
$newTask = $teamleader->projectTasks()->duplicate('original-task-uuid');

// Returns the new task's ID
echo $newTask['data']['id'];
```

### `byIds()`

Get tasks by specific IDs (convenience method).

**Parameters:**
- `ids` (array): Array of task UUIDs

**Example:**
```php
$tasks = $teamleader->projectTasks()->byIds([
    'task-uuid-1',
    'task-uuid-2',
    'task-uuid-3'
]);
```

## Convenience Methods

### `assignUser()` / `assignTeam()`

Simplified methods for assigning users or teams.

**Example:**
```php
// Assign a user
$teamleader->projectTasks()->assignUser('task-uuid', 'user-uuid');

// Assign a team
$teamleader->projectTasks()->assignTeam('task-uuid', 'team-uuid');
```

### `unassignUser()` / `unassignTeam()`

Simplified methods for unassigning users or teams.

**Example:**
```php
// Unassign a user
$teamleader->projectTasks()->unassignUser('task-uuid', 'user-uuid');

// Unassign a team
$teamleader->projectTasks()->unassignTeam('task-uuid', 'team-uuid');
```

### `updateStatus()`

Quick method to update just the task status.

**Example:**
```php
// Mark task as in progress
$teamleader->projectTasks()->updateStatus('task-uuid', 'in_progress');

// Mark task as done
$teamleader->projectTasks()->updateStatus('task-uuid', 'done');

// Put task on hold
$teamleader->projectTasks()->updateStatus('task-uuid', 'on_hold');
```

## Status Values

Tasks can have one of the following status values:
- `to_do` - Task is planned but not started
- `in_progress` - Task is currently being worked on
- `on_hold` - Task is paused
- `done` - Task is completed

## Billing Methods

Tasks support the following billing methods:
- `user_rate` - Bill based on the user's hourly rate
- `work_type_rate` - Bill based on the work type rate (requires `work_type_id`)
- `custom_rate` - Bill using a custom rate specified for this task
- `fixed_price` - Bill a fixed price for the entire task
- `parent_fixed_price` - Use the parent project's fixed price
- `non_billable` - Task is not billable

## Billing Status

Tasks automatically track their billing status:
- `not_billable` - Task is marked as non-billable
- `not_billed` - Task is billable but not yet billed
- `partially_billed` - Task has been partially billed
- `fully_billed` - Task has been completely billed

## Supported Currencies

The following currency codes are supported for monetary amounts:
`BAM`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CZK`, `DKK`, `EUR`, `GBP`, `INR`, `ISK`, `JPY`, `MAD`, `MXN`, `NOK`, `PEN`, `PLN`, `RON`, `SEK`, `TRY`, `USD`, `ZAR`

## Time Estimation

Time can be estimated using the following units:
- `hours` - Time in hours
- `minutes` - Time in minutes
- `seconds` - Time in seconds

**Example:**
```php
'time_estimated' => [
    'value' => 8,
    'unit' => 'hours'
]
```

## Response Structure

### Create Response
```json
{
  "data": {
    "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
    "type": "task"
  }
}
```

### Info/List Response
```json
{
  "data": {
    "id": "ff19a113-50ba-4afc-9fff-2e5c5c5a5485",
    "project": {
      "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
      "type": "project"
    },
    "group": {
      "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
      "type": "nextgenProjectGroup"
    },
    "work_type": {
      "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
      "type": "workType"
    },
    "status": "in_progress",
    "title": "Write API documentation",
    "description": "Complete API documentation",
    "billing_method": "user_rate",
    "billing_status": "not_billable",
    "assignees": [
      {
        "assignee": {
          "type": "user",
          "id": "66abace2-62af-0836-a927-fe3f44b9b47b"
        },
        "assign_type": "manual"
      }
    ],
    "start_date": "2023-01-18",
    "end_date": "2023-03-22",
    "time_estimated": {
      "value": 60,
      "unit": "seconds"
    },
    "time_tracked": {
      "value": 3600,
      "unit": "seconds"
    },
    "custom_fields": []
  }
}
```

## Complete Usage Examples

### Creating a Comprehensive Task
```php
$task = $teamleader->projectTasks()->create([
    'project_id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'group_id' => '0185968b-2c9e-73fd-9ce1-a12c0979783b',
    'title' => 'Develop new feature',
    'description' => 'Implement user authentication system',
    'work_type_id' => '0f517e20-2e76-4684-8d6c-3334f6d7148c',
    'billing_method' => 'work_type_rate',
    'start_date' => '2023-01-18',
    'end_date' => '2023-03-22',
    'time_estimated' => [
        'value' => 40,
        'unit' => 'hours'
    ],
    'external_budget' => [
        'amount' => 5000.00,
        'currency' => 'EUR'
    ],
    'internal_budget' => [
        'amount' => 3000.00,
        'currency' => 'EUR'
    ],
    'assignees' => [
        [
            'type' => 'user',
            'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'
        ]
    ]
]);
```

### Task Workflow Management
```php
// Create a task
$task = $teamleader->projectTasks()->create([
    'project_id' => 'project-uuid',
    'title' => 'Code review'
]);

$taskId = $task['data']['id'];

// Assign team members
$teamleader->projectTasks()->assignUser($taskId, 'reviewer-uuid');

// Start the task
$teamleader->projectTasks()->updateStatus($taskId, 'in_progress');

// Update progress
$teamleader->projectTasks()->update($taskId, [
    'description' => 'Reviewing authentication module'
]);

// Complete the task
$teamleader->projectTasks()->updateStatus($taskId, 'done');
```

### Managing Task Assignments
```php
$taskId = 'task-uuid';

// Add multiple assignees
$teamleader->projectTasks()->assignUser($taskId, 'user-1-uuid');
$teamleader->projectTasks()->assignUser($taskId, 'user-2-uuid');
$teamleader->projectTasks()->assignTeam($taskId, 'team-uuid');

// Later, reassign
$teamleader->projectTasks()->unassignUser($taskId, 'user-1-uuid');
$teamleader->projectTasks()->assignUser($taskId, 'user-3-uuid');
```

### Duplicating and Modifying Tasks
```php
// Duplicate an existing task
$originalTask = 'original-task-uuid';
$duplicatedTask = $teamleader->projectTasks()->duplicate($originalTask);

// Modify the duplicated task
$newTaskId = $duplicatedTask['data']['id'];
$teamleader->projectTasks()->update($newTaskId, [
    'title' => 'Updated task copy',
    'start_date' => '2023-04-01',
    'end_date' => '2023-05-01'
]);
```

## Notes

- The `task_type_id` field is deprecated. Use `work_type_id` instead.
- When using `work_type_rate` as the billing method, `work_type_id` cannot be null.
- Time tracking is measured in seconds and rounded to the nearest minute.
- The `time_tracked` field represents the total of all time tracked for the task.
- Custom fields values can be included when creating or updating tasks.
- The `margin_percentage` field returns null if the user does not have access to "Costs on projects".
- When deleting a task, consider whether time tracking should be unlinked or deleted.
- Monetary amounts should include both `amount` (as a decimal number) and `currency` (ISO code).

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;

try {
    $task = $teamleader->projectTasks()->create([
        'project_id' => 'invalid-uuid',
        'title' => 'Test task'
    ]);
} catch (ValidationException $e) {
    echo "Validation failed: " . $e->getMessage();
    print_r($e->getErrors());
}
```

## Related Resources

- **Projects** - Parent resource for tasks
- **Materials** - Similar project resource for materials/products
- **Time Tracking** - Track time spent on tasks (not covered in this SDK yet)
- **Work Types** - Define types of work for billing purposes

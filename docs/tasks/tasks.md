# Tasks

Manage tasks in Teamleader Focus.

## Overview

The Tasks resource provides full CRUD (Create, Read, Update, Delete) operations for managing task records in your Teamleader system. Tasks can be assigned to users, linked to customers (companies or contacts), associated with milestones, scheduled in calendars, and tracked for completion.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [complete()](#complete)
    - [reopen()](#reopen)
    - [schedule()](#schedule)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`tasks`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all tasks with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, sort)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all tasks
$tasks = Teamleader::tasks()->list();

// Get tasks for a specific user
$tasks = Teamleader::tasks()->list([
    'user_id' => 'user-uuid'
]);

// With pagination
$tasks = Teamleader::tasks()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// With sorting
$tasks = Teamleader::tasks()->list([], [
    'sort' => 'due_at',
    'sort_order' => 'asc'
]);
```

### `info()`

Get detailed information about a specific task.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
// Get task information
$task = Teamleader::tasks()->info('task-uuid');
```

### `create()`

Create a new task.

**Required Fields:**
- `title` (string): Task title
- `due_on` (string): Due date (YYYY-MM-DD format)
- `work_type_id` (string): Work type UUID

**Optional Fields:**
- `description` (string): Task description
- `user_id` (string): Assigned user UUID
- `milestone_id` (string): Milestone UUID
- `customer` (object): Customer object with type and id
- `estimated_duration` (int): Estimated duration in minutes

**Example:**
```php
$task = Teamleader::tasks()->create([
    'title' => 'Follow up with client',
    'due_on' => '2025-12-31',
    'work_type_id' => 'work-type-uuid',
    'description' => 'Discuss renewal options',
    'user_id' => 'user-uuid',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

### `update()`

Update an existing task.

**Parameters:**
- `id` (string): Task UUID
- `data` (array): Fields to update

**Example:**
```php
$task = Teamleader::tasks()->update('task-uuid', [
    'title' => 'Updated task title',
    'description' => 'Updated description'
]);
```

### `delete()`

Delete a task.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
$result = Teamleader::tasks()->delete('task-uuid');
```

### `complete()`

Mark a task as complete.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
$result = Teamleader::tasks()->complete('task-uuid');
```

### `reopen()`

Reopen a task that had been marked as complete.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
$result = Teamleader::tasks()->reopen('task-uuid');
```

### `schedule()`

Schedule a task in your calendar.

**Parameters:**
- `id` (string): Task UUID
- `startsAt` (string): Start datetime (ISO 8601 format)
- `endsAt` (string): End datetime (ISO 8601 format)

**Example:**
```php
$result = Teamleader::tasks()->schedule(
    'task-uuid',
    '2025-10-20T09:00:00+00:00',
    '2025-10-20T10:00:00+00:00'
);
```

## Helper Methods

### Status Filtering

```php
// Get completed tasks
$completed = Teamleader::tasks()->completed();

// Get incomplete tasks
$incomplete = Teamleader::tasks()->incomplete();

// Get scheduled tasks
$scheduled = Teamleader::tasks()->scheduled();
```

### User Filtering

```php
// Get tasks for specific user
$userTasks = Teamleader::tasks()->forUser('user-uuid');

// Get unassigned tasks
$unassigned = Teamleader::tasks()->unassigned();
```

### Customer Filtering

```php
// Get tasks for a company
$tasks = Teamleader::tasks()->forCustomer('company', 'company-uuid');

// Get tasks for a contact
$tasks = Teamleader::tasks()->forCustomer('contact', 'contact-uuid');
```

### Milestone Filtering

```php
// Get tasks for a milestone
$tasks = Teamleader::tasks()->forMilestone('milestone-uuid');
```

### Date Filtering

```php
// Get tasks due between dates
$tasks = Teamleader::tasks()->dueBetween('2025-01-01', '2025-12-31');
```

### Search

```php
// Search tasks by term (searches in description)
$tasks = Teamleader::tasks()->search('client meeting');
```

### ID Filtering

```php
// Get specific tasks by IDs
$tasks = Teamleader::tasks()->byIds(['uuid1', 'uuid2', 'uuid3']);
```

## Filters

Available filters for the `list()` method:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of task UUIDs |
| `user_id` | string | Filter by assigned user UUID |
| `milestone_id` | string | Filter by milestone UUID |
| `customer` | object | Filter by customer (type and id) |
| `completed` | boolean | Filter by completion status |
| `scheduled` | boolean | Filter by scheduled status |
| `due_from` | string | Tasks due from date (YYYY-MM-DD) |
| `due_by` | string | Tasks due by date (YYYY-MM-DD) |
| `term` | string | Search term (searches in description) |

### Customer Filter Structure

```php
[
    'customer' => [
        'type' => 'company', // or 'contact'
        'id' => 'uuid-here'
    ]
]
```

## Sorting

Available sort fields:

| Field | Description |
|-------|-------------|
| `due_at` | Sort by due date/time |

**Example:**
```php
$tasks = Teamleader::tasks()->list([], [
    'sort' => 'due_at',
    'sort_order' => 'asc' // or 'desc'
]);
```

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'task-uuid',
            'title' => 'Follow up with client',
            'description' => 'Discuss renewal options',
            'completed' => false,
            'due_at' => '2025-12-31T23:59:59+00:00',
            'estimated_duration' => 3600,
            'work_type' => [
                'type' => 'workType',
                'id' => 'work-type-uuid'
            ],
            'assignee' => [
                'type' => 'user',
                'id' => 'user-uuid'
            ],
            'customer' => [
                'type' => 'company',
                'id' => 'company-uuid'
            ],
            'milestone' => [
                'type' => 'milestone',
                'id' => 'milestone-uuid'
            ],
            'scheduled_at' => [
                'starts_at' => '2025-10-20T09:00:00+00:00',
                'ends_at' => '2025-10-20T10:00:00+00:00'
            ]
        ]
    ],
    'meta' => [
        'page' => [
            'size' => 20,
            'number' => 1
        ],
        'matches' => 42
    ]
]
```

### Info Response

```php
[
    'data' => [
        'id' => 'task-uuid',
        'title' => 'Follow up with client',
        'description' => 'Discuss renewal options',
        'completed' => false,
        'due_at' => '2025-12-31T23:59:59+00:00',
        'estimated_duration' => 3600,
        'work_type' => [
            'type' => 'workType',
            'id' => 'work-type-uuid'
        ],
        'assignee' => [
            'type' => 'user',
            'id' => 'user-uuid'
        ],
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'milestone' => [
            'type' => 'milestone',
            'id' => 'milestone-uuid'
        ]
    ]
]
```

## Usage Examples

### Create and Assign Task

```php
// Create a task and assign to user
$task = Teamleader::tasks()->create([
    'title' => 'Prepare quarterly report',
    'due_on' => '2025-10-31',
    'work_type_id' => 'admin-work-type-uuid',
    'user_id' => 'user-uuid',
    'description' => 'Compile Q3 financial data',
    'estimated_duration' => 240 // 4 hours in minutes
]);
```

### Create Task for Customer

```php
// Create task linked to a company
$task = Teamleader::tasks()->create([
    'title' => 'Follow up on proposal',
    'due_on' => '2025-10-25',
    'work_type_id' => 'sales-work-type-uuid',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'description' => 'Check if they received the quotation'
]);
```

### Schedule Task in Calendar

```php
// Create task
$task = Teamleader::tasks()->create([
    'title' => 'Client meeting preparation',
    'due_on' => '2025-10-20',
    'work_type_id' => 'work-type-uuid'
]);

// Schedule it in calendar
Teamleader::tasks()->schedule(
    $task['data']['id'],
    '2025-10-20T14:00:00+00:00',
    '2025-10-20T15:00:00+00:00'
);
```

### Get Overdue Tasks

```php
// Get incomplete tasks due before today
$overdue = Teamleader::tasks()->list([
    'completed' => false,
    'due_by' => date('Y-m-d')
]);
```

### Complete Task Workflow

```php
// Get task
$task = Teamleader::tasks()->info('task-uuid');

// Mark as complete
if (!$task['data']['completed']) {
    Teamleader::tasks()->complete($task['data']['id']);
}

// If needed, reopen it
Teamleader::tasks()->reopen($task['data']['id']);
```

### Get User's Tasks for Today

```php
$today = date('Y-m-d');
$tasks = Teamleader::tasks()->list([
    'user_id' => 'user-uuid',
    'due_from' => $today,
    'due_by' => $today,
    'completed' => false
]);
```

## Common Use Cases

### 1. Task Management Dashboard

```php
// Get incomplete tasks for logged-in user
$myTasks = Teamleader::tasks()->forUser($currentUserId)
    ->list(['completed' => false]);

// Get overdue tasks
$overdue = Teamleader::tasks()->list([
    'user_id' => $currentUserId,
    'completed' => false,
    'due_by' => date('Y-m-d', strtotime('-1 day'))
]);

// Get upcoming tasks (next 7 days)
$upcoming = Teamleader::tasks()->dueBetween(
    date('Y-m-d'),
    date('Y-m-d', strtotime('+7 days'))
);
```

### 2. Project Task Management

```php
// Get all tasks for a milestone
$milestoneTasks = Teamleader::tasks()->forMilestone('milestone-uuid');

// Track completion
$completed = array_filter($milestoneTasks['data'], fn($task) => $task['completed']);
$completionRate = count($completed) / count($milestoneTasks['data']) * 100;

echo "Project is {$completionRate}% complete";
```

### 3. Customer Follow-up System

```php
// Create follow-up task after deal
$deal = Teamleader::deals()->info('deal-uuid');

if ($deal['data']['lead']['customer']['type'] === 'company') {
    Teamleader::tasks()->create([
        'title' => 'Follow up on deal: ' . $deal['data']['title'],
        'due_on' => date('Y-m-d', strtotime('+3 days')),
        'work_type_id' => 'sales-work-type-uuid',
        'user_id' => $deal['data']['responsible_user']['id'],
        'customer' => [
            'type' => 'company',
            'id' => $deal['data']['lead']['customer']['id']
        ],
        'description' => 'Check satisfaction and discuss next steps'
    ]);
}
```

### 4. Task Reassignment

```php
// Get tasks for user leaving the team
$tasks = Teamleader::tasks()->forUser('old-user-uuid')
    ->list(['completed' => false]);

// Reassign to new user
foreach ($tasks['data'] as $task) {
    Teamleader::tasks()->update($task['id'], [
        'user_id' => 'new-user-uuid'
    ]);
}
```

## Best Practices

### 1. Always Set Due Dates

```php
// Good: Clear deadline
$task = Teamleader::tasks()->create([
    'title' => 'Review contract',
    'due_on' => '2025-10-25',
    'work_type_id' => 'work-type-uuid'
]);

// Avoid: Using far-future dates as placeholders
```

### 2. Use Descriptive Titles

```php
// Good: Clear and actionable
$task = Teamleader::tasks()->create([
    'title' => 'Prepare Q4 budget proposal for Acme Corp',
    'due_on' => '2025-10-31',
    'work_type_id' => 'work-type-uuid'
]);

// Less helpful: Vague title
// 'title' => 'Budget stuff'
```

### 3. Link Tasks to Context

```php
// Good: Link to customer and milestone
$task = Teamleader::tasks()->create([
    'title' => 'Website design review',
    'due_on' => '2025-10-30',
    'work_type_id' => 'work-type-uuid',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'milestone_id' => 'milestone-uuid',
    'description' => 'Review mockups and provide feedback'
]);
```

### 4. Include Estimated Duration

```php
// Helps with capacity planning
$task = Teamleader::tasks()->create([
    'title' => 'Code review',
    'due_on' => '2025-10-22',
    'work_type_id' => 'work-type-uuid',
    'estimated_duration' => 120 // 2 hours
]);
```

### 5. Handle Pagination for Large Lists

```php
function getAllTasks(): array
{
    $allTasks = [];
    $page = 1;
    $pageSize = 100;

    do {
        $response = Teamleader::tasks()->list([], [
            'page_size' => $pageSize,
            'page_number' => $page
        ]);

        $allTasks = array_merge($allTasks, $response['data']);
        $page++;
    } while (count($response['data']) === $pageSize);

    return $allTasks;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $task = Teamleader::tasks()->create([
        'title' => 'New task',
        'due_on' => '2025-10-20',
        'work_type_id' => 'work-type-uuid'
    ]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Task validation failed', [
            'errors' => $e->getDetails()
        ]);
    } elseif ($e->getCode() === 404) {
        // Work type not found
        Log::error('Work type does not exist');
    }
}

// Safely complete a task
try {
    Teamleader::tasks()->complete('task-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::warning('Task not found or already deleted');
    }
}
```

## Related Resources

- [Time Tracking](../time-tracking/time-tracking.md) - Track time spent on tasks
- [Users](../general/users.md) - Assign tasks to users
- [Work Types](../general/work-types.md) - Categorize tasks by work type
- [Projects](../projects/projects.md) - Link tasks to projects
- [Calendar Events](../calendar/events.md) - Schedule tasks in calendar

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

# Tasks

Manage tasks in Teamleader Focus. This resource provides complete CRUD operations for managing tasks, including special operations like completing, reopening, and scheduling tasks in your calendar.

## Endpoint

`tasks`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of tasks with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$tasks = $teamleader->tasks()->list([
    'completed' => false,
    'user_id' => 'user-uuid'
]);
```

### `info()`

Get detailed information about a specific task.

**Parameters:**
- `id` (string): Task UUID
- `includes` (array|string): Relations to include (not used for tasks)

**Example:**
```php
$task = $teamleader->tasks()->info('task-uuid-here');
```

### `create()`

Create a new task.

**Required Parameters:**
- `title` (string): Task title
- `due_on` (string): Due date in YYYY-MM-DD format
- `work_type_id` (string): Work type UUID

**Optional Parameters:**
- `description` (string): Task description
- `milestone_id` (string): Milestone UUID (old projects module)
- `project_id` (string): Project UUID (new projects module)
- `deal_id` (string): Deal UUID
- `ticket_id` (string): Ticket UUID
- `estimated_duration` (object): Estimated duration with unit and value
- `assignee` (object|null): Assignee object or null for unassigned
- `customer` (object): Customer object with type and id
- `custom_fields` (array): Array of custom field objects

**Example:**
```php
$task = $teamleader->tasks()->create([
    'title' => 'Review code changes',
    'due_on' => '2025-02-15',
    'work_type_id' => '32665afd-1818-0ed3-9e18-a603a3a21b95',
    'description' => 'Review the latest pull request',
    'estimated_duration' => [
        'unit' => 'min',
        'value' => 60
    ],
    'assignee' => [
        'type' => 'user',
        'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'
    ],
    'customer' => [
        'type' => 'contact',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ]
]);
```

### `update()`

Update an existing task.

**Parameters:**
- `id` (string): Task UUID
- `data` (array): Array of data to update

**Example:**
```php
$task = $teamleader->tasks()->update('task-uuid', [
    'title' => 'Updated task title',
    'due_on' => '2025-02-20'
]);
```

### `delete()`

Delete a task.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
$result = $teamleader->tasks()->delete('task-uuid');
```

### `complete()`

Mark a task as complete.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
$result = $teamleader->tasks()->complete('task-uuid');
```

### `reopen()`

Reopen a task that had been marked as complete.

**Parameters:**
- `id` (string): Task UUID

**Example:**
```php
$result = $teamleader->tasks()->reopen('task-uuid');
```

### `schedule()`

Schedule a task in your calendar (creates a calendar event).

**Parameters:**
- `id` (string): Task UUID
- `startsAt` (string): Start datetime in ISO 8601 format
- `endsAt` (string): End datetime in ISO 8601 format

**Example:**
```php
$event = $teamleader->tasks()->schedule(
    'task-uuid',
    '2025-02-15T09:00:00+00:00',
    '2025-02-15T10:00:00+00:00'
);
```

### `forUser()`

Get all tasks for a specific user (or team member).

**Parameters:**
- `userId` (string): User UUID
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->forUser('user-uuid');
```

### `unassigned()`

Get all unassigned tasks.

**Parameters:**
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->unassigned();
```

### `completed()`

Get all completed tasks.

**Parameters:**
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->completed();
```

### `incomplete()`

Get all incomplete tasks.

**Parameters:**
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->incomplete();
```

### `scheduled()`

Get all scheduled tasks (tasks that have been scheduled in calendar).

**Parameters:**
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->scheduled();
```

### `forMilestone()`

Get tasks for a specific milestone.

**Parameters:**
- `milestoneId` (string): Milestone UUID
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->forMilestone('milestone-uuid');
```

### `forCustomer()`

Get tasks for a specific customer.

**Parameters:**
- `customerType` (string): Type of customer ('contact' or 'company')
- `customerId` (string): UUID of the customer
- `options` (array): Additional options

**Example:**
```php
// Get tasks for a contact
$tasks = $teamleader->tasks()->forCustomer('contact', 'contact-uuid');

// Get tasks for a company
$tasks = $teamleader->tasks()->forCustomer('company', 'company-uuid');
```

### `dueBetween()`

Get tasks due within a specific date range.

**Parameters:**
- `dueFrom` (string): Start date (YYYY-MM-DD)
- `dueBy` (string): End date (YYYY-MM-DD)
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->dueBetween(
    '2025-02-01',
    '2025-02-28'
);
```

### `search()`

Search tasks by term (searches in description).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->search('website design');
```

### `byIds()`

Get tasks by specific IDs.

**Parameters:**
- `ids` (array): Array of task UUIDs
- `options` (array): Additional options

**Example:**
```php
$tasks = $teamleader->tasks()->byIds([
    'task-uuid-1',
    'task-uuid-2'
]);
```

## Available Filters

When using the `list()` method, you can apply the following filters:

- **ids**: Array of task UUIDs
- **user_id**: Filter by assigned user (or team member). Use `null` for unassigned tasks
- **milestone_id**: Filter by milestone UUID (old projects module)
- **completed**: Filter by completion status (boolean)
- **scheduled**: Filter by scheduled status (boolean)
- **due_by**: Filter tasks due by this date (YYYY-MM-DD)
- **due_from**: Filter tasks due from this date (YYYY-MM-DD)
- **term**: Search term (searches in description)
- **customer**: Filter by customer (object with `type` and `id`)

## Sorting

Tasks can be sorted by the following field:

- **name**: Sort by task name

**Example:**
```php
$tasks = $teamleader->tasks()->list([], [
    'sort' => [
        'field' => 'name',
        'order' => 'asc'
    ]
]);
```

## Pagination

Tasks support pagination through the standard pagination options:

**Example:**
```php
$tasks = $teamleader->tasks()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

## Assignee Types

When assigning tasks, use one of the following types:

- `user` - A Teamleader user
- `team` - A team

## Customer Types

When linking tasks to customers, use one of the following types:

- `contact` - Link to a contact
- `company` - Link to a company

## Priority Levels

Tasks support the following priority levels:

- `A` - Highest priority
- `B` - High priority
- `C` - Medium priority
- `D` - Low priority

## Complete Examples

### Creating a Full Task

```php
$task = $teamleader->tasks()->create([
    'title' => 'Complete project documentation',
    'description' => 'Write comprehensive documentation for the project',
    'due_on' => '2025-03-01',
    'work_type_id' => '32665afd-1818-0ed3-9e18-a603a3a21b95',
    'project_id' => '0185aa33-603c-7fd5-bf0d-5bd83d503b96',
    'estimated_duration' => [
        'unit' => 'min',
        'value' => 120
    ],
    'assignee' => [
        'type' => 'user',
        'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'
    ],
    'customer' => [
        'type' => 'company',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ],
    'custom_fields' => [
        [
            'id' => 'bf6765de-56eb-40ec-ad14-9096c5dc5fe1',
            'value' => 'High importance'
        ]
    ]
]);
```

### Getting This Week's Tasks for a User

```php
$startOfWeek = now()->startOfWeek()->format('Y-m-d');
$endOfWeek = now()->endOfWeek()->format('Y-m-d');

$tasks = $teamleader->tasks()->forUser('user-uuid', [
    'filters' => [
        'due_from' => $startOfWeek,
        'due_by' => $endOfWeek,
        'completed' => false
    ]
]);
```

### Complete Task Workflow

```php
// Create a task
$task = $teamleader->tasks()->create([
    'title' => 'Review quarterly report',
    'due_on' => '2025-02-28',
    'work_type_id' => 'work-type-uuid',
    'assignee' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ]
]);

$taskId = $task['data']['id'];

// Schedule it in calendar
$event = $teamleader->tasks()->schedule(
    $taskId,
    '2025-02-25T14:00:00+00:00',
    '2025-02-25T16:00:00+00:00'
);

// Mark as complete when done
$teamleader->tasks()->complete($taskId);

// If needed, reopen it
$teamleader->tasks()->reopen($taskId);
```

### Advanced Filtering

```php
// Get incomplete, scheduled tasks for a user due this month
$tasks = $teamleader->tasks()->list([
    'user_id' => 'user-uuid',
    'completed' => false,
    'scheduled' => true,
    'due_from' => '2025-02-01',
    'due_by' => '2025-02-28'
], [
    'sort' => [
        'field' => 'name',
        'order' => 'asc'
    ],
    'page_size' => 50
]);
```

### Working with Milestones and Projects

```php
// Get tasks for a milestone (old projects module)
$milestoneTasks = $teamleader->tasks()->forMilestone('milestone-uuid');

// Create a task for a project (new projects module)
$projectTask = $teamleader->tasks()->create([
    'title' => 'Design mockups',
    'due_on' => '2025-03-15',
    'work_type_id' => 'work-type-uuid',
    'project_id' => 'project-uuid'
]);
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

### Info Response

```json
{
    "data": {
        "id": "6fac0bf0-e803-424e-af67-76863a3d7d16",
        "title": "Review code changes",
        "description": "Review the latest pull request",
        "completed": false,
        "completed_at": null,
        "due_on": "2016-02-04",
        "added_at": "2016-02-04T16:44:33+00:00",
        "estimated_duration": {
            "unit": "min",
            "value": 60
        },
        "work_type": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "workType"
        },
        "assignee": {
            "type": "user",
            "id": "66abace2-62af-0836-a927-fe3f44b9b47b"
        },
        "customer": {
            "type": "contact",
            "id": "f29abf48-337d-44b4-aad4-585f5277a456"
        },
        "milestone": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "milestone"
        },
        "deal": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "deal"
        },
        "project": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "project"
        },
        "ticket": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "ticket"
        },
        "custom_fields": [
            {
                "definition": {
                    "type": "customFieldDefinition",
                    "id": "bf6765de-56eb-40ec-ad14-9096c5dc5fe1"
                },
                "value": "092980616"
            }
        ],
        "priority": "A"
    }
}
```

### Schedule Response

When scheduling a task, it returns a calendar event:

```json
{
    "data": {
        "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
        "type": "event"
    }
}
```

## Notes

- All date fields (`due_on`, `due_from`, `due_by`) must be in YYYY-MM-DD format
- All datetime fields (`starts_at`, `ends_at` for scheduling) must be in ISO 8601 format with timezone offset
- Use `null` for `user_id` filter to get unassigned tasks
- Use `null` for `assignee` field to unassign a task
- The `milestone_id` field is only available for users with access to the old projects module
- The `project_id` field is only available for users with access to the new projects module
- Completing a task sets the `completed` field to `true` and populates `completed_at`
- Reopening a task sets `completed` to `false` and clears `completed_at`
- Scheduling a task creates a calendar event linked to the task
- Custom fields must reference existing custom field definitions
- Tasks can be linked to customers (contacts or companies), milestones, projects, deals, or tickets
- The `term` filter searches only in the task description, not the title
- Priority levels (A, B, C, D) determine the importance of tasks

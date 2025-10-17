# Projects

Manage projects in Teamleader Focus (New Projects API v2).

## Overview

The Projects resource provides full CRUD operations for managing modern projects in Teamleader. This is the new Projects API (v2) that replaces the legacy projects system. Projects can contain tasks, materials, and groups, with flexible billing methods and comprehensive project management features.

**Note:** This is the new Projects API. For legacy projects, see [Legacy Projects](legacy/projects.md).

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [duplicate()](#duplicate)
    - [close()](#close)
    - [reopen()](#reopen)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Sideloading](#sideloading)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`projects-v2/projects`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ✅ Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all projects with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, sort, includes)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all projects
$projects = Teamleader::projects()->list();

// Filter by status
$projects = Teamleader::projects()->list([
    'status' => ['open']
]);

// With pagination
$projects = Teamleader::projects()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// With sorting
$projects = Teamleader::projects()->list([], [
    'sort' => [
        ['field' => 'title', 'order' => 'asc']
    ]
]);
```

### `info()`

Get detailed information about a specific project.

**Parameters:**
- `id` (string): Project UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Basic info
$project = Teamleader::projects()->info('project-uuid');

// With includes
$project = Teamleader::projects()->info('project-uuid', 'legacy_project,custom_fields');

// Using fluent interface
$project = Teamleader::projects()
    ->with(['legacy_project', 'custom_fields'])
    ->info('project-uuid');
```

### `create()`

Create a new project.

**Required fields:**
- `title` (string): Project title
- `customer` (object): Customer information
    - `type` (string): `contact` or `company`
    - `id` (string): Customer UUID

**Optional fields:**
- `description` (string): Project description
- `starts_on` (string): Start date (YYYY-MM-DD)
- `due_on` (string): Due date (YYYY-MM-DD)
- `budget` (object): Budget information
    - `amount` (decimal): Budget amount
    - `currency` (string): Currency code
- `department_id` (string): Department UUID
- `purchase_order_number` (string): PO number
- `custom_fields` (array): Custom field values

**Example:**
```php
// Basic project
$project = Teamleader::projects()->create([
    'title' => 'Website Redesign',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Complete project
$project = Teamleader::projects()->create([
    'title' => 'Website Redesign & Development',
    'description' => 'Complete website overhaul with new design and features',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'starts_on' => '2024-02-01',
    'due_on' => '2024-06-30',
    'budget' => [
        'amount' => 50000.00,
        'currency' => 'EUR'
    ],
    'department_id' => 'dept-uuid',
    'purchase_order_number' => 'PO-2024-001'
]);
```

### `update()`

Update an existing project.

**Parameters:**
- `id` (string): Project UUID
- `data` (array): Fields to update

**Example:**
```php
Teamleader::projects()->update('project-uuid', [
    'title' => 'Updated Project Title',
    'due_on' => '2024-08-31',
    'description' => 'Updated project scope'
]);
```

### `delete()`

Delete a project.

**Parameters:**
- `id` (string): Project UUID
- `deleteStrategy` (string, optional): How to handle tasks and time trackings
    - `unlink_tasks_and_time_trackings` (default)
    - `delete_tasks_and_time_trackings`
    - `delete_tasks_unlink_time_trackings`

**Example:**
```php
// Delete with default strategy
Teamleader::projects()->delete('project-uuid');

// Delete and remove all tasks
Teamleader::projects()->delete('project-uuid', 'delete_tasks_and_time_trackings');
```

### `duplicate()`

Duplicate a project with all its structure.

**Parameters:**
- `id` (string): Project UUID to duplicate
- `title` (string): Title for the new project

**Example:**
```php
$newProject = Teamleader::projects()->duplicate(
    'original-project-uuid',
    'Website Redesign - Phase 2'
);
```

### `close()`

Close a project.

**Parameters:**
- `id` (string): Project UUID
- `closingStrategy` (string, optional): How to handle tasks and materials
    - `mark_tasks_and_materials_as_done`
    - `none` (default)

**Example:**
```php
// Close without marking tasks as done
Teamleader::projects()->close('project-uuid');

// Close and mark all tasks as done
Teamleader::projects()->close('project-uuid', 'mark_tasks_and_materials_as_done');
```

### `reopen()`

Reopen a closed project.

**Parameters:**
- `id` (string): Project UUID

**Example:**
```php
Teamleader::projects()->reopen('project-uuid');
```

## Helper Methods

### Status-based Methods

```php
// Get open projects
$open = Teamleader::projects()->open();

// Get closed projects
$closed = Teamleader::projects()->closed();

// Get on-hold projects
$onHold = Teamleader::projects()->onHold();
```

### Customer-based Methods

```php
// Get projects for a company
$projects = Teamleader::projects()->forCompany('company-uuid');

// Get projects for a contact
$projects = Teamleader::projects()->forContact('contact-uuid');

// Generic customer method
$projects = Teamleader::projects()->forCustomer('customer-uuid', 'company');
```

### Search and Filter Methods

```php
// Search by term
$projects = Teamleader::projects()->search('website');

// Get updated projects
$projects = Teamleader::projects()->updatedSince('2024-01-01T00:00:00+00:00');

// Get overdue projects
$projects = Teamleader::projects()->overdue();
```

## Filtering

Available filters:

- `ids` - Array of project UUIDs
- `status` - Array of statuses: `open`, `closed`, `on_hold`
- `customer` - Customer object with `type` and `id`
- `term` - Search in title and description
- `updated_since` - ISO 8601 datetime
- `due_before` - Date filter (YYYY-MM-DD)
- `due_after` - Date filter (YYYY-MM-DD)

**Example:**
```php
$projects = Teamleader::projects()->list([
    'status' => ['open', 'on_hold'],
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'due_after' => '2024-01-01'
]);
```

## Sorting

Available sort fields:

- `title`
- `starts_on`
- `due_on`
- `created_at`
- `updated_at`

**Example:**
```php
$projects = Teamleader::projects()->list([], [
    'sort' => [
        ['field' => 'due_on', 'order' => 'asc'],
        ['field' => 'title', 'order' => 'asc']
    ]
]);
```

## Sideloading

Available includes:

- `legacy_project` - Legacy project information (if migrated)
- `custom_fields` - Custom field values

**Example:**
```php
$projects = Teamleader::projects()->list([], [
    'includes' => 'legacy_project,custom_fields'
]);
```

## Response Structure

### Project Object

```json
{
  "id": "project-uuid",
  "title": "Website Redesign",
  "description": "Complete website overhaul",
  "status": "open",
  "customer": {
    "type": "company",
    "id": "company-uuid"
  },
  "starts_on": "2024-02-01",
  "due_on": "2024-06-30",
  "budget": {
    "amount": 50000.00,
    "currency": "EUR"
  },
  "department": {
    "type": "department",
    "id": "dept-uuid"
  },
  "purchase_order_number": "PO-2024-001",
  "created_at": "2024-01-15T10:00:00+00:00",
  "updated_at": "2024-01-20T14:30:00+00:00"
}
```

## Usage Examples

### Create Project with Budget

```php
$project = Teamleader::projects()->create([
    'title' => 'Mobile App Development',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'starts_on' => date('Y-m-d'),
    'due_on' => date('Y-m-d', strtotime('+6 months')),
    'budget' => [
        'amount' => 75000.00,
        'currency' => 'EUR'
    ],
    'description' => 'Native iOS and Android app development'
]);

echo "Project created: {$project['data']['title']}\n";
```

### Get Overdue Projects

```php
$overdue = Teamleader::projects()->list([
    'status' => ['open'],
    'due_before' => date('Y-m-d')
]);

foreach ($overdue['data'] as $project) {
    echo "Overdue: {$project['title']} (Due: {$project['due_on']})\n";
}
```

### Complete Project Workflow

```php
// 1. Create project
$project = Teamleader::projects()->create([
    'title' => 'Q1 Marketing Campaign',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'starts_on' => '2024-01-01',
    'due_on' => '2024-03-31'
]);

$projectId = $project['data']['id'];

// 2. Add tasks (see ProjectTasks documentation)
// 3. Add materials (see Materials documentation)
// 4. Track time
// 5. Close when complete

Teamleader::projects()->close($projectId, 'mark_tasks_and_materials_as_done');
```

### Duplicate for New Phase

```php
$originalProject = Teamleader::projects()->info('project-uuid');

$newProject = Teamleader::projects()->duplicate(
    $originalProject['data']['id'],
    $originalProject['data']['title'] . ' - Phase 2'
);

// Update dates for new phase
Teamleader::projects()->update($newProject['data']['id'], [
    'starts_on' => date('Y-m-d'),
    'due_on' => date('Y-m-d', strtotime('+3 months'))
]);
```

### Monthly Project Report

```php
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$projects = Teamleader::projects()->list([
    'updated_since' => $startOfMonth . 'T00:00:00+00:00'
]);

$stats = [
    'total' => count($projects['data']),
    'open' => 0,
    'closed' => 0,
    'overdue' => 0
];

foreach ($projects['data'] as $project) {
    $stats[$project['status']]++;
    
    if ($project['status'] === 'open' && 
        isset($project['due_on']) && 
        $project['due_on'] < date('Y-m-d')) {
        $stats['overdue']++;
    }
}

echo "Project Statistics for " . date('F Y') . ":\n";
foreach ($stats as $key => $value) {
    echo "  " . ucfirst($key) . ": {$value}\n";
}
```

## Common Use Cases

### Project Dashboard

```php
$openProjects = Teamleader::projects()->open();
$today = date('Y-m-d');

$dashboard = [
    'total_open' => count($openProjects['data']),
    'due_this_week' => 0,
    'overdue' => 0,
    'on_track' => 0
];

$nextWeek = date('Y-m-d', strtotime('+7 days'));

foreach ($openProjects['data'] as $project) {
    if (!isset($project['due_on'])) continue;
    
    if ($project['due_on'] < $today) {
        $dashboard['overdue']++;
    } elseif ($project['due_on'] <= $nextWeek) {
        $dashboard['due_this_week']++;
    } else {
        $dashboard['on_track']++;
    }
}

print_r($dashboard);
```

### Bulk Update Projects

```php
$projects = Teamleader::projects()->forCompany('company-uuid');

foreach ($projects['data'] as $project) {
    if ($project['status'] === 'open') {
        // Extend due date by 2 weeks
        $newDueDate = date('Y-m-d', strtotime($project['due_on'] . ' +2 weeks'));
        
        Teamleader::projects()->update($project['id'], [
            'due_on' => $newDueDate
        ]);
        
        echo "Extended: {$project['title']}\n";
    }
}
```

## Best Practices

1. **Always Set Due Dates**: Projects with due dates are easier to track
```php
$project = Teamleader::projects()->create([
    'title' => 'Project Name',
    'customer' => ['type' => 'company', 'id' => 'uuid'],
    'starts_on' => date('Y-m-d'),
    'due_on' => date('Y-m-d', strtotime('+30 days'))  // Always set
]);
```

2. **Use Descriptive Titles**: Make projects easy to identify
```php
// Good
'title' => 'Website Redesign - Acme Corp - Q1 2024'

// Avoid
'title' => 'Project 1'
```

3. **Close Projects Properly**: Use the appropriate closing strategy
```php
// Mark tasks as done when closing
Teamleader::projects()->close($projectId, 'mark_tasks_and_materials_as_done');
```

4. **Use Pagination**: Don't load all projects at once
```php
$page = 1;
do {
    $projects = Teamleader::projects()->list([], [
        'page_size' => 50,
        'page_number' => $page
    ]);
    
    // Process projects...
    
    $page++;
} while (count($projects['data']) === 50);
```

5. **Filter Before Loading**: Use filters to reduce data transfer
```php
// Good - only get what you need
$projects = Teamleader::projects()->list([
    'status' => ['open'],
    'customer' => ['type' => 'company', 'id' => 'uuid']
]);

// Avoid - filtering in PHP
$all = Teamleader::projects()->list();
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $project = Teamleader::projects()->create([
        'title' => 'New Project',
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ]);
    
} catch (\InvalidArgumentException $e) {
    // Validation error
    Log::error('Invalid project data: ' . $e->getMessage());
    
} catch (\Exception $e) {
    // API error
    Log::error('Failed to create project: ' . $e->getMessage());
}

// Handle delete with validation
try {
    Teamleader::projects()->delete('project-uuid', 'invalid_strategy');
    
} catch (\InvalidArgumentException $e) {
    // Invalid delete strategy
    echo "Error: " . $e->getMessage();
}
```

## Related Resources

- **[Project Tasks](project-tasks.md)** - Manage tasks within projects
- **[Materials](materials.md)** - Manage project materials
- **[Groups](groups.md)** - Organize tasks and materials into groups
- **[Project Lines](project-lines.md)** - View all project lines
- **[External Parties](external-parties.md)** - Manage external stakeholders
- **[Time Tracking](../time-tracking/time-tracking.md)** - Track time on projects
- **[Legacy Projects](legacy/projects.md)** - Old projects API
- **[Companies](../crm/companies.md)** - Project customers

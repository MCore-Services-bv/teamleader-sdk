# Legacy Milestones

Manage legacy project milestones in Teamleader Focus.

## Overview

Legacy Milestones are project phases in the old Teamleader Projects API. Each milestone represents a phase of work with its own timeline, billing method, and tasks.

**Note:** This is part of the legacy Projects API. For new projects, use [Project Groups](../groups.md) in the new API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Usage Examples](#usage-examples)
- [Related Resources](#related-resources)

## Endpoint

`milestones`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`, `info()`, `create()`, `update()`, `delete()`

Standard CRUD operations.

### `close()`

Close a milestone (closes all open tasks, keeps meetings open). Closing the last milestone closes the project.

```php
Teamleader::legacyMilestones()->close('milestone-uuid');
```

### `open()`

Open or reopen a milestone. If the project is closed, it will be reopened.

```php
Teamleader::legacyMilestones()->open('milestone-uuid');
```

## Helper Methods

```php
// Get milestones for a project
$milestones = Teamleader::legacyMilestones()->forProject('project-uuid');
```

## Filtering

- `ids` - Array of milestone UUIDs
- `project_id` - Project UUID
- `status` - open, closed
- `due_before` - Date (YYYY-MM-DD)
- `due_after` - Date (YYYY-MM-DD)
- `term` - Search milestone title

## Sorting

- `due_on`
- `name`
- `created_at`

```php
$milestones = Teamleader::legacyMilestones()->list([], [
    'sort' => [
        ['field' => 'due_on', 'order' => 'asc']
    ]
]);
```

## Usage Examples

### Create Milestone

**Required fields:**
- `project_id`
- `name`
- `due_on`
- `responsible_user_id`
- `billing_method`

```php
$milestone = Teamleader::legacyMilestones()->create([
    'project_id' => 'project-uuid',
    'name' => 'Phase 1: Discovery',
    'starts_on' => '2024-01-01',
    'due_on' => '2024-02-28',
    'responsible_user_id' => 'user-uuid',
    'billing_method' => 'time_and_materials'
]);
```

**Billing Methods:**
- `time_and_materials`
- `fixed_price` (requires `estimated_duration` and `estimated_value`)
- `non_billable`

### Create Milestone with Fixed Price

```php
$milestone = Teamleader::legacyMilestones()->create([
    'project_id' => 'project-uuid',
    'name' => 'Phase 2: Design',
    'due_on' => '2024-03-31',
    'responsible_user_id' => 'user-uuid',
    'billing_method' => 'fixed_price',
    'estimated_duration' => [
        'value' => 80,
        'unit' => 'hours'
    ],
    'estimated_value' => [
        'amount' => 8000.00,
        'currency' => 'EUR'
    ]
]);
```

### Get Project Milestones

```php
$projectId = 'project-uuid';
$milestones = Teamleader::legacyMilestones()->forProject($projectId);

echo "Milestones for project:\n";
foreach ($milestones['data'] as $milestone) {
    $status = $milestone['status'];
    $dueDate = $milestone['due_on'];
    
    echo "- {$milestone['name']} ({$status}) - Due: {$dueDate}\n";
}
```

### Get Overdue Milestones

```php
$today = date('Y-m-d');

$overdue = Teamleader::legacyMilestones()->list([
    'status' => 'open',
    'due_before' => $today
]);

if (!empty($overdue['data'])) {
    echo "Overdue Milestones:\n";
    foreach ($overdue['data'] as $milestone) {
        echo "- {$milestone['name']} (Due: {$milestone['due_on']})\n";
    }
}
```

### Complete Milestone Workflow

```php
$milestoneId = 'milestone-uuid';

// Update milestone
Teamleader::legacyMilestones()->update($milestoneId, [
    'name' => 'Updated Milestone Name',
    'due_on' => '2024-04-30'
]);

// Close when complete
Teamleader::legacyMilestones()->close($milestoneId);

// Reopen if needed
Teamleader::legacyMilestones()->open($milestoneId);
```

### Get Upcoming Milestones

```php
$today = date('Y-m-d');
$nextMonth = date('Y-m-d', strtotime('+30 days'));

$upcoming = Teamleader::legacyMilestones()->list([
    'status' => 'open',
    'due_after' => $today,
    'due_before' => $nextMonth
], [
    'sort' => [
        ['field' => 'due_on', 'order' => 'asc']
    ]
]);

echo "Upcoming Milestones (Next 30 Days):\n";
foreach ($upcoming['data'] as $milestone) {
    $daysUntilDue = ceil((strtotime($milestone['due_on']) - time()) / 86400);
    echo "- {$milestone['name']}: {$daysUntilDue} days\n";
}
```

### Milestone Progress Report

```php
$projectId = 'project-uuid';
$milestones = Teamleader::legacyMilestones()->forProject($projectId);

$stats = [
    'total' => count($milestones['data']),
    'open' => 0,
    'closed' => 0,
    'overdue' => 0
];

$today = date('Y-m-d');

foreach ($milestones['data'] as $milestone) {
    if ($milestone['status'] === 'open') {
        $stats['open']++;
        
        if ($milestone['due_on'] < $today) {
            $stats['overdue']++;
        }
    } else {
        $stats['closed']++;
    }
}

echo "Project Progress:\n";
echo "Total Milestones: {$stats['total']}\n";
echo "Open: {$stats['open']}\n";
echo "Closed: {$stats['closed']}\n";
echo "Overdue: {$stats['overdue']}\n";

$progress = $stats['total'] > 0 
    ? round(($stats['closed'] / $stats['total']) * 100) 
    : 0;
echo "Completion: {$progress}%\n";
```

## Best Practices

1. **Set realistic due dates**: Help with project planning
2. **Choose appropriate billing method**: Matches project contract
3. **Use descriptive names**: Phase 1, Phase 2 is less clear than "Discovery", "Design"
4. **Track milestone status**: Close milestones when complete
5. **Monitor overdue milestones**: Regular status checks

## Validation

```php
// Ensure required fields
$milestoneData = [
    'project_id' => 'project-uuid',
    'name' => 'Phase 1',
    'due_on' => '2024-12-31',
    'responsible_user_id' => 'user-uuid',
    'billing_method' => 'time_and_materials'
];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $milestoneData['due_on'])) {
    throw new Exception('Invalid date format. Use YYYY-MM-DD');
}

// Create milestone
$milestone = Teamleader::legacyMilestones()->create($milestoneData);
```

## Related Resources

- **[Legacy Projects](projects.md)** - Parent projects
- **[New Projects](../projects.md)** - Modern API alternative
- **[Groups](../groups.md)** - Equivalent in new API
- **[Users](../../general/users.md)** - Responsible users

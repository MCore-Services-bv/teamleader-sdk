# Groups

Manage project groups in Teamleader Focus (organizes tasks and materials).

## Overview

Groups organize tasks and materials within projects into logical sections or phases. They can have their own billing methods and be assigned to users or teams.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Related Resources](#related-resources)

## Endpoint

`projects-v2/groups`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `create()`

```php
$group = Teamleader::groups()->create([
    'project_id' => 'project-uuid',
    'title' => 'Phase 1: Design',
    'description' => 'Initial design phase',
    'color' => '#00B2B2',
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 5000.00,
        'currency' => 'EUR'
    ]
]);
```

**Billing Methods:**
- `time_and_materials`
- `fixed_price` (requires `fixed_price`)
- `parent_fixed_price`
- `non_billable`

### `update()`

```php
Teamleader::groups()->update('group-uuid', [
    'title' => 'Updated Phase Title',
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 6000.00,
        'currency' => 'EUR'
    ]
]);
```

### `delete()`

**Delete Strategies:**
- `ungroup_tasks_and_materials` (default) - Move items out of group
- `delete_tasks_and_materials` - Delete everything
- `delete_tasks_materials_and_unbilled_timetrackings` - Delete all

```php
Teamleader::groups()->delete('group-uuid', 'ungroup_tasks_and_materials');
```

### `duplicate()`

```php
$newGroup = Teamleader::groups()->duplicate('group-uuid');
```

### `assign()` / `unassign()`

```php
Teamleader::groups()->assign('group-uuid', 'user', 'user-uuid');
Teamleader::groups()->unassign('group-uuid', 'user', 'user-uuid');
```

### Helper Methods

```php
// Get groups for a project
$groups = Teamleader::groups()->forProject('project-uuid');
```

## Response Structure

```json
{
  "id": "group-uuid",
  "project": {
    "type": "nextgenProject",
    "id": "project-uuid"
  },
  "title": "Phase 1: Design",
  "description": "Initial design phase",
  "color": "#00B2B2",
  "billing_method": "fixed_price",
  "fixed_price": {
    "amount": 5000.00,
    "currency": "EUR"
  },
  "billing_status": "not_billed",
  "assignees": []
}
```

## Usage Examples

### Create Project Phases

```php
$phases = [
    ['title' => 'Phase 1: Discovery', 'amount' => 3000],
    ['title' => 'Phase 2: Design', 'amount' => 5000],
    ['title' => 'Phase 3: Development', 'amount' => 15000],
    ['title' => 'Phase 4: Testing', 'amount' => 4000],
];

foreach ($phases as $phase) {
    Teamleader::groups()->create([
        'project_id' => $projectId,
        'title' => $phase['title'],
        'billing_method' => 'fixed_price',
        'fixed_price' => [
            'amount' => $phase['amount'],
            'currency' => 'EUR'
        ]
    ]);
}
```

### Update Group Billing Method

```php
// Change from T&M to fixed price
Teamleader::groups()->update('group-uuid', [
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 8000.00,
        'currency' => 'EUR'
    ]
], 'cascade');  // Update strategy: 'none' or 'cascade'
```

## Best Practices

1. **Use groups for phases**: Organize project work logically
2. **Set clear colors**: Visual differentiation
3. **Choose appropriate delete strategy**: Consider time trackings
4. **Use fixed price for phases**: Better budget control

## Related Resources

- **[Projects](projects.md)** - Parent projects
- **[Project Tasks](project-tasks.md)** - Add tasks to groups
- **[Materials](materials.md)** - Add materials to groups
- **[Project Lines](project-lines.md)** - Manage group membership

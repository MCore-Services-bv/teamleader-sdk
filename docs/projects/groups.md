# Project Groups

Manage project groups in Teamleader Focus using the New Projects API (v2). Groups allow you to organize tasks and materials within a project into logical phases or categories.

## Endpoint

`projectGroups` (via `groups` resource)

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported (by IDs and project_id)
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported (with strategy)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of project groups with optional filtering.

**Parameters:**
- `filters` (array): Array of filters to apply
    - `ids` (array): Filter by group UUIDs
    - `project_id` (string): Filter by project UUID
- `options` (array): Additional options (not used for this endpoint)

**Example:**
```php
$groups = $teamleader->groups()->list([
    'project_id' => 'project-uuid-here'
]);
```

### `forProject()`

Get all groups for a specific project (convenience method).

**Parameters:**
- `projectId` (string): Project UUID

**Example:**
```php
$groups = $teamleader->groups()->forProject('project-uuid-here');
```

### `byIds()`

Get specific groups by their IDs.

**Parameters:**
- `ids` (array): Array of group UUIDs

**Example:**
```php
$groups = $teamleader->groups()->byIds([
    'group-uuid-1',
    'group-uuid-2'
]);
```

### `info()`

Get detailed information about a specific group.

**Parameters:**
- `id` (string): Group UUID

**Example:**
```php
$group = $teamleader->groups()->info('group-uuid-here');
```

### `create()`

Create a new project group.

**Parameters:**
- `data` (array): Array of group data

**Required Fields:**
- `project_id` (string): UUID of the project
- `title` (string): Group title/name

**Optional Fields:**
- `description` (string): Group description
- `color` (string): Color code (see available colors below)
- `billing_method` (string): One of: `time_and_materials`, `fixed_price`, `parent_fixed_price`, `non_billable`
- `fixed_price` (object): Amount and currency (required if `billing_method` is `fixed_price`)
    - `amount` (number): Amount value
    - `currency` (string): Currency code
- `external_budget` (object): Budget amount and currency (for `time_and_materials` method)
    - `amount` (number): Amount value
    - `currency` (string): Currency code
- `internal_budget` (object): Cost budget amount and currency
    - `amount` (number): Amount value
    - `currency` (string): Currency code
- `start_date` (string): Start date in YYYY-MM-DD format
- `end_date` (string): End date in YYYY-MM-DD format
- `assignees` (array): Array of assignee objects
    - `type` (string): `user` or `team`
    - `id` (string): UUID of the user or team

**Example:**
```php
$group = $teamleader->groups()->create([
    'project_id' => 'project-uuid-here',
    'title' => 'Phase 1: Design',
    'description' => 'Initial design and planning phase',
    'color' => '#00B2B2',
    'billing_method' => 'fixed_price',
    'fixed_price' => [
        'amount' => 5000,
        'currency' => 'EUR'
    ],
    'start_date' => '2023-01-18',
    'end_date' => '2023-03-22',
    'assignees' => [
        [
            'type' => 'user',
            'id' => 'user-uuid-here'
        ]
    ]
]);
```

### `update()`

Update an existing project group.

**Parameters:**
- `id` (string): Group UUID
- `data` (array): Array of data to update

**Updatable Fields:**
- `title` (string): Group title
- `description` (string|null): Group description (null to clear)
- `color` (string): Color code
- `billing_method` (object): Billing method update
    - `value` (string): New billing method
    - `update_strategy` (string): `none` or `cascade`
- `fixed_price` (object|null): Fixed price amount and currency
- `external_budget` (object|null): External budget amount and currency
- `internal_budget` (object|null): Internal budget amount and currency
- `start_date` (string|null): Start date
- `end_date` (string|null): End date

**Example:**
```php
$group = $teamleader->groups()->update('group-uuid-here', [
    'title' => 'Phase 1: Design & Planning',
    'description' => 'Updated description with more details',
    'billing_method' => [
        'value' => 'time_and_materials',
        'update_strategy' => 'none'
    ],
    'external_budget' => [
        'amount' => 8000,
        'currency' => 'EUR'
    ]
]);
```

### `delete()`

Delete a project group with a specified strategy.

**Parameters:**
- `id` (string): Group UUID
- `deleteStrategy` (string): How to handle group contents (default: `ungroup_tasks_and_materials`)
    - `ungroup_tasks_and_materials`: Keep tasks/materials but remove group
    - `delete_tasks_and_materials`: Delete all tasks and materials
    - `delete_tasks_materials_and_unbilled_timetrackings`: Delete tasks, materials, and unbilled time

**Example:**
```php
$result = $teamleader->groups()->delete(
    'group-uuid-here',
    'ungroup_tasks_and_materials'
);
```

### `duplicate()`

Duplicate a group and its entities (without time trackings).

**Parameters:**
- `originId` (string): UUID of the group to duplicate

**Returns:**
- `data.id` (string): UUID of the new duplicated group
- `data.type` (string): Resource type

**Example:**
```php
$newGroup = $teamleader->groups()->duplicate('origin-group-uuid');
$newGroupId = $newGroup['data']['id'];
```

### `assign()`

Assign a user or team to a group.

**Parameters:**
- `groupId` (string): Group UUID
- `assigneeType` (string): Type of assignee (`user` or `team`)
- `assigneeId` (string): UUID of the user or team

**Example:**
```php
$result = $teamleader->groups()->assign(
    'group-uuid-here',
    'user',
    'user-uuid-here'
);
```

### `unassign()`

Unassign a user or team from a group.

**Parameters:**
- `groupId` (string): Group UUID
- `assigneeType` (string): Type of assignee (`user` or `team`)
- `assigneeId` (string): UUID of the user or team

**Example:**
```php
$result = $teamleader->groups()->unassign(
    'group-uuid-here',
    'user',
    'user-uuid-here'
);
```

### `assignUser()` / `unassignUser()`

Convenience methods to assign/unassign a user.

**Parameters:**
- `groupId` (string): Group UUID
- `userId` (string): User UUID

**Example:**
```php
$teamleader->groups()->assignUser('group-uuid', 'user-uuid');
$teamleader->groups()->unassignUser('group-uuid', 'user-uuid');
```

### `assignTeam()` / `unassignTeam()`

Convenience methods to assign/unassign a team.

**Parameters:**
- `groupId` (string): Group UUID
- `teamId` (string): Team UUID

**Example:**
```php
$teamleader->groups()->assignTeam('group-uuid', 'team-uuid');
$teamleader->groups()->unassignTeam('group-uuid', 'team-uuid');
```

## Available Filters

When using the `list()` method, you can filter groups using:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of group UUIDs to filter by |
| `project_id` | string | Filter groups belonging to a specific project |

## Group Response Fields

A group object contains:

- `id` (string): Group UUID
- `project` (object): Associated project details
    - `id` (string): Project UUID
    - `type` (string): Resource type
- `title` (string): Group title
- `description` (string|null): Group description
- `color` (string): Color code (hex)
- `billing_method` (string): Billing method
- `billing_status` (string): Current billing status
- `amount_billed` (object|null): Amount already billed
- `fixed_amount_billed` (object|null): Fixed amount billed
- `external_budget` (object|null): External budget ("budget")
- `external_budget_spent` (object|null): External budget spent
- `internal_budget` (object|null): Internal budget ("cost budget")
- `price` (object|null): Current price
- `fixed_price` (object|null): Fixed price
- `calculated_price` (object|null): Calculated price
- `cost` (object|null): Current cost
- `margin` (object|null): Margin
- `margin_percentage` (number|null): Margin percentage
- `assignees` (array): List of assigned users/teams
- `start_date` (string|null): Start date
- `end_date` (string|null): End date
- `time_estimated` (object|null): Total estimated time
- `time_tracked` (object|null): Total tracked time

## Available Values

### Colors

The following color codes are available for groups:

- `#00B2B2` - Teal
- `#008A8C` - Dark Teal
- `#992600` - Dark Red
- `#ED9E00` - Orange
- `#D157D3` - Purple
- `#A400B2` - Dark Purple
- `#0071F2` - Blue
- `#004DA6` - Dark Blue
- `#64788F` - Gray Blue
- `#C0C0C4` - Light Gray
- `#82828C` - Gray
- `#1A1C20` - Dark Gray

### Billing Methods

- `time_and_materials` - Bill based on time and materials
- `fixed_price` - Fixed price billing
- `parent_fixed_price` - Inherit from parent project
- `non_billable` - Not billable

### Billing Statuses

- `not_billable` - Group is not billable
- `not_billed` - Not yet billed
- `partially_billed` - Partially billed
- `fully_billed` - Fully billed

### Delete Strategies

- `ungroup_tasks_and_materials` - Keep tasks/materials, remove group
- `delete_tasks_and_materials` - Delete all tasks and materials
- `delete_tasks_materials_and_unbilled_timetrackings` - Delete tasks, materials, and unbilled time

### Update Strategies (for billing method)

- `none` - Only update this group
- `cascade` - Update this group and cascade to child items

## Usage Examples

### Complete Group Management Workflow

```php
// 1. Create a new group for a project
$group = $teamleader->groups()->create([
    'project_id' => 'abc123-project-id',
    'title' => 'Phase 1: Discovery',
    'description' => 'Research and discovery phase',
    'color' => '#00B2B2',
    'billing_method' => 'time_and_materials',
    'external_budget' => [
        'amount' => 10000,
        'currency' => 'EUR'
    ],
    'start_date' => '2023-01-15',
    'end_date' => '2023-02-15'
]);

$groupId = $group['data']['id'];

// 2. Assign team members to the group
$teamleader->groups()->assignUser($groupId, 'user-uuid-1');
$teamleader->groups()->assignUser($groupId, 'user-uuid-2');

// 3. Get group details with budget info
$groupInfo = $teamleader->groups()->info($groupId);
$budgetSpent = $groupInfo['data']['external_budget_spent']['amount'] ?? 0;
$budget = $groupInfo['data']['external_budget']['amount'] ?? 0;
$remaining = $budget - $budgetSpent;

echo "Budget remaining: €{$remaining}\n";

// 4. Update the group if needed
$teamleader->groups()->update($groupId, [
    'end_date' => '2023-02-28', // Extend deadline
    'external_budget' => [
        'amount' => 12000, // Increase budget
        'currency' => 'EUR'
    ]
]);

// 5. Duplicate for next phase
$phase2 = $teamleader->groups()->duplicate($groupId);
$teamleader->groups()->update($phase2['data']['id'], [
    'title' => 'Phase 2: Implementation'
]);

// 6. List all groups for the project
$allGroups = $teamleader->groups()->forProject('abc123-project-id');

// 7. Clean up - delete old groups
foreach ($allGroups['data'] as $g) {
    if ($g['billing_status'] === 'fully_billed') {
        $teamleader->groups()->delete(
            $g['id'],
            'ungroup_tasks_and_materials'
        );
    }
}
```

### Advanced Budget Tracking

```php
// Get all groups for a project
$groups = $teamleader->groups()->forProject('project-uuid');

$totalBudget = 0;
$totalSpent = 0;
$groupStats = [];

foreach ($groups['data'] as $group) {
    $budget = $group['external_budget']['amount'] ?? 0;
    $spent = $group['external_budget_spent']['amount'] ?? 0;
    
    $totalBudget += $budget;
    $totalSpent += $spent;
    
    $groupStats[] = [
        'title' => $group['title'],
        'budget' => $budget,
        'spent' => $spent,
        'remaining' => $budget - $spent,
        'percentage_used' => $budget > 0 ? ($spent / $budget * 100) : 0
    ];
}

// Output project budget summary
echo "Project Budget Overview:\n";
echo "Total Budget: €{$totalBudget}\n";
echo "Total Spent: €{$totalSpent}\n";
echo "Remaining: €" . ($totalBudget - $totalSpent) . "\n\n";

// Output per-group details
echo "Group Details:\n";
foreach ($groupStats as $stat) {
    echo "- {$stat['title']}: €{$stat['spent']} / €{$stat['budget']} ";
    echo "(" . number_format($stat['percentage_used'], 1) . "% used)\n";
}
```

### Managing Assignees

```php
$groupId = 'group-uuid-here';

// Get current assignees
$group = $teamleader->groups()->info($groupId);
$currentAssignees = $group['data']['assignees'];

// Assign multiple users
$userIds = ['user-1', 'user-2', 'user-3'];
foreach ($userIds as $userId) {
    $teamleader->groups()->assignUser($groupId, $userId);
}

// Assign a team
$teamleader->groups()->assignTeam($groupId, 'team-uuid');

// Remove a user
$teamleader->groups()->unassignUser($groupId, 'user-1');

// Get updated assignee list
$updated = $teamleader->groups()->info($groupId);
foreach ($updated['data']['assignees'] as $assignee) {
    echo "{$assignee['assignee']['type']}: {$assignee['assignee']['id']}\n";
}
```

### Changing Billing Methods

```php
// Update billing method with cascade
$teamleader->groups()->update('group-uuid', [
    'billing_method' => [
        'value' => 'fixed_price',
        'update_strategy' => 'cascade' // Apply to all child items
    ],
    'fixed_price' => [
        'amount' => 15000,
        'currency' => 'EUR'
    ]
]);

// Update billing method without cascade
$teamleader->groups()->update('group-uuid', [
    'billing_method' => [
        'value' => 'time_and_materials',
        'update_strategy' => 'none' // Only this group
    ],
    'external_budget' => [
        'amount' => 20000,
        'currency' => 'EUR'
    ]
]);
```

## Helper Methods

### `getAvailableBillingMethods()`

Get list of available billing methods.

```php
$methods = $teamleader->groups()->getAvailableBillingMethods();
// Returns: ['time_and_materials', 'fixed_price', 'parent_fixed_price', 'non_billable']
```

### `getAvailableDeleteStrategies()`

Get list of available delete strategies.

```php
$strategies = $teamleader->groups()->getAvailableDeleteStrategies();
// Returns: ['ungroup_tasks_and_materials', 'delete_tasks_and_materials', ...]
```

### `getAvailableAssigneeTypes()`

Get list of available assignee types.

```php
$types = $teamleader->groups()->getAvailableAssigneeTypes();
// Returns: ['team', 'user']
```

## Error Handling

```php
use InvalidArgumentException;

try {
    // Invalid color
    $group = $teamleader->groups()->create([
        'project_id' => 'project-uuid',
        'title' => 'Test Group',
        'color' => '#INVALID'
    ]);
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
}

try {
    // Invalid billing method
    $teamleader->groups()->update('group-uuid', [
        'billing_method' => [
            'value' => 'invalid_method',
            'update_strategy' => 'none'
        ]
    ]);
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Notes

- Groups are part of the New Projects API (v2) and use the `projectGroups` endpoint
- All date fields must be in `YYYY-MM-DD` format
- Currency codes follow ISO 4217 standard
- Time values are in seconds, rounded to the nearest minute
- Deleting a group requires choosing a delete strategy to handle its contents
- The `duplicate()` method copies the group and its entities but excludes time trackings
- Margin percentage is `null` if the user doesn't have access to "Costs on projects"
- When updating billing method, the `update_strategy` determines if changes cascade to child items

# Legacy Milestones

Manage project milestones in Teamleader Focus Legacy Projects. This resource provides complete CRUD operations for managing milestones, including closing, opening, and advanced filtering capabilities.

## Endpoint

`milestones`

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

Get a paginated list of milestones with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
$milestones = $teamleader->legacyMilestones()->list(['status' => 'open']);
```

### `info()`

Get detailed information about a specific milestone.

**Parameters:**
- `id` (string): Milestone UUID

**Example:**
```php
$milestone = $teamleader->legacyMilestones()->info('milestone-uuid-here');
```

### `create()`

Create a new milestone.

**Parameters:**
- `data` (array): Array of milestone data

**Example:**
```php
$milestone = $teamleader->legacyMilestones()->create([
    'project_id' => '1c159f98-4b07-438a-9f42-fb4206b9530d',
    'name' => 'Initial setup',
    'due_on' => '2024-12-31',
    'starts_on' => '2024-01-01',
    'responsible_user_id' => 'e1240972-6cfc-4549-b49c-edda7568cc48',
    'billing_method' => 'time_and_materials',
    'budget' => [
        'amount' => 5000.00,
        'currency' => 'EUR'
    ]
]);
```

### `update()`

Update an existing milestone.

**Parameters:**
- `id` (string): Milestone UUID
- `data` (array): Array of data to update

**Example:**
```php
$milestone = $teamleader->legacyMilestones()->update('milestone-uuid', [
    'name' => 'Updated milestone name',
    'due_on' => '2024-12-31',
    'propagate_date_changes' => false
]);
```

### `delete()`

Delete a milestone.

**Parameters:**
- `id` (string): Milestone UUID

**Example:**
```php
$result = $teamleader->legacyMilestones()->delete('milestone-uuid');
```

### `close()`

Close a milestone. All open tasks will be closed, open meetings will remain open. Closing the last open milestone will also close the project.

**Parameters:**
- `id` (string): Milestone UUID

**Example:**
```php
$result = $teamleader->legacyMilestones()->close('milestone-uuid');
```

### `open()`

(Re)open a milestone. If the milestone's project is closed, the project will be reopened.

**Parameters:**
- `id` (string): Milestone UUID

**Example:**
```php
$result = $teamleader->legacyMilestones()->open('milestone-uuid');
```

### `forProject()`

Get all milestones for a specific project.

**Parameters:**
- `projectId` (string): Project UUID
- `options` (array): Additional options

**Example:**
```php
$milestones = $teamleader->legacyMilestones()->forProject('project-uuid');
```

### `getOpen()` / `getClosed()`

Get open or closed milestones.

**Parameters:**
- `additionalFilters` (array): Additional filters to apply
- `options` (array): Additional options

**Example:**
```php
$openMilestones = $teamleader->legacyMilestones()->getOpen();
$closedMilestones = $teamleader->legacyMilestones()->getClosed();
```

### `search()`

Search milestones by term (searches in milestone title).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$milestones = $teamleader->legacyMilestones()->search('setup');
```

### `dueBefore()` / `dueAfter()` / `dueBetween()`

Filter milestones by due date.

**Parameters:**
- `date` (string): Date in Y-m-d format
- `additionalFilters` (array): Additional filters
- `options` (array): Additional options

**Example:**
```php
// Milestones due before a date
$milestones = $teamleader->legacyMilestones()->dueBefore('2024-12-31');

// Milestones due after a date
$milestones = $teamleader->legacyMilestones()->dueAfter('2024-01-01');

// Milestones due within a date range
$milestones = $teamleader->legacyMilestones()->dueBetween('2024-01-01', '2024-12-31');
```

## Filtering

### Available Filters

- **`ids`**: Array of milestone UUIDs to filter by
- **`project_id`**: Filter milestones by project UUID
- **`status`**: Filter by milestone status (`open`, `closed`)
- **`due_before`**: Filter milestones due before date (Y-m-d format)
- **`due_after`**: Filter milestones due after date (Y-m-d format)
- **`term`**: Search term - searches in milestone title

### Filtering Examples

```php
// Filter by project
$milestones = $teamleader->legacyMilestones()->list([
    'project_id' => '082e6289-30c5-45ad-bcd0-190b02d21e81'
]);

// Filter by status
$milestones = $teamleader->legacyMilestones()->list([
    'status' => 'open'
]);

// Filter by due date range
$milestones = $teamleader->legacyMilestones()->list([
    'due_after' => '2024-01-01',
    'due_before' => '2024-12-31'
]);

// Search by term
$milestones = $teamleader->legacyMilestones()->list([
    'term' => 'coffee'
]);

// Filter specific milestones
$milestones = $teamleader->legacyMilestones()->list([
    'ids' => [
        'bbbfe0da-e692-4ee3-9d3d-0716808d6523',
        '722e1eb9-53d5-4b8c-9d17-154dcc65c610'
    ]
]);

// Combine multiple filters
$milestones = $teamleader->legacyMilestones()->list([
    'project_id' => '082e6289-30c5-45ad-bcd0-190b02d21e81',
    'status' => 'open',
    'due_before' => '2024-12-31'
]);
```

## Sorting

### Available Sort Fields

- **`starts_on`**: Sort by milestone start date
- **`due_on`**: Sort by milestone due date (default)

### Sorting Examples

```php
// Sort by due date ascending (default)
$milestones = $teamleader->legacyMilestones()->list([], [
    'sort' => [
        [
            'field' => 'due_on',
            'order' => 'asc'
        ]
    ]
]);

// Sort by start date descending
$milestones = $teamleader->legacyMilestones()->list([], [
    'sort' => [
        [
            'field' => 'starts_on',
            'order' => 'desc'
        ]
    ]
]);
```

## Pagination

### Pagination Examples

```php
// Get first page with 20 items (default)
$milestones = $teamleader->legacyMilestones()->list([], [
    'page_size' => 20,
    'page_number' => 1
]);

// Get second page with 50 items
$milestones = $teamleader->legacyMilestones()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// Combine pagination with filters and sorting
$milestones = $teamleader->legacyMilestones()->list(
    ['status' => 'open'],
    [
        'page_size' => 25,
        'page_number' => 1,
        'sort' => [
            ['field' => 'due_on', 'order' => 'asc']
        ]
    ]
);
```

## Data Fields

### Milestone Creation Fields

**Required:**
- `project_id` (string): UUID of the project this milestone belongs to
- `name` (string): Name of the milestone (e.g., "Initial setup")
- `due_on` (string): Due date in Y-m-d format (e.g., "2024-12-31")
- `responsible_user_id` (string): UUID of the user responsible for this milestone

**Optional:**
- `starts_on` (string): Start date in Y-m-d format (e.g., "2024-01-01")
- `description` (string): Description of the milestone
- `depends_on` (string): UUID of another milestone this one depends on
- `billing_method` (string): Billing method (`non_invoiceable`, `time_and_materials`, `fixed_price`)
- `budget` (object): Budget information with `amount` (number) and `currency` (string)
- `custom_fields` (array): Array of custom field objects with `id` and `value`

### Milestone Update Fields

**Required:**
- `id` (string): UUID of the milestone to update

**Optional (all milestone creation fields are optional for updates):**
- `starts_on` (string): Start date in Y-m-d format
- `due_on` (string): Due date in Y-m-d format
- `name` (string): Name of the milestone
- `description` (string): Description of the milestone
- `responsible_user_id` (string): UUID of the responsible user
- `depends_on` (string): UUID of the milestone this depends on (can be null to remove dependency)
- `propagate_date_changes` (boolean): Whether to propagate date changes to dependent milestones
- `custom_fields` (array): Array of custom field objects

### Response Structure

```php
[
    'data' => [
        'id' => 'cfb4146d-06be-41f1-bb39-aa3c929c71dc',
        'project' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'project'
        ],
        'starts_on' => '2024-01-01',
        'due_on' => '2024-12-31',
        'name' => 'Initial setup',
        'description' => 'Setup project infrastructure',
        'responsible_user' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'user'
        ],
        'status' => 'open', // or 'closed'
        'invoicing_method' => 'time_and_materials',
        'depends_on' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'milestone'
        ],
        'dependency_for' => [
            [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'milestone'
            ]
        ],
        'actuals' => [
            'billable_amount' => [
                'amount' => 123.3,
                'currency' => 'EUR'
            ],
            'costs' => [
                'amount' => 123.3,
                'currency' => 'EUR'
            ],
            'result' => [
                'amount' => 123.3,
                'currency' => 'EUR'
            ]
        ],
        'budget' => [
            'provided' => [
                'amount' => 5000.00,
                'currency' => 'EUR'
            ],
            'spent' => [
                'amount' => 2500.00,
                'currency' => 'EUR'
            ],
            'remaining' => [
                'amount' => 2500.00,
                'currency' => 'EUR'
            ],
            'allocated' => [
                'amount' => 3000.00,
                'currency' => 'EUR'
            ],
            'forecasted' => [
                'amount' => 4800.00,
                'currency' => 'EUR'
            ]
        ],
        'custom_fields' => [
            [
                'definition' => [
                    'type' => 'customFieldDefinition',
                    'id' => 'bf6765de-56eb-40ec-ad14-9096c5dc5fe1'
                ],
                'value' => '092980616'
            ]
        ]
    ]
]
```

## Important Notes

### Milestone Closure Behavior

- Closing a milestone will close all open tasks associated with it
- Open meetings will remain open when a milestone is closed
- Closing the last open milestone will also close the project

### Milestone Reopening Behavior

- Reopening a milestone that belongs to a closed project will reopen the project

### Budget Information

- Budget information in the `actuals` and `budget` sections is only accessible for administrators of the project the milestone belongs to
- Budget fields include `provided`, `spent`, `remaining`, `allocated`, and `forecasted` amounts
- `allocated` is null if there isn't enough data to calculate
- `forecasted` is null if there isn't enough data to calculate a prediction

### Dependencies

- Milestones can depend on other milestones using the `depends_on` field
- The `dependency_for` array shows which milestones depend on the current milestone
- Setting `propagate_date_changes` to `true` when updating will propagate date changes to dependent milestones

### Billing Methods

- **`non_invoiceable`**: Milestone work is not invoiceable
- **`time_and_materials`**: Billing based on time and materials (default)
- **`fixed_price`**: Fixed price billing for the milestone

## Utility Methods

### Get Available Options

```php
// Get available billing methods
$billingMethods = $teamleader->legacyMilestones()->getAvailableBillingMethods();
// Returns: ['non_invoiceable' => 'Non Invoiceable', 'time_and_materials' => 'Time and Materials', 'fixed_price' => 'Fixed Price']

// Get available statuses
$statuses = $teamleader->legacyMilestones()->getAvailableStatuses();
// Returns: ['open', 'closed']

// Get available sort fields
$sortFields = $teamleader->legacyMilestones()->getAvailableSortFields();
// Returns: ['starts_on', 'due_on']
```

## Common Use Cases

### Close Multiple Milestones

```php
$milestonesToClose = ['uuid1', 'uuid2', 'uuid3'];

foreach ($milestonesToClose as $milestoneId) {
    $teamleader->legacyMilestones()->close($milestoneId);
}
```

### Get Overdue Milestones

```php
$today = date('Y-m-d');
$overdueMilestones = $teamleader->legacyMilestones()->list([
    'status' => 'open',
    'due_before' => $today
]);
```

### Get Upcoming Milestones

```php
$today = date('Y-m-d');
$nextMonth = date('Y-m-d', strtotime('+1 month'));

$upcomingMilestones = $teamleader->legacyMilestones()->dueBetween($today, $nextMonth, [
    'status' => 'open'
]);
```

### Create Milestone with Budget

```php
$milestone = $teamleader->legacyMilestones()->create([
    'project_id' => 'project-uuid',
    'name' => 'Phase 1',
    'starts_on' => '2024-01-01',
    'due_on' => '2024-03-31',
    'responsible_user_id' => 'user-uuid',
    'billing_method' => 'time_and_materials',
    'budget' => [
        'amount' => 10000.00,
        'currency' => 'EUR'
    ],
    'description' => 'First phase of the project'
]);
```

### Update Milestone Dates with Propagation

```php
$result = $teamleader->legacyMilestones()->update('milestone-uuid', [
    'starts_on' => '2024-02-01',
    'due_on' => '2024-04-30',
    'propagate_date_changes' => true // Propagate to dependent milestones
]);
```

## Error Handling

```php
try {
    $milestone = $teamleader->legacyMilestones()->create([
        'project_id' => 'project-uuid',
        'name' => 'New Milestone',
        'due_on' => '2024-12-31',
        'responsible_user_id' => 'user-uuid'
    ]);
} catch (\InvalidArgumentException $e) {
    // Handle validation errors
    echo "Validation error: " . $e->getMessage();
} catch (\Exception $e) {
    // Handle API errors
    echo "API error: " . $e->getMessage();
}
```

# Projects

Manage projects in Teamleader Focus using the new Projects API v2. This resource provides complete CRUD operations for managing projects, including advanced features like budget tracking, team assignments, customer linking, and deal/quotation associations.

## Endpoint

`projects-v2/projects`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ✅ Supported (legacy_project, custom_fields)
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of projects with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$projects = $teamleader->projects()->list(['status' => 'open']);
```

### `info()`

Get detailed information about a specific project.

**Parameters:**
- `id` (string): Project UUID
- `includes` (array|string): Relations to include (legacy_project, custom_fields)

**Example:**
```php
$project = $teamleader->projects()->info('project-uuid-here');
$projectWithLegacy = $teamleader->projects()->info('project-uuid', 'legacy_project');
```

### `create()`

Create a new project.

**Parameters:**
- `data` (array): Array of project data

**Example:**
```php
$project = $teamleader->projects()->create([
    'title' => 'My New Project',
    'description' => 'Project description',
    'billing_method' => 'time_and_materials',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
]);
```

### `update()`

Update an existing project.

**Parameters:**
- `id` (string): Project UUID
- `data` (array): Array of data to update

**Example:**
```php
$project = $teamleader->projects()->update('project-uuid', [
    'title' => 'Updated Project Title',
    'status' => 'running',
]);
```

### `delete()`

Delete a project with a specific strategy for handling tasks and time trackings.

**Parameters:**
- `id` (string): Project UUID
- `deleteStrategy` (string): Strategy for deletion (default: 'unlink_tasks_and_time_trackings')
    - `unlink_tasks_and_time_trackings`
    - `delete_tasks_and_time_trackings`
    - `delete_tasks_unlink_time_trackings`

**Example:**
```php
$result = $teamleader->projects()->delete('project-uuid');
$result = $teamleader->projects()->delete('project-uuid', 'delete_tasks_and_time_trackings');
```

### `duplicate()`

Duplicate an existing project.

**Parameters:**
- `id` (string): Project UUID
- `title` (string): Title for the new project

**Example:**
```php
$newProject = $teamleader->projects()->duplicate('project-uuid', 'Copy of My Project');
```

### `close()`

Mark a project as closed.

**Parameters:**
- `id` (string): Project UUID
- `closingStrategy` (string): Strategy for closing (default: 'none')
    - `mark_tasks_and_materials_as_done`
    - `none`

**Example:**
```php
$result = $teamleader->projects()->close('project-uuid');
$result = $teamleader->projects()->close('project-uuid', 'mark_tasks_and_materials_as_done');
```

### `reopen()`

Reopen a closed project.

**Parameters:**
- `id` (string): Project UUID

**Example:**
```php
$result = $teamleader->projects()->reopen('project-uuid');
```

### Customer Management

#### `addCustomer()` / `removeCustomer()`

Add or remove customers from a project.

**Parameters:**
- `id` (string): Project UUID
- `customerType` (string): 'contact' or 'company'
- `customerId` (string): Customer UUID

**Example:**
```php
$teamleader->projects()->addCustomer('project-uuid', 'company', 'company-uuid');
$teamleader->projects()->removeCustomer('project-uuid', 'company', 'company-uuid');
```

### Deal Management

#### `addDeal()` / `removeDeal()`

Add or remove deals from a project.

**Parameters:**
- `id` (string): Project UUID
- `dealId` (string): Deal UUID

**Example:**
```php
$teamleader->projects()->addDeal('project-uuid', 'deal-uuid');
$teamleader->projects()->removeDeal('project-uuid', 'deal-uuid');
```

### Quotation Management

#### `addQuotation()` / `removeQuotation()`

Add or remove quotations from a project.

**Parameters:**
- `id` (string): Project UUID
- `quotationId` (string): Quotation UUID

**Example:**
```php
$teamleader->projects()->addQuotation('project-uuid', 'quotation-uuid');
$teamleader->projects()->removeQuotation('project-uuid', 'quotation-uuid');
```

### Owner Management

#### `addOwner()` / `removeOwner()`

Add or remove owners from a project.

**Parameters:**
- `id` (string): Project UUID
- `userId` (string): User UUID

**Example:**
```php
$teamleader->projects()->addOwner('project-uuid', 'user-uuid');
$teamleader->projects()->removeOwner('project-uuid', 'user-uuid');
```

### Assignee Management

#### `assign()` / `unassign()`

Assign or unassign users or teams to/from a project.

**Parameters:**
- `id` (string): Project UUID
- `assigneeType` (string): 'user' or 'team'
- `assigneeId` (string): Assignee UUID

**Example:**
```php
$teamleader->projects()->assign('project-uuid', 'user', 'user-uuid');
$teamleader->projects()->assign('project-uuid', 'team', 'team-uuid');
$teamleader->projects()->unassign('project-uuid', 'user', 'user-uuid');
```

### Convenience Methods

#### Status-based Queries

```php
// Get open projects
$openProjects = $teamleader->projects()->open();

// Get closed projects
$closedProjects = $teamleader->projects()->closed();

// Get running projects
$runningProjects = $teamleader->projects()->running();

// Get overdue projects
$overdueProjects = $teamleader->projects()->overdue();

// Get over budget projects
$overBudgetProjects = $teamleader->projects()->overBudget();
```

#### `search()`

Search projects by term (searches project number, title, customer names, assignee names, owner names).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$projects = $teamleader->projects()->search('Website Redesign');
```

#### `byIds()`

Get projects by IDs.

**Parameters:**
- `ids` (array): Array of project UUIDs
- `options` (array): Additional options

**Example:**
```php
$projects = $teamleader->projects()->byIds(['uuid1', 'uuid2']);
```

#### `forCustomer()`

Get projects for a specific customer.

**Parameters:**
- `customerType` (string): 'contact' or 'company'
- `customerId` (string): Customer UUID
- `options` (array): Additional options

**Example:**
```php
$projects = $teamleader->projects()->forCustomer('company', 'company-uuid');
```

#### `forDeal()`

Get projects linked to a specific deal.

**Parameters:**
- `dealId` (string): Deal UUID
- `options` (array): Additional options

**Example:**
```php
$projects = $teamleader->projects()->forDeal('deal-uuid');
```

#### `forQuotation()`

Get projects linked to a specific quotation.

**Parameters:**
- `quotationId` (string): Quotation UUID
- `options` (array): Additional options

**Example:**
```php
$projects = $teamleader->projects()->forQuotation('quotation-uuid');
```

## Filtering

### Available Filters

- **`ids`**: Array of project UUIDs to filter by
- **`status`**: Project status
    - `open`
    - `planned`
    - `running`
    - `overdue`
    - `over_budget`
    - `closed`
- **`quotation_ids`**: Array of quotation UUIDs
- **`deal_ids`**: Array of deal UUIDs
- **`term`**: Search term (searches project number, title, customer names, assignee names, owner names)
- **`customers`**: Array of customer objects with `type` and `id`

### Filter Examples

```php
// Filter by status
$openProjects = $teamleader->projects()->list([
    'status' => 'open'
]);

// Search by term
$projects = $teamleader->projects()->list([
    'term' => 'Website'
]);

// Filter by customers
$projects = $teamleader->projects()->list([
    'customers' => [
        ['type' => 'company', 'id' => 'company-uuid']
    ]
]);

// Filter by deals
$projects = $teamleader->projects()->list([
    'deal_ids' => ['deal-uuid-1', 'deal-uuid-2']
]);

// Filter by quotations
$projects = $teamleader->projects()->list([
    'quotation_ids' => ['quotation-uuid']
]);

// Filter by multiple IDs
$projects = $teamleader->projects()->list([
    'ids' => ['project-uuid-1', 'project-uuid-2']
]);
```

## Sorting

### Available Sort Fields

- **`amount_billed`**: Amount billed
- **`amount_paid`**: Amount paid
- **`amount_unbilled`**: Amount unbilled
- **`cost`**: Project cost (requires "Costs on projects" permission)
- **`customer`**: Customer name
- **`end_date`**: End date
- **`external_budget_spent`**: External budget spent
- **`external_budget`**: External budget
- **`internal_budget`**: Internal budget
- **`margin`**: Margin (requires "Costs on projects" permission)
- **`price`**: Project price
- **`project_key`**: Project number (default sort)
- **`start_date`**: Start date
- **`status`**: Project status
- **`time_budget`**: Time budget
- **`time_estimated`**: Time estimated
- **`time_tracked`**: Time tracked
- **`title`**: Project title

### Sort Examples

```php
// Sort by project key (default)
$projects = $teamleader->projects()->list([], [
    'sort' => [['field' => 'project_key', 'order' => 'desc']]
]);

// Sort by title
$projects = $teamleader->projects()->list([], [
    'sort' => [['field' => 'title', 'order' => 'asc']]
]);

// Sort by amount billed
$projects = $teamleader->projects()->list([], [
    'sort' => [['field' => 'amount_billed', 'order' => 'desc']]
]);

// Multiple sort fields
$projects = $teamleader->projects()->list([], [
    'sort' => [
        ['field' => 'status', 'order' => 'asc'],
        ['field' => 'title', 'order' => 'asc']
    ]
]);
```

## Pagination

```php
// Get first page with 50 items
$projects = $teamleader->projects()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Get second page
$projects = $teamleader->projects()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

## Sideloading

```php
// Include legacy project data
$project = $teamleader->projects()->info('project-uuid', 'legacy_project');

// Include custom fields
$project = $teamleader->projects()->info('project-uuid', 'custom_fields');

// Include multiple
$project = $teamleader->projects()->info('project-uuid', ['legacy_project', 'custom_fields']);

// Include in list operations
$projects = $teamleader->projects()->list([], [
    'includes' => 'legacy_project,custom_fields'
]);
```

## Complete Examples

### Creating a Project

```php
$project = $teamleader->projects()->create([
    'title' => 'Website Redesign 2024',
    'description' => 'Complete website overhaul including new design and functionality',
    'billing_method' => 'time_and_materials',
    'time_budget' => [
        'value' => 120,
        'unit' => 'hours'
    ],
    'external_budget' => [
        'amount' => 25000.00,
        'currency' => 'EUR'
    ],
    'internal_budget' => [
        'amount' => 15000.00,
        'currency' => 'EUR'
    ],
    'start_date' => '2024-01-01',
    'end_date' => '2024-06-30',
    'company_entity_id' => 'company-entity-uuid',
    'color' => '#00B2B2',
    'customers' => [
        [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'assignees' => [
        [
            'type' => 'user',
            'id' => 'user-uuid'
        ]
    ],
    'owner_ids' => [
        'owner-user-uuid-1',
        'owner-user-uuid-2'
    ]
]);
```

### Updating a Project

```php
$project = $teamleader->projects()->update('project-uuid', [
    'title' => 'Updated Project Title',
    'description' => 'Updated description',
    'time_budget' => [
        'value' => 150,
        'unit' => 'hours'
    ],
    'billing_method' => [
        'value' => 'fixed_price',
        'update_strategy' => 'cascade'
    ],
    'fixed_price' => [
        'amount' => 30000.00,
        'currency' => 'EUR'
    ]
]);
```

### Complex Query

```php
// Find overdue projects with specific criteria
$projects = $teamleader->projects()->list([
    'status' => 'overdue',
    'customers' => [
        ['type' => 'company', 'id' => 'company-uuid']
    ]
], [
    'sort' => [
        ['field' => 'end_date', 'order' => 'asc']
    ],
    'page_size' => 25,
    'includes' => 'custom_fields'
]);
```

### Managing Project Relationships

```php
$projectId = 'project-uuid';

// Add relationships
$teamleader->projects()->addCustomer($projectId, 'company', 'company-uuid');
$teamleader->projects()->addDeal($projectId, 'deal-uuid');
$teamleader->projects()->addQuotation($projectId, 'quotation-uuid');
$teamleader->projects()->addOwner($projectId, 'user-uuid');
$teamleader->projects()->assign($projectId, 'team', 'team-uuid');

// Remove relationships
$teamleader->projects()->removeCustomer($projectId, 'company', 'company-uuid');
$teamleader->projects()->removeDeal($projectId, 'deal-uuid');
$teamleader->projects()->removeQuotation($projectId, 'quotation-uuid');
$teamleader->projects()->removeOwner($projectId, 'user-uuid');
$teamleader->projects()->unassign($projectId, 'team', 'team-uuid');
```

### Project Lifecycle Management

```php
$projectId = 'project-uuid';

// Close project
$teamleader->projects()->close($projectId, 'mark_tasks_and_materials_as_done');

// Reopen project
$teamleader->projects()->reopen($projectId);

// Duplicate project
$newProject = $teamleader->projects()->duplicate($projectId, 'Q2 2024 Version');

// Delete project
$teamleader->projects()->delete($projectId, 'unlink_tasks_and_time_trackings');
```

## Billing Methods

Projects support three billing methods:

- **`time_and_materials`**: Bill based on actual time tracked and materials used
- **`fixed_price`**: Bill a fixed amount regardless of time tracked
- **`non_billable`**: Project is not billed

When updating billing method with the `cascade` strategy, all project lines will be updated to match the new billing method.

## Project Colors

Available project colors:
- `#00B2B2` (Teal)
- `#008A8C` (Dark Teal)
- `#992600` (Brown)
- `#ED9E00` (Orange)
- `#D157D3` (Purple)
- `#A400B2` (Dark Purple)
- `#0071F2` (Blue)
- `#004DA6` (Dark Blue)
- `#64788F` (Gray Blue)
- `#C0C0C4` (Light Gray)
- `#82828C` (Gray)
- `#1A1C20` (Dark Gray)

## Error Handling

The projects resource follows standard SDK error handling:

```php
$result = $teamleader->projects()->create($projectData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Projects API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Project API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **CRUD operations**: 1 request per call
- **Relationship operations**: 1 request per call (add/remove customer, deal, quotation, owner, assignee)
- **Status operations**: 1 request per call (close, reopen, duplicate, delete)

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ProjectController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $projects = $teamleader->projects()->open();
        return view('projects.index', compact('projects'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $project = $teamleader->projects()->create($request->validated());
        return redirect()->route('projects.show', $project['data']['id']);
    }
    
    public function close($id, TeamleaderSDK $teamleader)
    {
        $teamleader->projects()->close($id, 'mark_tasks_and_materials_as_done');
        return redirect()->back()->with('success', 'Project closed successfully');
    }
}
```

## Differences from Legacy Projects

The new Projects API v2 differs from the legacy projects API:

- **Endpoint**: Uses `projects-v2/projects` instead of `projects`
- **Enhanced features**: Better budget tracking, custom fields support
- **Relationship management**: Dedicated endpoints for managing customers, deals, quotations
- **Improved filtering**: More granular filtering options
- **Status management**: Specific endpoints for closing/reopening projects

## Notes

- Sorting on `cost` or `margin` is only available to administrators and users with "Costs on projects" permission
- Custom fields can be included using the `custom_fields` include parameter
- The `legacy_project` include provides backward compatibility with the old projects API
- Projects can have multiple customers, owners, and assignees
- Time budget is stored in seconds but can be specified in hours, minutes, or seconds
- All monetary values use the format `{amount, currency}` where currency is a 3-letter ISO code

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

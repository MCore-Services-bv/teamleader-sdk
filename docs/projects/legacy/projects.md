# Legacy Projects

Manage legacy projects in Teamleader Focus. This resource provides complete CRUD operations for managing projects, including milestones, participants, and project lifecycle management (close/reopen operations).

## Endpoint

`legacyProjects`

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

Get a paginated list of projects with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
$projects = $teamleader->legacyProjects()->list([
    'status' => 'active'
]);
```

### `info()`

Get detailed information about a specific project.

**Parameters:**
- `id` (string): Project UUID

**Example:**
```php
$project = $teamleader->legacyProjects()->info('project-uuid-here');
```

### `create()`

Create a new project.

**Parameters:**
- `data` (array): Array of project data

**Required fields:**
- `title` (string): Project title
- `starts_on` (string): Start date (Y-m-d format)
- `milestones` (array): At least one milestone required
- `participants` (array): At least one participant required

**Example:**
```php
$project = $teamleader->legacyProjects()->create([
    'title' => 'New company website',
    'description' => 'Complete redesign of the company website',
    'starts_on' => '2024-01-01',
    'milestones' => [
        [
            'starts_on' => '2024-01-01',
            'due_on' => '2024-03-01',
            'name' => 'Initial setup',
            'responsible_user_id' => 'user-uuid'
        ]
    ],
    'participants' => [
        [
            'participant' => [
                'type' => 'user',
                'id' => 'user-uuid'
            ],
            'role' => 'decision_maker'
        ]
    ],
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

### `update()`

Update an existing project.

**Parameters:**
- `id` (string): Project UUID
- `data` (array): Array of data to update

**Example:**
```php
$project = $teamleader->legacyProjects()->update('project-uuid', [
    'title' => 'Updated project title',
    'status' => 'on_hold'
]);
```

### `delete()`

Delete a project.

**Parameters:**
- `id` (string): Project UUID

**Example:**
```php
$result = $teamleader->legacyProjects()->delete('project-uuid');
```

### `close()`

Close a project, all its phases, and all tasks within each phase (but not meetings).

**Parameters:**
- `id` (string): Project UUID

**Example:**
```php
$result = $teamleader->legacyProjects()->close('project-uuid');
```

### `reopen()`

Reopen a closed project, changing its status to "active".

**Parameters:**
- `id` (string): Project UUID

**Example:**
```php
$result = $teamleader->legacyProjects()->reopen('project-uuid');
```

### `addParticipant()`

Add a participant to a project.

**Parameters:**
- `id` (string): Project UUID
- `participant` (array): Participant data with 'type' and 'id' keys
- `role` (string): Participant role ('decision_maker' or 'member', default: 'member')

**Example:**
```php
$result = $teamleader->legacyProjects()->addParticipant('project-uuid', [
    'type' => 'user',
    'id' => 'user-uuid'
], 'decision_maker');
```

### `updateParticipant()`

Update a participant's role for a project.

**Parameters:**
- `id` (string): Project UUID
- `participant` (array): Participant data with 'type' and 'id' keys
- `role` (string): New role ('decision_maker' or 'member')

**Example:**
```php
$result = $teamleader->legacyProjects()->updateParticipant('project-uuid', [
    'type' => 'user',
    'id' => 'user-uuid'
], 'member');
```

## Convenience Methods

### `active()`

Get all active projects.

**Example:**
```php
$activeProjects = $teamleader->legacyProjects()->active();
```

### `byStatus()`

Get projects by status.

**Parameters:**
- `status` (string): Status filter ('active', 'on_hold', 'done', 'cancelled')
- `options` (array): Additional options

**Example:**
```php
$onHoldProjects = $teamleader->legacyProjects()->byStatus('on_hold');
```

### `forCustomer()`

Get projects for a specific customer.

**Parameters:**
- `customerId` (string): Customer UUID
- `customerType` (string): Customer type ('company' or 'contact', default: 'company')
- `options` (array): Additional options

**Example:**
```php
$companyProjects = $teamleader->legacyProjects()->forCustomer('company-uuid', 'company');
```

### `forParticipant()`

Get projects for a specific participant.

**Parameters:**
- `participantId` (string): Participant UUID
- `options` (array): Additional options

**Example:**
```php
$userProjects = $teamleader->legacyProjects()->forParticipant('user-uuid');
```

### `search()`

Search projects by term (searches title or description).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$results = $teamleader->legacyProjects()->search('website');
```

### `updatedSince()`

Get projects updated since a specific date.

**Parameters:**
- `datetime` (string): ISO 8601 datetime
- `options` (array): Additional options

**Example:**
```php
$recentProjects = $teamleader->legacyProjects()->updatedSince('2024-01-01T00:00:00+00:00');
```

## Filtering

Available filters:

- `customer.type` / `customer.id`: Filter by customer (company or contact)
- `status`: Project status (active, on_hold, done, cancelled)
- `participant_id`: Filter by participant UUID
- `term`: Search term (searches title or description)
- `updated_since`: ISO 8601 datetime

**Example:**
```php
// Complex filtering
$projects = $teamleader->legacyProjects()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'status' => 'active',
    'participant_id' => 'user-uuid'
], [
    'page_size' => 50,
    'sort_field' => 'due_on',
    'sort_order' => 'asc'
]);
```

## Sorting

Available sort fields:

- `due_on`: Sort by due date
- `title`: Sort by project title
- `created_at`: Sort by creation date

**Example:**
```php
$projects = $teamleader->legacyProjects()->list([], [
    'sort_field' => 'title',
    'sort_order' => 'asc'
]);

// Or using the full sort array
$projects = $teamleader->legacyProjects()->list([], [
    'sort' => [
        ['field' => 'due_on', 'order' => 'desc'],
        ['field' => 'title', 'order' => 'asc']
    ]
]);
```

## Project Status Workflow

Projects can have the following statuses:

- `active`: Project is currently active
- `on_hold`: Project is temporarily on hold
- `done`: Project is completed
- `cancelled`: Project is cancelled

**Example workflow:**
```php
// Create a project
$project = $teamleader->legacyProjects()->create([...]);

// Put on hold
$updated = $teamleader->legacyProjects()->update($project['data']['id'], [
    'status' => 'on_hold'
]);

// Reactivate
$updated = $teamleader->legacyProjects()->update($project['data']['id'], [
    'status' => 'active'
]);

// Close when complete
$closed = $teamleader->legacyProjects()->close($project['data']['id']);

// Reopen if needed
$reopened = $teamleader->legacyProjects()->reopen($project['data']['id']);
```

## Milestones

Each project requires at least one milestone. Milestones structure:

```php
[
    'starts_on' => '2024-01-01',  // Optional, nullable
    'due_on' => '2024-03-01',     // Required
    'name' => 'Phase 1',          // Required
    'responsible_user_id' => 'user-uuid'  // Required
]
```

## Participants

Each project requires at least one decision-making participant. Participants structure:

```php
[
    'participant' => [
        'type' => 'user',  // Required, currently only 'user' is supported
        'id' => 'user-uuid'  // Required
    ],
    'role' => 'decision_maker'  // 'decision_maker' or 'member'
]
```

## Custom Fields

Projects support custom fields:

```php
$project = $teamleader->legacyProjects()->create([
    // ... other fields
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'custom value'
        ]
    ]
]);
```

## Budget Management

Projects can have budget information (only accessible for project administrators):

```php
$project = $teamleader->legacyProjects()->update('project-uuid', [
    'budget' => [
        'amount' => 50000,
        'currency' => 'EUR'
    ]
]);
```

## Error Handling

The legacy projects resource follows standard SDK error handling:

```php
$result = $teamleader->legacyProjects()->create($projectData);

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
- **Participant operations**: 1 request per call
- **Close/Reopen operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ProjectController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $projects = $teamleader->legacyProjects()->active();
        return view('projects.index', compact('projects'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $project = $teamleader->legacyProjects()->create($request->validated());
        return redirect()->route('projects.show', $project['data']['id']);
    }
    
    public function close($id, TeamleaderSDK $teamleader)
    {
        $teamleader->legacyProjects()->close($id);
        return redirect()->route('projects.index')
            ->with('success', 'Project closed successfully');
    }
}
```

## Complete Example

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

$teamleader = app(TeamleaderSDK::class);

// Create a comprehensive project
$project = $teamleader->legacyProjects()->create([
    'title' => 'New Website Development',
    'description' => 'Complete redesign and development of the company website',
    'starts_on' => '2024-01-01',
    'milestones' => [
        [
            'name' => 'Discovery & Planning',
            'starts_on' => '2024-01-01',
            'due_on' => '2024-01-31',
            'responsible_user_id' => 'project-manager-uuid'
        ],
        [
            'name' => 'Design Phase',
            'starts_on' => '2024-02-01',
            'due_on' => '2024-02-28',
            'responsible_user_id' => 'designer-uuid'
        ],
        [
            'name' => 'Development',
            'starts_on' => '2024-03-01',
            'due_on' => '2024-05-31',
            'responsible_user_id' => 'developer-uuid'
        ]
    ],
    'participants' => [
        [
            'participant' => [
                'type' => 'user',
                'id' => 'project-manager-uuid'
            ],
            'role' => 'decision_maker'
        ],
        [
            'participant' => [
                'type' => 'user',
                'id' => 'developer-uuid'
            ],
            'role' => 'member'
        ]
    ],
    'customer' => [
        'type' => 'company',
        'id' => 'client-company-uuid'
    ],
    'budget' => [
        'amount' => 50000,
        'currency' => 'EUR'
    ],
    'purchase_order_number' => 'PO-2024-001',
    'custom_fields' => [
        [
            'id' => 'project-type-field-uuid',
            'value' => 'Website Development'
        ]
    ]
]);

// Get project info
$projectInfo = $teamleader->legacyProjects()->info($project['data']['id']);

// Add a participant
$teamleader->legacyProjects()->addParticipant(
    $project['data']['id'],
    ['type' => 'user', 'id' => 'new-member-uuid'],
    'member'
);

// Update project status
$teamleader->legacyProjects()->update($project['data']['id'], [
    'status' => 'active'
]);

// Close project when complete
$teamleader->legacyProjects()->close($project['data']['id']);
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

# Time Tracking

Manage time tracking entries in Teamleader Focus.

## Overview

The Time Tracking resource provides full CRUD (Create, Read, Update, Delete) operations for managing time tracking entries. Time tracking entries can be linked to various subjects (companies, contacts, events, milestones, tickets, projects), include materials, and support multiple tracking variants.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [resume()](#resume)
- [Helper Methods](#helper-methods)
- [Tracking Variants](#tracking-variants)
- [Filters](#filters)
- [Sorting](#sorting)
- [Sideloading](#sideloading)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`timeTracking`

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

Get all time tracking entries with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, sort, include)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all time tracking entries
$entries = Teamleader::timeTracking()->list();

// Get entries for specific user
$entries = Teamleader::timeTracking()->list([
    'user_id' => 'user-uuid'
]);

// With pagination and sorting
$entries = Teamleader::timeTracking()->list([], [
    'page_size' => 50,
    'page_number' => 1,
    'sort' => 'started_at',
    'sort_order' => 'desc'
]);
```

### `info()`

Get detailed information about a specific time tracking entry.

**Parameters:**
- `id` (string): Time tracking entry UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Get entry information
$entry = Teamleader::timeTracking()->info('entry-uuid');

// With materials
$entry = Teamleader::timeTracking()->info('entry-uuid', 'materials');

// Using fluent interface
$entry = Teamleader::timeTracking()
    ->withMaterials()
    ->info('entry-uuid');
```

### `create()`

Create a new time tracking entry. Supports three tracking variants.

**Variant 1: started_at + duration**
```php
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T09:00:00+00:00',
    'duration' => 3600, // seconds
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'work_type_id' => 'work-type-uuid',
    'description' => 'Client meeting'
]);
```

**Variant 2: started_at + ended_at**
```php
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T09:00:00+00:00',
    'ended_at' => '2025-10-17T11:00:00+00:00',
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'work_type_id' => 'work-type-uuid'
]);
```

**Variant 3: started_on + duration (duration tracking mode)**
```php
$entry = Teamleader::timeTracking()->create([
    'started_on' => '2025-10-17',
    'duration' => 7200, // seconds
    'subject' => [
        'type' => 'project',
        'id' => 'project-uuid'
    ],
    'work_type_id' => 'work-type-uuid'
]);
```

**Optional Fields:**
- `user_id` (string): User UUID (defaults to current user)
- `description` (string): Entry description
- `invoiceable` (boolean): Whether entry is invoiceable
- `materials` (array): Array of materials used

### `update()`

Update an existing time tracking entry.

**Parameters:**
- `id` (string): Entry UUID
- `data` (array): Fields to update

**Example:**
```php
$entry = Teamleader::timeTracking()->update('entry-uuid', [
    'description' => 'Updated description',
    'invoiceable' => false
]);
```

### `delete()`

Delete a time tracking entry.

**Parameters:**
- `id` (string): Entry UUID

**Example:**
```php
$result = Teamleader::timeTracking()->delete('entry-uuid');
```

### `resume()`

Resume a timer based on previously tracked time.

**Parameters:**
- `id` (string): Entry UUID to resume from
- `startedAt` (string|null): Optional start datetime

**Example:**
```php
// Resume with current time
$result = Teamleader::timeTracking()->resume('entry-uuid');

// Resume with specific start time
$result = Teamleader::timeTracking()->resume(
    'entry-uuid',
    '2025-10-17T14:00:00+00:00'
);
```

## Helper Methods

### User Filtering

```php
// Get entries for specific user
$entries = Teamleader::timeTracking()->forUser('user-uuid');
```

### Subject Filtering

```php
// Get entries for specific subject
$entries = Teamleader::timeTracking()->forSubject(
    'company-uuid',
    'company'
);

// Get entries for multiple subject types
$entries = Teamleader::timeTracking()->forSubjectTypes([
    'company',
    'project',
    'ticket'
]);
```

### Date Filtering

```php
// Get entries between dates (started)
$entries = Teamleader::timeTracking()->betweenDates(
    '2025-10-01T00:00:00+00:00',
    '2025-10-31T23:59:59+00:00'
);

// Get entries by ended date
$entries = Teamleader::timeTracking()->endedBetween(
    '2025-10-01T00:00:00+00:00',
    '2025-10-31T23:59:59+00:00'
);
```

### Relation Filtering

```php
// Get entries related to milestone
$entries = Teamleader::timeTracking()->relatedTo(
    'milestone-uuid',
    'milestone'
);

// Get entries related to project
$entries = Teamleader::timeTracking()->relatedTo(
    'project-uuid',
    'project'
);
```

### Sideloading Helpers

```php
// Load with materials
$entries = Teamleader::timeTracking()
    ->withMaterials()
    ->list();

// Load with relations
$entries = Teamleader::timeTracking()
    ->withRelations()
    ->list();

// Load with both
$entries = Teamleader::timeTracking()
    ->withMaterials()
    ->withRelations()
    ->list();
```

## Tracking Variants

The Time Tracking API supports three variants for recording time:

### Variant 1: Started At + Duration

Used when you know when work started and total duration.

```php
[
    'started_at' => '2025-10-17T09:00:00+00:00',
    'duration' => 3600, // 1 hour in seconds
    'subject' => [...],
    'work_type_id' => 'work-type-uuid'
]
```

### Variant 2: Started At + Ended At

Used when you know exact start and end times.

```php
[
    'started_at' => '2025-10-17T09:00:00+00:00',
    'ended_at' => '2025-10-17T11:30:00+00:00',
    'subject' => [...],
    'work_type_id' => 'work-type-uuid'
]
```

### Variant 3: Started On + Duration

Used for duration tracking mode (without specific times).

```php
[
    'started_on' => '2025-10-17',
    'duration' => 7200, // 2 hours in seconds
    'subject' => [...],
    'work_type_id' => 'work-type-uuid'
]
```

## Filters

Available filters for the `list()` method:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of entry UUIDs |
| `user_id` | string | Filter by user UUID |
| `subject` | object | Filter by subject (type and id) |
| `subject_types` | array | Filter by subject types |
| `started_after` | string | Entries started after datetime |
| `started_before` | string | Entries started before datetime |
| `ended_after` | string | Entries ended after datetime |
| `ended_before` | string | Entries ended before datetime |
| `relates_to` | object | Filter by related milestone or project |
| `invoiced` | boolean | Filter by invoiced status |
| `invoiceable` | boolean | Filter by invoiceable status |

### Subject Filter Structure

```php
[
    'subject' => [
        'type' => 'company', // or contact, event, milestone, ticket, project
        'id' => 'uuid-here'
    ]
]
```

### Relates To Filter Structure

```php
[
    'relates_to' => [
        'type' => 'milestone', // or project
        'id' => 'uuid-here'
    ]
]
```

## Sorting

Available sort fields:

| Field | Description |
|-------|-------------|
| `started_at` | Sort by start date/time |

**Example:**
```php
$entries = Teamleader::timeTracking()->list([], [
    'sort' => 'started_at',
    'sort_order' => 'desc' // or 'asc'
]);
```

## Sideloading

Available includes:

| Include | Description |
|---------|-------------|
| `materials` | Materials used in the entry |
| `relates_to` | Related milestone or project |

**Example:**
```php
$entry = Teamleader::timeTracking()
    ->with('materials,relates_to')
    ->info('entry-uuid');
```

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'entry-uuid',
            'description' => 'Client meeting',
            'started_at' => '2025-10-17T09:00:00+00:00',
            'ended_at' => '2025-10-17T11:00:00+00:00',
            'duration' => 7200,
            'invoiceable' => true,
            'invoiced' => false,
            'user' => [
                'type' => 'user',
                'id' => 'user-uuid'
            ],
            'subject' => [
                'type' => 'company',
                'id' => 'company-uuid'
            ],
            'work_type' => [
                'type' => 'workType',
                'id' => 'work-type-uuid'
            ]
        ]
    ],
    'meta' => [
        'page' => [
            'size' => 20,
            'number' => 1
        ],
        'matches' => 156
    ]
]
```

### Info Response with Materials

```php
[
    'data' => [
        'id' => 'entry-uuid',
        'description' => 'Installation work',
        'started_at' => '2025-10-17T09:00:00+00:00',
        'ended_at' => '2025-10-17T13:00:00+00:00',
        'duration' => 14400,
        'invoiceable' => true,
        'materials' => [
            [
                'material' => [
                    'type' => 'material',
                    'id' => 'material-uuid'
                ],
                'quantity' => 5
            ]
        ]
    ]
]
```

## Usage Examples

### Track Time with Duration

```php
// Track 2 hours of work
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T09:00:00+00:00',
    'duration' => 7200, // 2 hours
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'work_type_id' => 'consulting-work-type-uuid',
    'description' => 'Strategic planning session',
    'invoiceable' => true
]);
```

### Track Time with Start and End

```php
// Track time with exact times
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T10:00:00+00:00',
    'ended_at' => '2025-10-17T12:30:00+00:00',
    'subject' => [
        'type' => 'ticket',
        'id' => 'ticket-uuid'
    ],
    'work_type_id' => 'support-work-type-uuid',
    'description' => 'Bug fix and testing'
]);
```

### Track Time for Project

```php
// Track time on project work
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T14:00:00+00:00',
    'duration' => 10800, // 3 hours
    'subject' => [
        'type' => 'project',
        'id' => 'project-uuid'
    ],
    'work_type_id' => 'development-work-type-uuid',
    'description' => 'Frontend development',
    'invoiceable' => true
]);
```

### Track Time with Materials

```php
// Track time including materials used
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T08:00:00+00:00',
    'ended_at' => '2025-10-17T12:00:00+00:00',
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'work_type_id' => 'installation-work-type-uuid',
    'description' => 'Equipment installation',
    'invoiceable' => true,
    'materials' => [
        [
            'material' => [
                'type' => 'material',
                'id' => 'cable-material-uuid'
            ],
            'quantity' => 10
        ],
        [
            'material' => [
                'type' => 'material',
                'id' => 'connector-material-uuid'
            ],
            'quantity' => 20
        ]
    ]
]);
```

### Get Monthly Time Report

```php
// Get all time entries for current month
$startOfMonth = date('Y-m-01T00:00:00+00:00');
$endOfMonth = date('Y-m-tT23:59:59+00:00');

$entries = Teamleader::timeTracking()->betweenDates(
    $startOfMonth,
    $endOfMonth
);

// Calculate total hours
$totalSeconds = array_sum(array_column($entries['data'], 'duration'));
$totalHours = $totalSeconds / 3600;

echo "Total hours tracked this month: " . number_format($totalHours, 2);
```

### Resume Previous Work Session

```php
// Find last entry for a subject
$entries = Teamleader::timeTracking()->forSubject(
    'project-uuid',
    'project'
);

if (!empty($entries['data'])) {
    $lastEntry = $entries['data'][0];
    
    // Resume timer based on this entry
    Teamleader::timeTracking()->resume($lastEntry['id']);
}
```

## Common Use Cases

### 1. Weekly Time Sheet

```php
// Get entries for user for current week
$weekStart = date('Y-m-d', strtotime('monday this week')) . 'T00:00:00+00:00';
$weekEnd = date('Y-m-d', strtotime('sunday this week')) . 'T23:59:59+00:00';

$entries = Teamleader::timeTracking()->forUser('user-uuid')
    ->betweenDates($weekStart, $weekEnd);

// Group by day
$byDay = [];
foreach ($entries['data'] as $entry) {
    $day = date('Y-m-d', strtotime($entry['started_at']));
    if (!isset($byDay[$day])) {
        $byDay[$day] = [];
    }
    $byDay[$day][] = $entry;
}
```

### 2. Project Time Tracking Report

```php
// Get all time for project
$projectEntries = Teamleader::timeTracking()
    ->withMaterials()
    ->forSubject('project-uuid', 'project');

// Calculate totals
$totalHours = 0;
$invoiceableHours = 0;

foreach ($projectEntries['data'] as $entry) {
    $hours = $entry['duration'] / 3600;
    $totalHours += $hours;
    
    if ($entry['invoiceable']) {
        $invoiceableHours += $hours;
    }
}

echo "Total: {$totalHours}h, Invoiceable: {$invoiceableHours}h";
```

### 3. Invoicing Preparation

```php
// Get uninvoiced, invoiceable entries for customer
$entries = Teamleader::timeTracking()->list([
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'invoiceable' => true,
    'invoiced' => false
]);

// Calculate amount to invoice
$totalHours = array_sum(
    array_map(
        fn($e) => $e['duration'] / 3600,
        $entries['data']
    )
);

$hourlyRate = 75; // EUR
$amount = $totalHours * $hourlyRate;
```

### 4. User Productivity Report

```php
// Get entries for team
$teamMembers = ['user1-uuid', 'user2-uuid', 'user3-uuid'];
$monthStart = date('Y-m-01T00:00:00+00:00');
$monthEnd = date('Y-m-tT23:59:59+00:00');

$productivity = [];

foreach ($teamMembers as $userId) {
    $entries = Teamleader::timeTracking()->forUser($userId)
        ->betweenDates($monthStart, $monthEnd);
    
    $totalHours = array_sum(
        array_map(
            fn($e) => $e['duration'] / 3600,
            $entries['data']
        )
    );
    
    $productivity[$userId] = $totalHours;
}
```

## Best Practices

### 1. Always Include Descriptions

```php
// Good: Clear description
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T09:00:00+00:00',
    'duration' => 3600,
    'subject' => [...],
    'work_type_id' => 'work-type-uuid',
    'description' => 'Implemented user authentication feature'
]);

// Less helpful: No description
```

### 2. Use Correct Subject Types

```php
// Available subject types
$validTypes = [
    'company',
    'contact',
    'event',
    'milestone',
    'ticket',
    'project'
];
```

### 3. Set Invoiceable Flag Correctly

```php
// Mark billable work as invoiceable
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T09:00:00+00:00',
    'duration' => 3600,
    'subject' => [...],
    'work_type_id' => 'consulting-work-type-uuid',
    'invoiceable' => true, // Will appear on invoices
    'description' => 'Business consultation'
]);

// Mark internal work as non-invoiceable
$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T14:00:00+00:00',
    'duration' => 1800,
    'subject' => [...],
    'work_type_id' => 'admin-work-type-uuid',
    'invoiceable' => false, // Internal overhead
    'description' => 'Team standup meeting'
]);
```

### 4. Handle Pagination for Reports

```php
function getAllTimeEntries(array $filters): array
{
    $allEntries = [];
    $page = 1;
    $pageSize = 100;

    do {
        $response = Teamleader::timeTracking()->list($filters, [
            'page_size' => $pageSize,
            'page_number' => $page
        ]);

        $allEntries = array_merge($allEntries, $response['data']);
        $page++;
    } while (count($response['data']) === $pageSize);

    return $allEntries;
}
```

### 5. Validate Duration

```php
// Ensure duration is in seconds
$hours = 2.5;
$durationInSeconds = $hours * 3600; // Convert to seconds

$entry = Teamleader::timeTracking()->create([
    'started_at' => '2025-10-17T09:00:00+00:00',
    'duration' => $durationInSeconds,
    'subject' => [...],
    'work_type_id' => 'work-type-uuid'
]);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $entry = Teamleader::timeTracking()->create([
        'started_at' => '2025-10-17T09:00:00+00:00',
        'duration' => 3600,
        'subject' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'work_type_id' => 'work-type-uuid'
    ]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Time tracking validation failed', [
            'errors' => $e->getDetails()
        ]);
    } elseif ($e->getCode() === 404) {
        // Subject or work type not found
        Log::error('Resource not found', [
            'message' => $e->getMessage()
        ]);
    }
}
```

## Related Resources

- [Timers](timers.md) - Start and stop timers
- [Users](../general/users.md) - Users tracking time
- [Work Types](../general/work-types.md) - Categorize time entries
- [Projects](../projects/projects.md) - Track time on projects
- [Invoices](../invoicing/invoices.md) - Invoice time entries

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
- [Sideloading](../sideloading.md) - Load related data

# Time Tracking

Manage time tracking entries in Teamleader Focus. This resource provides complete CRUD operations for managing time entries, including timer management and tracking time against various entities like companies, contacts, projects, and tickets.

## Endpoint

`timeTracking`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported (by starts_on)
- **Supports Sideloading**: ✅ Supported (materials, relates_to)
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Time Entry Variants

Time tracking entries can be created in three different ways:

1. **Duration-based (with started_at)**: Specify a start time and duration in seconds
2. **Time range (with ended_at)**: Specify start and end times
3. **Date-based duration**: Specify a date and duration (for duration tracking mode)

## Available Methods

### `list()`

Get a paginated list of time tracking entries with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$entries = $teamleader->timeTracking()->list([
    'user_id' => 'user-uuid',
    'started_after' => '2024-01-01T00:00:00+00:00'
]);
```

### `info()`

Get detailed information about a specific time tracking entry.

**Parameters:**
- `id` (string): Time tracking entry UUID
- `includes` (array|string): Relations to include (materials, relates_to)

**Example:**
```php
$entry = $teamleader->timeTracking()->info('entry-uuid-here');

// With includes
$entry = $teamleader->timeTracking()->info('entry-uuid', ['materials', 'relates_to']);
```

### `create()`

Create a new time tracking entry. Supports three variants:

**Variant 1: Duration-based (started_at + duration)**
```php
$entry = $teamleader->timeTracking()->create([
    'started_at' => '2024-01-15T10:00:00+00:00',
    'duration' => 3600, // in seconds
    'work_type_id' => 'work-type-uuid',
    'description' => 'Development work',
    'subject' => [
        'id' => 'company-uuid',
        'type' => 'company'
    ],
    'invoiceable' => true
]);
```

**Variant 2: Time range (started_at + ended_at)**
```php
$entry = $teamleader->timeTracking()->create([
    'started_at' => '2024-01-15T10:00:00+00:00',
    'ended_at' => '2024-01-15T11:30:00+00:00',
    'work_type_id' => 'work-type-uuid',
    'subject' => [
        'id' => 'ticket-uuid',
        'type' => 'ticket'
    ]
]);
```

**Variant 3: Date-based duration (started_on + duration)**
```php
$entry = $teamleader->timeTracking()->create([
    'started_on' => '2024-01-15',
    'duration' => 7200, // in seconds
    'work_type_id' => 'work-type-uuid',
    'subject' => [
        'id' => 'project-uuid',
        'type' => 'milestone'
    ]
]);
```

**Optional Parameters:**
- `work_type_id` (string): Work type UUID
- `description` (string): Description of the work
- `subject` (object): Entity the time is tracked against (id + type)
- `invoiceable` (boolean): Whether entry is invoiceable
- `user_id` (string): User UUID (to add time for another user)

**Available Subject Types:**
- `company`
- `contact`
- `event`
- `milestone`
- `nextgenTask`
- `ticket`
- `todo`

### `update()`

Update an existing time tracking entry. Supports the same variants as create.

**Parameters:**
- `id` (string): Time tracking entry UUID
- `data` (array): Array of data to update

**Example:**
```php
$entry = $teamleader->timeTracking()->update('entry-uuid', [
    'started_at' => '2024-01-15T10:00:00+00:00',
    'duration' => 7200,
    'description' => 'Updated description'
]);
```

### `delete()`

Delete a time tracking entry.

**Parameters:**
- `id` (string): Time tracking entry UUID

**Example:**
```php
$result = $teamleader->timeTracking()->delete('entry-uuid');
```

### `resume()`

Start a new timer based on previously tracked time. Copies the work type, subject, and description from the original entry.

**Parameters:**
- `id` (string): Original time tracking entry UUID to resume from
- `startedAt` (string|null): Optional start time (defaults to current time)

**Example:**
```php
// Resume with current time
$newEntry = $teamleader->timeTracking()->resume('original-entry-uuid');

// Resume with specific start time
$newEntry = $teamleader->timeTracking()->resume(
    'original-entry-uuid',
    '2024-01-15T14:00:00+00:00'
);
```

## Filter Methods

### `forUser()`

Get time tracking entries for a specific user.

**Parameters:**
- `userId` (string): User UUID
- `options` (array): Additional options

**Example:**
```php
$entries = $teamleader->timeTracking()->forUser('user-uuid');
```

### `forSubject()`

Get time tracking entries for a specific subject entity.

**Parameters:**
- `subjectId` (string): Subject entity UUID
- `subjectType` (string): Subject type (company, contact, etc.)
- `options` (array): Additional options

**Example:**
```php
$entries = $teamleader->timeTracking()->forSubject(
    'company-uuid',
    'company'
);
```

### `forSubjectTypes()`

Get time tracking entries filtered by subject types.

**Parameters:**
- `subjectTypes` (array): Array of subject types
- `options` (array): Additional options

**Example:**
```php
$entries = $teamleader->timeTracking()->forSubjectTypes([
    'company',
    'ticket'
]);
```

### `betweenDates()`

Get time tracking entries within a date range (by start date).

**Parameters:**
- `startDate` (string): Start date (ISO 8601 format)
- `endDate` (string): End date (ISO 8601 format)
- `options` (array): Additional options

**Example:**
```php
$entries = $teamleader->timeTracking()->betweenDates(
    '2024-01-01T00:00:00+00:00',
    '2024-01-31T23:59:59+00:00'
);
```

### `endedBetween()`

Get time tracking entries within a date range (by end date).

**Parameters:**
- `startDate` (string): Start date (ISO 8601 format)
- `endDate` (string): End date (ISO 8601 format)
- `options` (array): Additional options

**Example:**
```php
$entries = $teamleader->timeTracking()->endedBetween(
    '2024-01-01T00:00:00+00:00',
    '2024-01-31T23:59:59+00:00'
);
```

### `relatedTo()`

Get time tracking entries related to a milestone or project.

**Parameters:**
- `entityId` (string): Milestone or project UUID
- `entityType` (string): Entity type (milestone or project)
- `options` (array): Additional options

**Example:**
```php
$entries = $teamleader->timeTracking()->relatedTo(
    'project-uuid',
    'project'
);
```

## Fluent Interface for Includes

### `withMaterials()`

Include materials data in the response.

**Example:**
```php
$entries = $teamleader->timeTracking()
    ->withMaterials()
    ->list();
```

### `withRelations()`

Include relates_to data in the response.

**Example:**
```php
$entries = $teamleader->timeTracking()
    ->withRelations()
    ->list();
```

### Chaining Multiple Includes

```php
$entries = $teamleader->timeTracking()
    ->withMaterials()
    ->withRelations()
    ->forUser('user-uuid')
    ->betweenDates('2024-01-01', '2024-01-31')
    ->list();
```

## Available Filters

When using the `list()` method directly, you can pass these filters:

- `ids` (array): Filter by specific time tracking entry UUIDs
- `user_id` (string): Filter by user UUID
- `started_after` (string): Start of period (ISO 8601 datetime)
- `started_before` (string): End of period (ISO 8601 datetime)
- `ended_after` (string): Start of period for ended entries (ISO 8601 datetime)
- `ended_before` (string): End of period for ended entries (ISO 8601 datetime)
- `subject` (object): Filter by subject (requires `id` and `type`)
- `subject_types` (array): Filter by subject types
- `relates_to` (object): Filter by related entity (requires `id` and `type`)

**Example:**
```php
$entries = $teamleader->timeTracking()->list([
    'user_id' => 'user-uuid',
    'started_after' => '2024-01-01T00:00:00+00:00',
    'started_before' => '2024-01-31T23:59:59+00:00',
    'subject_types' => ['company', 'ticket'],
    'relates_to' => [
        'id' => 'project-uuid',
        'type' => 'project'
    ]
]);
```

## Sorting

Time tracking entries can be sorted by `starts_on` field:

**Example:**
```php
$entries = $teamleader->timeTracking()->list(
    [],
    [
        'sort' => 'starts_on',
        'sort_order' => 'desc'
    ]
);
```

## Pagination

**Example:**
```php
$entries = $teamleader->timeTracking()->list(
    [],
    [
        'page_size' => 50,
        'page_number' => 2
    ]
);
```

## Response Structure

### Time Tracking Entry Object

```php
[
    'id' => 'uuid',
    'user' => [
        'id' => 'user-uuid',
        'type' => 'user'
    ],
    'work_type' => [
        'id' => 'work-type-uuid',
        'type' => 'workType'
    ],
    'started_on' => '2024-01-15',
    'started_at' => '2024-01-15T10:00:00+00:00',  // nullable
    'ended_at' => '2024-01-15T11:30:00+00:00',    // nullable
    'duration' => 5400,  // in seconds
    'description' => 'Development work',
    'subject' => [
        'id' => 'subject-uuid',
        'type' => 'company'
    ],
    'invoiceable' => true,
    'locked' => false,
    'billing_info' => [
        'type' => 'invoice',
        'invoice' => [
            'id' => 'invoice-uuid',
            'type' => 'invoice'
        ]
    ],
    'materials' => [  // only with includes=materials
        [
            'product' => [
                'id' => 'product-uuid',
                'type' => 'product'
            ],
            'description' => 'Material description',
            'unit_price' => [
                'amount' => 123.30,
                'currency' => 'EUR'
            ],
            'quantity' => 5
        ]
    ],
    'relates_to' => [  // only with includes=relates_to
        [
            'id' => 'project-uuid',
            'type' => 'project'
        ]
    ],
    'hourly_rate' => [  // only for users with invoicing access
        'amount' => 75.00,
        'currency' => 'EUR'
    ],
    'meta' => [
        'updatable' => true
    ]
]
```

## Important Notes

1. **Time Span Splitting**: Time tracking entries will be automatically split up if the time span passes midnight.

2. **Locked Entries**: Once the freeze time window has passed, entries become locked (`locked: true`). Users with appropriate permissions can still update locked entries if `meta.updatable` is true.

3. **Duration Format**: Duration is always in seconds. For example, 1 hour = 3600 seconds.

4. **Date Formats**: Always use ISO 8601 format for dates and datetimes:
    - Datetime: `2024-01-15T10:00:00+00:00`
    - Date: `2024-01-15`

5. **Billing Info**: The `billing_info` field indicates whether the entry has been invoiced or is part of a prepaid package.

6. **Hourly Rate**: Only included in responses for users with access to invoicing.

## Complete Example

```php
// Create a time entry
$entry = $teamleader->timeTracking()->create([
    'started_at' => '2024-01-15T09:00:00+00:00',
    'duration' => 7200,
    'work_type_id' => 'development-work-type-uuid',
    'description' => 'Implemented new feature',
    'subject' => [
        'id' => 'project-uuid',
        'type' => 'milestone'
    ],
    'invoiceable' => true
]);

// Get all time entries for a user in January 2024
$januaryEntries = $teamleader->timeTracking()
    ->withMaterials()
    ->withRelations()
    ->forUser('user-uuid')
    ->betweenDates(
        '2024-01-01T00:00:00+00:00',
        '2024-01-31T23:59:59+00:00'
    )
    ->list();

// Resume a previous entry
$newEntry = $teamleader->timeTracking()->resume($entry['data']['id']);

// Update the entry
$updated = $teamleader->timeTracking()->update($entry['data']['id'], [
    'duration' => 9000,
    'description' => 'Updated description'
]);

// Delete the entry
$teamleader->timeTracking()->delete($entry['data']['id']);
```

## Error Handling

```php
try {
    $entry = $teamleader->timeTracking()->create([
        'started_at' => '2024-01-15T10:00:00+00:00',
        'duration' => 3600
    ]);
} catch (\InvalidArgumentException $e) {
    // Validation error (e.g., invalid subject type, missing required fields)
    echo "Validation error: " . $e->getMessage();
} catch (\Exception $e) {
    // API error
    echo "API error: " . $e->getMessage();
}
```

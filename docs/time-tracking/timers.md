# Timers

Manage time tracking timers in Teamleader Focus. This resource allows you to start, stop, update, and retrieve the currently running timer. Timers are used to track time spent on various subjects like companies, contacts, events, tickets, and more.

## Endpoint

`timers`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported (start)
- **Supports Update**: ✅ Supported (current timer only)
- **Supports Deletion**: ❌ Not Supported (use stop instead)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `start()`

Start a new timer.

**Parameters:**
- `data` (array): Timer configuration
    - `work_type_id` (string, required): UUID of the work type
    - `subject` (array, required): Subject to track time for
        - `type` (string, required): Subject type (company, contact, event, todo, milestone, ticket)
        - `id` (string, required): Subject UUID
    - `description` (string, optional): Description of the work being performed
    - `started_at` (string, optional): ISO 8601 datetime. If not provided, current time will be used
    - `invoiceable` (boolean, optional): Whether the time is invoiceable

**Example:**
```php
$timer = $teamleader->timers()->start([
    'work_type_id' => 'db41328a-7a25-4e85-8fb9-830baacb7f40',
    'subject' => [
        'type' => 'company',
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471'
    ],
    'description' => 'Working on website redesign',
    'invoiceable' => true
]);
```

### `startForSubject()`

Start a timer for a specific subject (convenience method).

**Parameters:**
- `subjectType` (string): Type of subject (company, contact, event, todo, milestone, ticket)
- `subjectId` (string): UUID of the subject
- `workTypeId` (string): UUID of the work type
- `options` (array, optional): Additional options
    - `description` (string): Description of the work
    - `invoiceable` (boolean): Whether the time is invoiceable
    - `started_at` (string): ISO 8601 datetime

**Example:**
```php
// Start timer for a ticket
$timer = $teamleader->timers()->startForSubject(
    'ticket',
    'ticket-uuid-here',
    'work-type-uuid-here',
    [
        'description' => 'Fixing critical bug',
        'invoiceable' => true
    ]
);

// Start timer for a company
$timer = $teamleader->timers()->startForSubject(
    'company',
    'company-uuid-here',
    'work-type-uuid-here',
    ['description' => 'Client meeting']
);
```

### `current()`

Get the current running timer.

**Parameters:** None

**Returns:** Array with timer data if a timer is running, empty data array if no timer is running

**Example:**
```php
$currentTimer = $teamleader->timers()->current();

if (!empty($currentTimer['data'])) {
    echo "Timer is running: " . $currentTimer['data']['description'];
    echo "Started at: " . $currentTimer['data']['started_at'];
}
```

### `stop()`

Stop the current timer. This will add a new time tracking entry in the background.

**Parameters:** None

**Returns:** Array with the created time tracking entry reference

**Example:**
```php
$result = $teamleader->timers()->stop();

if (!empty($result['data'])) {
    echo "Timer stopped. Time tracking entry created with ID: " . $result['data']['id'];
}
```

### `update()`

Update the current timer. Only possible if there is a timer running.

**Parameters:**
- `data` (array): Data to update
    - `work_type_id` (string, nullable): UUID of the work type
    - `started_at` (string, optional): ISO 8601 datetime
    - `description` (string, nullable): Description of the work
    - `subject` (array, nullable): Subject to track time for
        - `type` (string, required if subject provided): Subject type
        - `id` (string, required if subject provided): Subject UUID
    - `invoiceable` (boolean, optional): Whether the time is invoiceable

**Example:**
```php
// Update description
$result = $teamleader->timers()->update([
    'description' => 'Updated: Working on database optimization'
]);

// Change subject
$result = $teamleader->timers()->update([
    'subject' => [
        'type' => 'ticket',
        'id' => 'new-ticket-uuid'
    ]
]);

// Update multiple fields
$result = $teamleader->timers()->update([
    'description' => 'Final review',
    'invoiceable' => false
]);
```

### `isRunning()`

Check if there is a timer currently running.

**Parameters:** None

**Returns:** Boolean (true if a timer is running, false otherwise)

**Example:**
```php
if ($teamleader->timers()->isRunning()) {
    echo "A timer is currently running";
} else {
    echo "No timer running";
}
```

### `getAvailableSubjectTypes()`

Get the list of available subject types for timers.

**Parameters:** None

**Returns:** Array of subject type strings

**Example:**
```php
$types = $teamleader->timers()->getAvailableSubjectTypes();
// Returns: ['company', 'contact', 'event', 'todo', 'milestone', 'ticket']
```

## Complete Workflow Examples

### Basic Timer Workflow

```php
// Start a timer
$timer = $teamleader->timers()->start([
    'work_type_id' => 'work-type-uuid',
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'description' => 'Client consultation',
    'invoiceable' => true
]);

// ... do some work ...

// Check what's running
$current = $teamleader->timers()->current();
echo "Currently tracking: " . $current['data']['description'];

// Update the timer
$teamleader->timers()->update([
    'description' => 'Client consultation - Project discussion'
]);

// Stop the timer
$result = $teamleader->timers()->stop();
```

### Checking Before Starting

```php
// Check if a timer is already running
if ($teamleader->timers()->isRunning()) {
    // Stop the current timer first
    $teamleader->timers()->stop();
}

// Now start a new timer
$timer = $teamleader->timers()->startForSubject(
    'ticket',
    'ticket-uuid',
    'work-type-uuid',
    ['description' => 'Bug fix']
);
```

### Working with Different Subjects

```php
// For a company
$teamleader->timers()->startForSubject(
    'company',
    'company-uuid',
    'work-type-uuid',
    ['description' => 'Business meeting']
);

// For a contact
$teamleader->timers()->startForSubject(
    'contact',
    'contact-uuid',
    'work-type-uuid',
    ['description' => 'Phone consultation']
);

// For a ticket
$teamleader->timers()->startForSubject(
    'ticket',
    'ticket-uuid',
    'work-type-uuid',
    ['description' => 'Technical support', 'invoiceable' => true]
);

// For a milestone
$teamleader->timers()->startForSubject(
    'milestone',
    'milestone-uuid',
    'work-type-uuid',
    ['description' => 'Project milestone work']
);
```

## Response Examples

### Start Timer Response

```php
[
    'data' => [
        'type' => 'timeTracking',
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471'
    ]
]
```

### Current Timer Response

```php
[
    'data' => [
        'id' => '2b282dec-ba9d-4faa-9b39-944b99ee5c0a',
        'user' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'user'
        ],
        'work_type' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'workType'
        ],
        'started_at' => '2017-04-26T10:01:49+00:00',
        'description' => 'Timer description',
        'subject' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'company'
        ],
        'invoiceable' => true
    ]
]
```

### Stop Timer Response

```php
[
    'data' => [
        'type' => 'timeTracking',
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471'
    ]
]
```

## Available Subject Types

- **company**: Track time for a company
- **contact**: Track time for a contact
- **event**: Track time for an event
- **todo**: Track time for a todo/task
- **milestone**: Track time for a project milestone
- **ticket**: Track time for a support ticket

## Notes

- Only one timer can be running at a time per user
- Stopping a timer automatically creates a time tracking entry
- The `started_at` parameter must be in ISO 8601 format (e.g., `2017-04-26T10:01:49+00:00`)
- If `started_at` is not provided when starting a timer, the current time will be used
- Timers must have a subject (the entity you're tracking time for)
- Update operations return a 204 status (no content) on success
- The timer tracks duration automatically from the `started_at` time until it's stopped

## Error Handling

```php
try {
    // Try to start a timer
    $timer = $teamleader->timers()->start([
        'work_type_id' => 'invalid-uuid',
        'subject' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ]);
} catch (\InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage();
} catch (\Exception $e) {
    echo "API error: " . $e->getMessage();
}

// Safe check for running timer
try {
    $current = $teamleader->timers()->current();
    if (!empty($current['data'])) {
        // Timer is running
    }
} catch (\Exception $e) {
    // No timer running or API error
}
```

# Timers

Manage time tracking timers in Teamleader Focus.

## Overview

The Timers resource provides methods for starting, stopping, and managing time tracking timers. Timers allow real-time tracking of work being performed, which automatically creates time tracking entries when stopped. Only one timer can run at a time per account.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [start()](#start)
    - [current()](#current)
    - [stop()](#stop)
    - [updateCurrent()](#updatecurrent)
    - [isRunning()](#isrunning)
- [Helper Methods](#helper-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`timers`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported (via start())
- **Update**: ✅ Supported (via updateCurrent())
- **Deletion**: ❌ Not Supported (use stop() instead)

## Available Methods

### `start()`

Start a new timer.

**Required Fields:**
- `work_type_id` (string): Work type UUID
- `subject` (object): Subject with type and id

**Optional Fields:**
- `description` (string): Timer description
- `invoiceable` (boolean): Whether time is invoiceable (default: true)
- `started_at` (string): Custom start time (ISO 8601 format)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$timer = Teamleader::timers()->start([
    'work_type_id' => 'work-type-uuid',
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'description' => 'Working on project',
    'invoiceable' => true
]);
```

### `current()`

Get the currently running timer (if any).

**Example:**
```php
// Get current timer
$timer = Teamleader::timers()->current();

// Returns empty data if no timer is running
if (!empty($timer['data'])) {
    echo "Timer is running!";
}
```

### `stop()`

Stop the current timer. This will automatically create a time tracking entry in the background.

**Example:**
```php
// Stop current timer
$result = Teamleader::timers()->stop();

// The timer data will be converted to a time tracking entry
```

### `updateCurrent()`

Update the current running timer.

**Parameters:**
- `data` (array): Fields to update (description, invoiceable)

**Example:**
```php
// Update description of running timer
$result = Teamleader::timers()->updateCurrent([
    'description' => 'Updated task description'
]);

// Change invoiceable status
$result = Teamleader::timers()->updateCurrent([
    'invoiceable' => false
]);
```

### `isRunning()`

Check if there is a timer currently running.

**Returns:** boolean

**Example:**
```php
if (Teamleader::timers()->isRunning()) {
    echo "A timer is currently active";
} else {
    echo "No timer running";
}
```

## Helper Methods

### `startForSubject()`

Convenience method to start a timer for a specific subject.

**Parameters:**
- `subjectType` (string): Type of subject
- `subjectId` (string): UUID of subject
- `workTypeId` (string): Work type UUID
- `options` (array): Optional fields (description, invoiceable, started_at)

**Example:**
```php
// Start timer for a company
$timer = Teamleader::timers()->startForSubject(
    'company',
    'company-uuid',
    'consulting-work-type-uuid',
    [
        'description' => 'Client consultation',
        'invoiceable' => true
    ]
);

// Start timer for a ticket
$timer = Teamleader::timers()->startForSubject(
    'ticket',
    'ticket-uuid',
    'support-work-type-uuid',
    ['description' => 'Bug fix']
);
```

## Available Subject Types

Valid subject types for timers:

- `company`
- `contact`
- `event`
- `todo`
- `milestone`
- `ticket`

## Response Structure

### Start/Current Response

```php
[
    'data' => [
        'id' => 'timer-uuid',
        'description' => 'Working on project',
        'started_at' => '2025-10-17T09:00:00+00:00',
        'invoiceable' => true,
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
]
```

### Stop Response

```php
[
    'data' => [
        'time_tracking' => [
            'type' => 'timeTracking',
            'id' => 'time-tracking-entry-uuid'
        ]
    ]
]
```

### Empty Response (No Timer Running)

```php
[
    'data' => []
]
```

## Usage Examples

### Start Timer for Company Work

```php
// Start tracking time for client work
$timer = Teamleader::timers()->start([
    'work_type_id' => 'consulting-work-type-uuid',
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'description' => 'Strategic planning session',
    'invoiceable' => true
]);

echo "Timer started at: " . $timer['data']['started_at'];
```

### Start Timer for Ticket

```php
// Track time spent on support ticket
$timer = Teamleader::timers()->startForSubject(
    'ticket',
    'ticket-uuid',
    'support-work-type-uuid',
    [
        'description' => 'Investigating reported issue',
        'invoiceable' => true
    ]
);
```

### Check and Update Running Timer

```php
// Check if timer is running
if (Teamleader::timers()->isRunning()) {
    // Get current timer details
    $timer = Teamleader::timers()->current();
    
    // Update description
    Teamleader::timers()->updateCurrent([
        'description' => 'Updated: Now working on code review'
    ]);
}
```

### Stop Timer and Get Entry

```php
// Stop the timer
$result = Teamleader::timers()->stop();

// Get the created time tracking entry ID
$entryId = $result['data']['time_tracking']['id'];

// Fetch full details of the created entry
$entry = Teamleader::timeTracking()->info($entryId);

echo "Tracked " . ($entry['data']['duration'] / 3600) . " hours";
```

### Timer Workflow

```php
// Check if timer already running
if (!Teamleader::timers()->isRunning()) {
    // Start new timer
    $timer = Teamleader::timers()->start([
        'work_type_id' => 'development-work-type-uuid',
        'subject' => [
            'type' => 'milestone',
            'id' => 'milestone-uuid'
        ],
        'description' => 'Feature development'
    ]);
    
    echo "Timer started!";
} else {
    echo "Timer already running. Stop it first.";
}
```

### Start Timer with Custom Start Time

```php
// Start timer with specific start time (e.g., forgot to start it earlier)
$timer = Teamleader::timers()->start([
    'work_type_id' => 'work-type-uuid',
    'subject' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'description' => 'Client meeting',
    'started_at' => '2025-10-17T09:00:00+00:00'
]);
```

## Common Use Cases

### 1. Time Tracking Widget

```php
// Widget to display/control timer
class TimerWidget
{
    public function getCurrentTimer()
    {
        if (Teamleader::timers()->isRunning()) {
            $timer = Teamleader::timers()->current();
            
            return [
                'active' => true,
                'description' => $timer['data']['description'],
                'started_at' => $timer['data']['started_at'],
                'duration' => $this->calculateDuration($timer['data']['started_at'])
            ];
        }
        
        return ['active' => false];
    }
    
    private function calculateDuration(string $startedAt): int
    {
        $start = new DateTime($startedAt);
        $now = new DateTime();
        return $now->getTimestamp() - $start->getTimestamp();
    }
}
```

### 2. Automatic Timer Management

```php
// Stop current timer and start new one
function switchTimer(string $newWorkTypeId, array $newSubject, string $description): array
{
    // Stop current timer if running
    if (Teamleader::timers()->isRunning()) {
        Teamleader::timers()->stop();
    }
    
    // Start new timer
    return Teamleader::timers()->start([
        'work_type_id' => $newWorkTypeId,
        'subject' => $newSubject,
        'description' => $description
    ]);
}

// Usage
$timer = switchTimer(
    'new-work-type-uuid',
    ['type' => 'ticket', 'id' => 'ticket-uuid'],
    'Working on high-priority bug'
);
```

### 3. Timer Reminder System

```php
// Check if timer has been running too long
function checkLongRunningTimer(int $maxHours = 8): ?array
{
    if (!Teamleader::timers()->isRunning()) {
        return null;
    }
    
    $timer = Teamleader::timers()->current();
    $startedAt = new DateTime($timer['data']['started_at']);
    $now = new DateTime();
    
    $hours = ($now->getTimestamp() - $startedAt->getTimestamp()) / 3600;
    
    if ($hours > $maxHours) {
        return [
            'warning' => true,
            'hours' => round($hours, 2),
            'description' => $timer['data']['description']
        ];
    }
    
    return null;
}

// Usage
$check = checkLongRunningTimer();
if ($check) {
    echo "Warning: Timer has been running for {$check['hours']} hours!";
}
```

### 4. Daily Timer Summary

```php
// Get today's time tracking (from stopped timers)
function getTodayTimeTracking(string $userId): array
{
    $today = date('Y-m-d');
    $entries = Teamleader::timeTracking()->forUser($userId)->list([
        'started_after' => $today . 'T00:00:00+00:00',
        'started_before' => $today . 'T23:59:59+00:00'
    ]);
    
    $totalSeconds = array_sum(array_column($entries['data'], 'duration'));
    
    // Add current timer if running
    if (Teamleader::timers()->isRunning()) {
        $timer = Teamleader::timers()->current();
        $start = new DateTime($timer['data']['started_at']);
        $now = new DateTime();
        $currentDuration = $now->getTimestamp() - $start->getTimestamp();
        $totalSeconds += $currentDuration;
    }
    
    return [
        'total_hours' => round($totalSeconds / 3600, 2),
        'entries_count' => count($entries['data']),
        'timer_active' => Teamleader::timers()->isRunning()
    ];
}
```

## Best Practices

### 1. Always Check Before Starting

```php
// Good: Check if timer is already running
if (!Teamleader::timers()->isRunning()) {
    $timer = Teamleader::timers()->start([...]);
} else {
    // Handle already running timer
    throw new Exception('Timer already running. Stop it first.');
}
```

### 2. Handle Timer State in UI

```php
// Check timer state before showing UI
$timerState = [
    'is_running' => Teamleader::timers()->isRunning(),
    'current_timer' => null
];

if ($timerState['is_running']) {
    $timerState['current_timer'] = Teamleader::timers()->current();
}

// Use this state to show appropriate UI (start/stop button)
```

### 3. Use Descriptive Timer Descriptions

```php
// Good: Clear and specific
$timer = Teamleader::timers()->start([
    'work_type_id' => 'work-type-uuid',
    'subject' => ['type' => 'company', 'id' => 'company-uuid'],
    'description' => 'Implementing user authentication feature for web app'
]);

// Less helpful: Vague description
// 'description' => 'Work'
```

### 4. Set Invoiceable Flag Correctly

```php
// Billable work
$timer = Teamleader::timers()->start([
    'work_type_id' => 'consulting-work-type-uuid',
    'subject' => ['type' => 'company', 'id' => 'company-uuid'],
    'description' => 'Business strategy session',
    'invoiceable' => true // Will appear on invoices
]);

// Internal/overhead work
$timer = Teamleader::timers()->start([
    'work_type_id' => 'admin-work-type-uuid',
    'subject' => ['type' => 'company', 'id' => 'company-uuid'],
    'description' => 'Internal team meeting',
    'invoiceable' => false // Won't be billed
]);
```

### 5. Handle Stop Gracefully

```php
function stopTimerSafely(): ?array
{
    try {
        if (Teamleader::timers()->isRunning()) {
            return Teamleader::timers()->stop();
        }
        return null;
    } catch (Exception $e) {
        Log::error('Failed to stop timer', [
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Starting a timer
try {
    $timer = Teamleader::timers()->start([
        'work_type_id' => 'work-type-uuid',
        'subject' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error (e.g., timer already running)
        Log::error('Timer start failed', [
            'error' => $e->getMessage()
        ]);
    } elseif ($e->getCode() === 404) {
        // Work type or subject not found
        Log::error('Resource not found');
    }
}

// Stopping a timer
try {
    $result = Teamleader::timers()->stop();
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // No timer running
        Log::warning('No timer to stop');
    }
}

// Updating current timer
try {
    Teamleader::timers()->updateCurrent([
        'description' => 'Updated description'
    ]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // No timer running to update
        Log::warning('No timer running to update');
    }
}
```

## Important Notes

### 1. One Timer Per Account

Only one timer can run at a time per Teamleader account. Starting a new timer while one is already running will result in an error.

### 2. Automatic Time Tracking Entry

When you stop a timer, it automatically creates a time tracking entry in the background. You don't need to manually create the entry.

### 3. Timer vs Time Tracking

- **Timers**: For real-time tracking (start/stop)
- **Time Tracking**: For manual entry of completed work

### 4. Subject Types Differ

Timer subject types include `todo` but Time Tracking uses different types. When a timer is stopped, it's converted appropriately.

## Related Resources

- [Time Tracking](time-tracking.md) - Manual time entry management
- [Users](../general/users.md) - Users who track time
- [Work Types](../general/work-types.md) - Categorize timer activities
- [Tickets](../tickets/tickets.md) - Track time on tickets
- [Projects](../projects/projects.md) - Track time on projects

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Error Handling](../error-handling.md) - Handle API errors

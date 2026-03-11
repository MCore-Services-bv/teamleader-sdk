# Meetings

Manage meetings in Teamleader Focus Calendar.

## Overview

The Meetings resource provides comprehensive management of meeting activities in your Teamleader account. Meetings are specialized calendar events with additional capabilities for tracking time, creating reports, and managing customer interactions.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [schedule()](#schedule)
    - [update()](#update)
    - [complete()](#complete)
    - [uncomplete()](#uncomplete)
    - [delete()](#delete)
- [Helper Methods](#helper-methods)
- [Sideloading](#sideloading)
- [Filtering](#filtering)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`meetings`

## Capabilities

| Feature | Supported |
|---------|-----------|
| Pagination | ✅ Supported |
| Filtering | ✅ Supported |
| Sorting | ✅ Supported (`scheduled_at` field, default: `asc`) |
| Sideloading | ✅ Supported (`tracked_time`, `estimated_time`) |
| Creation | ✅ Supported |
| Update | ✅ Supported |
| Deletion | ✅ Supported |

## Available Methods

### `list()`

Get a list of meetings with optional filtering and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for pagination and sorting

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all meetings
$meetings = Teamleader::meetings()->list();

// Get meetings with filters
$meetings = Teamleader::meetings()->list([
    'employee_id' => 'user-uuid',
    'start_date'  => '2025-02-01',
    'end_date'    => '2025-02-28'
]);

// With pagination
$meetings = Teamleader::meetings()->list([], [
    'page_size'   => 50,
    'page_number' => 2
]);

// With sorting
$meetings = Teamleader::meetings()->list([], [
    'sort' => [['field' => 'scheduled_at', 'order' => 'desc']]
]);
```

### `info()`

Get detailed information about a specific meeting.

**Parameters:**
- `id` (string): The meeting UUID
- `includes` (array|null): Optional sideloaded data

**Example:**
```php
// Basic info
$meeting = Teamleader::meetings()->info('meeting-uuid');

// With sideloaded data
$meeting = Teamleader::meetings()->info('meeting-uuid', ['tracked_time']);

// Using fluent interface
$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->withEstimatedTime()
    ->info('meeting-uuid');
```

### `schedule()`

Schedule a new meeting.

**Required fields:**
- `title` (string): Meeting title
- `starts_at` (string): Start datetime in ISO 8601 format
- `ends_at` (string): End datetime in ISO 8601 format
- `attendees` (array): Array of attendees (at least one user required)
- `customer` (object): Customer information
    - `type` (string): `contact` or `company`
    - `id` (string): Customer UUID

**Optional fields:**
- `description` (string): Meeting description
- `location` (string): Meeting location
- `milestone_id` (string): Associated milestone UUID
- `activity_type_id` (string): Activity type UUID

**Example:**
```php
$meeting = Teamleader::meetings()->schedule([
    'title'       => 'Client Kickoff Meeting',
    'starts_at'   => '2025-02-20T10:00:00+00:00',
    'ends_at'     => '2025-02-20T11:30:00+00:00',
    'description' => 'Initial project kickoff',
    'location'    => 'Client Office',
    'attendees'   => [
        ['type' => 'user',    'id' => 'user-uuid'],
        ['type' => 'contact', 'id' => 'contact-uuid']
    ],
    'customer' => [
        'type' => 'company',
        'id'   => 'company-uuid'
    ],
    'milestone_id' => 'milestone-uuid'
]);
```

### `update()`

Update an existing meeting.

**Parameters:**
- `id` (string): The meeting UUID
- `data` (array): Fields to update (all optional except id)

**Example:**
```php
$meeting = Teamleader::meetings()->update('meeting-uuid', [
    'title'     => 'Updated Meeting Title',
    'starts_at' => '2025-02-20T11:00:00+00:00',
    'location'  => 'New Location'
]);
```

### `complete()`

Mark a meeting as complete.

**Parameters:**
- `id` (string): The meeting UUID

**Example:**
```php
$result = Teamleader::meetings()->complete('meeting-uuid');
```

### `uncomplete()`

Mark a meeting as incomplete (reopen).

**Parameters:**
- `id` (string): The meeting UUID

**Example:**
```php
$result = Teamleader::meetings()->uncomplete('meeting-uuid');
```

### `delete()`

Delete a meeting.

**Parameters:**
- `id` (string): The meeting UUID

**Example:**
```php
$result = Teamleader::meetings()->delete('meeting-uuid');
```

## Helper Methods

### `forEmployee()`

Get meetings for a specific employee.

```php
$meetings = Teamleader::meetings()->forEmployee('user-uuid');

// With date range
$meetings = Teamleader::meetings()->forEmployee('user-uuid', [
    'filters' => [
        'start_date' => '2025-02-01',
        'end_date'   => '2025-02-28'
    ]
]);
```

### `forMilestone()`

Get meetings associated with a project milestone.

```php
$meetings = Teamleader::meetings()->forMilestone('milestone-uuid');
```

### `betweenDates()`

Get meetings within a specific date range.

```php
$meetings = Teamleader::meetings()->betweenDates(
    '2025-02-01',
    '2025-02-28'
);
```

### `search()`

Search meetings by term (searches title and description).

```php
$meetings = Teamleader::meetings()->search('project kickoff');
```

### `byIds()`

Get specific meetings by their UUIDs.

```php
$meetings = Teamleader::meetings()->byIds([
    'meeting-uuid-1',
    'meeting-uuid-2'
]);
```

### `forRecurringSeries()`

Get all meetings in a recurring series.

```php
$meetings = Teamleader::meetings()->forRecurringSeries('recurrence-uuid');
```

### Sideloading Methods

```php
// Include tracked time
$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->info('meeting-uuid');

// Include estimated time
$meeting = Teamleader::meetings()
    ->withEstimatedTime()
    ->info('meeting-uuid');

// Include multiple
$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->withEstimatedTime()
    ->info('meeting-uuid');
```

## Sideloading

Available includes for meetings:

- `tracked_time`: Include actual time tracked for the meeting
- `estimated_time`: Include estimated time for the meeting

**Example:**
```php
// Using includes parameter
$meeting = Teamleader::meetings()->info('meeting-uuid', [
    'tracked_time',
    'estimated_time'
]);

// Using fluent interface
$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->withEstimatedTime()
    ->info('meeting-uuid');

// For list operations
$meetings = Teamleader::meetings()
    ->withTrackedTime()
    ->list(['employee_id' => 'user-uuid']);
```

## Filtering

Available filters:

- `ids` (array): Array of meeting UUIDs
- `employee_id` (string): Filter by assigned employee UUID
- `start_date` (string): Filter meetings from this date (YYYY-MM-DD)
- `end_date` (string): Filter meetings up to this date (YYYY-MM-DD)
- `milestone_id` (string): Filter by project milestone UUID
- `term` (string): Search term for title or description
- `recurrence_id` (string): Filter by recurring meeting series UUID

**Filter Examples:**
```php
// Filter by employee
$meetings = Teamleader::meetings()->list([
    'employee_id' => 'user-uuid'
]);

// Filter by date range
$meetings = Teamleader::meetings()->list([
    'start_date' => '2025-02-01',
    'end_date'   => '2025-02-28'
]);

// Filter by milestone
$meetings = Teamleader::meetings()->list([
    'milestone_id' => 'milestone-uuid'
]);

// Search meetings
$meetings = Teamleader::meetings()->list([
    'term' => 'client review'
]);

// Multiple filters
$meetings = Teamleader::meetings()->list([
    'employee_id'  => 'user-uuid',
    'start_date'   => '2025-02-01',
    'end_date'     => '2025-02-28',
    'milestone_id' => 'milestone-uuid'
]);
```

## Response Structure

### Meeting Object (`info()` and `list()`)

```php
[
    'id'           => '70af3fdd-b037-0936-ad1a-6d784dd44cf4',
    'title'        => 'Client Kickoff Meeting',
    'description'  => 'Initial project kickoff discussion',
    'created_at'   => '2020-02-01T10:33:45+00:00',
    'scheduled_at' => '2020-02-04T16:44:33+00:00',
    'duration'     => [
        'unit'  => 'min',
        'value' => 90,
    ],
    'status'   => 'open',   // 'open' or 'done'
    'customer' => [         // nullable
        'type' => 'company',
        'id'   => 'company-uuid',
    ],
    'project'   => [        // nullable
        'type' => 'project',    // 'project' or 'nextgenProject'
        'id'   => 'project-uuid',
    ],
    'milestone' => [        // nullable
        'type' => 'milestone',
        'id'   => 'milestone-uuid',
    ],
    'group' => [            // nullable — added 2026
        'type' => 'projectGroup',
        'id'   => 'group-uuid',
    ],
    'attendees' => [
        ['type' => 'user',    'id' => 'user-uuid'],
        ['type' => 'contact', 'id' => 'contact-uuid'],
    ],
    'recurrence' => [       // nullable
        'type' => 'recurrence',
        'id'   => 'recurrence-uuid',
    ],
]
```

> **Note:** `info()` additionally returns `deal`, `location`, `online_meeting_room`, `custom_fields[]`, and `workOrder` fields not present in `list()` results.

### With Sideloaded Data

```php
[
    'id' => 'meeting-uuid',
    // ... other fields

    // Included when includes=tracked_time
    'tracked_time' => [
        'total' => ['value' => 60, 'unit' => 'min'],
    ],

    // Included when includes=estimated_time
    'estimated_time' => [
        'total' => ['value' => 60, 'unit' => 's'],
    ],
]
```

## Usage Examples

### Schedule Client Meeting

```php
$meeting = Teamleader::meetings()->schedule([
    'title'       => 'Q1 Business Review',
    'starts_at'   => '2025-02-25T14:00:00+00:00',
    'ends_at'     => '2025-02-25T16:00:00+00:00',
    'description' => 'Quarterly business review with key stakeholders',
    'location'    => 'Main Conference Room',
    'attendees'   => [
        ['type' => 'user',    'id' => 'account-manager-uuid'],
        ['type' => 'user',    'id' => 'sales-director-uuid'],
        ['type' => 'contact', 'id' => 'client-contact-uuid']
    ],
    'customer' => [
        'type' => 'company',
        'id'   => 'client-company-uuid'
    ]
]);

echo "Meeting scheduled: {$meeting['data']['id']}";
```

### Get Meetings Sorted by Date

```php
$meetings = Teamleader::meetings()->list(
    ['employee_id' => 'user-uuid'],
    ['sort' => [['field' => 'scheduled_at', 'order' => 'asc']]]
);
```

### Get Meetings Linked to a Group

```php
$meetings = Teamleader::meetings()->list([
    'employee_id' => 'user-uuid',
    'start_date'  => '2025-01-01',
    'end_date'    => '2025-03-31',
]);

// Filter locally by group
$groupMeetings = array_filter(
    $meetings['data'],
    fn($m) => isset($m['group']['id']) && $m['group']['id'] === 'group-uuid'
);
```

### Meeting Analytics Dashboard

```php
class MeetingAnalytics
{
    public function getMonthlyStats($userId, $month, $year)
    {
        $start = "{$year}-{$month}-01";
        $end   = date('Y-m-t', strtotime($start));

        $meetings = Teamleader::meetings()
            ->withTrackedTime()
            ->list([
                'employee_id' => $userId,
                'start_date'  => $start,
                'end_date'    => $end,
            ]);

        $total     = count($meetings['data']);
        $completed = count(array_filter(
            $meetings['data'],
            fn($m) => $m['status'] === 'done'
        ));

        $totalMinutes = array_reduce(
            $meetings['data'],
            fn($sum, $m) => $sum + ($m['tracked_time']['total']['value'] ?? 0),
            0
        );

        return [
            'total_meetings'     => $total,
            'completed_meetings' => $completed,
            'pending_meetings'   => $total - $completed,
            'total_hours'        => round($totalMinutes / 60, 2),
            'completion_rate'    => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    public function getUpcomingMeetings($userId, $days = 7)
    {
        $meetings = Teamleader::meetings()->list(
            [
                'employee_id' => $userId,
                'start_date'  => date('Y-m-d'),
                'end_date'    => date('Y-m-d', strtotime("+{$days} days")),
            ],
            ['sort' => [['field' => 'scheduled_at', 'order' => 'asc']]]
        );

        return array_filter(
            $meetings['data'],
            fn($m) => $m['status'] === 'open'
        );
    }
}
```

## Best Practices

### 1. Always Include Customer Information

```php
$meeting = Teamleader::meetings()->schedule([
    'title'     => 'Client Meeting',
    'starts_at' => '2025-02-20T10:00:00+00:00',
    'ends_at'   => '2025-02-20T11:00:00+00:00',
    'attendees' => [['type' => 'user', 'id' => 'user-uuid']],
    'customer'  => [
        'type' => 'company',
        'id'   => 'company-uuid'
    ]
]);
```

### 2. Include At Least One User Attendee

```php
$meeting = Teamleader::meetings()->schedule([
    // ... other fields
    'attendees' => [
        ['type' => 'user',    'id' => 'user-uuid'],     // required
        ['type' => 'contact', 'id' => 'contact-uuid']
    ]
]);
```

### 3. Use Sideloading for Time Tracking

```php
// Good: single request
$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->withEstimatedTime()
    ->info('meeting-uuid');
```

### 4. Sort When Building Calendar Views

```php
$startOfWeek = now()->startOfWeek()->format('Y-m-d');
$endOfWeek   = now()->endOfWeek()->format('Y-m-d');

$weekMeetings = Teamleader::meetings()->list(
    ['start_date' => $startOfWeek, 'end_date' => $endOfWeek],
    ['sort' => [['field' => 'scheduled_at', 'order' => 'asc']]]
);
```

### 5. Complete Meetings When Done

```php
Teamleader::meetings()->complete('meeting-uuid');

// Reopen if needed
Teamleader::meetings()->uncomplete('meeting-uuid');
```

### 6. Handle Recurring Meetings by Series

```php
$seriesMeetings = Teamleader::meetings()->forRecurringSeries('recurrence-uuid');

foreach ($seriesMeetings['data'] as $meeting) {
    if (strtotime($meeting['scheduled_at']) > time()) {
        Teamleader::meetings()->update($meeting['id'], [
            'location' => 'New Location'
        ]);
    }
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $meeting = Teamleader::meetings()->schedule([
        'title'     => 'Meeting',
        'starts_at' => '2025-02-20T10:00:00+00:00',
        'ends_at'   => '2025-02-20T11:00:00+00:00',
        'attendees' => [['type' => 'user', 'id' => 'user-uuid']],
        'customer'  => ['type' => 'company', 'id' => 'company-uuid'],
    ]);
} catch (\InvalidArgumentException $e) {
    // SDK validation error (missing required field, no user attendee, etc.)
    Log::error('Invalid meeting data: ' . $e->getMessage());
} catch (TeamleaderException $e) {
    Log::error('Teamleader API error', [
        'message' => $e->getMessage(),
        'code'    => $e->getCode(),
    ]);
}
```

## Related Resources

- [Events](events.md) — General calendar events
- [Calls](calls.md) — Call-specific activities
- [Activity Types](activity-types.md) — Define meeting types
- [Projects](../projects/projects.md) — Associated projects
- [Milestones](../projects/milestones.md) — Project milestones
- [Companies](../crm/companies.md) — Customer companies
- [Contacts](../crm/contacts.md) — Customer contacts
- [Users](../users/users.md) — Meeting attendees

## Rate Limiting

All meeting operations consume 1 API credit per request.

- `list()`: 1 credit
- `info()`: 1 credit
- `schedule()`: 1 credit
- `update()`: 1 credit
- `complete()`: 1 credit
- `uncomplete()`: 1 credit
- `delete()`: 1 credit

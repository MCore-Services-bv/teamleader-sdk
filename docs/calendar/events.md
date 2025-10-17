# Events

Manage calendar events in Teamleader Focus.

## Overview

The Events resource provides comprehensive management of calendar events in your Teamleader account. Events represent scheduled activities with start and end times, attendees, and can be linked to various entities like contacts, companies, and deals.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [cancel()](#cancel)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`events`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported (via cancel())

## Available Methods

### `list()`

Get a list of calendar events with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for pagination and sorting

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all events
$events = Teamleader::events()->list();

// Get events with filters
$events = Teamleader::events()->list([
    'user_id' => 'user-uuid',
    'ends_after' => '2025-01-01T00:00:00+00:00',
    'starts_before' => '2025-12-31T23:59:59+00:00'
]);

// With pagination
$events = Teamleader::events()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

### `info()`

Get detailed information about a specific event.

**Parameters:**
- `id` (string): The event UUID

**Example:**
```php
$event = Teamleader::events()->info('event-uuid');
```

### `create()`

Create a new calendar event.

**Required fields:**
- `title` (string): Event title
- `activity_type_id` (string): Activity type UUID
- `starts_at` (string): Start datetime in ISO 8601 format
- `ends_at` (string): End datetime in ISO 8601 format

**Optional fields:**
- `description` (string): Event description
- `location` (string): Event location
- `attendees` (array): Array of attendees
- `links` (array): Array of linked entities
- `task_id` (string): Associated task UUID

**Example:**
```php
$event = Teamleader::events()->create([
    'title' => 'Client Meeting',
    'activity_type_id' => 'activity-type-uuid',
    'starts_at' => '2025-02-15T14:00:00+00:00',
    'ends_at' => '2025-02-15T15:30:00+00:00',
    'description' => 'Quarterly review meeting',
    'location' => 'Conference Room A',
    'attendees' => [
        ['type' => 'user', 'id' => 'user-uuid'],
        ['type' => 'contact', 'id' => 'contact-uuid']
    ],
    'links' => [
        ['type' => 'company', 'id' => 'company-uuid']
    ]
]);
```

### `update()`

Update an existing calendar event.

**Parameters:**
- `id` (string): The event UUID
- `data` (array): Fields to update (all optional except id)

**Example:**
```php
$event = Teamleader::events()->update('event-uuid', [
    'title' => 'Updated Meeting Title',
    'starts_at' => '2025-02-15T15:00:00+00:00',
    'ends_at' => '2025-02-15T16:00:00+00:00'
]);
```

### `cancel()`

Cancel an event (for all attendees). This is the delete operation for events.

**Parameters:**
- `id` (string): The event UUID

**Example:**
```php
$result = Teamleader::events()->cancel('event-uuid');
```

## Helper Methods

### `forUser()`

Get events for a specific user.

```php
$events = Teamleader::events()->forUser('user-uuid');

// With additional options
$events = Teamleader::events()->forUser('user-uuid', [
    'filters' => ['done' => false],
    'page_size' => 25
]);
```

### `forActivityType()`

Get events of a specific activity type.

```php
$events = Teamleader::events()->forActivityType('activity-type-uuid');
```

### `search()`

Search events by term (searches title and description).

```php
$events = Teamleader::events()->search('project meeting');

// With date range
$events = Teamleader::events()->search('client', [
    'filters' => [
        'ends_after' => '2025-01-01T00:00:00+00:00'
    ]
]);
```

### `betweenDates()`

Get events within a specific date range.

```php
$events = Teamleader::events()->betweenDates(
    '2025-02-01T00:00:00+00:00',
    '2025-02-28T23:59:59+00:00'
);

// With pagination
$events = Teamleader::events()->betweenDates(
    '2025-02-01T00:00:00+00:00',
    '2025-02-28T23:59:59+00:00',
    ['page_size' => 50]
);
```

### `byIds()`

Get specific events by their UUIDs.

```php
$events = Teamleader::events()->byIds([
    'event-uuid-1',
    'event-uuid-2',
    'event-uuid-3'
]);
```

### `forAttendee()`

Get events for a specific attendee.

```php
$events = Teamleader::events()->forAttendee('user', 'user-uuid');
$events = Teamleader::events()->forAttendee('contact', 'contact-uuid');
```

### `linkedTo()`

Get events linked to a specific entity.

```php
$events = Teamleader::events()->linkedTo('company', 'company-uuid');
$events = Teamleader::events()->linkedTo('deal', 'deal-uuid');
$events = Teamleader::events()->linkedTo('contact', 'contact-uuid');
```

### `forTask()`

Get events associated with a specific task.

```php
$events = Teamleader::events()->forTask('task-uuid');
```

### `completed()`

Get completed events.

```php
$events = Teamleader::events()->completed();

// For a specific user
$events = Teamleader::events()->completed('user-uuid');
```

### `pending()`

Get pending (not completed) events.

```php
$events = Teamleader::events()->pending();

// For a specific user
$events = Teamleader::events()->pending('user-uuid');
```

## Filtering

Available filters:

- `ids` (array): Array of event UUIDs
- `user_id` (string): Filter events by user UUID
- `activity_type_id` (string): Filter by activity type UUID
- `ends_after` (string): Start of period (ISO 8601 format)
- `starts_before` (string): End of period (ISO 8601 format)
- `term` (string): Search term for title or description
- `attendee` (object): Filter by attendee
    - `type` (string): 'user' or 'contact'
    - `id` (string): Attendee UUID
- `link` (object): Filter by linked entity
    - `type` (string): 'contact', 'company', or 'deal'
    - `id` (string): Entity UUID
- `task_id` (string): Filter events by task UUID
- `done` (boolean): Filter by completion status

**Filter Examples:**
```php
// Filter by date range
$events = Teamleader::events()->list([
    'ends_after' => '2025-02-01T00:00:00+00:00',
    'starts_before' => '2025-02-28T23:59:59+00:00'
]);

// Filter by attendee
$events = Teamleader::events()->list([
    'attendee' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ]
]);

// Filter by linked company
$events = Teamleader::events()->list([
    'link' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Search and filter
$events = Teamleader::events()->list([
    'term' => 'client',
    'done' => false,
    'user_id' => 'user-uuid'
]);
```

## Sorting

Events can be sorted by:
- `starts_at`: Sort by event start date/time

**Sorting Examples:**
```php
// Sort by start date ascending
$events = Teamleader::events()->list([], [
    'sort' => [
        ['field' => 'starts_at', 'order' => 'asc']
    ]
]);

// Sort by start date descending
$events = Teamleader::events()->list([], [
    'sort' => [
        ['field' => 'starts_at', 'order' => 'desc']
    ]
]);
```

## Response Structure

### Event Object

```php
[
    'id' => 'event-uuid',
    'title' => 'Client Meeting',
    'description' => 'Quarterly business review',
    'starts_at' => '2025-02-15T14:00:00+00:00',
    'ends_at' => '2025-02-15T15:30:00+00:00',
    'location' => 'Conference Room A',
    'activity_type' => [
        'type' => 'activityType',
        'id' => 'activity-type-uuid'
    ],
    'attendees' => [
        [
            'type' => 'user',
            'id' => 'user-uuid'
        ],
        [
            'type' => 'contact',
            'id' => 'contact-uuid'
        ]
    ],
    'links' => [
        [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'task' => [
        'type' => 'task',
        'id' => 'task-uuid'
    ],
    'done' => false,
    'created_at' => '2025-01-15T10:30:00+00:00',
    'updated_at' => '2025-01-20T14:15:00+00:00'
]
```

## Usage Examples

### Create Team Meeting

```php
$meeting = Teamleader::events()->create([
    'title' => 'Weekly Team Standup',
    'activity_type_id' => 'meeting-type-uuid',
    'starts_at' => '2025-02-17T09:00:00+00:00',
    'ends_at' => '2025-02-17T09:30:00+00:00',
    'description' => 'Weekly sync meeting',
    'location' => 'Main Conference Room',
    'attendees' => [
        ['type' => 'user', 'id' => 'user1-uuid'],
        ['type' => 'user', 'id' => 'user2-uuid'],
        ['type' => 'user', 'id' => 'user3-uuid']
    ]
]);

echo "Created meeting: {$meeting['data']['id']}";
```

### Get User's Weekly Schedule

```php
$startOfWeek = now()->startOfWeek()->toIso8601String();
$endOfWeek = now()->endOfWeek()->toIso8601String();

$weekEvents = Teamleader::events()->list([
    'user_id' => 'user-uuid',
    'ends_after' => $startOfWeek,
    'starts_before' => $endOfWeek
], [
    'sort' => [
        ['field' => 'starts_at', 'order' => 'asc']
    ]
]);

foreach ($weekEvents['data'] as $event) {
    echo "{$event['title']} - {$event['starts_at']}\n";
}
```

### Create Client Meeting with Follow-up

```php
// Create the meeting
$meeting = Teamleader::events()->create([
    'title' => 'Q1 Business Review',
    'activity_type_id' => 'client-meeting-type-uuid',
    'starts_at' => '2025-02-20T14:00:00+00:00',
    'ends_at' => '2025-02-20T16:00:00+00:00',
    'description' => 'Review Q1 performance and discuss Q2 goals',
    'attendees' => [
        ['type' => 'user', 'id' => 'account-manager-uuid'],
        ['type' => 'contact', 'id' => 'client-contact-uuid']
    ],
    'links' => [
        ['type' => 'company', 'id' => 'client-company-uuid'],
        ['type' => 'deal', 'id' => 'deal-uuid']
    ]
]);

// Create follow-up task
$followUp = Teamleader::events()->create([
    'title' => 'Send Q1 Report to Client',
    'activity_type_id' => 'task-type-uuid',
    'starts_at' => '2025-02-21T09:00:00+00:00',
    'ends_at' => '2025-02-21T10:00:00+00:00',
    'attendees' => [
        ['type' => 'user', 'id' => 'account-manager-uuid']
    ],
    'links' => [
        ['type' => 'company', 'id' => 'client-company-uuid']
    ]
]);
```

### Update Event Time

```php
$event = Teamleader::events()->update('event-uuid', [
    'starts_at' => '2025-02-15T15:00:00+00:00',
    'ends_at' => '2025-02-15T16:30:00+00:00'
]);

echo "Event rescheduled to {$event['data']['starts_at']}";
```

### Find All Events for a Deal

```php
$dealEvents = Teamleader::events()->linkedTo('deal', 'deal-uuid');

echo "Found " . count($dealEvents['data']) . " events for this deal:\n";
foreach ($dealEvents['data'] as $event) {
    echo "- {$event['title']} on {$event['starts_at']}\n";
}
```

## Common Use Cases

### Calendar Dashboard

```php
class CalendarDashboard
{
    public function getTodaySchedule($userId)
    {
        $today = now()->startOfDay()->toIso8601String();
        $tomorrow = now()->addDay()->startOfDay()->toIso8601String();
        
        return Teamleader::events()->list([
            'user_id' => $userId,
            'ends_after' => $today,
            'starts_before' => $tomorrow
        ], [
            'sort' => [
                ['field' => 'starts_at', 'order' => 'asc']
            ]
        ]);
    }
    
    public function getUpcomingEvents($userId, $days = 7)
    {
        $start = now()->toIso8601String();
        $end = now()->addDays($days)->toIso8601String();
        
        return Teamleader::events()->betweenDates($start, $end, [
            'filters' => ['user_id' => $userId],
            'sort' => [
                ['field' => 'starts_at', 'order' => 'asc']
            ]
        ]);
    }
    
    public function getPendingEvents($userId)
    {
        return Teamleader::events()->list([
            'user_id' => $userId,
            'done' => false,
            'ends_after' => now()->toIso8601String()
        ]);
    }
}
```

### Meeting Scheduler

```php
class MeetingScheduler
{
    public function scheduleClientMeeting($clientCompanyId, $contactId, $data)
    {
        return Teamleader::events()->create([
            'title' => $data['title'],
            'activity_type_id' => $data['activity_type_id'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'description' => $data['description'] ?? '',
            'location' => $data['location'] ?? '',
            'attendees' => array_merge(
                [['type' => 'contact', 'id' => $contactId]],
                $data['attendees'] ?? []
            ),
            'links' => [
                ['type' => 'company', 'id' => $clientCompanyId]
            ]
        ]);
    }
    
    public function rescheduleMeeting($eventId, $newStartTime, $newEndTime)
    {
        return Teamleader::events()->update($eventId, [
            'starts_at' => $newStartTime,
            'ends_at' => $newEndTime
        ]);
    }
    
    public function cancelMeeting($eventId)
    {
        return Teamleader::events()->cancel($eventId);
    }
}
```

### Event Reporting

```php
class EventReporting
{
    public function getMonthlyEventStats($userId, $year, $month)
    {
        $start = "{$year}-{$month}-01T00:00:00+00:00";
        $end = date('Y-m-t', strtotime($start)) . 'T23:59:59+00:00';
        
        $events = Teamleader::events()->list([
            'user_id' => $userId,
            'ends_after' => $start,
            'starts_before' => $end
        ]);
        
        $total = count($events['data']);
        $completed = count(array_filter($events['data'], fn($e) => $e['done']));
        $pending = $total - $completed;
        
        // Group by activity type
        $byType = [];
        foreach ($events['data'] as $event) {
            $typeId = $event['activity_type']['id'];
            $byType[$typeId] = ($byType[$typeId] ?? 0) + 1;
        }
        
        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'by_type' => $byType
        ];
    }
}
```

## Best Practices

### 1. Use ISO 8601 Format for Dates

Always use ISO 8601 format for datetime values with timezone information:

```php
// Good
$event = Teamleader::events()->create([
    'starts_at' => '2025-02-15T14:00:00+00:00',
    'ends_at' => '2025-02-15T15:30:00+00:00'
]);

// Using Carbon
$event = Teamleader::events()->create([
    'starts_at' => now()->addDays(5)->toIso8601String(),
    'ends_at' => now()->addDays(5)->addHours(2)->toIso8601String()
]);
```

### 2. Always Include Required Attendees

Ensure events have at least one user attendee:

```php
$event = Teamleader::events()->create([
    'title' => 'Client Meeting',
    'activity_type_id' => 'meeting-type-uuid',
    'starts_at' => '2025-02-15T14:00:00+00:00',
    'ends_at' => '2025-02-15T15:30:00+00:00',
    'attendees' => [
        ['type' => 'user', 'id' => 'user-uuid'], // At least one user required
        ['type' => 'contact', 'id' => 'contact-uuid']
    ]
]);
```

### 3. Link Events to Relevant Entities

Always link events to relevant entities for better context and tracking:

```php
$event = Teamleader::events()->create([
    // ... other fields
    'links' => [
        ['type' => 'company', 'id' => 'company-uuid'],
        ['type' => 'deal', 'id' => 'deal-uuid']
    ]
]);
```

### 4. Use Helper Methods for Readability

Prefer helper methods for cleaner, more readable code:

```php
// Good
$events = Teamleader::events()->forUser('user-uuid');
$events = Teamleader::events()->betweenDates($start, $end);

// Less readable
$events = Teamleader::events()->list(['user_id' => 'user-uuid']);
$events = Teamleader::events()->list([
    'ends_after' => $start,
    'starts_before' => $end
]);
```

### 5. Handle Event Cancellations Properly

Use the `cancel()` method instead of trying to delete:

```php
// Correct
$result = Teamleader::events()->cancel('event-uuid');

// This also works (internally calls cancel)
$result = Teamleader::events()->delete('event-uuid');
```

### 6. Validate Date Ranges

Ensure end time is after start time:

```php
$startTime = '2025-02-15T14:00:00+00:00';
$endTime = '2025-02-15T15:30:00+00:00';

if (strtotime($endTime) <= strtotime($startTime)) {
    throw new InvalidArgumentException('End time must be after start time');
}

$event = Teamleader::events()->create([
    'starts_at' => $startTime,
    'ends_at' => $endTime,
    // ... other fields
]);
```

### 7. Use Pagination for Large Result Sets

When retrieving many events, use pagination to avoid performance issues:

```php
$pageSize = 50;
$pageNumber = 1;
$allEvents = [];

do {
    $result = Teamleader::events()->list([], [
        'page_size' => $pageSize,
        'page_number' => $pageNumber
    ]);
    
    $allEvents = array_merge($allEvents, $result['data']);
    $pageNumber++;
} while (count($result['data']) === $pageSize);
```

## Error Handling

### Common Errors and Solutions

**Missing Required Fields:**
```php
try {
    $event = Teamleader::events()->create([
        'title' => 'Meeting'
        // Missing required fields
    ]);
} catch (\Exception $e) {
    // Handle: "activity_type_id is required"
    // Handle: "starts_at is required"
}
```

**Invalid Date Format:**
```php
try {
    $event = Teamleader::events()->create([
        'starts_at' => '2025-02-15 14:00:00' // Wrong format
    ]);
} catch (\Exception $e) {
    // Handle: "Invalid datetime format. Use ISO 8601"
}
```

**Invalid Attendee Type:**
```php
try {
    $event = Teamleader::events()->create([
        'attendees' => [
            ['type' => 'invalid', 'id' => 'uuid'] // Invalid type
        ]
    ]);
} catch (\Exception $e) {
    // Handle: "Attendee type must be 'user' or 'contact'"
}
```

**Event Not Found:**
```php
try {
    $event = Teamleader::events()->info('non-existent-uuid');
} catch (\Exception $e) {
    // Handle: Event not found error
}
```

### Robust Error Handling Example

```php
class EventManager
{
    public function createEventSafely(array $data)
    {
        try {
            // Validate required fields
            $this->validateEventData($data);
            
            // Create event
            $event = Teamleader::events()->create($data);
            
            return [
                'success' => true,
                'event' => $event['data']
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => 'Validation error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create event', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to create event. Please try again.'
            ];
        }
    }
    
    private function validateEventData(array $data)
    {
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Event title is required');
        }
        
        if (empty($data['activity_type_id'])) {
            throw new \InvalidArgumentException('Activity type is required');
        }
        
        if (empty($data['starts_at']) || empty($data['ends_at'])) {
            throw new \InvalidArgumentException('Start and end times are required');
        }
        
        if (strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
            throw new \InvalidArgumentException('End time must be after start time');
        }
    }
}
```

## Related Resources

- [Activity Types](activity-types.md) - Define types of events
- [Meetings](meetings.md) - Specialized meeting management
- [Calls](calls.md) - Call-specific event handling
- [Users](../users/users.md) - Event attendees and owners
- [Contacts](../crm/contacts.md) - Contact attendees
- [Companies](../crm/companies.md) - Linked companies
- [Deals](../deals/deals.md) - Linked deals

## Rate Limiting

All event operations consume 1 API credit per request:

- `list()`: 1 credit
- `info()`: 1 credit
- `create()`: 1 credit
- `update()`: 1 credit
- `cancel()`: 1 credit

Monitor your API usage to stay within rate limits.

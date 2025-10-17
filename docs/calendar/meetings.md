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

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ✅ Supported (tracked_time, estimated_time)
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get a list of meetings with optional filtering and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for pagination

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all meetings
$meetings = Teamleader::meetings()->list();

// Get meetings with filters
$meetings = Teamleader::meetings()->list([
    'employee_id' => 'user-uuid',
    'start_date' => '2025-02-01',
    'end_date' => '2025-02-28'
]);

// With pagination
$meetings = Teamleader::meetings()->list([], [
    'page_size' => 50,
    'page_number' => 2
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
    - `type` (string): 'contact' or 'company'
    - `id` (string): Customer UUID

**Optional fields:**
- `description` (string): Meeting description
- `location` (string): Meeting location
- `milestone_id` (string): Associated milestone UUID
- `activity_type_id` (string): Activity type UUID

**Example:**
```php
$meeting = Teamleader::meetings()->schedule([
    'title' => 'Client Kickoff Meeting',
    'starts_at' => '2025-02-20T10:00:00+00:00',
    'ends_at' => '2025-02-20T11:30:00+00:00',
    'description' => 'Initial project kickoff',
    'location' => 'Client Office',
    'attendees' => [
        ['type' => 'user', 'id' => 'user-uuid'],
        ['type' => 'contact', 'id' => 'contact-uuid']
    ],
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
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
    'title' => 'Updated Meeting Title',
    'starts_at' => '2025-02-20T11:00:00+00:00',
    'location' => 'New Location'
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
        'end_date' => '2025-02-28'
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
    'end_date' => '2025-02-28'
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
    'employee_id' => 'user-uuid',
    'start_date' => '2025-02-01',
    'end_date' => '2025-02-28',
    'milestone_id' => 'milestone-uuid'
]);
```

## Response Structure

### Meeting Object

```php
[
    'id' => 'meeting-uuid',
    'title' => 'Client Kickoff Meeting',
    'description' => 'Initial project kickoff discussion',
    'starts_at' => '2025-02-20T10:00:00+00:00',
    'ends_at' => '2025-02-20T11:30:00+00:00',
    'location' => 'Client Office',
    'completed' => false,
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
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'milestone' => [
        'type' => 'milestone',
        'id' => 'milestone-uuid'
    ],
    'activity_type' => [
        'type' => 'activityType',
        'id' => 'activity-type-uuid'
    ],
    'recurrence' => [
        'id' => 'recurrence-uuid',
        'frequency' => 'weekly'
    ],
    'created_at' => '2025-01-15T10:30:00+00:00',
    'updated_at' => '2025-01-20T14:15:00+00:00'
]
```

### With Sideloaded Data

```php
[
    'id' => 'meeting-uuid',
    // ... other fields
    'tracked_time' => [
        'hours' => 1.5,
        'formatted' => '1h 30m'
    ],
    'estimated_time' => [
        'hours' => 2.0,
        'formatted' => '2h 0m'
    ]
]
```

## Usage Examples

### Schedule Client Meeting

```php
$meeting = Teamleader::meetings()->schedule([
    'title' => 'Q1 Business Review',
    'starts_at' => '2025-02-25T14:00:00+00:00',
    'ends_at' => '2025-02-25T16:00:00+00:00',
    'description' => 'Quarterly business review with key stakeholders',
    'location' => 'Main Conference Room',
    'attendees' => [
        ['type' => 'user', 'id' => 'account-manager-uuid'],
        ['type' => 'user', 'id' => 'sales-director-uuid'],
        ['type' => 'contact', 'id' => 'client-contact-uuid']
    ],
    'customer' => [
        'type' => 'company',
        'id' => 'client-company-uuid'
    ]
]);

echo "Meeting scheduled: {$meeting['data']['id']}";
```

### Get Employee's Monthly Meetings

```php
$startOfMonth = now()->startOfMonth()->format('Y-m-d');
$endOfMonth = now()->endOfMonth()->format('Y-m-d');

$monthlyMeetings = Teamleader::meetings()->list([
    'employee_id' => 'user-uuid',
    'start_date' => $startOfMonth,
    'end_date' => $endOfMonth
]);

echo "Total meetings this month: " . count($monthlyMeetings['data']);

foreach ($monthlyMeetings['data'] as $meeting) {
    $status = $meeting['completed'] ? 'Completed' : 'Pending';
    echo "{$meeting['title']} - {$status}\n";
}
```

### Complete Meeting and Create Report

```php
// Mark meeting as complete
Teamleader::meetings()->complete('meeting-uuid');

// Create meeting report/notes
$report = Teamleader::meetings()->createReport('meeting-uuid', [
    'content' => 'Meeting Summary:\n' .
                 '- Discussed project timeline\n' .
                 '- Reviewed deliverables\n' .
                 '- Set next steps',
    'action_items' => [
        'Send proposal by Friday',
        'Schedule follow-up meeting',
        'Prepare detailed cost breakdown'
    ]
]);

echo "Meeting completed and report created";
```

### Reschedule Meeting

```php
$newStartTime = '2025-02-26T15:00:00+00:00';
$newEndTime = '2025-02-26T16:30:00+00:00';

$updated = Teamleader::meetings()->update('meeting-uuid', [
    'starts_at' => $newStartTime,
    'ends_at' => $newEndTime
]);

echo "Meeting rescheduled to {$updated['data']['starts_at']}";
```

### Get Meeting with Time Tracking

```php
$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->withEstimatedTime()
    ->info('meeting-uuid');

$tracked = $meeting['data']['tracked_time']['hours'] ?? 0;
$estimated = $meeting['data']['estimated_time']['hours'] ?? 0;

if ($tracked > $estimated) {
    echo "Meeting went over estimated time by " . 
         ($tracked - $estimated) . " hours";
}
```

## Common Use Cases

### Meeting Scheduler Service

```php
class MeetingScheduler
{
    public function scheduleClientMeeting(array $data)
    {
        // Validate availability
        $this->checkAvailability($data['attendees'], $data['starts_at']);
        
        // Schedule meeting
        $meeting = Teamleader::meetings()->schedule([
            'title' => $data['title'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'description' => $data['description'] ?? '',
            'location' => $data['location'] ?? '',
            'attendees' => $data['attendees'],
            'customer' => $data['customer']
        ]);
        
        // Send notifications
        $this->sendNotifications($meeting['data']);
        
        return $meeting;
    }
    
    public function rescheduleMeeting($meetingId, $newStart, $newEnd)
    {
        // Get original meeting
        $original = Teamleader::meetings()->info($meetingId);
        
        // Check availability for new time
        $this->checkAvailability(
            $original['data']['attendees'],
            $newStart
        );
        
        // Update meeting
        $updated = Teamleader::meetings()->update($meetingId, [
            'starts_at' => $newStart,
            'ends_at' => $newEnd
        ]);
        
        // Notify attendees
        $this->sendRescheduleNotifications($updated['data']);
        
        return $updated;
    }
    
    private function checkAvailability($attendees, $startTime)
    {
        foreach ($attendees as $attendee) {
            if ($attendee['type'] !== 'user') continue;
            
            $conflicts = Teamleader::meetings()->list([
                'employee_id' => $attendee['id'],
                'start_date' => date('Y-m-d', strtotime($startTime)),
                'end_date' => date('Y-m-d', strtotime($startTime))
            ]);
            
            // Check for time conflicts
            // Implementation details...
        }
    }
}
```

### Project Meeting Tracker

```php
class ProjectMeetingTracker
{
    public function getMeetingsForMilestone($milestoneId)
    {
        return Teamleader::meetings()
            ->withTrackedTime()
            ->withEstimatedTime()
            ->list(['milestone_id' => $milestoneId]);
    }
    
    public function getTotalMeetingTime($milestoneId)
    {
        $meetings = $this->getMeetingsForMilestone($milestoneId);
        
        $totalTracked = 0;
        $totalEstimated = 0;
        
        foreach ($meetings['data'] as $meeting) {
            $totalTracked += $meeting['tracked_time']['hours'] ?? 0;
            $totalEstimated += $meeting['estimated_time']['hours'] ?? 0;
        }
        
        return [
            'total_meetings' => count($meetings['data']),
            'total_tracked_hours' => $totalTracked,
            'total_estimated_hours' => $totalEstimated,
            'variance' => $totalTracked - $totalEstimated
        ];
    }
    
    public function getCompletedMeetings($milestoneId)
    {
        $meetings = $this->getMeetingsForMilestone($milestoneId);
        
        return array_filter($meetings['data'], function($meeting) {
            return $meeting['completed'] === true;
        });
    }
}
```

### Meeting Analytics Dashboard

```php
class MeetingAnalytics
{
    public function getMonthlyStats($userId, $month, $year)
    {
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));
        
        $meetings = Teamleader::meetings()
            ->withTrackedTime()
            ->list([
                'employee_id' => $userId,
                'start_date' => $start,
                'end_date' => $end
            ]);
        
        $total = count($meetings['data']);
        $completed = count(array_filter(
            $meetings['data'],
            fn($m) => $m['completed']
        ));
        
        $totalHours = array_reduce(
            $meetings['data'],
            fn($sum, $m) => $sum + ($m['tracked_time']['hours'] ?? 0),
            0
        );
        
        return [
            'total_meetings' => $total,
            'completed_meetings' => $completed,
            'pending_meetings' => $total - $completed,
            'total_hours' => round($totalHours, 2),
            'average_duration' => $total > 0 ? round($totalHours / $total, 2) : 0,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
    }
    
    public function getUpcomingMeetings($userId, $days = 7)
    {
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime("+{$days} days"));
        
        $meetings = Teamleader::meetings()->list([
            'employee_id' => $userId,
            'start_date' => $start,
            'end_date' => $end
        ]);
        
        // Filter out completed meetings
        $upcoming = array_filter($meetings['data'], function($meeting) {
            return !$meeting['completed'];
        });
        
        // Sort by start time
        usort($upcoming, function($a, $b) {
            return strtotime($a['starts_at']) - strtotime($b['starts_at']);
        });
        
        return $upcoming;
    }
}
```

## Best Practices

### 1. Always Include Customer Information

Meetings require customer information to be associated properly:

```php
// Required
$meeting = Teamleader::meetings()->schedule([
    'title' => 'Client Meeting',
    'starts_at' => '2025-02-20T10:00:00+00:00',
    'ends_at' => '2025-02-20T11:00:00+00:00',
    'attendees' => [['type' => 'user', 'id' => 'user-uuid']],
    'customer' => [ // Required
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

### 2. Include At Least One User Attendee

Meetings must have at least one user as an attendee:

```php
$meeting = Teamleader::meetings()->schedule([
    // ... other fields
    'attendees' => [
        ['type' => 'user', 'id' => 'user-uuid'], // At least one required
        ['type' => 'contact', 'id' => 'contact-uuid']
    ]
]);
```

### 3. Use Sideloading for Time Tracking

When you need time information, use sideloading instead of separate requests:

```php
// Good - single request
$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->withEstimatedTime()
    ->info('meeting-uuid');

// Less efficient - would require separate requests if available
```

### 4. Link Meetings to Milestones for Projects

For project-related meetings, always link to the relevant milestone:

```php
$meeting = Teamleader::meetings()->schedule([
    // ... other fields
    'milestone_id' => 'milestone-uuid' // Links to project milestone
]);
```

### 5. Complete Meetings When Done

Mark meetings as complete to maintain accurate records:

```php
// After meeting is done
Teamleader::meetings()->complete('meeting-uuid');

// If reopening is needed
Teamleader::meetings()->uncomplete('meeting-uuid');
```

### 6. Use Date Ranges for Calendar Views

When building calendar interfaces, use date ranges efficiently:

```php
// Get current week
$startOfWeek = now()->startOfWeek()->format('Y-m-d');
$endOfWeek = now()->endOfWeek()->format('Y-m-d');

$weekMeetings = Teamleader::meetings()->betweenDates(
    $startOfWeek,
    $endOfWeek
);
```

### 7. Handle Recurring Meetings

For recurring meetings, use the recurrence_id to manage the series:

```php
// Get all meetings in a recurring series
$seriesMeetings = Teamleader::meetings()->forRecurringSeries(
    'recurrence-uuid'
);

// Update all future meetings in series
foreach ($seriesMeetings['data'] as $meeting) {
    if (strtotime($meeting['starts_at']) > time()) {
        Teamleader::meetings()->update($meeting['id'], [
            'location' => 'New Location'
        ]);
    }
}
```

## Error Handling

### Common Errors and Solutions

**Missing Customer Information:**
```php
try {
    $meeting = Teamleader::meetings()->schedule([
        'title' => 'Meeting',
        // Missing customer
    ]);
} catch (\InvalidArgumentException $e) {
    // Handle: "Customer information is required"
}
```

**No User Attendees:**
```php
try {
    $meeting = Teamleader::meetings()->schedule([
        'attendees' => [
            ['type' => 'contact', 'id' => 'contact-uuid']
            // No user attendee
        ]
    ]);
} catch (\InvalidArgumentException $e) {
    // Handle: "At least one user attendee must be present"
}
```

**Invalid Date Format:**
```php
try {
    $meeting = Teamleader::meetings()->schedule([
        'starts_at' => '2025-02-20 10:00:00' // Wrong format
    ]);
} catch (\Exception $e) {
    // Handle: "Invalid datetime format. Use ISO 8601"
}
```

### Robust Error Handling Example

```php
class MeetingManager
{
    public function scheduleMeetingSafely(array $data)
    {
        try {
            // Validate data
            $this->validateMeetingData($data);
            
            // Check for conflicts
            if ($this->hasConflicts($data)) {
                return [
                    'success' => false,
                    'error' => 'Time conflict detected'
                ];
            }
            
            // Schedule meeting
            $meeting = Teamleader::meetings()->schedule($data);
            
            return [
                'success' => true,
                'meeting' => $meeting['data']
            ];
            
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => 'Validation error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to schedule meeting', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to schedule meeting'
            ];
        }
    }
    
    private function validateMeetingData(array $data)
    {
        $required = ['title', 'starts_at', 'ends_at', 'attendees', 'customer'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("{$field} is required");
            }
        }
    }
    
    private function hasConflicts(array $data)
    {
        // Check for time conflicts
        // Implementation details...
        return false;
    }
}
```

## Related Resources

- [Events](events.md) - General calendar events
- [Calls](calls.md) - Call-specific activities
- [Activity Types](activity-types.md) - Define meeting types
- [Projects](../projects/projects.md) - Associated projects
- [Milestones](../projects/milestones.md) - Project milestones
- [Companies](../crm/companies.md) - Customer companies
- [Contacts](../crm/contacts.md) - Customer contacts
- [Users](../users/users.md) - Meeting attendees

## Rate Limiting

All meeting operations consume 1 API credit per request:

- `list()`: 1 credit
- `info()`: 1 credit
- `schedule()`: 1 credit
- `update()`: 1 credit
- `complete()`: 1 credit
- `uncomplete()`: 1 credit
- `delete()`: 1 credit

Monitor your API usage to stay within rate limits.

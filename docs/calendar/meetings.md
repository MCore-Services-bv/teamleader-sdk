# Meetings

Manage meetings in Teamleader Focus Calendar. This resource provides complete CRUD operations for managing meetings, including scheduling, updating, completing meetings, and creating reports.

## Endpoint

`meetings`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ✅ Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of meetings with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$meetings = $teamleader->meetings()->list(['employee_id' => 'employee-uuid']);
```

### `info()`

Get detailed information about a specific meeting.

**Parameters:**
- `id` (string): Meeting UUID
- `includes` (array|string): Relations to include (tracked_time, estimated_time)

**Example:**
```php
$meeting = $teamleader->meetings()->withTrackedTime()->info('meeting-uuid');
```

### `schedule()`

Schedule a new meeting.

**Parameters:**
- `data` (array): Array of meeting data

**Example:**
```php
$meeting = $teamleader->meetings()->schedule([
    'title' => 'Project Kickoff Meeting',
    'starts_at' => '2023-10-01T09:00:00+01:00',
    'ends_at' => '2023-10-01T10:00:00+01:00',
    'description' => 'Initial project discussion',
    'attendees' => [
        ['type' => 'user', 'id' => 'user-uuid'],
        ['type' => 'contact', 'id' => 'contact-uuid']
    ],
    'customer' => [
        'type' => 'contact',
        'id' => 'customer-uuid'
    ],
    'location' => [
        'type' => 'virtual'
    ]
]);
```

### `update()`

Update an existing meeting.

**Parameters:**
- `id` (string): Meeting UUID
- `data` (array): Array of data to update

**Example:**
```php
$meeting = $teamleader->meetings()->update('meeting-uuid', [
    'title' => 'Updated Meeting Title',
    'description' => 'Updated description'
]);
```

### `delete()`

Delete a meeting.

**Parameters:**
- `id` (string): Meeting UUID

**Example:**
```php
$result = $teamleader->meetings()->delete('meeting-uuid');
```

### `complete()`

Mark a meeting as complete.

**Parameters:**
- `id` (string): Meeting UUID

**Example:**
```php
$result = $teamleader->meetings()->complete('meeting-uuid');
```

### `createReport()`

Create a report for a completed meeting.

**Parameters:**
- `meetingId` (string): Meeting UUID
- `reportData` (array): Report data including attachment target and summary

**Example:**
```php
$report = $teamleader->meetings()->createReport('meeting-uuid', [
    'attach_to' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ],
    'summary' => 'Meeting went well, next steps discussed',
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Field value'
        ]
    ]
]);
```

### `forEmployee()`

Get meetings for a specific employee.

**Parameters:**
- `employeeId` (string): Employee UUID
- `options` (array): Additional options

**Example:**
```php
$meetings = $teamleader->meetings()->forEmployee('employee-uuid');
```

### `inDateRange()`

Get meetings within a date range.

**Parameters:**
- `startDate` (string): Start date (YYYY-MM-DD)
- `endDate` (string): End date (YYYY-MM-DD)
- `options` (array): Additional options

**Example:**
```php
$meetings = $teamleader->meetings()->inDateRange('2023-10-01', '2023-10-31');
```

### `today()`

Get meetings for today.

**Parameters:**
- `options` (array): Additional options

**Example:**
```php
$todayMeetings = $teamleader->meetings()->today();
```

### `search()`

Search meetings by term.

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$meetings = $teamleader->meetings()->search('project meeting');
```

### `withTrackedTime()` / `withEstimatedTime()`

Include time tracking information in the response.

**Example:**
```php
$meeting = $teamleader->meetings()->withTrackedTime()->info('meeting-uuid');
```

## Meeting Fields

### Required Fields for Scheduling

- `title` (string): Meeting title
- `starts_at` (string): Meeting start time (ISO 8601 format)
- `ends_at` (string): Meeting end time (ISO 8601 format)
- `attendees` (array): Array of attendees (at least one user required)
- `customer` (object): Customer information

### Optional Fields

- `description` (string): Meeting description
- `location` (object): Location information
- `milestone_id` (string): Associated milestone UUID
- `deal_id` (string): Associated deal UUID
- `work_order_id` (string): Associated work order UUID
- `custom_fields` (array): Custom field values

### Meeting Response Fields

All scheduling fields plus:
- `id` (string): Meeting UUID
- `created_at` (string): Creation timestamp
- `scheduled_at` (string): Scheduled time
- `duration` (object): Meeting duration
- `status` (string): open, done
- `project` (object): Associated project
- `milestone` (object): Associated milestone
- `deal` (object): Associated deal
- `recurrence` (object): Recurring meeting information
- `tracked_time` (object): Time tracking data (if included)
- `estimated_time` (object): Time estimates (if included)

## Usage Examples

### Basic Meeting Management

```php
// Schedule a meeting
$meeting = $teamleader->meetings()->schedule([
    'title' => 'Client Review',
    'starts_at' => '2023-10-01T14:00:00+01:00',
    'ends_at' => '2023-10-01T15:00:00+01:00',
    'attendees' => [
        ['type' => 'user', 'id' => 'user-uuid']
    ],
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Update meeting
$teamleader->meetings()->update($meeting['data']['id'], [
    'title' => 'Updated Client Review',
    'description' => 'Updated agenda items'
]);

// Mark as complete
$teamleader->meetings()->complete($meeting['data']['id']);

// Create meeting report
$report = $teamleader->meetings()->createReport($meeting['data']['id'], [
    'attach_to' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'summary' => 'Client was satisfied with progress'
]);
```

### Advanced Features

```php
// Get meetings with time tracking
$meetings = $teamleader->meetings()
    ->withTrackedTime()
    ->forEmployee('employee-uuid');

// Filter meetings by date range and milestone
$projectMeetings = $teamleader->meetings()->list([
    'start_date' => '2023-10-01',
    'end_date' => '2023-10-31',
    'milestone_id' => 'milestone-uuid'
], [
    'sort' => [
        ['field' => 'scheduled_at', 'order' => 'asc']
    ],
    'page_size' => 50
]);

// Search meetings with specific term
$searchResults = $teamleader->meetings()->search('kickoff', [
    'filters' => ['employee_id' => 'employee-uuid']
]);
```

### Custom Fields Integration

```php
// Schedule meeting with custom fields
$meeting = $teamleader->meetings()->schedule([
    'title' => 'Project Meeting',
    'starts_at' => '2023-10-01T10:00:00+01:00',
    'ends_at' => '2023-10-01T11:00:00+01:00',
    'attendees' => [['type' => 'user', 'id' => 'user-uuid']],
    'customer' => ['type' => 'contact', 'id' => 'contact-uuid'],
    'custom_fields' => [
        [
            'id' => 'meeting-type-field-uuid',
            'value' => 'Planning'
        ],
        [
            'id' => 'priority-field-uuid', 
            'value' => 'High'
        ]
    ]
]);

// Create report with custom fields
$report = $teamleader->meetings()->createReport('meeting-uuid', [
    'attach_to' => ['type' => 'contact', 'id' => 'contact-uuid'],
    'summary' => 'Meeting summary',
    'custom_fields' => [
        [
            'id' => 'outcome-field-uuid',
            'value' => 'Positive'
        ]
    ]
]);
```

## Error Handling

The meetings resource follows standard SDK error handling:

```php
$result = $teamleader->meetings()->schedule($meetingData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Meetings API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Meeting API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **CRUD operations**: 1 request per call
- **Complete operations**: 1 request per call
- **Report creation**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class MeetingsController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $meetings = $teamleader->meetings()->today();
        return view('calendar.meetings.index', compact('meetings'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $meeting = $teamleader->meetings()->schedule($request->validated());
        return redirect()->route('calendar.meetings.show', $meeting['data']['id']);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

# Calls

Manage calls in Teamleader Focus Calendar. This resource provides operations for scheduling, updating, and completing calls with customers.

## Endpoint

`calls`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of calls with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
    - `scheduled_after` (string): Filter calls on or after date (YYYY-MM-DD)
    - `scheduled_before` (string): Filter calls on or before date (YYYY-MM-DD)
    - `relates_to` (array): Filter by related object
    - `call_outcome_id` (string): Filter completed calls by outcome
- `options` (array): Pagination options
    - `page_size` (int): Number of results per page (default: 20)
    - `page_number` (int): Page number (default: 1)

**Example:**
```php
$calls = $teamleader->calls()->list([
    'scheduled_after' => '2024-01-01',
    'scheduled_before' => '2024-12-31'
]);
```

### `info()`

Get detailed information about a specific call.

**Parameters:**
- `id` (string): Call UUID

**Example:**
```php
$call = $teamleader->calls()->info('call-uuid-here');
```

### `create()` / `schedule()`

Create a new call. The `schedule()` method is an alias for better readability.

**Parameters:**
- `data` (array): Array of call data
    - `description` (string, optional): Description of the call
    - `participant` (array, required): Participant information
        - `customer` (array): Customer details
            - `type` (string): 'contact' or 'company'
            - `id` (string): Customer UUID
    - `due_at` (string, required): Scheduled datetime (ISO 8601)
    - `assignee` (array, required): Assignee information
        - `type` (string): 'user'
        - `id` (string): User UUID
    - `deal_id` (string, optional): Related deal UUID
    - `custom_fields` (array, optional): Custom field values

**Example:**
```php
$call = $teamleader->calls()->create([
    'description' => 'Follow up on proposal',
    'participant' => [
        'customer' => [
            'type' => 'contact',
            'id' => 'contact-uuid'
        ]
    ],
    'due_at' => '2024-02-15T14:00:00+00:00',
    'assignee' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ]
]);
```

### `update()`

Update an existing call.

**Parameters:**
- `id` (string): Call UUID
- `data` (array): Array of data to update

**Example:**
```php
$call = $teamleader->calls()->update('call-uuid', [
    'description' => 'Updated description',
    'due_at' => '2024-02-16T15:00:00+00:00'
]);
```

### `complete()`

Mark a call as complete with optional outcome.

**Parameters:**
- `id` (string): Call UUID
- `outcomeId` (string, optional): Call outcome UUID
- `outcomeSummary` (string, optional): Summary of the outcome

**Example:**
```php
$result = $teamleader->calls()->complete(
    'call-uuid',
    'outcome-uuid',
    'Called, but was not available'
);
```

### `reschedule()`

Reschedule a call to a new date/time.

**Parameters:**
- `id` (string): Call UUID
- `newDateTime` (string): New scheduled datetime (ISO 8601)

**Example:**
```php
$call = $teamleader->calls()->reschedule(
    'call-uuid',
    '2024-02-20T10:00:00+00:00'
);
```

## Convenience Methods

### `upcoming()`

Get all upcoming calls (scheduled after today).

**Parameters:**
- `options` (array): Pagination options

**Example:**
```php
$upcomingCalls = $teamleader->calls()->upcoming();
```

### `overdue()`

Get all overdue calls (scheduled before today, not completed).

**Parameters:**
- `options` (array): Pagination options

**Example:**
```php
$overdueCalls = $teamleader->calls()->overdue();
```

### `today()`

Get today's scheduled calls.

**Parameters:**
- `options` (array): Pagination options

**Example:**
```php
$todaysCalls = $teamleader->calls()->today();
```

### `thisWeek()`

Get this week's scheduled calls.

**Parameters:**
- `options` (array): Pagination options

**Example:**
```php
$weekCalls = $teamleader->calls()->thisWeek();
```

### `forCompany()`

Get all calls for a specific company.

**Parameters:**
- `companyId` (string): Company UUID
- `options` (array): Additional options

**Example:**
```php
$companyCalls = $teamleader->calls()->forCompany('company-uuid');
```

### `betweenDates()`

Get calls within a specific date range.

**Parameters:**
- `startDate` (string): Start date (YYYY-MM-DD)
- `endDate` (string): End date (YYYY-MM-DD)
- `options` (array): Additional options

**Example:**
```php
$calls = $teamleader->calls()->betweenDates('2024-01-01', '2024-01-31');
```

### `withOutcome()`

Get completed calls with a specific outcome.

**Parameters:**
- `outcomeId` (string): Call outcome UUID
- `options` (array): Additional options

**Example:**
```php
$completedCalls = $teamleader->calls()->withOutcome('outcome-uuid');
```

## Field Descriptions

### Call Request Fields

- `description` (string): Description or notes about the call
- `participant` (object): Who the call is with
- `due_at` (string): When the call is scheduled (ISO 8601)
- `assignee` (object): User responsible for the call
- `deal_id` (string): Related deal UUID
- `custom_fields` (array): Custom field values

### Call Response Fields

- `id` (string): Call UUID
- `added_at` (string): Creation timestamp
- `completed_at` (string): Completion timestamp (if completed)
- `participant` (object): Participant details with customer info
- `description` (string): Call description
- `outcome` (object): Outcome details (if completed)
- `outcome_summary` (string): Outcome summary text
- `assignee` (object): Assigned user details
- `scheduled_at` (string): Scheduled datetime
- `status` (string): 'open' or 'completed'
- `deal` (object): Related deal details

## Usage Examples

### Basic Call Management

```php
// Schedule a call
$call = $teamleader->calls()->schedule([
    'description' => 'Quarterly review meeting',
    'participant' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'due_at' => '2024-03-15T10:00:00+00:00',
    'assignee' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ]
]);

// Get call details
$callDetails = $teamleader->calls()->info($call['data']['id']);

// Complete the call
$teamleader->calls()->complete(
    $call['data']['id'],
    'outcome-uuid',
    'Successful meeting, agreed on next steps'
);
```

### Advanced Features

```php
// Get all overdue calls
$overdueCalls = $teamleader->calls()->overdue();

// Get calls for a specific company this week
$companyCalls = $teamleader->calls()->list([
    'relates_to' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'scheduled_after' => date('Y-m-d', strtotime('monday this week')),
    'scheduled_before' => date('Y-m-d', strtotime('sunday this week'))
]);

// Reschedule multiple calls
foreach ($overdueCalls['data'] as $call) {
    $newTime = date('Y-m-d\TH:i:s+00:00', strtotime('+1 day'));
    $teamleader->calls()->reschedule($call['id'], $newTime);
}
```

## Error Handling

The calls resource follows standard SDK error handling:

```php
$result = $teamleader->calls()->create($callData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Calls API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Call API operations count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **Create/Update operations**: 1 request per call
- **Complete operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CallController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $calls = $teamleader->calls()->upcoming();
        return view('calendar.calls.index', compact('calls'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $call = $teamleader->calls()->create($request->validated());
        return redirect()->route('calendar.calls.show', $call['data']['id']);
    }
    
    public function complete(Request $request, TeamleaderSDK $teamleader, $id)
    {
        $teamleader->calls()->complete(
            $id,
            $request->input('outcome_id'),
            $request->input('outcome_summary')
        );
        
        return redirect()->route('calendar.calls.index')
            ->with('success', 'Call marked as complete');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

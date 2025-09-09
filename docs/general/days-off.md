# Days Off

Manage days off for users in Teamleader Focus. This resource provides bulk import and delete operations for user days off records.

## Endpoint

`daysOff`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported (use bulk import)
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported (use bulk delete)
- **Supports Batch**: ✅ Supported

## Available Methods

### `bulkImport()`

Import multiple days off for a user.

**Parameters:**
- `userId` (string): The user UUID that the days off belong to
- `leaveTypeId` (string): The leave type UUID (from dayOffTypes resource)
- `days` (array): Array of day objects with starts_at and ends_at

**Example:**
```php
$result = $teamleader->daysOff()->bulkImport($userId, $leaveTypeId, $days);
```

### `bulkDelete()`

Delete multiple days off for a user.

**Parameters:**
- `userId` (string): The user UUID that owns the days off
- `dayOffIds` (array): Array of day off UUIDs to delete

**Example:**
```php
$result = $teamleader->daysOff()->bulkDelete($userId, $dayOffIds);
```

### `importSingleDay()`

Import a single day off - convenience method.

**Parameters:**
- `userId` (string): The user UUID
- `leaveTypeId` (string): The leave type UUID
- `startsAt` (string): Start datetime (ISO 8601 format)
- `endsAt` (string): End datetime (ISO 8601 format)

**Example:**
```php
$result = $teamleader->daysOff()->importSingleDay($userId, $leaveTypeId, $startsAt, $endsAt);
```

### `importMultipleDays()`

Import multiple days off with the same duration pattern.

**Parameters:**
- `userId` (string): The user UUID
- `leaveTypeId` (string): The leave type UUID
- `dates` (array): Array of date strings (Y-m-d format)
- `startTime` (string): Start time (H:i:s format, default: '08:00:00')
- `endTime` (string): End time (H:i:s format, default: '18:00:00')
- `timezone` (string): Timezone (default: '+00:00')

**Example:**
```php
$result = $teamleader->daysOff()->importMultipleDays(
    $userId, 
    $leaveTypeId, 
    ['2024-02-01', '2024-02-02', '2024-02-05']
);
```

### `importDateRange()`

Import a date range of days off.

**Parameters:**
- `userId` (string): The user UUID
- `leaveTypeId` (string): The leave type UUID
- `startDate` (string): Start date (Y-m-d format)
- `endDate` (string): End date (Y-m-d format)
- `startTime` (string): Start time (H:i:s format, default: '08:00:00')
- `endTime` (string): End time (H:i:s format, default: '18:00:00')
- `timezone` (string): Timezone (default: '+00:00')
- `excludeWeekends` (bool): Whether to exclude weekends (default: true)

**Example:**
```php
$result = $teamleader->daysOff()->importDateRange(
    $userId,
    $leaveTypeId,
    '2024-02-01',
    '2024-02-07',
    '08:00:00',
    '18:00:00',
    '+00:00',
    true  // exclude weekends
);
```

## Data Structures

### Day Object Format

Each day in the `days` array must have the following structure:

```php
[
    'starts_at' => '2024-02-01T08:00:00+00:00',  // ISO 8601 format
    'ends_at' => '2024-02-01T18:00:00+00:00'     // ISO 8601 format
]
```

### Required UUID Formats

- **User ID**: Must be a valid UUID (e.g., `f29abf48-337d-44b4-aad4-585f5277a456`)
- **Leave Type ID**: Must be a valid UUID (e.g., `0f517e20-2e76-4684-8d6c-3334f6d7148c`)
- **Day Off IDs**: Must be valid UUIDs for deletion operations

## Usage Examples

### Basic Bulk Import

Import multiple days off for a user:

```php
$userId = 'f29abf48-337d-44b4-aad4-585f5277a456';
$leaveTypeId = '0f517e20-2e76-4684-8d6c-3334f6d7148c';

$days = [
    [
        'starts_at' => '2024-02-01T08:00:00+00:00',
        'ends_at' => '2024-02-01T18:00:00+00:00'
    ],
    [
        'starts_at' => '2024-02-02T08:00:00+00:00',
        'ends_at' => '2024-02-02T18:00:00+00:00'
    ]
];

$result = $teamleader->daysOff()->bulkImport($userId, $leaveTypeId, $days);

if (isset($result['error'])) {
    // Handle error
    echo "Error: " . $result['message'];
} else {
    // Success - check HTTP status 201
    echo "Days off imported successfully";
}
```

### Basic Bulk Delete

Delete multiple days off:

```php
$userId = 'f29abf48-337d-44b4-aad4-585f5277a456';
$dayOffIds = [
    '0a481ce9-0d2a-0913-9439-0fd8b469b566',
    '5050789e-4385-02f6-bd3c-d051cc12f5cf'
];

$result = $teamleader->daysOff()->bulkDelete($userId, $dayOffIds);

if (isset($result['error'])) {
    // Handle error
    echo "Error: " . $result['message'];
} else {
    // Success - check HTTP status 204
    echo "Days off deleted successfully";
}
```

### Single Day Import

Import a single day off:

```php
$result = $teamleader->daysOff()->importSingleDay(
    'f29abf48-337d-44b4-aad4-585f5277a456',
    '0f517e20-2e76-4684-8d6c-3334f6d7148c',
    '2024-02-01T08:00:00+00:00',
    '2024-02-01T18:00:00+00:00'
);
```

### Multiple Days with Same Pattern

Import multiple days with the same work hours:

```php
$dates = ['2024-02-01', '2024-02-02', '2024-02-05'];

$result = $teamleader->daysOff()->importMultipleDays(
    $userId,
    $leaveTypeId,
    $dates,
    '08:00:00',  // start time
    '18:00:00',  // end time
    '+01:00'     // timezone (CET)
);
```

### Date Range Import

Import a full week of vacation (excluding weekends):

```php
$result = $teamleader->daysOff()->importDateRange(
    $userId,
    $leaveTypeId,
    '2024-02-05',  // Monday
    '2024-02-09',  // Friday
    '08:00:00',
    '18:00:00',
    '+01:00',
    true  // exclude weekends
);
```

### Half Day Import

Import half days (morning only):

```php
$days = [
    [
        'starts_at' => '2024-02-01T08:00:00+01:00',
        'ends_at' => '2024-02-01T12:00:00+01:00'  // Half day - morning
    ],
    [
        'starts_at' => '2024-02-02T13:00:00+01:00', // Half day - afternoon
        'ends_at' => '2024-02-02T18:00:00+01:00'
    ]
];

$result = $teamleader->daysOff()->bulkImport($userId, $leaveTypeId, $days);
```

## Response Format

### Successful Import Response

HTTP Status: `201 Created`

```json
{
    "success": true,
    "status_code": 201,
    "message": "Days off imported successfully"
}
```

### Successful Delete Response

HTTP Status: `204 No Content`

```json
{
    "success": true,
    "status_code": 204,
    "message": "Days off deleted successfully"
}
```

### Error Response

```json
{
    "error": true,
    "status_code": 400,
    "message": "Validation failed",
    "errors": [
        "Day at index 0 has invalid starts_at format"
    ]
}
```

## DateTime Format Requirements

All datetime values must be in **ISO 8601 format** with timezone information:

- **Format**: `YYYY-MM-DDTHH:MM:SS±HH:MM`
- **Example**: `2024-02-01T08:00:00+01:00`

### Common Timezones

- **UTC**: `+00:00`
- **CET (Central European Time)**: `+01:00`
- **CEST (Central European Summer Time)**: `+02:00`
- **EST (Eastern Standard Time)**: `-05:00`
- **PST (Pacific Standard Time)**: `-08:00`

### Common Work Hour Patterns

- **Full Day**: `08:00:00` to `18:00:00`
- **Half Day (Morning)**: `08:00:00` to `12:00:00`
- **Half Day (Afternoon)**: `13:00:00` to `18:00:00`
- **Short Day**: `08:00:00` to `12:30:00`

## Helper Methods

### Formatting Helpers

The resource provides several helper methods for working with dates and times:

```php
// Get formatting information
$helpers = $teamleader->daysOff()->getFormattingHelpers();

// Format a DateTime object for the API
$datetime = new DateTime('2024-02-01 08:00:00', new DateTimeZone('Europe/Brussels'));
$formatted = $teamleader->daysOff()->formatDatetime($datetime);

// Create datetime string from components
$datetime = $teamleader->daysOff()->createDatetime('2024-02-01', '08:00:00', '+01:00');
```

## Validation

The resource includes comprehensive validation:

### User ID Validation
- Must be a valid UUID format
- Cannot be empty

### Leave Type ID Validation
- Must be a valid UUID format
- Cannot be empty
- Should exist in the dayOffTypes resource

### Days Array Validation
- At least one day must be provided
- Each day must have `starts_at` and `ends_at`
- Datetime values must be in ISO 8601 format
- `starts_at` must be before `ends_at`

### Day Off IDs Validation (for deletion)
- At least one ID must be provided
- All IDs must be valid UUID format
- IDs must belong to the specified user

## Error Handling

### Common Errors

```php
try {
    $result = $teamleader->daysOff()->bulkImport($userId, $leaveTypeId, $days);
    
    if (isset($result['error']) && $result['error']) {
        throw new Exception($result['message']);
    }
    
} catch (InvalidArgumentException $e) {
    // Validation error (client-side)
    Log::error('Days off validation error: ' . $e->getMessage());
    
} catch (Exception $e) {
    // API error (server-side)
    Log::error('Days off API error: ' . $e->getMessage());
}
```

### Typical Validation Errors

- `"User ID is required"`
- `"Leave type ID must be a valid UUID format"`
- `"At least one day must be provided"`
- `"Day at index 0 has invalid starts_at format"`
- `"Day at index 1 has starts_at after or equal to ends_at"`

## Relationship with Other Resources

### Day Off Types (`dayOffTypes`)

The `leave_type_id` parameter references records from the Day Off Types resource:

```php
// First, get available leave types
$leaveTypes = $teamleader->dayOffTypes()->list();

// Use a leave type ID for importing days off
$leaveTypeId = $leaveTypes['data'][0]['id'];
$result = $teamleader->daysOff()->bulkImport($userId, $leaveTypeId, $days);
```

### Users (`users`)

The `user_id` parameter references user records:

```php
// Get users first
$users = $teamleader->users()->list(['status' => ['active']]);

// Import days off for a specific user
$userId = $users['data'][0]['id'];
$result = $teamleader->daysOff()->bulkImport($userId, $leaveTypeId, $days);
```

## Rate Limiting

Days off operations count towards your Teamleader API rate limit:

- **Bulk Import**: 1 request per operation (regardless of number of days)
- **Bulk Delete**: 1 request per operation (regardless of number of days)

**Rate limit cost**: 1 request per method call

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DaysOffController extends Controller
{
    public function import(Request $request, TeamleaderSDK $teamleader)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid',
            'leave_type_id' => 'required|uuid',
            'days' => 'required|array|min:1',
            'days.*.starts_at' => 'required|date_format:Y-m-d\TH:i:sP',
            'days.*.ends_at' => 'required|date_format:Y-m-d\TH:i:sP'
        ]);

        try {
            $result = $teamleader->daysOff()->bulkImport(
                $validated['user_id'],
                $validated['leave_type_id'],
                $validated['days']
            );

            return redirect()->back()->with('success', 'Days off imported successfully');
            
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to import days off: ' . $e->getMessage());
        }
    }

    public function delete(Request $request, TeamleaderSDK $teamleader)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid',
            'day_off_ids' => 'required|array|min:1',
            'day_off_ids.*' => 'uuid'
        ]);

        try {
            $result = $teamleader->daysOff()->bulkDelete(
                $validated['user_id'],
                $validated['day_off_ids']
            );

            return redirect()->back()->with('success', 'Days off deleted successfully');
            
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete days off: ' . $e->getMessage());
        }
    }
}
```

## Notes

- This resource only supports **bulk operations** - no individual create/read/update/delete
- All datetime values must include timezone information
- The resource validates UUID formats for all ID parameters
- Use the `dayOffTypes` resource to get valid leave type IDs
- Weekend exclusion in `importDateRange()` uses Monday=1, Sunday=7 format
- Maximum recommended days per bulk operation: 100 (not enforced by API but good for performance)

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

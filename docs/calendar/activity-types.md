# Activity Types

Manage activity types in Teamleader Focus Calendar. This resource provides read-only access to activity types, which are used to categorize calendar activities.

## Endpoint

`activityTypes`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported (by IDs only)
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported (Read-only)
- **Supports Update**: ❌ Not Supported (Read-only)
- **Supports Deletion**: ❌ Not Supported (Read-only)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of activity types with optional filtering.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination options

**Example:**
```php
$activityTypes = $teamleader->activityTypes()->list();
```

### `byIds()`

Get specific activity types by their UUIDs.

**Parameters:**
- `ids` (array): Array of activity type UUIDs
- `options` (array): Additional options

**Example:**
```php
$activityTypes = $teamleader->activityTypes()->byIds([
    '811a5825-96f4-4318-83c3-2840935c6003',
    '366b6100-7005-4b1b-a16a-7e88f445f496'
]);
```

### `all()`

Get all activity types (convenience method).

**Parameters:**
- `options` (array): Additional options

**Example:**
```php
$allActivityTypes = $teamleader->activityTypes()->all();
```

### `findByName()`

Find an activity type by name (case-insensitive).

**Parameters:**
- `name` (string): Activity type name to search for
- `options` (array): Additional options

**Example:**
```php
$meetingType = $teamleader->activityTypes()->findByName('Meeting');
```

### `exists()`

Check if an activity type exists by ID.

**Parameters:**
- `id` (string): Activity type UUID

**Example:**
```php
$exists = $teamleader->activityTypes()->exists('811a5825-96f4-4318-83c3-2840935c6003');
```

### `selectOptions()`

Get activity types formatted for HTML select options.

**Parameters:**
- `options` (array): Additional options

**Example:**
```php
$options = $teamleader->activityTypes()->selectOptions();
// Returns: [['value' => 'uuid', 'label' => 'Meeting'], ...]
```

## Filtering

Activity types support limited filtering options:

### By IDs
Filter to specific activity types by providing an array of UUIDs:

```php
$activityTypes = $teamleader->activityTypes()->list([
    'ids' => [
        '811a5825-96f4-4318-83c3-2840935c6003',
        '366b6100-7005-4b1b-a16a-7e88f445f496'
    ]
]);
```

## Usage Examples

### Basic Usage

```php
// Get all activity types
$allTypes = $teamleader->activityTypes()->all();

// Get specific activity types
$specificTypes = $teamleader->activityTypes()->byIds([
    '811a5825-96f4-4318-83c3-2840935c6003'
]);

// Find by name
$meetingType = $teamleader->activityTypes()->findByName('Meeting');
```

### Pagination

```php
// Get first page with 10 items
$firstPage = $teamleader->activityTypes()->list([], [
    'page_size' => 10,
    'page_number' => 1
]);

// Get second page
$secondPage = $teamleader->activityTypes()->list([], [
    'page_size' => 10,
    'page_number' => 2
]);
```

### Form Integration

```php
// Get options for HTML select
$selectOptions = $teamleader->activityTypes()->selectOptions();

// Use in Laravel view
return view('calendar.activity.create', [
    'activityTypeOptions' => $selectOptions
]);
```

## Response Structure

Activity types have a simple structure:

```json
{
    "data": [
        {
            "id": "811a5825-96f4-4318-83c3-2840935c6003",
            "name": "Meeting"
        },
        {
            "id": "366b6100-7005-4b1b-a16a-7e88f445f496",
            "name": "Call"
        }
    ],
    "meta": {
        "page": {
            "size": 20,
            "number": 1
        },
        "matches": 2
    }
}
```

## Error Handling

The activity types resource follows standard SDK error handling:

```php
$result = $teamleader->activityTypes()->list();

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Activity Types API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Activity types API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CalendarController extends Controller
{
    public function createActivity(TeamleaderSDK $teamleader)
    {
        $activityTypes = $teamleader->activityTypes()->selectOptions();
        return view('calendar.activity.create', compact('activityTypes'));
    }
    
    public function validateActivityType(Request $request, TeamleaderSDK $teamleader)
    {
        $typeId = $request->get('activity_type_id');
        $exists = $teamleader->activityTypes()->exists($typeId);
        
        return response()->json(['valid' => $exists]);
    }
}
```

## Notes

- Activity types are **read-only** in the Teamleader API
- They are system-defined and cannot be created, updated, or deleted via the API
- The resource only supports filtering by IDs
- No sorting or sideloading capabilities are available
- Pagination is supported but typically not needed due to the small number of activity types

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

# Users

Manage users in Teamleader Focus. This resource provides comprehensive access to user information, schedules, and time-off data.

## Endpoint

`users`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ✅ Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of users with filtering, sorting, and pagination options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
$users = $teamleader->users()->list(['status' => ['active']]);
```

### `info()`

Get detailed information about a specific user.

**Parameters:**
- `id` (string): User UUID
- `includes` (array|string): Relations to include (external_rate)

**Example:**
```php
$user = $teamleader->users()->info('user-uuid-here', 'external_rate');
```

### `me()`

Get the current authenticated user information.

**Example:**
```php
$currentUser = $teamleader->users()->me();
```

### `getWeekSchedule()`

Get week schedule information for a user. Only available with the Weekly working schedule feature.

**Parameters:**
- `id` (string): User UUID

**Example:**
```php
$schedule = $teamleader->users()->getWeekSchedule('user-uuid-here');
```

### `listDaysOff()`

Get days off information for a user within a specified period.

**Parameters:**
- `id` (string): User UUID
- `filters` (array): Filter options (starts_after, ends_before)
- `options` (array): Pagination options

**Example:**
```php
$daysOff = $teamleader->users()->listDaysOff('user-uuid-here', [
    'starts_after' => '2023-10-01',
    'ends_before' => '2023-10-30'
]);
```

### `active()`

Get only active users.

**Example:**
```php
$activeUsers = $teamleader->users()->active();
```

### `deactivated()`

Get only deactivated users.

**Example:**
```php
$deactivatedUsers = $teamleader->users()->deactivated();
```

### `search()`

Search users by term (first name, last name, email, function).

**Parameters:**
- `term` (string): Search term

**Example:**
```php
$users = $teamleader->users()->search('John');
```

### `byIds()`

Get specific users by their UUIDs.

**Parameters:**
- `ids` (array): Array of user UUIDs

**Example:**
```php
$users = $teamleader->users()->byIds(['uuid1', 'uuid2']);
```

### `withExternalRate()`

Fluent method to include external hourly rates in the response.

**Example:**
```php
$users = $teamleader->users()->withExternalRate()->list();
```

## Filtering

### Available Filters

- **`ids`**: Array of user UUIDs to filter by
- **`term`**: Search filter on first name, last name, email and function
- **`status`**: Filter by user status (active, deactivated)

### Filter Examples

```php
// Filter by status
$activeUsers = $teamleader->users()->list([
    'status' => ['active']
]);

// Search by term
$users = $teamleader->users()->list([
    'term' => 'John'
]);

// Filter by specific IDs
$specificUsers = $teamleader->users()->list([
    'ids' => [
        'cb8da52a-ce89-4bf6-8f7e-8ee6cb85e3b5',
        'f8a57a6f-dd1e-41a3-b8d3-428663f1d09e'
    ]
]);

// Combine filters
$filteredUsers = $teamleader->users()->list([
    'status' => ['active'],
    'term' => 'Sales'
]);
```

## Sorting

### Available Sort Fields

- **`first_name`**: Sort by first name
- **`last_name`**: Sort by last name
- **`email`**: Sort by email address
- **`function`**: Sort by user function/role

### Sorting Examples

```php
// Sort by first name (ascending)
$users = $teamleader->users()->list([], [
    'sort' => [
        [
            'field' => 'first_name',
            'order' => 'asc'
        ]
    ]
]);

// Sort by last name (descending)
$users = $teamleader->users()->list([], [
    'sort' => [
        [
            'field' => 'last_name',
            'order' => 'desc'
        ]
    ]
]);

// Multiple sort criteria
$users = $teamleader->users()->list([], [
    'sort' => [
        [
            'field' => 'function',
            'order' => 'asc'
        ],
        [
            'field' => 'first_name',
            'order' => 'asc'
        ]
    ]
]);
```

## Sideloading

### Available Includes

- **`external_rate`**: Include external hourly rates for the user

### Sideloading Examples

```php
// Include external rates
$user = $teamleader->users()->info('user-uuid', 'external_rate');

// Using fluent interface
$users = $teamleader->users()->withExternalRate()->list();

// Multiple users with external rates
$users = $teamleader->users()->withExternalRate()->active();
```

## Pagination

```php
// Custom pagination
$users = $teamleader->users()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// Default pagination (20 per page, page 1)
$users = $teamleader->users()->list();
```

## Response Formats

### List Response

```json
{
    "data": [
        {
            "id": "cb8da52a-ce89-4bf6-8f7e-8ee6cb85e3b5",
            "account": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "account"
            },
            "first_name": "John",
            "last_name": "Smith",
            "email": "john@teamleader.eu",
            "telephones": [
                {
                    "type": "phone",
                    "number": "092980615"
                }
            ],
            "language": "nl",
            "function": "Sales",
            "status": "active",
            "teams": []
        }
    ]
}
```

### Single User Response (info/me)

```json
{
    "data": {
        "id": "cb8da52a-ce89-4bf6-8f7e-8ee6cb85e3b5",
        "account": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "account"
        },
        "first_name": "John",
        "last_name": "Smith",
        "email": "john@teamleader.eu",
        "email_verification_status": "confirmed",
        "telephones": [
            {
                "type": "phone",
                "number": "092980615"
            }
        ],
        "language": "nl-BE",
        "function": "Sales",
        "time_zone": "Europe/Brussels",
        "preferences": {
            "invoiceable": true,
            "historic_time_tracking_limit": {
                "unit": "hour",
                "value": 24
            },
            "whitelabeling": true
        },
        "external_rate": {
            "amount": 123.3,
            "currency": "EUR"
        }
    }
}
```

### Week Schedule Response

```json
{
    "data": {
        "periods": [
            {
                "type": "working_hours",
                "start": {
                    "day": "monday",
                    "time": "09:00"
                },
                "end": {
                    "day": "monday",
                    "time": "17:00"
                }
            },
            {
                "type": "lunch_break",
                "start": {
                    "day": "monday",
                    "time": "12:00"
                },
                "end": {
                    "day": "monday",
                    "time": "13:00"
                }
            }
        ]
    }
}
```

### Days Off Response

```json
{
    "data": [
        {
            "id": "f611da79-90c2-02b1-b819-a810e0c77291",
            "starts_at": "2023-10-01T09:00:00+01:00",
            "ends_at": "2023-10-20T18:00:00+01:00",
            "user": {},
            "leave_type": {},
            "status": "approved"
        }
    ],
    "meta": {
        "page": {
            "size": 10,
            "number": 2
        },
        "matches": 12
    }
}
```

## Data Fields

### Common User Fields

- **`id`**: User UUID
- **`account`**: Account reference object
- **`first_name`**: User's first name
- **`last_name`**: User's last name
- **`email`**: User's email address
- **`telephones`**: Array of phone numbers with types (phone, mobile, fax)
- **`language`**: User's language preference
- **`function`**: User's role/function
- **`status`**: User status (active, deactivated)
- **`teams`**: Array of team memberships

### Additional Fields (info/me only)

- **`email_verification_status`**: Email verification status (pending, confirmed)
- **`time_zone`**: User's timezone (e.g., "Europe/Brussels")
- **`preferences`**: User preferences object
    - **`invoiceable`**: Whether user time is billable
    - **`historic_time_tracking_limit`**: Time tracking history limits
    - **`whitelabeling`**: Whitelabel preferences
- **`external_rate`**: External hourly rate (if included)
    - **`amount`**: Rate amount
    - **`currency`**: Currency code

### Week Schedule Fields

- **`periods`**: Array of schedule periods
    - **`type`**: Period type (working_hours, lunch_break)
    - **`start`**: Start time object (day, time)
    - **`end`**: End time object (day, time)

### Days Off Fields

- **`id`**: Days off record UUID
- **`starts_at`**: Start datetime (ISO 8601)
- **`ends_at`**: End datetime (ISO 8601)
- **`user`**: User reference object
- **`leave_type`**: Leave type reference object
- **`status`**: Approval status (approved, not_approved, pending)

## Usage Examples

### Basic Operations

```php
// Get all users
$users = $teamleader->users()->list();

// Get current user
$me = $teamleader->users()->me();

// Get specific user with external rate
$user = $teamleader->users()->withExternalRate()->info('user-uuid');

// Search for users
$salesTeam = $teamleader->users()->search('Sales');
```

### Complex Queries

```php
// Get active users sorted by name with external rates
$users = $teamleader->users()
    ->withExternalRate()
    ->list(['status' => ['active']], [
        'sort' => [['field' => 'first_name', 'order' => 'asc']],
        'page_size' => 25
    ]);

// Get user schedule and days off
$userId = 'user-uuid-here';
$user = $teamleader->users()->info($userId);
$schedule = $teamleader->users()->getWeekSchedule($userId);
$daysOff = $teamleader->users()->listDaysOff($userId, [
    'starts_after' => date('Y-m-01'), // This month
    'ends_before' => date('Y-m-t')    // End of this month
]);
```

### Pagination Handling

```php
$page = 1;
$allUsers = [];

do {
    $response = $teamleader->users()->list([], [
        'page_size' => 50,
        'page_number' => $page
    ]);
    
    if (isset($response['data'])) {
        $allUsers = array_merge($allUsers, $response['data']);
        $hasMore = count($response['data']) === 50;
        $page++;
    } else {
        $hasMore = false;
    }
} while ($hasMore);
```

## Error Handling

```php
$result = $teamleader->users()->list();

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Users API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}

// Handle schedule feature availability
$schedule = $teamleader->users()->getWeekSchedule($userId);
if (isset($schedule['error'])) {
    // Weekly working schedule feature might not be enabled
    Log::info('Week schedule not available for user', ['user_id' => $userId]);
}
```

## Rate Limiting

Users API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **Me operation**: 1 request per call
- **Schedule operations**: 1 request per call
- **Days off operations**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Users are **read-only** in the Teamleader API
- No create, update, or delete operations are supported
- The `getWeekSchedule()` method requires the "Weekly working schedule" feature to be enabled
- External rates are only included when explicitly requested via includes
- Time zones in responses are user-specific
- Phone numbers can have different types: phone, mobile, fax
- Language codes follow standard locale formats (e.g., "nl-BE")
- Days off status can be: approved, not_approved, pending
- Week schedule includes both working hours and lunch breaks

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class UserController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $users = $teamleader->users()->active();
        return view('users.index', compact('users'));
    }
    
    public function show(TeamleaderSDK $teamleader, string $id)
    {
        $user = $teamleader->users()->withExternalRate()->info($id);
        $schedule = $teamleader->users()->getWeekSchedule($id);
        $daysOff = $teamleader->users()->listDaysOff($id, [
            'starts_after' => now()->startOfMonth()->format('Y-m-d'),
            'ends_before' => now()->endOfMonth()->format('Y-m-d')
        ]);
        
        return view('users.show', compact('user', 'schedule', 'daysOff'));
    }
    
    public function me(TeamleaderSDK $teamleader)
    {
        $currentUser = $teamleader->users()->me();
        return response()->json($currentUser);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

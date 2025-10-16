# Users

Manage users in Teamleader Focus.

## Overview

The Users resource provides read-only access to user information in your Teamleader account. This resource is primarily used for retrieving user details, checking user status, and accessing work schedules.

**Important:** The Users resource is read-only. You cannot create, update, or delete users through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [me()](#me)
    - [getWeekSchedule()](#getweekschedule)
    - [listDaysOff()](#listdaysoff)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sorting](#sorting)
- [Sideloading](#sideloading)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`users`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ✅ Supported (external_rate)
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of users with optional filtering and sorting.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all users
$users = Teamleader::users()->list();

// With filters
$activeUsers = Teamleader::users()->list([
    'status' => ['active']
]);

// With pagination and sorting
$users = Teamleader::users()->list(
    ['status' => ['active']],
    [
        'page_size' => 50,
        'page_number' => 1,
        'sort' => [
            ['field' => 'first_name', 'order' => 'asc']
        ]
    ]
);
```

### `info()`

Get detailed information about a specific user.

**Parameters:**
- `id` (string): User UUID
- `includes` (string|array): Optional includes (external_rate)

**Example:**
```php
// Get user information
$user = Teamleader::users()->info('user-uuid');

// Get user with external rate
$user = Teamleader::users()->info('user-uuid', 'external_rate');

// Using fluent interface
$user = Teamleader::users()
    ->withExternalRate()
    ->info('user-uuid');
```

### `me()`

Get information about the currently authenticated user.

**Parameters:** None

**Example:**
```php
// Get current user
$currentUser = Teamleader::users()->me();

echo "Hello, " . $currentUser['data']['first_name'];
```

### `getWeekSchedule()`

Get the weekly work schedule for a specific user. This method is only available if the "Weekly working schedule" feature is enabled in your Teamleader account.

**Parameters:**
- `id` (string): User UUID

**Example:**
```php
// Get user's weekly schedule
$schedule = Teamleader::users()->getWeekSchedule('user-uuid');

// Access schedule details
foreach ($schedule['data'] as $day) {
    echo $day['day_of_week'] . ": " . $day['hours'] . " hours\n";
}
```

### `listDaysOff()`

Get a list of days off for a specific user.

**Parameters:**
- `id` (string): User UUID
- `filters` (array): Filter options
    - `starts_after` (string): ISO 8601 date - Get days off starting after this date
    - `ends_before` (string): ISO 8601 date - Get days off ending before this date
- `options` (array): Pagination options

**Example:**
```php
// Get all days off for a user
$daysOff = Teamleader::users()->listDaysOff('user-uuid');

// Get days off in a date range
$daysOff = Teamleader::users()->listDaysOff('user-uuid', [
    'starts_after' => '2024-01-01',
    'ends_before' => '2024-12-31'
]);

// With pagination
$daysOff = Teamleader::users()->listDaysOff(
    'user-uuid',
    ['starts_after' => '2024-01-01'],
    ['page_size' => 100]
);
```

## Helper Methods

The Users resource provides convenient helper methods for common operations:

### `active()`

Get only active users.

```php
$activeUsers = Teamleader::users()->active();
```

### `deactivated()`

Get only deactivated users.

```php
$deactivatedUsers = Teamleader::users()->deactivated();
```

### `search()`

Search users by term (searches first name, last name, email, and function).

```php
// Search for users
$users = Teamleader::users()->search('John');

// Will match:
// - First name: John
// - Last name: Johnson
// - Email: john@example.com
// - Function: Marketing Manager John
```

### `byIds()`

Get specific users by their UUIDs.

```php
$users = Teamleader::users()->byIds([
    'user-uuid-1',
    'user-uuid-2',
    'user-uuid-3'
]);
```

### `withExternalRate()`

Include external hourly rate information in the response.

```php
$user = Teamleader::users()
    ->withExternalRate()
    ->info('user-uuid');

// Access the rate
$hourlyRate = $user['data']['external_rate']['amount'];
$currency = $user['data']['external_rate']['currency'];
```

## Filters

### Available Filters

#### `ids`
Filter by specific user UUIDs.

```php
$users = Teamleader::users()->list([
    'ids' => ['user-uuid-1', 'user-uuid-2']
]);
```

#### `term`
Search across first name, last name, email, and function.

```php
$users = Teamleader::users()->list([
    'term' => 'john'
]);
```

#### `status`
Filter by user status. Must be an array.

**Values:** `active`, `deactivated`

```php
// Active users only
$users = Teamleader::users()->list([
    'status' => ['active']
]);

// Deactivated users only
$users = Teamleader::users()->list([
    'status' => ['deactivated']
]);

// Both active and deactivated
$users = Teamleader::users()->list([
    'status' => ['active', 'deactivated']
]);
```

## Sorting

### Available Sort Fields

- `first_name` - Sort by first name
- `last_name` - Sort by last name
- `email` - Sort by email address
- `function` - Sort by user function/role

### Sort Examples

```php
// Sort by first name (ascending)
$users = Teamleader::users()->list([], [
    'sort' => [['field' => 'first_name', 'order' => 'asc']]
]);

// Sort by last name (descending)
$users = Teamleader::users()->list([], [
    'sort' => [['field' => 'last_name', 'order' => 'desc']]
]);

// Multiple sort fields
$users = Teamleader::users()->list([], [
    'sort' => [
        ['field' => 'first_name', 'order' => 'asc'],
        ['field' => 'last_name', 'order' => 'asc']
    ]
]);

// Get available sort fields
$sortFields = Teamleader::users()->getAvailableSortFields();
```

## Sideloading

### Available Includes

- `external_rate` - Include external hourly rate information for the user

### Examples

```php
// Single user with external rate
$user = Teamleader::users()
    ->withExternalRate()
    ->info('user-uuid');

// List users with external rate
$users = Teamleader::users()
    ->withExternalRate()
    ->list();

// Using the include parameter directly
$user = Teamleader::users()->info('user-uuid', 'external_rate');
```

## Response Structure

### User Object

```php
[
    'id' => 'user-uuid',
    'account' => ['type' => 'account', 'id' => 'account-uuid'],
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'function' => 'Sales Manager',
    'language' => 'en',
    'telephones' => [
        ['type' => 'phone', 'number' => '+32 123 456 789']
    ],
    'status' => 'active',
    'time_zone' => 'Europe/Brussels',
    'avatar_url' => 'https://...',
    // If withExternalRate() is used:
    'external_rate' => [
        'amount' => 100.00,
        'currency' => 'EUR'
    ]
]
```

## Usage Examples

### Get All Active Users

```php
// Using helper method
$activeUsers = Teamleader::users()->active();

// Or with list()
$activeUsers = Teamleader::users()->list([
    'status' => ['active']
]);
```

### Build a User Dropdown

```php
$users = Teamleader::users()->active();

$dropdown = [];
foreach ($users['data'] as $user) {
    $dropdown[$user['id']] = $user['first_name'] . ' ' . $user['last_name'];
}
```

### Find Users by Department

```php
// Note: Direct department filtering isn't available
// You'll need to filter in PHP after fetching users
$allUsers = Teamleader::users()->active();

$departmentUsers = array_filter($allUsers['data'], function($user) use ($departmentId) {
    return isset($user['department']['id']) && 
           $user['department']['id'] === $departmentId;
});
```

### Get User Details with Rate Information

```php
$user = Teamleader::users()
    ->withExternalRate()
    ->info('user-uuid');

if (isset($user['data']['external_rate'])) {
    $rate = $user['data']['external_rate']['amount'];
    $currency = $user['data']['external_rate']['currency'];
    echo "Hourly rate: {$rate} {$currency}";
}
```

### Check User Availability

```php
$userId = 'user-uuid';

// Get their schedule
$schedule = Teamleader::users()->getWeekSchedule($userId);

// Get their days off for the next month
$daysOff = Teamleader::users()->listDaysOff($userId, [
    'starts_after' => now()->toIso8601String(),
    'ends_before' => now()->addMonth()->toIso8601String()
]);

// Process availability
$workingDays = $schedule['data'];
$offDays = $daysOff['data'];
```

### Search for Team Members

```php
// Search by name
$results = Teamleader::users()->search('John');

// Search by email
$results = Teamleader::users()->search('john@example.com');

// Search by function
$results = Teamleader::users()->search('Manager');
```

### Get Specific Users for Assignment

```php
// Get specific users who can be assigned to a task
$assignableUsers = Teamleader::users()->byIds([
    'user-uuid-1',
    'user-uuid-2',
    'user-uuid-3'
]);
```

### Get Current User Information

```php
// Get authenticated user
$currentUser = Teamleader::users()->me();

// Use for personalization
$greeting = "Welcome back, " . $currentUser['data']['first_name'];

// Check permissions or role
$userFunction = $currentUser['data']['function'];
```

### Paginate Through All Users

```php
$allUsers = [];
$page = 1;
$pageSize = 100;

do {
    $response = Teamleader::users()->list([], [
        'page_size' => $pageSize,
        'page_number' => $page
    ]);
    
    $allUsers = array_merge($allUsers, $response['data']);
    $hasMore = count($response['data']) === $pageSize;
    $page++;
    
} while ($hasMore);
```

## Common Use Cases

### User Selection in Forms

```php
class TaskController extends Controller
{
    public function create()
    {
        $users = Teamleader::users()
            ->active()
            ->list([], [
                'sort' => [['field' => 'first_name', 'order' => 'asc']]
            ]);
        
        return view('tasks.create', [
            'users' => $users['data']
        ]);
    }
}
```

### Caching User List

```php
use Illuminate\Support\Facades\Cache;

class UserService
{
    public function getActiveUsers()
    {
        return Cache::remember('active_users', 3600, function() {
            return Teamleader::users()->active();
        });
    }
    
    public function getUserById($userId)
    {
        $cacheKey = "user.{$userId}";
        
        return Cache::remember($cacheKey, 3600, function() use ($userId) {
            return Teamleader::users()->info($userId);
        });
    }
}
```

### Sync Users to Local Database

```php
use App\Models\TeamleaderUser;
use Illuminate\Console\Command;

class SyncUsersCommand extends Command
{
    protected $signature = 'teamleader:sync-users';
    
    public function handle()
    {
        $page = 1;
        
        do {
            $response = Teamleader::users()->list([], [
                'page_size' => 100,
                'page_number' => $page
            ]);
            
            foreach ($response['data'] as $userData) {
                TeamleaderUser::updateOrCreate(
                    ['teamleader_id' => $userData['id']],
                    [
                        'first_name' => $userData['first_name'],
                        'last_name' => $userData['last_name'],
                        'email' => $userData['email'],
                        'function' => $userData['function'],
                        'status' => $userData['status'],
                    ]
                );
            }
            
            $hasMore = count($response['data']) === 100;
            $page++;
            
        } while ($hasMore);
        
        $this->info('Users synced successfully!');
    }
}
```

### User Availability Dashboard

```php
class AvailabilityController extends Controller
{
    public function show($userId)
    {
        // Get user info
        $user = Teamleader::users()->info($userId);
        
        // Get schedule
        $schedule = Teamleader::users()->getWeekSchedule($userId);
        
        // Get upcoming days off
        $daysOff = Teamleader::users()->listDaysOff($userId, [
            'starts_after' => now()->toIso8601String()
        ]);
        
        return view('availability.show', [
            'user' => $user['data'],
            'schedule' => $schedule['data'] ?? null,
            'daysOff' => $daysOff['data']
        ]);
    }
}
```

## Best Practices

### 1. Cache User Data

User information doesn't change frequently, so cache it:

```php
// Good: Cache for 1 hour
$users = Cache::remember('active_users', 3600, function() {
    return Teamleader::users()->active();
});

// Bad: Fetching on every request
$users = Teamleader::users()->active();
```

### 2. Filter at the API Level

```php
// Good: Filter on the API
$activeUsers = Teamleader::users()->active();

// Bad: Fetch all and filter in PHP
$allUsers = Teamleader::users()->list();
$activeUsers = array_filter($allUsers['data'], function($user) {
    return $user['status'] === 'active';
});
```

### 3. Use Helper Methods

```php
// Good: Clear and readable
$results = Teamleader::users()->search('John');

// Less ideal: Using list with filters
$results = Teamleader::users()->list(['term' => 'John']);
```

### 4. Handle Missing Data Gracefully

```php
$user = Teamleader::users()->info($userId);

// Good: Check before accessing
$email = $user['data']['email'] ?? 'No email';
$function = $user['data']['function'] ?? 'No function specified';

// Bad: Assuming data exists
$email = $user['data']['email']; // May throw undefined index error
```

### 5. Use Current User for Context

```php
// Get current user for audit logs or default assignments
$currentUser = Teamleader::users()->me();

$task = Task::create([
    'title' => 'New Task',
    'assigned_to' => $currentUser['data']['id'],
    'created_by' => $currentUser['data']['id']
]);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $user = Teamleader::users()->info($userId);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        // User not found
        return response()->json(['error' => 'User not found'], 404);
    }
    
    // Other error
    Log::error('Error fetching user', [
        'user_id' => $userId,
        'error' => $e->getMessage()
    ]);
    
    throw $e;
}
```

## Checking Available Features

Not all Teamleader accounts have the same features enabled. Check before using:

```php
try {
    $schedule = Teamleader::users()->getWeekSchedule($userId);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 403 || $e->getCode() === 404) {
        // Weekly schedule feature not available
        Log::info('Weekly schedule feature not available for this account');
        $schedule = null;
    }
}
```

## Related Resources

- [Departments](departments.md) - Get department information for users
- [Teams](teams.md) - Get team information
- [Days Off](../general/days_off.md) - Manage user days off
- [Time Tracking](../timetracking/time_tracking.md) - Track user time

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
- [Sideloading](../sideloading.md) - Efficiently load related data

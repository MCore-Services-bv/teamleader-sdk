# Users

Read user information in Teamleader Focus.

## Overview

The Users resource provides read-only access to users in your Teamleader account. Beyond the standard `list()`
and `info()` methods, it exposes `me()` for the currently authenticated user, `getWeekSchedule()` for working hour
schedules, and `listDaysOff()` for leave records.

Users cannot be created, updated, or deleted through the API.

## Endpoint

`users`

## Capabilities

| Capability  | Supported                          |
|-------------|------------------------------------|
| Pagination  | ✅ Supported                        |
| Filtering   | ✅ Supported                        |
| Sorting     | ✅ Supported                        |
| Sideloading | ✅ Supported (`external_rate` only) |
| Creation    | ❌ Not supported                    |
| Update      | ❌ Not supported                    |
| Deletion    | ❌ Not supported                    |

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns a paginated list of users with optional filtering and sorting.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All users
$users = Teamleader::users()->list();

// Active users only
$users = Teamleader::users()->list([
    'status' => ['active'],
]);

// With pagination and sorting
$users = Teamleader::users()->list(
    ['status' => ['active']],
    [
        'page_size'   => 50,
        'page_number' => 1,
        'sort'        => [['field' => 'last_name', 'order' => 'asc']],
    ]
);
```

---

### `info(string $id, string|array|null $includes = null)`

Returns a single user by UUID. Optionally includes `external_rate`.

```php
// Basic info
$user = Teamleader::users()->info('user-uuid');

// With external rate — string form
$user = Teamleader::users()->info('user-uuid', 'external_rate');

// With external rate — fluent form
$user = Teamleader::users()
    ->withExternalRate()
    ->info('user-uuid');
```

---

### `me()`

Returns the currently authenticated user. Takes no parameters.

```php
$me = Teamleader::users()->me();

$firstName = $me['data']['first_name'];
$email     = $me['data']['email'];
```

---

### `getWeekSchedule(string $id)`

Returns the weekly working schedule for a user. Only available if the **Weekly working schedule** feature is enabled on
the Teamleader account.

```php
$schedule = Teamleader::users()->getWeekSchedule('user-uuid');
```

---

### `listDaysOff(string $id, array $filters = [], array $options = [])`

Returns leave records for a user, with optional date range filters and pagination.

**Filter keys:**

- `starts_after` — include days off starting after this value
- `ends_before` — include days off ending before this value

```php
// All days off for a user
$daysOff = Teamleader::users()->listDaysOff('user-uuid');

// Filtered by date range
$daysOff = Teamleader::users()->listDaysOff('user-uuid', [
    'starts_after' => '2025-01-01',
    'ends_before'  => '2025-12-31',
]);

// With pagination
$daysOff = Teamleader::users()->listDaysOff(
    'user-uuid',
    ['starts_after' => '2025-01-01'],
    ['page_size' => 100, 'page_number' => 1]
);
```

---

## Helper Methods

### `active()`

Shorthand for `list(['status' => ['active']])`.

```php
$users = Teamleader::users()->active();
```

### `deactivated()`

Shorthand for `list(['status' => ['deactivated']])`.

```php
$users = Teamleader::users()->deactivated();
```

### `search(string $term)`

Shorthand for `list(['term' => $term])`. Searches across first name, last name, email, and function.

```php
$users = Teamleader::users()->search('Sarah');
$users = Teamleader::users()->search('sarah@example.com');
```

### `byIds(array $ids)`

Shorthand for `list(['ids' => $ids])`.

```php
$users = Teamleader::users()->byIds(['uuid-1', 'uuid-2']);
```

### `withExternalRate()`

Fluent method to include `external_rate` in the next `info()` call.

```php
$user = Teamleader::users()
    ->withExternalRate()
    ->info('user-uuid');

$amount   = $user['data']['external_rate']['amount'];
$currency = $user['data']['external_rate']['currency'];
```

---

## Filters

### `ids`

Filter by an array of user UUIDs.

```php
$users = Teamleader::users()->list([
    'ids' => ['uuid-1', 'uuid-2'],
]);
```

### `term`

Search across first name, last name, email, and function.

```php
$users = Teamleader::users()->list([
    'term' => 'developer',
]);
```

### `status`

Filter by status. Must be passed as an **array**.

| Value         | Description       |
|---------------|-------------------|
| `active`      | Active users      |
| `deactivated` | Deactivated users |

```php
$users = Teamleader::users()->list([
    'status' => ['active'],
]);

// Both statuses
$users = Teamleader::users()->list([
    'status' => ['active', 'deactivated'],
]);
```

---

## Sorting

Pass a sort array in the options argument. Available sort fields:

| Field        | Description                 |
|--------------|-----------------------------|
| `first_name` | Sort by first name          |
| `last_name`  | Sort by last name           |
| `email`      | Sort by email address       |
| `function`   | Sort by job function / role |

```php
$users = Teamleader::users()->list([], [
    'sort' => [['field' => 'last_name', 'order' => 'asc']],
]);
```

String shorthand is also accepted and normalised to the array format:

```php
$users = Teamleader::users()->list([], [
    'sort' => 'last_name',
]);
```

---

## Sideloading

The only available include is `external_rate`.

| Include         | Description                     |
|-----------------|---------------------------------|
| `external_rate` | The user's external hourly rate |

See [[Sideloading]] for general sideloading patterns.

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'         => 'user-uuid',
            'first_name' => 'Sarah',
            'last_name'  => 'De Smedt',
            'email'      => 'sarah@example.com',
            'function'   => 'Developer',
            'status'     => 'active',   // 'active' or 'deactivated'
            'avatar_url' => 'https://...',
        ],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 14,
    ],
]
```

### `info()` response

```php
[
    'data' => [
        'id'            => 'user-uuid',
        'first_name'    => 'Sarah',
        'last_name'     => 'De Smedt',
        'email'         => 'sarah@example.com',
        'function'      => 'Developer',
        'status'        => 'active',
        'avatar_url'    => 'https://...',
        // Only present when external_rate is included:
        'external_rate' => [
            'amount'   => 95.00,
            'currency' => 'EUR',
        ],
    ],
]
```

### `me()` response

Same structure as `info()`.

### `listDaysOff()` response

```php
[
    'data' => [
        [
            'starts_at' => '2025-07-14T08:00:00+02:00',
            'ends_at'   => '2025-07-14T17:00:00+02:00',
        ],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 3,
    ],
]
```

---

## Usage Examples

### Build a user select list

```php
$users = Teamleader::users()->active();

$options = [];
foreach ($users['data'] as $user) {
    $options[$user['id']] = $user['first_name'] . ' ' . $user['last_name'];
}
// ['uuid-1' => 'Sarah De Smedt', 'uuid-2' => 'Jan Peeters', ...]
```

### Get the current user's ID

```php
$me = Teamleader::users()->me();
$myId = $me['data']['id'];
```

### Paginate through all active users

```php
$all      = [];
$page     = 1;
$pageSize = 100;

do {
    $response = Teamleader::users()->list(
        ['status' => ['active']],
        ['page_size' => $pageSize, 'page_number' => $page]
    );

    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === $pageSize);
```

### Cache users

Users change infrequently. Cache them to avoid unnecessary API calls:

```php
$users = Cache::remember('tl_active_users', 3600, function () {
    return Teamleader::users()->active();
});
```

### Get a user's upcoming days off

```php
$daysOff = Teamleader::users()->listDaysOff('user-uuid', [
    'starts_after' => now()->toDateString(),
    'ends_before'  => now()->addMonths(3)->toDateString(),
]);
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $user = Teamleader::users()->info('user-uuid');
} catch (NotFoundException $e) {
    // User does not exist or UUID is wrong
    Log::warning('User not found', ['id' => 'user-uuid']);
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Departments]] — Departments users belong to
- [[Teams]] — Teams users are members of
- [[Deals]] — Deals assigned to users
- [[Time-Tracking]] — Time entries logged by users
- [[Filtering]] — Filter and sort reference
- [[Sideloading]] — Loading related data

# User Availability

Retrieve daily and total availability information for users and teams in Teamleader Focus.

## Overview

The User Availability resource provides read-only access to availability data. It exposes two endpoints:

- **`daily()`** — returns a per-date availability breakdown for each user, useful for scheduling and capacity planning within short windows (up to 100 days).
- **`total()`** — returns a single aggregated availability figure per user across the full period, useful for reporting and longer-term planning (up to 20,000 days).

Both endpoints return the same four availability metrics per user: gross time, net time, planned time, and unplanned time — all in minutes.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [daily()](#daily)
    - [total()](#total)
- [Convenience Methods](#convenience-methods)
- [Availability Metrics Explained](#availability-metrics-explained)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`userAvailability`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported (by assignees)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

---

## Available Methods

### `daily()`

Returns daily availability for all users (or filtered assignees) broken down by date.

**Maximum period: 100 days.**

**Parameters:**
- `params` (array, required):
    - `period` (array, required):
        - `start_date` (string, required): Start date in `YYYY-MM-DD` format
        - `end_date` (string, required): End date in `YYYY-MM-DD` format
    - `filter` (array, optional):
        - `assignees` (array, optional): Array of assignee objects
            - `type` (string, required): `user` or `team`
            - `id` (string, required): UUID of the user or team
    - `page` (array, optional):
        - `size` (int): Results per page (default: 20)
        - `number` (int): Page number (default: 1)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All users, one week
$availability = Teamleader::userAvailability()->daily([
    'period' => [
        'start_date' => '2024-01-01',
        'end_date'   => '2024-01-07',
    ],
]);

// Specific user
$availability = Teamleader::userAvailability()->daily([
    'period' => [
        'start_date' => '2024-01-01',
        'end_date'   => '2024-01-07',
    ],
    'filter' => [
        'assignees' => [
            ['type' => 'user', 'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'],
        ],
    ],
]);
```

---

### `total()`

Returns total (aggregated) availability per user across the full period.

**Maximum period: 20,000 days.**

**Parameters:** Same structure as `daily()`.

**Example:**
```php
// All users, full year
$availability = Teamleader::userAvailability()->total([
    'period' => [
        'start_date' => '2024-01-01',
        'end_date'   => '2024-12-31',
    ],
]);

// Filter by team
$availability = Teamleader::userAvailability()->total([
    'period' => [
        'start_date' => '2024-01-01',
        'end_date'   => '2024-12-31',
    ],
    'filter' => [
        'assignees' => [
            ['type' => 'team', 'id' => 'team-uuid'],
        ],
    ],
]);
```

---

## Convenience Methods

### `dailyForUser()`

```php
$availability = Teamleader::userAvailability()->dailyForUser(
    'user-uuid',
    '2024-01-01',
    '2024-01-07'
);
```

### `totalForUser()`

```php
$availability = Teamleader::userAvailability()->totalForUser(
    'user-uuid',
    '2024-01-01',
    '2024-12-31'
);
```

### `dailyForTeam()`

```php
$availability = Teamleader::userAvailability()->dailyForTeam(
    'team-uuid',
    '2024-01-01',
    '2024-01-07'
);
```

### `totalForTeam()`

```php
$availability = Teamleader::userAvailability()->totalForTeam(
    'team-uuid',
    '2024-01-01',
    '2024-12-31'
);
```

---

## Availability Metrics Explained

Each availability object contains four time values, all in **minutes**:

| Field | Description |
|-------|-------------|
| `gross_time_available` | Total working time based on the user's working hours schedule |
| `net_time_available` | Gross time minus approved days off |
| `planned_time` | Time already reserved via planning reservations |
| `unplanned_time` | Net time minus planned time — the remaining available capacity |

---

## Response Structure

### `daily()` Response

```json
{
    "data": [
        {
            "user": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "user"
            },
            "availabilities": [
                {
                    "date": "2024-01-01",
                    "availability": {
                        "gross_time_available": { "unit": "minutes", "value": 480 },
                        "net_time_available":   { "unit": "minutes", "value": 480 },
                        "planned_time":         { "unit": "minutes", "value": 120 },
                        "unplanned_time":       { "unit": "minutes", "value": 360 }
                    }
                },
                {
                    "date": "2024-01-02",
                    "availability": {
                        "gross_time_available": { "unit": "minutes", "value": 480 },
                        "net_time_available":   { "unit": "minutes", "value": 0 },
                        "planned_time":         { "unit": "minutes", "value": 0 },
                        "unplanned_time":       { "unit": "minutes", "value": 0 }
                    }
                }
            ]
        }
    ]
}
```

### `total()` Response

```json
{
    "data": [
        {
            "user": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "user"
            },
            "availability": {
                "gross_time_available": { "unit": "minutes", "value": 10080 },
                "net_time_available":   { "unit": "minutes", "value": 9600 },
                "planned_time":         { "unit": "minutes", "value": 2400 },
                "unplanned_time":       { "unit": "minutes", "value": 7200 }
            }
        }
    ]
}
```

---

## Usage Examples

### Check remaining capacity before creating a reservation

```php
$availability = Teamleader::userAvailability()->dailyForUser(
    $userId,
    $targetDate,
    $targetDate
);

$day = $availability['data'][0]['availabilities'][0] ?? null;

if ($day && $day['availability']['unplanned_time']['value'] >= 60) {
    // Enough capacity — create the reservation
    Teamleader::reservations()->create([
        'plannable_item_id' => $itemId,
        'date'              => $targetDate,
        'duration'          => ['value' => 60, 'unit' => 'minutes'],
        'assignee'          => ['type' => 'user', 'id' => $userId],
    ]);
}
```

### Build a weekly capacity overview for a team

```php
$weekly = Teamleader::userAvailability()->daily([
    'period' => [
        'start_date' => now()->startOfWeek()->format('Y-m-d'),
        'end_date'   => now()->endOfWeek()->format('Y-m-d'),
    ],
    'filter' => [
        'assignees' => [
            ['type' => 'team', 'id' => 'team-uuid'],
        ],
    ],
]);

foreach ($weekly['data'] as $userRow) {
    $userId = $userRow['user']['id'];
    foreach ($userRow['availabilities'] as $day) {
        echo "{$userId} on {$day['date']}: "
            . $day['availability']['unplanned_time']['value']
            . " minutes available\n";
    }
}
```

### Get full-year utilisation per user

```php
$yearly = Teamleader::userAvailability()->total([
    'period' => [
        'start_date' => '2024-01-01',
        'end_date'   => '2024-12-31',
    ],
]);

foreach ($yearly['data'] as $userRow) {
    $net     = $userRow['availability']['net_time_available']['value'];
    $planned = $userRow['availability']['planned_time']['value'];
    $pct     = $net > 0 ? round(($planned / $net) * 100) : 0;
    echo "User {$userRow['user']['id']}: {$pct}% utilisation\n";
}
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $availability = Teamleader::userAvailability()->daily([
        'period' => [
            'start_date' => '2024-01-01',
            'end_date'   => '2024-06-30', // > 100 days — will throw
        ],
    ]);
} catch (InvalidArgumentException $e) {
    // Period too long, invalid dates, or bad assignee structure
    Log::error('Validation error: '.$e->getMessage());
} catch (TeamleaderException $e) {
    Log::error('API error: '.$e->getMessage());
}
```

---

## Related Resources

- **[Reservations](reservations.md)** — Create reservations once you know availability
- **[Plannable Items](plannable-items.md)** — Items that can be planned
- **[Users](../general/users.md)** — User reference data
- **[Days Off](../general/days-off.md)** — Affects net_time_available

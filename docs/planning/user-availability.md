# User Availability

Retrieve availability data for users and teams in Teamleader Focus.

## Overview

User Availability exposes two read-only endpoints: a daily breakdown and an aggregated total. Both return the same four
capacity metrics per user — all in minutes — so you can understand how much unplanned time exists before
creating [[Reservations]].

Access via `Teamleader::userAvailability()`.

> **`list()` throws `BadMethodCallException`** — use `daily()` or `total()` instead.
>
> **`daily()` max period: 100 days.** `total()` max period: 20,000 days.
>
> **All params go into a single `$params` array** — including `period`, optional `filter`, and optional `page`. This
> differs from the `(filters, options)` pattern used by most resources.
>
> **The optional assignee filter nests inside `filter.assignees`**, not at the top level.
>
> **All duration values are returned in minutes.**

## Endpoint

`userAvailability`

## Capabilities

| Capability  | Supported                               |
|-------------|-----------------------------------------|
| Pagination  | ✅ Supported (inside `page` key)         |
| Filtering   | ✅ Supported (inside `filter.assignees`) |
| Sorting     | ❌ Not supported                         |
| Sideloading | ❌ Not supported                         |
| Creation    | ❌ Not supported                         |
| Update      | ❌ Not supported                         |
| Deletion    | ❌ Not supported                         |

---

## Methods

### `daily(array $params)`

Returns a per-date availability breakdown for each user. Maximum period: **100 days**.

**Parameter structure:**

```
$params = [
    'period' => [                       // required
        'start_date' => 'YYYY-MM-DD',   // required
        'end_date'   => 'YYYY-MM-DD',   // required
    ],
    'filter' => [                       // optional
        'assignees' => [                // optional
            ['type' => 'user|team', 'id' => 'uuid'],
        ],
    ],
    'page' => [                         // optional
        'size'   => 20,
        'number' => 1,
    ],
]
```

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All users, one week
$availability = Teamleader::userAvailability()->daily([
    'period' => ['start_date' => '2025-05-12', 'end_date' => '2025-05-16'],
]);

// Specific user
$availability = Teamleader::userAvailability()->daily([
    'period' => ['start_date' => '2025-05-12', 'end_date' => '2025-05-16'],
    'filter' => ['assignees' => [['type' => 'user', 'id' => 'user-uuid']]],
]);

// Specific team, with pagination
$availability = Teamleader::userAvailability()->daily([
    'period' => ['start_date' => '2025-05-01', 'end_date' => '2025-05-31'],
    'filter' => ['assignees' => [['type' => 'team', 'id' => 'team-uuid']]],
    'page'   => ['size' => 50, 'number' => 1],
]);
```

---

### `total(array $params)`

Returns a single aggregated availability figure per user across the full period. Maximum period: **20,000 days**.

Same parameter structure as `daily()`.

```php
// All users, full year
$availability = Teamleader::userAvailability()->total([
    'period' => ['start_date' => '2025-01-01', 'end_date' => '2025-12-31'],
]);

// Specific team, multi-year
$availability = Teamleader::userAvailability()->total([
    'period' => ['start_date' => '2025-01-01', 'end_date' => '2026-12-31'],
    'filter' => ['assignees' => [['type' => 'team', 'id' => 'team-uuid']]],
]);
```

---

## Helper Methods

All convenience methods call `daily()` or `total()` internally and build the filter for you.

| Method                                                     | Calls                      |
|------------------------------------------------------------|----------------------------|
| `dailyForUser(string $userId, string $start, string $end)` | `daily()` filtered by user |
| `totalForUser(string $userId, string $start, string $end)` | `total()` filtered by user |
| `dailyForTeam(string $teamId, string $start, string $end)` | `daily()` filtered by team |
| `totalForTeam(string $teamId, string $start, string $end)` | `total()` filtered by team |

```php
$daily = Teamleader::userAvailability()->dailyForUser('user-uuid', '2025-05-12', '2025-05-16');
$total = Teamleader::userAvailability()->totalForUser('user-uuid', '2025-01-01', '2025-12-31');
$daily = Teamleader::userAvailability()->dailyForTeam('team-uuid', '2025-05-12', '2025-05-16');
$total = Teamleader::userAvailability()->totalForTeam('team-uuid', '2025-01-01', '2025-12-31');
```

---

## Availability Metrics

All four values are returned in **minutes**:

| Field                  | Description                                                         |
|------------------------|---------------------------------------------------------------------|
| `gross_time_available` | Total working time based on the user's configured schedule          |
| `net_time_available`   | Gross time minus approved days off                                  |
| `planned_time`         | Time already consumed by reservations                               |
| `unplanned_time`       | `net_time_available` minus `planned_time` — remaining free capacity |

---

## Response Structure

### `daily()` — per-date breakdown

```php
[
    'data' => [
        [
            'user'           => ['type' => 'user', 'id' => 'user-uuid'],
            'availabilities' => [
                [
                    'date'         => '2025-05-12',
                    'availability' => [
                        'gross_time_available' => ['unit' => 'minutes', 'value' => 480],
                        'net_time_available'   => ['unit' => 'minutes', 'value' => 480],
                        'planned_time'         => ['unit' => 'minutes', 'value' => 120],
                        'unplanned_time'       => ['unit' => 'minutes', 'value' => 360],
                    ],
                ],
                // one entry per date in the period
            ],
        ],
        // one entry per user
    ],
]
```

### `total()` — aggregated per user

```php
[
    'data' => [
        [
            'user'         => ['type' => 'user', 'id' => 'user-uuid'],
            'availability' => [  // note: singular — not an array
                'gross_time_available' => ['unit' => 'minutes', 'value' => 105600],
                'net_time_available'   => ['unit' => 'minutes', 'value' => 99840],
                'planned_time'         => ['unit' => 'minutes', 'value' => 24000],
                'unplanned_time'       => ['unit' => 'minutes', 'value' => 75840],
            ],
        ],
    ],
]
```

> In `daily()` the availability data is inside `availabilities[]` (plural array per date). In `total()` it is directly
> on `availability` (singular object per user).

---

## Usage Examples

### Find free capacity before reserving

```php
$availability = Teamleader::userAvailability()->dailyForUser(
    'user-uuid', '2025-05-12', '2025-05-16'
);

foreach ($availability['data'][0]['availabilities'] as $day) {
    $free = $day['availability']['unplanned_time']['value'];
    echo "{$day['date']}: {$free} minutes free\n";
}
```

### Calculate utilisation for the year

```php
$yearly = Teamleader::userAvailability()->total([
    'period' => ['start_date' => '2025-01-01', 'end_date' => '2025-12-31'],
]);

foreach ($yearly['data'] as $row) {
    $net     = $row['availability']['net_time_available']['value'];
    $planned = $row['availability']['planned_time']['value'];
    $pct     = $net > 0 ? round(($planned / $net) * 100) : 0;
    echo "User {$row['user']['id']}: {$pct}% utilised\n";
}
```

---

## Error Handling

```php
use BadMethodCallException;
use InvalidArgumentException;

// list() not supported
try {
    Teamleader::userAvailability()->list();
} catch (BadMethodCallException $e) {
    // 'UserAvailability does not support list(). Use daily() or total() instead.'
}

// Period too long for daily()
try {
    Teamleader::userAvailability()->daily([
        'period' => ['start_date' => '2025-01-01', 'end_date' => '2026-12-31'], // > 100 days
    ]);
} catch (InvalidArgumentException $e) {
    // period validation error
}
```

---

## Related Resources

- [[Reservations]] — Create reservations using the capacity identified here
- [[Plannable-Items]] — Items that consume the planned capacity

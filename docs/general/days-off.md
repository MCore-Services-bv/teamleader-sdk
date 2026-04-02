# Days Off

Import and delete user leave records in Teamleader Focus.

## Overview

The Days Off resource handles bulk creation and deletion of leave records for individual users. It does not support any
standard CRUD operations — `list()`, `info()`, `create()`, `update()`, and `delete()` all
throw `BadMethodCallException`. All work goes through `bulkImport()` and `bulkDelete()`.

To **read** a user's leave records, use `Teamleader::users()->listDaysOff()`.

## Endpoint

`daysOff`

## Capabilities

| Capability  | Supported                         |
|-------------|-----------------------------------|
| Bulk import | ✅ `bulkImport()`                  |
| Bulk delete | ✅ `bulkDelete()`                  |
| list()      | ❌ Throws `BadMethodCallException` |
| info()      | ❌ Throws `BadMethodCallException` |
| create()    | ❌ Throws `BadMethodCallException` |
| update()    | ❌ Throws `BadMethodCallException` |
| delete()    | ❌ Throws `BadMethodCallException` |

---

## Methods

### `bulkImport(string $userId, string $leaveTypeId, array $days)`

Imports leave records for a user. Posts to `daysOff.import`.

All three parameters are validated before the request:

- `userId` — must be a valid UUID
- `leaveTypeId` — must be a valid UUID
- `days` — must be a non-empty array; each entry must have `starts_at` and `ends_at` as valid ISO 8601 datetimes with
  timezone (`YYYY-MM-DDTHH:MM:SS±HH:MM`); `starts_at` must be before `ends_at`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

Teamleader::daysOff()->bulkImport(
    'user-uuid',
    'leave-type-uuid',
    [
        [
            'starts_at' => '2025-12-23T08:00:00+01:00',
            'ends_at'   => '2025-12-23T18:00:00+01:00',
        ],
        [
            'starts_at' => '2025-12-24T08:00:00+01:00',
            'ends_at'   => '2025-12-24T18:00:00+01:00',
        ],
    ]
);
```

---

### `bulkDelete(string $userId, array $dayOffIds)`

Deletes leave records by UUID. Both `userId` and every entry in `dayOffIds` are validated as UUIDs before the request.

```php
Teamleader::daysOff()->bulkDelete(
    'user-uuid',
    ['day-off-uuid-1', 'day-off-uuid-2']
);
```

---

## Helper Methods

### `importSingleDay(string $userId, string $leaveTypeId, string $startsAt, string $endsAt)`

Convenience wrapper that calls `bulkImport()` with a single-entry array.

```php
Teamleader::daysOff()->importSingleDay(
    'user-uuid',
    'leave-type-uuid',
    '2025-12-25T08:00:00+01:00',
    '2025-12-25T18:00:00+01:00'
);
```

---

### `importMultipleDays(string $userId, string $leaveTypeId, array $dates, string $startTime, string $endTime, string $timezone)`

Builds ISO 8601 datetimes from simple date strings and a common time pattern, then calls `bulkImport()`. All dates
receive the same start/end time.

| Parameter    | Default      |
|--------------|--------------|
| `$startTime` | `'08:00:00'` |
| `$endTime`   | `'18:00:00'` |
| `$timezone`  | `'+00:00'`   |

```php
Teamleader::daysOff()->importMultipleDays(
    'user-uuid',
    'leave-type-uuid',
    ['2025-12-23', '2025-12-24', '2025-12-27'],
    '08:00:00',
    '18:00:00',
    '+01:00'
);
```

---

### `importDateRange(string $userId, string $leaveTypeId, string $startDate, string $endDate, string $startTime, string $endTime, string $timezone, bool $excludeWeekends)`

Generates a list of dates between `$startDate` and `$endDate`, optionally skipping weekends, then
calls `importMultipleDays()`. No extra API calls — date generation is local.

| Parameter          | Default      |
|--------------------|--------------|
| `$startTime`       | `'08:00:00'` |
| `$endTime`         | `'18:00:00'` |
| `$timezone`        | `'+00:00'`   |
| `$excludeWeekends` | `true`       |

```php
// Import a full working week
Teamleader::daysOff()->importDateRange(
    'user-uuid',
    'leave-type-uuid',
    '2025-07-14',
    '2025-07-18',
    '08:00:00',
    '18:00:00',
    '+02:00', // CEST
    true      // skip Saturday and Sunday
);
```

---

## Utility Methods

```php
// Format a PHP DateTime for the API
$datetime = new DateTime('2025-12-25 09:00:00', new DateTimeZone('Europe/Brussels'));
$iso = Teamleader::daysOff()->formatDatetime($datetime);
// '2025-12-25T09:00:00+01:00'

// Build an ISO 8601 string from parts
$iso = Teamleader::daysOff()->createDatetime('2025-12-25', '08:00:00', '+01:00');
// '2025-12-25T08:00:00+01:00'

// Reference common work-hour patterns and timezones
$helpers = Teamleader::daysOff()->getFormattingHelpers();
// ['common_timezones' => ['UTC' => '+00:00', 'CET' => '+01:00', ...],
//  'common_work_hours' => ['full_day' => ['08:00:00', '18:00:00'], ...]]
```

---

## Date Format

All `starts_at` / `ends_at` values must follow ISO 8601 with an explicit timezone offset:

```
YYYY-MM-DDTHH:MM:SS±HH:MM
```

```php
'2025-12-25T08:00:00+00:00'  // UTC
'2025-12-25T08:00:00+01:00'  // CET
'2025-12-25T08:00:00+02:00'  // CEST
```

The SDK validates this format with a regex and also checks that `starts_at` is strictly before `ends_at`.

---

## Reading Leave Records

This resource does not support `list()`. Use the `Users` resource instead:

```php
// Read leave records
$daysOff = Teamleader::users()->listDaysOff('user-uuid', [
    'starts_after' => '2025-01-01',
    'ends_before'  => '2025-12-31',
]);

// Extract IDs for deletion
$ids = array_column($daysOff['data'], 'id');
Teamleader::daysOff()->bulkDelete('user-uuid', $ids);
```

---

## Usage Examples

### Import a vacation week

```php
Teamleader::daysOff()->importDateRange(
    'user-uuid',
    config('teamleader.leave_types.vacation'),
    '2025-08-04',
    '2025-08-08',
    '00:00:00',
    '23:59:59',
    '+02:00',
    true // exclude weekends
);
```

### Import morning and afternoon half-days separately

```php
// Morning only
Teamleader::daysOff()->importSingleDay(
    'user-uuid', 'leave-type-uuid',
    '2025-09-05T08:00:00+02:00',
    '2025-09-05T12:00:00+02:00'
);

// Afternoon only
Teamleader::daysOff()->importSingleDay(
    'user-uuid', 'leave-type-uuid',
    '2025-09-05T13:00:00+02:00',
    '2025-09-05T18:00:00+02:00'
);
```

### Cancel upcoming leave

```php
$daysOff = Teamleader::users()->listDaysOff('user-uuid', [
    'starts_after' => date('Y-m-d'),
]);

$ids = array_column($daysOff['data'], 'id');

if (!empty($ids)) {
    Teamleader::daysOff()->bulkDelete('user-uuid', $ids);
}
```

---

## Error Handling

```php
use BadMethodCallException;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Standard methods throw immediately
try {
    Teamleader::daysOff()->list();
} catch (BadMethodCallException $e) {
    // Use Users::listDaysOff() instead
}

// Invalid UUID — thrown before the request
try {
    Teamleader::daysOff()->bulkImport('not-a-uuid', 'leave-type-uuid', [...]);
} catch (InvalidArgumentException $e) {
    // 'User ID must be a valid UUID format'
    Log::error($e->getMessage());
}

// Invalid datetime format — thrown before the request
try {
    Teamleader::daysOff()->bulkImport('user-uuid', 'leave-type-uuid', [
        ['starts_at' => '2025-12-25', 'ends_at' => '2025-12-25'], // missing time+timezone
    ]);
} catch (InvalidArgumentException $e) {
    // 'Day at index 0 has invalid starts_at format. Expected ISO 8601 format.'
    Log::error($e->getMessage());
}

// API-level errors
try {
    Teamleader::daysOff()->bulkImport('user-uuid', 'leave-type-uuid', $days);
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Day-Off-Types]] — Provides the `leaveTypeId` used in all import calls
- [[Users]] — `listDaysOff()` reads leave records; UUIDs retrieved here are used in `bulkDelete()`
- [[Closing-Days]] — Company-wide closure dates (not per-user leave)

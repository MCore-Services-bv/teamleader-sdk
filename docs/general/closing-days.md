# Closing Days

Manage company-wide closing days in Teamleader Focus.

## Overview

The Closing Days resource lets you create and delete dates on which your company is closed. Closing days affect
scheduling, user availability calculations, and planning across the account.

`update()` and `info()` are not supported — there are no API endpoints for them.

## Endpoint

`closingDays`

## Capabilities

| Capability  | Supported                                 |
|-------------|-------------------------------------------|
| Pagination  | ✅ Supported                               |
| Filtering   | ✅ Supported (`date_after`, `date_before`) |
| Sorting     | ❌ Not supported                           |
| Sideloading | ❌ Not supported                           |
| Creation    | ✅ Supported                               |
| Update      | ❌ Not supported                           |
| Deletion    | ✅ Supported                               |

> **Note on the API endpoint:** `create()` posts to `closingDays.add` internally, not `closingDays.create`. The SDK
> handles this transparently.

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns closing days, optionally filtered by date range. Both filter values are validated as `YYYY-MM-DD` before the
request is sent.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All closing days
$closingDays = Teamleader::closingDays()->list();

// Within a date range
$closingDays = Teamleader::closingDays()->list([
    'date_after'  => '2025-01-01',
    'date_before' => '2025-12-31',
]);

// With pagination
$closingDays = Teamleader::closingDays()->list([], [
    'page_size'   => 50,
    'page_number' => 1,
]);
```

---

### `create(array $data)`

Adds a closing day. The `day` field must be a valid date in `YYYY-MM-DD` format — validated before the request is sent.

```php
Teamleader::closingDays()->create(['day' => '2025-12-25']);
```

---

### `add(string $day)`

Alias for `create(['day' => $day])`. Accepts the date string directly.

```php
Teamleader::closingDays()->add('2025-12-25');
```

---

### `delete(mixed $id)`

Removes a closing day by UUID.

```php
Teamleader::closingDays()->delete('closing-day-uuid');
```

---

## Helper Methods

### `forMonth(string $yearMonth)`

Returns closing days for a specific month. Validates the `YYYY-MM` format.

```php
$closingDays = Teamleader::closingDays()->forMonth('2025-12');
$closingDays = Teamleader::closingDays()->forMonth(date('Y-m'));
```

### `forYear(int|string $year)`

Returns closing days for a full year. Year must be between 1900 and 2100.

```php
$closingDays = Teamleader::closingDays()->forYear(2025);
$closingDays = Teamleader::closingDays()->forYear(date('Y'));
```

### `forDateRange(string $startDate, string $endDate)`

Returns closing days between two dates (inclusive). Both dates are validated; start must not be after end.

```php
$closingDays = Teamleader::closingDays()->forDateRange('2025-12-20', '2025-12-31');
```

### `upcoming(int $daysAhead = 30)`

Returns closing days from today up to `$daysAhead` days in the future.

```php
$closingDays = Teamleader::closingDays()->upcoming();     // next 30 days
$closingDays = Teamleader::closingDays()->upcoming(90);   // next 90 days
```

### `isClosingDay(string $date)`

Returns `true` if the given date is registered as a closing day. Makes one API call.

```php
if (Teamleader::closingDays()->isClosingDay('2025-12-25')) {
    // Office is closed
}
```

### `bulkAdd(array $dates)`

Adds multiple closing days in a loop. Each date is passed through `add()`. Failures are caught and returned as error
objects in the results array — they do not throw.

```php
$results = Teamleader::closingDays()->bulkAdd([
    '2025-01-01',
    '2025-04-21',
    '2025-12-25',
    '2025-12-26',
]);

// Each entry is either a success response or:
// ['error' => true, 'date' => '2025-12-25', 'message' => '...']
```

### `getCommonHolidays(int $year, string $country = 'BE')`

Returns an array of common holiday dates for a year — **no API call**. Useful for seeding `bulkAdd()`. Currently covers
New Year's Day, Easter Monday, Christmas Day, and Boxing Day.

```php
$holidays = Teamleader::closingDays()->getCommonHolidays(2025);
// ['New Year\'s Day' => '2025-01-01', 'Easter Monday' => '2025-04-21', ...]

Teamleader::closingDays()->bulkAdd(array_values($holidays));
```

---

## Filters

### `date_after`

Start of the period (inclusive). Format: `YYYY-MM-DD`.

### `date_before`

End of the period (inclusive). Format: `YYYY-MM-DD`.

Both values are validated by `buildFilters()` before the request — an `InvalidArgumentException` is thrown for any
invalid format.

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        ['id' => 'closing-day-uuid', 'day' => '2025-12-25'],
        ['id' => 'closing-day-uuid', 'day' => '2025-12-26'],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 2,
    ],
]
```

---

## Usage Examples

### Add the year's public holidays

```php
$holidays = Teamleader::closingDays()->getCommonHolidays(2025);
Teamleader::closingDays()->bulkAdd(array_values($holidays));
```

### Find and delete a specific closing day

```php
$result = Teamleader::closingDays()->forDateRange('2025-12-25', '2025-12-25');

if (!empty($result['data'])) {
    Teamleader::closingDays()->delete($result['data'][0]['id']);
}
```

### Check whether today is a closing day

```php
if (Teamleader::closingDays()->isClosingDay(date('Y-m-d'))) {
    // Office closed — skip scheduling logic
}
```

### Cache closing days for the year

```php
$closingDays = Cache::remember('tl_closing_days_' . date('Y'), 86400, function () {
    return Teamleader::closingDays()->forYear((int) date('Y'));
});

$closedDates = array_column($closingDays['data'], 'day');
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid date format — thrown before the request
try {
    Teamleader::closingDays()->add('25-12-2025'); // wrong format
} catch (InvalidArgumentException $e) {
    // 'The "day" field must be a valid date in YYYY-MM-DD format'
    Log::error($e->getMessage());
}

// bulkAdd() catches failures per-entry — check results for errors
$results = Teamleader::closingDays()->bulkAdd(['2025-12-25', 'bad-date']);
foreach ($results as $result) {
    if (isset($result['error'])) {
        Log::warning('Bulk add failure', ['message' => $result['message']]);
    }
}

// API-level errors
try {
    Teamleader::closingDays()->delete('closing-day-uuid');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Day-Off-Types]] — Categories of leave used with `daysOff`
- [[Days-Off]] — Import/delete individual user leave entries
- [[Users]] — `listDaysOff()` reads user leave records
- [[Filtering]] — Filter and pagination reference

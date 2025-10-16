# Closing Days

Manage closing days in Teamleader Focus.

## Overview

The Closing Days resource allows you to manage company-wide closing days (holidays, closures) in your Teamleader account. These are days when your company is closed for business and can affect scheduling, availability, and time tracking.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [create() / add()](#create--add)
    - [delete()](#delete)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`closingDays`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ❌ Not Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get closing days with optional date range filtering.

**Parameters:**
- `filters` (array): Date range filters
- `options` (array): Pagination options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all closing days
$closingDays = Teamleader::closingDays()->list();

// Get closing days in a date range
$closingDays = Teamleader::closingDays()->list([
    'date_after' => '2024-01-01',
    'date_before' => '2024-12-31'
]);
```

### `create()` / `add()`

Add a new closing day.

**Parameters:**
- `data` (array): Must contain `day` field in YYYY-MM-DD format

**Example:**
```php
// Using create()
$result = Teamleader::closingDays()->create([
    'day' => '2024-12-25'
]);

// Using add() alias
$result = Teamleader::closingDays()->add('2024-12-25');
```

### `delete()`

Remove a closing day.

**Parameters:**
- `id` (string): Closing day UUID

**Example:**
```php
Teamleader::closingDays()->delete('closing-day-uuid');
```

## Helper Methods

The Closing Days resource provides convenient helper methods:

### `forMonth()`

Get all closing days in a specific month.

```php
// Get closing days for January 2024
$closingDays = Teamleader::closingDays()->forMonth('2024-01');

// Get closing days for current month
$closingDays = Teamleader::closingDays()->forMonth(date('Y-m'));
```

### `forYear()`

Get all closing days in a specific year.

```php
// Get all closing days in 2024
$closingDays = Teamleader::closingDays()->forYear(2024);

// Get all closing days in current year
$closingDays = Teamleader::closingDays()->forYear(date('Y'));
```

### `forDateRange()`

Get closing days within a specific date range.

```php
$closingDays = Teamleader::closingDays()->forDateRange(
    '2024-12-01',
    '2024-12-31'
);
```

### `upcoming()`

Get upcoming closing days from today.

```php
// Get closing days in next 30 days
$closingDays = Teamleader::closingDays()->upcoming();

// Get closing days in next 90 days
$closingDays = Teamleader::closingDays()->upcoming(90);
```

### `isClosingDay()`

Check if a specific date is a closing day.

```php
if (Teamleader::closingDays()->isClosingDay('2024-12-25')) {
    echo "Christmas Day is a closing day";
}
```

### `bulkAdd()`

Add multiple closing days at once.

```php
$dates = [
    '2024-12-25',
    '2024-12-26',
    '2024-01-01'
];

$results = Teamleader::closingDays()->bulkAdd($dates);
```

## Filters

### Available Filters

#### `date_after`
Start of the period (inclusive). Must be in YYYY-MM-DD format.

```php
$closingDays = Teamleader::closingDays()->list([
    'date_after' => '2024-01-01'
]);
```

#### `date_before`
End of the period (inclusive). Must be in YYYY-MM-DD format.

```php
$closingDays = Teamleader::closingDays()->list([
    'date_before' => '2024-12-31'
]);
```

#### Combined Date Range

```php
$closingDays = Teamleader::closingDays()->list([
    'date_after' => '2024-06-01',
    'date_before' => '2024-08-31'
]);
```

## Response Structure

### Closing Day Object

```php
[
    'id' => 'closing-day-uuid',
    'day' => '2024-12-25'
]
```

## Usage Examples

### Add a Single Closing Day

```php
// Add Christmas Day
$result = Teamleader::closingDays()->add('2024-12-25');

// Or using create
$result = Teamleader::closingDays()->create([
    'day' => '2024-12-25'
]);
```

### Add Multiple Closing Days

```php
$holidays = [
    '2024-01-01', // New Year's Day
    '2024-04-01', // Easter Monday
    '2024-05-01', // Labour Day
    '2024-12-25', // Christmas
    '2024-12-26'  // Boxing Day
];

$results = Teamleader::closingDays()->bulkAdd($holidays);
```

### Get Closing Days for Next Quarter

```php
$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+3 months'));

$closingDays = Teamleader::closingDays()->forDateRange($startDate, $endDate);
```

### Check if Today is a Closing Day

```php
$today = date('Y-m-d');

if (Teamleader::closingDays()->isClosingDay($today)) {
    echo "The office is closed today";
} else {
    echo "The office is open today";
}
```

### Delete a Closing Day

```php
// First, find the closing day
$closingDays = Teamleader::closingDays()->forDateRange('2024-12-25', '2024-12-25');

if (!empty($closingDays['data'])) {
    $closingDayId = $closingDays['data'][0]['id'];
    Teamleader::closingDays()->delete($closingDayId);
}
```

## Common Use Cases

### Annual Holiday Setup

```php
class HolidaySetup
{
    public function setupAnnualHolidays($year)
    {
        $holidays = [
            "{$year}-01-01", // New Year
            "{$year}-04-01", // Easter Monday (example date)
            "{$year}-05-01", // Labour Day
            "{$year}-12-25", // Christmas
            "{$year}-12-26"  // Boxing Day
        ];
        
        return Teamleader::closingDays()->bulkAdd($holidays);
    }
}
```

### Check Business Days

```php
class BusinessDayCalculator
{
    public function isBusinessDay($date)
    {
        // Check if weekend
        $dayOfWeek = date('N', strtotime($date));
        if ($dayOfWeek >= 6) {
            return false;
        }
        
        // Check if closing day
        return !Teamleader::closingDays()->isClosingDay($date);
    }
    
    public function getNextBusinessDay($date)
    {
        $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
        
        while (!$this->isBusinessDay($nextDay)) {
            $nextDay = date('Y-m-d', strtotime($nextDay . ' +1 day'));
        }
        
        return $nextDay;
    }
}
```

### Sync Holidays to Local Database

```php
use App\Models\ClosingDay;
use Illuminate\Console\Command;

class SyncClosingDaysCommand extends Command
{
    protected $signature = 'teamleader:sync-closing-days {year?}';
    
    public function handle()
    {
        $year = $this->argument('year') ?? date('Y');
        
        $this->info("Syncing closing days for {$year}...");
        
        $closingDays = Teamleader::closingDays()->forYear($year);
        
        foreach ($closingDays['data'] as $day) {
            ClosingDay::updateOrCreate(
                ['teamleader_id' => $day['id']],
                ['date' => $day['day']]
            );
        }
        
        $this->info('Closing days synced successfully!');
    }
}
```

### Calendar Integration

```php
class CalendarService
{
    public function getAvailableDates($startDate, $endDate)
    {
        $closingDays = Teamleader::closingDays()
            ->forDateRange($startDate, $endDate);
        
        $closedDates = array_column($closingDays['data'], 'day');
        
        // Generate all dates in range
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            new DateTime($endDate . ' +1 day')
        );
        
        $availableDates = [];
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dayOfWeek = $date->format('N');
            
            // Skip weekends and closing days
            if ($dayOfWeek < 6 && !in_array($dateString, $closedDates)) {
                $availableDates[] = $dateString;
            }
        }
        
        return $availableDates;
    }
}
```

### Upcoming Closures Dashboard

```php
class ClosuresDashboard
{
    public function getUpcomingClosures($days = 30)
    {
        $closingDays = Teamleader::closingDays()->upcoming($days);
        
        $formatted = [];
        foreach ($closingDays['data'] as $day) {
            $date = new DateTime($day['day']);
            $daysUntil = (int)$date->diff(new DateTime())->format('%a');
            
            $formatted[] = [
                'id' => $day['id'],
                'date' => $day['day'],
                'formatted_date' => $date->format('l, F j, Y'),
                'days_until' => $daysUntil,
                'is_this_week' => $daysUntil <= 7
            ];
        }
        
        return $formatted;
    }
}
```

### Remove Past Closing Days

```php
class ClosingDayMaintenance
{
    public function removePastDays()
    {
        $today = date('Y-m-d');
        $lastYear = date('Y-m-d', strtotime('-1 year'));
        
        $pastDays = Teamleader::closingDays()->forDateRange($lastYear, $today);
        
        foreach ($pastDays['data'] as $day) {
            if ($day['day'] < $today) {
                Teamleader::closingDays()->delete($day['id']);
            }
        }
    }
}
```

## Best Practices

### 1. Use Helper Methods

```php
// Good: Clear and readable
$nextMonthClosures = Teamleader::closingDays()->forMonth('2024-12');

// Less ideal: Manual filtering
$nextMonthClosures = Teamleader::closingDays()->list([
    'date_after' => '2024-12-01',
    'date_before' => '2024-12-31'
]);
```

### 2. Validate Dates Before Adding

```php
// Good: Validate date format
$date = '2024-12-25';

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date)) {
    Teamleader::closingDays()->add($date);
} else {
    throw new \InvalidArgumentException('Invalid date format');
}

// Bad: No validation
Teamleader::closingDays()->add($date); // Could fail
```

### 3. Handle Duplicates

```php
// Good: Check before adding
if (!Teamleader::closingDays()->isClosingDay('2024-12-25')) {
    Teamleader::closingDays()->add('2024-12-25');
}

// Bad: Add without checking (may cause errors)
Teamleader::closingDays()->add('2024-12-25');
```

### 4. Cache Closing Days

```php
use Illuminate\Support\Facades\Cache;

// Good: Cache for the day
$today = date('Y-m-d');
$cacheKey = "closing_days.check.{$today}";

$isClosingDay = Cache::remember($cacheKey, 86400, function() use ($today) {
    return Teamleader::closingDays()->isClosingDay($today);
});

// Bad: Check every time
$isClosingDay = Teamleader::closingDays()->isClosingDay($today);
```

### 5. Plan Ahead

```php
// Good: Setup holidays at the beginning of the year
class YearlySetup
{
    public function setupNewYear()
    {
        $year = date('Y') + 1;
        
        // Add next year's holidays
        $this->addPublicHolidays($year);
        $this->addCompanySpecificDays($year);
    }
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $result = Teamleader::closingDays()->add('2024-12-25');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error (maybe duplicate or invalid date)
        Log::warning('Could not add closing day', [
            'date' => '2024-12-25',
            'error' => $e->getMessage()
        ]);
    } else {
        Log::error('Error adding closing day', [
            'error' => $e->getMessage()
        ]);
    }
}
```

## Date Format Requirements

All dates must be in **YYYY-MM-DD** format:

```php
// Correct formats
'2024-12-25'
'2024-01-01'

// Incorrect formats (will fail)
'25-12-2024'
'12/25/2024'
'25 December 2024'
```

## Related Resources

- [Users](users.md) - User availability affected by closing days
- [Days Off](days_off.md) - Individual user days off
- [Day Off Types](day_off_types.md) - Types of days off

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

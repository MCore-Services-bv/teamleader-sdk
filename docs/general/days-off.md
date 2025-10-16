# Days Off

Manage user days off in Teamleader Focus.

## Overview

The Days Off resource allows you to bulk import and delete days off (vacation, sick leave, etc.) for users in your Teamleader account. This resource uses a specialized bulk operation approach rather than standard CRUD methods.

**Important:** This resource only supports bulk import and bulk delete operations. Standard `list()`, `info()`, `create()`, and `update()` methods are not available. To view user days off, use the `Users` resource `listDaysOff()` method.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [bulkImport()](#bulkimport)
    - [bulkDelete()](#bulkdelete)
- [Helper Methods](#helper-methods)
- [Date Format Requirements](#date-format-requirements)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`daysOff`

## Capabilities

- **Bulk Import**: ✅ Supported
- **Bulk Delete**: ✅ Supported
- **List**: ❌ Not Supported (use Users resource)
- **Info**: ❌ Not Supported
- **Create**: ❌ Not Supported (use bulk import)
- **Update**: ❌ Not Supported
- **Delete**: ❌ Not Supported (use bulk delete)

## Available Methods

### `bulkImport()`

Import (create) multiple days off for a user.

**Parameters:**
- `userId` (string): User UUID
- `leaveTypeId` (string): Day off type UUID (from dayOffTypes resource)
- `days` (array): Array of day objects with `starts_at` and `ends_at` in ISO 8601 format

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$result = Teamleader::daysOff()->bulkImport(
    'user-uuid',
    'leave-type-uuid',
    [
        [
            'starts_at' => '2024-12-23T08:00:00+00:00',
            'ends_at' => '2024-12-23T18:00:00+00:00'
        ],
        [
            'starts_at' => '2024-12-24T08:00:00+00:00',
            'ends_at' => '2024-12-24T18:00:00+00:00'
        ]
    ]
);
```

### `bulkDelete()`

Delete multiple days off for a user.

**Parameters:**
- `userId` (string): User UUID
- `dayOffIds` (array): Array of day off UUIDs to delete

**Example:**
```php
$result = Teamleader::daysOff()->bulkDelete(
    'user-uuid',
    [
        'day-off-uuid-1',
        'day-off-uuid-2',
        'day-off-uuid-3'
    ]
);
```

## Helper Methods

### `importSingleDay()`

Import a single day off (convenience wrapper for bulkImport).

```php
$result = Teamleader::daysOff()->importSingleDay(
    'user-uuid',
    'leave-type-uuid',
    '2024-12-25T08:00:00+00:00',
    '2024-12-25T18:00:00+00:00'
);
```

### `importMultipleDays()`

Import multiple days with the same daily schedule.

```php
$dates = ['2024-12-23', '2024-12-24', '2024-12-27'];

$result = Teamleader::daysOff()->importMultipleDays(
    'user-uuid',
    'leave-type-uuid',
    $dates,
    '08:00:00',  // Start time
    '18:00:00',  // End time
    '+00:00'     // Timezone
);
```

### `importDateRange()`

Import consecutive days within a date range.

```php
$result = Teamleader::daysOff()->importDateRange(
    'user-uuid',
    'leave-type-uuid',
    '2024-12-23',        // Start date
    '2024-12-27',        // End date
    '08:00:00',          // Daily start time
    '18:00:00',          // Daily end time
    '+00:00',            // Timezone
    true                 // Exclude weekends
);
```

## Date Format Requirements

All datetime values must be in **ISO 8601 format** with timezone:

```
YYYY-MM-DDTHH:MM:SS±HH:MM
```

**Examples:**
```php
'2024-12-25T08:00:00+00:00'  // UTC
'2024-12-25T08:00:00+01:00'  // CET (UTC+1)
'2024-12-25T08:00:00-05:00'  // EST (UTC-5)
```

## Usage Examples

### Import a Single Day Off

```php
// Full day off on Christmas
$result = Teamleader::daysOff()->importSingleDay(
    'user-uuid',
    'vacation-leave-type-uuid',
    '2024-12-25T00:00:00+00:00',
    '2024-12-25T23:59:59+00:00'
);
```

### Import Vacation Week

```php
// One week vacation
$result = Teamleader::daysOff()->importDateRange(
    'user-uuid',
    'vacation-leave-type-uuid',
    '2024-07-01',  // Monday
    '2024-07-05',  // Friday
    '00:00:00',
    '23:59:59',
    '+02:00',      // Summer time
    true           // Exclude weekends
);
```

### Import Half Days

```php
// Morning off
$morningOff = [
    [
        'starts_at' => '2024-12-20T08:00:00+00:00',
        'ends_at' => '2024-12-20T12:00:00+00:00'
    ]
];

Teamleader::daysOff()->bulkImport(
    'user-uuid',
    'leave-type-uuid',
    $morningOff
);

// Afternoon off
$afternoonOff = [
    [
        'starts_at' => '2024-12-20T13:00:00+00:00',
        'ends_at' => '2024-12-20T18:00:00+00:00'
    ]
];

Teamleader::daysOff()->bulkImport(
    'user-uuid',
    'leave-type-uuid',
    $afternoonOff
);
```

### Delete Cancelled Days Off

```php
// Get user's days off first
$daysOff = Teamleader::users()->listDaysOff('user-uuid', [
    'starts_after' => '2024-12-01'
]);

// Extract IDs to cancel
$dayOffIds = array_column($daysOff['data'], 'id');

// Delete them
if (!empty($dayOffIds)) {
    Teamleader::daysOff()->bulkDelete('user-uuid', $dayOffIds);
}
```

## Common Use Cases

### Vacation Request Handler

```php
class VacationRequestHandler
{
    public function approveVacation($userId, $leaveTypeId, $startDate, $endDate)
    {
        try {
            $result = Teamleader::daysOff()->importDateRange(
                $userId,
                $leaveTypeId,
                $startDate,
                $endDate,
                '00:00:00',
                '23:59:59',
                '+00:00',
                true  // Exclude weekends
            );
            
            return [
                'success' => true,
                'message' => 'Vacation approved and imported',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import vacation: ' . $e->getMessage()
            ];
        }
    }
}
```

### Bulk Holiday Import

```php
class HolidayImporter
{
    public function importPublicHolidays($year, $holidays)
    {
        $users = Teamleader::users()->active();
        $holidayLeaveType = $this->getHolidayLeaveType();
        
        $results = [];
        
        foreach ($users['data'] as $user) {
            $days = [];
            
            foreach ($holidays as $holiday) {
                $date = "{$year}-{$holiday}";
                $days[] = [
                    'starts_at' => "{$date}T00:00:00+00:00",
                    'ends_at' => "{$date}T23:59:59+00:00"
                ];
            }
            
            try {
                $result = Teamleader::daysOff()->bulkImport(
                    $user['id'],
                    $holidayLeaveType,
                    $days
                );
                
                $results[$user['id']] = [
                    'success' => true,
                    'imported' => count($days)
                ];
            } catch (\Exception $e) {
                $results[$user['id']] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    private function getHolidayLeaveType()
    {
        // Get the public holiday leave type
        $types = Teamleader::dayOffTypes()->list();
        
        foreach ($types['data'] as $type) {
            if ($type['name'] === 'Public Holiday') {
                return $type['id'];
            }
        }
        
        throw new \Exception('Public Holiday leave type not found');
    }
}
```

### Sick Leave Management

```php
class SickLeaveManager
{
    public function reportSickDay($userId, $date, $isFullDay = true)
    {
        $sickLeaveType = $this->getSickLeaveType();
        
        if ($isFullDay) {
            return Teamleader::daysOff()->importSingleDay(
                $userId,
                $sickLeaveType,
                "{$date}T00:00:00+00:00",
                "{$date}T23:59:59+00:00"
            );
        }
        
        // Half day sick leave (morning)
        return Teamleader::daysOff()->importSingleDay(
            $userId,
            $sickLeaveType,
            "{$date}T08:00:00+00:00",
            "{$date}T12:00:00+00:00"
        );
    }
    
    public function reportSickPeriod($userId, $startDate, $endDate)
    {
        $sickLeaveType = $this->getSickLeaveType();
        
        return Teamleader::daysOff()->importDateRange(
            $userId,
            $sickLeaveType,
            $startDate,
            $endDate,
            '00:00:00',
            '23:59:59',
            '+00:00',
            true  // Exclude weekends
        );
    }
    
    private function getSickLeaveType()
    {
        $types = Teamleader::dayOffTypes()->list();
        
        foreach ($types['data'] as $type) {
            if (stripos($type['name'], 'sick') !== false) {
                return $type['id'];
            }
        }
        
        throw new \Exception('Sick leave type not found');
    }
}
```

### Days Off Correction

```php
class DaysOffCorrection
{
    public function correctDaysOff($userId, $incorrectDayOffIds, $correctDays, $leaveTypeId)
    {
        // First, delete the incorrect entries
        Teamleader::daysOff()->bulkDelete($userId, $incorrectDayOffIds);
        
        // Then, import the correct ones
        return Teamleader::daysOff()->bulkImport(
            $userId,
            $leaveTypeId,
            $correctDays
        );
    }
}
```

### Sync from External System

```php
class DaysOffSync
{
    public function syncFromHRSystem($userId)
    {
        // Get days off from external HR system
        $externalDaysOff = $this->getFromHRSystem($userId);
        
        // Get current days off from Teamleader
        $currentDaysOff = Teamleader::users()->listDaysOff($userId, [
            'starts_after' => now()->toIso8601String()
        ]);
        
        // Determine which to keep and which to remove
        $toDelete = $this->getDaysToDelete($currentDaysOff['data'], $externalDaysOff);
        $toImport = $this->getDaysToImport($currentDaysOff['data'], $externalDaysOff);
        
        // Delete removed days
        if (!empty($toDelete)) {
            Teamleader::daysOff()->bulkDelete($userId, $toDelete);
        }
        
        // Import new days
        if (!empty($toImport)) {
            foreach ($toImport as $leaveType => $days) {
                Teamleader::daysOff()->bulkImport($userId, $leaveType, $days);
            }
        }
    }
}
```

## Best Practices

### 1. Always Exclude Weekends

```php
// Good: Exclude weekends from date ranges
$result = Teamleader::daysOff()->importDateRange(
    $userId,
    $leaveTypeId,
    $startDate,
    $endDate,
    '08:00:00',
    '18:00:00',
    '+00:00',
    true  // Exclude weekends
);

// Bad: Include weekends (wastes quota)
$result = Teamleader::daysOff()->importDateRange(
    $userId,
    $leaveTypeId,
    $startDate,
    $endDate,
    '08:00:00',
    '18:00:00',
    '+00:00',
    false
);
```

### 2. Validate User and Leave Type IDs

```php
// Good: Validate before importing
if (!$this->isValidUserId($userId) || !$this->isValidLeaveTypeId($leaveTypeId)) {
    throw new \InvalidArgumentException('Invalid user or leave type ID');
}

$result = Teamleader::daysOff()->bulkImport($userId, $leaveTypeId, $days);
```

### 3. Handle Large Imports in Chunks

```php
// Good: Process in chunks
$allDays = $this->generateYearOfDaysOff();
$chunks = array_chunk($allDays, 50);  // Max 50 days per request

foreach ($chunks as $chunk) {
    Teamleader::daysOff()->bulkImport($userId, $leaveTypeId, $chunk);
    sleep(1);  // Rate limiting
}

// Bad: Try to import too many at once
Teamleader::daysOff()->bulkImport($userId, $leaveTypeId, $allDays);
```

### 4. Use Correct Timezone

```php
// Good: Use company timezone
$timezone = config('app.timezone_offset'); // e.g., '+01:00'

$result = Teamleader::daysOff()->importDateRange(
    $userId,
    $leaveTypeId,
    $startDate,
    $endDate,
    '08:00:00',
    '18:00:00',
    $timezone  // Correct timezone
);

// Bad: Always use UTC (may be incorrect for local times)
$result = Teamleader::daysOff()->importDateRange(
    $userId,
    $leaveTypeId,
    $startDate,
    $endDate,
    '08:00:00',
    '18:00:00',
    '+00:00'
);
```

### 5. Verify After Import

```php
// Good: Verify the import was successful
$result = Teamleader::daysOff()->bulkImport($userId, $leaveTypeId, $days);

// Check the imported days
$imported = Teamleader::users()->listDaysOff($userId, [
    'starts_after' => $startDate
]);

if (count($imported['data']) !== count($days)) {
    Log::warning('Not all days were imported', [
        'expected' => count($days),
        'actual' => count($imported['data'])
    ]);
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $result = Teamleader::daysOff()->bulkImport(
        $userId,
        $leaveTypeId,
        $days
    );
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Invalid days off data', [
            'user_id' => $userId,
            'error' => $e->getMessage(),
            'details' => $e->getDetails()
        ]);
    } else {
        Log::error('Failed to import days off', [
            'error' => $e->getMessage()
        ]);
    }
    
    throw $e;
}
```

## Method Restrictions

The following standard methods are **not available**:

```php
// These will throw BadMethodCallException
Teamleader::daysOff()->list();        // ❌ Use Users->listDaysOff()
Teamleader::daysOff()->info($id);     // ❌ Not available
Teamleader::daysOff()->create([]);    // ❌ Use bulkImport()
Teamleader::daysOff()->update();      // ❌ Not available
Teamleader::daysOff()->delete($id);   // ❌ Use bulkDelete()
```

## Related Resources

- [Users](users.md) - Use `listDaysOff()` to view user days off
- [Day Off Types](day_off_types.md) - Manage day off type definitions
- [Closing Days](closing_days.md) - Company-wide closing days

## See Also

- [Usage Guide](../usage.md) - General SDK usage

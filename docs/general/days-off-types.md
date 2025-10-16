# Day Off Types

Manage day off type definitions in Teamleader Focus.

## Overview

The Day Off Types resource allows you to create, update, delete, and list day off type definitions in your Teamleader account. Day off types categorize different kinds of leave (vacation, sick leave, personal days, etc.) and can be assigned different colors and validity periods.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`dayOffTypes`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all day off types.

**Parameters:** None

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all day off types
$dayOffTypes = Teamleader::dayOffTypes()->list();
```

### `create()`

Create a new day off type.

**Parameters:**
- `data` (array): Day off type data
    - `name` (string, required): Name of the day off type
    - `color` (string, required): Hex color code (e.g., '#FF0000')
    - `date_validity` (array, optional): Date range when this type is valid
        - `from` (string): Start date (YYYY-MM-DD)
        - `until` (string): End date (YYYY-MM-DD)

**Example:**
```php
// Create a basic day off type
$dayOffType = Teamleader::dayOffTypes()->create([
    'name' => 'Vacation',
    'color' => '#00B2B2'
]);

// Create with date validity
$dayOffType = Teamleader::dayOffTypes()->create([
    'name' => 'Summer Leave',
    'color' => '#FFB600',
    'date_validity' => [
        'from' => '2024-06-01',
        'until' => '2024-08-31'
    ]
]);
```

### `update()`

Update an existing day off type.

**Parameters:**
- `id` (string): Day off type UUID
- `data` (array): Updated data

**Example:**
```php
$result = Teamleader::dayOffTypes()->update('day-off-type-uuid', [
    'name' => 'Updated Name',
    'color' => '#FF5500'
]);
```

### `delete()`

Delete a day off type.

**Parameters:**
- `id` (string): Day off type UUID

**Example:**
```php
Teamleader::dayOffTypes()->delete('day-off-type-uuid');
```

## Response Structure

### Day Off Type Object

```php
[
    'id' => 'day-off-type-uuid',
    'name' => 'Vacation',
    'color' => '#00B2B2',
    'date_validity' => [
        'from' => '2024-01-01',
        'until' => '2024-12-31'
    ] // Or null if no validity period
]
```

## Usage Examples

### Create Standard Leave Types

```php
// Vacation
$vacation = Teamleader::dayOffTypes()->create([
    'name' => 'Vacation',
    'color' => '#00B2B2'
]);

// Sick Leave
$sickLeave = Teamleader::dayOffTypes()->create([
    'name' => 'Sick Leave',
    'color' => '#FF0000'
]);

// Personal Day
$personalDay = Teamleader::dayOffTypes()->create([
    'name' => 'Personal Day',
    'color' => '#FFB600'
]);
```

### Create Seasonal Leave Type

```php
// Summer hours - only valid during summer months
$summerHours = Teamleader::dayOffTypes()->create([
    'name' => 'Summer Hours',
    'color' => '#FFA500',
    'date_validity' => [
        'from' => '2024-06-01',
        'until' => '2024-08-31'
    ]
]);
```

### Update Leave Type

```php
// Change the color of a leave type
$result = Teamleader::dayOffTypes()->update('vacation-uuid', [
    'color' => '#0000FF'  // Change to blue
]);

// Update validity period
$result = Teamleader::dayOffTypes()->update('summer-leave-uuid', [
    'date_validity' => [
        'from' => '2024-07-01',
        'until' => '2024-09-30'
    ]
]);
```

### Delete Unused Leave Type

```php
Teamleader::dayOffTypes()->delete('old-leave-type-uuid');
```

### Get All Leave Types

```php
$allTypes = Teamleader::dayOffTypes()->list();

foreach ($allTypes['data'] as $type) {
    echo "{$type['name']} - {$type['color']}\n";
}
```

## Common Use Cases

### Initialize Leave Types for New Account

```php
class LeaveTypeInitializer
{
    public function setupStandardTypes()
    {
        $standardTypes = [
            [
                'name' => 'Annual Leave',
                'color' => '#00B2B2'
            ],
            [
                'name' => 'Sick Leave',
                'color' => '#FF0000'
            ],
            [
                'name' => 'Personal Day',
                'color' => '#FFB600'
            ],
            [
                'name' => 'Public Holiday',
                'color' => '#9900FF'
            ],
            [
                'name' => 'Unpaid Leave',
                'color' => '#808080'
            ],
            [
                'name' => 'Parental Leave',
                'color' => '#FF69B4'
            ]
        ];
        
        $created = [];
        foreach ($standardTypes as $type) {
            $created[] = Teamleader::dayOffTypes()->create($type);
        }
        
        return $created;
    }
}
```

### Leave Type Selector

```php
class LeaveTypeSelector
{
    public function getOptions()
    {
        $types = Teamleader::dayOffTypes()->list();
        
        $options = [];
        foreach ($types['data'] as $type) {
            $options[] = [
                'value' => $type['id'],
                'label' => $type['name'],
                'color' => $type['color'],
                'valid_from' => $type['date_validity']['from'] ?? null,
                'valid_until' => $type['date_validity']['until'] ?? null
            ];
        }
        
        return $options;
    }
    
    public function getActiveTypes($date = null)
    {
        $date = $date ?? date('Y-m-d');
        $types = Teamleader::dayOffTypes()->list();
        
        $active = [];
        foreach ($types['data'] as $type) {
            if ($this->isValidOnDate($type, $date)) {
                $active[] = $type;
            }
        }
        
        return $active;
    }
    
    private function isValidOnDate($type, $date)
    {
        if (!isset($type['date_validity'])) {
            return true;
        }
        
        $from = $type['date_validity']['from'];
        $until = $type['date_validity']['until'];
        
        return $date >= $from && $date <= $until;
    }
}
```

### Color-Coded Calendar

```php
class LeaveCalendar
{
    public function getColorMapping()
    {
        $types = Teamleader::dayOffTypes()->list();
        
        $colorMap = [];
        foreach ($types['data'] as $type) {
            $colorMap[$type['id']] = [
                'name' => $type['name'],
                'color' => $type['color']
            ];
        }
        
        return $colorMap;
    }
    
    public function renderLeave($dayOffTypeId)
    {
        $colorMap = $this->getColorMapping();
        
        if (isset($colorMap[$dayOffTypeId])) {
            return [
                'style' => "background-color: {$colorMap[$dayOffTypeId]['color']}",
                'title' => $colorMap[$dayOffTypeId]['name']
            ];
        }
        
        return [
            'style' => 'background-color: #CCCCCC',
            'title' => 'Unknown'
        ];
    }
}
```

### Sync to Local Database

```php
use App\Models\DayOffType;
use Illuminate\Console\Command;

class SyncDayOffTypesCommand extends Command
{
    protected $signature = 'teamleader:sync-day-off-types';
    
    public function handle()
    {
        $this->info('Syncing day off types...');
        
        $types = Teamleader::dayOffTypes()->list();
        
        foreach ($types['data'] as $typeData) {
            DayOffType::updateOrCreate(
                ['teamleader_id' => $typeData['id']],
                [
                    'name' => $typeData['name'],
                    'color' => $typeData['color'],
                    'valid_from' => $typeData['date_validity']['from'] ?? null,
                    'valid_until' => $typeData['date_validity']['until'] ?? null,
                ]
            );
        }
        
        $this->info('Day off types synced successfully!');
    }
}
```

### Find Leave Type by Name

```php
class LeaveTypeFinder
{
    public function findByName($name)
    {
        $types = Teamleader::dayOffTypes()->list();
        
        foreach ($types['data'] as $type) {
            if (strcasecmp($type['name'], $name) === 0) {
                return $type;
            }
        }
        
        return null;
    }
    
    public function getVacationType()
    {
        return $this->findByName('Vacation') 
            ?? $this->findByName('Annual Leave')
            ?? $this->findByName('Holiday');
    }
    
    public function getSickLeaveType()
    {
        return $this->findByName('Sick Leave')
            ?? $this->findByName('Sick Day');
    }
}
```

## Best Practices

### 1. Use Meaningful Colors

```php
// Good: Use intuitive colors
$vacation = ['name' => 'Vacation', 'color' => '#00B2B2'];    // Teal/Blue
$sickLeave = ['name' => 'Sick Leave', 'color' => '#FF0000']; // Red
$personal = ['name' => 'Personal', 'color' => '#FFB600'];    // Orange

// Bad: Random colors
$vacation = ['name' => 'Vacation', 'color' => '#123456'];
```

### 2. Cache Leave Types

```php
use Illuminate\Support\Facades\Cache;

// Good: Cache leave types
$types = Cache::remember('day_off_types', 3600, function() {
    return Teamleader::dayOffTypes()->list();
});

// Bad: Fetch every time
$types = Teamleader::dayOffTypes()->list();
```

### 3. Validate Before Delete

```php
// Good: Check if type is in use before deleting
if (!$this->isLeaveTypeInUse($typeId)) {
    Teamleader::dayOffTypes()->delete($typeId);
} else {
    throw new \Exception('Cannot delete: leave type is in use');
}

// Bad: Delete without checking
Teamleader::dayOffTypes()->delete($typeId);
```

### 4. Use Date Validity for Seasonal Types

```php
// Good: Set validity for seasonal types
Teamleader::dayOffTypes()->create([
    'name' => 'Summer Friday',
    'color' => '#FFA500',
    'date_validity' => [
        'from' => date('Y') . '-06-01',
        'until' => date('Y') . '-08-31'
    ]
]);

// Bad: Create without validity (available year-round)
Teamleader::dayOffTypes()->create([
    'name' => 'Summer Friday',
    'color' => '#FFA500'
]);
```

### 5. Document Color Choices

```php
// Good: Document color system
class LeaveTypeColors
{
    const VACATION = '#00B2B2';     // Teal - relaxation
    const SICK = '#FF0000';          // Red - urgent/medical
    const PERSONAL = '#FFB600';      // Orange - flexible
    const TRAINING = '#9900FF';      // Purple - development
    const UNPAID = '#808080';        // Gray - neutral
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $dayOffType = Teamleader::dayOffTypes()->create([
        'name' => 'New Leave Type',
        'color' => '#00B2B2'
    ]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Invalid day off type data', [
            'error' => $e->getMessage(),
            'details' => $e->getDetails()
        ]);
    } else {
        Log::error('Failed to create day off type', [
            'error' => $e->getMessage()
        ]);
    }
}
```

## Color Format

Colors must be in hexadecimal format:

```php
// Valid colors
'#FF0000'  // Red
'#00B2B2'  // Teal
'#FFB600'  // Orange

// Invalid colors (will fail validation)
'red'
'rgb(255, 0, 0)'
'FF0000'  // Missing #
```

## Related Resources

- [Days Off](days_off.md) - Import days off using these types
- [Users](users.md) - View user days off
- [Closing Days](closing_days.md) - Company-wide closing days

## See Also

- [Usage Guide](../usage.md) - General SDK usage

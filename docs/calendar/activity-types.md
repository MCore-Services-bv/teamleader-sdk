# Activity Types

Manage activity type definitions in Teamleader Focus.

## Overview

The Activity Types resource provides read-only access to activity type definitions in your Teamleader account. Activity types are used to categorize calendar events, meetings, and other activities, helping organize and filter your team's work.

**Important:** This resource is read-only. You cannot create, update, or delete activity types through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`activityTypes`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get all activity type definitions available in your Teamleader account.

**Parameters:**
- None

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all activity types
$activityTypes = Teamleader::activityTypes()->list();

foreach ($activityTypes['data'] as $type) {
    echo "{$type['name']}\n";
}
```

### `info()`

Get detailed information about a specific activity type.

**Parameters:**
- `id` (string): The activity type UUID

**Example:**
```php
$activityType = Teamleader::activityTypes()->info('activity-type-uuid');

echo "Activity Type: {$activityType['data']['name']}";
```

## Response Structure

### Activity Type Object

```php
[
    'id' => 'activity-type-uuid',
    'name' => 'Client Meeting',
    'description' => 'Meetings with clients and prospects',
    'color' => '#2196F3',
    'is_internal' => false,
    'is_default' => false,
    'icon' => 'meeting',
    'category' => 'meeting',
    'created_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-20T14:15:00+00:00'
]
```

### List Response

```php
[
    'data' => [
        [
            'id' => 'type-uuid-1',
            'name' => 'Client Meeting',
            'category' => 'meeting',
            'is_internal' => false
        ],
        [
            'id' => 'type-uuid-2',
            'name' => 'Internal Meeting',
            'category' => 'meeting',
            'is_internal' => true
        ],
        [
            'id' => 'type-uuid-3',
            'name' => 'Phone Call',
            'category' => 'call',
            'is_internal' => false
        ]
    ]
]
```

## Usage Examples

### Get All Activity Types

```php
$activityTypes = Teamleader::activityTypes()->list();

echo "Available activity types:\n";
foreach ($activityTypes['data'] as $type) {
    echo "- {$type['name']} (Category: {$type['category']})\n";
}
```

### Get Specific Activity Type Details

```php
$activityTypeId = 'activity-type-uuid';
$activityType = Teamleader::activityTypes()->info($activityTypeId);

echo "Activity Type: {$activityType['data']['name']}\n";
echo "Description: {$activityType['data']['description']}\n";
echo "Category: {$activityType['data']['category']}\n";
echo "Internal: " . ($activityType['data']['is_internal'] ? 'Yes' : 'No');
```

### Filter by Category

```php
$activityTypes = Teamleader::activityTypes()->list();

// Get only meeting types
$meetingTypes = array_filter($activityTypes['data'], function($type) {
    return $type['category'] === 'meeting';
});

echo "Meeting activity types:\n";
foreach ($meetingTypes as $type) {
    echo "- {$type['name']}\n";
}
```

### Get External Activity Types

```php
$activityTypes = Teamleader::activityTypes()->list();

$externalTypes = array_filter($activityTypes['data'], function($type) {
    return $type['is_internal'] === false;
});

echo "External activity types:\n";
foreach ($externalTypes as $type) {
    echo "- {$type['name']}\n";
}
```

### Build Activity Type Selection for UI

```php
$activityTypes = Teamleader::activityTypes()->list();

$typeOptions = [];
foreach ($activityTypes['data'] as $type) {
    $typeOptions[$type['id']] = $type['name'];
}

// Use in form/select
// ['type-uuid-1' => 'Client Meeting', 'type-uuid-2' => 'Phone Call', ...]
```

### Get Default Activity Type

```php
$activityTypes = Teamleader::activityTypes()->list();

$defaultType = null;
foreach ($activityTypes['data'] as $type) {
    if ($type['is_default'] ?? false) {
        $defaultType = $type;
        break;
    }
}

if ($defaultType) {
    echo "Default activity type: {$defaultType['name']}";
}
```

## Common Use Cases

### Activity Type Selector Service

```php
class ActivityTypeSelector
{
    protected $activityTypes = null;
    
    public function getAll()
    {
        if ($this->activityTypes === null) {
            $this->activityTypes = Teamleader::activityTypes()->list()['data'];
        }
        
        return $this->activityTypes;
    }
    
    public function getByCategory($category)
    {
        return array_filter($this->getAll(), function($type) use ($category) {
            return $type['category'] === $category;
        });
    }
    
    public function getExternal()
    {
        return array_filter($this->getAll(), function($type) {
            return $type['is_internal'] === false;
        });
    }
    
    public function getInternal()
    {
        return array_filter($this->getAll(), function($type) {
            return $type['is_internal'] === true;
        });
    }
    
    public function findByName($name)
    {
        $types = $this->getAll();
        
        foreach ($types as $type) {
            if (strcasecmp($type['name'], $name) === 0) {
                return $type;
            }
        }
        
        return null;
    }
    
    public function getDefault()
    {
        $types = $this->getAll();
        
        foreach ($types as $type) {
            if ($type['is_default'] ?? false) {
                return $type;
            }
        }
        
        return $types[0] ?? null;
    }
    
    public function getForDropdown($category = null)
    {
        $types = $category ? $this->getByCategory($category) : $this->getAll();
        
        $options = [];
        foreach ($types as $type) {
            $options[$type['id']] = $type['name'];
        }
        
        return $options;
    }
}

// Usage
$selector = new ActivityTypeSelector();

// Get meeting types for dropdown
$meetingOptions = $selector->getForDropdown('meeting');

// Get default type
$defaultType = $selector->getDefault();

// Find specific type
$clientMeetingType = $selector->findByName('Client Meeting');
```

### Activity Type Cache

```php
class ActivityTypeCache
{
    protected $cacheKey = 'teamleader_activity_types';
    protected $cacheDuration = 86400; // 24 hours
    
    public function get()
    {
        return Cache::remember($this->cacheKey, $this->cacheDuration, function() {
            return Teamleader::activityTypes()->list()['data'];
        });
    }
    
    public function getById($id)
    {
        $types = $this->get();
        
        foreach ($types as $type) {
            if ($type['id'] === $id) {
                return $type;
            }
        }
        
        return null;
    }
    
    public function refresh()
    {
        Cache::forget($this->cacheKey);
        return $this->get();
    }
    
    public function getGrouped()
    {
        $types = $this->get();
        $grouped = [];
        
        foreach ($types as $type) {
            $category = $type['category'] ?? 'other';
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            $grouped[$category][] = $type;
        }
        
        return $grouped;
    }
    
    public function getMap()
    {
        $types = $this->get();
        $map = [];
        
        foreach ($types as $type) {
            $map[$type['id']] = $type;
        }
        
        return $map;
    }
}

// Usage
$cache = new ActivityTypeCache();
$activityTypes = $cache->get();
$typeById = $cache->getById('type-uuid');
$groupedTypes = $cache->getGrouped();
```

### Activity Type Validator

```php
class ActivityTypeValidator
{
    protected $validTypes = null;
    
    public function isValid($typeId)
    {
        if ($this->validTypes === null) {
            $types = Teamleader::activityTypes()->list()['data'];
            $this->validTypes = array_column($types, 'id');
        }
        
        return in_array($typeId, $this->validTypes);
    }
    
    public function validate($typeId)
    {
        if (!$this->isValid($typeId)) {
            throw new InvalidArgumentException(
                "Invalid activity type ID: {$typeId}"
            );
        }
    }
    
    public function getTypeName($typeId)
    {
        if (!$this->isValid($typeId)) {
            return 'Unknown';
        }
        
        $type = Teamleader::activityTypes()->info($typeId);
        return $type['data']['name'] ?? 'Unknown';
    }
    
    public function isInternalType($typeId)
    {
        if (!$this->isValid($typeId)) {
            return false;
        }
        
        $type = Teamleader::activityTypes()->info($typeId);
        return $type['data']['is_internal'] ?? false;
    }
}

// Usage
$validator = new ActivityTypeValidator();

try {
    $validator->validate($userProvidedTypeId);
    // Proceed with using the type
} catch (InvalidArgumentException $e) {
    // Handle invalid type
}
```

### Event Creation Helper

```php
class EventCreationHelper
{
    protected $typeCache;
    
    public function __construct(ActivityTypeCache $typeCache)
    {
        $this->typeCache = $typeCache;
    }
    
    public function createClientMeeting(array $data)
    {
        $clientMeetingType = $this->findTypeByName('Client Meeting');
        
        if (!$clientMeetingType) {
            throw new Exception('Client Meeting activity type not found');
        }
        
        return Teamleader::events()->create(array_merge($data, [
            'activity_type_id' => $clientMeetingType['id']
        ]));
    }
    
    public function createInternalMeeting(array $data)
    {
        $internalTypes = array_filter(
            $this->typeCache->get(),
            fn($t) => $t['is_internal'] && $t['category'] === 'meeting'
        );
        
        if (empty($internalTypes)) {
            throw new Exception('No internal meeting type found');
        }
        
        $type = reset($internalTypes);
        
        return Teamleader::events()->create(array_merge($data, [
            'activity_type_id' => $type['id']
        ]));
    }
    
    public function createCall(array $data)
    {
        $callTypes = array_filter(
            $this->typeCache->get(),
            fn($t) => $t['category'] === 'call'
        );
        
        if (empty($callTypes)) {
            throw new Exception('No call type found');
        }
        
        $type = reset($callTypes);
        
        return Teamleader::calls()->create(array_merge($data, [
            'activity_type_id' => $type['id']
        ]));
    }
    
    protected function findTypeByName($name)
    {
        $types = $this->typeCache->get();
        
        foreach ($types as $type) {
            if (strcasecmp($type['name'], $name) === 0) {
                return $type;
            }
        }
        
        return null;
    }
}
```

### Activity Analytics

```php
class ActivityAnalytics
{
    public function getActivityBreakdown($startDate, $endDate)
    {
        // Get all events in date range
        $events = Teamleader::events()->betweenDates(
            $startDate,
            $endDate
        );
        
        // Get activity type definitions
        $types = Teamleader::activityTypes()->list()['data'];
        $typeMap = [];
        
        foreach ($types as $type) {
            $typeMap[$type['id']] = [
                'name' => $type['name'],
                'category' => $type['category'],
                'is_internal' => $type['is_internal'],
                'count' => 0
            ];
        }
        
        // Count events by type
        $totalEvents = count($events['data']);
        
        foreach ($events['data'] as $event) {
            if (isset($event['activity_type']['id'])) {
                $typeId = $event['activity_type']['id'];
                
                if (isset($typeMap[$typeId])) {
                    $typeMap[$typeId]['count']++;
                }
            }
        }
        
        // Calculate percentages
        foreach ($typeMap as &$type) {
            $type['percentage'] = $totalEvents > 0
                ? round(($type['count'] / $totalEvents) * 100, 2)
                : 0;
        }
        
        // Sort by count
        uasort($typeMap, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return [
            'total_events' => $totalEvents,
            'by_type' => $typeMap
        ];
    }
    
    public function getInternalVsExternal($startDate, $endDate)
    {
        $breakdown = $this->getActivityBreakdown($startDate, $endDate);
        
        $internal = 0;
        $external = 0;
        
        foreach ($breakdown['by_type'] as $type) {
            if ($type['is_internal']) {
                $internal += $type['count'];
            } else {
                $external += $type['count'];
            }
        }
        
        $total = $internal + $external;
        
        return [
            'internal' => $internal,
            'external' => $external,
            'internal_percentage' => $total > 0 
                ? round(($internal / $total) * 100, 2) 
                : 0,
            'external_percentage' => $total > 0 
                ? round(($external / $total) * 100, 2) 
                : 0
        ];
    }
}
```

## Best Practices

### 1. Cache Activity Types

Since activity types rarely change, always cache them:

```php
$activityTypes = Cache::remember('activity_types', 86400, function() {
    return Teamleader::activityTypes()->list()['data'];
});
```

### 2. Validate Type IDs Before Use

Always validate activity type IDs before using them:

```php
$validTypes = array_column(
    Teamleader::activityTypes()->list()['data'],
    'id'
);

if (!in_array($activityTypeId, $validTypes)) {
    throw new InvalidArgumentException('Invalid activity type ID');
}
```

### 3. Use Type Names for Display

Store IDs but display names to users:

```php
function getActivityTypeName($typeId, $types) {
    foreach ($types as $type) {
        if ($type['id'] === $typeId) {
            return $type['name'];
        }
    }
    return 'Unknown';
}
```

### 4. Group Types by Category

Organize types by category for better UX:

```php
$activityTypes = Teamleader::activityTypes()->list()['data'];

$grouped = [];
foreach ($activityTypes as $type) {
    $category = $type['category'] ?? 'other';
    
    if (!isset($grouped[$category])) {
        $grouped[$category] = [];
    }
    
    $grouped[$category][] = $type;
}
```

### 5. Create Lookup Maps

Build lookup maps for efficient access:

```php
$activityTypes = Teamleader::activityTypes()->list()['data'];

$typeMap = [];
foreach ($activityTypes as $type) {
    $typeMap[$type['id']] = $type;
}

// Quick lookup
$typeName = $typeMap[$typeId]['name'] ?? 'Unknown';
```

### 6. Handle Missing Types Gracefully

Always have fallbacks for missing or invalid types:

```php
function getActivityType($typeId) {
    try {
        return Teamleader::activityTypes()->info($typeId);
    } catch (Exception $e) {
        return [
            'data' => [
                'id' => $typeId,
                'name' => 'Unknown Activity Type',
                'category' => 'other'
            ]
        ];
    }
}
```

### 7. Use Default Types When Appropriate

Provide sensible defaults:

```php
function getDefaultActivityType($category = null) {
    $types = Teamleader::activityTypes()->list()['data'];
    
    // First, look for default type
    foreach ($types as $type) {
        if ($type['is_default'] ?? false) {
            if (!$category || $type['category'] === $category) {
                return $type;
            }
        }
    }
    
    // If no default, return first type of category
    if ($category) {
        foreach ($types as $type) {
            if ($type['category'] === $category) {
                return $type;
            }
        }
    }
    
    // Last resort: return first type
    return $types[0] ?? null;
}
```

## Error Handling

### Common Errors and Solutions

**Activity Type Not Found:**
```php
try {
    $type = Teamleader::activityTypes()->info('invalid-uuid');
} catch (\Exception $e) {
    // Handle: Activity type not found
    Log::warning('Activity type not found', ['id' => 'invalid-uuid']);
}
```

**Network/API Errors:**
```php
try {
    $activityTypes = Teamleader::activityTypes()->list();
} catch (\Exception $e) {
    // Fallback to cached data
    $activityTypes = Cache::get('activity_types_backup', ['data' => []]);
}
```

### Robust Error Handling Example

```php
class ActivityTypeManager
{
    public function getAllTypes()
    {
        try {
            $types = Teamleader::activityTypes()->list();
            
            // Cache successful response
            Cache::put('activity_types', $types['data'], 86400);
            
            return $types['data'];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch activity types', [
                'error' => $e->getMessage()
            ]);
            
            // Try to return cached data
            $cached = Cache::get('activity_types');
            
            if ($cached) {
                return $cached;
            }
            
            // Return empty array as last resort
            return [];
        }
    }
    
    public function getTypeSafely($typeId)
    {
        try {
            $type = Teamleader::activityTypes()->info($typeId);
            return $type['data'];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch activity type', [
                'id' => $typeId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'id' => $typeId,
                'name' => 'Unknown',
                'category' => 'other'
            ];
        }
    }
}
```

## Related Resources

- [Events](events.md) - Calendar events using activity types
- [Meetings](meetings.md) - Meeting activities
- [Calls](calls.md) - Call activities
- [Users](../users/users.md) - Activity participants

## Rate Limiting

All activity type operations consume 1 API credit per request:

- `list()`: 1 credit
- `info()`: 1 credit

Since this is a read-only resource with infrequent changes, implement caching to minimize API usage.

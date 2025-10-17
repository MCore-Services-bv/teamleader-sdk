# Call Outcomes

Manage call outcome definitions in Teamleader Focus.

## Overview

The Call Outcomes resource provides read-only access to call outcome definitions in your Teamleader account. Call outcomes are used to categorize the results of completed calls, helping track success rates and call effectiveness.

**Important:** This resource is read-only. You cannot create, update, or delete call outcomes through the API.

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

`callOutcomes`

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

Get all call outcome definitions available in your Teamleader account.

**Parameters:**
- None

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all call outcomes
$outcomes = Teamleader::callOutcomes()->list();

foreach ($outcomes['data'] as $outcome) {
    echo "{$outcome['name']}\n";
}
```

### `info()`

Get detailed information about a specific call outcome.

**Parameters:**
- `id` (string): The call outcome UUID

**Example:**
```php
$outcome = Teamleader::callOutcomes()->info('outcome-uuid');

echo "Outcome: {$outcome['data']['name']}";
```

## Response Structure

### Call Outcome Object

```php
[
    'id' => 'outcome-uuid',
    'name' => 'Successful',
    'description' => 'Call was successful and resulted in positive action',
    'color' => '#4CAF50',
    'is_positive' => true,
    'created_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-20T14:15:00+00:00'
]
```

### List Response

```php
[
    'data' => [
        [
            'id' => 'outcome-uuid-1',
            'name' => 'Successful',
            'is_positive' => true
        ],
        [
            'id' => 'outcome-uuid-2',
            'name' => 'No Answer',
            'is_positive' => false
        ],
        [
            'id' => 'outcome-uuid-3',
            'name' => 'Callback Requested',
            'is_positive' => null
        ]
    ]
]
```

## Usage Examples

### Get All Outcomes

```php
$outcomes = Teamleader::callOutcomes()->list();

echo "Available call outcomes:\n";
foreach ($outcomes['data'] as $outcome) {
    echo "- {$outcome['name']} (ID: {$outcome['id']})\n";
}
```

### Get Specific Outcome Details

```php
$outcomeId = 'outcome-uuid';
$outcome = Teamleader::callOutcomes()->info($outcomeId);

echo "Outcome: {$outcome['data']['name']}\n";
echo "Description: {$outcome['data']['description']}\n";
echo "Positive: " . ($outcome['data']['is_positive'] ? 'Yes' : 'No');
```

### Filter Positive Outcomes

```php
$outcomes = Teamleader::callOutcomes()->list();

$positiveOutcomes = array_filter($outcomes['data'], function($outcome) {
    return $outcome['is_positive'] === true;
});

echo "Positive outcomes:\n";
foreach ($positiveOutcomes as $outcome) {
    echo "- {$outcome['name']}\n";
}
```

### Build Outcome Selection for UI

```php
$outcomes = Teamleader::callOutcomes()->list();

$outcomeOptions = [];
foreach ($outcomes['data'] as $outcome) {
    $outcomeOptions[$outcome['id']] = $outcome['name'];
}

// Use in form/select
// ['outcome-uuid-1' => 'Successful', 'outcome-uuid-2' => 'No Answer', ...]
```

## Common Use Cases

### Outcome Selector Service

```php
class CallOutcomeSelector
{
    protected $outcomes = null;
    
    public function getAll()
    {
        if ($this->outcomes === null) {
            $this->outcomes = Teamleader::callOutcomes()->list()['data'];
        }
        
        return $this->outcomes;
    }
    
    public function getPositiveOutcomes()
    {
        return array_filter($this->getAll(), function($outcome) {
            return $outcome['is_positive'] === true;
        });
    }
    
    public function getNegativeOutcomes()
    {
        return array_filter($this->getAll(), function($outcome) {
            return $outcome['is_positive'] === false;
        });
    }
    
    public function getNeutralOutcomes()
    {
        return array_filter($this->getAll(), function($outcome) {
            return $outcome['is_positive'] === null;
        });
    }
    
    public function findByName($name)
    {
        $outcomes = $this->getAll();
        
        foreach ($outcomes as $outcome) {
            if (strcasecmp($outcome['name'], $name) === 0) {
                return $outcome;
            }
        }
        
        return null;
    }
    
    public function getForDropdown()
    {
        $options = [];
        
        foreach ($this->getAll() as $outcome) {
            $options[$outcome['id']] = $outcome['name'];
        }
        
        return $options;
    }
}

// Usage
$selector = new CallOutcomeSelector();
$positiveOutcomes = $selector->getPositiveOutcomes();
$dropdownOptions = $selector->getForDropdown();
```

### Call Outcome Cache

```php
class CallOutcomeCache
{
    protected $cacheKey = 'teamleader_call_outcomes';
    protected $cacheDuration = 86400; // 24 hours
    
    public function get()
    {
        return Cache::remember($this->cacheKey, $this->cacheDuration, function() {
            return Teamleader::callOutcomes()->list()['data'];
        });
    }
    
    public function getById($id)
    {
        $outcomes = $this->get();
        
        foreach ($outcomes as $outcome) {
            if ($outcome['id'] === $id) {
                return $outcome;
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
        $outcomes = $this->get();
        
        return [
            'positive' => array_filter($outcomes, fn($o) => $o['is_positive'] === true),
            'negative' => array_filter($outcomes, fn($o) => $o['is_positive'] === false),
            'neutral' => array_filter($outcomes, fn($o) => $o['is_positive'] === null)
        ];
    }
}
```

### Call Outcome Validator

```php
class CallOutcomeValidator
{
    protected $validOutcomes = null;
    
    public function isValid($outcomeId)
    {
        if ($this->validOutcomes === null) {
            $outcomes = Teamleader::callOutcomes()->list()['data'];
            $this->validOutcomes = array_column($outcomes, 'id');
        }
        
        return in_array($outcomeId, $this->validOutcomes);
    }
    
    public function validate($outcomeId)
    {
        if (!$this->isValid($outcomeId)) {
            throw new InvalidArgumentException(
                "Invalid call outcome ID: {$outcomeId}"
            );
        }
    }
    
    public function getOutcomeName($outcomeId)
    {
        if (!$this->isValid($outcomeId)) {
            return 'Unknown';
        }
        
        $outcome = Teamleader::callOutcomes()->info($outcomeId);
        return $outcome['data']['name'] ?? 'Unknown';
    }
}

// Usage
$validator = new CallOutcomeValidator();

try {
    $validator->validate($userProvidedOutcomeId);
    // Proceed with using the outcome
} catch (InvalidArgumentException $e) {
    // Handle invalid outcome
}
```

### Call Analytics with Outcomes

```php
class CallOutcomeAnalytics
{
    public function analyzeCallsBetweenDates($startDate, $endDate)
    {
        // Get all calls in date range
        $calls = Teamleader::calls()->betweenDates($startDate, $endDate);
        
        // Get outcome definitions
        $outcomes = Teamleader::callOutcomes()->list()['data'];
        $outcomeMap = [];
        
        foreach ($outcomes as $outcome) {
            $outcomeMap[$outcome['id']] = [
                'name' => $outcome['name'],
                'is_positive' => $outcome['is_positive'],
                'count' => 0
            ];
        }
        
        // Count calls by outcome
        $totalCalls = 0;
        $completedCalls = 0;
        
        foreach ($calls['data'] as $call) {
            $totalCalls++;
            
            if (isset($call['call_outcome']['id'])) {
                $completedCalls++;
                $outcomeId = $call['call_outcome']['id'];
                
                if (isset($outcomeMap[$outcomeId])) {
                    $outcomeMap[$outcomeId]['count']++;
                }
            }
        }
        
        // Calculate success rate
        $successfulCalls = 0;
        foreach ($outcomeMap as $outcome) {
            if ($outcome['is_positive'] === true) {
                $successfulCalls += $outcome['count'];
            }
        }
        
        return [
            'total_calls' => $totalCalls,
            'completed_calls' => $completedCalls,
            'success_rate' => $completedCalls > 0 
                ? round(($successfulCalls / $completedCalls) * 100, 2) 
                : 0,
            'outcomes' => $outcomeMap
        ];
    }
}
```

## Best Practices

### 1. Cache Call Outcomes

Since call outcomes rarely change, cache them to reduce API calls:

```php
$outcomes = Cache::remember('call_outcomes', 86400, function() {
    return Teamleader::callOutcomes()->list()['data'];
});
```

### 2. Validate Outcome IDs Before Use

Always validate outcome IDs before using them:

```php
$validOutcomes = array_column(
    Teamleader::callOutcomes()->list()['data'],
    'id'
);

if (!in_array($outcomeId, $validOutcomes)) {
    throw new InvalidArgumentException('Invalid outcome ID');
}
```

### 3. Use Outcome Names for Display

Store IDs but display names to users:

```php
function getOutcomeName($outcomeId, $outcomes) {
    foreach ($outcomes as $outcome) {
        if ($outcome['id'] === $outcomeId) {
            return $outcome['name'];
        }
    }
    return 'Unknown';
}
```

### 4. Group Outcomes by Type

Organize outcomes by positive/negative/neutral for better UX:

```php
$outcomes = Teamleader::callOutcomes()->list()['data'];

$grouped = [
    'positive' => [],
    'negative' => [],
    'neutral' => []
];

foreach ($outcomes as $outcome) {
    if ($outcome['is_positive'] === true) {
        $grouped['positive'][] = $outcome;
    } elseif ($outcome['is_positive'] === false) {
        $grouped['negative'][] = $outcome;
    } else {
        $grouped['neutral'][] = $outcome;
    }
}
```

### 5. Create Lookup Maps

Build lookup maps for efficient access:

```php
$outcomes = Teamleader::callOutcomes()->list()['data'];

$outcomeMap = [];
foreach ($outcomes as $outcome) {
    $outcomeMap[$outcome['id']] = $outcome;
}

// Quick lookup
$outcomeName = $outcomeMap[$outcomeId]['name'] ?? 'Unknown';
```

### 6. Handle Missing Outcomes Gracefully

Always have fallbacks for missing or invalid outcomes:

```php
function getOutcome($outcomeId) {
    try {
        return Teamleader::callOutcomes()->info($outcomeId);
    } catch (Exception $e) {
        return [
            'data' => [
                'id' => $outcomeId,
                'name' => 'Unknown Outcome',
                'is_positive' => null
            ]
        ];
    }
}
```

## Error Handling

### Common Errors and Solutions

**Outcome Not Found:**
```php
try {
    $outcome = Teamleader::callOutcomes()->info('invalid-uuid');
} catch (\Exception $e) {
    // Handle: Outcome not found
    Log::warning('Call outcome not found', ['id' => 'invalid-uuid']);
}
```

**Network/API Errors:**
```php
try {
    $outcomes = Teamleader::callOutcomes()->list();
} catch (\Exception $e) {
    // Fallback to cached data or default outcomes
    $outcomes = Cache::get('call_outcomes_backup', ['data' => []]);
}
```

### Robust Error Handling Example

```php
class CallOutcomeManager
{
    public function getAllOutcomes()
    {
        try {
            $outcomes = Teamleader::callOutcomes()->list();
            
            // Cache successful response
            Cache::put('call_outcomes', $outcomes['data'], 86400);
            
            return $outcomes['data'];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch call outcomes', [
                'error' => $e->getMessage()
            ]);
            
            // Try to return cached data
            $cached = Cache::get('call_outcomes');
            
            if ($cached) {
                return $cached;
            }
            
            // Return empty array as last resort
            return [];
        }
    }
    
    public function getOutcomeSafely($outcomeId)
    {
        try {
            $outcome = Teamleader::callOutcomes()->info($outcomeId);
            return $outcome['data'];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch call outcome', [
                'id' => $outcomeId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'id' => $outcomeId,
                'name' => 'Unknown',
                'is_positive' => null
            ];
        }
    }
}
```

## Related Resources

- [Calls](calls.md) - Call activities that use outcomes
- [Events](events.md) - General calendar events
- [Activity Types](activity-types.md) - Activity categorization

## Rate Limiting

All call outcome operations consume 1 API credit per request:

- `list()`: 1 credit
- `info()`: 1 credit

Since this is a read-only resource with infrequent changes, implement caching to minimize API usage.

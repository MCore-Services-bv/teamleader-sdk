# Lost Reasons

Manage lost reasons for deals in Teamleader Focus.

## Overview

The Lost Reasons resource provides read-only access to the reasons why deals are marked as lost in your Teamleader account. Tracking why deals are lost helps identify common objections, improve your sales process, and understand competitive weaknesses.

**Important:** The Lost Reasons resource is read-only. You cannot create, update, or delete lost reasons through the API. Lost reasons must be managed through the Teamleader interface.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`lostReasons`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported (ids only)
- **Sorting**: ✅ Supported (by name only)
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get all lost reasons with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Sorting and pagination options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all lost reasons
$lostReasons = Teamleader::lostReasons()->list();

// Get specific lost reasons by ID
$lostReasons = Teamleader::lostReasons()->list([
    'ids' => ['reason-uuid-1', 'reason-uuid-2']
]);

// With pagination
$lostReasons = Teamleader::lostReasons()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Sorted alphabetically
$lostReasons = Teamleader::lostReasons()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);
```

## Helper Methods

The Lost Reasons resource provides convenient helper methods:

### `all()`

Get all lost reasons sorted alphabetically.

```php
$lostReasons = Teamleader::lostReasons()->all();
```

### `byIds()`

Get specific lost reasons by their UUIDs.

```php
$lostReasons = Teamleader::lostReasons()->byIds([
    'reason-uuid-1',
    'reason-uuid-2'
]);
```

### `exists()`

Check if a lost reason exists by ID.

```php
$exists = Teamleader::lostReasons()->exists('reason-uuid');
// Returns: true or false
```

### `getName()`

Get the name of a lost reason by ID.

```php
$name = Teamleader::lostReasons()->getName('reason-uuid');
// Returns: 'Price too high' or null if not found
```

### `getSelectOptions()`

Get lost reasons formatted for form dropdowns.

```php
$options = Teamleader::lostReasons()->getSelectOptions();
// Returns: [
//     ['value' => 'uuid1', 'label' => 'Price too high'],
//     ['value' => 'uuid2', 'label' => 'Competitor chosen'],
//     ...
// ]
```

### `getStats()`

Get statistics about available lost reasons.

```php
$stats = Teamleader::lostReasons()->getStats();
// Returns: [
//     'total_count' => 5,
//     'names' => ['Price too high', 'Competitor chosen', ...],
//     'ids' => ['uuid1', 'uuid2', ...]
// ]
```

## Filters

### Available Filters

#### `ids`
Filter by specific lost reason UUIDs.

```php
$lostReasons = Teamleader::lostReasons()->list([
    'ids' => ['reason-uuid-1', 'reason-uuid-2']
]);
```

## Sorting

Lost reasons can only be sorted by name:

```php
// Sort ascending (A-Z)
$lostReasons = Teamleader::lostReasons()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);

// Sort descending (Z-A)
$lostReasons = Teamleader::lostReasons()->list([], [
    'sort' => [['field' => 'name', 'order' => 'desc']]
]);
```

## Response Structure

### Lost Reason Object

```php
[
    'id' => 'reason-uuid',
    'name' => 'Price too high'
]
```

**Note:** Lost reasons only contain ID and name. There is no additional metadata.

### Common Lost Reasons

Typical lost reasons in Teamleader might include:

- Price too high
- Competitor chosen
- Budget not available
- Timeline too long
- Feature requirements not met
- No response from prospect
- Project cancelled
- Internal solution chosen

## Usage Examples

### Get All Lost Reasons

```php
$allReasons = Teamleader::lostReasons()->all();

foreach ($allReasons['data'] as $reason) {
    echo "{$reason['name']} ({$reason['id']})\n";
}
```

### Create Dropdown for Lost Deal Form

```php
function getLostReasonDropdown()
{
    $reasons = Teamleader::lostReasons()->getSelectOptions();
    
    return view('deals.mark-lost', [
        'lost_reasons' => $reasons
    ]);
}

// In Blade template:
// <select name="reason_id">
//     @foreach($lost_reasons as $reason)
//         <option value="{{ $reason['value'] }}">{{ $reason['label'] }}</option>
//     @endforeach
// </select>
```

### Mark Deal as Lost with Reason

```php
function markDealAsLost($dealId, $reasonId, $notes = null)
{
    // Validate reason exists
    if (!Teamleader::lostReasons()->exists($reasonId)) {
        throw new \InvalidArgumentException('Invalid lost reason');
    }
    
    // Mark deal as lost
    $result = Teamleader::deals()->lose($dealId, $reasonId, $notes);
    
    // Log for analytics
    Log::info('Deal marked as lost', [
        'deal_id' => $dealId,
        'reason' => Teamleader::lostReasons()->getName($reasonId),
        'notes' => $notes,
        'user' => auth()->user()->id
    ]);
    
    return $result;
}
```

### Get Reason Name for Display

```php
$deal = Teamleader::deals()->info('deal-uuid');

if ($deal['data']['status'] === 'lost' && isset($deal['data']['lost_reason'])) {
    $reasonName = Teamleader::lostReasons()->getName($deal['data']['lost_reason']['id']);
    echo "Lost because: {$reasonName}";
}
```

## Common Use Cases

### 1. Lost Reason Analysis

```php
function analyzeLostReasons($startDate, $endDate)
{
    $lostReasons = Teamleader::lostReasons()->all();
    $analysis = [];
    
    foreach ($lostReasons['data'] as $reason) {
        // Get lost deals for this reason
        $lostDeals = Teamleader::deals()->lost([
            'lost_reason_id' => $reason['id'],
            'estimated_closing_date_from' => $startDate,
            'estimated_closing_date_to' => $endDate
        ]);
        
        $lostValue = array_reduce($lostDeals['data'], function($carry, $deal) {
            return $carry + $deal['estimated_value']['amount'];
        }, 0);
        
        $analysis[] = [
            'reason' => $reason['name'],
            'count' => count($lostDeals['data']),
            'lost_value' => $lostValue,
            'percentage' => 0 // Will calculate after getting total
        ];
    }
    
    // Calculate percentages
    $totalLost = array_sum(array_column($analysis, 'count'));
    foreach ($analysis as &$item) {
        $item['percentage'] = $totalLost > 0 
            ? ($item['count'] / $totalLost) * 100 
            : 0;
    }
    
    // Sort by count
    usort($analysis, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    return $analysis;
}
```

### 2. Cache Lost Reasons

```php
use Illuminate\Support\Facades\Cache;

class LostReasonService
{
    public function getCachedReasons()
    {
        return Cache::remember('lost_reasons', 3600, function() {
            return Teamleader::lostReasons()->all();
        });
    }
    
    public function getReasonName($reasonId)
    {
        $reasons = $this->getCachedReasons();
        
        foreach ($reasons['data'] as $reason) {
            if ($reason['id'] === $reasonId) {
                return $reason['name'];
            }
        }
        
        return 'Unknown';
    }
}
```

### 3. Competitive Loss Analysis

```php
function analyzeCompetitiveLosses()
{
    $lostReasons = Teamleader::lostReasons()->all();
    
    // Find competitor-related reasons
    $competitorReasons = array_filter($lostReasons['data'], function($reason) {
        return stripos($reason['name'], 'competitor') !== false;
    });
    
    $competitorLosses = [];
    
    foreach ($competitorReasons as $reason) {
        $lostDeals = Teamleader::deals()->lost([
            'lost_reason_id' => $reason['id']
        ]);
        
        $competitorLosses[] = [
            'reason' => $reason['name'],
            'deals_lost' => count($lostDeals['data']),
            'value_lost' => array_reduce($lostDeals['data'], function($carry, $deal) {
                return $carry + $deal['estimated_value']['amount'];
            }, 0)
        ];
    }
    
    return $competitorLosses;
}
```

### 4. Lost Deal Recovery Opportunity

```php
function findRecoveryOpportunities()
{
    $lostReasons = Teamleader::lostReasons()->all();
    $recoverable = [];
    
    // Reasons that might be recoverable
    $recoverableReasons = ['Price too high', 'Timeline too long', 'Budget not available'];
    
    foreach ($lostReasons['data'] as $reason) {
        if (in_array($reason['name'], $recoverableReasons)) {
            $lostDeals = Teamleader::deals()->lost([
                'lost_reason_id' => $reason['id'],
                'estimated_closing_date_from' => date('Y-m-d', strtotime('-90 days'))
            ]);
            
            $recoverable[] = [
                'reason' => $reason['name'],
                'recent_losses' => count($lostDeals['data']),
                'deals' => $lostDeals['data']
            ];
        }
    }
    
    return $recoverable;
}
```

### 5. Monthly Lost Reason Trends

```php
function analyzeLostReasonTrends($months = 6)
{
    $lostReasons = Teamleader::lostReasons()->all();
    $trends = [];
    
    foreach ($lostReasons['data'] as $reason) {
        $monthlyData = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = date('Y-m-01', strtotime("-{$i} months"));
            $endDate = date('Y-m-t', strtotime("-{$i} months"));
            
            $lostDeals = Teamleader::deals()->lost([
                'lost_reason_id' => $reason['id'],
                'estimated_closing_date_from' => $startDate,
                'estimated_closing_date_to' => $endDate
            ]);
            
            $monthlyData[] = [
                'month' => date('Y-m', strtotime($startDate)),
                'losses' => count($lostDeals['data'])
            ];
        }
        
        // Only include reasons with losses
        $totalLosses = array_sum(array_column($monthlyData, 'losses'));
        if ($totalLosses > 0) {
            $trends[] = [
                'reason' => $reason['name'],
                'monthly_data' => $monthlyData,
                'total_losses' => $totalLosses,
                'average_per_month' => $totalLosses / $months
            ];
        }
    }
    
    // Sort by total losses
    usort($trends, function($a, $b) {
        return $b['total_losses'] - $a['total_losses'];
    });
    
    return $trends;
}
```

## Best Practices

### 1. Cache Lost Reasons

Since lost reasons don't change frequently, caching them improves performance:

```php
// Good: Cache for 1 hour
$reasons = Cache::remember('lost_reasons', 3600, function() {
    return Teamleader::lostReasons()->all();
});

// Bad: Fetch on every request
$reasons = Teamleader::lostReasons()->all();
```

### 2. Always Validate Lost Reasons

```php
// Good: Validate before using
function markDealLost($dealId, $reasonId, $notes)
{
    if (!Teamleader::lostReasons()->exists($reasonId)) {
        throw new \InvalidArgumentException('Invalid lost reason ID');
    }
    
    return Teamleader::deals()->lose($dealId, $reasonId, $notes);
}

// Bad: No validation
Teamleader::deals()->lose($dealId, $reasonId, $notes);
```

### 3. Track Additional Context

```php
// Good: Log additional information
function trackLostDeal($dealId, $reasonId, $extraInfo)
{
    $result = Teamleader::deals()->lose($dealId, $reasonId, $extraInfo);
    
    // Store additional analytics
    DB::table('lost_deal_analytics')->insert([
        'deal_id' => $dealId,
        'reason_id' => $reasonId,
        'reason_name' => Teamleader::lostReasons()->getName($reasonId),
        'extra_info' => $extraInfo,
        'sales_rep' => auth()->user()->id,
        'lost_at' => now()
    ]);
    
    return $result;
}
```

### 4. Regular Lost Reason Analysis

```php
// Good: Schedule regular analysis
function scheduleWeeklyLostAnalysis()
{
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
    
    $analysis = analyzeLostReasons($startDate, $endDate);
    
    // Send report to management
    Mail::to('sales@company.com')->send(
        new WeeklyLostReasonReport($analysis)
    );
    
    // Store for historical tracking
    DB::table('weekly_loss_reports')->insert([
        'week' => date('Y-W'),
        'report' => json_encode($analysis),
        'created_at' => now()
    ]);
}
```

### 5. Create Actionable Insights

```php
// Good: Turn data into actions
function generateLostReasonActionItems()
{
    $analysis = analyzeLostReasons(
        date('Y-m-d', strtotime('-30 days')),
        date('Y-m-d')
    );
    
    $actions = [];
    
    foreach ($analysis as $item) {
        if ($item['reason'] === 'Price too high' && $item['count'] > 5) {
            $actions[] = [
                'type' => 'pricing',
                'priority' => 'high',
                'action' => 'Review pricing strategy - lost ' . $item['count'] . ' deals',
                'value_at_risk' => $item['lost_value']
            ];
        }
        
        if ($item['reason'] === 'Competitor chosen' && $item['percentage'] > 30) {
            $actions[] = [
                'type' => 'competitive',
                'priority' => 'high',
                'action' => 'Conduct competitive analysis - ' . round($item['percentage']) . '% of losses',
                'value_at_risk' => $item['lost_value']
            ];
        }
    }
    
    return $actions;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $lostReasons = Teamleader::lostReasons()->list();
} catch (TeamleaderException $e) {
    Log::error('Error fetching lost reasons', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    return ['data' => []];
}
```

## Limitations

1. **Read-Only**: You cannot create, update, or delete lost reasons via the API
2. **Limited Filtering**: Can only filter by IDs, not by name
3. **No Usage Statistics**: The API doesn't return how many deals have each lost reason
4. **Name Only**: Lost reasons only have name field, no description or categorization

```php
// Cannot do this:
// Teamleader::lostReasons()->create(['name' => 'New Reason']); // ❌ Not supported
// Teamleader::lostReasons()->update('uuid', ['name' => 'Updated']); // ❌ Not supported
// Teamleader::lostReasons()->delete('uuid'); // ❌ Not supported

// Can only do this:
Teamleader::lostReasons()->list(); // ✅ Supported
Teamleader::lostReasons()->list(['ids' => ['uuid']]); // ✅ Supported
```

## Related Resources

- [Deals](deals.md) - Deals can be marked as lost with reasons
- [Deal Sources](deal_sources.md) - Another tracking dimension for deals

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

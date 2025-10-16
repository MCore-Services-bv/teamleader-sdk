# Deal Sources

Manage deal sources in Teamleader Focus.

## Overview

The Deal Sources resource provides read-only access to deal sources in your Teamleader account. Deal sources help you track where your deals come from (e.g., website, referral, trade show, cold calling), allowing you to analyze which marketing channels are most effective.

**Important:** The Deal Sources resource is read-only. You cannot create, update, or delete deal sources through the API. Deal sources must be managed through the Teamleader interface.

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

`dealSources`

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

Get all deal sources with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Sorting and pagination options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all deal sources
$sources = Teamleader::dealSources()->list();

// Get specific sources by ID
$sources = Teamleader::dealSources()->list([
    'ids' => ['source-uuid-1', 'source-uuid-2']
]);

// With pagination
$sources = Teamleader::dealSources()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Sorted alphabetically
$sources = Teamleader::dealSources()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);
```

## Helper Methods

The Deal Sources resource provides convenient helper methods:

### `all()`

Get all deal sources sorted alphabetically.

```php
$sources = Teamleader::dealSources()->all();
```

### `byIds()`

Get specific deal sources by their UUIDs.

```php
$sources = Teamleader::dealSources()->byIds([
    'source-uuid-1',
    'source-uuid-2'
]);
```

### `exists()`

Check if a deal source exists by ID.

```php
$exists = Teamleader::dealSources()->exists('source-uuid');
// Returns: true or false
```

### `getName()`

Get the name of a deal source by ID.

```php
$name = Teamleader::dealSources()->getName('source-uuid');
// Returns: 'Website' or null if not found
```

### `getSelectOptions()`

Get deal sources formatted for form dropdowns.

```php
$options = Teamleader::dealSources()->getSelectOptions();
// Returns: [
//     ['value' => 'uuid1', 'label' => 'Website'],
//     ['value' => 'uuid2', 'label' => 'Referral'],
//     ...
// ]
```

### `getStatistics()`

Get statistics about available deal sources.

```php
$stats = Teamleader::dealSources()->getStatistics();
// Returns: [
//     'total_sources' => 5,
//     'sources' => [
//         ['id' => 'uuid', 'name' => 'Website', 'name_length' => 7],
//         ...
//     ]
// ]
```

## Filters

### Available Filters

#### `ids`
Filter by specific deal source UUIDs.

```php
$sources = Teamleader::dealSources()->list([
    'ids' => ['source-uuid-1', 'source-uuid-2']
]);
```

## Sorting

Deal sources can only be sorted by name:

```php
// Sort ascending (A-Z)
$sources = Teamleader::dealSources()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);

// Sort descending (Z-A)
$sources = Teamleader::dealSources()->list([], [
    'sort' => [['field' => 'name', 'order' => 'desc']]
]);
```

## Response Structure

### Deal Source Object

```php
[
    'id' => 'source-uuid',
    'name' => 'Website'
]
```

**Note:** Deal sources only contain ID and name. There is no additional metadata.

## Usage Examples

### Get All Sources

```php
$allSources = Teamleader::dealSources()->all();

foreach ($allSources['data'] as $source) {
    echo "{$source['name']} ({$source['id']})\n";
}
```

### Create Dropdown for Deal Form

```php
function getDealSourceDropdown()
{
    $sources = Teamleader::dealSources()->getSelectOptions();
    
    return view('deals.create', [
        'sources' => $sources
    ]);
}

// In Blade template:
// <select name="source_id">
//     @foreach($sources as $source)
//         <option value="{{ $source['value'] }}">{{ $source['label'] }}</option>
//     @endforeach
// </select>
```

### Validate Source Before Creating Deal

```php
function createDealWithValidation($data)
{
    // Validate source exists
    if (!Teamleader::dealSources()->exists($data['source_id'])) {
        throw new \InvalidArgumentException('Invalid deal source');
    }
    
    return Teamleader::deals()->create($data);
}
```

### Get Source Name for Display

```php
$deal = Teamleader::deals()->info('deal-uuid');

$sourceName = Teamleader::dealSources()->getName($deal['data']['source']['id']);

echo "Deal source: {$sourceName}";
```

## Common Use Cases

### 1. Source Attribution Report

```php
function generateSourceAttributionReport($startDate, $endDate)
{
    $sources = Teamleader::dealSources()->all();
    $report = [];
    
    foreach ($sources['data'] as $source) {
        // Get won deals for this source
        $wonDeals = Teamleader::deals()->won([
            'source_id' => $source['id'],
            'estimated_closing_date_from' => $startDate,
            'estimated_closing_date_to' => $endDate
        ]);
        
        $totalValue = array_reduce($wonDeals['data'], function($carry, $deal) {
            return $carry + $deal['estimated_value']['amount'];
        }, 0);
        
        $report[] = [
            'source' => $source['name'],
            'deals_won' => count($wonDeals['data']),
            'total_value' => $totalValue,
            'average_deal_size' => count($wonDeals['data']) > 0 
                ? $totalValue / count($wonDeals['data']) 
                : 0
        ];
    }
    
    // Sort by total value
    usort($report, function($a, $b) {
        return $b['total_value'] - $a['total_value'];
    });
    
    return $report;
}
```

### 2. Cache Sources for Performance

```php
use Illuminate\Support\Facades\Cache;

class DealSourceService
{
    public function getCachedSources()
    {
        return Cache::remember('deal_sources', 3600, function() {
            return Teamleader::dealSources()->all();
        });
    }
    
    public function getSourceName($sourceId)
    {
        $sources = $this->getCachedSources();
        
        foreach ($sources['data'] as $source) {
            if ($source['id'] === $sourceId) {
                return $source['name'];
            }
        }
        
        return 'Unknown';
    }
}
```

### 3. Source Performance Comparison

```php
function compareSourcePerformance()
{
    $sources = Teamleader::dealSources()->all();
    $comparison = [];
    
    foreach ($sources['data'] as $source) {
        $openDeals = Teamleader::deals()->open([
            'source_id' => $source['id']
        ]);
        
        $wonDeals = Teamleader::deals()->won([
            'source_id' => $source['id']
        ]);
        
        $lostDeals = Teamleader::deals()->lost([
            'source_id' => $source['id']
        ]);
        
        $totalClosed = count($wonDeals['data']) + count($lostDeals['data']);
        
        $comparison[] = [
            'source' => $source['name'],
            'open' => count($openDeals['data']),
            'won' => count($wonDeals['data']),
            'lost' => count($lostDeals['data']),
            'win_rate' => $totalClosed > 0 
                ? (count($wonDeals['data']) / $totalClosed) * 100 
                : 0
        ];
    }
    
    return $comparison;
}
```

### 4. Marketing ROI Calculator

```php
function calculateMarketingROI($sourceId, $marketingCost)
{
    $sourceName = Teamleader::dealSources()->getName($sourceId);
    
    $wonDeals = Teamleader::deals()->won([
        'source_id' => $sourceId
    ]);
    
    $totalRevenue = array_reduce($wonDeals['data'], function($carry, $deal) {
        return $carry + $deal['estimated_value']['amount'];
    }, 0);
    
    $roi = $marketingCost > 0 
        ? (($totalRevenue - $marketingCost) / $marketingCost) * 100 
        : 0;
    
    return [
        'source' => $sourceName,
        'marketing_cost' => $marketingCost,
        'total_revenue' => $totalRevenue,
        'deal_count' => count($wonDeals['data']),
        'roi_percentage' => $roi,
        'cost_per_deal' => count($wonDeals['data']) > 0 
            ? $marketingCost / count($wonDeals['data']) 
            : 0
    ];
}
```

### 5. Source Trend Analysis

```php
function analyzeSourceTrends($months = 6)
{
    $sources = Teamleader::dealSources()->all();
    $trends = [];
    
    foreach ($sources['data'] as $source) {
        $monthlyData = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = date('Y-m-01', strtotime("-{$i} months"));
            $endDate = date('Y-m-t', strtotime("-{$i} months"));
            
            $wonDeals = Teamleader::deals()->won([
                'source_id' => $source['id'],
                'estimated_closing_date_from' => $startDate,
                'estimated_closing_date_to' => $endDate
            ]);
            
            $monthlyData[] = [
                'month' => date('Y-m', strtotime($startDate)),
                'deals' => count($wonDeals['data'])
            ];
        }
        
        $trends[] = [
            'source' => $source['name'],
            'monthly_data' => $monthlyData,
            'average_per_month' => array_sum(array_column($monthlyData, 'deals')) / $months
        ];
    }
    
    return $trends;
}
```

## Best Practices

### 1. Cache Deal Sources

Since sources don't change frequently, caching them improves performance:

```php
// Good: Cache for 1 hour
$sources = Cache::remember('deal_sources', 3600, function() {
    return Teamleader::dealSources()->all();
});

// Bad: Fetch on every request
$sources = Teamleader::dealSources()->all();
```

### 2. Use Helper Methods for Validation

```php
// Good: Use exists() method
if (Teamleader::dealSources()->exists($sourceId)) {
    // Proceed with deal creation
}

// Less efficient: Fetch all and check
$sources = Teamleader::dealSources()->list();
$exists = in_array($sourceId, array_column($sources['data'], 'id'));
```

### 3. Create Dropdown Options Efficiently

```php
// Good: Use getSelectOptions() helper
$options = Teamleader::dealSources()->getSelectOptions();

// Less efficient: Manual transformation
$sources = Teamleader::dealSources()->list();
$options = array_map(function($source) {
    return ['value' => $source['id'], 'label' => $source['name']];
}, $sources['data']);
```

### 4. Handle Missing Sources Gracefully

```php
// Good: Provide fallback
$sourceName = Teamleader::dealSources()->getName($sourceId) ?? 'Unknown Source';

// Bad: No fallback
$sourceName = Teamleader::dealSources()->getName($sourceId);
// Could be null
```

### 5. Track Source Effectiveness

```php
// Good: Regular analysis
function scheduleSourceAnalysis()
{
    // Run weekly to identify best-performing sources
    $report = generateSourceAttributionReport(
        date('Y-m-d', strtotime('-7 days')),
        date('Y-m-d')
    );
    
    // Store for historical comparison
    DB::table('source_performance_history')->insert([
        'week' => date('Y-W'),
        'report' => json_encode($report),
        'created_at' => now()
    ]);
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $sources = Teamleader::dealSources()->list();
} catch (TeamleaderException $e) {
    Log::error('Error fetching deal sources', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    return ['data' => []];
}
```

## Limitations

1. **Read-Only**: You cannot create, update, or delete deal sources via the API
2. **Limited Filtering**: Can only filter by IDs, not by name or other criteria
3. **No Individual Info**: No dedicated `info()` method; use `list()` with ID filter
4. **Name Only**: Deal sources only have name field, no description or additional metadata
5. **No Usage Statistics**: The API doesn't return how many deals use each source

```php
// Cannot do this:
// Teamleader::dealSources()->create(['name' => 'LinkedIn']); // ❌ Not supported
// Teamleader::dealSources()->update('uuid', ['name' => 'New Name']); // ❌ Not supported
// Teamleader::dealSources()->delete('uuid'); // ❌ Not supported

// Can only do this:
Teamleader::dealSources()->list(); // ✅ Supported
Teamleader::dealSources()->list(['ids' => ['uuid']]); // ✅ Supported
```

## Related Resources

- [Deals](deals.md) - Deals have sources
- [Lost Reasons](lost_reasons.md) - Another tracking dimension for deals

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

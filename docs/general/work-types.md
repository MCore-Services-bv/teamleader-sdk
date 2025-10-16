# Work Types

Manage work types in Teamleader Focus.

## Overview

The Work Types resource provides read-only access to work type definitions in your Teamleader account. Work types are used to categorize time tracking entries and can be associated with hourly rates for billing purposes.

**Important:** The Work Types resource is read-only. You cannot create, update, or delete work types through the API. Work types must be managed through the Teamleader interface.

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

`workTypes`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of work types with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Sorting and pagination options

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all work types
$workTypes = Teamleader::workTypes()->list();

// Search work types by name
$workTypes = Teamleader::workTypes()->list([
    'term' => 'design'
]);

// Get work types with pagination
$workTypes = Teamleader::workTypes()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Get work types sorted by name
$workTypes = Teamleader::workTypes()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);
```

**Note:** The Work Types resource does not support the `info()` method. Use `list()` with the `ids` filter to get specific work types.

## Helper Methods

The Work Types resource provides convenient helper methods for common operations:

### `search()`

Search work types by name.

```php
// Search for work types containing "development"
$workTypes = Teamleader::workTypes()->search('development');

// Search for work types containing "consulting"
$workTypes = Teamleader::workTypes()->search('consulting');
```

### `byIds()`

Get specific work types by their UUIDs.

```php
$workTypes = Teamleader::workTypes()->byIds([
    'work-type-uuid-1',
    'work-type-uuid-2'
]);
```

### `sortedByName()`

Get work types sorted by name.

```php
// Sort ascending (A-Z)
$workTypes = Teamleader::workTypes()->sortedByName('asc');

// Sort descending (Z-A)
$workTypes = Teamleader::workTypes()->sortedByName('desc');
```

### `paginate()`

Get work types with custom pagination.

```php
// Get first page with 50 items
$workTypes = Teamleader::workTypes()->paginate(50, 1);

// Get second page with 50 items
$workTypes = Teamleader::workTypes()->paginate(50, 2);
```

## Filters

### Available Filters

#### `ids`
Filter by specific work type UUIDs.

```php
$workTypes = Teamleader::workTypes()->list([
    'ids' => ['work-type-uuid-1', 'work-type-uuid-2']
]);
```

#### `term`
Search filter on work type name only.

```php
// Find work types with "design" in the name
$workTypes = Teamleader::workTypes()->list([
    'term' => 'design'
]);

// Find work types with "development" in the name
$workTypes = Teamleader::workTypes()->list([
    'term' => 'development'
]);
```

## Sorting

### Available Sort Fields

- `name` - Sort by work type name (alphabetically)

### Sort Examples

```php
// Sort by name (ascending)
$workTypes = Teamleader::workTypes()->list([], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);

// Sort by name (descending)
$workTypes = Teamleader::workTypes()->list([], [
    'sort' => [['field' => 'name', 'order' => 'desc']]
]);

// Get available sort fields
$sortFields = Teamleader::workTypes()->getAvailableSortFields();
```

## Response Structure

### Work Type Object

```php
[
    'id' => 'work-type-uuid',
    'name' => 'Development'
]
```

## Usage Examples

### Get All Work Types

```php
$workTypes = Teamleader::workTypes()->list();

foreach ($workTypes['data'] as $workType) {
    echo $workType['name'] . PHP_EOL;
}
```

### Search for Work Types

```php
// Search for design work types
$designWork = Teamleader::workTypes()->search('design');

// Search for consulting work types
$consultingWork = Teamleader::workTypes()->search('consulting');
```

### Build a Work Type Dropdown

```php
$workTypes = Teamleader::workTypes()->sortedByName('asc');

$dropdown = [];
foreach ($workTypes['data'] as $workType) {
    $dropdown[$workType['id']] = $workType['name'];
}

// Use in Blade template
<select name="work_type_id">
    @foreach($dropdown as $id => $name)
        <option value="{{ $id }}">{{ $name }}</option>
    @endforeach
</select>
```

### Find Work Type by Name

```php
function findWorkTypeByName($searchName)
{
    $workTypes = Teamleader::workTypes()->search($searchName);
    
    foreach ($workTypes['data'] as $workType) {
        if (strcasecmp($workType['name'], $searchName) === 0) {
            return $workType;
        }
    }
    
    return null;
}

$developmentType = findWorkTypeByName('Development');
```

### Get Specific Work Types

```php
// Get multiple work types by their IDs
$selectedTypes = Teamleader::workTypes()->byIds([
    'uuid-1',
    'uuid-2',
    'uuid-3'
]);
```

### Paginate Through All Work Types

```php
$allWorkTypes = [];
$page = 1;
$pageSize = 100;

do {
    $response = Teamleader::workTypes()->paginate($pageSize, $page);
    
    $allWorkTypes = array_merge($allWorkTypes, $response['data']);
    $hasMore = count($response['data']) === $pageSize;
    $page++;
    
} while ($hasMore);
```

## Common Use Cases

### Time Tracking Category Selection

```php
class TimeEntryController extends Controller
{
    public function create()
    {
        $workTypes = Teamleader::workTypes()->sortedByName('asc');
        
        return view('time-entries.create', [
            'workTypes' => $workTypes['data']
        ]);
    }
}
```

### Cache Work Types

```php
use Illuminate\Support\Facades\Cache;

class WorkTypeService
{
    public function getAllWorkTypes()
    {
        return Cache::remember('all_work_types', 7200, function() {
            return Teamleader::workTypes()->sortedByName('asc');
        });
    }
    
    public function getWorkTypeById($workTypeId)
    {
        $allTypes = $this->getAllWorkTypes();
        
        return collect($allTypes['data'])->firstWhere('id', $workTypeId);
    }
    
    public function searchWorkTypes($term)
    {
        $cacheKey = "work_types.search.{$term}";
        
        return Cache::remember($cacheKey, 3600, function() use ($term) {
            return Teamleader::workTypes()->search($term);
        });
    }
}
```

### Sync Work Types to Local Database

```php
use App\Models\WorkType;
use Illuminate\Console\Command;

class SyncWorkTypesCommand extends Command
{
    protected $signature = 'teamleader:sync-work-types';
    
    public function handle()
    {
        $this->info('Syncing work types...');
        
        $page = 1;
        
        do {
            $response = Teamleader::workTypes()->paginate(100, $page);
            
            foreach ($response['data'] as $workTypeData) {
                WorkType::updateOrCreate(
                    ['teamleader_id' => $workTypeData['id']],
                    ['name' => $workTypeData['name']]
                );
            }
            
            $hasMore = count($response['data']) === 100;
            $page++;
            
        } while ($hasMore);
        
        $this->info('Work types synced successfully!');
    }
}
```

### Work Type Validation

```php
class WorkTypeValidator
{
    public function isValidWorkType($workTypeId): bool
    {
        $workTypes = Teamleader::workTypes()->byIds([$workTypeId]);
        return !empty($workTypes['data']);
    }
    
    public function getWorkTypeName($workTypeId): ?string
    {
        $workTypes = Teamleader::workTypes()->byIds([$workTypeId]);
        return $workTypes['data'][0]['name'] ?? null;
    }
}
```

### Time Entry Categorization

```php
class TimeEntryService
{
    public function createTimeEntry($userId, $projectId, $workTypeId, $hours, $description)
    {
        // Validate work type
        $workType = $this->getWorkType($workTypeId);
        
        if (!$workType) {
            throw new \InvalidArgumentException('Invalid work type');
        }
        
        // Create time entry with work type
        return Teamleader::timeTracking()->add([
            'user_id' => $userId,
            'project_id' => $projectId,
            'work_type_id' => $workTypeId,
            'duration' => $hours * 3600, // Convert to seconds
            'description' => $description
        ]);
    }
    
    private function getWorkType($workTypeId)
    {
        $workTypes = Teamleader::workTypes()->byIds([$workTypeId]);
        return $workTypes['data'][0] ?? null;
    }
}
```

### Work Type Statistics

```php
class WorkTypeStatistics
{
    public function getMostUsedWorkTypes($startDate, $endDate)
    {
        // Get all time entries in date range
        $timeEntries = $this->getTimeEntriesForPeriod($startDate, $endDate);
        
        // Count usage by work type
        $workTypeUsage = [];
        foreach ($timeEntries as $entry) {
            $workTypeId = $entry['work_type_id'];
            
            if (!isset($workTypeUsage[$workTypeId])) {
                $workTypeUsage[$workTypeId] = [
                    'count' => 0,
                    'total_hours' => 0
                ];
            }
            
            $workTypeUsage[$workTypeId]['count']++;
            $workTypeUsage[$workTypeId]['total_hours'] += $entry['duration'] / 3600;
        }
        
        // Get work type names
        $workTypeIds = array_keys($workTypeUsage);
        $workTypes = Teamleader::workTypes()->byIds($workTypeIds);
        
        // Combine data
        $statistics = [];
        foreach ($workTypes['data'] as $workType) {
            $statistics[] = [
                'name' => $workType['name'],
                'usage_count' => $workTypeUsage[$workType['id']]['count'],
                'total_hours' => $workTypeUsage[$workType['id']]['total_hours']
            ];
        }
        
        // Sort by usage
        usort($statistics, function($a, $b) {
            return $b['usage_count'] <=> $a['usage_count'];
        });
        
        return $statistics;
    }
}
```

### Dynamic Form Generation

```php
class TimeTrackingFormBuilder
{
    public function buildForm()
    {
        $workTypes = Teamleader::workTypes()->sortedByName('asc');
        
        $formData = [
            'work_type_options' => []
        ];
        
        foreach ($workTypes['data'] as $workType) {
            $formData['work_type_options'][] = [
                'value' => $workType['id'],
                'label' => $workType['name']
            ];
        }
        
        return $formData;
    }
}
```

## Best Practices

### 1. Cache Work Type Data

Work types change infrequently, so cache them aggressively:

```php
// Good: Cache for 2 hours
$workTypes = Cache::remember('all_work_types', 7200, function() {
    return Teamleader::workTypes()->list();
});

// Bad: Fetching on every request
$workTypes = Teamleader::workTypes()->list();
```

### 2. Use Helper Methods

```php
// Good: Use search method
$workTypes = Teamleader::workTypes()->search('design');

// Less ideal: Manual filtering
$allTypes = Teamleader::workTypes()->list();
$filtered = array_filter($allTypes['data'], function($type) {
    return stripos($type['name'], 'design') !== false;
});
```

### 3. Sort Results for User-Facing Lists

```php
// Good: Sorted alphabetically
$workTypes = Teamleader::workTypes()->sortedByName('asc');

// Bad: Unsorted (inconsistent order)
$workTypes = Teamleader::workTypes()->list();
```

### 4. Handle Empty Results

```php
$workTypes = Teamleader::workTypes()->search('NonExistent');

if (empty($workTypes['data'])) {
    // No work types found
    return response()->json([
        'message' => 'No work types found matching your search'
    ], 404);
}
```

### 5. Validate Work Type IDs

```php
// Good: Validate before using
$validator = new WorkTypeValidator();

if (!$validator->isValidWorkType($workTypeId)) {
    throw new \InvalidArgumentException('Invalid work type ID');
}

// Proceed with valid work type
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $workTypes = Teamleader::workTypes()->list();
} catch (TeamleaderException $e) {
    Log::error('Error fetching work types', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    return ['data' => []];
}
```

## Using Work Types with Time Tracking

Work types are primarily used with time tracking entries:

```php
// Get work type
$workTypes = Teamleader::workTypes()->search('Development');
$developmentType = $workTypes['data'][0] ?? null;

if ($developmentType) {
    // Create time entry with work type
    $timeEntry = Teamleader::timeTracking()->add([
        'user_id' => 'user-uuid',
        'project_id' => 'project-uuid',
        'work_type_id' => $developmentType['id'],
        'started_at' => '2024-01-15T09:00:00+00:00',
        'ended_at' => '2024-01-15T17:00:00+00:00',
        'description' => 'Backend development'
    ]);
}
```

## Limitations

1. **No info() method**: You cannot fetch individual work type details. Use `list()` with the `ids` filter instead.
2. **Read-only**: Work types cannot be created, updated, or deleted via the API.
3. **Limited data**: Work types only contain ID and name fields.

```php
// To get a specific work type, use list with ids filter
$workType = Teamleader::workTypes()->list(['ids' => ['work-type-uuid']]);

if (!empty($workType['data'])) {
    $workTypeData = $workType['data'][0];
}
```

## Related Resources

- [Time Tracking](../timetracking/time_tracking.md) - Use work types with time entries
- [Projects](../projects/projects.md) - Track time on projects by work type

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

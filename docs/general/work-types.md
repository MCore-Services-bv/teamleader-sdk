# Work Types

Manage work types in Teamleader Focus. This resource provides read-only access to work type information from your Teamleader account.

## Endpoint

`workTypes`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of work types with filtering and sorting options. Work types are sorted alphabetically (on their name) by default.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting options

**Example:**
```php
$workTypes = $teamleader->workTypes()->list(['term' => 'design']);
```

### `search()`

Search work types by name.

**Parameters:**
- `term` (string): Search term

**Example:**
```php
$designTypes = $teamleader->workTypes()->search('design');
```

### `byIds()`

Get specific work types by their UUIDs.

**Parameters:**
- `ids` (array): Array of work type UUIDs

**Example:**
```php
$workTypes = $teamleader->workTypes()->byIds(['uuid1', 'uuid2']);
```

### `paginate()`

Get work types with pagination.

**Parameters:**
- `pageSize` (int): Number of items per page (default: 20)
- `pageNumber` (int): Page number (default: 1)
- `filters` (array): Optional filters

**Example:**
```php
$workTypes = $teamleader->workTypes()->paginate(50, 2);
```

### `sortedByName()`

Get work types sorted by name.

**Parameters:**
- `order` (string): Sort order (asc or desc, default: asc)
- `filters` (array): Optional filters

**Example:**
```php
$workTypes = $teamleader->workTypes()->sortedByName('desc');
```

## Filtering

### Available Filters

- **`ids`**: Array of work type UUIDs to filter by
- **`term`**: Search term - searches in the work type name only

### Filter Examples

```php
// Search by term
$designTypes = $teamleader->workTypes()->list([
    'term' => 'design'
]);

// Filter by specific IDs
$specificTypes = $teamleader->workTypes()->list([
    'ids' => [
        '811a5825-96f4-4318-83c3-2840935c6003',
        'another-uuid-here'
    ]
]);

// Combine filters (search for specific IDs containing term)
$filteredTypes = $teamleader->workTypes()->list([
    'term' => 'development',
    'ids' => ['uuid1', 'uuid2']
]);
```

## Sorting

### Available Sort Fields

- **`name`**: Sorts by work type name (alphabetically)

### Sorting Examples

```php
// Sort by name (ascending) - this is the default
$workTypes = $teamleader->workTypes()->list([], [
    'sort' => [
        'field' => 'name',
        'order' => 'asc'
    ]
]);

// Sort by name (descending)
$workTypes = $teamleader->workTypes()->list([], [
    'sort' => [
        'field' => 'name',
        'order' => 'desc'
    ]
]);
```

## Pagination

Work types support pagination with configurable page size and page number.

### Pagination Examples

```php
// Default pagination (20 items, page 1)
$workTypes = $teamleader->workTypes()->list();

// Custom page size
$workTypes = $teamleader->workTypes()->list([], [
    'page' => [
        'size' => 50,
        'number' => 1
    ]
]);

// Navigate to specific page
$workTypes = $teamleader->workTypes()->list([], [
    'page' => [
        'size' => 20,
        'number' => 3
    ]
]);

// Using the convenience method
$workTypes = $teamleader->workTypes()->paginate(25, 2);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "811a5825-96f4-4318-83c3-2840935c6003",
            "name": "Design"
        },
        {
            "id": "another-uuid-here",
            "name": "Development"
        }
    ]
}
```

## Data Fields

### Available Fields

- **`id`**: Work type UUID
- **`name`**: Work type name

## Usage Examples

### Basic List

Get all work types with default settings:

```php
$workTypes = $teamleader->workTypes()->list();
```

### Search Work Types

Search for work types containing "design":

```php
$designTypes = $teamleader->workTypes()->search('design');
```

### Filtered and Sorted List

Get work types sorted by name in descending order:

```php
$workTypes = $teamleader->workTypes()->sortedByName('desc');
```

### Paginated List

Get work types with custom pagination:

```php
$workTypes = $teamleader->workTypes()->paginate(50, 2, [
    'term' => 'development'
]);
```

### Complex Query

Get specific work types with search term and custom sorting:

```php
$workTypes = $teamleader->workTypes()->list([
    'term' => 'design',
    'ids' => [
        '811a5825-96f4-4318-83c3-2840935c6003',
        'another-uuid-here'
    ]
], [
    'sort' => [
        'field' => 'name',
        'order' => 'asc'
    ],
    'page' => [
        'size' => 25,
        'number' => 1
    ]
]);
```

### Processing Results

Work with the returned work types:

```php
$result = $teamleader->workTypes()->list();

if (!isset($result['error'])) {
    $workTypes = $result['data'] ?? [];
    
    foreach ($workTypes as $workType) {
        echo "ID: " . $workType['id'] . "\n";
        echo "Name: " . $workType['name'] . "\n";
        echo "---\n";
    }
}
```

## Error Handling

The work types resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->workTypes()->list();

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Work Types API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Work Types API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Search operations**: 1 request per call
- **Convenience methods**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Work types are **read-only** in the Teamleader API
- No create, update, or delete operations are supported
- No individual `info()` method is available - use `list()` with `ids` filter instead
- Work types don't support sideloading/includes
- Search is performed on the work type name only
- Default sorting is alphabetical by name
- Pagination defaults to 20 items per page

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class WorkTypeController extends Controller
{
    public function index(TeamleaderSDK $teamleader, Request $request)
    {
        $filters = [];
        
        if ($request->has('search')) {
            $filters['term'] = $request->get('search');
        }
        
        $workTypes = $teamleader->workTypes()->list($filters, [
            'page' => [
                'size' => $request->get('per_page', 20),
                'number' => $request->get('page', 1)
            ]
        ]);
        
        return view('work-types.index', compact('workTypes'));
    }
    
    public function search(TeamleaderSDK $teamleader, Request $request)
    {
        $term = $request->get('term', '');
        
        $workTypes = $teamleader->workTypes()->search($term);
        
        return response()->json($workTypes);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

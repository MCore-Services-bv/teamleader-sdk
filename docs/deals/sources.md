# Deal Sources

Manage deal sources in Teamleader Focus. Deal sources help track where your deals are coming from, providing valuable insights into your sales pipeline and marketing effectiveness.

## Endpoint

`dealSources`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported (by IDs)
- **Supports Sorting**: ✅ Supported (by name only)
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of deal sources with filtering and sorting options. Results are sorted alphabetically by name by default.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
$sources = $teamleader->dealSources()->list();
```

### `all()`

Get all deal sources (convenience method for list without parameters).

**Example:**
```php
$allSources = $teamleader->dealSources()->all();
```

### `byIds()`

Get specific deal sources by their UUIDs.

**Parameters:**
- `ids` (array): Array of deal source UUIDs

**Example:**
```php
$sources = $teamleader->dealSources()->byIds(['uuid1', 'uuid2']);
```

### `search()`

Search deal sources by name (client-side filtering).

**Parameters:**
- `query` (string): Search term to filter by name

**Example:**
```php
$sources = $teamleader->dealSources()->search('website');
```

### `selectOptions()`

Get deal sources formatted for HTML select options.

**Returns:** Array with ID as key and name as value

**Example:**
```php
$options = $teamleader->dealSources()->selectOptions();
// Returns: ['uuid1' => 'Website', 'uuid2' => 'Referral', ...]
```

### `exists()`

Check if a deal source ID exists.

**Parameters:**
- `sourceId` (string): Deal source UUID to check

**Example:**
```php
$exists = $teamleader->dealSources()->exists('source-uuid-here');
```

### `getName()`

Get deal source name by ID.

**Parameters:**
- `sourceId` (string): Deal source UUID

**Example:**
```php
$name = $teamleader->dealSources()->getName('source-uuid-here');
```

### `getStatistics()`

Get statistics about all deal sources.

**Example:**
```php
$stats = $teamleader->dealSources()->getStatistics();
```

## Filtering

### Available Filters

- **`ids`**: Array of deal source UUIDs to filter by

### Filter Examples

```php
// Filter by specific IDs
$specificSources = $teamleader->dealSources()->list([
    'ids' => [
        '811a5825-96f4-4318-83c3-2840935c6003',
        '922b6936-a7f5-5429-94d4-3951046d7114'
    ]
]);

// Get multiple sources by ID (convenience method)
$sources = $teamleader->dealSources()->byIds([
    '811a5825-96f4-4318-83c3-2840935c6003',
    '922b6936-a7f5-5429-94d4-3951046d7114'
]);
```

## Sorting

### Available Sort Fields

- **`name`**: Sorts by deal source name (ascending only - API limitation)

### Sorting Examples

```php
// Sort by name (default behavior)
$sources = $teamleader->dealSources()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);

// Simple sort (same as above)
$sources = $teamleader->dealSources()->list();
```

**Note:** The API only supports ascending sort order for deal sources.

## Pagination

```php
// Get first 50 deal sources
$sources = $teamleader->dealSources()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Get second page with default size (20)
$sources = $teamleader->dealSources()->list([], [
    'page_number' => 2
]);

// Combine with filters
$filteredSources = $teamleader->dealSources()->list([
    'ids' => ['uuid1', 'uuid2']
], [
    'page_size' => 10,
    'page_number' => 1
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "811a5825-96f4-4318-83c3-2840935c6003",
            "name": "Website"
        },
        {
            "id": "922b6936-a7f5-5429-94d4-3951046d7114",
            "name": "Referral"
        },
        {
            "id": "a33c7047-b8g6-6530-a5e5-4a62157e8225",
            "name": "Cold Calling"
        }
    ]
}
```

## Data Fields

### Available Fields

- **`id`**: Deal source UUID (string)
- **`name`**: Deal source name (string)

**Note:** Deal sources have a simple structure with only ID and name fields.

## Usage Examples

### Basic List

Get all deal sources:

```php
$sources = $teamleader->dealSources()->list();

// Access the data
foreach ($sources['data'] as $source) {
    echo "ID: {$source['id']}\n";
    echo "Name: {$source['name']}\n";
}
```

### Filtered List

Get specific deal sources by ID:

```php
$sources = $teamleader->dealSources()->list([
    'ids' => [
        '811a5825-96f4-4318-83c3-2840935c6003',
        '922b6936-a7f5-5429-94d4-3951046d7114'
    ]
]);
```

### Search by Name

Search for sources containing specific text:

```php
$websiteSources = $teamleader->dealSources()->search('website');
$referralSources = $teamleader->dealSources()->search('referral');
```

### Form Integration

Get sources for HTML select dropdown:

```php
$sourceOptions = $teamleader->dealSources()->selectOptions();

// In a Blade template:
// <select name="deal_source_id">
//     @foreach($sourceOptions as $id => $name)
//         <option value="{{ $id }}">{{ $name }}</option>
//     @endforeach
// </select>
```

### Validation

Check if a source exists before using:

```php
$sourceId = '811a5825-96f4-4318-83c3-2840935c6003';

if ($teamleader->dealSources()->exists($sourceId)) {
    $sourceName = $teamleader->dealSources()->getName($sourceId);
    echo "Source exists: {$sourceName}";
} else {
    echo "Source not found";
}
```

### Statistics

Get information about all deal sources:

```php
$stats = $teamleader->dealSources()->getStatistics();

echo "Total sources: {$stats['total_sources']}\n";

foreach ($stats['sources'] as $source) {
    echo "- {$source['name']} (ID: {$source['id']})\n";
}
```

## Error Handling

Deal sources follow the standard SDK error handling patterns:

```php
$result = $teamleader->dealSources()->list();

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Deal Sources API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
} else {
    // Process successful response
    $sources = $result['data'] ?? [];
}
```

## Rate Limiting

Deal sources API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Search operations**: 1 request per call (fetches all then filters)
- **Convenience methods**: 1 request per call

Rate limit cost: **1 request per method call**

## Notes

- Deal sources are **read-only** in the Teamleader API
- No create, update, delete, or info operations are supported
- Deal sources don't support sideloading/includes
- Only sorting by name is supported (ascending order only)
- The `search()` method performs client-side filtering after fetching all sources
- Deal sources have a simple structure (only ID and name)
- Always sort alphabetically by name by default

## Common Use Cases

### Lead Source Tracking

```php
// Get all available sources for lead creation forms
$sources = $teamleader->dealSources()->selectOptions();

// Validate a source before creating a deal
$sourceId = $request->input('source_id');
if (!$teamleader->dealSources()->exists($sourceId)) {
    return back()->withErrors(['source_id' => 'Invalid deal source selected']);
}
```

### Reporting and Analytics

```php
// Get statistics for reporting
$stats = $teamleader->dealSources()->getStatistics();

// Find specific sources for analytics
$digitalSources = $teamleader->dealSources()->search('digital');
$referralSources = $teamleader->dealSources()->search('referral');
```

### Form Population

```php
// In a Laravel controller
public function create()
{
    $dealSources = $teamleader->dealSources()->selectOptions();
    return view('deals.create', compact('dealSources'));
}
```

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DealController extends Controller
{
    public function create(TeamleaderSDK $teamleader)
    {
        $dealSources = $teamleader->dealSources()->selectOptions();
        
        return view('deals.create', compact('dealSources'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        // Validate source exists
        if (!$teamleader->dealSources()->exists($request->source_id)) {
            return back()->withErrors(['source_id' => 'Invalid source']);
        }
        
        // Create deal with validated source...
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

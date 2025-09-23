# Lost Reasons

Manage lost reasons for deals in Teamleader Focus. This resource provides read-only access to deal lost reason information from your Teamleader account.

## Endpoint

`lostReasons`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported (by IDs only)
- **Supports Sorting**: ✅ Supported (by name only)
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of lost reasons with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting options

**Example:**
```php
$lostReasons = $teamleader->lostReasons()->list();
```

### `info()`

Get detailed information about a specific lost reason (emulated through list filtering).

**Parameters:**
- `id` (string): Lost reason UUID

**Example:**
```php
$lostReason = $teamleader->lostReasons()->info('lost-reason-uuid-here');
```

### `all()`

Get all lost reasons with optional sorting.

**Parameters:**
- `sortOrder` (string): Sort order ('asc' or 'desc')

**Example:**
```php
$allLostReasons = $teamleader->lostReasons()->all('asc');
```

### `byIds()`

Get specific lost reasons by their UUIDs.

**Parameters:**
- `ids` (array): Array of lost reason UUIDs

**Example:**
```php
$lostReasons = $teamleader->lostReasons()->byIds(['uuid1', 'uuid2']);
```

### `exists()`

Check if a lost reason exists.

**Parameters:**
- `id` (string): Lost reason UUID

**Example:**
```php
$exists = $teamleader->lostReasons()->exists('lost-reason-uuid');
```

### `getSelectOptions()`

Get lost reasons formatted for form dropdowns.

**Example:**
```php
$options = $teamleader->lostReasons()->getSelectOptions();
```

### `getStats()`

Get statistics about available lost reasons.

**Example:**
```php
$stats = $teamleader->lostReasons()->getStats();
```

## Filtering

### Available Filters

- **`ids`**: Array of lost reason UUIDs to filter by

### Filter Examples

```php
// Filter by specific IDs
$specificLostReasons = $teamleader->lostReasons()->list([
    'ids' => [
        '811a5825-96f4-4318-83c3-2840935c6003',
        '922b6936-a7g5-5429-94d4-3951046d7114'
    ]
]);

// Get all lost reasons (no filters)
$allLostReasons = $teamleader->lostReasons()->list();
```

## Sorting

### Available Sort Fields

- **`name`**: Sort by lost reason name (only available sort field)

### Sorting Examples

```php
// Sort by name (ascending) - default
$lostReasons = $teamleader->lostReasons()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);

// Sort by name (descending) - note: API only supports 'asc' order
$lostReasons = $teamleader->lostReasons()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'  // Only 'asc' is supported by the API
        ]
    ]
]);

// Using convenience method
$sortedLostReasons = $teamleader->lostReasons()->all('asc');
```

## Pagination

### Pagination Examples

```php
// Custom page size
$lostReasons = $teamleader->lostReasons()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Navigate through pages
$page2 = $teamleader->lostReasons()->list([], [
    'page_size' => 20,
    'page_number' => 2
]);

// Get all results (large page size)
$allResults = $teamleader->lostReasons()->list([], [
    'page_size' => 100
]);
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "id": "811a5825-96f4-4318-83c3-2840935c6003",
            "name": "Budget constraints"
        },
        {
            "id": "922b6936-a7g5-5429-94d4-3951046d7114",
            "name": "Competitor chosen"
        },
        {
            "id": "a33c7a47-b8h6-6530-a5e5-4a62157e8225",
            "name": "Not a priority"
        }
    ]
}
```

### Single Lost Reason Response (via info method)

```json
{
    "data": {
        "id": "811a5825-96f4-4318-83c3-2840935c6003",
        "name": "Budget constraints"
    }
}
```

## Data Fields

### Common Fields

- **`id`**: Lost reason UUID
- **`name`**: Lost reason name/description

## Usage Examples

### Basic List

Get all lost reasons with default settings:

```php
$lostReasons = $teamleader->lostReasons()->list();
```

### Filtered List

Get specific lost reasons by ID:

```php
$specificLostReasons = $teamleader->lostReasons()->list([
    'ids' => [
        '811a5825-96f4-4318-83c3-2840935c6003',
        '922b6936-a7g5-5429-94d4-3951046d7114'
    ]
]);
```

### Sorted List

Get lost reasons sorted by name:

```php
$sortedLostReasons = $teamleader->lostReasons()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);
```

### Paginated List

Get lost reasons with custom pagination:

```php
$paginatedLostReasons = $teamleader->lostReasons()->list([], [
    'page_size' => 25,
    'page_number' => 1
]);
```

### Get Single Lost Reason

Retrieve information for a specific lost reason:

```php
$lostReason = $teamleader->lostReasons()->info('811a5825-96f4-4318-83c3-2840935c6003');

// Access the data
$name = $lostReason['data']['name'];
$id = $lostReason['data']['id'];
```

### Convenience Methods

Use the built-in convenience methods for common operations:

```php
// Get all lost reasons
$allLostReasons = $teamleader->lostReasons()->all();

// Get specific lost reasons by ID
$specificLostReasons = $teamleader->lostReasons()->byIds([
    '811a5825-96f4-4318-83c3-2840935c6003',
    '922b6936-a7g5-5429-94d4-3951046d7114'
]);

// Check if a lost reason exists
$exists = $teamleader->lostReasons()->exists('811a5825-96f4-4318-83c3-2840935c6003');

// Get select options for forms
$selectOptions = $teamleader->lostReasons()->getSelectOptions();
// Returns: [['value' => 'uuid', 'label' => 'Budget constraints'], ...]

// Get statistics
$stats = $teamleader->lostReasons()->getStats();
// Returns: ['total_count' => 5, 'names' => [...], 'ids' => [...]]
```

### Form Integration

Use lost reasons in forms and dropdowns:

```php
// Get options for a select dropdown
$lostReasonOptions = $teamleader->lostReasons()->getSelectOptions();

// In a Laravel view
foreach ($lostReasonOptions as $option) {
    echo "<option value='{$option['value']}'>{$option['label']}</option>";
}
```

### Validation and Checking

```php
// Check if specific lost reasons exist
$lostReasonIds = ['811a5825-96f4-4318-83c3-2840935c6003', 'invalid-uuid'];

foreach ($lostReasonIds as $id) {
    if ($teamleader->lostReasons()->exists($id)) {
        echo "Lost reason {$id} exists\n";
    } else {
        echo "Lost reason {$id} does not exist\n";
    }
}

// Get statistics about available lost reasons
$stats = $teamleader->lostReasons()->getStats();
echo "Total lost reasons: {$stats['total_count']}\n";
echo "Available options: " . implode(', ', $stats['names']) . "\n";
```

## Error Handling

The lost reasons resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->lostReasons()->list();

if (isset($result['error']) && $result['error']) {
    // Handle error
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Lost Reasons API error: {$errorMessage}", [
        'status_code' => $statusCode
    ]);
}
```

## Rate Limiting

Lost reasons API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Convenience methods**: 1 request per call

Rate limit cost: **1 request per method call**

## API Limitations

Based on the Teamleader API documentation:

1. **Sorting**: Only supports sorting by `name` field
2. **Sort Order**: Only supports `asc` (ascending) order
3. **Filtering**: Only supports filtering by `ids` (UUIDs)
4. **No Text Search**: The API doesn't support searching by name text
5. **Read-only**: No create, update, or delete operations available
6. **No Sideloading**: No related resources can be included

## Notes

- Lost reasons are **read-only** in the Teamleader API
- No create, update, or delete operations are supported
- Lost reasons don't support sideloading/includes
- The `info()` method is emulated by filtering the list by ID
- Only the `name` field can be used for sorting
- Only `asc` (ascending) sort order is supported by the API
- Filtering is limited to UUID-based filtering only

## Laravel Integration

When using this resource in Laravel applications, you can inject the SDK:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DealController extends Controller
{
    public function getLostReasons(TeamleaderSDK $teamleader)
    {
        $lostReasons = $teamleader->lostReasons()->all();
        
        return response()->json($lostReasons);
    }
    
    public function createDealForm(TeamleaderSDK $teamleader)
    {
        $lostReasonOptions = $teamleader->lostReasons()->getSelectOptions();
        
        return view('deals.create', compact('lostReasonOptions'));
    }
    
    public function validateLostReason(TeamleaderSDK $teamleader, string $id)
    {
        $exists = $teamleader->lostReasons()->exists($id);
        
        return response()->json(['exists' => $exists]);
    }
}
```

### Caching Recommendations

Since lost reasons are relatively static data, consider caching them:

```php
use Illuminate\Support\Facades\Cache;

class LostReasonService
{
    public function getCachedLostReasons(TeamleaderSDK $teamleader)
    {
        return Cache::remember('teamleader_lost_reasons', 3600, function () use ($teamleader) {
            return $teamleader->lostReasons()->all();
        });
    }
    
    public function getCachedSelectOptions(TeamleaderSDK $teamleader)
    {
        return Cache::remember('teamleader_lost_reasons_options', 3600, function () use ($teamleader) {
            return $teamleader->lostReasons()->getSelectOptions();
        });
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

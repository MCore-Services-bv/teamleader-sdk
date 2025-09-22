# Tags

Manage tags in Teamleader Focus. Tags are used to categorize and organize various resources like contacts, companies, and deals.

## Endpoint

`tags`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ❌ Not Supported (client-side search available)
- **Supports Sorting**: ✅ Supported (tag name only)
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported (read-only)
- **Supports Update**: ❌ Not Supported (read-only)
- **Supports Deletion**: ❌ Not Supported (read-only)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of tags with sorting options.

**Parameters:**
- `filters` (array): No server-side filters supported
- `options` (array): Pagination and sorting options

**Example:**
```php
$tags = $teamleader->tags()->list([], [
    'page_size' => 50,
    'page_number' => 2,
    'sort' => 'tag',
    'sort_order' => 'asc'
]);
```

### `all()`

Get all tags without pagination (automatically handles pagination).

**Example:**
```php
$allTags = $teamleader->tags()->all();
```

### `search()`

Search tags by name (client-side filtering).

**Parameters:**
- `query` (string): Search query
- `exactMatch` (bool): Whether to match exactly or use partial matching (default: false)

**Example:**
```php
// Search for tags containing "campaign"
$campaignTags = $teamleader->tags()->search('campaign');

// Exact match search
$exactTag = $teamleader->tags()->search('priority', true);
```

### `containing()`

Get tags containing specific text.

**Parameters:**
- `text` (string): Text to search for

**Example:**
```php
$clientTags = $teamleader->tags()->containing('client');
```

### `startingWith()`

Get tags starting with specific text.

**Parameters:**
- `prefix` (string): Prefix to search for

**Example:**
```php
$urgentTags = $teamleader->tags()->startingWith('urgent');
```

### `paginate()`

Get paginated results with enhanced metadata.

**Parameters:**
- `pageSize` (int): Items per page (default: 20)
- `pageNumber` (int): Page number (default: 1)

**Example:**
```php
$paginatedTags = $teamleader->tags()->paginate(25, 3);
```

### `getStatistics()`

Get comprehensive statistics about your tags.

**Example:**
```php
$stats = $teamleader->tags()->getStatistics();
```

## Pagination

### Basic Pagination

```php
// Get first 20 tags (default)
$tags = $teamleader->tags()->list();

// Get specific page with custom size
$tags = $teamleader->tags()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

### Enhanced Pagination

```php
$result = $teamleader->tags()->paginate(30, 1);

// Access pagination metadata
$currentPage = $result['pagination']['current_page'];
$hasMore = $result['pagination']['has_more_pages'];
$totalOnPage = $result['pagination']['total_on_page'];
```

## Sorting

Tags only support sorting by the `tag` field in ascending order.

### Sorting Examples

```php
// Sort by tag name (default behavior)
$sortedTags = $teamleader->tags()->list([], [
    'sort' => 'tag',
    'sort_order' => 'asc'
]);
```

**Available Sort Fields:**
- `tag`: Sort by tag name (only option available)

**Available Sort Orders:**
- `asc`: Ascending order (only option available)

## Client-Side Search

Since the API doesn't support server-side filtering, the SDK provides client-side search functionality:

### Search Examples

```php
// Search for tags containing "campaign"
$campaignTags = $teamleader->tags()->search('campaign');

// Exact match search
$exactMatch = $teamleader->tags()->search('VIP', true);

// Find tags starting with "client"
$clientTags = $teamleader->tags()->startingWith('client');

// Find tags containing "urgent"
$urgentTags = $teamleader->tags()->containing('urgent');
```

## Response Format

### List Response

```json
{
    "data": [
        {
            "tag": "campaign"
        },
        {
            "tag": "priority"
        },
        {
            "tag": "client-vip"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total_on_page": 3,
        "has_more_pages": false
    }
}
```

### Search Response

```json
{
    "data": [
        {
            "tag": "campaign-2024"
        },
        {
            "tag": "campaign-summer"
        }
    ],
    "total_count": 2,
    "query": "campaign",
    "exact_match": false
}
```

### Statistics Response

```json
{
    "total_count": 45,
    "average_length": 8.7,
    "shortest_tag": "vip",
    "longest_tag": "high-priority-client",
    "most_common_prefixes": {
        "cli": 8,
        "cam": 5,
        "pri": 3
    }
}
```

## Data Fields

- **`tag`**: Tag name/label (e.g., "campaign", "priority", "client-vip")

## Usage Examples

### Basic Tag Retrieval

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class TagController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $tags = $teamleader->tags()->list([], [
            'page_size' => 25,
            'page_number' => 1
        ]);
        
        return view('tags.index', compact('tags'));
    }
}
```

### Search Functionality

```php
public function search(Request $request, TeamleaderSDK $teamleader)
{
    $query = $request->get('q');
    
    if (empty($query)) {
        $tags = $teamleader->tags()->list();
    } else {
        $tags = $teamleader->tags()->search($query);
    }
    
    return response()->json($tags);
}
```

### Tag Autocomplete

Create an autocomplete endpoint for forms:

```php
public function autocomplete(Request $request, TeamleaderSDK $teamleader)
{
    $query = $request->get('term', '');
    
    if (strlen($query) < 2) {
        return response()->json([]);
    }
    
    $tags = $teamleader->tags()->search($query);
    
    $suggestions = collect($tags['data'])->map(function($tag) {
        return [
            'value' => $tag['tag'],
            'label' => $tag['tag']
        ];
    })->take(10);
    
    return response()->json($suggestions);
}
```

### Tag Analytics

Get insights about your tag usage:

```php
public function analytics(TeamleaderSDK $teamleader)
{
    $stats = $teamleader->tags()->getStatistics();
    
    return view('tags.analytics', [
        'totalTags' => $stats['total_count'],
        'averageLength' => $stats['average_length'],
        'shortestTag' => $stats['shortest_tag'],
        'longestTag' => $stats['longest_tag'],
        'commonPrefixes' => $stats['most_common_prefixes']
    ]);
}
```

### Cached Tag Service

Create a service with caching for better performance:

```php
use Illuminate\Support\Facades\Cache;

class TagService
{
    public function __construct(private TeamleaderSDK $teamleader) {}
    
    public function getAllTags(): array
    {
        return Cache::remember('all_tags', 1800, function() {
            return $this->teamleader->tags()->all();
        });
    }
    
    public function searchTags(string $query): array
    {
        $allTags = $this->getAllTags();
        
        $filteredTags = array_filter($allTags['data'], function($tag) use ($query) {
            return stripos($tag['tag'], $query) !== false;
        });
        
        return [
            'data' => array_values($filteredTags),
            'total_count' => count($filteredTags),
            'query' => $query
        ];
    }
    
    public function getTagsByPrefix(string $prefix): array
    {
        $allTags = $this->getAllTags();
        
        $filteredTags = array_filter($allTags['data'], function($tag) use ($prefix) {
            return stripos($tag['tag'], $prefix) === 0;
        });
        
        return array_values($filteredTags);
    }
}
```

### Form Integration

Use tags in Laravel forms:

```php
// Controller
public function create(TagService $tagService)
{
    $availableTags = $tagService->getAllTags();
    
    return view('contacts.create', compact('availableTags'));
}
```

```blade
{{-- Blade template --}}
<div class="form-group">
    <label for="tags">Tags</label>
    <select name="tags[]" id="tags" class="form-control" multiple>
        @foreach($availableTags['data'] as $tag)
            <option value="{{ $tag['tag'] }}">{{ $tag['tag'] }}</option>
        @endforeach
    </select>
</div>
```

### Tag Validation

Validate tags before using them:

```php
public function validateTags(array $inputTags, TeamleaderSDK $teamleader): array
{
    $allTags = $teamleader->tags()->all();
    $validTags = collect($allTags['data'])->pluck('tag')->toArray();
    
    $validInputTags = [];
    $invalidTags = [];
    
    foreach ($inputTags as $tag) {
        if (in_array($tag, $validTags)) {
            $validInputTags[] = $tag;
        } else {
            $invalidTags[] = $tag;
        }
    }
    
    return [
        'valid' => $validInputTags,
        'invalid' => $invalidTags
    ];
}
```

## Performance Considerations

### Caching Strategy

```php
// Cache all tags for 30 minutes
$allTags = Cache::remember('teamleader_tags', 1800, function() use ($teamleader) {
    return $teamleader->tags()->all();
});

// Cache search results for 15 minutes
$searchKey = 'tag_search_' . md5($query);
$searchResults = Cache::remember($searchKey, 900, function() use ($teamleader, $query) {
    return $teamleader->tags()->search($query);
});
```

### Efficient Pagination

```php
// For large tag lists, use smaller page sizes
$tags = $teamleader->tags()->list([], [
    'page_size' => 50,  // Reasonable size
    'page_number' => $page
]);

// Or use all() method which handles pagination automatically
$allTags = $teamleader->tags()->all(); // Automatically paginates through all results
```

## Error Handling

Handle API errors gracefully:

```php
$result = $teamleader->tags()->list();

if (isset($result['error']) && $result['error']) {
    Log::error("Tags API error: {$result['message']}", [
        'status_code' => $result['status_code'] ?? null
    ]);
    
    return response()->json([
        'error' => 'Failed to fetch tags',
        'message' => 'Please try again later'
    ], 500);
}
```

Handle search errors:

```php
try {
    $tags = $teamleader->tags()->search($query);
    
    if (empty($tags['data'])) {
        return response()->json([
            'message' => 'No tags found matching your search',
            'query' => $query
        ]);
    }
    
    return response()->json($tags);
} catch (\Exception $e) {
    return response()->json([
        'error' => 'Search failed',
        'message' => $e->getMessage()
    ], 500);
}
```

## Rate Limiting

Tags API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Search operations**: 1 request (uses client-side filtering)
- **All tags**: Multiple requests (automatically paginated)

Rate limit cost: **1 request per list operation**

## Best Practices

1. **Use Caching**: Tags don't change frequently, cache them for better performance
2. **Client-Side Search**: Leverage the SDK's client-side search to reduce API calls
3. **Batch Loading**: Use `all()` method to load all tags once, then filter locally
4. **Reasonable Page Sizes**: Use page sizes between 20-100 for optimal performance
5. **Tag Validation**: Always validate user-provided tags against available tags

## Notes

- Tags are **read-only** in the Teamleader API
- Only sorting by tag name in ascending order is supported
- No server-side filtering is available - use client-side search methods
- Individual tag `info()` method is not supported
- Tag names are case-sensitive
- The SDK provides comprehensive client-side search and filtering capabilities

## Laravel Integration Example

Complete Laravel integration with caching and search:

```php
// Service Provider
class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TagService::class, function ($app) {
            return new TagService($app->make(TeamleaderSDK::class));
        });
    }
}

// Route
Route::get('/api/tags/search', [TagController::class, 'search']);

// Controller
class TagController extends Controller
{
    public function search(Request $request, TagService $tagService)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }
        
        $tags = $tagService->searchTags($query);
        
        return response()->json($tags);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

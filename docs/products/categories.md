# Product Categories

Manage product categories in Teamleader Focus. This resource provides read-only access to product categories with filtering capabilities by department.

## Endpoint

`productCategories`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a list of product categories with optional filtering by department.

**Parameters:**
- `filters` (array): Array of filters to apply

**Example:**
```php
$categories = $teamleader->productCategories()->list();
```

### `forDepartment()`

Get product categories for a specific department.

**Parameters:**
- `departmentId` (string): Department UUID

**Example:**
```php
$categories = $teamleader->productCategories()->forDepartment('080aac72-ff1a-4627-bfe3-146b6eee979c');
```

## Filtering

Product categories support filtering by department:

### Available Filters

- `department_id` (string): Filter categories by department UUID

### Examples

```php
// Get all categories
$allCategories = $teamleader->productCategories()->list();

// Get categories for specific department
$deptCategories = $teamleader->productCategories()->list([
    'department_id' => '080aac72-ff1a-4627-bfe3-146b6eee979c'
]);

// Using the convenience method
$categories = $teamleader->productCategories()->forDepartment('080aac72-ff1a-4627-bfe3-146b6eee979c');
```

## Response Structure

The API returns product categories in the following format:

```php
[
    'data' => [
        [
            'id' => '2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4',
            'name' => 'Asian Flowers',
            'ledgers' => [
                [
                    'department' => [
                        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                        'type' => 'string',
                        'ledger_account_number' => '70100'
                    ]
                ]
            ]
        ]
    ]
]
```

### Field Descriptions

- `id`: Unique UUID identifier for the product category
- `name`: Human-readable name of the category (e.g., "Asian Flowers")
- `ledgers`: Array containing ledger information and associated department details
    - `department.id`: Department UUID
    - `department.type`: Department type
    - `ledger_account_number`: Associated ledger account number

## Error Handling

The product categories resource follows standard SDK error handling:

```php
$result = $teamleader->productCategories()->list(['department_id' => 'invalid-uuid']);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Product Categories API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Product category API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ProductCategoryController extends Controller
{
    public function index(TeamleaderSDK $teamleader, Request $request)
    {
        $filters = [];
        
        // Add department filter if provided
        if ($request->has('department_id')) {
            $filters['department_id'] = $request->get('department_id');
        }
        
        $categories = $teamleader->productCategories()->list($filters);
        return view('products.categories.index', compact('categories'));
    }
    
    public function forDepartment(TeamleaderSDK $teamleader, $departmentId)
    {
        $categories = $teamleader->productCategories()->forDepartment($departmentId);
        return response()->json($categories);
    }
}
```

## Usage with Departments

Since product categories are filtered by departments, you'll typically want to load departments first:

```php
// Get all departments
$departments = $teamleader->departments()->list();

// Get categories for each department
foreach ($departments['data'] as $department) {
    $categories = $teamleader->productCategories()->forDepartment($department['id']);
    // Process categories...
}
```

## Best Practices

1. **Department Integration**: Always consider which department context you're working in when fetching categories
2. **Caching**: Since categories don't change frequently, consider caching the results
3. **Error Handling**: Always check for errors in the response
4. **Rate Limiting**: Be mindful of rate limits when making multiple calls for different departments

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

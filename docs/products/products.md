# Products

Manage products in Teamleader Focus. This resource provides complete CRUD operations for managing products, including pricing, stock management, and custom fields.

## Endpoint

`products`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ✅ Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of products with filtering options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and include options

**Example:**
```php
$products = $teamleader->products()->list(['term' => 'cookies']);
```

### `info()`

Get detailed information about a specific product.

**Parameters:**
- `id` (string): Product UUID
- `includes` (array|string): Relations to include

**Example:**
```php
$product = $teamleader->products()->info('product-uuid-here', ['suppliers']);
```

### `create()`

Create a new product.

**Parameters:**
- `data` (array): Array of product data

**Example:**
```php
$product = $teamleader->products()->create([
    'name' => 'Dark Chocolate Cookies',
    'code' => 'COOK-DARK-001',
    'description' => 'Delicious dark chocolate cookies',
    'selling_price' => [
        'amount' => 15.99,
        'currency' => 'EUR'
    ]
]);
```

### `update()`

Update an existing product.

**Parameters:**
- `id` (string): Product UUID
- `data` (array): Array of data to update

**Example:**
```php
$product = $teamleader->products()->update('product-uuid', [
    'name' => 'Premium Dark Chocolate Cookies',
    'selling_price' => [
        'amount' => 18.99,
        'currency' => 'EUR'
    ]
]);
```

### `delete()`

Delete a product.

**Parameters:**
- `id` (string): Product UUID

**Example:**
```php
$result = $teamleader->products()->delete('product-uuid');
```

### `search()`

Search products by term (searches name and code).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$products = $teamleader->products()->search('cookies');
```

### `updatedSince()`

Get products updated since a specific date.

**Parameters:**
- `date` (string): ISO 8601 datetime
- `options` (array): Additional options

**Example:**
```php
$products = $teamleader->products()->updatedSince('2024-01-01T00:00:00+00:00');
```

## Available Includes

Products can be loaded with additional relations:

- `suppliers` - Include product suppliers
- `custom_fields` - Include custom field values

## Filtering

The following filters are available:

- `ids` (array): Filter by specific product UUIDs
- `term` (string): Search in name or code
- `updated_since` (string): ISO 8601 datetime

## Usage Examples

### Basic Operations

```php
// List all products
$products = $teamleader->products()->list();

// Get product with suppliers
$product = $teamleader->products()
    ->withSuppliers()
    ->info('product-uuid');

// Search products
$results = $teamleader->products()->search('chocolate');
```

### Creating Products

```php
// Create by name
$product = $teamleader->products()->create([
    'name' => 'Premium Cookies',
    'description' => 'High-quality artisan cookies',
    'selling_price' => [
        'amount' => 25.00,
        'currency' => 'EUR'
    ],
    'purchase_price' => [
        'amount' => 12.50,
        'currency' => 'EUR'
    ],
    'stock' => [
        'amount' => 100
    ],
    'configuration' => [
        'stock_threshold' => [
            'minimum' => 10,
            'action' => 'notify'
        ]
    ]
]);

// Create by code
$product = $teamleader->products()->create([
    'code' => 'COOK-PREM-001',
    'description' => 'Premium cookie product',
    'selling_price' => [
        'amount' => 25.00,
        'currency' => 'EUR'
    ]
]);
```

### Advanced Filtering

```php
// Products updated in last 24 hours
$recentProducts = $teamleader->products()->updatedSince(
    now()->subDay()->toISOString()
);

// Multiple filters
$products = $teamleader->products()->list([
    'term' => 'premium',
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);

// With custom fields and suppliers
$detailedProducts = $teamleader->products()
    ->withCustomFields()
    ->withSuppliers()
    ->list();
```

### Product Management

```php
// Update pricing
$teamleader->products()->update('product-uuid', [
    'selling_price' => [
        'amount' => 29.99,
        'currency' => 'EUR'
    ],
    'purchase_price' => [
        'amount' => 15.00,
        'currency' => 'EUR'
    ]
]);

// Update stock
$teamleader->products()->update('product-uuid', [
    'stock' => [
        'amount' => 250
    ],
    'configuration' => [
        'stock_threshold' => [
            'minimum' => 25,
            'action' => 'notify'
        ]
    ]
]);

// Add custom fields
$teamleader->products()->update('product-uuid', [
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Custom Value'
        ]
    ]
]);
```

## Error Handling

The products resource follows standard SDK error handling:

```php
$result = $teamleader->products()->create($productData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Products API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Product API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **CRUD operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ProductController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $products = $teamleader->products()->list();
        return view('products.index', compact('products'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $product = $teamleader->products()->create($request->validated());
        return redirect()->route('products.show', $product['data']['id']);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

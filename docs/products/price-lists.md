# Price Lists

Manage price lists in Teamleader Focus. This resource provides read-only access to price lists for pricing products and services.

## Endpoint

`priceLists`

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

Get a list of price lists with optional filtering.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Additional options (not used for this endpoint)

**Example:**
```php
$priceLists = $teamleader->priceLists()->list();
```

### `byIds()`

Get specific price lists by their UUIDs.

**Parameters:**
- `ids` (array): Array of price list UUIDs

**Example:**
```php
$priceLists = $teamleader->priceLists()->byIds([
    '2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4',
    '5b38d7f2-8df9-4762-b9c4-37bfb3fb25e5'
]);
```

## Available Filters

### `ids`
Filter price lists by specific UUIDs.

**Type:** Array of strings  
**Example:**
```php
$priceLists = $teamleader->priceLists()->list([
    'ids' => [
        '2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4',
        '5b38d7f2-8df9-4762-b9c4-37bfb3fb25e5'
    ]
]);
```

## Response Fields

### Price List Object

- `id` (string): Price list UUID (e.g., "2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4")
- `name` (string): Price list name (e.g., "Standard Prices")
- `calculation_method` (string): How prices are calculated
    - `manual` - Prices set manually
    - `based_on_price_list` - Based on another price list
    - `based_on_purchase_price` - Based on purchase price with markup

## Usage Examples

### Basic Price List Management

```php
// Get all price lists
$allPriceLists = $teamleader->priceLists()->list();

// Get specific price lists by ID
$specificPriceLists = $teamleader->priceLists()->byIds([
    '2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4'
]);

// Filter price lists (same as byIds)
$filteredPriceLists = $teamleader->priceLists()->list([
    'ids' => ['2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4']
]);
```

### Working with Response Data

```php
$response = $teamleader->priceLists()->list();

foreach ($response['data'] as $priceList) {
    echo "Price List: {$priceList['name']}\n";
    echo "ID: {$priceList['id']}\n";
    echo "Calculation Method: {$priceList['calculation_method']}\n";
    echo "---\n";
}
```

### Finding Price Lists by Calculation Method

```php
// Get all price lists and filter by calculation method
$response = $teamleader->priceLists()->list();
$priceLists = $response['data'] ?? [];

$manualPriceLists = array_filter($priceLists, function($priceList) {
    return $priceList['calculation_method'] === 'manual';
});

$basedOnPriceListPriceLists = array_filter($priceLists, function($priceList) {
    return $priceList['calculation_method'] === 'based_on_price_list';
});
```

## Error Handling

The price lists resource follows standard SDK error handling:

```php
$result = $teamleader->priceLists()->list();

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Price Lists API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Price list API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class PriceListController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $priceLists = $teamleader->priceLists()->list();
        return view('products.price-lists.index', compact('priceLists'));
    }
    
    public function getSpecific(Request $request, TeamleaderSDK $teamleader)
    {
        $ids = $request->get('ids', []);
        $priceLists = $teamleader->priceLists()->byIds($ids);
        return response()->json($priceLists);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

# Units of Measure

Manage units of measure in Teamleader Focus Products. This resource provides access to the standardized units of measure available in your Teamleader instance.

## Endpoint

`unitsOfMeasure`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ❌ Not Supported
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ❌ Not Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get all units of measure available in your Teamleader instance.

**Parameters:**
- None required

**Example:**
```php
$unitsOfMeasure = $teamleader->unitsOfMeasure()->list();
```

### `findByName()`

Find a specific unit of measure by name (case-insensitive).

**Parameters:**
- `name` (string): Name of the unit to find

**Example:**
```php
$unit = $teamleader->unitsOfMeasure()->findByName('piece');
$unit = $teamleader->unitsOfMeasure()->findByName('kg'); // kilogram
```

### `findById()`

Find a specific unit of measure by ID.

**Parameters:**
- `id` (string): UUID of the unit to find

**Example:**
```php
$unit = $teamleader->unitsOfMeasure()->findById('811a5825-96f4-4318-83c3-2840935c6003');
```

### `asOptions()`

Get all units of measure as key-value pairs for dropdowns.

**Parameters:**
- None

**Example:**
```php
$options = $teamleader->unitsOfMeasure()->asOptions();
// Returns: ['uuid1' => 'piece', 'uuid2' => 'kg', ...]
```

### `asCollection()`

Get units of measure as a Laravel collection for advanced manipulation.

**Parameters:**
- None

**Example:**
```php
$collection = $teamleader->unitsOfMeasure()->asCollection();
$sorted = $collection->sortBy('name');
```

### `exists()`

Check if a unit of measure exists by name.

**Parameters:**
- `name` (string): Name to check

**Example:**
```php
$exists = $teamleader->unitsOfMeasure()->exists('kg'); // true/false
```

### `count()`

Get the total number of units of measure.

**Parameters:**
- None

**Example:**
```php
$total = $teamleader->unitsOfMeasure()->count();
```

## Response Structure

### Unit of Measure Fields

- `id` (string): Unit UUID
- `name` (string): Display name of the unit

**Example Response:**
```php
[
    'data' => [
        [
            'id' => '811a5825-96f4-4318-83c3-2840935c6003',
            'name' => 'piece'
        ],
        [
            'id' => '9f2a7b3c-84e1-4567-89ab-cdef01234567',
            'name' => 'kg'
        ]
    ]
]
```

## Usage Examples

### Basic Usage

```php
// Get all units
$response = $teamleader->unitsOfMeasure()->list();
$units = $response['data'];

// Find specific unit
$pieceUnit = $teamleader->unitsOfMeasure()->findByName('piece');

// Get for dropdown
$unitOptions = $teamleader->unitsOfMeasure()->asOptions();
```

### Advanced Usage

```php
// Use in product creation
$units = $teamleader->unitsOfMeasure()->asOptions();

// Find unit for form validation
if (!$teamleader->unitsOfMeasure()->exists($requestedUnit)) {
    throw new ValidationException('Invalid unit of measure');
}

// Working with collections
$unitsCollection = $teamleader->unitsOfMeasure()->asCollection();
$sortedUnits = $unitsCollection->sortBy('name')->values();
```

### Integration with Products

```php
// When creating/updating products
$selectedUnitId = $teamleader->unitsOfMeasure()->findByName('kg')['id'];

$product = $teamleader->products()->create([
    'name' => 'Coffee Beans',
    'unit_of_measure_id' => $selectedUnitId,
    // ... other fields
]);
```

## Common Use Cases

### Form Dropdowns

```php
// Controller
public function create()
{
    $unitOptions = $teamleader->unitsOfMeasure()->asOptions();
    return view('products.create', compact('unitOptions'));
}
```

### Data Validation

```php
// Custom validation rule
public function validateUnit($attribute, $value, $parameters)
{
    return $this->teamleader->unitsOfMeasure()->findById($value) !== null;
}
```

### Unit Conversion Context

```php
// Get unit info for display
$unit = $teamleader->unitsOfMeasure()->findById($product['unit_of_measure_id']);
echo "Sold by: " . $unit['name'];
```

## Error Handling

The units of measure resource uses standard SDK error handling:

```php
$result = $teamleader->unitsOfMeasure()->list();

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    Log::error("Units of Measure API error: {$errorMessage}");
}
```

## Rate Limiting

Units of measure API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Helper methods** (findByName, asOptions, etc.): Use cached list data, no additional requests

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ProductController extends Controller
{
    public function create(TeamleaderSDK $teamleader)
    {
        $unitOptions = $teamleader->unitsOfMeasure()->asOptions();
        return view('products.create', compact('unitOptions'));
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

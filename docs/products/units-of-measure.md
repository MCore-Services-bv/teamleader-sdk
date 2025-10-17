# Units of Measure

Manage units of measure in Teamleader Focus.

## Overview

The Units of Measure resource provides read-only access to the units of measure configured in your Teamleader account. Units of measure define how products are quantified (e.g., pieces, kilograms, hours, meters). These units are used when creating and managing products.

**Important:** This resource is read-only. Units of measure must be created and managed through the Teamleader Focus web interface.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`unitsOfMeasure`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get all units of measure configured in your Teamleader account.

**Parameters:**
- `filters` (array): Not used for this endpoint
- `options` (array): Not used for this endpoint

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all units of measure
$units = Teamleader::unitsOfMeasure()->list();
```

## Helper Methods

The Units of Measure resource provides several convenient helper methods:

### `findByName()`

Find a unit of measure by name (case-insensitive).

```php
$unit = Teamleader::unitsOfMeasure()->findByName('piece');

if ($unit) {
    echo "Found: {$unit['name']} (ID: {$unit['id']})\n";
}
```

### `findById()`

Find a unit of measure by UUID.

```php
$unit = Teamleader::unitsOfMeasure()->findById('unit-uuid');

if ($unit) {
    echo "Found: {$unit['name']}\n";
}
```

### `asOptions()`

Get units as key-value pairs (id => name) for use in dropdowns.

```php
$options = Teamleader::unitsOfMeasure()->asOptions();

// Returns: ['uuid-1' => 'piece', 'uuid-2' => 'kilogram', ...]
```

### `asCollection()`

Get units as a Laravel Collection for easier manipulation.

```php
$collection = Teamleader::unitsOfMeasure()->asCollection();

// Use Collection methods
$filtered = $collection->filter(function($unit) {
    return strpos(strtolower($unit['name']), 'meter') !== false;
});
```

### `exists()`

Check if a unit exists by name.

```php
if (Teamleader::unitsOfMeasure()->exists('piece')) {
    echo "Unit 'piece' exists\n";
}
```

### `count()`

Get the total count of units of measure.

```php
$total = Teamleader::unitsOfMeasure()->count();
echo "Total units: {$total}\n";
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "f8a57a6f-dd1c-4f38-b2d7-7e06c08a1b96",
      "name": "piece"
    },
    {
      "id": "c3e7a891-23b4-4d65-9f4e-1a2b3c4d5e6f",
      "name": "kilogram"
    },
    {
      "id": "d4f8b902-34c5-5e76-0g5f-2b3c4d5e6f7g",
      "name": "hour"
    },
    {
      "id": "e5g9c013-45d6-6f87-1h6g-3c4d5e6f7g8h",
      "name": "meter"
    }
  ]
}
```

### Unit Object Properties

- `id` (string) - Unit of measure UUID
- `name` (string) - Unit name (e.g., piece, kilogram, hour)

## Usage Examples

### Get All Units

```php
$units = Teamleader::unitsOfMeasure()->list();

foreach ($units['data'] as $unit) {
    echo "Unit: {$unit['name']} (ID: {$unit['id']})\n";
}
```

### Find Unit by Name

```php
$unitName = 'piece';
$unit = Teamleader::unitsOfMeasure()->findByName($unitName);

if ($unit) {
    echo "Found unit '{$unitName}': {$unit['id']}\n";
} else {
    echo "Unit '{$unitName}' not found\n";
}
```

### Create Dropdown Options

```php
$options = Teamleader::unitsOfMeasure()->asOptions();

// Use in Blade template
// <select name="unit_of_measure_id">
//   @foreach($options as $id => $name)
//     <option value="{{ $id }}">{{ $name }}</option>
//   @endforeach
// </select>

// Or in plain PHP
echo '<select name="unit_of_measure_id">';
foreach ($options as $id => $name) {
    echo "<option value=\"{$id}\">{$name}</option>";
}
echo '</select>';
```

### Validate Unit ID

```php
function isValidUnitId($unitId)
{
    $unit = Teamleader::unitsOfMeasure()->findById($unitId);
    return $unit !== null;
}

// Usage
if (isValidUnitId($productData['unit_of_measure_id'])) {
    // Create product
} else {
    echo "Invalid unit of measure ID\n";
}
```

### Search Units by Keyword

```php
$searchTerm = 'meter';
$collection = Teamleader::unitsOfMeasure()->asCollection();

$results = $collection->filter(function($unit) use ($searchTerm) {
    return stripos($unit['name'], $searchTerm) !== false;
});

foreach ($results as $unit) {
    echo "Found: {$unit['name']}\n";
}
```

### Get Unit ID by Name

```php
function getUnitId($unitName)
{
    $unit = Teamleader::unitsOfMeasure()->findByName($unitName);
    return $unit ? $unit['id'] : null;
}

// Usage
$pieceUnitId = getUnitId('piece');
if ($pieceUnitId) {
    $product = Teamleader::products()->create([
        'name' => 'Widget',
        'code' => 'WID-001',
        'unit_of_measure_id' => $pieceUnitId
    ]);
}
```

### Check if Unit Exists

```php
$requiredUnits = ['piece', 'hour', 'kilogram'];

foreach ($requiredUnits as $unitName) {
    if (Teamleader::unitsOfMeasure()->exists($unitName)) {
        echo "✓ {$unitName} exists\n";
    } else {
        echo "✗ {$unitName} missing\n";
    }
}
```

## Common Use Cases

### Product Creation with Unit

```php
// Get the unit ID for 'piece'
$pieceUnit = Teamleader::unitsOfMeasure()->findByName('piece');

if (!$pieceUnit) {
    throw new Exception("Unit 'piece' not found");
}

// Create product with the unit
$product = Teamleader::products()->create([
    'name' => 'Wireless Mouse',
    'code' => 'MOUSE-001',
    'unit_of_measure_id' => $pieceUnit['id'],
    'selling_price' => [
        'amount' => 25.00,
        'currency' => 'EUR'
    ]
]);
```

### Bulk Product Import with Units

```php
$productsToImport = [
    ['name' => 'Widget', 'unit' => 'piece', 'price' => 10.00],
    ['name' => 'Cable', 'unit' => 'meter', 'price' => 2.50],
    ['name' => 'Consulting', 'unit' => 'hour', 'price' => 75.00],
];

// Get all units once
$units = Teamleader::unitsOfMeasure()->asOptions();

// Create a reverse lookup (name => id)
$unitsByName = [];
foreach ($units as $id => $name) {
    $unitsByName[strtolower($name)] = $id;
}

foreach ($productsToImport as $data) {
    $unitName = strtolower($data['unit']);
    
    if (!isset($unitsByName[$unitName])) {
        Log::warning("Unit '{$data['unit']}' not found, skipping {$data['name']}");
        continue;
    }
    
    $product = Teamleader::products()->create([
        'name' => $data['name'],
        'unit_of_measure_id' => $unitsByName[$unitName],
        'selling_price' => [
            'amount' => $data['price'],
            'currency' => 'EUR'
        ]
    ]);
    
    echo "Created: {$data['name']} ({$data['unit']})\n";
}
```

### Unit Type Validation

```php
function validateProductUnit($productData)
{
    $errors = [];
    
    if (empty($productData['unit_of_measure_id'])) {
        $errors[] = 'Unit of measure is required';
    } else {
        $unit = Teamleader::unitsOfMeasure()->findById($productData['unit_of_measure_id']);
        
        if (!$unit) {
            $errors[] = 'Invalid unit of measure ID';
        }
    }
    
    return $errors;
}

// Usage
$errors = validateProductUnit($productData);
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "Error: {$error}\n";
    }
}
```

### Cache Units for Performance

```php
use Illuminate\Support\Facades\Cache;

function getCachedUnits()
{
    return Cache::remember('units_of_measure', 86400, function () {
        return Teamleader::unitsOfMeasure()->list();
    });
}

function getCachedUnitOptions()
{
    return Cache::remember('unit_options', 86400, function () {
        return Teamleader::unitsOfMeasure()->asOptions();
    });
}

// Usage
$units = getCachedUnits();
$options = getCachedUnitOptions();
```

### Unit Statistics Report

```php
$products = Teamleader::products()->list();
$units = Teamleader::unitsOfMeasure()->asCollection();

$unitStats = $units->mapWithKeys(function($unit) {
    return [$unit['id'] => [
        'name' => $unit['name'],
        'product_count' => 0,
        'total_value' => 0
    ]];
});

foreach ($products['data'] as $product) {
    if (isset($product['unit_of_measure']['id'])) {
        $unitId = $product['unit_of_measure']['id'];
        
        if (isset($unitStats[$unitId])) {
            $unitStats[$unitId]['product_count']++;
            
            if (isset($product['selling_price']['amount'])) {
                $unitStats[$unitId]['total_value'] += $product['selling_price']['amount'];
            }
        }
    }
}

echo "Unit Usage Report:\n";
foreach ($unitStats as $stats) {
    if ($stats['product_count'] > 0) {
        echo "{$stats['name']}: {$stats['product_count']} products, €" . 
             number_format($stats['total_value'], 2) . "\n";
    }
}
```

### Convert Unit Names to IDs

```php
class UnitHelper
{
    protected static $units = null;
    
    public static function getUnitId($unitName)
    {
        if (self::$units === null) {
            self::loadUnits();
        }
        
        $searchName = strtolower(trim($unitName));
        
        foreach (self::$units as $unit) {
            if (strtolower($unit['name']) === $searchName) {
                return $unit['id'];
            }
        }
        
        return null;
    }
    
    protected static function loadUnits()
    {
        $response = Teamleader::unitsOfMeasure()->list();
        self::$units = $response['data'] ?? [];
    }
}

// Usage
$unitId = UnitHelper::getUnitId('piece');
```

## Best Practices

1. **Cache Units Data**: Units rarely change, so cache them
```php
$units = Cache::remember('units_of_measure', 86400, function () {
    return Teamleader::unitsOfMeasure()->list();
});
```

2. **Use Helper Methods**: Leverage built-in helpers for common tasks
```php
// Good - use helper
$unit = Teamleader::unitsOfMeasure()->findByName('piece');

// Avoid - manual search
$units = Teamleader::unitsOfMeasure()->list();
foreach ($units['data'] as $unit) {
    if ($unit['name'] === 'piece') {
        // Found it
    }
}
```

3. **Validate Before Using**: Always check if a unit exists
```php
$unit = Teamleader::unitsOfMeasure()->findByName('piece');

if (!$unit) {
    throw new Exception("Required unit 'piece' not found");
}
```

4. **Use asOptions() for Forms**: Generate dropdown options efficiently
```php
$options = Teamleader::unitsOfMeasure()->asOptions();
// Ready to use in select elements
```

5. **Check Unit Count**: Monitor configured units
```php
$count = Teamleader::unitsOfMeasure()->count();

if ($count === 0) {
    Log::warning('No units of measure configured');
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $units = Teamleader::unitsOfMeasure()->list();
    
    if (empty($units['data'])) {
        Log::warning('No units of measure configured in Teamleader');
    }
    
} catch (\Exception $e) {
    Log::error('Failed to fetch units of measure: ' . $e->getMessage());
}

// Handle unit not found
try {
    $unit = Teamleader::unitsOfMeasure()->findByName('invalid-unit');
    
    if (!$unit) {
        throw new \Exception('Unit not found: invalid-unit');
    }
    
} catch (\Exception $e) {
    Log::error('Unit error: ' . $e->getMessage());
}

// Validate unit before creating product
try {
    $unitName = 'piece';
    $unit = Teamleader::unitsOfMeasure()->findByName($unitName);
    
    if (!$unit) {
        throw new \Exception("Unit '{$unitName}' does not exist. Please configure it in Teamleader first.");
    }
    
    $product = Teamleader::products()->create([
        'name' => 'Product',
        'unit_of_measure_id' => $unit['id']
    ]);
    
} catch (\Exception $e) {
    Log::error('Product creation failed: ' . $e->getMessage());
}
```

## Related Resources

- **[Products](products.md)** - Use units when creating products
- **[Product Categories](categories.md)** - Organize products
- **[Quotations](../deals/quotations.md)** - Units are used in quote line items
- **[Invoices](../invoicing/invoices.md)** - Units are used in invoice line items

# Product Categories

Manage product categories in Teamleader Focus.

## Overview

The Product Categories resource provides read-only access to product categories in your Teamleader account. Product categories help organize and group products for better management and reporting. Each category can be associated with specific departments and ledger accounts for accounting purposes.

**Important:** This resource is read-only. Product categories must be created and managed through the Teamleader Focus web interface.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Filtering](#filtering)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`productCategories`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get all product categories with optional filtering.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options (not used for this endpoint)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all product categories
$categories = Teamleader::productCategories()->list();

// Filter by department
$categories = Teamleader::productCategories()->list([
    'department_id' => 'department-uuid'
]);
```

## Helper Methods

### `forDepartment()`

Get product categories for a specific department.

```php
$categories = Teamleader::productCategories()->forDepartment('department-uuid');
```

## Filtering

### Available Filters

#### `department_id`
Filter categories by department UUID.

```php
$categories = Teamleader::productCategories()->list([
    'department_id' => '080aac72-ff1a-4627-bfe3-146b6eee979c'
]);
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4",
      "name": "Electronics",
      "ledgers": [
        {
          "department": {
            "type": "department",
            "id": "080aac72-ff1a-4627-bfe3-146b6eee979c"
          },
          "sales_account": {
            "id": "account-uuid",
            "code": "7000",
            "description": "Sales - Products"
          },
          "purchase_account": {
            "id": "account-uuid",
            "code": "6000",
            "description": "Purchases - Products"
          }
        }
      ]
    },
    {
      "id": "3bb5b7b0-0df9-5962-c0c4-37bfb3fb25d5",
      "name": "Office Supplies",
      "ledgers": [
        {
          "department": {
            "type": "department",
            "id": "080aac72-ff1a-4627-bfe3-146b6eee979c"
          },
          "sales_account": {
            "id": "account-uuid",
            "code": "7010",
            "description": "Sales - Services"
          },
          "purchase_account": {
            "id": "account-uuid",
            "code": "6010",
            "description": "Purchases - Services"
          }
        }
      ]
    }
  ]
}
```

### Category Object Properties

- `id` (string) - Category UUID
- `name` (string) - Category name
- `ledgers` (array) - Array of ledger configurations per department
    - `department` (object) - Department reference
    - `sales_account` (object) - General ledger account for sales
    - `purchase_account` (object) - General ledger account for purchases

## Usage Examples

### Get All Categories

```php
$categories = Teamleader::productCategories()->list();

foreach ($categories['data'] as $category) {
    echo "Category: {$category['name']} (ID: {$category['id']})\n";
    
    // Access ledger information
    if (!empty($category['ledgers'])) {
        foreach ($category['ledgers'] as $ledger) {
            echo "  Department: {$ledger['department']['id']}\n";
            echo "  Sales Account: {$ledger['sales_account']['code']}\n";
            echo "  Purchase Account: {$ledger['purchase_account']['code']}\n";
        }
    }
}
```

### Get Categories for Department

```php
$departmentId = 'department-uuid';
$categories = Teamleader::productCategories()->forDepartment($departmentId);

echo "Categories in department:\n";
foreach ($categories['data'] as $category) {
    echo "- {$category['name']}\n";
}
```

### Find Category by Name

```php
$categories = Teamleader::productCategories()->list();
$categoryName = 'Electronics';

$foundCategory = null;
foreach ($categories['data'] as $category) {
    if (strcasecmp($category['name'], $categoryName) === 0) {
        $foundCategory = $category;
        break;
    }
}

if ($foundCategory) {
    echo "Found category: {$foundCategory['name']} ({$foundCategory['id']})\n";
}
```

### Create Category Dropdown

```php
$categories = Teamleader::productCategories()->list();

$options = [];
foreach ($categories['data'] as $category) {
    $options[$category['id']] = $category['name'];
}

// Use in forms
// <select name="category_id">
//   @foreach($options as $id => $name)
//     <option value="{{ $id }}">{{ $name }}</option>
//   @endforeach
// </select>
```

### Get Ledger Accounts for Category

```php
$categoryId = 'category-uuid';
$categories = Teamleader::productCategories()->list();

foreach ($categories['data'] as $category) {
    if ($category['id'] === $categoryId) {
        echo "Ledger accounts for {$category['name']}:\n";
        
        foreach ($category['ledgers'] as $ledger) {
            echo "\nDepartment: {$ledger['department']['id']}\n";
            echo "Sales Account: {$ledger['sales_account']['code']} - {$ledger['sales_account']['description']}\n";
            echo "Purchase Account: {$ledger['purchase_account']['code']} - {$ledger['purchase_account']['description']}\n";
        }
        break;
    }
}
```

## Common Use Cases

### Validate Product Category

```php
function isValidCategory($categoryId)
{
    $categories = Teamleader::productCategories()->list();
    
    foreach ($categories['data'] as $category) {
        if ($category['id'] === $categoryId) {
            return true;
        }
    }
    
    return false;
}

// Usage
if (isValidCategory($productData['category_id'])) {
    // Create product
}
```

### Category Analytics

```php
$categories = Teamleader::productCategories()->list();
$products = Teamleader::products()->list();

$categoryStats = [];

foreach ($categories['data'] as $category) {
    $categoryStats[$category['id']] = [
        'name' => $category['name'],
        'product_count' => 0,
        'total_value' => 0
    ];
}

foreach ($products['data'] as $product) {
    if (isset($product['product_category']['id'])) {
        $categoryId = $product['product_category']['id'];
        
        if (isset($categoryStats[$categoryId])) {
            $categoryStats[$categoryId]['product_count']++;
            
            if (isset($product['selling_price']['amount'])) {
                $categoryStats[$categoryId]['total_value'] += $product['selling_price']['amount'];
            }
        }
    }
}

echo "Category Statistics:\n";
foreach ($categoryStats as $stats) {
    echo "{$stats['name']}: {$stats['product_count']} products, €" . number_format($stats['total_value'], 2) . "\n";
}
```

### Cache Categories

```php
use Illuminate\Support\Facades\Cache;

function getCachedCategories()
{
    return Cache::remember('product_categories', 3600, function () {
        return Teamleader::productCategories()->list();
    });
}

// Usage
$categories = getCachedCategories();
```

### Map Categories to Departments

```php
$categories = Teamleader::productCategories()->list();
$departmentCategories = [];

foreach ($categories['data'] as $category) {
    foreach ($category['ledgers'] as $ledger) {
        $deptId = $ledger['department']['id'];
        
        if (!isset($departmentCategories[$deptId])) {
            $departmentCategories[$deptId] = [];
        }
        
        $departmentCategories[$deptId][] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'sales_account' => $ledger['sales_account']['code'],
            'purchase_account' => $ledger['purchase_account']['code']
        ];
    }
}

// Access categories for a specific department
$deptId = 'department-uuid';
if (isset($departmentCategories[$deptId])) {
    echo "Categories for this department:\n";
    foreach ($departmentCategories[$deptId] as $cat) {
        echo "- {$cat['name']}\n";
    }
}
```

## Best Practices

1. **Cache Category Data**: Categories change infrequently, so cache them
```php
$categories = Cache::remember('categories', 3600, function () {
    return Teamleader::productCategories()->list();
});
```

2. **Filter by Department**: When working with department-specific data
```php
// Good - only get relevant categories
$categories = Teamleader::productCategories()->forDepartment($departmentId);

// Avoid - getting all categories when you only need one department
$all = Teamleader::productCategories()->list();
```

3. **Use Helper Methods**: Leverage the `forDepartment()` helper for cleaner code
```php
// Good
$categories = Teamleader::productCategories()->forDepartment('dept-uuid');

// Works but less readable
$categories = Teamleader::productCategories()->list(['department_id' => 'dept-uuid']);
```

4. **Handle Empty Results**: Always check if categories exist
```php
$categories = Teamleader::productCategories()->list();

if (empty($categories['data'])) {
    // No categories found
    Log::warning('No product categories found');
}
```

5. **Store Category References**: When creating products, store the category ID
```php
$product = Teamleader::products()->create([
    'name' => 'Laptop',
    'code' => 'LAPTOP-001',
    'product_category_id' => $categoryId  // Store the reference
]);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $categories = Teamleader::productCategories()->list();
    
    if (empty($categories['data'])) {
        Log::info('No product categories configured');
    }
    
} catch (\Exception $e) {
    Log::error('Failed to fetch product categories: ' . $e->getMessage());
}

// Filter by department with validation
try {
    $departmentId = 'department-uuid';
    $categories = Teamleader::productCategories()->forDepartment($departmentId);
    
    if (empty($categories['data'])) {
        throw new \Exception("No categories found for department: {$departmentId}");
    }
    
} catch (\Exception $e) {
    Log::error('Error fetching categories: ' . $e->getMessage());
}
```

## Related Resources

- **[Products](products.md)** - Assign categories to products
- **[Departments](../general/departments.md)** - Department information
- **[Price Lists](price-lists.md)** - Product pricing
- **[Units of Measure](units-of-measure.md)** - Product measurement units

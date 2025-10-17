# Products

Manage products in Teamleader Focus.

## Overview

The Products resource provides full CRUD operations for managing products in Teamleader. Products can be physical goods or services that you sell or purchase. Each product can have pricing, stock levels, categories, suppliers, and custom fields.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [search()](#search)
    - [updatedSince()](#updatedsince)
- [Sideloading](#sideloading)
- [Filtering](#filtering)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`products`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ✅ Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all products with optional filtering and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, include)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all products
$products = Teamleader::products()->list();

// With filters
$products = Teamleader::products()->list([
    'term' => 'laptop'
]);

// With pagination
$products = Teamleader::products()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// With sideloading
$products = Teamleader::products()->list([], [
    'include' => 'suppliers,custom_fields'
]);
```

### `info()`

Get detailed information about a specific product.

**Parameters:**
- `id` (string): Product UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Basic info
$product = Teamleader::products()->info('product-uuid');

// With suppliers
$product = Teamleader::products()->info('product-uuid', 'suppliers');

// With multiple includes
$product = Teamleader::products()->info('product-uuid', ['suppliers', 'custom_fields']);

// Using fluent interface
$product = Teamleader::products()
    ->withSuppliers()
    ->withCustomFields()
    ->info('product-uuid');
```

### `create()`

Create a new product.

**Required fields:**
- `name` (string) OR `code` (string) - At least one is required

**Optional fields:**
- `code` (string) - Product code/SKU
- `description` (string) - Product description
- `purchase_price` (object) - Purchase price information
    - `amount` (decimal) - Price amount
    - `currency` (string) - Currency code (e.g., EUR)
- `selling_price` (object) - Selling price information
    - `amount` (decimal) - Price amount
    - `currency` (string) - Currency code
- `unit_of_measure_id` (string) - Unit of measure UUID
- `stock` (object) - Stock information
    - `amount` (decimal) - Current stock level
- `configuration` (object) - Product configuration
    - `stock_threshold` (object) - Low stock alert settings
        - `minimum` (decimal) - Minimum stock level
        - `action` (string) - Action to take (notify)
- `department_id` (string) - Department UUID
- `product_category_id` (string) - Category UUID
- `tax_rate_id` (string) - Tax rate UUID
- `custom_fields` (array) - Custom field values

**Example:**
```php
// Basic product
$product = Teamleader::products()->create([
    'name' => 'Wireless Mouse',
    'code' => 'MOUSE-001'
]);

// Complete product
$product = Teamleader::products()->create([
    'name' => 'Ergonomic Wireless Mouse',
    'code' => 'MOUSE-ERG-001',
    'description' => 'Comfortable ergonomic design with 2.4GHz wireless connectivity',
    'purchase_price' => [
        'amount' => 15.00,
        'currency' => 'EUR'
    ],
    'selling_price' => [
        'amount' => 35.00,
        'currency' => 'EUR'
    ],
    'unit_of_measure_id' => 'unit-uuid',
    'stock' => [
        'amount' => 50
    ],
    'configuration' => [
        'stock_threshold' => [
            'minimum' => 10,
            'action' => 'notify'
        ]
    ],
    'department_id' => 'dept-uuid',
    'product_category_id' => 'category-uuid',
    'tax_rate_id' => 'tax-rate-uuid'
]);
```

### `update()`

Update an existing product.

**Parameters:**
- `id` (string): Product UUID
- `data` (array): Fields to update (same as create)

**Example:**
```php
Teamleader::products()->update('product-uuid', [
    'name' => 'Updated Product Name',
    'selling_price' => [
        'amount' => 39.99,
        'currency' => 'EUR'
    ],
    'stock' => [
        'amount' => 75
    ]
]);
```

### `delete()`

Delete a product.

**Parameters:**
- `id` (string): Product UUID

**Example:**
```php
Teamleader::products()->delete('product-uuid');
```

### `search()`

Search products by name or code.

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options (pagination, filters)

**Example:**
```php
// Search by name or code
$products = Teamleader::products()->search('laptop');

// With additional filters
$products = Teamleader::products()->search('laptop', [
    'filters' => ['ids' => ['product-uuid-1', 'product-uuid-2']],
    'page_size' => 50
]);
```

### `updatedSince()`

Get products updated since a specific date.

**Parameters:**
- `date` (string): ISO 8601 date string
- `options` (array): Additional options

**Example:**
```php
// Get products updated in the last week
$products = Teamleader::products()->updatedSince('2024-01-15T00:00:00+00:00');

// With pagination
$products = Teamleader::products()->updatedSince('2024-01-15T00:00:00+00:00', [
    'page_size' => 100
]);
```

## Sideloading

Available includes for products:

- `suppliers` - Supplier information
- `custom_fields` - Custom field values

### Using Sideloading

```php
// Method 1: In list options
$products = Teamleader::products()->list([], [
    'include' => 'suppliers,custom_fields'
]);

// Method 2: In info method
$product = Teamleader::products()->info('product-uuid', 'suppliers');

// Method 3: Fluent interface
$products = Teamleader::products()
    ->withSuppliers()
    ->withCustomFields()
    ->list();

// Method 4: Generic with() method
$products = Teamleader::products()
    ->with(['suppliers', 'custom_fields'])
    ->list();
```

## Filtering

Available filters for products:

- `ids` - Array of product UUIDs
- `term` - Search by name or code (case-insensitive)
- `updated_since` - ISO 8601 datetime string

**Example:**
```php
// Filter by IDs
$products = Teamleader::products()->list([
    'ids' => ['product-uuid-1', 'product-uuid-2']
]);

// Search by term
$products = Teamleader::products()->list([
    'term' => 'laptop'
]);

// Updated since
$products = Teamleader::products()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);

// Combine filters
$products = Teamleader::products()->list([
    'term' => 'mouse',
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);
```

## Response Structure

### Product Object

```json
{
  "id": "product-uuid",
  "name": "Ergonomic Wireless Mouse",
  "code": "MOUSE-ERG-001",
  "description": "Comfortable ergonomic design",
  "purchase_price": {
    "amount": 15.00,
    "currency": "EUR"
  },
  "selling_price": {
    "amount": 35.00,
    "currency": "EUR"
  },
  "unit_of_measure": {
    "type": "unitOfMeasure",
    "id": "unit-uuid"
  },
  "stock": {
    "amount": 50.0
  },
  "configuration": {
    "stock_threshold": {
      "minimum": 10.0,
      "action": "notify"
    }
  },
  "department": {
    "type": "department",
    "id": "dept-uuid"
  },
  "product_category": {
    "type": "productCategory",
    "id": "category-uuid"
  },
  "tax_rate": {
    "type": "taxRate",
    "id": "tax-rate-uuid"
  },
  "created_at": "2024-01-15T10:00:00+00:00",
  "updated_at": "2024-01-20T14:30:00+00:00"
}
```

### With Suppliers Included

```json
{
  "data": {
    "id": "product-uuid",
    "name": "Product Name",
    "...": "..."
  },
  "included": {
    "suppliers": [
      {
        "type": "company",
        "id": "supplier-uuid"
      }
    ]
  }
}
```

## Usage Examples

### Create Simple Product

```php
$product = Teamleader::products()->create([
    'name' => 'Office Chair',
    'code' => 'CHAIR-001',
    'selling_price' => [
        'amount' => 199.99,
        'currency' => 'EUR'
    ]
]);

echo "Created product: {$product['data']['name']} (ID: {$product['data']['id']})\n";
```

### Update Product Stock

```php
$productId = 'product-uuid';
$currentProduct = Teamleader::products()->info($productId);

// Increase stock by 20
$newStock = $currentProduct['data']['stock']['amount'] + 20;

Teamleader::products()->update($productId, [
    'stock' => ['amount' => $newStock]
]);
```

### Search and Update Prices

```php
$products = Teamleader::products()->search('laptop');

foreach ($products['data'] as $product) {
    $currentPrice = $product['selling_price']['amount'];
    $newPrice = $currentPrice * 1.1; // 10% increase
    
    Teamleader::products()->update($product['id'], [
        'selling_price' => [
            'amount' => $newPrice,
            'currency' => $product['selling_price']['currency']
        ]
    ]);
    
    echo "Updated {$product['name']}: €{$currentPrice} → €{$newPrice}\n";
}
```

### Get Products with Low Stock

```php
$products = Teamleader::products()->list();

$lowStockProducts = [];
foreach ($products['data'] as $product) {
    $currentStock = $product['stock']['amount'] ?? 0;
    $minimumStock = $product['configuration']['stock_threshold']['minimum'] ?? 0;
    
    if ($currentStock < $minimumStock) {
        $lowStockProducts[] = [
            'name' => $product['name'],
            'code' => $product['code'],
            'current_stock' => $currentStock,
            'minimum_stock' => $minimumStock
        ];
    }
}

if (!empty($lowStockProducts)) {
    echo "Low Stock Alert:\n";
    foreach ($lowStockProducts as $item) {
        echo "- {$item['name']} ({$item['code']}): {$item['current_stock']} (min: {$item['minimum_stock']})\n";
    }
}
```

### Bulk Import Products

```php
$productsToImport = [
    ['name' => 'Product 1', 'code' => 'PROD-001', 'price' => 29.99],
    ['name' => 'Product 2', 'code' => 'PROD-002', 'price' => 39.99],
    ['name' => 'Product 3', 'code' => 'PROD-003', 'price' => 49.99],
];

foreach ($productsToImport as $data) {
    try {
        $product = Teamleader::products()->create([
            'name' => $data['name'],
            'code' => $data['code'],
            'selling_price' => [
                'amount' => $data['price'],
                'currency' => 'EUR'
            ]
        ]);
        
        echo "Imported: {$data['name']}\n";
        
    } catch (Exception $e) {
        Log::error("Failed to import {$data['name']}: " . $e->getMessage());
    }
}
```

### Get Products by Category

```php
$categoryId = 'category-uuid';
$products = Teamleader::products()->list();

$categoryProducts = array_filter($products['data'], function($product) use ($categoryId) {
    return isset($product['product_category']['id']) && 
           $product['product_category']['id'] === $categoryId;
});

foreach ($categoryProducts as $product) {
    echo "{$product['name']} - €{$product['selling_price']['amount']}\n";
}
```

## Common Use Cases

### Inventory Management

```php
function updateInventory($productId, $quantityChange, $operation = 'add')
{
    $product = Teamleader::products()->info($productId);
    $currentStock = $product['data']['stock']['amount'] ?? 0;
    
    if ($operation === 'add') {
        $newStock = $currentStock + $quantityChange;
    } else {
        $newStock = $currentStock - $quantityChange;
    }
    
    // Ensure stock doesn't go negative
    $newStock = max(0, $newStock);
    
    Teamleader::products()->update($productId, [
        'stock' => ['amount' => $newStock]
    ]);
    
    return $newStock;
}

// Usage
$newStock = updateInventory('product-uuid', 10, 'add');
echo "New stock level: {$newStock}\n";
```

### Price List Export

```php
$products = Teamleader::products()->list();

$csvData = [];
$csvData[] = ['Code', 'Name', 'Purchase Price', 'Selling Price', 'Margin %'];

foreach ($products['data'] as $product) {
    $purchasePrice = $product['purchase_price']['amount'] ?? 0;
    $sellingPrice = $product['selling_price']['amount'] ?? 0;
    
    $margin = 0;
    if ($purchasePrice > 0) {
        $margin = (($sellingPrice - $purchasePrice) / $purchasePrice) * 100;
    }
    
    $csvData[] = [
        $product['code'] ?? '',
        $product['name'],
        number_format($purchasePrice, 2),
        number_format($sellingPrice, 2),
        number_format($margin, 2)
    ];
}

// Write to CSV
$fp = fopen('price_list.csv', 'w');
foreach ($csvData as $row) {
    fputcsv($fp, $row);
}
fclose($fp);
```

### Sync Products with External System

```php
$lastSyncDate = '2024-01-01T00:00:00+00:00';
$updatedProducts = Teamleader::products()->updatedSince($lastSyncDate);

foreach ($updatedProducts['data'] as $product) {
    // Sync to external system
    syncToExternalSystem([
        'id' => $product['id'],
        'name' => $product['name'],
        'code' => $product['code'],
        'price' => $product['selling_price']['amount'],
        'stock' => $product['stock']['amount']
    ]);
    
    echo "Synced: {$product['name']}\n";
}
```

### Product Catalog Generator

```php
$products = Teamleader::products()
    ->withCustomFields()
    ->list();

foreach ($products['data'] as $product) {
    echo "## {$product['name']}\n";
    echo "**Code:** {$product['code']}\n";
    echo "**Price:** €{$product['selling_price']['amount']}\n";
    
    if (!empty($product['description'])) {
        echo "**Description:** {$product['description']}\n";
    }
    
    echo "\n---\n\n";
}
```

## Best Practices

1. **Always Provide Name or Code**: At least one is required for creation
```php
// Good
$product = Teamleader::products()->create([
    'name' => 'Product Name',
    'code' => 'PROD-001'
]);

// Will fail - missing both
$product = Teamleader::products()->create([
    'description' => 'Some product'
]);
```

2. **Use Pagination for Large Lists**: Products can be numerous
```php
$page = 1;
do {
    $products = Teamleader::products()->list([], [
        'page_size' => 100,
        'page_number' => $page
    ]);
    
    // Process products...
    
    $page++;
} while (count($products['data']) === 100);
```

3. **Include Suppliers When Needed**: Use sideloading efficiently
```php
// Good - get suppliers in one request
$products = Teamleader::products()->withSuppliers()->list();

// Avoid - making separate requests for each product
$products = Teamleader::products()->list();
foreach ($products['data'] as $product) {
    // Don't do this in a loop
}
```

4. **Validate Stock Changes**: Prevent negative stock
```php
$currentStock = $product['stock']['amount'] ?? 0;
$quantityToRemove = 10;

if ($currentStock >= $quantityToRemove) {
    Teamleader::products()->update($productId, [
        'stock' => ['amount' => $currentStock - $quantityToRemove]
    ]);
}
```

5. **Use Search for Lookups**: More efficient than filtering locally
```php
// Good - search on server
$products = Teamleader::products()->search('laptop');

// Avoid - fetching all and filtering
$all = Teamleader::products()->list();
// Then filtering in PHP
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $product = Teamleader::products()->create([
        'name' => 'New Product',
        'code' => 'PROD-001',
        'selling_price' => [
            'amount' => 99.99,
            'currency' => 'EUR'
        ]
    ]);
    
} catch (\InvalidArgumentException $e) {
    // Validation error
    Log::error('Invalid product data: ' . $e->getMessage());
    
} catch (\Exception $e) {
    // API error
    Log::error('Failed to create product: ' . $e->getMessage());
}

// Handle updates with validation
try {
    $product = Teamleader::products()->info('product-uuid');
    
    Teamleader::products()->update('product-uuid', [
        'stock' => ['amount' => $product['data']['stock']['amount'] + 10]
    ]);
    
} catch (\Exception $e) {
    Log::error('Failed to update stock: ' . $e->getMessage());
}
```

## Related Resources

- **[Product Categories](categories.md)** - Organize products into categories
- **[Price Lists](price-lists.md)** - Manage product pricing
- **[Units of Measure](units-of-measure.md)** - Product measurement units
- **[Quotations](../deals/quotations.md)** - Add products to quotes
- **[Invoices](../invoicing/invoices.md)** - Add products to invoices
- **[Orders](../deals/orders.md)** - Manage product orders

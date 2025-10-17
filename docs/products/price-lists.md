# Price Lists

Manage price lists in Teamleader Focus.

## Overview

The Price Lists resource provides read-only access to price lists in your Teamleader account. Price lists allow you to define different pricing strategies for products, such as retail prices, wholesale prices, or customer-specific pricing. Each price list can contain custom prices for your products.

**Important:** This resource is read-only. Price lists must be created and managed through the Teamleader Focus web interface.

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

`priceLists`

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

Get all price lists with optional filtering.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options (not used for this endpoint)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all price lists
$priceLists = Teamleader::priceLists()->list();

// Filter by specific IDs
$priceLists = Teamleader::priceLists()->list([
    'ids' => ['pricelist-uuid-1', 'pricelist-uuid-2']
]);
```

## Helper Methods

### `byIds()`

Get specific price lists by their UUIDs.

```php
$priceLists = Teamleader::priceLists()->byIds([
    'pricelist-uuid-1',
    'pricelist-uuid-2'
]);
```

## Filtering

### Available Filters

#### `ids`
Filter price lists by specific UUIDs.

```php
$priceLists = Teamleader::priceLists()->list([
    'ids' => [
        '9d5e8d1a-1234-5678-9abc-def012345678',
        '8c4d7c0b-2345-6789-0bcd-ef1234567890'
    ]
]);
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "9d5e8d1a-1234-5678-9abc-def012345678",
      "name": "Retail Price List",
      "description": "Standard retail pricing",
      "currency": {
        "code": "EUR"
      },
      "is_default": true,
      "created_at": "2023-01-15T10:00:00+00:00",
      "updated_at": "2024-01-15T14:30:00+00:00"
    },
    {
      "id": "8c4d7c0b-2345-6789-0bcd-ef1234567890",
      "name": "Wholesale Price List",
      "description": "Bulk purchase pricing",
      "currency": {
        "code": "EUR"
      },
      "is_default": false,
      "created_at": "2023-02-01T09:00:00+00:00",
      "updated_at": "2024-01-20T11:15:00+00:00"
    }
  ]
}
```

### Price List Object Properties

- `id` (string) - Price list UUID
- `name` (string) - Price list name
- `description` (string) - Price list description
- `currency` (object) - Currency information
    - `code` (string) - Currency code (e.g., EUR, USD)
- `is_default` (boolean) - Whether this is the default price list
- `created_at` (string) - Creation timestamp
- `updated_at` (string) - Last update timestamp

## Usage Examples

### Get All Price Lists

```php
$priceLists = Teamleader::priceLists()->list();

foreach ($priceLists['data'] as $priceList) {
    echo "Price List: {$priceList['name']}\n";
    echo "Currency: {$priceList['currency']['code']}\n";
    echo "Default: " . ($priceList['is_default'] ? 'Yes' : 'No') . "\n";
    echo "---\n";
}
```

### Find Default Price List

```php
$priceLists = Teamleader::priceLists()->list();

$defaultPriceList = null;
foreach ($priceLists['data'] as $priceList) {
    if ($priceList['is_default']) {
        $defaultPriceList = $priceList;
        break;
    }
}

if ($defaultPriceList) {
    echo "Default price list: {$defaultPriceList['name']}\n";
}
```

### Get Specific Price Lists

```php
$ids = ['pricelist-uuid-1', 'pricelist-uuid-2'];
$priceLists = Teamleader::priceLists()->byIds($ids);

foreach ($priceLists['data'] as $priceList) {
    echo "{$priceList['name']}: {$priceList['description']}\n";
}
```

### Create Price List Dropdown

```php
$priceLists = Teamleader::priceLists()->list();

$options = [];
foreach ($priceLists['data'] as $priceList) {
    $options[$priceList['id']] = $priceList['name'];
}

// Use in forms
// <select name="price_list_id">
//   @foreach($options as $id => $name)
//     <option value="{{ $id }}">{{ $name }}</option>
//   @endforeach
// </select>
```

### Filter by Currency

```php
$priceLists = Teamleader::priceLists()->list();
$currencyCode = 'EUR';

$filteredLists = array_filter($priceLists['data'], function($priceList) use ($currencyCode) {
    return $priceList['currency']['code'] === $currencyCode;
});

foreach ($filteredLists as $priceList) {
    echo "{$priceList['name']} (€)\n";
}
```

## Common Use Cases

### Get Customer-Specific Pricing

```php
// When viewing a customer/company
$company = Teamleader::companies()->info('company-uuid');

// Check if company has a specific price list
if (isset($company['data']['price_list']['id'])) {
    $priceListId = $company['data']['price_list']['id'];
    $priceLists = Teamleader::priceLists()->byIds([$priceListId]);
    
    echo "Customer uses: {$priceLists['data'][0]['name']}\n";
} else {
    // Use default price list
    $allPriceLists = Teamleader::priceLists()->list();
    foreach ($allPriceLists['data'] as $priceList) {
        if ($priceList['is_default']) {
            echo "Customer uses default: {$priceList['name']}\n";
            break;
        }
    }
}
```

### Validate Price List

```php
function isValidPriceList($priceListId)
{
    $priceLists = Teamleader::priceLists()->list();
    
    foreach ($priceLists['data'] as $priceList) {
        if ($priceList['id'] === $priceListId) {
            return true;
        }
    }
    
    return false;
}

// Usage
if (isValidPriceList($customerId['price_list_id'])) {
    // Proceed with quote/order
}
```

### Price List Reporting

```php
$priceLists = Teamleader::priceLists()->list();
$companies = Teamleader::companies()->list();

$priceListUsage = [];

// Initialize counts
foreach ($priceLists['data'] as $priceList) {
    $priceListUsage[$priceList['id']] = [
        'name' => $priceList['name'],
        'customer_count' => 0,
        'is_default' => $priceList['is_default']
    ];
}

// Count usage
foreach ($companies['data'] as $company) {
    if (isset($company['price_list']['id'])) {
        $priceListId = $company['price_list']['id'];
        if (isset($priceListUsage[$priceListId])) {
            $priceListUsage[$priceListId]['customer_count']++;
        }
    }
}

echo "Price List Usage Report:\n";
foreach ($priceListUsage as $stats) {
    $default = $stats['is_default'] ? ' (Default)' : '';
    echo "{$stats['name']}{$default}: {$stats['customer_count']} customers\n";
}
```

### Cache Price Lists

```php
use Illuminate\Support\Facades\Cache;

function getCachedPriceLists()
{
    return Cache::remember('price_lists', 3600, function () {
        return Teamleader::priceLists()->list();
    });
}

// Usage
$priceLists = getCachedPriceLists();
```

### Get Price List by Name

```php
function getPriceListByName($name)
{
    $priceLists = Teamleader::priceLists()->list();
    
    foreach ($priceLists['data'] as $priceList) {
        if (strcasecmp($priceList['name'], $name) === 0) {
            return $priceList;
        }
    }
    
    return null;
}

// Usage
$wholesalePriceList = getPriceListByName('Wholesale');
if ($wholesalePriceList) {
    echo "Found: {$wholesalePriceList['name']} ({$wholesalePriceList['id']})\n";
}
```

## Best Practices

1. **Cache Price List Data**: Price lists change infrequently
```php
$priceLists = Cache::remember('price_lists', 3600, function () {
    return Teamleader::priceLists()->list();
});
```

2. **Use byIds() for Specific Lists**: When you need specific price lists
```php
// Good - only fetch what you need
$priceLists = Teamleader::priceLists()->byIds([$priceListId]);

// Avoid - fetching all when you only need one
$all = Teamleader::priceLists()->list();
```

3. **Handle Default Price List**: Always have fallback logic
```php
$priceListId = $company['price_list']['id'] ?? null;

if (!$priceListId) {
    // Use default price list
    $allLists = Teamleader::priceLists()->list();
    foreach ($allLists['data'] as $list) {
        if ($list['is_default']) {
            $priceListId = $list['id'];
            break;
        }
    }
}
```

4. **Validate Price Lists**: Check price list exists before using
```php
$priceListIds = array_column($priceLists['data'], 'id');

if (!in_array($requestedPriceListId, $priceListIds)) {
    throw new \Exception('Invalid price list');
}
```

5. **Store Price List References**: When working with customers
```php
$company = Teamleader::companies()->create([
    'name' => 'Customer Name',
    'price_list_id' => $priceListId  // Store the reference
]);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $priceLists = Teamleader::priceLists()->list();
    
    if (empty($priceLists['data'])) {
        Log::warning('No price lists configured');
    }
    
} catch (\Exception $e) {
    Log::error('Failed to fetch price lists: ' . $e->getMessage());
}

// Get specific price lists with validation
try {
    $ids = ['pricelist-uuid-1', 'pricelist-uuid-2'];
    
    if (empty($ids)) {
        throw new \InvalidArgumentException('At least one price list ID must be provided');
    }
    
    $priceLists = Teamleader::priceLists()->byIds($ids);
    
    if (count($priceLists['data']) !== count($ids)) {
        Log::warning('Some price lists were not found');
    }
    
} catch (\InvalidArgumentException $e) {
    Log::error('Invalid input: ' . $e->getMessage());
} catch (\Exception $e) {
    Log::error('Error fetching price lists: ' . $e->getMessage());
}
```

## Related Resources

- **[Products](products.md)** - Products use price lists for pricing
- **[Companies](../crm/companies.md)** - Assign price lists to customers
- **[Quotations](../deals/quotations.md)** - Use price lists in quotes
- **[Invoices](../invoicing/invoices.md)** - Price lists affect invoice pricing

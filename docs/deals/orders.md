# Orders Resource

The Orders resource allows you to retrieve and view orders in Teamleader Focus. Orders are read-only in the API.

## Overview

- **Base Path**: `orders`
- **Supports**: Read, List
- **Available Actions**: None (read-only)
- **Pagination**: No
- **Sorting**: No
- **Filtering**: Yes (by IDs)
- **Sideloading**: Yes (custom_fields)

## Available Methods

### List Orders

```php
list(array $filters = [], array $options = []): array
```

Get a list of orders with optional filtering.

### Get Order Info

```php
info(string $id, mixed $includes = null): array
```

Get detailed information about a specific order.

## Usage Examples

### Basic Operations

#### List All Orders

```php
$orders = $teamleader->orders()->list();
```

#### List Specific Orders

```php
$orders = $teamleader->orders()->list([
    'ids' => [
        '31b9d864-da1d-060e-9811-f683896aeb11',
        '7c3c4edc-fd8d-0cc3-bd1e-9f3f9d7b7db2'
    ]
]);
```

#### Get Order with Custom Fields

```php
$order = $teamleader->orders()
    ->include('custom_fields')
    ->info('6fac0bf0-e803-424e-af67-76863a3d7d16');

// Access custom fields
foreach ($order['data']['custom_fields'] as $field) {
    $definitionId = $field['definition']['id'];
    $value = $field['value'];
    // Process custom field data
}
```

#### Get Single Order

```php
$order = $teamleader->orders()->info('6fac0bf0-e803-424e-af67-76863a3d7d16');

// Access order details
$name = $order['data']['name'];
$orderDate = $order['data']['order_date'];
$deliveryDate = $order['data']['delivery_date'];
$totalTaxInclusive = $order['data']['total']['tax_inclusive'];
$webUrl = $order['data']['web_url'];
```

### Convenience Methods

#### Get Orders by IDs

```php
$orders = $teamleader->orders()->byIds([
    '31b9d864-da1d-060e-9811-f683896aeb11',
    '7c3c4edc-fd8d-0cc3-bd1e-9f3f9d7b7db2'
]);
```

## Working with Order Data

### Accessing Order Totals

```php
$order = $teamleader->orders()->info('order-uuid');

// Tax exclusive total
$taxExclusive = $order['data']['total']['tax_exclusive']['amount'];
$currency = $order['data']['total']['tax_exclusive']['currency'];

// Tax inclusive total
$taxInclusive = $order['data']['total']['tax_inclusive']['amount'];

// Purchase prices (if available)
$purchasePriceTaxExclusive = $order['data']['total']['purchase_price_tax_exclusive'];
$purchasePriceTaxInclusive = $order['data']['total']['purchase_price_tax_inclusive'];

// Tax breakdown
foreach ($order['data']['total']['taxes'] as $tax) {
    $rate = $tax['rate'];
    $taxableAmount = $tax['taxable']['amount'];
    $taxAmount = $tax['tax']['amount'];
}
```

### Working with Grouped Lines

```php
$order = $teamleader->orders()->info('order-uuid');

foreach ($order['data']['grouped_lines'] as $group) {
    $sectionTitle = $group['section']['title'];
    
    foreach ($group['line_items'] as $item) {
        $quantity = $item['quantity'];
        $description = $item['description'];
        $extendedDescription = $item['extended_description']; // Markdown formatted
        
        // Unit price
        $unitPrice = $item['unit_price']['amount'];
        $tax = $item['unit_price']['tax']; // 'excluding'
        
        // Tax information
        $taxId = $item['tax']['id'];
        
        // Discount (if applicable)
        if (isset($item['discount'])) {
            $discountValue = $item['discount']['value'];
            $discountType = $item['discount']['type']; // 'percentage'
        }
        
        // Totals for this line item
        $lineTotalTaxExclusive = $item['total']['tax_exclusive']['amount'];
        $lineTotalTaxInclusive = $item['total']['tax_inclusive']['amount'];
        
        // Product reference (if available)
        if (isset($item['product'])) {
            $productId = $item['product']['id'];
            $productType = $item['product']['type'];
        }
        
        // Product category (if available)
        if (isset($item['product_category'])) {
            $categoryId = $item['product_category']['id'];
        }
    }
}
```

### Accessing Payment Terms

```php
$order = $teamleader->orders()->info('order-uuid');

if (isset($order['data']['payment_term'])) {
    $paymentType = $order['data']['payment_term']['type'];
    // Types: 'cash', 'end_of_month', 'after_invoice_date'
    
    if ($paymentType !== 'cash') {
        $days = $order['data']['payment_term']['days'];
        // e.g., "net 30" would be type='after_invoice_date', days=30
    }
}
```

### Accessing Related Entities

```php
$order = $teamleader->orders()->info('order-uuid');

// Supplier (company or contact)
if (isset($order['data']['supplier'])) {
    $supplierType = $order['data']['supplier']['type']; // 'company' or 'contact'
    $supplierId = $order['data']['supplier']['id'];
}

// Department
if (isset($order['data']['department'])) {
    $departmentId = $order['data']['department']['id'];
}

// Deal
if (isset($order['data']['deal'])) {
    $dealId = $order['data']['deal']['id'];
}

// Project
if (isset($order['data']['project'])) {
    $projectId = $order['data']['project']['id'];
}

// Assignee
if (isset($order['data']['assignee'])) {
    $assigneeId = $order['data']['assignee']['id'];
    $assigneeType = $order['data']['assignee']['type'];
}
```

### Working with Custom Fields

```php
$order = $teamleader->orders()
    ->include('custom_fields')
    ->info('order-uuid');

if (isset($order['data']['custom_fields'])) {
    foreach ($order['data']['custom_fields'] as $field) {
        $fieldId = $field['definition']['id'];
        $fieldType = $field['definition']['type']; // 'customFieldDefinition'
        
        // Value can be string, number, boolean, or object depending on field type
        $value = $field['value'];
        
        // Process based on value type
        if (is_string($value)) {
            // String custom field
            $stringValue = $value;
        } elseif (is_numeric($value)) {
            // Numeric custom field
            $numericValue = $value;
        } elseif (is_bool($value)) {
            // Boolean custom field
            $boolValue = $value;
        } elseif (is_array($value)) {
            // Multiple selection or object type
            // Handle accordingly
        }
    }
}
```

## Data Structures

### Payment Term Types

- `cash` - Payment on delivery (no days field required)
- `end_of_month` - Payment at the end of the month plus X days
- `after_invoice_date` - Payment X days after invoice date

### Supplier Types

- `company` - Supplier is a company
- `contact` - Supplier is a contact person

### Order Structure

An order contains:
- Basic information (name, dates, payment terms)
- Grouped line items with sections
- Total calculations (tax exclusive/inclusive)
- Purchase price information
- Tax breakdown
- Related entities (supplier, department, deal, project, assignee)
- Web URL for viewing in Teamleader
- Optional custom fields

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $order = $teamleader->orders()->info('invalid-id');
} catch (TeamleaderException $e) {
    Log::error('Failed to retrieve order', [
        'message' => $e->getMessage(),
        'status' => $e->getCode()
    ]);
}
```

## Rate Limiting

Each order operation counts towards your API rate limit:

- **List operations**: 1 request
- **Info operations**: 1 request

## Notes

- Orders are **read-only** in the Teamleader API
- No create, update, or delete operations are supported
- Extended descriptions support Markdown formatting
- Line items can have discounts applied at the item level
- Custom fields must be explicitly requested using `includes=custom_fields`
- The `web_url` field provides a direct link to view the order in Teamleader Focus
- Purchase prices are nullable and may not always be available
- Payment term days are not required when type is 'cash'
- All monetary amounts include both amount and currency code

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class OrderController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $orders = $teamleader->orders()->list();
        
        return view('orders.index', compact('orders'));
    }
    
    public function show(TeamleaderSDK $teamleader, string $id)
    {
        $order = $teamleader->orders()
            ->include('custom_fields')
            ->info($id);
        
        return view('orders.show', compact('order'));
    }
    
    public function bySupplier(TeamleaderSDK $teamleader, string $supplierId)
    {
        // Note: The API doesn't support filtering by supplier directly
        // You would need to fetch all orders and filter in your application
        $allOrders = $teamleader->orders()->list();
        
        $supplierOrders = array_filter($allOrders['data'], function($order) use ($supplierId) {
            return isset($order['supplier']) && $order['supplier']['id'] === $supplierId;
        });
        
        return view('orders.supplier', compact('supplierOrders'));
    }
}
```

## Advanced Usage

### Calculate Order Margin

```php
$order = $teamleader->orders()->info('order-uuid');

$totalRevenue = $order['data']['total']['tax_exclusive']['amount'];
$totalCost = 0;

if (isset($order['data']['total']['purchase_price_tax_exclusive'])) {
    $totalCost = $order['data']['total']['purchase_price_tax_exclusive']['amount'];
}

$margin = $totalRevenue - $totalCost;
$marginPercentage = $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0;

echo "Margin: {$margin} ({$marginPercentage}%)";
```

### Generate Order Report

```php
$orders = $teamleader->orders()->list();

$report = [
    'total_orders' => count($orders['data']),
    'total_revenue_tax_exclusive' => 0,
    'total_revenue_tax_inclusive' => 0,
    'by_currency' => []
];

foreach ($orders['data'] as $order) {
    $currency = $order['total']['tax_exclusive']['currency'];
    $taxExclusive = $order['total']['tax_exclusive']['amount'];
    $taxInclusive = $order['total']['tax_inclusive']['amount'];
    
    if (!isset($report['by_currency'][$currency])) {
        $report['by_currency'][$currency] = [
            'count' => 0,
            'tax_exclusive' => 0,
            'tax_inclusive' => 0
        ];
    }
    
    $report['by_currency'][$currency]['count']++;
    $report['by_currency'][$currency]['tax_exclusive'] += $taxExclusive;
    $report['by_currency'][$currency]['tax_inclusive'] += $taxInclusive;
}

return $report;
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

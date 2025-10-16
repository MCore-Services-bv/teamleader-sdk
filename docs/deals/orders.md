# Orders

Retrieve and view orders in Teamleader Focus.

## Overview

The Orders resource provides read-only access to orders in your Teamleader account. Orders are typically created when quotations are accepted or can be entered directly for purchases from suppliers. While you cannot create or modify orders through this endpoint, you can retrieve order information for reporting and integration purposes.

**Important:** The Orders resource is read-only. Orders are typically created through the quotation acceptance process or manually in the Teamleader interface. You cannot create, update, or delete orders through this API endpoint.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sideloading](#sideloading)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`orders`

## Capabilities

- **Pagination**: ❌ Not Supported (all results returned)
- **Filtering**: ✅ Supported (ids only)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ✅ Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get all orders with optional filtering.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (includes)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all orders
$orders = Teamleader::orders()->list();

// Get specific orders by ID
$orders = Teamleader::orders()->list([
    'ids' => ['order-uuid-1', 'order-uuid-2']
]);

// With custom fields
$orders = Teamleader::orders()->list([], [
    'includes' => 'custom_fields'
]);
```

### `info()`

Get detailed information about a specific order.

**Parameters:**
- `id` (string): Order UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Get order information
$order = Teamleader::orders()->info('order-uuid');

// With custom fields
$order = Teamleader::orders()->info('order-uuid', 'custom_fields');

// Using fluent interface
$order = Teamleader::orders()
    ->with('custom_fields')
    ->info('order-uuid');
```

## Helper Methods

The Orders resource provides convenient helper methods:

### `byIds()`

Get specific orders by their UUIDs.

```php
$orders = Teamleader::orders()->byIds([
    'order-uuid-1',
    'order-uuid-2'
]);
```

### Information Methods

```php
// Get payment term types
$types = Teamleader::orders()->getPaymentTermTypes();
// Returns: ['cash', 'end_of_month', 'after_invoice_date']

// Get supplier types
$types = Teamleader::orders()->getSupplierTypes();
// Returns: ['company', 'contact']
```

## Filters

### Available Filters

#### `ids`
Filter by specific order UUIDs.

```php
$orders = Teamleader::orders()->list([
    'ids' => ['order-uuid-1', 'order-uuid-2']
]);
```

## Sideloading

Load related data in a single request:

### Available Includes

- `custom_fields` - Include custom field values for the order

### Usage

```php
// With custom fields
$order = Teamleader::orders()
    ->with('custom_fields')
    ->info('order-uuid');

// In list() calls
$orders = Teamleader::orders()->list([], [
    'includes' => 'custom_fields'
]);
```

## Response Structure

### Order Object (List)

```php
[
    'id' => 'order-uuid',
    'name' => 'Order #O2024-001',
    'order_date' => '2024-01-15',
    'delivery_date' => '2024-01-22',
    'payment_term' => [
        'type' => 'cash',
        'days' => null
    ],
    'total' => [
        'tax_exclusive' => 1000.00,
        'tax_inclusive' => 1210.00,
        'taxes' => [
            [
                'rate' => 0.21,
                'amount' => 210.00
            ]
        ]
    ],
    'supplier' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'department' => [
        'id' => 'department-uuid'
    ],
    'deal' => [
        'id' => 'deal-uuid'
    ],
    'project' => [
        'id' => 'project-uuid'
    ],
    'assignee' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ],
    'web_url' => 'https://focus.teamleader.eu/order_detail.php?id=123'
]
```

### Order Object (Info)

The `info()` endpoint returns more detailed information including:

```php
[
    'id' => 'order-uuid',
    'name' => 'Order #O2024-001',
    'order_date' => '2024-01-15',
    'delivery_date' => '2024-01-22',
    'payment_term' => [
        'type' => 'after_invoice_date',
        'days' => 30
    ],
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Products'
            ],
            'line_items' => [
                [
                    'product' => [
                        'id' => 'product-uuid'
                    ],
                    'quantity' => 10,
                    'description' => 'Premium Package',
                    'unit_price' => [
                        'amount' => 100.00,
                        'tax' => 'excluding'
                    ],
                    'total' => [
                        'tax_exclusive' => 1000.00,
                        'tax_inclusive' => 1210.00
                    ]
                ]
            ]
        ]
    ],
    'total' => [
        'tax_exclusive' => 1000.00,
        'tax_inclusive' => 1210.00,
        'taxes' => [
            [
                'rate' => 0.21,
                'amount' => 210.00
            ]
        ]
    ],
    'purchase_price' => [
        'amount' => 750.00,
        'currency' => 'EUR'
    ],
    'supplier' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'department' => [
        'id' => 'department-uuid'
    ],
    'deal' => [
        'id' => 'deal-uuid'
    ],
    'project' => [
        'id' => 'project-uuid'
    ],
    'assignee' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ],
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Custom Value'
        ]
    ],
    'created_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-20T14:45:00+00:00',
    'web_url' => 'https://focus.teamleader.eu/order_detail.php?id=123'
]
```

## Usage Examples

### Get All Orders

```php
$allOrders = Teamleader::orders()->list();

foreach ($allOrders['data'] as $order) {
    echo "Order: {$order['name']}\n";
    echo "Total: €{$order['total']['tax_inclusive']}\n";
    echo "Order Date: {$order['order_date']}\n\n";
}
```

### Get Order Details with Custom Fields

```php
$order = Teamleader::orders()
    ->with('custom_fields')
    ->info('order-uuid');

echo "Order: {$order['data']['name']}\n";
echo "Supplier: {$order['data']['supplier']['type']} - {$order['data']['supplier']['id']}\n";

// Process custom fields
foreach ($order['data']['custom_fields'] ?? [] as $field) {
    echo "Custom field: {$field['id']} = {$field['value']}\n";
}
```

### Check Order Status

```php
function getOrderSummary($orderId)
{
    $order = Teamleader::orders()->info($orderId);
    
    return [
        'name' => $order['data']['name'],
        'order_date' => $order['data']['order_date'],
        'delivery_date' => $order['data']['delivery_date'],
        'total' => $order['data']['total']['tax_inclusive'],
        'supplier' => $order['data']['supplier']['type'],
        'has_deal' => !empty($order['data']['deal']),
        'has_project' => !empty($order['data']['project'])
    ];
}
```

### Track Delivery Dates

```php
function getOrdersNearingDelivery($days = 7)
{
    $allOrders = Teamleader::orders()->list();
    $upcoming = [];
    
    $targetDate = date('Y-m-d', strtotime("+{$days} days"));
    
    foreach ($allOrders['data'] as $order) {
        if ($order['delivery_date'] && $order['delivery_date'] <= $targetDate) {
            $upcoming[] = [
                'order_id' => $order['id'],
                'name' => $order['name'],
                'delivery_date' => $order['delivery_date'],
                'days_until_delivery' => round(
                    (strtotime($order['delivery_date']) - time()) / 86400
                )
            ];
        }
    }
    
    // Sort by delivery date
    usort($upcoming, function($a, $b) {
        return strcmp($a['delivery_date'], $b['delivery_date']);
    });
    
    return $upcoming;
}
```

## Common Use Cases

### 1. Order Reporting Dashboard

```php
function generateOrderReport($startDate, $endDate)
{
    $allOrders = Teamleader::orders()->list();
    
    $totalOrders = 0;
    $totalValue = 0;
    $ordersByMonth = [];
    
    foreach ($allOrders['data'] as $order) {
        $orderDate = $order['order_date'];
        
        if ($orderDate >= $startDate && $orderDate <= $endDate) {
            $totalOrders++;
            $totalValue += $order['total']['tax_exclusive'];
            
            $month = date('Y-m', strtotime($orderDate));
            if (!isset($ordersByMonth[$month])) {
                $ordersByMonth[$month] = [
                    'count' => 0,
                    'value' => 0
                ];
            }
            
            $ordersByMonth[$month]['count']++;
            $ordersByMonth[$month]['value'] += $order['total']['tax_exclusive'];
        }
    }
    
    return [
        'total_orders' => $totalOrders,
        'total_value' => $totalValue,
        'average_order_value' => $totalOrders > 0 ? $totalValue / $totalOrders : 0,
        'monthly_breakdown' => $ordersByMonth
    ];
}
```

### 2. Supplier Performance Analysis

```php
function analyzeSupplierPerformance()
{
    $allOrders = Teamleader::orders()->list();
    $supplierStats = [];
    
    foreach ($allOrders['data'] as $order) {
        if (!isset($order['supplier'])) {
            continue;
        }
        
        $supplierId = $order['supplier']['id'];
        
        if (!isset($supplierStats[$supplierId])) {
            $supplierStats[$supplierId] = [
                'type' => $order['supplier']['type'],
                'order_count' => 0,
                'total_value' => 0,
                'orders' => []
            ];
        }
        
        $supplierStats[$supplierId]['order_count']++;
        $supplierStats[$supplierId]['total_value'] += $order['total']['tax_exclusive'];
        $supplierStats[$supplierId]['orders'][] = $order['id'];
    }
    
    // Sort by total value
    uasort($supplierStats, function($a, $b) {
        return $b['total_value'] - $a['total_value'];
    });
    
    return $supplierStats;
}
```

### 3. Project Cost Tracking

```php
function getProjectOrderCosts($projectId)
{
    $allOrders = Teamleader::orders()->list();
    $projectOrders = [];
    $totalCost = 0;
    
    foreach ($allOrders['data'] as $order) {
        if (isset($order['project']) && $order['project']['id'] === $projectId) {
            $projectOrders[] = [
                'order_name' => $order['name'],
                'order_date' => $order['order_date'],
                'amount' => $order['total']['tax_exclusive']
            ];
            
            $totalCost += $order['total']['tax_exclusive'];
        }
    }
    
    return [
        'project_id' => $projectId,
        'order_count' => count($projectOrders),
        'total_cost' => $totalCost,
        'orders' => $projectOrders
    ];
}
```

### 4. Payment Term Analysis

```php
function analyzePaymentTerms()
{
    $allOrders = Teamleader::orders()->list();
    $termStats = [];
    
    foreach ($allOrders['data'] as $order) {
        $termType = $order['payment_term']['type'] ?? 'unknown';
        
        if (!isset($termStats[$termType])) {
            $termStats[$termType] = [
                'count' => 0,
                'total_value' => 0
            ];
        }
        
        $termStats[$termType]['count']++;
        $termStats[$termType]['total_value'] += $order['total']['tax_exclusive'];
    }
    
    return $termStats;
}
```

### 5. Order to Deal Conversion Tracking

```php
function trackOrderConversions()
{
    $allOrders = Teamleader::orders()->list();
    
    $withDeals = 0;
    $withoutDeals = 0;
    
    foreach ($allOrders['data'] as $order) {
        if (!empty($order['deal'])) {
            $withDeals++;
        } else {
            $withoutDeals++;
        }
    }
    
    return [
        'total_orders' => count($allOrders['data']),
        'orders_from_deals' => $withDeals,
        'orders_without_deals' => $withoutDeals,
        'conversion_rate' => count($allOrders['data']) > 0 
            ? ($withDeals / count($allOrders['data'])) * 100 
            : 0
    ];
}
```

## Best Practices

### 1. Cache Order Data for Reports

```php
// Good: Cache order list for reporting
use Illuminate\Support\Facades\Cache;

$orders = Cache::remember('orders_list', 300, function() {
    return Teamleader::orders()->list();
});
```

### 2. Handle Missing Optional Fields

```php
// Good: Check for optional fields
function displayOrder($orderId)
{
    $order = Teamleader::orders()->info($orderId);
    
    return [
        'name' => $order['data']['name'],
        'order_date' => $order['data']['order_date'] ?? 'Not set',
        'delivery_date' => $order['data']['delivery_date'] ?? 'Not set',
        'supplier' => $order['data']['supplier']['id'] ?? 'Unknown',
        'deal_id' => $order['data']['deal']['id'] ?? null,
        'project_id' => $order['data']['project']['id'] ?? null
    ];
}
```

### 3. Use Custom Fields for Integration

```php
// Good: Store external system IDs in custom fields
function syncOrderWithExternalSystem($orderId)
{
    $order = Teamleader::orders()
        ->with('custom_fields')
        ->info($orderId);
    
    // Look for external system ID in custom fields
    $externalId = null;
    foreach ($order['data']['custom_fields'] ?? [] as $field) {
        if ($field['id'] === 'external-system-id-field-uuid') {
            $externalId = $field['value'];
            break;
        }
    }
    
    if ($externalId) {
        // Sync with external system
        ExternalSystem::syncOrder($externalId, $order['data']);
    }
}
```

### 4. Track Order Delivery Compliance

```php
// Good: Monitor delivery date performance
function checkDeliveryCompliance()
{
    $allOrders = Teamleader::orders()->list();
    $today = date('Y-m-d');
    
    $onTime = 0;
    $late = 0;
    $pending = 0;
    
    foreach ($allOrders['data'] as $order) {
        if ($order['delivery_date']) {
            if ($order['delivery_date'] > $today) {
                $pending++;
            } elseif ($order['delivery_date'] <= $today) {
                // Would need additional status field to determine if delivered
                $onTime++; // Assuming delivered
            }
        }
    }
    
    return [
        'on_time' => $onTime,
        'late' => $late,
        'pending' => $pending,
        'compliance_rate' => ($onTime + $late) > 0 
            ? ($onTime / ($onTime + $late)) * 100 
            : 0
    ];
}
```

### 5. Generate Order Export

```php
// Good: Export order data for accounting
function exportOrdersForAccounting($startDate, $endDate)
{
    $allOrders = Teamleader::orders()->list();
    $export = [];
    
    foreach ($allOrders['data'] as $order) {
        if ($order['order_date'] >= $startDate && $order['order_date'] <= $endDate) {
            $export[] = [
                'Order ID' => $order['id'],
                'Order Number' => $order['name'],
                'Order Date' => $order['order_date'],
                'Delivery Date' => $order['delivery_date'] ?? '',
                'Supplier Type' => $order['supplier']['type'] ?? '',
                'Supplier ID' => $order['supplier']['id'] ?? '',
                'Tax Exclusive' => $order['total']['tax_exclusive'],
                'Tax Inclusive' => $order['total']['tax_inclusive'],
                'Tax Amount' => $order['total']['taxes'][0]['amount'] ?? 0
            ];
        }
    }
    
    return $export;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $orders = Teamleader::orders()->list();
} catch (TeamleaderException $e) {
    Log::error('Error fetching orders', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Provide fallback
    return ['data' => []];
}

// Handle specific order not found
try {
    $order = Teamleader::orders()->info('order-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        return response()->json([
            'error' => 'Order not found'
        ], 404);
    }
    
    throw $e;
}
```

## Payment Term Types

Orders can have different payment term types:

- **cash** - Payment on delivery
- **end_of_month** - Payment at the end of the month
- **after_invoice_date** - Payment X days after invoice date (days specified in `payment_term.days`)

## Limitations

1. **Read-Only**: You cannot create, update, or delete orders via this endpoint
2. **No Pagination**: All orders are returned in a single request
3. **Limited Filtering**: Can only filter by IDs
4. **No Status Field**: Orders don't have an explicit status field
5. **No Sorting**: Cannot sort orders by date or other fields

```php
// Cannot do this:
// Teamleader::orders()->create([...]); // ❌ Not supported
// Teamleader::orders()->update('uuid', [...]); // ❌ Not supported
// Teamleader::orders()->delete('uuid'); // ❌ Not supported

// Can only do this:
Teamleader::orders()->list(); // ✅ Supported
Teamleader::orders()->info('uuid'); // ✅ Supported
Teamleader::orders()->list(['ids' => ['uuid']]); // ✅ Supported
```

## Related Resources

- [Quotations](quotations.md) - Quotations become orders when accepted
- [Invoices](../invoicing/invoices.md) - Orders can be converted to invoices
- [Deals](deals.md) - Orders may be linked to deals
- [Projects](../projects/projects.md) - Orders may be linked to projects

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Sideloading](../sideloading.md) - Efficiently load related data

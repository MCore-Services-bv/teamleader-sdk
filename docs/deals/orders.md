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

Get detailed information about a specific order, including full grouped line items.

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

Load related data in a single request.

### Available Includes

- `custom_fields` - Include custom field values for the order

### Usage

```php
// With custom fields on info()
$order = Teamleader::orders()
    ->with('custom_fields')
    ->info('order-uuid');

// With custom fields on list()
$orders = Teamleader::orders()->list([], [
    'includes' => 'custom_fields'
]);
```

## Response Structure

### Order Object (List)

The `list()` endpoint returns a summary of each order. Note that `total` sub-objects contain `amount` and `currency`.

```json
{
  "data": [
    {
      "id": "order-uuid",
      "name": "General costs",
      "order_date": "2024-01-15",
      "order_number": 32,
      "delivery_date": "2024-01-22",
      "payment_term": {
        "type": "after_invoice_date",
        "days": 30
      },
      "total": {
        "tax_exclusive": {
          "amount": 1000.00,
          "currency": "EUR"
        },
        "tax_inclusive": {
          "amount": 1210.00,
          "currency": "EUR"
        },
        "purchase_price_tax_exclusive": {
          "amount": 750.00,
          "currency": "EUR"
        },
        "purchase_price_tax_inclusive": {
          "amount": 907.50,
          "currency": "EUR"
        },
        "taxes": [
          {
            "rate": 0.21,
            "taxable": {
              "amount": 1000.00,
              "currency": "EUR"
            },
            "tax": {
              "amount": 210.00,
              "currency": "EUR"
            }
          }
        ]
      },
      "web_url": "https://focus.teamleader.eu/order_detail.php?id=order-uuid",
      "supplier": {
        "type": "company",
        "id": "company-uuid"
      },
      "department": {
        "type": "department",
        "id": "department-uuid"
      },
      "deal": {
        "type": "deal",
        "id": "deal-uuid"
      },
      "project": {
        "type": "project",
        "id": "project-uuid"
      },
      "assignee": {
        "type": "user",
        "id": "user-uuid"
      }
    }
  ]
}
```

### Order Object (Info)

The `info()` endpoint returns full detail including `grouped_lines`. Line items now include `project`, `group`, and `purchase_price` per item, and each total sub-object includes before-discount variants.

```json
{
  "data": {
    "id": "order-uuid",
    "name": "General costs",
    "order_date": "2024-01-15",
    "order_number": 32,
    "delivery_date": "2024-01-22",
    "payment_term": {
      "type": "after_invoice_date",
      "days": 30
    },
    "grouped_lines": [
      {
        "section": {
          "title": "Products"
        },
        "line_items": [
          {
            "product": {
              "type": "product",
              "id": "product-uuid"
            },
            "quantity": 10,
            "description": "Premium Package",
            "extended_description": "Some more information about this product",
            "unit": {
              "type": "unit",
              "id": "unit-uuid"
            },
            "unit_price": {
              "amount": 100.00,
              "tax": "excluding"
            },
            "tax": {
              "type": "taxRate",
              "id": "tax-uuid"
            },
            "discount": {
              "value": 10,
              "type": "percentage"
            },
            "total": {
              "tax_exclusive": {
                "amount": 900.00,
                "currency": "EUR"
              },
              "tax_exclusive_before_discount": {
                "amount": 1000.00,
                "currency": "EUR"
              },
              "tax_inclusive": {
                "amount": 1089.00,
                "currency": "EUR"
              },
              "tax_inclusive_before_discount": {
                "amount": 1210.00,
                "currency": "EUR"
              }
            },
            "product_category": {
              "type": "productCategory",
              "id": "category-uuid"
            },
            "project": {
              "type": "nextgenProject",
              "id": "project-uuid"
            },
            "group": {
              "type": "nextgenProjectGroup",
              "id": "group-uuid"
            },
            "purchase_price": {
              "amount": 75.00,
              "currency": "EUR"
            }
          }
        ]
      }
    ],
    "total": {
      "tax_exclusive": {
        "amount": 900.00,
        "currency": "EUR"
      },
      "tax_inclusive": {
        "amount": 1089.00,
        "currency": "EUR"
      },
      "purchase_price_tax_exclusive": {
        "amount": 750.00,
        "currency": "EUR"
      },
      "purchase_price_tax_inclusive": {
        "amount": 907.50,
        "currency": "EUR"
      },
      "taxes": [
        {
          "rate": 0.21,
          "taxable": {
            "amount": 900.00,
            "currency": "EUR"
          },
          "tax": {
            "amount": 189.00,
            "currency": "EUR"
          }
        }
      ]
    },
    "web_url": "https://focus.teamleader.eu/order_detail.php?id=order-uuid",
    "supplier": {
      "type": "company",
      "id": "company-uuid"
    },
    "department": {
      "type": "department",
      "id": "department-uuid"
    },
    "deal": {
      "type": "deal",
      "id": "deal-uuid"
    },
    "project": {
      "type": "project",
      "id": "project-uuid"
    },
    "assignee": {
      "type": "user",
      "id": "user-uuid"
    },
    "custom_fields": [
      {
        "definition": {
          "type": "customFieldDefinition",
          "id": "field-def-uuid"
        },
        "value": "Custom value"
      }
    ]
  }
}
```

### Key Response Fields

| Field | Endpoint | Nullable | Notes |
|---|---|---|---|
| `id` | list, info | No | Order UUID |
| `name` | list, info | No | Order name |
| `order_date` | list, info | Yes | Format: YYYY-MM-DD |
| `order_number` | list, info | Yes | Sequential number |
| `delivery_date` | list, info | Yes | Format: YYYY-MM-DD |
| `payment_term` | list, info | Yes | Object with `type` and `days` |
| `total` | list, info | No | All sub-objects have `amount` + `currency` |
| `total.purchase_price_tax_exclusive` | list, info | Yes | Aggregate purchase price |
| `total.purchase_price_tax_inclusive` | list, info | Yes | Aggregate purchase price with tax |
| `grouped_lines` | info only | No | Full line items |
| `grouped_lines[].line_items[].project` | info only | Yes | Per-line project (`nextgenProject`) |
| `grouped_lines[].line_items[].group` | info only | Yes | Per-line project group (`nextgenProjectGroup`) |
| `grouped_lines[].line_items[].purchase_price` | info only | Yes | Per-line purchase cost |
| `project` | list, info | Yes | Old projects module only |
| `custom_fields` | list, info | Yes | Only with `includes=custom_fields` |

> **Note:** `project` at the root level is only available for users with access to the old projects module. `project` and `group` on individual line items are the current nextgen project references.

## Usage Examples

### Get All Orders

```php
$allOrders = Teamleader::orders()->list();

foreach ($allOrders['data'] as $order) {
    echo "Order #{$order['order_number']}: {$order['name']}\n";
    echo "Total: €{$order['total']['tax_inclusive']['amount']}\n";
    echo "Order Date: {$order['order_date']}\n\n";
}
```

### Get Order Details with Line Items

```php
$order = Teamleader::orders()->info('order-uuid');

foreach ($order['data']['grouped_lines'] as $group) {
    echo "Section: {$group['section']['title']}\n";

    foreach ($group['line_items'] as $item) {
        echo "  {$item['description']} x{$item['quantity']}";
        echo " = €{$item['total']['tax_exclusive']['amount']}\n";

        // Check for project assignment on this line
        if (! empty($item['project'])) {
            echo "  → Project: {$item['project']['id']}\n";
        }

        // Check for purchase cost
        if (! empty($item['purchase_price'])) {
            echo "  → Purchase price: €{$item['purchase_price']['amount']}\n";
        }
    }
}
```

### Get Order Details with Custom Fields

```php
$order = Teamleader::orders()
    ->with('custom_fields')
    ->info('order-uuid');

echo "Order: {$order['data']['name']}\n";
echo "Order #: {$order['data']['order_number']}\n";

foreach ($order['data']['custom_fields'] ?? [] as $field) {
    echo "Custom field: {$field['definition']['id']} = {$field['value']}\n";
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
                'order_id'           => $order['id'],
                'order_number'       => $order['order_number'],
                'name'               => $order['name'],
                'delivery_date'      => $order['delivery_date'],
                'days_until_delivery' => round(
                    (strtotime($order['delivery_date']) - time()) / 86400
                ),
            ];
        }
    }

    usort($upcoming, fn ($a, $b) => strcmp($a['delivery_date'], $b['delivery_date']));

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
    $totalValue  = 0;
    $ordersByMonth = [];

    foreach ($allOrders['data'] as $order) {
        if ($order['order_date'] >= $startDate && $order['order_date'] <= $endDate) {
            $totalOrders++;
            $totalValue += $order['total']['tax_exclusive']['amount'];

            $month = date('Y-m', strtotime($order['order_date']));
            $ordersByMonth[$month] ??= ['count' => 0, 'value' => 0];
            $ordersByMonth[$month]['count']++;
            $ordersByMonth[$month]['value'] += $order['total']['tax_exclusive']['amount'];
        }
    }

    return [
        'total_orders'       => $totalOrders,
        'total_value'        => $totalValue,
        'average_order_value' => $totalOrders > 0 ? $totalValue / $totalOrders : 0,
        'monthly_breakdown'  => $ordersByMonth,
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
        if (empty($order['supplier'])) {
            continue;
        }

        $supplierId = $order['supplier']['id'];
        $supplierStats[$supplierId] ??= [
            'type'        => $order['supplier']['type'],
            'order_count' => 0,
            'total_value' => 0,
            'orders'      => [],
        ];

        $supplierStats[$supplierId]['order_count']++;
        $supplierStats[$supplierId]['total_value'] += $order['total']['tax_exclusive']['amount'];
        $supplierStats[$supplierId]['orders'][] = $order['id'];
    }

    uasort($supplierStats, fn ($a, $b) => $b['total_value'] - $a['total_value']);

    return $supplierStats;
}
```

### 3. Project Cost Tracking (Line Item Level)

```php
function getProjectLineItemCosts($projectId)
{
    $allOrders = Teamleader::orders()->list();
    $lineItems = [];
    $totalCost = 0;

    foreach ($allOrders['data'] as $orderSummary) {
        $order = Teamleader::orders()->info($orderSummary['id']);

        foreach ($order['data']['grouped_lines'] as $group) {
            foreach ($group['line_items'] as $item) {
                if (isset($item['project']['id']) && $item['project']['id'] === $projectId) {
                    $amount = $item['total']['tax_exclusive']['amount'];
                    $lineItems[] = [
                        'order_number' => $order['data']['order_number'],
                        'description'  => $item['description'],
                        'quantity'     => $item['quantity'],
                        'amount'       => $amount,
                        'group'        => $item['group']['id'] ?? null,
                    ];
                    $totalCost += $amount;
                }
            }
        }
    }

    return [
        'project_id'  => $projectId,
        'line_count'  => count($lineItems),
        'total_cost'  => $totalCost,
        'line_items'  => $lineItems,
    ];
}
```

### 4. Margin Analysis Using Purchase Price

```php
function analyzeOrderMargins()
{
    $allOrders = Teamleader::orders()->list();
    $results = [];

    foreach ($allOrders['data'] as $order) {
        $revenue      = $order['total']['tax_exclusive']['amount'];
        $purchaseCost = $order['total']['purchase_price_tax_exclusive']['amount'] ?? null;

        if ($purchaseCost !== null && $revenue > 0) {
            $results[] = [
                'order_number' => $order['order_number'],
                'name'         => $order['name'],
                'revenue'      => $revenue,
                'cost'         => $purchaseCost,
                'margin'       => $revenue - $purchaseCost,
                'margin_pct'   => round((($revenue - $purchaseCost) / $revenue) * 100, 2),
            ];
        }
    }

    return $results;
}
```

### 5. Export Orders for Accounting

```php
function exportOrdersForAccounting($startDate, $endDate)
{
    $allOrders = Teamleader::orders()->list();
    $export = [];

    foreach ($allOrders['data'] as $order) {
        if ($order['order_date'] >= $startDate && $order['order_date'] <= $endDate) {
            $export[] = [
                'Order ID'        => $order['id'],
                'Order Number'    => $order['order_number'] ?? '',
                'Order Name'      => $order['name'],
                'Order Date'      => $order['order_date'],
                'Delivery Date'   => $order['delivery_date'] ?? '',
                'Supplier Type'   => $order['supplier']['type'] ?? '',
                'Supplier ID'     => $order['supplier']['id'] ?? '',
                'Tax Exclusive'   => $order['total']['tax_exclusive']['amount'],
                'Tax Inclusive'   => $order['total']['tax_inclusive']['amount'],
                'Currency'        => $order['total']['tax_exclusive']['currency'],
            ];
        }
    }

    return $export;
}
```

## Best Practices

1. **Cache Order Data for Reports**: Order data changes infrequently, so caching is beneficial.
```php
use Illuminate\Support\Facades\Cache;

$orders = Cache::remember('orders_list', 300, function () {
    return Teamleader::orders()->list();
});
```

2. **Handle Missing Optional Fields**: Many fields are nullable.
```php
$order = Teamleader::orders()->info($orderId);

return [
    'name'          => $order['data']['name'],
    'order_number'  => $order['data']['order_number'] ?? 'Not assigned',
    'order_date'    => $order['data']['order_date'] ?? 'Not set',
    'delivery_date' => $order['data']['delivery_date'] ?? 'Not set',
    'supplier'      => $order['data']['supplier']['id'] ?? 'Unknown',
];
```

3. **Use `order_number` for Display**: Use `order_number` (sequential integer) for human-readable references rather than the UUID `id`.

4. **`total` Values Are Objects**: Remember that all amounts in `total` are objects — access them as `$order['total']['tax_exclusive']['amount']`, not `$order['total']['tax_exclusive']`.

5. **Line Item `project` vs Root `project`**: `project` on individual line items (type `nextgenProject`) is the current project reference. `project` at the order root level is only available for accounts using the old projects module.

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $orders = Teamleader::orders()->list();
} catch (TeamleaderException $e) {
    Log::error('Error fetching orders', [
        'error' => $e->getMessage(),
        'code'  => $e->getCode(),
    ]);

    return ['data' => []];
}

// Handle specific order not found
try {
    $order = Teamleader::orders()->info('order-uuid');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        return response()->json(['error' => 'Order not found'], 404);
    }

    throw $e;
}
```

## Payment Term Types

| Type | Description |
|---|---|
| `cash` | Payment on delivery (no `days` field) |
| `end_of_month` | Payment at end of month |
| `after_invoice_date` | Payment X days after invoice date (`days` field required) |

## Limitations

1. **Read-Only**: You cannot create, update, or delete orders via this endpoint.
2. **No Pagination**: All orders are returned in a single request.
3. **Limited Filtering**: Can only filter by IDs.
4. **No Status Field**: Orders don't have an explicit status field.
5. **No Sorting**: Cannot sort orders by date or other fields.

## Related Resources

- [Quotations](quotations.md) - Quotations that become orders when accepted
- [Invoices](../invoicing/invoices.md) - Orders can be converted to invoices
- [Deals](deals.md) - Orders may be linked to deals
- [Projects](../projects/projects.md) - Orders may be linked to projects

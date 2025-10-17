# Commercial Discounts

Access commercial discount information in Teamleader Focus.

## Overview

The Commercial Discounts resource provides read-only access to commercial discounts configured in your Teamleader account. Commercial discounts can be applied to invoices, quotations, and other financial documents.

**Important:** This resource is read-only. Commercial discounts are configured in Teamleader Focus settings and cannot be created or modified through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
- [Helper Methods](#helper-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Best Practices](#best-practices)
- [Related Resources](#related-resources)

## Endpoint

`commercialDiscounts`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported (department_id)
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported
- **Update**: ❌ Not Supported
- **Deletion**: ❌ Not Supported

## Available Methods

### `list()`

Get all available commercial discounts, optionally filtered by department.

**Parameters:**
- `filters` (array, optional): Filter by department_id

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all commercial discounts
$discounts = Teamleader::commercialDiscounts()->list();

// Get discounts for specific department
$discounts = Teamleader::commercialDiscounts()->list([
    'department_id' => 'dept-uuid'
]);
```

## Helper Methods

### `forDepartment()`

Get commercial discounts for a specific department.

```php
$discounts = Teamleader::commercialDiscounts()->forDepartment('dept-uuid');
```

### `findByName()`

Find a commercial discount by its name.

```php
$discount = Teamleader::commercialDiscounts()->findByName('Early payment discount');
```

### `asOptions()`

Get commercial discounts formatted as key-value pairs for dropdowns.

```php
$options = Teamleader::commercialDiscounts()->asOptions();
// Returns: ['uuid-1' => 'Early payment discount', 'uuid-2' => 'Volume discount', ...]
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "uuid",
      "department": {
        "type": "department",
        "id": "uuid"
      },
      "name": "Early payment discount",
      "percentage": 2.00
    },
    {
      "id": "uuid",
      "department": {
        "type": "department",
        "id": "uuid"
      },
      "name": "Volume discount",
      "percentage": 5.00
    }
  ]
}
```

## Usage Examples

### Get Available Commercial Discounts

```php
$discounts = Teamleader::commercialDiscounts()->list();

echo "Available commercial discounts:\n";
foreach ($discounts['data'] as $discount) {
    echo "- {$discount['name']}: {$discount['percentage']}%\n";
}
```

### Apply Discount to Invoice

```php
// Get discount
$discount = Teamleader::commercialDiscounts()->findByName('Early payment discount');

$invoice = Teamleader::invoices()->create([
    'invoice_date' => '2024-02-01',
    'invoicee' => [...],
    'grouped_lines' => [...],
    'discounts' => [
        [
            'type' => 'commercial_discount',
            'commercial_discount_id' => $discount['id']
        ]
    ]
]);
```

### Calculate Discount Amount

```php
$subtotal = 1000.00;
$discount = Teamleader::commercialDiscounts()->findByName('Volume discount');

$discountAmount = $subtotal * ($discount['percentage'] / 100);
$total = $subtotal - $discountAmount;

echo "Subtotal: €{$subtotal}\n";
echo "Discount ({$discount['name']}): €{$discountAmount}\n";
echo "Total: €{$total}\n";
```

## Best Practices

### 1. Cache Commercial Discounts

```php
use Illuminate\Support\Facades\Cache;

$discounts = Cache::remember('commercial_discounts', 86400, function () {
    return Teamleader::commercialDiscounts()->list();
});
```

### 2. Department-Specific Discounts

```php
$departmentId = 'dept-uuid';
$cacheKey = "commercial_discounts_{$departmentId}";

$discounts = Cache::remember($cacheKey, 86400, function () use ($departmentId) {
    return Teamleader::commercialDiscounts()->forDepartment($departmentId);
});
```

### 3. Validate Before Use

```php
$discountName = $request->input('discount_name');
$discount = Teamleader::commercialDiscounts()->findByName($discountName);

if (!$discount) {
    throw new ValidationException('Invalid commercial discount');
}
```

## Related Resources

- [Invoices](invoices.md) - Invoice management
- [Quotations](../deals/quotations.md) - Quotation management

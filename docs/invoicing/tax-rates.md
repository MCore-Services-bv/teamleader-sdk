# Tax Rates

Access tax rate information in Teamleader Focus.

## Overview

The Tax Rates resource provides read-only access to tax rates configured in your Teamleader account. Tax rates are used when creating invoices, quotations, and other financial documents to calculate taxes on line items.

**Important:** This resource is read-only. Tax rates are configured in Teamleader Focus settings and cannot be created or modified through the API.

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

`taxRates`

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

Get all available tax rates, optionally filtered by department.

**Parameters:**
- `filters` (array, optional): Filter by department_id

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all tax rates
$taxRates = Teamleader::taxRates()->list();

// Get tax rates for specific department
$taxRates = Teamleader::taxRates()->list([
    'department_id' => 'dept-uuid'
]);
```

## Helper Methods

### `forDepartment()`

Get tax rates for a specific department.

```php
$taxRates = Teamleader::taxRates()->forDepartment('dept-uuid');
```

### `findByRate()`

Find a tax rate by its exact rate value.

```php
// Find 21% tax rate
$taxRate = Teamleader::taxRates()->findByRate(0.21);
```

### `findByDescription()`

Find a tax rate by its description.

```php
$taxRate = Teamleader::taxRates()->findByDescription('21%');
```

### `asOptions()`

Get tax rates formatted as key-value pairs for dropdowns.

```php
$options = Teamleader::taxRates()->asOptions();
// Returns: ['uuid-1' => '21%', 'uuid-2' => '6%', ...]
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
      "description": "21%",
      "rate": 0.21
    },
    {
      "id": "uuid",
      "department": {
        "type": "department",
        "id": "uuid"
      },
      "description": "6%",
      "rate": 0.06
    },
    {
      "id": "uuid",
      "department": {
        "type": "department",
        "id": "uuid"
      },
      "description": "0%",
      "rate": 0.00
    }
  ]
}
```

## Usage Examples

### Get Available Tax Rates

```php
$taxRates = Teamleader::taxRates()->list();

echo "Available tax rates:\n";
foreach ($taxRates['data'] as $rate) {
    echo "- {$rate['description']} ({$rate['rate']})\n";
}
```

### Use in Invoice Line Items

```php
// Get standard VAT rate
$standardVat = Teamleader::taxRates()->findByRate(0.21);

$invoice = Teamleader::invoices()->create([
    'invoice_date' => '2024-02-01',
    'invoicee' => [...],
    'grouped_lines' => [
        [
            'line_items' => [
                [
                    'quantity' => 2,
                    'description' => 'Product A',
                    'unit_price' => [
                        'amount' => 100.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => $standardVat['id']
                ]
            ]
        ]
    ]
]);
```

### Create Dropdown for Tax Rates

```php
$options = Teamleader::taxRates()->asOptions();

echo '<select name="tax_rate_id">';
foreach ($options as $id => $description) {
    echo "<option value='{$id}'>{$description}</option>";
}
echo '</select>';
```

### Calculate Tax Amount

```php
$amount = 100.00;
$taxRate = Teamleader::taxRates()->findByRate(0.21);

$taxAmount = $amount * $taxRate['rate'];
$totalWithTax = $amount + $taxAmount;

echo "Amount: €{$amount}\n";
echo "Tax ({$taxRate['description']}): €{$taxAmount}\n";
echo "Total: €{$totalWithTax}\n";
```

## Best Practices

### 1. Cache Tax Rates

Tax rates rarely change, so cache them to reduce API calls:

```php
use Illuminate\Support\Facades\Cache;

$taxRates = Cache::remember('tax_rates', 86400, function () {
    return Teamleader::taxRates()->list();
});
```

### 2. Department-Specific Rates

If you work with multiple departments, cache per department:

```php
$departmentId = 'dept-uuid';
$cacheKey = "tax_rates_{$departmentId}";

$taxRates = Cache::remember($cacheKey, 86400, function () use ($departmentId) {
    return Teamleader::taxRates()->forDepartment($departmentId);
});
```

### 3. Validate Tax Rate Exists

```php
$taxRateId = $request->input('tax_rate_id');
$taxRate = Teamleader::taxRates()->findByRate($expectedRate);

if (!$taxRate || $taxRate['id'] !== $taxRateId) {
    throw new ValidationException('Invalid tax rate');
}
```

### 4. Use Helper Methods

```php
// Good: Clear and concise
$vatRate = Teamleader::taxRates()->findByRate(0.21);

// Less ideal: Manual searching
$rates = Teamleader::taxRates()->list();
$vatRate = null;
foreach ($rates['data'] as $rate) {
    if ($rate['rate'] === 0.21) {
        $vatRate = $rate;
        break;
    }
}
```

## Related Resources

- [Invoices](invoices.md) - Invoice management
- [Withholding Tax Rates](withholding-tax-rates.md) - Withholding tax information
- [Quotations](../deals/quotations.md) - Quotation management

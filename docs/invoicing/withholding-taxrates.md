# Withholding Tax Rates

Access withholding tax rate information in Teamleader Focus.

## Overview

The Withholding Tax Rates resource provides read-only access to withholding tax rates configured in your Teamleader account. Withholding tax rates are used for certain types of invoices where tax is withheld at source.

**Important:** This resource is read-only. Withholding tax rates are configured in Teamleader Focus settings and cannot be created or modified through the API.

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

`withholdingTaxRates`

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

Get all available withholding tax rates.

**Parameters:** None

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all withholding tax rates
$rates = Teamleader::withholdingTaxRates()->list();
```

## Helper Methods

### `findByRate()`

Find a withholding tax rate by its exact rate value.

```php
// Find 15% withholding tax rate
$rate = Teamleader::withholdingTaxRates()->findByRate(0.15);
```

### `findByDescription()`

Find a withholding tax rate by its description.

```php
$rate = Teamleader::withholdingTaxRates()->findByDescription('15%');
```

### `asOptions()`

Get withholding tax rates formatted as key-value pairs for dropdowns.

```php
$options = Teamleader::withholdingTaxRates()->asOptions();
// Returns: ['uuid-1' => '15%', 'uuid-2' => '10%', ...]
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "uuid",
      "description": "15%",
      "rate": 0.15
    },
    {
      "id": "uuid",
      "description": "10%",
      "rate": 0.10
    },
    {
      "id": "uuid",
      "description": "0%",
      "rate": 0.00
    }
  ]
}
```

## Usage Examples

### Get Available Withholding Tax Rates

```php
$rates = Teamleader::withholdingTaxRates()->list();

echo "Available withholding tax rates:\n";
foreach ($rates['data'] as $rate) {
    echo "- {$rate['description']} ({$rate['rate']})\n";
}
```

### Use in Invoice Creation

```php
// Get withholding tax rate
$withholdingRate = Teamleader::withholdingTaxRates()->findByRate(0.15);

$invoice = Teamleader::invoices()->create([
    'invoice_date' => '2024-02-01',
    'invoicee' => [...],
    'grouped_lines' => [...],
    'withholding_tax_rate_id' => $withholdingRate['id']
]);
```

### Calculate Withholding Tax

```php
$amount = 1000.00;
$rate = Teamleader::withholdingTaxRates()->findByRate(0.15);

$withholdingTax = $amount * $rate['rate'];
$netAmount = $amount - $withholdingTax;

echo "Gross amount: €{$amount}\n";
echo "Withholding tax ({$rate['description']}): €{$withholdingTax}\n";
echo "Net amount: €{$netAmount}\n";
```

## Best Practices

### 1. Cache Withholding Tax Rates

```php
use Illuminate\Support\Facades\Cache;

$rates = Cache::remember('withholding_tax_rates', 86400, function () {
    return Teamleader::withholdingTaxRates()->list();
});
```

### 2. Use Helper Methods

```php
// Good: Clear and concise
$rate = Teamleader::withholdingTaxRates()->findByRate(0.15);

// Less ideal: Manual searching
$rates = Teamleader::withholdingTaxRates()->list();
$rate = null;
foreach ($rates['data'] as $r) {
    if ($r['rate'] === 0.15) {
        $rate = $r;
        break;
    }
}
```

## Related Resources

- [Invoices](invoices.md) - Invoice management
- [Tax Rates](tax-rates.md) - Standard tax information

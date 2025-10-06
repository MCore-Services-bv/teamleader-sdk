# Withholding Tax Rates

The Withholding Tax Rates resource allows you to retrieve available withholding tax rates in Teamleader Focus. Withholding tax rates are used for specific tax scenarios where tax is withheld at source (e.g., for freelancers or contractors). This is a read-only resource managed through the Teamleader application settings.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Listing Withholding Tax Rates](#listing-withholding-tax-rates)
- [Finding Withholding Tax Rates](#finding-withholding-tax-rates)
- [Convenience Methods](#convenience-methods)
- [Data Fields](#data-fields)

## Basic Usage

```php
// Get the withholding tax rates resource
$rates = $teamleader->withholdingTaxRates();

// List all withholding tax rates
$allRates = $rates->list();

// Find rate by exact value
$fifteenPercent = $rates->findByRate(0.15);
```

## Listing Withholding Tax Rates

```php
// Get all withholding tax rates
$rates = $teamleader->withholdingTaxRates()->list();

// Access the data
$rateList = $rates['data'];
foreach ($rateList as $rate) {
    echo "{$rate['description']}: {$rate['rate']}\n";
}
```

**Note:** Withholding tax rates do not support filtering, pagination, or sorting. The `list()` method returns all available rates at once.

## Finding Withholding Tax Rates

### Find by ID

```php
// Find a specific rate by ID
$rate = $teamleader->withholdingTaxRates()->find('rate-uuid');

if ($rate) {
    echo "Found: {$rate['description']} - Rate: {$rate['rate']}";
}
```

### Find by Rate

```php
// Find rate with exact rate value (15% = 0.15)
$rate = $teamleader->withholdingTaxRates()->findByRate(0.15);

// Common withholding tax rates
$fifteen = $teamleader->withholdingTaxRates()->findByRate(0.15);  // 15%
$twentyFive = $teamleader->withholdingTaxRates()->findByRate(0.25); // 25%
$thirty = $teamleader->withholdingTaxRates()->findByRate(0.30);  // 30%
```

### Find by Rate Range

```php
// Find all rates between 10% and 30%
$rates = $teamleader->withholdingTaxRates()->findByRateRange(0.10, 0.30);

foreach ($rates as $rate) {
    echo "{$rate['description']}: " . ($rate['rate'] * 100) . "%\n";
}

// Find low withholding rates (below 20%)
$lowRates = $teamleader->withholdingTaxRates()->findByRateRange(0.00, 0.20);

// Find high withholding rates (above 25%)
$highRates = $teamleader->withholdingTaxRates()->findByRateRange(0.25, 1.00);
```

### Find by Description

```php
// Find rate by exact description
$rate = $teamleader->withholdingTaxRates()->findByDescription('15%');

// Partial match search (case-insensitive)
$rate = $teamleader->withholdingTaxRates()->findByDescription('15', false);
```

### Check if Rate Exists

```php
// Check if a rate ID is valid
$exists = $teamleader->withholdingTaxRates()->exists('rate-uuid');

if ($exists) {
    // Rate exists
}
```

## Convenience Methods

### Get All Withholding Tax Rates

```php
// Get all rates
$allRates = $teamleader->withholdingTaxRates()->list();

foreach ($allRates['data'] as $rate) {
    $percentage = ($rate['rate'] * 100);
    echo "{$rate['description']}: {$percentage}%\n";
}
```

### Get Rates Sorted

```php
// Get rates sorted by rate value (ascending)
$sortedRates = $teamleader->withholdingTaxRates()->sortedByRate();

// Sort by description alphabetically
$sortedByDesc = $teamleader->withholdingTaxRates()->sortedByDescription();

// Sort by description descending
$sortedByDescDesc = $teamleader->withholdingTaxRates()->sortedByDescription('desc');
```

### Get Rates Grouped by Department

```php
// Get rates organized by department
$grouped = $teamleader->withholdingTaxRates()->groupedByDepartment();

foreach ($grouped as $departmentId => $data) {
    echo "Department: {$departmentId}\n";
    foreach ($data['withholding_tax_rates'] as $rate) {
        echo "  - {$rate['description']}: {$rate['rate']}\n";
    }
    echo "\n";
}
```

### Get as Select Options

```php
// Get rates as key-value pairs for dropdowns
$options = $teamleader->withholdingTaxRates()->asOptions();
// Returns: ['uuid1' => '15%', 'uuid2' => '25%', ...]

// Use in a form
echo '<select name="withholding_tax_rate_id">';
echo '<option value="">No withholding tax</option>';
foreach ($options as $id => $description) {
    echo "<option value=\"{$id}\">{$description}</option>";
}
echo '</select>';
```

### Format Rate for Display

```php
// Format a rate as human-readable string
$rate = $teamleader->withholdingTaxRates()->findByRate(0.15);
$formatted = $teamleader->withholdingTaxRates()->format($rate);
// Returns: "15% (15%)"

echo "Withholding tax: {$formatted}";
```

## Data Fields

### Withholding Tax Rate Fields

Each withholding tax rate contains:

- **`id`**: Withholding tax rate UUID (string)
    - Example: `"c93ddb52-0af8-47d9-8551-441435be66a7"`
    - Unique identifier for the withholding tax rate

- **`description`**: Rate description (string)
    - Example: `"21%"`, `"15%"`, `"Withholding 25%"`
    - Human-readable description of the rate

- **`rate`**: Rate as decimal (number)
    - Example: `0.21` for 21%, `0.15` for 15%, `0.30` for 30%
    - The actual withholding tax rate value

- **`department`**: Department reference (object)
    - **`id`**: Department UUID (string)
        - Example: `"eab232c6-49b2-4b7e-a977-5e1148dad471"`
    - **`type`**: Resource type (string)
        - Always `"department"`

## Response Examples

### List Response

```php
[
    'data' => [
        [
            'id' => 'c93ddb52-0af8-47d9-8551-441435be66a7',
            'description' => '15%',
            'rate' => 0.15,
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ]
        ],
        [
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'description' => '25%',
            'rate' => 0.25,
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ]
        ],
        [
            'id' => 'f0e9d8c7-b6a5-4321-9876-543210fedcba',
            'description' => '30%',
            'rate' => 0.30,
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ]
        ]
    ]
]
```

### Find Response

```php
// Found
[
    'id' => 'c93ddb52-0af8-47d9-8551-441435be66a7',
    'description' => '15%',
    'rate' => 0.15,
    'department' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'department'
    ]
]

// Not found
null
```

### As Options Response

```php
[
    'c93ddb52-0af8-47d9-8551-441435be66a7' => '15%',
    'a1b2c3d4-e5f6-7890-abcd-ef1234567890' => '25%',
    'f0e9d8c7-b6a5-4321-9876-543210fedcba' => '30%'
]
```

## Common Use Cases

### Display Withholding Tax Rates in Invoice Form

```php
// Get rates as options
$options = $teamleader->withholdingTaxRates()->asOptions();

// Generate HTML select
echo '<select name="withholding_tax_rate_id">';
echo '<option value="">No withholding tax</option>';
foreach ($options as $id => $description) {
    echo "<option value=\"{$id}\">{$description}</option>";
}
echo '</select>';
```

### Calculate Withholding Tax Amount

```php
function calculateWithholdingTax(float $amount, string $rateId, $teamleader): float
{
    $rate = $teamleader->withholdingTaxRates()->find($rateId);
    
    if (!$rate) {
        throw new Exception('Invalid withholding tax rate ID');
    }
    
    return $amount * $rate['rate'];
}

// Example usage
$invoiceAmount = 1000.00;
$rateId = 'c93ddb52-0af8-47d9-8551-441435be66a7'; // 15%
$withholdingTax = calculateWithholdingTax($invoiceAmount, $rateId, $teamleader);
$netAmount = $invoiceAmount - $withholdingTax;

echo "Invoice amount: €{$invoiceAmount}\n";
echo "Withholding tax (15%): €{$withholdingTax}\n";
echo "Net amount to pay: €{$netAmount}\n";
// Output:
// Invoice amount: €1000.00
// Withholding tax (15%): €150.00
// Net amount to pay: €850.00
```

### Find Common Withholding Rates

```php
// Common withholding tax rates for freelancers/contractors
$standard = $teamleader->withholdingTaxRates()->findByRate(0.15);  // 15% standard
$reduced = $teamleader->withholdingTaxRates()->findByRate(0.10);   // 10% reduced
$high = $teamleader->withholdingTaxRates()->findByRate(0.30);      // 30% high rate

// Store IDs for later use
$rates = [
    'standard' => $standard['id'] ?? null,
    'reduced' => $reduced['id'] ?? null,
    'high' => $high['id'] ?? null,
];
```

### Validate Withholding Tax Rate

```php
// Validate that a rate ID from user input is valid
$rateId = $_POST['withholding_tax_rate_id'];

if ($rateId && !$teamleader->withholdingTaxRates()->exists($rateId)) {
    throw new Exception('Invalid withholding tax rate selected');
}

// Get the full rate details
if ($rateId) {
    $rate = $teamleader->withholdingTaxRates()->find($rateId);
    $percentage = ($rate['rate'] * 100);
    echo "Applying {$percentage}% withholding tax\n";
}
```

### Display Rates Summary

```php
// Show a summary of all available withholding tax rates
$rates = $teamleader->withholdingTaxRates()->sortedByRate();

echo "Available Withholding Tax Rates:\n\n";

if (empty($rates['data'])) {
    echo "No withholding tax rates configured.\n";
} else {
    foreach ($rates['data'] as $rate) {
        $percentage = ($rate['rate'] * 100);
        echo "• {$rate['description']}: {$percentage}%\n";
        echo "  ID: {$rate['id']}\n";
        echo "  Department: {$rate['department']['id']}\n\n";
    }
    
    echo "Total: " . count($rates['data']) . " rate(s)\n";
}
```

### Group Rates by Range

```php
// Categorize withholding rates by range
$lowRates = $teamleader->withholdingTaxRates()->findByRateRange(0.00, 0.15);
$mediumRates = $teamleader->withholdingTaxRates()->findByRateRange(0.16, 0.25);
$highRates = $teamleader->withholdingTaxRates()->findByRateRange(0.26, 1.00);

echo "Low withholding rates (0-15%):\n";
foreach ($lowRates as $rate) {
    echo "  - {$rate['description']}\n";
}

echo "\nMedium withholding rates (16-25%):\n";
foreach ($mediumRates as $rate) {
    echo "  - {$rate['description']}\n";
}

echo "\nHigh withholding rates (26%+):\n";
foreach ($highRates as $rate) {
    echo "  - {$rate['description']}\n";
}
```

### Calculate Net Payment with Withholding Tax

```php
// Calculate the actual amount to pay after withholding tax
function calculateNetPayment(float $grossAmount, ?string $rateId, $teamleader): array
{
    if (!$rateId) {
        return [
            'gross' => $grossAmount,
            'withholding_tax' => 0.00,
            'net' => $grossAmount,
            'rate' => 0
        ];
    }
    
    $rate = $teamleader->withholdingTaxRates()->find($rateId);
    
    if (!$rate) {
        throw new Exception('Invalid withholding tax rate');
    }
    
    $withholdingTax = $grossAmount * $rate['rate'];
    $netAmount = $grossAmount - $withholdingTax;
    
    return [
        'gross' => $grossAmount,
        'withholding_tax' => $withholdingTax,
        'net' => $netAmount,
        'rate' => $rate['rate'] * 100,
        'rate_description' => $rate['description']
    ];
}

// Usage
$payment = calculateNetPayment(2000.00, 'rate-uuid', $teamleader);

echo "Gross amount: €" . number_format($payment['gross'], 2) . "\n";
echo "Withholding tax ({$payment['rate']}%): €" . number_format($payment['withholding_tax'], 2) . "\n";
echo "Net payment: €" . number_format($payment['net'], 2) . "\n";
```

## Notes

- Withholding tax rates are managed in Teamleader Focus settings and cannot be created, updated, or deleted via the API
- The `list()` method returns all rates at once - no pagination or filtering is available
- The `rate` field is a decimal value (e.g., 0.15 for 15%, not 15)
- When comparing rates, use a small tolerance (e.g., 0.0001) to account for floating-point precision
- Withholding tax rates are typically used for invoicing freelancers, contractors, or in specific tax jurisdictions
- Common withholding tax percentages vary by country and context (often 10%, 15%, 20%, 25%, or 30%)
- Withholding tax is deducted from the invoice amount and paid directly to tax authorities
- The net amount (after withholding tax) is what the contractor/freelancer actually receives
- Withholding tax rates are department-specific in the Teamleader system
- Use the `format()` method to display rates in a user-friendly way
- The `sortedByRate()` method sorts rates from lowest to highest
- Consider caching withholding tax rates as they don't change frequently

## Withholding Tax vs Regular Tax

**Withholding Tax:**
- Deducted from the invoice amount at source
- Paid directly to tax authorities by the payer
- Contractor receives net amount (invoice amount minus withholding tax)
- Common for freelancers, contractors, international payments
- Example: €1000 invoice with 15% withholding = €850 paid to contractor, €150 to tax office

**Regular Tax (VAT/Sales Tax):**
- Added to the invoice amount
- Collected by the seller and remitted to tax authorities
- Customer pays the full amount including tax
- Common for most business-to-consumer transactions
- Example: €1000 + 21% VAT = €1210 paid by customer

## Best Practices

1. **Clearly communicate withholding**: When withholding tax applies, clearly show it on invoices
2. **Store rate IDs**: Reference withholding rates by ID, not by percentage value
3. **Handle optional withholding**: Not all invoices require withholding tax - make it optional in forms
4. **Display net amounts**: Clearly show both gross and net amounts when withholding applies
5. **Document compliance**: Keep records of withholding tax for tax reporting and compliance
6. **Cache rates**: Withholding rates change infrequently, so caching is appropriate
7. **Validate selections**: Always validate that user-selected rates exist before processing

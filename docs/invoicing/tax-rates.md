# Tax Rates

The Tax Rates resource allows you to retrieve available tax rates in Teamleader Focus. Tax rates define the percentage of tax applied to invoices and are typically configured per department. This is a read-only resource managed through the Teamleader application settings.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Listing Tax Rates](#listing-tax-rates)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Pagination](#pagination)
- [Finding Tax Rates](#finding-tax-rates)
- [Convenience Methods](#convenience-methods)
- [Data Fields](#data-fields)

## Basic Usage

```php
// Get the tax rates resource
$taxRates = $teamleader->taxRates();

// List all tax rates
$allRates = $taxRates->list();

// Find tax rate by rate value
$twentyOnePercent = $taxRates->findByRate(0.21);
```

## Listing Tax Rates

```php
// Get all tax rates
$taxRates = $teamleader->taxRates()->list();

// List with pagination
$taxRates = $teamleader->taxRates()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Get all tax rates across all pages
$allRates = $teamleader->taxRates()->all();
```

## Filtering

### Available Filters

Tax rates can be filtered using:

- **`department_id`**: Filter by department UUID

### Filtering Examples

```php
// Filter by department
$taxRates = $teamleader->taxRates()->list([
    'department_id' => '080aac72-ff1a-4627-bfe3-146b6eee979c'
]);

// Using the convenience method
$taxRates = $teamleader->taxRates()->forDepartment('department-uuid');
```

## Sorting

### Available Sort Fields

Tax rates can be sorted by:
- `department_id`
- `rate`
- `description`

### Sorting Examples

```php
// Sort by rate ascending
$taxRates = $teamleader->taxRates()->list([], [
    'sort' => [
        ['field' => 'rate', 'order' => 'asc']
    ]
]);

// Sort by description descending
$taxRates = $teamleader->taxRates()->list([], [
    'sort' => [
        ['field' => 'description', 'order' => 'desc']
    ]
]);

// Sort by department_id
$taxRates = $teamleader->taxRates()->list([], [
    'sort' => [
        ['field' => 'department_id', 'order' => 'asc']
    ]
]);

// Multiple sort fields
$taxRates = $teamleader->taxRates()->list([], [
    'sort' => [
        ['field' => 'department_id', 'order' => 'asc'],
        ['field' => 'rate', 'order' => 'asc']
    ]
]);

// Using convenience method for sorting by rate
$taxRates = $teamleader->taxRates()->sortedByRate();

// Using convenience method for sorting by description
$taxRates = $teamleader->taxRates()->sortedByDescription([], 'asc');
```

## Pagination

Tax rates support pagination to handle large result sets efficiently.

```php
// Get first page (20 items per page by default)
$page1 = $teamleader->taxRates()->list([], [
    'page_size' => 20,
    'page_number' => 1
]);

// Get second page
$page2 = $teamleader->taxRates()->list([], [
    'page_size' => 20,
    'page_number' => 2
]);

// Get larger page size
$taxRates = $teamleader->taxRates()->list([], [
    'page_size' => 100,
    'page_number' => 1
]);

// Combine with filters and sorting
$taxRates = $teamleader->taxRates()->list(
    ['department_id' => 'department-uuid'],
    [
        'page_size' => 50,
        'page_number' => 1,
        'sort' => [
            ['field' => 'rate', 'order' => 'asc']
        ]
    ]
);
```

## Finding Tax Rates

### Find by ID

```php
// Find a specific tax rate by ID
$taxRate = $teamleader->taxRates()->find('tax-rate-uuid');

if ($taxRate) {
    echo "Found: {$taxRate['description']} - Rate: {$taxRate['rate']}";
}
```

### Find by Rate

```php
// Find tax rate with exact rate value (21% = 0.21)
$taxRate = $teamleader->taxRates()->findByRate(0.21);

// Find in a specific department
$taxRate = $teamleader->taxRates()->findByRate(0.21, 'department-uuid');

// Common tax rates
$vat21 = $teamleader->taxRates()->findByRate(0.21); // 21% VAT
$vat6 = $teamleader->taxRates()->findByRate(0.06);  // 6% VAT
$vat0 = $teamleader->taxRates()->findByRate(0.00);  // 0% VAT
```

### Find by Rate Range

```php
// Find all tax rates between 5% and 25%
$taxRates = $teamleader->taxRates()->findByRateRange(0.05, 0.25);

// Find in a specific department
$taxRates = $teamleader->taxRates()->findByRateRange(0.05, 0.25, 'department-uuid');

// Find reduced rates (typically 0% to 10%)
$reducedRates = $teamleader->taxRates()->findByRateRange(0.00, 0.10);

// Find standard rates (typically above 15%)
$standardRates = $teamleader->taxRates()->findByRateRange(0.15, 1.00);
```

### Find by Description

```php
// Find tax rate by exact description
$taxRate = $teamleader->taxRates()->findByDescription('21%');

// Find in a specific department
$taxRate = $teamleader->taxRates()->findByDescription('21%', 'department-uuid');

// Partial match search
$taxRate = $teamleader->taxRates()->findByDescription('21', null, false);

// Case-insensitive search
$taxRate = $teamleader->taxRates()->findByDescription('vat 21%');
```

### Check if Tax Rate Exists

```php
// Check if a tax rate ID is valid
$exists = $teamleader->taxRates()->exists('tax-rate-uuid');

if ($exists) {
    // Tax rate exists
}
```

## Convenience Methods

### Get Tax Rates for Department

```php
// Get all tax rates for a specific department
$taxRates = $teamleader->taxRates()->forDepartment('department-uuid');

// With pagination
$taxRates = $teamleader->taxRates()->forDepartment('department-uuid', [
    'page_size' => 50,
    'page_number' => 1
]);
```

### Get All Tax Rates

```php
// Get all tax rates (handles pagination automatically)
$allRates = $teamleader->taxRates()->all();

// For a specific department
$allDepartmentRates = $teamleader->taxRates()->all([
    'department_id' => 'department-uuid'
]);

// Limit maximum pages to fetch
$allRates = $teamleader->taxRates()->all([], 5); // Max 5 pages
```

### Get Tax Rates Sorted

```php
// Get tax rates sorted by rate (ascending)
$sortedRates = $teamleader->taxRates()->sortedByRate();

// With department filter
$sortedRates = $teamleader->taxRates()->sortedByRate([
    'department_id' => 'department-uuid'
]);

// Sort by description
$sortedByDesc = $teamleader->taxRates()->sortedByDescription();
$sortedByDescDesc = $teamleader->taxRates()->sortedByDescription([], 'desc');
```

### Get Tax Rates Grouped by Department

```php
// Get tax rates grouped by department
$grouped = $teamleader->taxRates()->groupedByDepartment();

foreach ($grouped as $departmentId => $data) {
    echo "Department: {$data['department']['id']}\n";
    foreach ($data['tax_rates'] as $rate) {
        echo "  - {$rate['description']}: {$rate['rate']}\n";
    }
}
```

### Get as Select Options

```php
// Get tax rates as key-value pairs for dropdowns
$options = $teamleader->taxRates()->asOptions();
// Returns: ['uuid1' => '21%', 'uuid2' => '6%', ...]

// For a specific department
$departmentOptions = $teamleader->taxRates()->asOptions('department-uuid');

// Use in a form
echo '<select name="tax_rate_id">';
foreach ($options as $id => $description) {
    echo "<option value=\"{$id}\">{$description}</option>";
}
echo '</select>';
```

## Data Fields

### Tax Rate Fields

Each tax rate contains:

- **`id`**: Tax rate UUID (string)
    - Example: `"c93ddb52-0af8-47d9-8551-441435be66a7"`
    - Unique identifier for the tax rate

- **`description`**: Tax rate description (string)
    - Example: `"21%"`, `"6%"`, `"VAT Standard"`
    - Human-readable description of the tax rate

- **`rate`**: Tax rate as decimal (number)
    - Example: `0.21` for 21%, `0.06` for 6%, `0.00` for 0%
    - The actual tax rate value

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
            'description' => '21%',
            'rate' => 0.21,
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ]
        ],
        [
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'description' => '6%',
            'rate' => 0.06,
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ]
        ],
        [
            'id' => 'f0e9d8c7-b6a5-4321-9876-543210fedcba',
            'description' => '0%',
            'rate' => 0.00,
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
    'description' => '21%',
    'rate' => 0.21,
    'department' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'department'
    ]
]

// Not found
null
```

### Grouped by Department Response

```php
[
    'eab232c6-49b2-4b7e-a977-5e1148dad471' => [
        'department' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'department'
        ],
        'tax_rates' => [
            [
                'id' => 'c93ddb52-0af8-47d9-8551-441435be66a7',
                'description' => '21%',
                'rate' => 0.21,
                'department' => [
                    'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                    'type' => 'department'
                ]
            ],
            // ... more tax rates for this department
        ]
    ],
    // ... more departments
]
```

### As Options Response

```php
[
    'c93ddb52-0af8-47d9-8551-441435be66a7' => '21%',
    'a1b2c3d4-e5f6-7890-abcd-ef1234567890' => '6%',
    'f0e9d8c7-b6a5-4321-9876-543210fedcba' => '0%'
]
```

## Common Use Cases

### Display Tax Rates in Invoice Form

```php
// Get tax rates as options for the current department
$departmentId = 'current-department-uuid';
$options = $teamleader->taxRates()->asOptions($departmentId);

// Generate HTML select
echo '<select name="tax_rate_id">';
echo '<option value="">Select tax rate</option>';
foreach ($options as $id => $description) {
    echo "<option value=\"{$id}\">{$description}</option>";
}
echo '</select>';
```

### Calculate Tax Amount

```php
function calculateTax(float $amount, string $taxRateId, $teamleader): float
{
    $taxRate = $teamleader->taxRates()->find($taxRateId);
    
    if (!$taxRate) {
        throw new Exception('Invalid tax rate ID');
    }
    
    return $amount * $taxRate['rate'];
}

// Example usage
$subtotal = 100.00;
$taxRateId = 'c93ddb52-0af8-47d9-8551-441435be66a7'; // 21%
$taxAmount = calculateTax($subtotal, $taxRateId, $teamleader);
$total = $subtotal + $taxAmount;

echo "Subtotal: €{$subtotal}\n";
echo "Tax: €{$taxAmount}\n";
echo "Total: €{$total}\n";
// Output:
// Subtotal: €100.00
// Tax: €21.00
// Total: €121.00
```

### Find Common VAT Rates

```php
// Belgium VAT rates (example)
$standardRate = $teamleader->taxRates()->findByRate(0.21);  // 21% standard
$reducedRate = $teamleader->taxRates()->findByRate(0.06);   // 6% reduced
$parkingRate = $teamleader->taxRates()->findByRate(0.12);   // 12% parking
$zeroRate = $teamleader->taxRates()->findByRate(0.00);      // 0% exempt

// Store IDs for later use
$vatRates = [
    'standard' => $standardRate['id'] ?? null,
    'reduced' => $reducedRate['id'] ?? null,
    'parking' => $parkingRate['id'] ?? null,
    'zero' => $zeroRate['id'] ?? null,
];
```

### Validate Tax Rate

```php
// Validate that a tax rate ID from user input is valid
$taxRateId = $_POST['tax_rate_id'];

if ($teamleader->taxRates()->exists($taxRateId)) {
    $taxRate = $teamleader->taxRates()->find($taxRateId);
    echo "Using tax rate: {$taxRate['description']} ({$taxRate['rate']})\n";
} else {
    throw new Exception('Invalid tax rate selected');
}
```

### Display Tax Rates Summary by Department

```php
// Get tax rates grouped by department
$grouped = $teamleader->taxRates()->groupedByDepartment();

echo "Tax Rates by Department:\n\n";

foreach ($grouped as $departmentId => $data) {
    echo "Department ID: {$departmentId}\n";
    echo "Tax Rates:\n";
    
    foreach ($data['tax_rates'] as $rate) {
        $percentage = ($rate['rate'] * 100) . '%';
        echo "  - {$rate['description']}: {$percentage}\n";
    }
    
    echo "\n";
}
```

### Get Tax Rate from Description Input

```php
// User enters "21%" or "21" - find the matching tax rate
function getTaxRateFromInput(string $input, $teamleader): ?array
{
    // Try exact match first
    $taxRate = $teamleader->taxRates()->findByDescription($input);
    
    if (!$taxRate) {
        // Try partial match
        $taxRate = $teamleader->taxRates()->findByDescription($input, null, false);
    }
    
    if (!$taxRate) {
        // Try converting to decimal and finding by rate
        $numericValue = preg_replace('/[^0-9.]/', '', $input);
        if (is_numeric($numericValue)) {
            $rate = floatval($numericValue);
            // If input is like "21", assume it's percentage
            if ($rate > 1) {
                $rate = $rate / 100;
            }
            $taxRate = $teamleader->taxRates()->findByRate($rate);
        }
    }
    
    return $taxRate;
}

// Usage
$userInput = '21%';
$taxRate = getTaxRateFromInput($userInput, $teamleader);
if ($taxRate) {
    echo "Found tax rate: {$taxRate['id']}";
}
```

## Notes

- Tax rates are managed in Teamleader Focus settings and cannot be created, updated, or deleted via the API
- Tax rates are department-specific - different departments may have different tax rates available
- The `rate` field is a decimal value (e.g., 0.21 for 21%, not 21)
- When comparing rates, use a small tolerance (e.g., 0.0001) to account for floating-point precision
- Tax rate descriptions are typically formatted as percentages (e.g., "21%", "6%")
- The `sortedByRate()` method always sorts ascending by default
- Use `groupedByDepartment()` when you need to display tax rates organized by department
- The `asOptions()` method is useful for generating dropdown selections in forms
- Tax rates returned by the API reflect the configuration in your Teamleader Focus account
- Common EU VAT rates: 0% (exempt), 6% (reduced), 12% (parking), 21% (standard) - actual rates vary by country

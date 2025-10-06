# Commercial Discounts

The Commercial Discounts resource allows you to retrieve predefined commercial discounts in Teamleader Focus. Commercial discounts are reusable discount configurations that can be applied to invoices and quotes. This is a read-only resource managed through the Teamleader application settings.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Listing Commercial Discounts](#listing-commercial-discounts)
- [Filtering](#filtering)
- [Finding Discounts](#finding-discounts)
- [Convenience Methods](#convenience-methods)
- [Data Fields](#data-fields)

## Basic Usage

```php
// Get the commercial discounts resource
$discounts = $teamleader->commercialDiscounts();

// List all commercial discounts
$allDiscounts = $discounts->list();

// Find discount by name
$holidayDiscount = $discounts->findByName('Holiday discount');
```

## Listing Commercial Discounts

```php
// Get all commercial discounts
$discounts = $teamleader->commercialDiscounts()->list();

// Access the data
$discountList = $discounts['data'];
foreach ($discountList as $discount) {
    echo "{$discount['name']}\n";
}
```

**Note:** Commercial discounts do not support pagination or sorting. The `list()` method returns all available discounts.

## Filtering

### Available Filters

Commercial discounts can be filtered using:

- **`department_id`**: Filter by department UUID

### Filtering Examples

```php
// Filter by department
$discounts = $teamleader->commercialDiscounts()->list([
    'department_id' => '6a6343fc-fdd8-4bc0-aa69-3a004c710e87'
]);

// Using the convenience method
$discounts = $teamleader->commercialDiscounts()->forDepartment('department-uuid');
```

## Finding Discounts

### Find by Name

```php
// Find by exact name (case-insensitive)
$discount = $teamleader->commercialDiscounts()->findByName('Holiday discount');

if ($discount) {
    echo "Found: {$discount['name']}";
    echo "Department: {$discount['department']['id']}";
}

// Find in a specific department
$discount = $teamleader->commercialDiscounts()->findByName(
    'Holiday discount',
    'department-uuid'
);

// Partial name match
$discount = $teamleader->commercialDiscounts()->findByName(
    'Holiday',
    null,
    false  // exactMatch = false
);
```

### Search by Partial Name

```php
// Search for discounts containing "summer"
$discounts = $teamleader->commercialDiscounts()->search('summer');

// Search in a specific department
$discounts = $teamleader->commercialDiscounts()->search('summer', 'department-uuid');

// Display results
foreach ($discounts as $discount) {
    echo "- {$discount['name']}\n";
}
```

### Check if Discount Exists

```php
// Check if a discount exists by name
$exists = $teamleader->commercialDiscounts()->exists('Holiday discount');

if ($exists) {
    // Discount exists
}

// Check in a specific department
$exists = $teamleader->commercialDiscounts()->exists(
    'Holiday discount',
    'department-uuid'
);
```

## Convenience Methods

### Get Discounts for Department

```php
// Get all discounts for a specific department
$discounts = $teamleader->commercialDiscounts()->forDepartment('department-uuid');

foreach ($discounts['data'] as $discount) {
    echo "{$discount['name']}\n";
}
```

### Get Discounts Grouped by Department

```php
// Get discounts organized by department
$grouped = $teamleader->commercialDiscounts()->groupedByDepartment();

foreach ($grouped as $departmentId => $data) {
    echo "Department: {$departmentId}\n";
    echo "Discounts:\n";
    foreach ($data['discounts'] as $discount) {
        echo "  - {$discount['name']}\n";
    }
    echo "\n";
}
```

### Get Discount Names

```php
// Get all discount names as a simple array
$names = $teamleader->commercialDiscounts()->names();
// Returns: ['Holiday discount', 'Early bird discount', 'Volume discount', ...]

// For a specific department
$departmentNames = $teamleader->commercialDiscounts()->names('department-uuid');

// Use in validation
$allowedDiscounts = $teamleader->commercialDiscounts()->names();
if (in_array($userInput, $allowedDiscounts)) {
    // Valid discount name
}
```

### Get as Select Options

```php
// Get discounts as key-value pairs for dropdowns
$options = $teamleader->commercialDiscounts()->asOptions();
// Returns: ['Holiday discount' => 'Holiday discount', 'Early bird discount' => 'Early bird discount', ...]

// For a specific department
$departmentOptions = $teamleader->commercialDiscounts()->asOptions('department-uuid');

// Use in a form
echo '<select name="discount_name">';
echo '<option value="">Select a discount</option>';
foreach ($options as $name => $label) {
    echo "<option value=\"{$name}\">{$label}</option>";
}
echo '</select>';
```

## Data Fields

### Commercial Discount Fields

Each commercial discount contains:

- **`name`**: Discount name (string)
    - Example: `"My holiday discount"`, `"Early bird special"`, `"Volume discount"`
    - The human-readable name of the discount
    - This is the only identifier available (no separate ID field in the API response)

- **`department`**: Department reference (object)
    - **`id`**: Department UUID (string)
        - Example: `"eab232c6-49b2-4b7e-a977-5e1148dad471"`
        - The department this discount belongs to
    - **`type`**: Resource type (string)
        - Always `"department"`

## Response Examples

### List Response

```php
[
    'data' => [
        [
            'name' => 'Holiday discount',
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ]
        ],
        [
            'name' => 'Early bird discount',
            'department' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'department'
            ]
        ],
        [
            'name' => 'Volume discount',
            'department' => [
                'id' => 'f0e9d8c7-b6a5-4321-9876-543210fedcba',
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
    'name' => 'Holiday discount',
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
        'discounts' => [
            [
                'name' => 'Holiday discount',
                'department' => [
                    'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                    'type' => 'department'
                ]
            ],
            [
                'name' => 'Early bird discount',
                'department' => [
                    'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                    'type' => 'department'
                ]
            ]
        ]
    ],
    // ... more departments
]
```

### Names Response

```php
[
    'Holiday discount',
    'Early bird discount',
    'Volume discount',
    'Loyalty discount'
]
```

## Common Use Cases

### Display Discounts in Invoice Form

```php
// Get available discounts as options
$options = $teamleader->commercialDiscounts()->asOptions();

// Generate HTML select
echo '<select name="commercial_discount">';
echo '<option value="">No discount</option>';
foreach ($options as $name => $label) {
    echo "<option value=\"{$name}\">{$label}</option>";
}
echo '</select>';
```

### Validate User-Selected Discount

```php
// Validate that a discount name from user input is valid
$discountName = $_POST['commercial_discount'];

if ($discountName && !$teamleader->commercialDiscounts()->exists($discountName)) {
    throw new Exception('Invalid commercial discount selected');
}

// Or get the full discount details
$discount = $teamleader->commercialDiscounts()->findByName($discountName);
if (!$discount) {
    throw new Exception('Invalid commercial discount selected');
}
```

### Filter Discounts by Search Term

```php
// Allow users to search for discounts
$searchTerm = $_GET['search'] ?? '';
$results = $teamleader->commercialDiscounts()->search($searchTerm);

// Display search results
foreach ($results as $discount) {
    echo "- {$discount['name']} (Dept: {$discount['department']['id']})\n";
}
```

### Display Discounts by Department

```php
// Show discounts organized by department
$grouped = $teamleader->commercialDiscounts()->groupedByDepartment();

echo "<h2>Commercial Discounts by Department</h2>";
foreach ($grouped as $departmentId => $data) {
    echo "<h3>Department: {$departmentId}</h3>";
    echo "<ul>";
    foreach ($data['discounts'] as $discount) {
        echo "<li>{$discount['name']}</li>";
    }
    echo "</ul>";
}
```

### Get Department-Specific Discounts for Form

```php
// When creating an invoice for a specific department
$departmentId = 'user-department-uuid';
$availableDiscounts = $teamleader->commercialDiscounts()->forDepartment($departmentId);

// Show only relevant discounts
echo '<select name="discount">';
echo '<option value="">No discount</option>';
foreach ($availableDiscounts['data'] as $discount) {
    echo "<option value=\"{$discount['name']}\">{$discount['name']}</option>";
}
echo '</select>';
```

### Check for Seasonal Discounts

```php
// Check if seasonal discounts are available
$seasonalDiscounts = $teamleader->commercialDiscounts()->search('holiday');

if (!empty($seasonalDiscounts)) {
    echo "Special holiday discounts are available:\n";
    foreach ($seasonalDiscounts as $discount) {
        echo "- {$discount['name']}\n";
    }
} else {
    echo "No seasonal discounts available at this time.\n";
}
```

### Auto-complete Discount Input

```php
// Provide auto-complete suggestions based on partial input
function getDiscountSuggestions(string $partial, $teamleader): array
{
    $matches = $teamleader->commercialDiscounts()->search($partial);
    
    $suggestions = [];
    foreach ($matches as $discount) {
        $suggestions[] = [
            'value' => $discount['name'],
            'label' => $discount['name'],
            'department' => $discount['department']['id']
        ];
    }
    
    return $suggestions;
}

// Usage in API endpoint
$partial = $_GET['q'] ?? '';
if (strlen($partial) >= 2) {
    $suggestions = getDiscountSuggestions($partial, $teamleader);
    echo json_encode($suggestions);
}
```

### Display Available Discounts Summary

```php
// Show a summary of all available discounts
$discounts = $teamleader->commercialDiscounts()->list();

echo "Available Commercial Discounts:\n\n";

if (empty($discounts['data'])) {
    echo "No commercial discounts configured.\n";
} else {
    foreach ($discounts['data'] as $discount) {
        echo "• {$discount['name']}\n";
        echo "  Department ID: {$discount['department']['id']}\n\n";
    }
    
    echo "Total: " . count($discounts['data']) . " discount(s)\n";
}
```

## Notes

- Commercial discounts are managed in Teamleader Focus settings and cannot be created, updated, or deleted via the API
- The API response does not include a separate ID field - the discount name serves as the identifier
- Discount names should be unique within a department to avoid ambiguity
- When using discounts in invoices, you reference them by name, not by ID
- The `list()` method returns all discounts at once - no pagination is available
- Discounts are department-specific - each department can have its own set of commercial discounts
- Use case-insensitive search methods when looking up discounts by user input
- The `search()` method performs case-insensitive partial matching on discount names
- Commercial discounts define reusable discount configurations that can be applied to invoice line items
- The actual discount amount/percentage is configured in Teamleader and not returned by this API endpoint
- Always validate user-provided discount names against the list of available discounts
- Consider caching the list of commercial discounts if you need to reference them frequently

## Best Practices

1. **Cache discount lists**: Since discounts don't change frequently, consider caching the results
2. **Validate user input**: Always validate discount names from user input against available discounts
3. **Use case-insensitive comparison**: The `findByName()` method uses case-insensitive comparison by default
4. **Department filtering**: When working with multi-department setups, filter discounts by department to show only relevant options
5. **Search functionality**: Use the `search()` method to implement type-ahead or auto-complete features
6. **Error handling**: Handle cases where a discount might not exist or has been removed from the system

# Payment Terms

The Payment Terms resource allows you to retrieve available payment terms in Teamleader Focus. Payment terms define when an invoice should be paid (immediately, after X days, end of month, etc.). This is a read-only resource managed through the Teamleader application settings.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Listing Payment Terms](#listing-payment-terms)
- [Finding Payment Terms](#finding-payment-terms)
- [Default Payment Term](#default-payment-term)
- [Payment Term Types](#payment-term-types)
- [Convenience Methods](#convenience-methods)
- [Data Fields](#data-fields)

## Basic Usage

```php
// Get the payment terms resource
$paymentTerms = $teamleader->paymentTerms();

// List all payment terms
$allTerms = $paymentTerms->list();

// Get the default payment term
$defaultTerm = $paymentTerms->getDefault();
```

## Listing Payment Terms

```php
// Get all payment terms
$paymentTerms = $teamleader->paymentTerms()->list();

// Access the data
$terms = $paymentTerms['data'];
$defaultId = $paymentTerms['meta']['default'];
```

**Note:** Payment terms do not support filtering, pagination, or sorting. The `list()` method returns all available payment terms.

## Finding Payment Terms

### Find by ID

```php
// Find a specific payment term by ID
$term = $teamleader->paymentTerms()->find('payment-term-uuid');

if ($term) {
    echo "Found: {$term['type']} - {$term['days']} days";
}
```

### Find by Type

```php
// Find all cash payment terms
$cashTerms = $teamleader->paymentTerms()->findByType('cash');

// Find all end of month payment terms
$endOfMonthTerms = $teamleader->paymentTerms()->findByType('end_of_month');

// Find all after invoice date payment terms
$afterInvoiceTerms = $teamleader->paymentTerms()->findByType('after_invoice_date');
```

### Find by Days

```php
// Find payment term with 30 days
$term = $teamleader->paymentTerms()->findByDays(30);

// Find 30 days payment term of specific type
$term = $teamleader->paymentTerms()->findByDays(30, 'after_invoice_date');

// Find 15 days end of month term
$term = $teamleader->paymentTerms()->findByDays(15, 'end_of_month');
```

### Check if Payment Term Exists

```php
// Check if a payment term ID is valid
$exists = $teamleader->paymentTerms()->exists('payment-term-uuid');

if ($exists) {
    // Payment term exists
}
```

## Default Payment Term

Teamleader allows you to set a default payment term that is automatically selected for new invoices.

```php
// Get the default payment term
$defaultTerm = $teamleader->paymentTerms()->getDefault();

if ($defaultTerm) {
    echo "Default payment term: {$defaultTerm['type']}";
    if (isset($defaultTerm['days'])) {
        echo " - {$defaultTerm['days']} days";
    }
}

// Get just the default payment term ID
$defaultId = $teamleader->paymentTerms()->getDefaultId();
```

## Payment Term Types

Payment terms have three types, each with different behavior:

### Cash

- **Type:** `cash`
- **Description:** Direct/immediate payment
- **Days field:** Not required (not used)
- **Example:** Payment due immediately upon receipt

```php
// Get all cash payment terms
$cashTerms = $teamleader->paymentTerms()->cash();

foreach ($cashTerms as $term) {
    echo "Cash payment term ID: {$term['id']}\n";
}
```

### End of Month

- **Type:** `end_of_month`
- **Description:** End of the month + X days after the invoice date
- **Days field:** Modifier X (e.g., 15 means end of month + 15 days)
- **Example:** If invoice date is January 15th and days is 15, payment is due February 15th (end of January + 15 days)

```php
// Get all end of month payment terms
$endOfMonthTerms = $teamleader->paymentTerms()->endOfMonth();

foreach ($endOfMonthTerms as $term) {
    $days = $term['days'] ?? 0;
    echo "End of month + {$days} days\n";
}
```

### After Invoice Date

- **Type:** `after_invoice_date`
- **Description:** X days after the invoice date
- **Days field:** Number of days after invoice date
- **Example:** If days is 30, payment is due 30 days after the invoice date

```php
// Get all after invoice date payment terms
$afterInvoiceTerms = $teamleader->paymentTerms()->afterInvoiceDate();

foreach ($afterInvoiceTerms as $term) {
    $days = $term['days'] ?? 0;
    echo "{$days} days after invoice date\n";
}
```

## Convenience Methods

### Get by Type Shortcuts

```php
// Shortcut methods for each type
$cashTerms = $teamleader->paymentTerms()->cash();
$endOfMonthTerms = $teamleader->paymentTerms()->endOfMonth();
$afterInvoiceTerms = $teamleader->paymentTerms()->afterInvoiceDate();
```

### Format as Human-Readable Description

```php
// Get a payment term
$term = $teamleader->paymentTerms()->findByDays(30, 'after_invoice_date');

// Format as human-readable description
$description = $teamleader->paymentTerms()->formatPaymentTermDescription($term);
// Returns: "30 days after invoice date"

// Example for end of month
$term = $teamleader->paymentTerms()->findByDays(15, 'end_of_month');
$description = $teamleader->paymentTerms()->formatPaymentTermDescription($term);
// Returns: "End of month + 15 days"

// Example for cash
$term = $teamleader->paymentTerms()->cash()[0];
$description = $teamleader->paymentTerms()->formatPaymentTermDescription($term);
// Returns: "Cash (immediate payment)"
```

### Get as Select Options

```php
// Get payment terms as key-value pairs for dropdowns
$options = $teamleader->paymentTerms()->asOptions();
// Returns: ['uuid1' => '30 days after invoice date', 'uuid2' => 'Cash (immediate payment)', ...]

// Use in a form
echo '<select name="payment_term_id">';
foreach ($options as $id => $description) {
    echo "<option value=\"{$id}\">{$description}</option>";
}
echo '</select>';

// Pre-select the default
$defaultId = $teamleader->paymentTerms()->getDefaultId();
foreach ($options as $id => $description) {
    $selected = ($id === $defaultId) ? 'selected' : '';
    echo "<option value=\"{$id}\" {$selected}>{$description}</option>";
}
```

### Validate Payment Term Type

```php
// Check if a type is valid
$isValid = $teamleader->paymentTerms()->isValidType('cash'); // true
$isValid = $teamleader->paymentTerms()->isValidType('invalid'); // false
```

## Data Fields

### Payment Term Fields

Each payment term contains:

- **`id`**: Payment term UUID (string)
    - Example: `"c93ddb52-0af8-47d9-8551-441435be66a7"`
    - Unique identifier for the payment term

- **`type`**: Payment term type (string)
    - **Possible values:**
        - `"cash"` - Direct payment, often cash/immediate
        - `"end_of_month"` - End of the month + X days after invoice date
        - `"after_invoice_date"` - X days after invoice date
    - Example: `"after_invoice_date"`

- **`days`**: Number of days modifier (number)
    - Not required when type is `"cash"`
    - For `"end_of_month"`: days added to end of month
    - For `"after_invoice_date"`: days after invoice date
    - Example: `30`

### Response Metadata

The response includes metadata with the default payment term:

- **`meta.default`**: UUID of the default payment term (string)
    - Example: `"c93ddb52-0af8-47d9-8551-441435be66a7"`
    - This payment term is automatically selected for new invoices

## Response Examples

### List Response

```php
[
    'data' => [
        [
            'id' => 'c93ddb52-0af8-47d9-8551-441435be66a7',
            'type' => 'cash'
        ],
        [
            'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'type' => 'after_invoice_date',
            'days' => 15
        ],
        [
            'id' => 'f0e9d8c7-b6a5-4321-9876-543210fedcba',
            'type' => 'after_invoice_date',
            'days' => 30
        ],
        [
            'id' => '11223344-5566-7788-99aa-bbccddeeff00',
            'type' => 'end_of_month',
            'days' => 15
        ]
    ],
    'meta' => [
        'default' => 'f0e9d8c7-b6a5-4321-9876-543210fedcba'
    ]
]
```

### Find Response

```php
// Found
[
    'id' => 'f0e9d8c7-b6a5-4321-9876-543210fedcba',
    'type' => 'after_invoice_date',
    'days' => 30
]

// Not found
null
```

### As Options Response

```php
[
    'c93ddb52-0af8-47d9-8551-441435be66a7' => 'Cash (immediate payment)',
    'a1b2c3d4-e5f6-7890-abcd-ef1234567890' => '15 days after invoice date',
    'f0e9d8c7-b6a5-4321-9876-543210fedcba' => '30 days after invoice date',
    '11223344-5566-7788-99aa-bbccddeeff00' => 'End of month + 15 days'
]
```

## Common Use Cases

### Display Payment Terms in Invoice Form

```php
// Get payment terms as options
$options = $teamleader->paymentTerms()->asOptions();
$defaultId = $teamleader->paymentTerms()->getDefaultId();

// Generate HTML select
echo '<select name="payment_term_id">';
foreach ($options as $id => $description) {
    $selected = ($id === $defaultId) ? 'selected' : '';
    echo "<option value=\"{$id}\" {$selected}>{$description}</option>";
}
echo '</select>';
```

### Find Common Payment Terms

```php
// Find the 30-day payment term
$thirtyDays = $teamleader->paymentTerms()->findByDays(30, 'after_invoice_date');

if ($thirtyDays) {
    echo "30-day payment term ID: {$thirtyDays['id']}";
} else {
    // Use default or create a fallback
    $defaultId = $teamleader->paymentTerms()->getDefaultId();
}
```

### Validate Invoice Payment Term

```php
// Validate that a payment term ID from user input is valid
$paymentTermId = $_POST['payment_term_id'];

if ($teamleader->paymentTerms()->exists($paymentTermId)) {
    // Valid payment term
    $term = $teamleader->paymentTerms()->find($paymentTermId);
    echo "Using payment term: " . 
         $teamleader->paymentTerms()->formatPaymentTermDescription($term);
} else {
    // Invalid payment term - use default
    $defaultId = $teamleader->paymentTerms()->getDefaultId();
}
```

### Calculate Due Date from Payment Term

```php
use DateTime;

function calculateDueDate(string $invoiceDate, array $paymentTerm): DateTime
{
    $date = new DateTime($invoiceDate);
    
    switch ($paymentTerm['type']) {
        case 'cash':
            // Due immediately
            return $date;
            
        case 'after_invoice_date':
            // Add days to invoice date
            $days = $paymentTerm['days'] ?? 0;
            $date->modify("+{$days} days");
            return $date;
            
        case 'end_of_month':
            // Go to end of invoice month
            $date->modify('last day of this month');
            // Then add additional days
            $days = $paymentTerm['days'] ?? 0;
            if ($days > 0) {
                $date->modify("+{$days} days");
            }
            return $date;
            
        default:
            return $date;
    }
}

// Example usage
$paymentTerm = $teamleader->paymentTerms()->findByDays(30, 'after_invoice_date');
$invoiceDate = '2024-01-15';
$dueDate = calculateDueDate($invoiceDate, $paymentTerm);
echo "Invoice date: {$invoiceDate}\n";
echo "Due date: " . $dueDate->format('Y-m-d') . "\n";
// Output: Due date: 2024-02-14
```

### Display Payment Terms Summary

```php
// Generate a summary of all available payment terms
$paymentTerms = $teamleader->paymentTerms()->list();
$defaultId = $paymentTerms['meta']['default'];

echo "Available Payment Terms:\n\n";

foreach ($paymentTerms['data'] as $term) {
    $description = $teamleader->paymentTerms()->formatPaymentTermDescription($term);
    $isDefault = ($term['id'] === $defaultId) ? ' (Default)' : '';
    
    echo "- {$description}{$isDefault}\n";
    echo "  ID: {$term['id']}\n";
    echo "  Type: {$term['type']}\n";
    
    if (isset($term['days'])) {
        echo "  Days: {$term['days']}\n";
    }
    
    echo "\n";
}
```

## Notes

- Payment terms are managed in Teamleader Focus settings and cannot be created, updated, or deleted via the API
- The `list()` method returns all payment terms at once - no pagination or filtering is available
- The `days` field is not present for `cash` type payment terms
- The `meta.default` field indicates which payment term is used as the default for new invoices
- Payment term descriptions can vary based on configuration in your Teamleader account
- Use the `formatPaymentTermDescription()` method to generate consistent, human-readable descriptions
- When calculating due dates, remember that `end_of_month` goes to the last day of the invoice month first, then adds the specified days
- The payment terms returned reflect the configuration in your Teamleader Focus account
- All three types (cash, end_of_month, after_invoice_date) are standard Teamleader payment term types

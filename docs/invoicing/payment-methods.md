# Payment Methods

The Payment Methods resource allows you to retrieve available payment methods in Teamleader Focus. Payment methods are read-only and managed through the Teamleader application settings.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Listing Payment Methods](#listing-payment-methods)
- [Filtering](#filtering)
- [Pagination](#pagination)
- [Convenience Methods](#convenience-methods)
- [Data Fields](#data-fields)

## Basic Usage

```php
// Get the payment methods resource
$paymentMethods = $teamleader->paymentMethods();

// List all payment methods
$allMethods = $paymentMethods->list();

// Get active payment methods
$activeMethods = $paymentMethods->active();
```

## Listing Payment Methods

```php
// Get all payment methods
$paymentMethods = $teamleader->paymentMethods()->list();

// List with pagination
$paymentMethods = $teamleader->paymentMethods()->list([], [
    'page_size' => 50,
    'page_number' => 1
]);

// Get all payment methods across all pages
$allMethods = $teamleader->paymentMethods()->all();
```

## Filtering

### Available Filters

Payment methods can be filtered using the following parameters:

- **`ids`**: Array of payment method UUIDs
- **`status`**: Array of statuses (`active`, `archived`)

### Filtering Examples

```php
// Filter by specific IDs
$paymentMethods = $teamleader->paymentMethods()->list([
    'ids' => ['uuid1', 'uuid2', 'uuid3']
]);

// Using the byIds convenience method
$paymentMethods = $teamleader->paymentMethods()->byIds([
    '92296ad0-2d61-4179-b174-9f354ca2157f',
    '53635682-c382-4fbf-9fd9-9506ca4fbcdd'
]);

// Filter by status - active only
$paymentMethods = $teamleader->paymentMethods()->list([
    'status' => ['active']
]);

// Filter by status - archived only
$paymentMethods = $teamleader->paymentMethods()->list([
    'status' => ['archived']
]);

// Filter by multiple statuses
$paymentMethods = $teamleader->paymentMethods()->list([
    'status' => ['active', 'archived']
]);

// Combine multiple filters
$paymentMethods = $teamleader->paymentMethods()->list([
    'ids' => ['uuid1', 'uuid2'],
    'status' => ['active']
]);
```

## Pagination

Payment methods support pagination to handle large result sets efficiently.

```php
// Get first page (20 items per page by default)
$page1 = $teamleader->paymentMethods()->list([], [
    'page_size' => 20,
    'page_number' => 1
]);

// Get second page
$page2 = $teamleader->paymentMethods()->list([], [
    'page_size' => 20,
    'page_number' => 2
]);

// Get larger page size
$paymentMethods = $teamleader->paymentMethods()->list([], [
    'page_size' => 100,
    'page_number' => 1
]);
```

## Convenience Methods

The Payment Methods resource provides several convenience methods for common operations:

### Get Active Payment Methods

```php
// Get all active payment methods
$active = $teamleader->paymentMethods()->active();

// With pagination
$active = $teamleader->paymentMethods()->active([], [
    'page_size' => 50,
    'page_number' => 1
]);

// With additional filters
$active = $teamleader->paymentMethods()->active([
    'ids' => ['uuid1', 'uuid2']
]);
```

### Get Archived Payment Methods

```php
// Get all archived payment methods
$archived = $teamleader->paymentMethods()->archived();

// With pagination
$archived = $teamleader->paymentMethods()->archived([], [
    'page_size' => 50,
    'page_number' => 1
]);
```

### Get Specific Payment Methods by ID

```php
// Get specific payment methods
$methods = $teamleader->paymentMethods()->byIds([
    '92296ad0-2d61-4179-b174-9f354ca2157f',
    '53635682-c382-4fbf-9fd9-9506ca4fbcdd'
]);
```

### Find Payment Method by Name

```php
// Find an active payment method by name (case-insensitive)
$method = $teamleader->paymentMethods()->findByName('Credit Card');

// Find in all payment methods (including archived)
$method = $teamleader->paymentMethods()->findByName('Bank Transfer', false);

// Check the result
if ($method) {
    $methodId = $method['id'];
    $methodName = $method['name'];
    $status = $method['status'];
}
```

### Check if Payment Method Exists

```php
// Check if a payment method exists by ID
$exists = $teamleader->paymentMethods()->exists('payment-method-uuid');

if ($exists) {
    // Payment method exists
}
```

### Get All Payment Methods

```php
// Get all payment methods (handles pagination automatically)
$allMethods = $teamleader->paymentMethods()->all();

// Get all active payment methods
$allActive = $teamleader->paymentMethods()->all(['status' => ['active']]);

// Limit maximum pages to fetch
$allMethods = $teamleader->paymentMethods()->all([], 5); // Max 5 pages
```

### Get as Select Options

```php
// Get payment methods as key-value pairs for dropdowns
$options = $teamleader->paymentMethods()->asOptions();
// Returns: ['uuid1' => 'Credit Card', 'uuid2' => 'Bank Transfer', ...]

// Include archived payment methods in options
$allOptions = $teamleader->paymentMethods()->asOptions(false);

// Use in a form
foreach ($options as $id => $name) {
    echo "<option value=\"{$id}\">{$name}</option>";
}
```

## Data Fields

### Payment Method Fields

Each payment method contains the following fields:

- **`id`**: Payment method UUID (string)
    - Example: `"49b403be-a32e-0901-9b1c-25214f9027c6"`
    - Unique identifier for the payment method

- **`name`**: Payment method name (string)
    - Example: `"Credit Card"`, `"Bank Transfer"`, `"Cash"`
    - The display name of the payment method

- **`status`**: Payment method status (string, **required**)
    - Possible values: `"active"`, `"archived"`
    - Example: `"active"`
    - Indicates whether the payment method is currently in use

## Response Examples

### List Response

```php
[
    'data' => [
        [
            'id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
            'name' => 'Credit Card',
            'status' => 'active'
        ],
        [
            'id' => '92296ad0-2d61-4179-b174-9f354ca2157f',
            'name' => 'Bank Transfer',
            'status' => 'active'
        ],
        [
            'id' => '53635682-c382-4fbf-9fd9-9506ca4fbcdd',
            'name' => 'Cash',
            'status' => 'active'
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-0abc-def123456789',
            'name' => 'Check',
            'status' => 'archived'
        ]
    ]
]
```

### Find by Name Response

```php
// Success
[
    'id' => '49b403be-a32e-0901-9b1c-25214f9027c6',
    'name' => 'Credit Card',
    'status' => 'active'
]

// Not found
null
```

### As Options Response

```php
[
    '49b403be-a32e-0901-9b1c-25214f9027c6' => 'Credit Card',
    '92296ad0-2d61-4179-b174-9f354ca2157f' => 'Bank Transfer',
    '53635682-c382-4fbf-9fd9-9506ca4fbcdd' => 'Cash'
]
```

## Common Use Cases

### Display Payment Methods in a Form

```php
// Get active payment methods as options
$options = $teamleader->paymentMethods()->asOptions();

// Generate HTML select
echo '<select name="payment_method_id">';
echo '<option value="">Select a payment method</option>';
foreach ($options as $id => $name) {
    echo "<option value=\"{$id}\">{$name}</option>";
}
echo '</select>';
```

### Validate Payment Method

```php
// Check if a payment method ID is valid and active
$paymentMethodId = 'user-provided-uuid';

$method = $teamleader->paymentMethods()->findByName('Credit Card');
if ($method && $method['status'] === 'active') {
    // Valid active payment method
    $validId = $method['id'];
} else {
    // Invalid or archived payment method
    throw new Exception('Invalid payment method');
}
```

### Get Payment Method Name by ID

```php
// Get all payment methods once and cache
$methods = $teamleader->paymentMethods()->all();
$methodsById = [];
foreach ($methods['data'] as $method) {
    $methodsById[$method['id']] = $method;
}

// Look up name by ID
$paymentMethodId = '49b403be-a32e-0901-9b1c-25214f9027c6';
$methodName = $methodsById[$paymentMethodId]['name'] ?? 'Unknown';
```

### List Active Payment Methods for Reports

```php
// Get all active payment methods
$activeMethods = $teamleader->paymentMethods()->active();

// Display in a report
foreach ($activeMethods['data'] as $method) {
    echo "Payment Method: {$method['name']} (ID: {$method['id']})\n";
}
```

## Notes

- Payment methods are managed in Teamleader Focus settings and cannot be created, updated, or deleted via the API
- The `status` field is always present and required in responses
- Payment method names are not unique - use the `id` field for identification
- The `findByName()` method performs case-insensitive matching
- Archived payment methods may still be associated with existing invoices but are not available for new transactions
- The `all()` method automatically handles pagination, fetching up to 10 pages (1000 items) by default
- Use `asOptions()` for quick dropdown/select generation in forms
- Payment methods returned by the API reflect the configuration in your Teamleader Focus account

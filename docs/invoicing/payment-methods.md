# Payment Methods

Access payment method information in Teamleader Focus.

## Overview

The Payment Methods resource provides read-only access to payment methods configured in your Teamleader account. Payment methods define how customers can pay invoices and are used when creating invoices and registering payments.

**Important:** This resource is read-only. Payment methods are configured in Teamleader Focus settings and cannot be created or modified through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Related Resources](#related-resources)

## Endpoint

`paymentMethods`

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

Get all available payment methods.

**Parameters:** None

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all payment methods
$paymentMethods = Teamleader::paymentMethods()->list();
```

## Helper Methods

The Payment Methods resource provides convenient helper methods:

### `findByName()`

Find a payment method by its name.

```php
$method = Teamleader::paymentMethods()->findByName('Bank transfer');
```

### `findById()`

Find a payment method by its UUID.

```php
$method = Teamleader::paymentMethods()->findById('payment-method-uuid');
```

### `getDefault()`

Get the default payment method if one is set.

```php
$defaultMethod = Teamleader::paymentMethods()->getDefault();
```

### `asOptions()`

Get payment methods formatted as key-value pairs for use in dropdowns.

```php
$options = Teamleader::paymentMethods()->asOptions();
// Returns: ['uuid-1' => 'Bank transfer', 'uuid-2' => 'Cash', ...]
```

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Bank transfer",
      "type": "bank_transfer"
    },
    {
      "id": "uuid",
      "name": "Cash",
      "type": "cash"
    },
    {
      "id": "uuid",
      "name": "Credit card",
      "type": "credit_card"
    },
    {
      "id": "uuid",
      "name": "Direct debit",
      "type": "direct_debit"
    }
  ],
  "meta": {
    "default": "uuid-of-default-method"
  }
}
```

## Usage Examples

### Get Available Payment Methods

```php
$paymentMethods = Teamleader::paymentMethods()->list();

echo "Available payment methods:\n";
foreach ($paymentMethods['data'] as $method) {
    $default = ($method['id'] === $paymentMethods['meta']['default']) ? ' (default)' : '';
    echo "- {$method['name']}{$default}\n";
}
```

### Use in Invoice Creation

```php
// Get default payment method
$defaultMethod = Teamleader::paymentMethods()->getDefault();

// Create invoice with payment method
$invoice = Teamleader::invoices()->create([
    'invoice_date' => '2024-02-01',
    'invoicee' => [...],
    'grouped_lines' => [...],
    'payment_method_id' => $defaultMethod['id']
]);
```

### Register Payment with Specific Method

```php
// Find payment method by name
$bankTransfer = Teamleader::paymentMethods()->findByName('Bank transfer');

// Register payment
Teamleader::invoices()->registerPayment(
    'invoice-uuid',
    250.00,
    '2024-02-10',
    $bankTransfer['id']
);
```

### Create Dropdown for Payment Methods

```php
$options = Teamleader::paymentMethods()->asOptions();

// Use in a form
echo '<select name="payment_method">';
foreach ($options as $id => $name) {
    echo "<option value='{$id}'>{$name}</option>";
}
echo '</select>';
```

## Common Use Cases

### 1. Invoice Form Population

```php
// Get payment methods for form
$paymentMethods = Teamleader::paymentMethods()->list();
$defaultMethodId = $paymentMethods['meta']['default'] ?? null;

// Pass to view
return view('invoices.create', [
    'payment_methods' => $paymentMethods['data'],
    'default_payment_method' => $defaultMethodId
]);
```

### 2. Payment Method Validation

```php
function validatePaymentMethod($methodId) {
    $methods = Teamleader::paymentMethods()->list();
    
    foreach ($methods['data'] as $method) {
        if ($method['id'] === $methodId) {
            return true;
        }
    }
    
    return false;
}
```

### 3. Payment Statistics

```php
$paymentMethods = Teamleader::paymentMethods()->list();
$invoices = Teamleader::invoices()->matched();

$stats = [];
foreach ($paymentMethods['data'] as $method) {
    $stats[$method['name']] = [
        'count' => 0,
        'total' => 0
    ];
}

// Count payments by method
foreach ($invoices['data'] as $invoice) {
    if (isset($invoice['payment_method'])) {
        $methodName = $this->getMethodName($invoice['payment_method']['id']);
        $stats[$methodName]['count']++;
        $stats[$methodName]['total'] += $invoice['total']['payable']['amount'];
    }
}
```

### 4. Cache Payment Methods

```php
use Illuminate\Support\Facades\Cache;

function getPaymentMethods() {
    return Cache::remember('payment_methods', 3600, function () {
        return Teamleader::paymentMethods()->list();
    });
}
```

## Best Practices

### 1. Cache the Results

Payment methods rarely change, so cache them to reduce API calls:

```php
$paymentMethods = Cache::remember('payment_methods', 86400, function () {
    return Teamleader::paymentMethods()->list();
});
```

### 2. Always Have a Fallback

```php
$defaultMethod = Teamleader::paymentMethods()->getDefault();

if (!$defaultMethod) {
    // Fallback to first available method
    $methods = Teamleader::paymentMethods()->list();
    $defaultMethod = $methods['data'][0] ?? null;
}
```

### 3. Use Helper Methods

```php
// Good: Clear and concise
$method = Teamleader::paymentMethods()->findByName('Bank transfer');

// Less ideal: Manual searching
$methods = Teamleader::paymentMethods()->list();
$method = null;
foreach ($methods['data'] as $m) {
    if ($m['name'] === 'Bank transfer') {
        $method = $m;
        break;
    }
}
```

### 4. Validate Before Use

```php
$methodId = $request->input('payment_method_id');

// Validate method exists
$method = Teamleader::paymentMethods()->findById($methodId);

if (!$method) {
    throw new ValidationException('Invalid payment method');
}

// Use in invoice
$invoice = Teamleader::invoices()->create([
    'payment_method_id' => $methodId,
    // ... other fields
]);
```

## Related Resources

- [Invoices](invoices.md) - Invoice management
- [Payment Terms](payment-terms.md) - Payment term configuration
- [Subscriptions](subscriptions.md) - Subscription management

# Payment Terms

Access payment term information in Teamleader Focus.

## Overview

The Payment Terms resource provides read-only access to payment terms configured in your Teamleader account. Payment terms define when invoices are due and are used when creating invoices and subscriptions.

**Important:** This resource is read-only. Payment terms are configured in Teamleader Focus settings and cannot be created or modified through the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Payment Term Types](#payment-term-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Related Resources](#related-resources)

## Endpoint

`paymentTerms`

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

Get all available payment terms.

**Parameters:** None

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all payment terms
$paymentTerms = Teamleader::paymentTerms()->list();
```

## Helper Methods

The Payment Terms resource provides convenient helper methods:

### `getDefault()`

Get the default payment term.

```php
$defaultTerm = Teamleader::paymentTerms()->getDefault();
```

### `getDefaultId()`

Get the UUID of the default payment term.

```php
$defaultTermId = Teamleader::paymentTerms()->getDefaultId();
```

### `findByType()`

Find payment terms by type.

```php
// Find all "cash" type payment terms
$cashTerms = Teamleader::paymentTerms()->findByType('cash');

// Find "after_invoice_date" terms
$standardTerms = Teamleader::paymentTerms()->findByType('after_invoice_date');
```

### `findByDays()`

Find a payment term by number of days.

```php
// Find payment term with 30 days
$term30 = Teamleader::paymentTerms()->findByDays(30);

// Find with specific type
$term30AfterInvoice = Teamleader::paymentTerms()->findByDays(30, 'after_invoice_date');
```

### `asOptions()`

Get payment terms formatted as key-value pairs for dropdowns.

```php
$options = Teamleader::paymentTerms()->asOptions();
// Returns: ['uuid-1' => 'Cash', 'uuid-2' => '30 days', ...]
```

## Payment Term Types

Payment terms can be one of three types:

1. **cash** - Payment due immediately
2. **end_of_month** - Payment due at the end of the month
3. **after_invoice_date** - Payment due X days after invoice date

## Response Structure

### List Response

```json
{
  "data": [
    {
      "id": "uuid",
      "type": "cash",
      "days": 0,
      "description": "Cash"
    },
    {
      "id": "uuid",
      "type": "after_invoice_date",
      "days": 30,
      "description": "30 days"
    },
    {
      "id": "uuid",
      "type": "end_of_month",
      "days": 0,
      "description": "End of month"
    }
  ],
  "meta": {
    "default": "uuid-of-default-term"
  }
}
```

## Usage Examples

### Get Available Payment Terms

```php
$paymentTerms = Teamleader::paymentTerms()->list();

echo "Available payment terms:\n";
foreach ($paymentTerms['data'] as $term) {
    $default = ($term['id'] === $paymentTerms['meta']['default']) ? ' (default)' : '';
    echo "- {$term['description']}{$default}\n";
}
```

### Use in Invoice Creation

```php
// Get default payment term
$defaultTerm = Teamleader::paymentTerms()->getDefault();

// Create invoice with payment term
$invoice = Teamleader::invoices()->create([
    'invoice_date' => '2024-02-01',
    'invoicee' => [...],
    'grouped_lines' => [...],
    'payment_term' => [
        'type' => $defaultTerm['type'],
        'days' => $defaultTerm['days']
    ]
]);
```

### Create Invoice with Custom Payment Term

```php
// Find 30-day payment term
$term30 = Teamleader::paymentTerms()->findByDays(30);

$invoice = Teamleader::invoices()->create([
    'invoice_date' => '2024-02-01',
    'invoicee' => [...],
    'grouped_lines' => [...],
    'payment_term' => [
        'type' => $term30['type'],
        'days' => $term30['days']
    ]
]);
```

### Calculate Due Date

```php
function calculateDueDate($invoiceDate, $paymentTerm) {
    $date = new DateTime($invoiceDate);
    
    switch ($paymentTerm['type']) {
        case 'cash':
            return $date->format('Y-m-d');
            
        case 'after_invoice_date':
            $date->modify("+{$paymentTerm['days']} days");
            return $date->format('Y-m-d');
            
        case 'end_of_month':
            $date->modify('last day of this month');
            return $date->format('Y-m-d');
            
        default:
            return $date->format('Y-m-d');
    }
}

$term = Teamleader::paymentTerms()->findByDays(30);
$dueDate = calculateDueDate('2024-02-01', $term);
echo "Due date: {$dueDate}"; // 2024-03-02
```

## Common Use Cases

### 1. Invoice Form Population

```php
$paymentTerms = Teamleader::paymentTerms()->list();
$defaultTermId = $paymentTerms['meta']['default'] ?? null;

return view('invoices.create', [
    'payment_terms' => $paymentTerms['data'],
    'default_payment_term' => $defaultTermId
]);
```

### 2. Customer-Specific Terms

```php
// Get customer's preferred payment term
$customer = Teamleader::companies()->info('company-uuid');
$preferredDays = $customer['data']['payment_term_days'] ?? 30;

// Find matching payment term
$paymentTerm = Teamleader::paymentTerms()->findByDays($preferredDays);

// Use in invoice
$invoice = Teamleader::invoices()->create([
    'payment_term' => [
        'type' => $paymentTerm['type'],
        'days' => $paymentTerm['days']
    ],
    // ... other fields
]);
```

### 3. Overdue Invoice Detection

```php
$invoices = Teamleader::invoices()->outstanding();
$paymentTerms = Teamleader::paymentTerms()->list();

foreach ($invoices['data'] as $invoice) {
    $paymentTerm = $this->findPaymentTerm(
        $invoice['payment_term'],
        $paymentTerms['data']
    );
    
    $dueDate = $this->calculateDueDate(
        $invoice['invoice_date'],
        $paymentTerm
    );
    
    if ($dueDate < date('Y-m-d')) {
        echo "Invoice {$invoice['invoice_number']} is overdue!\n";
    }
}
```

### 4. Cache Payment Terms

```php
use Illuminate\Support\Facades\Cache;

function getPaymentTerms() {
    return Cache::remember('payment_terms', 3600, function () {
        return Teamleader::paymentTerms()->list();
    });
}
```

## Best Practices

### 1. Cache the Results

Payment terms rarely change, so cache them:

```php
$paymentTerms = Cache::remember('payment_terms', 86400, function () {
    return Teamleader::paymentTerms()->list();
});
```

### 2. Always Have a Fallback

```php
$defaultTerm = Teamleader::paymentTerms()->getDefault();

if (!$defaultTerm) {
    // Fallback to first available term
    $terms = Teamleader::paymentTerms()->list();
    $defaultTerm = $terms['data'][0] ?? null;
}
```

### 3. Use Helper Methods

```php
// Good: Clear and concise
$term = Teamleader::paymentTerms()->findByDays(30);

// Less ideal: Manual searching
$terms = Teamleader::paymentTerms()->list();
$term = null;
foreach ($terms['data'] as $t) {
    if ($t['days'] === 30 && $t['type'] === 'after_invoice_date') {
        $term = $t;
        break;
    }
}
```

### 4. Store Term Details in Invoice Data

```php
// Store full payment term details
$paymentTerm = Teamleader::paymentTerms()->findByDays(30);

$invoiceData = [
    'payment_term' => [
        'type' => $paymentTerm['type'],
        'days' => $paymentTerm['days']
    ],
    // Store for reference
    '_payment_term_description' => $paymentTerm['description']
];
```

## Related Resources

- [Invoices](invoices.md) - Invoice management
- [Payment Methods](payment-methods.md) - Payment method information
- [Subscriptions](subscriptions.md) - Subscription management
- [Companies](../crm/companies.md) - Customer management

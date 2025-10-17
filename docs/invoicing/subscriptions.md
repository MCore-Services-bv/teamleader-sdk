# Subscriptions

Manage recurring subscriptions in Teamleader Focus.

## Overview

The Subscriptions resource provides comprehensive management of recurring subscriptions in your Teamleader account. Subscriptions automatically generate invoices on a recurring basis based on configured billing cycles.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [activate()](#activate)
    - [deactivate()](#deactivate)
- [Helper Methods](#helper-methods)
- [Billing Cycles](#billing-cycles)
- [Invoice Generation](#invoice-generation)
- [Filtering](#filtering)
- [Sorting](#sorting)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`subscriptions`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ❌ Not Supported (use deactivate)

## Available Methods

### `list()`

Get a list of subscriptions with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Optional filters to apply
- `options` (array): Additional options for sorting and pagination

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all subscriptions
$subscriptions = Teamleader::subscriptions()->list();

// Get active subscriptions
$subscriptions = Teamleader::subscriptions()->list([
    'status' => ['active']
]);

// With sorting and pagination
$subscriptions = Teamleader::subscriptions()->list([], [
    'sort' => 'title',
    'sort_order' => 'asc',
    'page_size' => 50,
    'page_number' => 1
]);
```

### `info()`

Get detailed information about a specific subscription.

**Parameters:**
- `id` (string): The subscription UUID

**Example:**
```php
$subscription = Teamleader::subscriptions()->info('subscription-uuid');
```

### `create()`

Create a new subscription.

**Required fields:**
- `title` (string): Subscription title
- `invoicee` (object): Invoice recipient information
    - `customer` (object): Customer reference
        - `type` (string): 'contact' or 'company'
        - `id` (string): Customer UUID
- `billing_cycle` (object): Billing cycle configuration
    - `periodicity` (object): How often to bill
        - `unit` (string): 'day', 'week', 'month', or 'year'
        - `value` (integer): Number of units
    - `starts_on` (string): Start date (YYYY-MM-DD)
- `grouped_lines` (array): Subscription line items

**Optional fields:**
- `department_id` (string): Department UUID
- `billing_cycle.ends_on` (string): End date (YYYY-MM-DD)
- `invoice_generation` (object): Invoice generation settings
- `payment_term` (object): Payment terms
- `discounts` (array): Discounts to apply
- Many more configuration options

**Example:**
```php
$subscription = Teamleader::subscriptions()->create([
    'title' => 'Monthly SaaS Subscription',
    'department_id' => 'dept-uuid',
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'for_attention_of' => [
            'name' => 'John Doe',
            'contact_id' => 'contact-uuid'
        ]
    ],
    'billing_cycle' => [
        'periodicity' => [
            'unit' => 'month',
            'value' => 1
        ],
        'starts_on' => '2024-02-01'
    ],
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Services'
            ],
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Monthly subscription fee',
                    'unit_price' => [
                        'amount' => 99.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ],
    'payment_term' => [
        'type' => 'after_invoice_date',
        'days' => 14
    ],
    'invoice_generation' => [
        'action' => 'book',
        'offset_days' => 0
    ]
]);
```

### `update()`

Update an existing subscription.

**Parameters:**
- `id` (string): Subscription UUID
- `data` (array): Fields to update

**Example:**
```php
$result = Teamleader::subscriptions()->update('subscription-uuid', [
    'title' => 'Updated Subscription Title',
    'grouped_lines' => [
        [
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Updated subscription fee',
                    'unit_price' => [
                        'amount' => 129.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ]
]);
```

### `activate()`

Activate a deactivated subscription.

**Parameters:**
- `id` (string): Subscription UUID

**Example:**
```php
$result = Teamleader::subscriptions()->activate('subscription-uuid');
```

### `deactivate()`

Deactivate an active subscription (stops invoice generation).

**Parameters:**
- `id` (string): Subscription UUID

**Example:**
```php
$result = Teamleader::subscriptions()->deactivate('subscription-uuid');
```

## Helper Methods

The Subscriptions resource provides convenient helper methods:

### Status-based Methods

```php
// Get active subscriptions
$active = Teamleader::subscriptions()->active();

// Get deactivated subscriptions
$deactivated = Teamleader::subscriptions()->deactivated();
```

### Customer-based Methods

```php
// Get subscriptions for a company
$subscriptions = Teamleader::subscriptions()->forCompany('company-uuid');

// Get subscriptions for a contact
$subscriptions = Teamleader::subscriptions()->forContact('contact-uuid');

// Get subscriptions for any customer type
$subscriptions = Teamleader::subscriptions()->forCustomer('company', 'company-uuid');
```

### Department Method

```php
// Get subscriptions for a department
$subscriptions = Teamleader::subscriptions()->forDepartment('dept-uuid');
```

## Billing Cycles

Subscriptions support various billing cycles:

### Periodicity Units

- `day` - Daily billing
- `week` - Weekly billing
- `month` - Monthly billing
- `year` - Yearly billing

**Examples:**

```php
// Monthly billing
'billing_cycle' => [
    'periodicity' => [
        'unit' => 'month',
        'value' => 1
    ],
    'starts_on' => '2024-02-01'
]

// Quarterly billing (every 3 months)
'billing_cycle' => [
    'periodicity' => [
        'unit' => 'month',
        'value' => 3
    ],
    'starts_on' => '2024-01-01'
]

// Annual billing
'billing_cycle' => [
    'periodicity' => [
        'unit' => 'year',
        'value' => 1
    ],
    'starts_on' => '2024-01-01'
]

// With end date
'billing_cycle' => [
    'periodicity' => [
        'unit' => 'month',
        'value' => 1
    ],
    'starts_on' => '2024-01-01',
    'ends_on' => '2024-12-31'
]
```

## Invoice Generation

Configure how invoices are automatically generated:

### Generation Actions

- `draft` - Create invoices as drafts (requires manual booking)
- `book` - Automatically book invoices

### Offset Days

Control when invoices are generated relative to the billing date:

```php
'invoice_generation' => [
    'action' => 'book',
    'offset_days' => 0  // Generate on billing date
]

// Generate 7 days before billing date
'invoice_generation' => [
    'action' => 'draft',
    'offset_days' => -7
]

// Generate 5 days after billing date
'invoice_generation' => [
    'action' => 'book',
    'offset_days' => 5
]
```

## Filtering

Available filters for subscriptions:

- `ids` - Array of subscription UUIDs
- `status` - Array of statuses ('active', 'deactivated')
- `customer` - Filter by customer (object with type and id)
- `department_id` - Filter by department UUID
- `updated_since` - ISO 8601 datetime

**Example:**
```php
$subscriptions = Teamleader::subscriptions()->list([
    'status' => ['active'],
    'department_id' => 'dept-uuid',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

## Sorting

Available sort fields:

- `title` - Sort by subscription title
- `starts_on` - Sort by start date

**Example:**
```php
$subscriptions = Teamleader::subscriptions()->list([], [
    'sort' => 'starts_on',
    'sort_order' => 'desc'
]);
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
      "title": "Monthly SaaS Subscription",
      "status": "active",
      "invoicee": {
        "name": "Company Name",
        "customer": {
          "type": "company",
          "id": "uuid"
        }
      },
      "billing_cycle": {
        "periodicity": {
          "unit": "month",
          "value": 1
        },
        "starts_on": "2024-02-01",
        "ends_on": null
      },
      "total": {
        "tax_exclusive": {
          "amount": 99.00,
          "currency": "EUR"
        },
        "tax_inclusive": {
          "amount": 119.79,
          "currency": "EUR"
        }
      },
      "created_at": "2024-01-15T10:00:00+00:00",
      "updated_at": "2024-01-15T10:00:00+00:00"
    }
  ]
}
```

### Info Response

Contains complete subscription information including all fields from the list response plus detailed line items, payment terms, and invoice generation settings.

## Usage Examples

### Create Monthly Subscription

```php
$subscription = Teamleader::subscriptions()->create([
    'title' => 'Premium Plan - Monthly',
    'department_id' => 'dept-uuid',
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'billing_cycle' => [
        'periodicity' => [
            'unit' => 'month',
            'value' => 1
        ],
        'starts_on' => date('Y-m-d')
    ],
    'grouped_lines' => [
        [
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Premium Plan',
                    'unit_price' => [
                        'amount' => 99.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ],
    'invoice_generation' => [
        'action' => 'book',
        'offset_days' => 0
    ]
]);
```

### Create Annual Subscription with Discount

```php
$discount = Teamleader::commercialDiscounts()->findByName('Annual discount');

$subscription = Teamleader::subscriptions()->create([
    'title' => 'Premium Plan - Annual',
    'invoicee' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'billing_cycle' => [
        'periodicity' => [
            'unit' => 'year',
            'value' => 1
        ],
        'starts_on' => '2024-01-01'
    ],
    'grouped_lines' => [
        [
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Premium Plan - Annual',
                    'unit_price' => [
                        'amount' => 1188.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ],
    'discounts' => [
        [
            'type' => 'commercial_discount',
            'commercial_discount_id' => $discount['id']
        ]
    ],
    'invoice_generation' => [
        'action' => 'book',
        'offset_days' => -7  // Generate 7 days before
    ]
]);
```

### Update Subscription Pricing

```php
// Price increase
$subscription = Teamleader::subscriptions()->update('subscription-uuid', [
    'grouped_lines' => [
        [
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Premium Plan (New Pricing)',
                    'unit_price' => [
                        'amount' => 129.00,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'tax-rate-uuid'
                ]
            ]
        ]
    ]
]);
```

### Manage Subscription Lifecycle

```php
// Create new subscription
$subscription = Teamleader::subscriptions()->create($data);
$subscriptionId = $subscription['data']['id'];

// Later: Temporarily suspend
Teamleader::subscriptions()->deactivate($subscriptionId);

// Resume subscription
Teamleader::subscriptions()->activate($subscriptionId);

// Update end date to cancel at end of period
Teamleader::subscriptions()->update($subscriptionId, [
    'billing_cycle' => [
        'ends_on' => date('Y-m-d', strtotime('+1 month'))
    ]
]);
```

## Common Use Cases

### 1. SaaS Subscription Management

```php
// Create tiered subscriptions
$plans = [
    'basic' => ['price' => 29.00, 'description' => 'Basic Plan'],
    'premium' => ['price' => 99.00, 'description' => 'Premium Plan'],
    'enterprise' => ['price' => 299.00, 'description' => 'Enterprise Plan']
];

foreach ($plans as $tier => $details) {
    Teamleader::subscriptions()->create([
        'title' => $details['description'],
        'invoicee' => ['customer' => [...]],
        'billing_cycle' => [
            'periodicity' => ['unit' => 'month', 'value' => 1],
            'starts_on' => date('Y-m-d')
        ],
        'grouped_lines' => [[
            'line_items' => [[
                'quantity' => 1,
                'description' => $details['description'],
                'unit_price' => [
                    'amount' => $details['price'],
                    'tax' => 'excluding'
                ],
                'tax_rate_id' => 'tax-rate-uuid'
            ]]
        ]],
        'invoice_generation' => [
            'action' => 'book',
            'offset_days' => 0
        ]
    ]);
}
```

### 2. Monitor Subscription Revenue

```php
$activeSubscriptions = Teamleader::subscriptions()->active();

$monthlyRevenue = 0;
$annualRevenue = 0;

foreach ($activeSubscriptions['data'] as $subscription) {
    $amount = $subscription['total']['tax_exclusive']['amount'];
    $unit = $subscription['billing_cycle']['periodicity']['unit'];
    $value = $subscription['billing_cycle']['periodicity']['value'];
    
    // Convert to monthly revenue
    if ($unit === 'month') {
        $monthlyAmount = $amount / $value;
    } elseif ($unit === 'year') {
        $monthlyAmount = $amount / (12 * $value);
    } elseif ($unit === 'week') {
        $monthlyAmount = ($amount * 4.33) / $value;
    } elseif ($unit === 'day') {
        $monthlyAmount = ($amount * 30) / $value;
    }
    
    $monthlyRevenue += $monthlyAmount;
}

$annualRevenue = $monthlyRevenue * 12;

echo "Monthly Recurring Revenue: €" . number_format($monthlyRevenue, 2) . "\n";
echo "Annual Recurring Revenue: €" . number_format($annualRevenue, 2) . "\n";
```

### 3. Upcoming Renewals Report

```php
$subscriptions = Teamleader::subscriptions()->active();
$upcomingRenewals = [];

$today = new DateTime();
$nextMonth = (new DateTime())->modify('+30 days');

foreach ($subscriptions['data'] as $subscription) {
    $startsOn = new DateTime($subscription['billing_cycle']['starts_on']);
    $unit = $subscription['billing_cycle']['periodicity']['unit'];
    $value = $subscription['billing_cycle']['periodicity']['value'];
    
    // Calculate next billing date
    $nextBilling = clone $startsOn;
    while ($nextBilling < $today) {
        $nextBilling->modify("+{$value} {$unit}");
    }
    
    if ($nextBilling <= $nextMonth) {
        $upcomingRenewals[] = [
            'title' => $subscription['title'],
            'customer' => $subscription['invoicee']['name'],
            'next_billing' => $nextBilling->format('Y-m-d'),
            'amount' => $subscription['total']['tax_inclusive']['amount']
        ];
    }
}

// Sort by date
usort($upcomingRenewals, function($a, $b) {
    return strtotime($a['next_billing']) - strtotime($b['next_billing']);
});
```

## Best Practices

### 1. Set End Dates for Fixed-Term Subscriptions

```php
// 12-month contract
$subscription = Teamleader::subscriptions()->create([
    'title' => '12-Month Contract',
    'billing_cycle' => [
        'periodicity' => ['unit' => 'month', 'value' => 1],
        'starts_on' => '2024-01-01',
        'ends_on' => '2024-12-31'
    ],
    // ... other fields
]);
```

### 2. Use Appropriate Invoice Generation Settings

```php
// For manual review: draft invoices
'invoice_generation' => [
    'action' => 'draft',
    'offset_days' => -3  // Generate 3 days early for review
]

// For automated billing: book invoices
'invoice_generation' => [
    'action' => 'book',
    'offset_days' => 0  // Generate on billing date
]
```

### 3. Track Subscription Changes

```php
// Before updating, log the change
$oldSubscription = Teamleader::subscriptions()->info($subscriptionId);

$this->logSubscriptionChange([
    'subscription_id' => $subscriptionId,
    'old_amount' => $oldSubscription['data']['total']['tax_exclusive']['amount'],
    'new_amount' => $newAmount,
    'changed_at' => now()
]);

// Then update
Teamleader::subscriptions()->update($subscriptionId, $newData);
```

### 4. Handle Subscription Upgrades/Downgrades

```php
// Deactivate old subscription
Teamleader::subscriptions()->deactivate($oldSubscriptionId);

// Create new subscription with new terms
$newSubscription = Teamleader::subscriptions()->create([
    'title' => 'Upgraded Plan',
    'billing_cycle' => [
        'starts_on' => date('Y-m-d')  // Start immediately
    ],
    // ... new plan details
]);

// Store the relationship
$this->storeUpgradeRelation($oldSubscriptionId, $newSubscription['data']['id']);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $subscription = Teamleader::subscriptions()->create($data);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Invalid subscription data', $e->getErrors());
    } else {
        // Other error
        Log::error('Failed to create subscription', [
            'error' => $e->getMessage()
        ]);
    }
}
```

## Related Resources

- [Invoices](invoices.md) - Invoice management (generated by subscriptions)
- [Payment Terms](payment-terms.md) - Payment term configuration
- [Tax Rates](tax-rates.md) - Tax rate information
- [Commercial Discounts](commercial-discounts.md) - Discount management
- [Companies](../crm/companies.md) - Customer management
- [Contacts](../crm/contacts.md) - Contact management

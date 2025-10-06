# Subscriptions

Manage recurring subscriptions in Teamleader Focus. This resource provides operations for creating, updating, listing, and deactivating subscription contracts that automatically generate invoices based on the billing cycle.

## Endpoint

`subscriptions`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ⚠️ Deactivation only (no permanent deletion)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of subscriptions with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
// Get all subscriptions
$subscriptions = $teamleader->subscriptions()->list();

// Get active subscriptions for a specific customer
$subscriptions = $teamleader->subscriptions()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid-here'
    ],
    'status' => ['active']
]);

// With pagination and sorting
$subscriptions = $teamleader->subscriptions()->list(
    ['status' => ['active']],
    [
        'page_size' => 50,
        'page_number' => 1,
        'sort' => [['field' => 'title', 'order' => 'asc']]
    ]
);
```

### `info()`

Get detailed information about a specific subscription.

**Parameters:**
- `id` (string): Subscription UUID
- `includes` (array|string): Not used for subscriptions

**Example:**
```php
$subscription = $teamleader->subscriptions()->info('subscription-uuid-here');
```

### `create()`

Create a new subscription.

**Parameters:**
- `data` (array): Array of subscription data

**Required Fields:**
- `invoicee`: Customer and billing information
- `starts_on`: Start date (YYYY-MM-DD)
- `billing_cycle`: Billing cycle configuration
- `title`: Subscription title
- `grouped_lines`: Array of line items with sections
- `payment_term`: Payment terms
- `invoice_generation`: Invoice generation settings

**Example:**
```php
$subscription = $teamleader->subscriptions()->create([
    'invoicee' => [
        'customer' => [
            'type' => 'contact',
            'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
        ],
        'for_attention_of' => [
            'name' => 'Finance Dept.'
        ]
    ],
    'department_id' => '6a6343fc-fdd8-4bc0-aa69-3a004c710e87',
    'starts_on' => '2024-04-26',
    'billing_cycle' => [
        'periodicity' => [
            'unit' => 'month',
            'period' => 1
        ],
        'days_in_advance' => 7
    ],
    'title' => 'Monthly Maintenance Subscription',
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Services'
            ],
            'line_items' => [
                [
                    'quantity' => 1,
                    'description' => 'Monthly maintenance',
                    'unit_price' => [
                        'amount' => 99.99,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'c0c03f1e-77e3-402c-a713-30ea1c585823'
                ]
            ]
        ]
    ],
    'payment_term' => [
        'type' => 'cash'
    ],
    'invoice_generation' => [
        'action' => 'draft'
    ]
]);
```

### `update()`

Update an existing subscription.

**Parameters:**
- `id` (string): Subscription UUID
- `data` (array): Array of data to update

**Note:** `starts_on` and `billing_cycle` can only be updated if no invoices have been created yet.

**Example:**
```php
$subscription = $teamleader->subscriptions()->update('subscription-uuid', [
    'title' => 'Updated Subscription Title',
    'ends_on' => '2025-12-31',
    'note' => 'Additional notes about this subscription'
]);
```

### `deactivate()`

Deactivate a subscription (stops future invoice generation).

**Parameters:**
- `id` (string): Subscription UUID

**Example:**
```php
$result = $teamleader->subscriptions()->deactivate('subscription-uuid');
```

### `active()`

Get all active subscriptions.

**Parameters:**
- `additionalFilters` (array): Additional filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->active();
```

### `deactivated()`

Get all deactivated subscriptions.

**Parameters:**
- `additionalFilters` (array): Additional filters to apply
- `options` (array): Pagination and sorting options

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->deactivated();
```

### `forCustomer()`

Get all subscriptions for a specific customer.

**Parameters:**
- `type` (string): Customer type ('contact' or 'company')
- `id` (string): Customer UUID
- `options` (array): Pagination and sorting options

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->forCustomer('company', 'company-uuid');
```

### `forDepartment()`

Get all subscriptions for a specific department.

**Parameters:**
- `departmentId` (string): Department UUID
- `options` (array): Pagination and sorting options

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->forDepartment('department-uuid');
```

### `forDeal()`

Get subscriptions created from a specific deal.

**Parameters:**
- `dealId` (string): Deal UUID
- `options` (array): Pagination and sorting options

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->forDeal('deal-uuid');
```

### `forInvoice()`

Get subscriptions that generated a specific invoice.

**Parameters:**
- `invoiceId` (string): Invoice UUID
- `options` (array): Pagination and sorting options

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->forInvoice('invoice-uuid');
```

### `byIds()`

Get specific subscriptions by their UUIDs.

**Parameters:**
- `ids` (array): Array of subscription UUIDs
- `options` (array): Pagination and sorting options

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->byIds(['uuid1', 'uuid2']);
```

## Filtering Options

The `list()` method accepts various filters:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of subscription UUIDs |
| `invoice_id` | string | Find subscriptions that generated this invoice |
| `deal_id` | string | Filter subscriptions created from this deal |
| `department_id` | string | Filter by department UUID |
| `customer` | object | Customer filter with `type` and `id` |
| `status` | array | Filter by status: `['active']` or `['deactivated']` |

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->list([
    'department_id' => 'department-uuid',
    'status' => ['active'],
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);
```

## Sorting Options

Available sort fields:
- `title`
- `created_at`
- `status`

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->list([], [
    'sort' => [
        ['field' => 'title', 'order' => 'asc']
    ]
]);
```

## Pagination

**Example:**
```php
$subscriptions = $teamleader->subscriptions()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

## Response Structure

### Create/Update Response
```php
[
    'data' => [
        'id' => 'subscription-uuid',
        'type' => 'subscription'
    ]
]
```

### Info/List Response
```php
[
    'data' => [
        'id' => 'subscription-uuid',
        'title' => 'Subscription Title',
        'note' => 'Additional notes (Markdown)',
        'status' => 'active',
        'department' => [
            'id' => 'department-uuid',
            'type' => 'department'
        ],
        'invoicee' => [
            'customer' => [
                'type' => 'contact',
                'id' => 'customer-uuid'
            ],
            'for_attention_of' => [
                'name' => 'Finance Dept.',
                'contact' => [
                    'id' => 'contact-uuid',
                    'type' => 'contact'
                ]
            ]
        ],
        'starts_on' => '2024-04-26',
        'ends_on' => null,
        'next_renewal_date' => '2024-05-26',
        'billing_cycle' => [
            'periodicity' => [
                'unit' => 'month',
                'period' => 1
            ],
            'days_in_advance' => 7
        ],
        'total' => [
            'tax_exclusive' => [
                'amount' => 99.99,
                'currency' => 'EUR'
            ],
            'tax_inclusive' => [
                'amount' => 120.99,
                'currency' => 'EUR'
            ],
            'taxes' => [
                [
                    'rate' => 0.21,
                    'taxable' => [
                        'amount' => 99.99,
                        'currency' => 'EUR'
                    ],
                    'tax' => [
                        'amount' => 21.00,
                        'currency' => 'EUR'
                    ]
                ]
            ]
        ],
        'payment_term' => [
            'type' => 'cash',
            'days' => 0
        ],
        'grouped_lines' => [
            [
                'section' => [
                    'title' => 'Services'
                ],
                'line_items' => [
                    [
                        'product' => [
                            'id' => 'product-uuid',
                            'type' => 'product'
                        ],
                        'quantity' => 1,
                        'description' => 'Monthly maintenance',
                        'extended_description' => null,
                        'unit' => null,
                        'unit_price' => [
                            'amount' => 99.99,
                            'tax' => 'excluding'
                        ],
                        'tax' => [
                            'id' => 'tax-uuid',
                            'type' => 'taxRate'
                        ],
                        'discount' => null,
                        'total' => [
                            'tax_exclusive' => [
                                'amount' => 99.99,
                                'currency' => 'EUR'
                            ],
                            'tax_inclusive' => [
                                'amount' => 120.99,
                                'currency' => 'EUR'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'invoice_generation' => [
            'action' => 'draft',
            'payment_method' => 'direct_debit'
        ],
        'custom_fields' => [],
        'document_template' => [
            'id' => 'template-uuid',
            'type' => 'documentTemplate'
        ],
        'currency' => 'EUR',
        'web_url' => 'https://focus.teamleader.eu/subscription_detail.php?id=...'
    ]
]
```

## Billing Cycle Configuration

The billing cycle determines when invoices are generated:

### Periodicity
- **unit**: `'week'`, `'month'`, or `'year'`
- **period**: Number of units (e.g., 1 for monthly, 2 for bi-weekly)

### Days in Advance
How many days before the renewal date to generate the invoice:
- `0`: On the renewal date
- `7`: 7 days before
- `14`: 14 days before
- `21`: 21 days before
- `28`: 28 days before

**Example:**
```php
'billing_cycle' => [
    'periodicity' => [
        'unit' => 'month',
        'period' => 1  // Every month
    ],
    'days_in_advance' => 7  // Generate invoice 7 days before renewal
]
```

## Invoice Generation Settings

Control how invoices are generated automatically:

### Actions
- `'draft'`: Create as draft (manual review required)
- `'book'`: Automatically book the invoice
- `'book_and_send'`: Book and send the invoice automatically

### Payment Methods
- `'direct_debit'`: Enable direct debit payment

**Example:**
```php
'invoice_generation' => [
    'action' => 'book_and_send',
    'payment_method' => 'direct_debit'
]
```

## Payment Terms

Configure payment terms for generated invoices:

- **type**: `'cash'`, `'end_of_month'`, `'after_invoice_date'`
- **days**: Number of days (not required for 'cash')

**Example:**
```php
'payment_term' => [
    'type' => 'after_invoice_date',
    'days' => 30  // Net 30
]
```

## Line Items Structure

Subscriptions use grouped lines with sections:

```php
'grouped_lines' => [
    [
        'section' => [
            'title' => 'Section Title'  // Optional
        ],
        'line_items' => [
            [
                'quantity' => 1,
                'description' => 'Product or service description',
                'extended_description' => 'Additional details (Markdown)',  // Optional
                'unit_of_measure_id' => 'unit-uuid',  // Optional
                'unit_price' => [
                    'amount' => 99.99,
                    'tax' => 'excluding'
                ],
                'tax_rate_id' => 'tax-rate-uuid',
                'discount' => [  // Optional
                    'value' => 10,
                    'type' => 'percentage'
                ],
                'product_id' => 'product-uuid',  // Optional
                'product_category_id' => 'category-uuid',  // Optional
                'withholding_tax_rate_id' => 'withholding-tax-uuid'  // Optional
            ]
        ]
    ]
]
```

## Custom Fields

You can add custom fields to subscriptions:

```php
'custom_fields' => [
    [
        'id' => 'custom-field-definition-uuid',
        'value' => 'Custom value'
    ]
]
```

## Important Notes

1. **Update Restrictions**: `starts_on` and `billing_cycle` can only be updated if no invoices have been generated yet.

2. **Deactivation vs Deletion**: Subscriptions cannot be permanently deleted, only deactivated. Deactivation stops future invoice generation.

3. **Automatic Invoice Generation**: Once active, subscriptions automatically generate invoices based on the billing cycle configuration.

4. **Customer Requirements**: You must provide either a contact or company as the customer with valid type and ID.

5. **Date Format**: All dates must be in ISO 8601 format (YYYY-MM-DD).

6. **Currency**: Currency is inherited from the department and cannot be changed per subscription.

## Common Use Cases

### Create Monthly Subscription
```php
$subscription = $teamleader->subscriptions()->create([
    'invoicee' => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid']
    ],
    'department_id' => 'department-uuid',
    'starts_on' => '2024-01-01',
    'billing_cycle' => [
        'periodicity' => ['unit' => 'month', 'period' => 1],
        'days_in_advance' => 7
    ],
    'title' => 'Monthly Service Subscription',
    'grouped_lines' => [...],
    'payment_term' => ['type' => 'cash'],
    'invoice_generation' => ['action' => 'draft']
]);
```

### Update Subscription End Date
```php
$teamleader->subscriptions()->update('subscription-uuid', [
    'ends_on' => '2024-12-31'
]);
```

### Find Customer's Active Subscriptions
```php
$subscriptions = $teamleader->subscriptions()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'status' => ['active']
]);
```

### Deactivate Subscription
```php
$teamleader->subscriptions()->deactivate('subscription-uuid');
```

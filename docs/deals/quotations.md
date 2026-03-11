# Quotations

Manage quotations in Teamleader Focus.

## Overview

The Quotations resource provides full CRUD operations for managing quotations (also known as proposals or quotes) in Teamleader. Quotations are formal offers sent to customers detailing products, services, prices, and terms. They can be accepted to convert into orders or invoices.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [accept()](#accept)
    - [send()](#send)
    - [download()](#download)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sideloading](#sideloading)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`quotations`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ✅ Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all quotations with optional filtering and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (`page.size`, `page.number`)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all quotations
$quotations = Teamleader::quotations()->list();

// Get specific quotations by ID
$quotations = Teamleader::quotations()->list([
    'ids' => ['quotation-uuid-1', 'quotation-uuid-2']
]);

// Filter by status
$quotations = Teamleader::quotations()->list([
    'status' => ['open', 'accepted']
]);

// With pagination
$quotations = Teamleader::quotations()->list([], [
    'page' => ['size' => 50, 'number' => 1]
]);
```

### `info()`

Get detailed information about a specific quotation.

**Parameters:**
- `id` (string): Quotation UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Get quotation information
$quotation = Teamleader::quotations()->info('quotation-uuid');

// With expiry information (if enabled)
$quotation = Teamleader::quotations()->info('quotation-uuid', 'expiry');

// Using fluent interface
$quotation = Teamleader::quotations()
    ->with('expiry')
    ->info('quotation-uuid');
```

### `create()`

Create a new quotation.

**Parameters:**
- `data` (array): Quotation data
    - `deal_id` (string, required): Deal UUID to attach the quotation to
    - `grouped_lines` (array): Line item groups — required if `text` is not provided
    - `text` (string): Rich text (Markdown) content — required if `grouped_lines` is not provided
    - `currency` (array): Currency object, e.g. `['code' => 'EUR']`

**Example:**
```php
$quotation = Teamleader::quotations()->create([
    'deal_id' => 'deal-uuid',
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Products'
            ],
            'line_items' => [
                [
                    'quantity' => 2,
                    'description' => 'Premium Package',
                    'unit_price' => [
                        'amount' => 500,
                        'currency' => 'EUR',
                        'tax' => 'excluding'
                    ]
                ]
            ]
        ]
    ]
]);
```

### `update()`

Update an existing quotation.

**Parameters:**
- `id` (string): Quotation UUID
- `data` (array): Updated quotation data

**Example:**
```php
Teamleader::quotations()->update('quotation-uuid', [
    'grouped_lines' => [
        [
            'section' => ['title' => 'Updated Products'],
            'line_items' => [
                [
                    'quantity' => 3,
                    'description' => 'Premium Package',
                    'unit_price' => [
                        'amount' => 450,
                        'currency' => 'EUR',
                        'tax' => 'excluding'
                    ]
                ]
            ]
        ]
    ]
]);
```

### `delete()`

Delete a quotation.

**Parameters:**
- `id` (string): Quotation UUID

**Example:**
```php
Teamleader::quotations()->delete('quotation-uuid');
```

### `accept()`

Mark a quotation as accepted.

**Parameters:**
- `id` (string): Quotation UUID

**Example:**
```php
Teamleader::quotations()->accept('quotation-uuid');
```

**Note:** Accepting a quotation may trigger automated processes in Teamleader, such as creating an order or invoice.

### `send()`

Send one or more quotations via email.

**Parameters:**
- `data` (array): Send parameters including quotations, sender, recipients, subject, content, and language

**Example:**
```php
Teamleader::quotations()->send([
    'quotations' => ['quotation-uuid-1', 'quotation-uuid-2'],
    'from' => [
        'sender' => [
            'type' => 'user',
            'id' => 'user-uuid'
        ]
    ],
    'recipients' => [
        'to' => [
            ['type' => 'contact', 'id' => 'contact-uuid']
        ]
    ],
    'subject' => 'Your Quotation',
    'content' => 'Please find attached your quotation.',
    'language' => 'en'
]);
```

### `download()`

Download a quotation in a specific format (PDF).

**Parameters:**
- `id` (string): Quotation UUID
- `format` (string): Download format (default: `pdf`)

**Example:**
```php
$download = Teamleader::quotations()->download('quotation-uuid', 'pdf');

// Returns temporary download URL
$url     = $download['data']['location'];
$expires = $download['data']['expires'];

// Download the file
file_put_contents('quotation.pdf', file_get_contents($url));
```

## Helper Methods

### `byIds()`

Get specific quotations by their UUIDs.

```php
$quotations = Teamleader::quotations()->byIds([
    'quotation-uuid-1',
    'quotation-uuid-2'
]);
```

### `byStatus()`

Get quotations by status.

**Available statuses:** `open`, `accepted`, `expired`, `rejected`, `closed`

```php
// Get open quotations
$quotations = Teamleader::quotations()->byStatus('open');

// Get accepted quotations
$quotations = Teamleader::quotations()->byStatus('accepted');

// Multiple statuses
$quotations = Teamleader::quotations()->byStatus(['open', 'accepted']);
```

## Filters

Available filters for the `list()` method:

| Filter | Type | Description |
|---|---|---|
| `ids` | string[] | Filter by specific quotation UUIDs |
| `status` | string[] | Filter by status: `open`, `accepted`, `expired`, `rejected`, `closed` |

**Examples:**
```php
// By IDs
$quotations = Teamleader::quotations()->list([
    'ids' => ['quotation-uuid-1', 'quotation-uuid-2']
]);

// By status
$quotations = Teamleader::quotations()->list([
    'status' => ['open', 'accepted']
]);

// Combined
$quotations = Teamleader::quotations()->list([
    'ids'    => ['quotation-uuid-1'],
    'status' => ['open']
]);
```

## Sideloading

Load related data in a single request:

### Available Includes

- `expiry` — Include expiry information. Only returned if the authenticated user has access to the quotation expiry feature.

### Usage

```php
// With expiry information
$quotation = Teamleader::quotations()
    ->with('expiry')
    ->info('quotation-uuid');

// Access expiry fields
$expiresAfter       = $quotation['data']['expiry']['expires_after'];       // YYYY-MM-DD
$actionAfterExpiry  = $quotation['data']['expiry']['action_after_expiry']; // 'lock' or 'none'
```

## Response Structure

### Quotation Object (`info`)

```json
{
  "data": {
    "id": "quotation-uuid",
    "deal": {
      "type": "deal",
      "id": "deal-uuid"
    },
    "grouped_lines": [
      {
        "section": {
          "title": "Products"
        },
        "line_items": [
          {
            "product": null,
            "quantity": 2,
            "description": "Premium Package",
            "extended_description": "Some more information in **Markdown**",
            "unit": null,
            "unit_price": {
              "amount": 500.00,
              "currency": "EUR",
              "tax": "excluding"
            },
            "tax": {
              "type": "taxRate",
              "id": "tax-rate-uuid"
            },
            "discount": {
              "value": 10,
              "type": "percentage"
            },
            "total": {
              "tax_exclusive": {
                "amount": 900.00,
                "currency": "EUR"
              },
              "tax_exclusive_before_discount": {
                "amount": 1000.00,
                "currency": "EUR"
              },
              "tax_inclusive": {
                "amount": 1089.00,
                "currency": "EUR"
              },
              "tax_inclusive_before_discount": {
                "amount": 1210.00,
                "currency": "EUR"
              }
            },
            "purchase_price": {
              "amount": 350.00,
              "currency": "EUR"
            },
            "periodicity": null
          }
        ]
      }
    ],
    "currency": "EUR",
    "currency_exchange_rate": {
      "from": "USD",
      "to": "EUR",
      "rate": 1.1234
    },
    "text": "Thank you for your interest. Please find our offer below.",
    "total": {
      "tax_exclusive": {
        "amount": 900.00,
        "currency": "EUR"
      },
      "tax_inclusive": {
        "amount": 1089.00,
        "currency": "EUR"
      },
      "taxes": [
        {
          "rate": 0.21,
          "taxable": {
            "amount": 900.00,
            "currency": "EUR"
          },
          "tax": {
            "amount": 189.00,
            "currency": "EUR"
          }
        }
      ],
      "purchase_price": {
        "amount": 350.00,
        "currency": "EUR"
      }
    },
    "discounts": [
      {
        "type": "percentage",
        "value": 15.5,
        "description": "Winter promotion"
      }
    ],
    "created_at": "2024-01-15T10:30:00+00:00",
    "updated_at": "2024-01-20T14:45:00+00:00",
    "status": "open",
    "name": "Quotation #Q2024-001",
    "document_template": {
      "type": "documentTemplate",
      "id": "template-uuid"
    },
    "expiry": {
      "expires_after": "2024-02-15",
      "action_after_expiry": "lock"
    }
  }
}
```

### Key Response Fields

| Field | Nullable | Description |
|---|---|---|
| `deal` | No | Deal this quotation belongs to |
| `grouped_lines` | No | Array of section + line item groups |
| `currency` | No | Quotation currency code |
| `currency_exchange_rate` | No | Exchange rate object (when currencies differ) |
| `text` | No | Rich text content in Markdown |
| `total.taxes[].taxable` | No | Taxable amount object with `amount` + `currency` |
| `total.taxes[].tax` | No | Tax amount object with `amount` + `currency` |
| `total.purchase_price` | Yes | Total purchase price across all line items |
| `discounts` | No | Document-level discount array |
| `created_at` | Yes | Creation timestamp |
| `updated_at` | Yes | Last update timestamp |
| `document_template` | Yes | Applied document template |
| `expiry` | Yes | Only returned when `includes=expiry` is requested and user has access |
| `line_items[].product` | Yes | Product reference |
| `line_items[].unit` | Yes | Unit of measure |
| `line_items[].discount` | Yes | Line-level discount |
| `line_items[].purchase_price` | Yes | Per-line purchase price |
| `line_items[].periodicity` | Yes | Recurring billing period (`unit` + `period`) |
| `line_items[].total.tax_exclusive_before_discount` | No | Pre-discount total ex VAT |
| `line_items[].total.tax_inclusive_before_discount` | No | Pre-discount total inc VAT |

### Periodicity (Recurring Line Items)

When a line item has a recurring billing period, `periodicity` contains:

```json
{
  "periodicity": {
    "unit": "month",
    "period": 1
  }
}
```

- `unit`: `week`, `month`, or `year`
- `period`: multiplier (e.g. `2` for bi-weekly/bi-monthly)

## Usage Examples

### Create a Complete Quotation

```php
$quotation = Teamleader::quotations()->create([
    'deal_id' => 'deal-uuid',
    'grouped_lines' => [
        [
            'section' => ['title' => 'Software Licenses'],
            'line_items' => [
                [
                    'quantity' => 10,
                    'description' => 'Professional License',
                    'unit_price' => [
                        'amount' => 99,
                        'currency' => 'EUR',
                        'tax' => 'excluding'
                    ]
                ],
                [
                    'quantity' => 5,
                    'description' => 'Enterprise License',
                    'unit_price' => [
                        'amount' => 199,
                        'currency' => 'EUR',
                        'tax' => 'excluding'
                    ]
                ]
            ]
        ],
        [
            'section' => ['title' => 'Services'],
            'line_items' => [
                [
                    'quantity' => 8,
                    'description' => 'Implementation Hours',
                    'unit_price' => [
                        'amount' => 125,
                        'currency' => 'EUR',
                        'tax' => 'excluding'
                    ]
                ]
            ]
        ]
    ]
]);
```

### Send Quotation to Customer

```php
$quotation = Teamleader::quotations()->create([...]);

Teamleader::quotations()->send([
    'quotations' => [$quotation['data']['id']],
    'from' => [
        'sender' => [
            'type' => 'user',
            'id' => auth()->user()->teamleader_id
        ]
    ],
    'recipients' => [
        'to' => [
            ['type' => 'contact', 'id' => 'contact-uuid']
        ],
        'cc' => [
            ['type' => 'user', 'id' => 'manager-uuid']
        ]
    ],
    'subject' => 'Quotation for Your Project',
    'content' => 'Dear Customer, please review the attached quotation.',
    'language' => 'en'
]);
```

### Download and Store Quotation

```php
function downloadAndStoreQuotation($quotationId)
{
    $download = Teamleader::quotations()->download($quotationId, 'pdf');

    $pdfContent = file_get_contents($download['data']['location']);

    $filename = "quotation_{$quotationId}_" . date('Y-m-d') . ".pdf";
    Storage::put("quotations/{$filename}", $pdfContent);

    return $filename;
}
```

### Track Quotation Status

```php
$quotation = Teamleader::quotations()->info($quotationId);

$statusMessages = [
    'open'     => 'Waiting for customer response',
    'accepted' => 'Quotation has been accepted!',
    'expired'  => 'Quotation has expired',
    'rejected' => 'Quotation was rejected',
    'closed'   => 'Quotation is closed',
];

$status = $quotation['data']['status'];
echo $statusMessages[$status] ?? 'Unknown status';
```

### Check Expiry Information

```php
$quotation = Teamleader::quotations()
    ->with('expiry')
    ->info('quotation-uuid');

if (isset($quotation['data']['expiry'])) {
    $expiresAfter = $quotation['data']['expiry']['expires_after'];
    $action       = $quotation['data']['expiry']['action_after_expiry']; // 'lock' or 'none'

    if ($expiresAfter < date('Y-m-d')) {
        echo "Quotation expired on {$expiresAfter} — action: {$action}";
    }
}
```

### Calculate Margin from Purchase Prices

```php
$quotation = Teamleader::quotations()->info('quotation-uuid');

$revenue      = $quotation['data']['total']['tax_exclusive']['amount'];
$purchasePrice = $quotation['data']['total']['purchase_price']['amount'] ?? 0;

$margin = $revenue - $purchasePrice;
$marginPercent = $revenue > 0 ? ($margin / $revenue) * 100 : 0;

echo "Revenue: €{$revenue} | Cost: €{$purchasePrice} | Margin: " . round($marginPercent, 1) . "%";
```

## Common Use Cases

### 1. Automated Quotation Generation

```php
function generateQuotationFromDeal($dealId)
{
    $deal = Teamleader::deals()->info($dealId);

    $quotation = Teamleader::quotations()->create([
        'deal_id' => $dealId,
        'grouped_lines' => [
            [
                'section' => ['title' => 'Proposed Solution'],
                'line_items' => [
                    // ... populate from deal details
                ]
            ]
        ]
    ]);

    $customer = $deal['data']['lead']['customer'];

    Teamleader::quotations()->send([
        'quotations' => [$quotation['data']['id']],
        'from'       => ['sender' => ['type' => 'user', 'id' => 'user-uuid']],
        'recipients' => [
            'to' => [
                ['type' => $customer['type'], 'id' => $customer['id']]
            ]
        ],
        'subject'  => "Quotation for {$deal['data']['title']}",
        'content'  => 'Please review the attached quotation.',
        'language' => 'en'
    ]);

    return $quotation;
}
```

### 2. Quotation Follow-up System

```php
function checkPendingQuotations($days = 7)
{
    $openQuotations = Teamleader::quotations()->byStatus('open');
    $pending = [];

    $cutoff = date('Y-m-d', strtotime("-{$days} days"));

    foreach ($openQuotations['data'] as $quotation) {
        if ($quotation['created_at'] < $cutoff) {
            $pending[] = [
                'quotation_id'   => $quotation['id'],
                'quotation_name' => $quotation['name'],
                'days_pending'   => round((time() - strtotime($quotation['created_at'])) / 86400),
                'deal_id'        => $quotation['deal']['id'],
            ];
        }
    }

    return $pending;
}
```

### 3. Quotation Conversion Rate

```php
function calculateQuotationConversionRate()
{
    $all      = Teamleader::quotations()->list();
    $accepted = Teamleader::quotations()->byStatus('accepted');

    $total         = count($all['data']);
    $acceptedCount = count($accepted['data']);

    return [
        'total'           => $total,
        'accepted'        => $acceptedCount,
        'conversion_rate' => $total > 0 ? round(($acceptedCount / $total) * 100, 1) : 0,
    ];
}
```

### 4. Bulk Quotation Send

```php
function sendBulkQuotations(array $quotationIds, $recipients, $subject, $content)
{
    $validIds = [];

    foreach ($quotationIds as $id) {
        $q = Teamleader::quotations()->info($id);
        if ($q['data']['status'] === 'open') {
            $validIds[] = $id;
        }
    }

    if (empty($validIds)) {
        throw new \Exception('No valid open quotations found');
    }

    return Teamleader::quotations()->send([
        'quotations' => $validIds,
        'from'       => ['sender' => ['type' => 'user', 'id' => 'user-uuid']],
        'recipients' => $recipients,
        'subject'    => $subject,
        'content'    => $content,
        'language'   => 'en'
    ]);
}
```

## Best Practices

1. **Include `currency` in `unit_price`**: The API requires both `amount` and `currency` on each line item's `unit_price`.
```php
// Correct
'unit_price' => ['amount' => 500, 'currency' => 'EUR', 'tax' => 'excluding']

// Wrong — missing currency
'unit_price' => ['amount' => 500, 'tax' => 'excluding']
```

2. **Access totals as objects**: All monetary amounts are `{amount, currency}` objects, not plain scalars.
```php
// Correct
$total = $quotation['data']['total']['tax_exclusive']['amount'];

// Wrong
$total = $quotation['data']['total']['tax_exclusive'];
```

3. **Use before-discount totals for margin analysis**: When discounts are applied, use `tax_exclusive_before_discount` to understand the original value.
```php
$beforeDiscount = $lineItem['total']['tax_exclusive_before_discount']['amount'];
$afterDiscount  = $lineItem['total']['tax_exclusive']['amount'];
$saving         = $beforeDiscount - $afterDiscount;
```

4. **Check `taxes[].taxable` and `taxes[].tax` as objects**: These are amount objects, not scalars.
```php
foreach ($quotation['data']['total']['taxes'] as $tax) {
    $rate    = $tax['rate'];                    // e.g. 0.21
    $taxable = $tax['taxable']['amount'];       // taxable base
    $taxAmt  = $tax['tax']['amount'];           // VAT amount
}
```

5. **Handle `periodicity` for recurring items**: Check before accessing to avoid null errors.
```php
foreach ($lineItems as $item) {
    if ($item['periodicity'] !== null) {
        $unit   = $item['periodicity']['unit'];   // week / month / year
        $period = $item['periodicity']['period']; // 1, 2, ...
    }
}
```

6. **Validate status before sending**: A quotation can only be sent when it is `open`.
```php
$quotation = Teamleader::quotations()->info($quotationId);

if ($quotation['data']['status'] !== 'open') {
    throw new \Exception("Cannot send quotation with status: {$quotation['data']['status']}");
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

try {
    $quotation = Teamleader::quotations()->create([
        'deal_id' => 'deal-uuid',
        'grouped_lines' => [...]
    ]);

    Teamleader::quotations()->send([...]);

} catch (\InvalidArgumentException $e) {
    // Missing required fields or invalid values
    Log::error('Validation error: ' . $e->getMessage());

} catch (\Exception $e) {
    // API error
    Log::error('Quotation error: ' . $e->getMessage());
}
```

## Quotation Statuses

- **open** — Quotation is awaiting response
- **accepted** — Customer has accepted the quotation
- **expired** — Quotation has passed its expiry date (if set)
- **rejected** — Customer has rejected the quotation
- **closed** — Quotation is closed (manually or automatically)

## Related Resources

- [Deals](deals.md) — Quotations are linked to deals
- [Orders](orders.md) — Accepted quotations may become orders
- [Invoices](../invoicing/invoices.md) — Quotations can be converted to invoices

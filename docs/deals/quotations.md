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
- `options` (array): Additional options (page_size, page_number)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all quotations
$quotations = Teamleader::quotations()->list();

// Get specific quotations by ID
$quotations = Teamleader::quotations()->list([
    'ids' => ['quotation-uuid-1', 'quotation-uuid-2']
]);

// With pagination
$quotations = Teamleader::quotations()->list([], [
    'page_size' => 50,
    'page_number' => 1
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
$quotation = Teamleader::quotations()->update('quotation-uuid', [
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Updated Products'
            ],
            'line_items' => [
                [
                    'quantity' => 3,
                    'description' => 'Premium Package',
                    'unit_price' => [
                        'amount' => 450,
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
- `format` (string): Download format (default: 'pdf')

**Example:**
```php
$download = Teamleader::quotations()->download('quotation-uuid', 'pdf');

// Returns temporary download URL
$url = $download['data']['location'];
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

### Available Filters

#### `ids`
Filter by specific quotation UUIDs.

```php
$quotations = Teamleader::quotations()->list([
    'ids' => ['quotation-uuid-1', 'quotation-uuid-2']
]);
```

## Sideloading

Load related data in a single request:

### Available Includes

- `expiry` - Include expiry information (only if user has access to quotation expiry feature)

### Usage

```php
// With expiry information
$quotation = Teamleader::quotations()
    ->with('expiry')
    ->info('quotation-uuid');
```

## Response Structure

### Quotation Object

```php
[
    'id' => 'quotation-uuid',
    'deal' => [
        'id' => 'deal-uuid'
    ],
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
                        'amount' => 500.00,
                        'tax' => 'excluding'
                    ],
                    'total' => [
                        'tax_exclusive' => 1000.00,
                        'tax_inclusive' => 1210.00,
                        'taxes' => [
                            [
                                'rate' => 0.21,
                                'amount' => 210.00
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'currency' => [
        'code' => 'EUR'
    ],
    'total' => [
        'tax_exclusive' => 1000.00,
        'tax_inclusive' => 1210.00,
        'taxes' => [
            [
                'rate' => 0.21,
                'amount' => 210.00
            ]
        ]
    ],
    'status' => 'open',
    'name' => 'Quotation #Q2024-001',
    'created_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-20T14:45:00+00:00'
]
```

## Usage Examples

### Create a Complete Quotation

```php
$quotation = Teamleader::quotations()->create([
    'deal_id' => 'deal-uuid',
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Software Licenses'
            ],
            'line_items' => [
                [
                    'quantity' => 10,
                    'description' => 'Professional License',
                    'unit_price' => [
                        'amount' => 99,
                        'tax' => 'excluding'
                    ]
                ],
                [
                    'quantity' => 5,
                    'description' => 'Enterprise License',
                    'unit_price' => [
                        'amount' => 199,
                        'tax' => 'excluding'
                    ]
                ]
            ]
        ],
        [
            'section' => [
                'title' => 'Services'
            ],
            'line_items' => [
                [
                    'quantity' => 8,
                    'description' => 'Implementation Hours',
                    'unit_price' => [
                        'amount' => 125,
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
// Create quotation
$quotation = Teamleader::quotations()->create([...]);

// Send via email
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
    'content' => 'Dear Customer,\n\nPlease find attached our quotation for your project.',
    'language' => 'en'
]);
```

### Download and Store Quotation

```php
function downloadAndStoreQuotation($quotationId)
{
    // Get download URL
    $download = Teamleader::quotations()->download($quotationId, 'pdf');
    
    // Download file
    $pdfContent = file_get_contents($download['data']['location']);
    
    // Store in your system
    $filename = "quotation_{$quotationId}_" . date('Y-m-d') . ".pdf";
    Storage::put("quotations/{$filename}", $pdfContent);
    
    return $filename;
}
```

### Track Quotation Status

```php
function checkQuotationStatus($quotationId)
{
    $quotation = Teamleader::quotations()->info($quotationId);
    
    $statusMessages = [
        'open' => 'Quotation is waiting for customer response',
        'accepted' => 'Quotation has been accepted!',
        'expired' => 'Quotation has expired',
        'rejected' => 'Quotation was rejected',
        'closed' => 'Quotation is closed'
    ];
    
    return [
        'status' => $quotation['data']['status'],
        'message' => $statusMessages[$quotation['data']['status']] ?? 'Unknown status',
        'quotation' => $quotation['data']
    ];
}
```

## Common Use Cases

### 1. Automated Quotation Generation

```php
function generateQuotationFromDeal($dealId)
{
    $deal = Teamleader::deals()->info($dealId);
    
    // Build line items from deal
    $lineItems = [];
    // ... populate line items based on deal details
    
    $quotation = Teamleader::quotations()->create([
        'deal_id' => $dealId,
        'grouped_lines' => [
            [
                'section' => [
                    'title' => 'Proposed Solution'
                ],
                'line_items' => $lineItems
            ]
        ]
    ]);
    
    // Send to customer
    $customer = $deal['data']['lead']['customer'];
    
    Teamleader::quotations()->send([
        'quotations' => [$quotation['data']['id']],
        'from' => ['sender' => ['type' => 'user', 'id' => 'user-uuid']],
        'recipients' => [
            'to' => [
                ['type' => $customer['type'], 'id' => $customer['id']]
            ]
        ],
        'subject' => "Quotation for {$deal['data']['title']}",
        'content' => 'Please review the attached quotation.',
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
    
    $followUpDate = date('Y-m-d', strtotime("-{$days} days"));
    
    foreach ($openQuotations['data'] as $quotation) {
        if ($quotation['created_at'] < $followUpDate) {
            $pending[] = [
                'quotation_id' => $quotation['id'],
                'quotation_name' => $quotation['name'],
                'days_pending' => round((time() - strtotime($quotation['created_at'])) / 86400),
                'deal_id' => $quotation['deal']['id']
            ];
        }
    }
    
    return $pending;
}
```

### 3. Quotation Conversion Rate

```php
function calculateQuotationConversionRate($startDate, $endDate)
{
    $allQuotations = Teamleader::quotations()->list();
    
    $sent = 0;
    $accepted = 0;
    
    foreach ($allQuotations['data'] as $quotation) {
        $createdDate = date('Y-m-d', strtotime($quotation['created_at']));
        
        if ($createdDate >= $startDate && $createdDate <= $endDate) {
            $sent++;
            
            if ($quotation['status'] === 'accepted') {
                $accepted++;
            }
        }
    }
    
    return [
        'sent' => $sent,
        'accepted' => $accepted,
        'conversion_rate' => $sent > 0 ? ($accepted / $sent) * 100 : 0
    ];
}
```

### 4. Bulk Quotation Operations

```php
function sendBulkQuotations(array $quotationIds, $recipients, $subject, $content)
{
    // Validate all quotations exist and are open
    $validQuotations = [];
    
    foreach ($quotationIds as $id) {
        $quotation = Teamleader::quotations()->info($id);
        
        if ($quotation['data']['status'] === 'open') {
            $validQuotations[] = $id;
        }
    }
    
    if (empty($validQuotations)) {
        throw new \Exception('No valid open quotations found');
    }
    
    // Send all at once
    return Teamleader::quotations()->send([
        'quotations' => $validQuotations,
        'from' => ['sender' => ['type' => 'user', 'id' => 'user-uuid']],
        'recipients' => $recipients,
        'subject' => $subject,
        'content' => $content,
        'language' => 'en'
    ]);
}
```

### 5. Quotation Version Control

```php
function createQuotationRevision($originalQuotationId, $changes)
{
    // Get original quotation
    $original = Teamleader::quotations()->info($originalQuotationId);
    
    // Create new version with changes
    $newData = array_merge($original['data'], $changes);
    
    $revision = Teamleader::quotations()->create($newData);
    
    // Log the revision
    DB::table('quotation_revisions')->insert([
        'original_quotation_id' => $originalQuotationId,
        'new_quotation_id' => $revision['data']['id'],
        'changes' => json_encode($changes),
        'created_by' => auth()->user()->id,
        'created_at' => now()
    ]);
    
    return $revision;
}
```

## Best Practices

### 1. Always Validate Before Sending

```php
// Good: Validate quotation is in correct state
function sendQuotation($quotationId, $recipientEmail)
{
    $quotation = Teamleader::quotations()->info($quotationId);
    
    if ($quotation['data']['status'] !== 'open') {
        throw new \Exception("Cannot send quotation with status: {$quotation['data']['status']}");
    }
    
    // Proceed with sending
    return Teamleader::quotations()->send([...]);
}
```

### 2. Track Quotation Lifecycle

```php
// Good: Log all quotation state changes
function acceptQuotation($quotationId)
{
    $result = Teamleader::quotations()->accept($quotationId);
    
    // Log the acceptance
    DB::table('quotation_events')->insert([
        'quotation_id' => $quotationId,
        'event_type' => 'accepted',
        'user_id' => auth()->user()->id,
        'created_at' => now()
    ]);
    
    // Trigger follow-up actions
    event(new QuotationAccepted($quotationId));
    
    return $result;
}
```

### 3. Handle Download URLs Properly

```php
// Good: Use download URLs immediately or store securely
function getQuotationForCustomer($quotationId)
{
    $download = Teamleader::quotations()->download($quotationId);
    
    // Check expiration
    $expires = new \DateTime($download['data']['expires']);
    $now = new \DateTime();
    
    if ($expires < $now) {
        // Re-generate download link
        $download = Teamleader::quotations()->download($quotationId);
    }
    
    return $download['data']['location'];
}
```

### 4. Organize Line Items Logically

```php
// Good: Group related items into sections
Teamleader::quotations()->create([
    'deal_id' => 'deal-uuid',
    'grouped_lines' => [
        [
            'section' => ['title' => 'Hardware'],
            'line_items' => [/* hardware items */]
        ],
        [
            'section' => ['title' => 'Software'],
            'line_items' => [/* software items */]
        ],
        [
            'section' => ['title' => 'Services'],
            'line_items' => [/* service items */]
        ]
    ]
]);
```

### 5. Set Follow-up Reminders

```php
// Good: Create automated follow-ups
function createQuotationWithFollowUp($data, $followUpDays = 7)
{
    $quotation = Teamleader::quotations()->create($data);
    
    // Schedule follow-up
    DB::table('quotation_follow_ups')->insert([
        'quotation_id' => $quotation['data']['id'],
        'follow_up_date' => date('Y-m-d', strtotime("+{$followUpDays} days")),
        'assigned_to' => auth()->user()->id,
        'created_at' => now()
    ]);
    
    return $quotation;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $quotation = Teamleader::quotations()->create([...]);
} catch (TeamleaderException $e) {
    Log::error('Error creating quotation', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    if ($e->getCode() === 422) {
        // Validation error
        return response()->json([
            'error' => 'Invalid quotation data'
        ], 422);
    }
}

// Handle send errors
try {
    Teamleader::quotations()->send([...]);
} catch (\InvalidArgumentException $e) {
    // Missing required fields
    return response()->json([
        'error' => 'Missing required send parameters: ' . $e->getMessage()
    ], 400);
}
```

## Quotation Statuses

- **open** - Quotation is awaiting response
- **accepted** - Customer has accepted the quotation
- **expired** - Quotation has passed its expiry date (if set)
- **rejected** - Customer has rejected the quotation
- **closed** - Quotation is closed (manually or automatically)

## Related Resources

- [Deals](deals.md) - Quotations are linked to deals
- [Orders](orders.md) - Accepted quotations become orders
- [Invoices](../invoicing/invoices.md) - Quotations can be converted to invoices

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

# Deals

Manage deals (opportunities) in Teamleader Focus.

## Overview

The Deals resource provides full CRUD (Create, Read, Update, Delete) operations for managing deal records in your Teamleader CRM. Deals represent sales opportunities that move through your sales pipeline from initial contact to either won or lost status.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [win()](#win)
    - [lose()](#lose)
    - [move()](#move)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Sorting](#sorting)
- [Sideloading](#sideloading)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`deals`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ✅ Supported
- **Sideloading**: ✅ Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all deals with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, sort, include)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all deals
$deals = Teamleader::deals()->list();

// Get open deals only
$deals = Teamleader::deals()->list([
    'status' => ['open']
]);

// With pagination and sorting
$deals = Teamleader::deals()->list([], [
    'page_size' => 50,
    'page_number' => 2,
    'sort' => 'created_at',
    'sort_order' => 'desc'
]);
```

### `info()`

Get detailed information about a specific deal.

**Parameters:**
- `id` (string): Deal UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Get deal information
$deal = Teamleader::deals()->info('deal-uuid');

// With sideloading
$deal = Teamleader::deals()->info('deal-uuid', 'customer,responsible_user');

// Using fluent interface
$deal = Teamleader::deals()
    ->with('customer,responsible_user,current_phase')
    ->info('deal-uuid');
```

### `create()`

Create a new deal.

**Parameters:**
- `data` (array): Deal data

**Example:**
```php
$deal = Teamleader::deals()->create([
    'title' => 'New Sales Opportunity',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'phase_id' => 'phase-uuid',
    'estimated_value' => [
        'amount' => 5000,
        'currency' => 'EUR'
    ],
    'estimated_probability' => 0.75,
    'estimated_closing_date' => '2024-12-31',
    'responsible_user_id' => 'user-uuid',
    'source_id' => 'source-uuid'
]);
```

### `update()`

Update an existing deal.

**Parameters:**
- `id` (string): Deal UUID
- `data` (array): Updated deal data

**Example:**
```php
$deal = Teamleader::deals()->update('deal-uuid', [
    'title' => 'Updated Opportunity Title',
    'estimated_probability' => 0.90,
    'estimated_value' => [
        'amount' => 7500,
        'currency' => 'EUR'
    ]
]);
```

### `delete()`

Delete a deal.

**Parameters:**
- `id` (string): Deal UUID

**Example:**
```php
Teamleader::deals()->delete('deal-uuid');
```

### `win()`

Mark a deal as won.

**Parameters:**
- `id` (string): Deal UUID

**Example:**
```php
Teamleader::deals()->win('deal-uuid');
```

### `lose()`

Mark a deal as lost, optionally with a reason.

**Parameters:**
- `id` (string): Deal UUID
- `reasonId` (string, optional): Lost reason UUID
- `extraInfo` (string, optional): Additional information

**Example:**
```php
// Mark as lost without reason
Teamleader::deals()->lose('deal-uuid');

// Mark as lost with reason
Teamleader::deals()->lose('deal-uuid', 'lost-reason-uuid');

// Mark as lost with reason and additional info
Teamleader::deals()->lose('deal-uuid', 'lost-reason-uuid', 'Price too high for client budget');
```

### `move()`

Move a deal to a different phase in the pipeline.

**Parameters:**
- `id` (string): Deal UUID
- `phaseId` (string): Target phase UUID

**Example:**
```php
Teamleader::deals()->move('deal-uuid', 'new-phase-uuid');
```

## Helper Methods

The Deals resource provides convenient helper methods for common operations:

### Status Filter Methods

```php
// Get only open deals
$deals = Teamleader::deals()->open();

// Get only won deals
$deals = Teamleader::deals()->won();

// Get only lost deals
$deals = Teamleader::deals()->lost();

// Add additional filters
$deals = Teamleader::deals()->open([
    'responsible_user_id' => 'user-uuid'
]);
```

### Customer Filter Methods

```php
// Get deals for a specific company
$deals = Teamleader::deals()->forCustomer('company', 'company-uuid');

// Get deals for a specific contact
$deals = Teamleader::deals()->forCustomer('contact', 'contact-uuid');

// With additional filters
$deals = Teamleader::deals()->forCustomer('company', 'company-uuid', [
    'status' => ['open']
]);
```

### Filter Helper Methods

```php
// Get deals updated since a date
$deals = Teamleader::deals()->updatedSince('2024-01-01');

// Get deals for specific phase
$deals = Teamleader::deals()->inPhase('phase-uuid');

// Get deals for specific pipeline
$deals = Teamleader::deals()->inPipeline('pipeline-uuid');

// Get deals by IDs
$deals = Teamleader::deals()->byIds(['deal-uuid-1', 'deal-uuid-2']);

// Get deals with specific tags
$deals = Teamleader::deals()->withTags(['High Priority', 'Q1 2024']);
```

### Sideloading Helper Methods

```php
// Include customer information
$deals = Teamleader::deals()
    ->withCustomer()
    ->list();

// Include responsible user
$deals = Teamleader::deals()
    ->withResponsibleUser()
    ->list();

// Include current phase
$deals = Teamleader::deals()
    ->withCurrentPhase()
    ->list();

// Include quotations
$deals = Teamleader::deals()
    ->withQuotations()
    ->list();

// Chain multiple includes
$deals = Teamleader::deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->withCurrentPhase()
    ->list();
```

## Filters

### Available Filters

#### `ids`
Filter by specific deal UUIDs.

```php
$deals = Teamleader::deals()->list([
    'ids' => ['deal-uuid-1', 'deal-uuid-2']
]);
```

#### `phase_id`
Filter by deal phase.

```php
$deals = Teamleader::deals()->list([
    'phase_id' => 'phase-uuid'
]);
```

#### `pipeline_id`
Filter by deal pipeline.

```php
$deals = Teamleader::deals()->list([
    'pipeline_id' => 'pipeline-uuid'
]);
```

#### `status`
Filter by deal status.

**Values:** `open`, `won`, `lost`

```php
$deals = Teamleader::deals()->list([
    'status' => ['open', 'won']
]);
```

#### `customer`
Filter by customer (company or contact).

```php
// Deals for a company
$deals = Teamleader::deals()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Deals for a contact
$deals = Teamleader::deals()->list([
    'customer' => [
        'type' => 'contact',
        'id' => 'contact-uuid'
    ]
]);
```

#### `term`
Search term that searches across deal title.

```php
$deals = Teamleader::deals()->list([
    'term' => 'New Office'
]);
```

#### `responsible_user_id`
Filter by responsible user.

```php
$deals = Teamleader::deals()->list([
    'responsible_user_id' => 'user-uuid'
]);
```

#### `estimated_value_min` / `estimated_value_max`
Filter by estimated value range.

```php
$deals = Teamleader::deals()->list([
    'estimated_value_min' => 1000,
    'estimated_value_max' => 10000,
    'currency' => 'EUR'
]);
```

#### `estimated_closing_date_from` / `estimated_closing_date_to`
Filter by estimated closing date range.

```php
$deals = Teamleader::deals()->list([
    'estimated_closing_date_from' => '2024-01-01',
    'estimated_closing_date_to' => '2024-12-31'
]);
```

#### `updated_since`
Filter by last update date (ISO 8601 datetime).

```php
$deals = Teamleader::deals()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);
```

#### `tags`
Filter by tag names.

```php
$deals = Teamleader::deals()->list([
    'tags' => ['High Priority', 'Enterprise']
]);
```

## Sorting

Deals can be sorted by various fields:

```php
// Sort by creation date
$deals = Teamleader::deals()->list([], [
    'sort' => 'created_at',
    'sort_order' => 'desc'
]);

// Sort by estimated value
$deals = Teamleader::deals()->list([], [
    'sort' => 'estimated_value',
    'sort_order' => 'desc'
]);

// Sort by estimated closing date
$deals = Teamleader::deals()->list([], [
    'sort' => 'estimated_closing_date',
    'sort_order' => 'asc'
]);
```

## Sideloading

Load related data in a single request:

### Available Includes

- `customer` - Customer information (company or contact)
- `customer.primary_address` - Customer's primary address
- `responsible_user` - User responsible for the deal
- `current_phase` - Current deal phase information
- `quotations` - Quotations associated with the deal

### Usage

```php
// Single include
$deal = Teamleader::deals()
    ->with('customer')
    ->info('deal-uuid');

// Multiple includes
$deal = Teamleader::deals()
    ->with('customer,responsible_user,current_phase,quotations')
    ->info('deal-uuid');

// In list() calls
$deals = Teamleader::deals()->list([], [
    'include' => 'customer,responsible_user'
]);
```

## Response Structure

### Deal Object

```php
[
    'id' => 'deal-uuid',
    'title' => 'New Sales Opportunity',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'phase' => [
        'id' => 'phase-uuid'
    ],
    'estimated_value' => [
        'amount' => 5000.00,
        'currency' => 'EUR'
    ],
    'estimated_probability' => 0.75,
    'estimated_closing_date' => '2024-12-31',
    'responsible_user' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ],
    'source' => [
        'id' => 'source-uuid'
    ],
    'department' => [
        'id' => 'department-uuid'
    ],
    'created_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-20T14:45:00+00:00',
    'status' => 'open',
    'web_url' => 'https://focus.teamleader.eu/deal_detail.php?id=123'
]
```

## Usage Examples

### Create a Complete Deal

```php
$deal = Teamleader::deals()->create([
    'title' => 'Enterprise Software License',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'contact_person_id' => 'contact-uuid'
    ],
    'phase_id' => 'phase-uuid',
    'estimated_value' => [
        'amount' => 25000,
        'currency' => 'EUR'
    ],
    'estimated_probability' => 0.60,
    'estimated_closing_date' => '2024-06-30',
    'responsible_user_id' => 'user-uuid',
    'source_id' => 'source-uuid',
    'department_id' => 'department-uuid',
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Enterprise'
        ]
    ]
]);
```

### Move Deal Through Pipeline

```php
// Create deal in first phase
$deal = Teamleader::deals()->create([
    'title' => 'New Opportunity',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'phase_id' => 'first-phase-uuid',
    'estimated_value' => [
        'amount' => 10000,
        'currency' => 'EUR'
    ]
]);

// Move to next phase
Teamleader::deals()->move($deal['data']['id'], 'second-phase-uuid');

// Update probability
Teamleader::deals()->update($deal['data']['id'], [
    'estimated_probability' => 0.75
]);

// Move to final phase
Teamleader::deals()->move($deal['data']['id'], 'final-phase-uuid');

// Mark as won
Teamleader::deals()->win($deal['data']['id']);
```

### Handle Lost Deal

```php
// Get lost reasons
$lostReasons = Teamleader::lostReasons()->list();

// Mark deal as lost with reason
Teamleader::deals()->lose(
    'deal-uuid',
    $lostReasons['data'][0]['id'],
    'Client decided to go with a competitor due to pricing'
);
```

### Get Deals with Full Information

```php
$deals = Teamleader::deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->withCurrentPhase()
    ->withQuotations()
    ->list([
        'status' => ['open'],
        'responsible_user_id' => 'user-uuid'
    ]);

foreach ($deals['data'] as $deal) {
    echo "Deal: {$deal['title']}\n";
    echo "Customer: {$deal['customer']['name']}\n";
    echo "Phase: {$deal['current_phase']['name']}\n";
    echo "Owner: {$deal['responsible_user']['first_name']} {$deal['responsible_user']['last_name']}\n";
    echo "Quotations: " . count($deal['quotations']) . "\n\n";
}
```

## Common Use Cases

### 1. Sales Pipeline Report

```php
function generatePipelineReport($pipelineId)
{
    $phases = Teamleader::dealPhases()->forPipeline($pipelineId);
    $report = [];
    
    foreach ($phases['data'] as $phase) {
        $deals = Teamleader::deals()->inPhase($phase['id']);
        
        $totalValue = array_reduce($deals['data'], function($carry, $deal) {
            return $carry + $deal['estimated_value']['amount'];
        }, 0);
        
        $report[] = [
            'phase' => $phase['name'],
            'deal_count' => count($deals['data']),
            'total_value' => $totalValue,
            'average_value' => count($deals['data']) > 0 
                ? $totalValue / count($deals['data']) 
                : 0
        ];
    }
    
    return $report;
}
```

### 2. Deal Aging Analysis

```php
function analyzeOldDeals($days = 30)
{
    $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
    
    $oldDeals = Teamleader::deals()
        ->withCustomer()
        ->withResponsibleUser()
        ->open([
            'updated_since' => '2020-01-01'
        ]);
    
    $staleDeals = array_filter($oldDeals['data'], function($deal) use ($cutoffDate) {
        return $deal['updated_at'] < $cutoffDate;
    });
    
    return [
        'total_open_deals' => count($oldDeals['data']),
        'stale_deals' => count($staleDeals),
        'deals' => $staleDeals
    ];
}
```

### 3. Win Rate Calculator

```php
function calculateWinRate($userId, $startDate, $endDate)
{
    $wonDeals = Teamleader::deals()->won([
        'responsible_user_id' => $userId,
        'estimated_closing_date_from' => $startDate,
        'estimated_closing_date_to' => $endDate
    ]);
    
    $lostDeals = Teamleader::deals()->lost([
        'responsible_user_id' => $userId,
        'estimated_closing_date_from' => $startDate,
        'estimated_closing_date_to' => $endDate
    ]);
    
    $totalClosed = count($wonDeals['data']) + count($lostDeals['data']);
    
    return [
        'won' => count($wonDeals['data']),
        'lost' => count($lostDeals['data']),
        'win_rate' => $totalClosed > 0 
            ? (count($wonDeals['data']) / $totalClosed) * 100 
            : 0
    ];
}
```

### 4. Automated Deal Follow-up

```php
function getDealsNeedingAttention()
{
    $today = date('Y-m-d');
    
    // Get deals closing soon
    $closingSoon = Teamleader::deals()->open([
        'estimated_closing_date_from' => $today,
        'estimated_closing_date_to' => date('Y-m-d', strtotime('+7 days'))
    ]);
    
    // Get high-value deals
    $highValue = Teamleader::deals()->open([
        'estimated_value_min' => 10000,
        'currency' => 'EUR'
    ]);
    
    return [
        'closing_soon' => $closingSoon['data'],
        'high_value' => $highValue['data']
    ];
}
```

### 5. Deal Assignment Balancing

```php
function balanceDealLoad()
{
    $users = Teamleader::users()->active();
    $dealCounts = [];
    
    foreach ($users['data'] as $user) {
        $deals = Teamleader::deals()->open([
            'responsible_user_id' => $user['id']
        ]);
        
        $dealCounts[$user['id']] = [
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'count' => count($deals['data']),
            'total_value' => array_reduce($deals['data'], function($carry, $deal) {
                return $carry + $deal['estimated_value']['amount'];
            }, 0)
        ];
    }
    
    // Sort by deal count
    uasort($dealCounts, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    return $dealCounts;
}
```

## Best Practices

### 1. Always Use Sideloading for Related Data

```php
// Good: One API call
$deal = Teamleader::deals()
    ->with('customer,responsible_user,current_phase')
    ->info('deal-uuid');

// Bad: Multiple API calls
$deal = Teamleader::deals()->info('deal-uuid');
$customer = Teamleader::companies()->info($deal['data']['lead']['customer']['id']);
$user = Teamleader::users()->info($deal['data']['responsible_user']['id']);
```

### 2. Track Status Changes

```php
// Good: Track when and why deals are lost
function markDealLost($dealId, $reasonId, $notes)
{
    // Log the change
    Log::info('Deal marked as lost', [
        'deal_id' => $dealId,
        'reason_id' => $reasonId,
        'notes' => $notes,
        'user' => auth()->user()->id,
        'timestamp' => now()
    ]);
    
    return Teamleader::deals()->lose($dealId, $reasonId, $notes);
}
```

### 3. Validate Phase Transitions

```php
// Good: Ensure phase belongs to deal's pipeline
function moveDealToPhase($dealId, $phaseId)
{
    $deal = Teamleader::deals()->info($dealId);
    $phase = Teamleader::dealPhases()->info($phaseId);
    
    // Get current phase's pipeline
    $currentPhase = Teamleader::dealPhases()->info($deal['data']['phase']['id']);
    
    if ($phase['data']['deal_pipeline_id'] !== $currentPhase['data']['deal_pipeline_id']) {
        throw new \Exception('Phase must be in the same pipeline');
    }
    
    return Teamleader::deals()->move($dealId, $phaseId);
}
```

### 4. Use Filters for Performance

```php
// Good: Filter at API level
$deals = Teamleader::deals()->list([
    'status' => ['open'],
    'responsible_user_id' => 'user-uuid',
    'phase_id' => 'phase-uuid'
]);

// Bad: Filter after fetching everything
$allDeals = Teamleader::deals()->list();
$filtered = array_filter($allDeals['data'], function($deal) {
    return $deal['status'] === 'open' && 
           $deal['responsible_user']['id'] === 'user-uuid';
});
```

### 5. Handle Estimated Values Carefully

```php
// Good: Always specify currency
Teamleader::deals()->create([
    'title' => 'New Deal',
    'estimated_value' => [
        'amount' => 5000,
        'currency' => 'EUR'
    ]
]);

// Good: Validate currency when filtering
Teamleader::deals()->list([
    'estimated_value_min' => 1000,
    'currency' => 'EUR' // Always specify currency with value filters
]);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $deal = Teamleader::deals()->create([
        'title' => 'New Opportunity',
        'phase_id' => 'phase-uuid'
    ]);
} catch (TeamleaderException $e) {
    Log::error('Error creating deal', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Handle specific error cases
    if ($e->getCode() === 422) {
        // Validation error - likely missing required fields
        return response()->json([
            'error' => 'Deal must have a customer'
        ], 422);
    }
}
```

## Related Resources

- [Deal Phases](deal_phases.md) - Manage deal phases
- [Deal Pipelines](deal_pipelines.md) - Manage deal pipelines
- [Deal Sources](deal_sources.md) - Track deal sources
- [Lost Reasons](lost_reasons.md) - Manage lost reasons
- [Quotations](quotations.md) - Create quotations for deals
- [Orders](orders.md) - View orders from deals
- [Companies](../crm/companies.md) - Deal customers
- [Contacts](../crm/contacts.md) - Deal contacts
- [Users](../general/users.md) - Responsible users

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
- [Sideloading](../sideloading.md) - Efficiently load related data

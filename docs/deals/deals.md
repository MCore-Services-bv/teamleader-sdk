# Deals

Manage sales deals in Teamleader Focus. This resource provides comprehensive functionality for creating, updating, and tracking deals through your sales pipeline.

## Endpoint

`deals`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ✅ Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of deals with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$deals = $teamleader->deals()->list([
    'status' => ['open'],
    'responsible_user_id' => 'user-uuid'
]);
```

### `info()`

Get detailed information about a specific deal.

**Parameters:**
- `id` (string): Deal UUID
- `includes` (array|string): Relations to include

**Example:**
```php
$deal = $teamleader->deals()->info('deal-uuid-here');
```

### `create()`

Create a new deal for a customer.

**Parameters:**
- `data` (array): Deal data including lead, title, estimated value, etc.

**Example:**
```php
$deal = $teamleader->deals()->create([
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ],
    'title' => 'New Business Deal',
    'estimated_value' => [
        'amount' => 10000,
        'currency' => 'EUR'
    ]
]);
```

### `update()`

Update an existing deal.

**Parameters:**
- `id` (string): Deal UUID
- `data` (array): Data to update

**Example:**
```php
$deal = $teamleader->deals()->update('deal-uuid', [
    'title' => 'Updated Deal Title',
    'estimated_probability' => 0.75
]);
```

### `delete()`

Delete a deal.

**Parameters:**
- `id` (string): Deal UUID

**Example:**
```php
$result = $teamleader->deals()->delete('deal-uuid');
```

### `win()`

Mark a deal as won.

**Parameters:**
- `id` (string): Deal UUID

**Example:**
```php
$result = $teamleader->deals()->win('deal-uuid');
```

### `lose()`

Mark a deal as lost with optional reason and details.

**Parameters:**
- `id` (string): Deal UUID
- `reasonId` (string|null): Lost reason UUID (optional)
- `extraInfo` (string|null): Additional information (optional)

**Example:**
```php
$result = $teamleader->deals()->lose(
    'deal-uuid',
    'reason-uuid',
    'Customer chose competitor'
);
```

### `move()`

Move a deal to a different phase in the pipeline.

**Parameters:**
- `id` (string): Deal UUID
- `phaseId` (string): Target phase UUID

**Example:**
```php
$result = $teamleader->deals()->move('deal-uuid', 'phase-uuid');
```

### `open()`

Get only open deals.

**Example:**
```php
$openDeals = $teamleader->deals()->open();
```

### `won()`

Get only won deals.

**Example:**
```php
$wonDeals = $teamleader->deals()->won();
```

### `lost()`

Get only lost deals.

**Example:**
```php
$lostDeals = $teamleader->deals()->lost();
```

### `forCustomer()`

Get all deals for a specific customer.

**Parameters:**
- `customerType` (string): Customer type ('contact' or 'company')
- `customerId` (string): Customer UUID

**Example:**
```php
$customerDeals = $teamleader->deals()->forCustomer('company', 'company-uuid');
```

### `byPhase()`

Get deals in a specific phase.

**Parameters:**
- `phaseId` (string): Phase UUID

**Example:**
```php
$phaseDeals = $teamleader->deals()->byPhase('phase-uuid');
```

## Filtering

### Available Filters

- **`ids`**: Array of deal UUIDs to filter by
- **`term`**: Search term (filters on title, reference, and customer name)
- **`customer`**: Filter by customer (requires type and id)
- **`phase_id`**: Filter by specific phase UUID
- **`estimated_closing_date`**: Filter by exact closing date
- **`estimated_closing_date_from`**: Filter by closing date from (inclusive)
- **`estimated_closing_date_until`**: Filter by closing date until (inclusive)
- **`responsible_user_id`**: Filter by responsible user UUID
- **`updated_since`**: Filter by last update date (inclusive)
- **`created_before`**: Filter by creation date (inclusive)
- **`status`**: Filter by deal status (open, won, lost)
- **`pipeline_ids`**: Array of pipeline UUIDs

### Filter Examples

```php
// Filter by status
$openDeals = $teamleader->deals()->list([
    'status' => ['open']
]);

// Filter by customer
$customerDeals = $teamleader->deals()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Filter by date range
$deals = $teamleader->deals()->list([
    'estimated_closing_date_from' => '2024-01-01',
    'estimated_closing_date_until' => '2024-12-31'
]);

// Search by term
$searchResults = $teamleader->deals()->list([
    'term' => 'Software Implementation'
]);

// Filter by responsible user
$myDeals = $teamleader->deals()->list([
    'responsible_user_id' => 'user-uuid'
]);

// Filter by updated since
$recentDeals = $teamleader->deals()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);

// Combine multiple filters
$filteredDeals = $teamleader->deals()->list([
    'status' => ['open'],
    'phase_id' => 'phase-uuid',
    'responsible_user_id' => 'user-uuid'
]);
```

## Sorting

### Available Sort Fields

- **`created_at`**: Sort by creation date (default)
- **`weighted_value`**: Sort by weighted value (estimated value × probability)

### Sorting Examples

```php
// Sort by creation date (ascending)
$deals = $teamleader->deals()->list([], [
    'sort' => [
        [
            'field' => 'created_at',
            'order' => 'asc'
        ]
    ]
]);

// Sort by weighted value (descending)
$deals = $teamleader->deals()->list([], [
    'sort' => [
        [
            'field' => 'weighted_value',
            'order' => 'desc'
        ]
    ]
]);
```

## Sideloading (Includes)

### Available Includes

- **`lead.customer`**: Include customer information
- **`responsible_user`**: Include responsible user details
- **`department`**: Include department information
- **`current_phase`**: Include current phase details
- **`source`**: Include deal source information
- **`custom_fields`**: Include custom field values

### Sideloading Examples

```php
// Using fluent interface
$deal = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->info('deal-uuid');

// Using with() method
$deals = $teamleader->deals()
    ->with(['lead.customer', 'responsible_user', 'department'])
    ->list();

// Include in list options
$deals = $teamleader->deals()->list([], [
    'include' => 'lead.customer,responsible_user,custom_fields'
]);

// Include in info call
$deal = $teamleader->deals()->info('deal-uuid', 'lead.customer,responsible_user');
```

## Pagination

```php
// Set page size and number
$deals = $teamleader->deals()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// Access pagination info
$result = $teamleader->deals()->list();
$currentPage = $result['meta']['page']['number'] ?? 1;
$totalPages = $result['meta']['page']['count'] ?? 1;
```

## Usage Examples

### Create a New Deal

```php
$deal = $teamleader->deals()->create([
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'contact_person_id' => 'contact-uuid'
    ],
    'title' => 'Software License Renewal',
    'summary' => 'Annual license renewal for enterprise plan',
    'source_id' => 'source-uuid',
    'department_id' => 'department-uuid',
    'responsible_user_id' => 'user-uuid',
    'phase_id' => 'phase-uuid',
    'estimated_value' => [
        'amount' => 25000,
        'currency' => 'EUR'
    ],
    'estimated_probability' => 0.80,
    'estimated_closing_date' => '2024-12-31',
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Enterprise'
        ]
    ]
]);
```

### Update Deal Information

```php
$deal = $teamleader->deals()->update('deal-uuid', [
    'title' => 'Updated Deal Title',
    'estimated_probability' => 0.90,
    'estimated_value' => [
        'amount' => 30000,
        'currency' => 'EUR'
    ],
    'estimated_closing_date' => '2024-11-30'
]);
```

### Move Deal Through Pipeline

```php
// Move to next phase
$result = $teamleader->deals()->move('deal-uuid', 'next-phase-uuid');

// Mark as won
$result = $teamleader->deals()->win('deal-uuid');

// Mark as lost with reason
$result = $teamleader->deals()->lose(
    'deal-uuid',
    'lost-reason-uuid',
    'Price was too high for customer budget'
);
```

### Get Deals with Full Information

```php
$deal = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->withDepartment()
    ->info('deal-uuid');

// Access included data
$customer = $deal['included']['company'][0] ?? null;
$responsibleUser = $deal['included']['user'][0] ?? null;
$department = $deal['included']['department'][0] ?? null;
```

### Filter and Sort Deals

```php
// Get open deals for a specific user, sorted by weighted value
$myOpenDeals = $teamleader->deals()->list([
    'status' => ['open'],
    'responsible_user_id' => 'user-uuid'
], [
    'sort' => [
        [
            'field' => 'weighted_value',
            'order' => 'desc'
        ]
    ],
    'page_size' => 20
]);
```

### Complex Query Example

Get high-value open deals closing this quarter with full details:

```php
$deals = $teamleader->deals()
    ->withCustomer()
    ->withResponsibleUser()
    ->list([
        'status' => ['open'],
        'estimated_closing_date_from' => '2024-10-01',
        'estimated_closing_date_until' => '2024-12-31'
    ], [
        'sort' => [
            [
                'field' => 'weighted_value',
                'order' => 'desc'
            ]
        ],
        'page_size' => 50
    ]);

foreach ($deals['data'] as $deal) {
    $weightedValue = $deal['weighted_value']['amount'] ?? 0;
    $probability = $deal['estimated_probability'] ?? 0;
    $closingDate = $deal['estimated_closing_date'] ?? 'N/A';
    
    echo "Deal: {$deal['title']} - €{$weightedValue} ({$probability}% chance) - Closes: {$closingDate}\n";
}
```

## Error Handling

```php
$result = $teamleader->deals()->create($dealData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Deals API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'data' => $dealData
    ]);
}
```

## Rate Limiting

Deals API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Status change operations** (win/lose/move): 1 request per call

Rate limit cost: **1 request per method call**

## Data Fields

### Common Fields (Available in list and info)

- **`id`**: Deal UUID
- **`title`**: Deal title
- **`summary`**: Deal description
- **`reference`**: Deal reference number (e.g., "2017/2")
- **`status`**: Deal status (new, open, won, lost)
- **`lead`**: Lead information including customer and contact person
- **`estimated_value`**: Estimated value with amount and currency
- **`estimated_closing_date`**: Expected closing date
- **`estimated_probability`**: Win probability (0-1)
- **`weighted_value`**: Calculated weighted value (value × probability)
- **`purchase_order_number`**: Customer's purchase order number
- **`current_phase`**: Current pipeline phase
- **`responsible_user`**: Responsible user information
- **`department`**: Department information
- **`source`**: Deal source information
- **`closed_at`**: Date when deal was closed (won/lost)
- **`created_at`**: Deal creation timestamp
- **`updated_at`**: Last update timestamp
- **`web_url`**: Direct link to deal in Teamleader

### Additional Fields (Available in info only)

- **`phase_history`**: Complete history of phase changes
- **`quotations`**: Related quotations
- **`lost_reason`**: Information about why deal was lost (if applicable)
- **`custom_fields`**: Custom field values
- **`currency_exchange_rate`**: Exchange rate information if deal uses different currency

## Notes

- All monetary values require both `amount` and `currency` fields
- Estimated probability must be between 0 and 1 (inclusive)
- When marking a deal as lost, providing a reason improves reporting
- Moving a deal to a different phase requires the phase to be in the same pipeline
- Customer type must be either 'contact' or 'company'
- Dates should be in ISO format (YYYY-MM-DD)
- Timestamps should be in ISO 8601 format with timezone
- Custom fields require the custom field definition ID

## Laravel Integration

When using this resource in Laravel applications:

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DealController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $deals = $teamleader->deals()
            ->withCustomer()
            ->withResponsibleUser()
            ->open();
        
        return view('deals.index', compact('deals'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid',
            'title' => 'required|string',
            'estimated_value' => 'required|numeric',
        ]);
        
        $deal = $teamleader->deals()->create([
            'lead' => [
                'customer' => [
                    'type' => 'company',
                    'id' => $validated['customer_id']
                ]
            ],
            'title' => $validated['title'],
            'estimated_value' => [
                'amount' => $validated['estimated_value'],
                'currency' => 'EUR'
            ]
        ]);
        
        return redirect()->route('deals.show', $deal['data']['id']);
    }
    
    public function markAsWon(TeamleaderSDK $teamleader, string $id)
    {
        $teamleader->deals()->win($id);
        
        return redirect()->back()->with('success', 'Deal marked as won!');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

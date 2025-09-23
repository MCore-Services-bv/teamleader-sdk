# Deals

Manage deals in Teamleader Focus. This resource provides complete CRUD operations for managing sales opportunities, including status management and phase transitions.

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
$deals = $teamleader->deals()->list(['status' => ['open']]);
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

Create a new deal.

**Parameters:**
- `data` (array): Array of deal data

**Example:**
```php
$deal = $teamleader->deals()->create([
    'title' => 'New Business Deal',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ]
    ]
]);
```

### `update()`

Update an existing deal.

**Parameters:**
- `id` (string): Deal UUID
- `data` (array): Array of data to update

**Example:**
```php
$deal = $teamleader->deals()->update('deal-uuid', [
    'title' => 'Updated Deal Title'
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

### `search()`

Search deals by term (searches title, reference and customer's name).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$deals = $teamleader->deals()->search('important deal');
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

Mark a deal as lost with optional reason.

**Parameters:**
- `id` (string): Deal UUID
- `reasonId` (string): Optional lost reason UUID
- `extraInfo` (string): Optional additional information

**Example:**
```php
$result = $teamleader->deals()->lose('deal-uuid', 'reason-uuid', 'Customer chose competitor');
```

### `move()`

Move a deal to a different phase.

**Parameters:**
- `id` (string): Deal UUID
- `phaseId` (string): Target phase UUID

**Example:**
```php
$result = $teamleader->deals()->move('deal-uuid', 'new-phase-uuid');
```

### `forCustomer()`

Get deals for a specific customer.

**Parameters:**
- `type` (string): Customer type (contact or company)
- `customerId` (string): Customer UUID
- `options` (array): Additional options

**Example:**
```php
$deals = $teamleader->deals()->forCustomer('company', 'company-uuid');
```

### `inPhase()` / `withStatus()` / `forUser()`

Filter deals by phase, status, or user.

**Example:**
```php
$deals = $teamleader->deals()->inPhase('phase-uuid');
$deals = $teamleader->deals()->withStatus(['open', 'won']);
$deals = $teamleader->deals()->forUser('user-uuid');
```

### `open()` / `won()` / `lost()`

Get deals with specific status.

**Example:**
```php
$openDeals = $teamleader->deals()->open();
$wonDeals = $teamleader->deals()->won();
$lostDeals = $teamleader->deals()->lost();
```

## Filtering

### Available Filters

- **`ids`**: Array of deal UUIDs to filter by
- **`term`**: Search term (searches title, reference and customer's name)
- **`customer`**: Customer object with type (contact/company) and id
- **`phase_id`**: Deal phase UUID
- **`estimated_closing_date`**: Specific closing date (Y-m-d format)
- **`estimated_closing_date_from`**: Closing date range start (inclusive)
- **`estimated_closing_date_until`**: Closing date range end (inclusive)
- **`responsible_user_id`**: User UUID or array of UUIDs
- **`updated_since`**: ISO 8601 datetime
- **`created_before`**: ISO 8601 datetime
- **`status`**: Array of statuses (open, won, lost)
- **`pipeline_ids`**: Array of pipeline UUIDs

### Filter Examples

```php
// Filter by status
$openDeals = $teamleader->deals()->list([
    'status' => ['open']
]);

// Search by term
$deals = $teamleader->deals()->list([
    'term' => 'software implementation'
]);

// Filter by customer
$deals = $teamleader->deals()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// Filter by phase
$deals = $teamleader->deals()->list([
    'phase_id' => 'proposal-phase-uuid'
]);

// Filter by closing date range
$deals = $teamleader->deals()->list([
    'estimated_closing_date_from' => '2024-01-01',
    'estimated_closing_date_until' => '2024-06-30'
]);

// Filter by responsible user
$deals = $teamleader->deals()->list([
    'responsible_user_id' => ['user1-uuid', 'user2-uuid']
]);
```

## Sorting

### Available Sort Fields

- **`created_at`**: Date deal was created (default)
- **`weighted_value`**: Deal weighted value

### Sorting Examples

```php
// Sort by creation date (newest first)
$deals = $teamleader->deals()->list([], [
    'sort' => 'created_at',
    'sort_order' => 'desc'
]);

// Sort by weighted value (highest first)
$deals = $teamleader->deals()->list([], [
    'sort' => 'weighted_value',
    'sort_order' => 'desc'
]);
```

## Sideloading

### Available Includes

- **`custom_fields`**: Include custom field values

### Sideloading Examples

```php
// Include custom fields
$deals = $teamleader->deals()->withCustomFields()->list();

// Using fluent interface
$deals = $teamleader->deals()->with('custom_fields')->list();

// Include in specific calls
$deal = $teamleader->deals()->info('deal-uuid', 'custom_fields');
```

## Data Fields

### Deal Creation Fields

**Required:**
- `title` (string): Deal title
- `lead.customer.type` (string): Customer type (contact or company)
- `lead.customer.id` (string): Customer UUID

**Optional:**
- `summary` (string): Deal description
- `lead.contact_person_id` (string): Contact person UUID
- `source_id` (string): Deal source UUID
- `department_id` (string): Department UUID
- `responsible_user_id` (string): Responsible user UUID
- `phase_id` (string): Initial phase UUID
- `estimated_value` (object): Amount and currency
- `estimated_probability` (number): Probability between 0 and 1
- `estimated_closing_date` (string): Expected closing date (Y-m-d)
- `currency` (object): Currency code and exchange rate
- `custom_fields` (array): Custom field values

### Deal Response Fields

All creation fields plus:
- `id` (string): Deal UUID
- `reference` (string): Deal reference number
- `status` (string): open, won, lost, new
- `current_phase` (object): Current phase information
- `weighted_value` (object): Probability-weighted value
- `phase_history` (array): Phase transition history
- `quotations` (array): Linked quotations
- `lost_reason` (object): Lost reason (if status is lost)
- `closed_at` (string): Closing timestamp
- `created_at` (string): Creation timestamp
- `updated_at` (string): Last update timestamp
- `web_url` (string): Link to Teamleader interface

## Usage Examples

### Basic Deal Management

```php
// Create a deal
$deal = $teamleader->deals()->create([
    'title' => 'Software License Deal',
    'summary' => 'Annual software licensing opportunity',
    'lead' => [
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'contact_person_id' => 'contact-uuid'
    ],
    'estimated_value' => [
        'amount' => 50000.00,
        'currency' => 'EUR'
    ],
    'estimated_probability' => 0.75,
    'estimated_closing_date' => '2024-06-30'
]);

// Update a deal
$updatedDeal = $teamleader->deals()->update($deal['data']['id'], [
    'estimated_probability' => 0.85,
    'summary' => 'Updated deal description'
]);

// Get deal details
$dealDetails = $teamleader->deals()->info($deal['data']['id']);
```

### Status Management

```php
// Mark deal as won
$teamleader->deals()->win('deal-uuid');

// Mark deal as lost with reason
$teamleader->deals()->lose('deal-uuid', 'competitor-chosen-uuid', 'Price was too high');

// Move to next phase
$teamleader->deals()->move('deal-uuid', 'negotiation-phase-uuid');
```

### Search and Filtering

```php
// Search deals
$results = $teamleader->deals()->search('enterprise software');

// Get deals for company
$companyDeals = $teamleader->deals()->forCustomer('company', 'company-uuid');

// Get open deals in specific phase
$phaseDeals = $teamleader->deals()->list([
    'status' => ['open'],
    'phase_id' => 'proposal-phase-uuid'
]);

// Get deals closing soon
$closingDeals = $teamleader->deals()->closingBetween(
    date('Y-m-d'),
    date('Y-m-d', strtotime('+30 days'))
);
```

### Complex Queries

```php
// High-value open deals with custom fields
$deals = $teamleader->deals()
    ->withCustomFields()
    ->list([
        'status' => ['open'],
        'responsible_user_id' => 'user-uuid'
    ], [
        'sort' => 'weighted_value',
        'sort_order' => 'desc',
        'page_size' => 25
    ]);

// Recent deals for specific pipeline
$recentDeals = $teamleader->deals()->list([
    'pipeline_ids' => ['sales-pipeline-uuid'],
    'updated_since' => '2024-01-01T00:00:00+00:00'
], [
    'sort' => 'updated_at',
    'sort_order' => 'desc'
]);
```

## Error Handling

The deals resource follows standard SDK error handling:

```php
$result = $teamleader->deals()->create($dealData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Deals API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Deal API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **CRUD operations**: 1 request per call
- **Status operations**: 1 request per call
- **Move operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class DealController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $deals = $teamleader->deals()->open();
        return view('deals.index', compact('deals'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $deal = $teamleader->deals()->create($request->validated());
        return redirect()->route('deals.show', $deal['data']['id']);
    }
    
    public function markWon(TeamleaderSDK $teamleader, $id)
    {
        $teamleader->deals()->win($id);
        return back()->with('success', 'Deal marked as won!');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

# Quotations Resource

The Quotations resource allows you to manage quotations in Teamleader Focus, including creating, updating, accepting, sending, and downloading quotations.

## Overview

- **Base Path**: `quotations`
- **Supports**: Create, Read, Update, Delete, List, Custom Actions
- **Available Actions**: accept, send, download
- **Pagination**: Yes (default: 20 items per page)
- **Sorting**: Yes
- **Filtering**: Yes (by IDs)
- **Sideloading**: Yes (expiry)

## Available Methods

### List Quotations

```php
list(array $filters = [], array $options = []): array
```

Get a list of quotations with optional filtering and sorting.

### Get Quotation Info

```php
info(string $id, mixed $includes = null): array
```

Get detailed information about a specific quotation.

### Create Quotation

```php
create(array $data): array
```

Create a new quotation.

### Update Quotation

```php
update(string $id, array $data): array
```

Update an existing quotation.

### Delete Quotation

```php
delete(string $id): array
```

Delete a quotation.

### Accept Quotation

```php
accept(string $id): array
```

Mark a quotation as accepted.

### Send Quotation

```php
send(array $data): array
```

Send one or more quotations via email.

### Download Quotation

```php
download(string $id, string $format = 'pdf'): array
```

Download a quotation in a specific format (currently only PDF supported).

## Usage Examples

### Basic Operations

#### List All Quotations

```php
$quotations = $teamleader->quotations()->list();
```

#### List Specific Quotations

```php
$quotations = $teamleader->quotations()->list([
    'ids' => [
        '5b16f6ee-e302-0079-901b-50c26c4a55b1',
        '2700006a-b351-070b-b311-fb45ed99abe2'
    ]
]);
```

#### Get Quotation with Expiry Information

```php
$quotation = $teamleader->quotations()
    ->include('expiry')
    ->info('e7a3fe2b-2c75-480f-87b9-121816b5257b');

// Access expiry information
$expiryDate = $quotation['data']['expiry']['expires_after'];
$actionAfterExpiry = $quotation['data']['expiry']['action_after_expiry'];
```

### Create a New Quotation

```php
$quotation = $teamleader->quotations()->create([
    'deal_id' => 'cef01135-7e51-4f6f-a6eb-6e5e5a885ac8',
    'currency' => [
        'code' => 'EUR',
        'exchange_rate' => 1.0
    ],
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Products'
            ],
            'line_items' => [
                [
                    'quantity' => 3,
                    'description' => 'An awesome product',
                    'extended_description' => 'Some more information about this awesome product',
                    'unit_price' => [
                        'amount' => 123.3,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'c0c03f1e-77e3-402c-a713-30ea1c585823',
                    'discount' => [
                        'value' => 10,
                        'type' => 'percentage'
                    ]
                ]
            ]
        ]
    ]
]);

$quotationId = $quotation['data']['id'];
```

### Update a Quotation

```php
$teamleader->quotations()->update('5b16f6ee-e302-0079-901b-50c26c4a55b1', [
    'grouped_lines' => [
        [
            'section' => [
                'title' => 'Updated Products'
            ],
            'line_items' => [
                [
                    'quantity' => 5,
                    'description' => 'Updated product',
                    'unit_price' => [
                        'amount' => 150.0,
                        'tax' => 'excluding'
                    ],
                    'tax_rate_id' => 'c0c03f1e-77e3-402c-a713-30ea1c585823'
                ]
            ]
        ]
    ]
]);
```

### Accept a Quotation

```php
$teamleader->quotations()->accept('e7a3fe2b-2c75-480f-87b9-121816b5257b');
```

### Send a Quotation

```php
$teamleader->quotations()->send([
    'quotations' => [
        '023a4609-eda4-006c-8d2c-314539ec5d85',
        'b42635b7-ccd3-0bfc-9628-e90940694845'
    ],
    'from' => [
        'sender' => [
            'type' => 'user',
            'id' => '2659dc4d-444b-4ced-b51c-b87591f604d7'
        ],
        'email_address' => 'info@teamleader.eu'
    ],
    'recipients' => [
        'to' => [
            [
                'customer' => [
                    'type' => 'company',
                    'id' => '2659dc4d-444b-4ced-b51c-b87591f604d7'
                ],
                'email_address' => 'customer@example.com'
            ]
        ]
    ],
    'subject' => 'Your Quotation',
    'content' => 'Please review your quotation here: #LINK',
    'language' => 'en'
]);
```

### Download a Quotation

```php
$downloadInfo = $teamleader->quotations()->download(
    'd885e5d5-bacb-4607-bde9-abc4a04a901b',
    'pdf'
);

$downloadUrl = $downloadInfo['data']['location'];
$expiresAt = $downloadInfo['data']['expires'];

// Download the file
file_put_contents('quotation.pdf', file_get_contents($downloadUrl));
```

### Delete a Quotation

```php
$teamleader->quotations()->delete('4e235f27-0af0-40e5-82f3-d32d0aa9edb3');
```

### Convenience Methods

#### Get Quotations by IDs

```php
$quotations = $teamleader->quotations()->byIds([
    '5b16f6ee-e302-0079-901b-50c26c4a55b1',
    '2700006a-b351-070b-b311-fb45ed99abe2'
]);
```

#### Get Quotations by Status

```php
// Get open quotations
$openQuotations = $teamleader->quotations()->byStatus('open');

// Get accepted quotations
$acceptedQuotations = $teamleader->quotations()->byStatus('accepted');

// Get multiple statuses
$quotations = $teamleader->quotations()->byStatus(['open', 'accepted']);
```

## Advanced Features

### Pagination

```php
// First page (default: 20 items)
$page1 = $teamleader->quotations()->list([], [
    'page' => [
        'size' => 20,
        'number' => 1
    ]
]);

// Second page
$page2 = $teamleader->quotations()->list([], [
    'page' => [
        'size' => 20,
        'number' => 2
    ]
]);
```

### Working with Quotation Expiry

```php
// Create quotation with expiry settings
$quotation = $teamleader->quotations()->create([
    'deal_id' => 'cef01135-7e51-4f6f-a6eb-6e5e5a885ac8',
    'currency' => [
        'code' => 'EUR'
    ],
    'grouped_lines' => [/* ... */],
    'expiry' => [
        'expires_after' => '2023-12-31',
        'action_after_expiry' => 'lock' // or 'none'
    ]
]);

// Retrieve with expiry information
$quotationWithExpiry = $teamleader->quotations()
    ->include('expiry')
    ->info($quotation['data']['id']);
```

### Working with Discounts

```php
$quotation = $teamleader->quotations()->create([
    'deal_id' => 'cef01135-7e51-4f6f-a6eb-6e5e5a885ac8',
    'currency' => ['code' => 'EUR'],
    'grouped_lines' => [/* ... */],
    'discounts' => [
        [
            'type' => 'percentage',
            'value' => 15.5,
            'description' => 'Winter promotion'
        ]
    ]
]);
```

### Using Document Templates

```php
$quotation = $teamleader->quotations()->create([
    'deal_id' => 'cef01135-7e51-4f6f-a6eb-6e5e5a885ac8',
    'currency' => ['code' => 'EUR'],
    'grouped_lines' => [/* ... */],
    'document_template_id' => '179e1564-493b-4305-8c54-a34fc80920fc'
]);
```

## Data Structures

### Quotation Status Values

- `open` - Quotation is open
- `accepted` - Quotation has been accepted
- `expired` - Quotation has expired
- `rejected` - Quotation was rejected
- `closed` - Quotation is closed

### Currency Exchange Rate

```php
[
    'from' => 'USD',
    'to' => 'EUR',
    'rate' => 1.1234
]
```

### Line Item Structure

```php
[
    'quantity' => 3,
    'description' => 'Product name',
    'extended_description' => 'Additional details (Markdown supported)',
    'unit_of_measure_id' => 'f79d3e04-b8dc-0637-8f18-ca7c8fc63b71',
    'unit_price' => [
        'amount' => 123.3,
        'tax' => 'excluding'
    ],
    'tax_rate_id' => 'c0c03f1e-77e3-402c-a713-30ea1c585823',
    'discount' => [
        'value' => 10,
        'type' => 'percentage'
    ],
    'product_id' => 'e2314517-3cab-4aa9-8471-450e73449041',
    'purchase_price' => [
        'amount' => 100.0,
        'currency' => 'EUR'
    ],
    'periodicity' => [
        'unit' => 'week',
        'period' => 2
    ]
]
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $quotation = $teamleader->quotations()->create([
        'deal_id' => 'invalid-id',
        'currency' => ['code' => 'EUR'],
        'grouped_lines' => []
    ]);
} catch (TeamleaderException $e) {
    Log::error('Failed to create quotation', [
        'message' => $e->getMessage(),
        'status' => $e->getCode()
    ]);
}
```

## Rate Limiting

Each quotation operation counts towards your API rate limit:

- **List operations**: 1 request
- **Info operations**: 1 request
- **Create operations**: 1 request
- **Update operations**: 1 request
- **Delete operations**: 1 request
- **Accept operations**: 1 request
- **Send operations**: 1 request
- **Download operations**: 1 request

## Notes

- A quotation needs either `grouped_lines` or `text` to be valid
- Extended descriptions support Markdown formatting
- Line items can have discounts applied at the item level
- Global discounts can be applied to the entire quotation
- The `#LINK` shortcode in email content is replaced with the cloudsign URL
- When sending quotations, all quotations must be from the same deal
- Downloaded quotations expire after a certain time (check the `expires` field)
- Quotation expiry settings control what happens when a quotation expires
- Currency exchange rates are optional and default to 1:1

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class QuotationController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $quotations = $teamleader->quotations()->list();
        
        return view('quotations.index', compact('quotations'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $validated = $request->validate([
            'deal_id' => 'required|uuid',
            'grouped_lines' => 'required|array'
        ]);
        
        $quotation = $teamleader->quotations()->create([
            'deal_id' => $validated['deal_id'],
            'currency' => ['code' => 'EUR'],
            'grouped_lines' => $validated['grouped_lines']
        ]);
        
        return redirect()->route('quotations.show', $quotation['data']['id']);
    }
    
    public function accept(TeamleaderSDK $teamleader, string $id)
    {
        $teamleader->quotations()->accept($id);
        
        return back()->with('success', 'Quotation accepted');
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

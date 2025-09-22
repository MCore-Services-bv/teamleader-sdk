# Companies

Manage companies in Teamleader Focus CRM. This resource provides full CRUD operations for company management including tagging, filtering, and relationship management.

## Endpoint

`companies`

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

Get a paginated list of companies with filtering, sorting, and sideloading options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$companies = $teamleader->companies()->list(['name' => 'Acme'], ['page_size' => 50]);
```

### `info()`

Get detailed information about a specific company.

**Parameters:**
- `id` (string): Company UUID
- `includes` (array|string): Relations to include

**Example:**
```php
$company = $teamleader->companies()->info('company-uuid-here');
```

### `create()`

Create a new company.

**Parameters:**
- `data` (array): Company data

**Example:**
```php
$company = $teamleader->companies()->create(['name' => 'New Company']);
```

### `update()`

Update an existing company.

**Parameters:**
- `id` (string): Company UUID
- `data` (array): Data to update

**Example:**
```php
$company = $teamleader->companies()->update('uuid-here', ['name' => 'Updated Name']);
```

### `delete()`

Delete a company.

**Parameters:**
- `id` (string): Company UUID

**Example:**
```php
$result = $teamleader->companies()->delete('uuid-here');
```

### `tag()`

Add tags to a company.

**Parameters:**
- `id` (string): Company UUID
- `tags` (array|string): Tags to add

**Example:**
```php
$result = $teamleader->companies()->tag('uuid-here', ['vip', 'prospect']);
```

### `untag()`

Remove tags from a company.

**Parameters:**
- `id` (string): Company UUID
- `tags` (array|string): Tags to remove

**Example:**
```php
$result = $teamleader->companies()->untag('uuid-here', ['old-tag']);
```

### `manageTags()`

Add and/or remove tags safely (preserves existing tags).

**Parameters:**
- `id` (string): Company UUID
- `tagsToAdd` (array): Tags to add
- `tagsToRemove` (array): Tags to remove

**Example:**
```php
$result = $teamleader->companies()->manageTags('uuid-here', ['new-tag'], ['old-tag']);
```

## Filtering

### Available Filters

- **`ids`**: Array of company UUIDs
- **`company_number`**: Company number
- **`email`**: Email address
- **`name`**: Company name (fuzzy search)
- **`telephones`**: Phone numbers
- **`vat_number`**: VAT number
- **`website`**: Website URL
- **`tags`**: Array of tag names
- **`updated_since`**: ISO 8601 datetime
- **`added_since`**: ISO 8601 datetime
- **`term`**: Search term (searches name, email, phone, website)

### Filter Examples

```php
// Search by name (fuzzy search)
$companies = $teamleader->companies()->list([
    'name' => 'Acme'
]);

// Search by email
$companies = $teamleader->companies()->list([
    'email' => 'contact@acme.com'
]);

// Search by VAT number
$companies = $teamleader->companies()->list([
    'vat_number' => 'BE0123456789'
]);

// Search by tags
$companies = $teamleader->companies()->list([
    'tags' => ['vip', 'prospect']
]);

// Search by term (searches across multiple fields)
$companies = $teamleader->companies()->list([
    'term' => 'acme corp'
]);

// Filter by updated date
$companies = $teamleader->companies()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);

// Combine multiple filters
$companies = $teamleader->companies()->list([
    'name' => 'Acme',
    'tags' => ['prospect'],
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);
```

## Sorting

### Available Sort Fields

- **`added_at`**: Date company was added
- **`updated_at`**: Date company was last updated
- **`name`**: Company name

### Sorting Examples

```php
// Sort by name (ascending)
$companies = $teamleader->companies()->list([], [
    'sort' => 'name',
    'sort_order' => 'asc'
]);

// Sort by date added (descending)
$companies = $teamleader->companies()->list([], [
    'sort' => 'added_at',
    'sort_order' => 'desc'
]);

// Sort by last updated (descending)
$companies = $teamleader->companies()->list([], [
    'sort' => 'updated_at',
    'sort_order' => 'desc'
]);
```

## Sideloading (Includes)

### Available Includes

- **`addresses`**: Company addresses
- **`business_type`**: Business type information
- **`responsible_user`**: Responsible user details
- **`added_by`**: User who added the company
- **`tags`**: Company tags

### Sideloading Examples

```php
// Include specific relationships
$companies = $teamleader->companies()
    ->withAddresses()
    ->withResponsibleUser()
    ->list();

// Include all common relationships
$companies = $teamleader->companies()
    ->withCommonRelationships()
    ->list();

// Include specific relationships using array
$companies = $teamleader->companies()->list([], [
    'include' => ['addresses', 'responsible_user', 'tags']
]);

// Get single company with all relationships
$company = $teamleader->companies()
    ->withCommonRelationships()
    ->info('uuid-here');
```

## Convenience Methods

### Search Methods

```php
// General search across multiple fields
$companies = $teamleader->companies()->search('acme corp');

// Search by name (fuzzy search)
$companies = $teamleader->companies()->byName('Acme Corporation');

// Search by email
$companies = $teamleader->companies()->byEmail('contact@acme.com');

// Search by VAT number
$companies = $teamleader->companies()->byVatNumber('BE0123456789');

// Get companies with specific tags
$companies = $teamleader->companies()->withTags(['vip', 'prospect']);

// Get recently updated companies
$companies = $teamleader->companies()->updatedSince('2024-01-01T00:00:00+00:00');

// Get recently added companies
$companies = $teamleader->companies()->addedSince('2024-01-01T00:00:00+00:00');
```

## Company Data Structure

### Create/Update Data Format

```php
$companyData = [
    'name' => 'Acme Corporation',
    'business_type' => [
        'id' => 'business-type-uuid'
    ],
    'company_number' => 'COMP001',
    'vat_number' => 'BE0123456789',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'info@acme.com'
        ],
        [
            'type' => 'invoicing',
            'email' => 'billing@acme.com'
        ]
    ],
    'telephones' => [
        [
            'type' => 'phone',
            'number' => '+32 9 123 45 67'
        ]
    ],
    'website' => 'https://acme.com',
    'addresses' => [
        [
            'type' => 'primary',
            'line_1' => '123 Business Street',
            'postal_code' => '9000',
            'city' => 'Ghent',
            'country' => 'BE'
        ]
    ],
    'iban' => 'BE68 5390 0754 7034',
    'bic' => 'BBRUBEBB',
    'language' => 'nl',
    'payment_term' => [
        'type' => 'cash'
    ],
    'responsible_user' => [
        'id' => 'user-uuid'
    ],
    'remarks' => 'Important client notes',
    'tags' => ['vip', 'prospect']
];
```

### Response Format

```json
{
    "data": {
        "id": "c2b92506-be8c-4e9e-baaa-bb0ad9a5b105",
        "name": "Acme Corporation",
        "company_number": "COMP001",
        "vat_number": "BE0123456789",
        "emails": [
            {
                "type": "primary",
                "email": "info@acme.com"
            }
        ],
        "telephones": [
            {
                "type": "phone",
                "number": "+32 9 123 45 67"
            }
        ],
        "website": "https://acme.com",
        "iban": "BE68 5390 0754 7034",
        "bic": "BBRUBEBB",
        "language": "nl",
        "remarks": "Important client notes",
        "added_at": "2024-01-15T10:30:00+01:00",
        "updated_at": "2024-01-20T14:15:00+01:00"
    },
    "included": {
        "addresses": [
            {
                "id": "address-uuid",
                "type": "primary",
                "line_1": "123 Business Street",
                "postal_code": "9000",
                "city": "Ghent",
                "country": "BE"
            }
        ],
        "responsible_user": [
            {
                "id": "user-uuid",
                "first_name": "John",
                "last_name": "Doe",
                "email": "john@company.com"
            }
        ],
        "tags": [
            {
                "tag": "vip"
            },
            {
                "tag": "prospect"
            }
        ]
    }
}
```

## Tag Management

### Adding Tags

```php
// Add single tag
$result = $teamleader->companies()->tag('company-uuid', 'vip');

// Add multiple tags
$result = $teamleader->companies()->tag('company-uuid', ['vip', 'prospect', 'priority']);
```

### Removing Tags

```php
// Remove single tag
$result = $teamleader->companies()->untag('company-uuid', 'old-tag');

// Remove multiple tags
$result = $teamleader->companies()->untag('company-uuid', ['old-tag', 'outdated']);
```

### Safe Tag Management

Use `manageTags()` to add and remove tags while preserving existing ones:

```php
// Add 'vip' and remove 'prospect' while keeping other existing tags
$result = $teamleader->companies()->manageTags(
    'company-uuid',
    ['vip'],           // Tags to add
    ['prospect']       // Tags to remove
);
```

## Usage Examples

### Basic CRUD Operations

```php
// Create a company
$newCompany = $teamleader->companies()->create([
    'name' => 'New Company Ltd',
    'emails' => [['type' => 'primary', 'email' => 'info@newcompany.com']],
    'website' => 'https://newcompany.com'
]);

// Get the created company ID
$companyId = $newCompany['data']['id'];

// Update the company
$updatedCompany = $teamleader->companies()->update($companyId, [
    'name' => 'New Company Limited',
    'vat_number' => 'BE0987654321'
]);

// Add tags
$teamleader->companies()->tag($companyId, ['new-client', 'priority']);

// Get company with all details
$company = $teamleader->companies()
    ->withCommonRelationships()
    ->info($companyId);

// Delete the company (if needed)
$teamleader->companies()->delete($companyId);
```

### Advanced Search and Filtering

```php
// Find all VIP companies updated in the last month
$vipCompanies = $teamleader->companies()->list([
    'tags' => ['vip'],
    'updated_since' => now()->subMonth()->toISOString()
], [
    'include' => ['responsible_user', 'tags'],
    'sort' => 'updated_at',
    'sort_order' => 'desc'
]);

// Search for companies by various criteria
$searchResults = $teamleader->companies()->search('acme', [
    'filters' => ['tags' => ['prospect']],
    'include' => ['addresses', 'responsible_user']
]);
```

### Bulk Tag Management

```php
$companyIds = ['uuid1', 'uuid2', 'uuid3'];

foreach ($companyIds as $companyId) {
    // Add 'bulk-update' tag and remove 'old-status' tag
    $teamleader->companies()->manageTags(
        $companyId,
        ['bulk-update', 'reviewed-2024'],
        ['old-status', 'pending-review']
    );
}
```

## Error Handling

The companies resource follows the standard SDK error handling patterns:

```php
$result = $teamleader->companies()->create($companyData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    $errors = $result['errors'] ?? [$errorMessage];
    
    Log::error("Company creation failed: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $errors
    ]);
    
    // Handle validation errors (422)
    if ($statusCode === 422) {
        foreach ($errors as $error) {
            // Handle individual validation errors
        }
    }
}
```

## Rate Limiting

Companies API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **Create operations**: 1 request per call
- **Update operations**: 1 request per call
- **Delete operations**: 1 request per call
- **Tag/Untag operations**: 1 request per call

Rate limit cost: **1 request per method call**

## Validation

The SDK performs client-side validation before sending requests:

- **Name**: Required for creation
- **Email**: Must be valid email format if provided
- **Website**: Must be valid URL format if provided
- **Empty values**: Automatically filtered out

## Notes

- Company names support fuzzy search when filtering
- Tags are case-sensitive
- The `term` filter searches across name, email, phone, and website fields
- When updating, only provide fields that need to be changed
- Addresses and other complex fields require full replacement, not partial updates
- Use sideloading to reduce API calls when you need related data
- Always handle errors appropriately as API operations can fail for various reasons

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class CompanyController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $companies = $teamleader->companies()
            ->withResponsibleUser()
            ->withTags()
            ->list();
        
        return view('companies.index', compact('companies'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $company = $teamleader->companies()->create([
            'name' => $request->name,
            'emails' => [['type' => 'primary', 'email' => $request->email]],
            'website' => $request->website
        ]);
        
        return redirect()->route('companies.show', $company['data']['id']);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

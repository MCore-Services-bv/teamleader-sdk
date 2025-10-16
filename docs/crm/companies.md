# Companies

Manage companies in Teamleader Focus CRM.

## Overview

The Companies resource provides full CRUD (Create, Read, Update, Delete) operations for managing company records in your Teamleader CRM. Companies are business entities that can be associated with contacts, deals, projects, and invoices.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
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

`companies`

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

Get all companies with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, sort, include)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all companies
$companies = Teamleader::companies()->list();

// Get active companies only
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);

// With pagination
$companies = Teamleader::companies()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);

// With sorting
$companies = Teamleader::companies()->list([], [
    'sort' => 'name',
    'sort_order' => 'asc'
]);
```

### `info()`

Get detailed information about a specific company.

**Parameters:**
- `id` (string): Company UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Get company information
$company = Teamleader::companies()->info('company-uuid');

// With sideloading
$company = Teamleader::companies()->info('company-uuid', 'responsible_user,addresses');

// Using fluent interface
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type')
    ->info('company-uuid');
```

### `create()`

Create a new company.

**Parameters:**
- `data` (array): Company data

**Example:**
```php
$company = Teamleader::companies()->create([
    'name' => 'Acme Corporation',
    'business_type_id' => 'business-type-uuid',
    'vat_number' => 'BE0123456789',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'info@acme.com'
        ]
    ],
    'telephones' => [
        [
            'type' => 'phone',
            'number' => '+32 2 123 45 67'
        ]
    ],
    'website' => 'https://www.acme.com',
    'addresses' => [
        [
            'type' => 'primary',
            'address' => [
                'line_1' => '123 Business Street',
                'postal_code' => '1000',
                'city' => 'Brussels',
                'country' => 'BE'
            ]
        ]
    ],
    'responsible_user_id' => 'user-uuid'
]);
```

### `update()`

Update an existing company.

**Parameters:**
- `id` (string): Company UUID
- `data` (array): Updated company data

**Example:**
```php
$company = Teamleader::companies()->update('company-uuid', [
    'name' => 'Acme Corporation Ltd',
    'website' => 'https://www.acme-corp.com',
    'responsible_user_id' => 'new-user-uuid'
]);
```

### `delete()`

Delete a company.

**Parameters:**
- `id` (string): Company UUID

**Example:**
```php
Teamleader::companies()->delete('company-uuid');
```

## Helper Methods

The Companies resource provides convenient helper methods for common operations:

### Search Methods

```php
// Search across multiple fields (name, VAT, emails, phones)
$companies = Teamleader::companies()->search('Acme');

// Search by email
$companies = Teamleader::companies()->byEmail('info@acme.com');

// Search by VAT number
$companies = Teamleader::companies()->byVatNumber('BE0123456789');

// Search by name
$companies = Teamleader::companies()->byName('Acme');
```

### Filter Methods

```php
// Get active companies
$companies = Teamleader::companies()->active();

// Get deactivated companies
$companies = Teamleader::companies()->deactivated();

// Get companies with specific tags
$companies = Teamleader::companies()->withTags(['VIP', 'Enterprise']);

// Get companies updated since a date
$companies = Teamleader::companies()->updatedSince('2024-01-01');
```

### Tagging Methods

```php
// Add tags to a company
$result = Teamleader::companies()->tag('company-uuid', ['VIP', 'Enterprise']);

// Remove tags from a company
$result = Teamleader::companies()->untag('company-uuid', ['VIP']);

// Manage tags (add and remove in one call)
$result = Teamleader::companies()->manageTags(
    'company-uuid',
    ['NewTag'],        // Tags to add
    ['OldTag']         // Tags to remove
);
```

## Filters

### Available Filters

#### `ids`
Filter by specific company UUIDs.

```php
$companies = Teamleader::companies()->list([
    'ids' => ['company-uuid-1', 'company-uuid-2']
]);
```

#### `email`
Filter by email address. Requires both type and email fields.

```php
$companies = Teamleader::companies()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'info@acme.com'
    ]
]);
```

#### `name`
Filter by company name (fuzzy search).

```php
$companies = Teamleader::companies()->list([
    'name' => 'Acme'
]);
```

#### `vat_number`
Filter by VAT number.

```php
$companies = Teamleader::companies()->list([
    'vat_number' => 'BE0123456789'
]);
```

#### `term`
Search term that searches across multiple fields (name, VAT, emails, phones).

```php
$companies = Teamleader::companies()->list([
    'term' => 'Acme'
]);
```

#### `tags`
Filter by tag names. Returns companies with ALL specified tags.

```php
$companies = Teamleader::companies()->list([
    'tags' => ['VIP', 'Enterprise']
]);
```

#### `updated_since`
Filter by last update date (ISO 8601 datetime).

```php
$companies = Teamleader::companies()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);
```

#### `status`
Filter by company status.

**Values:** `active`, `deactivated`

```php
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);
```

## Sorting

Companies can be sorted by various fields:

```php
// Sort by name
$companies = Teamleader::companies()->list([], [
    'sort' => 'name',
    'sort_order' => 'asc'
]);

// Sort by multiple fields
$companies = Teamleader::companies()->list([], [
    'sort' => ['name', 'updated_at'],
    'sort_order' => 'desc'
]);
```

## Sideloading

Load related data in a single request:

### Available Includes

- `addresses` - Company addresses
- `business_type` - Business type/legal structure
- `responsible_user` - User responsible for the company
- `added_by` - User who created the company
- `tags` - Company tags

### Usage

```php
// Single include
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');

// Multiple includes
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type,tags')
    ->info('company-uuid');

// In list() calls
$companies = Teamleader::companies()->list([], [
    'include' => 'responsible_user,addresses'
]);
```

## Response Structure

### Company Object

```php
[
    'id' => 'company-uuid',
    'name' => 'Acme Corporation',
    'business_type' => [
        'id' => 'business-type-uuid'
    ],
    'vat_number' => 'BE0123456789',
    'national_identification_number' => null,
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'info@acme.com'
        ]
    ],
    'telephones' => [
        [
            'type' => 'phone',
            'number' => '+32 2 123 45 67'
        ]
    ],
    'website' => 'https://www.acme.com',
    'addresses' => [
        [
            'type' => 'primary',
            'address' => [
                'line_1' => '123 Business Street',
                'postal_code' => '1000',
                'city' => 'Brussels',
                'country' => 'BE'
            ]
        ]
    ],
    'iban' => 'BE68539007547034',
    'bic' => 'GKCCBEBB',
    'language' => 'nl',
    'responsible_user' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ],
    'remarks' => 'Important client',
    'added_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-20T14:45:00+00:00',
    'web_url' => 'https://focus.teamleader.eu/company_detail.php?id=123',
    'status' => 'active'
]
```

## Usage Examples

### Create a Complete Company

```php
$company = Teamleader::companies()->create([
    'name' => 'Tech Solutions BV',
    'business_type_id' => 'business-type-uuid',
    'vat_number' => 'BE0987654321',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'contact@techsolutions.be'
        ],
        [
            'type' => 'invoicing',
            'email' => 'invoices@techsolutions.be'
        ]
    ],
    'telephones' => [
        [
            'type' => 'phone',
            'number' => '+32 3 456 78 90'
        ],
        [
            'type' => 'mobile',
            'number' => '+32 475 12 34 56'
        ]
    ],
    'website' => 'https://www.techsolutions.be',
    'addresses' => [
        [
            'type' => 'primary',
            'address' => [
                'line_1' => 'Innovation Street 45',
                'postal_code' => '2000',
                'city' => 'Antwerp',
                'country' => 'BE'
            ]
        ],
        [
            'type' => 'invoicing',
            'address' => [
                'line_1' => 'PO Box 123',
                'postal_code' => '2000',
                'city' => 'Antwerp',
                'country' => 'BE'
            ]
        ]
    ],
    'iban' => 'BE71096123456769',
    'bic' => 'GKCCBEBB',
    'language' => 'nl',
    'responsible_user_id' => 'user-uuid',
    'remarks' => 'Key technology partner',
    'tags' => ['Technology', 'Partner']
]);
```

### Search and Filter Companies

```php
// Find companies by email
$companies = Teamleader::companies()->byEmail('info@example.com');

// Find companies by VAT
$companies = Teamleader::companies()->byVatNumber('BE0123456789');

// Search across multiple fields
$companies = Teamleader::companies()->search('Tech Solutions');

// Get active companies with specific tags
$companies = Teamleader::companies()
    ->active()
    ->withTags(['VIP']);

// Get recently updated companies
$companies = Teamleader::companies()->updatedSince('2024-01-01');
```

### Update Company Information

```php
// Update basic information
Teamleader::companies()->update('company-uuid', [
    'name' => 'Tech Solutions International BV',
    'website' => 'https://www.techsolutions.international'
]);

// Change responsible user
Teamleader::companies()->update('company-uuid', [
    'responsible_user_id' => 'new-user-uuid'
]);

// Add new email
Teamleader::companies()->update('company-uuid', [
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'info@techsolutions.be'
        ],
        [
            'type' => 'support',
            'email' => 'support@techsolutions.be'
        ]
    ]
]);
```

### Work with Tags

```php
// Add tags
Teamleader::companies()->tag('company-uuid', ['Premium', 'Partner']);

// Remove tags
Teamleader::companies()->untag('company-uuid', ['Trial']);

// Add and remove tags in one operation
Teamleader::companies()->manageTags(
    'company-uuid',
    ['Active', 'Paid'],      // Add these
    ['Trial', 'Prospect']    // Remove these
);
```

## Common Use Cases

### 1. Synchronize Companies from External System

```php
function syncCompanies(array $externalCompanies)
{
    foreach ($externalCompanies as $externalCompany) {
        // Check if company exists by VAT number
        $existing = Teamleader::companies()->byVatNumber($externalCompany['vat']);
        
        if (empty($existing['data'])) {
            // Create new company
            Teamleader::companies()->create([
                'name' => $externalCompany['name'],
                'vat_number' => $externalCompany['vat'],
                'emails' => [
                    ['type' => 'primary', 'email' => $externalCompany['email']]
                ]
            ]);
        } else {
            // Update existing company
            Teamleader::companies()->update($existing['data'][0]['id'], [
                'name' => $externalCompany['name']
            ]);
        }
    }
}
```

### 2. Generate Company Report

```php
function generateCompanyReport()
{
    $allCompanies = [];
    $page = 1;
    
    do {
        $response = Teamleader::companies()
            ->with('responsible_user,tags')
            ->list(['status' => 'active'], [
                'page_size' => 100,
                'page_number' => $page,
                'sort' => 'name'
            ]);
        
        $allCompanies = array_merge($allCompanies, $response['data']);
        $page++;
    } while (!empty($response['data']) && count($response['data']) === 100);
    
    return $allCompanies;
}
```

### 3. Find Companies Without Responsible User

```php
$companies = Teamleader::companies()
    ->with('responsible_user')
    ->list(['status' => 'active']);

$unassigned = array_filter($companies['data'], function($company) {
    return empty($company['responsible_user']);
});

foreach ($unassigned as $company) {
    echo "Company '{$company['name']}' has no responsible user\n";
}
```

### 4. Bulk Tag Companies

```php
function bulkTagCompanies(array $companyIds, array $tags)
{
    $results = [];
    
    foreach ($companyIds as $companyId) {
        try {
            $result = Teamleader::companies()->tag($companyId, $tags);
            $results[$companyId] = ['success' => true];
        } catch (\Exception $e) {
            $results[$companyId] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $results;
}
```

## Best Practices

### 1. Use Sideloading for Related Data

```php
// Good: One API call
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type')
    ->info('company-uuid');

// Bad: Multiple API calls
$company = Teamleader::companies()->info('company-uuid');
$user = Teamleader::users()->info($company['data']['responsible_user']['id']);
```

### 2. Search Efficiently

```php
// Good: Use specific search methods
$company = Teamleader::companies()->byVatNumber('BE0123456789');

// Less efficient: Search by term
$companies = Teamleader::companies()->search('BE0123456789');
```

### 3. Handle Pagination for Large Datasets

```php
function getAllActiveCompanies()
{
    $allCompanies = [];
    $page = 1;
    
    do {
        $response = Teamleader::companies()->list(
            ['status' => 'active'],
            ['page_size' => 100, 'page_number' => $page]
        );
        
        $allCompanies = array_merge($allCompanies, $response['data']);
        $page++;
    } while (!empty($response['data']) && count($response['data']) === 100);
    
    return $allCompanies;
}
```

### 4. Validate Data Before Creating

```php
function createCompanySafely(array $data)
{
    // Check if company already exists
    if (!empty($data['vat_number'])) {
        $existing = Teamleader::companies()->byVatNumber($data['vat_number']);
        if (!empty($existing['data'])) {
            throw new \Exception('Company with this VAT number already exists');
        }
    }
    
    // Validate required fields
    if (empty($data['name'])) {
        throw new \Exception('Company name is required');
    }
    
    return Teamleader::companies()->create($data);
}
```

### 5. Use Tags for Organization

```php
// Organize companies by tier
$premiumCompanies = Teamleader::companies()->withTags(['Premium']);
$standardCompanies = Teamleader::companies()->withTags(['Standard']);

// Organize by industry
$techCompanies = Teamleader::companies()->withTags(['Technology']);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $company = Teamleader::companies()->create([
        'name' => 'New Company'
    ]);
} catch (TeamleaderException $e) {
    Log::error('Error creating company', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Handle specific error cases
    if ($e->getCode() === 422) {
        // Validation error
        return response()->json([
            'error' => 'Invalid company data provided'
        ], 422);
    }
}
```

## Related Resources

- [Contacts](contacts.md) - Link contacts to companies
- [Business Types](business_types.md) - Company legal structures
- [Tags](tags.md) - Organize companies with tags
- [Addresses](addresses.md) - Geographical area information
- [Deals](../deals/deals.md) - Create deals for companies
- [Invoices](../invoicing/invoices.md) - Invoice companies
- [Users](../general/users.md) - Assign responsible users

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
- [Sideloading](../sideloading.md) - Efficiently load related data

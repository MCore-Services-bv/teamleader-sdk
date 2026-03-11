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
    - [uploadLogo()](#uploadlogo)
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

### `uploadLogo()`

Upload or remove the logo of a company. The image must be provided as a base64-encoded data URI. Pass `null` to remove an existing logo.

Returns a 204 No Content response on success (empty array).

**Parameters:**
- `id` (string): Company UUID
- `image` (string|null): Base64 data URI (e.g. `data:image/png;base64,...`) or `null` to remove the logo

**Example:**
```php
// Upload a logo from a file on disk
$imageData = base64_encode(file_get_contents('/path/to/logo.png'));
$dataUri = 'data:image/png;base64,' . $imageData;

Teamleader::companies()->uploadLogo('company-uuid', $dataUri);

// Remove an existing logo
Teamleader::companies()->uploadLogo('company-uuid', null);
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

// Search by national identification number
$companies = Teamleader::companies()->byNationalIdentificationNumber('63326426');

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
Filter by email address. Requires both type and email fields. Only `primary` is accepted as type.

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

#### `national_identification_number`
Filter by national identification number.

```php
$companies = Teamleader::companies()->list([
    'national_identification_number' => '63326426'
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

#### `marketing_mails_consent`
Filter by marketing mails consent status.

```php
// Companies that have given consent
$companies = Teamleader::companies()->list([
    'marketing_mails_consent' => true
]);

// Companies that have not given consent
$companies = Teamleader::companies()->list([
    'marketing_mails_consent' => false
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

**Available sort fields:** `name`, `added_at`, `updated_at`

## Sideloading

Load related data in a single request:

### Available Includes

| Include | Description |
|---|---|
| `addresses` | Company addresses |
| `business_type` | Business type / legal structure |
| `responsible_user` | User responsible for the company |
| `added_by` | User who created the company |
| `tags` | Company tags |
| `custom_fields` | Custom field values (requires `includes=custom_fields`) |
| `price_list` | Assigned price list (requires `includes=price_list`) |

### Usage

```php
// Single include
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');

// Multiple includes via fluent interface
$company = Teamleader::companies()
    ->withResponsibleUser()
    ->withAddresses()
    ->withCustomFields()
    ->info('company-uuid');

// Include custom fields
$company = Teamleader::companies()
    ->withCustomFields()
    ->info('company-uuid');

// Include price list
$company = Teamleader::companies()
    ->withPriceList()
    ->info('company-uuid');

// Include in list()
$companies = Teamleader::companies()->list([], [
    'include' => 'responsible_user,addresses,custom_fields'
]);
```

### Fluent Include Methods

| Method | Include |
|---|---|
| `->withAddresses()` | `addresses` |
| `->withBusinessType()` | `business_type` |
| `->withResponsibleUser()` | `responsible_user` |
| `->withAddedBy()` | `added_by` |
| `->withCustomFields()` | `custom_fields` |
| `->withPriceList()` | `price_list` |
| `->withCommonRelationships()` | `addresses`, `responsible_user`, `business_type`, `tags` |

## Response Structure

A typical company response includes:

```php
[
    'data' => [
        [
            'id' => '96a38bbf-24ed-4083-8a5c-20db92aa471e',
            'name' => 'Pied Piper',
            'status' => 'active',
            'business_type' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'businessType'
            ],
            'vat_number' => 'BE0899623035',
            'national_identification_number' => '63326426',
            'emails' => [
                ['type' => 'primary', 'email' => 'info@piedpiper.eu']
            ],
            'telephones' => [
                ['type' => 'phone', 'number' => '092980615']
            ],
            'website' => 'https://piedpiper.com',
            'primary_address' => [...],
            'iban' => 'BE12123412341234',
            'bic' => 'BICBANK',
            'language' => 'nl',
            'preferred_currency' => 'EUR',
            'payment_term' => [
                'type' => 'after_invoice_date',
                'days' => 30
            ],
            'invoicing_preferences' => [
                'electronic_invoicing_address' => null
            ],
            'responsible_user' => [
                'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                'type' => 'user'
            ],
            'added_at' => '2016-02-04T16:44:33+00:00',
            'updated_at' => '2016-02-05T16:44:33+00:00',
            'web_url' => 'https://focus.teamleader.eu/company_detail.php?id=...',
            'tags' => ['expo', 'lead'],
            'marketing_mails_consent' => false,
            // Only with includes=custom_fields:
            'custom_fields' => [...],
            // Only with includes=price_list:
            'price_list' => [
                'type' => 'priceList',
                'id' => '27261187-19c9-081f-b833-021fa5873129'
            ]
        ]
    ]
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
        ['type' => 'primary', 'email' => 'contact@techsolutions.be'],
        ['type' => 'invoicing', 'email' => 'invoices@techsolutions.be']
    ],
    'telephones' => [
        ['type' => 'phone', 'number' => '+32 3 456 78 90']
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
        ]
    ],
    'iban' => 'BE71096123456769',
    'bic' => 'GKCCBEBB',
    'language' => 'nl',
    'responsible_user_id' => 'user-uuid',
    'tags' => ['Technology', 'Partner']
]);
```

### Search and Filter Companies

```php
// Find companies by email
$companies = Teamleader::companies()->byEmail('info@example.com');

// Find companies by VAT
$companies = Teamleader::companies()->byVatNumber('BE0123456789');

// Find by national identification number
$companies = Teamleader::companies()->byNationalIdentificationNumber('63326426');

// Search across multiple fields
$companies = Teamleader::companies()->search('Tech Solutions');

// Get active companies with specific tags
$companies = Teamleader::companies()->list([
    'status' => 'active',
    'tags' => ['VIP']
]);

// Get recently updated companies
$companies = Teamleader::companies()->updatedSince('2024-01-01');

// Get companies with marketing consent
$companies = Teamleader::companies()->list([
    'marketing_mails_consent' => true
]);
```

### Upload a Company Logo

```php
// Upload from a file on disk
$imageData = base64_encode(file_get_contents(storage_path('logos/acme.png')));
Teamleader::companies()->uploadLogo('company-uuid', 'data:image/png;base64,' . $imageData);

// Upload from a Laravel uploaded file
$file = $request->file('logo');
$imageData = base64_encode(file_get_contents($file->getRealPath()));
$mimeType = $file->getMimeType();
Teamleader::companies()->uploadLogo('company-uuid', "data:{$mimeType};base64,{$imageData}");

// Remove existing logo
Teamleader::companies()->uploadLogo('company-uuid', null);
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

### Load Custom Fields and Price List

```php
// Get a company with custom fields
$company = Teamleader::companies()
    ->withCustomFields()
    ->info('company-uuid');

foreach ($company['data']['custom_fields'] as $field) {
    echo $field['definition']['id'] . ': ' . $field['value'] . "\n";
}

// Get a company's assigned price list
$company = Teamleader::companies()
    ->withPriceList()
    ->info('company-uuid');

$priceListId = $company['data']['price_list']['id'] ?? null;
```

## Common Use Cases

### 1. Synchronize Companies from External System

```php
function syncCompanies(array $externalCompanies)
{
    foreach ($externalCompanies as $externalCompany) {
        $existing = Teamleader::companies()->byVatNumber($externalCompany['vat']);

        if (empty($existing['data'])) {
            Teamleader::companies()->create([
                'name' => $externalCompany['name'],
                'vat_number' => $externalCompany['vat'],
                'emails' => [
                    ['type' => 'primary', 'email' => $externalCompany['email']]
                ]
            ]);
        } else {
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
    ->withResponsibleUser()
    ->list(['status' => 'active']);

$unassigned = array_filter($companies['data'], function ($company) {
    return empty($company['responsible_user']);
});
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

### 4. Use `null` to Remove a Logo

When removing a logo, always pass `null` explicitly rather than an empty string:

```php
// Correct
Teamleader::companies()->uploadLogo('company-uuid', null);

// Wrong — will throw InvalidArgumentException
Teamleader::companies()->uploadLogo('company-uuid', '');
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

    if ($e->getCode() === 422) {
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

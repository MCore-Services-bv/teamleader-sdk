# Contacts

Manage contacts in Teamleader Focus CRM.

## Overview

The Contacts resource provides full CRUD (Create, Read, Update, Delete) operations for managing contact records in your Teamleader CRM. Contacts are individual people that can be linked to companies, deals, projects, and other entities.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [uploadAvatar()](#uploadavatar)
- [Helper Methods](#helper-methods)
- [Company Linking Methods](#company-linking-methods)
- [Tagging Methods](#tagging-methods)
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

`contacts`

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

Get all contacts with optional filtering, sorting, and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number, sort, include)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all contacts
$contacts = Teamleader::contacts()->list();

// Get active contacts only
$contacts = Teamleader::contacts()->list([
    'status' => 'active'
]);

// With pagination
$contacts = Teamleader::contacts()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

### `info()`

Get detailed information about a specific contact.

**Parameters:**
- `id` (string): Contact UUID
- `includes` (string|array): Optional sideloaded relationships

**Example:**
```php
// Get contact information
$contact = Teamleader::contacts()->info('contact-uuid');

// With custom fields
$contact = Teamleader::contacts()->info('contact-uuid', 'custom_fields');

// Using fluent interface
$contact = Teamleader::contacts()
    ->with('custom_fields')
    ->info('contact-uuid');
```

### `create()`

Create a new contact.

**Parameters:**
- `data` (array): Contact data

**Example:**
```php
$contact = Teamleader::contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'john.doe@example.com'
        ]
    ],
    'telephones' => [
        [
            'type' => 'mobile',
            'number' => '+32 475 12 34 56'
        ]
    ],
    'website' => 'https://www.johndoe.com',
    'addresses' => [
        [
            'type' => 'primary',
            'address' => [
                'line_1' => '123 Main Street',
                'postal_code' => '1000',
                'city' => 'Brussels',
                'country' => 'BE'
            ]
        ]
    ],
    'language' => 'en',
    'gender' => 'male'
]);
```

### `update()`

Update an existing contact.

**Parameters:**
- `id` (string): Contact UUID
- `data` (array): Updated contact data

**Example:**
```php
$contact = Teamleader::contacts()->update('contact-uuid', [
    'first_name' => 'John',
    'last_name' => 'Smith',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'john.smith@example.com'
        ]
    ]
]);
```

### `delete()`

Delete a contact.

**Parameters:**
- `id` (string): Contact UUID

**Example:**
```php
Teamleader::contacts()->delete('contact-uuid');
```

### `uploadAvatar()`

Upload or remove the avatar of a contact. The image must be provided as a base64-encoded data URI. Pass `null` to remove an existing avatar.

Returns a 204 No Content response on success (empty array).

**Parameters:**
- `id` (string): Contact UUID
- `image` (string|null): Base64 data URI (e.g. `data:image/png;base64,...`) or `null` to remove the avatar

**Example:**
```php
// Upload an avatar from a file on disk
$imageData = base64_encode(file_get_contents('/path/to/avatar.png'));
$dataUri = 'data:image/png;base64,' . $imageData;

Teamleader::contacts()->uploadAvatar('contact-uuid', $dataUri);

// Upload from a Laravel uploaded file
$file = $request->file('avatar');
$imageData = base64_encode(file_get_contents($file->getRealPath()));
$mimeType = $file->getMimeType();
Teamleader::contacts()->uploadAvatar('contact-uuid', "data:{$mimeType};base64,{$imageData}");

// Remove an existing avatar
Teamleader::contacts()->uploadAvatar('contact-uuid', null);
```

## Helper Methods

The Contacts resource provides convenient helper methods for common operations:

### Search Methods

```php
// Search across multiple fields (first_name, last_name, email, telephone)
$contacts = Teamleader::contacts()->search('John');

// Search by email
$contacts = Teamleader::contacts()->byEmail('john.doe@example.com');
```

### Filter Methods

```php
// Get contacts for a specific company
$contacts = Teamleader::contacts()->forCompany('company-uuid');

// Get active contacts
$contacts = Teamleader::contacts()->active();

// Get deactivated contacts
$contacts = Teamleader::contacts()->deactivated();

// Get contacts with specific tags
$contacts = Teamleader::contacts()->withTags(['VIP', 'Decision Maker']);

// Get contacts updated since a date
$contacts = Teamleader::contacts()->updatedSince('2024-01-01');
```

## Company Linking Methods

### Link Contact to Company

```php
// Basic linking
Teamleader::contacts()->linkToCompany('contact-uuid', 'company-uuid');

// Link with position and decision maker status
Teamleader::contacts()->linkToCompany('contact-uuid', 'company-uuid', [
    'position' => 'CEO',
    'decision_maker' => true
]);
```

### Unlink Contact from Company

```php
Teamleader::contacts()->unlinkFromCompany('contact-uuid', 'company-uuid');
```

### Update Company Link

```php
Teamleader::contacts()->updateCompanyLink('contact-uuid', 'company-uuid', [
    'position' => 'Managing Director',
    'decision_maker' => true
]);
```

## Tagging Methods

```php
// Add tags to a contact
Teamleader::contacts()->tag('contact-uuid', ['VIP', 'Decision Maker']);

// Remove tags from a contact
Teamleader::contacts()->untag('contact-uuid', ['Prospect']);

// Manage tags (add and remove in one call)
Teamleader::contacts()->manageTags(
    'contact-uuid',
    ['Active', 'Customer'],    // Tags to add
    ['Lead', 'Prospect']       // Tags to remove
);
```

## Filters

### Available Filters

#### `ids`
Filter by specific contact UUIDs.

```php
$contacts = Teamleader::contacts()->list([
    'ids' => ['contact-uuid-1', 'contact-uuid-2']
]);
```

#### `email`
Filter by email address. Requires both type and email fields. Only `primary` is accepted as type.

```php
$contacts = Teamleader::contacts()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'john.doe@example.com'
    ]
]);
```

#### `company_id`
Filter by company UUID to get all contacts linked to that company.

```php
$contacts = Teamleader::contacts()->list([
    'company_id' => 'company-uuid'
]);
```

#### `term`
Search term that searches across first_name, last_name, email, and telephone.

```php
$contacts = Teamleader::contacts()->list([
    'term' => 'John Doe'
]);
```

#### `updated_since`
Filter by last update date (ISO 8601 datetime).

```php
$contacts = Teamleader::contacts()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);
```

#### `tags`
Filter by tag names. Returns contacts with ALL specified tags.

```php
$contacts = Teamleader::contacts()->list([
    'tags' => ['VIP', 'Decision Maker']
]);
```

#### `status`
Filter by contact status.

**Values:** `active`, `deactivated`

```php
$contacts = Teamleader::contacts()->list([
    'status' => 'active'
]);
```

#### `marketing_mails_consent`
Filter by marketing mails consent status.

```php
// Contacts that have given consent
$contacts = Teamleader::contacts()->list([
    'marketing_mails_consent' => true
]);

// Contacts that have not given consent
$contacts = Teamleader::contacts()->list([
    'marketing_mails_consent' => false
]);
```

## Sorting

Contacts can be sorted by the following fields:

```php
$contacts = Teamleader::contacts()->list([], [
    'sort' => 'name',
    'sort_order' => 'asc'
]);
```

**Available sort fields:** `name`, `added_at`, `updated_at`

## Sideloading

Load related data in a single request:

### Available Includes

| Include | Description |
|---|---|
| `custom_fields` | Custom field values (requires `includes=custom_fields`) |
| `price_list` | Assigned price list (requires `includes=price_list`) |

### Usage

```php
// Include custom fields
$contact = Teamleader::contacts()
    ->withCustomFields()
    ->info('contact-uuid');

// Include price list
$contact = Teamleader::contacts()
    ->withPriceList()
    ->info('contact-uuid');

// Both at once
$contact = Teamleader::contacts()
    ->with('custom_fields,price_list')
    ->info('contact-uuid');

// Include in list()
$contacts = Teamleader::contacts()->list([], [
    'include' => 'custom_fields,price_list'
]);
```

### Fluent Include Methods

| Method | Include |
|---|---|
| `->withCustomFields()` | `custom_fields` |
| `->withPriceList()` | `price_list` |

## Response Structure

A typical contact response includes:

```php
[
    'data' => [
        [
            'id' => '2a39e420-3ba3-4384-8024-fa702ef99c9f',
            'first_name' => 'Erlich',
            'last_name' => 'Bachman',
            'status' => 'active',
            'salutation' => 'Mr',
            'emails' => [
                ['type' => 'primary', 'email' => 'info@piedpiper.eu']
            ],
            'telephones' => [
                ['type' => 'phone', 'number' => '092980615']
            ],
            'website' => 'https://piedpiper.com',
            'primary_address' => [
                'line_1' => 'Dok Noord 3A 101',
                'postal_code' => '9000',
                'city' => 'Ghent',
                'country' => 'BE',
                'area_level_two' => null
            ],
            'gender' => 'male',
            'birthdate' => '1987-04-25',
            'iban' => 'BE12123412341234',
            'bic' => 'BICBANK',
            'national_identification_number' => '86792345-L',
            'language' => 'en',
            'payment_term' => [
                'type' => 'after_invoice_date',
                'days' => 30
            ],
            'invoicing_preferences' => [
                'electronic_invoicing_address' => null
            ],
            'tags' => ['vip', 'decision-maker'],
            'added_at' => '2016-02-04T16:44:33+00:00',
            'updated_at' => '2016-02-05T16:44:33+00:00',
            'web_url' => 'https://focus.teamleader.eu/contact_detail.php?id=...',
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

### Create a Complete Contact

```php
$contact = Teamleader::contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'emails' => [
        ['type' => 'primary', 'email' => 'john.doe@example.com']
    ],
    'telephones' => [
        ['type' => 'mobile', 'number' => '+32 475 12 34 56']
    ],
    'website' => 'https://www.johndoe.com',
    'language' => 'en',
    'gender' => 'male',
    'responsible_user_id' => 'user-uuid'
]);
```

### Upload a Contact Avatar

```php
// Upload from a file on disk
$imageData = base64_encode(file_get_contents(storage_path('avatars/john.png')));
Teamleader::contacts()->uploadAvatar('contact-uuid', 'data:image/png;base64,' . $imageData);

// Upload from a Laravel uploaded file
$file = $request->file('avatar');
$imageData = base64_encode(file_get_contents($file->getRealPath()));
$mimeType = $file->getMimeType();
Teamleader::contacts()->uploadAvatar('contact-uuid', "data:{$mimeType};base64,{$imageData}");

// Remove existing avatar
Teamleader::contacts()->uploadAvatar('contact-uuid', null);
```

### Search and Filter Contacts

```php
// Find contacts by email
$contacts = Teamleader::contacts()->byEmail('john.doe@example.com');

// Search across multiple fields
$contacts = Teamleader::contacts()->search('John');

// Get contacts for a company
$contacts = Teamleader::contacts()->forCompany('company-uuid');

// Get active contacts with specific tags
$contacts = Teamleader::contacts()->list([
    'status' => 'active',
    'tags' => ['VIP']
]);

// Get contacts with marketing consent
$contacts = Teamleader::contacts()->list([
    'marketing_mails_consent' => true
]);
```

### Load Price List

```php
$contact = Teamleader::contacts()
    ->withPriceList()
    ->info('contact-uuid');

$priceListId = $contact['data']['price_list']['id'] ?? null;
```

### Find and Update a Contact

```php
$contacts = Teamleader::contacts()->byEmail('john@example.com');

if (!empty($contacts['data'])) {
    $contactId = $contacts['data'][0]['id'];

    Teamleader::contacts()->update($contactId, [
        'telephones' => [
            [
                'type' => 'mobile',
                'number' => '+32 475 99 88 77'
            ]
        ]
    ]);
}
```

### Work with Tags

```php
// Add tags to categorize contact
Teamleader::contacts()->tag('contact-uuid', ['Premium', 'Newsletter']);

// Remove old tags
Teamleader::contacts()->untag('contact-uuid', ['Trial']);

// Bulk tag management
Teamleader::contacts()->manageTags(
    'contact-uuid',
    ['Active', 'Paid Customer'],     // Add
    ['Lead', 'Free Trial']           // Remove
);
```

### Get Company Contacts

```php
// Get all contacts for a company
$contacts = Teamleader::contacts()->forCompany('company-uuid');

// Find decision makers
$decisionMakers = array_filter($contacts['data'], function ($contact) {
    foreach ($contact['companies'] as $company) {
        if ($company['decision_maker'] === true) {
            return true;
        }
    }
    return false;
});
```

## Common Use Cases

### 1. Import Contacts from CSV

```php
function importContactsFromCSV($csvPath)
{
    $csv = array_map('str_getcsv', file($csvPath));
    $headers = array_shift($csv);
    $results = ['success' => [], 'errors' => []];

    foreach ($csv as $row) {
        $data = array_combine($headers, $row);

        try {
            $existing = Teamleader::contacts()->byEmail($data['email']);

            if (empty($existing['data'])) {
                Teamleader::contacts()->create([
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'emails'     => [
                        ['type' => 'primary', 'email' => $data['email']]
                    ]
                ]);
                $results['success'][] = $data['email'];
            }
        } catch (\Exception $e) {
            $results['errors'][] = [
                'email' => $data['email'],
                'error' => $e->getMessage()
            ];
        }
    }

    return $results;
}
```

### 2. Find Contacts Without Companies

```php
$allContacts = Teamleader::contacts()->list(['status' => 'active']);

$unlinkedContacts = array_filter($allContacts['data'], function ($contact) {
    return empty($contact['companies']);
});
```

### 3. Bulk Update Contact Tags

```php
function bulkUpdateContactTags(array $contactIds, array $tags)
{
    $results = [];

    foreach ($contactIds as $contactId) {
        try {
            Teamleader::contacts()->tag($contactId, $tags);
            $results[$contactId] = ['success' => true];
        } catch (\Exception $e) {
            $results[$contactId] = [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    return $results;
}
```

### 4. Generate Contact Report

```php
function generateContactReport($companyId = null)
{
    $filters = ['status' => 'active'];

    if ($companyId) {
        $filters['company_id'] = $companyId;
    }

    $allContacts = [];
    $page = 1;

    do {
        $response = Teamleader::contacts()->list($filters, [
            'page_size'   => 100,
            'page_number' => $page,
            'sort'        => 'name'
        ]);

        $allContacts = array_merge($allContacts, $response['data']);
        $page++;
    } while (!empty($response['data']) && count($response['data']) === 100);

    return $allContacts;
}
```

### 5. Sync Contact Company Links

```php
function syncContactToCompany($contactId, $companyId, $position)
{
    $contact = Teamleader::contacts()->info($contactId);

    $isLinked = false;
    foreach ($contact['data']['companies'] ?? [] as $company) {
        if ($company['customer']['id'] === $companyId) {
            $isLinked = true;
            break;
        }
    }

    if ($isLinked) {
        return Teamleader::contacts()->updateCompanyLink(
            $contactId,
            $companyId,
            ['position' => $position]
        );
    } else {
        return Teamleader::contacts()->linkToCompany(
            $contactId,
            $companyId,
            ['position' => $position]
        );
    }
}
```

## Best Practices

### 1. Always Check for Existing Contacts

```php
$existing = Teamleader::contacts()->byEmail('john@example.com');

if (empty($existing['data'])) {
    $contact = Teamleader::contacts()->create([
        'first_name' => 'John',
        'last_name'  => 'Doe',
        'emails'     => [['type' => 'primary', 'email' => 'john@example.com']]
    ]);
} else {
    $contactId = $existing['data'][0]['id'];
    Teamleader::contacts()->update($contactId, $updateData);
}
```

### 2. Use `null` to Remove an Avatar

When removing an avatar, always pass `null` explicitly rather than an empty string:

```php
// Correct
Teamleader::contacts()->uploadAvatar('contact-uuid', null);

// Wrong — will throw InvalidArgumentException
Teamleader::contacts()->uploadAvatar('contact-uuid', '');
```

### 3. Handle Company Links Properly

```php
$contact = Teamleader::contacts()->info('contact-uuid');

$hasCompanyLink = false;
foreach ($contact['data']['companies'] ?? [] as $company) {
    if ($company['customer']['id'] === 'company-uuid') {
        $hasCompanyLink = true;
        break;
    }
}

if ($hasCompanyLink) {
    Teamleader::contacts()->updateCompanyLink('contact-uuid', 'company-uuid', [
        'position' => 'New Position'
    ]);
} else {
    Teamleader::contacts()->linkToCompany('contact-uuid', 'company-uuid', [
        'position' => 'New Position'
    ]);
}
```

### 4. Use Tags for Segmentation

```php
Teamleader::contacts()->tag('contact-uuid', ['Decision Maker']);

$decisionMakers = Teamleader::contacts()->withTags(['Decision Maker']);
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $contact = Teamleader::contacts()->create([
        'first_name' => 'John',
        'last_name'  => 'Doe'
    ]);
} catch (TeamleaderException $e) {
    Log::error('Error creating contact', [
        'error' => $e->getMessage(),
        'code'  => $e->getCode()
    ]);

    if ($e->getCode() === 422) {
        return response()->json([
            'error' => 'Contact must have at least one email address'
        ], 422);
    }
}
```

## Related Resources

- [Companies](companies.md) - Link contacts to companies
- [Tags](tags.md) - Organize contacts with tags
- [Custom Fields](../general/custom_fields.md) - Add custom data to contacts
- [Deals](../deals/deals.md) - Create deals for contacts
- [Notes](../general/notes.md) - Add notes to contacts
- [Users](../general/users.md) - Responsible users

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques
- [Sideloading](../sideloading.md) - Efficiently load related data

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
Filter by email address. Requires both type and email fields.

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

## Sorting

Contacts can be sorted by various fields:

```php
// Sort by last name
$contacts = Teamleader::contacts()->list([], [
    'sort' => 'last_name',
    'sort_order' => 'asc'
]);

// Sort by multiple fields
$contacts = Teamleader::contacts()->list([], [
    'sort' => ['last_name', 'first_name'],
    'sort_order' => 'asc'
]);
```

## Sideloading

Load related data in a single request:

### Available Includes

- `custom_fields` - Custom field values for the contact

### Usage

```php
// With custom fields
$contact = Teamleader::contacts()
    ->with('custom_fields')
    ->info('contact-uuid');

// In list() calls
$contacts = Teamleader::contacts()->list([], [
    'include' => 'custom_fields'
]);
```

## Response Structure

### Contact Object

```php
[
    'id' => 'contact-uuid',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'salutation' => 'Mr.',
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
    'gender' => 'male',
    'birthdate' => '1985-05-15',
    'iban' => 'BE68539007547034',
    'bic' => 'GKCCBEBB',
    'national_identification_number' => null,
    'companies' => [
        [
            'customer' => [
                'type' => 'company',
                'id' => 'company-uuid'
            ],
            'position' => 'CEO',
            'decision_maker' => true
        ]
    ],
    'remarks' => 'Important contact',
    'tags' => ['VIP', 'Decision Maker'],
    'custom_fields' => [
        [
            'id' => 'custom-field-uuid',
            'value' => 'Custom Value'
        ]
    ],
    'marketing_mails_consent' => true,
    'added_at' => '2024-01-15T10:30:00+00:00',
    'updated_at' => '2024-01-20T14:45:00+00:00',
    'web_url' => 'https://focus.teamleader.eu/contact_detail.php?id=123',
    'status' => 'active'
]
```

## Usage Examples

### Create a Complete Contact

```php
$contact = Teamleader::contacts()->create([
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'salutation' => 'Ms.',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'jane.smith@example.com'
        ],
        [
            'type' => 'work',
            'email' => 'j.smith@company.com'
        ]
    ],
    'telephones' => [
        [
            'type' => 'mobile',
            'number' => '+32 475 11 22 33'
        ],
        [
            'type' => 'phone',
            'number' => '+32 2 123 45 67'
        ]
    ],
    'website' => 'https://www.janesmith.com',
    'addresses' => [
        [
            'type' => 'primary',
            'address' => [
                'line_1' => '456 Business Avenue',
                'line_2' => 'Suite 200',
                'postal_code' => '2000',
                'city' => 'Antwerp',
                'country' => 'BE'
            ]
        ]
    ],
    'language' => 'en',
    'gender' => 'female',
    'birthdate' => '1990-03-20',
    'remarks' => 'Key decision maker',
    'tags' => ['VIP', 'Decision Maker'],
    'marketing_mails_consent' => true
]);
```

### Link Contact to Company with Details

```php
// Create or get contact
$contact = Teamleader::contacts()->create([
    'first_name' => 'Michael',
    'last_name' => 'Johnson',
    'emails' => [
        ['type' => 'primary', 'email' => 'mjohnson@techcorp.com']
    ]
]);

// Link to company with position
Teamleader::contacts()->linkToCompany(
    $contact['data']['id'],
    'company-uuid',
    [
        'position' => 'Chief Technology Officer',
        'decision_maker' => true
    ]
);
```

### Search and Update Contact

```php
// Find contact by email
$contacts = Teamleader::contacts()->byEmail('john.doe@example.com');

if (!empty($contacts['data'])) {
    $contactId = $contacts['data'][0]['id'];
    
    // Update contact
    Teamleader::contacts()->update($contactId, [
        'telephones' => [
            [
                'type' => 'mobile',
                'number' => '+32 475 99 88 77'
            ]
        ],
        'remarks' => 'Updated mobile number on ' . date('Y-m-d')
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
$decisionMakers = array_filter($contacts['data'], function($contact) {
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
            // Check if contact exists
            $existing = Teamleader::contacts()->byEmail($data['email']);
            
            if (empty($existing['data'])) {
                // Create new contact
                $contact = Teamleader::contacts()->create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'emails' => [
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

$unlinkedContacts = array_filter($allContacts['data'], function($contact) {
    return empty($contact['companies']);
});

foreach ($unlinkedContacts as $contact) {
    echo "{$contact['first_name']} {$contact['last_name']} is not linked to any company\n";
}
```

### 3. Update Multiple Contacts

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
                'error' => $e->getMessage()
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
            'page_size' => 100,
            'page_number' => $page,
            'sort' => 'last_name'
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
    // Get current contact data
    $contact = Teamleader::contacts()->info($contactId);
    
    $isLinked = false;
    foreach ($contact['data']['companies'] ?? [] as $company) {
        if ($company['customer']['id'] === $companyId) {
            $isLinked = true;
            break;
        }
    }
    
    if ($isLinked) {
        // Update existing link
        return Teamleader::contacts()->updateCompanyLink(
            $contactId,
            $companyId,
            ['position' => $position]
        );
    } else {
        // Create new link
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
// Good: Check before creating
$existing = Teamleader::contacts()->byEmail('john@example.com');

if (empty($existing['data'])) {
    $contact = Teamleader::contacts()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'emails' => [['type' => 'primary', 'email' => 'john@example.com']]
    ]);
} else {
    // Update existing contact
    $contactId = $existing['data'][0]['id'];
    Teamleader::contacts()->update($contactId, $updateData);
}
```

### 2. Use Custom Fields for Additional Data

```php
// Get custom fields first
$customFields = Teamleader::customFields()->forContacts();

// Create contact with custom fields
$contact = Teamleader::contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'custom_fields' => [
        [
            'id' => $customFields['data'][0]['id'],
            'value' => 'Custom Value'
        ]
    ]
]);
```

### 3. Handle Company Links Properly

```php
// When updating a contact's company link, ensure it exists first
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
// Segment contacts by role
Teamleader::contacts()->tag('contact-uuid', ['Decision Maker']);

// Segment by engagement level
Teamleader::contacts()->tag('contact-uuid', ['Highly Engaged']);

// Query segmented contacts
$decisionMakers = Teamleader::contacts()->withTags(['Decision Maker']);
```

### 5. Batch Process with Error Handling

```php
function batchProcessContacts(array $contactIds, callable $operation)
{
    $results = [
        'successful' => [],
        'failed' => []
    ];
    
    foreach ($contactIds as $contactId) {
        try {
            $result = $operation($contactId);
            $results['successful'][] = [
                'id' => $contactId,
                'result' => $result
            ];
        } catch (\Exception $e) {
            $results['failed'][] = [
                'id' => $contactId,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $results;
}

// Usage
$results = batchProcessContacts($contactIds, function($contactId) {
    return Teamleader::contacts()->tag($contactId, ['Processed']);
});
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $contact = Teamleader::contacts()->create([
        'first_name' => 'John',
        'last_name' => 'Doe'
    ]);
} catch (TeamleaderException $e) {
    Log::error('Error creating contact', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    // Handle specific error cases
    if ($e->getCode() === 422) {
        // Validation error - likely missing required email
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

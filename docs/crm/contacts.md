# Contacts

Manage contacts in Teamleader Focus CRM. This resource provides complete CRUD operations for managing individual contacts, including advanced features like tagging and company linking.

## Endpoint

`contacts`

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

Get a paginated list of contacts with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$contacts = $teamleader->contacts()->list(['status' => 'active']);
```

### `info()`

Get detailed information about a specific contact.

**Parameters:**
- `id` (string): Contact UUID
- `includes` (array|string): Relations to include

**Example:**
```php
$contact = $teamleader->contacts()->info('contact-uuid-here');
```

### `create()`

Create a new contact.

**Parameters:**
- `data` (array): Array of contact data

**Example:**
```php
$contact = $teamleader->contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'emails' => [
        ['type' => 'primary', 'email' => 'john@example.com']
    ]
]);
```

### `update()`

Update an existing contact.

**Parameters:**
- `id` (string): Contact UUID
- `data` (array): Array of data to update

**Example:**
```php
$contact = $teamleader->contacts()->update('contact-uuid', [
    'first_name' => 'Jane'
]);
```

### `delete()`

Delete a contact.

**Parameters:**
- `id` (string): Contact UUID

**Example:**
```php
$result = $teamleader->contacts()->delete('contact-uuid');
```

### `search()`

Search contacts by term (searches first_name, last_name, email and telephone).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$contacts = $teamleader->contacts()->search('John');
```

### `byEmail()`

Find contacts by email address.

**Parameters:**
- `email` (string): Email address
- `options` (array): Additional options

**Example:**
```php
$contacts = $teamleader->contacts()->byEmail('john@example.com');
```

### `forCompany()`

Get all contacts for a specific company.

**Parameters:**
- `companyId` (string): Company UUID
- `options` (array): Additional options

**Example:**
```php
$contacts = $teamleader->contacts()->forCompany('company-uuid');
```

### `tag()` / `untag()`

Add or remove tags from a contact.

**Parameters:**
- `id` (string): Contact UUID
- `tags` (array|string): Tags to add/remove

**Example:**
```php
$teamleader->contacts()->tag('contact-uuid', ['prospect', 'vip']);
$teamleader->contacts()->untag('contact-uuid', 'prospect');
```

### `linkToCompany()` / `unlinkFromCompany()`

Link or unlink a contact to/from a company.

**Example:**
```php
$teamleader->contacts()->linkToCompany('contact-uuid', 'company-uuid', [
    'position' => 'CEO',
    'decision_maker' => true
]);

$teamleader->contacts()->unlinkFromCompany('contact-uuid', 'company-uuid');
```

## Filtering

### Available Filters

- **`ids`**: Array of contact UUIDs to filter by
- **`email`**: Email address (requires type and email fields)
- **`company_id`**: Filter by company UUID
- **`term`**: Search term (searches first_name, last_name, email and telephone)
- **`updated_since`**: ISO 8601 datetime
- **`tags`**: Array of tag names (filters on contacts coupled to all given tags)
- **`status`**: Contact status (active, deactivated)

### Filter Examples

```php
// Filter by status
$activeContacts = $teamleader->contacts()->list([
    'status' => 'active'
]);

// Search by term
$contacts = $teamleader->contacts()->list([
    'term' => 'John'
]);

// Filter by email
$contacts = $teamleader->contacts()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'john@example.com'
    ]
]);

// Filter by company
$companyContacts = $teamleader->contacts()->list([
    'company_id' => 'company-uuid'
]);

// Filter by tags
$taggedContacts = $teamleader->contacts()->list([
    'tags' => ['prospect', 'vip']
]);

// Filter by updated since
$recentContacts = $teamleader->contacts()->list([
    'updated_since' => '2024-01-01T00:00:00+00:00'
]);
```

## Sorting

### Available Sort Fields

- **`added_at`**: Date contact was added
- **`name`**: Contact name (first_name + last_name)
- **`updated_at`**: Date contact was last updated

### Sorting Examples

```php
// Sort by name
$contacts = $teamleader->contacts()->list([], [
    'sort' => [
        [
            'field' => 'name',
            'order' => 'asc'
        ]
    ]
]);

// Sort by most recently added
$contacts = $teamleader->contacts()->list([], [
    'sort' => [
        [
            'field' => 'added_at',
            'order' => 'desc'
        ]
    ]
]);
```

## Sideloading

### Available Includes

- **`custom_fields`**: Include custom field values

### Sideloading Examples

```php
// Include custom fields
$contacts = $teamleader->contacts()->withCustomFields()->list();

// Using fluent interface
$contacts = $teamleader->contacts()->with('custom_fields')->list();

// Include in specific calls
$contact = $teamleader->contacts()->info('contact-uuid', 'custom_fields');
```

## Data Fields

### Contact Creation Fields

**Required:**
- `first_name` OR `last_name` (at least one required)

**Optional:**
- `salutation` (string): e.g., "Mr", "Mrs"
- `emails` (array): Email addresses with type
- `telephones` (array): Phone numbers with type
- `website` (string): Website URL
- `addresses` (array): Address information
- `language` (string): Contact language (e.g., "en")
- `gender` (string): female, male, non_binary, prefers_not_to_say, unknown
- `birthdate` (string): ISO date format
- `iban` (string): Bank account IBAN
- `bic` (string): Bank identifier code
- `national_identification_number` (string)
- `remarks` (string): Markdown formatted notes
- `tags` (array): Array of tag strings
- `custom_fields` (array): Custom field values
- `marketing_mails_consent` (boolean): Email consent

### Contact Response Fields

All creation fields plus:
- `id` (string): Contact UUID
- `status` (string): active, deactivated
- `vat_number` (string): VAT number
- `companies` (array): Linked companies with positions
- `payment_term` (object): Payment terms
- `invoicing_preferences` (object): Invoicing settings
- `added_at` (string): Creation timestamp
- `updated_at` (string): Last update timestamp
- `web_url` (string): Link to Teamleader interface

## Usage Examples

### Basic Contact Management

```php
// Create a contact
$contact = $teamleader->contacts()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'emails' => [
        [
            'type' => 'primary',
            'email' => 'john@example.com'
        ]
    ],
    'telephones' => [
        [
            'type' => 'mobile',
            'number' => '+32123456789'
        ]
    ]
]);

// Update a contact
$updatedContact = $teamleader->contacts()->update($contact['data']['id'], [
    'salutation' => 'Mr',
    'remarks' => 'VIP client'
]);

// Get contact details
$contactDetails = $teamleader->contacts()->info($contact['data']['id']);

// Delete a contact
$teamleader->contacts()->delete($contact['data']['id']);
```

### Advanced Features

```php
// Tag management
$teamleader->contacts()->tag('contact-uuid', ['vip', 'prospect']);
$teamleader->contacts()->manageTags('contact-uuid', ['client'], ['prospect']);

// Company linking
$teamleader->contacts()->linkToCompany('contact-uuid', 'company-uuid', [
    'position' => 'CEO',
    'decision_maker' => true
]);

// Update company link
$teamleader->contacts()->updateCompanyLink('contact-uuid', 'company-uuid', [
    'position' => 'CTO'
]);

// Unlink from company
$teamleader->contacts()->unlinkFromCompany('contact-uuid', 'company-uuid');
```

### Search and Filtering

```php
// Search by name or email
$results = $teamleader->contacts()->search('John Doe');

// Find by email
$contact = $teamleader->contacts()->byEmail('john@example.com');

// Get company contacts
$companyContacts = $teamleader->contacts()->forCompany('company-uuid');

// Filter by tags
$vipContacts = $teamleader->contacts()->withTags(['vip']);

// Get recent contacts
$recentContacts = $teamleader->contacts()->updatedSince('2024-01-01T00:00:00+00:00');
```

### Complex Queries

```php
// Active VIP contacts with custom fields
$vipContacts = $teamleader->contacts()
    ->withCustomFields()
    ->list([
        'status' => 'active',
        'tags' => ['vip']
    ], [
        'sort' => [['field' => 'updated_at', 'order' => 'desc']],
        'page_size' => 50
    ]);

// Search within company
$companyResults = $teamleader->contacts()->list([
    'company_id' => 'company-uuid',
    'term' => 'manager'
], [
    'sort' => [['field' => 'name', 'order' => 'asc']]
]);
```

## Error Handling

The contacts resource follows standard SDK error handling:

```php
$result = $teamleader->contacts()->create($contactData);

if (isset($result['error']) && $result['error']) {
    $errorMessage = $result['message'] ?? 'Unknown error';
    $statusCode = $result['status_code'] ?? 0;
    
    Log::error("Contacts API error: {$errorMessage}", [
        'status_code' => $statusCode,
        'errors' => $result['errors'] ?? []
    ]);
}
```

## Rate Limiting

Contact API calls count towards your overall Teamleader API rate limit:

- **List operations**: 1 request per call
- **Info operations**: 1 request per call
- **CRUD operations**: 1 request per call
- **Tag operations**: 1 request per call
- **Company link operations**: 1 request per call

## Laravel Integration

```php
use McoreServices\TeamleaderSDK\TeamleaderSDK;

class ContactController extends Controller
{
    public function index(TeamleaderSDK $teamleader)
    {
        $contacts = $teamleader->contacts()->active();
        return view('contacts.index', compact('contacts'));
    }
    
    public function store(Request $request, TeamleaderSDK $teamleader)
    {
        $contact = $teamleader->contacts()->create($request->validated());
        return redirect()->route('contacts.show', $contact['data']['id']);
    }
}
```

For more information, refer to the [Teamleader API Documentation](https://developer.focus.teamleader.eu/).

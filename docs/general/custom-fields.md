# Custom Fields

Manage custom field definitions in Teamleader Focus.

## Overview

The Custom Fields resource provides access to custom field definitions in your Teamleader account. Custom fields allow you to extend standard Teamleader objects (contacts, companies, deals, etc.) with your own data fields.

As of January 2026, the API supports **creating** custom field definitions programmatically. The `create` endpoint requires the `settings` OAuth scope.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Available Contexts](#available-contexts)
- [Field Types](#field-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`customFieldDefinitions`

## Capabilities

| Feature    | Supported |
|------------|-----------|
| Pagination | ✅ Supported (page size + number) |
| Filtering  | ✅ Supported |
| Sorting    | ✅ Supported (label, context) |
| Sideloading | ❌ Not Supported |
| Creation   | ✅ Supported (requires `settings` scope) |
| Update     | ❌ Not Supported |
| Deletion   | ❌ Not Supported |

## Available Methods

### `list()`

Get all custom field definitions with optional filtering and pagination.

**Parameters:**
- `filters` (array): Filters to apply — see [Filters](#filters)
- `options` (array): Pagination options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `page_size` | int | `20` | Number of results per page (max 100) |
| `page_number` | int | `1` | Page number to retrieve |

> **Important:** The Teamleader API defaults to a page size of 20. If you have more than 20 custom fields, you must paginate through pages to retrieve all of them. The `SyncReferenceDataJob` handles this automatically — use `page_size: 100` and loop until a page returns fewer results than requested.

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all custom fields (first page of 20)
$customFields = Teamleader::customFields()->list();

// Get custom fields for contacts
$contactFields = Teamleader::customFields()->list([
    'context' => 'contact'
]);

// Get specific fields by UUID
$fields = Teamleader::customFields()->list([
    'ids' => ['uuid-1', 'uuid-2']
]);

// Paginate through all custom fields
$allFields = [];
$page = 1;

do {
    $response = Teamleader::customFields()->list([], [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $allFields = array_merge($allFields, $response['data']);
    $hasMore   = count($response['data']) === 100;
    $page++;
} while ($hasMore);
```

---

### `info()`

Get detailed information about a specific custom field definition.

**Parameters:**
- `id` (string): Custom field UUID

**Example:**
```php
$field = Teamleader::customFields()->info('field-uuid');

$fieldName = $field['data']['label'];
$fieldType = $field['data']['type'];
```

---

### `create()`

Create a new custom field definition. Requires the `settings` OAuth scope.

**Parameters:**
- `data` (array): Field definition data

**Required keys:**

| Key | Type | Description |
|-----|------|-------------|
| `label` | string | Display label for the custom field |
| `type` | string | Field type — see [Field Types](#field-types) |
| `context` | string | Entity context — see [Available Contexts](#available-contexts) |

**Optional keys:**

| Key | Type | Applies to |
|-----|------|------------|
| `configuration.options` | string[] | `single_select`, `multi_select` only |
| `configuration.default_value` | mixed | `auto_increment` only |
| `configuration.searchable` | bool | `single_line`, `company`, `integer`, `number`, `auto_increment`, `email`, `telephone` |

**Returns:** `data.id` and `data.type` of the newly created field (HTTP 201).

**Examples:**

```php
// Create a simple text field on contacts
$field = Teamleader::customFields()->create([
    'label'   => 'VAT Number',
    'type'    => 'single_line',
    'context' => 'contact',
]);

$newFieldId = $field['data']['id'];

// Create a searchable text field
$field = Teamleader::customFields()->create([
    'label'         => 'Reference Code',
    'type'          => 'single_line',
    'context'       => 'deal',
    'configuration' => [
        'searchable' => true,
    ],
]);

// Create a single-select dropdown with options
$field = Teamleader::customFields()->create([
    'label'         => 'Lead Source',
    'type'          => 'single_select',
    'context'       => 'deal',
    'configuration' => [
        'options' => ['Referral', 'Website', 'Cold Call', 'Event'],
    ],
]);

// Create an auto-incrementing field
$field = Teamleader::customFields()->create([
    'label'         => 'Customer Number',
    'type'          => 'auto_increment',
    'context'       => 'company',
    'configuration' => [
        'default_value' => 1000,
    ],
]);
```

**Validation errors thrown by the SDK:**

```php
// Missing required field
Teamleader::customFields()->create([
    'type'    => 'single_line',
    'context' => 'contact',
    // label missing — throws InvalidArgumentException
]);

// Invalid type
Teamleader::customFields()->create([
    'label'   => 'Test',
    'type'    => 'auto_number', // invalid — correct value is 'auto_increment'
    'context' => 'contact',
]);

// Invalid configuration key for type
Teamleader::customFields()->create([
    'label'         => 'Test',
    'type'          => 'date',
    'context'       => 'contact',
    'configuration' => [
        'options' => ['A', 'B'], // invalid — 'options' only for single_select / multi_select
    ],
]);
```

---

## Helper Methods

### Context-Specific Methods

```php
$fields = Teamleader::customFields()->forContacts();
$fields = Teamleader::customFields()->forCompanies();
$fields = Teamleader::customFields()->forDeals();
$fields = Teamleader::customFields()->forProjects();
$fields = Teamleader::customFields()->forInvoices();
$fields = Teamleader::customFields()->forProducts();
$fields = Teamleader::customFields()->forMilestones();
$fields = Teamleader::customFields()->forTickets();
$fields = Teamleader::customFields()->forSubscriptions();
```

### Type-Specific Methods

```php
$selectFields = Teamleader::customFields()->byType('single_select');
$textFields   = Teamleader::customFields()->byType('single_line');
$dateFields   = Teamleader::customFields()->byType('date');
```

### ID-Based Methods

```php
$fields = Teamleader::customFields()->byIds([
    'field-uuid-1',
    'field-uuid-2'
]);
```

### Type Capability Checks

```php
// Does this type use an options list?
Teamleader::customFields()->typeHasOptions('single_select'); // true
Teamleader::customFields()->typeHasOptions('single_line');   // false

// Does this type support the searchable flag?
Teamleader::customFields()->typeIsSearchable('single_line'); // true
Teamleader::customFields()->typeIsSearchable('date');        // false

// Is this type a reference to another entity?
Teamleader::customFields()->typeIsReference('company'); // true
Teamleader::customFields()->typeIsReference('date');    // false
```

---

## Filters

### `ids`
Filter by specific custom field UUIDs.

```php
$fields = Teamleader::customFields()->list([
    'ids' => ['field-uuid-1', 'field-uuid-2']
]);
```

### `context`
Filter by the entity context where the custom field is used.

**Valid values:** `contact`, `company`, `deal`, `project`, `milestone`, `product`, `invoice`, `subscription`, `ticket`

```php
$fields = Teamleader::customFields()->list(['context' => 'deal']);
```

---

## Available Contexts

| Context | Description |
|---------|-------------|
| `contact` | Contact custom fields |
| `company` | Company custom fields |
| `deal` | Deal custom fields |
| `project` | Project custom fields |
| `milestone` | Milestone custom fields |
| `product` | Product custom fields |
| `invoice` | Invoice custom fields |
| `subscription` | Subscription custom fields |
| `ticket` | Ticket custom fields |

```php
$contexts = Teamleader::customFields()->getAvailableContexts();
```

---

## Field Types

| Type | Description | Supports options | Supports searchable |
|------|-------------|:---:|:---:|
| `single_line` | Single line text | | ✅ |
| `multi_line` | Multi-line text | | |
| `single_select` | Dropdown (one value) | ✅ | |
| `multi_select` | Dropdown (multiple values) | ✅ | |
| `date` | Date field | | |
| `money` | Money / currency field | | |
| `auto_increment` | Auto-incrementing number | | ✅ |
| `integer` | Integer number | | ✅ |
| `number` | Decimal number | | ✅ |
| `boolean` | Boolean (yes/no) | | |
| `email` | Email address | | ✅ |
| `telephone` | Telephone number | | ✅ |
| `url` | URL | | |
| `company` | Company reference | | ✅ |
| `contact` | Contact reference | | |
| `product` | Product reference | | |
| `user` | User reference | | |

```php
$types = Teamleader::customFields()->getAvailableTypes();
```

---

## Response Structure

### `list()` / `info()` — Field Object

```php
[
    'id'       => '74855f4a-2b61-429c-81d8-c79ad3675a76',
    'context'  => 'company',
    'type'     => 'single_select',
    'label'    => 'Industry',
    'group'    => 'Company Details',    // string or null
    'required' => false,
    'configuration' => [
        // Only present for single_select and multi_select:
        'options' => [
            ['id' => 'uuid', 'value' => 'Technology'],
            ['id' => 'uuid', 'value' => 'Retail'],
        ],
        'extra_option_allowed' => true,
    ],
]
```

### `create()` — Response

```php
[
    'data' => [
        'id'   => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'customFieldDefinition',
    ]
]
```

---

## Usage Examples

### Create and Immediately Use a Custom Field

```php
// 1. Create the field
$response = Teamleader::customFields()->create([
    'label'   => 'LinkedIn URL',
    'type'    => 'url',
    'context' => 'contact',
]);

$fieldId = $response['data']['id'];

// 2. Use it when creating a contact
$contact = Teamleader::contacts()->create([
    'first_name' => 'Jane',
    'last_name'  => 'Doe',
    'custom_fields' => [
        ['id' => $fieldId, 'value' => 'https://linkedin.com/in/janedoe']
    ],
]);
```

### Build Dynamic Forms

```php
$customFields = Teamleader::customFields()->forContacts();

$formFields = [];
foreach ($customFields['data'] as $field) {
    $formFields[] = [
        'name'     => 'custom_' . $field['id'],
        'label'    => $field['label'],
        'type'     => $field['type'],
        'required' => $field['required'],
        'options'  => $field['configuration']['options'] ?? [],
    ];
}
```

### Validate Custom Field Values

```php
$field = Teamleader::customFields()->info($fieldId);
$fieldData = $field['data'];

if ($fieldData['required'] && empty($value)) {
    throw new \Exception("Field {$fieldData['label']} is required");
}

if ($fieldData['type'] === 'single_select') {
    $validOptions = array_column($fieldData['configuration']['options'], 'value');
    if (! in_array($value, $validOptions)) {
        throw new \Exception("Invalid option for {$fieldData['label']}");
    }
}
```

### Cache Custom Fields

```php
use Illuminate\Support\Facades\Cache;

class CustomFieldService
{
    public function getFieldsForContext(string $context): array
    {
        return Cache::remember("custom_fields.{$context}", 7200, function () use ($context) {
            return Teamleader::customFields()->forContext($context);
        });
    }
}
```

### Sync Custom Fields to Local Database

```php
use Illuminate\Console\Command;

class SyncCustomFieldsCommand extends Command
{
    protected $signature = 'teamleader:sync-custom-fields';

    public function handle(): void
    {
        $page = 1;

        do {
            $response = Teamleader::customFields()->list([], [
                'page_size'   => 100,
                'page_number' => $page,
            ]);

            foreach ($response['data'] as $fieldData) {
                \App\Models\CustomField::updateOrCreate(
                    ['teamleader_id' => $fieldData['id']],
                    [
                        'label'       => $fieldData['label'],
                        'type'        => $fieldData['type'],
                        'context'     => $fieldData['context'],
                        'required'    => $fieldData['required'] ?? false,
                        'options'     => json_encode($fieldData['configuration']['options'] ?? []),
                        'group_label' => $fieldData['group'] ?? null,
                    ]
                );
            }

            $hasMore = count($response['data']) === 100;
            $page++;
        } while ($hasMore);

        $this->info('Custom fields synced successfully!');
    }
}
```

---

## Best Practices

### 1. Cache Custom Field Definitions

Custom fields rarely change, so cache them aggressively:

```php
// Good: cache for 2 hours
$fields = Cache::remember('custom_fields.contact', 7200, fn () =>
    Teamleader::customFields()->forContacts()
);
```

### 2. Use Helper Methods for Context Filtering

```php
// Preferred
$contactFields = Teamleader::customFields()->forContacts();

// Also valid
$contactFields = Teamleader::customFields()->list(['context' => 'contact']);
```

### 3. Validate Configuration Before Creating

Use the SDK's type capability methods before building the `configuration` array:

```php
$type = 'single_select';

$data = ['label' => 'Status', 'type' => $type, 'context' => 'deal'];

if (Teamleader::customFields()->typeHasOptions($type)) {
    $data['configuration']['options'] = ['Open', 'Won', 'Lost'];
}

$field = Teamleader::customFields()->create($data);
```

### 4. Handle Missing Fields Gracefully

```php
try {
    $field = Teamleader::customFields()->info($fieldId);
} catch (TeamleaderException $e) {
    Log::warning("Custom field not found: {$fieldId}");
    return null;
}
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $field = Teamleader::customFields()->create([
        'label'   => 'Test Field',
        'type'    => 'single_line',
        'context' => 'contact',
    ]);
} catch (\InvalidArgumentException $e) {
    // SDK validation failed before the API was called
    Log::error('Invalid custom field data: ' . $e->getMessage());
} catch (TeamleaderException $e) {
    // API returned an error (e.g. missing settings scope)
    Log::error('Teamleader API error', [
        'message' => $e->getMessage(),
        'code'    => $e->getCode(),
    ]);
}
```

---

## Working with Custom Field Values

This resource manages field _definitions_. Set custom field values when creating or updating entities:

```php
$customFields  = Teamleader::customFields()->forContacts();
$industryField = $customFields['data'][0];

$contact = Teamleader::contacts()->create([
    'first_name'    => 'John',
    'last_name'     => 'Doe',
    'custom_fields' => [
        ['id' => $industryField['id'], 'value' => 'technology']
    ],
]);
```

---

## Related Resources

- [Contacts](../crm/contacts.md) — Use custom fields with contacts
- [Companies](../crm/companies.md) — Use custom fields with companies
- [Deals](../deals/deals.md) — Use custom fields with deals
- [Projects](../projects/projects.md) — Use custom fields with projects
- [Invoices](../invoicing/invoices.md) — Use custom fields with invoices

## See Also

- [Usage Guide](../usage.md) — General SDK usage
- [Filtering](../filtering.md) — Advanced filtering techniques

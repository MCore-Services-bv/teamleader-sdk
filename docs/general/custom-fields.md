# Custom Fields

Manage custom field definitions in Teamleader Focus.

## Overview

The Custom Fields resource gives access to the custom field definitions in your Teamleader account — the schema-level
objects that describe what extra fields exist on contacts, companies, deals, and other entities. It does not read or
write the *values* of those fields; values are sideloaded on their parent resource (
e.g. `Teamleader::companies()->with('custom_fields')->info(...)`).

As of v1.2.0 the SDK supports **creating** custom field definitions programmatically. Creating requires the `settings`
OAuth scope.

## Endpoint

`customFieldDefinitions`

## Capabilities

| Capability  | Supported                               |
|-------------|-----------------------------------------|
| Pagination  | ✅ Supported                             |
| Filtering   | ✅ Supported                             |
| Sorting     | ❌ Not supported                         |
| Sideloading | ❌ Not supported                         |
| Creation    | ✅ Supported (requires `settings` scope) |
| Update      | ❌ Not supported                         |
| Deletion    | ❌ Not supported                         |

> **Note on pagination:** The API defaults to page size 20. If you have more than 20 custom fields you must paginate
> explicitly. The `list()` method always sends a page block — pass `page_size` and `page_number` via options.

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns a paginated list of custom field definitions.

**Options:**

| Key           | Type | Default | Description      |
|---------------|------|---------|------------------|
| `page_size`   | int  | `20`    | Records per page |
| `page_number` | int  | `1`     | Page to retrieve |

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// First page of 20
$fields = Teamleader::customFields()->list();

// All fields for a context
$contactFields = Teamleader::customFields()->list([
    'context' => 'contact',
]);

// Specific fields by UUID
$fields = Teamleader::customFields()->list([
    'ids' => ['uuid-1', 'uuid-2'],
]);

// Paginate through all fields
$all  = [];
$page = 1;

do {
    $response = Teamleader::customFields()->list([], [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

---

### `info(string $id)`

Returns a single custom field definition by UUID.

```php
$field = Teamleader::customFields()->info('field-uuid');

$label   = $field['data']['label'];
$type    = $field['data']['type'];
$context = $field['data']['context'];
```

---

### `create(array $data)`

Creates a new custom field definition. Requires the `settings` OAuth scope.

**Required fields:**

| Field     | Type   | Description                                             |
|-----------|--------|---------------------------------------------------------|
| `label`   | string | Display label for the field                             |
| `type`    | string | Field type — see [Field Types](#field-types)            |
| `context` | string | Entity the field belongs to — see [Contexts](#contexts) |

**Optional fields:**

| Field           | Type  | Description                                                       |
|-----------------|-------|-------------------------------------------------------------------|
| `configuration` | array | Type-specific configuration — see [Configuration](#configuration) |

The SDK validates `label`, `type`, `context`, and `configuration` before sending the request.
An `InvalidArgumentException` is thrown for any invalid value.

```php
// Simple single-line text field
$field = Teamleader::customFields()->create([
    'label'   => 'Purchase Order Number',
    'type'    => 'single_line',
    'context' => 'invoice',
]);

// Single-select dropdown with options
$field = Teamleader::customFields()->create([
    'label'         => 'Lead Source',
    'type'          => 'single_select',
    'context'       => 'deal',
    'configuration' => [
        'options' => ['Referral', 'Website', 'Cold Call', 'Event'],
    ],
]);

// Auto-increment with a starting value
$field = Teamleader::customFields()->create([
    'label'         => 'Customer Number',
    'type'          => 'auto_increment',
    'context'       => 'company',
    'configuration' => [
        'default_value' => 1000,
    ],
]);

// Searchable text field
$field = Teamleader::customFields()->create([
    'label'         => 'External ID',
    'type'          => 'single_line',
    'context'       => 'contact',
    'configuration' => [
        'searchable' => true,
    ],
]);
```

**Create response:**

```php
[
    'data' => [
        'type' => 'customFieldDefinition',
        'id'   => 'new-field-uuid',
    ],
]
```

---

## Helper Methods

### `forContext(string $context)`

Returns fields for a specific context. Shorthand for `list(['context' => $context])`.

```php
$fields = Teamleader::customFields()->forContext('deal');
```

### Context convenience methods

| Method               | Context passed                  |
|----------------------|---------------------------------|
| `forContacts()`      | `contact`                       |
| `forCompanies()`     | `company`                       |
| `forDeals()`         | `deal`                          |
| `forSales()`         | `deal` (alias for `forDeals()`) |
| `forProjects()`      | `project`                       |
| `forMilestones()`    | `milestone`                     |
| `forProducts()`      | `product`                       |
| `forInvoices()`      | `invoice`                       |
| `forSubscriptions()` | `subscription`                  |
| `forTickets()`       | `ticket`                        |

> **Note:** `forQuotations()` and `forCreditnotes()` exist in the source but pass `quotation` and `creditnote` as
> context values, which are not in the validated context list. Their behaviour depends on whether the Teamleader API
> accepts those values.

### `byIds(array $ids)`

Shorthand for `list(['ids' => $ids])`.

```php
$fields = Teamleader::customFields()->byIds(['uuid-1', 'uuid-2']);
```

### `byType(string $type)`

Calls `list(['type' => $type])`. Note: the `type` key is not currently handled by `buildFilters()` in the source, so
this filter is silently dropped and all fields are returned regardless of type. Use `forContext()` combined with
client-side filtering instead.

---

## Introspection Helpers

```php
// All valid contexts as an array
$contexts = Teamleader::customFields()->getAllSupportedContexts();

// All valid types as an array
$types = Teamleader::customFields()->getAllSupportedTypes();

// Check capabilities of a type
$hasOptions    = Teamleader::customFields()->typeHasOptions('single_select');     // true
$isSearchable  = Teamleader::customFields()->typeIsSearchable('single_line');     // true
$isReference   = Teamleader::customFields()->typeIsReference('company');          // true
```

---

## Filters

### `ids`

Filter by an array of custom field UUIDs.

```php
$fields = Teamleader::customFields()->list([
    'ids' => ['uuid-1', 'uuid-2'],
]);
```

### `context`

Filter by entity context (string, not array).

```php
$fields = Teamleader::customFields()->list([
    'context' => 'contact',
]);
```

---

## Contexts

Valid context values for both filtering and creation:

| Context        | Description         |
|----------------|---------------------|
| `contact`      | Contact fields      |
| `company`      | Company fields      |
| `deal`         | Deal fields         |
| `project`      | Project fields      |
| `milestone`    | Milestone fields    |
| `product`      | Product fields      |
| `invoice`      | Invoice fields      |
| `subscription` | Subscription fields |
| `ticket`       | Ticket fields       |

---

## Field Types

| Type             | Description              | Supports `options`       | Supports `searchable` |
|------------------|--------------------------|--------------------------|-----------------------|
| `single_line`    | Single-line text         | ❌                        | ✅                     |
| `multi_line`     | Multi-line text          | ❌                        | ❌                     |
| `single_select`  | Single-choice dropdown   | ✅                        | ❌                     |
| `multi_select`   | Multi-choice dropdown    | ✅                        | ❌                     |
| `date`           | Date picker              | ❌                        | ❌                     |
| `money`          | Monetary value           | ❌                        | ❌                     |
| `auto_increment` | Auto-incrementing number | ❌ (`default_value` only) | ❌                     |
| `integer`        | Whole number             | ❌                        | ✅                     |
| `number`         | Decimal number           | ❌                        | ✅                     |
| `boolean`        | True/false toggle        | ❌                        | ❌                     |
| `email`          | Email address            | ❌                        | ✅                     |
| `telephone`      | Phone number             | ❌                        | ✅                     |
| `url`            | URL / website            | ❌                        | ❌                     |
| `company`        | Reference to a company   | ❌                        | ❌                     |
| `contact`        | Reference to a contact   | ❌                        | ❌                     |
| `product`        | Reference to a product   | ❌                        | ❌                     |
| `user`           | Reference to a user      | ❌                        | ❌                     |

---

## Configuration

The `configuration` key is optional on `create()` and its valid sub-keys depend on the field type.

### `options` — `single_select` and `multi_select` only

An array of string option labels.

```php
'configuration' => [
    'options' => ['Option A', 'Option B', 'Option C'],
],
```

### `default_value` — `auto_increment` only

The starting integer for the auto-increment sequence.

```php
'configuration' => [
    'default_value' => 1000,
],
```

### `searchable` — specific types only

A boolean that makes the field searchable. Valid
for: `single_line`, `company`, `integer`, `number`, `auto_increment`, `email`, `telephone`.

```php
'configuration' => [
    'searchable' => true,
],
```

Passing a configuration key for a type that doesn't support it throws an `InvalidArgumentException` before the request
is sent.

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'            => 'field-uuid',
            'label'         => 'Lead Source',
            'type'          => 'single_select',
            'context'       => 'deal',
            'configuration' => [
                'options' => ['Referral', 'Website', 'Cold Call'],
            ],
            'required'      => false,
        ],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 42,
    ],
]
```

### `info()` response

```php
[
    'data' => [
        'id'            => 'field-uuid',
        'label'         => 'Lead Source',
        'type'          => 'single_select',
        'context'       => 'deal',
        'configuration' => [
            'options' => ['Referral', 'Website', 'Cold Call'],
        ],
        'required'      => false,
    ],
]
```

---

## Usage Examples

### Get all custom field definitions

```php
$all  = [];
$page = 1;

do {
    $response = Teamleader::customFields()->list([], [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

### Build a UUID map for a context

```php
$fields = Teamleader::customFields()->forDeals();

$map = array_column($fields['data'], 'id', 'label');
// ['Lead Source' => 'uuid-1', 'Budget' => 'uuid-2', ...]
```

### Read custom field values from a company

Custom field values are sideloaded on the parent resource, not fetched here:

```php
$company = Teamleader::companies()
    ->with('custom_fields')
    ->info('company-uuid');

foreach ($company['data']['custom_fields'] ?? [] as $field) {
    $definitionId = $field['definition']['id'];
    $value        = $field['value'];
}
```

### Cache field definitions

```php
$fields = Cache::remember('tl_custom_fields', 3600, function () {
    $all  = [];
    $page = 1;

    do {
        $response = Teamleader::customFields()->list([], [
            'page_size'   => 100,
            'page_number' => $page,
        ]);

        $all  = array_merge($all, $response['data']);
        $page++;
    } while (count($response['data']) === 100);

    return $all;
});
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// create() validates before the request
try {
    $field = Teamleader::customFields()->create([
        'label'         => 'Lead Source',
        'type'          => 'single_select',
        'context'       => 'deal',
        'configuration' => ['options' => ['Referral', 'Website']],
    ]);
} catch (InvalidArgumentException $e) {
    // Invalid type, context, or configuration key
    Log::error('Invalid custom field data', ['message' => $e->getMessage()]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 403) {
        // Missing 'settings' OAuth scope
        Log::error('Missing settings scope for custom field creation');
    }
}

// info() on a missing field
try {
    $field = Teamleader::customFields()->info('field-uuid');
} catch (NotFoundException $e) {
    Log::warning('Custom field not found', ['id' => 'field-uuid']);
}
```

---

## Related Resources

- [[Sideloading]] — Reading custom field *values* on entities
- [[Companies]] — Companies support `custom_fields` sideloading
- [[Contacts]] — Contacts support `custom_fields` sideloading
- [[Deals]] — Deals support `custom_fields` sideloading
- [[Filtering]] — Filter and pagination reference

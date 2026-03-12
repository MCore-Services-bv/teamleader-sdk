# Filtering Guide

Comprehensive guide to filtering resources in the Teamleader SDK.

## Overview

The Teamleader SDK provides powerful filtering capabilities to help you retrieve exactly the data you need. Most resources support filtering through the `list()` method.

## Basic Filtering

### Simple Filters

Pass filters as the first parameter to the `list()` method:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Filter by status
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);

// Filter by multiple criteria
$contacts = Teamleader::contacts()->list([
    'status' => 'active',
    'company_id' => 'company-uuid'
]);
```

## Common Filter Types

### Status Filters

Most resources support filtering by status:

```php
// Active resources only
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);

// Deactivated resources
$contacts = Teamleader::contacts()->list([
    'status' => 'deactivated'
]);
```

### ID Filters

Filter by one or multiple IDs:

```php
// Single ID
$companies = Teamleader::companies()->list([
    'ids' => ['company-uuid-1']
]);

// Multiple IDs
$companies = Teamleader::companies()->list([
    'ids' => [
        'company-uuid-1',
        'company-uuid-2',
        'company-uuid-3'
    ]
]);
```

### Date Filters

Filter resources by date ranges:

```php
// Updated since a specific date
$contacts = Teamleader::contacts()->list([
    'updated_since' => '2024-01-01'
]);

// Created within a date range
$deals = Teamleader::deals()->list([
    'created_after' => '2024-01-01',
    'created_before' => '2024-12-31'
]);
```

### Tag Filters

Filter resources by tags:

```php
// Single tag
$companies = Teamleader::companies()->list([
    'tags' => ['vip']
]);

// Multiple tags
$companies = Teamleader::companies()->list([
    'tags' => ['vip', 'enterprise', 'partner']
]);
```

## Advanced Filtering

### Email Filters

Email filters require a specific structure:

```php
// Filter by primary email
$companies = Teamleader::companies()->list([
    'email' => [
        'type' => 'primary',
        'email' => 'info@example.com'
    ]
]);

// Using the helper method (recommended)
$companies = Teamleader::companies()->byEmail('info@example.com');
```

### Search Filters

Use the `term` filter for full-text search across multiple fields:

```php
$companies = Teamleader::companies()->list([
    'term' => 'Acme'
]);
```

## Sideloading Related Data (Includes)

Use the `include` option to request related data alongside your results in a single API call.
The SDK translates this into the `includes` parameter that the Teamleader API expects.

> **Important:** The Teamleader API uses `includes` (plural) in the POST body. The SDK handles
> this translation automatically — always pass `'include'` in your options array and the SDK
> will send the correct key.

### Requesting Includes in `list()` Calls

Pass `include` as part of the options (second parameter):

```php
// Single include
$companies = Teamleader::companies()->list([], [
    'include' => 'custom_fields'
]);

// Multiple includes (comma-separated)
$companies = Teamleader::companies()->list([], [
    'include' => 'custom_fields,price_list'
]);
```

### Requesting Includes in `info()` Calls

Pass includes as the second argument:

```php
$company = Teamleader::companies()->info('company-uuid', 'custom_fields,responsible_user');
```

### Using the Fluent `with()` Interface

The `with()` method is the recommended approach for readability:

```php
// Single relationship
$company = Teamleader::companies()
    ->with('custom_fields')
    ->info('company-uuid');

// Multiple relationships
$company = Teamleader::companies()
    ->with('custom_fields,responsible_user,addresses')
    ->info('company-uuid');

// Chaining
$company = Teamleader::companies()
    ->with('custom_fields')
    ->with('responsible_user')
    ->info('company-uuid');

// Works with list() too
$companies = Teamleader::companies()
    ->with('custom_fields,price_list')
    ->list(['status' => 'active']);
```

### Available Includes per Resource

#### Companies

| Include | Description |
|---|---|
| `custom_fields` | Custom field values defined for companies |
| `price_list` | The assigned price list |
| `responsible_user` | The user responsible for the company |
| `addresses` | Company address records |
| `business_type` | Business type information |
| `tags` | Associated tags |

#### Contacts

| Include | Description |
|---|---|
| `custom_fields` | Custom field values defined for contacts |
| `price_list` | The assigned price list |
| `responsible_user` | The user responsible for the contact |
| `addresses` | Contact address records |

### Working with Custom Fields

Custom fields are only returned when explicitly requested via `include`:

```php
$companies = Teamleader::companies()->list([], [
    'page_size' => 100,
    'include'   => 'custom_fields',
]);

foreach ($companies['data'] as $company) {
    $customFields = $company['custom_fields'] ?? [];

    foreach ($customFields as $field) {
        $definitionId = $field['definition']['id'];
        $value        = $field['value'];
        // Process field...
    }
}
```

Custom field values follow this structure:

```json
{
  "definition": {
    "type": "customFieldDefinition",
    "id": "bf6765de-56eb-40ec-ad14-9096c5dc5fe1"
  },
  "value": "some value"
}
```

## Filter Validation

The SDK automatically removes null, empty string, and empty array values:

```php
// Invalid filters are automatically removed
$companies = Teamleader::companies()->list([
    'status'         => 'active',
    'invalid_filter' => null,   // Removed (null value)
    'empty_array'    => [],     // Removed (empty array)
    'empty_string'   => '',     // Removed (empty string)
    'valid_filter'   => 'value' // Kept
]);
```

## Combining Filters, Pagination, and Includes

All options can be combined freely:

```php
$companies = Teamleader::companies()->list(
    // Filters (first argument)
    [
        'status'        => 'active',
        'tags'          => ['vip'],
        'updated_since' => '2024-01-01',
    ],
    // Options (second argument)
    [
        'page_size'   => 50,
        'page_number' => 1,
        'sort'        => 'name',
        'sort_order'  => 'asc',
        'include'     => 'custom_fields,price_list',
    ]
);
```

## Checking Resource Capabilities

Not all resources support all filtering or sideloading options:

```php
$capabilities = Teamleader::companies()->getCapabilities();

if ($capabilities['supports_filtering']) {
    // This resource supports filtering
}

if ($capabilities['supports_sideloading']) {
    $availableIncludes = $capabilities['available_includes'];
}
```

## See Also

- [Sideloading Guide](sideloading.md) — in-depth guide to loading related data
- [Usage Guide](usage.md) — general SDK usage
- [Resources](resources.md) — resource architecture overview

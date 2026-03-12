# Sideloading Guide

Complete guide to efficiently loading related data using sideloading (includes) in the Teamleader SDK.

## Overview

Sideloading allows you to load related resources in a single API request instead of making multiple requests. This significantly improves performance and reduces API call usage.

## How the API Parameter Works

The Teamleader API uses `includes` (plural) as the POST body parameter for both `.list` and `.info`
endpoints. The SDK's `FilterTrait::applyIncludes()` method handles this automatically — you never
need to write `includes` yourself. Always use the SDK methods documented below.

> **Do not** set `'includes'` directly in your params array. Use `'include'` in the options array,
> or the `with()` fluent method. The SDK sends the correct key to the API.

## The Problem Sideloading Solves

Without sideloading, you'd need multiple API calls:

```php
// ❌ Bad: 3 API calls
$company = Teamleader::companies()->info('company-uuid');
$user = Teamleader::users()->info($company['responsible_user']['id']);
$businessType = Teamleader::businessTypes()->info($company['business_type']['id']);
```

With sideloading, you get everything in one call:

```php
// ✅ Good: 1 API call
$company = Teamleader::companies()
    ->with('responsible_user,business_type')
    ->info('company-uuid');
```

## Basic Sideloading

### Using the `with()` Method (Recommended)

The fluent `with()` method is the recommended way to sideload relationships:

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Single relationship
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');

// Multiple relationships (comma-separated string)
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type')
    ->info('company-uuid');

// Multiple relationships (array)
$company = Teamleader::companies()
    ->with(['responsible_user', 'addresses', 'business_type'])
    ->info('company-uuid');

// Chaining
$company = Teamleader::companies()
    ->with('responsible_user')
    ->with('addresses')
    ->info('company-uuid');
```

### Using the Options Parameter

You can also specify includes in the options array via the `include` key:

```php
// In list() calls
$companies = Teamleader::companies()->list([], [
    'include' => 'custom_fields,price_list'
]);

// In info() calls — pass as second argument
$company = Teamleader::companies()->info('company-uuid', 'custom_fields,responsible_user');
```

## Common Relationships

### CRM Resources

#### Companies

Available includes:

| Include | Description |
|---|---|
| `custom_fields` | Custom field values for the company |
| `price_list` | The assigned price list |
| `responsible_user` | User responsible for the company |
| `addresses` | Company address records |
| `business_type` | Business type reference |
| `tags` | Associated tags |

#### Contacts

Available includes:

| Include | Description |
|---|---|
| `custom_fields` | Custom field values for the contact |
| `price_list` | The assigned price list |
| `responsible_user` | User responsible for the contact |
| `addresses` | Contact address records |

## Sideloading in List Calls

Sideloading works with both `info()` and `list()` calls:

```php
// Load all companies with custom fields and price list
$companies = Teamleader::companies()
    ->with('custom_fields,price_list')
    ->list();

// With filters
$companies = Teamleader::companies()
    ->with('responsible_user,addresses')
    ->list(['status' => 'active']);

// With pagination and sorting
$companies = Teamleader::companies()
    ->with('custom_fields')
    ->list(
        ['status' => 'active'],
        ['page_size' => 100, 'sort' => 'name']
    );
```

## Working with Custom Fields

Custom fields are not included in API responses by default. You must request them explicitly.
Once included, each entry follows this structure:

```json
{
  "definition": {
    "type": "customFieldDefinition",
    "id": "bf6765de-56eb-40ec-ad14-9096c5dc5fe1"
  },
  "value": "some value"
}
```

Example — iterating over custom fields on a list response:

```php
$companies = Teamleader::companies()->list([], [
    'page_size' => 100,
    'include'   => 'custom_fields',
]);

foreach ($companies['data'] as $company) {
    foreach ($company['custom_fields'] ?? [] as $field) {
        $uuid  = $field['definition']['id'];
        $value = $field['value'];
        // Resolve UUID against tl_custom_fields table, or process directly
    }
}
```

## Performance Optimization

### Load Only What You Need

```php
// ❌ Bad: Loading unnecessary data
$company = Teamleader::companies()
    ->with('responsible_user,addresses,business_type,tags,custom_fields,price_list')
    ->info('company-uuid');

// ✅ Good: Load only required relationships
$company = Teamleader::companies()
    ->with('custom_fields')
    ->info('company-uuid');
```

### Avoid Per-Record Info Calls

Always prefer sideloading in list calls over fetching each record individually:

```php
// ✅ Efficient: One API call, all custom fields included
$companies = Teamleader::companies()
    ->with('custom_fields')
    ->list([], ['page_size' => 100]);

foreach ($companies['data'] as $company) {
    $customFields = $company['custom_fields'] ?? [];
    // Process...
}

// ❌ Inefficient: 1 list call + N info calls
$companies = Teamleader::companies()->list([], ['page_size' => 100]);

foreach ($companies['data'] as $company) {
    $detail = Teamleader::companies()->info($company['id'], 'custom_fields');
    // Each iteration is an extra API call
}
```

## Clearing Pending Includes

Reset includes mid-chain if needed:

```php
$resource = Teamleader::companies()
    ->with('responsible_user')
    ->withoutIncludes()  // Clears all pending includes
    ->with('addresses')  // Start fresh
    ->info('company-uuid');
```

## Checking Available Includes

```php
$capabilities = Teamleader::companies()->getCapabilities();

if ($capabilities['supports_sideloading']) {
    $available = $capabilities['available_includes'];
}
```

## Working with Included Data

```php
$company = Teamleader::companies()
    ->with('responsible_user,addresses,custom_fields')
    ->info('company-uuid');

// Main resource
$companyName = $company['data']['name'];

// Sideloaded object relationship
$userName = $company['data']['responsible_user']['first_name'] ?? 'No user assigned';

// Sideloaded array relationship
foreach ($company['data']['addresses'] ?? [] as $address) {
    echo $address['line_1'];
}

// Custom fields array
foreach ($company['data']['custom_fields'] ?? [] as $field) {
    $id    = $field['definition']['id'];
    $value = $field['value'];
}
```

## Testing with Sideloading

```php
use Tests\TestCase;

class CompanySideloadingTest extends TestCase
{
    public function test_can_load_company_with_custom_fields()
    {
        $company = Teamleader::companies()
            ->with('custom_fields')
            ->info('test-company-uuid');

        $this->assertArrayHasKey('custom_fields', $company['data']);
        $this->assertIsArray($company['data']['custom_fields']);
    }

    public function test_can_load_company_with_relationships()
    {
        $company = Teamleader::companies()
            ->with('responsible_user,addresses')
            ->info('test-company-uuid');

        $this->assertArrayHasKey('responsible_user', $company['data']);
        $this->assertArrayHasKey('addresses', $company['data']);
    }
}
```

## Common Patterns

### Building a List View with Related Data

```php
public function index(Request $request)
{
    $companies = Teamleader::companies()
        ->with('responsible_user,custom_fields')
        ->list(
            ['status' => 'active'],
            [
                'page_size'   => 20,
                'page_number' => $request->get('page', 1),
            ]
        );

    return view('companies.index', [
        'companies' => $companies['data']
    ]);
}
```

### Syncing with Custom Fields

```php
$response = Teamleader::companies()->list([], [
    'page_size' => 100,
    'include'   => 'custom_fields',
]);

foreach ($response['data'] as $company) {
    TlCompany::updateOrCreate(
        ['teamleader_id' => $company['id']],
        [
            'name'          => $company['name'],
            'custom_fields' => $company['custom_fields'] ?? [],
            'raw'           => $company,
        ]
    );
}
```

## Next Steps

- Learn about [Filtering](filtering.md) to narrow down your results
- See [Usage Guide](usage.md) for general SDK usage
- Check individual resource documentation for available relationships

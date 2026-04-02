# Sideloading

How to load related data in a single API request.

## Overview

Sideloading lets you include related resources in the same response instead of making separate API calls for each one.
This reduces round trips and API usage.

```php
// ❌ Without sideloading — 3 API calls
$company = Teamleader::companies()->info('company-uuid');
$user    = Teamleader::users()->info($company['data']['responsible_user']['id']);

// ✅ With sideloading — 1 API call
$company = Teamleader::companies()
    ->with('responsible_user')
    ->info('company-uuid');
```

---

## How the API Parameter Works

The Teamleader API accepts `includes` (plural) as the POST body key for both `.list` and `.info` endpoints. The SDK
handles this translation automatically.

**You should always use one of the SDK patterns below.** Do not set `includes` directly in a params array —
use `include` in the options array or the `with()` method, and the SDK will send the correct key.

---

## Methods

### Fluent `with()` — Recommended

Chain `with()` before `info()` or `list()`. Accepts a comma-separated string or an array.

```php
// Single relationship
$company = Teamleader::companies()
    ->with('custom_fields')
    ->info('company-uuid');

// Multiple relationships — comma-separated string
$company = Teamleader::companies()
    ->with('custom_fields,responsible_user,addresses')
    ->info('company-uuid');

// Multiple relationships — array
$company = Teamleader::companies()
    ->with(['custom_fields', 'responsible_user'])
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

### Options Array

Pass `include` as part of the options (second argument to `list()`, or second argument to `info()`):

```php
// list()
$companies = Teamleader::companies()->list([], [
    'include' => 'custom_fields,responsible_user',
]);

// info()
$company = Teamleader::companies()->info('company-uuid', 'custom_fields,responsible_user');
```

---

## Available Includes by Resource

Not every resource supports sideloading. Resources with `supportsSideloading: false` will ignore the `includes`
parameter. Check individual resource docs for their available includes.

### Companies

| Include            | Description             |
|--------------------|-------------------------|
| `custom_fields`    | Custom field values     |
| `price_list`       | Assigned price list     |
| `responsible_user` | Responsible user        |
| `addresses`        | Address records         |
| `business_type`    | Business type reference |
| `tags`             | Associated tags         |

### Contacts

| Include            | Description         |
|--------------------|---------------------|
| `custom_fields`    | Custom field values |
| `price_list`       | Assigned price list |
| `responsible_user` | Responsible user    |
| `addresses`        | Address records     |

### Deals

| Include            | Description         |
|--------------------|---------------------|
| `custom_fields`    | Custom field values |
| `responsible_user` | Responsible user    |

### Invoices / Credit Notes

| Include         | Description         |
|-----------------|---------------------|
| `custom_fields` | Custom field values |

### Products

| Include         | Description          |
|-----------------|----------------------|
| `custom_fields` | Custom field values  |
| `suppliers`     | Associated suppliers |

### Orders / Quotations

| Include         | Description         |
|-----------------|---------------------|
| `custom_fields` | Custom field values |

---

## Working with Custom Fields

Custom fields are not included by default. Request them explicitly and iterate over the returned array:

```php
$companies = Teamleader::companies()->list([], [
    'page_size' => 100,
    'include'   => 'custom_fields',
]);

foreach ($companies['data'] as $company) {
    foreach ($company['custom_fields'] ?? [] as $field) {
        $definitionId = $field['definition']['id']; // UUID
        $value        = $field['value'];
    }
}
```

Custom field structure in the response:

```json
{
    "definition": {
        "type": "customFieldDefinition",
        "id": "bf6765de-56eb-40ec-ad14-9096c5dc5fe1"
    },
    "value": "some value"
}
```

---

## Accessing Sideloaded Data

```php
$company = Teamleader::companies()
    ->with('responsible_user,addresses,custom_fields')
    ->info('company-uuid');

// Sideloaded object relationship
$userId = $company['data']['responsible_user']['id'] ?? null;

// Sideloaded array relationship
foreach ($company['data']['addresses'] ?? [] as $address) {
    echo $address['line_1'];
}

// Custom fields
foreach ($company['data']['custom_fields'] ?? [] as $field) {
    $id    = $field['definition']['id'];
    $value = $field['value'];
}
```

---

## Performance Tips

Only request the relationships you actually need:

```php
// ❌ Requesting everything unnecessarily
$company = Teamleader::companies()
    ->with('custom_fields,price_list,responsible_user,addresses,business_type,tags')
    ->info('company-uuid');

// ✅ Only what is needed
$company = Teamleader::companies()
    ->with('custom_fields')
    ->info('company-uuid');
```

Prefer sideloading in `list()` calls over calling `info()` per record:

```php
// ❌ N+1 calls
foreach ($companies['data'] as $company) {
    $full = Teamleader::companies()->info($company['id'], 'custom_fields');
}

// ✅ One call
$companies = Teamleader::companies()
    ->with('custom_fields')
    ->list([], ['page_size' => 100]);
```

---

## Related Resources

- [[Filtering]] — Filters, sorting, pagination
- [[Resources]] — Resource architecture and capabilities
- [[Errors]] — Exception reference

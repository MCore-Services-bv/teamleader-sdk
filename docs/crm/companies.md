# Companies

Manage companies in Teamleader Focus CRM.

## Overview

The Companies resource provides full CRUD operations for company records. Beyond standard list/info/create/update/delete
it exposes tag management, logo upload, and a rich set of search and filter helpers.

## Endpoint

`companies`

## Capabilities

| Capability  | Supported   |
|-------------|-------------|
| Pagination  | ✅ Supported |
| Filtering   | ✅ Supported |
| Sorting     | ✅ Supported |
| Sideloading | ✅ Supported |
| Creation    | ✅ Supported |
| Update      | ✅ Supported |
| Deletion    | ✅ Supported |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$companies = Teamleader::companies()->list();

$companies = Teamleader::companies()->list(
    ['status' => 'active', 'tags' => ['VIP']],
    ['page_size' => 50, 'page_number' => 1, 'sort' => 'name', 'sort_order' => 'asc']
);

// With sideloading via options
$companies = Teamleader::companies()->list([], ['include' => 'custom_fields,responsible_user']);
```

---

### `info(string $id, string|array|null $includes = null)`

```php
$company = Teamleader::companies()->info('company-uuid');

// With includes — string or array form
$company = Teamleader::companies()->info('company-uuid', 'addresses,responsible_user');

// Via fluent interface
$company = Teamleader::companies()
    ->withResponsibleUser()
    ->withAddresses()
    ->info('company-uuid');
```

---

### `create(array $data)`

```php
$company = Teamleader::companies()->create([
    'name'                   => 'Acme Corp',
    'vat_number'             => 'BE0123456789',
    'website'                => 'https://acme.be',
    'language'               => 'nl',
    'responsible_user_id'    => 'user-uuid',
    'business_type_id'       => 'business-type-uuid',
    'marketing_mails_consent' => true,
    'emails'                 => [['type' => 'primary', 'email' => 'info@acme.be']],
    'telephones'             => [['type' => 'phone', 'number' => '+32 3 123 45 67']],
    'addresses'              => [[
        'type'    => 'primary',
        'address' => [
            'line_1'      => 'Keizerstraat 1',
            'postal_code' => '2000',
            'city'        => 'Antwerp',
            'country'     => 'BE',
        ],
    ]],
    'tags' => ['Partner', 'VIP'],
]);
```

---

### `update(string $id, array $data)`

```php
Teamleader::companies()->update('company-uuid', [
    'name'                => 'Acme Corp Ltd',
    'responsible_user_id' => 'new-user-uuid',
]);
```

---

### `delete(string $id)`

```php
Teamleader::companies()->delete('company-uuid');
```

---

### `uploadLogo(string $id, string|null $image)`

Uploads or removes a company logo. The image must be a base64 data URI starting with `data:image/`. Pass `null` to
remove an existing logo. An `InvalidArgumentException` is thrown if a non-null value doesn't start with `data:image/`.

Returns empty array (HTTP 204) on success.

```php
$imageData = base64_encode(file_get_contents('/path/to/logo.png'));
Teamleader::companies()->uploadLogo('company-uuid', 'data:image/png;base64,' . $imageData);

// Remove logo
Teamleader::companies()->uploadLogo('company-uuid', null);
```

---

## Tag Methods

### `tag(string $id, string|array $tags)`

Adds one or more tags. Accepts a string or array.

```php
Teamleader::companies()->tag('company-uuid', ['VIP', 'Partner']);
Teamleader::companies()->tag('company-uuid', 'Enterprise'); // string also accepted
```

### `untag(string $id, string|array $tags)`

Removes one or more tags.

```php
Teamleader::companies()->untag('company-uuid', ['Trial']);
```

### `manageTags(string $id, array $tagsToAdd = [], array $tagsToRemove = [])`

Adds and removes tags in one call. Makes **two separate API calls** internally — one `tag`, one `untag`.
Returns `['tagged' => [...], 'untagged' => [...]]`.

```php
$result = Teamleader::companies()->manageTags(
    'company-uuid',
    ['Active', 'Paid'],   // add
    ['Trial', 'Prospect'] // remove
);
```

---

## Helper Methods

### Search and filter helpers

| Method                                      | Filter applied                              |
|---------------------------------------------|---------------------------------------------|
| `search(string $term)`                      | `term` (searches name, VAT, emails, phones) |
| `searchAll(string $query)`                  | `term` (alias)                              |
| `byEmail(string $email)`                    | `email` → `{type: primary, email: $email}`  |
| `byVatNumber(string $vat)`                  | `vat_number`                                |
| `byNationalIdentificationNumber(string $n)` | `national_identification_number`            |
| `byName(string $name)`                      | `name` (fuzzy)                              |
| `active()`                                  | `status: active`                            |
| `deactivated()`                             | `status: deactivated`                       |
| `withTags(string\|array $tags)`             | `tags`                                      |
| `updatedSince(string $date)`                | `updated_since`                             |

All helpers accept an optional `$options` array as their last parameter for pagination/sorting.

```php
$companies = Teamleader::companies()->byEmail('info@acme.be');
$companies = Teamleader::companies()->byVatNumber('BE0123456789');
$companies = Teamleader::companies()->active(['page_size' => 100]);
$companies = Teamleader::companies()->updatedSince('2025-01-01T00:00:00+00:00');
```

### Fluent include methods

| Method                      | Include added                                            |
|-----------------------------|----------------------------------------------------------|
| `withAddresses()`           | `addresses`                                              |
| `withBusinessType()`        | `business_type`                                          |
| `withResponsibleUser()`     | `responsible_user`                                       |
| `withAddedBy()`             | `added_by`                                               |
| `withCustomFields()`        | `custom_fields`                                          |
| `withPriceList()`           | `price_list`                                             |
| `withCommonRelationships()` | `addresses`, `responsible_user`, `business_type`, `tags` |

---

## Filters

| Filter                           | Type            | Description                                    |
|----------------------------------|-----------------|------------------------------------------------|
| `ids`                            | array           | Filter by UUIDs                                |
| `email`                          | array or string | Email — string auto-wraps as `{type: primary}` |
| `name`                           | string          | Fuzzy name search                              |
| `vat_number`                     | string          | Exact VAT number                               |
| `national_identification_number` | string          | National ID number                             |
| `term`                           | string          | Searches name, VAT, emails, phones             |
| `tags`                           | array           | All specified tags must be present             |
| `updated_since`                  | string          | ISO 8601 datetime                              |
| `status`                         | string          | `active` or `deactivated`                      |
| `marketing_mails_consent`        | bool            | Marketing consent flag                         |

---

## Sorting

| Field        | Description       |
|--------------|-------------------|
| `name`       | Company name      |
| `added_at`   | Date added        |
| `updated_at` | Date last updated |

```php
$companies = Teamleader::companies()->list([], [
    'sort'       => 'name',
    'sort_order' => 'asc',
]);
```

---

## Sideloading

| Include            | Description                 |
|--------------------|-----------------------------|
| `addresses`        | Address records             |
| `business_type`    | Legal structure             |
| `responsible_user` | Responsible user            |
| `added_by`         | User who created the record |
| `tags`             | Applied tags                |
| `custom_fields`    | Custom field values         |
| `price_list`       | Assigned price list         |

See [[Sideloading]] for general patterns.

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'                      => 'company-uuid',
            'name'                    => 'Acme Corp',
            'status'                  => 'active',
            'vat_number'              => 'BE0123456789',
            'website'                 => 'https://acme.be',
            'language'                => 'nl',
            'marketing_mails_consent' => true,
            'responsible_user'        => ['type' => 'user', 'id' => 'user-uuid'],
            'added_at'                => '2025-01-15T10:00:00+00:00',
            'updated_at'              => '2025-03-01T09:00:00+00:00',
        ],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 142],
]
```

---

## Usage Examples

### Upsert by VAT number

```php
$existing = Teamleader::companies()->byVatNumber('BE0123456789');

if (!empty($existing['data'])) {
    Teamleader::companies()->update($existing['data'][0]['id'], ['name' => 'Acme Corp Ltd']);
} else {
    Teamleader::companies()->create(['name' => 'Acme Corp', 'vat_number' => 'BE0123456789']);
}
```

### Paginate all active companies

```php
$all  = [];
$page = 1;

do {
    $response = Teamleader::companies()->list(
        ['status' => 'active'],
        ['page_size' => 100, 'page_number' => $page]
    );
    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

### Load with custom fields

```php
$company = Teamleader::companies()
    ->withCustomFields()
    ->withResponsibleUser()
    ->info('company-uuid');

foreach ($company['data']['custom_fields'] ?? [] as $field) {
    $id    = $field['definition']['id'];
    $value = $field['value'];
}
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\{NotFoundException, ValidationException, TeamleaderException};

try {
    Teamleader::companies()->uploadLogo('company-uuid', 'not-a-data-uri');
} catch (InvalidArgumentException $e) {
    // 'Image must be a base64 data URI (e.g. data:image/png;base64,...) or null'
}

try {
    Teamleader::companies()->info('company-uuid');
} catch (NotFoundException $e) {
    // Company does not exist
}

try {
    Teamleader::companies()->create(['name' => '']);
} catch (ValidationException $e) {
    $errors = $e->getAllErrors();
}
```

---

## Related Resources

- [[Contacts]] — Contacts can be linked to companies
- [[Business-Types]] — Legal structures used on company creation
- [[Tags]] — Tag reference list
- [[Deals]] — Deals reference companies as customers
- [[Invoices]] — Invoices can be issued to companies
- [[Sideloading]] — Loading related data
- [[Filtering]] — Filter and pagination reference

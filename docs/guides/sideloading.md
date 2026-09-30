# Sideloading

A few Teamleader endpoints accept `includes`, which adds optional data to the
response: custom field values on a list, related contacts on a company,
supplier details on a product. **Far fewer than you might expect.** Most related
records — responsible user, addresses, tags, price list — are returned as a
`{type, id}` reference by default and cannot be sideloaded at all.

The complete list is the [**Includes** table in the API reference](../reference/README.md#includes).
No other resource accepts includes.

## Requesting includes

### Fluent `with()`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$companies = Teamleader::companies()
    ->with('custom_fields')
    ->list(['status' => 'active']);

$company = Teamleader::companies()
    ->with(['related_companies', 'related_contacts'])
    ->info('company-uuid');
```

`with()` takes a single value, a comma-separated string or an array. The
includes apply to the next request only, including one that throws, so they
never leak into a later call.

Many resources also have named helpers, such as `withCustomFields()` or
`withRelatedContacts()`. They are listed under **Methods** on each reference
page.

### Options array

```php
$contacts = Teamleader::contacts()->list([], ['include' => 'custom_fields']);

$company = Teamleader::companies()->info('company-uuid', 'related_companies,related_contacts');
```

On `list()`, pass `include` (or `includes`) in the options. On `info()`, it is the
second argument.

## `list` and `info` differ

Includes are declared per endpoint, and the two often take different sets:

| | `companies.list` | `companies.info` |
|---|---|---|
| `custom_fields` | include | returned by default; asking for it throws |
| `related_contacts` | not accepted | include |

Each resource's reference page shows both sets.

## Unknown includes throw

```php
Teamleader::contacts()->with('responsible_user')->list();
// InvalidArgumentException: Invalid include for contacts.list: responsible_user.
// Accepts: custom_fields.
```

The API ignores include values it does not know and answers `200`. Because the
field often arrives by default anyway, a wrong include looks like it works —
which is how six phantom includes ended up documented for companies before
v2.2.4.

## Custom fields

On companies, contacts, deals, orders and projects, custom field values come
with `list()` only when requested:

```php
$companies = Teamleader::companies()->withCustomFields()->list([], ['page_size' => 100]);

foreach ($companies['data'] as $company) {
    foreach ($company['custom_fields'] ?? [] as $field) {
        $definitionId = $field['definition']['id'];
        $value = $field['value'];
    }
}
```

On `companies.info` and `contacts.info` they are always returned. The shape of
`value` depends on the field type; see [Custom fields](../reference/general/custom-fields.md).

## Avoid a request per record

Prefer one sideloaded `list()` to an `info()` per row. For related records that
cannot be sideloaded, collect the ids and fetch them with one `ids` filter:

```php
$userIds = array_values(array_unique(array_column(array_column($companies['data'], 'responsible_user'), 'id')));

$users = Teamleader::users()->list(['ids' => $userIds]);
```

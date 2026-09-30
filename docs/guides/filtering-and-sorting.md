# Filtering and Sorting

Every `list()` call takes two arrays: **filters** first, **options** second.

```php
Teamleader::companies()->list(
    ['status' => 'active', 'updated_since' => '2026-01-01T00:00:00+00:00'],   // filters
    ['sort' => 'name', 'page_size' => 50]                                      // options
);
```

## Filters

The SDK wraps the first array in the `filter` object the API expects. Each
endpoint accepts its own set; the page for each resource in the
[API reference](../reference/README.md) lists them with a description.

```php
// Scalar filters
Teamleader::companies()->list(['status' => 'active']);

// List filters
Teamleader::deals()->list(['ids' => ['uuid-1', 'uuid-2']]);

// Object filters
Teamleader::companies()->list([
    'email' => ['type' => 'primary', 'email' => 'info@example.com'],
]);

// Search
Teamleader::contacts()->list(['term' => 'janssens']);
```

Several filters that the API types as a list accept a single value too, and
the SDK wraps it — `'ids' => 'uuid-1'` is sent as `['uuid-1']`. The reference
notes where that applies.

### Unknown filters throw

```php
Teamleader::companies()->list(['name' => 'Acme']);
// InvalidArgumentException: Invalid filter key 'name' for companies.list.
// Supported filters: ids, email, vat_number, ... companies.list has no name
// filter — use 'term', which searches name, VAT, emails and telephones.
```

The API would have answered `200 OK` and returned every company. See
[Validation](validation.md).

### Empty values are dropped

A filter whose value is `null`, `''` or `[]` is not sent, so optional filters
need no cleaning up first:

```php
Teamleader::companies()->list([
    'status'        => $status,          // skipped when null
    'updated_since' => $since,           // skipped when null
    'tags'          => $tags ?? [],      // skipped when empty
]);
```

The exception is a filter where `null` itself means something. On contacts,
`company_id => null` finds contacts linked to no company — use
`Teamleader::contacts()->withoutCompany()` to make that explicit.

### Helpers

Most resources have named helpers for their common filters — `search()`,
`byIds()`, `updatedSince()`, `forUser()` and so on. They build the same filter
array and are listed under **Methods** on each reference page.

## Sorting

Sorting goes in the options. Only resources whose reference page has a
**Sorting** section accept it; the rest throw on any `sort` option.

```php
// One field, ascending
Teamleader::companies()->list([], ['sort' => 'name']);

// One field, with a direction
Teamleader::companies()->list([], ['sort' => 'name', 'sort_order' => 'desc']);

// Several fields as a field => direction map
Teamleader::companies()->list([], ['sort' => ['name' => 'asc', 'added_at' => 'desc']]);

// Several fields as sort objects — the API's own shape
Teamleader::companies()->list([], ['sort' => [
    ['field' => 'name',     'order' => 'asc'],
    ['field' => 'added_at', 'order' => 'desc'],
]]);
```

A field the endpoint does not accept throws. So does an order other than `asc`
or `desc`:

```php
Teamleader::companies()->list([], ['sort' => 'created_at']);
// InvalidArgumentException: Invalid sort field: created_at.
// Accepted: name, added_at, updated_at.
```

The API would have ignored it and returned its default order.

Some endpoints accept only one direction — `files.list` sorts on `updated_at`,
descending only — and the reference page says so where it applies.

## Options

| Option | Purpose |
|---|---|
| `page_size`, `page_number` | [Pagination](pagination.md) |
| `sort`, `sort_order` | Sorting, above |
| `include` (or `includes`) | [Sideloading](sideloading.md) |

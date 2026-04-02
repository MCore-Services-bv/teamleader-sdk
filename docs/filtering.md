# Filtering

How filtering, sorting, and pagination work across all SDK resources.

## Overview

All resources use `FilterTrait` to build API request parameters. Filters, sorting, and pagination are always passed as
separate arguments to `list()` — filters in the first array, options (sort, page) in the second.

Null values and empty arrays in filters are automatically stripped before the request is sent, so it is safe to pass
conditional filters without pre-cleaning them.

---

## Filters

Filters are passed as the first argument to `list()`. The SDK wraps them in a `filter` key in the POST body.

```php
// Single filter
$companies = Teamleader::companies()->list([
    'status' => 'active'
]);

// Multiple filters
$companies = Teamleader::companies()->list([
    'status'        => 'active',
    'updated_since' => '2025-01-01T00:00:00+00:00',
]);

// Nested filter (e.g. email search)
$companies = Teamleader::companies()->list([
    'email' => [
        'type'  => 'primary',
        'email' => 'info@example.com',
    ],
]);

// Array-value filter (e.g. status must be an array for some resources)
$expenses = Teamleader::expenses()->list([
    'payment_statuses' => ['not_paid', 'paid'],
]);
```

### Null and Empty Values Are Stripped

You do not need to clean up optional filters before passing them. The SDK removes any filter key whose value is `null`
or an empty array.

```php
// This is safe — null filters are ignored
$companies = Teamleader::companies()->list([
    'status'        => $status ?: null,      // ignored if null
    'updated_since' => $since ?: null,       // ignored if null
    'tag_ids'       => $tagIds ?: [],        // ignored if empty array
]);
```

---

## Sorting

Sorting is passed in the options (second) argument as `sort` and optionally `sort_order`.

```php
// Simple sort (ascending by default)
$companies = Teamleader::companies()->list([], [
    'sort' => 'name',
]);

// With explicit direction
$companies = Teamleader::companies()->list([], [
    'sort'       => 'name',
    'sort_order' => 'desc',
]);

// Pre-configured sort array (passed directly to the API)
$companies = Teamleader::companies()->list([], [
    'sort' => [
        ['field' => 'name',       'order' => 'asc'],
        ['field' => 'created_at', 'order' => 'desc'],
    ],
]);
```

Not all resources support sorting. Check the resource's capability matrix. Passing a sort to a resource that does not
support it is ignored.

---

## Pagination

Pagination is passed in the options (second) argument. Defaults are `page_size: 20` and `page_number: 1`.

```php
$companies = Teamleader::companies()->list([], [
    'page_size'   => 50,
    'page_number' => 2,
]);
```

The response `meta` object contains the total number of matching records:

```php
$response = Teamleader::companies()->list([], ['page_size' => 50]);

$total   = $response['meta']['matches'];
$current = $response['meta']['page']['number'];
$size    = $response['meta']['page']['size'];
```

### Fetching All Pages

```php
function fetchAll(string $resource, array $filters = []): array
{
    $all      = [];
    $page     = 1;
    $pageSize = 100;

    do {
        $response = Teamleader::{$resource}()->list($filters, [
            'page_size'   => $pageSize,
            'page_number' => $page,
        ]);

        $all  = array_merge($all, $response['data']);
        $page++;
    } while (count($response['data']) === $pageSize);

    return $all;
}

$allCompanies = fetchAll('companies', ['status' => 'active']);
```

---

## Combining Filters, Sorting, and Pagination

All three can be combined freely:

```php
$companies = Teamleader::companies()->list(
    // Filters
    [
        'status'        => 'active',
        'updated_since' => '2025-01-01T00:00:00+00:00',
    ],
    // Options
    [
        'sort'        => 'name',
        'sort_order'  => 'asc',
        'page_size'   => 50,
        'page_number' => 1,
    ]
);
```

---

## Sideloading (Includes)

Sideloading is also passed via the options array, using the `include` key (singular). The SDK internally
sends `includes` (plural) to the Teamleader API — you never need to write `includes` yourself.

```php
// In list() — via options
$companies = Teamleader::companies()->list([], [
    'include' => 'custom_fields,responsible_user',
]);

// In info() — second argument
$company = Teamleader::companies()->info('company-uuid', 'custom_fields');

// Via fluent with() method (recommended)
$company = Teamleader::companies()
    ->with('custom_fields,responsible_user')
    ->info('company-uuid');
```

See [[Sideloading]] for the full guide.

---

## Related Resources

- [[Sideloading]] — Loading related data in a single request
- [[Resources]] — Resource architecture and capabilities
- [[Errors]] — Exception reference

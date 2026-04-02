# Deal Sources

Read deal source definitions in Teamleader Focus.

## Overview

The Deal Sources resource provides read-only access to the sources that can be assigned to deals — Web, Referral, Cold
Call, and so on. Sources are defined in Teamleader and cannot be created or modified through the API.

Access via `Teamleader::dealSources()`.

## Endpoint

`dealSources`

## Capabilities

| Capability  | Supported                                          |
|-------------|----------------------------------------------------|
| Pagination  | ✅ Supported                                        |
| Filtering   | ✅ Supported (`ids` only)                           |
| Sorting     | ✅ Supported (`name` only — always forced to `asc`) |
| Sideloading | ❌ Not supported                                    |
| Creation    | ❌ Not supported                                    |
| Update      | ❌ Not supported                                    |
| Deletion    | ❌ Not supported                                    |

> **Sort behaviour:** Every `list()` call applies `sort: [{field: name, order: asc}]` — even when no sort is requested.
> Any other sort field or order is silently replaced with `name`/`asc`.

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All sources — always sorted by name asc
$sources = Teamleader::dealSources()->list();

// Specific sources by ID
$sources = Teamleader::dealSources()->list(['ids' => ['source-uuid-1', 'source-uuid-2']]);

// With pagination
$sources = Teamleader::dealSources()->list([], ['page_size' => 50, 'page_number' => 1]);
```

---

## Helper Methods

### `all()`

Fetches all sources without pagination constraints. Returns the raw API response.

```php
$sources = Teamleader::dealSources()->all();
```

### `selectOptions()`

Returns a flat `[id => name]` map — useful for form dropdowns. Calls `all()` internally.

```php
$options = Teamleader::dealSources()->selectOptions();
// ['uuid-1' => 'Cold Call', 'uuid-2' => 'Referral', 'uuid-3' => 'Web']
```

### `search(string $query)`

**Client-side filtering** — calls `all()` then filters by name in PHP. Case-insensitive partial match.

```php
$sources = Teamleader::dealSources()->search('ref');
// Matches sources whose name contains 'ref', e.g. 'Referral'
```

---

## Filters

| Filter | Type  | Description            |
|--------|-------|------------------------|
| `ids`  | array | Filter by source UUIDs |

---

## Response Structure

```php
[
    'data' => [
        ['id' => 'source-uuid', 'name' => 'Cold Call'],
        ['id' => 'source-uuid', 'name' => 'Referral'],
        ['id' => 'source-uuid', 'name' => 'Web'],
    ],
    'meta' => ['page' => ['size' => 20, 'number' => 1], 'matches' => 6],
]
```

---

## Usage Examples

### Populate a source dropdown

```php
$options = Teamleader::dealSources()->selectOptions();
// Use directly in a select: ['uuid' => 'Source Name', ...]
```

### Find a source by name

```php
$matches = Teamleader::dealSources()->search('referral');
$source  = $matches['data'][0] ?? null;
```

### Cache sources

```php
$sources = Cache::remember('tl_deal_sources', 3600, function () {
    return Teamleader::dealSources()->all();
});
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $sources = Teamleader::dealSources()->list();
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Deals]] — Sources are assigned to deals via `source_id`
- [[Filtering]] — General filter reference

# Tags

Read tags in Teamleader Focus.

## Overview

The Tags resource lets you list and paginate through the tags that exist in your account. Tags have no UUID — they are
identified only by their string value. They are created automatically when you apply them to an entity (company,
contact, deal, etc.) and are removed automatically when no longer used by any entity.

You cannot create, update, or delete tags through this resource.

## Endpoint

`tags`

## Capabilities

| Capability  | Supported                      |
|-------------|--------------------------------|
| Pagination  | ✅ Supported                    |
| Filtering   | ❌ Not supported                |
| Sorting     | ✅ Supported (`tag` field only) |
| Sideloading | ❌ Not supported                |
| Creation    | ❌ (created via entity tagging) |
| Update      | ❌ Not supported                |
| Deletion    | ❌ (removed when unused)        |

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns tags with pagination and sorting. Filters are accepted for signature compatibility but ignored.

**Sort behaviour:** The only valid sort field is `tag` — any other value is silently forced to `tag`. The only valid
sort order is `asc` — any other value is silently forced to `asc`.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All tags (first page)
$tags = Teamleader::tags()->list();

// With pagination
$tags = Teamleader::tags()->list([], [
    'page_size'   => 50,
    'page_number' => 2,
]);

// Sorted — 'tag' / 'asc' are the only accepted values
$tags = Teamleader::tags()->list([], [
    'sort'       => 'tag',
    'sort_order' => 'asc',
]);
```

---

## Helper Methods

### `all()`

Paginates through all pages automatically and returns a merged array. Uses page size 100 with a safety limit of 50
pages. Returns a custom structure (not the standard API response format):

```php
$result = Teamleader::tags()->all();
// ['data' => [...all tags...], 'total_count' => 312]
```

### `search(string $query, bool $exactMatch = false)`

**Client-side filtering** — calls `all()` first, then filters in PHP. Not a server-side API filter. Partial matching by
default; pass `true` for exact match.

```php
// Partial match (default)
$tags = Teamleader::tags()->search('VIP');
// Matches: 'VIP', 'VIP Customer', 'VIP Partner'

// Exact match
$tags = Teamleader::tags()->search('VIP', true);
// Matches: 'VIP' only
```

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        ['tag' => 'Customer'],
        ['tag' => 'Decision Maker'],
        ['tag' => 'Enterprise'],
        ['tag' => 'Partner'],
        ['tag' => 'VIP'],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 42,
    ],
]
```

> **No UUID:** Tags have only a `tag` string field. Use the tag name as the identifier.

---

## How Tags Work

Tags are not managed through this resource. They are created when applied to an entity:

```php
// Creates the 'VIP' tag if it doesn't exist
Teamleader::companies()->tag('company-uuid', ['VIP']);

// Now visible via the Tags resource
$tags = Teamleader::tags()->list(); // includes 'VIP'
```

Tags are removed from the Tags list when no entity uses them any more.

### Entities that support tagging

- Companies — `Teamleader::companies()->tag()`
- Contacts — `Teamleader::contacts()->tag()`
- Deals — `Teamleader::deals()->tag()`

---

## Usage Examples

### Get all tags for a select list

```php
$result  = Teamleader::tags()->all();
$options = array_column($result['data'], 'tag', 'tag');
// ['VIP' => 'VIP', 'Partner' => 'Partner', ...]
```

### Paginate manually

```php
$all  = [];
$page = 1;

do {
    $response = Teamleader::tags()->list([], [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

### Check whether a tag exists

```php
$result = Teamleader::tags()->search('VIP', true); // exact match
$exists = !empty($result['data']);
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $tags = Teamleader::tags()->list();
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Companies]] — `tag()`, `untag()`, `manageTags()` on companies
- [[Contacts]] — `tag()`, `untag()`, `manageTags()` on contacts
- [[Deals]] — `tag()`, `untag()` on deals
- [[Filtering]] — General filter reference

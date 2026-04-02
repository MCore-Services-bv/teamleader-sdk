# Migrate

Translate legacy numeric IDs and types to new UUID-based values.

## Overview

The Migrate resource helps with one-time migrations from the deprecated numeric-ID Teamleader API to the current
UUID-based API. Three translation endpoints are available: activity type lookup, tax rate lookup, and general numeric
ID → UUID conversion.

Access via `Teamleader::migrate()`.

> **`list()` and `info()` throw** `InvalidArgumentException` — only the three dedicated methods are supported.
>
> **Response types can differ from request types.** `task` becomes `todo`, and `meeting`/`call` become `event` in
> the `data.type` field of `id()` responses.
>
> **`batchIds()` makes one API call per ID** — it loops internally.

## Endpoint

`migrate`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ❌ Not supported |
| Filtering   | ❌ Not supported |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ❌ Not supported |
| Update      | ❌ Not supported |
| Deletion    | ❌ Not supported |

---

## Methods

### `activityType(string $type)`

Translates a generic activity type string into the account's corresponding Activity Type UUID.
Throws `InvalidArgumentException` for invalid types.

**Valid types:** `meeting`, `call`, `task`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$result = Teamleader::migrate()->activityType('meeting');
$uuid   = $result['data']['id'];

// Use UUID when creating a calendar event
Teamleader::calenderEvents()->create([
    'activity_type_id' => $uuid,
    'title'            => 'Client call',
    'starts_at'        => '2025-05-01T10:00:00+02:00',
    'ends_at'          => '2025-05-01T11:00:00+02:00',
]);
```

---

### `taxRate(string $departmentId, string $taxRate)`

Translates an old percentage string (e.g. `"21"`) to the corresponding Tax Rate UUID for a given department.
Throws `InvalidArgumentException` if either parameter is empty or if `$taxRate` is not numeric.

```php
$result    = Teamleader::migrate()->taxRate('dept-uuid', '21');
$taxRateId = $result['data']['id'];

// Use UUID on an invoice line item
// 'tax_rate_id' => $taxRateId
```

---

### `id(string $type, int $id)`

Translates an old positive integer ID to a new UUID. Throws `InvalidArgumentException` if `$type` is not in the
supported list or if `$id < 1`.

```php
$result  = Teamleader::migrate()->id('contact', 123);
$newUuid = $result['data']['id'];
$newType = $result['data']['type']; // may differ — see note below
```

> **Response type transforms:**
> - `task` request → `todo` in response
> - `meeting` or `call` request → `event` in response
> - All others match the request type

**Supported request types:**
`account`, `user`, `department`, `product`, `contact`, `company`, `deal`, `dealPhase`, `project`, `milestone`, `task`, `meeting`, `call`, `ticket`, `invoice`, `creditNote`, `subscription`, `quotation`, `timeTracking`, `customField`

---

## Helper Methods

### `batchIds(string $type, array $ids)`

Convenience wrapper that calls `id()` for each entry. Returns `[oldId => newUuid, ...]`. Throws if `$type` is
invalid, `$ids` is empty, or any entry is not an integer.

```php
$mapping = Teamleader::migrate()->batchIds('contact', [1, 2, 3]);
// [1 => 'uuid-1', 2 => 'uuid-2', 3 => 'uuid-3']
```

### `getActivityTypes()` / `getResourceTypes()`

Return the valid type arrays for input validation.

```php
$validActivityTypes = Teamleader::migrate()->getActivityTypes(); // ['meeting', 'call', 'task']
$validResourceTypes = Teamleader::migrate()->getResourceTypes(); // ['contact', 'company', ...]
```

### `isValidActivityType(string $type)` / `isValidResourceType(string $type)`

```php
if (Teamleader::migrate()->isValidResourceType($requestedType)) {
    $result = Teamleader::migrate()->id($requestedType, $legacyId);
}
```

---

## Usage Examples

### Database migration script

```php
$contacts = DB::table('contacts')
    ->whereNotNull('teamleader_legacy_id')
    ->whereNull('teamleader_uuid')
    ->get();

foreach ($contacts as $contact) {
    try {
        $result = Teamleader::migrate()->id('contact', $contact->teamleader_legacy_id);

        DB::table('contacts')
            ->where('id', $contact->id)
            ->update(['teamleader_uuid' => $result['data']['id']]);
    } catch (Exception $e) {
        Log::error("Failed to migrate contact {$contact->id}: {$e->getMessage()}");
    }
}
```

### Cache migrated IDs

```php
function getMigratedUuid(string $type, int $legacyId): string
{
    return Cache::remember("tl_migrate_{$type}_{$legacyId}", 86400, function () use ($type, $legacyId) {
        return Teamleader::migrate()->id($type, $legacyId)['data']['id'];
    });
}
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid activity type
try {
    Teamleader::migrate()->activityType('project');
} catch (InvalidArgumentException $e) {
    // 'Invalid activity type: project. Must be one of: meeting, call, task'
}

// Non-numeric tax rate
try {
    Teamleader::migrate()->taxRate('dept-uuid', 'vat21');
} catch (InvalidArgumentException $e) {
    // 'Tax rate must be a numeric value (as string)'
}

// Invalid resource type
try {
    Teamleader::migrate()->id('tag', 42);
} catch (InvalidArgumentException $e) {
    // 'Invalid resource type: tag. Must be one of: ...'
}

// ID not found in legacy system (API returns 404)
try {
    Teamleader::migrate()->id('contact', 999999);
} catch (TeamleaderException $e) {
    // 404 — legacy ID does not exist
}

// list() not supported
try {
    Teamleader::migrate()->list();
} catch (InvalidArgumentException $e) {
    // 'The migrate resource does not support list operations.'
}
```

---

## Related Resources

- [[Accounts]] — Check Projects version (separate migration concern)
- [[Activity-Types]] — The UUID-based activity types resource
- [[Tax-Rates]] — The UUID-based tax rates resource
- [[Contacts]] — Target resource after contact ID migration
- [[Companies]] — Target resource after company ID migration
- [[Invoices]] — Target resource after invoice ID migration

# Ticket Status

Read ticket status definitions in Teamleader Focus.

## Overview

Ticket statuses define the workflow states a ticket can move through. This resource is read-only — statuses are
configured in the Teamleader Focus web interface.

Access via `Teamleader::ticketStatus()`.

> **No pagination.** `list()` returns all statuses in a single response.
>
> **`findByType()` is client-side** — it calls `list()` then filters in PHP.
>
> **`byIds()` throws on an empty array.**

## Endpoint

`ticketStatus`

## Capabilities

| Capability  | Supported                |
|-------------|--------------------------|
| Pagination  | ❌ Not supported          |
| Filtering   | ✅ Supported (`ids` only) |
| Sorting     | ❌ Not supported          |
| Sideloading | ❌ Not supported          |
| Creation    | ❌ Not supported          |
| Update      | ❌ Not supported          |
| Deletion    | ❌ Not supported          |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$statuses = Teamleader::ticketStatus()->list();

$statuses = Teamleader::ticketStatus()->list(['ids' => ['uuid-1', 'uuid-2']]);
```

---

## Helper Methods

### `byIds(array $ids)`

Throws `InvalidArgumentException` if `$ids` is empty.

```php
$statuses = Teamleader::ticketStatus()->byIds(['uuid-1', 'uuid-2']);
```

### `findByType(string $type)` — client-side

`$type` is validated before calling `list()`. Returns an array of matching statuses or `null`.

```php
$openStatuses   = Teamleader::ticketStatus()->findByType('open');
$closedStatuses = Teamleader::ticketStatus()->findByType('closed');
```

### `customStatuses()` — client-side

Returns all statuses with `type: custom`, or `null` if none exist.

```php
$custom = Teamleader::ticketStatus()->customStatuses();
```

### `asOptions()` — client-side

Returns `[id => label]` map for dropdowns. Uses the `label` field if present, falls back to `status`.

```php
$options = Teamleader::ticketStatus()->asOptions();
// ['uuid-1' => 'New', 'uuid-2' => 'Open', 'uuid-3' => 'My Custom Status']
```

---

## Valid Status Types

| Type                   | Meaning                          |
|------------------------|----------------------------------|
| `new`                  | Newly created tickets            |
| `open`                 | Tickets being actively worked on |
| `waiting_for_client`   | Awaiting customer response       |
| `escalated_thirdparty` | Escalated to a third party       |
| `closed`               | Resolved or closed tickets       |
| `custom`               | Account-specific custom states   |

---

## Response Structure

```php
[
    'data' => [
        [
            'id'     => 'status-uuid',
            'status' => 'open',
            'label'  => 'Open',       // present on custom statuses; may equal status on standard ones
        ],
        [
            'id'     => 'custom-uuid',
            'status' => 'custom',
            'label'  => 'Awaiting Parts',
        ],
    ],
]
```

---

## Usage Example

```php
// Build a map of status names to IDs for ticket creation
$statuses = Teamleader::ticketStatus()->asOptions();
// Cache this — statuses rarely change
$openStatusId = array_search('Open', $statuses);

Teamleader::tickets()->create([
    'subject'          => 'Login issue',
    'customer'         => ['type' => 'company', 'id' => 'company-uuid'],
    'ticket_status_id' => $openStatusId,
]);
```

---

## Error Handling

```php
use InvalidArgumentException;

// Empty IDs on byIds()
try {
    Teamleader::ticketStatus()->byIds([]);
} catch (InvalidArgumentException $e) {
    // 'At least one ticket status ID is required'
}

// Invalid type on findByType()
try {
    Teamleader::ticketStatus()->findByType('resolved');
} catch (InvalidArgumentException $e) {
    // 'Invalid status type. Must be one of: new, open, waiting_for_client, escalated_thirdparty, closed, custom'
}
```

---

## Related Resources

- [[Tickets]] — Tickets reference a `ticket_status_id`

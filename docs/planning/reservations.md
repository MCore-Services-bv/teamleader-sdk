# Reservations

Manage planning reservations in Teamleader Focus.

## Overview

The Reservations resource allows you to create, update, delete, and list planning reservations in Teamleader. A reservation assigns a plannable item to a user or team for a specific date and duration.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
- [Convenience Methods](#convenience-methods)
- [Filters Reference](#filters-reference)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`reservations`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

List reservations with optional filters and pagination.

**Parameters:**
- `filters` (array, optional): Filters to narrow results — see [Filters Reference](#filters-reference)
- `options` (array, optional): Pagination options
    - `page_size` (int): Number of results per page (default: 20)
    - `page_number` (int): Page number (default: 1)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all reservations
$reservations = Teamleader::reservations()->list();

// Filter by date range
$reservations = Teamleader::reservations()->list([
    'start_date' => '2024-01-01',
    'end_date'   => '2024-01-31',
]);

// Filter by plannable items
$reservations = Teamleader::reservations()->list([
    'plannable_item_ids' => [
        '46156648-87c6-478d-8aa7-1dc3a00dacab',
    ],
]);

// Filter by assignee
$reservations = Teamleader::reservations()->list([
    'assignees' => [
        ['type' => 'user', 'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'],
    ],
]);

// Get unassigned reservations (pass null in assignees array)
$reservations = Teamleader::reservations()->list([
    'assignees' => [null],
]);

// With pagination
$reservations = Teamleader::reservations()->list([], [
    'page_size'   => 50,
    'page_number' => 2,
]);
```

---

### `create()`

Create a new reservation.

**Parameters:**
- `data` (array, required):
    - `plannable_item_id` (string, required): UUID of the plannable item
    - `date` (string, required): Date in `YYYY-MM-DD` format
    - `duration` (array, required): Duration object
        - `value` (number, required): Duration value
        - `unit` (string, required): Duration unit — must be `minutes`
    - `assignee` (array, required): Assignee object
        - `type` (string, required): `user` or `team`
        - `id` (string, required): UUID of the user or team

**Returns:** `data.id` and `data.type` of the created reservation (HTTP 201)

**Example:**
```php
$reservation = Teamleader::reservations()->create([
    'plannable_item_id' => '46156648-87c6-478d-8aa7-1dc3a00dacab',
    'date'              => '2024-01-12',
    'duration'          => [
        'value' => 60,
        'unit'  => 'minutes',
    ],
    'assignee' => [
        'type' => 'user',
        'id'   => '66abace2-62af-0836-a927-fe3f44b9b47b',
    ],
]);

$reservationId = $reservation['data']['id'];
```

---

### `update()`

Update an existing reservation. All fields are optional — only include the fields you want to change.

**Parameters:**
- `id` (string, required): Reservation UUID
- `data` (array, required):
    - `date` (string, optional): Date in `YYYY-MM-DD` format
    - `duration` (array, optional): Duration object with `value` and `unit`
    - `assignee` (array, optional): Assignee object with `type` and `id`

**Returns:** Empty response (HTTP 204)

**Example:**
```php
// Update date and duration
Teamleader::reservations()->update('01878019-c72c-70dc-b097-7e519c775e35', [
    'date'     => '2024-01-15',
    'duration' => [
        'value' => 120,
        'unit'  => 'minutes',
    ],
]);

// Reassign to a team
Teamleader::reservations()->update('01878019-c72c-70dc-b097-7e519c775e35', [
    'assignee' => [
        'type' => 'team',
        'id'   => 'team-uuid',
    ],
]);
```

---

### `delete()`

Delete a reservation.

**Parameters:**
- `id` (string, required): Reservation UUID

**Returns:** Empty response (HTTP 204)

**Example:**
```php
Teamleader::reservations()->delete('01878019-c72c-70dc-b097-7e519c775e35');
```

---

## Convenience Methods

### `forUser()`

Get all reservations assigned to a specific user.

```php
$reservations = Teamleader::reservations()->forUser('user-uuid');

// With additional filters
$reservations = Teamleader::reservations()->forUser('user-uuid', [
    'filters' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-31'],
]);
```

### `forTeam()`

Get all reservations assigned to a specific team.

```php
$reservations = Teamleader::reservations()->forTeam('team-uuid');
```

### `forDateRange()`

Get reservations within a date range.

```php
$reservations = Teamleader::reservations()->forDateRange('2024-01-01', '2024-01-31');
```

### `unassigned()`

Get all unassigned reservations.

```php
$reservations = Teamleader::reservations()->unassigned();
```

---

## Filters Reference

| Filter | Type | Description |
|--------|------|-------------|
| `plannable_item_ids` | `string[]` | Filter by plannable item UUIDs |
| `start_date` | `string` | Start of date range (YYYY-MM-DD) |
| `end_date` | `string` | End of date range (YYYY-MM-DD) |
| `assignees` | `object[]` | Filter by assignees. Each object: `{type: user\|team, id: UUID}`. Pass `null` to fetch unassigned |
| `sources` | `object[]` | Filter by sources. Each object: `{id: UUID, type: SourceType}` |
| `source_types` | `string[]` | Filter by source types — see valid values below |

**Valid `source_types` values:** `call`, `closingDay`, `dayOffType`, `externalEvent`, `meeting`, `task`

---

## Response Structure

### `list()` Response

```json
{
    "data": [
        {
            "id": "39c64ba9-ebf1-491a-8486-a0b96ff21c07",
            "plannable_item": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "plannableItem"
            },
            "date": "2024-01-11",
            "duration": {
                "unit": "minutes",
                "value": 60
            },
            "assignee": {
                "type": "user",
                "id": "66abace2-62af-0836-a927-fe3f44b9b47b"
            },
            "origin": {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "dayOff"
            }
        }
    ]
}
```

### `create()` Response

```json
{
    "data": {
        "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
        "type": "reservation"
    }
}
```

---

## Usage Examples

### Get a user's reservations for the current month

```php
$reservations = Teamleader::reservations()->forUser('user-uuid', [
    'filters' => [
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date'   => now()->endOfMonth()->format('Y-m-d'),
    ],
]);
```

### Create and then immediately update a reservation

```php
$created = Teamleader::reservations()->create([
    'plannable_item_id' => 'item-uuid',
    'date'              => '2024-06-01',
    'duration'          => ['value' => 30, 'unit' => 'minutes'],
    'assignee'          => ['type' => 'user', 'id' => 'user-uuid'],
]);

Teamleader::reservations()->update($created['data']['id'], [
    'duration' => ['value' => 60, 'unit' => 'minutes'],
]);
```

### Filter by source type

```php
$reservations = Teamleader::reservations()->list([
    'source_types' => ['meeting', 'task'],
    'start_date'   => '2024-01-01',
    'end_date'     => '2024-01-31',
]);
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $reservation = Teamleader::reservations()->create([
        'plannable_item_id' => 'item-uuid',
        'date'              => '2024-01-12',
        'duration'          => ['value' => 60, 'unit' => 'minutes'],
        'assignee'          => ['type' => 'user', 'id' => 'user-uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // Validation error (missing required field, invalid format, etc.)
    Log::error('Validation error: '.$e->getMessage());
} catch (TeamleaderException $e) {
    // API error
    Log::error('API error: '.$e->getMessage());
}
```

---

## Related Resources

- **[Plannable Items](plannable-items.md)** — The items being reserved
- **[User Availability](user-availability.md)** — Check availability before creating reservations
- **[Users](../general/users.md)** — User assignees

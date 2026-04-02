# Calendar Events

Manage calendar events in Teamleader Focus.

## Overview

The Calendar Events resource manages generic scheduled activities — distinct from the more specialised Meetings and
Calls resources. Events require an activity type, a start time, and an end time.

Access via `Teamleader::calenderEvents()` (note the SDK key uses the typo `calenderEvents` — one `a` in calendar).

> **Deletion uses `cancel()`**, not `delete()`. The `delete()` method exists but internally calls `cancel()` — the API
> endpoint is `events.cancel`.

## Endpoint

`events`

## Capabilities

| Capability  | Supported                      |
|-------------|--------------------------------|
| Pagination  | ✅ Supported                    |
| Filtering   | ✅ Supported                    |
| Sorting     | ✅ Supported (`starts_at` only) |
| Sideloading | ❌ Not supported                |
| Creation    | ✅ Supported                    |
| Update      | ✅ Supported                    |
| Deletion    | ✅ Supported (via `cancel()`)   |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$events = Teamleader::calenderEvents()->list();

$events = Teamleader::calenderEvents()->list([
    'user_id'      => 'user-uuid',
    'ends_after'   => '2025-04-01T00:00:00+02:00',
    'starts_before'=> '2025-04-30T23:59:59+02:00',
]);

$events = Teamleader::calenderEvents()->list([], [
    'page_size'   => 50,
    'page_number' => 1,
    'sort'        => [['field' => 'starts_at', 'order' => 'asc']],
]);
```

---

### `info(string $id)`

```php
$event = Teamleader::calenderEvents()->info('event-uuid');
```

---

### `create(array $data)`

Four fields are **required** and validated before the request. Datetime fields are also validated against ISO 8601
format (`YYYY-MM-DDTHH:MM:SS±HH:MM`).

| Required field     | Notes                                 |
|--------------------|---------------------------------------|
| `title`            | Event title                           |
| `activity_type_id` | UUID from the Activity Types resource |
| `starts_at`        | ISO 8601 datetime with timezone       |
| `ends_at`          | ISO 8601 datetime with timezone       |

**Attendees** (optional) — each entry must have `type` (`user` or `contact`) and `id`. Invalid type
throws `InvalidArgumentException`.

**Links** (optional) — each entry must have `type` (`contact`, `company`, or `deal`) and `id`. Invalid type
throws `InvalidArgumentException`.

```php
$event = Teamleader::calenderEvents()->create([
    'title'            => 'Stakeholder Workshop',
    'activity_type_id' => 'activity-type-uuid',
    'starts_at'        => '2025-05-08T09:00:00+02:00',
    'ends_at'          => '2025-05-08T12:00:00+02:00',
    'description'      => 'Annual planning workshop',
    'location'         => 'Ghent office',
    'attendees'        => [
        ['type' => 'user',    'id' => 'user-uuid'],
        ['type' => 'contact', 'id' => 'contact-uuid'],
    ],
    'links' => [
        ['type' => 'deal', 'id' => 'deal-uuid'],
    ],
    'done' => false,
]);
```

---

### `update(mixed $id, array $data)`

The `id` is injected into the request body. Datetime fields are validated if provided.

```php
Teamleader::calenderEvents()->update('event-uuid', [
    'title'     => 'Stakeholder Workshop (updated)',
    'starts_at' => '2025-05-09T09:00:00+02:00',
    'ends_at'   => '2025-05-09T12:00:00+02:00',
]);
```

---

### `cancel(string $id)`

Cancels the event for all attendees. Posts to `events.cancel`.

```php
Teamleader::calenderEvents()->cancel('event-uuid');
```

---

### `delete(mixed $id)`

Internally calls `cancel()`. Both produce identical results.

```php
Teamleader::calenderEvents()->delete('event-uuid');
```

---

## Helper Methods

### Filter helpers

| Method                                                  | Filter applied                                         |
|---------------------------------------------------------|--------------------------------------------------------|
| `forUser(string $userId)`                               | `user_id`                                              |
| `forActivityType(string $typeId)`                       | `activity_type_id`                                     |
| `search(string $term)`                                  | `term` (title and description)                         |
| `byIds(array $ids)`                                     | `ids`                                                  |
| `betweenDates(string $startsAfter, string $endsBefore)` | `ends_after` + `starts_before`                         |
| `forAttendee(string $type, string $id)`                 | `attendee` — validates type (`user`, `contact`)        |
| `forLink(string $type, string $id)`                     | `link` — validates type (`contact`, `company`, `deal`) |

> **`betweenDates()` filter mapping:** The parameters `$startsAfter` and `$endsBefore` map to the API filter
> keys `ends_after` and `starts_before` respectively. This is the actual API filter naming — events are returned where
> they end after your start boundary and start before your end boundary.

```php
$events = Teamleader::calenderEvents()->forUser('user-uuid');
$events = Teamleader::calenderEvents()->forActivityType('type-uuid');
$events = Teamleader::calenderEvents()->search('workshop');
$events = Teamleader::calenderEvents()->betweenDates(
    '2025-05-01T00:00:00+02:00',
    '2025-05-31T23:59:59+02:00'
);
$events = Teamleader::calenderEvents()->forAttendee('user', 'user-uuid');
$events = Teamleader::calenderEvents()->forLink('deal', 'deal-uuid');
```

---

## Filters

| Filter             | Type   | Description                                     |
|--------------------|--------|-------------------------------------------------|
| `ids`              | array  | Filter by event UUIDs                           |
| `user_id`          | string | Events belonging to this user                   |
| `activity_type_id` | string | Filter by activity type UUID                    |
| `ends_after`       | string | ISO 8601 — events ending after this datetime    |
| `starts_before`    | string | ISO 8601 — events starting before this datetime |
| `term`             | string | Search in title and description                 |
| `attendee`         | object | `{type: user\|contact, id: uuid}`               |
| `link`             | object | `{type: contact\|company\|deal, id: uuid}`      |
| `task_id`          | string | Filter events by associated task UUID           |
| `done`             | bool   | Filter by completion status                     |

---

## Sorting

| Field       | Description          |
|-------------|----------------------|
| `starts_at` | Event start datetime |

```php
$events = Teamleader::calenderEvents()->list([], [
    'sort' => [['field' => 'starts_at', 'order' => 'asc']],
]);
```

---

## Usage Examples

### Create a linked event

```php
$event = Teamleader::calenderEvents()->create([
    'title'            => 'Contract Signing',
    'activity_type_id' => 'activity-type-uuid',
    'starts_at'        => '2025-05-15T11:00:00+02:00',
    'ends_at'          => '2025-05-15T12:00:00+02:00',
    'attendees'        => [['type' => 'user', 'id' => 'user-uuid']],
    'links'            => [['type' => 'deal', 'id' => 'deal-uuid']],
]);
```

### Get a user's calendar for the week

```php
$events = Teamleader::calenderEvents()
    ->forUser('user-uuid')
    ->betweenDates(
        '2025-05-12T00:00:00+02:00',
        '2025-05-16T23:59:59+02:00'
    );
```

> Note: `forUser()` returns `$this`, so you can chain `betweenDates()` only if you pass it as a filter option instead —
> or call `list()` directly with both filters combined.

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing required field on create
try {
    Teamleader::calenderEvents()->create([
        'title'    => 'Test',
        'starts_at' => '2025-05-01T10:00:00+02:00',
        'ends_at'   => '2025-05-01T11:00:00+02:00',
        // missing activity_type_id
    ]);
} catch (InvalidArgumentException $e) {
    // 'activity_type_id is required for creating an event'
}

// Invalid datetime format
try {
    Teamleader::calenderEvents()->create([
        'title'            => 'Test',
        'activity_type_id' => 'type-uuid',
        'starts_at'        => '2025-05-01 10:00:00', // wrong format
        'ends_at'          => '2025-05-01T11:00:00+02:00',
    ]);
} catch (InvalidArgumentException $e) {
    // 'starts_at must be in ISO 8601 format (e.g., 2025-02-04T16:00:00+00:00)'
}

// Invalid attendee type
try {
    Teamleader::calenderEvents()->create([
        ...,
        'attendees' => [['type' => 'team', 'id' => 'uuid']],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid attendee type. Must be one of: user, contact'
}

// Invalid link type via forLink()
try {
    Teamleader::calenderEvents()->forLink('project', 'project-uuid');
} catch (InvalidArgumentException $e) {
    // 'Invalid link type. Must be one of: contact, company, deal'
}
```

---

## Related Resources

- [[Activity-Types]] — Provides `activity_type_id` for event creation
- [[Meetings]] — Specialised meeting activities
- [[Calls]] — Specialised call activities
- [[Companies]] — Events can be linked to companies
- [[Contacts]] — Events can be linked to contacts and have contact attendees
- [[Deals]] — Events can be linked to deals
- [[Users]] — Event attendees

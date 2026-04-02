# Time Tracking

Manage time tracking entries in Teamleader Focus.

## Overview

Time tracking entries record work done against a subject (company, contact, event, milestone, task, ticket). Entries can
be created in three different time-recording variants depending on what information is available.

Access via `Teamleader::timeTracking()`.

> **`create()` posts to `timeTracking.add`** — not `.create`.
>
> **Three recording variants** — `started_at + duration`, `started_at + ended_at`, or `started_on + duration`. The SDK
> validates the combination before the request.
>
> **Subject types and `relates_to` types are validated separately** with different allowed values.

## Endpoint

`timeTracking`

## Capabilities

| Capability  | Supported                               |
|-------------|-----------------------------------------|
| Pagination  | ✅ Supported                             |
| Filtering   | ✅ Supported                             |
| Sorting     | ✅ Supported (`started_at`)              |
| Sideloading | ✅ Supported (`materials`, `relates_to`) |
| Creation    | ✅ Supported                             |
| Update      | ✅ Supported                             |
| Deletion    | ✅ Supported                             |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$entries = Teamleader::timeTracking()->list();

$entries = Teamleader::timeTracking()->list(
    ['user_id' => 'user-uuid', 'started_after' => '2025-04-01T00:00:00+02:00'],
    ['sort' => 'started_at', 'sort_order' => 'desc', 'page_size' => 50]
);

// With sideloading
$entries = Teamleader::timeTracking()->withMaterials()->withRelations()->list();
```

---

### `info(mixed $id, mixed $includes = null)`

```php
$entry = Teamleader::timeTracking()->info('entry-uuid');
$entry = Teamleader::timeTracking()->info('entry-uuid', 'materials');
$entry = Teamleader::timeTracking()->withMaterials()->info('entry-uuid');
```

---

### `create(array $data)`

Posts to `timeTracking.add`. Three recording variants — pass exactly one combination:

**Variant 1: `started_at` + `duration` (seconds)**

```php
Teamleader::timeTracking()->create([
    'started_at'   => '2025-05-12T09:00:00+02:00',
    'duration'     => 3600,   // seconds
    'subject'      => ['type' => 'company', 'id' => 'company-uuid'],
    'work_type_id' => 'work-type-uuid',
    'description'  => 'Client meeting',
    'invoiceable'  => true,
]);
```

**Variant 2: `started_at` + `ended_at`**

```php
Teamleader::timeTracking()->create([
    'started_at'   => '2025-05-12T09:00:00+02:00',
    'ended_at'     => '2025-05-12T11:00:00+02:00',
    'subject'      => ['type' => 'ticket', 'id' => 'ticket-uuid'],
    'work_type_id' => 'work-type-uuid',
]);
```

**Variant 3: `started_on` + `duration` (duration tracking mode)**

```php
Teamleader::timeTracking()->create([
    'started_on'   => '2025-05-12',     // date only
    'duration'     => 7200,
    'subject'      => ['type' => 'nextgenTask', 'id' => 'task-uuid'],
    'work_type_id' => 'work-type-uuid',
]);
```

---

### `update(mixed $id, array $data)`

Injects `id` into the request body.

```php
Teamleader::timeTracking()->update('entry-uuid', ['description' => 'Updated', 'invoiceable' => false]);
```

---

### `delete(mixed $id)`

```php
Teamleader::timeTracking()->delete('entry-uuid');
```

---

### `resume(string $id, ?string $startedAt = null)`

Resumes a timer from a previously stopped entry. `$startedAt` defaults to now.

```php
Teamleader::timeTracking()->resume('entry-uuid');
Teamleader::timeTracking()->resume('entry-uuid', '2025-05-12T14:00:00+02:00');
```

---

## Helper Methods

| Method                                     | Description                                      |
|--------------------------------------------|--------------------------------------------------|
| `forUser(string $userId)`                  | Filter by `user_id`                              |
| `forSubject(string $id, string $type)`     | Filter by `subject` — type validated             |
| `forSubjectTypes(array $types)`            | Filter by `subject_types` array — each validated |
| `betweenDates(string $start, string $end)` | `started_after` + `started_before`               |
| `endedBetween(string $start, string $end)` | `ended_after` + `ended_before`                   |
| `relatedTo(string $id, string $type)`      | `relates_to` — type validated separately         |
| `withMaterials()`                          | Fluent: sideload `materials`                     |
| `withRelations()`                          | Fluent: sideload `relates_to`                    |

```php
Teamleader::timeTracking()->forUser('user-uuid');
Teamleader::timeTracking()->forSubject('ticket-uuid', 'ticket');
Teamleader::timeTracking()->betweenDates('2025-05-01T00:00:00+02:00', '2025-05-31T23:59:59+02:00');
Teamleader::timeTracking()->relatedTo('project-uuid', 'nextgenProject');
```

---

## Valid Subject Types (filter + create)

`company`, `contact`, `event`, `milestone`, `nextgenTask`, `ticket`, `todo`

## Valid `relates_to` Types (filter only)

`milestone`, `project`, `nextgenProject`, `nextgenProjectGroup`

---

## Filters

| Filter           | Type   | Description                   |
|------------------|--------|-------------------------------|
| `ids`            | array  | Filter by entry UUIDs         |
| `user_id`        | string | Filter by user UUID           |
| `started_after`  | string | ISO 8601 datetime             |
| `started_before` | string | ISO 8601 datetime             |
| `ended_after`    | string | ISO 8601 datetime             |
| `ended_before`   | string | ISO 8601 datetime             |
| `subject`        | object | `{type, id}`                  |
| `subject_types`  | array  | Array of subject type strings |
| `relates_to`     | object | `{type, id}` — type validated |

---

## Sideloading

| Include      | Description                                       |
|--------------|---------------------------------------------------|
| `materials`  | Materials linked to the entry                     |
| `relates_to` | Project, milestone, or group the entry relates to |

---

## Error Handling

```php
use InvalidArgumentException;

// Invalid subject type
try {
    Teamleader::timeTracking()->forSubject('uuid', 'deal');
} catch (InvalidArgumentException $e) {
    // 'Invalid subject type. Must be one of: company, contact, event, ...'
}

// Invalid relates_to type
try {
    Teamleader::timeTracking()->relatedTo('uuid', 'deal');
} catch (InvalidArgumentException $e) {
    // 'Invalid relates_to type. Must be one of: milestone, project, nextgenProject, nextgenProjectGroup'
}
```

---

## Related Resources

- [[Timers]] — Start/stop a live running timer that creates entries on stop
- [[Work-Types]] — `work_type_id` on entries
- [[Tasks]] — Time tracked against standalone tasks
- [[Project-Tasks]] — Time tracked against project tasks
- [[Tickets]] — Time tracked against support tickets

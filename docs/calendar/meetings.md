# Meetings

Manage meetings in Teamleader Focus.

## Overview

The Meetings resource manages meeting activities linked to customers and employees. Creation uses `schedule()` — there
is no `create()` method. Meetings support time tracking, completion status, and post-meeting reports.

Access via `Teamleader::meetings()`.

## Endpoint

`meetings`

## Capabilities

| Capability  | Supported                                      |
|-------------|------------------------------------------------|
| Pagination  | ✅ Supported                                    |
| Filtering   | ✅ Supported                                    |
| Sorting     | ✅ Supported (`scheduled_at`)                   |
| Sideloading | ✅ Supported (`tracked_time`, `estimated_time`) |
| Creation    | ✅ Supported (via `schedule()`)                 |
| Update      | ✅ Supported                                    |
| Deletion    | ✅ Supported                                    |

> **Includes key:** When passing includes via `options`, use `options['include']`. The SDK sends this to the API
> as `includes` (plural). The fluent `->with()` / `->withTrackedTime()` methods handle this automatically.

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$meetings = Teamleader::meetings()->list();

$meetings = Teamleader::meetings()->list([
    'employee_id' => 'user-uuid',
    'start_date'  => '2025-04-01',
    'end_date'    => '2025-04-30',
]);

$meetings = Teamleader::meetings()->list([], [
    'page_size'   => 50,
    'page_number' => 1,
    'sort'        => [['field' => 'scheduled_at', 'order' => 'asc']],
]);
```

---

### `info(string $id, mixed $includes = null)`

```php
$meeting = Teamleader::meetings()->info('meeting-uuid');

$meeting = Teamleader::meetings()->info('meeting-uuid', 'tracked_time');

$meeting = Teamleader::meetings()
    ->withTrackedTime()
    ->withEstimatedTime()
    ->info('meeting-uuid');
```

---

### `schedule(array $data)`

Creates a new meeting. Posts to `meetings.schedule`. All five fields below are validated before the request.

**Required:**

| Field       | Notes                                                              |
|-------------|--------------------------------------------------------------------|
| `title`     | Meeting title                                                      |
| `starts_at` | ISO 8601 datetime with timezone                                    |
| `ends_at`   | ISO 8601 datetime with timezone                                    |
| `attendees` | Non-empty array; must include at least one entry with `type: user` |
| `customer`  | Object with `type` (`contact` or `company`) and `id`               |

If `attendees` is provided but contains no `user` type entry, throws `InvalidArgumentException`.

```php
$meeting = Teamleader::meetings()->schedule([
    'title'       => 'Quarterly Review',
    'starts_at'   => '2025-04-15T09:00:00+02:00',
    'ends_at'     => '2025-04-15T10:30:00+02:00',
    'description' => 'Q1 performance review',
    'location'    => 'Client HQ, Antwerp',
    'attendees'   => [
        ['type' => 'user',    'id' => 'user-uuid'],
        ['type' => 'contact', 'id' => 'contact-uuid'],
    ],
    'customer'         => ['type' => 'company', 'id' => 'company-uuid'],
    'activity_type_id' => 'activity-type-uuid',
    'milestone_id'     => 'milestone-uuid',
]);
```

---

### `update(mixed $id, array $data)`

The `id` is injected into the request body. If `attendees` is provided in the update data it must still include at least
one `user` type entry — otherwise throws `InvalidArgumentException`.

```php
Teamleader::meetings()->update('meeting-uuid', [
    'title'    => 'Quarterly Review (rescheduled)',
    'starts_at' => '2025-04-16T09:00:00+02:00',
    'ends_at'   => '2025-04-16T10:30:00+02:00',
    'location'  => 'Video call',
]);
```

---

### `complete(string $id)`

Marks a meeting as completed.

```php
Teamleader::meetings()->complete('meeting-uuid');
```

---

### `uncomplete(string $id)`

Reopens a completed meeting.

```php
Teamleader::meetings()->uncomplete('meeting-uuid');
```

---

### `delete(string $id)`

```php
Teamleader::meetings()->delete('meeting-uuid');
```

---

### `createReport(string $meetingId, array $reportData)`

Creates a post-meeting report and attaches it to a related entity. The `attach_to.type` must be `contact`, `company`,
or `deal` — any other value throws `InvalidArgumentException`.

```php
Teamleader::meetings()->createReport('meeting-uuid', [
    'attach_to'   => ['type' => 'deal', 'id' => 'deal-uuid'],
    'description' => 'Client confirmed budget. Sending contract next week.',
]);
```

---

## Helper Methods

| Method                                    | Filter applied                         |
|-------------------------------------------|----------------------------------------|
| `forEmployee(string $id)`                 | `employee_id`                          |
| `inDateRange(string $start, string $end)` | `start_date` + `end_date`              |
| `today()`                                 | `start_date` + `end_date` set to today |
| `search(string $term)`                    | `term` (title and description)         |

```php
$meetings = Teamleader::meetings()->forEmployee('user-uuid');
$meetings = Teamleader::meetings()->inDateRange('2025-04-01', '2025-04-30');
$meetings = Teamleader::meetings()->today();
$meetings = Teamleader::meetings()->search('kickoff');
```

### Fluent include methods

| Method                | Include          |
|-----------------------|------------------|
| `withTrackedTime()`   | `tracked_time`   |
| `withEstimatedTime()` | `estimated_time` |

---

## Filters

| Filter          | Type   | Description                      |
|-----------------|--------|----------------------------------|
| `ids`           | array  | Filter by meeting UUIDs          |
| `employee_id`   | string | Filter by assigned employee UUID |
| `start_date`    | string | From date (YYYY-MM-DD)           |
| `end_date`      | string | To date (YYYY-MM-DD)             |
| `milestone_id`  | string | Filter by project milestone UUID |
| `term`          | string | Search in title and description  |
| `recurrence_id` | string | Filter by recurring series UUID  |

---

## Sideloading

| Include          | Description                       |
|------------------|-----------------------------------|
| `tracked_time`   | Time tracked against this meeting |
| `estimated_time` | Estimated time for this meeting   |

---

## Usage Examples

### Schedule a meeting and create a report after

```php
$meeting = Teamleader::meetings()->schedule([
    'title'     => 'Contract Negotiation',
    'starts_at' => '2025-05-10T14:00:00+02:00',
    'ends_at'   => '2025-05-10T15:30:00+02:00',
    'attendees' => [['type' => 'user', 'id' => 'user-uuid']],
    'customer'  => ['type' => 'company', 'id' => 'company-uuid'],
]);

$meetingId = $meeting['data']['id'];

// After the meeting
Teamleader::meetings()->complete($meetingId);

Teamleader::meetings()->createReport($meetingId, [
    'attach_to'   => ['type' => 'company', 'id' => 'company-uuid'],
    'description' => 'Agreed on pricing. Contract to be signed by 15 May.',
]);
```

### Get this week's meetings with time tracking

```php
$start = date('Y-m-d', strtotime('monday this week'));
$end   = date('Y-m-d', strtotime('sunday this week'));

$meetings = Teamleader::meetings()
    ->withTrackedTime()
    ->inDateRange($start, $end);
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// No user attendee
try {
    Teamleader::meetings()->schedule([
        'title'     => 'Test',
        'starts_at' => '2025-05-01T10:00:00+02:00',
        'ends_at'   => '2025-05-01T11:00:00+02:00',
        'attendees' => [['type' => 'contact', 'id' => 'contact-uuid']],
        'customer'  => ['type' => 'company', 'id' => 'company-uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'At least one user attendee must be present'
}

// Invalid report attach_to type
try {
    Teamleader::meetings()->createReport('meeting-uuid', [
        'attach_to' => ['type' => 'project', 'id' => 'project-uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Report can only be attached to: contact, company, deal'
}
```

---

## Related Resources

- [[Calls]] — Phone-call activities
- [[Calendar-Events]] — Generic calendar events
- [[Activity-Types]] — Meeting type categorisation
- [[Companies]] — Meeting customer reference
- [[Contacts]] — Meeting attendees and customer reference
- [[Users]] — Meeting attendees

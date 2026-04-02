# Timers

Manage the live running timer in Teamleader Focus.

## Overview

Timers let you track work in real time. Starting a timer begins counting; stopping it automatically creates a completed
time tracking entry. Only one timer can run at a time per account.

Access via `Teamleader::timers()`.

> **`work_type_id` + `subject` are both required on `start()`** — missing either throws before the request.
>
> **`stop()` creates a time tracking entry** and returns `data.time_tracking.id` — the new entry's UUID.
>
> **The update method is `updateCurrent()`**, not `update()`.
>
> **No `delete()` method** — use `stop()` to end the timer instead.
>
> **`isRunning()` swallows exceptions** — it returns `false` on any API error.
>
> **Subject types differ from [[Time-Tracking]]** — `nextgenTask` is not a valid timer subject type.

## Endpoint

`timers`

## Capabilities

| Capability  | Supported               |
|-------------|-------------------------|
| Pagination  | ❌ Not supported         |
| Filtering   | ❌ Not supported         |
| Sorting     | ❌ Not supported         |
| Sideloading | ❌ Not supported         |
| Creation    | ✅ Via `start()`         |
| Update      | ✅ Via `updateCurrent()` |
| Deletion    | ❌ Use `stop()`          |

---

## Methods

### `start(array $data)`

Both `subject` and `work_type_id` are required and validated. Subject type is validated against the list below.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$timer = Teamleader::timers()->start([
    'work_type_id' => 'work-type-uuid',
    'subject'      => ['type' => 'company', 'id' => 'company-uuid'],
    'description'  => 'Client strategy session',
    'invoiceable'  => true,
    'started_at'   => '2025-05-12T09:00:00+02:00',  // optional — defaults to now
]);
```

---

### `current()`

Returns the running timer, or `['data' => []]` if no timer is active.

```php
$timer = Teamleader::timers()->current();

if (!empty($timer['data'])) {
    echo "Running since: " . $timer['data']['started_at'];
}
```

---

### `stop()`

Stops the running timer and creates a time tracking entry. Returns `data.time_tracking.id`.

```php
$result  = Teamleader::timers()->stop();
$entryId = $result['data']['time_tracking']['id'];

// Fetch the full entry
$entry = Teamleader::timeTracking()->info($entryId);
```

---

### `updateCurrent(array $data)`

Updates the currently running timer. Throws if no timer is running.

```php
Teamleader::timers()->updateCurrent(['description' => 'Updated: now doing code review']);
Teamleader::timers()->updateCurrent(['invoiceable' => false]);
```

---

### `isRunning()`

Returns `true` if a timer is active. Catches all exceptions internally — returns `false` on any API error.

```php
if (Teamleader::timers()->isRunning()) {
    // safe to call stop(), current(), or updateCurrent()
}
```

---

## Helper Methods

### `startForSubject(string $type, string $id, string $workTypeId, array $options = [])`

Convenience wrapper for `start()`. Subject type validated.

```php
Teamleader::timers()->startForSubject(
    'ticket',
    'ticket-uuid',
    'support-work-type-uuid',
    ['description' => 'Fixing reported bug', 'invoiceable' => true]
);
```

---

## Valid Subject Types

`company`, `contact`, `event`, `todo`, `milestone`, `ticket`

> Note: `nextgenTask` is **not** a valid timer subject type (unlike [[Time-Tracking]] entries).

---

## Response Structure

```php
// start() / current()
[
    'data' => [
        'id'          => 'timer-uuid',
        'description' => 'Client session',
        'started_at'  => '2025-05-12T09:00:00+02:00',
        'invoiceable' => true,
        'user'        => ['type' => 'user',     'id' => 'user-uuid'],
        'subject'     => ['type' => 'company',  'id' => 'company-uuid'],
        'work_type'   => ['type' => 'workType', 'id' => 'work-type-uuid'],
    ],
]

// stop()
[
    'data' => [
        'time_tracking' => ['type' => 'timeTracking', 'id' => 'entry-uuid'],
    ],
]

// current() when nothing is running
['data' => []]
```

---

## Usage Examples

### Safe start pattern

```php
if (!Teamleader::timers()->isRunning()) {
    Teamleader::timers()->start([
        'work_type_id' => 'work-type-uuid',
        'subject'      => ['type' => 'milestone', 'id' => 'milestone-uuid'],
        'description'  => 'Feature development',
    ]);
} else {
    // stop first, then start
    Teamleader::timers()->stop();
    Teamleader::timers()->start([...]);
}
```

### Stop and read the entry

```php
$result  = Teamleader::timers()->stop();
$entryId = $result['data']['time_tracking']['id'];
$entry   = Teamleader::timeTracking()->info($entryId);
$hours   = $entry['data']['duration'] / 3600;
echo "Tracked {$hours} hours.";
```

---

## Error Handling

```php
use InvalidArgumentException;

// Missing work_type_id
try {
    Teamleader::timers()->start(['subject' => ['type' => 'company', 'id' => 'uuid']]);
} catch (InvalidArgumentException $e) {
    // 'work_type_id is required'
}

// Invalid subject type
try {
    Teamleader::timers()->start([
        'work_type_id' => 'uuid',
        'subject'      => ['type' => 'nextgenTask', 'id' => 'uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Invalid subject type. Must be one of: company, contact, event, todo, milestone, ticket'
}
```

---

## Related Resources

- [[Time-Tracking]] — The completed entries created when a timer is stopped
- [[Work-Types]] — `work_type_id` required to start a timer

# Calls

Manage call activities in Teamleader Focus.

## Overview

The Calls resource manages scheduled and completed phone-call activities linked to companies. Calls can be assigned to
users, marked complete with an outcome, and filtered by date or related entity.

Access via `Teamleader::calls()`.

> **Creation endpoint:** `create()` posts to `calls.add` internally, not `calls.create`.
> **No deletion:** There is no delete endpoint for calls.

## Endpoint

`calls`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ✅ Supported     |
| Filtering   | ✅ Supported     |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ✅ Supported     |
| Update      | ✅ Supported     |
| Deletion    | ❌ Not supported |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$calls = Teamleader::calls()->list();

$calls = Teamleader::calls()->list([
    'scheduled_after'  => '2025-04-01',
    'scheduled_before' => '2025-04-30',
]);

$calls = Teamleader::calls()->list([], ['page_size' => 50, 'page_number' => 1]);
```

---

### `info(string $id)`

Validates `$id` is non-empty before the request.

```php
$call = Teamleader::calls()->info('call-uuid');
```

---

### `create(array $data)`

Posts to `calls.add`. Three fields are **required** and validated before the request:

| Field                       | Notes                                   |
|-----------------------------|-----------------------------------------|
| `participant`               | Must contain a nested `customer` object |
| `participant.customer.type` | Required                                |
| `participant.customer.id`   | Required                                |
| `due_at`                    | ISO 8601 datetime with timezone         |
| `assignee`                  | Object with `type` and `id`             |
| `assignee.type`             | Required                                |
| `assignee.id`               | Required                                |

```php
$call = Teamleader::calls()->create([
    'participant' => [
        'customer' => ['type' => 'company', 'id' => 'company-uuid'],
    ],
    'due_at'      => '2025-04-20T14:00:00+02:00',
    'assignee'    => ['type' => 'user', 'id' => 'user-uuid'],
    'description' => 'Follow-up on proposal — discuss pricing and timeline.',
]);
```

---

### `update(string $id, array $data)`

The `id` is injected into the request body. Validates `$id` before the request.

```php
Teamleader::calls()->update('call-uuid', [
    'description'     => 'Updated notes after rescheduling',
    'call_outcome_id' => 'outcome-uuid',
]);
```

---

### `complete(string $id, ?string $outcomeId = null, ?string $outcomeSummary = null)`

Marks a call as completed. `$outcomeId` and `$outcomeSummary` are optional. Validates `$id` before the request.

```php
// No outcome
Teamleader::calls()->complete('call-uuid');

// With outcome UUID (from callOutcomes resource)
Teamleader::calls()->complete('call-uuid', 'outcome-uuid');

// With outcome and summary
Teamleader::calls()->complete(
    'call-uuid',
    'outcome-uuid',
    'Client confirmed interest. Sending proposal next week.'
);
```

---

## Helper Methods

### Date and status shortcuts

| Method                                     | Filter applied                                      |
|--------------------------------------------|-----------------------------------------------------|
| `today()`                                  | `scheduled_after` + `scheduled_before` set to today |
| `thisWeek()`                               | Monday–Sunday of current week                       |
| `upcoming()`                               | `scheduled_after` set to today                      |
| `overdue()`                                | `scheduled_before` set to today                     |
| `betweenDates(string $start, string $end)` | `scheduled_after` + `scheduled_before`              |

```php
$calls = Teamleader::calls()->today();
$calls = Teamleader::calls()->thisWeek();
$calls = Teamleader::calls()->upcoming();
$calls = Teamleader::calls()->overdue();
$calls = Teamleader::calls()->betweenDates('2025-04-01', '2025-04-30');
```

### `forCompany(string $companyId)`

Builds the `relates_to` filter as `{type: company, id: $companyId}`. Validates `$companyId` before the request.

```php
$calls = Teamleader::calls()->forCompany('company-uuid');
```

### `withOutcome(string $outcomeId)`

Filters by `call_outcome_id`. Validates `$outcomeId` before the request.

```php
$calls = Teamleader::calls()->withOutcome('outcome-uuid');
```

### `schedule(array $data)`

Alias for `create()`.

```php
$call = Teamleader::calls()->schedule([...]);
```

### `reschedule(string $id, string $newDateTime)`

Alias for `update($id, ['due_at' => $newDateTime])`.

```php
Teamleader::calls()->reschedule('call-uuid', '2025-04-22T10:00:00+02:00');
```

---

## Filters

| Filter             | Type   | Description                                  |
|--------------------|--------|----------------------------------------------|
| `scheduled_after`  | string | Calls on or after this date (`YYYY-MM-DD`)   |
| `scheduled_before` | string | Calls on or before this date (`YYYY-MM-DD`)  |
| `relates_to`       | object | Related entity — `{type: company, id: uuid}` |
| `call_outcome_id`  | string | Filter completed calls by outcome UUID       |

---

## Usage Examples

### Schedule, reschedule, and complete a call

```php
$call = Teamleader::calls()->create([
    'participant' => ['customer' => ['type' => 'company', 'id' => 'company-uuid']],
    'due_at'      => '2025-04-20T14:00:00+02:00',
    'assignee'    => ['type' => 'user', 'id' => 'user-uuid'],
    'description' => 'Initial discovery call',
]);

$id = $call['data']['id'];

// Reschedule if needed
Teamleader::calls()->reschedule($id, '2025-04-21T10:00:00+02:00');

// Mark complete after the call
Teamleader::calls()->complete($id, 'positive-outcome-uuid', 'Client ready to move forward.');
```

### Review all overdue calls

```php
$overdue = Teamleader::calls()->overdue();

foreach ($overdue['data'] as $call) {
    echo "{$call['description']} — due {$call['due_at']}\n";
}
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing required field
try {
    Teamleader::calls()->create([
        'participant' => ['customer' => ['type' => 'company', 'id' => 'uuid']],
        'assignee'    => ['type' => 'user', 'id' => 'uuid'],
        // missing due_at
    ]);
} catch (InvalidArgumentException $e) {
    // "Field 'due_at' is required for creating a call"
}

// Missing customer type/id inside participant
try {
    Teamleader::calls()->create([
        'participant' => ['customer' => ['type' => 'company']],  // missing id
        'due_at'      => '2025-04-20T14:00:00+02:00',
        'assignee'    => ['type' => 'user', 'id' => 'uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Participant customer must have type and id'
}
```

---

## Related Resources

- [[Call-Outcomes]] — Outcome definitions used in `complete()`
- [[Meetings]] — Meeting activities
- [[Calendar-Events]] — Generic calendar events
- [[Companies]] — Calls are linked to companies via `relates_to`
- [[Users]] — Call assignees

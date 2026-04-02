# Notes

Create, update, and list notes in Teamleader Focus.

## Overview

The Notes resource lets you attach text notes to entities in Teamleader. Notes support pagination and optional user
notifications on creation.

`info()` and `delete()` are not supported and throw a `BadMethodCallException` if called. There is no way to delete a
note through the API.

## Endpoint

`notes`

## Capabilities

| Capability  | Supported                         |
|-------------|-----------------------------------|
| Pagination  | ✅ Supported                       |
| Filtering   | ✅ Supported (subject filter only) |
| Sorting     | ❌ Not supported                   |
| Sideloading | ❌ Not supported                   |
| Creation    | ✅ Supported                       |
| Update      | ✅ Supported                       |
| Deletion    | ❌ Not supported                   |

> **Note on `info()`:** Throws `BadMethodCallException`. Use `list()` with a subject filter instead.
>
> **Note on `delete()`:** Throws `BadMethodCallException`. Notes cannot be deleted via the API.

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns notes for a specific subject. The `subject` filter is the only supported filter — it must include both `type`
and `id`. An `InvalidArgumentException` is thrown for an unrecognised subject type.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Notes for a company
$notes = Teamleader::notes()->list([
    'subject' => [
        'type' => 'company',
        'id'   => 'company-uuid',
    ],
]);

// With pagination
$notes = Teamleader::notes()->list(
    ['subject' => ['type' => 'deal', 'id' => 'deal-uuid']],
    ['page_size' => 50, 'page_number' => 1]
);
```

---

### `create(array $data)`

Creates a note. Validates all fields before sending the request.

**Required fields:**

| Field          | Type   | Description                                        |
|----------------|--------|----------------------------------------------------|
| `subject`      | array  | Object with `type` and `id`                        |
| `subject.type` | string | Subject type — see [Subject Types](#subject-types) |
| `subject.id`   | string | Subject UUID                                       |
| `content`      | string | Note text — must not be empty                      |

**Optional fields:**

| Field    | Type  | Description                                                              |
|----------|-------|--------------------------------------------------------------------------|
| `notify` | array | Users to notify — each entry must be `['type' => 'user', 'id' => '...']` |

> **`notify` only supports `type: user`** — passing any other type throws `InvalidArgumentException`.

```php
// Simple note
$note = Teamleader::notes()->create([
    'subject' => ['type' => 'company', 'id' => 'company-uuid'],
    'content' => 'Follow-up call scheduled for Friday.',
]);

// With user notifications
$note = Teamleader::notes()->create([
    'subject' => ['type' => 'deal', 'id' => 'deal-uuid'],
    'content' => 'Contract signed — moving to onboarding.',
    'notify'  => [
        ['type' => 'user', 'id' => 'user-uuid-1'],
        ['type' => 'user', 'id' => 'user-uuid-2'],
    ],
]);
```

---

### `update(mixed $id, array $data)`

Updates an existing note. The ID is merged into the request body before posting to `notes.update`.

**`content` is optional on update** but must be non-empty if provided.

```php
Teamleader::notes()->update('note-uuid', [
    'content' => 'Updated note content.',
]);
```

---

## Helper Methods

### Read helpers

| Method                                                      | Equivalent                                                  |
|-------------------------------------------------------------|-------------------------------------------------------------|
| `forSubject(string $type, string $id, array $options = [])` | `list(['subject' => [...]], $options)` with type validation |
| `forCompany(string $id, array $options = [])`               | `forSubject('company', $id, $options)`                      |
| `forContact(string $id, array $options = [])`               | `forSubject('contact', $id, $options)`                      |
| `forDeal(string $id, array $options = [])`                  | `forSubject('deal', $id, $options)`                         |

```php
$notes = Teamleader::notes()->forCompany('company-uuid');
$notes = Teamleader::notes()->forContact('contact-uuid');
$notes = Teamleader::notes()->forDeal('deal-uuid');

// Any subject type
$notes = Teamleader::notes()->forSubject('nextgenProject', 'project-uuid');

// With pagination
$notes = Teamleader::notes()->forCompany('company-uuid', [
    'page_size'   => 50,
    'page_number' => 1,
]);
```

### Create helpers

| Method                                                                            | Equivalent                           |
|-----------------------------------------------------------------------------------|--------------------------------------|
| `createForSubject(string $type, string $id, string $content, array $notify = [])` | `create([...])` with type validation |
| `createForCompany(string $id, string $content, array $notify = [])`               | `createForSubject('company', ...)`   |
| `createForContact(string $id, string $content, array $notify = [])`               | `createForSubject('contact', ...)`   |
| `createForDeal(string $id, string $content, array $notify = [])`                  | `createForSubject('deal', ...)`      |

```php
// Simple creation
Teamleader::notes()->createForCompany('company-uuid', 'Meeting notes.');
Teamleader::notes()->createForContact('contact-uuid', 'Sent proposal.');
Teamleader::notes()->createForDeal('deal-uuid', 'Contract signed.');

// Any subject type
Teamleader::notes()->createForSubject('nextgenProject', 'project-uuid', 'Kickoff complete.');

// With notifications
Teamleader::notes()->createForDeal(
    'deal-uuid',
    'Urgent: customer requesting callback.',
    [['type' => 'user', 'id' => 'user-uuid']]
);
```

### Introspection

```php
$types = Teamleader::notes()->getAvailableSubjectTypes();
```

---

## Subject Types

The subject type is validated before the request is sent. An `InvalidArgumentException` is thrown for any value not in
this list.

| Type             | Description      |
|------------------|------------------|
| `company`        | Company          |
| `contact`        | Contact          |
| `creditNote`     | Credit note      |
| `deal`           | Deal             |
| `invoice`        | Invoice          |
| `nextgenProject` | Project (v2)     |
| `product`        | Product          |
| `project`        | Project (legacy) |
| `quotation`      | Quotation        |
| `subscription`   | Subscription     |

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'         => 'note-uuid',
            'author'     => ['type' => 'user', 'id' => 'user-uuid'],
            'subject'    => ['type' => 'company', 'id' => 'company-uuid'],
            'content'    => 'Follow-up call scheduled.',
            'created_at' => '2025-03-10T09:15:00+00:00',
            'updated_at' => '2025-03-10T09:15:00+00:00',
        ],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 4,
    ],
]
```

### `create()` / `update()` response

```php
[
    'data' => [
        'type' => 'note',
        'id'   => 'note-uuid',
    ],
]
```

---

## Usage Examples

### List all notes for a company with pagination

```php
$all  = [];
$page = 1;

do {
    $response = Teamleader::notes()->forCompany('company-uuid', [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

### Create a note and notify the responsible user

```php
$company = Teamleader::companies()->info('company-uuid');
$userId  = $company['data']['responsible_user']['id'] ?? null;

$notify = $userId ? [['type' => 'user', 'id' => $userId]] : [];

Teamleader::notes()->createForCompany(
    'company-uuid',
    'Contract renewal discussion — action required.',
    $notify
);
```

### Append to an existing note

Notes cannot be deleted, so appending is a common way to preserve history:

```php
// Fetch existing note content first — info() is not available,
// so retrieve via list() and filter client-side by ID
$notes  = Teamleader::notes()->forDeal('deal-uuid');
$target = collect($notes['data'])->firstWhere('id', 'note-uuid');

if ($target) {
    Teamleader::notes()->update('note-uuid', [
        'content' => $target['content'] . "\n\n---\n" . 'Update: terms revised.',
    ]);
}
```

---

## Error Handling

```php
use BadMethodCallException;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid subject type — thrown before the request
try {
    $notes = Teamleader::notes()->forSubject('meeting', 'uuid'); // invalid type
} catch (InvalidArgumentException $e) {
    // "Invalid subject type 'meeting'. Available types: company, contact, ..."
    Log::error($e->getMessage());
}

// Invalid notify type — thrown before the request
try {
    Teamleader::notes()->create([
        'subject' => ['type' => 'deal', 'id' => 'deal-uuid'],
        'content' => 'Update.',
        'notify'  => [['type' => 'team', 'id' => 'team-uuid']], // only 'user' is valid
    ]);
} catch (InvalidArgumentException $e) {
    // 'Only user notifications are supported'
    Log::error($e->getMessage());
}

// info() / delete() — always throw
try {
    Teamleader::notes()->info('note-uuid');
} catch (BadMethodCallException $e) {
    // Use list() with subject filter instead
}

// API-level errors
try {
    Teamleader::notes()->createForDeal('deal-uuid', 'Note content.');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Companies]] — Notes can be attached to companies
- [[Contacts]] — Notes can be attached to contacts
- [[Deals]] — Notes can be attached to deals
- [[Filtering]] — Filter and pagination reference

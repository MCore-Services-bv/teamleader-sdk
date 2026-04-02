# Tickets

Manage support tickets in Teamleader Focus.

## Overview

The Tickets resource provides full CRUD for support tickets, plus message threading (customer replies, internal notes,
message import).

Access via `Teamleader::tickets()`.

> **Assignee type must be `user`** — not `team`. Passing any other type throws `InvalidArgumentException`.
>
> **Participant customer type must be `company`** — contacts are not valid participants.
>
> **`forCustomer()` uses the `relates_to` filter** internally, not a `customer` filter.
>
> **`create()` returns `data.{id, type}`.** `update()` and `delete()` return HTTP 204 (no body).

## Endpoint

`tickets`

## Capabilities

| Capability  | Supported       |
|-------------|-----------------|
| Pagination  | ✅ Supported     |
| Filtering   | ✅ Supported     |
| Sorting     | ❌ Not supported |
| Sideloading | ❌ Not supported |
| Creation    | ✅ Supported     |
| Update      | ✅ Supported     |
| Deletion    | ✅ Supported     |

---

## Methods

### `list(array $filters = [], array $options = [])`

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$tickets = Teamleader::tickets()->list();

$tickets = Teamleader::tickets()->list(
    ['ids' => ['ticket-uuid-1', 'ticket-uuid-2']],
    ['page_size' => 50, 'page_number' => 1]
);
```

---

### `info(string $id)`

Throws `InvalidArgumentException` if `$id` is empty.

```php
$ticket = Teamleader::tickets()->info('ticket-uuid');
```

---

### `create(array $data)`

Three fields required and validated:

| Required           | Validation                                  |
|--------------------|---------------------------------------------|
| `subject`          | Non-empty string                            |
| `customer`         | Object `{type: contact\|company, id: uuid}` |
| `ticket_status_id` | Non-empty string                            |

Returns `data.{id, type}`.

```php
$ticket = Teamleader::tickets()->create([
    'subject'          => 'Login issue on mobile app',
    'customer'         => ['type' => 'company', 'id' => 'company-uuid'],
    'ticket_status_id' => 'open-status-uuid',
    'assignee'         => ['type' => 'user', 'id' => 'user-uuid'],
    'project_id'       => 'project-uuid',
    'custom_fields'    => [['id' => 'field-uuid', 'value' => 'high']],
]);

$ticketId = $ticket['data']['id'];
```

---

### `update(mixed $id, array $data)`

Returns HTTP 204. Customer and assignee structures are validated when provided.

```php
Teamleader::tickets()->update('ticket-uuid', [
    'ticket_status_id' => 'resolved-status-uuid',
    'assignee'         => ['type' => 'user', 'id' => 'new-user-uuid'],
]);
```

---

### `delete(mixed $id)`

Returns HTTP 204.

```php
Teamleader::tickets()->delete('ticket-uuid');
```

---

### `addReply(string $ticketId, string $message, string $statusId, array $attachments = [])`

Adds a customer-facing reply. `$message` is HTML. Returns `data.{id, type}` of the created message.

```php
Teamleader::tickets()->addReply(
    'ticket-uuid',
    '<p>We are investigating this issue and will update you shortly.</p>',
    'status-uuid'
);
```

---

### `addInternalMessage(string $ticketId, string $message, array $attachments = [])`

Adds an internal note — not visible to the customer. Returns `data.{id, type}`.

```php
Teamleader::tickets()->addInternalMessage(
    'ticket-uuid',
    '<p>Spoke with customer by phone. Issue is reproduced on their end.</p>'
);
```

---

### `listMessages(string $ticketId, array $filters = [], array $options = [])`

Lists all messages on a ticket. Message `type` filter is validated when provided.

```php
$messages = Teamleader::tickets()->listMessages('ticket-uuid');

// Filter by type (validated)
$messages = Teamleader::tickets()->listMessages('ticket-uuid', ['type' => 'internal']);
```

---

### `getMessage(string $messageId)`

Fetches a single message. Note: uses param key `message_id`, not `id`.

```php
$message = Teamleader::tickets()->getMessage('message-uuid');
```

---

### `importMessage(string $ticketId, string $body, string $sentByType, string $sentById, string $sentAt, array $attachments = [])`

Imports a historical message (e.g. from an email integration). `$sentByType` validated: `company`, `contact`, `user`.

```php
Teamleader::tickets()->importMessage(
    'ticket-uuid',
    '<p>Hi, I cannot log in.</p>',
    'contact',           // company | contact | user
    'contact-uuid',
    '2025-04-15T09:30:00+02:00'
);
```

---

## Helper Methods

| Method                                  | Filter sent                                      |
|-----------------------------------------|--------------------------------------------------|
| `forCustomer(string $type, string $id)` | `relates_to: {type, id}` — type validated        |
| `forProjects(array $projectIds)`        | `project_ids: [...]` — throws on empty           |
| `byIds(array $ids)`                     | `ids: [...]` — throws on empty                   |
| `excludeStatuses(array $statusIds)`     | `exclude: {status_ids: [...]}` — throws on empty |

```php
$tickets = Teamleader::tickets()->forCustomer('company', 'company-uuid');
$tickets = Teamleader::tickets()->forProjects(['project-uuid-1', 'project-uuid-2']);
$tickets = Teamleader::tickets()->byIds(['uuid-1', 'uuid-2']);
$tickets = Teamleader::tickets()->excludeStatuses(['closed-status-uuid']);
```

---

## Filters

| Filter        | Type   | Description                                      |
|---------------|--------|--------------------------------------------------|
| `ids`         | array  | Filter by ticket UUIDs                           |
| `relates_to`  | object | `{type: contact\|company, id: uuid}`             |
| `project_ids` | array  | Filter by project UUIDs                          |
| `exclude`     | object | `{status_ids: [...]}` — exclude certain statuses |

---

## Error Handling

```php
use InvalidArgumentException;

// Assignee type must be 'user'
try {
    Teamleader::tickets()->create([
        'subject'          => 'Test',
        'customer'         => ['type' => 'company', 'id' => 'uuid'],
        'ticket_status_id' => 'status-uuid',
        'assignee'         => ['type' => 'team', 'id' => 'team-uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Assignee type must be "user"'
}

// Participant customer type must be 'company'
try {
    Teamleader::tickets()->create([
        ...,
        'participant' => ['customer' => ['type' => 'contact', 'id' => 'uuid']],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Participant customer type must be "company"'
}

// Invalid sentByType on importMessage
try {
    Teamleader::tickets()->importMessage('ticket-uuid', '<p>msg</p>', 'team', 'uuid', '2025-01-01T00:00:00+00:00');
} catch (InvalidArgumentException $e) {
    // 'Invalid sent_by type. Must be one of: company, contact, user'
}
```

---

## Related Resources

- [[Ticket-Status]] — Status definitions used on tickets
- [[Companies]] — Tickets linked to companies
- [[Contacts]] — Tickets linked to contacts
- [[Projects]] — Optional project link on tickets
- [[Time-Tracking]] — Log time against tickets

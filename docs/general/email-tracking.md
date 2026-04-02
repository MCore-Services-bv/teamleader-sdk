# Email Tracking

List and create email tracking records in Teamleader Focus.

## Overview

The Email Tracking resource lets you log outbound emails against Teamleader entities, maintaining a complete
communication history alongside deals, contacts, companies, and other records. Records can be listed per subject and
created with optional file attachments.

`update()` and `delete()` are not supported — there are no API endpoints for them.

## Endpoint

`emailTracking`

## Capabilities

| Capability  | Supported                    |
|-------------|------------------------------|
| Pagination  | ✅ Supported                  |
| Filtering   | ✅ Supported (subject filter) |
| Sorting     | ❌ Not supported              |
| Sideloading | ❌ Not supported              |
| Creation    | ✅ Supported                  |
| Update      | ❌ Not supported              |
| Deletion    | ❌ Not supported              |

> **API filter requirement:** `list()` always sends a `filter` object in the request body, even when no filters are
> provided. When called without filters it sends an empty object — this is required by the Teamleader API.

---

## Methods

### `list(array $filters = [], array $options = [])`

Returns email tracking records for a subject. Accepts the subject filter in two formats — nested object or flat
underscore keys.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Nested object form (recommended)
$emails = Teamleader::emailTracking()->list([
    'subject' => [
        'type' => 'contact',
        'id'   => 'contact-uuid',
    ],
]);

// Flat underscore form (also accepted)
$emails = Teamleader::emailTracking()->list([
    'subject_type' => 'company',
    'subject_id'   => 'company-uuid',
]);

// With pagination
$emails = Teamleader::emailTracking()->list(
    ['subject' => ['type' => 'deal', 'id' => 'deal-uuid']],
    ['page_size' => 50, 'page_number' => 1]
);

// Without filters — returns all accessible records
$emails = Teamleader::emailTracking()->list();
```

---

### `create(array $data)`

Creates an email tracking record. Validates all fields before sending the request. Subject ID and attachment IDs are
format-validated as UUIDs.

**Required fields:**

| Field          | Type   | Description                                        |
|----------------|--------|----------------------------------------------------|
| `subject`      | array  | Object with `type` and `id`                        |
| `subject.type` | string | Subject type — see [Subject Types](#subject-types) |
| `subject.id`   | string | Subject UUID — validated as UUID format            |
| `title`        | string | Email subject line                                 |
| `content`      | string | Email body                                         |

**Optional fields:**

| Field         | Type  | Description                                         |
|---------------|-------|-----------------------------------------------------|
| `attachments` | array | Array of file UUIDs — each validated as UUID format |

```php
// Basic record
$email = Teamleader::emailTracking()->create([
    'subject' => ['type' => 'contact', 'id' => 'contact-uuid'],
    'title'   => 'Follow-up on our meeting',
    'content' => 'Hi Sarah, great to meet you today...',
]);

// With attachments
$email = Teamleader::emailTracking()->create([
    'subject'     => ['type' => 'deal', 'id' => 'deal-uuid'],
    'title'       => 'Proposal attached',
    'content'     => 'Please find our proposal document below.',
    'attachments' => ['file-uuid-1', 'file-uuid-2'],
]);
```

---

## Helper Methods

### Read helpers

`forSubject()` is the generic list helper. Note that it does **not** validate the subject type — an invalid type will
reach the API and return an error there rather than being caught locally.

```php
// Generic — any valid subject type
$emails = Teamleader::emailTracking()->forSubject('contact', 'contact-uuid');

// With pagination
$emails = Teamleader::emailTracking()->forSubject('deal', 'deal-uuid', [
    'page_size'   => 50,
    'page_number' => 1,
]);
```

### Create helpers

Subject type is validated before the request for all create helpers.

| Method                                                                                  | Subject type |
|-----------------------------------------------------------------------------------------|--------------|
| `createForContact(string $id, string $title, string $content, array $attachments = [])` | `contact`    |
| `createForCompany(string $id, string $title, string $content, array $attachments = [])` | `company`    |
| `createForDeal(string $id, string $title, string $content, array $attachments = [])`    | `deal`       |

```php
Teamleader::emailTracking()->createForContact(
    'contact-uuid',
    'Introductory email',
    'Hi, thanks for connecting...'
);

Teamleader::emailTracking()->createForDeal(
    'deal-uuid',
    'Revised proposal',
    'Please find the updated figures attached.',
    ['file-uuid']
);
```

### Introspection

```php
$types = Teamleader::emailTracking()->getAvailableSubjectTypes();
```

---

## Subject Types

| Type             | Description  |
|------------------|--------------|
| `contact`        | Contact      |
| `company`        | Company      |
| `deal`           | Deal         |
| `invoice`        | Invoice      |
| `creditNote`     | Credit note  |
| `subscription`   | Subscription |
| `product`        | Product      |
| `quotation`      | Quotation    |
| `nextgenProject` | Project (v2) |

Subject type is validated on `create()` calls. An `InvalidArgumentException` is thrown for any value not in this list.

> **`forSubject()` does not validate the type** — only creation methods do. Pass an invalid type to `forSubject()` and
> the error will come from the API, not the SDK.

---

## Response Structure

### `list()` response

```php
[
    'data' => [
        [
            'id'         => 'email-tracking-uuid',
            'subject'    => ['type' => 'contact', 'id' => 'contact-uuid'],
            'title'      => 'Follow-up on our meeting',
            'content'    => 'Hi Sarah, great to meet you today...',
            'created_at' => '2025-03-10T09:15:00+00:00',
        ],
    ],
    'meta' => [
        'page'    => ['size' => 20, 'number' => 1],
        'matches' => 6,
    ],
]
```

### `create()` response

```php
[
    'data' => [
        'type' => 'emailTracking',
        'id'   => 'email-tracking-uuid',
    ],
]
```

---

## Usage Examples

### Log an outbound email after sending

```php
// Send via your mail service, then log in Teamleader
Mail::to($recipient)->send(new ProposalMail($deal));

Teamleader::emailTracking()->createForDeal(
    $deal->teamleader_id,
    'Proposal: ' . $deal->name,
    $emailBody
);
```

### Log with an uploaded attachment

```php
// Upload the file first via the Files resource
$upload = Teamleader::files()->upload('proposal.pdf', 'deal', 'deal-uuid');
// PUT file to $upload['data']['location'] ...

// Then create the tracking record with the file UUID
Teamleader::emailTracking()->createForDeal(
    'deal-uuid',
    'Proposal attached',
    'Please find our proposal below.',
    [$fileId]
);
```

### Paginate through all email history for a company

```php
$all  = [];
$page = 1;

do {
    $response = Teamleader::emailTracking()->forSubject('company', 'company-uuid', [
        'page_size'   => 100,
        'page_number' => $page,
    ]);

    $all  = array_merge($all, $response['data']);
    $page++;
} while (count($response['data']) === 100);
```

### Build a communication timeline

```php
$emails = Teamleader::emailTracking()->forSubject('deal', 'deal-uuid');
$notes  = Teamleader::notes()->forDeal('deal-uuid');

$timeline = array_merge($emails['data'], $notes['data']);

usort($timeline, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Invalid subject type — thrown before the request on create
try {
    Teamleader::emailTracking()->create([
        'subject' => ['type' => 'meeting', 'id' => 'uuid'], // invalid
        'title'   => 'Subject',
        'content' => 'Body',
    ]);
} catch (InvalidArgumentException $e) {
    // "Invalid subject type 'meeting'. Must be one of: contact, company, ..."
    Log::error($e->getMessage());
}

// Invalid UUID format — thrown before the request
try {
    Teamleader::emailTracking()->create([
        'subject'     => ['type' => 'contact', 'id' => 'not-a-uuid'],
        'title'       => 'Subject',
        'content'     => 'Body',
        'attachments' => ['also-not-a-uuid'],
    ]);
} catch (InvalidArgumentException $e) {
    // 'Subject ID must be a valid UUID' or 'All attachment IDs must be valid UUIDs'
    Log::error($e->getMessage());
}

// API-level errors
try {
    $emails = Teamleader::emailTracking()->forSubject('contact', 'contact-uuid');
} catch (TeamleaderException $e) {
    Log::error('Teamleader error', ['message' => $e->getMessage()]);
}
```

---

## Related Resources

- [[Notes]] — Text notes on entities (similar pattern, different data)
- [[Files]] — Upload attachments before referencing them here
- [[Companies]] — Email tracking can be attached to companies
- [[Contacts]] — Email tracking can be attached to contacts
- [[Deals]] — Email tracking can be attached to deals
- [[Filtering]] — Filter and pagination reference

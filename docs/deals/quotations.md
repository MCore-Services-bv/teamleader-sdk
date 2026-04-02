# Quotations

Manage quotations in Teamleader Focus.

## Overview

The Quotations resource provides full CRUD operations for quotations attached to deals, plus `accept()`, `send()`,
and `download()` lifecycle methods.

> **Pagination differs from other resources.** Quotations uses `options['page']['size']`
> and `options['page']['number']` — not the standard `page_size` / `page_number` keys used elsewhere in the SDK.

## Endpoint

`quotations`

## Capabilities

| Capability  | Supported                                     |
|-------------|-----------------------------------------------|
| Pagination  | ✅ Supported (non-standard format — see below) |
| Filtering   | ✅ Supported (`ids`, `status`)                 |
| Sorting     | ❌ Not supported                               |
| Sideloading | ✅ Supported (`expiry` — feature-gated)        |
| Creation    | ✅ Supported                                   |
| Update      | ✅ Supported                                   |
| Deletion    | ✅ Supported                                   |

---

## Methods

### `list(array $filters = [], array $options = [])`

Pagination is passed as a nested `page` object under `$options`, not as flat `page_size`/`page_number` keys.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// All quotations
$quotations = Teamleader::quotations()->list();

// Filtered by status
$quotations = Teamleader::quotations()->list(['status' => ['open', 'accepted']]);

// With pagination — note the nested 'page' key
$quotations = Teamleader::quotations()->list([], [
    'page' => ['size' => 50, 'number' => 1],
]);
```

---

### `info(string $id, mixed $includes = null)`

```php
$quotation = Teamleader::quotations()->info('quotation-uuid');

// With expiry sideload (only if feature is enabled on the account)
$quotation = Teamleader::quotations()->info('quotation-uuid', 'expiry');
$quotation = Teamleader::quotations()->with('expiry')->info('quotation-uuid');
```

---

### `create(array $data)`

**Required (validated before the request):**

- `deal_id` — UUID of the deal this quotation belongs to
- `grouped_lines` OR `text` — at least one must be present

```php
$quotation = Teamleader::quotations()->create([
    'deal_id'       => 'deal-uuid',
    'grouped_lines' => [
        [
            'section'    => ['title' => 'Professional Services'],
            'line_items' => [
                [
                    'quantity'    => 5,
                    'description' => 'Consulting days',
                    'unit_price'  => [
                        'amount'   => 1200,
                        'currency' => 'EUR',
                        'tax'      => 'excluding',
                    ],
                ],
            ],
        ],
    ],
]);

// Text-only quotation (Markdown)
$quotation = Teamleader::quotations()->create([
    'deal_id' => 'deal-uuid',
    'text'    => '## Proposal\n\nPlease find our offer below.',
]);
```

---

### `update(string $id, array $data)`

The `id` is injected into the request body before posting. Returns empty (HTTP 204).

```php
Teamleader::quotations()->update('quotation-uuid', [
    'grouped_lines' => [...],
]);
```

---

### `delete(string $id)`

Returns empty (HTTP 204).

```php
Teamleader::quotations()->delete('quotation-uuid');
```

---

### `accept(string $id)`

Marks a quotation as accepted. Returns empty (HTTP 204).

```php
Teamleader::quotations()->accept('quotation-uuid');
```

---

### `send(array $data)`

Sends one or more quotations by email. All six keys are **required** and validated before the request —
an `InvalidArgumentException` is thrown for any missing field.

| Required key    | Description                          |
|-----------------|--------------------------------------|
| `quotations`    | Non-empty array of quotation UUIDs   |
| `from.sender`   | Sender object (type + id)            |
| `recipients.to` | Non-empty array of recipient objects |
| `subject`       | Email subject line                   |
| `content`       | Email body text                      |
| `language`      | Language code (e.g. `en`, `nl`)      |

Returns empty (HTTP 204).

```php
Teamleader::quotations()->send([
    'quotations' => ['quotation-uuid'],
    'from'       => [
        'sender' => ['type' => 'user', 'id' => 'user-uuid'],
    ],
    'recipients' => [
        'to' => [
            ['type' => 'contact', 'id' => 'contact-uuid'],
        ],
    ],
    'subject'  => 'Your quotation from Acme Corp',
    'content'  => 'Please review the attached quotation.',
    'language' => 'nl',
]);
```

---

### `download(string $id, string $format = 'pdf')`

Downloads a quotation as a temporary URL. Only `pdf` is a valid format — `InvalidArgumentException` is thrown for any
other value.

```php
$result = Teamleader::quotations()->download('quotation-uuid', 'pdf');

$url     = $result['data']['location']; // temporary download URL
$expires = $result['data']['expires'];  // expiration time

file_put_contents('quotation.pdf', file_get_contents($url));
```

---

## Helper Methods

### `byIds(array $ids)`

```php
$quotations = Teamleader::quotations()->byIds(['uuid-1', 'uuid-2']);
```

### `byStatus(string|array $status)`

Validates each status against the allowed list before calling `list()`. Throws `InvalidArgumentException` for any
invalid value.

```php
$quotations = Teamleader::quotations()->byStatus('open');
$quotations = Teamleader::quotations()->byStatus(['open', 'accepted']);
```

**Valid statuses:** `open`, `accepted`, `expired`, `rejected`, `closed`

---

## Filters

| Filter   | Type  | Description                                   |
|----------|-------|-----------------------------------------------|
| `ids`    | array | Filter by quotation UUIDs                     |
| `status` | array | Filter by status — string is coerced to array |

---

## Sideloading

| Include  | Description                                                                                                 |
|----------|-------------------------------------------------------------------------------------------------------------|
| `expiry` | Expiry date and action after expiry. Only returned if the account has the quotation expiry feature enabled. |

```php
$quotation = Teamleader::quotations()->with('expiry')->info('quotation-uuid');

$expiresAfter      = $quotation['data']['expiry']['expires_after'];       // YYYY-MM-DD
$actionAfterExpiry = $quotation['data']['expiry']['action_after_expiry']; // 'lock' or 'none'
```

---

## Response Notes

| Method       | Returns                                                 |
|--------------|---------------------------------------------------------|
| `list()`     | Array of quotation summaries                            |
| `info()`     | Full quotation with `grouped_lines`                     |
| `create()`   | `['data' => ['type' => 'quotation', 'id' => 'uuid']]`   |
| `update()`   | Empty array (HTTP 204)                                  |
| `delete()`   | Empty array (HTTP 204)                                  |
| `accept()`   | Empty array (HTTP 204)                                  |
| `send()`     | Empty array (HTTP 204)                                  |
| `download()` | `['data' => ['location' => '...', 'expires' => '...']]` |

---

## Usage Examples

### Create and immediately send a quotation

```php
$deal     = Teamleader::deals()->withCustomer()->info('deal-uuid');
$customer = $deal['data']['lead']['customer'];

$quotation = Teamleader::quotations()->create([
    'deal_id'       => 'deal-uuid',
    'grouped_lines' => [[
        'section'    => ['title' => 'Proposed Solution'],
        'line_items' => [[
            'quantity'    => 1,
            'description' => 'Implementation package',
            'unit_price'  => ['amount' => 5000, 'currency' => 'EUR', 'tax' => 'excluding'],
        ]],
    ]],
]);

Teamleader::quotations()->send([
    'quotations' => [$quotation['data']['id']],
    'from'       => ['sender' => ['type' => 'user', 'id' => 'responsible-user-uuid']],
    'recipients' => ['to' => [['type' => $customer['type'], 'id' => $customer['id']]]],
    'subject'    => 'Your quotation',
    'content'    => 'Please find the attached quotation.',
    'language'   => 'nl',
]);
```

### Download and store a PDF

```php
$download = Teamleader::quotations()->download('quotation-uuid', 'pdf');
$filename = 'quotation_' . date('Ymd') . '.pdf';
Storage::put("quotations/{$filename}", file_get_contents($download['data']['location']));
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Missing deal_id
try {
    Teamleader::quotations()->create(['grouped_lines' => [...]]);
} catch (InvalidArgumentException $e) {
    // 'deal_id is required to create a quotation'
}

// Neither grouped_lines nor text
try {
    Teamleader::quotations()->create(['deal_id' => 'uuid']);
} catch (InvalidArgumentException $e) {
    // 'A quotation needs either grouped_lines or text to be valid'
}

// Invalid download format
try {
    Teamleader::quotations()->download('uuid', 'docx');
} catch (InvalidArgumentException $e) {
    // "Invalid format 'docx'. Supported formats: pdf"
}

// Invalid status in byStatus()
try {
    Teamleader::quotations()->byStatus('draft');
} catch (InvalidArgumentException $e) {
    // "Invalid status 'draft'. Must be one of: open, accepted, expired, rejected, closed"
}
```

---

## Related Resources

- [[Deals]] — Every quotation belongs to a deal
- [[Orders]] — Orders are created when quotations are accepted
- [[Contacts]] — Used as email recipients in `send()`
- [[Companies]] — Used as email recipients in `send()`
- [[Filtering]] — Filter and pagination reference

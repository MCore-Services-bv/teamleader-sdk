# Webhooks

Manage webhook registrations for real-time event notifications in Teamleader Focus.

## Overview

Webhooks push event notifications to your HTTPS endpoint when something changes in Teamleader. You register a URL + list
of event types; Teamleader sends a POST to your URL each time a matching event fires.

Access via `Teamleader::webhooks()`.

> **No update method.** To change which events a URL subscribes to, `unregister()` the old types and `register()` the
> new ones.
>
> **Webhook payload delivers `subject.id`**, not `data.id`. The entity UUID is at `payload['subject']['id']`.
>
> **URL must be HTTPS** — HTTP URLs throw `InvalidArgumentException` before the request.
>
> All event types are validated against the internal `$eventTypes` array. Passing an unrecognised type throws before the
> request.

## Endpoint

`webhooks`

## Capabilities

| Capability  | Supported                  |
|-------------|----------------------------|
| Pagination  | ❌ Not supported            |
| Filtering   | ❌ Not supported            |
| Sorting     | ❌ Not supported            |
| Sideloading | ❌ Not supported            |
| Creation    | ✅ Via `register()`         |
| Update      | ❌ Unregister + re-register |
| Deletion    | ✅ Via `unregister()`       |

---

## Methods

### `list()`

Returns all registered webhooks ordered by URL.

```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$webhooks = Teamleader::webhooks()->list();

foreach ($webhooks['data'] as $webhook) {
    echo "{$webhook['url']} — " . implode(', ', $webhook['types']) . "\n";
}
```

---

### `register(string $url, array $types)`

Registers the URL for the given event types. Throws `InvalidArgumentException` if:

- `$url` is empty, not a valid URL, or not HTTPS
- `$types` is empty
- Any type string is not in the known event type list

```php
Teamleader::webhooks()->register(
    'https://myapp.com/webhooks/teamleader',
    ['invoice.booked', 'invoice.paymentRegistered', 'deal.won']
);

// Register all invoice-related events in one call
$types = Teamleader::webhooks()->getInvoiceEventTypes();
Teamleader::webhooks()->register('https://myapp.com/webhooks/teamleader', $types);
```

Returns an empty response (HTTP 204) on success.

---

### `unregister(string $url, array $types)`

Removes the given event types from the URL. Same validation as `register()`. To fully remove a webhook, pass all its
currently subscribed types.

```php
// Remove specific types
Teamleader::webhooks()->unregister(
    'https://myapp.com/webhooks/teamleader',
    ['invoice.booked']
);

// Remove all types (effectively deletes the webhook)
$webhooks = Teamleader::webhooks()->list();
foreach ($webhooks['data'] as $webhook) {
    if ($webhook['url'] === 'https://myapp.com/webhooks/teamleader') {
        Teamleader::webhooks()->unregister($webhook['url'], $webhook['types']);
        break;
    }
}
```

Returns an empty response (HTTP 204) on success.

---

## Helper Methods

### `getAvailableEventTypes()`

Returns the full array of valid event type strings.

```php
$allTypes = Teamleader::webhooks()->getAvailableEventTypes();
```

### `getEventTypesByCategory(string $category)`

Returns all types whose prefix matches `$category`.

```php
$receiptTypes = Teamleader::webhooks()->getEventTypesByCategory('receipt');
// ['receipt.added', 'receipt.approved', 'receipt.bookkeepingSubmissionFailed', ...]
```

### Category shortcut helpers

| Method                        | Returns types for                       |
|-------------------------------|-----------------------------------------|
| `getInvoiceEventTypes()`      | `invoice.*` + `incomingInvoice.*`       |
| `getCreditNoteEventTypes()`   | `creditNote.*` + `incomingCreditNote.*` |
| `getDealEventTypes()`         | `deal.*`                                |
| `getContactEventTypes()`      | `contact.*`                             |
| `getCompanyEventTypes()`      | `company.*`                             |
| `getProjectEventTypes()`      | `project.*` + `nextgenProject.*`        |
| `getTaskEventTypes()`         | `task.*` + `nextgenTask.*`              |
| `getTicketEventTypes()`       | `ticket.*` + `ticketMessage.*`          |
| `getTimeTrackingEventTypes()` | `timeTracking.*`                        |

```php
$invoiceTypes    = Teamleader::webhooks()->getInvoiceEventTypes();
$projectTypes    = Teamleader::webhooks()->getProjectEventTypes();
$timeTrackTypes  = Teamleader::webhooks()->getTimeTrackingEventTypes();
```

---

## Event Types

| Category               | Events                                                                                                                                          |
|------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| **account**            | `deactivated`, `deleted`                                                                                                                        |
| **call**               | `added`, `completed`, `deleted`, `updated`                                                                                                      |
| **company**            | `added`, `deleted`, `updated`                                                                                                                   |
| **contact**            | `added`, `deleted`, `linkedToCompany`, `unlinkedFromCompany`, `updatedLinkToCompany`, `updated`                                                 |
| **creditNote**         | `booked`, `deleted`, `peppolSubmissionFailed`, `peppolSubmissionSucceeded`, `sent`, `updated`                                                   |
| **deal**               | `created`, `deleted`, `lost`, `moved`, `updated`, `won`                                                                                         |
| **incomingCreditNote** | `added`, `approved`, `bookkeepingSubmissionFailed`, `bookkeepingSubmissionSucceeded`, `deleted`, `refused`, `updated`                           |
| **incomingInvoice**    | `added`, `approved`, `bookkeepingSubmissionFailed`, `bookkeepingSubmissionSucceeded`, `deleted`, `refused`, `updated`                           |
| **invoice**            | `booked`, `deleted`, `drafted`, `paymentRegistered`, `paymentRemoved`, `peppolSubmissionFailed`, `peppolSubmissionSucceeded`, `sent`, `updated` |
| **meeting**            | `completed`, `created`, `deleted`, `updated`                                                                                                    |
| **milestone**          | `created`, `updated`                                                                                                                            |
| **nextgenProject**     | `closed`, `created`, `deleted`, `updated`                                                                                                       |
| **nextgenTask**        | `completed`, `created`, `deleted`, `updated`                                                                                                    |
| **product**            | `added`, `deleted`, `updated`                                                                                                                   |
| **project**            | `created`, `deleted`, `updated`                                                                                                                 |
| **receipt**            | `added`, `approved`, `bookkeepingSubmissionFailed`, `bookkeepingSubmissionSucceeded`, `deleted`, `refused`, `updated`                           |
| **subscription**       | `added`, `deactivated`, `deleted`, `updated`                                                                                                    |
| **task**               | `completed`, `created`, `deleted`, `updated`                                                                                                    |
| **ticket**             | `closed`, `created`, `deleted`, `reopened`, `updated`                                                                                           |
| **ticketMessage**      | `added`                                                                                                                                         |
| **timeTracking**       | `added`, `deleted`, `updated`                                                                                                                   |
| **user**               | `deactivated`                                                                                                                                   |

---

## Webhook Payload

When an event fires, Teamleader POSTs JSON to your endpoint:

```json
{
    "type": "invoice.booked",
    "subject": {
        "type": "invoice",
        "id": "invoice-uuid"
    },
    "account": {
        "type": "account",
        "id": "account-uuid"
    }
}
```

> `payload['subject']['id']` — **not** `payload['data']['id']`.

### Laravel route example

```php
Route::post('/webhooks/teamleader', function (Request $request) {
    $type = $request->input('type');
    $id   = $request->input('subject.id');  // entity UUID

    match ($type) {
        'invoice.booked'            => handleInvoiceBooked($id),
        'invoice.peppolSubmissionFailed' => handlePeppolFailure($id),
        'deal.won'                  => handleDealWon($id),
        default                     => null,
    };

    return response()->json(['status' => 'received']);
});
```

---

## Usage Examples

### Register for all Peppol events

```php
Teamleader::webhooks()->register('https://myapp.com/webhooks/teamleader', [
    'invoice.peppolSubmissionSucceeded',
    'invoice.peppolSubmissionFailed',
    'creditNote.peppolSubmissionSucceeded',
    'creditNote.peppolSubmissionFailed',
]);
```

### Register for multiple resource categories

```php
Teamleader::webhooks()->register(
    'https://myapp.com/webhooks/teamleader',
    array_merge(
        Teamleader::webhooks()->getInvoiceEventTypes(),
        Teamleader::webhooks()->getDealEventTypes(),
        Teamleader::webhooks()->getContactEventTypes(),
    )
);
```

### Store webhook config in `.env` / config

```php
$webhookUrl   = config('app.url') . '/webhooks/teamleader';
$eventTypes   = config('teamleader.webhook_events', ['invoice.booked', 'deal.won']);

Teamleader::webhooks()->register($webhookUrl, $eventTypes);
```

---

## Error Handling

```php
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Non-HTTPS URL
try {
    Teamleader::webhooks()->register('http://myapp.com/webhooks', ['invoice.booked']);
} catch (InvalidArgumentException $e) {
    // 'Webhook URL must use HTTPS protocol'
}

// Invalid event type
try {
    Teamleader::webhooks()->register('https://myapp.com/webhooks', ['invoice.created']); // doesn't exist
} catch (InvalidArgumentException $e) {
    // 'Invalid event type: invoice.created. Use getAvailableEventTypes() to see all valid types.'
}

// Empty types array
try {
    Teamleader::webhooks()->register('https://myapp.com/webhooks', []);
} catch (InvalidArgumentException $e) {
    // 'At least one event type is required'
}
```

---

## Related Resources

- [[Invoices]] — `invoice.*` events
- [[Deals]] — `deal.*` events
- [[Contacts]] — `contact.*` events
- [[Companies]] — `company.*` events
- [[Subscriptions]] — `subscription.*` events
- [[Receipts]] — `receipt.*` events
- [[Incoming-Invoices]] — `incomingInvoice.*` events
- [[Incoming-Credit-Notes]] — `incomingCreditNote.*` events

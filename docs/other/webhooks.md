# Webhooks

Manage webhooks for real-time event notifications in Teamleader Focus.

## Overview

The Webhooks resource allows you to register and manage webhooks for real-time notifications when events occur in Teamleader. Webhooks enable your application to respond immediately to changes like new invoices, updated deals, or created tickets without constantly polling the API.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [register()](#register)
    - [unregister()](#unregister)
- [Helper Methods](#helper-methods)
- [Event Types](#event-types)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Webhook Verification](#webhook-verification)
- [Related Resources](#related-resources)

## Endpoint

`webhooks`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ❌ Not Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported (via `register()`)
- **Update**: ❌ Not Supported
- **Deletion**: ✅ Supported (via `unregister()`)

---

## Available Methods

### `list()`

Get all registered webhooks, ordered by URL.

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

$webhooks = Teamleader::webhooks()->list();

foreach ($webhooks['data'] as $webhook) {
    echo "URL: {$webhook['url']}\n";
    echo "Types: " . implode(', ', $webhook['types']) . "\n";
}
```

---

### `register()`

Register a new webhook for specific event types.

**Parameters:**
- `url` (string, required): Your webhook URL — must use HTTPS
- `types` (array, required): Array of event type strings to subscribe to

**Example:**
```php
Teamleader::webhooks()->register(
    'https://example.com/webhooks/teamleader',
    [
        'invoice.booked',
        'invoice.sent',
        'invoice.paymentRegistered',
    ]
);
```

---

### `unregister()`

Remove specific event types from a registered webhook. Both `url` and `types` are required.

**Parameters:**
- `url` (string, required): The webhook URL to unregister from
- `types` (array, required): Array of event type strings to remove

**Example:**
```php
// Remove specific event types from a webhook
Teamleader::webhooks()->unregister(
    'https://example.com/webhooks/teamleader',
    ['invoice.booked', 'invoice.sent']
);

// Remove all event types (effectively deletes the webhook)
$webhooks = Teamleader::webhooks()->list();
$url = 'https://example.com/webhooks/teamleader';

foreach ($webhooks['data'] as $webhook) {
    if ($webhook['url'] === $url) {
        Teamleader::webhooks()->unregister($url, $webhook['types']);
        break;
    }
}
```

---

## Helper Methods

### `getAvailableEventTypes()`

Returns the full list of valid event type strings.

```php
$allTypes = Teamleader::webhooks()->getAvailableEventTypes();
```

### `getEventTypesByCategory()`

Filter event types by their category prefix.

```php
$receiptTypes = Teamleader::webhooks()->getEventTypesByCategory('receipt');
// ['receipt.added', 'receipt.approved', 'receipt.deleted', ...]
```

### Category Shortcut Helpers

```php
// invoice + incomingInvoice events combined
$invoiceTypes = Teamleader::webhooks()->getInvoiceEventTypes();

// creditNote + incomingCreditNote events combined
$creditNoteTypes = Teamleader::webhooks()->getCreditNoteEventTypes();

// deal events
$dealTypes = Teamleader::webhooks()->getDealEventTypes();

// contact events
$contactTypes = Teamleader::webhooks()->getContactEventTypes();

// company events
$companyTypes = Teamleader::webhooks()->getCompanyEventTypes();

// project + nextgenProject events combined
$projectTypes = Teamleader::webhooks()->getProjectEventTypes();

// task + nextgenTask events combined
$taskTypes = Teamleader::webhooks()->getTaskEventTypes();

// ticket + ticketMessage events combined
$ticketTypes = Teamleader::webhooks()->getTicketEventTypes();

// timeTracking events
$timeTrackingTypes = Teamleader::webhooks()->getTimeTrackingEventTypes();
```

---

## Event Types

### Account Events

- `account.deactivated`
- `account.deleted`

### Call Events

- `call.added`
- `call.completed`
- `call.deleted`
- `call.updated`

### Company Events

- `company.added`
- `company.deleted`
- `company.updated`

### Contact Events

- `contact.added`
- `contact.deleted`
- `contact.linkedToCompany`
- `contact.unlinkedFromCompany`
- `contact.updatedLinkToCompany`
- `contact.updated`

### Credit Note Events

- `creditNote.booked`
- `creditNote.deleted`
- `creditNote.peppolSubmissionFailed`
- `creditNote.peppolSubmissionSucceeded`
- `creditNote.sent`
- `creditNote.updated`

### Deal Events

- `deal.created`
- `deal.deleted`
- `deal.lost`
- `deal.moved`
- `deal.updated`
- `deal.won`

### Incoming Credit Note Events

- `incomingCreditNote.added`
- `incomingCreditNote.approved`
- `incomingCreditNote.bookkeepingSubmissionFailed`
- `incomingCreditNote.bookkeepingSubmissionSucceeded`
- `incomingCreditNote.deleted`
- `incomingCreditNote.refused`
- `incomingCreditNote.updated`

### Incoming Invoice Events

- `incomingInvoice.added`
- `incomingInvoice.approved`
- `incomingInvoice.bookkeepingSubmissionFailed`
- `incomingInvoice.bookkeepingSubmissionSucceeded`
- `incomingInvoice.deleted`
- `incomingInvoice.refused`
- `incomingInvoice.updated`

### Invoice Events

- `invoice.booked`
- `invoice.deleted`
- `invoice.drafted`
- `invoice.paymentRegistered`
- `invoice.paymentRemoved`
- `invoice.peppolSubmissionFailed`
- `invoice.peppolSubmissionSucceeded`
- `invoice.sent`
- `invoice.updated`

### Meeting Events

- `meeting.completed`
- `meeting.created`
- `meeting.deleted`
- `meeting.updated`

### Milestone Events

- `milestone.created`
- `milestone.updated`

### Next-gen Project Events

- `nextgenProject.closed`
- `nextgenProject.created`
- `nextgenProject.deleted`
- `nextgenProject.updated`

### Next-gen Task Events

- `nextgenTask.completed`
- `nextgenTask.created`
- `nextgenTask.deleted`
- `nextgenTask.updated`

### Product Events

- `product.added`
- `product.deleted`
- `product.updated`

### Project Events

- `project.created`
- `project.deleted`
- `project.updated`

### Receipt Events

- `receipt.added`
- `receipt.approved`
- `receipt.bookkeepingSubmissionFailed`
- `receipt.bookkeepingSubmissionSucceeded`
- `receipt.deleted`
- `receipt.refused`
- `receipt.updated`

### Subscription Events

- `subscription.added`
- `subscription.deactivated`
- `subscription.deleted`
- `subscription.updated`

### Task Events

- `task.completed`
- `task.created`
- `task.deleted`
- `task.updated`

### Ticket Events

- `ticket.closed`
- `ticket.created`
- `ticket.deleted`
- `ticket.reopened`
- `ticket.updated`
- `ticketMessage.added`

### Time Tracking Events

- `timeTracking.added`
- `timeTracking.deleted`
- `timeTracking.updated`

### User Events

- `user.deactivated`

---

## Response Structure

### `list()` Response

```php
[
    'data' => [
        [
            'url'   => 'https://example.com/webhooks/teamleader',
            'types' => [
                'invoice.booked',
                'invoice.sent',
                'invoice.paymentRegistered',
            ],
        ],
    ],
]
```

### `register()` / `unregister()` Response

Both return an empty array on success (HTTP 204 No Content).

### Webhook Payload

When an event fires, Teamleader POSTs to your URL:

```json
{
    "type": "invoice.booked",
    "data": {
        "id": "invoice-uuid",
        "type": "invoice"
    },
    "meta": {
        "timestamp": "2025-10-17T10:30:00+00:00"
    }
}
```

---

## Usage Examples

### Register for all invoice events

```php
$types = Teamleader::webhooks()->getInvoiceEventTypes();

Teamleader::webhooks()->register('https://myapp.com/webhooks/teamleader', $types);
```

### Register for Peppol submission results only

```php
Teamleader::webhooks()->register('https://myapp.com/webhooks/teamleader', [
    'invoice.peppolSubmissionSucceeded',
    'invoice.peppolSubmissionFailed',
    'creditNote.peppolSubmissionSucceeded',
    'creditNote.peppolSubmissionFailed',
]);
```

### Register for multiple resource types

```php
Teamleader::webhooks()->register(
    'https://myapp.com/webhooks/teamleader',
    [
        'invoice.booked',
        'deal.won',
        'deal.lost',
        'ticket.created',
        'contact.added',
    ]
);
```

### List registered webhooks

```php
$webhooks = Teamleader::webhooks()->list();

foreach ($webhooks['data'] as $webhook) {
    echo "Webhook URL: {$webhook['url']}\n";
    echo "Listening to " . count($webhook['types']) . " event types\n\n";
}
```

### Handle webhook payload in Laravel

```php
// routes/web.php or a controller
Route::post('/webhooks/teamleader', function (Request $request) {
    $payload   = $request->json()->all();
    $eventType = $payload['type'];
    $id        = $payload['data']['id'];

    Log::info('Webhook received', ['type' => $eventType, 'id' => $id]);

    switch ($eventType) {
        case 'invoice.booked':
            handleInvoiceBooked($id);
            break;
        case 'invoice.peppolSubmissionFailed':
            handlePeppolFailure($id);
            break;
        case 'deal.won':
            handleDealWon($id);
            break;
        case 'ticket.created':
            handleTicketCreated($id);
            break;
    }

    return response()->json(['status' => 'received'], 200);
});
```

### Dynamic registration from config

```php
$eventTypes = config('teamleader.webhook_events', [
    'invoice.booked',
    'invoice.paymentRegistered',
]);

$webhookUrl = config('app.url') . '/webhooks/teamleader';

try {
    Teamleader::webhooks()->register($webhookUrl, $eventTypes);
    Log::info('Webhook registered successfully');
} catch (Exception $e) {
    Log::error('Webhook registration failed: ' . $e->getMessage());
}
```

---

## Common Use Cases

### 1. Real-time Invoice Notifications

```php
class InvoiceWebhookHandler
{
    public function handle(array $payload): void
    {
        $eventType = $payload['type'];
        $invoiceId = $payload['data']['id'];

        switch ($eventType) {
            case 'invoice.booked':
                $this->handleInvoiceBooked($invoiceId);
                break;
            case 'invoice.sent':
                $this->handleInvoiceSent($invoiceId);
                break;
            case 'invoice.paymentRegistered':
                $this->handlePaymentReceived($invoiceId);
                break;
            case 'invoice.peppolSubmissionFailed':
                $this->handlePeppolFailure($invoiceId);
                break;
        }
    }

    private function handleInvoiceBooked(string $invoiceId): void
    {
        $invoice = Teamleader::invoices()->info($invoiceId);

        DB::table('invoices')->insert([
            'teamleader_id'  => $invoiceId,
            'invoice_number' => $invoice['data']['invoice_number'],
            'created_at'     => now(),
        ]);

        Notification::send(
            User::admins()->get(),
            new InvoiceBookedNotification($invoice['data'])
        );
    }

    private function handlePeppolFailure(string $invoiceId): void
    {
        $invoice = Teamleader::invoices()->info($invoiceId);

        Log::error('Peppol submission failed', [
            'invoice_id'     => $invoiceId,
            'invoice_number' => $invoice['data']['invoice_number'],
            'peppol_status'  => $invoice['data']['peppol_status'] ?? null,
        ]);

        // Notify billing team
        Notification::send(
            User::billingTeam()->get(),
            new PeppolFailureNotification($invoice['data'])
        );
    }
}
```

### 2. Deal Pipeline Automation

```php
class DealWebhookHandler
{
    public function handle(array $payload): void
    {
        $eventType = $payload['type'];
        $dealId    = $payload['data']['id'];

        if ($eventType === 'deal.won') {
            $this->handleDealWon($dealId);
        } elseif ($eventType === 'deal.lost') {
            $this->handleDealLost($dealId);
        }
    }

    private function handleDealWon(string $dealId): void
    {
        $deal = Teamleader::deals()->info($dealId);

        Teamleader::projects()->create([
            'title'    => 'Project: ' . $deal['data']['title'],
            'customer' => $deal['data']['lead']['customer'],
        ]);

        $this->notifySalesTeam($deal['data']);
    }
}
```

---

## Best Practices

### 1. Webhook Delivery

- Teamleader will retry failed webhooks
- Always return HTTP 200 to acknowledge receipt
- Process webhooks asynchronously using Laravel queues to avoid timeouts

### 2. HTTPS Required

Webhook URLs must use HTTPS.

### 3. Event Ordering

Events may not always arrive in chronological order — use the `meta.timestamp` in the payload for ordering.

### 4. Adding Events to an Existing Webhook

The API has no update endpoint. To add event types, unregister the current types and re-register the combined set:

```php
$url = 'https://example.com/webhooks/teamleader';

// Get current types
$webhooks     = Teamleader::webhooks()->list();
$currentTypes = [];
foreach ($webhooks['data'] as $webhook) {
    if ($webhook['url'] === $url) {
        $currentTypes = $webhook['types'];
        break;
    }
}

// Merge and re-register
$newTypes = array_unique(array_merge($currentTypes, ['receipt.added', 'receipt.updated']));
Teamleader::webhooks()->unregister($url, $currentTypes);
Teamleader::webhooks()->register($url, $newTypes);
```

---

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    Teamleader::webhooks()->register(
        'https://myapp.com/webhooks/teamleader',
        ['invoice.booked']
    );
} catch (InvalidArgumentException $e) {
    // Invalid URL, non-HTTPS, or unknown event type
    Log::error('Webhook validation failed: ' . $e->getMessage());
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        Log::error('Webhook registration rejected by API');
    }
}

try {
    Teamleader::webhooks()->unregister(
        'https://myapp.com/webhooks/teamleader',
        ['invoice.booked']
    );
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::warning('Webhook was not registered');
    }
}
```

---

## Webhook Verification

### Security Considerations

1. **HTTPS Only** — Webhook URLs must use HTTPS (enforced by the SDK)
2. **IP Whitelist** — Consider whitelisting Teamleader's IP ranges at your firewall
3. **Rate Limiting** — Protect your endpoint from abuse with throttling middleware
4. **Idempotency** — The same event may be delivered more than once; use the event `id` or `timestamp` to deduplicate

### Recommended Endpoint Implementation

```php
class TeamleaderWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Verify content type
        if ($request->header('Content-Type') !== 'application/json') {
            return response()->json(['error' => 'Invalid content type'], 400);
        }

        $payload = $request->json()->all();

        // Dispatch to a queued job to avoid timeout
        ProcessTeamleaderWebhook::dispatch($payload);

        return response()->json(['status' => 'received'], 200);
    }
}
```

---

## Related Resources

- [Invoices](../invoicing/invoices.md) — Invoice events
- [Credit Notes](../invoicing/creditnotes.md) — Credit note events, including Peppol
- [Deals](../deals/deals.md) — Deal events
- [Tickets](../tickets/tickets.md) — Ticket events
- [Contacts](../crm/contacts.md) — Contact events
- [Companies](../crm/companies.md) — Company events
- [Receipts](../expenses/receipts.md) — Receipt events

## See Also

- [Usage Guide](../usage.md) — General SDK usage

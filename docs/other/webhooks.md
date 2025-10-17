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
- **Creation**: ✅ Supported (via register())
- **Update**: ❌ Not Supported
- **Deletion**: ✅ Supported (via unregister())

## Available Methods

### `list()`

Get all registered webhooks.

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all webhooks
$webhooks = Teamleader::webhooks()->list();

foreach ($webhooks['data'] as $webhook) {
    echo "URL: {$webhook['url']}\n";
    echo "Types: " . implode(', ', $webhook['types']) . "\n";
}
```

### `register()`

Register a new webhook for specific event types.

**Parameters:**
- `url` (string): Your webhook URL that will receive POST requests
- `types` (array): Array of event types to subscribe to

**Example:**
```php
// Register webhook for invoice events
$webhook = Teamleader::webhooks()->register(
    'https://example.com/webhooks/teamleader',
    [
        'invoice.booked',
        'invoice.sent',
        'invoice.paymentRegistered'
    ]
);
```

### `unregister()`

Remove a registered webhook.

**Parameters:**
- `url` (string): The webhook URL to unregister

**Example:**
```php
// Unregister webhook
$result = Teamleader::webhooks()->unregister('https://example.com/webhooks/teamleader');
```

## Helper Methods

### Event Type Helpers

```php
// Get all available event types
$allTypes = Teamleader::webhooks()->getEventTypes();

// Get event types for specific resources
$invoiceTypes = Teamleader::webhooks()->getInvoiceEventTypes();
$dealTypes = Teamleader::webhooks()->getDealEventTypes();
$contactTypes = Teamleader::webhooks()->getContactEventTypes();
$companyTypes = Teamleader::webhooks()->getCompanyEventTypes();
$ticketTypes = Teamleader::webhooks()->getTicketEventTypes();
```

### Multi-Resource Registration

```php
// Register for all invoice events
$webhook = Teamleader::webhooks()->registerForInvoices('https://example.com/webhook');

// Register for all deal events
$webhook = Teamleader::webhooks()->registerForDeals('https://example.com/webhook');

// Register for all ticket events
$webhook = Teamleader::webhooks()->registerForTickets('https://example.com/webhook');
```

## Event Types

### Available Event Categories

Webhooks support events across many resource types:

**Account Events:**
- `account.deactivated`
- `account.deleted`

**Call Events:**
- `call.added`
- `call.completed`
- `call.deleted`
- `call.updated`

**Company Events:**
- `company.added`
- `company.deleted`
- `company.updated`

**Contact Events:**
- `contact.added`
- `contact.deleted`
- `contact.linkedToCompany`
- `contact.unlinkedFromCompany`
- `contact.updatedLinkToCompany`
- `contact.updated`

**Credit Note Events:**
- `creditNote.booked`
- `creditNote.deleted`
- `creditNote.sent`
- `creditNote.updated`

**Deal Events:**
- `deal.created`
- `deal.deleted`
- `deal.lost`
- `deal.moved`
- `deal.updated`
- `deal.won`

**Event Events:**
- `event.cancelled`
- `event.completed`
- `event.created`
- `event.deleted`
- `event.updated`

**Invoice Events:**
- `invoice.booked`
- `invoice.credited`
- `invoice.deleted`
- `invoice.paymentLinked`
- `invoice.paymentRegistered`
- `invoice.paymentUnlinked`
- `invoice.sent`
- `invoice.updated`

**Meeting Events:**
- `meeting.completed`
- `meeting.created`
- `meeting.deleted`
- `meeting.updated`

**Milestone Events:**
- `milestone.created`
- `milestone.deleted`
- `milestone.updated`

**Product Events:**
- `product.added`
- `product.deleted`
- `product.updated`

**Project Events:**
- `project.closed`
- `project.created`
- `project.deleted`
- `project.updated`

**Quotation Events:**
- `quotation.accepted`
- `quotation.deleted`
- `quotation.sent`
- `quotation.updated`

**Subscription Events:**
- `subscription.activated`
- `subscription.deactivated`
- `subscription.deleted`
- `subscription.updated`

**Task Events:**
- `task.completed`
- `task.created`
- `task.deleted`
- `task.updated`

**Ticket Events:**
- `ticket.closed`
- `ticket.created`
- `ticket.deleted`
- `ticket.reopened`
- `ticket.updated`
- `ticketMessage.added`

**Time Tracking Events:**
- `timeTracking.added`
- `timeTracking.deleted`
- `timeTracking.updated`

**User Events:**
- `user.deactivated`

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'url' => 'https://example.com/webhooks/teamleader',
            'types' => [
                'invoice.booked',
                'invoice.sent',
                'invoice.paymentRegistered'
            ]
        ],
        [
            'url' => 'https://example.com/webhooks/deals',
            'types' => [
                'deal.created',
                'deal.won',
                'deal.lost'
            ]
        ]
    ]
]
```

### Register Response

```php
[
    'data' => [
        'url' => 'https://example.com/webhooks/teamleader',
        'types' => [
            'invoice.booked',
            'invoice.sent'
        ]
    ]
]
```

### Webhook Payload

When an event occurs, Teamleader will POST to your webhook URL:

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

## Usage Examples

### Register Invoice Webhook

```php
// Listen for invoice events
$webhook = Teamleader::webhooks()->register(
    'https://myapp.com/webhooks/teamleader',
    [
        'invoice.booked',
        'invoice.sent',
        'invoice.paymentRegistered'
    ]
);

echo "Webhook registered for " . count($webhook['data']['types']) . " event types";
```

### Register Multiple Resource Types

```php
// Listen for various events
$webhook = Teamleader::webhooks()->register(
    'https://myapp.com/webhooks/teamleader',
    [
        'invoice.booked',
        'deal.won',
        'deal.lost',
        'ticket.created',
        'contact.added'
    ]
);
```

### List Registered Webhooks

```php
// Check current webhooks
$webhooks = Teamleader::webhooks()->list();

foreach ($webhooks['data'] as $webhook) {
    echo "Webhook URL: {$webhook['url']}\n";
    echo "Listening to " . count($webhook['types']) . " event types\n";
    echo "Types: " . implode(', ', $webhook['types']) . "\n\n";
}
```

### Unregister Webhook

```php
// Remove webhook
$result = Teamleader::webhooks()->unregister('https://myapp.com/webhooks/teamleader');

echo "Webhook unregistered successfully";
```

### Handle Webhook Payload

```php
// In your webhook endpoint (e.g., routes/web.php or controller)
Route::post('/webhooks/teamleader', function(Request $request) {
    $payload = $request->json()->all();
    
    $eventType = $payload['type'];
    $resourceId = $payload['data']['id'];
    $timestamp = $payload['meta']['timestamp'];
    
    Log::info('Webhook received', [
        'type' => $eventType,
        'id' => $resourceId,
        'timestamp' => $timestamp
    ]);
    
    // Handle different event types
    switch ($eventType) {
        case 'invoice.booked':
            handleInvoiceBooked($resourceId);
            break;
            
        case 'deal.won':
            handleDealWon($resourceId);
            break;
            
        case 'ticket.created':
            handleTicketCreated($resourceId);
            break;
    }
    
    return response()->json(['status' => 'received'], 200);
});
```

### Dynamic Webhook Registration

```php
// Register webhook based on configuration
$eventTypes = config('teamleader.webhook_events', [
    'invoice.booked',
    'invoice.paymentRegistered'
]);

$webhookUrl = config('app.url') . '/webhooks/teamleader';

try {
    $webhook = Teamleader::webhooks()->register($webhookUrl, $eventTypes);
    Log::info('Webhook registered successfully');
} catch (Exception $e) {
    Log::error('Webhook registration failed: ' . $e->getMessage());
}
```

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
        }
    }
    
    private function handleInvoiceBooked(string $invoiceId): void
    {
        $invoice = Teamleader::invoices()->info($invoiceId);
        
        // Update local database
        DB::table('invoices')->insert([
            'teamleader_id' => $invoiceId,
            'invoice_number' => $invoice['data']['invoice_number'],
            'created_at' => now()
        ]);
        
        // Send notification
        Notification::send(
            User::admins()->get(),
            new InvoiceBookedNotification($invoice['data'])
        );
    }
    
    private function handlePaymentReceived(string $invoiceId): void
    {
        $invoice = Teamleader::invoices()->info($invoiceId);
        
        // Mark as paid in local system
        DB::table('invoices')
            ->where('teamleader_id', $invoiceId)
            ->update(['paid_at' => now()]);
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
        $dealId = $payload['data']['id'];
        
        if ($eventType === 'deal.won') {
            $this->handleDealWon($dealId);
        } elseif ($eventType === 'deal.lost') {
            $this->handleDealLost($dealId);
        }
    }
    
    private function handleDealWon(string $dealId): void
    {
        $deal = Teamleader::deals()->info($dealId);
        
        // Create project for won deal
        $project = Teamleader::projects()->create([
            'title' => 'Project: ' . $deal['data']['title'],
            'customer' => $deal['data']['lead']['customer']
        ]);
        
        // Send congratulations email to sales team
        $this->notifySalesTeam($deal['data']);
    }
}
```

### 3. Support Ticket Monitoring

```php
class TicketWebhookHandler
{
    public function handle(array $payload): void
    {
        $eventType = $payload['type'];
        $ticketId = $payload['data']['id'];
        
        switch ($eventType) {
            case 'ticket.created':
                $this->notifySupport($ticketId);
                break;
                
            case 'ticketMessage.added':
                $this->checkForEscalation($ticketId);
                break;
                
            case 'ticket.closed':
                $this->sendSatisfactionSurvey($ticketId);
                break;
        }
    }
    
    private function notifySupport(string $ticketId): void
    {
        $ticket = Teamleader::tickets()->info($ticketId);
        
        // Send notification to support team
        Slack::send('#support', "New ticket: {$ticket['data']['subject']}");
    }
}
```

### 4. Contact Synchronization

```php
class ContactWebhookHandler
{
    public function handle(array $payload): void
    {
        $eventType = $payload['type'];
        $contactId = $payload['data']['id'];
        
        switch ($eventType) {
            case 'contact.added':
                $this->syncNewContact($contactId);
                break;
                
            case 'contact.updated':
                $this->updateContact($contactId);
                break;
                
            case 'contact.linkedToCompany':
                $this->handleCompanyLink($contactId);
                break;
        }
    }
    
    private function syncNewContact(string $contactId): void
    {
        $contact = Teamleader::contacts()->info($contactId);
        
        // Sync to CRM
        $this->crmService->createContact([
            'teamleader_id' => $contactId,
            'name' => $contact['data']['first_name'] . ' ' . $contact['data']['last_name'],
            'email' => $contact['data']['emails'][0]['email'] ?? null
        ]);
    }
}
```

## Best Practices

### 1. Verify Webhook Origin

```php
// Validate webhook requests
Route::post('/webhooks/teamleader', function(Request $request) {
    // Verify request is from Teamleader
    // Check IP whitelist, signatures, etc.
    
    if (!$this->isValidWebhook($request)) {
        return response()->json(['error' => 'Invalid webhook'], 403);
    }
    
    // Process webhook
    $payload = $request->json()->all();
    // ... handle payload
    
    return response()->json(['status' => 'received'], 200);
});
```

### 2. Return 200 Quickly

```php
// Process webhooks asynchronously
Route::post('/webhooks/teamleader', function(Request $request) {
    $payload = $request->json()->all();
    
    // Queue for background processing
    ProcessTeamleaderWebhook::dispatch($payload);
    
    // Return 200 immediately
    return response()->json(['status' => 'queued'], 200);
});

// In ProcessTeamleaderWebhook job
class ProcessTeamleaderWebhook implements ShouldQueue
{
    public function handle(array $payload): void
    {
        // Process webhook in background
        // ... your logic here
    }
}
```

### 3. Handle Duplicate Events

```php
// Prevent duplicate processing
function processWebhook(array $payload): void
{
    $eventId = md5(json_encode($payload));
    
    // Check if already processed
    if (Cache::has("webhook_processed_{$eventId}")) {
        Log::info('Webhook already processed', ['event_id' => $eventId]);
        return;
    }
    
    // Process webhook
    // ... your logic
    
    // Mark as processed (24 hour cache)
    Cache::put("webhook_processed_{$eventId}", true, 86400);
}
```

### 4. Log All Webhooks

```php
Route::post('/webhooks/teamleader', function(Request $request) {
    $payload = $request->json()->all();
    
    // Log all webhooks
    Log::info('Webhook received', [
        'type' => $payload['type'],
        'data' => $payload['data'],
        'timestamp' => $payload['meta']['timestamp']
    ]);
    
    // Store in database for debugging
    DB::table('webhook_log')->insert([
        'type' => $payload['type'],
        'payload' => json_encode($payload),
        'received_at' => now()
    ]);
    
    // Process webhook
    // ...
    
    return response()->json(['status' => 'received'], 200);
});
```

### 5. Handle Errors Gracefully

```php
Route::post('/webhooks/teamleader', function(Request $request) {
    try {
        $payload = $request->json()->all();
        
        // Process webhook
        WebhookProcessor::process($payload);
        
        return response()->json(['status' => 'processed'], 200);
        
    } catch (Exception $e) {
        Log::error('Webhook processing failed', [
            'error' => $e->getMessage(),
            'payload' => $request->json()->all()
        ]);
        
        // Still return 200 to avoid retries
        return response()->json(['status' => 'error'], 200);
    }
});
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

// Register webhook
try {
    $webhook = Teamleader::webhooks()->register(
        'https://myapp.com/webhooks/teamleader',
        ['invoice.booked']
    );
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Invalid URL or event types
        Log::error('Webhook registration failed: Invalid parameters');
    }
}

// Unregister webhook
try {
    Teamleader::webhooks()->unregister('https://myapp.com/webhooks/teamleader');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        // Webhook not found
        Log::warning('Webhook was not registered');
    }
}
```

## Webhook Verification

### Security Considerations

1. **HTTPS Only**: Webhook URLs must use HTTPS
2. **IP Whitelist**: Consider whitelisting Teamleader IPs
3. **Signature Verification**: Implement signature verification if available
4. **Rate Limiting**: Protect your endpoint from abuse

### Recommended Implementation

```php
class WebhookVerifier
{
    public function verify(Request $request): bool
    {
        // Check if request is over HTTPS
        if (!$request->secure()) {
            return false;
        }
        
        // Verify content type
        if ($request->header('Content-Type') !== 'application/json') {
            return false;
        }
        
        // Additional verification logic
        // ...
        
        return true;
    }
}
```

## Important Notes

### 1. Webhook Delivery

- Teamleader will retry failed webhooks
- Return HTTP 200 status code to acknowledge receipt
- Process webhooks asynchronously to avoid timeouts

### 2. HTTPS Required

Webhook URLs must use HTTPS for security.

### 3. Event Ordering

Events may not always arrive in chronological order. Use the timestamp in the payload.

### 4. One URL per Registration

Each `register()` call creates a separate webhook registration. To add event types to an existing webhook, you must unregister and re-register.

## Related Resources

- [Invoices](../invoicing/invoices.md) - Invoice events
- [Deals](../deals/deals.md) - Deal events
- [Tickets](../tickets/tickets.md) - Ticket events
- [Contacts](../crm/contacts.md) - Contact events
- [Companies](../crm/companies.md) - Company events

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Event Types Reference](../webhooks-events.md) - Complete event types list

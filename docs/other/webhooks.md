# Webhooks

Manage webhooks for real-time event notifications in Teamleader Focus. Webhooks allow you to subscribe to specific events and receive instant HTTP POST notifications when those events occur in your Teamleader account.

## Endpoint

`webhooks`

## Capabilities

- **Supports Pagination**: ❌ Not Supported
- **Supports Filtering**: ❌ Not Supported
- **Supports Sorting**: ❌ Not Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported (via `register()` method)
- **Supports Update**: ❌ Not Supported
- **Supports Deletion**: ✅ Supported (via `unregister()` method)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get all registered webhooks ordered by URL.

**Parameters:**
- None required

**Example:**
```php
$webhooks = $teamleader->webhooks()->list();

// Returns:
// [
//     'data' => [
//         [
//             'url' => 'https://example.com/webhook',
//             'types' => ['invoice.booked', 'invoice.sent']
//         ],
//         ...
//     ]
// ]
```

### `register()`

Register a new webhook to receive notifications for specific event types.

**Parameters:**
- `url` (string, required): Your webhook URL (must be HTTPS)
- `types` (array, required): Array of event types to subscribe to

**Example:**
```php
// Register for a single event type
$result = $teamleader->webhooks()->register(
    'https://example.com/webhook',
    ['invoice.booked']
);

// Register for multiple event types
$result = $teamleader->webhooks()->register(
    'https://example.com/webhook',
    [
        'invoice.booked',
        'invoice.sent',
        'invoice.paymentRegistered'
    ]
);
```

**Returns:** Empty response with 204 status code on success

### `unregister()`

Unregister event types from a webhook URL. This removes the specified event types from the webhook registration.

**Parameters:**
- `url` (string, required): Your webhook URL
- `types` (array, required): Array of event types to unsubscribe from

**Example:**
```php
// Unregister specific event types
$result = $teamleader->webhooks()->unregister(
    'https://example.com/webhook',
    ['invoice.booked']
);

// To completely remove a webhook, unregister all its types
$webhooks = $teamleader->webhooks()->list();
$url = 'https://example.com/webhook';
$types = [];
foreach ($webhooks['data'] as $webhook) {
    if ($webhook['url'] === $url) {
        $types = $webhook['types'];
        break;
    }
}
$result = $teamleader->webhooks()->unregister($url, $types);
```

**Returns:** Empty response with 204 status code on success

## Helper Methods

### `getAvailableEventTypes()`

Get all available webhook event types.

**Example:**
```php
$allTypes = $teamleader->webhooks()->getAvailableEventTypes();
```

### `getEventTypesByCategory()`

Get event types filtered by category (e.g., 'invoice', 'contact', 'deal').

**Parameters:**
- `category` (string): The category prefix to filter by

**Example:**
```php
$contactEvents = $teamleader->webhooks()->getEventTypesByCategory('contact');
$dealEvents = $teamleader->webhooks()->getEventTypesByCategory('deal');
```

### Category-Specific Methods

Convenient methods to get event types for specific categories:

```php
// Get all invoice-related events (including incomingInvoice)
$invoiceEvents = $teamleader->webhooks()->getInvoiceEventTypes();

// Get all deal-related events
$dealEvents = $teamleader->webhooks()->getDealEventTypes();

// Get all contact-related events
$contactEvents = $teamleader->webhooks()->getContactEventTypes();

// Get all company-related events
$companyEvents = $teamleader->webhooks()->getCompanyEventTypes();

// Get all project-related events (including nextgenProject)
$projectEvents = $teamleader->webhooks()->getProjectEventTypes();

// Get all task-related events (including nextgenTask)
$taskEvents = $teamleader->webhooks()->getTaskEventTypes();

// Get all time tracking events
$timeTrackingEvents = $teamleader->webhooks()->getTimeTrackingEventTypes();
```

## Available Event Types

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
- `invoice.sent`
- `invoice.updated`

### Meeting Events
- `meeting.created`
- `meeting.completed`
- `meeting.deleted`
- `meeting.updated`

### Milestone Events
- `milestone.created`
- `milestone.updated`

### Next Generation Project Events
- `nextgenProject.created`
- `nextgenProject.updated`
- `nextgenProject.closed`
- `nextgenProject.deleted`

### Next Generation Task Events
- `nextgenTask.completed`
- `nextgenTask.created`
- `nextgenTask.deleted`
- `nextgenTask.updated`

### Product Events
- `product.added`
- `product.updated`
- `product.deleted`

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

## Common Usage Patterns

### Subscribe to All Invoice Events
```php
$invoiceTypes = $teamleader->webhooks()->getInvoiceEventTypes();
$result = $teamleader->webhooks()->register(
    'https://example.com/webhooks/invoices',
    $invoiceTypes
);
```

### Subscribe to CRM Updates
```php
$crmTypes = array_merge(
    $teamleader->webhooks()->getContactEventTypes(),
    $teamleader->webhooks()->getCompanyEventTypes(),
    $teamleader->webhooks()->getDealEventTypes()
);

$result = $teamleader->webhooks()->register(
    'https://example.com/webhooks/crm',
    $crmTypes
);
```

### Subscribe to Project Management Events
```php
$projectTypes = array_merge(
    $teamleader->webhooks()->getProjectEventTypes(),
    $teamleader->webhooks()->getTaskEventTypes(),
    $teamleader->webhooks()->getTimeTrackingEventTypes()
);

$result = $teamleader->webhooks()->register(
    'https://example.com/webhooks/projects',
    $projectTypes
);
```

### Update Webhook Subscriptions
```php
// Get current webhooks
$webhooks = $teamleader->webhooks()->list();

// Find your webhook
$myWebhookUrl = 'https://example.com/webhook';
$currentTypes = [];
foreach ($webhooks['data'] as $webhook) {
    if ($webhook['url'] === $myWebhookUrl) {
        $currentTypes = $webhook['types'];
        break;
    }
}

// Remove old subscriptions
if (!empty($currentTypes)) {
    $teamleader->webhooks()->unregister($myWebhookUrl, $currentTypes);
}

// Add new subscriptions
$newTypes = ['invoice.booked', 'invoice.sent', 'deal.won'];
$teamleader->webhooks()->register($myWebhookUrl, $newTypes);
```

## Webhook Payload

When an event occurs, Teamleader will send a POST request to your registered webhook URL with the following structure:

```json
{
    "event": "invoice.booked",
    "data": {
        "id": "uuid-of-resource",
        "type": "invoice"
    },
    "meta": {
        "timestamp": "2025-10-06T12:34:56+00:00"
    }
}
```

## Security Recommendations

1. **Always use HTTPS**: Webhook URLs must use HTTPS to ensure secure data transmission
2. **Validate webhook signatures**: Implement signature validation to verify requests are from Teamleader
3. **Use dedicated endpoints**: Create specific endpoints for different event categories
4. **Implement idempotency**: Handle duplicate webhook deliveries gracefully
5. **Monitor webhook health**: Track failed deliveries and implement retry logic
6. **Keep URLs stable**: Avoid changing webhook URLs frequently to prevent missed events

## Response Structure

### list()
```php
[
    'data' => [
        [
            'url' => 'https://example.com/webhook',
            'types' => ['invoice.booked', 'invoice.sent', ...]
        ],
        ...
    ]
]
```

### register() / unregister()
Both methods return an empty response with HTTP 204 (No Content) status on success.

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $result = $teamleader->webhooks()->register(
        'https://example.com/webhook',
        ['invoice.booked']
    );
} catch (ValidationException $e) {
    // Handle validation errors (invalid URL, invalid event types, etc.)
    echo "Validation error: " . $e->getMessage();
} catch (TeamleaderException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage();
}
```

## Notes

- Webhooks must use HTTPS protocol
- Each webhook URL can be registered with multiple event types
- To completely remove a webhook, unregister all its event types
- Webhook deliveries are retried automatically by Teamleader in case of failures
- There is no built-in update operation - use unregister followed by register to modify subscriptions
- Webhooks are ordered by URL in the list response

# Tickets

Manage support tickets in Teamleader Focus.

## Overview

The Tickets resource provides full CRUD (Create, Read, Update, Delete) operations for managing support tickets in your Teamleader system. Tickets can be linked to customers, assigned to users, tracked through status changes, and include both customer-facing replies and internal notes.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
    - [info()](#info)
    - [create()](#create)
    - [update()](#update)
    - [delete()](#delete)
    - [addReply()](#addreply)
    - [addInternalMessage()](#addinternalmessage)
    - [listMessages()](#listmessages)
    - [getMessage()](#getmessage)
    - [importMessage()](#importmessage)
- [Helper Methods](#helper-methods)
- [Filters](#filters)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`tickets`

## Capabilities

- **Pagination**: ✅ Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ✅ Supported
- **Update**: ✅ Supported
- **Deletion**: ✅ Supported

## Available Methods

### `list()`

Get all tickets with optional filtering and pagination.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (page_size, page_number)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all tickets
$tickets = Teamleader::tickets()->list();

// Get tickets for specific customer
$tickets = Teamleader::tickets()->list([
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ]
]);

// With pagination
$tickets = Teamleader::tickets()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

### `info()`

Get detailed information about a specific ticket.

**Parameters:**
- `id` (string): Ticket UUID

**Example:**
```php
// Get ticket information
$ticket = Teamleader::tickets()->info('ticket-uuid');
```

### `create()`

Create a new ticket.

**Required Fields:**
- `subject` (string): Ticket subject/title
- `customer` (object): Customer object with type and id
- `ticket_status_id` (string): Ticket status UUID

**Optional Fields:**
- `assignee` (object): Assignee object with type and id
- `project_id` (string): Project UUID
- `custom_fields` (array): Custom field values

**Example:**
```php
$ticket = Teamleader::tickets()->create([
    'subject' => 'Website login issue',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'ticket_status_id' => 'status-uuid',
    'assignee' => [
        'type' => 'user',
        'id' => 'user-uuid'
    ]
]);
```

### `update()`

Update an existing ticket.

**Parameters:**
- `id` (string): Ticket UUID
- `data` (array): Fields to update

**Example:**
```php
$ticket = Teamleader::tickets()->update('ticket-uuid', [
    'subject' => 'Updated subject',
    'ticket_status_id' => 'new-status-uuid',
    'assignee' => [
        'type' => 'user',
        'id' => 'new-user-uuid'
    ]
]);
```

### `delete()`

Delete a ticket.

**Parameters:**
- `id` (string): Ticket UUID

**Example:**
```php
$result = Teamleader::tickets()->delete('ticket-uuid');
```

### `addReply()`

Add a customer-facing reply to a ticket.

**Parameters:**
- `ticketId` (string): Ticket UUID
- `message` (string): Message content (HTML)
- `messageType` (string): Type of message ('external_public' or 'external_internal')

**Example:**
```php
// Add public reply
$result = Teamleader::tickets()->addReply(
    'ticket-uuid',
    '<p>Thank you for your inquiry. We are investigating this issue.</p>',
    'external_public'
);

// Add internal reply (not visible to customer)
$result = Teamleader::tickets()->addReply(
    'ticket-uuid',
    '<p>Reply sent to customer via email</p>',
    'external_internal'
);
```

### `addInternalMessage()`

Add an internal note to a ticket (not visible to customer).

**Parameters:**
- `ticketId` (string): Ticket UUID
- `message` (string): Internal message content (HTML)

**Example:**
```php
$result = Teamleader::tickets()->addInternalMessage(
    'ticket-uuid',
    '<p>Spoke with customer via phone. Issue resolved.</p>'
);
```

### `listMessages()`

Get all messages for a ticket.

**Parameters:**
- `ticketId` (string): Ticket UUID

**Example:**
```php
$messages = Teamleader::tickets()->listMessages('ticket-uuid');

foreach ($messages['data'] as $message) {
    echo $message['message'];
}
```

### `getMessage()`

Get details of a specific message.

**Parameters:**
- `messageId` (string): Message UUID

**Example:**
```php
$message = Teamleader::tickets()->getMessage('message-uuid');
```

### `importMessage()`

Import an existing message (e.g., from email integration).

**Parameters:**
- `ticketId` (string): Ticket UUID
- `message` (string): Message content (HTML)
- `authorType` (string): Author type ('user' or 'contact')
- `authorId` (string): Author UUID
- `sentAt` (string): When message was sent (ISO 8601 format)

**Example:**
```php
// Import email from contact
$result = Teamleader::tickets()->importMessage(
    'ticket-uuid',
    '<p>I am having trouble accessing my account.</p>',
    'contact',
    'contact-uuid',
    '2025-10-17T09:30:00+00:00'
);

// Import reply from user
$result = Teamleader::tickets()->importMessage(
    'ticket-uuid',
    '<p>I have reset your password. Please try again.</p>',
    'user',
    'user-uuid',
    '2025-10-17T10:15:00+00:00'
);
```

## Helper Methods

### Customer Filtering

```php
// Get tickets for a company
$tickets = Teamleader::tickets()->forCustomer('company', 'company-uuid');

// Get tickets for a contact
$tickets = Teamleader::tickets()->forCustomer('contact', 'contact-uuid');
```

### Project Filtering

```php
// Get tickets for specific projects
$tickets = Teamleader::tickets()->forProjects([
    'project-uuid-1',
    'project-uuid-2'
]);
```

### ID Filtering

```php
// Get specific tickets by IDs
$tickets = Teamleader::tickets()->byIds(['uuid1', 'uuid2', 'uuid3']);
```

## Filters

Available filters for the `list()` method:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of ticket UUIDs |
| `customer` | object | Filter by customer (type and id) |
| `project_ids` | array | Filter by project UUIDs |

### Customer Filter Structure

```php
[
    'customer' => [
        'type' => 'company', // or 'contact'
        'id' => 'uuid-here'
    ]
]
```

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'ticket-uuid',
            'subject' => 'Website login issue',
            'customer' => [
                'type' => 'company',
                'id' => 'company-uuid'
            ],
            'assignee' => [
                'type' => 'user',
                'id' => 'user-uuid'
            ],
            'ticket_status' => [
                'type' => 'ticketStatus',
                'id' => 'status-uuid'
            ],
            'project' => [
                'type' => 'project',
                'id' => 'project-uuid'
            ],
            'created_at' => '2025-10-17T09:00:00+00:00',
            'updated_at' => '2025-10-17T14:30:00+00:00'
        ]
    ],
    'meta' => [
        'page' => [
            'size' => 20,
            'number' => 1
        ],
        'matches' => 45
    ]
]
```

### Info Response

```php
[
    'data' => [
        'id' => 'ticket-uuid',
        'subject' => 'Website login issue',
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'assignee' => [
            'type' => 'user',
            'id' => 'user-uuid'
        ],
        'ticket_status' => [
            'type' => 'ticketStatus',
            'id' => 'status-uuid'
        ],
        'project' => [
            'type' => 'project',
            'id' => 'project-uuid'
        ],
        'custom_fields' => [
            [
                'definition' => [
                    'type' => 'customFieldDefinition',
                    'id' => 'field-uuid'
                ],
                'value' => 'Custom value'
            ]
        ],
        'created_at' => '2025-10-17T09:00:00+00:00',
        'updated_at' => '2025-10-17T14:30:00+00:00',
        'web_url' => 'https://focus.teamleader.eu/ticket_detail.php?id=123'
    ]
]
```

### Messages List Response

```php
[
    'data' => [
        [
            'id' => 'message-uuid',
            'message' => '<p>Thank you for reporting this issue.</p>',
            'message_type' => 'external_public',
            'author' => [
                'type' => 'user',
                'id' => 'user-uuid'
            ],
            'sent_at' => '2025-10-17T10:00:00+00:00'
        ],
        [
            'id' => 'message-uuid-2',
            'message' => '<p>Internal note about the issue</p>',
            'message_type' => 'internal',
            'author' => [
                'type' => 'user',
                'id' => 'user-uuid'
            ],
            'sent_at' => '2025-10-17T10:30:00+00:00'
        ]
    ]
]
```

## Usage Examples

### Create Support Ticket

```php
// Create new support ticket for company
$ticket = Teamleader::tickets()->create([
    'subject' => 'Unable to access dashboard',
    'customer' => [
        'type' => 'company',
        'id' => 'company-uuid'
    ],
    'ticket_status_id' => 'open-status-uuid',
    'assignee' => [
        'type' => 'user',
        'id' => 'support-agent-uuid'
    ],
    'project_id' => 'support-project-uuid'
]);

// Add initial reply
Teamleader::tickets()->addReply(
    $ticket['data']['id'],
    '<p>Thank you for contacting support. We are looking into this issue.</p>',
    'external_public'
);
```

### Update Ticket Status

```php
// Get ticket
$ticket = Teamleader::tickets()->info('ticket-uuid');

// Update to resolved status
Teamleader::tickets()->update($ticket['data']['id'], [
    'ticket_status_id' => 'resolved-status-uuid'
]);

// Add resolution note
Teamleader::tickets()->addInternalMessage(
    $ticket['data']['id'],
    '<p>Issue resolved. Password was reset.</p>'
);
```

### Reassign Ticket

```php
// Reassign ticket to different agent
Teamleader::tickets()->update('ticket-uuid', [
    'assignee' => [
        'type' => 'user',
        'id' => 'new-agent-uuid'
    ]
]);

// Add internal note about reassignment
Teamleader::tickets()->addInternalMessage(
    'ticket-uuid',
    '<p>Reassigned to John for specialized support.</p>'
);
```

### Get Ticket Conversation

```php
// Get all messages for a ticket
$messages = Teamleader::tickets()->listMessages('ticket-uuid');

echo "<h2>Ticket Conversation</h2>";

foreach ($messages['data'] as $message) {
    $type = $message['message_type'];
    $author = $message['author']['type'];
    $time = date('Y-m-d H:i', strtotime($message['sent_at']));
    
    echo "<div class='message message-{$type}'>";
    echo "<strong>{$author} at {$time}:</strong><br>";
    echo $message['message'];
    echo "</div>";
}
```

### Import Email Conversation

```php
// Import customer's initial email
$importedMsg = Teamleader::tickets()->importMessage(
    'ticket-uuid',
    '<p>Hello, I cannot log into my account. Can you help?</p>',
    'contact',
    'contact-uuid',
    '2025-10-17T08:30:00+00:00'
);

// Import agent's email reply
$importedReply = Teamleader::tickets()->importMessage(
    'ticket-uuid',
    '<p>I have reset your password. Check your email.</p>',
    'user',
    'agent-uuid',
    '2025-10-17T09:15:00+00:00'
);
```

### Customer Support Dashboard

```php
// Get open tickets for company
$openTickets = Teamleader::tickets()->forCustomer('company', 'company-uuid');

// Filter by status (if needed)
$statuses = Teamleader::ticketStatus()->list();
$openStatusIds = array_column(
    array_filter($statuses['data'], fn($s) => $s['type'] === 'open'),
    'id'
);

// Display tickets
foreach ($openTickets['data'] as $ticket) {
    echo "Ticket: {$ticket['subject']}<br>";
    echo "Status: {$ticket['ticket_status']['id']}<br>";
    
    // Get latest message
    $messages = Teamleader::tickets()->listMessages($ticket['id']);
    if (!empty($messages['data'])) {
        $latest = $messages['data'][0];
        echo "Last update: {$latest['sent_at']}<br>";
    }
    
    echo "<hr>";
}
```

## Common Use Cases

### 1. Ticket Management System

```php
class TicketManager
{
    public function createFromEmail(array $emailData): array
    {
        // Create ticket
        $ticket = Teamleader::tickets()->create([
            'subject' => $emailData['subject'],
            'customer' => [
                'type' => 'contact',
                'id' => $emailData['contact_id']
            ],
            'ticket_status_id' => $this->getDefaultStatusId()
        ]);
        
        // Import email as first message
        Teamleader::tickets()->importMessage(
            $ticket['data']['id'],
            $emailData['body'],
            'contact',
            $emailData['contact_id'],
            $emailData['sent_at']
        );
        
        return $ticket;
    }
    
    public function assignToAgent(string $ticketId, string $agentId): void
    {
        Teamleader::tickets()->update($ticketId, [
            'assignee' => [
                'type' => 'user',
                'id' => $agentId
            ]
        ]);
    }
    
    public function resolve(string $ticketId, string $resolution): void
    {
        // Update status
        Teamleader::tickets()->update($ticketId, [
            'ticket_status_id' => $this->getResolvedStatusId()
        ]);
        
        // Add resolution note
        Teamleader::tickets()->addInternalMessage($ticketId, $resolution);
    }
}
```

### 2. SLA Tracking

```php
// Check tickets approaching SLA deadline
function getTicketsApproachingSLA(int $hours = 24): array
{
    $tickets = Teamleader::tickets()->list();
    $approaching = [];
    
    foreach ($tickets['data'] as $ticket) {
        $created = new DateTime($ticket['created_at']);
        $now = new DateTime();
        $age = $now->diff($created)->h;
        
        if ($age > (48 - $hours) && $age < 48) { // 48h SLA
            $approaching[] = [
                'ticket' => $ticket,
                'hours_remaining' => 48 - $age
            ];
        }
    }
    
    return $approaching;
}
```

### 3. Customer Communication

```php
// Send update to customer
function sendCustomerUpdate(string $ticketId, string $message): void
{
    Teamleader::tickets()->addReply(
        $ticketId,
        "<p>{$message}</p>",
        'external_public'
    );
    
    // Also log internal note
    Teamleader::tickets()->addInternalMessage(
        $ticketId,
        '<p>Customer update sent at ' . date('Y-m-d H:i:s') . '</p>'
    );
}
```

### 4. Ticket Statistics

```php
// Get ticket statistics for company
function getTicketStats(string $companyId): array
{
    $tickets = Teamleader::tickets()->forCustomer('company', $companyId);
    
    $stats = [
        'total' => count($tickets['data']),
        'by_status' => [],
        'average_age_days' => 0
    ];
    
    $totalAge = 0;
    
    foreach ($tickets['data'] as $ticket) {
        // Count by status
        $statusId = $ticket['ticket_status']['id'];
        $stats['by_status'][$statusId] = ($stats['by_status'][$statusId] ?? 0) + 1;
        
        // Calculate age
        $created = new DateTime($ticket['created_at']);
        $now = new DateTime();
        $age = $now->diff($created)->days;
        $totalAge += $age;
    }
    
    if ($stats['total'] > 0) {
        $stats['average_age_days'] = $totalAge / $stats['total'];
    }
    
    return $stats;
}
```

## Best Practices

### 1. Use Clear Ticket Subjects

```php
// Good: Descriptive subject
$ticket = Teamleader::tickets()->create([
    'subject' => 'Login error: "Invalid credentials" on mobile app',
    'customer' => [...],
    'ticket_status_id' => 'status-uuid'
]);

// Less helpful: Vague subject
// 'subject' => 'Problem'
```

### 2. Add Context in Messages

```php
// Good: Include relevant details
Teamleader::tickets()->addReply(
    'ticket-uuid',
    '<p>I have investigated the login issue. The problem was caused by an expired session token. I have reset your session and you should now be able to log in.</p>',
    'external_public'
);
```

### 3. Use Internal Messages for Notes

```php
// Document internal actions
Teamleader::tickets()->addInternalMessage(
    'ticket-uuid',
    '<p>Contacted customer via phone. Confirmed issue is resolved. Awaiting customer confirmation before closing.</p>'
);
```

### 4. Link Tickets to Projects

```php
// Link ticket to relevant project for better tracking
$ticket = Teamleader::tickets()->create([
    'subject' => 'Feature request: Export to Excel',
    'customer' => [...],
    'ticket_status_id' => 'status-uuid',
    'project_id' => 'website-redesign-project-uuid'
]);
```

### 5. Handle Pagination for Large Lists

```php
function getAllTicketsForCustomer(string $type, string $id): array
{
    $allTickets = [];
    $page = 1;
    $pageSize = 100;

    do {
        $response = Teamleader::tickets()->forCustomer($type, $id)
            ->list([], [
                'page_size' => $pageSize,
                'page_number' => $page
            ]);

        $allTickets = array_merge($allTickets, $response['data']);
        $page++;
    } while (count($response['data']) === $pageSize);

    return $allTickets;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $ticket = Teamleader::tickets()->create([
        'subject' => 'Support request',
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'ticket_status_id' => 'status-uuid'
    ]);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 422) {
        // Validation error
        Log::error('Ticket creation failed', [
            'errors' => $e->getDetails()
        ]);
    } elseif ($e->getCode() === 404) {
        // Status or customer not found
        Log::error('Resource not found');
    }
}

// Adding messages
try {
    Teamleader::tickets()->addReply('ticket-uuid', '<p>Message</p>', 'external_public');
} catch (TeamleaderException $e) {
    if ($e->getCode() === 404) {
        Log::error('Ticket not found');
    }
}
```

## Related Resources

- [Ticket Status](ticket-status.md) - Ticket status management
- [Companies](../crm/companies.md) - Link tickets to companies
- [Contacts](../crm/contacts.md) - Link tickets to contacts
- [Users](../general/users.md) - Assign tickets to users
- [Projects](../projects/projects.md) - Link tickets to projects
- [Time Tracking](../time-tracking/time-tracking.md) - Track time on tickets

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

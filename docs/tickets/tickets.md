# Tickets Resource

The Tickets resource allows you to manage support tickets (help desk cases) in Teamleader Focus. This includes creating tickets, updating their status, adding replies and internal notes, and managing ticket messages.

## Table of Contents

- [Overview](#overview)
- [Available Methods](#available-methods)
- [Usage Examples](#usage-examples)
- [Filtering](#filtering)
- [Pagination](#pagination)
- [Response Structure](#response-structure)

## Overview

Tickets are support cases that can be created for contacts or companies. Each ticket has:
- A subject and optional description
- A status (from configured ticket statuses)
- An optional assignee (user)
- Customer-facing replies and internal messages
- Attachments
- Optional links to projects and milestones

## Available Methods

### List Tickets

```php
$teamleader->tickets()->list(array $filters = [], array $options = [])
```

Get a list of tickets with optional filtering and pagination.

**Parameters:**
- `$filters` (array): Filter criteria (see [Filtering](#filtering))
- `$options` (array): Pagination options
    - `page_size` (int): Number of results per page (default: 20)
    - `page_number` (int): Page number (default: 1)

**Returns:** Array containing ticket data

### Get Ticket Info

```php
$teamleader->tickets()->info(string $id, $includes = null)
```

Get detailed information about a specific ticket.

**Parameters:**
- `$id` (string): Ticket UUID
- `$includes` (mixed): Optional includes (not used in current API)

**Returns:** Array containing complete ticket information

### Create Ticket

```php
$teamleader->tickets()->create(array $data)
```

Create a new ticket.

**Required fields:**
- `subject` (string): Ticket subject
- `customer` (object): Customer reference
    - `type` (string): "contact" or "company"
    - `id` (string): Customer UUID
- `ticket_status_id` (string): Status UUID

**Optional fields:**
- `assignee` (object): Assigned user
    - `type` (string): Must be "user"
    - `id` (string): User UUID
- `custom_fields` (array): Custom field values
    - `id` (string): Custom field definition UUID
    - `value` (mixed): Field value
- `description` (string): Ticket description (Markdown format)
- `participant` (object): Third-party participant
    - `customer` (object): Participant company
        - `type` (string): Must be "company"
        - `id` (string): Company UUID
- `initial_reply` (string): "automatic" or "disabled" (default: automatic)
- `milestone_id` (string): Associated milestone UUID

**Returns:** Array containing the created ticket ID and type

### Update Ticket

```php
$teamleader->tickets()->update(string $id, array $data)
```

Update an existing ticket.

**Parameters:**
- `$id` (string): Ticket UUID
- `$data` (array): Fields to update (all optional except id)

**Returns:** Empty response with 204 status code

### Add Reply

```php
$teamleader->tickets()->addReply(
    string $ticketId,
    string $body,
    ?string $ticketStatusId = null,
    array $attachments = []
)
```

Add a customer-facing reply to a ticket.

**Parameters:**
- `$ticketId` (string): Ticket UUID
- `$body` (string): Message body (HTML format)
- `$ticketStatusId` (string|null): Optional status UUID to update
- `$attachments` (array): Optional array of file UUIDs (files must have the ticket as subject)

**Returns:** Array containing the created message ID and type

### Add Internal Message

```php
$teamleader->tickets()->addInternalMessage(
    string $ticketId,
    string $body,
    ?string $ticketStatusId = null,
    array $attachments = []
)
```

Add an internal note to a ticket (not visible to customers).

**Parameters:** Same as `addReply()`

**Returns:** Array containing the created message ID and type

### Import Message

```php
$teamleader->tickets()->importMessage(
    string $ticketId,
    string $body,
    string $sentByType,
    string $sentById,
    string $sentAt,
    array $attachments = []
)
```

Import an existing message to a ticket (useful for importing email conversations).

**Parameters:**
- `$ticketId` (string): Ticket UUID
- `$body` (string): Message body (HTML format)
- `$sentByType` (string): Sender type ("company", "contact", or "user")
- `$sentById` (string): Sender UUID
- `$sentAt` (string): ISO 8601 datetime when message was sent
- `$attachments` (array): Optional array of file UUIDs

**Returns:** Array containing the imported message ID and type

### Get Message

```php
$teamleader->tickets()->getMessage(string $messageId)
```

Get detailed information about a specific ticket message.

**Parameters:**
- `$messageId` (string): Message UUID

**Returns:** Array containing complete message information

### List Messages

```php
$teamleader->tickets()->listMessages(
    string $ticketId,
    array $filters = [],
    array $options = []
)
```

Get all messages for a ticket.

**Parameters:**
- `$ticketId` (string): Ticket UUID
- `$filters` (array): Message filters
    - `type` (string): Message type ("customer", "internal", or "thirdParty")
    - `created_before` (string): ISO 8601 datetime
    - `created_after` (string): ISO 8601 datetime
- `$options` (array): Pagination options

**Returns:** Array containing message data

## Convenience Methods

### For Customer

```php
$teamleader->tickets()->forCustomer(
    string $customerType,
    string $customerId,
    array $additionalFilters = [],
    array $options = []
)
```

Get tickets for a specific customer (contact or company).

### For Projects

```php
$teamleader->tickets()->forProjects(
    array $projectIds,
    array $additionalFilters = [],
    array $options = []
)
```

Get tickets associated with specific projects.

### By IDs

```php
$teamleader->tickets()->byIds(array $ids, array $options = [])
```

Get specific tickets by their UUIDs.

### Exclude Statuses

```php
$teamleader->tickets()->excludeStatuses(
    array $statusIds,
    array $additionalFilters = [],
    array $options = []
)
```

Get tickets excluding specific statuses.

## Usage Examples

### Basic Examples

#### List all tickets

```php
$tickets = $teamleader->tickets()->list();
```

#### Get a specific ticket

```php
$ticket = $teamleader->tickets()->info('f29abf48-337d-44b4-aad4-585f5277a456');
```

#### Create a new ticket

```php
$ticket = $teamleader->tickets()->create([
    'subject' => 'Customer issue with product',
    'customer' => [
        'type' => 'company',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ],
    'ticket_status_id' => '46156648-87c6-478d-8aa7-1dc3a00dacab',
    'assignee' => [
        'type' => 'user',
        'id' => '98b2863e-7b01-4232-82f5-ede1f0b9db22'
    ],
    'description' => 'Customer reports that the product is not working as expected.',
]);
```

#### Update a ticket

```php
$result = $teamleader->tickets()->update('f29abf48-337d-44b4-aad4-585f5277a456', [
    'subject' => 'Updated subject',
    'ticket_status_id' => '46156648-87c6-478d-8aa7-1dc3a00dacab'
]);
```

### Working with Messages

#### Add a customer-facing reply

```php
$result = $teamleader->tickets()->addReply(
    'f29abf48-337d-44b4-aad4-585f5277a456',
    '<p>Thank you for contacting us. We will look into this issue.</p>',
    '46156648-87c6-478d-8aa7-1dc3a00dacab' // Optional: update status
);
```

#### Add an internal note

```php
$result = $teamleader->tickets()->addInternalMessage(
    'f29abf48-337d-44b4-aad4-585f5277a456',
    '<p>Customer called about this issue. Investigating root cause.</p>'
);
```

#### Import an email message

```php
$result = $teamleader->tickets()->importMessage(
    'f29abf48-337d-44b4-aad4-585f5277a456',
    '<p>This is an imported email message.</p>',
    'contact',
    '4b3b07c6-a4bf-4c1b-9471-283fee71b049',
    '2024-02-29T11:11:11+00:00',
    ['4f4288b2-c21b-4dac-87f6-a97511309079'] // Optional attachments
);
```

#### List all messages for a ticket

```php
$messages = $teamleader->tickets()->listMessages('f29abf48-337d-44b4-aad4-585f5277a456');
```

#### Filter messages by type

```php
// Get only customer messages
$customerMessages = $teamleader->tickets()->listMessages(
    'f29abf48-337d-44b4-aad4-585f5277a456',
    ['type' => 'customer']
);

// Get internal messages from a date range
$internalMessages = $teamleader->tickets()->listMessages(
    'f29abf48-337d-44b4-aad4-585f5277a456',
    [
        'type' => 'internal',
        'created_after' => '2024-01-01T00:00:00+00:00',
        'created_before' => '2024-02-01T00:00:00+00:00'
    ]
);
```

#### Get a specific message

```php
$message = $teamleader->tickets()->getMessage('eab232c6-49b2-4b7e-a977-5e1148dad471');
```

### Filtering Examples

#### Get tickets for a specific customer

```php
// For a company
$tickets = $teamleader->tickets()->forCustomer(
    'company',
    'f29abf48-337d-44b4-aad4-585f5277a456'
);

// For a contact
$tickets = $teamleader->tickets()->forCustomer(
    'contact',
    'f29abf48-337d-44b4-aad4-585f5277a456'
);
```

#### Get tickets for specific projects

```php
$tickets = $teamleader->tickets()->forProjects([
    '082e6289-30c5-45ad-bcd0-190b02d21e81',
    '32665afd-1818-0ed3-9e18-a603a3a21b95'
]);
```

#### Get specific tickets by ID

```php
$tickets = $teamleader->tickets()->byIds([
    '8607faa8-3d2e-0a66-a71e-e69f447a2ed1',
    '21467288-3baa-0027-a910-cd952030dbc2'
]);
```

#### Exclude specific statuses

```php
// Exclude closed and cancelled tickets
$tickets = $teamleader->tickets()->excludeStatuses([
    'a344c251-2494-0013-b433-ccee8e8435e6', // closed
    'c11dc02c-3556-0daf-8035-c5b0376eb928'  // cancelled
]);
```

### Advanced Examples

#### Create a ticket with custom fields

```php
$ticket = $teamleader->tickets()->create([
    'subject' => 'Technical Support Request',
    'customer' => [
        'type' => 'company',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ],
    'ticket_status_id' => '46156648-87c6-478d-8aa7-1dc3a00dacab',
    'custom_fields' => [
        [
            'id' => 'bf6765de-56eb-40ec-ad14-9096c5dc5fe1',
            'value' => '092980616'
        ]
    ],
    'description' => 'Customer needs assistance with technical setup.',
    'milestone_id' => '32665afd-1818-0ed3-9e18-a603a3a21b95'
]);
```

#### Create ticket with third-party participant

```php
$ticket = $teamleader->tickets()->create([
    'subject' => 'Collaboration with Partner',
    'customer' => [
        'type' => 'company',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ],
    'ticket_status_id' => '46156648-87c6-478d-8aa7-1dc3a00dacab',
    'participant' => [
        'customer' => [
            'type' => 'company',
            'id' => '2659dc4d-444b-4ced-b51c-b87591f604d7'
        ]
    ]
]);
```

#### Complex filtering

```php
// Get tickets for a customer, excluding certain statuses, with pagination
$tickets = $teamleader->tickets()->list(
    [
        'relates_to' => [
            'type' => 'company',
            'id' => '2659dc4d-444b-4ced-b51c-b87591f604d7'
        ],
        'project_ids' => ['082e6289-30c5-45ad-bcd0-190b02d21e81'],
        'exclude' => [
            'status_ids' => [
                'a344c251-2494-0013-b433-ccee8e8435e6',
                'c11dc02c-3556-0daf-8035-c5b0376eb928'
            ]
        ]
    ],
    [
        'page_size' => 50,
        'page_number' => 1
    ]
);
```

## Filtering

Available filters for the `list()` method:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of ticket UUIDs |
| `relates_to` | object | Filter by related customer |
| `relates_to.type` | string | Customer type ("contact" or "company") |
| `relates_to.id` | string | Customer UUID |
| `project_ids` | array | Array of project UUIDs |
| `exclude` | object | Exclude criteria |
| `exclude.status_ids` | array | Array of status UUIDs to exclude |

## Pagination

Pagination is supported using the `page` parameter in options:

```php
$tickets = $teamleader->tickets()->list(
    [], // filters
    [
        'page_size' => 20,    // Default: 20
        'page_number' => 1    // Default: 1
    ]
);
```

For messages, pagination works the same way:

```php
$messages = $teamleader->tickets()->listMessages(
    'ticket-uuid',
    [], // filters
    [
        'page_size' => 20,
        'page_number' => 1
    ]
);
```

## Response Structure

### Create Response

```php
[
    'data' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'ticket'
    ]
]
```

### Update Response

Empty response with HTTP 204 status code on success.

### Info Response

```php
[
    'id' => 'f29abf48-337d-44b4-aad4-585f5277a456',
    'reference' => 123,
    'subject' => 'My ticket subject',
    'status' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'ticketStatus'
    ],
    'assignee' => [
        'type' => 'user',
        'id' => '66abace2-62af-0836-a927-fe3f44b9b47b'
    ], // nullable
    'created_at' => '2017-05-09T11:25:11+00:00',
    'closed_at' => '2017-05-09T11:25:11+00:00', // nullable
    'customer' => [
        'type' => 'contact',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ],
    'participant' => [
        'customer' => [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'company'
        ]
    ], // nullable
    'last_message_at' => '2017-05-09T11:25:11+00:00', // nullable
    'description' => 'My ticket details',
    'project' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'project'
    ], // nullable
    'milestone' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'milestone'
    ], // nullable
    'custom_fields' => [
        [
            'definition' => [
                'type' => 'customFieldDefinition',
                'id' => 'bf6765de-56eb-40ec-ad14-9096c5dc5fe1'
            ],
            'value' => '092980616'
        ]
    ]
]
```

### List Response

```php
[
    'data' => [
        // Array of ticket objects (same structure as info response)
    ]
]
```

### Message Response (addReply, addInternalMessage, importMessage)

```php
[
    'data' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'message'
    ]
]
```

### Get Message Response

```php
[
    'message_id' => 'f29abf48-337d-44b4-aad4-585f5277a456',
    'body' => '<p>This is a message</p>',
    'raw_body' => '<p>This is a message</p>',
    'created_at' => '2017-05-09T11:25:11+00:00',
    'sent_by' => [
        'type' => 'contact',
        'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
    ],
    'ticket' => [
        'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
        'type' => 'ticket'
    ],
    'attachments' => [
        [
            'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
            'type' => 'file'
        ]
    ],
    'type' => 'customer' // or 'internal' or 'thirdParty'
]
```

### List Messages Response

```php
[
    'data' => [
        [
            'message_id' => 'f29abf48-337d-44b4-aad4-585f5277a456',
            'body' => '<p>This is a message</p>',
            'type' => 'customer',
            'created_at' => '2017-05-09T11:25:11+00:00',
            'sent_by' => [
                'type' => 'contact',
                'id' => 'f29abf48-337d-44b4-aad4-585f5277a456'
            ],
            'attachments' => [
                [
                    'id' => 'eab232c6-49b2-4b7e-a977-5e1148dad471',
                    'type' => 'file'
                ]
            ]
        ]
        // ... more messages
    ],
    'meta' => [ // Only included with 'includes=pagination' parameter
        'page' => [
            'size' => 10,
            'number' => 2
        ],
        'matches' => 12
    ]
]
```

## Notes

- All file attachments must have the ticket as their subject before being attached to messages
- The `body` field for messages uses HTML formatting
- The `description` field for tickets uses Markdown formatting
- Ticket statuses and their IDs are configured in Teamleader Focus
- The `initial_reply` parameter controls automatic reply creation based on configuration
- Participants are always companies (third-party organizations involved in the ticket)
- Messages can be of type "customer" (visible to customer), "internal" (staff-only), or "thirdParty" (involving participant)

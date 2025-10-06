# Ticket Status Resource

The Ticket Status resource allows you to retrieve the list of available ticket statuses configured in Teamleader Focus. This is a read-only resource used to get status IDs needed when creating or updating tickets.

## Table of Contents

- [Overview](#overview)
- [Available Methods](#available-methods)
- [Usage Examples](#usage-examples)
- [Response Structure](#response-structure)

## Overview

Ticket statuses represent the different states a ticket can be in within Teamleader Focus. The system includes standard statuses and allows for custom statuses to be configured.

### Standard Status Types

- `new` - New ticket, not yet addressed
- `open` - Ticket is being worked on
- `waiting_for_client` - Waiting for customer response
- `escalated_thirdparty` - Escalated to a third party
- `closed` - Ticket is resolved and closed
- `custom` - Custom status defined by the organization

## Available Methods

### List Ticket Statuses

```php
$teamleader->ticketStatus()->list(array $filters = [], array $options = [])
```

Get a list of all available ticket statuses.

**Parameters:**
- `$filters` (array): Filter criteria
    - `ids` (array): Optional array of status UUIDs to filter by
- `$options` (array): Not used for this resource

**Returns:** Array containing ticket status data

## Convenience Methods

### By IDs

```php
$teamleader->ticketStatus()->byIds(array $ids)
```

Get specific ticket statuses by their UUIDs.

**Parameters:**
- `$ids` (array): Array of ticket status UUIDs

**Returns:** Array containing filtered status data

### Find by Type

```php
$teamleader->ticketStatus()->findByType(string $type)
```

Find ticket statuses by their type.

**Parameters:**
- `$type` (string): Status type (new, open, waiting_for_client, escalated_thirdparty, closed, custom)

**Returns:** Array of matching statuses or null if not found

### Custom Statuses

```php
$teamleader->ticketStatus()->customStatuses()
```

Get all custom ticket statuses.

**Returns:** Array of custom statuses or null if none found

### As Options

```php
$teamleader->ticketStatus()->asOptions()
```

Get ticket statuses formatted as key-value pairs suitable for dropdowns/select fields.

**Returns:** Associative array with status IDs as keys and labels as values

### All IDs

```php
$teamleader->ticketStatus()->allIds()
```

Get all ticket status IDs.

**Returns:** Array of status UUIDs

### Find by Label

```php
$teamleader->ticketStatus()->findByLabel(string $label)
```

Find a ticket status by its label (particularly useful for custom statuses).

**Parameters:**
- `$label` (string): Status label to search for (case-insensitive)

**Returns:** Status data or null if not found

### Is Valid Status Type

```php
$teamleader->ticketStatus()->isValidStatusType(string $type)
```

Check if a status type is valid.

**Parameters:**
- `$type` (string): Status type to validate

**Returns:** Boolean indicating if the type is valid

### Get Valid Status Types

```php
$teamleader->ticketStatus()->getValidStatusTypes()
```

Get all valid status types.

**Returns:** Array of valid status type strings

## Usage Examples

### Basic Examples

#### List all ticket statuses

```php
$statuses = $teamleader->ticketStatus()->list();
```

#### Get specific statuses by ID

```php
$statuses = $teamleader->ticketStatus()->byIds([
    '46156648-87c6-478d-8aa7-1dc3a00dacab',
    '46156648-87c6-478d-8aa7-1dc3a00daca4'
]);
```

### Working with Status Types

#### Get statuses by type

```php
// Get all "open" statuses
$openStatuses = $teamleader->ticketStatus()->findByType('open');

// Get all "closed" statuses
$closedStatuses = $teamleader->ticketStatus()->findByType('closed');

// Get all custom statuses
$customStatuses = $teamleader->ticketStatus()->customStatuses();
```

#### Check if a status type is valid

```php
if ($teamleader->ticketStatus()->isValidStatusType('open')) {
    // Type is valid
}
```

#### Get all valid status types

```php
$validTypes = $teamleader->ticketStatus()->getValidStatusTypes();
// Returns: ['new', 'open', 'waiting_for_client', 'escalated_thirdparty', 'closed', 'custom']
```

### UI Integration Examples

#### Get statuses as dropdown options

```php
$statusOptions = $teamleader->ticketStatus()->asOptions();
// Returns: ['uuid1' => 'New', 'uuid2' => 'Open', 'uuid3' => 'My Custom Status', ...]

// Use in a view
foreach ($statusOptions as $id => $label) {
    echo "<option value=\"{$id}\">{$label}</option>";
}
```

#### Find a status by label

```php
// Find by standard status (formatted label)
$openStatus = $teamleader->ticketStatus()->findByLabel('Open');

// Find by custom status label
$customStatus = $teamleader->ticketStatus()->findByLabel('My Custom Status');

if ($customStatus) {
    echo "Status ID: " . $customStatus['id'];
    echo "Status Type: " . $customStatus['status'];
    echo "Status Label: " . $customStatus['label'];
}
```

#### Get all status IDs for filtering

```php
$allStatusIds = $teamleader->ticketStatus()->allIds();
// Use these IDs for filtering tickets by status
```

### Practical Application Examples

#### Create a ticket with a specific status

```php
// First, find the status you want
$newStatus = $teamleader->ticketStatus()->findByType('new');

if ($newStatus && isset($newStatus[0]['id'])) {
    // Create a ticket with this status
    $ticket = $teamleader->tickets()->create([
        'subject' => 'New customer inquiry',
        'customer' => [
            'type' => 'company',
            'id' => 'company-uuid'
        ],
        'ticket_status_id' => $newStatus[0]['id']
    ]);
}
```

#### Filter tickets excluding certain statuses

```php
// Get closed status IDs
$closedStatuses = $teamleader->ticketStatus()->findByType('closed');
$closedStatusIds = array_column($closedStatuses, 'id');

// Get all tickets except closed ones
$activeTickets = $teamleader->tickets()->excludeStatuses($closedStatusIds);
```

#### Build a status selector with grouped options

```php
$allStatuses = $teamleader->ticketStatus()->list();

// Group statuses by type
$grouped = [
    'standard' => [],
    'custom' => []
];

foreach ($allStatuses['data'] as $status) {
    if ($status['status'] === 'custom') {
        $grouped['custom'][] = $status;
    } else {
        $grouped['standard'][] = $status;
    }
}

// Render grouped select
echo '<optgroup label="Standard Statuses">';
foreach ($grouped['standard'] as $status) {
    $label = ucwords(str_replace('_', ' ', $status['status']));
    echo "<option value=\"{$status['id']}\">{$label}</option>";
}
echo '</optgroup>';

echo '<optgroup label="Custom Statuses">';
foreach ($grouped['custom'] as $status) {
    echo "<option value=\"{$status['id']}\">{$status['label']}</option>";
}
echo '</optgroup>';
```

#### Validate status before updating a ticket

```php
$newStatusId = '46156648-87c6-478d-8aa7-1dc3a00dacab';

// Verify the status exists
$statuses = $teamleader->ticketStatus()->byIds([$newStatusId]);

if (!empty($statuses['data'])) {
    // Status is valid, proceed with update
    $teamleader->tickets()->update('ticket-uuid', [
        'ticket_status_id' => $newStatusId
    ]);
} else {
    // Invalid status ID
    throw new Exception('Invalid ticket status ID');
}
```

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => '46156648-87c6-478d-8aa7-1dc3a00dacab',
            'status' => 'new'
            // Note: 'label' is only present for custom statuses
        ],
        [
            'id' => '46156648-87c6-478d-8aa7-1dc3a00daca1',
            'status' => 'open'
        ],
        [
            'id' => '46156648-87c6-478d-8aa7-1dc3a00daca2',
            'status' => 'waiting_for_client'
        ],
        [
            'id' => '46156648-87c6-478d-8aa7-1dc3a00daca3',
            'status' => 'escalated_thirdparty'
        ],
        [
            'id' => '46156648-87c6-478d-8aa7-1dc3a00daca4',
            'status' => 'closed'
        ],
        [
            'id' => '46156648-87c6-478d-8aa7-1dc3a00daca5',
            'status' => 'custom',
            'label' => 'Waiting for Parts' // Custom label only for custom statuses
        ],
        [
            'id' => '46156648-87c6-478d-8aa7-1dc3a00daca6',
            'status' => 'custom',
            'label' => 'Under Review'
        ]
    ]
]
```

### Field Descriptions

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique UUID of the ticket status |
| `status` | string | Status type (new, open, waiting_for_client, escalated_thirdparty, closed, custom) |
| `label` | string | Custom label (only present when status type is "custom") |

## Notes

- Ticket statuses are configured in Teamleader Focus and cannot be created, updated, or deleted via the API
- Standard statuses (new, open, waiting_for_client, escalated_thirdparty, closed) do not have a `label` field
- Custom statuses always have the `status` field set to "custom" and include a `label` field with the custom name
- The status IDs returned by this endpoint are used when creating or updating tickets
- Status IDs are persistent and should be cached appropriately to reduce API calls
- There is no pagination for this endpoint as the number of statuses is typically small

## Best Practices

1. **Cache Status Data**: Status configurations rarely change, so consider caching the results to reduce API calls
2. **Use Type Checking**: Use `isValidStatusType()` to validate status types before filtering
3. **Handle Custom Statuses**: Always check for the presence of the `label` field when displaying custom statuses
4. **Dropdown Integration**: Use `asOptions()` for easy integration with form select fields
5. **Status Validation**: Verify status IDs exist before using them in ticket operations
6. **Group Presentation**: When displaying statuses to users, consider grouping standard and custom statuses separately for better UX

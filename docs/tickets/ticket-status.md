# Ticket Status

Manage ticket statuses in Teamleader Focus.

## Overview

The Ticket Status resource provides read-only access to ticket status information. Ticket statuses define the workflow states that tickets can be in (new, open, closed, etc.). This resource is read-only as statuses are configured in the Teamleader Focus interface.

## Navigation

- [Endpoint](#endpoint)
- [Capabilities](#capabilities)
- [Available Methods](#available-methods)
    - [list()](#list)
- [Helper Methods](#helper-methods)
- [Status Types](#status-types)
- [Filters](#filters)
- [Response Structure](#response-structure)
- [Usage Examples](#usage-examples)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)
- [Related Resources](#related-resources)

## Endpoint

`ticketStatus`

## Capabilities

- **Pagination**: ❌ Not Supported
- **Filtering**: ✅ Supported
- **Sorting**: ❌ Not Supported
- **Sideloading**: ❌ Not Supported
- **Creation**: ❌ Not Supported (read-only)
- **Update**: ❌ Not Supported (read-only)
- **Deletion**: ❌ Not Supported (read-only)

## Available Methods

### `list()`

Get all ticket statuses with optional filtering.

**Parameters:**
- `filters` (array): Filters to apply
- `options` (array): Additional options (not used)

**Example:**
```php
use McoreServices\TeamleaderSDK\Facades\Teamleader;

// Get all ticket statuses
$statuses = Teamleader::ticketStatus()->list();

// Get specific statuses by IDs
$statuses = Teamleader::ticketStatus()->list([
    'ids' => ['uuid1', 'uuid2']
]);
```

## Helper Methods

### ID Filtering

```php
// Get specific statuses by IDs
$statuses = Teamleader::ticketStatus()->byIds(['uuid1', 'uuid2', 'uuid3']);
```

### Type Filtering

```php
// Find statuses by type
$openStatuses = Teamleader::ticketStatus()->findByType('open');
$closedStatuses = Teamleader::ticketStatus()->findByType('closed');
```

### Custom Status Filtering

```php
// Get only custom statuses
$customStatuses = Teamleader::ticketStatus()->customStatuses();
```

### Dropdown Options

```php
// Get statuses as key-value pairs for dropdowns
$options = Teamleader::ticketStatus()->asOptions();

// Returns: ['uuid1' => 'New', 'uuid2' => 'Open', ...]
```

## Status Types

Available ticket status types:

| Type | Description |
|------|-------------|
| `new` | Newly created tickets |
| `open` | Tickets being worked on |
| `waiting_for_client` | Awaiting customer response |
| `escalated_thirdparty` | Escalated to third party |
| `closed` | Resolved/closed tickets |
| `custom` | Custom status types |

## Filters

Available filters for the `list()` method:

| Filter | Type | Description |
|--------|------|-------------|
| `ids` | array | Array of ticket status UUIDs |

## Response Structure

### List Response

```php
[
    'data' => [
        [
            'id' => 'status-uuid-1',
            'name' => 'New',
            'type' => 'new',
            'icon' => 'envelope',
            'color' => '#FF6B6B'
        ],
        [
            'id' => 'status-uuid-2',
            'name' => 'In Progress',
            'type' => 'open',
            'icon' => 'cog',
            'color' => '#4ECDC4'
        ],
        [
            'id' => 'status-uuid-3',
            'name' => 'Waiting for Customer',
            'type' => 'waiting_for_client',
            'icon' => 'clock',
            'color' => '#FFE66D'
        ],
        [
            'id' => 'status-uuid-4',
            'name' => 'Resolved',
            'type' => 'closed',
            'icon' => 'check',
            'color' => '#95E1D3'
        ],
        [
            'id' => 'status-uuid-5',
            'name' => 'Escalated',
            'type' => 'custom',
            'icon' => 'arrow-up',
            'color' => '#F38181'
        ]
    ]
]
```

## Usage Examples

### Get All Statuses

```php
// Fetch all ticket statuses
$statuses = Teamleader::ticketStatus()->list();

foreach ($statuses['data'] as $status) {
    echo "{$status['name']} ({$status['type']})<br>";
}
```

### Get Specific Statuses

```php
// Get statuses for specific IDs
$statusIds = ['uuid1', 'uuid2', 'uuid3'];
$statuses = Teamleader::ticketStatus()->byIds($statusIds);
```

### Find Status by Type

```php
// Find all "open" type statuses
$openStatuses = Teamleader::ticketStatus()->findByType('open');

if (!empty($openStatuses)) {
    $defaultOpenId = $openStatuses[0]['id'];
    
    // Use for creating tickets
    $ticket = Teamleader::tickets()->create([
        'subject' => 'Support request',
        'customer' => [...],
        'ticket_status_id' => $defaultOpenId
    ]);
}
```

### Generate Status Dropdown

```php
// Get statuses for form dropdown
$options = Teamleader::ticketStatus()->asOptions();

echo '<select name="ticket_status">';
foreach ($options as $id => $name) {
    echo "<option value='{$id}'>{$name}</option>";
}
echo '</select>';
```

### Get Default Status for New Tickets

```php
// Find "new" status for creating tickets
$statuses = Teamleader::ticketStatus()->findByType('new');

if (!empty($statuses)) {
    $newStatusId = $statuses[0]['id'];
    
    // Create ticket with "new" status
    $ticket = Teamleader::tickets()->create([
        'subject' => 'Customer inquiry',
        'customer' => [...],
        'ticket_status_id' => $newStatusId
    ]);
}
```

### Build Status Badge Components

```php
// Create colored status badges for UI
$statuses = Teamleader::ticketStatus()->list();

foreach ($statuses['data'] as $status) {
    echo '<span class="badge" style="background-color: ' . $status['color'] . ';">';
    echo '<i class="icon-' . $status['icon'] . '"></i> ';
    echo $status['name'];
    echo '</span>';
}
```

## Common Use Cases

### 1. Status Selector Component

```php
class TicketStatusSelector
{
    private $statuses;
    
    public function __construct()
    {
        $this->statuses = Teamleader::ticketStatus()->list()['data'];
    }
    
    public function getStatusOptions(): array
    {
        return array_column($this->statuses, 'name', 'id');
    }
    
    public function getStatusByType(string $type): ?array
    {
        foreach ($this->statuses as $status) {
            if ($status['type'] === $type) {
                return $status;
            }
        }
        return null;
    }
    
    public function getStatusById(string $id): ?array
    {
        foreach ($this->statuses as $status) {
            if ($status['id'] === $id) {
                return $status;
            }
        }
        return null;
    }
}
```

### 2. Ticket Workflow Automation

```php
class TicketWorkflow
{
    private $statuses;
    
    public function __construct()
    {
        $this->statuses = $this->indexStatuses();
    }
    
    private function indexStatuses(): array
    {
        $statuses = Teamleader::ticketStatus()->list()['data'];
        $indexed = [];
        
        foreach ($statuses as $status) {
            $indexed[$status['type']] = $status['id'];
        }
        
        return $indexed;
    }
    
    public function moveToOpen(string $ticketId): void
    {
        if (isset($this->statuses['open'])) {
            Teamleader::tickets()->update($ticketId, [
                'ticket_status_id' => $this->statuses['open']
            ]);
        }
    }
    
    public function waitForCustomer(string $ticketId): void
    {
        if (isset($this->statuses['waiting_for_client'])) {
            Teamleader::tickets()->update($ticketId, [
                'ticket_status_id' => $this->statuses['waiting_for_client']
            ]);
        }
    }
    
    public function resolve(string $ticketId): void
    {
        if (isset($this->statuses['closed'])) {
            Teamleader::tickets()->update($ticketId, [
                'ticket_status_id' => $this->statuses['closed']
            ]);
        }
    }
}
```

### 3. Status-based Ticket Filtering

```php
// Get IDs for specific status types
function getStatusIdsByType(string $type): array
{
    $statuses = Teamleader::ticketStatus()->findByType($type);
    return array_column($statuses, 'id');
}

// Get all open tickets (assuming you filter locally after fetching)
$allTickets = Teamleader::tickets()->list();
$openStatusIds = getStatusIdsByType('open');

$openTickets = array_filter($allTickets['data'], function($ticket) use ($openStatusIds) {
    return in_array($ticket['ticket_status']['id'], $openStatusIds);
});
```

### 4. Status Statistics Dashboard

```php
function getTicketStatusStats(): array
{
    $statuses = Teamleader::ticketStatus()->list()['data'];
    $tickets = Teamleader::tickets()->list();
    
    $stats = [];
    
    foreach ($statuses as $status) {
        $count = 0;
        
        foreach ($tickets['data'] as $ticket) {
            if ($ticket['ticket_status']['id'] === $status['id']) {
                $count++;
            }
        }
        
        $stats[] = [
            'status' => $status['name'],
            'type' => $status['type'],
            'color' => $status['color'],
            'count' => $count
        ];
    }
    
    return $stats;
}

// Display statistics
$stats = getTicketStatusStats();

foreach ($stats as $stat) {
    echo '<div style="color: ' . $stat['color'] . '">';
    echo $stat['status'] . ': ' . $stat['count'] . ' tickets';
    echo '</div>';
}
```

## Best Practices

### 1. Cache Status Data

```php
// Statuses rarely change, so cache them
class StatusCache
{
    private static $statuses = null;
    
    public static function getStatuses(): array
    {
        if (self::$statuses === null) {
            self::$statuses = Teamleader::ticketStatus()->list()['data'];
        }
        return self::$statuses;
    }
    
    public static function getStatusById(string $id): ?array
    {
        $statuses = self::getStatuses();
        
        foreach ($statuses as $status) {
            if ($status['id'] === $id) {
                return $status;
            }
        }
        
        return null;
    }
}
```

### 2. Validate Status Before Assignment

```php
function setTicketStatus(string $ticketId, string $statusId): bool
{
    // Verify status exists
    $statuses = Teamleader::ticketStatus()->byIds([$statusId]);
    
    if (empty($statuses['data'])) {
        Log::error('Invalid status ID', ['status_id' => $statusId]);
        return false;
    }
    
    // Update ticket
    try {
        Teamleader::tickets()->update($ticketId, [
            'ticket_status_id' => $statusId
        ]);
        return true;
    } catch (Exception $e) {
        Log::error('Failed to update ticket status', [
            'ticket_id' => $ticketId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
```

### 3. Use Type-based Logic

```php
// Instead of hardcoding status IDs, use types
function getStatusIdByType(string $type): ?string
{
    $statuses = Teamleader::ticketStatus()->findByType($type);
    
    return !empty($statuses) ? $statuses[0]['id'] : null;
}

// Usage
$newStatusId = getStatusIdByType('new');
$closedStatusId = getStatusIdByType('closed');
```

### 4. Build Status Mapping

```php
// Create a mapping of types to IDs for quick lookup
function buildStatusMapping(): array
{
    $statuses = Teamleader::ticketStatus()->list()['data'];
    $mapping = [];
    
    foreach ($statuses as $status) {
        $mapping[$status['type']] = [
            'id' => $status['id'],
            'name' => $status['name'],
            'color' => $status['color'],
            'icon' => $status['icon']
        ];
    }
    
    return $mapping;
}

// Usage
$statusMap = buildStatusMapping();
$openStatusId = $statusMap['open']['id'];
```

### 5. Handle Missing Statuses

```php
function getStatusOrDefault(string $type, string $default = 'new'): string
{
    $statuses = Teamleader::ticketStatus()->findByType($type);
    
    if (!empty($statuses)) {
        return $statuses[0]['id'];
    }
    
    // Fallback to default
    $defaultStatuses = Teamleader::ticketStatus()->findByType($default);
    
    if (!empty($defaultStatuses)) {
        return $defaultStatuses[0]['id'];
    }
    
    // Last resort: get first available status
    $allStatuses = Teamleader::ticketStatus()->list();
    return $allStatuses['data'][0]['id'] ?? null;
}
```

## Error Handling

```php
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;

try {
    $statuses = Teamleader::ticketStatus()->list();
} catch (TeamleaderException $e) {
    Log::error('Failed to fetch ticket statuses', [
        'error' => $e->getMessage()
    ]);
    
    // Provide fallback or default statuses
    $statuses = ['data' => []];
}

// Validate status exists before using
try {
    $statuses = Teamleader::ticketStatus()->byIds(['status-uuid']);
    
    if (empty($statuses['data'])) {
        throw new Exception('Status not found');
    }
} catch (TeamleaderException $e) {
    Log::error('Status validation failed');
}
```

## Important Notes

### 1. Read-Only Resource

Ticket statuses cannot be created, updated, or deleted via the API. They must be configured in the Teamleader Focus interface.

### 2. Status Types vs Custom Statuses

- Standard types: `new`, `open`, `waiting_for_client`, `escalated_thirdparty`, `closed`
- Custom statuses will have `type: 'custom'`

### 3. Multiple Statuses Per Type

There can be multiple statuses with the same type (e.g., multiple "open" statuses with different names).

## Related Resources

- [Tickets](tickets.md) - Ticket management
- [Users](../general/users.md) - Users working with tickets
- [Companies](../crm/companies.md) - Ticket customers
- [Contacts](../crm/contacts.md) - Ticket contacts

## See Also

- [Usage Guide](../usage.md) - General SDK usage
- [Filtering](../filtering.md) - Advanced filtering techniques

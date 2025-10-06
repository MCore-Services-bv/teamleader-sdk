# Events

Manage calendar events in Teamleader Focus. This resource provides complete CRUD operations for managing calendar events, including attendees, links to other entities, and activity types.

## Endpoint

`events`

## Capabilities

- **Supports Pagination**: ✅ Supported
- **Supports Filtering**: ✅ Supported
- **Supports Sorting**: ✅ Supported
- **Supports Sideloading**: ❌ Not Supported
- **Supports Creation**: ✅ Supported
- **Supports Update**: ✅ Supported
- **Supports Deletion**: ✅ Supported (via cancel)
- **Supports Batch**: ❌ Not Supported

## Available Methods

### `list()`

Get a paginated list of calendar events with filtering and sorting options.

**Parameters:**
- `filters` (array): Array of filters to apply
- `options` (array): Pagination, sorting, and include options

**Example:**
```php
$events = $teamleader->events()->list([
    'ends_after' => '2025-01-01T00:00:00+00:00',
    'starts_before' => '2025-12-31T23:59:59+00:00'
]);
```

### `info()`

Get detailed information about a specific calendar event.

**Parameters:**
- `id` (string): Event UUID
- `includes` (array|string): Relations to include (not used for events)

**Example:**
```php
$event = $teamleader->events()->info('event-uuid-here');
```

### `create()`

Create a new calendar event.

**Required Parameters:**
- `title` (string): Event title
- `activity_type_id` (string): Activity type UUID
- `starts_at` (string): Start datetime in ISO 8601 format
- `ends_at` (string): End datetime in ISO 8601 format

**Optional Parameters:**
- `description` (string): Event description
- `location` (string): Event location
- `work_type_id` (string): Work type UUID
- `attendees` (array): Array of attendee objects
- `links` (array): Array of link objects

**Example:**
```php
$event = $teamleader->events()->create([
    'title' => 'Meeting with stakeholders',
    'activity_type_id' => 'b0a9ace5-fe82-4827-9d90-fc52f2c93050',
    'starts_at' => '2025-02-04T16:00:00+00:00',
    'ends_at' => '2025-02-04T18:00:00+00:00',
    'location' => 'Conference Room A',
    'attendees' => [
        ['type' => 'user', 'id' => '6ddd2666-65a0-497f-9f01-54c4343ec1a6']
    ],
    'links' => [
        ['type' => 'company', 'id' => 'c9258836-f9a5-40cb-aa2a-d55c22991b93']
    ]
]);
```

### `update()`

Update an existing calendar event.

**Parameters:**
- `id` (string): Event UUID
- `data` (array): Array of data to update

**Example:**
```php
$event = $teamleader->events()->update('event-uuid', [
    'title' => 'Updated meeting title',
    'starts_at' => '2025-02-04T17:00:00+00:00'
]);
```

### `cancel()` / `delete()`

Cancel a calendar event (for all attendees). Note that `delete()` is an alias for `cancel()`.

**Parameters:**
- `id` (string): Event UUID

**Example:**
```php
$result = $teamleader->events()->cancel('event-uuid');
// or
$result = $teamleader->events()->delete('event-uuid');
```

### `forUser()`

Get all events for a specific user.

**Parameters:**
- `userId` (string): User UUID
- `options` (array): Additional options

**Example:**
```php
$events = $teamleader->events()->forUser('user-uuid');
```

### `forActivityType()`

Get events filtered by activity type.

**Parameters:**
- `activityTypeId` (string): Activity type UUID
- `options` (array): Additional options

**Example:**
```php
$events = $teamleader->events()->forActivityType('activity-type-uuid');
```

### `search()`

Search events by term (searches title and description).

**Parameters:**
- `term` (string): Search term
- `options` (array): Additional options

**Example:**
```php
$events = $teamleader->events()->search('coffee');
```

### `betweenDates()`

Get events within a specific date range.

**Parameters:**
- `startsAfter` (string): ISO 8601 datetime
- `endsBefore` (string): ISO 8601 datetime
- `options` (array): Additional options

**Example:**
```php
$events = $teamleader->events()->betweenDates(
    '2025-02-01T00:00:00+00:00',
    '2025-02-28T23:59:59+00:00'
);
```

### `byIds()`

Get events by specific IDs.

**Parameters:**
- `ids` (array): Array of event UUIDs
- `options` (array): Additional options

**Example:**
```php
$events = $teamleader->events()->byIds([
    'event-uuid-1',
    'event-uuid-2'
]);
```

### `forAttendee()`

Get events for a specific attendee.

**Parameters:**
- `attendeeType` (string): Type of attendee ('user' or 'contact')
- `attendeeId` (string): UUID of the attendee
- `options` (array): Additional options

**Example:**
```php
// Get events where a specific user is an attendee
$events = $teamleader->events()->forAttendee('user', 'user-uuid');

// Get events where a specific contact is an attendee
$events = $teamleader->events()->forAttendee('contact', 'contact-uuid');
```

### `forLink()`

Get events linked to a specific entity.

**Parameters:**
- `linkType` (string): Type of link ('contact', 'company', or 'deal')
- `linkId` (string): UUID of the linked entity
- `options` (array): Additional options

**Example:**
```php
// Get events linked to a company
$events = $teamleader->events()->forLink('company', 'company-uuid');

// Get events linked to a deal
$events = $teamleader->events()->forLink('deal', 'deal-uuid');
```

## Available Filters

When using the `list()` method, you can apply the following filters:

- **ids**: Array of event UUIDs
- **user_id**: Filter events by user UUID
- **activity_type_id**: Filter by activity type UUID
- **ends_after**: Start of the period for which to return events (ISO 8601 format)
- **starts_before**: End of the period for which to return events (ISO 8601 format)
- **term**: Searches for a term in title or description
- **attendee**: Filter by attendee (object with `type` and `id`)
- **link**: Filter by linked entity (object with `id` and `type`)
- **task_id**: Filter events by task UUID
- **done**: Filter by completion status (boolean)

## Sorting

Events can be sorted by the following field:

- **starts_at**: Sort by event start date/time (default field)

**Example:**
```php
$events = $teamleader->events()->list([], [
    'sort' => [
        'field' => 'starts_at',
        'order' => 'asc'
    ]
]);
```

## Pagination

Events support pagination through the standard pagination options:

**Example:**
```php
$events = $teamleader->events()->list([], [
    'page_size' => 50,
    'page_number' => 2
]);
```

## Attendee Types

When adding attendees to events, use one of the following types:

- `user` - A Teamleader user
- `contact` - A contact from your CRM

## Link Types

When linking events to other entities, use one of the following types:

- `contact` - Link to a contact
- `company` - Link to a company
- `deal` - Link to a deal

## Complete Examples

### Creating a Full Event

```php
$event = $teamleader->events()->create([
    'title' => 'Quarterly Business Review',
    'description' => 'Review Q1 performance and plan for Q2',
    'activity_type_id' => 'b0a9ace5-fe82-4827-9d90-fc52f2c93050',
    'starts_at' => '2025-04-15T09:00:00+00:00',
    'ends_at' => '2025-04-15T11:00:00+00:00',
    'location' => 'Main Office, Conference Room 1',
    'work_type_id' => 'b37e2bc7-dea0-4fda-88e9-c092fb65667d',
    'attendees' => [
        ['type' => 'user', 'id' => 'user-uuid-1'],
        ['type' => 'user', 'id' => 'user-uuid-2'],
        ['type' => 'contact', 'id' => 'contact-uuid-1']
    ],
    'links' => [
        ['type' => 'company', 'id' => 'company-uuid'],
        ['type' => 'deal', 'id' => 'deal-uuid']
    ]
]);
```

### Getting This Week's Events

```php
$startOfWeek = now()->startOfWeek()->toIso8601String();
$endOfWeek = now()->endOfWeek()->toIso8601String();

$events = $teamleader->events()->betweenDates($startOfWeek, $endOfWeek);
```

### Finding Events for a User This Month

```php
$startOfMonth = now()->startOfMonth()->toIso8601String();
$endOfMonth = now()->endOfMonth()->toIso8601String();

$events = $teamleader->events()->forUser('user-uuid', [
    'filters' => [
        'ends_after' => $startOfMonth,
        'starts_before' => $endOfMonth
    ]
]);
```

### Searching and Filtering Events

```php
// Search for events with "meeting" in title or description
$events = $teamleader->events()->search('meeting');

// Get events for a specific activity type within a date range
$events = $teamleader->events()->list([
    'activity_type_id' => 'activity-type-uuid',
    'ends_after' => '2025-01-01T00:00:00+00:00',
    'starts_before' => '2025-03-31T23:59:59+00:00'
]);
```

## Response Structure

### Create Response

```json
{
    "data": {
        "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
        "type": "event"
    }
}
```

### Info Response

```json
{
    "data": {
        "id": "9a5a3984-abfc-40cd-a880-f97683c6a99c",
        "title": "Meeting with stakeholders",
        "description": "Discuss project timeline",
        "creator": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "user"
        },
        "task": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "task"
        },
        "activity_type": {
            "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
            "type": "activityType"
        },
        "starts_at": "2016-02-04T16:00:00+00:00",
        "ends_at": "2016-02-04T18:00:00+00:00",
        "location": "Office",
        "attendees": [
            {
                "type": "user",
                "id": "6ddd2666-65a0-497f-9f01-54c4343ec1a6"
            }
        ],
        "links": [
            {
                "id": "eab232c6-49b2-4b7e-a977-5e1148dad471",
                "type": "company"
            }
        ]
    }
}
```

## Notes

- All datetime fields must be in ISO 8601 format with timezone offset (e.g., `2025-02-04T16:00:00+00:00`)
- Canceling an event affects all attendees
- The `cancel()` method returns a 204 No Content response on success
- Events can be linked to contacts, companies, and deals
- Attendees can be users or contacts
- Each event must have an associated activity type

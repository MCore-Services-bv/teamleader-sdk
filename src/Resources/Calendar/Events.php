<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Events extends Resource
{
    use ValidatesWritePayload;

    /** Body fields events.create accepts */
    public const CREATE_FIELDS = [
        'title', 'description', 'activity_type_id', 'starts_at', 'ends_at',
        'location', 'work_type_id', 'attendees', 'links',
    ];

    /** Body fields events.update accepts, besides `id` — the activity type cannot change */
    public const UPDATE_FIELDS = [
        'title', 'description', 'starts_at', 'ends_at', 'location', 'work_type_id', 'attendees', 'links',
    ];

    public const REQUIRED_ON_CREATE = ['title', 'activity_type_id', 'starts_at', 'ends_at'];

    /** `attendees[].type` on create and update */
    public const ATTENDEE_TYPES = ['user', 'contact'];

    /** `filter.attendee.type` on events.list — contacts only */
    public const FILTER_ATTENDEE_TYPES = ['contact'];

    /** `links[].type` on create and update, and `filter.link.type` on list */
    public const LINK_TYPES = ['contact', 'company', 'deal'];

    protected string $description = 'Manage calendar events in Teamleader Focus';

    // Resource capabilities - Events support full CRUD operations
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true; // Via cancel() method

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false; // No includes mentioned in API docs

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of event UUIDs',
        'user_id' => 'Filter events by user UUID',
        'activity_type_id' => 'Filter by activity type UUID',
        'ends_after' => 'Start of the period for which to return events (ISO 8601 format)',
        'starts_before' => 'End of the period for which to return events (ISO 8601 format)',
        'term' => 'Searches for a term in title or description',
        'attendee' => 'Filter by attendee: [type => contact, id => uuid] (contacts only)',
        'link' => 'Filter by linked entity (object with id and type)',
        'task_id' => 'Filter events by task UUID',
        'done' => 'Filter by completion status (boolean)',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'starts_at' => 'Sort by event start date/time',
    ];

    // Valid attendee types
    protected array $attendeeTypes = self::ATTENDEE_TYPES;

    // Valid link types
    protected array $linkTypes = self::LINK_TYPES;

    // Usage examples specific to events
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all events',
            'code' => '$events = $teamleader->calendarEvents()->list();',
        ],
        'list_for_user' => [
            'description' => 'Get events for a specific user',
            'code' => '$events = $teamleader->calendarEvents()->forUser("user-uuid");',
        ],
        'list_by_date_range' => [
            'description' => 'Get events within a date range',
            'code' => '$events = $teamleader->calendarEvents()->list([
    "ends_after" => "2025-01-01T00:00:00+00:00",
    "starts_before" => "2025-12-31T23:59:59+00:00"
]);',
        ],
        'create_event' => [
            'description' => 'Create a new calendar event',
            'code' => '$event = $teamleader->calendarEvents()->create([
    "title" => "Meeting with stakeholders",
    "activity_type_id" => "activity-type-uuid",
    "starts_at" => "2025-02-04T16:00:00+00:00",
    "ends_at" => "2025-02-04T18:00:00+00:00",
    "attendees" => [
        ["type" => "user", "id" => "user-uuid"]
    ]
]);',
        ],
        'update_event' => [
            'description' => 'Update an existing event',
            'code' => '$event = $teamleader->calendarEvents()->update("event-uuid", [
    "title" => "Updated meeting title",
    "starts_at" => "2025-02-04T17:00:00+00:00"
]);',
        ],
        'cancel_event' => [
            'description' => 'Cancel an event (for all attendees)',
            'code' => '$result = $teamleader->calendarEvents()->cancel("event-uuid");',
        ],
        'search_events' => [
            'description' => 'Search events by term',
            'code' => '$events = $teamleader->calendarEvents()->search("coffee");',
        ],
    ];

    /**
     * Get the base path for the events resource
     */
    protected function getBasePath(): string
    {
        return 'events';
    }

    /**
     * List events with filtering, sorting, and pagination
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'sort', 'sort_order', 'filters']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'events.list does not support: '.implode(', ', $unknown)
                .'. Supported: page_size, page_number, sort, sort_order.'
            );
        }

        $params = [];

        // Build filter object
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        // Apply sorting
        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get event information
     */
    public function info($id, $includes = null): array
    {
        $this->assertIncludes($includes, [], 'events.info');

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Create a new calendar event
     *
     * Required fields: title, activity_type_id, starts_at, ends_at
     */
    public function create(array $data): array
    {
        $this->validateEventData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update an existing calendar event
     *
     * All fields except id are optional.
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateEventData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Cancel a calendar event (for all attendees)
     *
     * Note: This is the delete operation for events
     */
    public function cancel(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.cancel', [
            'id' => $id,
        ]);
    }

    /**
     * Override delete to use cancel
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->cancel($id);
    }

    /**
     * Get events for a specific user
     *
     * @param  string  $userId  User UUID
     * @param  array  $options  Additional options
     */
    public function forUser(string $userId, array $options = []): array
    {
        return $this->list(
            array_merge(['user_id' => $userId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get events for a specific activity type
     *
     * @param  string  $activityTypeId  Activity type UUID
     * @param  array  $options  Additional options
     */
    public function forActivityType(string $activityTypeId, array $options = []): array
    {
        return $this->list(
            array_merge(['activity_type_id' => $activityTypeId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Search events by term (searches title and description)
     *
     * @param  string  $term  Search term
     * @param  array  $options  Additional options
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $term], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get events within a date range
     *
     * @param  string  $startsAfter  ISO 8601 datetime
     * @param  string  $endsBefore  ISO 8601 datetime
     * @param  array  $options  Additional options
     */
    public function betweenDates(string $startsAfter, string $endsBefore, array $options = []): array
    {
        return $this->list(
            array_merge([
                'ends_after' => $startsAfter,
                'starts_before' => $endsBefore,
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get events by specific IDs
     *
     * @param  array  $ids  Array of event UUIDs
     * @param  array  $options  Additional options
     */
    public function byIds(array $ids, array $options = []): array
    {
        return $this->list(
            array_merge(['ids' => $ids], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get events for a specific attendee
     *
     * events.list filters on contact attendees only. Before v2.2.12 `user`
     * was accepted here too; use forUser() for a user's events.
     *
     * @param  string  $attendeeType  Type of attendee: contact
     * @param  string  $attendeeId  UUID of the attendee
     * @param  array  $options  Additional options
     */
    public function forAttendee(string $attendeeType, string $attendeeId, array $options = []): array
    {
        $this->assertEnum($attendeeType, self::FILTER_ATTENDEE_TYPES, 'filter.attendee.type', 'events.list');

        return $this->list(
            array_merge([
                'attendee' => [
                    'type' => $attendeeType,
                    'id' => $attendeeId,
                ],
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get events linked to a specific entity
     *
     * @param  string  $linkType  Type of link (contact, company, deal)
     * @param  string  $linkId  UUID of the linked entity
     * @param  array  $options  Additional options
     */
    public function forLink(string $linkType, string $linkId, array $options = []): array
    {
        $this->validateLinkType($linkType);

        return $this->list(
            array_merge([
                'link' => [
                    'id' => $linkId,
                    'type' => $linkType,
                ],
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Validate event data for create/update operations
     *
     * @param  string  $operation  'create' or 'update'
     *
     * @throws InvalidArgumentException
     */
    protected function validateEventData(array $data, string $operation = 'create'): void
    {
        $endpoint = 'events.'.$operation;

        if ($operation === 'create') {
            foreach (self::REQUIRED_ON_CREATE as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException("{$field} is required for creating an event");
                }
            }

            $this->rejectUnknownFields($data, self::CREATE_FIELDS, $endpoint);
        } else {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating an event');
            }

            $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        }

        foreach (['starts_at', 'ends_at'] as $field) {
            if (isset($data[$field])) {
                $this->validateDateTimeFormat($data[$field], $field);
            }
        }

        if (isset($data['starts_at'], $data['ends_at']) && strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
            throw new InvalidArgumentException('ends_at must be after starts_at');
        }

        $this->validateTypedList($data, 'attendees', self::ATTENDEE_TYPES, $endpoint);
        $this->validateTypedList($data, 'links', self::LINK_TYPES, $endpoint);
    }

    /**
     * A list of [type, id] entries with the type from $types
     *
     * @param  list<string>  $types
     *
     * @throws InvalidArgumentException
     */
    private function validateTypedList(array $data, string $field, array $types, string $endpoint): void
    {
        if (! isset($data[$field])) {
            return;
        }

        if (! is_array($data[$field]) || ! array_is_list($data[$field])) {
            throw new InvalidArgumentException("{$field} must be a list of ['type' => ..., 'id' => uuid]");
        }

        foreach ($data[$field] as $index => $entry) {
            if (! is_array($entry) || empty($entry['id']) || ! isset($entry['type'])) {
                throw new InvalidArgumentException("{$field}[{$index}] needs both a type and an id");
            }

            $this->assertEnum($entry['type'], $types, "{$field}[{$index}].type", $endpoint);
        }
    }

    /**
     * Validate datetime format (ISO 8601)
     *
     * @throws InvalidArgumentException
     */
    protected function validateDateTimeFormat(string $datetime, string $fieldName): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';

        if (! preg_match($pattern, $datetime) || strtotime($datetime) === false) {
            throw new InvalidArgumentException(
                "{$fieldName} must be in ISO 8601 format (e.g., 2025-02-04T16:00:00+00:00)"
            );
        }
    }

    /**
     * Validate attendee type
     *
     * @throws InvalidArgumentException
     */
    protected function validateAttendeeType(string $type): void
    {
        if (! in_array($type, $this->attendeeTypes)) {
            throw new InvalidArgumentException(
                'Invalid attendee type. Must be one of: '.implode(', ', $this->attendeeTypes)
            );
        }
    }

    /**
     * Validate link type
     *
     * @throws InvalidArgumentException
     */
    protected function validateLinkType(string $type): void
    {
        if (! in_array($type, $this->linkTypes)) {
            throw new InvalidArgumentException(
                'Invalid link type. Must be one of: '.implode(', ', $this->linkTypes)
            );
        }
    }

    /**
     * Build the filter object for events.list
     *
     * Until v2.2.12 every key was passed through unchecked, so a mistyped key
     * returned every event.
     *
     * @throws InvalidArgumentException On an unknown key or value
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'events.list');

        if (isset($filters['done']) && ! is_bool($filters['done'])) {
            throw new InvalidArgumentException('done must be true or false.');
        }

        foreach (['attendee' => self::FILTER_ATTENDEE_TYPES, 'link' => self::LINK_TYPES] as $key => $types) {
            if (! isset($filters[$key])) {
                continue;
            }

            if (! is_array($filters[$key]) || empty($filters[$key]['id']) || ! isset($filters[$key]['type'])) {
                throw new InvalidArgumentException("The {$key} filter must be ['type' => ..., 'id' => uuid].");
            }

            $this->assertEnum($filters[$key]['type'], $types, "filter.{$key}.type", 'events.list');
        }

        if (isset($filters['ids']) && ! is_array($filters['ids'])) {
            $filters['ids'] = [$filters['ids']];
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Build the sort array for events.list (starts_at only)
     *
     * Before v2.2.12 a field name was a TypeError, and sort_order was ignored.
     *
     * @param  array|string  $sort
     *
     * @throws InvalidArgumentException On an unknown field or order
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        return $this->normaliseSort($sort, $order);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created event ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created event',
                    'data.type' => 'Resource type (always "event")',
                ],
            ],
            'info' => [
                'description' => 'Complete event information',
                'fields' => [
                    'data.id' => 'Event UUID',
                    'data.title' => 'Event title',
                    'data.description' => 'Event description (nullable)',
                    'data.creator' => 'Creator object with id and type',
                    'data.task' => 'Associated task object (nullable)',
                    'data.activity_type' => 'Activity type object with id and type',
                    'data.starts_at' => 'Event start datetime (ISO 8601)',
                    'data.ends_at' => 'Event end datetime (ISO 8601)',
                    'data.location' => 'Event location (nullable)',
                    'data.attendees' => 'Array of attendee objects with type and id',
                    'data.links' => 'Array of linked entities with id and type',
                ],
            ],
            'list' => [
                'description' => 'Array of events',
                'fields' => [
                    'data' => 'Array of event objects with structure similar to info endpoint',
                ],
            ],
        ];
    }
}

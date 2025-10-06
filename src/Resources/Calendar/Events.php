<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Events extends Resource
{
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
        'attendee' => 'Filter by attendee (object with type and id)',
        'link' => 'Filter by linked entity (object with id and type)',
        'task_id' => 'Filter events by task UUID',
        'done' => 'Filter by completion status (boolean)',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'starts_at' => 'Sort by event start date/time',
    ];

    // Valid attendee types
    protected array $attendeeTypes = [
        'user',
        'contact',
    ];

    // Valid link types
    protected array $linkTypes = [
        'contact',
        'company',
        'deal',
    ];

    // Usage examples specific to events
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all events',
            'code' => '$events = $teamleader->events()->list();'
        ],
        'list_for_user' => [
            'description' => 'Get events for a specific user',
            'code' => '$events = $teamleader->events()->forUser("user-uuid");'
        ],
        'list_by_date_range' => [
            'description' => 'Get events within a date range',
            'code' => '$events = $teamleader->events()->list([
    "ends_after" => "2025-01-01T00:00:00+00:00",
    "starts_before" => "2025-12-31T23:59:59+00:00"
]);'
        ],
        'create_event' => [
            'description' => 'Create a new calendar event',
            'code' => '$event = $teamleader->events()->create([
    "title" => "Meeting with stakeholders",
    "activity_type_id" => "activity-type-uuid",
    "starts_at" => "2025-02-04T16:00:00+00:00",
    "ends_at" => "2025-02-04T18:00:00+00:00",
    "attendees" => [
        ["type" => "user", "id" => "user-uuid"]
    ]
]);'
        ],
        'update_event' => [
            'description' => 'Update an existing event',
            'code' => '$event = $teamleader->events()->update("event-uuid", [
    "title" => "Updated meeting title",
    "starts_at" => "2025-02-04T17:00:00+00:00"
]);'
        ],
        'cancel_event' => [
            'description' => 'Cancel an event (for all attendees)',
            'code' => '$result = $teamleader->events()->cancel("event-uuid");'
        ],
        'search_events' => [
            'description' => 'Search events by term',
            'code' => '$events = $teamleader->events()->search("coffee");'
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
        $params = [];

        // Build filter object
        if (!empty($filters)) {
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
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get event information
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.info', [
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
        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
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
        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Cancel a calendar event (for all attendees)
     *
     * Note: This is the delete operation for events
     */
    public function cancel(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.cancel', [
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
     * @param string $userId User UUID
     * @param array $options Additional options
     * @return array
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
     * @param string $activityTypeId Activity type UUID
     * @param array $options Additional options
     * @return array
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
     * @param string $term Search term
     * @param array $options Additional options
     * @return array
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
     * @param string $startsAfter ISO 8601 datetime
     * @param string $endsBefore ISO 8601 datetime
     * @param array $options Additional options
     * @return array
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
     * @param array $ids Array of event UUIDs
     * @param array $options Additional options
     * @return array
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
     * @param string $attendeeType Type of attendee (user, contact)
     * @param string $attendeeId UUID of the attendee
     * @param array $options Additional options
     * @return array
     */
    public function forAttendee(string $attendeeType, string $attendeeId, array $options = []): array
    {
        $this->validateAttendeeType($attendeeType);

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
     * @param string $linkType Type of link (contact, company, deal)
     * @param string $linkId UUID of the linked entity
     * @param array $options Additional options
     * @return array
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
     * @param array $data
     * @param string $operation 'create' or 'update'
     * @throws InvalidArgumentException
     */
    protected function validateEventData(array $data, string $operation = 'create'): void
    {
        // Required fields for create
        if ($operation === 'create') {
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required for creating an event');
            }
            if (empty($data['activity_type_id'])) {
                throw new InvalidArgumentException('activity_type_id is required for creating an event');
            }
            if (empty($data['starts_at'])) {
                throw new InvalidArgumentException('starts_at is required for creating an event');
            }
            if (empty($data['ends_at'])) {
                throw new InvalidArgumentException('ends_at is required for creating an event');
            }

            // Validate datetime format
            $this->validateDateTimeFormat($data['starts_at'], 'starts_at');
            $this->validateDateTimeFormat($data['ends_at'], 'ends_at');
        }

        // Required field for update
        if ($operation === 'update') {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating an event');
            }

            // Validate datetime format if provided
            if (isset($data['starts_at'])) {
                $this->validateDateTimeFormat($data['starts_at'], 'starts_at');
            }
            if (isset($data['ends_at'])) {
                $this->validateDateTimeFormat($data['ends_at'], 'ends_at');
            }
        }

        // Validate attendees structure if provided
        if (isset($data['attendees']) && is_array($data['attendees'])) {
            foreach ($data['attendees'] as $attendee) {
                if (!isset($attendee['type']) || !in_array($attendee['type'], $this->attendeeTypes)) {
                    throw new InvalidArgumentException(
                        'Invalid attendee type. Must be one of: ' . implode(', ', $this->attendeeTypes)
                    );
                }
                if (!isset($attendee['id']) || empty($attendee['id'])) {
                    throw new InvalidArgumentException('Attendee id is required');
                }
            }
        }

        // Validate links structure if provided
        if (isset($data['links']) && is_array($data['links'])) {
            foreach ($data['links'] as $link) {
                if (!isset($link['type']) || !in_array($link['type'], $this->linkTypes)) {
                    throw new InvalidArgumentException(
                        'Invalid link type. Must be one of: ' . implode(', ', $this->linkTypes)
                    );
                }
                if (!isset($link['id']) || empty($link['id'])) {
                    throw new InvalidArgumentException('Link id is required');
                }
            }
        }
    }

    /**
     * Validate datetime format (ISO 8601)
     *
     * @param string $datetime
     * @param string $fieldName
     * @throws InvalidArgumentException
     */
    protected function validateDateTimeFormat(string $datetime, string $fieldName): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';
        if (!preg_match($pattern, $datetime)) {
            throw new InvalidArgumentException(
                "{$fieldName} must be in ISO 8601 format (e.g., 2025-02-04T16:00:00+00:00)"
            );
        }
    }

    /**
     * Validate attendee type
     *
     * @param string $type
     * @throws InvalidArgumentException
     */
    protected function validateAttendeeType(string $type): void
    {
        if (!in_array($type, $this->attendeeTypes)) {
            throw new InvalidArgumentException(
                'Invalid attendee type. Must be one of: ' . implode(', ', $this->attendeeTypes)
            );
        }
    }

    /**
     * Validate link type
     *
     * @param string $type
     * @throws InvalidArgumentException
     */
    protected function validateLinkType(string $type): void
    {
        if (!in_array($type, $this->linkTypes)) {
            throw new InvalidArgumentException(
                'Invalid link type. Must be one of: ' . implode(', ', $this->linkTypes)
            );
        }
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters
     * @return array
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        foreach ($filters as $key => $value) {
            // Pass through complex filter structures as-is
            if (in_array($key, ['attendee', 'link'])) {
                $apiFilters[$key] = $value;
            } elseif ($key === 'ids' && is_array($value)) {
                $apiFilters[$key] = $value;
            } else {
                $apiFilters[$key] = $value;
            }
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     *
     * @param array $sort
     * @return array
     */
    protected function buildSort(array $sort): array
    {
        if (isset($sort['field'])) {
            // Single sort field
            return [[
                'field' => $sort['field'],
                'order' => $sort['order'] ?? 'asc',
            ]];
        }

        // Multiple sort fields
        return array_map(function ($item) {
            return [
                'field' => $item['field'],
                'order' => $item['order'] ?? 'asc',
            ];
        }, $sort);
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

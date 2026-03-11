<?php

namespace McoreServices\TeamleaderSDK\Resources\Planning;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Reservations extends Resource
{
    protected string $description = 'Manage planning reservations in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'plannable_item_ids' => 'Filter by array of plannable item UUIDs',
        'start_date' => 'Filter reservations from this date (YYYY-MM-DD)',
        'end_date' => 'Filter reservations up to this date (YYYY-MM-DD)',
        'assignees' => 'Filter by assignees (array of objects with type and id; pass null for unassigned)',
        'sources' => 'Filter by sources (array of objects with id and type)',
        'source_types' => 'Filter by source types (array of SourceType strings)',
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'team',
        'user',
    ];

    // Valid source types
    protected array $sourceTypes = [
        'call',
        'closingDay',
        'dayOffType',
        'externalEvent',
        'meeting',
        'task',
    ];

    // Valid duration units
    protected array $durationUnits = [
        'minutes',
    ];

    // Usage examples specific to reservations
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all reservations',
            'code' => '$reservations = $teamleader->reservations()->list();',
        ],
        'filter_by_date_range' => [
            'description' => 'Get reservations within a date range',
            'code' => '$reservations = $teamleader->reservations()->list([
    \'start_date\' => \'2024-01-01\',
    \'end_date\'   => \'2024-01-31\',
]);',
        ],
        'filter_by_plannable_items' => [
            'description' => 'Get reservations for specific plannable items',
            'code' => '$reservations = $teamleader->reservations()->list([
    \'plannable_item_ids\' => [
        \'46156648-87c6-478d-8aa7-1dc3a00dacab\',
    ],
]);',
        ],
        'filter_by_assignee' => [
            'description' => 'Get reservations for a specific user',
            'code' => '$reservations = $teamleader->reservations()->list([
    \'assignees\' => [
        [\'type\' => \'user\', \'id\' => \'66abace2-62af-0836-a927-fe3f44b9b47b\'],
    ],
]);',
        ],
        'filter_unassigned' => [
            'description' => 'Get unassigned reservations (pass null in assignees)',
            'code' => '$reservations = $teamleader->reservations()->list([
    \'assignees\' => [null],
]);',
        ],
        'create_reservation' => [
            'description' => 'Create a new reservation',
            'code' => '$reservation = $teamleader->reservations()->create([
    \'plannable_item_id\' => \'46156648-87c6-478d-8aa7-1dc3a00dacab\',
    \'date\'              => \'2024-01-12\',
    \'duration\'          => [
        \'value\' => 60,
        \'unit\'  => \'minutes\',
    ],
    \'assignee\' => [
        \'type\' => \'user\',
        \'id\'   => \'66abace2-62af-0836-a927-fe3f44b9b47b\',
    ],
]);',
        ],
        'update_reservation' => [
            'description' => 'Update an existing reservation',
            'code' => '$result = $teamleader->reservations()->update(\'01878019-c72c-70dc-b097-7e519c775e35\', [
    \'date\'     => \'2024-01-15\',
    \'duration\' => [
        \'value\' => 120,
        \'unit\'  => \'minutes\',
    ],
]);',
        ],
        'delete_reservation' => [
            'description' => 'Delete a reservation',
            'code' => '$teamleader->reservations()->delete(\'01878019-c72c-70dc-b097-7e519c775e35\');',
        ],
    ];

    /**
     * Get the base path for the reservations resource
     */
    protected function getBasePath(): string
    {
        return 'reservations';
    }

    /**
     * List reservations with optional filters and pagination
     *
     * @param  array  $filters  Filters to apply (plannable_item_ids, start_date, end_date, assignees, sources, source_types)
     * @param  array  $options  Pagination options (page_size, page_number)
     *
     * @throws InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
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

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Create a new reservation
     *
     * @param  array  $data  Reservation data
     *                       - plannable_item_id (string, required): UUID of the plannable item
     *                       - date (string, required): Date in YYYY-MM-DD format
     *                       - duration (array, required): Object with 'value' (number) and 'unit' (string: 'minutes')
     *                       - assignee (array, required): Object with 'type' ('user' or 'team') and 'id' (UUID)
     *
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update an existing reservation
     *
     * @param  string  $id  Reservation UUID
     * @param  array  $data  Data to update
     *                       - date (string, optional): Date in YYYY-MM-DD format
     *                       - duration (array, optional): Object with 'value' (number) and 'unit' (string: 'minutes')
     *                       - assignee (array, optional): Object with 'type' ('user' or 'team') and 'id' (UUID)
     *
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Reservation ID is required');
        }

        $data['id'] = $id;

        $this->validateUpdateData($data);

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a reservation
     *
     * @param  string  $id  Reservation UUID
     * @param  mixed  ...$additionalParams  Unused — signature-compatible with parent
     *
     * @throws InvalidArgumentException
     */
    public function delete($id, ...$additionalParams): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Reservation ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
        ]);
    }

    /**
     * Convenience method: get reservations for a specific user
     *
     * @param  string  $userId  User UUID
     * @param  array  $options  Additional filters or pagination
     */
    public function forUser(string $userId, array $options = []): array
    {
        $filters = array_merge(
            ['assignees' => [['type' => 'user', 'id' => $userId]]],
            $options['filters'] ?? []
        );

        return $this->list($filters, $options);
    }

    /**
     * Convenience method: get reservations for a specific team
     *
     * @param  string  $teamId  Team UUID
     * @param  array  $options  Additional filters or pagination
     */
    public function forTeam(string $teamId, array $options = []): array
    {
        $filters = array_merge(
            ['assignees' => [['type' => 'team', 'id' => $teamId]]],
            $options['filters'] ?? []
        );

        return $this->list($filters, $options);
    }

    /**
     * Convenience method: get reservations for a date range
     *
     * @param  string  $startDate  Start date in YYYY-MM-DD format
     * @param  string  $endDate  End date in YYYY-MM-DD format
     * @param  array  $options  Additional filters or pagination
     */
    public function forDateRange(string $startDate, string $endDate, array $options = []): array
    {
        $this->validateDateFormat($startDate, 'start_date');
        $this->validateDateFormat($endDate, 'end_date');

        $filters = array_merge(
            ['start_date' => $startDate, 'end_date' => $endDate],
            $options['filters'] ?? []
        );

        return $this->list($filters, $options);
    }

    /**
     * Convenience method: get unassigned reservations
     *
     * @param  array  $options  Additional filters or pagination
     */
    public function unassigned(array $options = []): array
    {
        $filters = array_merge(
            ['assignees' => [null]],
            $options['filters'] ?? []
        );

        return $this->list($filters, $options);
    }

    /**
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // plannable_item_ids — array of UUIDs
        if (isset($filters['plannable_item_ids']) && is_array($filters['plannable_item_ids'])) {
            $apiFilters['plannable_item_ids'] = $filters['plannable_item_ids'];
        }

        // start_date
        if (isset($filters['start_date'])) {
            $this->validateDateFormat($filters['start_date'], 'start_date');
            $apiFilters['start_date'] = $filters['start_date'];
        }

        // end_date
        if (isset($filters['end_date'])) {
            $this->validateDateFormat($filters['end_date'], 'end_date');
            $apiFilters['end_date'] = $filters['end_date'];
        }

        // assignees — array of objects or null values
        if (isset($filters['assignees']) && is_array($filters['assignees'])) {
            $apiFilters['assignees'] = $filters['assignees'];
        }

        // sources — array of objects with id and type
        if (isset($filters['sources']) && is_array($filters['sources'])) {
            $apiFilters['sources'] = $filters['sources'];
        }

        // source_types — array of SourceType strings
        if (isset($filters['source_types']) && is_array($filters['source_types'])) {
            foreach ($filters['source_types'] as $type) {
                if (! in_array($type, $this->sourceTypes)) {
                    throw new InvalidArgumentException(
                        "Invalid source_type: {$type}. Must be one of: ".implode(', ', $this->sourceTypes)
                    );
                }
            }
            $apiFilters['source_types'] = $filters['source_types'];
        }

        return $apiFilters;
    }

    /**
     * Validate data for reservation creation
     *
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): void
    {
        if (empty($data['plannable_item_id'])) {
            throw new InvalidArgumentException('plannable_item_id is required');
        }

        if (empty($data['date'])) {
            throw new InvalidArgumentException('date is required');
        }
        $this->validateDateFormat($data['date'], 'date');

        if (empty($data['duration']) || ! is_array($data['duration'])) {
            throw new InvalidArgumentException('duration is required and must be an object');
        }
        $this->validateDuration($data['duration']);

        if (empty($data['assignee']) || ! is_array($data['assignee'])) {
            throw new InvalidArgumentException('assignee is required and must be an object');
        }
        $this->validateAssignee($data['assignee']);
    }

    /**
     * Validate data for reservation update
     *
     * @throws InvalidArgumentException
     */
    protected function validateUpdateData(array $data): void
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('id is required for update');
        }

        if (isset($data['date'])) {
            $this->validateDateFormat($data['date'], 'date');
        }

        if (isset($data['duration'])) {
            if (! is_array($data['duration'])) {
                throw new InvalidArgumentException('duration must be an object');
            }
            $this->validateDuration($data['duration']);
        }

        if (isset($data['assignee'])) {
            if (! is_array($data['assignee'])) {
                throw new InvalidArgumentException('assignee must be an object');
            }
            $this->validateAssignee($data['assignee']);
        }
    }

    /**
     * Validate a duration object
     *
     * @throws InvalidArgumentException
     */
    protected function validateDuration(array $duration): void
    {
        if (! isset($duration['unit'])) {
            throw new InvalidArgumentException('duration.unit is required');
        }

        if (! in_array($duration['unit'], $this->durationUnits)) {
            throw new InvalidArgumentException(
                "Invalid duration unit: {$duration['unit']}. Must be one of: ".implode(', ', $this->durationUnits)
            );
        }

        if (! isset($duration['value']) || ! is_numeric($duration['value'])) {
            throw new InvalidArgumentException('duration.value is required and must be a number');
        }
    }

    /**
     * Validate an assignee object
     *
     * @throws InvalidArgumentException
     */
    protected function validateAssignee(array $assignee): void
    {
        if (empty($assignee['type'])) {
            throw new InvalidArgumentException('assignee.type is required');
        }

        if (! in_array($assignee['type'], $this->assigneeTypes)) {
            throw new InvalidArgumentException(
                "Invalid assignee type: {$assignee['type']}. Must be one of: ".implode(', ', $this->assigneeTypes)
            );
        }

        if (empty($assignee['id'])) {
            throw new InvalidArgumentException('assignee.id is required');
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @throws InvalidArgumentException
     */
    protected function validateDateFormat(string $date, string $fieldName): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException(
                "{$fieldName} must be in YYYY-MM-DD format (e.g., 2024-01-12)"
            );
        }
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of reservations with pagination (HTTP 200)',
                'fields' => [
                    'data' => 'Array of reservation objects',
                    'data[].id' => 'Reservation UUID',
                    'data[].plannable_item' => 'Plannable item reference {id, type: plannableItem}',
                    'data[].date' => 'Reservation date (YYYY-MM-DD)',
                    'data[].duration' => 'Duration object {unit: minutes, value: number}',
                    'data[].assignee' => 'Assignee object {type: user|team, id: UUID}',
                    'data[].origin' => 'Origin reference {id, type} (nullable)',
                ],
            ],
            'create' => [
                'description' => 'Response contains the created reservation ID and type (HTTP 201)',
                'fields' => [
                    'data.id' => 'UUID of the created reservation',
                    'data.type' => 'Resource type',
                ],
            ],
            'update' => [
                'description' => 'Empty response with 204 status on success',
            ],
            'delete' => [
                'description' => 'Empty response with 204 status on success',
            ],
        ];
    }
}

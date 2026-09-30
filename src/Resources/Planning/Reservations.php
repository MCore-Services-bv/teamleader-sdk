<?php

namespace McoreServices\TeamleaderSDK\Resources\Planning;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Planning\Concerns\ChecksPlanningFilters;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Reservations extends Resource
{
    use ChecksPlanningFilters;
    use ValidatesWritePayload;

    /** Body fields reservations.create accepts */
    public const CREATE_FIELDS = ['plannable_item_id', 'date', 'duration', 'assignee'];

    /** Body fields reservations.update accepts, besides `id` — the plannable item cannot change */
    public const UPDATE_FIELDS = ['date', 'duration', 'assignee'];

    /** `source_types[]` and `sources[].type` on reservations.list */
    public const SOURCE_TYPES = ['call', 'closingDay', 'dayOffType', 'externalEvent', 'meeting', 'task'];

    /** `duration.unit` */
    public const DURATION_UNITS = ['minutes'];

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
        'project_ids' => 'Filter by array of project UUIDs',
        'work_type_ids' => 'Filter by array of work type UUIDs',
        'term' => 'Search term',
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
    protected array $sourceTypes = self::SOURCE_TYPES;

    // Valid duration units
    protected array $durationUnits = self::DURATION_UNITS;

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
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'filters']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'reservations.list does not support: '.implode(', ', $unknown).'. Supported: page_size, page_number.'
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
     * Build the filter object for reservations.list
     *
     * Until v2.2.16 project_ids, work_type_ids and term were not offered,
     * unknown keys were dropped without a word, and assignees and sources were
     * passed through unchecked.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $endpoint = 'reservations.list';

        $this->rejectUnknownFilters($filters, $endpoint);

        foreach (['plannable_item_ids', 'project_ids', 'work_type_ids'] as $key) {
            if (isset($filters[$key]) && ! is_array($filters[$key])) {
                $filters[$key] = [$filters[$key]];
            }
        }

        foreach (['start_date', 'end_date'] as $key) {
            if (isset($filters[$key])) {
                $this->checkedDate($filters[$key], $key);
            }
        }

        if (isset($filters['assignees'])) {
            $filters['assignees'] = $this->checkedAssignees($filters['assignees'], $endpoint, true);
        }

        if (isset($filters['source_types'])) {
            $filters['source_types'] = $this->checkedEnumList($filters['source_types'], self::SOURCE_TYPES, 'source_types', $endpoint);
        }

        if (isset($filters['sources'])) {
            if (! is_array($filters['sources']) || ! array_is_list($filters['sources'])) {
                throw new InvalidArgumentException("sources on {$endpoint} must be a list of ['type' => ..., 'id' => uuid].");
            }

            foreach ($filters['sources'] as $index => $source) {
                if (! is_array($source) || empty($source['id']) || ! isset($source['type'])) {
                    throw new InvalidArgumentException("sources[{$index}] on {$endpoint} needs both a type and an id.");
                }

                $this->assertEnum($source['type'], self::SOURCE_TYPES, "filter.sources[{$index}].type", $endpoint);
            }
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Validate data for reservation creation
     *
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): void
    {
        $this->rejectUnknownFields($data, self::CREATE_FIELDS, 'reservations.create');
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

        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], 'reservations.update');

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
        $this->checkedDate($date, $fieldName);
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

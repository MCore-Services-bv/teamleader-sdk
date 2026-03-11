<?php

namespace McoreServices\TeamleaderSDK\Resources\Planning;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class PlannableItems extends Resource
{
    protected string $description = 'Retrieve plannable items from Teamleader Focus';

    // Resource capabilities — read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Filter by array of plannable item UUIDs',
        'status' => 'Filter by status: active, deactivated',
        'term' => 'Search term (matches item title/name)',
        'start_date' => 'Filter items from this date (YYYY-MM-DD)',
        'end_date' => 'Filter items up to this date (YYYY-MM-DD)',
        'project_ids' => 'Filter by array of project UUIDs',
        'assignees' => 'Filter by assignees (array of objects with type and id; pass null for unassigned)',
        'work_type_ids' => 'Filter by array of work type UUIDs',
        'completion_statuses' => 'Filter by completion status: to_do, done',
        'planned_time_statuses' => 'Filter by planned time status: unplanned, partially_planned, fully_planned, overbooked',
    ];

    // Valid status values
    protected array $statusValues = [
        'active',
        'deactivated',
    ];

    // Valid completion status values
    protected array $completionStatuses = [
        'to_do',
        'done',
    ];

    // Valid planned time status values
    protected array $plannedTimeStatuses = [
        'unplanned',
        'partially_planned',
        'fully_planned',
        'overbooked',
    ];

    // Valid sort fields
    protected array $availableSortFields = [
        'id' => 'Sort by plannable item ID (default)',
        'end_date' => 'Sort by end date',
        'total_duration' => 'Sort by total duration',
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'team',
        'user',
    ];

    // Usage examples specific to plannable items
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all plannable items',
            'code' => '$items = $teamleader->plannableItems()->list();',
        ],
        'filter_by_status' => [
            'description' => 'Get only active plannable items',
            'code' => '$items = $teamleader->plannableItems()->list([
    \'status\' => [\'active\'],
]);',
        ],
        'filter_by_project' => [
            'description' => 'Get plannable items for specific projects',
            'code' => '$items = $teamleader->plannableItems()->list([
    \'project_ids\' => [\'project-uuid-1\', \'project-uuid-2\'],
]);',
        ],
        'filter_unplanned' => [
            'description' => 'Get items that have no planned time yet',
            'code' => '$items = $teamleader->plannableItems()->list([
    \'planned_time_statuses\' => [\'unplanned\'],
]);',
        ],
        'filter_by_assignee' => [
            'description' => 'Get plannable items assigned to a specific user',
            'code' => '$items = $teamleader->plannableItems()->list([
    \'assignees\' => [
        [\'type\' => \'user\', \'id\' => \'66abace2-62af-0836-a927-fe3f44b9b47b\'],
    ],
]);',
        ],
        'sort_by_end_date' => [
            'description' => 'Get plannable items sorted by end date ascending',
            'code' => '$items = $teamleader->plannableItems()->list([], [
    \'sort\' => [[\'field\' => \'end_date\', \'order\' => \'asc\']],
]);',
        ],
        'info_by_id' => [
            'description' => 'Get a single plannable item by its ID',
            'code' => '$item = $teamleader->plannableItems()->info(\'018d79a1-2b99-7fbd-b323-500b01305371\');',
        ],
        'info_by_source' => [
            'description' => 'Get a plannable item by its source (when the plannable item ID is unknown)',
            'code' => '$item = $teamleader->plannableItems()->infoBySource(\'task\', \'eab232c6-49b2-4b7e-a977-5e1148dad471\');',
        ],
    ];

    /**
     * Get the base path for the plannable items resource
     */
    protected function getBasePath(): string
    {
        return 'plannableItems';
    }

    /**
     * List plannable items with optional filters, sorting, and pagination
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Pagination and sorting options
     *                          - page_size (int): Results per page (default: 20)
     *                          - page_number (int): Page number (default: 1)
     *                          - sort (array): Array of sort objects with 'field' and 'order' keys
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

        // Apply sorting
        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get a single plannable item by its ID or by source
     *
     * Either `id` or `source` must be provided. If the plannable item ID is
     * unknown, use `source` with the underlying entity's type and ID.
     *
     * @param  mixed  $id  Plannable item UUID, or null when looking up by source
     * @param  mixed  $includes  Unused — retained for base class signature compatibility
     *
     * @throws InvalidArgumentException
     */
    public function info($id, $includes = null): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException(
                'Plannable item ID is required. To look up by source, use infoBySource() instead.'
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Get a single plannable item by its source entity type and ID
     *
     * Use this when the plannable item's own ID is unknown but you have the
     * underlying source entity (e.g. a task UUID).
     *
     * @param  string  $sourceType  Type of the source entity (e.g. 'task')
     * @param  string  $sourceId  UUID of the source entity
     *
     * @throws InvalidArgumentException
     */
    public function infoBySource(string $sourceType, string $sourceId): array
    {
        if (empty($sourceType)) {
            throw new InvalidArgumentException('source.type is required');
        }

        if (empty($sourceId)) {
            throw new InvalidArgumentException('source.id is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.info', [
            'source' => [
                'type' => $sourceType,
                'id' => $sourceId,
            ],
        ]);
    }

    /**
     * Convenience method: get active plannable items
     *
     * @param  array  $filters  Additional filters
     * @param  array  $options  Pagination and sorting options
     */
    public function active(array $filters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['active']], $filters),
            $options
        );
    }

    /**
     * Convenience method: get unplanned plannable items
     *
     * @param  array  $filters  Additional filters
     * @param  array  $options  Pagination and sorting options
     */
    public function unplanned(array $filters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['planned_time_statuses' => ['unplanned']], $filters),
            $options
        );
    }

    /**
     * Convenience method: get overbooked plannable items
     *
     * @param  array  $filters  Additional filters
     * @param  array  $options  Pagination and sorting options
     */
    public function overbooked(array $filters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['planned_time_statuses' => ['overbooked']], $filters),
            $options
        );
    }

    /**
     * Convenience method: get plannable items for a specific project
     *
     * @param  string  $projectId  Project UUID
     * @param  array  $filters  Additional filters
     * @param  array  $options  Pagination and sorting options
     */
    public function forProject(string $projectId, array $filters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['project_ids' => [$projectId]], $filters),
            $options
        );
    }

    /**
     * Convenience method: get plannable items assigned to a specific user
     *
     * @param  string  $userId  User UUID
     * @param  array  $filters  Additional filters
     * @param  array  $options  Pagination and sorting options
     */
    public function forUser(string $userId, array $filters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['assignees' => [['type' => 'user', 'id' => $userId]]], $filters),
            $options
        );
    }

    /**
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // ids — array of UUIDs
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // status — array of status strings
        if (isset($filters['status'])) {
            $statuses = (array) $filters['status'];
            foreach ($statuses as $status) {
                if (! in_array($status, $this->statusValues)) {
                    throw new InvalidArgumentException(
                        "Invalid status: {$status}. Must be one of: ".implode(', ', $this->statusValues)
                    );
                }
            }
            $apiFilters['status'] = $statuses;
        }

        // term — search string
        if (isset($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
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

        // project_ids
        if (isset($filters['project_ids']) && is_array($filters['project_ids'])) {
            $apiFilters['project_ids'] = $filters['project_ids'];
        }

        // assignees — array of objects or null values
        if (isset($filters['assignees']) && is_array($filters['assignees'])) {
            $apiFilters['assignees'] = $filters['assignees'];
        }

        // work_type_ids
        if (isset($filters['work_type_ids']) && is_array($filters['work_type_ids'])) {
            $apiFilters['work_type_ids'] = $filters['work_type_ids'];
        }

        // completion_statuses
        if (isset($filters['completion_statuses']) && is_array($filters['completion_statuses'])) {
            foreach ($filters['completion_statuses'] as $status) {
                if (! in_array($status, $this->completionStatuses)) {
                    throw new InvalidArgumentException(
                        "Invalid completion_status: {$status}. Must be one of: ".implode(', ', $this->completionStatuses)
                    );
                }
            }
            $apiFilters['completion_statuses'] = $filters['completion_statuses'];
        }

        // planned_time_statuses
        if (isset($filters['planned_time_statuses']) && is_array($filters['planned_time_statuses'])) {
            foreach ($filters['planned_time_statuses'] as $status) {
                if (! in_array($status, $this->plannedTimeStatuses)) {
                    throw new InvalidArgumentException(
                        "Invalid planned_time_status: {$status}. Must be one of: ".implode(', ', $this->plannedTimeStatuses)
                    );
                }
            }
            $apiFilters['planned_time_statuses'] = $filters['planned_time_statuses'];
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     *
     * @param  mixed  $sort  Array of sort objects, or a field name string
     * @param  string  $order  Default order when $sort is a simple string
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        // Simple string shorthand: 'end_date' or 'end_date:desc'
        if (is_string($sort)) {
            [$field, $dir] = array_pad(explode(':', $sort, 2), 2, $order);

            return [['field' => $field, 'order' => $dir]];
        }

        // Already a structured array: [['field' => 'end_date', 'order' => 'asc']]
        if (isset($sort[0]) && is_array($sort[0])) {
            return array_map(function (array $item) {
                if (! isset($item['field'])) {
                    throw new InvalidArgumentException('Each sort item must have a field');
                }

                if (! in_array($item['field'], array_keys($this->availableSortFields))) {
                    throw new InvalidArgumentException(
                        "Invalid sort field: {$item['field']}. Must be one of: ".implode(', ', array_keys($this->availableSortFields))
                    );
                }

                return [
                    'field' => $item['field'],
                    'order' => $item['order'] ?? 'asc',
                ];
            }, $sort);
        }

        // Single sort object: ['field' => 'end_date', 'order' => 'asc']
        if (isset($sort['field'])) {
            return [['field' => $sort['field'], 'order' => $sort['order'] ?? $order]];
        }

        return [];
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
                'description' => 'Array of plannable items with pagination (HTTP 200)',
                'fields' => [
                    'data' => 'Array of plannable item objects',
                    'data[].id' => 'Plannable item UUID',
                    'data[].source' => 'Source entity reference {id, type}',
                    'data[].total_duration' => 'Total estimated duration {unit: minutes, value}',
                    'data[].planned_duration' => 'Duration already planned {unit: minutes, value}',
                    'data[].unplanned_duration' => 'Remaining duration to plan {unit: minutes, value}',
                ],
            ],
            'info' => [
                'description' => 'Single plannable item (HTTP 200)',
                'fields' => [
                    'data.id' => 'Plannable item UUID',
                    'data.source' => 'Source entity reference {id, type}',
                    'data.total_duration' => 'Total estimated duration {unit: minutes, value}',
                    'data.planned_duration' => 'Duration already planned {unit: minutes, value}',
                    'data.unplanned_duration' => 'Remaining duration to plan {unit: minutes, value}',
                ],
            ],
        ];
    }
}

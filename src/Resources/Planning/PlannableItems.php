<?php

namespace McoreServices\TeamleaderSDK\Resources\Planning;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Planning\Concerns\ChecksPlanningFilters;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class PlannableItems extends Resource
{
    use ChecksPlanningFilters;
    use ValidatesWritePayload;

    /** `filter.types[]` — the kind of item behind the plannable item */
    public const TYPES = ['closingDay', 'dayOffType', 'meeting', 'task', 'call', 'externalEvent'];

    public const COMPLETION_STATUSES = ['to_do', 'done'];

    public const PLANNED_TIME_STATUSES = ['unplanned', 'partially_planned', 'fully_planned', 'overbooked'];

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
        'types' => 'Filter by item type: closingDay, dayOffType, meeting, task, call, externalEvent',
        'term' => 'Search term (matches item title/name)',
        'start_date' => 'Filter items from this date (YYYY-MM-DD)',
        'end_date' => 'Filter items up to this date (YYYY-MM-DD)',
        'project_ids' => 'Filter by array of project UUIDs',
        'assignees' => 'Filter by assignees (list of [type, id]; a null entry matches unassigned items)',
        'work_type_ids' => 'Filter by array of work type UUIDs',
        'completion_statuses' => 'Filter by completion status: to_do, done',
        'planned_time_statuses' => 'Filter by planned time status: unplanned, partially_planned, fully_planned, overbooked',
    ];

    // Valid status values
    // Valid completion status values
    protected array $completionStatuses = self::COMPLETION_STATUSES;

    // Valid planned time status values
    protected array $plannedTimeStatuses = self::PLANNED_TIME_STATUSES;

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
        'filter_by_type' => [
            'description' => 'Get only tasks and meetings',
            'code' => '$items = $teamleader->plannableItems()->ofTypes([\'task\', \'meeting\']);',
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
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'sort', 'sort_order']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'plannableItems.list does not support: '.implode(', ', $unknown)
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
     * @deprecated since v2.2.16 — plannableItems.list has no status filter.
     * Until now this sent `status: [active]`, which the API ignored, so it
     * returned every item; it still does, without the ignored filter, and
     * raises E_USER_DEPRECATED once. Removed in v3.0.
     */
    public function active(array $filters = [], array $options = []): array
    {
        static $warned = false;

        if (! $warned) {
            $warned = true;
            trigger_error(
                'plannableItems()->active() is deprecated: plannableItems.list has no status filter, so it never filtered. '
                .'Use list(), or filter on completion_statuses / planned_time_statuses.',
                E_USER_DEPRECATED
            );
        }

        return $this->list($filters, $options);
    }

    /**
     * Get plannable items of the given types
     *
     * @param  list<string>  $types  closingDay, dayOffType, meeting, task, call, externalEvent
     */
    public function ofTypes(array $types, array $filters = [], array $options = []): array
    {
        return $this->list(array_merge(['types' => $types], $filters), $options);
    }

    /**
     * Get plannable items nobody is assigned to
     */
    public function unassigned(array $filters = [], array $options = []): array
    {
        return $this->list(array_merge(['assignees' => [null]], $filters), $options);
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
     * Build the filter object for plannableItems.list
     *
     * Until v2.2.16 this advertised and sent a `status` filter the endpoint
     * does not have (so it returned everything), did not offer `types`,
     * dropped unknown keys and string ids without a word, and passed
     * assignees through unchecked.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $endpoint = 'plannableItems.list';

        if (array_key_exists('status', $filters)) {
            throw new InvalidArgumentException(
                'plannableItems.list has no status filter; the API would ignore it and return every item. '
                .'Use types, completion_statuses or planned_time_statuses.'
            );
        }

        $this->rejectUnknownFilters($filters, $endpoint);

        foreach (['ids', 'project_ids', 'work_type_ids'] as $key) {
            if (isset($filters[$key]) && ! is_array($filters[$key])) {
                $filters[$key] = [$filters[$key]];
            }
        }

        foreach (['types' => self::TYPES, 'completion_statuses' => self::COMPLETION_STATUSES, 'planned_time_statuses' => self::PLANNED_TIME_STATUSES] as $key => $allowed) {
            if (isset($filters[$key])) {
                $filters[$key] = $this->checkedEnumList($filters[$key], $allowed, $key, $endpoint);
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

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Build the sort array for plannableItems.list
     *
     * Accepts everything normaliseSort() does, plus "field:order" strings
     * ('end_date:desc'). A string sort was not validated before v2.2.16.
     *
     * @param  array|string  $sort
     *
     * @throws InvalidArgumentException On an unknown field or order
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        if (is_string($sort) && str_contains($sort, ':')) {
            [$sort, $order] = explode(':', $sort, 2);
        }

        return $this->normaliseSort($sort, $order);
    }

    /**
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

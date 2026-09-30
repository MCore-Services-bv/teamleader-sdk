<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Departments extends Resource
{
    use ValidatesWritePayload;

    /** `filter.status[]` on departments.list */
    public const STATUSES = ['active', 'archived'];

    protected string $description = 'Manage departments in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;  // Based on API docs, no create endpoint

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    // departments.list takes no page and neither endpoint takes includes;
    // both flags defaulted to true until v2.2.14.
    protected bool $supportsPagination = false;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    /**
     * Sort fields departments.list accepts. Until v2.2.14 the sort was passed
     * through unchecked.
     */
    protected array $availableSortFields = [
        'default_department' => 'Ascending lists the default department first',
        'name' => 'Department name',
        'created_at' => 'Creation date',
    ];

    // Available includes for sideloading
    protected array $availableIncludes = [
        // No specific includes mentioned in API docs for departments
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of department UUIDs to filter by',
        'status' => 'Filter by department status (active, archived)',
    ];

    // Usage examples specific to departments
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all departments',
            'code' => '$departments = $teamleader->departments()->list();',
        ],
        'list_active' => [
            'description' => 'Get only active departments',
            'code' => '$departments = $teamleader->departments()->list([\'status\' => [\'active\']]);',
        ],
        'list_specific' => [
            'description' => 'Get specific departments by ID',
            'code' => '$departments = $teamleader->departments()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'sorted_list' => [
            'description' => 'Get departments sorted by name',
            'code' => '$departments = $teamleader->departments()->list([], [\'sort\' => [[\'field\' => \'name\', \'order\' => \'asc\']]]);',
        ],
        'get_single' => [
            'description' => 'Get a single department',
            'code' => '$department = $teamleader->departments()->info(\'department-uuid-here\');',
        ],
    ];

    /**
     * Get the base path for the departments resource
     */
    protected function getBasePath(): string
    {
        return 'departments';
    }

    /**
     * List departments
     *
     * @param  array  $filters  ids, status (active and/or archived)
     * @param  array  $options  sort (default_department|name|created_at), sort_order
     *
     * @throws InvalidArgumentException On an unknown filter key, option, value or sort field
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['sort', 'sort_order']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'departments.list does not support: '.implode(', ', $unknown).'. Supported: sort, sort_order.'
            );
        }

        $params = [];

        if ($filters !== []) {
            $params['filter'] = $this->buildFilters($filters);
        }

        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get one department
     *
     * @param  string  $id  Department UUID
     * @param  mixed  $includes  departments.info takes no includes; passing any throws
     */
    public function info($id, $includes = null): array
    {
        $this->assertIncludes($includes, [], 'departments.info');

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Get active departments only
     */
    public function active(): array
    {
        return $this->list(['status' => ['active']]);
    }

    /**
     * Get archived departments only
     */
    public function archived(): array
    {
        return $this->list(['status' => ['archived']]);
    }

    /**
     * Get departments by specific IDs
     *
     * @param  array  $ids  Array of department UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Build the filter object for departments.list
     *
     * Until v2.2.14 unknown keys and a string `ids` were dropped without a
     * word, and status values were not checked.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'departments.list');

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? array_values($filters['ids']) : [$filters['ids']];
        }

        if (isset($filters['status'])) {
            $apiFilters['status'] = is_array($filters['status']) ? array_values($filters['status']) : [$filters['status']];

            foreach ($apiFilters['status'] as $index => $status) {
                $this->assertEnum($status, self::STATUSES, "filter.status[{$index}]", 'departments.list');
            }
        }

        return $apiFilters;
    }

    /**
     * Build the sort array for departments.list
     *
     * Accepts a field name, ['field' => ..., 'order' => ...], a list of those
     * or a field => order map.
     *
     * @param  array|string  $sort
     *
     * @throws InvalidArgumentException On an unknown field or order
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        return $this->normaliseSort($sort, $order);
    }

    public function getAvailableSortFields(): array
    {
        return $this->availableSortFields;
    }

    /**
     * Get available status values for filtering
     */
    public function getAvailableStatuses(): array
    {
        return self::STATUSES;
    }

    /**
     * Override the default validation since departments have limited operations
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Departments are read-only in this API, so no validation needed for create/update
        return $data;
    }

    /**
     * Override getSuggestedIncludes as departments don't have common includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Departments don't have sideloadable relationships in the API
    }
}

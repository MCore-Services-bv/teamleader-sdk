<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use McoreServices\TeamleaderSDK\Resources\Resource;

class Departments extends Resource
{
    protected string $description = 'Manage departments in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;  // Based on API docs, no create endpoint
    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint
    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint
    protected bool $supportsBatch = false;

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
            'code' => '$departments = $teamleader->departments()->list();'
        ],
        'list_active' => [
            'description' => 'Get only active departments',
            'code' => '$departments = $teamleader->departments()->list([\'status\' => [\'active\']]);'
        ],
        'list_specific' => [
            'description' => 'Get specific departments by ID',
            'code' => '$departments = $teamleader->departments()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);'
        ],
        'sorted_list' => [
            'description' => 'Get departments sorted by name',
            'code' => '$departments = $teamleader->departments()->list([], [\'sort\' => [[\'field\' => \'name\', \'order\' => \'asc\']]]);'
        ],
        'get_single' => [
            'description' => 'Get a single department',
            'code' => '$department = $teamleader->departments()->info(\'department-uuid-here\');'
        ]
    ];

    /**
     * Get the base path for the departments resource
     */
    protected function getBasePath(): string
    {
        return 'departments';
    }

    /**
     * List departments with enhanced filtering and sorting
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (sorting, pagination)
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply sorting - departments have specific sort fields
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get department information
     *
     * @param string $id Department UUID
     * @param mixed $includes Includes (not used for departments)
     * @return array
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        // Departments don't support includes, but we maintain compatibility
        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    /**
     * Get active departments only
     *
     * @return array
     */
    public function active(): array
    {
        return $this->list(['status' => ['active']]);
    }

    /**
     * Get archived departments only
     *
     * @return array
     */
    public function archived(): array
    {
        return $this->list(['status' => ['archived']]);
    }

    /**
     * Get departments by specific IDs
     *
     * @param array $ids Array of department UUIDs
     * @return array
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters
     * @return array
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // Handle status filter
        if (isset($filters['status'])) {
            if (is_string($filters['status'])) {
                $apiFilters['status'] = [$filters['status']];
            } elseif (is_array($filters['status'])) {
                $apiFilters['status'] = $filters['status'];
            }
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     *
     * @param mixed $sort
     * @return array
     */
    private function buildSort($sort): array
    {
        // If already in correct format, return as-is
        if (is_array($sort) && isset($sort[0]['field'])) {
            return $sort;
        }

        // Handle simple string sort
        if (is_string($sort)) {
            return [
                [
                    'field' => $sort,
                    'order' => 'asc'
                ]
            ];
        }

        // Handle associative array
        if (is_array($sort)) {
            $sortArray = [];
            foreach ($sort as $field => $order) {
                if (is_numeric($field) && is_array($order)) {
                    // Already in correct format
                    $sortArray[] = $order;
                } else {
                    $sortArray[] = [
                        'field' => $field,
                        'order' => $order
                    ];
                }
            }
            return $sortArray;
        }

        return [];
    }

    /**
     * Get available sort fields for departments (based on API documentation)
     *
     * @return array
     */
    public function getAvailableSortFields(): array
    {
        return [
            'default_department' => 'When sorting ascending, default departments are listed first (sorting only)',
            'name' => 'Sorts by department name',
            'created_at' => 'Sorts by department creation date'
        ];
    }

    /**
     * Get available status values for filtering
     *
     * @return array
     */
    public function getAvailableStatuses(): array
    {
        return ['active', 'archived'];
    }

    /**
     * Override the default validation since departments have limited operations
     *
     * @param array $data
     * @param string $operation
     * @return array
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Departments are read-only in this API, so no validation needed for create/update
        return $data;
    }

    /**
     * Override getSuggestedIncludes as departments don't have common includes
     *
     * @return array
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Departments don't have sideloadable relationships in the API
    }
}

<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class WorkTypes extends Resource
{
    protected string $description = 'Manage work types in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;  // Based on API docs, no create endpoint

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [
        // No specific includes mentioned in API docs for work types
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of work type UUIDs to filter by',
        'term' => 'Search term - searches in the work type name only',
    ];

    // Usage examples specific to work types
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all work types',
            'code' => '$workTypes = $teamleader->workTypes()->list();',
        ],
        'search_by_term' => [
            'description' => 'Search work types by name',
            'code' => '$workTypes = $teamleader->workTypes()->list([\'term\' => \'design\']);',
        ],
        'list_specific' => [
            'description' => 'Get specific work types by ID',
            'code' => '$workTypes = $teamleader->workTypes()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'sorted_list' => [
            'description' => 'Get work types sorted by name',
            'code' => '$workTypes = $teamleader->workTypes()->list([], [\'sort\' => [[\'field\' => \'name\', \'order\' => \'asc\']]]);',
        ],
        'paginated_list' => [
            'description' => 'Get work types with pagination',
            'code' => '$workTypes = $teamleader->workTypes()->list([], [\'page_size\' => 50, \'page_number\' => 2]);',
        ],
    ];

    /**
     * Search work types by term
     *
     * @param  string  $term  Search term
     */
    public function search(string $term): array
    {
        return $this->list(['term' => $term]);
    }

    /**
     * List work types with enhanced filtering and sorting
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (sorting, pagination)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply sorting - work types support name sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
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
     * Build filters array for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // Handle search term filter
        if (isset($filters['term']) && is_string($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     *
     * @param array|string $sort
     * @param string $order
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        // If already in correct format, return as-is
        if (is_array($sort) && isset($sort['field'])) {
            return $sort;
        }

        // If it's an array of sort objects, return as-is
        if (is_array($sort) && isset($sort[0]) && is_array($sort[0]) && isset($sort[0]['field'])) {
            return $sort;
        }

        // Handle simple string sort
        if (is_string($sort)) {
            return [
                'field' => $sort,
                'order' => 'asc',
            ];
        }

        // Handle associative array
        if (is_array($sort)) {
            foreach ($sort as $field => $order) {
                if (is_numeric($field) && is_array($order)) {
                    // Already in correct format
                    return $order;
                } else {
                    return [
                        'field' => $field,
                        'order' => $order,
                    ];
                }
            }
        }

        // Default sort
        return [
            'field' => 'name',
            'order' => 'asc',
        ];
    }

    /**
     * Get the base path for the work types resource
     */
    protected function getBasePath(): string
    {
        return 'workTypes';
    }

    /**
     * Get work types by specific IDs
     *
     * @param  array  $ids  Array of work type UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get work types with pagination
     *
     * @param  int  $pageSize  Number of items per page
     * @param  int  $pageNumber  Page number
     * @param  array  $filters  Optional filters
     */
    public function paginate(int $pageSize = 20, int $pageNumber = 1, array $filters = []): array
    {
        return $this->list($filters, [
            'page_size' => $pageSize,
            'page_number' => $pageNumber,
        ]);
    }

    /**
     * Get work types sorted by name
     *
     * @param  string  $order  Sort order (asc or desc)
     * @param  array  $filters  Optional filters
     */
    public function sortedByName(string $order = 'asc', array $filters = []): array
    {
        return $this->list($filters, [
            'sort' => [
                [
                    'field' => 'name',
                    'order' => $order,
                ],
            ],
        ]);
    }

    /**
     * Get available sort fields for work types (based on API documentation)
     */
    public function getAvailableSortFields(): array
    {
        return [
            'name' => 'Sorts by work type name (alphabetically)',
        ];
    }

    /**
     * Work types don't support info method as per API docs, but we keep it for consistency
     * and throw a helpful exception
     *
     * @param  string  $id  Work type UUID
     * @param  mixed  $includes  Includes (not used)
     *
     * @throws InvalidArgumentException
     */
    public function info($id, $includes = null): array
    {
        throw new InvalidArgumentException(
            'The workTypes resource does not support individual info requests. '.
            "Use the list() method with an 'ids' filter to get specific work types."
        );
    }

    /**
     * Override the default validation since work types have limited operations
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Work types are read-only in this API, so no validation needed for create/update
        return $data;
    }

    /**
     * Override getSuggestedIncludes as work types don't have common includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Work types don't have sideloadable relationships in the API
    }
}

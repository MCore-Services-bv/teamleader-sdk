<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use McoreServices\TeamleaderSDK\Resources\Resource;

class LostReasons extends Resource
{
    protected string $description = 'Manage lost reasons for deals in Teamleader Focus';

    // Resource capabilities - based on API docs, only list endpoint available
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsSideloading = false; // No includes mentioned in API docs

    // Available sort fields based on API documentation
    protected array $availableSortFields = [
        'name' => 'Sort by lost reason name',
    ];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of lost reason UUIDs to filter by',
    ];

    // Usage examples specific to lost reasons
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all lost reasons',
            'code' => '$lostReasons = $teamleader->lostReasons()->list();',
        ],
        'list_specific' => [
            'description' => 'Get specific lost reasons by ID',
            'code' => '$lostReasons = $teamleader->lostReasons()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'sorted_list' => [
            'description' => 'Get lost reasons sorted by name',
            'code' => '$lostReasons = $teamleader->lostReasons()->list([], [\'sort\' => [[\'field\' => \'name\', \'order\' => \'asc\']]]);',
        ],
        'with_pagination' => [
            'description' => 'Get lost reasons with custom pagination',
            'code' => '$lostReasons = $teamleader->lostReasons()->list([], [\'page_size\' => 50, \'page_number\' => 2]);',
        ],
    ];

    /**
     * Get the base path for the lost reasons resource
     */
    protected function getBasePath(): string
    {
        return 'lostReasons';
    }

    /**
     * List lost reasons with enhanced filtering and sorting
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

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        } elseif (isset($options['sort_field']) || isset($options['sort_order'])) {
            // Handle legacy sort parameters
            $sortField = $options['sort_field'] ?? 'name';
            $sortOrder = $options['sort_order'] ?? 'asc';
            $params['sort'] = $this->buildSort($sortField, $sortOrder);
        }

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get lost reasons by specific IDs
     *
     * @param  array  $ids  Array of lost reason UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get all lost reasons (convenience method)
     *
     * @param  string  $sortOrder  Sort order (asc or desc)
     */
    public function all(string $sortOrder = 'asc'): array
    {
        return $this->list([], [
            'sort' => [
                [
                    'field' => 'name',
                    'order' => $sortOrder,
                ],
            ],
            'page_size' => 100, // Get more results in one go
        ]);
    }

    /**
     * Search lost reasons by name (using existing filters)
     *
     * @param  string  $query  Search query (note: actual text search not supported, this gets specific IDs)
     * @param  array  $ids  Array of IDs to search within
     */
    public function search(array $ids = []): array
    {
        if (empty($ids)) {
            return $this->all();
        }

        return $this->byIds($ids);
    }

    /**
     * Build filters array for the API request
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = array_values($filters['ids']); // Ensure indexed array
        }

        // Remove empty filters
        return array_filter($apiFilters, function ($value) {
            return ! empty($value);
        });
    }

    /**
     * Build sort array for the API request
     *
     * @param  mixed  $sort  Sort field or array
     * @param  string  $order  Sort order (when $sort is string)
     */
    private function buildSort($sort, string $order = 'asc'): array
    {
        // If already in correct format, return as-is
        if (is_array($sort) && isset($sort[0]) && is_array($sort[0]) && isset($sort[0]['field'])) {
            return $sort;
        }

        // Handle simple string sort
        if (is_string($sort)) {
            return [
                [
                    'field' => $sort === 'name' ? 'name' : 'name', // Only 'name' is supported
                    'order' => in_array($order, ['asc', 'desc']) ? $order : 'asc',
                ],
            ];
        }

        // Handle associative array
        if (is_array($sort)) {
            $sortArray = [];
            foreach ($sort as $field => $sortOrder) {
                if (is_numeric($field) && is_array($sortOrder)) {
                    // Already in correct format
                    $sortArray[] = $sortOrder;
                } else {
                    $sortArray[] = [
                        'field' => 'name', // Only name field is supported
                        'order' => in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc',
                    ];
                }
            }

            return $sortArray;
        }

        // Default sort
        return [
            [
                'field' => 'name',
                'order' => 'asc',
            ],
        ];
    }

    /**
     * Get available sort fields (based on API documentation)
     */
    public function getAvailableSortFields(): array
    {
        return [
            'name' => 'Sort by lost reason name',
        ];
    }

    /**
     * Validate sort field (only 'name' is supported)
     */
    public function isValidSortField(string $field): bool
    {
        return $field === 'name';
    }

    /**
     * Override info method since this resource doesn't support individual item retrieval
     */
    public function info($id, $includes = null): array
    {
        // Since there's no info endpoint, we try to get it from the list
        $result = $this->list(['ids' => [$id]]);

        if (isset($result['data']) && ! empty($result['data'])) {
            return [
                'data' => $result['data'][0],
                'included' => $result['included'] ?? [],
            ];
        }

        return [
            'error' => true,
            'status_code' => 404,
            'message' => 'Lost reason not found',
        ];
    }

    /**
     * Override validation since this resource is read-only
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Lost reasons are read-only in this API, so no validation needed
        return $data;
    }

    /**
     * Override getSuggestedIncludes as lost reasons don't have sideloadable relationships
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // No includes available for lost reasons
    }

    /**
     * Get statistics about available lost reasons (convenience method)
     */
    public function getStats(): array
    {
        $result = $this->all();

        if (isset($result['data']) && is_array($result['data'])) {
            return [
                'total_count' => count($result['data']),
                'names' => array_column($result['data'], 'name'),
                'ids' => array_column($result['data'], 'id'),
            ];
        }

        return [
            'total_count' => 0,
            'names' => [],
            'ids' => [],
        ];
    }

    /**
     * Check if a lost reason exists by ID
     */
    public function exists(string $id): bool
    {
        $result = $this->list(['ids' => [$id]]);

        return isset($result['data']) && ! empty($result['data']);
    }

    /**
     * Get lost reasons as select options for forms
     */
    public function getSelectOptions(): array
    {
        $result = $this->all();
        $options = [];

        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $lostReason) {
                $options[] = [
                    'value' => $lostReason['id'],
                    'label' => $lostReason['name'],
                ];
            }
        }

        return $options;
    }
}

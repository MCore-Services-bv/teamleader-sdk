<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Sources extends Resource
{
    protected string $description = 'Manage deal sources in Teamleader Focus';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = false;   // No create endpoint available

    protected bool $supportsUpdate = false;     // No update endpoint available

    protected bool $supportsDeletion = false;   // No delete endpoint available

    protected bool $supportsBatch = false;      // No batch operations available

    protected bool $supportsPagination = true;  // Has pagination support

    protected bool $supportsFiltering = true;   // Has filtering by IDs

    protected bool $supportsSorting = true;     // Has sorting support

    protected bool $supportsSideloading = false; // No includes available

    // Available includes for sideloading (none for deal sources)
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of deal source UUIDs to filter by',
    ];

    // Usage examples specific to deal sources
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all deal sources',
            'code' => '$sources = $teamleader->dealSources()->list();',
        ],
        'list_specific' => [
            'description' => 'Get specific deal sources by ID',
            'code' => '$sources = $teamleader->dealSources()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'sorted_list' => [
            'description' => 'Get deal sources sorted by name (default)',
            'code' => '$sources = $teamleader->dealSources()->list([], [\'sort\' => [[\'field\' => \'name\', \'order\' => \'asc\']]]);',
        ],
        'paginated_list' => [
            'description' => 'Get deal sources with pagination',
            'code' => '$sources = $teamleader->dealSources()->list([], [\'page_size\' => 50, \'page_number\' => 1]);',
        ],
    ];

    /**
     * Search deal sources by name (client-side filtering)
     * Note: The API doesn't support text search, so we fetch all and filter
     *
     * @param  string  $query  Search query
     */
    public function search(string $query): array
    {
        $response = $this->all();

        if (isset($response['error']) || ! isset($response['data'])) {
            return $response;
        }

        // Filter results by name containing the query
        $filteredData = array_filter($response['data'], function ($source) use ($query) {
            return stripos($source['name'] ?? '', $query) !== false;
        });

        // Re-index array and maintain response structure
        $response['data'] = array_values($filteredData);

        return $response;
    }

    /**
     * Get all deal sources (convenience method)
     */
    public function all(): array
    {
        return $this->list();
    }

    /**
     * List deal sources with enhanced filtering and sorting
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

        // Apply pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        // Apply sorting - deal sources only support name field
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        } else {
            // Default sort by name ascending
            $params['sort'] = [
                [
                    'field' => 'name',
                    'order' => 'asc',
                ],
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
            $apiFilters['ids'] = array_values($filters['ids']); // Ensure indexed array
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     *
     * @param  mixed  $sort
     */
    protected function buildSort($sort): array
    {
        // If already in correct format, return as-is
        if (is_array($sort) && isset($sort[0]['field'])) {
            return $sort;
        }

        // Handle simple string sort (only 'name' is supported)
        if (is_string($sort) && $sort === 'name') {
            return [
                [
                    'field' => 'name',
                    'order' => 'asc',
                ],
            ];
        }

        // Handle associative array
        if (is_array($sort)) {
            $sortArray = [];
            foreach ($sort as $field => $order) {
                if (is_numeric($field) && is_array($order)) {
                    // Already in correct format
                    $sortArray[] = $order;
                } elseif ($field === 'name') {
                    $sortArray[] = [
                        'field' => 'name',
                        'order' => strtolower($order) === 'desc' ? 'asc' : 'asc', // API only supports asc
                    ];
                }
            }

            return $sortArray;
        }

        // Default fallback
        return [
            [
                'field' => 'name',
                'order' => 'asc',
            ],
        ];
    }

    /**
     * Get the base path for the deal sources resource
     */
    protected function getBasePath(): string
    {
        return 'dealSources';
    }

    /**
     * Get deal sources in select option format for forms
     */
    public function selectOptions(): array
    {
        $response = $this->all();

        if (isset($response['error']) || ! isset($response['data'])) {
            return [];
        }

        $options = [];
        foreach ($response['data'] as $source) {
            $options[$source['id']] = $source['name'] ?? 'Unnamed Source';
        }

        return $options;
    }

    /**
     * Get available sort fields for deal sources (based on API documentation)
     */
    public function getAvailableSortFields(): array
    {
        return [
            'name' => 'Sorts by deal source name (ascending only)',
        ];
    }

    /**
     * Override info method to clarify it's not available
     *
     * @param  string  $id
     * @param  mixed  $includes
     */
    public function info($id, $includes = null): array
    {
        throw new InvalidArgumentException(
            'The dealSources resource does not support individual resource retrieval. Use list() to get all sources.'
        );
    }

    /**
     * Get statistics about deal sources
     */
    public function getStatistics(): array
    {
        $response = $this->all();

        if (isset($response['error']) || ! isset($response['data'])) {
            return [
                'total_sources' => 0,
                'error' => $response['message'] ?? 'Failed to fetch statistics',
            ];
        }

        return [
            'total_sources' => count($response['data']),
            'sources' => array_map(function ($source) {
                return [
                    'id' => $source['id'],
                    'name' => $source['name'],
                    'name_length' => strlen($source['name'] ?? ''),
                ];
            }, $response['data']),
        ];
    }

    /**
     * Validate if a deal source ID exists
     */
    public function exists(string $sourceId): bool
    {
        $response = $this->byIds([$sourceId]);

        if (isset($response['error']) || ! isset($response['data'])) {
            return false;
        }

        return count($response['data']) > 0;
    }

    /**
     * Get deal sources by specific IDs
     *
     * @param  array  $ids  Array of deal source UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get deal source name by ID
     */
    public function getName(string $sourceId): ?string
    {
        $response = $this->byIds([$sourceId]);

        if (isset($response['error']) || ! isset($response['data']) || empty($response['data'])) {
            return null;
        }

        return $response['data'][0]['name'] ?? null;
    }

    /**
     * Override getSuggestedIncludes as deal sources don't have includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Deal sources don't have sideloadable relationships
    }
}

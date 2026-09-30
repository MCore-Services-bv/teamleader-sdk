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

    // Filters accepted by dealSources.list
    protected array $commonFilters = [
        'ids' => 'Array of deal source UUIDs to filter by',
        'term' => 'Search the deal source name',
    ];

    /**
     * The one sort field dealSources.list documents. Order is asc only.
     */
    protected array $availableSortFields = [
        'name' => 'Deal source name (ascending only)',
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
     * Search deal sources by name
     *
     * Uses the `term` filter, which dealSources.list has declared since
     * specification 1.221.0. Before v2.2.5 this fetched the first page and
     * filtered it in PHP, so a source past the first 20 was never found.
     *
     * @param  string  $query  Search query
     */
    public function search(string $query, array $options = []): array
    {
        return $this->list(['term' => $query], $options);
    }

    /**
     * Get every deal source, walking all pages
     *
     * Before v2.2.5 this was list() with no options — the first 20 sources
     * only — so selectOptions() and getStatistics() silently dropped the rest.
     *
     * @param  int  $maxPages  Runaway guard: 50 pages of 100
     *
     * @throws InvalidArgumentException When $maxPages is reached with sources still pending
     */
    public function all(int $maxPages = 50): array
    {
        $sources = [];
        $page = 1;

        do {
            $batch = $this->list([], ['page_size' => 100, 'page_number' => $page])['data'] ?? [];
            $sources = array_merge($sources, $batch);
            $hasMore = count($batch) === 100;
            $page++;
        } while ($hasMore && $page <= $maxPages);

        if ($hasMore) {
            throw new InvalidArgumentException(
                "dealSources.list still had records after {$maxPages} pages of 100. Raise \$maxPages."
            );
        }

        return ['data' => $sources];
    }

    /**
     * List deal sources with filtering, pagination and sorting
     *
     * @param  array  $filters  `ids`, `term`
     * @param  array  $options  page_size, page_number, sort (name), sort_order (asc)
     *
     * @throws InvalidArgumentException On an unknown filter, or a sort other than name/asc
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

        // name / asc is the only sort the endpoint documents, and the default
        $params['sort'] = $this->buildSort($options['sort'] ?? 'name', $options['sort_order'] ?? 'asc');

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build filters array for the API request
     *
     * @throws InvalidArgumentException When an unsupported filter key is passed
     */
    protected function buildFilters(array $filters): array
    {
        $unknown = array_diff(array_keys($filters), array_keys($this->commonFilters));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key').' for dealSources.list: '
                .implode(', ', $unknown).'. Supported: '.implode(', ', array_keys($this->commonFilters)).'.'
            );
        }

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? array_values($filters['ids']) : [$filters['ids']];
        }

        if (isset($filters['term']) && $filters['term'] !== '') {
            $apiFilters['term'] = $filters['term'];
        }

        return $apiFilters;
    }

    /**
     * Build the sort array — `name`, ascending, is the only sort accepted
     *
     * Before v2.2.5 anything else was silently rewritten to name/asc, so a
     * caller asking for descending order got ascending back with no
     * indication. The same defect was fixed on Tags in v2.1.2.
     *
     * @param  array|string  $sort
     *
     * @throws InvalidArgumentException When another field or order is requested
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        $sort = $this->normaliseSort($sort, $order);

        foreach ($sort as $entry) {
            if ($entry['order'] !== 'asc') {
                throw new InvalidArgumentException(
                    "Invalid sort order: {$entry['order']}. dealSources.list accepts only 'asc'."
                );
            }
        }

        return $sort;
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
        return $this->availableSortFields;
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

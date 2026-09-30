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
     * List lost reasons with filtering, sorting and pagination
     *
     * @param  array  $filters  `ids` only
     * @param  array  $options  page_size, page_number, sort (name), sort_order (asc)
     *
     * @throws \InvalidArgumentException On an unknown filter, or a sort other than name/asc
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        // Apply sorting — `name`, ascending, is the only sort the endpoint
        // accepts. `sort_field` is the legacy spelling of `sort`.
        if (isset($options['sort']) || isset($options['sort_field']) || isset($options['sort_order'])) {
            $params['sort'] = $this->buildSort(
                $options['sort'] ?? $options['sort_field'] ?? 'name',
                $options['sort_order'] ?? 'asc'
            );
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
     * Get every lost reason, walking all pages
     *
     * lostReasons.list sorts by name ascending only. Before v2.2.5 this took a
     * $sortOrder and passed 'desc' straight through; the argument is kept for
     * compatibility and must be 'asc'. It also returned only the first page
     * of 100.
     *
     * @param  string  $sortOrder  Must be 'asc'
     * @param  int  $maxPages  Runaway guard: 50 pages of 100
     *
     * @throws \InvalidArgumentException On a sort order other than asc, or when $maxPages is reached
     */
    public function all(string $sortOrder = 'asc', int $maxPages = 50): array
    {
        $reasons = [];
        $page = 1;

        do {
            $batch = $this->list([], [
                'sort' => 'name',
                'sort_order' => $sortOrder,
                'page_size' => 100,
                'page_number' => $page,
            ])['data'] ?? [];

            $reasons = array_merge($reasons, $batch);
            $hasMore = count($batch) === 100;
            $page++;
        } while ($hasMore && $page <= $maxPages);

        if ($hasMore) {
            throw new \InvalidArgumentException(
                "lostReasons.list still had records after {$maxPages} pages of 100. Raise \$maxPages."
            );
        }

        return ['data' => $reasons];
    }

    /**
     * Build filters array for the API request
     *
     * @throws \InvalidArgumentException When an unsupported filter key is passed
     */
    protected function buildFilters(array $filters): array
    {
        $unknown = array_diff(array_keys($filters), array_keys($this->commonFilters));

        if ($unknown !== []) {
            throw new \InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key').' for lostReasons.list: '
                .implode(', ', $unknown).'. Supported: ids.'
            );
        }

        if (! isset($filters['ids'])) {
            return [];
        }

        return ['ids' => is_array($filters['ids']) ? array_values($filters['ids']) : [$filters['ids']]];
    }

    /**
     * Build the sort array — `name`, ascending, is the only sort accepted
     *
     * Before v2.2.5 any field was rewritten to `name` without a word, and
     * `desc` was sent although the endpoint declares `asc` only.
     *
     * @param  mixed  $sort  A field name, list of names, or sort objects
     *
     * @throws \InvalidArgumentException When another field or order is requested
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        $sort = $this->normaliseSort($sort, $order);

        foreach ($sort as $entry) {
            if ($entry['order'] !== 'asc') {
                throw new \InvalidArgumentException(
                    "Invalid sort order: {$entry['order']}. lostReasons.list accepts only 'asc'."
                );
            }
        }

        return $sort;
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
            return ['data' => $result['data'][0]];
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

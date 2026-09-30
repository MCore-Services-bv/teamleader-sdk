<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class WorkTypes extends Resource
{
    use ValidatesWritePayload;

    protected string $description = 'Manage work types in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = false;  // Based on API docs, no create endpoint

    protected bool $supportsUpdate = false;    // Based on API docs, no update endpoint

    protected bool $supportsDeletion = false;  // Based on API docs, no delete endpoint

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    // workTypes.list takes no sort; sortedByName() sorts client-side
    protected bool $supportsSorting = false;

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
            'code' => '$workTypes = $teamleader->workTypes()->sortedByName();',
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
     * List work types
     *
     * @param  array  $filters  ids, term
     * @param  array  $options  page_size, page_number (no sort — see sortedByName())
     *
     * @throws InvalidArgumentException On an unknown filter key or option
     */
    public function list(array $filters = [], array $options = []): array
    {
        if (isset($options['sort'])) {
            throw new InvalidArgumentException(
                'workTypes.list takes no sort: the API would ignore it. Use sortedByName(), which sorts the page client-side.'
            );
        }

        $unknown = array_diff(array_keys($options), ['page_size', 'page_number']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'workTypes.list does not support: '.implode(', ', $unknown).'. Supported: page_size, page_number.'
            );
        }

        $params = [];

        if ($filters !== []) {
            $params['filter'] = $this->buildFilters($filters);
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build the filter object for workTypes.list
     *
     * Until v2.2.14 unknown keys, a string `ids` and a non-string `term` were
     * dropped without a word.
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'workTypes.list');

        if (isset($filters['term']) && ! is_string($filters['term'])) {
            throw new InvalidArgumentException('term must be a string.');
        }

        if (isset($filters['ids']) && ! is_array($filters['ids'])) {
            $filters['ids'] = [$filters['ids']];
        }

        return array_filter($filters, fn ($value) => $value !== null);
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
        $order = $this->normaliseSortOrder($order);

        // workTypes.list declares no sort, so the API ignored the sort this
        // method sent until v2.2.14. The page is sorted here instead.
        $response = $this->list($filters, ['page_size' => 100]);

        if (isset($response['data']) && is_array($response['data'])) {
            usort($response['data'], fn ($a, $b) => strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

            if ($order === 'desc') {
                $response['data'] = array_reverse($response['data']);
            }
        }

        return $response;
    }

    /**
     * Get available sort fields for work types (based on API documentation)
     */
    public function getAvailableSortFields(): array
    {
        // workTypes.list declares no sort fields; `name` was listed here until v2.2.14.
        return [];
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

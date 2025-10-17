<?php

namespace McoreServices\TeamleaderSDK\Resources\Calendar;

use McoreServices\TeamleaderSDK\Resources\Resource;

class CallOutcomes extends Resource
{
    protected string $description = 'Manage call outcomes in Teamleader Focus Calendar';

    // Resource capabilities - CallOutcomes are typically read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for call outcomes)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of call outcome UUIDs',
    ];

    // Usage examples specific to call outcomes
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all call outcomes',
            'code' => '$callOutcomes = $teamleader->callOutcomes()->list();',
        ],
        'filter_by_ids' => [
            'description' => 'Get specific call outcomes by IDs',
            'code' => '$callOutcomes = $teamleader->callOutcomes()->byIds(["uuid1", "uuid2"]);',
        ],
        'find_by_name' => [
            'description' => 'Find call outcome by name',
            'code' => '$outcome = $teamleader->callOutcomes()->findByName("Succesvol gesprek");',
        ],
    ];

    /**
     * Get the base path for the call outcomes resource
     */
    protected function getBasePath(): string
    {
        return 'callOutcomes';
    }

    /**
     * List call outcomes with filtering and pagination
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = $this->buildQueryParams(
            [],
            $filters,
            $options['sort'] ?? null,
            $options['sort_order'] ?? 'asc',
            $options['page_size'] ?? 20,
            $options['page_number'] ?? 1,
            $options['include'] ?? null
        );

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build query parameters for API requests
     */
    protected function buildQueryParams(
        array $baseParams = [],
        array $filters = [],
        $sort = null,
        string $sortOrder = 'asc',
        int $pageSize = 20,
        int $pageNumber = 1,
        $includes = null
    ): array {
        $params = $baseParams;

        // Build filter object
        if (! empty($filters)) {
            $params['filter'] = [];

            if (isset($filters['ids']) && is_array($filters['ids'])) {
                $params['filter']['ids'] = $filters['ids'];
            }
        }

        // Build page object
        $params['page'] = [
            'size' => $pageSize,
            'number' => $pageNumber,
        ];

        return $params;
    }

    /**
     * Get call outcomes by specific IDs
     */
    public function byIds(array $ids, array $options = []): array
    {
        return $this->list(
            array_merge(['ids' => $ids], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Find call outcome by name
     */
    public function findByName(string $name, array $options = []): ?array
    {
        $response = $this->list([], $options);

        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $outcome) {
                if (isset($outcome['name']) && strcasecmp($outcome['name'], $name) === 0) {
                    return $outcome;
                }
            }
        }

        return null;
    }
}

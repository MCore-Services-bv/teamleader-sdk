<?php

namespace McoreServices\TeamleaderSDK\Resources\Products;

use McoreServices\TeamleaderSDK\Resources\Resource;

class PriceLists extends Resource
{
    protected string $description = 'Manage price lists in Teamleader Focus';

    // Resource capabilities - Price Lists are read-only based on API docs
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false; // No pagination mentioned in API docs

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of price list UUIDs to filter by',
    ];

    // Usage examples specific to price lists
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all price lists',
            'code' => '$priceLists = $teamleader->priceLists()->list();',
        ],
        'filter_by_ids' => [
            'description' => 'Get specific price lists by IDs',
            'code' => '$priceLists = $teamleader->priceLists()->list([\'ids\' => [\'uuid1\', \'uuid2\']]);',
        ],
        'get_by_ids' => [
            'description' => 'Get specific price lists (alias method)',
            'code' => '$priceLists = $teamleader->priceLists()->byIds([\'uuid1\', \'uuid2\']);',
        ],
    ];

    /**
     * Get the base path for the price lists resource
     */
    protected function getBasePath(): string
    {
        return 'priceLists';
    }

    /**
     * List price lists with filtering
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (not used for this endpoint)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get specific price lists by their UUIDs
     *
     * @param  array  $ids  Array of price list UUIDs
     */
    public function byIds(array $ids): array
    {
        if (empty($ids)) {
            throw new \InvalidArgumentException('At least one price list ID must be provided');
        }

        return $this->list(['ids' => $ids]);
    }

    /**
     * Build filters array for the API request
     *
     * @param  array  $filters  Raw filters
     * @return array Processed filters for the API
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
}

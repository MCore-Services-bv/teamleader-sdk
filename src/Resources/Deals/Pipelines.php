<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Pipelines extends Resource
{
    protected string $description = 'Manage deal pipelines in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = false;

    protected bool $supportsSideloading = false;

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    // Available includes for sideloading (none for deal pipelines)
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of deal pipeline UUIDs to filter by',
        'status' => 'Filter by pipeline status (open, pending_deletion)',
    ];

    /**
     * List deal pipelines with enhanced filtering and pagination
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination, includes)
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

        // Include pagination meta data by default
        if (! isset($options['includes'])) {
            $options['includes'] = 'pagination';
        }

        if (isset($options['includes'])) {
            $params['includes'] = $options['includes'];
        }

        // Debug logging
        if (function_exists('Log') && class_exists('Illuminate\Support\Facades\Log')) {
            \Illuminate\Support\Facades\Log::debug('DealPipelines list request', [
                'filters' => $filters,
                'options' => $options,
                'final_params' => $params,
            ]);
        }

        $response = $this->api->request('POST', $this->getBasePath().'.list', $params);

        // Debug logging for response
        if (function_exists('Log') && class_exists('Illuminate\Support\Facades\Log')) {
            \Illuminate\Support\Facades\Log::debug('DealPipelines list response', [
                'has_error' => isset($response['error']),
                'response_keys' => array_keys($response),
                'data_count' => isset($response['data']) ? count($response['data']) : 0,
            ]);
        }

        return $response;
    }

    /**
     * Create a new deal pipeline
     *
     * @param  array  $data  Pipeline data
     */
    public function create(array $data): array
    {
        if (! $this->supportsCreation) {
            throw new InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support creation"
            );
        }

        // Validate required fields
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Pipeline name is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Get the base path for the deal pipelines resource
     */
    protected function getBasePath(): string
    {
        return 'dealPipelines';
    }

    /**
     * Update a deal pipeline
     *
     * @param  string  $id  Pipeline UUID
     * @param  array  $data  Update data
     */
    public function update($id, array $data): array
    {
        if (! $this->supportsUpdate) {
            throw new InvalidArgumentException(
                "The {$this->getBasePath()} resource does not support updates"
            );
        }

        $data['id'] = $id;

        // Validate required fields
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Pipeline name is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a deal pipeline with phase migration
     *
     * @param  string  $id  Pipeline UUID to delete
     * @param  mixed  ...$additionalParams  Additional parameters (expects migratePhases array as first param)
     */
    public function delete($id, ...$additionalParams): array
    {
        $migratePhases = $additionalParams[0] ?? [];

        if (! is_array($migratePhases)) {
            throw new InvalidArgumentException(
                'Pipeline deletion expects an array of phase migrations as the second parameter'
            );
        }

        return parent::delete($id, $migratePhases);
    }

    /**
     * Prepare additional data for delete operation
     */
    protected function prepareDeleteData($id, ...$additionalParams): array
    {
        $migratePhases = $additionalParams[0] ?? [];

        return ['migrate_phases' => $migratePhases];
    }

    /**
     * Duplicate an existing deal pipeline
     *
     * @param  string  $id  Source pipeline UUID
     */
    public function duplicate(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.duplicate', [
            'id' => $id,
        ]);
    }

    /**
     * Mark a pipeline as default
     *
     * @param  string  $id  Pipeline UUID
     */
    public function markAsDefault(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.markAsDefault', [
            'id' => $id,
        ]);
    }

    /**
     * Get only open pipelines
     */
    public function open(): array
    {
        return $this->list(['status' => 'open']);
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

        // Handle status filter - API expects array format
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
     * Get pipelines pending deletion
     */
    public function pendingDeletion(): array
    {
        return $this->list(['status' => 'pending_deletion']);
    }

    /**
     * Get pipelines by specific IDs
     *
     * @param  array  $ids  Array of pipeline UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get available status values for filtering
     */
    public function getAvailableStatuses(): array
    {
        return ['open', 'pending_deletion'];
    }

    /**
     * Validate pipeline data
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Remove empty values but keep required fields
        $data = array_filter($data, function ($value, $key) {
            if (in_array($key, ['name', 'id'])) {
                return true; // Keep required fields even if empty for validation
            }

            return $value !== '' && $value !== null && $value !== [];
        }, ARRAY_FILTER_USE_BOTH);

        return $data;
    }

    /**
     * Override getSuggestedIncludes as pipelines don't have common includes
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Deal pipelines don't have sideloadable relationships
    }
}

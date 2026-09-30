<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Pipelines extends Resource
{
    /** `filter.status[]` on dealPipelines.list */
    public const STATUSES = ['open', 'pending_deletion'];

    protected string $description = 'Manage deal pipelines in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsPagination = true;

    protected bool $requestsPaginationMeta = true;

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
        'status' => 'Filter by pipeline status (open, pending_deletion) — a string is wrapped into an array',
        'term' => 'Search the pipeline name',
    ];

    /**
     * List deal pipelines with enhanced filtering and pagination
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Additional options (pagination, includes)
     */
    public function list(array $filters = [], array $options = []): array
    {
        $this->rejectUnsupportedListArguments($filters, $options);

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

        // Request pagination metadata by default, so meta.matches carries a
        // real total count. Both the `include` and `includes` option keys are
        // accepted; either overrides the default.
        $params = $this->applyIncludes(
            $params,
            $this->resolveIncludesOption($options) ?? 'pagination'
        );

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
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
     * Delete a deal pipeline, optionally migrating its phases
     *
     * Deals in the deleted pipeline's phases are moved to the phases named in
     * $migratePhases, each entry being
     * ['old_phase_id' => ..., 'new_phase_id' => ...].
     *
     * @param  string  $id  Pipeline UUID to delete
     * @param  mixed  ...$additionalParams  Expects an array of phase migrations as the first param
     *
     * @throws InvalidArgumentException When the migration argument is not an array
     */
    public function delete($id, ...$additionalParams): array
    {
        $migratePhases = $additionalParams[0] ?? [];

        if (! is_array($migratePhases)) {
            throw new InvalidArgumentException(
                'Pipeline deletion expects an array of phase migrations as the second parameter'
            );
        }

        // Built here rather than delegated. Until v2.2.2 this called
        // parent::delete(), but Resource defines no delete() — so every call
        // raised "Error: Call to undefined method". prepareDeleteData() was
        // written for that delegation and was never reached either.
        $params = ['id' => $id];

        if ($migratePhases !== []) {
            $params['migrate_phases'] = $migratePhases;
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', $params);
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
     *
     * dealPipelines.list accepts `ids`, `status` and — since specification
     * 1.221.0 — `term`. Before v2.2.5 any other key, including `term`, was
     * dropped here without a word.
     *
     * @throws InvalidArgumentException When an unsupported filter key or status is passed
     */
    protected function buildFilters(array $filters): array
    {
        $unknown = array_diff(array_keys($filters), array_keys($this->commonFilters));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key').' for dealPipelines.list: '
                .implode(', ', $unknown).'. Supported: '.implode(', ', array_keys($this->commonFilters)).'.'
            );
        }

        $apiFilters = [];

        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? array_values($filters['ids']) : [$filters['ids']];
        }

        if (isset($filters['status'])) {
            $statuses = is_array($filters['status']) ? array_values($filters['status']) : [$filters['status']];

            foreach ($statuses as $status) {
                if (! in_array($status, self::STATUSES, true)) {
                    throw new InvalidArgumentException(
                        "Invalid pipeline status: {$status}. Must be one of: ".implode(', ', self::STATUSES).'.'
                    );
                }
            }

            $apiFilters['status'] = $statuses;
        }

        if (isset($filters['term']) && $filters['term'] !== '') {
            $apiFilters['term'] = $filters['term'];
        }

        return $apiFilters;
    }

    /**
     * Search pipelines by name
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(['term' => $term], $options);
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
        return self::STATUSES;
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

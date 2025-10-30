<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use DateTime;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class LegacyMilestones extends Resource
{
    protected string $description = 'Manage legacy project milestones in Teamleader Focus';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSorting = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of milestone UUIDs to filter by',
        'project_id' => 'Filter milestones by project UUID',
        'status' => 'Filter by milestone status (open, closed)',
        'due_before' => 'Filter milestones due before date (Y-m-d format)',
        'due_after' => 'Filter milestones due after date (Y-m-d format)',
        'term' => 'Search term - searches in milestone title',
    ];

    // Usage examples specific to milestones
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all milestones',
            'code' => '$milestones = $teamleader->legacyMilestones()->list();',
        ],
        'list_by_project' => [
            'description' => 'Get milestones for a specific project',
            'code' => '$milestones = $teamleader->legacyMilestones()->forProject(\'project-uuid\');',
        ],
        'list_open' => [
            'description' => 'Get only open milestones',
            'code' => '$milestones = $teamleader->legacyMilestones()->list([\'status\' => \'open\']);',
        ],
        'get_single' => [
            'description' => 'Get a single milestone',
            'code' => '$milestone = $teamleader->legacyMilestones()->info(\'milestone-uuid\');',
        ],
        'create_milestone' => [
            'description' => 'Create a new milestone',
            'code' => '$milestone = $teamleader->legacyMilestones()->create([
                \'project_id\' => \'project-uuid\',
                \'name\' => \'Initial setup\',
                \'due_on\' => \'2024-12-31\',
                \'responsible_user_id\' => \'user-uuid\',
                \'billing_method\' => \'time_and_materials\'
            ]);',
        ],
        'update_milestone' => [
            'description' => 'Update a milestone',
            'code' => '$result = $teamleader->legacyMilestones()->update(\'milestone-uuid\', [
                \'name\' => \'Updated name\',
                \'due_on\' => \'2024-12-31\'
            ]);',
        ],
        'close_milestone' => [
            'description' => 'Close a milestone',
            'code' => '$result = $teamleader->legacyMilestones()->close(\'milestone-uuid\');',
        ],
        'open_milestone' => [
            'description' => 'Open/reopen a milestone',
            'code' => '$result = $teamleader->legacyMilestones()->open(\'milestone-uuid\');',
        ],
        'delete_milestone' => [
            'description' => 'Delete a milestone',
            'code' => '$result = $teamleader->legacyMilestones()->delete(\'milestone-uuid\');',
        ],
    ];

    /**
     * Get detailed information about a specific milestone
     *
     * @param  string  $id  Milestone UUID
     * @param  mixed  $includes  Not used for milestones
     */
    public function info($id, $includes = null): array
    {
        $this->validateId($id);

        $params = ['id' => $id];

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Validate ID format
     *
     * @throws InvalidArgumentException
     */
    protected function validateId(string $id): void
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Milestone ID cannot be empty');
        }
    }

    /**
     * Get the base path for the milestones resource
     */
    protected function getBasePath(): string
    {
        return 'milestones';
    }

    /**
     * Create a new milestone
     *
     * @param  array  $data  Milestone data
     *
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Validate create data
     *
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): void
    {
        // Required fields
        $required = ['project_id', 'name', 'due_on', 'responsible_user_id'];

        foreach ($required as $field) {
            if (! isset($data[$field]) || empty($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required for milestone creation");
            }
        }

        // Validate billing_method if provided
        if (isset($data['billing_method'])) {
            $validMethods = ['non_invoiceable', 'time_and_materials', 'fixed_price'];
            if (! in_array($data['billing_method'], $validMethods)) {
                throw new InvalidArgumentException(
                    'Invalid billing_method. Must be one of: '.implode(', ', $validMethods)
                );
            }
        }

        // Validate date format for starts_on
        if (isset($data['starts_on']) && ! $this->isValidDate($data['starts_on'])) {
            throw new InvalidArgumentException('Invalid starts_on date format. Use Y-m-d format.');
        }

        // Validate date format for due_on
        if (! $this->isValidDate($data['due_on'])) {
            throw new InvalidArgumentException('Invalid due_on date format. Use Y-m-d format.');
        }
    }

    /**
     * Check if date is in valid format (Y-m-d)
     */
    protected function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Update an existing milestone
     *
     * @param  string  $id  Milestone UUID
     * @param  array  $data  Data to update
     *
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): array
    {
        $this->validateId($id);

        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a milestone
     *
     * @param  string  $id  Milestone UUID
     * @param  mixed  ...$additionalParams  Not used for legacy milestones
     */
    public function delete($id, ...$additionalParams): array
    {
        $this->validateId($id);

        $params = ['id' => $id];

        return $this->api->request('POST', $this->getBasePath().'.delete', $params);
    }

    /**
     * Close a milestone
     * All open tasks will be closed, open meetings will remain open
     * Closing the last open milestone will also close the project
     *
     * @param  string  $id  Milestone UUID
     */
    public function close(string $id): array
    {
        $this->validateId($id);

        $params = ['id' => $id];

        return $this->api->request('POST', $this->getBasePath().'.close', $params);
    }

    /**
     * (Re)open a milestone
     * If the milestone's project is closed, the project will be reopened
     *
     * @param  string  $id  Milestone UUID
     */
    public function open(string $id): array
    {
        $this->validateId($id);

        $params = ['id' => $id];

        return $this->api->request('POST', $this->getBasePath().'.open', $params);
    }

    /**
     * Get all milestones for a specific project
     *
     * @param  string  $projectId  Project UUID
     * @param  array  $options  Additional options (pagination, sorting)
     */
    public function forProject(string $projectId, array $options = []): array
    {
        return $this->list(['project_id' => $projectId], $options);
    }

    /**
     * List milestones with filtering and sorting
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

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
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
        if (isset($filters['ids'])) {
            $apiFilters['ids'] = is_array($filters['ids']) ? $filters['ids'] : [$filters['ids']];
        }

        // Handle project_id filter
        if (isset($filters['project_id'])) {
            $apiFilters['project_id'] = $filters['project_id'];
        }

        // Handle status filter
        if (isset($filters['status'])) {
            $apiFilters['status'] = $filters['status'];
        }

        // Handle due_before filter
        if (isset($filters['due_before'])) {
            $apiFilters['due_before'] = $filters['due_before'];
        }

        // Handle due_after filter
        if (isset($filters['due_after'])) {
            $apiFilters['due_after'] = $filters['due_after'];
        }

        // Handle term filter (search)
        if (isset($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
        }

        return $apiFilters;
    }

    /**
     * Build sort array for the API request
     * @param array $sort
     * @param string $order
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        $apiSort = [];

        foreach ($sort as $sortItem) {
            $field = $sortItem['field'] ?? 'due_on';
            $order = $sortItem['order'] ?? 'asc';

            // Validate sort field
            if (! in_array($field, ['starts_on', 'due_on'])) {
                $field = 'due_on';
            }

            // Validate sort order
            if (! in_array($order, ['asc', 'desc'])) {
                $order = 'asc';
            }

            $apiSort[] = [
                'field' => $field,
                'order' => $order,
            ];
        }

        return $apiSort;
    }

    /**
     * Get open milestones
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Additional options
     */
    public function getOpen(array $additionalFilters = [], array $options = []): array
    {
        $filters = array_merge(['status' => 'open'], $additionalFilters);

        return $this->list($filters, $options);
    }

    /**
     * Get closed milestones
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Additional options
     */
    public function getClosed(array $additionalFilters = [], array $options = []): array
    {
        $filters = array_merge(['status' => 'closed'], $additionalFilters);

        return $this->list($filters, $options);
    }

    /**
     * Search milestones by term
     *
     * @param  string  $term  Search term
     * @param  array  $options  Additional options
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(['term' => $term], $options);
    }

    /**
     * Get milestones due before a specific date
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  array  $additionalFilters  Additional filters
     * @param  array  $options  Additional options
     */
    public function dueBefore(string $date, array $additionalFilters = [], array $options = []): array
    {
        $filters = array_merge(['due_before' => $date], $additionalFilters);

        return $this->list($filters, $options);
    }

    /**
     * Get milestones due after a specific date
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  array  $additionalFilters  Additional filters
     * @param  array  $options  Additional options
     */
    public function dueAfter(string $date, array $additionalFilters = [], array $options = []): array
    {
        $filters = array_merge(['due_after' => $date], $additionalFilters);

        return $this->list($filters, $options);
    }

    /**
     * Get milestones due within a date range
     *
     * @param  string  $startDate  Start date in Y-m-d format
     * @param  string  $endDate  End date in Y-m-d format
     * @param  array  $additionalFilters  Additional filters
     * @param  array  $options  Additional options
     */
    public function dueBetween(string $startDate, string $endDate, array $additionalFilters = [], array $options = []): array
    {
        $filters = array_merge([
            'due_after' => $startDate,
            'due_before' => $endDate,
        ], $additionalFilters);

        return $this->list($filters, $options);
    }

    /**
     * Get available billing methods
     */
    public function getAvailableBillingMethods(): array
    {
        return [
            'non_invoiceable' => 'Non Invoiceable',
            'time_and_materials' => 'Time and Materials',
            'fixed_price' => 'Fixed Price',
        ];
    }

    /**
     * Get available status values
     */
    public function getAvailableStatuses(): array
    {
        return ['open', 'closed'];
    }

    /**
     * Get available sort fields
     */
    public function getAvailableSortFields(): array
    {
        return ['starts_on', 'due_on'];
    }
}

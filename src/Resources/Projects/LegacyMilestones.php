<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use DateTime;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

/**
 * Milestones of the legacy project system — `milestones.*`.
 *
 * @see LegacyProjects
 */
class LegacyMilestones extends Resource
{
    use ValidatesWritePayload;

    /** Body fields milestones.create accepts */
    public const CREATE_FIELDS = [
        'project_id', 'starts_on', 'due_on', 'name', 'description', 'responsible_user_id',
        'depends_on', 'custom_fields', 'billing_method', 'budget', 'price',
    ];

    /** Body fields milestones.update accepts, besides `id` */
    public const UPDATE_FIELDS = [
        'starts_on', 'due_on', 'name', 'description', 'responsible_user_id', 'depends_on',
        'propagate_date_changes', 'custom_fields',
    ];

    /**
     * milestones.create takes either a budget (non_invoiceable or
     * time_and_materials, the default) or a price (fixed_price).
     */
    public const BILLING_METHODS = ['non_invoiceable', 'time_and_materials', 'fixed_price'];

    /** `filter.status` on milestones.list */
    public const STATUSES = ['open', 'closed'];

    public const CURRENCIES = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP', 'INR', 'ISK',
        'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

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

    /**
     * Sort fields milestones.list accepts. Before v2.2.9 any other field was
     * silently replaced by due_on, and a plain field name was a PHP warning.
     */
    protected array $availableSortFields = [
        'due_on' => 'Due date',
        'starts_on' => 'Start date',
    ];

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
        $this->assertIncludes($includes, [], 'milestones.info');

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

        $this->rejectUnknownFields($data, self::CREATE_FIELDS, 'milestones.create');
        $this->assertEnum($data['billing_method'] ?? null, self::BILLING_METHODS, 'billing_method', 'milestones.create');

        // "With price": fixed_price requires a price and takes no budget.
        // "With budget": the other two take an optional budget and no price.
        if (($data['billing_method'] ?? null) === 'fixed_price') {
            if (empty($data['price'])) {
                throw new InvalidArgumentException('price is required when billing_method is fixed_price');
            }
            if (isset($data['budget'])) {
                throw new InvalidArgumentException('budget is not accepted with billing_method fixed_price; use price');
            }
        } elseif (isset($data['price'])) {
            throw new InvalidArgumentException('price is only accepted with billing_method fixed_price');
        }

        $this->assertMilestoneMoney($data, ['budget', 'price']);
        $this->assertMilestoneDates($data);
    }

    /**
     * @param  list<string>  $fields
     *
     * @throws InvalidArgumentException
     */
    private function assertMilestoneMoney(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (! isset($data[$field])) {
                continue;
            }

            if (! is_array($data[$field]) || ! isset($data[$field]['amount'], $data[$field]['currency'])) {
                throw new InvalidArgumentException("{$field} must be ['amount' => number, 'currency' => code]");
            }

            $this->assertEnum($data[$field]['currency'], self::CURRENCIES, "{$field}.currency", 'milestones.create');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertMilestoneDates(array $data): void
    {
        // starts_on is nullable on both endpoints
        if (isset($data['starts_on']) && ! $this->isValidDate($data['starts_on'])) {
            throw new InvalidArgumentException('Invalid starts_on date format. Use Y-m-d format.');
        }

        if (isset($data['due_on']) && ! $this->isValidDate($data['due_on'])) {
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

        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], 'milestones.update');
        $this->assertMilestoneDates($data);

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
     * List milestones
     *
     * @param  array  $filters  ids, project_id, status (open|closed), due_before, due_after, term
     * @param  array  $options  page_size, page_number, sort (due_on|starts_on), sort_order
     *
     * @throws InvalidArgumentException On an unknown filter key, option, value or sort field
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknownOptions = array_diff(array_keys($options), ['page_size', 'page_number', 'sort', 'sort_order']);

        if ($unknownOptions !== []) {
            throw new InvalidArgumentException(
                'milestones.list does not support: '.implode(', ', $unknownOptions)
                .'. Supported: page_size, page_number, sort, sort_order.'
            );
        }

        $params = [];

        if (! empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => (int) ($options['page_size'] ?? 20),
                'number' => (int) ($options['page_number'] ?? 1),
            ];
        }

        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'asc');
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build the filter object for milestones.list
     *
     * @throws InvalidArgumentException On an unknown key or value
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'milestones.list');
        $this->assertEnum($filters['status'] ?? null, self::STATUSES, 'filter.status', 'milestones.list');

        foreach (['due_before', 'due_after'] as $field) {
            if (isset($filters[$field]) && ! $this->isValidDate($filters[$field])) {
                throw new InvalidArgumentException("{$field} must be a date in Y-m-d format.");
            }
        }

        if (isset($filters['ids']) && ! is_array($filters['ids'])) {
            $filters['ids'] = [$filters['ids']];
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Build the sort array for milestones.list
     *
     * Accepts a field name, a list of names, ['field' => ..., 'order' => ...]
     * or a list of those.
     *
     * @param  array|string  $sort
     *
     * @throws InvalidArgumentException On an unknown field or order
     */
    protected function buildSort($sort, string $order = 'asc'): array
    {
        return $this->normaliseSort($sort, $order);
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
        return self::STATUSES;
    }

    /**
     * Get available sort fields
     */
    public function getAvailableSortFields(): array
    {
        return array_keys($this->availableSortFields);
    }
}

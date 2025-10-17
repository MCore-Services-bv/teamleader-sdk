<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class PaymentMethods extends Resource
{
    protected string $description = 'Manage payment methods in Teamleader Focus';

    // Resource capabilities - Payment methods are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of payment method UUIDs',
        'status' => 'Array of statuses (active, archived)',
    ];

    // Valid status values
    protected array $validStatuses = [
        'active',
        'archived',
    ];

    // Usage examples specific to payment methods
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all payment methods',
            'code' => '$paymentMethods = $teamleader->paymentMethods()->list();',
        ],
        'filter_by_ids' => [
            'description' => 'Get specific payment methods by IDs',
            'code' => '$paymentMethods = $teamleader->paymentMethods()->byIds([\'uuid1\', \'uuid2\']);',
        ],
        'get_active' => [
            'description' => 'Get only active payment methods',
            'code' => '$paymentMethods = $teamleader->paymentMethods()->active();',
        ],
        'get_archived' => [
            'description' => 'Get only archived payment methods',
            'code' => '$paymentMethods = $teamleader->paymentMethods()->archived();',
        ],
        'find_by_name' => [
            'description' => 'Find a payment method by name',
            'code' => '$method = $teamleader->paymentMethods()->findByName("Credit Card");',
        ],
        'as_options' => [
            'description' => 'Get payment methods as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->paymentMethods()->asOptions();',
        ],
    ];

    /**
     * Get the base path for the payment methods resource
     */
    protected function getBasePath(): string
    {
        return 'paymentMethods';
    }

    /**
     * List payment methods with filtering and pagination
     *
     * @param  array  $filters  Filter parameters
     * @param  array  $options  Pagination options
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

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get active payment methods
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination options
     */
    public function active(array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['active']], $additionalFilters),
            $options
        );
    }

    /**
     * Get archived payment methods
     *
     * @param  array  $additionalFilters  Additional filters to apply
     * @param  array  $options  Pagination options
     */
    public function archived(array $additionalFilters = [], array $options = []): array
    {
        return $this->list(
            array_merge(['status' => ['archived']], $additionalFilters),
            $options
        );
    }

    /**
     * Get payment methods by specific IDs
     *
     * @param  array  $ids  Array of payment method UUIDs
     * @param  array  $options  Pagination options
     */
    public function byIds(array $ids, array $options = []): array
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('At least one payment method ID is required');
        }

        return $this->list(['ids' => $ids], $options);
    }

    /**
     * Find a payment method by name
     *
     * @param  string  $name  Payment method name to search for
     * @param  bool  $activeOnly  Whether to search only active payment methods
     * @return array|null Payment method data or null if not found
     */
    public function findByName(string $name, bool $activeOnly = true): ?array
    {
        $filters = $activeOnly ? ['status' => ['active']] : [];
        $result = $this->list($filters);

        if (empty($result['data'])) {
            return null;
        }

        foreach ($result['data'] as $method) {
            if (strcasecmp($method['name'], $name) === 0) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Check if a payment method exists by ID
     *
     * @param  string  $id  Payment method UUID
     */
    public function exists(string $id): bool
    {
        try {
            $result = $this->byIds([$id]);

            return ! empty($result['data']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all payment methods (convenience method that handles pagination)
     *
     * @param  array  $filters  Filters to apply
     * @param  int  $maxPages  Maximum number of pages to fetch (default: 10)
     * @return array All payment methods
     */
    public function all(array $filters = [], int $maxPages = 10): array
    {
        $allMethods = [];
        $page = 1;

        do {
            $result = $this->list($filters, [
                'page_size' => 100,
                'page_number' => $page,
            ]);

            if (! empty($result['data'])) {
                $allMethods = array_merge($allMethods, $result['data']);
            }

            $hasMore = ! empty($result['data']) && count($result['data']) === 100;
            $page++;
        } while ($hasMore && $page <= $maxPages);

        return ['data' => $allMethods];
    }

    /**
     * Get payment methods formatted as options for select dropdowns
     *
     * @param  bool  $activeOnly  Whether to include only active payment methods
     * @return array Array with id => name pairs
     */
    public function asOptions(bool $activeOnly = true): array
    {
        $filters = $activeOnly ? ['status' => ['active']] : [];
        $result = $this->all($filters);

        $options = [];
        foreach ($result['data'] as $method) {
            $options[$method['id']] = $method['name'];
        }

        return $options;
    }

    /**
     * Validate status values
     *
     * @throws InvalidArgumentException
     */
    private function validateStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            if (! in_array($status, $this->validStatuses)) {
                throw new InvalidArgumentException(
                    "Invalid status '{$status}'. Must be one of: ".
                    implode(', ', $this->validStatuses)
                );
            }
        }
    }

    /**
     * Build filters for the API request
     */
    protected function buildFilters(array $filters): array
    {
        $built = [];

        foreach ($filters as $key => $value) {
            // Validate status values
            if ($key === 'status' && is_array($value)) {
                $this->validateStatuses($value);
            }

            $built[$key] = $value;
        }

        return $built;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of payment methods',
                'fields' => [
                    'data' => 'Array of payment method objects',
                    'data[].id' => 'Payment method UUID',
                    'data[].name' => 'Payment method name',
                    'data[].status' => 'Status (active or archived)',
                ],
            ],
        ];
    }
}

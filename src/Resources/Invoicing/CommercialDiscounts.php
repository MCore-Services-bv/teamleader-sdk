<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use McoreServices\TeamleaderSDK\Resources\Resource;

class CommercialDiscounts extends Resource
{
    protected string $description = 'Manage commercial discounts in Teamleader Focus';

    // Resource capabilities - Commercial discounts are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'department_id' => 'Filter by department UUID',
    ];

    // Usage examples specific to commercial discounts
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all commercial discounts',
            'code' => '$discounts = $teamleader->commercialDiscounts()->list();',
        ],
        'filter_by_department' => [
            'description' => 'Get commercial discounts for a specific department',
            'code' => '$discounts = $teamleader->commercialDiscounts()->forDepartment(\'department-uuid\');',
        ],
        'find_by_name' => [
            'description' => 'Find a commercial discount by name',
            'code' => '$discount = $teamleader->commercialDiscounts()->findByName("Holiday discount");',
        ],
        'as_options' => [
            'description' => 'Get commercial discounts as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->commercialDiscounts()->asOptions();',
        ],
    ];

    /**
     * Get the base path for the commercial discounts resource
     */
    protected function getBasePath(): string
    {
        return 'commercialDiscounts';
    }

    /**
     * List commercial discounts with optional filtering
     *
     * @param  array  $filters  Filter parameters
     * @param  array  $options  Not used for commercial discounts
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
     * Get commercial discounts for a specific department
     *
     * @param  string  $departmentId  Department UUID
     */
    public function forDepartment(string $departmentId): array
    {
        return $this->list(['department_id' => $departmentId]);
    }

    /**
     * Find a commercial discount by name
     *
     * @param  string  $name  Discount name to search for
     * @param  string|null  $departmentId  Optional department filter
     * @param  bool  $exactMatch  Whether to match exactly or search partial
     * @return array|null Commercial discount data or null if not found
     */
    public function findByName(string $name, ?string $departmentId = null, bool $exactMatch = true): ?array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->list($filters);

        if (empty($result['data'])) {
            return null;
        }

        foreach ($result['data'] as $discount) {
            if ($exactMatch) {
                if (strcasecmp($discount['name'], $name) === 0) {
                    return $discount;
                }
            } else {
                if (stripos($discount['name'], $name) !== false) {
                    return $discount;
                }
            }
        }

        return null;
    }

    /**
     * Get commercial discounts formatted as options for select dropdowns
     *
     * @param  string|null  $departmentId  Optional department filter
     * @return array Array with name as both key and value (no ID in response)
     */
    public function asOptions(?string $departmentId = null): array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->list($filters);

        $options = [];
        foreach ($result['data'] as $discount) {
            // Note: API doesn't return ID, so we use name as key
            $options[$discount['name']] = $discount['name'];
        }

        return $options;
    }

    /**
     * Get commercial discounts grouped by department
     *
     * @return array Discounts grouped by department ID
     */
    public function groupedByDepartment(): array
    {
        $result = $this->list();
        $grouped = [];

        if (empty($result['data'])) {
            return $grouped;
        }

        foreach ($result['data'] as $discount) {
            $departmentId = $discount['department']['id'];
            if (! isset($grouped[$departmentId])) {
                $grouped[$departmentId] = [
                    'department' => $discount['department'],
                    'discounts' => [],
                ];
            }
            $grouped[$departmentId]['discounts'][] = $discount;
        }

        return $grouped;
    }

    /**
     * Check if a commercial discount exists by name
     *
     * @param  string  $name  Discount name
     * @param  string|null  $departmentId  Optional department filter
     */
    public function exists(string $name, ?string $departmentId = null): bool
    {
        return $this->findByName($name, $departmentId) !== null;
    }

    /**
     * Search for commercial discounts by partial name match
     *
     * @param  string  $searchTerm  Search term to match against discount names
     * @param  string|null  $departmentId  Optional department filter
     * @return array Array of matching discounts
     */
    public function search(string $searchTerm, ?string $departmentId = null): array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->list($filters);

        if (empty($result['data'])) {
            return [];
        }

        $matches = [];
        foreach ($result['data'] as $discount) {
            if (stripos($discount['name'], $searchTerm) !== false) {
                $matches[] = $discount;
            }
        }

        return $matches;
    }

    /**
     * Get all discount names as a simple array
     *
     * @param  string|null  $departmentId  Optional department filter
     * @return array Array of discount names
     */
    public function names(?string $departmentId = null): array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->list($filters);

        $names = [];
        foreach ($result['data'] as $discount) {
            $names[] = $discount['name'];
        }

        return $names;
    }

    /**
     * Build filters for the API request
     */
    protected function buildFilters(array $filters): array
    {
        return $filters;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of commercial discounts',
                'fields' => [
                    'data' => 'Array of commercial discount objects',
                    'data[].name' => 'Discount name',
                    'data[].department' => 'Department reference object',
                    'data[].department.id' => 'Department UUID',
                    'data[].department.type' => 'Resource type ("department")',
                ],
            ],
        ];
    }
}

<?php

namespace McoreServices\TeamleaderSDK\Resources\Products;

use McoreServices\TeamleaderSDK\Resources\Resource;

class Categories extends Resource
{
    protected string $description = 'Manage product categories in Teamleader Focus';

    // Resource capabilities - Product Categories are read-only based on API docs
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
        'department_id' => 'The ID of the department to filter on (UUID format)',
    ];

    // Usage examples specific to product categories
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all product categories',
            'code' => '$categories = $teamleader->productCategories()->list();'
        ],
        'filter_by_department' => [
            'description' => 'Get product categories for specific department',
            'code' => '$categories = $teamleader->productCategories()->forDepartment("080aac72-ff1a-4627-bfe3-146b6eee979c");'
        ],
        'list_with_options' => [
            'description' => 'Get categories with custom filtering',
            'code' => '$categories = $teamleader->productCategories()->list([
                "department_id" => "department-uuid-here"
            ]);'
        ]
    ];

    /**
     * Get the base path for the product categories resource
     */
    protected function getBasePath(): string
    {
        return 'productCategories';
    }

    /**
     * List product categories with filtering
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (not used for this endpoint)
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get product categories for a specific department
     *
     * @param string $departmentId Department UUID
     * @return array
     */
    public function forDepartment(string $departmentId): array
    {
        return $this->list(['department_id' => $departmentId]);
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters
     * @return array
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle department_id filter
        if (isset($filters['department_id'])) {
            $apiFilters['department_id'] = $filters['department_id'];
        }

        return $apiFilters;
    }

    /**
     * Get response structure documentation
     *
     * @return array
     */
    public function getResponseStructure(): array
    {
        return [
            'data' => [
                'description' => 'Array of product categories',
                'type' => 'array',
                'items' => [
                    'id' => 'Category UUID (e.g., "2aa4a6a9-9ce8-4851-a9b3-26aea2ea14c4")',
                    'name' => 'Category name (e.g., "Asian Flowers")',
                    'ledgers' => 'Array of ledger information with department details'
                ]
            ]
        ];
    }

    /**
     * Override validation since product categories are read-only
     *
     * @param array $data
     * @param string $operation
     * @return array
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        // Product categories are read-only, no validation needed
        return $data;
    }

    /**
     * Override getSuggestedIncludes as categories don't have includes
     *
     * @return array
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Product categories don't have sideloadable relationships
    }

    /**
     * Get available filters for product categories
     *
     * @return array
     */
    public function getAvailableFilters(): array
    {
        return [
            'department_id' => 'Filter by department UUID (string)'
        ];
    }
}

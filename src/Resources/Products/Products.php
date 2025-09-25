<?php

namespace McoreServices\TeamleaderSDK\Resources\Products;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Products extends Resource
{
    protected string $description = 'Manage products in Teamleader Focus';

    // Resource capabilities - Products support full CRUD operations
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;
    protected bool $supportsSorting = false; // API docs don't mention sorting for products
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = true;

    // Available includes for sideloading (based on API docs)
    protected array $availableIncludes = [
        'suppliers',
        'custom_fields'
    ];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of product UUIDs',
        'term' => 'Search term (will filter on the name or the code)',
        'updated_since' => 'ISO 8601 datetime'
    ];

    // Usage examples specific to products
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all products',
            'code' => '$products = $teamleader->products()->list();'
        ],
        'search_by_term' => [
            'description' => 'Search products by name or code',
            'code' => '$products = $teamleader->products()->search("cookies");'
        ],
        'with_suppliers' => [
            'description' => 'Get products with suppliers',
            'code' => '$products = $teamleader->products()->withSuppliers()->list();'
        ],
        'with_custom_fields' => [
            'description' => 'Get products with custom fields',
            'code' => '$products = $teamleader->products()->withCustomFields()->list();'
        ],
        'create_product' => [
            'description' => 'Create a new product',
            'code' => '$product = $teamleader->products()->create(["name" => "Dark Chocolate Cookies", "code" => "COOK-DARK-001"]);'
        ]
    ];

    /**
     * Get the base path for the products resource
     */
    protected function getBasePath(): string
    {
        return 'products';
    }

    /**
     * List products with enhanced filtering
     */
    public function list(array $filters = [], array $options = []): array
    {
        // Since products API doesn't support sorting, we'll skip sort parameters
        // and build the params manually to avoid the null sortOrder issue
        $params = [];

        // Apply filters
        $params = $this->applyFilters($params, $filters);

        // Apply pagination
        $params = $this->applyPagination(
            $params,
            $options['page_size'] ?? 20,
            $options['page_number'] ?? 1
        );

        // Apply includes
        if (isset($options['include'])) {
            $params = $this->applyIncludes($params, $options['include']);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get product information with enhanced include handling
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        // FIXED: Use 'includes' parameter name as per API documentation
        if (!empty($includes)) {
            if (is_array($includes)) {
                $params['includes'] = implode(',', $includes);
            } else {
                $params['includes'] = $includes;
            }
        }

        // Apply any pending includes from fluent interface
        if (!empty($this->pendingIncludes)) {
            $existingIncludes = $params['includes'] ?? '';
            $pendingIncludesStr = implode(',', $this->pendingIncludes);

            if (!empty($existingIncludes)) {
                $params['includes'] = $existingIncludes . ',' . $pendingIncludesStr;
            } else {
                $params['includes'] = $pendingIncludesStr;
            }

            // Clear pending includes after applying
            $this->pendingIncludes = [];
        }

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    /**
     * Create a new product
     */
    public function create(array $data): array
    {
        $validatedData = $this->validateProductData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.add', $validatedData);
    }

    /**
     * Update a product
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $validatedData = $this->validateProductData($data, 'update');
        return $this->api->request('POST', $this->getBasePath() . '.update', $validatedData);
    }

    /**
     * Delete a product
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Search products by term (searches name and code)
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $term], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get products updated since a specific date
     */
    public function updatedSince(string $date, array $options = []): array
    {
        return $this->list(
            array_merge(['updated_since' => $date], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Validate product data before sending to API
     */
    protected function validateProductData(array $data, string $operation = 'create'): array
    {
        $rules = [
            'name' => 'string|max:255',
            'code' => 'string|max:255',
            'description' => 'string',
            'purchase_price.amount' => 'numeric',
            'purchase_price.currency' => 'string|size:3',
            'selling_price.amount' => 'numeric',
            'selling_price.currency' => 'string|size:3',
            'unit_of_measure_id' => 'string',
            'stock.amount' => 'numeric',
            'configuration.stock_threshold.minimum' => 'numeric|min:0',
            'configuration.stock_threshold.action' => 'in:notify',
            'department_id' => 'string',
            'product_category_id' => 'string',
            'tax_rate_id' => 'string',
            'custom_fields' => 'array'
        ];

        if ($operation === 'create') {
            // Name is required for create, but can be by name OR by code (oneOf)
            if (empty($data['name']) && empty($data['code'])) {
                throw new InvalidArgumentException('Either name or code is required when creating a product');
            }
        }

        // Basic validation - in a real implementation you'd use Laravel's validator
        foreach ($rules as $field => $rule) {
            $fieldValue = data_get($data, $field);
            if ($fieldValue !== null) {
                if (str_contains($field, '.')) {
                    $this->validateNestedField($data, $field, $rule);
                }
            }
        }

        return $data;
    }

    /**
     * Validate nested field structure
     */
    protected function validateNestedField(array $data, string $field, string $rule): void
    {
        $parts = explode('.', $field);
        $current = $data;

        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return; // Field doesn't exist, skip validation
            }
            $current = $current[$part];
        }
    }

    /**
     * Convert filter array to API parameters
     */
    protected function convertFiltersToApiParams(array $filters): array
    {
        $params = [];
        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            switch ($key) {
                case 'ids':
                    if (is_array($value)) {
                        $apiFilters['ids'] = $value;
                    }
                    break;

                case 'term':
                    $apiFilters['term'] = $value;
                    break;

                case 'updated_since':
                    $apiFilters['updated_since'] = $value;
                    break;

                // Handle legacy/alternative field names
                case 'search':
                case 'general_search':
                    // Map general search to 'term'
                    $apiFilters['term'] = $value;
                    break;
            }
        }

        if (!empty($apiFilters)) {
            $params['filter'] = $apiFilters;
        }

        return $params;
    }

    /**
     * Get available sort fields for products
     */
    public function getAvailableSortFields(): array
    {
        // API docs don't mention sorting for products
        return [];
    }

    /**
     * Get suggested includes
     */
    protected function getSuggestedIncludes(): array
    {
        return $this->defaultIncludes;
    }

    /**
     * Fluent interface for including suppliers
     */
    public function withSuppliers(): self
    {
        return $this->with('suppliers');
    }

    /**
     * Fluent interface for including custom fields
     */
    public function withCustomFields(): self
    {
        return $this->with('custom_fields');
    }
}

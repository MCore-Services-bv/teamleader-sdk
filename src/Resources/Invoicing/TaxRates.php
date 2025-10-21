<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class TaxRates extends Resource
{
    protected string $description = 'Manage tax rates in Teamleader Focus';

    // Resource capabilities - Tax rates are read-only
    protected bool $supportsCreation = false;

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

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

    // Available sort fields
    protected array $availableSortFields = [
        'department_id',
        'rate',
        'description',
    ];

    // Usage examples specific to tax rates
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all tax rates',
            'code' => '$taxRates = $teamleader->taxRates()->list();',
        ],
        'filter_by_department' => [
            'description' => 'Get tax rates for a specific department',
            'code' => '$taxRates = $teamleader->taxRates()->forDepartment(\'department-uuid\');',
        ],
        'sort_by_rate' => [
            'description' => 'Get tax rates sorted by rate',
            'code' => '$taxRates = $teamleader->taxRates()->list([], [\'sort\' => [[\'field\' => \'rate\', \'order\' => \'asc\']]]);',
        ],
        'find_by_rate' => [
            'description' => 'Find tax rate by exact rate value',
            'code' => '$taxRate = $teamleader->taxRates()->findByRate(0.21);',
        ],
        'find_by_description' => [
            'description' => 'Find tax rate by description',
            'code' => '$taxRate = $teamleader->taxRates()->findByDescription("21%");',
        ],
        'as_options' => [
            'description' => 'Get tax rates as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->taxRates()->asOptions();',
        ],
    ];

    /**
     * Get the base path for the tax rates resource
     */
    protected function getBasePath(): string
    {
        return 'taxRates';
    }

    /**
     * List tax rates with filtering, sorting, and pagination
     *
     * @param  array  $filters  Filter parameters
     * @param  array  $options  Pagination and sorting options
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
     * Get tax rates for a specific department
     *
     * @param  string  $departmentId  Department UUID
     * @param  array  $options  Pagination and sorting options
     */
    public function forDepartment(string $departmentId, array $options = []): array
    {
        return $this->list(['department_id' => $departmentId], $options);
    }

    /**
     * Find a tax rate by ID
     *
     * @param  string  $id  Tax rate UUID
     * @return array|null Tax rate data or null if not found
     */
    public function find(string $id): ?array
    {
        // Get all tax rates (since there's no info endpoint)
        $result = $this->all();

        foreach ($result['data'] as $taxRate) {
            if ($taxRate['id'] === $id) {
                return $taxRate;
            }
        }

        return null;
    }

    /**
     * Find a tax rate by exact rate value
     *
     * @param  float  $rate  Tax rate (e.g., 0.21 for 21%)
     * @param  string|null  $departmentId  Optional department filter
     * @return array|null Tax rate data or null if not found
     */
    public function findByRate(float $rate, ?string $departmentId = null): ?array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->list($filters);

        foreach ($result['data'] as $taxRate) {
            if (abs($taxRate['rate'] - $rate) < 0.0001) { // Float comparison with tolerance
                return $taxRate;
            }
        }

        return null;
    }

    /**
     * Find tax rates by rate range
     *
     * @param  float  $minRate  Minimum rate (inclusive)
     * @param  float  $maxRate  Maximum rate (inclusive)
     * @param  string|null  $departmentId  Optional department filter
     * @return array Array of matching tax rates
     */
    public function findByRateRange(float $minRate, float $maxRate, ?string $departmentId = null): array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->all($filters);

        $matches = [];
        foreach ($result['data'] as $taxRate) {
            if ($taxRate['rate'] >= $minRate && $taxRate['rate'] <= $maxRate) {
                $matches[] = $taxRate;
            }
        }

        return $matches;
    }

    /**
     * Find a tax rate by description
     *
     * @param  string  $description  Tax rate description (e.g., "21%")
     * @param  string|null  $departmentId  Optional department filter
     * @param  bool  $exactMatch  Whether to match exactly or search partial
     * @return array|null Tax rate data or null if not found
     */
    public function findByDescription(string $description, ?string $departmentId = null, bool $exactMatch = true): ?array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->list($filters);

        foreach ($result['data'] as $taxRate) {
            if ($exactMatch) {
                if (strcasecmp($taxRate['description'], $description) === 0) {
                    return $taxRate;
                }
            } else {
                if (stripos($taxRate['description'], $description) !== false) {
                    return $taxRate;
                }
            }
        }

        return null;
    }

    /**
     * Check if a tax rate exists by ID
     *
     * @param  string  $id  Tax rate UUID
     */
    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Get all tax rates (handles pagination automatically)
     *
     * @param  array  $filters  Filters to apply
     * @param  int  $maxPages  Maximum number of pages to fetch (default: 10)
     * @return array All tax rates
     */
    public function all(array $filters = [], int $maxPages = 10): array
    {
        $allRates = [];
        $page = 1;

        do {
            $result = $this->list($filters, [
                'page_size' => 100,
                'page_number' => $page,
            ]);

            if (! empty($result['data'])) {
                $allRates = array_merge($allRates, $result['data']);
            }

            $hasMore = ! empty($result['data']) && count($result['data']) === 100;
            $page++;
        } while ($hasMore && $page <= $maxPages);

        return ['data' => $allRates];
    }

    /**
     * Get tax rates formatted as options for select dropdowns
     *
     * @param  string|null  $departmentId  Optional department filter
     * @return array Array with id => description pairs
     */
    public function asOptions(?string $departmentId = null): array
    {
        $filters = $departmentId ? ['department_id' => $departmentId] : [];
        $result = $this->all($filters);

        $options = [];
        foreach ($result['data'] as $taxRate) {
            $options[$taxRate['id']] = $taxRate['description'];
        }

        return $options;
    }

    /**
     * Get tax rates grouped by department
     *
     * @return array Tax rates grouped by department ID
     */
    public function groupedByDepartment(): array
    {
        $result = $this->all();
        $grouped = [];

        foreach ($result['data'] as $taxRate) {
            $departmentId = $taxRate['department']['id'];
            if (! isset($grouped[$departmentId])) {
                $grouped[$departmentId] = [
                    'department' => $taxRate['department'],
                    'tax_rates' => [],
                ];
            }
            $grouped[$departmentId]['tax_rates'][] = $taxRate;
        }

        return $grouped;
    }

    /**
     * Get tax rates sorted by rate ascending
     *
     * @param  array  $filters  Additional filters
     */
    public function sortedByRate(array $filters = []): array
    {
        return $this->list($filters, [
            'sort' => [
                ['field' => 'rate', 'order' => 'asc'],
            ],
        ]);
    }

    /**
     * Get tax rates sorted by description
     *
     * @param  array  $filters  Additional filters
     * @param  string  $order  Sort order (asc or desc)
     */
    public function sortedByDescription(array $filters = [], string $order = 'asc'): array
    {
        return $this->list($filters, [
            'sort' => [
                ['field' => 'description', 'order' => $order],
            ],
        ]);
    }

    /**
     * Build filters for the API request
     */
    protected function buildFilters(array $filters): array
    {
        return $filters;
    }

    /**
     * Build sort parameters for the API request
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        // Validate sort fields
        foreach ($sort as $sortItem) {
            if (isset($sortItem['field']) && ! in_array($sortItem['field'], $this->availableSortFields)) {
                throw new InvalidArgumentException(
                    "Invalid sort field '{$sortItem['field']}'. Must be one of: ".
                    implode(', ', $this->availableSortFields)
                );
            }
        }

        return $sort;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of tax rates',
                'fields' => [
                    'data' => 'Array of tax rate objects',
                    'data[].id' => 'Tax rate UUID',
                    'data[].description' => 'Tax rate description (e.g., "21%")',
                    'data[].rate' => 'Tax rate as decimal (e.g., 0.21 for 21%)',
                    'data[].department' => 'Department reference object',
                    'data[].department.id' => 'Department UUID',
                    'data[].department.type' => 'Resource type ("department")',
                ],
            ],
        ];
    }
}

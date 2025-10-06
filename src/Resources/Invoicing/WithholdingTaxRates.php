<?php

namespace McoreServices\TeamleaderSDK\Resources\Invoicing;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class WithholdingTaxRates extends Resource
{
    protected string $description = 'Manage withholding tax rates in Teamleader Focus';

    // Resource capabilities - Withholding tax rates are read-only
    protected bool $supportsCreation = false;
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters - department_id appears to be optional based on docs
    protected array $commonFilters = [];

    // Usage examples specific to withholding tax rates
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all withholding tax rates',
            'code' => '$rates = $teamleader->withholdingTaxRates()->list();'
        ],
        'find_by_rate' => [
            'description' => 'Find withholding tax rate by exact rate value',
            'code' => '$rate = $teamleader->withholdingTaxRates()->findByRate(0.15);'
        ],
        'find_by_description' => [
            'description' => 'Find withholding tax rate by description',
            'code' => '$rate = $teamleader->withholdingTaxRates()->findByDescription("15%");'
        ],
        'as_options' => [
            'description' => 'Get withholding tax rates as key-value pairs for dropdowns',
            'code' => '$options = $teamleader->withholdingTaxRates()->asOptions();'
        ],
    ];

    /**
     * Get the base path for the withholding tax rates resource
     */
    protected function getBasePath(): string
    {
        return 'withholdingTaxRates';
    }

    /**
     * List all withholding tax rates
     *
     * @param array $filters Not used for withholding tax rates
     * @param array $options Not used for withholding tax rates
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.list', ['filter' => (object)[]]);
    }

    /**
     * Find a withholding tax rate by ID
     *
     * @param string $id Withholding tax rate UUID
     * @return array|null Withholding tax rate data or null if not found
     */
    public function find(string $id): ?array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return null;
        }

        foreach ($result['data'] as $rate) {
            if ($rate['id'] === $id) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Find a withholding tax rate by exact rate value
     *
     * @param float $rate Withholding tax rate (e.g., 0.15 for 15%)
     * @return array|null Withholding tax rate data or null if not found
     */
    public function findByRate(float $rate): ?array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return null;
        }

        foreach ($result['data'] as $taxRate) {
            if (abs($taxRate['rate'] - $rate) < 0.0001) { // Float comparison with tolerance
                return $taxRate;
            }
        }

        return null;
    }

    /**
     * Find withholding tax rates by rate range
     *
     * @param float $minRate Minimum rate (inclusive)
     * @param float $maxRate Maximum rate (inclusive)
     * @return array Array of matching withholding tax rates
     */
    public function findByRateRange(float $minRate, float $maxRate): array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return [];
        }

        $matches = [];
        foreach ($result['data'] as $rate) {
            if ($rate['rate'] >= $minRate && $rate['rate'] <= $maxRate) {
                $matches[] = $rate;
            }
        }

        return $matches;
    }

    /**
     * Find a withholding tax rate by description
     *
     * @param string $description Withholding tax rate description (e.g., "15%")
     * @param bool $exactMatch Whether to match exactly or search partial
     * @return array|null Withholding tax rate data or null if not found
     */
    public function findByDescription(string $description, bool $exactMatch = true): ?array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return null;
        }

        foreach ($result['data'] as $rate) {
            if ($exactMatch) {
                if (strcasecmp($rate['description'], $description) === 0) {
                    return $rate;
                }
            } else {
                if (stripos($rate['description'], $description) !== false) {
                    return $rate;
                }
            }
        }

        return null;
    }

    /**
     * Check if a withholding tax rate exists by ID
     *
     * @param string $id Withholding tax rate UUID
     * @return bool
     */
    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Get withholding tax rates formatted as options for select dropdowns
     *
     * @return array Array with id => description pairs
     */
    public function asOptions(): array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return [];
        }

        $options = [];
        foreach ($result['data'] as $rate) {
            $options[$rate['id']] = $rate['description'];
        }

        return $options;
    }

    /**
     * Get withholding tax rates grouped by department
     *
     * @return array Withholding tax rates grouped by department ID
     */
    public function groupedByDepartment(): array
    {
        $result = $this->list();
        $grouped = [];

        if (empty($result['data'])) {
            return $grouped;
        }

        foreach ($result['data'] as $rate) {
            $departmentId = $rate['department']['id'];
            if (!isset($grouped[$departmentId])) {
                $grouped[$departmentId] = [
                    'department' => $rate['department'],
                    'withholding_tax_rates' => []
                ];
            }
            $grouped[$departmentId]['withholding_tax_rates'][] = $rate;
        }

        return $grouped;
    }

    /**
     * Get withholding tax rates sorted by rate ascending
     *
     * @return array
     */
    public function sortedByRate(): array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return $result;
        }

        $data = $result['data'];
        usort($data, function ($a, $b) {
            return $a['rate'] <=> $b['rate'];
        });

        return ['data' => $data];
    }

    /**
     * Get withholding tax rates sorted by description
     *
     * @param string $order Sort order (asc or desc)
     * @return array
     */
    public function sortedByDescription(string $order = 'asc'): array
    {
        $result = $this->list();

        if (empty($result['data'])) {
            return $result;
        }

        $data = $result['data'];
        usort($data, function ($a, $b) use ($order) {
            $comparison = strcasecmp($a['description'], $b['description']);
            return $order === 'desc' ? -$comparison : $comparison;
        });

        return ['data' => $data];
    }

    /**
     * Format a withholding tax rate as a human-readable string
     *
     * @param array $rate Withholding tax rate data
     * @return string Formatted string
     */
    public function format(array $rate): string
    {
        $percentage = ($rate['rate'] * 100);
        return "{$rate['description']} ({$percentage}%)";
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of withholding tax rates',
                'fields' => [
                    'data' => 'Array of withholding tax rate objects',
                    'data[].id' => 'Withholding tax rate UUID',
                    'data[].description' => 'Withholding tax rate description (e.g., "21%")',
                    'data[].rate' => 'Withholding tax rate as decimal (e.g., 0.21 for 21%)',
                    'data[].department' => 'Department reference object',
                    'data[].department.id' => 'Department UUID',
                    'data[].department.type' => 'Resource type ("department")',
                ]
            ]
        ];
    }
}

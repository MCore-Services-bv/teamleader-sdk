<?php

namespace McoreServices\TeamleaderSDK\Traits;

trait FilterTrait
{
    /**
     * Apply filters to the parameters.
     */
    protected function applyFilters(array $params = [], array $filters = [])
    {
        // Remove null or empty array values to avoid invalid filters
        $filters = array_filter($filters, function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }

            return $value !== null && $value !== '';
        });

        if (!empty($filters)) {
            $params['filter'] = $filters;
        }

        return $params;
    }

    /**
     * Apply sorting to the parameters.
     */
    protected function applySorting(array $params = [], $sort = null, $order = 'asc')
    {
        if (empty($sort)) {
            return $params;
        }

        // If sort is already a configured array, use it directly
        if (is_array($sort) && isset($sort[0]) && is_array($sort[0])) {
            $params['sort'] = $sort;

            return $params;
        }

        // If sort is a single field or array of fields
        $sortConfig = [];

        if (is_array($sort)) {
            foreach ($sort as $field) {
                $sortConfig[] = [
                    'field' => $field,
                    'order' => $order,
                ];
            }
        } else {
            $sortConfig[] = [
                'field' => $sort,
                'order' => $order,
            ];
        }

        $params['sort'] = $sortConfig;

        return $params;
    }

    /**
     * Apply pagination to the parameters.
     */
    protected function applyPagination(array $params = [], $size = 20, $number = 1)
    {
        $params['page'] = [
            'size' => (int) $size,
            'number' => (int) $number,
        ];

        return $params;
    }

    /**
     * Apply includes for sideloading related resources (enhanced version).
     */
    protected function applyIncludes(array $params = [], $includes = null)
    {
        if (!empty($includes)) {
            if (is_array($includes)) {
                // Filter out empty includes and join with comma
                $validIncludes = array_filter($includes, function ($include) {
                    return !empty($include) && is_string($include);
                });

                if (!empty($validIncludes)) {
                    $params['include'] = implode(',', $validIncludes);
                }
            } elseif (is_string($includes)) {
                $params['include'] = $includes;
            }
        }

        return $params;
    }

    /**
     * Get pending includes that were set via fluent interface
     */
    protected function getPendingIncludes(): array
    {
        return $this->pendingIncludes ?? [];
    }

    /**
     * Apply pending includes to parameters and clear them
     */
    protected function applyPendingIncludes(array $params = []): array
    {
        $pendingIncludes = $this->getPendingIncludes();

        if (!empty($pendingIncludes)) {
            $params = $this->applyIncludes($params, $pendingIncludes);
            $this->pendingIncludes = []; // Clear after applying
        }

        return $params;
    }

    /**
     * Build complete query parameters with all applied filters, sorting, pagination, and includes
     */
    protected function buildQueryParams(
        array $baseParams = [],
        array $filters = [],
              $sort = null,
        string $sortOrder = 'asc',
        int $pageSize = 20,
        int $pageNumber = 1,
        $includes = null
    ): array {
        $params = $baseParams;

        // Apply filters
        $params = $this->applyFilters($params, $filters);

        // Apply sorting
        $params = $this->applySorting($params, $sort, $sortOrder);

        // Apply pagination
        $params = $this->applyPagination($params, $pageSize, $pageNumber);

        // Apply includes (both provided and pending)
        if ($includes !== null) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $params;
    }

    /**
     * Validate include paths to prevent invalid API calls
     */
    protected function validateIncludes(array $includes): array
    {
        $validIncludes = [];

        foreach ($includes as $include) {
            // Basic validation - ensure it's a string with valid characters
            if (is_string($include) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)*$/', $include)) {
                $validIncludes[] = $include;
            }
        }

        return $validIncludes;
    }

    /**
     * Get suggested includes for the current resource type
     * Override in specific resource classes to provide context-appropriate suggestions
     */
    protected function getSuggestedIncludes(): array
    {
        return [
            'responsible_user',
            'department',
        ];
    }

    /**
     * Property to store pending includes for fluent interface
     */
    protected array $pendingIncludes = [];
}

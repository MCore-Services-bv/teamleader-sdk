<?php

namespace McoreServices\TeamleaderSDK\Traits;

trait FilterTrait
{
    /**
     * Apply filters to the parameters.
     *
     * @param  array  $params  The current parameters
     * @param  array  $filters  The filters to apply
     * @return array The updated parameters
     */
    protected function applyFilters(array $params = [], array $filters = [])
    {
        // Remove null or empty array values to avoid invalid filters
        $filters = array_filter($filters, function ($value) {
            if (is_array($value)) {
                return ! empty($value);
            }

            return $value !== null && $value !== '';
        });

        if (! empty($filters)) {
            $params['filter'] = $filters;
        }

        return $params;
    }

    /**
     * Apply sorting to the parameters.
     *
     * @param  array  $params  The current parameters
     * @param  string|array  $sort  Field(s) to sort by
     * @param  string  $order  Sort order (asc or desc)
     * @return array The updated parameters
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
     *
     * @param  array  $params  The current parameters
     * @param  int  $size  Page size
     * @param  int  $number  Page number
     * @return array The updated parameters
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
     * Apply includes for sideloading related resources (FIXED VERSION).
     *
     * CRITICAL FIX: Changed to use 'includes' (plural) instead of 'include' (singular)
     * because the Teamleader API requires 'includes' for most endpoints (companies.info, products.info, etc.)
     *
     * @param  array  $params  The current parameters
     * @param  string|array  $includes  The includes to apply
     * @return array The updated parameters
     */
    protected function applyIncludes(array $params = [], $includes = null)
    {
        if (! empty($includes)) {
            if (is_array($includes)) {
                // Filter out empty includes and join with comma
                $validIncludes = array_filter($includes, function ($include) {
                    return ! empty(trim($include));
                });

                if (! empty($validIncludes)) {
                    // FIXED: Changed from 'include' to 'includes' (plural)
                    $params['includes'] = implode(',', $validIncludes);
                }
            } else {
                // Single include string
                $trimmedInclude = trim($includes);
                if (! empty($trimmedInclude)) {
                    // FIXED: Changed from 'include' to 'includes' (plural)
                    $params['includes'] = $trimmedInclude;
                }
            }
        }

        return $params;
    }

    /**
     * Fluent interface for sideloading relationships
     *
     * @param  string|array  $relationships  Relationship(s) to include
     * @return static
     */
    public function with($relationships)
    {
        if (is_string($relationships)) {
            // Handle comma-separated string or single relationship
            $relationships = explode(',', $relationships);
        } elseif (! is_array($relationships)) {
            // Convert to array if it's neither string nor array
            $relationships = [$relationships];
        }

        // Clean up the relationships
        $relationships = array_map('trim', $relationships);
        $relationships = array_filter($relationships);

        // Store for use in the next API call
        $this->pendingIncludes = array_merge($this->pendingIncludes ?? [], $relationships);

        return $this;
    }

    /**
     * Common sideloading helper methods for frequently used relationships
     */

    /**
     * Include customer information (for deals, quotations, etc.)
     *
     * @return static
     */
    public function withCustomer()
    {
        return $this->with('lead.customer');
    }

    /**
     * Include responsible user information
     *
     * @return static
     */
    public function withResponsibleUser()
    {
        return $this->with('responsible_user');
    }

    /**
     * Include department information
     *
     * @return static
     */
    public function withDepartment()
    {
        return $this->with('department');
    }

    /**
     * Include company information (for contacts)
     *
     * @return static
     */
    public function withCompany()
    {
        return $this->with('company');
    }

    /**
     * Include custom fields
     *
     * @return static
     */
    public function withCustomFields()
    {
        return $this->with('custom_fields');
    }

    /**
     * Include multiple common relationships at once
     *
     * @return static
     */
    public function withCommonRelationships()
    {
        return $this->with([
            'responsible_user',
            'department',
            'lead.customer',
        ]);
    }

    /**
     * Clear any pending includes
     *
     * @return static
     */
    public function withoutIncludes()
    {
        $this->pendingIncludes = [];

        return $this;
    }

    /**
     * Get pending includes
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

        if (! empty($pendingIncludes)) {
            $params = $this->applyIncludes($params, $pendingIncludes);
            $this->pendingIncludes = []; // Clear after applying
        }

        return $params;
    }

    /**
     * Build complete query parameters with all applied filters, sorting, pagination, and includes
     *
     * @param  array  $baseParams  Base parameters
     * @param  array  $filters  Filters to apply
     * @param  string|array  $sort  Sorting configuration
     * @param  string  $sortOrder  Sort order
     * @param  int  $pageSize  Page size
     * @param  int  $pageNumber  Page number
     * @param  string|array  $includes  Includes to apply
     * @return array Complete parameters array
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
     *
     * @return array Valid includes only
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

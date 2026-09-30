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
     * Apply includes for sideloading related resources.
     *
     * NOTE: The Teamleader API uses "includes" (plural) as the body parameter
     * for both .list and .info endpoints. Using "include" (singular) is silently
     * ignored by the API, which is why custom_fields would not appear in responses.
     */
    protected function applyIncludes(array $params = [], $includes = null)
    {
        if (! empty($includes)) {
            if (is_array($includes)) {
                // Filter out empty includes and join with comma
                $validIncludes = array_filter($includes, function ($include) {
                    return ! empty($include) && is_string($include);
                });

                if (! empty($validIncludes)) {
                    $params['includes'] = implode(',', $validIncludes);
                }
            } elseif (is_string($includes)) {
                $params['includes'] = $includes;
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

        if (! empty($pendingIncludes)) {
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
     * Queue one or more includes for the next request (fluent interface).
     *
     * Resources expose typed wrappers around this — Deals::withCustomer(),
     * TimeTracking::withMaterials() and so on. The queued includes are consumed
     * and cleared by applyPendingIncludes() when the request is built, so the
     * fluent state does not leak into a subsequent call on the same instance.
     *
     * @param  array|string  $includes  An include path, or an array of them
     * @return static
     */
    public function with($includes)
    {
        $includes = is_array($includes) ? $includes : [$includes];

        foreach ($includes as $include) {
            if (! is_string($include) || $include === '') {
                continue;
            }

            if (! in_array($include, $this->pendingIncludes, true)) {
                $this->pendingIncludes[] = $include;
            }
        }

        return $this;
    }

    /**
     * Resolve the sideload option from a caller's $options array.
     *
     * The SDK's convention is `$options['include']` (singular) as the *option*
     * key, translated to `includes` (plural) as the *body* key by
     * applyIncludes(). Four resources — Projects, Orders, Pipelines and
     * Invoices — historically read `$options['includes']` instead, so
     * `['include' => 'custom_fields']` was silently ignored on those and
     * `['includes' => ...]` was silently ignored everywhere else.
     *
     * Both keys are now accepted everywhere. `include` wins when both are
     * present, since it is the documented one.
     *
     * @return array|string|null Null when neither key is set
     */
    protected function resolveIncludesOption(array $options)
    {
        if (! empty($options['include'])) {
            return $options['include'];
        }

        if (! empty($options['includes'])) {
            return $options['includes'];
        }

        return null;
    }

    /**
     * Normalise a sort option into the array-of-objects shape the API expects.
     *
     * Accepts a field name, a list of field names, a single
     * ['field' => ..., 'order' => ...] entry, a list of those, or a
     * field => order map (['name' => 'desc']).
     *
     * Resources that declare $availableSortFields get their fields validated;
     * those that do not are passed through unchecked.
     *
     * This is the single implementation of the sort rule. Until v3.0 Deals,
     * TimeTracking and CustomFields each carried their own copy of this method
     * and of validateSortField()/normaliseSortOrder(); they now delegate here.
     * Resources adapt it through a thin buildSort() wrapper — for an endpoint
     * that only sorts ascending, or accepts a `field:order` shorthand — never
     * by redefining the validation.
     *
     * @param  array|string  $sort
     *
     * @throws \InvalidArgumentException When a sort field or order is not supported
     */
    protected function normaliseSort($sort, string $order = 'asc'): array
    {
        $order = $this->normaliseSortOrder($order);

        // Already a list of sort objects
        if (is_array($sort) && isset($sort[0]) && is_array($sort[0])) {
            return array_map(fn (array $entry) => [
                'field' => $this->validateSortField($entry['field'] ?? null),
                'order' => $this->normaliseSortOrder($entry['order'] ?? $order),
            ], array_values($sort));
        }

        // A single ['field' => ..., 'order' => ...] entry
        if (is_array($sort) && isset($sort['field'])) {
            return [[
                'field' => $this->validateSortField($sort['field']),
                'order' => $this->normaliseSortOrder($sort['order'] ?? $order),
            ]];
        }

        // A field => order map: ['name' => 'asc', 'created_at' => 'desc']
        if (is_array($sort) && $sort !== [] && ! array_is_list($sort)) {
            $normalised = [];

            foreach ($sort as $field => $fieldOrder) {
                $normalised[] = [
                    'field' => $this->validateSortField((string) $field),
                    'order' => $this->normaliseSortOrder($fieldOrder),
                ];
            }

            return $normalised;
        }

        // A list of field names
        if (is_array($sort)) {
            return array_map(fn ($field) => [
                'field' => $this->validateSortField($field),
                'order' => $order,
            ], array_values($sort));
        }

        // A single field name
        return [[
            'field' => $this->validateSortField($sort),
            'order' => $order,
        ]];
    }

    /**
     * Ensure a sort field is one the endpoint accepts.
     *
     * Validates against $availableSortFields when the resource declares it as a
     * keyed map. Resources that declare it as a plain list, or not at all, get a
     * type check only.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateSortField(mixed $field): string
    {
        if (! is_string($field) || $field === '') {
            throw new \InvalidArgumentException(
                'Sort field must be a non-empty string, '.gettype($field).' given.'
            );
        }

        $available = $this->availableSortFields ?? [];

        // Only validate against a keyed map — a plain list carries no descriptions
        // and predates the convention.
        if ($available !== [] && ! array_is_list($available) && ! array_key_exists($field, $available)) {
            throw new \InvalidArgumentException(
                "Invalid sort field: {$field}. {$this->sortEndpoint()} accepts: "
                .implode(', ', array_keys($available)).'.'
            );
        }

        return $field;
    }

    /**
     * The endpoint named in sort validation messages.
     *
     * Every sortable endpoint is a `.list`, so the default is the resource's
     * base path plus `.list`. Override when a resource sorts on another endpoint.
     */
    protected function sortEndpoint(): string
    {
        return method_exists($this, 'getBasePath') ? $this->getBasePath().'.list' : 'This endpoint';
    }

    /**
     * Ensure a sort order is asc or desc.
     *
     * @throws \InvalidArgumentException
     */
    protected function normaliseSortOrder(mixed $order): string
    {
        if (! is_string($order)) {
            throw new \InvalidArgumentException('Sort order must be a string: asc or desc.');
        }

        $normalised = strtolower($order);

        if (! in_array($normalised, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException("Invalid sort order: {$order}. Must be asc or desc.");
        }

        return $normalised;
    }

    /**
     * Property to store pending includes for fluent interface
     */
    protected array $pendingIncludes = [];
}

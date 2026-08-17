<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Deals extends Resource
{
    protected string $description = 'Manage sales deals in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = true;

    // Available includes for sideloading
    protected array $availableIncludes = [
        'lead.customer',
        'responsible_user',
        'department',
        'current_phase',
        'source',
        'custom_fields',
    ];

    // Default includes
    protected array $defaultIncludes = [];

    /**
     * Filters accepted by deals.list.
     *
     * Verified against @teamleader/focus-api-specification v1.197.0 — these are
     * exactly the keys the endpoint declares, and the keys of this array are
     * used as the filter whitelist in buildFilters(). Keeping the whitelist and
     * the documentation in one place is deliberate: they previously drifted, and
     * the SDK carried tag() / untag() / withTags() methods plus a `tags` filter
     * for endpoints Teamleader has never exposed.
     *
     * @see https://developer.focus.teamleader.eu/docs/api/deals-list
     */
    protected array $commonFilters = [
        'ids' => 'Array of deal UUIDs to filter by',
        'term' => 'Search term (filters on title, reference, and customer name)',
        'customer' => 'Filter by customer (requires type and id)',
        'phase_id' => 'Filter by specific phase UUID',
        'estimated_closing_date' => 'Filter by exact closing date',
        'estimated_closing_date_from' => 'Filter by closing date from (inclusive)',
        'estimated_closing_date_until' => 'Filter by closing date until (inclusive)',
        'responsible_user_id' => 'Filter by responsible user UUID (string or array)',
        'updated_since' => 'Filter by last update date (inclusive)',
        'created_before' => 'Filter by creation date (inclusive)',
        'status' => 'Filter by deal status (open, won, lost) — string is coerced to array',
        'pipeline_ids' => 'Array of pipeline UUIDs',
    ];

    // Available deal statuses
    protected array $availableStatuses = [
        'new',
        'open',
        'won',
        'lost',
    ];

    // Available customer types
    protected array $customerTypes = [
        'contact',
        'company',
    ];

    /**
     * Sort fields accepted by deals.list.
     *
     * The API declares only these two; anything else is rejected by
     * buildSort() rather than silently coerced.
     */
    protected array $availableSortFields = [
        'created_at' => 'Sort by creation date',
        'weighted_value' => 'Sort by weighted deal value',
    ];

    // Filter keys whose value the API expects as an array, but which are
    // convenient to pass as a single string
    protected array $arrayFilters = [
        'ids',
        'pipeline_ids',
        'status',
    ];

    // Available currency codes
    protected array $availableCurrencies = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP',
        'INR', 'ISK', 'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK',
        'TRY', 'USD', 'ZAR',
    ];

    // Usage examples
    protected array $usageExamples = [
        'list_open_deals' => [
            'description' => 'Get all open deals',
            'code' => '$deals = $teamleader->deals()->open();',
        ],
        'create_deal' => [
            'description' => 'Create a new deal',
            'code' => '$deal = $teamleader->deals()->create([
                "lead" => ["customer" => ["type" => "company", "id" => "company-uuid"]],
                "title" => "New Business Deal",
                "estimated_value" => ["amount" => 10000, "currency" => "EUR"]
            ]);',
        ],
        'update_deal' => [
            'description' => 'Update a deal',
            'code' => '$deal = $teamleader->deals()->update("deal-uuid", [
                "title" => "Updated Deal Title",
                "estimated_probability" => 0.75
            ]);',
        ],
        'win_deal' => [
            'description' => 'Mark a deal as won',
            'code' => '$result = $teamleader->deals()->win("deal-uuid");',
        ],
        'lose_deal' => [
            'description' => 'Mark a deal as lost with reason',
            'code' => '$result = $teamleader->deals()->lose("deal-uuid", "reason-uuid", "Price too high");',
        ],
        'move_deal' => [
            'description' => 'Move a deal to a different phase',
            'code' => '$result = $teamleader->deals()->move("deal-uuid", "phase-uuid");',
        ],
        'filter_deals' => [
            'description' => 'Get deals with full information',
            'code' => '$deals = $teamleader->deals()
                ->withCustomer()
                ->withResponsibleUser()
                ->list(["status" => ["open"]]);',
        ],
        'sorted_deals' => [
            'description' => 'Get deals sorted by weighted value',
            'code' => '$deals = $teamleader->deals()->list([], ["sort" => "weighted_value", "sort_order" => "desc"]);',
        ],
        'sideload_custom_fields' => [
            'description' => 'Get deals with custom fields sideloaded',
            'code' => '$deals = $teamleader->deals()->list([], ["include" => "custom_fields"]);',
        ],
    ];

    /**
     * List deals with filtering, sorting, pagination and sideloading
     *
     * Unknown filter keys throw rather than being forwarded — the API ignores
     * keys it does not recognise and returns a full unfiltered result set with
     * a 200, which is indistinguishable from a correct response.
     *
     * @param  array  $filters  Filters to apply — see $commonFilters
     * @param  array  $options  sort, sort_order, page_size, page_number, include
     *
     * @throws InvalidArgumentException When a filter key or sort field is not supported
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Add filters
        //
        // This routes through buildFilters() rather than assigning $filters
        // directly. The whitelist has always existed on this class; list() just
        // never called it, so any key a caller invented was passed straight to
        // the API.
        if (! empty($filters)) {
            $apiFilters = $this->buildFilters($filters);

            if (! empty($apiFilters)) {
                $params['filter'] = $apiFilters;
            }
        }

        // Add pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $params['page'] = [
                'size' => $options['page_size'] ?? 20,
                'number' => $options['page_number'] ?? 1,
            ];
        }

        // Add sorting
        if (isset($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'desc');
        }

        // Add includes
        //
        // Must go through applyIncludes(), which writes the `includes` (plural)
        // body key. The singular form is silently ignored by the API — see the
        // note on FilterTrait::applyIncludes().
        if (isset($options['include'])) {
            $params = $this->applyIncludes($params, $options['include']);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get deal information with enhanced include handling
     *
     * @param  string  $id  Deal UUID
     * @param  mixed  $includes  Relations to include
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        if (! empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * Create a new deal
     *
     * Optional pass-through fields include:
     * - purchase_order_number (string|null): the customer's purchase order number
     *
     * @param  array  $data  Deal data
     *
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        $this->validateDealData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update a deal
     *
     * Optional pass-through fields include:
     * - purchase_order_number (string|null): the customer's purchase order number
     *
     * @param  string  $id  Deal UUID
     * @param  array  $data  Data to update
     *
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateDealData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a deal
     *
     * @param  string  $id  Deal UUID
     * @param  mixed  ...$additionalParams  Additional parameters (not used)
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Mark a deal as won
     *
     * @param  string  $id  Deal UUID
     */
    public function win(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.win', ['id' => $id]);
    }

    /**
     * Mark a deal as lost
     *
     * @param  string  $id  Deal UUID
     * @param  string|null  $reasonId  Lost reason UUID (optional)
     * @param  string|null  $extraInfo  Additional information (optional)
     */
    public function lose(string $id, ?string $reasonId = null, ?string $extraInfo = null): array
    {
        $params = ['id' => $id];

        if ($reasonId !== null) {
            $params['reason_id'] = $reasonId;
        }

        if ($extraInfo !== null) {
            $params['extra_info'] = $extraInfo;
        }

        return $this->api->request('POST', $this->getBasePath().'.lose', $params);
    }

    /**
     * Move a deal to a different phase
     *
     * @param  string  $id  Deal UUID
     * @param  string  $phaseId  Target phase UUID
     *
     * @throws InvalidArgumentException
     */
    public function move(string $id, string $phaseId): array
    {
        if (empty($phaseId)) {
            throw new InvalidArgumentException('Phase ID is required to move a deal');
        }

        return $this->api->request('POST', $this->getBasePath().'.move', [
            'id' => $id,
            'phase_id' => $phaseId,
        ]);
    }

    /**
     * Get only open deals
     *
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function open(array $additionalFilters = []): array
    {
        return $this->list(array_merge(['status' => ['open']], $additionalFilters));
    }

    /**
     * Get only won deals
     *
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function won(array $additionalFilters = []): array
    {
        return $this->list(array_merge(['status' => ['won']], $additionalFilters));
    }

    /**
     * Get only lost deals
     *
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function lost(array $additionalFilters = []): array
    {
        return $this->list(array_merge(['status' => ['lost']], $additionalFilters));
    }

    /**
     * Get deals for a specific customer
     *
     * @param  string  $customerType  Customer type ('contact' or 'company')
     * @param  string  $customerId  Customer UUID
     * @param  array  $additionalFilters  Additional filters to apply
     *
     * @throws InvalidArgumentException
     */
    public function forCustomer(string $customerType, string $customerId, array $additionalFilters = []): array
    {
        if (! in_array($customerType, $this->customerTypes)) {
            throw new InvalidArgumentException(
                "Invalid customer type: {$customerType}. Must be 'contact' or 'company'"
            );
        }

        $filters = array_merge([
            'customer' => [
                'type' => $customerType,
                'id' => $customerId,
            ],
        ], $additionalFilters);

        return $this->list($filters);
    }

    /**
     * Get deals in a specific phase
     *
     * @param  string  $phaseId  Phase UUID
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function byPhase(string $phaseId, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['phase_id' => $phaseId], $additionalFilters));
    }

    /**
     * Get deals by specific IDs
     *
     * @param  array  $ids  Array of deal UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get deals updated since a specific date
     *
     * @param  string  $date  Date in ISO format
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function updatedSince(string $date, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['updated_since' => $date], $additionalFilters));
    }

    /**
     * Get deals for a specific responsible user
     *
     * @param  string  $userId  User UUID
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function forUser(string $userId, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['responsible_user_id' => $userId], $additionalFilters));
    }

    /**
     * Search deals by term
     *
     * @param  string  $term  Search term (searches title, reference, customer name)
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function search(string $term, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['term' => $term], $additionalFilters));
    }

    /**
     * Get deals closing in a date range
     *
     * @param  string  $from  Start date (inclusive)
     * @param  string  $until  End date (inclusive)
     * @param  array  $additionalFilters  Additional filters to apply
     */
    public function closingBetween(string $from, string $until, array $additionalFilters = []): array
    {
        return $this->list(array_merge([
            'estimated_closing_date_from' => $from,
            'estimated_closing_date_until' => $until,
        ], $additionalFilters));
    }

    /**
     * Validate deal data before create/update
     *
     * @param  array  $data  Deal data
     * @param  string  $operation  Operation type ('create' or 'update')
     *
     * @throws InvalidArgumentException
     */
    protected function validateDealData(array $data, string $operation): void
    {
        // Validate required fields for creation
        if ($operation === 'create') {
            if (empty($data['lead']['customer'])) {
                throw new InvalidArgumentException('Customer is required for deal creation');
            }

            if (empty($data['lead']['customer']['type'])) {
                throw new InvalidArgumentException('Customer type is required');
            }

            if (! in_array($data['lead']['customer']['type'], $this->customerTypes)) {
                throw new InvalidArgumentException(
                    "Invalid customer type: {$data['lead']['customer']['type']}. Must be 'contact' or 'company'"
                );
            }

            if (empty($data['lead']['customer']['id'])) {
                throw new InvalidArgumentException('Customer ID is required');
            }

            if (empty($data['title'])) {
                throw new InvalidArgumentException('Title is required for deal creation');
            }
        }

        // Validate estimated value if provided
        if (isset($data['estimated_value'])) {
            if (! isset($data['estimated_value']['amount'])) {
                throw new InvalidArgumentException('Estimated value amount is required');
            }

            if (! isset($data['estimated_value']['currency'])) {
                throw new InvalidArgumentException('Estimated value currency is required');
            }

            if (! in_array($data['estimated_value']['currency'], $this->availableCurrencies)) {
                throw new InvalidArgumentException(
                    "Invalid currency: {$data['estimated_value']['currency']}"
                );
            }
        }

        // Validate estimated probability if provided
        if (isset($data['estimated_probability'])) {
            $probability = $data['estimated_probability'];
            if (! is_numeric($probability) || $probability < 0 || $probability > 1) {
                throw new InvalidArgumentException(
                    'Estimated probability must be a number between 0 and 1 (inclusive)'
                );
            }
        }

        // Validate currency if provided
        if (isset($data['currency'])) {
            if (! isset($data['currency']['code']) || ! isset($data['currency']['exchange_rate'])) {
                throw new InvalidArgumentException(
                    'Currency must include both code and exchange_rate'
                );
            }

            if (! in_array($data['currency']['code'], $this->availableCurrencies)) {
                throw new InvalidArgumentException(
                    "Invalid currency code: {$data['currency']['code']}"
                );
            }
        }

        // Validate custom fields if provided
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            foreach ($data['custom_fields'] as $field) {
                if (! isset($field['id'])) {
                    throw new InvalidArgumentException('Custom field must include an id');
                }
                if (! isset($field['value'])) {
                    throw new InvalidArgumentException('Custom field must include a value');
                }
            }
        }
    }

    /**
     * Build the filter object for the API request
     *
     * Rejects keys deals.list does not accept. The API ignores unrecognised
     * filter keys and answers 200 with the full unfiltered set, so forwarding
     * them produces silently wrong results rather than an error.
     *
     * Values for ids, pipeline_ids and status are coerced to arrays when a
     * single string is given, since the API requires arrays for all three.
     *
     * @param  array  $filters  User-provided filters
     * @return array API-formatted filters
     *
     * @throws InvalidArgumentException When a filter key is not supported
     */
    protected function buildFilters(array $filters): array
    {
        $supported = array_keys($this->commonFilters);
        $unknown = array_diff(array_keys($filters), $supported);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unsupported filter '.(count($unknown) > 1 ? 'keys' : 'key')
                .' for deals.list: '.implode(', ', $unknown)
                .'. Supported: '.implode(', ', $supported).'.'
            );
        }

        $apiFilters = [];

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (in_array($key, $this->arrayFilters, true) && ! is_array($value)) {
                $apiFilters[$key] = [$value];

                continue;
            }

            $apiFilters[$key] = $value;
        }

        return $apiFilters;
    }

    /**
     * Build the sort object for the API request
     *
     * The API expects an array of objects — [['field' => ..., 'order' => ...]].
     *
     * This method did not exist on this class before v2.1.2, while list()
     * called it, so passing a sort option raised
     * "Call to undefined method ...::buildSort()".
     *
     * @param  array|string  $sort  A field name, an array of field names, or an
     *                              array of ['field' => ..., 'order' => ...] entries
     * @param  string  $order  Default order applied to entries that do not carry one
     *
     * @throws InvalidArgumentException When a sort field or order is not supported
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        $order = $this->normaliseSortOrder($order);

        // Already a list of sort objects
        if (is_array($sort) && isset($sort[0]) && is_array($sort[0])) {
            return array_map(function (array $entry) use ($order) {
                $field = $entry['field'] ?? null;

                if (! is_string($field)) {
                    throw new InvalidArgumentException('Each sort entry must contain a field.');
                }

                return [
                    'field' => $this->validateSortField($field),
                    'order' => $this->normaliseSortOrder($entry['order'] ?? $order),
                ];
            }, $sort);
        }

        // A single ['field' => ..., 'order' => ...] entry
        if (is_array($sort) && isset($sort['field'])) {
            return [[
                'field' => $this->validateSortField($sort['field']),
                'order' => $this->normaliseSortOrder($sort['order'] ?? $order),
            ]];
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
     * Ensure a sort field is one the API accepts
     *
     * @throws InvalidArgumentException
     */
    protected function validateSortField(mixed $field): string
    {
        if (! is_string($field) || ! array_key_exists($field, $this->availableSortFields)) {
            throw new InvalidArgumentException(
                'Invalid sort field: '.(is_string($field) ? $field : gettype($field))
                .'. deals.list accepts: '.implode(', ', array_keys($this->availableSortFields)).'.'
            );
        }

        return $field;
    }

    /**
     * Ensure a sort order is asc or desc
     *
     * @throws InvalidArgumentException
     */
    protected function normaliseSortOrder(mixed $order): string
    {
        if (! is_string($order)) {
            throw new InvalidArgumentException('Sort order must be a string: asc or desc.');
        }

        $normalised = strtolower($order);

        if (! in_array($normalised, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Invalid sort order: {$order}. Must be asc or desc.");
        }

        return $normalised;
    }

    /**
     * Get the base path for the deals resource
     */
    protected function getBasePath(): string
    {
        return 'deals';
    }

    /**
     * Fluent method to include customer information
     */
    public function withCustomer(): self
    {
        return $this->with('lead.customer');
    }

    /**
     * Fluent method to include responsible user information
     */
    public function withResponsibleUser(): self
    {
        return $this->with('responsible_user');
    }

    /**
     * Fluent method to include department information
     */
    public function withDepartment(): self
    {
        return $this->with('department');
    }

    /**
     * Fluent method to include current phase information
     */
    public function withCurrentPhase(): self
    {
        return $this->with('current_phase');
    }

    /**
     * Fluent method to include source information
     */
    public function withSource(): self
    {
        return $this->with('source');
    }

    /**
     * Fluent method to include custom fields
     */
    public function withCustomFields(): self
    {
        return $this->with('custom_fields');
    }

    /**
     * Fluent method to include all common relationships
     */
    public function withAll(): self
    {
        return $this->with([
            'lead.customer',
            'responsible_user',
            'department',
            'current_phase',
            'source',
        ]);
    }

    /**
     * Get suggested includes for this resource
     */
    protected function getSuggestedIncludes(): array
    {
        return $this->availableIncludes;
    }
}

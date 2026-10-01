<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Deals extends Resource
{
    use ValidatesWritePayload;

    /**
     * Body fields deals.update accepts, besides `id`. deals.create accepts the
     * same set plus `phase_id` — a deal changes phase through move(), not
     * update(). From @teamleader/focus-api-specification v1.221.0.
     */
    public const UPDATE_FIELDS = [
        'lead', 'title', 'summary', 'source_id', 'department_id', 'responsible_user_id',
        'second_responsible_user_id', 'estimated_value', 'estimated_probability',
        'estimated_closing_date', 'currency', 'custom_fields', 'purchase_order_number',
    ];

    /** `lead.customer.type` on deals.create / deals.update, `filter.customer.type` on deals.list */
    public const CUSTOMER_TYPES = ['contact', 'company'];

    /** `filter.status[]` on deals.list */
    public const STATUSES = ['open', 'won', 'lost'];

    /** `estimated_value.currency` and `currency.code` on deals.create / deals.update */
    public const CURRENCIES = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP', 'INR', 'ISK',
        'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

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

    /**
     * Includes accepted by deals.list.
     *
     * Until v2.2.5 this also listed lead.customer, responsible_user,
     * department, current_phase and source. None of them is an include: all
     * five are returned on every deal as a {type, id} reference. Requesting
     * them did nothing, and because the data arrived regardless it looked like
     * it worked — the Companies and Contacts defect of v2.2.0, again.
     *
     * `second_responsible_user` requires the second deal responsible feature
     * to be enabled on the account.
     *
     * @see $infoIncludes For deals.info, which takes a smaller set
     */
    protected array $availableIncludes = [
        'custom_fields',
        'second_responsible_user',
    ];

    /**
     * Includes accepted by deals.info. Custom fields come back on info()
     * without being asked.
     */
    protected array $infoIncludes = [
        'second_responsible_user',
    ];

    // Default includes
    protected array $defaultIncludes = [];

    /**
     * Filters accepted by deals.list.
     *
     * Verified against @teamleader/focus-api-specification v1.221.0 — these are
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
        'status' => 'Filter by deal status (open, won, lost) — a string is wrapped into an array',
        'pipeline_ids' => 'Array of pipeline UUIDs',
    ];

    // Kept for backwards compatibility — use the CUSTOMER_TYPES constant
    protected array $customerTypes = self::CUSTOMER_TYPES;

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

    // Kept for backwards compatibility — use the CURRENCIES constant
    protected array $availableCurrencies = self::CURRENCIES;

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
            'description' => 'Get open deals with custom fields',
            'code' => '$deals = $teamleader->deals()
                ->withCustomFields()
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
        // note on FilterTrait::applyIncludes(). Both option spellings are
        // accepted, merged with fluent includes, and checked against the
        // endpoint's set. Pending includes are consumed first, so a rejected
        // call cannot leak them into the next one.
        $pending = $this->getPendingIncludes();
        $this->applyPendingIncludes([]);

        $includes = $this->assertIncludes(
            [...(array) ($this->resolveIncludesOption($options) ?? []), ...$pending],
            $this->availableIncludes,
            'deals.list'
        );

        $params = $this->applyIncludes($params, $includes);

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get deal information
     *
     * deals.info accepts one include, `second_responsible_user`. Custom fields
     * are returned without being asked; the lead, responsible user, department,
     * phase and source are always returned as references.
     *
     * @param  string  $id  Deal UUID
     * @param  mixed  $includes  second_responsible_user
     *
     * @throws InvalidArgumentException When an include is not valid for this endpoint
     */
    public function info($id, $includes = null): array
    {
        $pending = $this->getPendingIncludes();
        $this->applyPendingIncludes([]);

        $requested = $this->assertIncludes(
            [...(array) ($includes ?? []), ...$pending],
            $this->infoIncludes,
            'deals.info'
        );

        return $this->api->request(
            'POST',
            $this->getBasePath().'.info',
            $this->applyIncludes(['id' => $id], $requested)
        );
    }

    /**
     * Create a new deal
     *
     * Optional pass-through fields include:
     * - purchase_order_number (string|null): the customer's purchase order number
     *
     * A select custom field takes the option **label** as its value (a string,
     * or a list of strings for multi select), not the option id: Teamleader
     * refuses the id with "has an invalid single selection value". Resolve
     * either form with customFields()->selectValue($fieldId, $labelOrId).
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
     * Checked against deals.create / deals.update in the specification: the
     * lead and title are required on create; unknown top-level fields throw
     * (the API would drop them and report success — `phase_id` on update is
     * the common one, since a deal changes phase through move()); enums are
     * checked for the customer type and both currency fields.
     *
     * `estimated_value.amount` may be negative since specification 1.221.0.
     *
     * @param  array  $data  Deal data
     * @param  string  $operation  Operation type ('create' or 'update')
     *
     * @throws InvalidArgumentException
     */
    protected function validateDealData(array $data, string $operation): void
    {
        $endpoint = $operation === 'create' ? 'deals.create' : 'deals.update';

        $this->rejectUnknownFields(
            $data,
            $operation === 'create' ? [...self::UPDATE_FIELDS, 'phase_id'] : [...self::UPDATE_FIELDS, 'id'],
            $endpoint
        );

        // Validate required fields for creation
        if ($operation === 'create') {
            if (empty($data['lead']['customer'])) {
                throw new InvalidArgumentException('Customer is required for deal creation');
            }

            if (empty($data['lead']['customer']['type'])) {
                throw new InvalidArgumentException('Customer type is required');
            }

            if (empty($data['lead']['customer']['id'])) {
                throw new InvalidArgumentException('Customer ID is required');
            }

            if (empty($data['title'])) {
                throw new InvalidArgumentException('Title is required for deal creation');
            }
        }

        if (isset($data['lead']['customer'])) {
            $this->assertEnum($data['lead']['customer']['type'] ?? null, self::CUSTOMER_TYPES, 'lead.customer.type', $endpoint);
        }

        // Validate estimated value if provided — null clears it
        if (isset($data['estimated_value'])) {
            if (! isset($data['estimated_value']['amount'])) {
                throw new InvalidArgumentException('Estimated value amount is required');
            }

            if (! isset($data['estimated_value']['currency'])) {
                throw new InvalidArgumentException('Estimated value currency is required');
            }

            $this->assertEnum($data['estimated_value']['currency'], self::CURRENCIES, 'estimated_value.currency', $endpoint);
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

            $this->assertEnum($data['currency']['code'], self::CURRENCIES, 'currency.code', $endpoint);
        }

        // Validate custom fields if provided. A null value is allowed: it
        // clears the field. Before v2.2.5 isset() rejected it client-side.
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            foreach ($data['custom_fields'] as $field) {
                if (! isset($field['id'])) {
                    throw new InvalidArgumentException('Custom field must include an id');
                }
                if (! is_array($field) || ! array_key_exists('value', $field)) {
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
                $value = [$value];
            }

            if ($key === 'status') {
                foreach ($value as $status) {
                    $this->assertEnum($status, self::STATUSES, 'filter.status[]', 'deals.list');
                }
            }

            if ($key === 'customer') {
                if (! is_array($value) || ! isset($value['type'], $value['id'])) {
                    throw new InvalidArgumentException(
                        'The customer filter takes ["type" => "contact"|"company", "id" => "..."].'
                    );
                }

                $this->assertEnum($value['type'], self::CUSTOMER_TYPES, 'filter.customer.type', 'deals.list');
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
     * Delegates to FilterTrait::normaliseSort(), which validates each field
     * against $availableSortFields. Deals sorts descending unless told
     * otherwise.
     *
     * @param  array|string  $sort  A field name, an array of field names, or an
     *                              array of ['field' => ..., 'order' => ...] entries
     * @param  string  $order  Default order applied to entries that do not carry one
     *
     * @throws InvalidArgumentException When a sort field or order is not supported
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        return $this->normaliseSort($sort, $order);
    }

    /**
     * Get the base path for the deals resource
     */
    protected function getBasePath(): string
    {
        return 'deals';
    }

    /**
     * Fluent method to include custom fields — deals.list only; deals.info
     * returns them without being asked
     */
    public function withCustomFields(): self
    {
        return $this->with('custom_fields');
    }

    /**
     * Fluent method to include the second responsible user, on list() or
     * info(). Requires the second deal responsible feature on the account.
     */
    public function withSecondResponsibleUser(): self
    {
        return $this->with('second_responsible_user');
    }

    /**
     * Get suggested includes for this resource
     */
    protected function getSuggestedIncludes(): array
    {
        return [];
    }
}

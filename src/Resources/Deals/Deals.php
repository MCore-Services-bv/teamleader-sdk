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

    // Common filters based on API documentation
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
        'status' => 'Filter by deal status (open, won, lost)',
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

    // Available sort fields
    protected array $availableSortFields = [
        'created_at',
        'weighted_value',
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
            'code' => '$deals = $teamleader->deals()->open();'
        ],
        'create_deal' => [
            'description' => 'Create a new deal',
            'code' => '$deal = $teamleader->deals()->create([
                "lead" => ["customer" => ["type" => "company", "id" => "company-uuid"]],
                "title" => "New Business Deal",
                "estimated_value" => ["amount" => 10000, "currency" => "EUR"]
            ]);'
        ],
        'update_deal' => [
            'description' => 'Update a deal',
            'code' => '$deal = $teamleader->deals()->update("deal-uuid", [
                "title" => "Updated Deal Title",
                "estimated_probability" => 0.75
            ]);'
        ],
        'win_deal' => [
            'description' => 'Mark a deal as won',
            'code' => '$result = $teamleader->deals()->win("deal-uuid");'
        ],
        'lose_deal' => [
            'description' => 'Mark a deal as lost with reason',
            'code' => '$result = $teamleader->deals()->lose("deal-uuid", "reason-uuid", "Price too high");'
        ],
        'move_deal' => [
            'description' => 'Move a deal to a different phase',
            'code' => '$result = $teamleader->deals()->move("deal-uuid", "phase-uuid");'
        ],
        'filter_deals' => [
            'description' => 'Get deals with full information',
            'code' => '$deals = $teamleader->deals()
                ->withCustomer()
                ->withResponsibleUser()
                ->list(["status" => ["open"]]);'
        ],
    ];

    /**
     * List deals with enhanced filtering and sorting
     *
     * @param array $filters Filters to apply
     * @param array $options Additional options (sorting, pagination, includes)
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = $this->createParams(
            $filters,
            $options['sort'] ?? null,
            $options['sort_order'] ?? 'desc',
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
     * Get deal information with enhanced include handling
     *
     * @param string $id Deal UUID
     * @param mixed $includes Relations to include
     * @return array
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        if (!empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        // Apply any pending includes from fluent interface
        $params = $this->applyPendingIncludes($params);

        return $this->api->request('POST', $this->getBasePath() . '.info', $params);
    }

    /**
     * Create a new deal
     *
     * @param array $data Deal data
     * @return array
     * @throws InvalidArgumentException
     */
    public function create(array $data): array
    {
        $this->validateDealData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Update a deal
     *
     * @param string $id Deal UUID
     * @param array $data Data to update
     * @return array
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateDealData($data, 'update');
        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Delete a deal
     *
     * @param string $id Deal UUID
     * @param mixed ...$additionalParams Additional parameters (not used)
     * @return array
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Mark a deal as won
     *
     * @param string $id Deal UUID
     * @return array
     */
    public function win(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.win', ['id' => $id]);
    }

    /**
     * Mark a deal as lost
     *
     * @param string $id Deal UUID
     * @param string|null $reasonId Lost reason UUID (optional)
     * @param string|null $extraInfo Additional information (optional)
     * @return array
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

        return $this->api->request('POST', $this->getBasePath() . '.lose', $params);
    }

    /**
     * Move a deal to a different phase
     *
     * @param string $id Deal UUID
     * @param string $phaseId Target phase UUID
     * @return array
     * @throws InvalidArgumentException
     */
    public function move(string $id, string $phaseId): array
    {
        if (empty($phaseId)) {
            throw new InvalidArgumentException('Phase ID is required to move a deal');
        }

        return $this->api->request('POST', $this->getBasePath() . '.move', [
            'id' => $id,
            'phase_id' => $phaseId,
        ]);
    }

    /**
     * Get only open deals
     *
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function open(array $additionalFilters = []): array
    {
        return $this->list(array_merge(['status' => ['open']], $additionalFilters));
    }

    /**
     * Get only won deals
     *
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function won(array $additionalFilters = []): array
    {
        return $this->list(array_merge(['status' => ['won']], $additionalFilters));
    }

    /**
     * Get only lost deals
     *
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function lost(array $additionalFilters = []): array
    {
        return $this->list(array_merge(['status' => ['lost']], $additionalFilters));
    }

    /**
     * Get deals for a specific customer
     *
     * @param string $customerType Customer type ('contact' or 'company')
     * @param string $customerId Customer UUID
     * @param array $additionalFilters Additional filters to apply
     * @return array
     * @throws InvalidArgumentException
     */
    public function forCustomer(string $customerType, string $customerId, array $additionalFilters = []): array
    {
        if (!in_array($customerType, $this->customerTypes)) {
            throw new InvalidArgumentException(
                "Invalid customer type: {$customerType}. Must be 'contact' or 'company'"
            );
        }

        $filters = array_merge([
            'customer' => [
                'type' => $customerType,
                'id' => $customerId,
            ]
        ], $additionalFilters);

        return $this->list($filters);
    }

    /**
     * Get deals in a specific phase
     *
     * @param string $phaseId Phase UUID
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function byPhase(string $phaseId, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['phase_id' => $phaseId], $additionalFilters));
    }

    /**
     * Get deals by specific IDs
     *
     * @param array $ids Array of deal UUIDs
     * @return array
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get deals updated since a specific date
     *
     * @param string $date Date in ISO format
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function updatedSince(string $date, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['updated_since' => $date], $additionalFilters));
    }

    /**
     * Get deals for a specific responsible user
     *
     * @param string $userId User UUID
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function forUser(string $userId, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['responsible_user_id' => $userId], $additionalFilters));
    }

    /**
     * Search deals by term
     *
     * @param string $term Search term (searches title, reference, customer name)
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function search(string $term, array $additionalFilters = []): array
    {
        return $this->list(array_merge(['term' => $term], $additionalFilters));
    }

    /**
     * Get deals closing in a date range
     *
     * @param string $from Start date (inclusive)
     * @param string $until End date (inclusive)
     * @param array $additionalFilters Additional filters to apply
     * @return array
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
     * @param array $data Deal data
     * @param string $operation Operation type ('create' or 'update')
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

            if (!in_array($data['lead']['customer']['type'], $this->customerTypes)) {
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
            if (!isset($data['estimated_value']['amount'])) {
                throw new InvalidArgumentException('Estimated value amount is required');
            }

            if (!isset($data['estimated_value']['currency'])) {
                throw new InvalidArgumentException('Estimated value currency is required');
            }

            if (!in_array($data['estimated_value']['currency'], $this->availableCurrencies)) {
                throw new InvalidArgumentException(
                    "Invalid currency: {$data['estimated_value']['currency']}"
                );
            }
        }

        // Validate estimated probability if provided
        if (isset($data['estimated_probability'])) {
            $probability = $data['estimated_probability'];
            if (!is_numeric($probability) || $probability < 0 || $probability > 1) {
                throw new InvalidArgumentException(
                    'Estimated probability must be a number between 0 and 1 (inclusive)'
                );
            }
        }

        // Validate currency if provided
        if (isset($data['currency'])) {
            if (!isset($data['currency']['code']) || !isset($data['currency']['exchange_rate'])) {
                throw new InvalidArgumentException(
                    'Currency must include both code and exchange_rate'
                );
            }

            if (!in_array($data['currency']['code'], $this->availableCurrencies)) {
                throw new InvalidArgumentException(
                    "Invalid currency code: {$data['currency']['code']}"
                );
            }
        }

        // Validate custom fields if provided
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            foreach ($data['custom_fields'] as $field) {
                if (!isset($field['id'])) {
                    throw new InvalidArgumentException('Custom field must include an id');
                }
                if (!isset($field['value'])) {
                    throw new InvalidArgumentException('Custom field must include a value');
                }
            }
        }
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters User-provided filters
     * @return array API-formatted filters
     */
    private function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle IDs filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // Handle term filter
        if (isset($filters['term'])) {
            $apiFilters['term'] = $filters['term'];
        }

        // Handle customer filter
        if (isset($filters['customer'])) {
            $apiFilters['customer'] = $filters['customer'];
        }

        // Handle phase_id filter
        if (isset($filters['phase_id'])) {
            $apiFilters['phase_id'] = $filters['phase_id'];
        }

        // Handle estimated_closing_date filter
        if (isset($filters['estimated_closing_date'])) {
            $apiFilters['estimated_closing_date'] = $filters['estimated_closing_date'];
        }

        // Handle estimated_closing_date_from filter
        if (isset($filters['estimated_closing_date_from'])) {
            $apiFilters['estimated_closing_date_from'] = $filters['estimated_closing_date_from'];
        }

        // Handle estimated_closing_date_until filter
        if (isset($filters['estimated_closing_date_until'])) {
            $apiFilters['estimated_closing_date_until'] = $filters['estimated_closing_date_until'];
        }

        // Handle responsible_user_id filter (can be string or array)
        if (isset($filters['responsible_user_id'])) {
            $apiFilters['responsible_user_id'] = $filters['responsible_user_id'];
        }

        // Handle updated_since filter
        if (isset($filters['updated_since'])) {
            $apiFilters['updated_since'] = $filters['updated_since'];
        }

        // Handle created_before filter
        if (isset($filters['created_before'])) {
            $apiFilters['created_before'] = $filters['created_before'];
        }

        // Handle status filter
        if (isset($filters['status'])) {
            if (is_string($filters['status'])) {
                $apiFilters['status'] = [$filters['status']];
            } elseif (is_array($filters['status'])) {
                $apiFilters['status'] = $filters['status'];
            }
        }

        // Handle pipeline_ids filter
        if (isset($filters['pipeline_ids']) && is_array($filters['pipeline_ids'])) {
            $apiFilters['pipeline_ids'] = $filters['pipeline_ids'];
        }

        return $apiFilters;
    }

    /**
     * Get the base path for the deals resource
     *
     * @return string
     */
    protected function getBasePath(): string
    {
        return 'deals';
    }

    /**
     * Fluent method to include customer information
     *
     * @return self
     */
    public function withCustomer(): self
    {
        return $this->with('lead.customer');
    }

    /**
     * Fluent method to include responsible user information
     *
     * @return self
     */
    public function withResponsibleUser(): self
    {
        return $this->with('responsible_user');
    }

    /**
     * Fluent method to include department information
     *
     * @return self
     */
    public function withDepartment(): self
    {
        return $this->with('department');
    }

    /**
     * Fluent method to include current phase information
     *
     * @return self
     */
    public function withCurrentPhase(): self
    {
        return $this->with('current_phase');
    }

    /**
     * Fluent method to include source information
     *
     * @return self
     */
    public function withSource(): self
    {
        return $this->with('source');
    }

    /**
     * Fluent method to include custom fields
     *
     * @return self
     */
    public function withCustomFields(): self
    {
        return $this->with('custom_fields');
    }

    /**
     * Fluent method to include all common relationships
     *
     * @return self
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
     *
     * @return array
     */
    protected function getSuggestedIncludes(): array
    {
        return $this->availableIncludes;
    }
}

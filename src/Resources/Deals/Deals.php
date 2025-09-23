<?php

namespace McoreServices\TeamleaderSDK\Resources\Deals;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Deals extends Resource
{
    protected string $description = 'Manage deals in Teamleader Focus';

    // Resource capabilities - Deals support full CRUD operations
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = true;
    protected bool $supportsSorting = true;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = true;

    // Available includes for sideloading (based on API docs and response structure)
    protected array $availableIncludes = [
        'custom_fields'
    ];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of deal UUIDs',
        'term' => 'Filters on the title, reference and customer\'s name',
        'customer' => 'Filter by customer (object with type and id)',
        'phase_id' => 'Filter by deal phase UUID',
        'estimated_closing_date' => 'Specific closing date (Y-m-d)',
        'estimated_closing_date_from' => 'Closing date range start (inclusive)',
        'estimated_closing_date_until' => 'Closing date range end (inclusive)',
        'responsible_user_id' => 'Filter by responsible user UUID or array of UUIDs',
        'updated_since' => 'ISO 8601 datetime',
        'created_before' => 'ISO 8601 datetime',
        'status' => 'Deal status array (open, won, lost)',
        'pipeline_ids' => 'Array of pipeline UUIDs'
    ];

    // Usage examples specific to deals
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all deals',
            'code' => '$deals = $teamleader->deals()->list();'
        ],
        'search_by_term' => [
            'description' => 'Search deals by term (title, reference, customer name)',
            'code' => '$deals = $teamleader->deals()->search("Important deal");'
        ],
        'filter_by_phase' => [
            'description' => 'Get deals in specific phase',
            'code' => '$deals = $teamleader->deals()->inPhase("phase-uuid");'
        ],
        'filter_by_customer' => [
            'description' => 'Get deals for specific customer',
            'code' => '$deals = $teamleader->deals()->forCustomer("contact", "customer-uuid");'
        ],
        'filter_by_status' => [
            'description' => 'Get won deals',
            'code' => '$deals = $teamleader->deals()->withStatus(["won"]);'
        ],
        'with_custom_fields' => [
            'description' => 'Get deals with custom fields',
            'code' => '$deals = $teamleader->deals()->withCustomFields()->list();'
        ],
        'create_deal' => [
            'description' => 'Create a new deal',
            'code' => '$deal = $teamleader->deals()->create(["title" => "New Deal", "lead" => [...]]); '
        ]
    ];

    /**
     * Get the base path for the deals resource
     */
    protected function getBasePath(): string
    {
        return 'deals';
    }

    /**
     * List deals with enhanced filtering and sorting
     */
    public function list(array $filters = [], array $options = []): array
    {
        $requestBody = [];

        // Build filter object
        if (!empty($filters)) {
            $requestBody['filter'] = $filters;
        }

        // Add pagination
        if (isset($options['page_size']) || isset($options['page_number'])) {
            $requestBody['page'] = [];
            if (isset($options['page_size'])) {
                $requestBody['page']['size'] = $options['page_size'];
            }
            if (isset($options['page_number'])) {
                $requestBody['page']['number'] = $options['page_number'];
            }
        }

        // Add sorting
        if (isset($options['sort'])) {
            $requestBody['sort'] = [[
                'field' => $options['sort'],
                'order' => $options['sort_order'] ?? 'desc'
            ]];
        }

        // Add includes
        if (isset($options['include'])) {
            $requestBody['includes'] = $options['include'];
        }

        // Apply any pending includes from fluent interface
        $requestBody = $this->applyPendingIncludesToRequestBody($requestBody);

        return $this->api->request('POST', $this->getBasePath() . '.list', $requestBody);
    }

    /**
     * Get deal information with enhanced include handling
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
     */
    public function create(array $data): array
    {
        $validatedData = $this->validateDealData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.create', $validatedData);
    }

    /**
     * Update a deal
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $validatedData = $this->validateDealData($data, 'update');
        return $this->api->request('POST', $this->getBasePath() . '.update', $validatedData);
    }

    /**
     * Delete a deal
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', ['id' => $id]);
    }

    /**
     * Mark a deal as won
     */
    public function win($id): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.win', ['id' => $id]);
    }

    /**
     * Mark a deal as lost
     */
    public function lose($id, $reasonId = null, $extraInfo = null): array
    {
        $data = ['id' => $id];

        if ($reasonId) {
            $data['reason_id'] = $reasonId;
        }

        if ($extraInfo) {
            $data['extra_info'] = $extraInfo;
        }

        return $this->api->request('POST', $this->getBasePath() . '.lose', $data);
    }

    /**
     * Move a deal to a different phase
     */
    public function move($id, $phaseId): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.move', [
            'id' => $id,
            'phase_id' => $phaseId
        ]);
    }

    /**
     * Search deals by term (searches title, reference and customer's name)
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $term], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter deals by customer
     */
    public function forCustomer(string $type, string $customerId, array $options = []): array
    {
        return $this->list(
            array_merge([
                'customer' => [
                    'type' => $type, // 'contact' or 'company'
                    'id' => $customerId
                ]
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter deals by phase
     */
    public function inPhase(string $phaseId, array $options = []): array
    {
        return $this->list(
            array_merge(['phase_id' => $phaseId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter deals by status
     */
    public function withStatus(array $statuses, array $options = []): array
    {
        return $this->list(
            array_merge(['status' => $statuses], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter deals by responsible user
     */
    public function forUser(string $userId, array $options = []): array
    {
        return $this->list(
            array_merge(['responsible_user_id' => $userId], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter deals by pipeline
     */
    public function inPipeline(array $pipelineIds, array $options = []): array
    {
        return $this->list(
            array_merge(['pipeline_ids' => $pipelineIds], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Filter deals by closing date range
     */
    public function closingBetween(string $from, string $until, array $options = []): array
    {
        return $this->list(
            array_merge([
                'estimated_closing_date_from' => $from,
                'estimated_closing_date_until' => $until
            ], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get deals updated since a specific date
     */
    public function updatedSince(string $datetime, array $options = []): array
    {
        return $this->list(
            array_merge(['updated_since' => $datetime], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get open deals
     */
    public function open(array $options = []): array
    {
        return $this->withStatus(['open'], $options);
    }

    /**
     * Get won deals
     */
    public function won(array $options = []): array
    {
        return $this->withStatus(['won'], $options);
    }

    /**
     * Get lost deals
     */
    public function lost(array $options = []): array
    {
        return $this->withStatus(['lost'], $options);
    }

    /**
     * Fluent interface for custom fields
     */
    public function withCustomFields(): self
    {
        return $this->with('custom_fields');
    }

    /**
     * Validate deal data for create/update operations
     */
    private function validateDealData(array $data, string $operation): array
    {
        if ($operation === 'create') {
            // Required fields for creation
            if (!isset($data['lead']['customer']['type']) || !isset($data['lead']['customer']['id'])) {
                throw new InvalidArgumentException('Deal must have a valid customer (lead.customer.type and lead.customer.id are required)');
            }

            if (!isset($data['title']) || empty(trim($data['title']))) {
                throw new InvalidArgumentException('Deal title is required');
            }
        }

        // Validate customer structure if provided
        if (isset($data['lead']['customer'])) {
            $customer = $data['lead']['customer'];
            if (!in_array($customer['type'] ?? '', ['contact', 'company'])) {
                throw new InvalidArgumentException('Customer type must be either "contact" or "company"');
            }
        }

        // Validate estimated value if provided
        if (isset($data['estimated_value'])) {
            if (!isset($data['estimated_value']['amount']) || !isset($data['estimated_value']['currency'])) {
                throw new InvalidArgumentException('Estimated value must include both amount and currency');
            }
        }

        // Validate estimated probability if provided
        if (isset($data['estimated_probability'])) {
            $probability = $data['estimated_probability'];
            if ($probability < 0 || $probability > 1) {
                throw new InvalidArgumentException('Estimated probability must be between 0 and 1 (inclusive)');
            }
        }

        return $data;
    }

    /**
     * Apply pending includes to request body for list operations - FIXED
     */
    private function applyPendingIncludesToRequestBody(array $requestBody): array
    {
        if (!empty($this->pendingIncludes)) {
            $requestBody['includes'] = implode(',', $this->pendingIncludes);
            // Clear pending includes - fixed method name
            $this->pendingIncludes = [];
        }

        return $requestBody;
    }
}

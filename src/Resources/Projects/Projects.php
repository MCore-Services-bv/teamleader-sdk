<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Other\Accounts;
use McoreServices\TeamleaderSDK\Resources\Resource;

/**
 * Projects — the current ("nextgen") project system in Teamleader Focus.
 *
 * Teamleader uses three different names for this one thing, and they do not
 * line up. To be explicit:
 *
 *     SDK method   Teamleader::projects()  (alias: Teamleader::nextgenProjects())
 *     API path     projects-v2/projects.*
 *     Webhooks     nextgenProject.created, nextgenProject.updated,
 *                  nextgenProject.closed, nextgenProject.deleted
 *
 * Reading the webhook names, it is natural to assume `projects()` must be the
 * legacy resource and that some `nextgenProjects()` covers the new system. It is
 * the other way round: this class is what Teamleader calls "nextgen", and
 * `nextgenProjects()` is registered as an alias for exactly that reason.
 *
 * The old system is {@see LegacyProjects}, which uses the bare `projects.*`
 * path — so the shorter path belongs to the older resource. Teamleader chose the
 * `projects-v2` prefix for the new module to avoid colliding with those existing
 * endpoints.
 *
 * **Do not infer which system an account is on from whether a list call returns
 * rows.** Both endpoints answer, so querying this resource and receiving real
 * projects does not mean the account is on nextgen. Ask directly:
 *
 *     Teamleader::accounts()->getProjectsVersion();   // "projects-v2" or "legacy"
 *     Teamleader::accounts()->isUsingProjectsV2();    // bool
 *
 * @see LegacyProjects For accounts still on the old project system
 * @see Accounts::getProjectsVersion()
 * @see https://developer.focus.teamleader.eu/docs/api/projects-v2-projects-list
 */
class Projects extends Resource
{
    protected string $description = 'Manage projects in Teamleader Focus — the current "nextgen" project system (API path projects-v2/projects, webhook events nextgenProject.*)';

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
        'legacy_project',
        'custom_fields',
    ];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of project UUIDs to filter by',
        'status' => 'Project status (open, planned, running, overdue, over_budget, closed)',
        'quotation_ids' => 'Array of quotation UUIDs',
        'deal_ids' => 'Array of deal UUIDs',
        'term' => 'Search term (searches project number, title, customer names, assignee names, owner names)',
        'customers' => 'Array of customer objects [{type, id}]',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'amount_billed',
        'amount_paid',
        'amount_unbilled',
        'cost',
        'customer',
        'end_date',
        'external_budget_spent',
        'external_budget',
        'internal_budget',
        'margin',
        'price',
        'project_key',
        'start_date',
        'status',
        'time_budget',
        'time_estimated',
        'time_tracked',
        'title',
    ];

    // Available billing methods
    protected array $billingMethods = [
        'time_and_materials',
        'fixed_price',
        'non_billable',
    ];

    // Available project colors
    protected array $availableColors = [
        '#00B2B2', '#008A8C', '#992600', '#ED9E00', '#D157D3',
        '#A400B2', '#0071F2', '#004DA6', '#64788F', '#C0C0C4',
        '#82828C', '#1A1C20',
    ];

    // Usage examples
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all projects',
            'code' => '$projects = $teamleader->projects()->list();',
        ],
        'filter_by_status' => [
            'description' => 'Get open projects',
            'code' => '$projects = $teamleader->projects()->open();',
        ],
        'create_project' => [
            'description' => 'Create a new project',
            'code' => '$project = $teamleader->projects()->create([...]);',
        ],
        'close_project' => [
            'description' => 'Close a project',
            'code' => '$result = $teamleader->projects()->close("project-uuid");',
        ],
        'nextgen_alias' => [
            'description' => 'Same resource under the name Teamleader uses for its webhook events',
            'code' => '$projects = $teamleader->nextgenProjects()->list(); // identical to projects()',
        ],
        'check_which_system' => [
            'description' => 'Check which project system the account is on before querying',
            'code' => 'if ($teamleader->accounts()->isUsingProjectsV2()) {
                $projects = $teamleader->projects()->list();
            } else {
                $projects = $teamleader->legacyProjects()->list();
            }',
        ],
    ];

    /**
     * Get the base path for the projects resource
     */
    protected function getBasePath(): string
    {
        return 'projects-v2/projects';
    }

    /**
     * Get detailed information about a specific project
     *
     * @param  string  $id  Project UUID
     * @param  mixed  $includes  Optional includes (legacy_project, custom_fields)
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];

        if (! empty($includes)) {
            $params = $this->applyIncludes($params, $includes);
        }

        return $this->api->request('POST', $this->getBasePath().'.info', $params);
    }

    /**
     * List projects with filtering and sorting
     *
     * @param  array  $filters  Filters to apply
     * @param  array  $options  Pagination and sorting options
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Build filter object
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
        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort']);
        }

        // Apply includes
        if (! empty($options['includes'])) {
            $params['includes'] = is_array($options['includes'])
                ? implode(',', $options['includes'])
                : $options['includes'];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Create a new project
     *
     * @param  array  $data  Project data
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update an existing project
     *
     * All fields are pass-through. Notable nullable fields, used mainly when
     * seeding a project with data migrated from another system (2026-05-06):
     * - initial_time_tracked (object|null): {value, unit: hours|minutes|seconds}
     * - initial_price (object|null): {amount, currency}
     * - initial_cost (object|null): {amount, currency}
     * - initial_amount_billed (object|null): {amount, currency}
     * - initial_amount_paid (object|null): {amount, currency}
     *
     * @param  string  $id  Project UUID
     * @param  array  $data  Data to update
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $deleteStrategy  Strategy for handling tasks and time trackings
     */
    /**
     * Delete a project
     *
     * @param  string  $id  Project UUID
     * @param  mixed  ...$additionalParams  Additional parameters (deleteStrategy)
     */
    public function delete($id, ...$additionalParams): array
    {
        // Get deleteStrategy from additional params or use default
        $deleteStrategy = $additionalParams[0] ?? 'unlink_tasks_and_time_trackings';

        $validStrategies = [
            'unlink_tasks_and_time_trackings',
            'delete_tasks_and_time_trackings',
            'delete_tasks_unlink_time_trackings',
        ];

        if (! in_array($deleteStrategy, $validStrategies)) {
            throw new InvalidArgumentException('Invalid delete strategy. Must be one of: '.implode(', ', $validStrategies));
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
            'delete_strategy' => $deleteStrategy,
        ]);
    }

    /**
     * Duplicate a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $title  Title for the new project
     */
    public function duplicate(string $id, string $title): array
    {
        return $this->api->request('POST', $this->getBasePath().'.duplicate', [
            'id' => $id,
            'title' => $title,
        ]);
    }

    /**
     * Close a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $closingStrategy  Strategy for closing (mark_tasks_and_materials_as_done, none)
     */
    public function close(string $id, string $closingStrategy = 'none'): array
    {
        $validStrategies = ['mark_tasks_and_materials_as_done', 'none'];

        if (! in_array($closingStrategy, $validStrategies)) {
            throw new InvalidArgumentException('Invalid closing strategy. Must be one of: '.implode(', ', $validStrategies));
        }

        return $this->api->request('POST', $this->getBasePath().'.close', [
            'id' => $id,
            'closing_strategy' => $closingStrategy,
        ]);
    }

    /**
     * Reopen a closed project
     *
     * @param  string  $id  Project UUID
     */
    public function reopen(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.reopen', [
            'id' => $id,
        ]);
    }

    /**
     * Add a customer to a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $customerType  Customer type (contact, company)
     * @param  string  $customerId  Customer UUID
     */
    public function addCustomer(string $id, string $customerType, string $customerId): array
    {
        $this->validateCustomerType($customerType);

        return $this->api->request('POST', $this->getBasePath().'.addCustomer', [
            'id' => $id,
            'customer' => [
                'type' => $customerType,
                'id' => $customerId,
            ],
        ]);
    }

    /**
     * Remove a customer from a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $customerType  Customer type (contact, company)
     * @param  string  $customerId  Customer UUID
     */
    public function removeCustomer(string $id, string $customerType, string $customerId): array
    {
        $this->validateCustomerType($customerType);

        return $this->api->request('POST', $this->getBasePath().'.removeCustomer', [
            'id' => $id,
            'customer' => [
                'type' => $customerType,
                'id' => $customerId,
            ],
        ]);
    }

    /**
     * Add a deal to a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $dealId  Deal UUID
     */
    public function addDeal(string $id, string $dealId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.addDeal', [
            'id' => $id,
            'deal_id' => $dealId,
        ]);
    }

    /**
     * Remove a deal from a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $dealId  Deal UUID
     */
    public function removeDeal(string $id, string $dealId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.removeDeal', [
            'id' => $id,
            'deal_id' => $dealId,
        ]);
    }

    /**
     * Add a quotation to a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $quotationId  Quotation UUID
     */
    public function addQuotation(string $id, string $quotationId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.addQuotation', [
            'id' => $id,
            'quotation_id' => $quotationId,
        ]);
    }

    /**
     * Remove a quotation from a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $quotationId  Quotation UUID
     */
    public function removeQuotation(string $id, string $quotationId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.removeQuotation', [
            'id' => $id,
            'quotation_id' => $quotationId,
        ]);
    }

    /**
     * Add an owner to a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $userId  User UUID
     */
    public function addOwner(string $id, string $userId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.addOwner', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * Remove an owner from a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $userId  User UUID
     */
    public function removeOwner(string $id, string $userId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.removeOwner', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * Assign a user or team to a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $assigneeType  Assignee type (user, team)
     * @param  string  $assigneeId  Assignee UUID
     */
    public function assign(string $id, string $assigneeType, string $assigneeId): array
    {
        $this->validateAssigneeType($assigneeType);

        return $this->api->request('POST', $this->getBasePath().'.assign', [
            'id' => $id,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId,
            ],
        ]);
    }

    /**
     * Unassign a user or team from a project
     *
     * @param  string  $id  Project UUID
     * @param  string  $assigneeType  Assignee type (user, team)
     * @param  string  $assigneeId  Assignee UUID
     */
    public function unassign(string $id, string $assigneeType, string $assigneeId): array
    {
        $this->validateAssigneeType($assigneeType);

        return $this->api->request('POST', $this->getBasePath().'.unassign', [
            'id' => $id,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId,
            ],
        ]);
    }

    // ===== Convenience Methods =====

    /**
     * Get open projects
     */
    public function open(array $options = []): array
    {
        return $this->list(['status' => 'open'], $options);
    }

    /**
     * Get closed projects
     */
    public function closed(array $options = []): array
    {
        return $this->list(['status' => 'closed'], $options);
    }

    /**
     * Get running projects
     */
    public function running(array $options = []): array
    {
        return $this->list(['status' => 'running'], $options);
    }

    /**
     * Get overdue projects
     */
    public function overdue(array $options = []): array
    {
        return $this->list(['status' => 'overdue'], $options);
    }

    /**
     * Get over budget projects
     */
    public function overBudget(array $options = []): array
    {
        return $this->list(['status' => 'over_budget'], $options);
    }

    /**
     * Search projects by term
     *
     * @param  string  $term  Search term
     * @param  array  $options  Additional options
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(['term' => $term], $options);
    }

    /**
     * Get projects by IDs
     *
     * @param  array  $ids  Array of project UUIDs
     * @param  array  $options  Additional options
     */
    public function byIds(array $ids, array $options = []): array
    {
        return $this->list(['ids' => $ids], $options);
    }

    /**
     * Get projects for a customer
     *
     * @param  string  $customerType  Customer type (contact, company)
     * @param  string  $customerId  Customer UUID
     * @param  array  $options  Additional options
     */
    public function forCustomer(string $customerType, string $customerId, array $options = []): array
    {
        $this->validateCustomerType($customerType);

        return $this->list([
            'customers' => [
                ['type' => $customerType, 'id' => $customerId],
            ],
        ], $options);
    }

    /**
     * Get projects linked to a deal
     *
     * @param  string  $dealId  Deal UUID
     * @param  array  $options  Additional options
     */
    public function forDeal(string $dealId, array $options = []): array
    {
        return $this->list(['deal_ids' => [$dealId]], $options);
    }

    /**
     * Get projects linked to a quotation
     *
     * @param  string  $quotationId  Quotation UUID
     * @param  array  $options  Additional options
     */
    public function forQuotation(string $quotationId, array $options = []): array
    {
        return $this->list(['quotation_ids' => [$quotationId]], $options);
    }

    // ===== Validation Methods =====

    /**
     * Validate project creation data
     *
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): void
    {
        if (empty($data['title'])) {
            throw new InvalidArgumentException('Title is required for creating a project');
        }

        if (isset($data['billing_method']) && ! in_array($data['billing_method'], $this->billingMethods)) {
            throw new InvalidArgumentException(
                'Invalid billing method. Must be one of: '.implode(', ', $this->billingMethods)
            );
        }

        if (isset($data['color']) && ! in_array($data['color'], $this->availableColors)) {
            throw new InvalidArgumentException(
                'Invalid color. Must be one of the predefined colors.'
            );
        }
    }

    /**
     * Validate customer type
     *
     * @throws InvalidArgumentException
     */
    protected function validateCustomerType(string $type): void
    {
        $validTypes = ['contact', 'company'];
        if (! in_array($type, $validTypes)) {
            throw new InvalidArgumentException(
                'Invalid customer type. Must be one of: '.implode(', ', $validTypes)
            );
        }
    }

    /**
     * Validate assignee type
     *
     * @throws InvalidArgumentException
     */
    protected function validateAssigneeType(string $type): void
    {
        $validTypes = ['user', 'team'];
        if (! in_array($type, $validTypes)) {
            throw new InvalidArgumentException(
                'Invalid assignee type. Must be one of: '.implode(', ', $validTypes)
            );
        }
    }

    /**
     * Build filters array for API request
     */
    protected function buildFilters(array $filters): array
    {
        $formatted = [];

        foreach ($filters as $key => $value) {
            // Pass through complex filter structures as-is
            if (in_array($key, ['customers'])) {
                $formatted[$key] = $value;
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Build sort array for API request
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        if (isset($sort['field'])) {
            // Single sort field
            return [[
                'field' => $sort['field'],
                'order' => $sort['order'] ?? 'desc',
            ]];
        }

        // Multiple sort fields
        return array_map(function ($item) {
            return [
                'field' => $item['field'],
                'order' => $item['order'] ?? 'desc',
            ];
        }, $sort);
    }
}

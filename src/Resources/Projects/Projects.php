<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Other\Accounts;

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
class Projects extends ProjectsV2Resource
{
    /** Body fields projects.create accepts */
    public const CREATE_FIELDS = [
        'title', 'description', 'owner_ids', 'time_budget', 'billing_method', 'external_budget',
        'internal_budget', 'fixed_price', 'start_date', 'end_date', 'purchase_order_number',
        'company_entity_id', 'color', 'customers', 'assignees', 'deal_ids', 'quotation_ids',
        'initial_time_tracked', 'initial_price', 'initial_cost', 'initial_amount_billed',
        'initial_amount_paid', 'custom_fields',
    ];

    /** Body fields projects.update accepts, besides `id` */
    public const UPDATE_FIELDS = [
        'title', 'description', 'time_budget', 'billing_method', 'external_budget', 'internal_budget',
        'fixed_price', 'start_date', 'end_date', 'purchase_order_number', 'company_entity_id', 'color',
        'initial_time_tracked', 'initial_price', 'initial_cost', 'initial_amount_billed',
        'initial_amount_paid', 'custom_fields',
    ];

    public const BILLING_METHODS = ['time_and_materials', 'fixed_price', 'non_billable'];

    /** `filter.status` on projects.list */
    public const STATUSES = ['open', 'planned', 'running', 'overdue', 'over_budget', 'closed'];

    public const CUSTOMER_TYPES = ['contact', 'company'];

    public const DELETE_STRATEGIES = [
        'unlink_tasks_and_time_trackings',
        'delete_tasks_and_time_trackings',
        'delete_tasks_unlink_time_trackings',
    ];

    public const CLOSING_STRATEGIES = ['mark_tasks_and_materials_as_done', 'none'];

    /** Includes projects.list accepts (besides `pagination`, which is always requested) */
    public const INCLUDES = ['custom_fields', 'legacy_project'];

    /** Includes projects.info accepts */
    public const INFO_INCLUDES = ['legacy_project'];

    private const MONEY_FIELDS = [
        'external_budget', 'internal_budget', 'fixed_price',
        'initial_price', 'initial_cost', 'initial_amount_billed', 'initial_amount_paid',
    ];

    private const DURATION_FIELDS = ['time_budget', 'initial_time_tracked'];

    protected string $description = 'Manage projects in Teamleader Focus — the current "nextgen" project system (API path projects-v2/projects, webhook events nextgenProject.*)';

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsSideloading = true;

    protected bool $supportsFiltering = true;

    /**
     * `includes=pagination` adds a meta block with the total match count —
     * projects.list documents it, so it is requested on every call.
     */
    protected bool $requestsPaginationMeta = true;

    protected array $availableIncludes = self::INCLUDES;

    /**
     * projects.info accepts `legacy_project` only; custom fields are always
     * part of an info response.
     */
    protected array $infoIncludes = self::INFO_INCLUDES;

    protected array $defaultIncludes = [];

    protected array $commonFilters = [
        'ids' => 'Array of project UUIDs to filter by',
        'status' => 'Project status (open, planned, running, overdue, over_budget, closed)',
        'quotation_ids' => 'Array of quotation UUIDs',
        'deal_ids' => 'Array of deal UUIDs',
        'term' => 'Search term (searches project number, title, customer names, assignee names, owner names)',
        'customers' => 'Array of customer objects [{type, id}]',
    ];

    /**
     * Sort fields projects.list accepts. A keyed map since v2.2.9, so the
     * field is validated; before, a mistyped field was sent and ignored.
     */
    protected array $availableSortFields = [
        'amount_billed' => 'Amount billed',
        'amount_paid' => 'Amount paid',
        'amount_unbilled' => 'Amount not yet billed',
        'cost' => 'Cost',
        'customer' => 'Customer name',
        'end_date' => 'End date',
        'external_budget' => 'External budget',
        'external_budget_spent' => 'External budget spent',
        'internal_budget' => 'Internal budget',
        'margin' => 'Margin',
        'price' => 'Price',
        'project_key' => 'Project number',
        'start_date' => 'Start date',
        'status' => 'Status',
        'time_budget' => 'Time budget',
        'time_estimated' => 'Estimated time',
        'time_tracked' => 'Tracked time',
        'title' => 'Title',
    ];

    // Kept for backwards compatibility — see the constants above
    protected array $billingMethods = self::BILLING_METHODS;

    protected array $availableColors = self::COLORS;

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

    protected function getBasePath(): string
    {
        return 'projects-v2/projects';
    }

    /**
     * Get one project
     *
     * @param  string  $id  Project UUID
     * @param  mixed  $includes  legacy_project only — custom fields are always returned
     *
     * @throws InvalidArgumentException On an include projects.info does not accept
     */
    public function info($id, $includes = null): array
    {
        $params = ['id' => $id];
        $includes = $this->assertIncludes($includes, self::INFO_INCLUDES, $this->getBasePath().'.info');

        return $this->api->request('POST', $this->getBasePath().'.info', $this->applyIncludes($params, $includes));
    }

    /**
     * List projects
     *
     * `includes=pagination` is always sent, so the response carries
     * `meta.matches` with the total number of matching projects.
     *
     * @param  array  $filters  ids, status, quotation_ids, deal_ids, term, customers
     * @param  array  $options  page_size, page_number, sort, sort_order, include(s)
     *
     * @throws InvalidArgumentException On an unknown filter key, option, sort field or include
     */
    public function list(array $filters = [], array $options = []): array
    {
        $endpoint = $this->getBasePath().'.list';

        $this->rejectUnknownOptions(
            $options,
            ['page_size', 'page_number', 'sort', 'sort_order', 'include', 'includes'],
            $endpoint
        );

        $params = [];
        $filter = $this->buildFilters($filters);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        if (($page = $this->pageFromOptions($options)) !== null) {
            $params['page'] = $page;
        }

        if (! empty($options['sort'])) {
            $params['sort'] = $this->buildSort($options['sort'], $options['sort_order'] ?? 'desc');
        }

        $pending = $this->getPendingIncludes();
        $this->pendingIncludes = [];

        $includes = $this->assertIncludes(
            [...(array) ($this->resolveIncludesOption($options) ?? []), ...$pending],
            [...self::INCLUDES, 'pagination'],
            $endpoint
        );

        if (! in_array('pagination', $includes, true)) {
            $includes[] = 'pagination';
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $this->applyIncludes($params, $includes));
    }

    /**
     * Create a project
     *
     * Only title is required.
     *
     * @throws InvalidArgumentException When title is missing, or a field or value is not accepted
     */
    public function create(array $data): array
    {
        $this->validateCreateData($data);

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update a project
     *
     * billing_method is sent as {value, update_strategy}. A plain method name
     * is accepted and sent with update_strategy `none`.
     *
     * The initial_* fields (2026-05-06) seed a project with figures migrated
     * from another system: initial_time_tracked {value, unit} and
     * initial_price / initial_cost / initial_amount_billed /
     * initial_amount_paid {amount, currency}. Null clears them.
     *
     * Customers, deals, quotations and owners are not update fields; use
     * addCustomer(), addDeal(), addQuotation(), addOwner() and their remove*
     * counterparts.
     *
     * @param  string  $id  Project UUID
     *
     * @throws InvalidArgumentException When a field or value is not accepted
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $endpoint = $this->getBasePath().'.update';

        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        $data = $this->normaliseBillingMethodUpdate($data, self::BILLING_METHODS, $endpoint);
        $this->validateCommonFields($data, $endpoint);

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a project
     *
     * @param  string  $id  Project UUID
     * @param  mixed  ...$additionalParams  Delete strategy: unlink_tasks_and_time_trackings (default),
     *                                      delete_tasks_and_time_trackings or
     *                                      delete_tasks_unlink_time_trackings
     *
     * @throws InvalidArgumentException
     */
    public function delete($id, ...$additionalParams): array
    {
        $strategy = $additionalParams[0] ?? 'unlink_tasks_and_time_trackings';
        $this->assertEnum($strategy, self::DELETE_STRATEGIES, 'delete_strategy', $this->getBasePath().'.delete');

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
            'delete_strategy' => $strategy,
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
     * @param  string  $closingStrategy  none (default) or mark_tasks_and_materials_as_done
     *
     * @throws InvalidArgumentException
     */
    public function close(string $id, string $closingStrategy = 'none'): array
    {
        $this->assertEnum($closingStrategy, self::CLOSING_STRATEGIES, 'closing_strategy', $this->getBasePath().'.close');

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
        $this->assertEnum($customerType, self::CUSTOMER_TYPES, 'customer.type', $this->getBasePath().'.addCustomer');

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
        $this->assertEnum($customerType, self::CUSTOMER_TYPES, 'customer.type', $this->getBasePath().'.removeCustomer');

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
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): void
    {
        $endpoint = $this->getBasePath().'.create';

        if (empty($data['title'])) {
            throw new InvalidArgumentException('Title is required for creating a project');
        }

        $this->rejectUnknownFields($data, self::CREATE_FIELDS, $endpoint);
        $this->assertEnum($data['billing_method'] ?? null, self::BILLING_METHODS, 'billing_method', $endpoint);
        $this->assertItemEnum($data, 'customers', 'type', self::CUSTOMER_TYPES, $endpoint);
        $this->assertAssignees($data, $endpoint);
        $this->validateCommonFields($data, $endpoint);
    }

    /**
     * Checks create and update share
     *
     * @throws InvalidArgumentException
     */
    private function validateCommonFields(array $data, string $endpoint): void
    {
        $this->assertEnum($data['color'] ?? null, self::COLORS, 'color', $endpoint);
        $this->assertMoney($data, self::MONEY_FIELDS, $endpoint);
        $this->assertDuration($data, self::DURATION_FIELDS, $endpoint);
        $this->assertDates($data, ['start_date', 'end_date'], $endpoint);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateCustomerType(string $type): void
    {
        $this->assertEnum($type, self::CUSTOMER_TYPES, 'customer.type', $this->getBasePath());
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateAssigneeType(string $type): void
    {
        $this->assertEnum($type, self::ASSIGNEE_TYPES, 'assignee.type', $this->getBasePath().'.assign');
    }

    /**
     * Build the filter object for projects.list
     *
     * @throws InvalidArgumentException On an unknown key or value
     */
    protected function buildFilters(array $filters): array
    {
        $endpoint = $this->getBasePath().'.list';

        $this->rejectUnknownFilters($filters, $endpoint);
        $this->assertEnum($filters['status'] ?? null, self::STATUSES, 'filter.status', $endpoint);
        $this->assertItemEnum($filters, 'customers', 'type', self::CUSTOMER_TYPES, $endpoint);

        // ids, deal_ids and quotation_ids are arrays; a lone string is wrapped
        return $this->wrapArrayFilters($filters, ['ids', 'deal_ids', 'quotation_ids']);
    }

    /**
     * Build the sort array for projects.list
     *
     * @throws InvalidArgumentException On an unknown field or order
     */
    protected function buildSort($sort, string $order = 'desc'): array
    {
        return $this->normaliseSort($sort, $order);
    }
}

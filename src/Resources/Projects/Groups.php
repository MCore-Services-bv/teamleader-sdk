<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;

/**
 * Project groups in the current ("nextgen") project system — `projects-v2/projectGroups.*`.
 */
class Groups extends ProjectsV2Resource
{
    /** Body fields projectGroups.create accepts */
    public const CREATE_FIELDS = [
        'project_id', 'title', 'description', 'color', 'billing_method', 'fixed_price',
        'external_budget', 'internal_budget', 'start_date', 'end_date', 'assignees',
    ];

    /** Body fields projectGroups.update accepts, besides `id` */
    public const UPDATE_FIELDS = [
        'title', 'description', 'color', 'billing_method', 'fixed_price',
        'external_budget', 'internal_budget', 'start_date', 'end_date',
    ];

    public const BILLING_METHODS = ['time_and_materials', 'fixed_price', 'parent_fixed_price', 'non_billable'];

    /** `billing_status` on info responses */
    public const BILLING_STATUSES = ['not_billable', 'not_billed', 'partially_billed', 'fully_billed'];

    public const DELETE_STRATEGIES = [
        'ungroup_tasks_and_materials',
        'delete_tasks_and_materials',
        'delete_tasks_materials_and_unbilled_timetrackings',
    ];

    private const MONEY_FIELDS = ['fixed_price', 'external_budget', 'internal_budget'];

    protected string $description = 'Manage project groups in Teamleader Focus (New Projects API)';

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    protected array $availableIncludes = [];

    protected array $defaultIncludes = [];

    protected array $commonFilters = [
        'ids' => 'Array of group UUIDs to filter by',
        'project_id' => 'Filter groups by project UUID',
    ];

    // Kept for backwards compatibility — see the constants above
    protected array $billingMethods = self::BILLING_METHODS;

    protected array $billingStatuses = self::BILLING_STATUSES;

    protected array $assigneeTypes = self::ASSIGNEE_TYPES;

    protected array $deleteStrategies = self::DELETE_STRATEGIES;

    protected array $updateStrategies = self::UPDATE_STRATEGIES;

    // Usage examples specific to project groups
    protected array $usageExamples = [
        'list_for_project' => [
            'description' => 'Get all groups for a specific project',
            'code' => '$groups = $teamleader->groups()->forProject("project-uuid");',
        ],
        'create_group' => [
            'description' => 'Create a new project group',
            'code' => '$group = $teamleader->groups()->create([
                "project_id" => "project-uuid",
                "title" => "Phase 1: Design",
                "description" => "Initial design phase",
                "color" => "#00B2B2",
                "billing_method" => "fixed_price",
                "fixed_price" => ["amount" => 5000, "currency" => "EUR"]
            ]);',
        ],
        'update_group' => [
            'description' => 'Update a project group',
            'code' => '$group = $teamleader->groups()->update("group-uuid", [
                "title" => "Phase 1: Design & Planning",
                "start_date" => "2023-01-18",
                "end_date" => "2023-03-22"
            ]);',
        ],
        'assign_user' => [
            'description' => 'Assign a user to a group',
            'code' => '$result = $teamleader->groups()->assign("group-uuid", "user", "user-uuid");',
        ],
        'duplicate_group' => [
            'description' => 'Duplicate a group',
            'code' => '$newGroup = $teamleader->groups()->duplicate("origin-group-uuid");',
        ],
        'delete_group' => [
            'description' => 'Delete a group',
            'code' => '$result = $teamleader->groups()->delete("group-uuid", "ungroup_tasks_and_materials");',
        ],
    ];

    protected function getBasePath(): string
    {
        return 'projects-v2/projectGroups';
    }

    /**
     * List project groups
     *
     * Before v2.2.9 unknown filter keys and a string `ids` were dropped without
     * a word, as were the paging options.
     *
     * @param  array  $filters  ids (a UUID or a list of UUIDs), project_id
     * @param  array  $options  page_size, page_number
     *
     * @throws InvalidArgumentException On an unknown filter key or option
     */
    public function list(array $filters = [], array $options = []): array
    {
        $endpoint = $this->getBasePath().'.list';

        $this->rejectUnknownFilters($filters, $endpoint);
        $this->rejectUnknownOptions($options, ['page_size', 'page_number'], $endpoint);

        $params = [];
        $filter = $this->wrapArrayFilters($filters, ['ids']);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        if (($page = $this->pageFromOptions($options)) !== null) {
            $params['page'] = $page;
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get all groups of a project
     *
     * @param  string  $projectId  Project UUID
     * @param  array  $options  page_size, page_number
     */
    public function forProject(string $projectId, array $options = []): array
    {
        return $this->list(['project_id' => $projectId], $options);
    }

    /**
     * Get project groups by ID
     *
     * @param  array  $ids  Group UUIDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get one group
     *
     * @param  string  $id  Group UUID
     * @param  mixed  $includes  projectGroups.info takes no includes; passing any throws
     */
    public function info($id, $includes = null): array
    {
        $this->assertIncludes($includes, [], $this->getBasePath().'.info');

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Create a project group
     *
     * Requires project_id and title. fixed_price is only allowed with billing
     * method fixed_price, external_budget only with time_and_materials.
     *
     * @throws InvalidArgumentException When a required field is missing, or a field or value is not accepted
     */
    public function create(array $data): array
    {
        return $this->api->request('POST', $this->getBasePath().'.create', $this->validateCreateData($data));
    }

    /**
     * Update a project group
     *
     * billing_method is sent as {value, update_strategy}. A plain method name
     * is accepted and sent with update_strategy `none` (this group only);
     * `cascade` applies it to the group's tasks and materials as well.
     *
     * @param  string  $id  Group UUID
     *
     * @throws InvalidArgumentException When a field or value is not accepted
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath().'.update', $this->validateUpdateData($data));
    }

    /**
     * Delete a project group
     *
     * @param  string  $id  Group UUID
     * @param  string  ...$additionalParams  Delete strategy: ungroup_tasks_and_materials (default),
     *                                       delete_tasks_and_materials or
     *                                       delete_tasks_materials_and_unbilled_timetrackings
     *
     * @throws InvalidArgumentException
     */
    public function delete($id, ...$additionalParams): array
    {
        $strategy = $additionalParams[0] ?? 'ungroup_tasks_and_materials';
        $this->assertEnum($strategy, self::DELETE_STRATEGIES, 'delete_strategy', $this->getBasePath().'.delete');

        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
            'delete_strategy' => $strategy,
        ]);
    }

    /**
     * Duplicate a project group and its entities (without time trackings)
     *
     * @param  string  $originId  UUID of the group to duplicate
     */
    public function duplicate(string $originId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.duplicate', [
            'origin_id' => $originId,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): array
    {
        $endpoint = $this->getBasePath().'.create';

        if (empty($data['project_id'])) {
            throw new InvalidArgumentException('project_id is required');
        }

        if (empty($data['title'])) {
            throw new InvalidArgumentException('title is required');
        }

        $this->rejectUnknownFields($data, self::CREATE_FIELDS, $endpoint);
        $this->assertEnum($data['billing_method'] ?? null, self::BILLING_METHODS, 'billing_method', $endpoint);
        $this->validateCommonFields($data, $endpoint);
        $this->assertAssignees($data, $endpoint);

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateUpdateData(array $data): array
    {
        $endpoint = $this->getBasePath().'.update';

        if (empty($data['id'])) {
            throw new InvalidArgumentException('id is required for update');
        }

        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        $data = $this->normaliseBillingMethodUpdate($data, self::BILLING_METHODS, $endpoint);
        $this->validateCommonFields($data, $endpoint);

        return $data;
    }

    private function validateCommonFields(array $data, string $endpoint): void
    {
        $this->assertEnum($data['color'] ?? null, self::COLORS, 'color', $endpoint);
        $this->assertMoney($data, self::MONEY_FIELDS, $endpoint);
        $this->assertDates($data, ['start_date', 'end_date'], $endpoint);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateColor(string $color): void
    {
        $this->assertEnum($color, self::COLORS, 'color', $this->getBasePath().'.create');
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateDate(string $date, string $fieldName): void
    {
        $this->assertDates([$fieldName => $date], [$fieldName], $this->getBasePath());
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateAssigneeType(string $type): void
    {
        $this->assertEnum($type, self::ASSIGNEE_TYPES, 'assignee.type', $this->getBasePath().'.assign');
    }

    /**
     * @return list<string>
     */
    public function getAvailableBillingMethods(): array
    {
        return self::BILLING_METHODS;
    }

    /**
     * @return list<string>
     */
    public function getAvailableDeleteStrategies(): array
    {
        return self::DELETE_STRATEGIES;
    }
}

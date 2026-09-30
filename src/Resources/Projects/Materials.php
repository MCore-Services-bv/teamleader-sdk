<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;

/**
 * Materials in the current ("nextgen") project system — `projects-v2/materials.*`.
 */
class Materials extends ProjectsV2Resource
{
    /** Body fields materials.create accepts */
    public const CREATE_FIELDS = [
        'project_id', 'title', 'group_id', 'after_id', 'description', 'billing_method',
        'quantity', 'quantity_estimated', 'unit_price', 'unit_cost', 'unit_id', 'fixed_price',
        'external_budget', 'internal_budget', 'start_date', 'end_date', 'product_id', 'assignees',
    ];

    /** Body fields materials.update accepts, besides `id` */
    public const UPDATE_FIELDS = [
        'title', 'description', 'status', 'billing_method', 'quantity', 'quantity_estimated',
        'unit_price', 'unit_cost', 'unit_id', 'fixed_price', 'external_budget', 'internal_budget',
        'start_date', 'end_date', 'product_id',
    ];

    /** `parent_fixed_price` is only accepted when the parent is fixed price */
    public const BILLING_METHODS = ['fixed_price', 'unit_price', 'non_billable', 'parent_fixed_price'];

    public const STATUSES = ['to_do', 'in_progress', 'on_hold', 'done'];

    private const MONEY_FIELDS = ['unit_price', 'unit_cost', 'fixed_price', 'external_budget', 'internal_budget'];

    protected string $description = 'Manage materials in Teamleader Focus projects';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of material UUIDs',
    ];

    // Kept for backwards compatibility — see the constants above
    protected array $billingMethods = self::BILLING_METHODS;

    protected array $statusValues = self::STATUSES;

    protected array $assigneeTypes = self::ASSIGNEE_TYPES;

    // Usage examples specific to materials
    protected array $usageExamples = [
        'create_material' => [
            'description' => 'Create a new material with unit pricing and quantity tracking',
            'code' => '$material = $teamleader->materials()->create([
                "project_id" => "49b403be-a32e-0901-9b1c-25214f9027c6",
                "title" => "WD-40 Multi-Use Product",
                "description" => "Industrial size lubricant",
                "billing_method" => "unit_price",
                "quantity_estimated" => 12,
                "quantity" => 10,
                "unit_price" => [
                    "amount" => 25.50,
                    "currency" => "EUR"
                ]
            ]);',
        ],
        'update_material' => [
            'description' => 'Update an existing material',
            'code' => '$material = $teamleader->materials()->update(
                "material-uuid",
                [
                    "title" => "Updated Material Name",
                    "status" => "in_progress",
                    "quantity" => 15,
                    "quantity_estimated" => 20
                ]
            );',
        ],
        'get_material_info' => [
            'description' => 'Get detailed information about a material',
            'code' => '$material = $teamleader->materials()->info("material-uuid");',
        ],
        'list_materials' => [
            'description' => 'List materials by IDs',
            'code' => '$materials = $teamleader->materials()->list([
                "ids" => ["uuid1", "uuid2"]
            ]);',
        ],
        'track_quantity_vs_estimate' => [
            'description' => 'Create a material with an estimate then update with actual quantity',
            'code' => '$result = $teamleader->materials()->create([
                "project_id" => "project-uuid",
                "title" => "Copper pipe (meters)",
                "billing_method" => "unit_price",
                "quantity_estimated" => 25,
                "unit_price" => ["amount" => 8.50, "currency" => "EUR"],
            ]);
            // Later, update with actual usage
            $teamleader->materials()->update($result["data"]["id"], [
                "quantity" => 22,
                "status" => "done",
            ]);',
        ],
        'duplicate_material' => [
            'description' => 'Duplicate a material',
            'code' => '$copy = $teamleader->materials()->duplicate("material-uuid");',
        ],
        'assign_user' => [
            'description' => 'Assign a user to a material',
            'code' => '$teamleader->materials()->assignUser("material-uuid", "user-uuid");',
        ],
        'delete_material' => [
            'description' => 'Delete a material',
            'code' => '$teamleader->materials()->delete("material-uuid");',
        ],
    ];

    /**
     * Get the base path for the materials resource
     */
    protected function getBasePath(): string
    {
        return 'projects-v2/materials';
    }

    /**
     * List materials
     *
     * Before v2.2.9 filter keys other than `ids` were dropped without a word,
     * as were the paging options.
     *
     * Response fields per item:
     * - id, project {id, type}, group (nullable) {id, type: nextgenProjectGroup}
     * - title, description (nullable), status, billing_method, billing_status
     * - quantity, quantity_estimated (nullable numbers)
     * - unit_price, unit_cost, amount_billed, external_budget, external_budget_spent,
     *   internal_budget, price, fixed_price, cost, margin (nullable {amount, currency})
     * - unit (nullable) {id, type: priceunit} — null if the default unit is used
     * - margin_percentage (nullable) — null without "Costs on projects" access
     * - assignees [{assignee: {type, id}, assign_type}]
     * - start_date, end_date (nullable), product (nullable) {id, type: product}
     *
     * @param  array  $filters  ids (a UUID or a list of UUIDs)
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
     * Get one material (same fields as a list() item)
     *
     * @param  string  $id  Material UUID
     * @param  mixed  $includes  materials.info takes no includes; passing any throws
     */
    public function info($id, $includes = null): array
    {
        $this->assertIncludes($includes, [], $this->getBasePath().'.info');

        return $this->api->request('POST', $this->getBasePath().'.info', ['id' => $id]);
    }

    /**
     * Create a material
     *
     * Requires project_id and title. `after_id` null places the material at the
     * top of its project or group; omitting it places it at the bottom.
     *
     * Returns HTTP 201 with data.{id, type}
     *
     * @throws InvalidArgumentException When a required field is missing, or a field or value is not accepted
     */
    public function create(array $data): array
    {
        $this->validateMaterialData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update a material
     *
     * Every field is optional; null clears a nullable field. Returns HTTP 204.
     *
     * @param  string  $id  Material UUID
     *
     * @throws InvalidArgumentException When a field or value is not accepted
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateMaterialData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete a material (new in v2.2.9)
     *
     * @param  string  $id  Material UUID
     * @param  mixed  ...$additionalParams  Not used
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Duplicate a material (new in v2.2.9)
     *
     * @param  string  $originId  UUID of the material to duplicate
     */
    public function duplicate(string $originId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.duplicate', [
            'origin_id' => $originId,
        ]);
    }

    /**
     * Get materials by ID
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Validate a create or update body against the specification
     *
     * @param  string  $operation  'create' or 'update'
     *
     * @throws InvalidArgumentException
     */
    protected function validateMaterialData(array $data, string $operation = 'create'): void
    {
        $endpoint = $this->getBasePath().'.'.$operation;

        if ($operation === 'create') {
            if (empty($data['project_id'])) {
                throw new InvalidArgumentException('project_id is required for creating a material');
            }
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required for creating a material');
            }

            $this->rejectUnknownFields($data, self::CREATE_FIELDS, $endpoint);
        } else {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating a material');
            }

            $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        }

        $this->assertEnum($data['billing_method'] ?? null, self::BILLING_METHODS, 'billing_method', $endpoint);
        $this->assertEnum($data['status'] ?? null, self::STATUSES, 'status', $endpoint);
        $this->assertMoney($data, self::MONEY_FIELDS, $endpoint);
        $this->assertAssignees($data, $endpoint);
        $this->assertDates($data, ['start_date', 'end_date'], $endpoint);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created material ID and type (HTTP 201)',
                'fields' => [
                    'data.id' => 'UUID of the created material',
                    'data.type' => 'Resource type',
                ],
            ],
            'info' => [
                'description' => 'Complete material information',
                'fields' => [
                    'data.id' => 'Material UUID',
                    'data.project' => 'Project reference {id, type}',
                    'data.group' => 'Group reference (nullable) {id, type: nextgenProjectGroup}',
                    'data.title' => 'Material title',
                    'data.description' => 'Material description (nullable)',
                    'data.status' => 'Material status (to_do, in_progress, on_hold, done)',
                    'data.billing_method' => 'Billing method (fixed_price, unit_price, non_billable, parent_fixed_price)',
                    'data.billing_status' => 'Billing status (not_billable, not_billed, partially_billed, fully_billed)',
                    'data.quantity' => 'Actual quantity used (nullable number)',
                    'data.quantity_estimated' => 'Estimated quantity (nullable number)',
                    'data.unit_price' => 'Unit price (nullable) {amount, currency}',
                    'data.unit_cost' => 'Unit cost (nullable) {amount, currency}',
                    'data.unit' => 'Price unit reference (nullable) {id, type: priceunit} — null = default unit',
                    'data.amount_billed' => 'Amount already billed (nullable) {amount, currency}',
                    'data.external_budget' => 'External budget (nullable) {amount, currency}',
                    'data.external_budget_spent' => 'External budget spent (nullable) {amount, currency}',
                    'data.internal_budget' => 'Internal budget (nullable) {amount, currency}',
                    'data.price' => 'Calculated total price (nullable) {amount, currency}',
                    'data.fixed_price' => 'Fixed price (nullable) {amount, currency}',
                    'data.cost' => 'Calculated total cost (nullable) {amount, currency}',
                    'data.margin' => 'Calculated margin (nullable) {amount, currency}',
                    'data.margin_percentage' => 'Margin percentage (nullable) — null if no "Costs on projects" access',
                    'data.assignees' => 'Array of assignees [{assignee: {type, id}, assign_type}]',
                    'data.start_date' => 'Start date YYYY-MM-DD (nullable)',
                    'data.end_date' => 'End date YYYY-MM-DD (nullable)',
                    'data.product' => 'Coupled product reference (nullable) {id, type: product}',
                ],
            ],
            'list' => [
                'description' => 'Array of material objects (same fields as info)',
                'fields' => [
                    'data' => 'Array of material objects with structure identical to info endpoint',
                ],
            ],
            'update' => [
                'description' => 'Empty response on success (HTTP 204 No Content)',
                'fields' => [],
            ],
        ];
    }
}

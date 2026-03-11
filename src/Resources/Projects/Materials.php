<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Materials extends Resource
{
    protected string $description = 'Manage materials in Teamleader Focus projects';

    // Resource capabilities based on API documentation
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

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

    // Valid billing methods
    protected array $billingMethods = [
        'fixed_price',
        'unit_price',
        'non_billable',
    ];

    // Valid status values
    protected array $statusValues = [
        'to_do',
        'in_progress',
        'on_hold',
        'done',
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'user',
        'team',
    ];

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
    ];

    /**
     * Get the base path for the materials resource
     */
    protected function getBasePath(): string
    {
        return 'projects-v2/materials';
    }

    /**
     * List materials with optional filtering by IDs
     *
     * Note: The only available filter is `ids`. No pagination or sorting is supported.
     *
     * Response fields per item:
     * - id, project {id, type}, group (nullable) {id, type: nextgenProjectGroup}
     * - title, description (nullable), status, billing_method, billing_status
     * - quantity (nullable number): actual quantity used
     * - quantity_estimated (nullable number): estimated quantity
     * - unit_price (nullable) {amount, currency}
     * - unit_cost (nullable) {amount, currency}
     * - unit (nullable) {id, type: priceunit} — null if default unit is used
     * - amount_billed (nullable) {amount, currency}
     * - external_budget (nullable) {amount, currency}
     * - external_budget_spent (nullable) {amount, currency}
     * - internal_budget (nullable) {amount, currency}
     * - price (nullable) {amount, currency}
     * - fixed_price (nullable) {amount, currency}
     * - cost (nullable) {amount, currency}
     * - margin (nullable) {amount, currency}
     * - margin_percentage (nullable number) — null if no "Costs on projects" access
     * - assignees [{assignee: {type, id}, assign_type}]
     * - start_date (nullable string), end_date (nullable string)
     * - product (nullable) {id, type: product}
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        if (! empty($filters)) {
            $params['filter'] = [];

            if (isset($filters['ids']) && is_array($filters['ids'])) {
                $params['filter']['ids'] = $filters['ids'];
            }
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get material information
     *
     * Response fields (identical to list items, see list() docblock):
     * Full details including billing, budget, assignees, product linkage,
     * quantity (actual) and quantity_estimated (planned).
     *
     * - quantity (nullable number): actual quantity used
     * - quantity_estimated (nullable number): estimated quantity at planning phase
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Create a new material
     *
     * Required fields:
     * - project_id (string): Project UUID
     * - title (string): Material title
     *
     * Optional fields:
     * - group_id (string): Group UUID — if omitted, material is not added to a group
     * - after_id (string|null): UUID to position after; null = top; omit = bottom
     * - description (string): Free-text description
     * - billing_method (string): fixed_price|unit_price|non_billable
     * - quantity (number): Actual quantity used
     * - quantity_estimated (number): Estimated quantity
     * - unit_price (object|null): {amount, currency}
     * - unit_cost (object|null): {amount, currency}
     * - unit_id (string): Price unit UUID
     * - fixed_price (object|null): {amount, currency} — for fixed_price billing
     * - external_budget (object|null): {amount, currency}
     * - internal_budget (object|null): {amount, currency}
     * - start_date (string): YYYY-MM-DD
     * - end_date (string): YYYY-MM-DD
     * - product_id (string): Product UUID to couple to this material
     * - assignees (array): [{type: team|user, id: uuid}]
     *
     * Returns HTTP 201 with data.{id, type}
     */
    public function create(array $data): array
    {
        $this->validateMaterialData($data, 'create');

        return $this->api->request('POST', $this->getBasePath().'.create', $data);
    }

    /**
     * Update an existing material
     *
     * Only `id` is required. All other fields are optional.
     * Providing null for a nullable field will clear that value.
     *
     * Updatable fields:
     * - title (string)
     * - description (string|null)
     * - status (string): to_do|in_progress|on_hold|done
     * - billing_method (string): fixed_price|unit_price|non_billable
     * - quantity (number|null): Actual quantity used
     * - quantity_estimated (number|null): Estimated quantity
     * - unit_price (object|null): {amount, currency}
     * - unit_cost (object|null): {amount, currency}
     * - unit_id (string|null): Price unit UUID
     * - fixed_price (object|null): {amount, currency}
     * - external_budget (object|null): {amount, currency}
     * - internal_budget (object|null): {amount, currency}
     * - start_date (string|null): YYYY-MM-DD
     * - end_date (string|null): YYYY-MM-DD
     * - product_id (string|null): Product UUID
     *
     * Returns HTTP 204 (no body)
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateMaterialData($data, 'update');

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Get materials by specific IDs
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Validate material data for create/update operations
     *
     * @param  string  $operation  'create' or 'update'
     *
     * @throws InvalidArgumentException
     */
    protected function validateMaterialData(array $data, string $operation = 'create'): void
    {
        // Required fields for create
        if ($operation === 'create') {
            if (empty($data['project_id'])) {
                throw new InvalidArgumentException('project_id is required for creating a material');
            }
            if (empty($data['title'])) {
                throw new InvalidArgumentException('title is required for creating a material');
            }
        }

        // Required field for update
        if ($operation === 'update') {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('id is required for updating a material');
            }
        }

        // Validate billing_method if provided
        if (isset($data['billing_method']) && ! in_array($data['billing_method'], $this->billingMethods)) {
            throw new InvalidArgumentException(
                'Invalid billing_method. Must be one of: '.implode(', ', $this->billingMethods)
            );
        }

        // Validate status if provided
        if (isset($data['status']) && ! in_array($data['status'], $this->statusValues)) {
            throw new InvalidArgumentException(
                'Invalid status. Must be one of: '.implode(', ', $this->statusValues)
            );
        }

        // Validate assignees structure if provided
        if (isset($data['assignees']) && is_array($data['assignees'])) {
            foreach ($data['assignees'] as $assignee) {
                if (! isset($assignee['type']) || ! in_array($assignee['type'], $this->assigneeTypes)) {
                    throw new InvalidArgumentException(
                        'Invalid assignee type. Must be one of: '.implode(', ', $this->assigneeTypes)
                    );
                }
                if (! isset($assignee['id'])) {
                    throw new InvalidArgumentException('Assignee must have an id');
                }
            }
        }

        // Validate monetary amounts have proper structure
        $monetaryFields = ['unit_price', 'unit_cost', 'fixed_price', 'external_budget', 'internal_budget'];
        foreach ($monetaryFields as $field) {
            if (isset($data[$field]) && ! is_null($data[$field])) {
                if (! isset($data[$field]['amount']) || ! isset($data[$field]['currency'])) {
                    throw new InvalidArgumentException(
                        "{$field} must contain 'amount' and 'currency' fields"
                    );
                }
            }
        }
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
                    'data.billing_method' => 'Billing method (fixed_price, unit_price, non_billable)',
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

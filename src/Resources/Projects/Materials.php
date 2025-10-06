<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Materials extends Resource
{
    protected string $description = 'Manage materials in Teamleader Focus projects';

    // Resource capabilities - Materials support full CRUD operations plus special operations
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false; // No pagination mentioned in API docs
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = true;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'ids' => 'Array of material UUIDs'
    ];

    // Valid billing methods
    protected array $billingMethods = [
        'fixed_price',
        'unit_price',
        'non_billable'
    ];

    // Valid status values
    protected array $statusValues = [
        'to_do',
        'in_progress',
        'on_hold',
        'done'
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'user',
        'team'
    ];

    // Usage examples specific to materials
    protected array $usageExamples = [
        'create_material' => [
            'description' => 'Create a new material with unit pricing',
            'code' => '$material = $teamleader->materials()->create([
                "project_id" => "49b403be-a32e-0901-9b1c-25214f9027c6",
                "title" => "WD-40 Multi-Use Product",
                "description" => "Industrial size lubricant",
                "billing_method" => "unit_price",
                "quantity" => 10,
                "unit_price" => [
                    "amount" => 25.50,
                    "currency" => "EUR"
                ]
            ]);'
        ],
        'update_material' => [
            'description' => 'Update an existing material',
            'code' => '$material = $teamleader->materials()->update(
                "material-uuid",
                [
                    "title" => "Updated Material Name",
                    "status" => "in_progress",
                    "quantity" => 15
                ]
            );'
        ],
        'get_material_info' => [
            'description' => 'Get detailed information about a material',
            'code' => '$material = $teamleader->materials()->info("material-uuid");'
        ],
        'list_materials' => [
            'description' => 'List materials by IDs',
            'code' => '$materials = $teamleader->materials()->list([
                "ids" => ["uuid1", "uuid2"]
            ]);'
        ],
        'assign_user' => [
            'description' => 'Assign a user to a material',
            'code' => '$result = $teamleader->materials()->assign(
                "material-uuid",
                "user",
                "user-uuid"
            );'
        ],
        'unassign_user' => [
            'description' => 'Unassign a user from a material',
            'code' => '$result = $teamleader->materials()->unassign(
                "material-uuid",
                "user",
                "user-uuid"
            );'
        ],
        'duplicate_material' => [
            'description' => 'Duplicate an existing material',
            'code' => '$newMaterial = $teamleader->materials()->duplicate("material-uuid");'
        ],
        'delete_material' => [
            'description' => 'Delete a material',
            'code' => '$result = $teamleader->materials()->delete("material-uuid");'
        ]
    ];

    /**
     * Get the base path for the materials resource
     */
    protected function getBasePath(): string
    {
        return 'projects-v2/materials';
    }

    /**
     * List materials with filtering
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Build filter object
        if (!empty($filters)) {
            $params['filter'] = [];

            if (isset($filters['ids']) && is_array($filters['ids'])) {
                $params['filter']['ids'] = $filters['ids'];
            }
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get material information
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.info', [
            'id' => $id
        ]);
    }

    /**
     * Create a new material
     *
     * Required fields: project_id, title
     */
    public function create(array $data): array
    {
        $this->validateMaterialData($data, 'create');
        return $this->api->request('POST', $this->getBasePath() . '.create', $data);
    }

    /**
     * Update an existing material
     *
     * All fields except id are optional. Providing null will clear nullable values.
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;
        $this->validateMaterialData($data, 'update');
        return $this->api->request('POST', $this->getBasePath() . '.update', $data);
    }

    /**
     * Delete a material
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.delete', [
            'id' => $id
        ]);
    }

    /**
     * Assign a user or team to a material
     *
     * @param string $materialId Material UUID
     * @param string $assigneeType Type of assignee ('user' or 'team')
     * @param string $assigneeId Assignee UUID
     * @return array
     */
    public function assign(string $materialId, string $assigneeType, string $assigneeId): array
    {
        $this->validateAssigneeType($assigneeType);

        return $this->api->request('POST', $this->getBasePath() . '.assign', [
            'id' => $materialId,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId
            ]
        ]);
    }

    /**
     * Unassign a user or team from a material
     *
     * @param string $materialId Material UUID
     * @param string $assigneeType Type of assignee ('user' or 'team')
     * @param string $assigneeId Assignee UUID
     * @return array
     */
    public function unassign(string $materialId, string $assigneeType, string $assigneeId): array
    {
        $this->validateAssigneeType($assigneeType);

        return $this->api->request('POST', $this->getBasePath() . '.unassign', [
            'id' => $materialId,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId
            ]
        ]);
    }

    /**
     * Duplicate a material
     *
     * @param string $originId The UUID of the material to duplicate
     * @return array
     */
    public function duplicate(string $originId): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.duplicate', [
            'origin_id' => $originId
        ]);
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
     * @param array $data
     * @param string $operation 'create' or 'update'
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
        if (isset($data['billing_method']) && !in_array($data['billing_method'], $this->billingMethods)) {
            throw new InvalidArgumentException(
                'Invalid billing_method. Must be one of: ' . implode(', ', $this->billingMethods)
            );
        }

        // Validate status if provided
        if (isset($data['status']) && !in_array($data['status'], $this->statusValues)) {
            throw new InvalidArgumentException(
                'Invalid status. Must be one of: ' . implode(', ', $this->statusValues)
            );
        }

        // Validate quantity can only be provided with unit_price billing method
        if (isset($data['quantity']) && isset($data['billing_method']) && $data['billing_method'] !== 'unit_price') {
            throw new InvalidArgumentException(
                'quantity can only be provided when billing_method is unit_price'
            );
        }

        // Validate assignees structure if provided
        if (isset($data['assignees']) && is_array($data['assignees'])) {
            foreach ($data['assignees'] as $assignee) {
                if (!isset($assignee['type']) || !in_array($assignee['type'], $this->assigneeTypes)) {
                    throw new InvalidArgumentException(
                        'Invalid assignee type. Must be one of: ' . implode(', ', $this->assigneeTypes)
                    );
                }
                if (!isset($assignee['id'])) {
                    throw new InvalidArgumentException('Assignee must have an id');
                }
            }
        }

        // Validate monetary amounts have proper structure
        $monetaryFields = ['unit_price', 'unit_cost', 'fixed_price', 'external_budget', 'internal_budget'];
        foreach ($monetaryFields as $field) {
            if (isset($data[$field]) && !is_null($data[$field])) {
                if (!isset($data[$field]['amount']) || !isset($data[$field]['currency'])) {
                    throw new InvalidArgumentException(
                        "{$field} must contain 'amount' and 'currency' fields"
                    );
                }
            }
        }
    }

    /**
     * Validate assignee type
     *
     * @param string $type
     * @throws InvalidArgumentException
     */
    protected function validateAssigneeType(string $type): void
    {
        if (!in_array($type, $this->assigneeTypes)) {
            throw new InvalidArgumentException(
                'Invalid assignee type. Must be one of: ' . implode(', ', $this->assigneeTypes)
            );
        }
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'create' => [
                'description' => 'Response contains the created material ID and type',
                'fields' => [
                    'data.id' => 'UUID of the created material',
                    'data.type' => 'Resource type (always "material")'
                ]
            ],
            'info' => [
                'description' => 'Complete material information',
                'fields' => [
                    'data.id' => 'Material UUID',
                    'data.project' => 'Project reference',
                    'data.group' => 'Group reference (nullable)',
                    'data.title' => 'Material title',
                    'data.description' => 'Material description (nullable)',
                    'data.status' => 'Material status (to_do, in_progress, on_hold, done)',
                    'data.billing_method' => 'Billing method (fixed_price, unit_price, non_billable)',
                    'data.billing_status' => 'Billing status',
                    'data.quantity' => 'Quantity (nullable)',
                    'data.unit_price' => 'Unit price (nullable)',
                    'data.unit_cost' => 'Unit cost (nullable)',
                    'data.unit' => 'Unit of measure (nullable)',
                    'data.fixed_price' => 'Fixed price (nullable)',
                    'data.external_budget' => 'External budget (nullable)',
                    'data.internal_budget' => 'Internal budget (nullable)',
                    'data.price' => 'Calculated price (nullable)',
                    'data.cost' => 'Calculated cost (nullable)',
                    'data.margin' => 'Calculated margin (nullable)',
                    'data.assignees' => 'Array of assigned users/teams',
                    'data.start_date' => 'Start date (nullable)',
                    'data.end_date' => 'End date (nullable)',
                    'data.product' => 'Associated product (nullable)'
                ]
            ],
            'list' => [
                'description' => 'Array of materials',
                'fields' => [
                    'data' => 'Array of material objects with structure similar to info endpoint'
                ]
            ]
        ];
    }
}

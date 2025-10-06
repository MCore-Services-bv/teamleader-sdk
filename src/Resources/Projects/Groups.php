<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Groups extends Resource
{
    protected string $description = 'Manage project groups in Teamleader Focus (New Projects API)';

    // Resource capabilities - Groups support most operations
    protected bool $supportsCreation = true;
    protected bool $supportsUpdate = true;
    protected bool $supportsDeletion = true;
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
        'ids' => 'Array of group UUIDs to filter by',
        'project_id' => 'Filter groups by project UUID',
    ];

    // Available billing methods
    protected array $billingMethods = [
        'time_and_materials',
        'fixed_price',
        'parent_fixed_price',
        'non_billable',
    ];

    // Available billing statuses
    protected array $billingStatuses = [
        'not_billable',
        'not_billed',
        'partially_billed',
        'fully_billed',
    ];

    // Available assignee types
    protected array $assigneeTypes = [
        'team',
        'user',
    ];

    // Available delete strategies
    protected array $deleteStrategies = [
        'ungroup_tasks_and_materials',
        'delete_tasks_and_materials',
        'delete_tasks_materials_and_unbilled_timetrackings',
    ];

    // Available update strategies for billing method
    protected array $updateStrategies = [
        'none',
        'cascade',
    ];

    // Usage examples specific to project groups
    protected array $usageExamples = [
        'list_for_project' => [
            'description' => 'Get all groups for a specific project',
            'code' => '$groups = $teamleader->groups()->forProject("project-uuid");'
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
            ]);'
        ],
        'update_group' => [
            'description' => 'Update a project group',
            'code' => '$group = $teamleader->groups()->update("group-uuid", [
                "title" => "Phase 1: Design & Planning",
                "start_date" => "2023-01-18",
                "end_date" => "2023-03-22"
            ]);'
        ],
        'assign_user' => [
            'description' => 'Assign a user to a group',
            'code' => '$result = $teamleader->groups()->assign("group-uuid", "user", "user-uuid");'
        ],
        'duplicate_group' => [
            'description' => 'Duplicate a group',
            'code' => '$newGroup = $teamleader->groups()->duplicate("origin-group-uuid");'
        ],
        'delete_group' => [
            'description' => 'Delete a group',
            'code' => '$result = $teamleader->groups()->delete("group-uuid", "ungroup_tasks_and_materials");'
        ],
    ];

    /**
     * Get the base path for the project groups resource
     */
    protected function getBasePath(): string
    {
        return 'projectGroups';
    }

    /**
     * List project groups with filtering
     *
     * @param array $filters Filters to apply (ids, project_id)
     * @param array $options Additional options (not used for this endpoint)
     * @return array
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (!empty($filters)) {
            $params['filter'] = $this->buildFilters($filters);
        }

        return $this->api->request('POST', $this->getBasePath() . '.list', $params);
    }

    /**
     * Get all groups for a specific project
     *
     * @param string $projectId Project UUID
     * @return array
     */
    public function forProject(string $projectId): array
    {
        return $this->list(['project_id' => $projectId]);
    }

    /**
     * Get project groups by IDs
     *
     * @param array $ids Array of group UUIDs
     * @return array
     */
    public function byIds(array $ids): array
    {
        return $this->list(['ids' => $ids]);
    }

    /**
     * Get detailed information about a specific group
     *
     * @param string $id Group UUID
     * @param mixed $includes Not used for groups (included for parent compatibility)
     * @return array
     */
    public function info($id, $includes = null): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.info', ['id' => $id]);
    }

    /**
     * Create a new project group
     *
     * @param array $data Group data
     * @return array
     */
    public function create(array $data): array
    {
        $validatedData = $this->validateCreateData($data);
        return $this->api->request('POST', $this->getBasePath() . '.create', $validatedData);
    }

    /**
     * Update a project group
     *
     * @param string $id Group UUID
     * @param array $data Data to update
     * @return array
     */
    public function update($id, array $data)
    {
        $data['id'] = $id;
        $validatedData = $this->validateUpdateData($data);
        return $this->api->request('POST', $this->getBasePath() . '.update', $validatedData);
    }

    /**
     * Delete a project group
     *
     * @param string $id Group UUID
     * @param string ...$additionalParams First param should be delete strategy
     * @return array
     */
    public function delete($id, ...$additionalParams): array
    {
        // Get delete strategy from first additional param, default if not provided
        $deleteStrategy = $additionalParams[0] ?? 'ungroup_tasks_and_materials';

        if (!in_array($deleteStrategy, $this->deleteStrategies)) {
            throw new InvalidArgumentException(
                "Invalid delete strategy. Must be one of: " . implode(', ', $this->deleteStrategies)
            );
        }

        return $this->api->request('POST', $this->getBasePath() . '.delete', [
            'id' => $id,
            'delete_strategy' => $deleteStrategy
        ]);
    }

    /**
     * Duplicate a project group and its entities (without time trackings)
     *
     * @param string $originId The ID of the group to duplicate
     * @return array
     */
    public function duplicate(string $originId): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.duplicate', [
            'origin_id' => $originId
        ]);
    }

    /**
     * Assign a user or team to a group
     *
     * @param string $groupId Group UUID
     * @param string $assigneeType Type of assignee ('user' or 'team')
     * @param string $assigneeId UUID of the user or team
     * @return array
     */
    public function assign(string $groupId, string $assigneeType, string $assigneeId): array
    {
        $this->validateAssigneeType($assigneeType);

        return $this->api->request('POST', $this->getBasePath() . '.assign', [
            'id' => $groupId,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId
            ]
        ]);
    }

    /**
     * Unassign a user or team from a group
     *
     * @param string $groupId Group UUID
     * @param string $assigneeType Type of assignee ('user' or 'team')
     * @param string $assigneeId UUID of the user or team
     * @return array
     */
    public function unassign(string $groupId, string $assigneeType, string $assigneeId): array
    {
        $this->validateAssigneeType($assigneeType);

        return $this->api->request('POST', $this->getBasePath() . '.unassign', [
            'id' => $groupId,
            'assignee' => [
                'type' => $assigneeType,
                'id' => $assigneeId
            ]
        ]);
    }

    /**
     * Convenience method to assign a user to a group
     *
     * @param string $groupId Group UUID
     * @param string $userId User UUID
     * @return array
     */
    public function assignUser(string $groupId, string $userId): array
    {
        return $this->assign($groupId, 'user', $userId);
    }

    /**
     * Convenience method to assign a team to a group
     *
     * @param string $groupId Group UUID
     * @param string $teamId Team UUID
     * @return array
     */
    public function assignTeam(string $groupId, string $teamId): array
    {
        return $this->assign($groupId, 'team', $teamId);
    }

    /**
     * Convenience method to unassign a user from a group
     *
     * @param string $groupId Group UUID
     * @param string $userId User UUID
     * @return array
     */
    public function unassignUser(string $groupId, string $userId): array
    {
        return $this->unassign($groupId, 'user', $userId);
    }

    /**
     * Convenience method to unassign a team from a group
     *
     * @param string $groupId Group UUID
     * @param string $teamId Team UUID
     * @return array
     */
    public function unassignTeam(string $groupId, string $teamId): array
    {
        return $this->unassign($groupId, 'team', $teamId);
    }

    /**
     * Build filters array for the API request
     *
     * @param array $filters
     * @return array
     */
    protected function buildFilters(array $filters): array
    {
        $apiFilters = [];

        // Handle ids filter
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $apiFilters['ids'] = $filters['ids'];
        }

        // Handle project_id filter
        if (isset($filters['project_id'])) {
            $apiFilters['project_id'] = $filters['project_id'];
        }

        return $apiFilters;
    }

    /**
     * Validate data for group creation
     *
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): array
    {
        // Required fields
        if (empty($data['project_id'])) {
            throw new InvalidArgumentException('project_id is required');
        }

        if (empty($data['title'])) {
            throw new InvalidArgumentException('title is required');
        }

        // Validate billing method if provided
        if (isset($data['billing_method']) && !in_array($data['billing_method'], $this->billingMethods)) {
            throw new InvalidArgumentException(
                "Invalid billing_method. Must be one of: " . implode(', ', $this->billingMethods)
            );
        }

        // Validate color if provided
        if (isset($data['color'])) {
            $this->validateColor($data['color']);
        }

        // Validate dates if provided
        if (isset($data['start_date'])) {
            $this->validateDate($data['start_date'], 'start_date');
        }

        if (isset($data['end_date'])) {
            $this->validateDate($data['end_date'], 'end_date');
        }

        return $data;
    }

    /**
     * Validate data for group update
     *
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    protected function validateUpdateData(array $data): array
    {
        // Required: id
        if (empty($data['id'])) {
            throw new InvalidArgumentException('id is required for update');
        }

        // Validate billing method if provided
        if (isset($data['billing_method'])) {
            if (!is_array($data['billing_method'])) {
                throw new InvalidArgumentException('billing_method must be an object with value and update_strategy');
            }

            if (empty($data['billing_method']['value']) ||
                !in_array($data['billing_method']['value'], $this->billingMethods)) {
                throw new InvalidArgumentException(
                    "Invalid billing_method value. Must be one of: " . implode(', ', $this->billingMethods)
                );
            }

            if (empty($data['billing_method']['update_strategy']) ||
                !in_array($data['billing_method']['update_strategy'], $this->updateStrategies)) {
                throw new InvalidArgumentException(
                    "Invalid update_strategy. Must be one of: " . implode(', ', $this->updateStrategies)
                );
            }
        }

        // Validate color if provided
        if (isset($data['color'])) {
            $this->validateColor($data['color']);
        }

        // Validate dates if provided
        if (isset($data['start_date'])) {
            $this->validateDate($data['start_date'], 'start_date');
        }

        if (isset($data['end_date'])) {
            $this->validateDate($data['end_date'], 'end_date');
        }

        return $data;
    }

    /**
     * Validate color format
     *
     * @param string $color
     * @throws InvalidArgumentException
     */
    protected function validateColor(string $color): void
    {
        $validColors = [
            '#00B2B2', '#008A8C', '#992600', '#ED9E00', '#D157D3',
            '#A400B2', '#0071F2', '#004DA6', '#64788F', '#C0C0C4',
            '#82828C', '#1A1C20'
        ];

        if (!in_array($color, $validColors)) {
            throw new InvalidArgumentException(
                "Invalid color. Must be one of: " . implode(', ', $validColors)
            );
        }
    }

    /**
     * Validate date format
     *
     * @param string $date
     * @param string $fieldName
     * @throws InvalidArgumentException
     */
    protected function validateDate(string $date, string $fieldName): void
    {
        $parsed = date_parse($date);

        if ($parsed === false || $parsed['error_count'] > 0 || $parsed['warning_count'] > 0) {
            throw new InvalidArgumentException(
                "{$fieldName} must be a valid date in format YYYY-MM-DD"
            );
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
                "Invalid assignee type. Must be one of: " . implode(', ', $this->assigneeTypes)
            );
        }
    }

    /**
     * Get available billing methods
     *
     * @return array
     */
    public function getAvailableBillingMethods(): array
    {
        return $this->billingMethods;
    }

    /**
     * Get available delete strategies
     *
     * @return array
     */
    public function getAvailableDeleteStrategies(): array
    {
        return $this->deleteStrategies;
    }

    /**
     * Get available assignee types
     *
     * @return array
     */
    public function getAvailableAssigneeTypes(): array
    {
        return $this->assigneeTypes;
    }
}

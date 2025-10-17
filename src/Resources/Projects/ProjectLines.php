<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class ProjectLines extends Resource
{
    protected string $description = 'Manage project lines (tasks, materials, groups) in Teamleader Focus projects';

    // Resource capabilities - ProjectLines support listing and group management operations
    protected bool $supportsCreation = false; // Use Tasks or Materials resources for creation

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

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
        'project_id' => 'UUID of the project (required for list operation)',
        'filter.types' => 'Array of line types to filter (nextgenTask, nextgenMaterial, nextgenProjectGroup)',
        'filter.assignees' => 'Array of assignee objects to filter by (provide null for unassigned lines)',
    ];

    // Valid line types
    protected array $lineTypes = [
        'nextgenTask',
        'nextgenMaterial',
        'nextgenProjectGroup',
    ];

    // Valid assignee types
    protected array $assigneeTypes = [
        'team',
        'user',
    ];

    // Usage examples specific to project lines
    protected array $usageExamples = [
        'list_all_lines' => [
            'description' => 'Get all lines for a project',
            'code' => '$lines = $teamleader->projectLines()->list([
                "project_id" => "49b403be-a32e-0901-9b1c-25214f9027c6"
            ]);',
        ],
        'filter_by_type' => [
            'description' => 'Get only tasks for a project',
            'code' => '$tasks = $teamleader->projectLines()->list([
                "project_id" => "49b403be-a32e-0901-9b1c-25214f9027c6",
                "filter" => [
                    "types" => ["nextgenTask"]
                ]
            ]);',
        ],
        'filter_by_assignee' => [
            'description' => 'Get lines assigned to specific user',
            'code' => '$lines = $teamleader->projectLines()->list([
                "project_id" => "49b403be-a32e-0901-9b1c-25214f9027c6",
                "filter" => [
                    "assignees" => [
                        [
                            "type" => "user",
                            "id" => "66abace2-62af-0836-a927-fe3f44b9b47b"
                        ]
                    ]
                ]
            ]);',
        ],
        'get_unassigned' => [
            'description' => 'Get unassigned lines',
            'code' => '$unassigned = $teamleader->projectLines()->unassigned("project-uuid");',
        ],
        'add_to_group' => [
            'description' => 'Add a task or material to a group',
            'code' => '$result = $teamleader->projectLines()->addToGroup(
                "a14a464d-320a-49bb-b6ee-b510c7f4f66c",
                "0daf76e6-5141-4fb0-866f-01916a873a38"
            );',
        ],
        'remove_from_group' => [
            'description' => 'Remove a task or material from its current group',
            'code' => '$result = $teamleader->projectLines()->removeFromGroup(
                "a14a464d-320a-49bb-b6ee-b510c7f4f66c"
            );',
        ],
        'fluent_interface' => [
            'description' => 'Use fluent methods for filtering',
            'code' => '$tasks = $teamleader->projectLines()
                ->forProject("project-uuid")
                ->ofType(["nextgenTask"])
                ->assignedTo("user", "user-uuid")
                ->get();',
        ],
    ];

    /**
     * Property to store pending filters for fluent interface
     */
    protected array $pendingFilters = [];

    /**
     * Add an existing task or material to a group
     *
     * @param  string  $lineId  The ID of the task or material (may not be a group)
     * @param  string  $groupId  The ID of the group
     */
    public function addToGroup(string $lineId, string $groupId): array
    {
        $params = [
            'line_id' => $lineId,
            'group_id' => $groupId,
        ];

        return $this->api->request('POST', $this->getBasePath().'.addToGroup', $params);
    }

    /**
     * Get the base path for the project lines resource
     */
    protected function getBasePath(): string
    {
        return 'projects-v2/projectLines';
    }

    /**
     * Remove a task or material from the group it is currently in
     *
     * @param  string  $lineId  The ID of the task or material (may not be a group)
     */
    public function removeFromGroup(string $lineId): array
    {
        $params = [
            'line_id' => $lineId,
        ];

        return $this->api->request('POST', $this->getBasePath().'.removeFromGroup', $params);
    }

    /**
     * Get lines for a specific project (fluent interface starting point)
     *
     * @param  string  $projectId  The project UUID
     * @return static
     */
    public function forProject(string $projectId)
    {
        $this->pendingFilters['project_id'] = $projectId;

        return $this;
    }

    /**
     * Filter by tasks only (fluent interface)
     *
     * @return static
     */
    public function tasksOnly()
    {
        return $this->ofType(['nextgenTask']);
    }

    /**
     * Filter by line types (fluent interface)
     *
     * @param  array  $types  Array of line types
     * @return static
     */
    public function ofType(array $types)
    {
        if (! isset($this->pendingFilters['filter'])) {
            $this->pendingFilters['filter'] = [];
        }
        $this->pendingFilters['filter']['types'] = $types;

        return $this;
    }

    /**
     * Filter by materials only (fluent interface)
     *
     * @return static
     */
    public function materialsOnly()
    {
        return $this->ofType(['nextgenMaterial']);
    }

    /**
     * Filter by groups only (fluent interface)
     *
     * @return static
     */
    public function groupsOnly()
    {
        return $this->ofType(['nextgenProjectGroup']);
    }

    /**
     * Filter by assignee (fluent interface)
     *
     * @param  string  $type  Assignee type (user or team)
     * @param  string  $id  Assignee UUID
     * @return static
     */
    public function assignedTo(string $type, string $id)
    {
        if (! isset($this->pendingFilters['filter'])) {
            $this->pendingFilters['filter'] = [];
        }
        if (! isset($this->pendingFilters['filter']['assignees'])) {
            $this->pendingFilters['filter']['assignees'] = [];
        }

        $this->pendingFilters['filter']['assignees'][] = [
            'type' => $type,
            'id' => $id,
        ];

        return $this;
    }

    /**
     * Get unassigned lines (fluent interface)
     *
     * @param  string|null  $projectId  Optional project ID if not already set
     */
    public function unassigned(?string $projectId = null): array
    {
        $filters = $projectId ? ['project_id' => $projectId] : $this->pendingFilters;

        if (! isset($filters['filter'])) {
            $filters['filter'] = [];
        }
        $filters['filter']['assignees'] = null;

        $this->clearPendingFilters();

        return $this->list($filters);
    }

    /**
     * Clear pending filters
     */
    protected function clearPendingFilters(): void
    {
        $this->pendingFilters = [];
    }

    /**
     * List project lines with filtering
     *
     * @param  array  $filters  Filters containing project_id and optional filter object
     * @param  array  $options  Not used for this endpoint
     *
     * @throws InvalidArgumentException
     */
    public function list(array $filters = [], array $options = []): array
    {
        if (empty($filters['project_id'])) {
            throw new InvalidArgumentException(
                'project_id is required. Use forProject() method or provide project_id in filters.'
            );
        }

        $requestParams = [
            'project_id' => $filters['project_id'],
        ];

        // Build filter object if provided
        if (! empty($filters['filter'])) {
            $requestParams['filter'] = $this->buildFilter($filters['filter']);
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $requestParams);
    }

    /**
     * Build filter object from provided filters
     *
     * @param  array  $filter  Raw filter data
     * @return array Formatted filter object
     */
    protected function buildFilter(array $filter): array
    {
        $formatted = [];

        // Handle types filter
        if (isset($filter['types'])) {
            if (! is_array($filter['types'])) {
                throw new InvalidArgumentException('types must be an array');
            }

            // Validate line types
            foreach ($filter['types'] as $type) {
                if (! in_array($type, $this->lineTypes)) {
                    throw new InvalidArgumentException(
                        "Invalid line type: {$type}. Must be one of: ".implode(', ', $this->lineTypes)
                    );
                }
            }

            $formatted['types'] = $filter['types'];
        }

        // Handle assignees filter
        if (isset($filter['assignees'])) {
            // null means unassigned lines
            if ($filter['assignees'] === null) {
                $formatted['assignees'] = null;
            } elseif (is_array($filter['assignees'])) {
                // Validate assignee structure
                foreach ($filter['assignees'] as $assignee) {
                    if (! isset($assignee['type']) || ! isset($assignee['id'])) {
                        throw new InvalidArgumentException(
                            'Each assignee must have type and id fields'
                        );
                    }

                    if (! in_array($assignee['type'], $this->assigneeTypes)) {
                        throw new InvalidArgumentException(
                            "Invalid assignee type: {$assignee['type']}. Must be one of: ".implode(', ', $this->assigneeTypes)
                        );
                    }
                }

                $formatted['assignees'] = $filter['assignees'];
            } else {
                throw new InvalidArgumentException('assignees must be an array or null');
            }
        }

        return $formatted;
    }

    /**
     * Execute the query with pending filters (fluent interface terminator)
     */
    public function get(): array
    {
        $filters = $this->pendingFilters;
        $this->clearPendingFilters();

        return $this->list($filters);
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'list' => [
                'description' => 'Array of project lines',
                'fields' => [
                    'data' => 'Array of line objects',
                    'data[].line.type' => 'Line type (nextgenTask, nextgenMaterial, nextgenProjectGroup)',
                    'data[].line.id' => 'Line UUID',
                    'data[].group' => 'Group reference (nullable - null if not part of a group)',
                    'data[].group.id' => 'Group UUID',
                    'data[].group.type' => 'Group type (nextgenProjectGroup)',
                ],
            ],
            'addToGroup' => [
                'description' => '204 No Content on success',
                'fields' => [],
            ],
            'removeFromGroup' => [
                'description' => '204 No Content on success',
                'fields' => [],
            ],
        ];
    }
}

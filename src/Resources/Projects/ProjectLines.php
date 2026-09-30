<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

/**
 * The lines of a project — its tasks, materials and groups, in order —
 * `projects-v2/projectLines.*`.
 *
 * Lines are created through {@see ProjectTasks}, {@see Materials} and
 * {@see Groups}; this resource lists them and moves tasks and materials in and
 * out of groups.
 */
class ProjectLines extends Resource
{
    use ValidatesWritePayload;

    /** `filter.types[]` on projectLines.list */
    public const LINE_TYPES = ['nextgenTask', 'nextgenMaterial', 'nextgenProjectGroup'];

    /** `filter.assignees[].type` on projectLines.list */
    public const ASSIGNEE_TYPES = ['team', 'user'];

    protected string $description = 'Manage project lines (tasks, materials, groups) in Teamleader Focus projects';

    protected bool $supportsCreation = false; // Use ProjectTasks, Materials or Groups

    protected bool $supportsUpdate = false;

    protected bool $supportsDeletion = false;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    protected array $availableIncludes = [];

    protected array $defaultIncludes = [];

    /**
     * `project_id` is a required top-level parameter; `types` and `assignees`
     * go into the filter object. All three are passed flat to list().
     *
     * Until v2.2.9 these were documented as `filter.types` / `filter.assignees`
     * and had to be nested under a `filter` key; that form is still accepted.
     */
    protected array $commonFilters = [
        'project_id' => 'UUID of the project (required)',
        'types' => 'Line types: nextgenTask, nextgenMaterial, nextgenProjectGroup',
        'assignees' => 'Assignees [{type: user|team, id}]; a null entry matches unassigned lines',
    ];

    // Kept for backwards compatibility — see the constants above
    protected array $lineTypes = self::LINE_TYPES;

    protected array $assigneeTypes = self::ASSIGNEE_TYPES;

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
                "types" => ["nextgenTask"],
            ]);',
        ],
        'filter_by_assignee' => [
            'description' => 'Get lines assigned to specific user',
            'code' => '$lines = $teamleader->projectLines()->list([
                "project_id" => "49b403be-a32e-0901-9b1c-25214f9027c6",
                "assignees" => [
                    ["type" => "user", "id" => "66abace2-62af-0836-a927-fe3f44b9b47b"],
                ],
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
     * Pending filters for the fluent interface
     */
    protected array $pendingFilters = [];

    protected function getBasePath(): string
    {
        return 'projects-v2/projectLines';
    }

    /**
     * List a project's lines
     *
     *     list(['project_id' => $id])
     *     list(['project_id' => $id, 'types' => ['nextgenTask']])
     *     list(['project_id' => $id, 'assignees' => [['type' => 'user', 'id' => $userId]]])
     *     list(['project_id' => $id, 'assignees' => [null]])   // unassigned lines
     *
     * @param  array  $filters  project_id (required), types, assignees — or the pre-2.2.9
     *                          form with types / assignees nested under `filter`
     * @param  array  $options  None; the endpoint takes no paging, sorting or includes
     *
     * @throws InvalidArgumentException When project_id is missing, or on an unknown key or value
     */
    public function list(array $filters = [], array $options = []): array
    {
        $endpoint = $this->getBasePath().'.list';

        if ($options !== []) {
            throw new InvalidArgumentException(
                "{$endpoint} takes no paging, sorting or includes; got: ".implode(', ', array_keys($options)).'.'
            );
        }

        if (isset($filters['filter'])) {
            if (! is_array($filters['filter'])) {
                throw new InvalidArgumentException('filter must be an array with types and/or assignees.');
            }

            $nested = $filters['filter'];
            unset($filters['filter']);

            $this->rejectUnknownFilters($nested, $endpoint.' filter', ['types', 'assignees']);
            $filters = array_merge($nested, $filters);
        }

        $this->rejectUnknownFilters($filters, $endpoint);

        if (empty($filters['project_id'])) {
            throw new InvalidArgumentException(
                'project_id is required. Use forProject() method or provide project_id in filters.'
            );
        }

        $params = ['project_id' => $filters['project_id']];
        $filter = $this->buildFilter($filters);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Build the filter object from `types` and `assignees`
     *
     * @throws InvalidArgumentException
     */
    protected function buildFilter(array $filter): array
    {
        $endpoint = $this->getBasePath().'.list';
        $formatted = [];

        if (isset($filter['types'])) {
            if (! is_array($filter['types'])) {
                throw new InvalidArgumentException('types must be an array');
            }

            foreach (array_values($filter['types']) as $index => $type) {
                $this->assertEnum($type, self::LINE_TYPES, "filter.types[{$index}]", $endpoint);
            }

            $formatted['types'] = array_values($filter['types']);
        }

        if (array_key_exists('assignees', $filter)) {
            // A bare null — the pre-2.2.9 way of asking for unassigned lines —
            // is the same as a list holding one null entry.
            $assignees = $filter['assignees'] === null ? [null] : $filter['assignees'];

            if (! is_array($assignees)) {
                throw new InvalidArgumentException('assignees must be an array of [type, id] entries or null');
            }

            foreach (array_values($assignees) as $index => $assignee) {
                // "To fetch unassigned lines, provide null instead of the type/id object"
                if ($assignee === null) {
                    continue;
                }

                if (! is_array($assignee) || ! isset($assignee['type'], $assignee['id'])) {
                    throw new InvalidArgumentException('Each assignee must have type and id fields, or be null for unassigned lines');
                }

                $this->assertEnum($assignee['type'], self::ASSIGNEE_TYPES, "filter.assignees[{$index}].type", $endpoint);
            }

            $formatted['assignees'] = array_values($assignees);
        }

        return $formatted;
    }

    /**
     * Add an existing task or material to a group
     *
     * @param  string  $lineId  The ID of the task or material (may not be a group)
     * @param  string  $groupId  The ID of the group
     */
    public function addToGroup(string $lineId, string $groupId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.addToGroup', [
            'line_id' => $lineId,
            'group_id' => $groupId,
        ]);
    }

    /**
     * Remove a task or material from the group it is currently in
     *
     * @param  string  $lineId  The ID of the task or material (may not be a group)
     */
    public function removeFromGroup(string $lineId): array
    {
        return $this->api->request('POST', $this->getBasePath().'.removeFromGroup', [
            'line_id' => $lineId,
        ]);
    }

    // -- fluent interface ------------------------------------------------------

    /**
     * @return static
     */
    public function forProject(string $projectId)
    {
        $this->pendingFilters['project_id'] = $projectId;

        return $this;
    }

    /**
     * @param  array  $types  nextgenTask, nextgenMaterial and/or nextgenProjectGroup
     * @return static
     */
    public function ofType(array $types)
    {
        $this->pendingFilters['types'] = $types;

        return $this;
    }

    /**
     * @return static
     */
    public function tasksOnly()
    {
        return $this->ofType(['nextgenTask']);
    }

    /**
     * @return static
     */
    public function materialsOnly()
    {
        return $this->ofType(['nextgenMaterial']);
    }

    /**
     * @return static
     */
    public function groupsOnly()
    {
        return $this->ofType(['nextgenProjectGroup']);
    }

    /**
     * Add an assignee to match; call more than once to match any of several
     *
     * @param  string  $type  user or team
     * @return static
     */
    public function assignedTo(string $type, string $id)
    {
        $this->pendingFilters['assignees'][] = ['type' => $type, 'id' => $id];

        return $this;
    }

    /**
     * Get lines nobody is assigned to
     *
     * Before v2.2.9 this sent no assignee filter at all and so returned every
     * line: `assignees => null` was dropped by an isset() check. It now sends
     * `assignees: [null]`, as the API documents. Combined with assignedTo(),
     * it returns the unassigned lines plus that assignee's.
     *
     * @param  string|null  $projectId  Optional project ID if not already set
     */
    public function unassigned(?string $projectId = null): array
    {
        $filters = $this->pendingFilters;
        $this->clearPendingFilters();

        if ($projectId !== null) {
            $filters['project_id'] = $projectId;
        }

        $filters['assignees'] = [...($filters['assignees'] ?? []), null];

        return $this->list($filters);
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

    protected function clearPendingFilters(): void
    {
        $this->pendingFilters = [];
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

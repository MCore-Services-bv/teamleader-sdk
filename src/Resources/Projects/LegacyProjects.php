<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class LegacyProjects extends Resource
{
    protected string $description = 'Manage legacy projects in Teamleader Focus';

    // Resource capabilities
    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = true;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters based on API documentation
    protected array $commonFilters = [
        'customer.type' => 'Customer type (contact, company)',
        'customer.id' => 'Customer UUID',
        'status' => 'Project status (active, on_hold, done, cancelled)',
        'participant_id' => 'Filter by participant UUID',
        'term' => 'Search term (searches title or description)',
        'updated_since' => 'ISO 8601 datetime',
    ];

    // Available sort fields
    protected array $availableSortFields = [
        'due_on',
        'title',
        'created_at',
    ];

    // Usage examples specific to legacy projects
    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all projects',
            'code' => '$projects = $teamleader->legacyProjects()->list();',
        ],
        'filter_by_status' => [
            'description' => 'Get active projects',
            'code' => '$projects = $teamleader->legacyProjects()->active();',
        ],
        'create_project' => [
            'description' => 'Create a new project',
            'code' => '$project = $teamleader->legacyProjects()->create([...]);',
        ],
        'close_project' => [
            'description' => 'Close a project',
            'code' => '$result = $teamleader->legacyProjects()->close("project-uuid");',
        ],
    ];

    /**
     * Get detailed information about a specific project
     *
     * @param  string  $id  Project UUID
     * @param  mixed  $includes  Not used for legacy projects
     * @return array
     */
    public function info($id, $includes = null)
    {
        return $this->api->request('POST', $this->getBasePath().'.info', [
            'id' => $id,
        ]);
    }

    /**
     * Get the base path for the legacy projects resource
     */
    protected function getBasePath(): string
    {
        return 'projects';
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
     * Validate create data
     *
     * @throws InvalidArgumentException
     */
    protected function validateCreateData(array $data): void
    {
        // Required fields
        $required = ['title', 'starts_on', 'milestones', 'participants'];

        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required for creating a project");
            }
        }

        // Validate milestones (at least one required)
        if (empty($data['milestones']) || ! is_array($data['milestones'])) {
            throw new InvalidArgumentException('At least one milestone is required');
        }

        // Validate participants (at least one decision maker required)
        if (empty($data['participants']) || ! is_array($data['participants'])) {
            throw new InvalidArgumentException('At least one participant is required');
        }
    }

    /**
     * Update an existing project
     *
     * @param  string  $id  Project UUID
     * @param  array  $data  Project data to update
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
     * @param  mixed  ...$additionalParams  Not used for legacy projects
     */
    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', [
            'id' => $id,
        ]);
    }

    /**
     * Close a project (also closes all phases and tasks)
     *
     * @param  string  $id  Project UUID
     */
    public function close(string $id): array
    {
        return $this->api->request('POST', $this->getBasePath().'.close', [
            'id' => $id,
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
     * Add a participant to a project
     *
     * @param  string  $id  Project UUID
     * @param  array  $participant  Participant data
     * @param  string|null  $role  Participant role (decision_maker, member)
     */
    public function addParticipant(string $id, array $participant, ?string $role = 'member'): array
    {
        $data = [
            'id' => $id,
            'participant' => $participant,
        ];

        if ($role) {
            $data['role'] = $role;
        }

        return $this->api->request('POST', $this->getBasePath().'.addParticipant', $data);
    }

    /**
     * Update a participant's role in a project
     *
     * @param  string  $id  Project UUID
     * @param  array  $participant  Participant data
     * @param  string  $role  New role (decision_maker, member)
     */
    public function updateParticipant(string $id, array $participant, string $role): array
    {
        return $this->api->request('POST', $this->getBasePath().'.updateParticipant', [
            'id' => $id,
            'participant' => $participant,
            'role' => $role,
        ]);
    }

    // Convenience methods

    /**
     * Get active projects
     *
     * @param  array  $options  Additional options
     */
    public function active(array $options = []): array
    {
        return $this->list(['status' => 'active'], $options);
    }

    /**
     * List projects with filtering and sorting
     *
     * @param  array  $filters  Filter parameters
     * @param  array  $options  Pagination and sorting options
     */
    public function list(array $filters = [], array $options = []): array
    {
        $params = [];

        // Apply filters
        if (! empty($filters)) {
            $params['filter'] = [];

            // Customer filter (nested object)
            if (isset($filters['customer'])) {
                $params['filter']['customer'] = $filters['customer'];
            } elseif (isset($filters['customer.type']) && isset($filters['customer.id'])) {
                $params['filter']['customer'] = [
                    'type' => $filters['customer.type'],
                    'id' => $filters['customer.id'],
                ];
            }

            // Status filter
            if (isset($filters['status'])) {
                $params['filter']['status'] = $filters['status'];
            }

            // Participant filter
            if (isset($filters['participant_id'])) {
                $params['filter']['participant_id'] = $filters['participant_id'];
            }

            // Term filter
            if (isset($filters['term'])) {
                $params['filter']['term'] = $filters['term'];
            }

            // Updated since filter
            if (isset($filters['updated_since'])) {
                $params['filter']['updated_since'] = $filters['updated_since'];
            }
        }

        // Apply pagination
        $params['page'] = [
            'size' => $options['page_size'] ?? 20,
            'number' => $options['page_number'] ?? 1,
        ];

        // Apply sorting
        if (isset($options['sort'])) {
            $params['sort'] = $options['sort'];
        } elseif (isset($options['sort_field'])) {
            $params['sort'] = [[
                'field' => $options['sort_field'],
                'order' => $options['sort_order'] ?? 'asc',
            ]];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get projects by status
     *
     * @param  string  $status  Status (active, on_hold, done, cancelled)
     * @param  array  $options  Additional options
     */
    public function byStatus(string $status, array $options = []): array
    {
        return $this->list(['status' => $status], $options);
    }

    /**
     * Get projects for a specific customer
     *
     * @param  string  $customerId  Customer UUID
     * @param  string  $customerType  Customer type (contact, company)
     * @param  array  $options  Additional options
     */
    public function forCustomer(string $customerId, string $customerType = 'company', array $options = []): array
    {
        return $this->list([
            'customer' => [
                'type' => $customerType,
                'id' => $customerId,
            ],
        ], $options);
    }

    /**
     * Get projects for a specific participant
     *
     * @param  string  $participantId  Participant UUID
     * @param  array  $options  Additional options
     */
    public function forParticipant(string $participantId, array $options = []): array
    {
        return $this->list(['participant_id' => $participantId], $options);
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
     * Get projects updated since a specific date
     *
     * @param  string  $datetime  ISO 8601 datetime
     * @param  array  $options  Additional options
     */
    public function updatedSince(string $datetime, array $options = []): array
    {
        return $this->list(['updated_since' => $datetime], $options);
    }
}

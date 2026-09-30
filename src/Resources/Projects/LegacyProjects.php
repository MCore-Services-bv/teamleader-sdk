<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Other\Accounts;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

/**
 * LegacyProjects — the original project system in Teamleader Focus.
 *
 * The names do not line up with the class names; to be explicit:
 *
 *     SDK method   Teamleader::legacyProjects()
 *     API path     projects.*        (the bare path belongs to the OLD system)
 *     Webhooks     project.created, project.updated, project.deleted
 *
 * The current system is {@see Projects}, on the `projects-v2/projects.*` path,
 * with `nextgenProject.*` webhook events. So the class names and the endpoint
 * paths run in opposite directions: `Projects` is the newer class on the longer
 * path, `LegacyProjects` is the older class on the shorter one.
 *
 * **Do not infer which system an account is on from whether a list call returns
 * rows.** Both endpoints answer. Ask directly:
 *
 *     Teamleader::accounts()->getProjectsVersion();      // "projects-v2" or "legacy"
 *     Teamleader::accounts()->isUsingLegacyProjects();   // bool
 *
 * Accounts are migrated to the new system over time;
 * Accounts::getAutoSwitchDate() reports when, if it is scheduled.
 *
 * @see Projects For the current ("nextgen") project system
 * @see Accounts::getProjectsVersion()
 * @see https://developer.focus.teamleader.eu/docs/api/projects-list
 */
class LegacyProjects extends Resource
{
    use ValidatesWritePayload;

    /** Body fields projects.create accepts */
    public const CREATE_FIELDS = [
        'title', 'description', 'starts_on', 'milestones', 'participants',
        'customer', 'purchase_order_number', 'custom_fields',
    ];

    /** Body fields projects.update accepts, besides `id` */
    public const UPDATE_FIELDS = [
        'title', 'description', 'status', 'starts_on', 'customer', 'budget',
        'purchase_order_number', 'custom_fields',
    ];

    /** `status` on projects.update and `filter.status` on projects.list */
    public const STATUSES = ['active', 'on_hold', 'done', 'cancelled'];

    /** `role` on participants */
    public const ROLES = ['decision_maker', 'member'];

    public const CUSTOMER_TYPES = ['contact', 'company'];

    public const CURRENCIES = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP', 'INR', 'ISK',
        'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    protected string $description = 'Manage legacy projects in Teamleader Focus — the original project system (API path projects, webhook events project.*). See Projects for the current system.';

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

    /**
     * Sort fields accepted by projects.list.
     *
     * Verified against @teamleader/focus-api-specification.
     */
    protected array $availableSortFields = [
        'due_on' => 'Sort by project due date',
        'title' => 'Sort by project title',
        'created_at' => 'Sort by creation date',
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
        'check_which_system' => [
            'description' => 'Check whether this account is on the legacy system at all',
            'code' => 'if ($teamleader->accounts()->isUsingLegacyProjects()) {
                $projects = $teamleader->legacyProjects()->list();
            } else {
                $projects = $teamleader->projects()->list();
            }',
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
        $this->assertIncludes($includes, [], 'projects.info');

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

        $this->rejectUnknownFields($data, self::CREATE_FIELDS, 'projects.create');

        // Validate milestones (at least one required)
        if (empty($data['milestones']) || ! is_array($data['milestones'])) {
            throw new InvalidArgumentException('At least one milestone is required');
        }

        foreach (array_values($data['milestones']) as $index => $milestone) {
            foreach (['due_on', 'name', 'responsible_user_id'] as $field) {
                if (empty($milestone[$field])) {
                    throw new InvalidArgumentException("milestones[{$index}].{$field} is required");
                }
            }
        }

        // Validate participants (at least one decision maker required)
        if (empty($data['participants']) || ! is_array($data['participants'])) {
            throw new InvalidArgumentException('At least one participant is required');
        }

        foreach (array_values($data['participants']) as $index => $participant) {
            if (empty($participant['participant']['type']) || empty($participant['participant']['id'])) {
                throw new InvalidArgumentException(
                    "participants[{$index}] must be ['participant' => ['type' => 'user', 'id' => uuid], 'role' => ...]"
                );
            }
        }

        $this->assertItemEnum($data, 'participants', 'role', self::ROLES, 'projects.create');
        $this->validateCustomer($data, 'projects.create');
    }

    /**
     * `customer` is {type: contact|company, id}; null unlinks it on update.
     *
     * @throws InvalidArgumentException
     */
    private function validateCustomer(array $data, string $endpoint): void
    {
        if (! isset($data['customer'])) {
            return;
        }

        if (! is_array($data['customer']) || empty($data['customer']['id']) || ! isset($data['customer']['type'])) {
            throw new InvalidArgumentException("customer on {$endpoint} must be ['type' => contact|company, 'id' => uuid]");
        }

        $this->assertEnum($data['customer']['type'], self::CUSTOMER_TYPES, 'customer.type', $endpoint);
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

        $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], 'projects.update');
        $this->assertEnum($data['status'] ?? null, self::STATUSES, 'status', 'projects.update');
        $this->validateCustomer($data, 'projects.update');

        if (isset($data['budget'])) {
            if (! is_array($data['budget']) || ! isset($data['budget']['amount'], $data['budget']['currency'])) {
                throw new InvalidArgumentException("budget must be ['amount' => number, 'currency' => code]");
            }

            $this->assertEnum($data['budget']['currency'], self::CURRENCIES, 'budget.currency', 'projects.update');
        }

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
        $this->assertEnum($role, self::ROLES, 'role', 'projects.addParticipant');

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
        $this->assertEnum($role, self::ROLES, 'role', 'projects.updateParticipant');

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
            $this->rejectUnknownFilters($filters, 'projects.list', ['customer']);
            $this->assertEnum($filters['status'] ?? null, self::STATUSES, 'filter.status', 'projects.list');

            $params['filter'] = [];

            // Customer filter (nested object)
            if (isset($filters['customer'])) {
                $this->validateCustomer($filters, 'projects.list');
                $params['filter']['customer'] = $filters['customer'];
            } elseif (isset($filters['customer.type']) || isset($filters['customer.id'])) {
                if (! isset($filters['customer.type'], $filters['customer.id'])) {
                    throw new InvalidArgumentException('The customer filter needs both customer.type and customer.id.');
                }

                $this->assertEnum($filters['customer.type'], self::CUSTOMER_TYPES, 'filter.customer.type', 'projects.list');
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
            $params['sort'] = $this->normaliseSort($options['sort'], $options['sort_order'] ?? 'asc');
        } elseif (isset($options['sort_field'])) {
            $params['sort'] = [[
                'field' => $this->validateSortField($options['sort_field']),
                'order' => $this->normaliseSortOrder($options['sort_order'] ?? 'asc'),
            ]];
        }

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    // Sort validation (field and asc/desc order) is FilterTrait's
    // normaliseSort() since v2.2.9. This class used to carry its own copy,
    // which did not check the order.

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
